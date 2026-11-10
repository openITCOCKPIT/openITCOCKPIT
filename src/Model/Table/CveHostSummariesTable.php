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
use itnovum\openITCOCKPIT\Filter\GenericFilter;

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
     * Stores a failed evaluation. The counters and the color of the last successful evaluation stay untouched,
     * so the host list still shows the last known state together with the error.
     *
     * @param int $hostId
     * @param string $platform
     * @param string $errorMessage
     * @param DateTime $now
     * @return CveHostSummary
     */
    public function saveFailure(int $hostId, string $platform, string $errorMessage, DateTime $now): CveHostSummary {
        $summary = $this->getSummaryEntity($hostId);

        $summary = $this->patchEntity($summary, [
            'host_id'       => $hostId,
            'platform'      => $platform,
            'status'        => self::STATUS_FAILED,
            'failed'        => $now,
            'error_message' => mb_substr($errorMessage, 0, 10000),
        ]);

        return $this->saveOrFail($summary);
    }

    /**
     * Fingerprint of the last successful evaluation. Hosts with an unchanged fingerprint do not need a new evaluation.
     *
     * @param int $hostId
     * @return string|null
     */
    public function getFingerprint(int $hostId): ?string {
        $summary = $this->unhydratedFind()
            ->select(['fingerprint'])
            ->where(['CveHostSummaries.host_id' => $hostId])
            ->first();

        return $summary['fingerprint'] ?? null;
    }

    /**
     * Returns the host list, most urgent hosts first.
     *
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsIndex(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
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
            ]);

        $this->filterByRights($query, $MY_RIGHTS);

        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query->orderBy($GenericFilter->getOrderForPaginator([
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
