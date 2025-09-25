<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace Statusengine3Module\Model\Table;

use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\DowntimeHostConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * DowntimeHost Model
 *
 * @method \Statusengine3Module\Model\Entity\DowntimeHost newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\DowntimeHost newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeHost[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class DowntimeHostsTable extends Table implements DowntimehistoryHostsTableInterface {

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statusengine_host_downtimehistory');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'node_name', 'scheduled_start_time', 'internal_downtime_id']);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        //Readonly table
        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        //Readonly table
        return $rules;
    }

    /**
     * @param DowntimeHostConditions $DowntimeHostConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array|void
     */
    public function getDowntimes(DowntimeHostConditions $DowntimeHostConditions, ?PaginateOMat $PaginateOMat = null) {
        $query = $this->find()
            ->select([
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.actual_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.was_cancelled',

                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['DowntimeHosts.hostname = Hosts.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            );

        $startTimestamp = $DowntimeHostConditions->getFrom();
        $endTimestamp = $DowntimeHostConditions->getTo();

        $query->where([
            'OR' => [
                ['(:start1 BETWEEN DowntimeHosts.scheduled_start_time AND DowntimeHosts.scheduled_end_time)'],
                ['(:end1   BETWEEN DowntimeHosts.scheduled_start_time AND DowntimeHosts.scheduled_end_time)'],
                ['(DowntimeHosts.scheduled_start_time BETWEEN :start2 AND :end2)'],

            ]
        ])
            ->bind(':start1', $startTimestamp, 'integer')
            ->bind(':end1', $endTimestamp, 'integer')
            ->bind(':start2', $startTimestamp, 'integer')
            ->bind(':end2', $endTimestamp, 'integer');
        $query->orderBy(
            array_merge(
                $DowntimeHostConditions->getOrder(),
                ['DowntimeHosts.internal_downtime_id' => 'asc']
            )
        )->groupBy('DowntimeHosts.internal_downtime_id');


        if ($DowntimeHostConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $DowntimeHostConditions->getContainerIds()
            ]);
        }


        if ($DowntimeHostConditions->hideExpired()) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => time()
            ]);
        }

        if ($DowntimeHostConditions->hasConditions()) {
            $query->andWhere($DowntimeHostConditions->getConditions());
        }

        if ($DowntimeHostConditions->isRunning()) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => time(),
                'DowntimeHosts.was_started'          => 1,
                'DowntimeHosts.was_cancelled'        => 0
            ]);
        }

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->emptyArrayIfNull($query->toArray());
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }


    /**
     * @param int $internalDowntimeId
     * @return array
     */
    public function getHostUuidWithDowntimeByInternalDowntimeId($internalDowntimeId) {
        $result = $this->find()
            ->select([
                'DowntimeHosts.hostname',
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.was_cancelled',

                'Hosts.uuid'
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['DowntimeHosts.hostname = Hosts.uuid']
            )
            ->where([
                'DowntimeHosts.internal_downtime_id' => $internalDowntimeId
            ])
            ->disableHydration()
            ->first();

        if ($result === null) {
            return [];
        }

        return [
            'DowntimeHosts' => $result,
            'Hosts'         => [
                'uuid' => $result['hostname']
            ]
        ];
    }

    /**
     * @param DowntimeHostConditions $DowntimeHostConditions
     * @param bool $enableHydration
     * @param bool $disableResultsCasting
     * @return array
     */
    public function getDowntimesForReporting(DowntimeHostConditions $DowntimeHostConditions, $enableHydration = true, $disableResultsCasting = false) {
        $query = $this->find()
            ->select([
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.actual_start_time',
                'DowntimeHosts.actual_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.was_cancelled',

                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['DowntimeHosts.hostname = Hosts.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->orderBy(
                array_merge(
                    $DowntimeHostConditions->getOrder(),
                    ['DowntimeHosts.internal_downtime_id' => 'asc']
                )
            )->groupBy('DowntimeHosts.internal_downtime_id');


        if ($DowntimeHostConditions->hasHostUuids()) {
            $uuids = $DowntimeHostConditions->getHostUuids();
            if (!is_array($uuids)) {
                $uuids = [$uuids];
            }

            $query->andWhere([
                'DowntimeHosts.hostname IN' => $uuids
            ]);
        }

        if ($DowntimeHostConditions->includeCancelledDowntimes() === false) {
            $query->andWhere([
                'DowntimeHosts.was_cancelled' => 0
            ]);
        }

        if ($DowntimeHostConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $DowntimeHostConditions->getContainerIds()
            ]);
        }

        if ($DowntimeHostConditions->hasConditions()) {
            $query->andWhere($DowntimeHostConditions->getConditions());
        }

        $startTimestamp = $DowntimeHostConditions->getFrom();
        $endTimestamp = $DowntimeHostConditions->getTo();

        $query->where([
            'OR' => [
                ['(:start1 BETWEEN DowntimeHosts.scheduled_start_time AND DowntimeHosts.scheduled_end_time)'],
                ['(:end1   BETWEEN DowntimeHosts.scheduled_start_time AND DowntimeHosts.scheduled_end_time)'],
                ['(DowntimeHosts.scheduled_start_time BETWEEN :start2 AND :end2)'],

            ]
        ])
            ->bind(':start1', $startTimestamp, 'integer')
            ->bind(':end1', $endTimestamp, 'integer')
            ->bind(':start2', $startTimestamp, 'integer')
            ->bind(':end2', $endTimestamp, 'integer');

        $query->enableHydration($enableHydration);
        if ($disableResultsCasting) {
            $query->disableResultsCasting();
        }
        $query->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param null $uuid
     * @param bool $isRunning
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function byHostUuid($uuid = null, $isRunning = false) {
        if (empty($uuid)) {
            return null;
        }

        $query = $this->find();
        $query->select([
            'DowntimeHosts.author_name',
            'DowntimeHosts.comment_data',
            'DowntimeHosts.entry_time',
            'DowntimeHosts.scheduled_start_time',
            'DowntimeHosts.scheduled_end_time',
            'DowntimeHosts.duration',
            'DowntimeHosts.was_started',
            'DowntimeHosts.internal_downtime_id',
            'DowntimeHosts.was_cancelled',
        ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['DowntimeHosts.hostname = Hosts.uuid']
            )
            ->orderBy([
                'DowntimeHosts.entry_time' => 'DESC'
            ])
            ->where([
                'DowntimeHosts.hostname' => $uuid
            ]);

        if ($isRunning) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => time(),
                'DowntimeHosts.was_started'          => 1,
                'DowntimeHosts.was_cancelled'        => 0

            ]);
        }

        return $query->first();
    }


    /**
     * @param $uuids
     * @param $isRunning
     * @return array
     */
    public function byUuidsNoJoins($uuids, $isRunning = false) {
        if (empty($uuids)) {
            return [];
        }
        if (!is_array($uuids)) {
            $uuids = [$uuids];
        }

        $query = $this->find()
            ->select([
                'DowntimeHosts.hostname',
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.was_cancelled',
            ])
            ->orderBy([
                'DowntimeHosts.entry_time' => 'DESC'
            ])
            ->where([
                'DowntimeHosts.hostname IN' => $uuids
            ]);

        if ($isRunning) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => time(),
                'DowntimeHosts.was_started'          => 1,
                'DowntimeHosts.was_cancelled'        => 0

            ]);
        }

        $query->disableHydration();

        $downtimes = $query->all();
        $result = [];
        foreach ($downtimes as $downtime) {
            $result[$downtime['hostname']] = $downtime;
        }

        return $result;
    }


    /**
     * @param $uuids
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    public function getPlannedDowntimes($uuids, int $startTimestamp, int $endTimestamp) {
        if (empty($uuids)) {
            return [];
        }

        if (!is_array($uuids)) {
            $uuids = [$uuids];
        }

        $query = $this->find()
            ->select([
                'DowntimeHosts.hostname',
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.actual_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.was_cancelled',
            ])
            ->where([
                'DowntimeHosts.scheduled_start_time >' => $startTimestamp,
                'DowntimeHosts.scheduled_start_time <' => $endTimestamp,
                'DowntimeHosts.was_started'            => 0,
                'DowntimeHosts.was_cancelled'          => 0,
                'DowntimeHosts.hostname IN'            => $uuids
            ])
            ->orderBy([
                'DowntimeHosts.scheduled_start_time' => 'DESC'
            ]);

        $query->disableHydration();
        $downtimes = $query->all();
        $result = [];
        foreach ($downtimes as $downtime) {
            $result[$downtime['hostname']][] = $downtime;
        }

        return $result;
    }

}
