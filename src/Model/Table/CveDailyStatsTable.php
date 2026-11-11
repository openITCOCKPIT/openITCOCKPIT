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

use App\Model\Entity\CveDailyStat;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CveDailyStats Model
 *
 * Values per host and day for the trend. Several evaluations on one day update the same row: the counters show
 * the last state of the day, new and resolved findings add up.
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\CveDailyStat newEmptyEntity()
 * @method \App\Model\Entity\CveDailyStat patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveDailyStat saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class CveDailyStatsTable extends Table {

    /**
     * Counters that show the state at the end of the day
     */
    private const STATE_COUNTERS = ['open_count', 'action_count', 'red_count', 'kev_count'];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_daily_stats');
        $this->setDisplayField('stat_date');
        $this->setPrimaryKey('id');

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
            ->date('stat_date')
            ->requirePresence('stat_date', 'create')
            ->notEmptyDate('stat_date');

        $validator
            ->integer('host_id')
            ->requirePresence('host_id', 'create')
            ->notEmptyString('host_id');

        foreach ([...self::STATE_COUNTERS, 'new_count', 'resolved_count'] as $counter) {
            $validator
                ->nonNegativeInteger($counter)
                ->notEmptyString($counter);
        }

        return $validator;
    }

    /**
     * Every evaluation and every daily classification of a host writes the values of the day. A missing row means
     * the host has not been classified on this day yet, e.g. a decision that ends today has not been applied.
     *
     * @param int $hostId
     * @param Date $date
     * @return bool
     */
    public function hasStatsOfDay(int $hostId, Date $date): bool {
        return $this->exists([
            'CveDailyStats.host_id'   => $hostId,
            'CveDailyStats.stat_date' => $date,
        ]);
    }

    /**
     * Stores the values of an evaluation for the given day.
     *
     * @param int $hostId
     * @param Date $date
     * @param array{open_count: int, action_count: int, red_count: int, kev_count: int} $counters State after the evaluation
     * @param int $newFindings New and reopened findings of this evaluation
     * @param int $resolvedFindings Resolved findings of this evaluation
     * @return CveDailyStat
     */
    public function saveDailyStats(int $hostId, Date $date, array $counters, int $newFindings, int $resolvedFindings): CveDailyStat {
        /** @var CveDailyStat|null $stat */
        $stat = $this->find()
            ->where([
                'CveDailyStats.stat_date' => $date,
                'CveDailyStats.host_id'   => $hostId,
            ])
            ->first();

        $data = array_intersect_key($counters, array_flip(self::STATE_COUNTERS)) + [
                'stat_date'      => $date,
                'host_id'        => $hostId,
                'new_count'      => ($stat->new_count ?? 0) + $newFindings,
                'resolved_count' => ($stat->resolved_count ?? 0) + $resolvedFindings,
            ];

        if ($stat === null) {
            $stat = $this->newEntity($data);
        } else {
            $stat = $this->patchEntity($stat, $data);
        }

        return $this->saveOrFail($stat);
    }

    /**
     * Returns the sum of all enabled hosts per day.
     *
     * @param Date $from
     * @param Date $to
     * @param array $MY_RIGHTS
     * @param int[] $hostIds Only these hosts, all hosts if empty
     * @return array<int, array{stat_date: string, hosts: int, open_count: int, action_count: int, red_count: int, kev_count: int, new_count: int, resolved_count: int}>
     */
    public function getTrend(Date $from, Date $to, array $MY_RIGHTS = [], array $hostIds = []): array {
        $query = $this->unhydratedFind();
        $fields = ['stat_date' => 'CveDailyStats.stat_date', 'hosts' => $query->expr('COUNT(DISTINCT CveDailyStats.host_id)')];
        foreach ([...self::STATE_COUNTERS, 'new_count', 'resolved_count'] as $counter) {
            $fields[$counter] = $query->func()->sum('CveDailyStats.' . $counter);
        }

        $query
            ->select($fields)
            ->innerJoinWith('Hosts')
            ->where([
                'CveDailyStats.stat_date >=' => $from,
                'CveDailyStats.stat_date <=' => $to,
                'Hosts.disabled'             => 0,
            ])
            ->groupBy(['CveDailyStats.stat_date'])
            ->orderBy(['CveDailyStats.stat_date' => 'asc']);

        if (!empty($hostIds)) {
            $query->where(['CveDailyStats.host_id IN' => $hostIds]);
        }

        if (!empty($MY_RIGHTS)) {
            // A subquery instead of a join, a host shared with several containers would be summed up several times
            $query->where([
                'CveDailyStats.host_id IN' => $this->Hosts->find()
                    ->select(['Hosts.id'])
                    ->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                        'HostsToContainersSharing.host_id = Hosts.id'
                    ])
                    ->where(['HostsToContainersSharing.container_id IN' => $MY_RIGHTS])
            ]);
        }

        return array_map(function ($row) {
            $result = ['stat_date' => (string)$row['stat_date']];
            foreach ($row as $field => $value) {
                if ($field !== 'stat_date') {
                    $result[$field] = (int)$value;
                }
            }
            return $result;
        }, $query->toArray());
    }
}
