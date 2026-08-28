<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Database\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\BookmarkAllocationsFilter;

/**
 * FilterBookmarkAllocations Model
 *
 * @property \App\Model\Table\FilterBookmarksTable&\Cake\ORM\Association\BelongsTo $FilterBookmarks
 * @property \App\Model\Table\ContainersTable&\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsergroupsToFilterBookmarkAllocationsTable&\Cake\ORM\Association\HasMany $UsergroupsToFilterBookmarkAllocations
 * @property \App\Model\Table\UsersToFilterBookmarkAllocationsTable&\Cake\ORM\Association\HasMany $UsersToFilterBookmarkAllocations
 *
 * @method \App\Model\Entity\FilterBookmarkAllocation newEmptyEntity()
 * @method \App\Model\Entity\FilterBookmarkAllocation newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FilterBookmarkAllocation findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\FilterBookmarkAllocation[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FilterBookmarkAllocationsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('filter_bookmark_allocations');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('FilterBookmarks', [
            'foreignKey' => 'filter_bookmark_id',
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
            'foreignKey'       => 'filter_bookmark_allocation_id',
            'targetForeignKey' => 'user_id',
            'joinTable'        => 'users_to_filter_bookmark_allocations',
            'saveStrategy'     => 'replace',
            'dependent'        => true
        ]);

        $this->belongsToMany('Usergroups', [
            'foreignKey'       => 'filter_bookmark_allocation_id',
            'targetForeignKey' => 'usergroup_id',
            'joinTable'        => 'usergroups_to_filter_bookmark_allocations',
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
            ->integer('filter_bookmark_id')
            ->requirePresence('filter_bookmark_id', 'create')
            ->allowEmptyString('filter_bookmark_id', null, false)
            ->greaterThanOrEqual('filter_bookmark_id', 1);

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
        $rules->add($rules->existsIn('filter_bookmark_id', 'FilterBookmarks'), ['errorField' => 'filter_bookmark_id']);
        $rules->add($rules->existsIn('container_id', 'Containers'), ['errorField' => 'container_id']);
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id): bool {
        return $this->exists(['FilterBookmarkAllocations.id' => $id]);
    }

    /**
     * @param BookmarkAllocationsFilter $BookmarkAllocationsFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getBookmarkAllocationsIndex(BookmarkAllocationsFilter $BookmarkAllocationsFilter, ?PaginateOMat $PaginateOMat, $User, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->contain([
                'Author'          => function (Query $query) {
                    return $query->select([
                        'author' => $query->func()->concat([
                            'Author.firstname' => 'literal',
                            ' ',
                            'Author.lastname'  => 'literal'
                        ])
                    ]);
                },
                'Users'           => function (Query $query) {
                    return $query->select([
                        'full_name' => $query->func()->concat([
                            'Users.firstname' => 'literal',
                            ' ',
                            'Users.lastname'  => 'literal'
                        ])
                    ]);
                },
                'Usergroups'      => function (Query $query) {
                    return $query->select([
                        'Usergroups.name'
                    ]);
                },
                'FilterBookmarks' => function (Query $query) {
                    return $query->select([
                        'FilterBookmarks.name',
                        'FilterBookmarks.controller'
                    ]);
                }
            ]);

        $where = $BookmarkAllocationsFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['FilterBookmarkAllocations.container_id IN'] = $MY_RIGHTS;

            $query->leftJoinWith('Users')
                ->leftJoinWith('Usergroups')
                ->where([
                    'OR' => [
                        'Users.id'      => $User->getId(),
                        'Usergroups.id' => $User->getUsergroupId()
                    ]
                ]);

        }
        $query->andWhere($where);

        $query->orderBy($BookmarkAllocationsFilter->getOrderForPaginator('FilterBookmarkAllocations.name', 'asc'));

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


    public function getFilterBookmarkAllocationForEdit($id) {
        $query = $this->find()
            ->contain([
                'Users',
                'Usergroups'
            ])
            ->where([
                'FilterBookmarkAllocations.id' => $id
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
            'FilterBookmarkAllocation' => $query
        ];
    }

    public function getAllBookmarkAllocationsByUser(User $User, string $plugin = '', string $controller = '', string $action = '') {
        $query = $this->find()
            ->leftJoinWith('Users')
            ->leftJoinWith('Usergroups');
        $query->andWhere([
                'OR' => [
                    'UsersToFilterBookmarkAllocations.user_id'           => $User->getId(),
                    'UsergroupsToFilterBookmarkAllocations.usergroup_id' => $User->getUsergroupId()
                ]
            ]
        )->groupBy([
            'FilterBookmarkAllocations.id'
        ]);
        $query->matching('FilterBookmarks', function (Query $q) use ($plugin, $controller, $action) {
            return $q->where([
                'FilterBookmarks.plugin'     => $plugin,
                'FilterBookmarks.controller' => $controller,
                'FilterBookmarks.action'     => $action,
            ]);
        });
        $query->disableHydration()
            ->all();

        $allocations = $query->toArray();
        if (empty($allocations)) {
            return [];
        }
        return $allocations;
    }

    /**
     * @param $user
     * @param $plugin
     * @param $controller
     * @param $action
     * @return array
     */
    public function getAllBookmarkAllocations(User $User, string $plugin = '', string $controller = '', string $action = '') {
        $query = $this->find()
            ->contain([
                'Users',
                'Usergroups'
            ])
            ->matching('FilterBookmarks', function (Query $q) use ($plugin, $controller, $action) {
                return $q->where([
                    'FilterBookmarks.plugin'     => $plugin,
                    'FilterBookmarks.controller' => $controller,
                    'FilterBookmarks.action'     => $action,
                ]);
            })
            ->groupBy([
                'FilterBookmarkAllocations.id'
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

    public function getAllocatedBookmarkIdsByContainerIdsAsList($containerIds, $MY_RIGHTS) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $query = $this->find();
        $query->select([
            'FilterBookmarkAllocations.id',
            'FilterBookmarkAllocations.filter_bookmark_id'
        ]);

        if (!empty($containerIds)) {
            $query->where([
                'FilterBookmarkAllocations.container_id IN' => $containerIds
            ]);
        }

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'FilterBookmarkAllocations.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->groupBy(['FilterBookmarkAllocations.filter_bookmark_id'])
            ->disableHydration()
            ->all();
        return $this->emptyArrayIfNull($query->toArray());
    }

}
