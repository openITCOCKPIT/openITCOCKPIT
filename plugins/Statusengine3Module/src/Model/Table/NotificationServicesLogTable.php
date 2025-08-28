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

declare(strict_types=1);

namespace Statusengine3Module\Model\Table;

use App\Lib\Interfaces\NotificationServicesLogTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServiceNotificationConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * NotificationServicesLog Model
 *
 * Created using
 * oitc bake model -p Statusengine3Module NotificationServicesLog --table statusengine_service_notifications_log
 *
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServicesLog[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class NotificationServicesLogTable extends Table implements NotificationServicesLogTableInterface {
    use PaginationAndScrollIndexTrait;

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statusengine_service_notifications_log');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'service_description', 'start_time', 'start_time_usec']);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->requirePresence('end_time', 'create')
            ->notEmptyString('end_time');

        $validator
            ->allowEmptyString('state');

        $validator
            ->allowEmptyString('reason_type');

        $validator
            ->boolean('is_escalated')
            ->notEmptyString('is_escalated');

        $validator
            ->notEmptyString('contacts_notified_count');

        $validator
            ->scalar('output')
            ->maxLength('output', 1024)
            ->allowEmptyString('output');

        $validator
            ->scalar('ack_author')
            ->maxLength('ack_author', 1024)
            ->allowEmptyString('ack_author');

        $validator
            ->scalar('ack_data')
            ->maxLength('ack_data', 1024)
            ->allowEmptyString('ack_data');

        return $validator;
    }

    public function getNotifications(ServiceNotificationConditions $ServiceNotificationConditions, ?PaginateOMat $PaginateOMat = null) {
        $query = $this->find();
        $query->select([
            'NotificationServicesLog.hostname',
            'NotificationServicesLog.service_description',
            'NotificationServicesLog.start_time',
            'NotificationServicesLog.state',
            'NotificationServicesLog.output',

            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',

            'Services.id',
            'Services.uuid',
            'Services.name',

            'Servicetemplates.id',
            'Servicetemplates.uuid',
            'Servicetemplates.name',

            'HostsToContainers.container_id',

            'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
        ])
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = NotificationServicesLog.service_description']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Servicetemplates.id = Services.servicetemplate_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.uuid = NotificationServicesLog.hostname']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->where([
                'NotificationServicesLog.start_time >' => $ServiceNotificationConditions->getFrom(),
            ])
            ->orderBy(['count' => 'DESC', 'start_time' => 'DESC'])
            ->groupBy([
                'NotificationServicesLog.service_description',
            ]);
        $query->select([
            'count'      => $query->func()->count('NotificationServicesLog.hostname'),
            'start_time' => $query->func()->max('NotificationServicesLog.start_time', ['integer'])
        ]);


        if ($ServiceNotificationConditions->getServiceUuid()) {
            $query->andWhere([
                'Services.uuid' => $ServiceNotificationConditions->getServiceUuid()
            ]);
        }

        if ($ServiceNotificationConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $ServiceNotificationConditions->getContainerIds()
            ]);
        }

        if (!empty($ServiceNotificationConditions->getStates())) {
            $query->andWhere([
                'NotificationServicesLog.state IN' => $ServiceNotificationConditions->getStates()
            ]);
        }

        if ($ServiceNotificationConditions->hasConditions()) {

            $where = $ServiceNotificationConditions->getConditions();
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

}
