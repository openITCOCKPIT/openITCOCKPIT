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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Database\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Filter\UsercontainerrolesFilter;


/**
 * Usercontainerroles Model
 *
 * @property \App\Model\Table\UsercontainerrolesToContainersTable|\Cake\ORM\Association\HasMany $UsercontainerrolesToContainers
 * @property \App\Model\Table\UsersToUsercontainerrolesTable|\Cake\ORM\Association\HasMany $UsersToUsercontainerroles
 *
 * @method \App\Model\Entity\Usercontainerrole get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Usercontainerrole newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Usercontainerrole[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Usercontainerrole|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Usercontainerrole saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Usercontainerrole patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Usercontainerrole[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Usercontainerrole findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class UsercontainerrolesTable extends Table {
    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('usercontainerroles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        /*$this->hasMany('UsersToUsercontainerroles', [
            'foreignKey' => 'usercontainerrole_id'
        ]);*/

        $this->belongsToMany('Users', [
            'through'          => 'UsercontainerrolesMemberships',
            'className'        => 'Users',
            'joinTable'        => 'users_to_usercontainerroles',
            'foreignKey'       => 'usercontainerrole_id',
            'targetForeignKey' => 'user_id',
            //'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Containers', [
            'through'          => 'ContainersUsercontainerrolesMemberships',
            'className'        => 'Containers',
            'foreignKey'       => 'usercontainerrole_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'usercontainerroles_to_containers'
        ]);

        $this->belongsToMany('Ldapgroups', [
            'className'        => 'Ldapgroups',
            'joinTable'        => 'ldapgroups_to_usercontainerroles',
            'foreignKey'       => 'usercontainerrole_id',
            'targetForeignKey' => 'ldapgroup_id',
            'saveStrategy'     => 'replace'
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->allowEmptyString('name', null, false);

        $validator
            ->requirePresence('containers', 'create', __('You have to choose at least one container.'))
            ->allowEmptyString('containers', null, false)
            ->multipleOptions('containers', [
                'min' => 1
            ], __('You have to choose at least one container.'));

        return $validator;
    }

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Usercontainerroles.id' => $id]);
    }


    /**
     * @param GenericFilter $GenericFilter
     * @param array $selected
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getUsercontainerrolesAsList(GenericFilter $GenericFilter, array $selected = [], array $MY_RIGHTS = []): array {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        $query = $this->find();
        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query->select([
            'Usercontainerroles.id',
            'Usercontainerroles.name'
        ])
            ->contain('Containers')
            ->matching('Containers');
        if (!empty($MY_RIGHTS)) {
            $query->where([
                    'ContainersUsercontainerrolesMemberships.container_id IN' => $MY_RIGHTS
                ]
            );
        }
        $query->groupBy([
            'Usercontainerroles.id'
        ])
            ->orderBy([
                'Usercontainerroles.name' => 'asc',
                'Usercontainerroles.id'   => 'asc'
            ])
            ->disableHydration();

        $result = [];
        foreach ($query->toArray() as $record) {
            $result[$record['id']] = $record['name'];
        }
        if (!empty($selected)) {
            $query = $this->find()
                ->select([
                    'Usercontainerroles.id',
                    'Usercontainerroles.name'
                ])
                ->contain('Containers')
                ->matching('Containers');
            if (!empty($MY_RIGHTS)) {
                $query->where([
                        'ContainersUsercontainerrolesMemberships.container_id IN' => $MY_RIGHTS
                    ]
                );
            }
            $query
                ->where([
                    'Usercontainerroles.id IN' => $selected
                ])
                ->groupBy([
                    'Usercontainerroles.id'
                ])
                ->orderBy([
                    'Usercontainerroles.name' => 'asc',
                    'Usercontainerroles.id'   => 'asc'
                ])->disableHydration();

            foreach ($query->toArray() as $record) {
                $result[$record['id']] = $record['name'];
            }
        }
        return $result;
    }

    /**
     * @param UsercontainerrolesFilter $UsercontainerrolesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getUsercontainerRolesIndex(UsercontainerrolesFilter $UsercontainerrolesFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->disableHydration()
            ->contain([
                'Containers',
                'Users' => function (Query $q) {
                    $q->disableAutoFields();
                    $q->contain([
                        'Usercontainerroles' => [
                            'Containers'
                        ],
                        'Containers'
                    ])
                        ->select([
                            'Users.id',
                            'Users.firstname',
                            'Users.lastname',
                            'full_name' => $q->func()->concat([
                                'Users.firstname' => 'literal',
                                ' ',
                                'Users.lastname'  => 'literal'
                            ])
                        ])->orderBy('full_name');
                    return $q;
                }
            ])
            ->matching('Containers')
            ->where([
                'ContainersUsercontainerrolesMemberships.container_id IN' => $MY_RIGHTS,
                $UsercontainerrolesFilter->indexFilter()
            ])
            ->orderBy(array_merge(
                    $UsercontainerrolesFilter->getOrderForPaginator('Usercontainerroles.name', 'asc'),
                    ['Usercontainerroles.id' => 'asc']
                )
            )
            ->groupBy([
                'Usercontainerroles.id'
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

    /**
     * @param int $id
     * @return array
     */
    public function getUserContainerRoleForEdit($id) {
        $query = $this->find()
            ->where([
                'Usercontainerroles.id' => $id
            ])
            ->contain([
                'Containers',
                'Ldapgroups' => [
                    'fields' => [
                        'Ldapgroups.id'
                    ]
                ]
            ])
            ->disableHydration()
            ->first();


        $usercontainerrole = $query;

        $usercontainerrole['containers'] = [
            '_ids' => Hash::extract($query, 'containers.{n}.id')
        ];
        $usercontainerrole['ldapgroups'] = [
            '_ids' => Hash::extract($query, 'ldapgroups.{n}.id')
        ];

        //Build up data struct for radio inputs
        $usercontainerrole['ContainersUsercontainerrolesMemberships'] = [];
        foreach ($query['containers'] as $container) {
            //Don't cast permission_level to string for Angular (old method for AngularJS removed...)
            $usercontainerrole['ContainersUsercontainerrolesMemberships'][$container['id']] = $container['_joinData']['permission_level'];
        }

        return [
            'Usercontainerrole' => $usercontainerrole
        ];
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getContainerPermissionsByUserContainerRoleIds($ids = []) {
        $query = $this->find()
            ->contain('Containers')
            ->where([
                'Usercontainerroles.id IN' => $ids
            ])
            ->disableHydration()
            ->all();

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $result = [];
        foreach ($query->toArray() as $record) {
            foreach ($record['containers'] as $index => $container) {
                $record['containers'][$index]['path'] = $ContainersTable->getPathByIdAsString($container['id']);
            }
            $result[$record['id']] = $record;
        }

        return $result;
    }

    /**
     * @param array $memberOfGroups
     * @return array
     */
    public function getContainerPermissionsByLdapUserMemberOf($memberOfGroups = []) {
        if (empty($memberOfGroups)) {
            return [];
        }
        if (!is_array($memberOfGroups)) {
            $memberOfGroups = [$memberOfGroups];
        }
        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->innerJoinWith('Ldapgroups')
            ->where([
                'Ldapgroups.dn IN' => $memberOfGroups
            ])
            ->disableHydration()
            ->all();

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $result = [];
        foreach ($query->toArray() as $record) {
            foreach ($record['containers'] as $index => $container) {
                $record['containers'][$index]['path'] = $ContainersTable->getPathByIdAsString($container['id']);
            }
            $result[$record['id']] = $record;
        }

        return $result;
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getUsercontainerrolesForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['Usercontainerroles.id IN' => $ids])
            ->contain([
                'Containers',
            ])
            ->matching('Containers')
            ->orderBy(['Usercontainerroles.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'ContainersUsercontainerrolesMemberships.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->groupBy([
            'Usercontainerroles.id'
        ]);

        $query->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param $id
     * @param array $MY_RIGHTS
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getSourceUserContainerRoleForCopy($id, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where([
                'Usercontainerroles.id' => $id
            ])
            ->contain([
                'Containers',
                'Ldapgroups' => [
                    'fields' => [
                        'Ldapgroups.id'
                    ]
                ]
            ])
            ->disableHydration()
            ->first();


        $usercontainerrole = $query;


        $usercontainerrole['containers'] = [
            '_ids' => Hash::extract($query, 'containers.{n}.id')
        ];
        $usercontainerrole['ldapgroups'] = [
            '_ids' => Hash::extract($query, 'ldapgroups.{n}.id')
        ];


        //Build up data struct for radio inputs
        $usercontainerrole['ContainersUsercontainerrolesMemberships'] = [];
        foreach ($query['containers'] as $container) {
            $usercontainerrole['ContainersUsercontainerrolesMemberships'][$container['id']] = (int)$container['_joinData']['permission_level'];
        }

        return $usercontainerrole;
    }


    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedUsercontainerrolesByContainerId(int $containerId) {
        $query = $this->find()
            ->innerJoinWith('Containers')
            ->contain([
                'Containers' => function (\Cake\ORM\Query $query) use ($containerId) {
                    return $query->select([
                        'Containers.id',
                    ])->whereNotInList('Containers.id', [$containerId]);
                }
            ])
            ->where(['Containers.id' => $containerId]);

        $result = $query->all();
        $usercontainerroles = $result->toArray();

        // Check each user container role, if it as more than one container.
        // If user container role has more than 1 container, we can keep this each user container role because is not orphaned
        $orphanedUsercontainerroles = [];
        foreach ($usercontainerroles as $usercontainerrole) {
            if (empty($usercontainerrole->containers)) {
                $orphanedUsercontainerroles[] = $usercontainerrole;
            }
        }

        return $orphanedUsercontainerroles;
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param string $index
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getContainerRoleByContainerIdExact(int $containerId, $type = 'all', $index = 'id', $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Usercontainerroles.id',
                'Usercontainerroles.name'
            ])
            ->innerJoinWith('Containers', function (\Cake\ORM\Query $q) use ($containerId, $MY_RIGHTS) {
                $q->disableAutoFields()
                    ->select([
                        'Containers.id'
                    ])
                    ->where([
                        'Containers.id' => $containerId
                    ]);
                if (!empty($MY_RIGHTS)) {
                    $q->andWhere([
                        'Containers.id IN' => $MY_RIGHTS
                    ]);
                }
                return $q;
            })
            ->disableHydration();
        $result = $query->toArray();

        if (empty($result)) {
            return [];
        }

        if ($type === 'all') {
            return $result;
        }

        $list = [];
        foreach ($result as $row) {
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getUserContainerRoleById($id) {
        return $this->find()
            ->where([
                'Usercontainerroles.id' => $id
            ])
            ->disableHydration()
            ->first();
    }

    /*
     * @param array $userRoleContainerIds
     */
    public function getUsercontanerRoleWithAllContainerIdsByIds($userRoleContainerIds = []) {
        if (!is_array($userRoleContainerIds)) {
            $userRoleContainerIds = [$userRoleContainerIds];
        }
        return $this->find('list',
            keyField: 'id',
            valueField: function ($row) {
                return Hash::extract($row['containers'], '{n}.id');
            })->select([
            'Usercontainerroles.id'
        ])->contain([
            'Containers' => function (\Cake\ORM\Query $q) {
                return $q->select([
                    'Containers.id'
                ])->disableAutoFields();
            }
        ])->where(['Usercontainerroles.id IN' => $userRoleContainerIds])
            ->all()
            ->toArray();
    }

    /**
     * @param array $MY_WRITABLE_CONTAINERS
     * @return array
     */
    public function getUsercontainerrolesForPermissionCheck(array $MY_WRITABLE_CONTAINERS = []): array {
        if (!is_array($MY_WRITABLE_CONTAINERS)) {
            $MY_WRITABLE_CONTAINERS = [$MY_WRITABLE_CONTAINERS];
        }

        $query = $this->find();

        $query->select([
            'Usercontainerroles.id',
            'Usercontainerroles.name'
        ])
            ->contain('Containers')
            ->matching('Containers');
        if (!empty($MY_WRITABLE_CONTAINERS)) {
            $query->where([
                    'ContainersUsercontainerrolesMemberships.container_id IN' => $MY_WRITABLE_CONTAINERS
                ]
            );
        }
        $query->groupBy([
            'Usercontainerroles.id'
        ])
            ->orderBy([
                'Usercontainerroles.name' => 'asc',
                'Usercontainerroles.id'   => 'asc'
            ])
            ->disableHydration();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        $filteredUserContainerRoles = [];

        // clean up user container roles
        foreach ($result as $userContainerRole) {
            $userContainerRolesWithContainerIDs = Hash::extract(
                $userContainerRole,
                'containers.{n}.id'
            );
            if (!empty($userContainerRolesWithContainerIDs)) {
                //Check if user has rights to see all containers in container user role
                if (empty(array_diff(
                    $userContainerRolesWithContainerIDs,
                    $MY_WRITABLE_CONTAINERS)
                )) {
                    $filteredUserContainerRoles[] = $userContainerRole;
                }
            }
        }
        return $filteredUserContainerRoles;
    }
}
