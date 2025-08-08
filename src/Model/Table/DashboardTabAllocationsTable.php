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

declare(strict_types=1);

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Database\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\DashboardTabAllocationsFilter;

/**
 * DashboardTabAllocations Model
 *
 * @property \App\Model\Table\DashboardTabsTable&\Cake\ORM\Association\BelongsTo $DashboardTabs
 * @property \App\Model\Table\ContainersTable&\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsergroupsToDashboardTabAllocationsTable&\Cake\ORM\Association\HasMany $UsergroupsToDashboardTabAllocations
 * @property \App\Model\Table\UsersToDashboardTabAllocationsTable&\Cake\ORM\Association\HasMany $UsersToDashboardTabAllocations
 *
 * @method \App\Model\Entity\DashboardTabAllocation newEmptyEntity()
 * @method \App\Model\Entity\DashboardTabAllocation newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation get($primaryKey, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\DashboardTabAllocation[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DashboardTabAllocationsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('dashboard_tab_allocations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('DashboardTabs', [
            'foreignKey' => 'dashboard_tab_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsTo('Author', [
            'className'  => 'Users',
            'foreignKey' => 'user_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsToMany('Users', [
            'foreignKey'       => 'dashboard_tab_allocation_id',
            'targetForeignKey' => 'user_id',
            'joinTable'        => 'users_to_dashboard_tab_allocations',
            'saveStrategy'     => 'replace',
            'dependent'        => true
        ]);

        $this->belongsToMany('Usergroups', [
            'foreignKey'       => 'dashboard_tab_allocation_id',
            'targetForeignKey' => 'usergroup_id',
            'joinTable'        => 'usergroups_to_dashboard_tab_allocations',
            'saveStrategy'     => 'replace',
            'dependent'        => true
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('dashboard_tab_id')
            ->requirePresence('dashboard_tab_id', 'create')
            ->allowEmptyString('dashboard_tab_id', null, false)
            ->greaterThanOrEqual('dashboard_tab_id', 1);

        $validator
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->allowEmptyString('user_id', null, false)
            ->greaterThanOrEqual('user_id', 1);

        $validator
            ->boolean('pinned')
            ->notEmptyString('pinned');

        $validator
            ->add('users', 'custom', [
                'rule'    => [$this, 'atLeastOneUserOrUsergroup'],
                'message' => __('You have to choose at least one user or one user role.')
            ]);

        $validator
            ->add('usergroups', 'custom', [
                'rule'    => [$this, 'atLeastOneUserOrUsergroup'],
                'message' => __('You have to choose at least one user or one user role.')
            ]);

        return $validator;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for users and or user groups
     */
    public function atLeastOneUserOrUsergroup($value, $context) {
        return !empty($context['data']['users']['_ids']) || !empty($context['data']['usergroups']['_ids']);
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn('dashboard_tab_id', 'DashboardTabs'), ['errorField' => 'dashboard_tab_id']);
        $rules->add($rules->existsIn('container_id', 'Containers'), ['errorField' => 'container_id']);
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id): bool {
        return $this->exists(['DashboardTabAllocations.id' => $id]);
    }

    /**
     * @param DashboardTabAllocationsFilter $DashboardTabAllocationsFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getDashboardTabAllocationsIndex(DashboardTabAllocationsFilter $DashboardTabAllocationsFilter, ?PaginateOMat $PaginateOMat, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->contain([
                'Author'        => function (Query $query) {
                    return $query->select([
                        'author' => $query->func()->concat([
                            'Author.firstname' => 'literal',
                            ' ',
                            'Author.lastname'  => 'literal'
                        ])
                    ]);
                },
                'Users'         => function (Query $query) {
                    return $query->select([
                        'full_name' => $query->func()->concat([
                            'Users.firstname' => 'literal',
                            ' ',
                            'Users.lastname'  => 'literal'
                        ])
                    ]);
                },
                'Usergroups'    => function (Query $query) {
                    return $query->select([
                        'Usergroups.name'
                    ]);
                },
                'DashboardTabs' => function (Query $query) {
                    return $query->select([
                        'DashboardTabs.name'
                    ]);
                }
            ]);

        $where = $DashboardTabAllocationsFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['DashboardTabAllocations.container_id IN'] = $MY_RIGHTS;
        }
        $query->where($where);

        $query->orderBy($DashboardTabAllocationsFilter->getOrderForPaginator('DashboardTabAllocations.name', 'asc'));

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

    public function getDashboardTabAllocationForEdit($id) {
        $query = $this->find()
            ->contain([
                'Author' => function (Query $q) {
                    // User who created the allocation
                    // Do not leak any sensitive data like password (even if it is hashed)!
                    $q
                        ->disableAutoFields()
                        ->select([
                            'Author.id',
                            'Author.firstname',
                            'Author.lastname',
                        ]);
                    return $q;
                },
                'Users',     // Users who are forced to use this dashboard
                'Usergroups' // User groups who are forced to use this dashboard
            ])
            ->where([
                'DashboardTabAllocations.id' => $id
            ])
            ->disableHydration()
            ->first();

        if (empty($query)) {
            return [];
        }

        $query['users'] = [
            '_ids' => Hash::extract($query, 'users.{n}.id')
        ];
        $query['usergroups'] = [
            '_ids' => Hash::extract($query, 'usergroups.{n}.id')
        ];

        return [
            'DashboardAllocation' => $query
        ];
    }

    public function getAllDashboardAllocationsByUser(User $User) {
        $query = $this->find()
            ->leftJoinWith('Users')
            ->leftJoinWith('Usergroups');
        $query->andWhere([
                'OR' => [
                    'UsersToDashboardTabAllocations.user_id'           => $User->getId(),
                    'UsergroupsToDashboardTabAllocations.usergroup_id' => $User->getUsergroupId()
                ]
            ]
        )->groupBy([
            'DashboardTabAllocations.id'
        ]);
        $query->disableHydration()
            ->all();
        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param $MY_RIGHTS
     * @return array
     */
    public function getAllDashboardAllocations($MY_RIGHTS = []) {
        $query = $this->find()
            ->contain([
                'Users',
                'Usergroups'
            ])
            ->disableHydration()
            ->all();
        $allocations = $query->toArray();
        if (empty($allocations)) {
            return [];
        }
        foreach ($allocations as $key => $allocation) {
            $allocations[$key]['users'] = [
                '_ids' => Hash::extract($allocations[$key]['users'], '{n}.id')
            ];
            $allocations[$key]['usergroups'] = [
                '_ids' => Hash::extract($allocations[$key]['usergroups'], '{n}.id')
            ];
        }
        return $allocations;
    }

    /**
     * @param $containerIds
     * @param $MY_RIGHTS
     * @return array
     */
    public function getAllocatedDashboardTabsIdsByContainerIdsAsList($containerIds, $MY_RIGHTS) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $query = $this->find();
        $query->select([
            'DashboardTabAllocations.id',
            'DashboardTabAllocations.dashboard_tab_id'
        ]);

        if (!empty($containerIds)) {
            $query->where([
                'DashboardTabAllocations.container_id IN' => $containerIds
            ]);
        }

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'DashboardTabAllocations.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->groupBy(['DashboardTabAllocations.dashboard_tab_id'])
            ->disableHydration()
            ->all();
        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int $tabId
     * @param User $User
     * @return array
     */
    public function getDashboardAllocationTabIdForUser(int $tabId, User $User): array {
        $query = $this->find()
            ->leftJoinWith('Users')
            ->leftJoinWith('Usergroups')
            ->contain('DashboardTabs');
        $query->where([
                'OR' => [
                    'UsersToDashboardTabAllocations.user_id'           => $User->getId(),
                    'UsergroupsToDashboardTabAllocations.usergroup_id' => $User->getUsergroupId()
                ]
            ]
        );
        $query->andWhere([
            'DashboardTabAllocations.dashboard_tab_id' => $tabId
        ]);

        $query->disableHydration();
        $result = $query->first();

        if (empty($result)) {
            return [];
        }

        return $result;
    }
}
