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
use App\Model\Entity\CveHostFinding;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\CvesFilter;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;
use itnovum\openITCOCKPIT\Vuln\Policy\Severity;

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
     * Conditions of one finding for the queries that group findings. Same meaning as in the policy:
     * CveRollup, FindingFacts::isKev(), hasWeaponizedExploit() and hasRemedy().
     */
    private const NEEDS_ACTION = "CveHostFindings.view_name = 'action'";
    private const IS_RED = "CveHostFindings.view_name = 'action' AND CveHostFindings.color = 'red'";
    private const IS_KEV = 'Cves.kev_cisa = 1 OR Cves.kev_vulncheck = 1';
    private const HAS_EXPLOIT = 'Cves.metasploit_count > 0 OR Cves.verified_exploit_count > 0';
    private const HAS_REMEDY = "CveHostFindings.not_fixed_yet = 0 AND (IFNULL(CveHostFindings.fixed_in, '') != '' OR CveHostFindings.update_available = 1)";
    private const IS_IN_PROGRESS = "CveDecisions.state = 'in_progress'";
    private const HAS_USABLE_UPDATE = 'CveHostFindings.update_available = 1 AND CveHostFindings.not_fixed_yet = 0';

    /** Distinct CVEs or hosts whose findings match one of the conditions above */
    private const COUNT_CVES_IF = 'COUNT(DISTINCT CASE WHEN %s THEN CveHostFindings.cve_id END)';
    private const COUNT_HOSTS_IF = 'COUNT(DISTINCT CASE WHEN %s THEN CveHostFindings.host_id END)';
    /** 1 if at least one finding of the group matches */
    private const ANY = 'MAX(CASE WHEN %s THEN 1 ELSE 0 END)';

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
        'rule',
        'reasons',
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
            ->inList('rule', array_column(Rule::cases(), 'value'))
            ->requirePresence('rule', 'create')
            ->notEmptyString('rule');

        $validator
            ->nonNegativeInteger('reasons')
            ->notEmptyString('reasons');

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
                    // A resolved finding is reported again (same CVE, package and version, e.g. after a reinstall
                    // or downgrade): reopen it instead of creating a new one, so its decision and history stay
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
     * Returns all open findings of a host as input for the classification: the fields of the finding,
     * the signals of the CVE (kev_cisa, metasploit_count, ...) and the decision as "decision" (null if none).
     * The rows fit FindingFacts::fromRow() and Decision::fromRow().
     *
     * @param int $hostId
     * @return array<int, array>
     */
    public function getOpenFindingsOfHost(int $hostId): array {
        $rows = $this->unhydratedFind()
            ->select($this)
            // A custom select does not include the fields of contained associations on its own
            ->select($this->CveDecisions)
            ->select([
                'kev_cisa'               => 'Cves.kev_cisa',
                'kev_vulncheck'          => 'Cves.kev_vulncheck',
                'metasploit_count'       => 'Cves.metasploit_count',
                'verified_exploit_count' => 'Cves.verified_exploit_count',
                'poc_exploit_count'      => 'Cves.poc_exploit_count',
            ])
            ->innerJoinWith('Cves')
            ->contain([
                'CveDecisions'
            ])
            ->where([
                'CveHostFindings.host_id'        => $hostId,
                'CveHostFindings.resolved_at IS' => null,
            ])
            ->orderBy(['CveHostFindings.id' => 'asc'])
            ->toArray();

        foreach ($rows as $index => $row) {
            $rows[$index]['decision'] = $row['cve_decision'] ?? null;
            unset($rows[$index]['cve_decision']);
        }

        return $rows;
    }

    /**
     * Returns the decisions of all findings of a host, also of resolved ones, indexed by finding_hash.
     * Used to classify the findings of a new evaluation before they get stored: a finding that comes back
     * keeps its decision.
     *
     * @param int $hostId
     * @return array<string, array> Rows of cve_decisions
     */
    public function getDecisionsOfHost(int $hostId): array {
        $rows = $this->CveDecisions->unhydratedFind()
            ->select($this->CveDecisions)
            ->select(['finding_hash' => 'CveHostFindings.finding_hash'])
            ->innerJoin(
                ['CveHostFindings' => 'cve_host_findings'],
                ['CveHostFindings.id = CveDecisions.cve_host_finding_id']
            )
            ->where(['CveHostFindings.host_id' => $hostId])
            ->toArray();

        $decisions = [];
        foreach ($rows as $row) {
            $decisions[(string)$row['finding_hash']] = $row;
        }

        return $decisions;
    }

    /**
     * Stores a new classification of findings, e.g. because a decision expired. Unchanged findings are skipped.
     *
     * @param array<int, array{view_name: string, rule: string, reasons: int, color: string, rule_rank: int, review_required: bool, policy_version: string}> $classifications
     *        Indexed by the id of the finding
     * @return int Number of changed findings
     */
    public function saveClassifications(array $classifications): int {
        $changed = 0;

        foreach (array_chunk($classifications, self::CHUNK_SIZE, true) as $chunk) {
            $entities = [];
            foreach ($this->find()->where(['CveHostFindings.id IN' => array_keys($chunk)])->all() as $entity) {
                $entity = $this->patchEntity($entity, $chunk[$entity->id], [
                    'fields' => ['view_name', 'rule', 'reasons', 'color', 'rule_rank', 'review_required', 'policy_version']
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
     * Components (package and version) of one host in one view, most urgent first.
     * The CVEs of a component get loaded with getFindingsOfHost() when it is expanded, a kernel can have thousands.
     * All counters count distinct CVEs.
     *
     * @param int $hostId
     * @param string $viewName One of the VIEW_* constants
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getComponentsOfHost(int $hostId, string $viewName, CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getAggregateQuery($MY_RIGHTS);
        $query
            ->select([
                'component_type'    => 'CveHostFindings.component_type',
                'component_name'    => 'CveHostFindings.component_name',
                'component_version' => 'CveHostFindings.component_version',
                'is_kernel'         => $query->func()->max('CveHostFindings.is_kernel'),
                'is_running'        => $query->func()->max('CveHostFindings.is_running'),
                'update_available'  => $query->func()->max('CveHostFindings.update_available'),
                'usable_update'     => $query->expr(sprintf(self::ANY, self::HAS_USABLE_UPDATE)),
                'update_version'    => $query->func()->max('CveHostFindings.update_version')->setReturnType('string'),
                'lowest_rule_rank'  => $query->func()->min('CveHostFindings.rule_rank'),
                'highest_severity'  => $query->func()->max('CveHostFindings.severity'),
                'cve_count'         => $query->expr('COUNT(DISTINCT CveHostFindings.cve_id)'),
                'red_count'         => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_RED)),
                'kev_count'         => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_KEV)),
                'exploit_count'     => $query->expr(sprintf(self::COUNT_CVES_IF, self::HAS_EXPLOIT)),
                'in_progress_count' => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_IN_PROGRESS)),
            ])
            ->where([
                'CveHostFindings.host_id'   => $hostId,
                'CveHostFindings.view_name' => $viewName,
            ])
            ->where($CvesFilter->hostComponentsFilter())
            ->groupBy([
                'CveHostFindings.component_type',
                'CveHostFindings.component_name',
                'CveHostFindings.component_version',
            ])
            ->orderBy($CvesFilter->getOrderForPaginator([
                'lowest_rule_rank' => 'asc',
                'red_count'        => 'desc',
                'highest_severity' => 'desc',
                'cve_count'        => 'desc',
                'component_name'   => 'asc',
            ]));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Findings of one host in one view, most urgent first. With component name and version only the findings
     * of this component, as shown when it is expanded in the host details.
     *
     * @param int $hostId
     * @param string $viewName One of the VIEW_* constants
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @param string|null $componentName
     * @param string|null $componentVersion
     * @return array
     */
    public function getFindingsOfHost(int $hostId, string $viewName, CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = [], ?string $componentName = null, ?string $componentVersion = null): array {
        $query = $this->getFindingsQuery($MY_RIGHTS)
            ->where([
                'CveHostFindings.host_id'   => $hostId,
                'CveHostFindings.view_name' => $viewName,
            ])
            ->where($CvesFilter->hostFindingsFilter());

        if ($componentName !== null) {
            $query->where(['CveHostFindings.component_name' => $componentName]);
        }
        if ($componentVersion !== null) {
            $query->where(['CveHostFindings.component_version' => $componentVersion]);
        }

        $query->orderBy($CvesFilter->getOrderForPaginator([
            'CveHostFindings.rule_rank' => 'asc',
            'CveHostFindings.severity'  => 'desc',
            'Cves.cvss_score'           => 'desc',
            'Cves.cve_id'               => 'asc',
        ]));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Number of open findings and distinct CVEs per view of one host, e.g. for the tabs of the host details.
     *
     * @param int $hostId
     * @return array<string, array{findings: int, cves: int}> Every view, also views without findings
     */
    public function getCountsPerView(int $hostId): array {
        $counts = [];
        foreach ([self::VIEW_ACTION, self::VIEW_UNFIXED, self::VIEW_ACCEPTED, self::VIEW_NOT_AFFECTED] as $viewName) {
            $counts[$viewName] = ['findings' => 0, 'cves' => 0];
        }

        $query = $this->find();
        $query
            ->select([
                'view_name' => 'CveHostFindings.view_name',
                'findings'  => $query->func()->count('*'),
                'cves'      => $query->expr('COUNT(DISTINCT CveHostFindings.cve_id)'),
            ])
            ->where([
                'CveHostFindings.host_id'        => $hostId,
                'CveHostFindings.resolved_at IS' => null,
            ])
            ->groupBy(['CveHostFindings.view_name'])
            ->disableHydration();

        foreach ($query->toArray() as $row) {
            $counts[(string)$row['view_name']] = ['findings' => (int)$row['findings'], 'cves' => (int)$row['cves']];
        }

        return $counts;
    }

    /**
     * One finding with everything the finding details show: the full CVE, the host and the decision.
     * Resolved findings are returned as well, a link to a finding stays valid.
     *
     * @param int $findingId
     * @param array $MY_RIGHTS
     * @return array|null null if the finding does not exist or the user may not see its host
     */
    public function getFindingById(int $findingId, array $MY_RIGHTS = []): ?array {
        $query = $this->unhydratedFind()
            ->contain([
                'Cves'         => function (SelectQuery $query) {
                    return $query->select($this->Cves);
                },
                'Hosts'        => function (SelectQuery $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                        'Hosts.disabled',
                    ]);
                },
                'CveDecisions' => function (SelectQuery $query) {
                    return $this->selectDecision($query);
                },
            ])
            ->where([
                'CveHostFindings.id' => $findingId,
            ]);
        $this->restrictToRights($query, $MY_RIGHTS);

        return $query->first();
    }

    /**
     * Findings with host for a decision of several findings at once, see CveDecisionsTable::decideMany().
     *
     * @param int[] $findingIds
     * @return array<int, CveHostFinding> Indexed by id, resolved findings are not returned
     */
    public function getOpenFindingsByIds(array $findingIds): array {
        if (empty($findingIds)) {
            return [];
        }

        return $this->find()
            ->contain([
                'Hosts' => fn(SelectQuery $query) => $query->select(['Hosts.id', 'Hosts.name', 'Hosts.uuid', 'Hosts.container_id']),
                'Cves'  => fn(SelectQuery $query) => $query->select(['Cves.id', 'Cves.cve_id']),
            ])
            ->where([
                'CveHostFindings.id IN'          => $findingIds,
                'CveHostFindings.resolved_at IS' => null,
            ])
            ->all()
            ->indexBy('id')
            ->toArray();
    }

    /**
     * Open CVEs across all hosts, most urgent first. Not affected findings do not count, as for the hosts.
     *
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getCvesIndex(CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getAggregateQuery($MY_RIGHTS);
        $query
            ->select([
                'id'                     => 'Cves.id',
                'cve_id'                 => 'Cves.cve_id',
                'summary'                => 'Cves.summary',
                'published'              => 'Cves.published',
                'cvss_score'             => 'Cves.cvss_score',
                'cvss_source'            => 'Cves.cvss_source',
                'kev_cisa'               => 'Cves.kev_cisa',
                'kev_vulncheck'          => 'Cves.kev_vulncheck',
                'metasploit_count'       => 'Cves.metasploit_count',
                'verified_exploit_count' => 'Cves.verified_exploit_count',
                'poc_exploit_count'      => 'Cves.poc_exploit_count',
                'highest_severity'       => $query->func()->max('CveHostFindings.severity'),
                'lowest_rule_rank'       => $query->func()->min('CveHostFindings.rule_rank'),
                'host_count'             => $query->expr('COUNT(DISTINCT CveHostFindings.host_id)'),
                'action_host_count'      => $query->expr(sprintf(self::COUNT_HOSTS_IF, self::NEEDS_ACTION)),
                'red_host_count'         => $query->expr(sprintf(self::COUNT_HOSTS_IF, self::IS_RED)),
                'fixable_host_count'     => $query->expr(sprintf(self::COUNT_HOSTS_IF, self::HAS_REMEDY)),
                'finding_count'          => $query->func()->count('*'),
            ])
            ->where([
                'CveHostFindings.view_name !=' => self::VIEW_NOT_AFFECTED,
            ])
            ->where($CvesFilter->cvesFilter())
            // Cves.id is the primary key, the other fields of the CVE depend on it
            ->groupBy(['Cves.id'])
            ->orderBy($CvesFilter->getOrderForPaginator([
                'lowest_rule_rank' => 'asc',
                'red_host_count'   => 'desc',
                'host_count'       => 'desc',
                'cve_id'           => 'desc',
            ]));

        // The aliases cve_id and published are columns of this table with another type (the id of the CVE),
        // without this the CVE ID would be converted to int
        $query->getSelectTypeMap()->setTypes(['cve_id' => 'string', 'published' => 'datetime', 'cvss_score' => 'decimal']);

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Open findings of one CVE on all hosts, for the CVE details.
     *
     * @param string $cveId e.g. CVE-2026-1234
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getFindingsByCveId(string $cveId, CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getFindingsQuery($MY_RIGHTS)
            ->where([
                'Cves.cve_id' => $cveId
            ])
            ->where($CvesFilter->viewFilter())
            ->orderBy($CvesFilter->getOrderForPaginator([
                'CveHostFindings.rule_rank' => 'asc',
                'Hosts.name'                => 'asc',
            ]));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * What to do across all hosts, e.g. "update openssl to 3.0.13-0ubuntu3.6, fixes 12 CVEs on 8 hosts".
     * Only findings that need action. One row per component and target version: hosts with different releases
     * get different updates of the same package.
     *
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getRecommendations(CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getAggregateQuery($MY_RIGHTS);
        $query
            ->select([
                'component_type'    => 'CveHostFindings.component_type',
                'component_name'    => 'CveHostFindings.component_name',
                'is_kernel'         => 'CveHostFindings.is_kernel',
                'update_version'    => 'CveHostFindings.update_version',
                'update_available'  => $query->func()->max('CveHostFindings.update_available'),
                'usable_update'     => $query->expr(sprintf(self::ANY, self::HAS_USABLE_UPDATE)),
                'host_update_count' => $query->expr(sprintf(self::COUNT_HOSTS_IF, self::HAS_USABLE_UPDATE)),
                'lowest_rule_rank'  => $query->func()->min('CveHostFindings.rule_rank'),
                'highest_severity'  => $query->func()->max('CveHostFindings.severity'),
                'cve_count'         => $query->expr('COUNT(DISTINCT CveHostFindings.cve_id)'),
                'red_count'         => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_RED)),
                'kev_count'         => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_KEV)),
                'host_count'        => $query->expr('COUNT(DISTINCT CveHostFindings.host_id)'),
                'version_count'     => $query->expr('COUNT(DISTINCT CveHostFindings.component_version)'),
            ])
            ->where([
                'CveHostFindings.view_name' => self::VIEW_ACTION,
            ])
            ->where($CvesFilter->recommendationsFilter())
            ->groupBy([
                'CveHostFindings.component_type',
                'CveHostFindings.component_name',
                'CveHostFindings.is_kernel',
                'CveHostFindings.update_version',
            ])
            ->orderBy($CvesFilter->getOrderForPaginator([
                'lowest_rule_rank' => 'asc',
                'red_count'        => 'desc',
                'cve_count'        => 'desc',
                'host_count'       => 'desc',
                'component_name'   => 'asc',
            ]));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Affected software across all hosts: one row per component and version with open CVEs.
     *
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getSoftwareIndex(CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getAggregateQuery($MY_RIGHTS);
        $query
            ->select([
                'component_type'    => 'CveHostFindings.component_type',
                'component_name'    => 'CveHostFindings.component_name',
                'component_version' => 'CveHostFindings.component_version',
                'is_kernel'         => $query->func()->max('CveHostFindings.is_kernel'),
                'lowest_rule_rank'  => $query->func()->min('CveHostFindings.rule_rank'),
                'highest_severity'  => $query->func()->max('CveHostFindings.severity'),
                'cve_count'         => $query->expr('COUNT(DISTINCT CveHostFindings.cve_id)'),
                'action_count'      => $query->expr(sprintf(self::COUNT_CVES_IF, self::NEEDS_ACTION)),
                'red_count'         => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_RED)),
                'host_count'        => $query->expr('COUNT(DISTINCT CveHostFindings.host_id)'),
            ])
            ->where([
                'CveHostFindings.view_name !=' => self::VIEW_NOT_AFFECTED,
            ])
            ->where($CvesFilter->softwareFilter())
            ->groupBy([
                'CveHostFindings.component_type',
                'CveHostFindings.component_name',
                'CveHostFindings.component_version',
            ])
            ->orderBy($CvesFilter->getOrderForPaginator([
                'lowest_rule_rank' => 'asc',
                'red_count'        => 'desc',
                'cve_count'        => 'desc',
                'component_name'   => 'asc',
            ]));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Hosts with open CVEs in one component and version.
     *
     * @param string $componentName
     * @param string $componentVersion
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsOfComponent(string $componentName, string $componentVersion, CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->getAggregateQuery($MY_RIGHTS);
        $query
            ->select([
                'host_id'          => 'Hosts.id',
                'host_name'        => 'Hosts.name',
                'host_uuid'        => 'Hosts.uuid',
                'component_type'   => $query->func()->max('CveHostFindings.component_type')->setReturnType('string'),
                'is_kernel'        => $query->func()->max('CveHostFindings.is_kernel'),
                'is_running'       => $query->func()->max('CveHostFindings.is_running'),
                'update_available' => $query->func()->max('CveHostFindings.update_available'),
                'usable_update'    => $query->expr(sprintf(self::ANY, self::HAS_USABLE_UPDATE)),
                'update_version'   => $query->func()->max('CveHostFindings.update_version')->setReturnType('string'),
                'lowest_rule_rank' => $query->func()->min('CveHostFindings.rule_rank'),
                'highest_severity' => $query->func()->max('CveHostFindings.severity'),
                'cve_count'        => $query->expr('COUNT(DISTINCT CveHostFindings.cve_id)'),
                'action_count'     => $query->expr(sprintf(self::COUNT_CVES_IF, self::NEEDS_ACTION)),
                'red_count'        => $query->expr(sprintf(self::COUNT_CVES_IF, self::IS_RED)),
            ])
            ->where([
                'CveHostFindings.component_name'    => $componentName,
                'CveHostFindings.component_version' => $componentVersion,
                'CveHostFindings.view_name !='      => self::VIEW_NOT_AFFECTED,
            ])
            ->where($CvesFilter->softwareFilter())
            ->groupBy(['Hosts.id'])
            ->orderBy($CvesFilter->getOrderForPaginator([
                'lowest_rule_rank' => 'asc',
                'red_count'        => 'desc',
                'host_name'        => 'asc',
            ]));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Counters across all hosts, as HostTotals counts them per host: every CVE counts once, even if it affects
     * many hosts. A CVE is open if at least one of its findings is not "not affected".
     *
     * @param array $MY_RIGHTS
     * @return array{open: int, critical: int, high: int, medium: int, low: int, unknown: int, kev: int, exploit: int, fixable: int, action: int, red: int, in_progress: int}
     */
    public function getCveTotals(array $MY_RIGHTS = []): array {
        $totals = array_fill_keys(['open', 'critical', 'high', 'medium', 'low', 'unknown', 'kev', 'exploit', 'fixable', 'action', 'red', 'in_progress'], 0);

        $query = $this->getAggregateQuery($MY_RIGHTS);
        $query
            ->select([
                'highest_severity' => $query->func()->max('CveHostFindings.severity'),
                'kev'              => $query->expr(sprintf(self::ANY, self::IS_KEV)),
                'exploit'          => $query->expr(sprintf(self::ANY, self::HAS_EXPLOIT)),
                'fixable'          => $query->expr(sprintf(self::ANY, self::HAS_REMEDY)),
                'action'           => $query->expr(sprintf(self::ANY, self::NEEDS_ACTION)),
                'red'              => $query->expr(sprintf(self::ANY, self::IS_RED)),
                'in_progress'      => $query->expr(sprintf(self::ANY, self::IS_IN_PROGRESS)),
            ])
            ->where([
                'CveHostFindings.view_name !=' => self::VIEW_NOT_AFFECTED,
            ])
            ->groupBy(['CveHostFindings.cve_id']);

        foreach ($query->toArray() as $row) {
            $totals['open']++;
            foreach (['kev', 'exploit', 'fixable', 'action', 'red', 'in_progress'] as $flag) {
                $totals[$flag] += (int)$row[$flag];
            }

            // Same buckets as HostTotals: severity "none" (e.g. Debian "unimportant") counts as low
            $severity = $row['highest_severity'] === null ? null : Severity::from((int)$row['highest_severity']);
            $bucket = match ($severity) {
                null => 'unknown',
                Severity::Critical => 'critical',
                Severity::High => 'high',
                Severity::Medium => 'medium',
                Severity::Low, Severity::None => 'low',
            };
            $totals[$bucket]++;
        }

        return $totals;
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
     * Open findings with CVE, host and decision of enabled hosts. Every field of the finding is selected,
     * the rows fit FindingFacts::fromRow() for the recommendation.
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
                        'Cves.published',
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
                    return $this->selectDecision($query);
                },
            ])
            ->where([
                'CveHostFindings.resolved_at IS' => null,
                'Hosts.disabled'                 => 0,
            ]);
        $this->restrictToRights($query, $MY_RIGHTS);

        return $query;
    }

    /**
     * Fields of a decision as the frontend shows them, with the user who made it.
     *
     * @param SelectQuery $query
     * @return SelectQuery
     */
    private function selectDecision(SelectQuery $query): SelectQuery {
        return $query
            ->select([
                'CveDecisions.id',
                'CveDecisions.cve_host_finding_id',
                'CveDecisions.state',
                'CveDecisions.owner',
                'CveDecisions.reason',
                'CveDecisions.review_after',
                'CveDecisions.evidence_url',
                'CveDecisions.severity',
                'CveDecisions.fixed_in',
                'CveDecisions.fix_state',
                'CveDecisions.not_fixed_yet',
                'CveDecisions.policy_version',
                'CveDecisions.user_id',
                'CveDecisions.created',
                'CveDecisions.modified',
            ])
            ->contain([
                'Users' => fn(SelectQuery $query) => $query->select(['Users.id', 'Users.firstname', 'Users.lastname']),
            ]);
    }

    /**
     * Open findings of enabled hosts for queries that group findings, with the CVE and the decision joined.
     * Hydration is disabled, the rows contain the aliases of the select.
     *
     * @param array $MY_RIGHTS
     * @return SelectQuery
     */
    private function getAggregateQuery(array $MY_RIGHTS): SelectQuery {
        $query = $this->find()
            ->innerJoinWith('Cves')
            ->innerJoinWith('Hosts')
            ->leftJoinWith('CveDecisions')
            ->where([
                'CveHostFindings.resolved_at IS' => null,
                'Hosts.disabled'                 => 0,
            ])
            ->disableHydration();
        $this->restrictToRights($query, $MY_RIGHTS);

        return $query;
    }

    /**
     * Only findings of hosts the user may see. A subquery instead of a join: a host shared with several containers
     * of the user would be counted once per container.
     *
     * @param SelectQuery $query
     * @param array $MY_RIGHTS Empty for root users
     * @return void
     */
    private function restrictToRights(SelectQuery $query, array $MY_RIGHTS): void {
        if (empty($MY_RIGHTS)) {
            return;
        }

        $query->where([
            'CveHostFindings.host_id IN' => $this->Hosts->find()
                ->select(['Hosts.id'])
                ->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                    'HostsToContainersSharing.host_id = Hosts.id'
                ])
                ->where(['HostsToContainersSharing.container_id IN' => $MY_RIGHTS])
        ]);
    }

    /**
     * @param SelectQuery $query
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    private function getResult(SelectQuery $query, ?PaginateOMat $PaginateOMat): array {
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
