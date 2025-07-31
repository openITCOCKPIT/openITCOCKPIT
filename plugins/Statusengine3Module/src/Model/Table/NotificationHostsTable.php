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

use App\Lib\Interfaces\NotificationHostsTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\HostNotificationConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * NotificationHosts Model
 *
 * @method \Statusengine3Module\Model\Entity\NotificationHost newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\NotificationHost newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHost[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class NotificationHostsTable extends Table implements NotificationHostsTableInterface {

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

        $this->setTable('statusengine_host_notifications');
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
     * @param HostNotificationConditions $HostNotificationConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getNotifications(HostNotificationConditions $HostNotificationConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->select([
                'NotificationHosts.hostname',
                'NotificationHosts.start_time',
                'NotificationHosts.state',
                'NotificationHosts.reason_type',
                'NotificationHosts.output',

                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'Contacts.id',
                'Contacts.uuid',
                'Contacts.name',

                'Commands.id',
                'Commands.uuid',
                'Commands.name',

                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.uuid = NotificationHosts.hostname']
            )
            ->innerJoin(
                ['Contacts' => 'contacts'],
                ['Contacts.uuid = NotificationHosts.contact_name']
            )
            ->innerJoin(
                ['Commands' => 'commands'],
                ['Commands.uuid = NotificationHosts.command_name']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->where([
                'NotificationHosts.start_time >' => $HostNotificationConditions->getFrom(),
                'NotificationHosts.start_time <' => $HostNotificationConditions->getTo()
            ])
            ->order(
                $HostNotificationConditions->getOrder()
            )
            ->group([
                'NotificationHosts.hostname',
                'NotificationHosts.start_time',
                'NotificationHosts.start_time_usec'
            ]);


        if ($HostNotificationConditions->getHostUuid()) {
            $query->andWhere([
                'Hosts.uuid' => $HostNotificationConditions->getHostUuid()
            ]);
        }

        if ($HostNotificationConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $HostNotificationConditions->getContainerIds()
            ]);
        }

        if (!empty($HostNotificationConditions->getStates())) {
            $query->andWhere([
                'NotificationHosts.state IN' => $HostNotificationConditions->getStates()
            ]);
        }

        if ($HostNotificationConditions->hasConditions()) {
            $query->andWhere($HostNotificationConditions->getConditions());
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
