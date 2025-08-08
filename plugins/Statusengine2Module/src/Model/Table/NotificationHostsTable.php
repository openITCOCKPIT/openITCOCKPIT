<?php
// Copyright (C) <2015>  <it-novum GmbH>
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

namespace Statusengine2Module\Model\Table;

use App\Lib\Interfaces\NotificationHostsTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\HostNotificationConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * NotificationHosts Model
 *
 * @link http://nagios.sourceforge.net/docs/ndoutils/NDOUtils_DB_Model.pdf
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\NotificationHost get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost findOrCreate($search, callable $callback = null, $options = [])
 *
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

        $this->setTable('nagios_notifications');
        $this->setDisplayField('notification_id');
        $this->setPrimaryKey(['notification_id', 'start_time']);

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
     * @param HostNotificationConditions $HostNotificationConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getNotifications(HostNotificationConditions $HostNotificationConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->select([
                'NotificationHosts.object_id',
                'NotificationHosts.notification_type',
                'NotificationHosts.start_time',
                'NotificationHosts.state',
                'NotificationHosts.output',

                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'Contactnotifications.notification_id',
                'Contactnotifications.contact_object_id',
                'Contactnotifications.start_time',

                'Contacts.id',
                'Contacts.uuid',
                'Contacts.name',

                'Commands.id',
                'Commands.uuid',
                'Commands.name',

                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = NotificationHosts.object_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->innerJoin(
                ['Contactnotifications' => 'nagios_contactnotifications'],
                ['NotificationHosts.notification_id = Contactnotifications.notification_id']
            )
            ->innerJoin(
                ['ContactObjects' => 'nagios_objects'],
                ['Contactnotifications.contact_object_id = ContactObjects.object_id']
            )
            ->innerJoin(
                ['Contacts' => 'contacts'],
                ['ContactObjects.name1 = Contacts.uuid']
            )
            ->innerJoin(
                ['Contactnotificationmethods' => 'nagios_contactnotificationmethods'],
                ['Contactnotificationmethods.contactnotification_id = Contactnotifications.contactnotification_id']
            )
            ->innerJoin(
                ['CommandObjects' => 'nagios_objects'],
                ['Contactnotificationmethods.command_object_id = CommandObjects.object_id']
            )
            ->innerJoin(
                ['Commands' => 'commands'],
                ['CommandObjects.name1 = Commands.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->where([
                'NotificationHosts.start_time >'      => date('Y-m-d H:i:s', $HostNotificationConditions->getFrom()),
                'NotificationHosts.start_time <'      => date('Y-m-d H:i:s', $HostNotificationConditions->getTo()),
                'NotificationHosts.notification_type' => 0
            ])
            ->orderBy($HostNotificationConditions->getOrder())
            ->groupBy(['Contactnotifications.contactnotification_id']);


        if ($HostNotificationConditions->getHostUuid()) {
            $query->andWhere([
                'Objects.name1' => $HostNotificationConditions->getHostUuid()
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
