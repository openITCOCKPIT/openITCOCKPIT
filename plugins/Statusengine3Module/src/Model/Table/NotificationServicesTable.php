<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use App\Lib\Interfaces\NotificationServicesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServiceNotificationConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * NotificationServicess Model
 *
 * @method \Statusengine3Module\Model\Entity\NotificationServices newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\NotificationServices newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationServices[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class NotificationServicesTable extends Table implements NotificationServicesTableInterface {

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

        $this->setTable('statusengine_service_notifications');
        $this->setDisplayField('id');
        $this->setPrimaryKey(['id', 'start_time']);
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
     * @param ServiceNotificationConditions $ServiceNotificationConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getNotifications(ServiceNotificationConditions $ServiceNotificationConditions, $PaginateOMat = null) {
        $query = $this->find();
        $query->select([
            'NotificationServices.hostname',
            'NotificationServices.service_description',
            'NotificationServices.start_time',
            'NotificationServices.state',
            'NotificationServices.reason_type',
            'NotificationServices.output',

            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',

            'Services.id',
            'Services.uuid',
            'Services.name',

            'Servicetemplates.id',
            'Servicetemplates.uuid',
            'Servicetemplates.name',

            'Contacts.id',
            'Contacts.uuid',
            'Contacts.name',

            'Commands.id',
            'Commands.uuid',
            'Commands.name',

            'HostsToContainers.container_id',

            'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
        ])
            ->innerJoin(
                ['Services' => 'services'],
                ['Services.uuid = NotificationServices.service_description']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Servicetemplates.id = Services.servicetemplate_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.uuid = NotificationServices.hostname']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->innerJoin(
                ['Contacts' => 'contacts'],
                ['Contacts.uuid = NotificationServices.contact_name']
            )
            ->innerJoin(
                ['Commands' => 'commands'],
                ['Commands.uuid = NotificationServices.command_name']
            )
            ->where([
                'NotificationServices.start_time >' => $ServiceNotificationConditions->getFrom(),
                'NotificationServices.start_time <' => $ServiceNotificationConditions->getTo()
            ])
            ->order($ServiceNotificationConditions->getOrder())
            ->group([
                'NotificationServices.service_description',
                'NotificationServices.start_time',
                'NotificationServices.start_time_usec'
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
                'NotificationServices.state IN' => $ServiceNotificationConditions->getStates()
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
