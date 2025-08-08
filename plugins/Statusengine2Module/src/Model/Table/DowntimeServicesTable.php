<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace Statusengine2Module\Model\Table;

use App\Lib\Interfaces\DowntimehistoryServicesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Http\Exception\NotImplementedException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * DowntimeServicesTable Model
 *
 * @link http://nagios.sourceforge.net/docs/ndoutils/NDOUtils_DB_Model.pdf
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\DowntimeService get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeService findOrCreate($search, callable $callback = null, $options = [])
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

        $this->setTable('nagios_downtimehistory');
        $this->setDisplayField('downtimehistory_id');
        $this->setPrimaryKey('downtimehistory_id');

        $this->belongsTo('Objects', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Statusengine2Module.Objects'
        ]);
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
    public function getDowntimes(DowntimeServiceConditions $DowntimeServiceConditions, $PaginateOMat = null) {
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
            'DowntimeServices.downtimehistory_id',
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
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeServices.object_id', 'DowntimeServices.downtime_type = 1'] //Downtime.downtime_type = 1 Service downtime
            )
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = Objects.name2']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Servicetemplates.id = Services.servicetemplate_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->where([
                'DowntimeServices.scheduled_start_time >' => date('Y-m-d H:i:s', $DowntimeServiceConditions->getFrom()),
                'DowntimeServices.scheduled_start_time <' => date('Y-m-d H:i:s', $DowntimeServiceConditions->getTo()),
            ])
            ->orderBy($DowntimeServiceConditions->getOrder())
            ->groupBy('DowntimeServices.downtimehistory_id');


        if ($DowntimeServiceConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $DowntimeServiceConditions->getContainerIds()
            ]);
        }


        if ($DowntimeServiceConditions->hideExpired()) {
            $query->andWhere([
                'DowntimeServices.scheduled_end_time >' => date('Y-m-d H:i:s', time())
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
                'DowntimeServices.scheduled_end_time >' => date('Y-m-d H:i:s', time()),
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
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeServices.object_id']
            )
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = Objects.name2']
            )
            ->where([
                'DowntimeServices.downtime_type'        => 1,  //Downtime.downtime_type = 1 Service downtime
                'Services.host_id'                      => $hostId,
                'DowntimeServices.scheduled_start_time' => date('Y-m-d H:i:s', $Downtime->getScheduledStartTime()),
                'DowntimeServices.scheduled_end_time'   => date('Y-m-d H:i:s', $Downtime->getScheduledEndTime())
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
            'DowntimeServices.downtimehistory_id',
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
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeServices.object_id', 'DowntimeServices.downtime_type = 1'] //Downtime.downtime_type = 1 Service downtime
            )
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = Objects.name2']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Servicetemplates.id = Services.servicetemplate_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->orderBy($DowntimeServiceConditions->getOrder())
            ->groupBy('DowntimeServices.downtimehistory_id');


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
                'Objects.name1 IN' => $hostUuids
            ]);
        }

        if ($DowntimeServiceConditions->hasServiceUuids()) {
            $serviceUuids = $DowntimeServiceConditions->getServiceUuids();
            if (!is_array($serviceUuids)) {
                $serviceUuids = [$serviceUuids];
            }
            $query->andWhere([
                'Objects.name2 IN' => $serviceUuids
            ]);
        }

        if ($DowntimeServiceConditions->includeCancelledDowntimes() === false) {
            $query->andWhere([
                'DowntimeServices.was_cancelled' => 0
            ]);
        }

        $startDateSqlFormat = date('Y-m-d H:i:s', $DowntimeServiceConditions->getFrom());
        $endDateSqlFormat = date('Y-m-d H:i:s', $DowntimeServiceConditions->getTo());

        $query->where([
            'OR' => [
                ['(:start1 BETWEEN DowntimeServices.scheduled_start_time AND DowntimeServices.scheduled_end_time)'],
                ['(:end1   BETWEEN DowntimeServices.scheduled_start_time AND DowntimeServices.scheduled_end_time)'],
                ['(DowntimeServices.scheduled_start_time BETWEEN :start2 AND :end2)'],

            ]
        ])
            ->bind(':start1', $startDateSqlFormat, 'date')
            ->bind(':end1', $endDateSqlFormat, 'date')
            ->bind(':start2', $startDateSqlFormat, 'date')
            ->bind(':end2', $endDateSqlFormat, 'date');

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
            'DowntimeServices.downtimehistory_id',
            'DowntimeServices.was_cancelled',
        ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeServices.object_id', 'DowntimeServices.downtime_type = 1'] //Downtime.downtime_type = 1 Service downtime
            )
            ->orderBy([
                'DowntimeServices.entry_time' => 'DESC'
            ])
            ->where([
                'Objects.name2'         => $uuid,
                'Objects.objecttype_id' => 2
            ]);

        if ($isRunning) {
            $query->andWhere([
                'DowntimeServices.scheduled_end_time >' => date('Y-m-d H:i:s', time()),
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
                'DowntimeServices.author_name',
                'DowntimeServices.comment_data',
                'DowntimeServices.entry_time',
                'DowntimeServices.scheduled_start_time',
                'DowntimeServices.scheduled_end_time',
                'DowntimeServices.duration',
                'DowntimeServices.was_started',
                'DowntimeServices.internal_downtime_id',
                'DowntimeServices.downtimehistory_id',
                'DowntimeServices.was_cancelled',

                'Objects.name1',
                'Objects.name2',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeServices.object_id', 'DowntimeServices.downtime_type = 1'] //Downtime.downtime_type = 1 Service downtime
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
                'uuid' => $result['Objects']['name1']
            ],
            'Services'         => [
                'uuid' => $result['Objects']['name2']
            ]
        ];
    }

    /**
     * @param array $uuids
     * @param bool $isRunning
     * @return array
     */
    public function byUuidsNoJoins($uuids, $isRunning = false) {
        throw new NotImplementedException();
    }

    /**
     * @param $uuids
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    public function getPlannedDowntimes($uuids, int $startTimestamp, int $endTimestamp) {
        throw new NotImplementedException();
    }
}
