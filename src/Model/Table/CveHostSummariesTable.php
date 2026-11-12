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
use App\Model\Entity\CveHostSummary;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\CvesFilter;
use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;
use itnovum\openITCOCKPIT\Vuln\Policy\HostRules;
use itnovum\openITCOCKPIT\Vuln\Policy\HostTotals;

/**
 * CveHostSummaries Model
 *
 * One row per host with the result of the last evaluation. The host list reads only this table, so it does not
 * have to count the findings of all hosts. A failed evaluation keeps the counters of the last successful one.
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\CveHostSummary newEmptyEntity()
 * @method \App\Model\Entity\CveHostSummary newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CveHostSummary patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveHostSummary|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CveHostSummary saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveHostSummariesTable extends Table {

    use PaginationAndScrollIndexTrait;

    public const STATUS_COMPLETE = 'complete';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_NOT_EVALUABLE = 'not_evaluable';
    public const STATUS_FAILED = 'failed';

    /**
     * Counters of an evaluation, all of them count CVEs
     */
    public const COUNTERS = [
        'critical_count',
        'high_count',
        'medium_count',
        'low_count',
        'unknown_count',
        'open_count',
        'red_count',
        'action_count',
        'kev_count',
        'exploit_count',
        'fixable_count',
        'accepted_count',
        'not_affected_count',
        'in_progress_count',
        'installations',
        'evaluated_installations',
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_host_summaries');
        $this->setDisplayField('status');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
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
            ->integer('host_id')
            ->requirePresence('host_id', 'create')
            ->notEmptyString('host_id');

        $validator
            ->inList('platform', ['linux', 'windows', 'macos'])
            ->requirePresence('platform', 'create')
            ->notEmptyString('platform');

        $validator
            ->inList('status', [self::STATUS_COMPLETE, self::STATUS_PARTIAL, self::STATUS_NOT_EVALUABLE, self::STATUS_FAILED])
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->inList('color', ['red', 'yellow', 'green', 'gray'])
            ->notEmptyString('color');

        $validator
            ->scalar('fingerprint')
            ->lengthBetween('fingerprint', [64, 64])
            ->allowEmptyString('fingerprint');

        $validator
            ->scalar('feed_digest')
            ->maxLength('feed_digest', 255)
            ->allowEmptyString('feed_digest');

        $validator
            ->dateTime('inventory_last_update')
            ->allowEmptyDateTime('inventory_last_update');

        $validator
            ->dateTime('evaluated')
            ->allowEmptyDateTime('evaluated');

        $validator
            ->dateTime('failed')
            ->allowEmptyDateTime('failed');

        $validator
            ->scalar('error_message')
            ->allowEmptyString('error_message');

        foreach (self::COUNTERS as $counter) {
            $validator
                ->nonNegativeInteger($counter)
                ->notEmptyString($counter);
        }

        return $validator;
    }

    /**
     * Stores the result of a successful evaluation of a host.
     *
     * @param int $hostId
     * @param array $data platform, status, color, fingerprint, feed_digest, inventory_last_update and the COUNTERS
     * @param DateTime $now Time of the evaluation
     * @return CveHostSummary
     */
    public function saveSummary(int $hostId, array $data, DateTime $now): CveHostSummary {
        $summary = $this->getSummaryEntity($hostId);

        // host_id first, the evaluation must not move the summary to another host
        $summary = $this->patchEntity($summary, [
                'host_id'       => $hostId,
                'evaluated'     => $now,
                'error_message' => null,
            ] + $data);

        return $this->saveOrFail($summary);
    }

    /**
     * Stores a failed evaluation. The counters of the last successful evaluation stay untouched,
     * so the host list still shows the last known state together with the error.
     * The color follows HostRules: red findings of the last evaluation keep the host red, otherwise it turns gray.
     *
     * @param int $hostId
     * @param string $platform
     * @param string $errorMessage
     * @param DateTime $now
     * @return CveHostSummary
     */
    public function saveFailure(int $hostId, string $platform, string $errorMessage, DateTime $now): CveHostSummary {
        $summary = $this->getSummaryEntity($hostId);

        $totals = new HostTotals();
        $totals->red = (int)$summary->red_count;

        $summary = $this->patchEntity($summary, [
            'host_id'       => $hostId,
            'platform'      => $platform,
            'status'        => self::STATUS_FAILED,
            'color'         => (new HostRules())->color(EvaluationStatus::Failed, $totals)->value,
            'failed'        => $now,
            'error_message' => mb_substr($errorMessage, 0, 10000),
        ]);

        return $this->saveOrFail($summary);
    }

    /**
     * Stores color and counters after a new classification without a new evaluation, e.g. because a decision
     * expired. Status, fingerprint and the time of the evaluation stay.
     *
     * @param int $hostId
     * @param string $color
     * @param array<string, int> $counters The COUNTERS without installations
     * @return void
     */
    public function saveClassificationTotals(int $hostId, string $color, array $counters): void {
        $summary = $this->find()
            ->where(['CveHostSummaries.host_id' => $hostId])
            ->first();
        if ($summary === null) {
            return;
        }

        $data = array_intersect_key($counters, array_flip(self::COUNTERS));
        unset($data['installations'], $data['evaluated_installations']);
        $data['color'] = $color;

        $summary = $this->patchEntity($summary, $data, ['fields' => array_keys($data)]);
        if ($summary->isDirty()) {
            $this->saveOrFail($summary);
        }
    }

    /**
     * Fingerprint and time of the last evaluation. Hosts with an unchanged fingerprint do not need a new evaluation.
     * A failed evaluation keeps the fingerprint of the last successful one, so the host gets evaluated again.
     *
     * @param int $hostId
     * @return array{fingerprint: string|null, evaluated: DateTime|null, status: string}|null null if the host has never been evaluated
     */
    public function getLastEvaluation(int $hostId): ?array {
        return $this->unhydratedFind()
            ->select([
                'CveHostSummaries.fingerprint',
                'CveHostSummaries.evaluated',
                'CveHostSummaries.status',
            ])
            ->where(['CveHostSummaries.host_id' => $hostId])
            ->first();
    }

    /**
     * Ids of hosts that have a summary but do not exist anymore, e.g. because they got deleted
     * while the CVE data was kept.
     *
     * @return int[]
     */
    public function getIdsOfDeletedHosts(): array {
        return $this->unhydratedFind()
            ->select(['CveHostSummaries.host_id'])
            ->leftJoin(['Hosts' => 'hosts'], ['Hosts.id = CveHostSummaries.host_id'])
            ->where(['Hosts.id IS' => null])
            ->all()
            ->extract('host_id')
            ->toList();
    }

    /**
     * Returns the host list, most urgent hosts first.
     *
     * @param CvesFilter $CvesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getCveHostsIndex(CvesFilter $CvesFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->unhydratedFind()
            ->contain([
                'Hosts' => function (SelectQuery $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                    ]);
                }
            ])
            ->where([
                'Hosts.disabled' => 0
            ])
            ->where($CvesFilter->indexFilter());

        $this->filterByRights($query, $MY_RIGHTS);

        $query->orderBy($CvesFilter->getOrderForPaginator([
            'CveHostSummaries.red_count'    => 'desc',
            'CveHostSummaries.action_count' => 'desc',
            'CveHostSummaries.open_count'   => 'desc',
            'Hosts.name'                    => 'asc',
        ]));

        if ($PaginateOMat === null) {
            //Just execute query
            return $query->toArray();
        }

        if ($PaginateOMat->useScroll()) {
            return $this->scrollCake4($query, $PaginateOMat->getHandler());
        }
        return $this->paginateCake4($query, $PaginateOMat->getHandler());
    }

    /**
     * Summary of one host for the head of the host details.
     *
     * @param int $hostId
     * @param array $MY_RIGHTS
     * @return array|null null if the host has never been evaluated or the user may not see it
     */
    public function getSummaryOfHost(int $hostId, array $MY_RIGHTS = []): ?array {
        $query = $this->unhydratedFind()
            ->contain([
                'Hosts' => function (SelectQuery $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                        'Hosts.disabled',
                    ]);
                }
            ])
            ->where([
                'CveHostSummaries.host_id' => $hostId
            ]);
        $this->filterByRights($query, $MY_RIGHTS);

        return $query->first();
    }

    /**
     * Summaries of several hosts, e.g. for an icon in the host list.
     *
     * @param int[] $hostIds
     * @param array $MY_RIGHTS
     * @return array<int, array> Indexed by host id, hosts without evaluation are missing
     */
    public function getSummariesOfHosts(array $hostIds, array $MY_RIGHTS = []): array {
        if (empty($hostIds)) {
            return [];
        }

        $query = $this->unhydratedFind()
            ->select([
                'CveHostSummaries.host_id',
                'CveHostSummaries.platform',
                'CveHostSummaries.status',
                'CveHostSummaries.color',
                'CveHostSummaries.inventory_last_update',
                'CveHostSummaries.evaluated',
                'CveHostSummaries.open_count',
                'CveHostSummaries.action_count',
                'CveHostSummaries.red_count',
                'CveHostSummaries.kev_count',
            ])
            ->innerJoinWith('Hosts')
            ->where([
                'CveHostSummaries.host_id IN' => $hostIds,
                'Hosts.disabled'              => 0,
            ]);
        $this->filterByRights($query, $MY_RIGHTS);

        $summaries = [];
        foreach ($query->toArray() as $row) {
            $summaries[(int)$row['host_id']] = $row;
        }
        return $summaries;
    }

    /**
     * Number of hosts per color and status, e.g. for a dashboard widget.
     *
     * @param array $MY_RIGHTS
     * @return array{colors: array<string, int>, statuses: array<string, int>, hosts: int}
     */
    public function getOverview(array $MY_RIGHTS = []): array {
        $overview = ['colors' => [], 'statuses' => [], 'hosts' => 0];

        foreach (['color' => 'colors', 'status' => 'statuses'] as $field => $key) {
            $query = $this->unhydratedFind();
            $query
                ->select([
                    'value'  => 'CveHostSummaries.' . $field,
                    'amount' => $query->expr('COUNT(DISTINCT CveHostSummaries.host_id)'),
                ])
                ->innerJoinWith('Hosts')
                ->where(['Hosts.disabled' => 0])
                ->groupBy(['CveHostSummaries.' . $field]);
            $this->filterByRights($query, $MY_RIGHTS);

            foreach ($query->toArray() as $row) {
                $overview[$key][(string)$row['value']] = (int)$row['amount'];
            }
        }
        $overview['hosts'] = array_sum($overview['colors']);

        return $overview;
    }

    /**
     * Deletes the summary of a host, e.g. after the host got deleted.
     *
     * @param int $hostId
     * @return bool
     */
    public function deleteSummaryOfHost(int $hostId): bool {
        return $this->deleteAll(['host_id' => $hostId]) > 0;
    }

    /**
     * @param int $hostId
     * @return CveHostSummary
     */
    private function getSummaryEntity(int $hostId): CveHostSummary {
        return $this->find()
            ->where(['CveHostSummaries.host_id' => $hostId])
            ->first() ?? $this->newEmptyEntity();
    }

    /**
     * @param SelectQuery $query
     * @param array $MY_RIGHTS
     * @return void
     */
    private function filterByRights(SelectQuery $query, array $MY_RIGHTS): void {
        if (empty($MY_RIGHTS)) {
            return;
        }

        $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
            'HostsToContainersSharing.host_id = CveHostSummaries.host_id'
        ]);
        $query->where([
            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
        ]);
        $query->groupBy(['CveHostSummaries.id']);
    }
}
