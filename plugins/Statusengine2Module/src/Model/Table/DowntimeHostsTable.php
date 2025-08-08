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

use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Http\Exception\NotImplementedException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\DowntimeHostConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * DowntimeHostsTable Model
 *
 * @link http://nagios.sourceforge.net/docs/ndoutils/NDOUtils_DB_Model.pdf
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\DowntimeHost get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\DowntimeHost findOrCreate($search, callable $callback = null, $options = [])
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
     * @param DowntimeHostConditions $DowntimeHostConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array|void
     */
    public function getDowntimes(DowntimeHostConditions $DowntimeHostConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->select([
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.downtimehistory_id',
                'DowntimeHosts.was_cancelled',

                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeHosts.object_id', 'DowntimeHosts.downtime_type = 2'] //Downtime.downtime_type = 2 Host downtime
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
                'DowntimeHosts.scheduled_start_time >' => date('Y-m-d H:i:s', $DowntimeHostConditions->getFrom()),
                'DowntimeHosts.scheduled_start_time <' => date('Y-m-d H:i:s', $DowntimeHostConditions->getTo()),
            ])
            ->orderBy($DowntimeHostConditions->getOrder())
            ->groupBy('DowntimeHosts.downtimehistory_id');


        if ($DowntimeHostConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $DowntimeHostConditions->getContainerIds()
            ]);
        }


        if ($DowntimeHostConditions->hideExpired()) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => date('Y-m-d H:i:s', time())
            ]);
        }

        if ($DowntimeHostConditions->hasConditions()) {
            $query->andWhere($DowntimeHostConditions->getConditions());
        }

        if ($DowntimeHostConditions->isRunning()) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => date('Y-m-d H:i:s', time()),
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
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.duration',
                'DowntimeHosts.was_started',
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.downtimehistory_id',
                'DowntimeHosts.was_cancelled',

                'Objects.name1'
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeHosts.object_id', 'DowntimeHosts.downtime_type = 2'] //Downtime.downtime_type = 2 Host downtime
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
                'uuid' => $result['Objects']['name1']
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
                'DowntimeHosts.downtimehistory_id',
                'DowntimeHosts.was_cancelled',

                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeHosts.object_id', 'DowntimeHosts.downtime_type = 2'] //Downtime.downtime_type = 2 Host downtime
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->orderBy($DowntimeHostConditions->getOrder())
            ->groupBy('DowntimeHosts.downtimehistory_id');


        if ($DowntimeHostConditions->hasHostUuids()) {
            $uuids = $DowntimeHostConditions->getHostUuids();
            if (!is_array($uuids)) {
                $uuids = [$uuids];
            }

            $query->andWhere([
                'Objects.name1 IN' => $uuids
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

        $startDateSqlFormat = date('Y-m-d H:i:s', $DowntimeHostConditions->getFrom());
        $endDateSqlFormat = date('Y-m-d H:i:s', $DowntimeHostConditions->getTo());

        $query->where([
            'OR' => [
                ['(:start1 BETWEEN DowntimeHosts.scheduled_start_time AND DowntimeHosts.scheduled_end_time)'],
                ['(:end1   BETWEEN DowntimeHosts.scheduled_start_time AND DowntimeHosts.scheduled_end_time)'],
                ['(DowntimeHosts.scheduled_start_time BETWEEN :start2 AND :end2)'],

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
            'DowntimeHosts.downtimehistory_id',
            'DowntimeHosts.was_cancelled',
        ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeHosts.object_id', 'DowntimeHosts.downtime_type = 2'] //Downtime.downtime_type = 2 Host downtime
            )
            ->orderBy([
                'DowntimeHosts.entry_time' => 'DESC'
            ])
            ->where([
                'Objects.name1'         => $uuid,
                'Objects.objecttype_id' => 1
            ]);

        if ($isRunning) {
            $query->andWhere([
                'DowntimeHosts.scheduled_end_time >' => date('Y-m-d H:i:s', time()),
                'DowntimeHosts.was_started'          => 1,
                'DowntimeHosts.was_cancelled'        => 0

            ]);
        }

        return $query->first();
    }

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
