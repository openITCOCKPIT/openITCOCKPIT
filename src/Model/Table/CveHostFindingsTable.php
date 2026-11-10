<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

declare(strict_types=1);

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * CveHostFindings Model
 *
 * One finding per host, CVE and component (the source package for Linux packages). Every version is its own
 * finding: updating a package resolves the finding of the old version.
 *
 * Life cycle:
 * - A finding that is reported for the first time gets first_seen
 * - Every evaluation that reports the finding again refreshes last_seen
 * - Only a complete evaluation resolves findings that were not reported anymore. Partial or failed
 *   evaluations never resolve anything, missing data is no proof for a fix
 * - A resolved finding that gets reported again is reopened
 *
 * @property \App\Model\Table\CvesTable&\Cake\ORM\Association\BelongsTo $Cves
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 * @property \App\Model\Table\CveDecisionsTable&\Cake\ORM\Association\HasOne $CveDecisions
 *
 * @method \App\Model\Entity\CveHostFinding newEmptyEntity()
 * @method \App\Model\Entity\CveHostFinding newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CveHostFinding> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CveHostFinding get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CveHostFinding patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveHostFinding|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CveHostFinding saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveHostFindingsTable extends Table {

    use PaginationAndScrollIndexTrait;

    public const VIEW_ACTION = 'action';
    public const VIEW_UNFIXED = 'unfixed';
    public const VIEW_ACCEPTED = 'accepted';
    public const VIEW_NOT_AFFECTED = 'not_affected';

    /**
     * Number of rows per query when reading or writing many findings at once
     */
    private const CHUNK_SIZE = 500;

    /**
     * Fields of a finding that the evaluation reports. Everything else is the life cycle.
     */
    private const EVALUATION_FIELDS = [
        'cve_id',
        'component_type',
        'component_name',
        'component_version',
        'is_kernel',
        'is_running',
        'fixed_in',
        'fix_state',
        'not_fixed_yet',
        'update_available',
        'update_version',
        'severity',
        'severity_source',
        'view_name',
        'color',
        'rule_rank',
        'review_required',
        'policy_version',
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_host_findings');
        $this->setDisplayField('component_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Cves', [
            'foreignKey'   => 'cve_id',
            'joinType'     => 'INNER',
            'propertyName' => 'cve',
        ]);

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
        ]);

        $this->hasOne('CveDecisions', [
            'foreignKey' => 'cve_host_finding_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->scalar('finding_hash')
            ->lengthBetween('finding_hash', [64, 64])
            ->requirePresence('finding_hash', 'create')
            ->notEmptyString('finding_hash');

        $validator
            ->integer('host_id')
            ->requirePresence('host_id', 'create')
            ->notEmptyString('host_id');

        $validator
            ->integer('cve_id')
            ->requirePresence('cve_id', 'create')
            ->notEmptyString('cve_id');

        $validator
            ->inList('component_type', ['package', 'os', 'app'])
            ->requirePresence('component_type', 'create')
            ->notEmptyString('component_type');

        $validator
            ->scalar('component_name')
            ->maxLength('component_name', 255)
            ->requirePresence('component_name', 'create')
            ->notEmptyString('component_name');

        $validator
            ->scalar('component_version')
            ->maxLength('component_version', 255)
            ->requirePresence('component_version', 'create')
            ->notEmptyString('component_version');

        $validator
            ->boolean('is_kernel')
            ->notEmptyString('is_kernel');

        $validator
            ->boolean('is_running')
            ->allowEmptyString('is_running');

        $validator
            ->scalar('fixed_in')
            ->maxLength('fixed_in', 255)
            ->allowEmptyString('fixed_in');

        $validator
            ->scalar('fix_state')
            ->maxLength('fix_state', 255)
            ->allowEmptyString('fix_state');

        $validator
            ->boolean('not_fixed_yet')
            ->notEmptyString('not_fixed_yet');

        $validator
            ->boolean('update_available')
            ->notEmptyString('update_available');

        $validator
            ->scalar('update_version')
            ->maxLength('update_version', 255)
            ->allowEmptyString('update_version');

        $validator
            ->integer('severity')
            ->range('severity', [0, 4])
            ->allowEmptyString('severity');

        $validator
            ->scalar('severity_source')
            ->maxLength('severity_source', 64)
            ->allowEmptyString('severity_source');

        $validator
            ->inList('view_name', [self::VIEW_ACTION, self::VIEW_UNFIXED, self::VIEW_ACCEPTED, self::VIEW_NOT_AFFECTED])
            ->requirePresence('view_name', 'create')
            ->notEmptyString('view_name');

        $validator
            ->inList('color', ['red', 'yellow', 'gray'])
            ->requirePresence('color', 'create')
            ->notEmptyString('color');

        $validator
            ->integer('rule_rank')
            ->range('rule_rank', [1, 99])
            ->requirePresence('rule_rank', 'create')
            ->notEmptyString('rule_rank');

        $validator
            ->boolean('review_required')
            ->notEmptyString('review_required');

        $validator
            ->scalar('policy_version')
            ->maxLength('policy_version', 32)
            ->requirePresence('policy_version', 'create')
            ->notEmptyString('policy_version');

        $validator
            ->dateTime('first_seen')
            ->requirePresence('first_seen', 'create')
            ->notEmptyDateTime('first_seen');

        $validator
            ->dateTime('last_seen')
            ->requirePresence('last_seen', 'create')
            ->notEmptyDateTime('last_seen');

        $validator
            ->dateTime('resolved_at')
            ->allowEmptyDateTime('resolved_at');

        $validator
            ->dateTime('reopened_at')
            ->allowEmptyDateTime('reopened_at');

        $validator
            ->nonNegativeInteger('reopen_count')
            ->notEmptyString('reopen_count');

        return $validator;
    }

    /**
     * Unique hash of a finding: host, CVE and component in one version
     *
     * @param int $hostId
     * @param int $cveId Primary key of the CVE in the cves table
     * @param string $componentType
     * @param string $componentName
     * @param string $componentVersion
     * @return string
     */
    public function getFindingHash(int $hostId, int $cveId, string $componentType, string $componentName, string $componentVersion): string {
        return hash('sha256', json_encode(
            [$hostId, $cveId, $componentType, $componentName, $componentVersion],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    /**
     * Stores the findings of one evaluation of a host and maintains the life cycle.
     *
     * @param int $hostId
     * @param array<int, array> $findings All findings of the evaluation with the keys of EVALUATION_FIELDS
     * @param bool $isComplete Only a complete evaluation resolves findings that were not reported anymore
     * @param DateTime $now Time of the evaluation
     * @return array{new: int, reopened: int, resolved: int, open: int}
     */
    public function saveFindingsOfHost(int $hostId, array $findings, bool $isComplete, DateTime $now): array {
        $result = ['new' => 0, 'reopened' => 0, 'resolved' => 0, 'open' => 0];

        $byHash = [];
        foreach ($findings as $finding) {
            $data = array_intersect_key($finding, array_flip(self::EVALUATION_FIELDS));
            $hash = $this->getFindingHash(
                $hostId,
                (int)$data['cve_id'],
                (string)$data['component_type'],
                (string)$data['component_name'],
                (string)$data['component_version']
            );
            $byHash[$hash] = $data;
        }
        $result['open'] = count($byHash);

        $this->getConnection()->transactional(function () use ($hostId, $byHash, $isComplete, $now, &$result) {
            foreach (array_chunk($byHash, self::CHUNK_SIZE, true) as $chunk) {
                /** @var array<string, \App\Model\Entity\CveHostFinding> $existing */
                $existing = $this->find()
                    ->where([
                        'CveHostFindings.host_id'         => $hostId,
                        'CveHostFindings.finding_hash IN' => array_map('strval', array_keys($chunk)),
                    ])
                    ->all()
                    ->indexBy('finding_hash')
                    ->toArray();

                $entities = [];
                foreach ($chunk as $hash => $data) {
                    $hash = (string)$hash;

                    if (!isset($existing[$hash])) {
                        $entities[] = $this->newEntity($data + [
                                'finding_hash' => $hash,
                                'host_id'      => $hostId,
                                'first_seen'   => $now,
                                'last_seen'    => $now,
                                'reopen_count' => 0,
                            ]);
                        $result['new']++;
                        continue;
                    }

                    $entity = $this->patchEntity($existing[$hash], $data);
                    if ($entity->resolved_at !== null) {
                        $entity->resolved_at = null;
                        $entity->reopened_at = $now;
                        $entity->reopen_count++;
                        $result['reopened']++;
                    }
                    // Only findings that changed get written, last_seen of all others is set below with one query
                    if ($entity->isDirty()) {
                        $entity->last_seen = $now;
                        $entities[] = $entity;
                    }
                }

                if (!empty($entities)) {
                    $this->saveManyOrFail($entities);
                }

                if (!empty($existing)) {
                    $this->updateAll(['last_seen' => $now], [
                        'id IN'       => array_values(array_map(fn($entity) => $entity->id, $existing)),
                        'last_seen <' => $now,
                    ]);
                }
            }

            if ($isComplete) {
                // Every finding of this evaluation has last_seen = $now, so all older open findings were not reported anymore
                $result['resolved'] = $this->updateAll(['resolved_at' => $now], [
                    'host_id'        => $hostId,
                    'resolved_at IS' => null,
                    'last_seen <'    => $now,
                ]);
            }
        });

        return $result;
    }

    /**
     * Returns all open findings of a host with the CVE and the decision, as input for the classification.
     *
     * @param int $hostId
     * @return \App\Model\Entity\CveHostFinding[]
     */
    public function getOpenFindingsOfHost(int $hostId): array {
        return $this->find()
            ->contain([
                'Cves',
                'CveDecisions',
            ])
            ->where([
                'CveHostFindings.host_id'        => $hostId,
                'CveHostFindings.resolved_at IS' => null,
            ])
            ->toArray();
    }

    /**
     * Stores a new classification of findings, e.g. because a decision expired. Unchanged findings are skipped.
     *
     * @param array<int, array{view_name: string, color: string, rule_rank: int, review_required: bool, policy_version: string}> $classifications
     *        Indexed by the id of the finding
     * @return int Number of changed findings
     */
    public function saveClassifications(array $classifications): int {
        $changed = 0;

        foreach (array_chunk($classifications, self::CHUNK_SIZE, true) as $chunk) {
            $entities = [];
            foreach ($this->find()->where(['CveHostFindings.id IN' => array_keys($chunk)])->all() as $entity) {
                $entity = $this->patchEntity($entity, $chunk[$entity->id], [
                    'fields' => ['view_name', 'color', 'rule_rank', 'review_required', 'policy_version']
                ]);
                if ($entity->isDirty()) {
                    $entities[] = $entity;
                }
            }

            if (!empty($entities)) {
                $this->saveManyOrFail($entities);
                $changed += count($entities);
            }
        }

        return $changed;
    }

    /**
     * Returns the open findings of a host for the host details, most urgent first.
     *
     * @param int $hostId
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @param string|null $viewName Only findings of this view, e.g. CveHostFindingsTable::VIEW_ACTION
     * @return array
     */
    public function getFindingsOfHost(int $hostId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = [], ?string $viewName = null): array {
        $query = $this->getFindingsQuery($MY_RIGHTS)
            ->where([
                'CveHostFindings.host_id' => $hostId
            ]);

        if ($viewName !== null) {
            $query->where(['CveHostFindings.view_name' => $viewName]);
        }

        return $this->getFindingsResult($query, $GenericFilter, $PaginateOMat, [
            'CveHostFindings.rule_rank' => 'asc',
            'CveHostFindings.severity'  => 'desc',
            'Cves.cvss_score'           => 'desc',
        ]);
    }

    /**
     * Returns the open findings of one CVE on all hosts, for the CVE details.
     *
     * @param string $cveId e.g. CVE-2026-1234
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getFindingsByCveId(string $cveId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getFindingsQuery($MY_RIGHTS)
            ->where([
                'Cves.cve_id' => $cveId
            ]);

        return $this->getFindingsResult($query, $GenericFilter, $PaginateOMat, [
            'CveHostFindings.rule_rank' => 'asc',
            'Hosts.name'                => 'asc',
        ]);
    }

    /**
     * Deletes all findings and decisions of a host, e.g. after the host got deleted.
     *
     * @param int $hostId
     * @return int Number of deleted findings
     */
    public function deleteFindingsOfHost(int $hostId): int {
        return $this->getConnection()->transactional(function () use ($hostId) {
            $this->CveDecisions->deleteAll([
                'cve_host_finding_id IN' => $this->find()->select(['CveHostFindings.id'])->where(['CveHostFindings.host_id' => $hostId])
            ]);
            return $this->deleteAll(['host_id' => $hostId]);
        });
    }

    /**
     * Open findings with CVE, host and decision of enabled hosts
     *
     * @param array $MY_RIGHTS
     * @return SelectQuery
     */
    private function getFindingsQuery(array $MY_RIGHTS): SelectQuery {
        $query = $this->unhydratedFind()
            ->contain([
                'Cves'         => function (SelectQuery $query) {
                    return $query->select([
                        'Cves.id',
                        'Cves.cve_id',
                        'Cves.summary',
                        'Cves.cvss_score',
                        'Cves.cvss_source',
                        'Cves.kev_cisa',
                        'Cves.kev_vulncheck',
                        'Cves.metasploit_count',
                        'Cves.verified_exploit_count',
                        'Cves.poc_exploit_count',
                    ]);
                },
                'Hosts'        => function (SelectQuery $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                    ]);
                },
                'CveDecisions' => function (SelectQuery $query) {
                    return $query->select([
                        'CveDecisions.id',
                        'CveDecisions.cve_host_finding_id',
                        'CveDecisions.state',
                        'CveDecisions.owner',
                        'CveDecisions.reason',
                        'CveDecisions.review_after',
                        'CveDecisions.evidence_url',
                        'CveDecisions.user_id',
                        'CveDecisions.modified',
                    ]);
                },
            ])
            ->where([
                'CveHostFindings.resolved_at IS' => null,
                'Hosts.disabled'                 => 0,
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        return $query;
    }

    /**
     * @param SelectQuery $query
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $defaultOrder Used if the filter does not request another order
     * @return array
     */
    private function getFindingsResult(SelectQuery $query, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat, array $defaultOrder): array {
        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query->orderBy($GenericFilter->getOrderForPaginator($defaultOrder));
        // A host shared with several containers of the user would appear once per container
        $query->groupBy(['CveHostFindings.id']);

        if ($PaginateOMat === null) {
            //Just execute query
            return $query->toArray();
        }

        if ($PaginateOMat->useScroll()) {
            return $this->scrollCake4($query, $PaginateOMat->getHandler());
        }
        return $this->paginateCake4($query, $PaginateOMat->getHandler());
    }
}
