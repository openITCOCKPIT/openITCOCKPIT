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

use App\Lib\Interfaces\DowntimehistoryServicesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * DowntimeService Model
 *
 * @method \Statusengine3Module\Model\Entity\DowntimeService newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\DowntimeService newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\DowntimeService[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class DowntimeServicesTable extends Table implements DowntimehistoryServicesTableInterface {

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

        $this->setTable('statusengine_service_downtimehistory');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'service_description', 'node_name', 'scheduled_start_time', 'internal_downtime_id']);
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
     * @param DowntimeServiceConditions $DowntimeServiceConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array|void
     */
    public function getDowntimes(DowntimeServiceConditions $DowntimeServiceConditions, ?PaginateOMat $PaginateOMat = null) {
        $query = $this->find();
        $query->select([
            'DowntimeServices.author_name',
            'DowntimeServices.comment_data',
            'DowntimeServices.entry_time',
            'DowntimeServices.scheduled_start_time',
            'DowntimeServices.scheduled_end_time',
            'DowntimeServices.actual_end_time',
            'DowntimeServices.duration',
            'DowntimeServices.was_started',
            'DowntimeServices.internal_downtime_id',
            'DowntimeServices.was_cancelled',

            'Services.id',
            'Services.uuid',
            'Services.name',
            'Services.servicetemplate_id',

            'Servicetemplates.id',
            'Servicetemplates.name',

            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',

            'HostsToContainers.container_id',

            'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
        ])
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = DowntimeServices.service_description']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Servicetemplates.id = Services.servicetemplate_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.uuid = DowntimeServices.hostname']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            );

        $startTimestamp = $DowntimeServiceConditions->getFrom();
        $endTimestamp = $DowntimeServiceConditions->getTo();

        $query->where([
            'OR' => [
                ['(:start1 BETWEEN DowntimeServices.scheduled_start_time AND DowntimeServices.scheduled_end_time)'],
                ['(:end1   BETWEEN DowntimeServices.scheduled_start_time AND DowntimeServices.scheduled_end_time)'],
                ['(DowntimeServices.scheduled_start_time BETWEEN :start2 AND :end2)'],

            ]
        ])
            ->bind(':start1', $startTimestamp, 'integer')
            ->bind(':end1', $endTimestamp, 'integer')
            ->bind(':start2', $startTimestamp, 'integer')
            ->bind(':end2', $endTimestamp, 'integer');
        $query->orderBy(
            array_merge(
                $DowntimeServiceConditions->getOrder(),
                ['DowntimeServices.internal_downtime_id' => 'asc']
            )
        )
            ->groupBy('DowntimeServices.internal_downtime_id');


        if ($DowntimeServiceConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $DowntimeServiceConditions->getContainerIds()
            ]);
        }


        if ($DowntimeServiceConditions->hideExpired()) {
            $query->andWhere([
                'DowntimeServices.scheduled_end_time >' => time()
            ]);
        }

        if ($DowntimeServiceConditions->hasConditions()) {

            $where = $DowntimeServiceConditions->getConditions();
            $having = null;
            if (isset($where['servicename LIKE'])) {
                $having = [
                    'servicename LIKE' => $where['servicename LIKE']
                ];
                unset($where['servicename LIKE']);
            }

            if (!empty($where))
                $query->andWhere($where);

            if (!empty($having)) {
                $query->having($having);
            }
        }

        if ($DowntimeServiceConditions->isRunning()) {
            $query->andWhere([
                'DowntimeServices.scheduled_end_time >' => time(),
                'DowntimeServices.was_started'          => 1,
                'DowntimeServices.was_cancelled'        => 0
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
     * @param int $hostId
     * @param Downtime $Downtime
     * @return array
     */
    public function getServiceDowntimesByHostAndDowntime($hostId, Downtime $Downtime) {
        $records = $this->find()
            ->select([
                'DowntimeServices.internal_downtime_id'
            ])
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = DowntimeServices.service_description']
            )
            ->where([
                'Services.host_id'                      => $hostId,
                'DowntimeServices.scheduled_start_time' => $Downtime->getScheduledStartTime(),
                'DowntimeServices.scheduled_end_time'   => $Downtime->getScheduledEndTime()
            ])
            ->disableHydration()
            ->all();

        $result = $records->toArray();
        return Hash::extract($result, '{n}.internal_downtime_id');
    }

    /**
     * @param DowntimeServiceConditions $DowntimeServiceConditions
     * @param bool $enableHydration
     * @param bool $disableResultsCasting
     * @return array
     */
    public function getDowntimesForReporting(DowntimeServiceConditions $DowntimeServiceConditions, $enableHydration = true, $disableResultsCasting = false) {
        $query = $this->find();
        $query->select([
            'DowntimeServices.author_name',
            'DowntimeServices.comment_data',
            'DowntimeServices.entry_time',
            'DowntimeServices.scheduled_start_time',
            'DowntimeServices.scheduled_end_time',
            'DowntimeServices.actual_start_time',
            'DowntimeServices.actual_end_time',
            'DowntimeServices.duration',
            'DowntimeServices.was_started',
            'DowntimeServices.internal_downtime_id',
            'DowntimeServices.was_cancelled',

            'Services.id',
            'Services.uuid',
            'Services.name',
            'Services.servicetemplate_id',

            'Servicetemplates.id',
            'Servicetemplates.name',
            'Servicetemplates.template_name',

            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',

            'HostsToContainers.container_id',

            'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
        ])
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = DowntimeServices.service_description']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Servicetemplates.id = Services.servicetemplate_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.uuid = DowntimeServices.hostname']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->orderBy(
                array_merge(
                    $DowntimeServiceConditions->getOrder(),
                    ['DowntimeServices.internal_downtime_id' => 'asc']
                )
            )
            ->groupBy('DowntimeServices.internal_downtime_id');


        if ($DowntimeServiceConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $DowntimeServiceConditions->getContainerIds()
            ]);
        }


        if ($DowntimeServiceConditions->hasHostUuids()) {
            $hostUuids = $DowntimeServiceConditions->getHostUuids();
            if (!is_array($hostUuids)) {
                $hostUuids = [$hostUuids];
            }
            $query->andWhere([
                'Hosts.uuid IN' => $hostUuids
            ]);
        }

        if ($DowntimeServiceConditions->hasServiceUuids()) {
            $serviceUuids = $DowntimeServiceConditions->getServiceUuids();
            if (!is_array($serviceUuids)) {
                $serviceUuids = [$serviceUuids];
            }
            $query->andWhere([
                'Services.uuid IN' => $serviceUuids
            ]);
        }

        if ($DowntimeServiceConditions->includeCancelledDowntimes() === false) {
            $query->andWhere([
                'DowntimeServices.was_cancelled' => 0
            ]);
        }

        $startTimestamp = $DowntimeServiceConditions->getFrom();
        $endTimestamp = $DowntimeServiceConditions->getTo();

        $query->where([
            'OR' => [
                ['(:start1 BETWEEN DowntimeServices.scheduled_start_time AND DowntimeServices.scheduled_end_time)'],
                ['(:end1   BETWEEN DowntimeServices.scheduled_start_time AND DowntimeServices.scheduled_end_time)'],
                ['(DowntimeServices.scheduled_start_time BETWEEN :start2 AND :end2)'],

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
    public function byServiceUuid($uuid = null, $isRunning = false) {
        if (empty($uuid)) {
            return null;
        }

        $query = $this->find();
        $query->select([
            'DowntimeServices.author_name',
            'DowntimeServices.comment_data',
            'DowntimeServices.entry_time',
            'DowntimeServices.scheduled_start_time',
            'DowntimeServices.scheduled_end_time',
            'DowntimeServices.duration',
            'DowntimeServices.was_started',
            'DowntimeServices.internal_downtime_id',
            'DowntimeServices.was_cancelled',
        ])
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = DowntimeServices.service_description']
            )
            ->orderBy([
                'DowntimeServices.entry_time' => 'DESC'
            ])
            ->where([
                'Services.uuid' => $uuid
            ]);

        if ($isRunning) {
            $query->andWhere([
                'DowntimeServices.scheduled_end_time >' => time(),
                'DowntimeServices.was_started'          => 1,
                'DowntimeServices.was_cancelled'        => 0

            ]);
        }

        return $query->first();
    }

    /**
     * @param int $internalDowntimeId
     * @return array
     */
    public function getHostAndServiceUuidWithDowntimeByInternalDowntimeId($internalDowntimeId) {
        $result = $this->find()
            ->select([
                'DowntimeServices.hostname',
                'DowntimeServices.service_description',
                'DowntimeServices.author_name',
                'DowntimeServices.comment_data',
                'DowntimeServices.entry_time',
                'DowntimeServices.scheduled_start_time',
                'DowntimeServices.scheduled_end_time',
                'DowntimeServices.duration',
                'DowntimeServices.was_started',
                'DowntimeServices.internal_downtime_id',
                'DowntimeServices.was_cancelled',

                'Hosts.uuid',
                'Services.uuid'
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['DowntimeServices.hostname = Hosts.uuid']
            )
            ->innerJoin(
                ['Services' => 'services'],
                ['DowntimeServices.service_description = Services.uuid']
            )
            ->where([
                'DowntimeServices.internal_downtime_id' => $internalDowntimeId
            ])
            ->disableHydration()
            ->first();

        if ($result === null) {
            return [];
        }

        return [
            'DowntimeServices' => $result,
            'Hosts'            => [
                'uuid' => $result['hostname']
            ],
            'Services'         => [
                'uuid' => $result['service_description']
            ]
        ];
    }

    /**
     * @param array $uuids
     * @param bool $isRunning
     * @return array
     */
    public function byUuidsNoJoins($uuids, $isRunning = false) {
        if (empty($uuids)) {
            return [];
        }

        if (!is_array($uuids)) {
            $uuids = [$uuids];
        }

        $query = $this->find();
        $query->select([
            'DowntimeServices.service_description',
            'DowntimeServices.author_name',
            'DowntimeServices.comment_data',
            'DowntimeServices.entry_time',
            'DowntimeServices.scheduled_start_time',
            'DowntimeServices.scheduled_end_time',
            'DowntimeServices.duration',
            'DowntimeServices.was_started',
            'DowntimeServices.internal_downtime_id',
            'DowntimeServices.was_cancelled',
        ])
            ->orderBy([
                'DowntimeServices.entry_time' => 'DESC'
            ])
            ->where([
                'DowntimeServices.service_description IN' => $uuids
            ]);

        if ($isRunning) {
            $query->andWhere([
                'DowntimeServices.scheduled_end_time >' => time(),
                'DowntimeServices.was_started'          => 1,
                'DowntimeServices.was_cancelled'        => 0

            ]);
        }

        $query->disableHydration();
        $downtimes = $query->all();
        $result = [];
        foreach ($downtimes as $downtime) {
            $result[$downtime['service_description']] = $downtime;
        }
        return $result;
    }

    /**
     *  Maybe we should refactor this in the future to query the statusengine_service_scheduleddowntimes table instead
     *  The statusengine_service_scheduleddowntimes only contains planed or currently running downtimes
     *
     * @param $uuids
     * @param int $startTimestamp
     * @param int|null $endTimestamp
     * @return array
     */
    public function getPlannedDowntimes($uuids, int $startTimestamp, ?int $endTimestamp = null): array {
        if (empty($uuids)) {
            return [];
        }

        if (!is_array($uuids)) {
            $uuids = [$uuids];
        }

        $query = $this->find()
            ->select([
                'DowntimeServices.service_description',
                'DowntimeServices.author_name',
                'DowntimeServices.comment_data',
                'DowntimeServices.entry_time',
                'DowntimeServices.scheduled_start_time',
                'DowntimeServices.scheduled_end_time',
                'DowntimeServices.actual_end_time',
                'DowntimeServices.duration',
                'DowntimeServices.was_started',
                'DowntimeServices.internal_downtime_id',
                'DowntimeServices.was_cancelled',
            ])
            ->where([
                'DowntimeServices.scheduled_start_time >' => $startTimestamp,
                'DowntimeServices.was_started'            => 0,
                'DowntimeServices.was_cancelled'          => 0,
                'DowntimeServices.service_description IN' => $uuids
            ]);

        if ($endTimestamp !== null) {
            $query->andWhere([
                'DowntimeServices.scheduled_start_time <' => $endTimestamp
            ]);
        }

        $query->orderBy([
            'DowntimeServices.scheduled_start_time' => 'ASC'
        ]);

        $query->disableHydration();
        $downtimes = $query->all();
        $result = [];
        foreach ($downtimes as $downtime) {
            $result[$downtime['service_description']][] = $downtime;
        }

        return $result;
    }

}
