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

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\Host;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostdependenciesFilter;

/**
 * Hostdependencies Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\HostdependenciesTable|\Cake\ORM\Association\HasMany $Hosts
 * @property \App\Model\Table\HostdependenciesTable|\Cake\ORM\Association\HasMany $Hostgroups
 *
 * @method \App\Model\Entity\Hostdependency get($primaryKey, $options = [])
 * @method \App\Model\Entity\Hostdependency newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Hostdependency[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Hostdependency|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hostdependency|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hostdependency patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Hostdependency[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Hostdependency findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class HostdependenciesTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;
    use CustomValidationTrait;


    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);
        $this->addBehavior('Timestamp');

        $this->setTable('hostdependencies');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);
        $this->belongsTo('Timeperiods', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'timeperiod_id',
            'joinType'   => 'LEFT'
        ]);

        $this->belongsToMany('Hosts', [
            'className'    => 'Hosts',
            'through'      => 'HostdependenciesHostMemberships',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('HostsDependent', [
            'className'        => 'Hosts',
            'through'          => 'HostdependenciesHostMemberships',
            'targetForeignKey' => 'host_id',
            'saveStrategy'     => 'replace'
        ]);
        $this->belongsToMany('Hostgroups', [
            'through'      => 'HostdependenciesHostgroupMemberships',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('HostgroupsDependent', [
            'className'        => 'Hostgroups',
            'through'          => 'HostdependenciesHostgroupMemberships',
            'targetForeignKey' => 'hostgroup_id',
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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('container_id')
            ->greaterThan('container_id', 0)
            ->requirePresence('container_id')
            ->allowEmptyString('container_id', null, false);

        $validator
            ->requirePresence('hosts', true, __('You have to choose at least one host.'))
            ->allowEmptyString('hosts', null, false)
            ->multipleOptions('hosts', [
                'min' => 1
            ], __('You have to choose at least one host.'));

        $validator
            ->requirePresence('hosts_dependent', true, __('You have to choose at least one dependent host.'))
            ->allowEmptyString('hosts_dependent', null, false)
            ->multipleOptions('hosts_dependent', [
                'min' => 1
            ], __('You have to choose at least one dependent host.'));

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
        $rules->add($rules->isUnique(['uuid']));

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Hostdependencies.id' => $id]);
    }

    /**
     * @param HostdependenciesFilter $HostdependenciesFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostdependenciesIndex(HostdependenciesFilter $HostdependenciesFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Timeperiods'    => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Timeperiods.id',
                            'Timeperiods.name'
                        ]);
                },
                'Hosts'          => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'HostdependenciesHostMemberships.dependent' => 0
                        ])
                        ->select([
                            'Hosts.id',
                            'Hosts.name',
                            'Hosts.disabled'
                        ]);
                },
                'HostsDependent' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'HostdependenciesHostMemberships.dependent' => 1
                        ])
                        ->select([
                            'HostsDependent.id',
                            'HostsDependent.name',
                            'HostsDependent.disabled'
                        ]);
                },

                'Hostgroups'          => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'HostdependenciesHostgroupMemberships.dependent' => 0
                            ])
                            ->select([
                                'Hostgroups.id',
                                'Containers.name'
                            ]);
                    },
                ],
                'HostgroupsDependent' => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'HostdependenciesHostgroupMemberships.dependent' => 1
                            ])
                            ->select([
                                'HostgroupsDependent.id',
                                'Containers.name'
                            ]);
                    },
                ]
            ])
            ->groupBy('Hostdependencies.id')
            ->disableHydration();
        $indexFilter = $HostdependenciesFilter->indexFilter();
        $containFilter = [
            'Hosts.name'               => '',
            'HostsDependent.name'      => '',
            'Hostgroups.name'          => '',
            'HostgroupsDependent.name' => ''
        ];
        if (!empty($indexFilter['Hosts.name LIKE'])) {
            $containFilter['Hosts.name'] = [
                'Hosts.name LIKE' => $indexFilter['Hosts.name LIKE']
            ];
            $query->innerJoinWith('Hosts', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostdependenciesHostMemberships.dependent' => 0,
                    $containFilter['Hosts.name']
                ]);
            });
            unset($indexFilter['Hosts.name LIKE']);
        }
        if (!empty($indexFilter['HostsDependent.name LIKE'])) {
            $containFilter['HostsDependent.name'] = [
                'HostsDependent.name LIKE' => $indexFilter['HostsDependent.name LIKE']
            ];
            $query->innerJoinWith('HostsDependent', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostdependenciesHostMemberships.dependent' => 1,
                    $containFilter['HostsDependent.name']
                ]);
            });
            unset($indexFilter['HostsDependent.name LIKE']);

        }
        if (!empty($indexFilter['Hostgroups.name LIKE'])) {
            $containFilter['Hostgroups.name'] = [
                'Containers.name LIKE' => $indexFilter['Hostgroups.name LIKE']
            ];
            $query->innerJoinWith('Hostgroups.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostdependenciesHostgroupMemberships.dependent' => 0,
                    $containFilter['Hostgroups.name']
                ]);
            });
            unset($indexFilter['Hostgroups.name LIKE']);
        }
        if (!empty($indexFilter['HostgroupsDependent.name LIKE'])) {
            $containFilter['HostgroupsDependent.name'] = [
                'Containers.name LIKE' => $indexFilter['HostgroupsDependent.name LIKE']
            ];
            $query->innerJoinWith('HostgroupsDependent.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostdependenciesHostgroupMemberships.dependent' => 1,
                    $containFilter['HostgroupsDependent.name']
                ]);
            });
            unset($indexFilter['HostgroupsDependent.name LIKE']);
        }
        if (!empty($MY_RIGHTS)) {
            $indexFilter['Hostdependencies.container_id IN'] = $MY_RIGHTS;
        }
        $query->where($indexFilter);
        $query->orderBy($HostdependenciesFilter->getOrderForPaginator('Hostdependencies.id', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler(), false);
            }
        }

        return $result;
    }

    /**
     * @param array|int $hosts
     * @param array|int $dependent_hosts
     * @return array
     */
    public function parseHostMembershipData($hosts = [], $dependent_hosts = []) {
        $hostmembershipData = [];
        foreach ($hosts as $host) {
            $hostmembershipData[] = [
                'id'        => $host,
                '_joinData' => [
                    'dependent' => 0
                ]
            ];
        }
        foreach ($dependent_hosts as $dependent_host) {
            $hostmembershipData[] = [
                'id'        => $dependent_host,
                '_joinData' => [
                    'dependent' => 1
                ]
            ];
        }
        return $hostmembershipData;
    }

    /**
     * @param array $hostgroups
     * @param array $dependent_hostgroups
     * @return array
     */
    public function parseHostgroupMembershipData($hostgroups = [], $dependent_hostgroups = []) {
        $hostgroupmembershipData = [];
        foreach ($hostgroups as $hostgroup) {
            $hostgroupmembershipData[] = [
                'id'        => $hostgroup,
                '_joinData' => [
                    'dependent' => 0
                ]
            ];
        }
        foreach ($dependent_hostgroups as $dependent_hostgroup) {
            $hostgroupmembershipData[] = [
                'id'        => $dependent_hostgroup,
                '_joinData' => [
                    'dependent' => 1
                ]
            ];
        }
        return $hostgroupmembershipData;
    }

    /**
     * @param null|string $uuid
     * @return array
     */
    public function getHostdependenciesForExport($uuid = null) {
        $query = $this->find()
            ->contain([
                'Hosts'       =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'Hosts.disabled' => 0
                            ])
                            ->select([
                                'id',
                                'uuid'
                            ]);
                    },
                'Hostgroups'  =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'id',
                                'uuid'
                            ]);
                    },
                'Timeperiods' =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid']);
                    }
            ])
            ->select([
                'id',
                'uuid',
                'inherits_parent',
                'execution_fail_on_up',
                'execution_fail_on_down',
                'execution_fail_on_unreachable',
                'execution_fail_on_pending',
                'execution_none',
                'notification_fail_on_up',
                'notification_fail_on_down',
                'notification_fail_on_unreachable',
                'notification_fail_on_pending',
                'notification_none'
            ]);
        if ($uuid !== null) {
            $query->where([
                'Hostdependencies.uuid' => $uuid
            ]);
        }

        $query->all();

        return $query;
    }

    /**
     * @param int|null $id
     * @param int|null $hostId
     * @return bool
     */
    public function isHostdependencyBroken($id = null, $hostId = null) {
        if (!$this->exists(['Hostdependencies.id' => $id]) && $id !== null) {
            throw new NotFoundException();
        }
        $query = $this->find()
            ->contain([
                'Hosts' =>
                    function (Query $q) use ($hostId) {
                        if ($hostId !== null) {
                            $q->where([
                                'Hosts.id !=' => $hostId
                            ]);
                        }
                        return $q->enableAutoFields(false)
                            ->where([
                                'Hosts.disabled' => 0
                            ])
                            ->select(['id']);
                    },
            ])->select([
                'id'
            ])->where([
                'Hostdependencies.id' => $id
            ])->first();

        $hosts = $query->get('hosts');
        $masterHostsForCfg = [];
        $dependentHostsForCfg = [];
        foreach ($hosts as $host) {
            /** @var Host $host */
            if ($host->get('_joinData')->get('dependent') === 0) {
                $masterHostsForCfg[] = $host->get('id');
            } else {
                $dependentHostsForCfg[] = $host->get('id');
            }
        }

        return empty($masterHostsForCfg) || empty($dependentHostsForCfg);
    }

    /**
     * @param $id
     * @return array
     */
    public function getHostdependencyById($id) {
        $query = $this->find()
            ->where([
                'Hostdependencies.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByHostdependencies($timeperiodId) {
        $count = $this->find()
            ->where([
                'timeperiod_id' => $timeperiodId,
            ])->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $timeperiodId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getHostdependenciesByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Hostdependencies.id'
            ])
            ->where([
                'timeperiod_id' => $timeperiodId,

            ]);

        if (!empty($MY_RIGHTS)) {
            $query->where(['Hostdependencies.container_id IN' => $MY_RIGHTS]);
        }
        $query->enableHydration($enableHydration);

        $result = $query->all();
        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param array $MY_RIGHTS
     * @param array $where
     * @return array
     */
    public function getHostdependenciesByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Hostdependencies.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Hostdependencies.' . $index,
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Hostdependencies.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        if ($type === 'all') {
            return $result;
        }

        $list = [];
        foreach ($result as $row) {
            $list[$row[$index]] = __('Host dependency #{0}', $row['id']);
        }

        return $list;
    }

}
