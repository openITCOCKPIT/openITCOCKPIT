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

namespace App\Model\Table;

use App\itnovum\openITCOCKPIT\Filter\SystemHealthUsersFilter;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Class __SystemHealthUsersTable
 * @package App\Model\Table
 *
 * This is a Table Object for an linking table xxx_to_yyy
 * Only use this Table object for find()->count() operations!
 */
class SystemHealthUsersTable extends Table {

    use CustomValidationTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('system_health_users');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'   => 'INNER'
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
            ->requirePresence('user_id', 'create')
            ->integer('user_id')
            ->allowEmptyString('user_id', null, false);

        $validator->add('notify_on_recovery', 'custom', [
            'rule'    => [$this, 'checkNotificationOptionsSystemHealth'], //\App\Lib\Traits\CustomValidationTrait
            'message' => __('You must specify at least one notification option.')
        ]);

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
        return $rules;
    }

    /**
     * @param $notify_on_warning
     * @param $notify_on_critical
     * @param $notify_on_recovery
     * @return array
     */
    public function getUsersForNotifications($notify_on_warning, $notify_on_critical, $notify_on_recovery) {

        $query = $this->find();
        $query->select([
            'full_name' => $query->func()->concat([
                'Users.firstname' => 'literal',
                ' ',
                'Users.lastname'  => 'literal'
            ]),
            'Users.email',
        ])
            ->contain(['Users']);

        if (!empty($notify_on_warning)) {
            $query->where([
                'SystemHealthUsers.notify_on_warning' => $notify_on_warning,
            ]);
        }

        if (!empty($notify_on_critical)) {
            $query->where([
                'SystemHealthUsers.notify_on_critical' => $notify_on_critical,
            ]);
        }

        if (!empty($notify_on_recovery)) {
            $query->where([
                'SystemHealthUsers.notify_on_recovery' => $notify_on_recovery,
            ]);
        }

        $query->disableHydration()->all();

        if (empty($query)) {
            return [];
        }

        $users = [];
        foreach ($query as $record) {

            $users[$record['user']['email']] = $record['full_name'];

        }

        return $users;

    }

    /**
     * @param int $id
     * @return array
     */
    public function getUserForEdit($id): array {

        $query = $this->find()->select([
            'SystemHealthUsers.user_id',
            'SystemHealthUsers.notify_on_warning',
            'SystemHealthUsers.notify_on_critical',
            'SystemHealthUsers.notify_on_recovery',
            'Users.firstname',
            'Users.lastname',
            'Users.email',
        ])
            ->contain([
                'Users',
            ])
            ->where([
                'SystemHealthUsers.id' => $id
            ])
            ->disableHydration();

        if (empty($query)) {
            return [];
        }

        $user = $query->first();

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $query = $UsersTable->find()
            ->where([
                'Users.id IS' => $user['user_id']
            ])
            ->contain([
                'Containers',
                'Usercontainerroles' => [
                    'Containers'
                ],
            ])
            ->disableHydration()->first();

        $user['containers'] = [
            '_ids' => Hash::extract($query, 'containers.{n}.id')
        ];

        $user['usercontainerroles'] = [
            '_ids' => Hash::extract($query, 'usercontainerroles.{n}._joinData[through_ldap=false].usercontainerrole_id')
        ];
        $user['usercontainerroles_ldap'] = [
            '_ids' => Hash::extract($query, 'usercontainerroles.{n}._joinData[through_ldap=true].usercontainerrole_id')
        ];
        $user['usercontainerroles_containerids'] = [
            '_ids' => Hash::extract($query, 'usercontainerroles.{n}.containers.{n}.id')
        ];

        return $user;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getSystemHealthUserByUserId($id): array {

        $query = $this->find()
            ->where([
                'SystemHealthUsers.user_id' => $id
            ])
            ->disableHydration();

        if (empty($query)) {
            return [];
        }

        return $query->first();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['SystemHealthUsers.id' => $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsByUserId($id) {
        return $this->exists(['SystemHealthUsers.user_id' => $id]);
    }

    /**
     * @param SystemHealthUsersFilter $ContactgroupsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getSystemHealthUsersIndex(SystemHealthUsersFilter $SystemHealthUsersFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        //Get all user ids where container assigned are made directly at the user
        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $query = $UsersTable->find()
            ->select([
                'Users.id'
            ])
            ->matching('Containers')
            ->groupBy([
                'Users.id'
            ])
            ->disableHydration();

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'ContainersUsersMemberships.container_id IN' => $MY_RIGHTS
            ]);
        }
        $userIds = Hash::extract($query->toArray(), '{n}.id');

        //Get all user ids where container assigned are made through an user container role
        $query = $UsersTable->find()
            ->select([
                'Users.id'
            ])
            ->matching('Usercontainerroles.Containers')
            ->groupBy([
                'Users.id'
            ])
            ->disableHydration();

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        $userIdsThroughContainerRoles = Hash::extract($query->toArray(), '{n}.id');

        $userIds = array_unique(array_merge($userIds, $userIdsThroughContainerRoles));

        $where = $SystemHealthUsersFilter->indexFilter();
        $having = [];
        if (isset($where['full_name LIKE'])) {
            $having['full_name LIKE'] = $where['full_name LIKE'];
            unset($where['full_name LIKE']);
        }

        $query = $UsersTable->find();
        $query->select([
            'Users.id',
            'Users.email',
            'full_name' => $query->func()->concat([
                'Users.firstname' => 'literal',
                ' ',
                'Users.lastname'  => 'literal'
            ]),
            'SystemHealthUsers.id',
            'SystemHealthUsers.notify_on_warning',
            'SystemHealthUsers.notify_on_critical',
            'SystemHealthUsers.notify_on_recovery',
        ])
            ->matching('SystemHealthUsers')
            ->contain([
                'SystemHealthUsers',
                'Containers',
                'Usercontainerroles' => [
                    'Containers'
                ]
            ])
            ->where([
                'Users.is_active' => 1
            ]);

        if (!empty($userIds)) {
            $query->where([
                'Users.id IN' => $userIds
            ]);
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }
        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $SystemHealthUsersFilter->getOrderForPaginator('full_name', 'asc'),
                ['Users.id' => 'asc']
            )
        );
        $query->groupBy([
            'Users.id'
        ]);

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
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
