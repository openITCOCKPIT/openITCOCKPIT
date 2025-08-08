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

declare(strict_types=1);

namespace Statusengine3Module\Model\Table;

use App\Lib\Interfaces\NotificationHostsLogTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\HostNotificationConditions;

/**
 * NotificationHostsLog Model
 *
 * Created using
 * oitc bake model -p Statusengine3Module NotificationHostsLog --table statusengine_host_notifications_log
 *
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\NotificationHostsLog[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class NotificationHostsLogTable extends Table implements NotificationHostsLogTableInterface {

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

        $this->setTable('statusengine_host_notifications_log');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'start_time', 'start_time_usec']);
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

    public function getNotifications(HostNotificationConditions $HostNotificationConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->select([
                'NotificationHostsLog.hostname',
                'NotificationHostsLog.state',
                'NotificationHostsLog.output',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'HostsToContainers.container_id',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.uuid = NotificationHostsLog.hostname']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->where([
                'NotificationHostsLog.start_time >' => $HostNotificationConditions->getFrom(),
            ])
            ->groupBy([
                'NotificationHostsLog.hostname'
            ])
            ->orderBy(
                ['count' => 'DESC', 'start_time' => 'DESC']
            );
        $query->select([
            'count'      => $query->func()->count('NotificationHostsLog.hostname'),
            'start_time' => $query->func()->max('NotificationHostsLog.start_time', ['integer'])
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
                'NotificationHostsLog.state IN' => $HostNotificationConditions->getStates()
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
