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
use App\Model\Entity\Service;
use Cake\Database\Expression\Comparison;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServicedependenciesFilter;

/**
 * Servicedependencies Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ServicedependenciesTable|\Cake\ORM\Association\HasMany $Services
 * @property \App\Model\Table\ServicedependenciesTable|\Cake\ORM\Association\HasMany $Servicegroups
 *
 * @method \App\Model\Entity\Servicedependency get($primaryKey, $options = [])
 * @method \App\Model\Entity\Servicedependency newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Servicedependency[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Servicedependency|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicedependency|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicedependency patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Servicedependency[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Servicedependency findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServicedependenciesTable extends Table {

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

        $this->setTable('servicedependencies');
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

        $this->belongsToMany('Services', [
            'className'    => 'Services',
            'through'      => 'ServicedependenciesServiceMemberships',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('ServicesDependent', [
            'className'        => 'Services',
            'through'          => 'ServicedependenciesServiceMemberships',
            'targetForeignKey' => 'service_id',
            'saveStrategy'     => 'replace'
        ]);
        $this->belongsToMany('Servicegroups', [
            'through'      => 'ServicedependenciesServicegroupMemberships',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('ServicegroupsDependent', [
            'className'        => 'Servicegroups',
            'through'          => 'ServicedependenciesServicegroupMemberships',
            'targetForeignKey' => 'servicegroup_id',
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
            ->requirePresence('services', true, __('You have to choose at least one service.'))
            ->allowEmptyString('services', null, false)
            ->multipleOptions('services', [
                'min' => 1
            ], __('You have to choose at least one service.'));

        $validator
            ->requirePresence('services_dependent', true, __('You have to choose at least one dependent service.'))
            ->allowEmptyString('services_dependent', null, false)
            ->multipleOptions('services_dependent', [
                'min' => 1
            ], __('You have to choose at least one dependent service.'));

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
        return $this->exists(['Servicedependencies.id' => $id]);
    }

    /**
     * @param ServicedependenciesFilter $ServicedependenciesFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicedependenciesIndex(ServicedependenciesFilter $ServicedependenciesFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Timeperiods'       => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Timeperiods.id',
                            'Timeperiods.name'
                        ]);
                },
                'Services'          => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'ServicedependenciesServiceMemberships.dependent' => 0
                        ])
                        ->innerJoinWith('Servicetemplates')
                        ->innerJoinWith('Hosts')
                        ->select([
                            'Services.id',
                            'servicename' => $q->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                            'Services.disabled'
                        ]);
                },
                'ServicesDependent' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'ServicedependenciesServiceMemberships.dependent' => 1
                        ])
                        ->innerJoinWith('Servicetemplates')
                        ->innerJoinWith('Hosts')
                        ->select([
                            'ServicesDependent.id',
                            'servicename' => $q->newExpr('CONCAT(Hosts.name, "/", IF(ServicesDependent.name IS NULL, Servicetemplates.name, ServicesDependent.name))'),
                            'ServicesDependent.disabled'
                        ]);
                },

                'Servicegroups'          => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'ServicedependenciesServicegroupMemberships.dependent' => 0
                            ])
                            ->select([
                                'Servicegroups.id',
                                'Containers.name'
                            ]);
                    },
                ],
                'ServicegroupsDependent' => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'ServicedependenciesServicegroupMemberships.dependent' => 1
                            ])
                            ->select([
                                'ServicegroupsDependent.id',
                                'Containers.name'
                            ]);
                    },
                ]
            ])
            ->groupBy('Servicedependencies.id')
            ->disableHydration();

        $indexFilter = $ServicedependenciesFilter->indexFilter();
        $containFilter = [
            'Servicegroups.name'          => '',
            'ServicegroupsDependent.name' => ''
        ];

        if (!empty($indexFilter['Services.servicename LIKE'])) {
            $query->innerJoinWith('Services', function (Query $q) use ($indexFilter) {
                return $q->innerJoinWith('Hosts')
                    ->innerJoinWith('Servicetemplates');
            });
            $where = new Comparison(
                'CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))',
                $indexFilter['Services.servicename LIKE'],
                'string',
                'LIKE'
            );
            $query->where([
                'ServicedependenciesServiceMemberships.dependent' => 0,
                $where
            ]);
        }

        unset($indexFilter['Services.servicename LIKE']);

        if (!empty($indexFilter['ServicesDependent.servicename LIKE'])) {
            $query->innerJoinWith('ServicesDependent', function (Query $q) use ($indexFilter) {
                return $q->innerJoinWith('Hosts')
                    ->innerJoinWith('Servicetemplates');
            });
            $where = new Comparison(
                'CONCAT(Hosts.name, "/", IF(ServicesDependent.name IS NULL, Servicetemplates.name, ServicesDependent.name))',
                $indexFilter['ServicesDependent.servicename LIKE'],
                'string',
                'LIKE'
            );
            $query->where([
                'ServicedependenciesServiceMemberships.dependent' => 1,
                $where
            ]);
        }

        unset($indexFilter['ServicesDependent.servicename LIKE']);


        if (!empty($indexFilter['Servicegroups.name LIKE'])) {
            $containFilter['Servicegroups.name'] = [
                'Containers.name LIKE' => $indexFilter['Servicegroups.name LIKE']
            ];
            $query->innerJoinWith('Servicegroups.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'ServicedependenciesServicegroupMemberships.dependent' => 0,
                    $containFilter['Servicegroups.name']
                ]);
            });
            unset($indexFilter['Servicegroups.name LIKE']);
        }
        if (!empty($indexFilter['ServicegroupsDependent.name LIKE'])) {
            $containFilter['ServicegroupsDependent.name'] = [
                'Containers.name LIKE' => $indexFilter['ServicegroupsDependent.name LIKE']
            ];
            $query->innerJoinWith('ServicegroupsDependent.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'ServicedependenciesServicegroupMemberships.dependent' => 1,
                    $containFilter['ServicegroupsDependent.name']
                ]);
            });
            unset($indexFilter['ServicegroupsDependent.name LIKE']);
        }

        if (!empty($MY_RIGHTS)) {
            $indexFilter['Servicedependencies.container_id IN'] = $MY_RIGHTS;
        }
        $query->where($indexFilter);
        $query->orderBy($ServicedependenciesFilter->getOrderForPaginator('Servicedependencies.id', 'asc'));

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
     * @param array|int $services
     * @param array|int $dependent_services
     * @return array
     */
    public function parseServiceMembershipData($services = [], $dependent_services = []) {
        $servicemembershipData = [];
        foreach ($services as $service) {
            $servicemembershipData[] = [
                'id'        => $service,
                '_joinData' => [
                    'dependent' => 0
                ]
            ];
        }
        foreach ($dependent_services as $dependent_service) {
            $servicemembershipData[] = [
                'id'        => $dependent_service,
                '_joinData' => [
                    'dependent' => 1
                ]
            ];
        }
        return $servicemembershipData;
    }

    /**
     * @param array $servicegroups
     * @param array $dependent_servicegroups
     * @return array
     */
    public function parseServicegroupMembershipData($servicegroups = [], $dependent_servicegroups = []) {
        $servicegroupmembershipData = [];
        foreach ($servicegroups as $servicegroup) {
            $servicegroupmembershipData[] = [
                'id'        => $servicegroup,
                '_joinData' => [
                    'dependent' => 0
                ]
            ];
        }
        foreach ($dependent_servicegroups as $dependent_servicegroup) {
            $servicegroupmembershipData[] = [
                'id'        => $dependent_servicegroup,
                '_joinData' => [
                    'dependent' => 1
                ]
            ];
        }
        return $servicegroupmembershipData;
    }

    /**
     * @param null|string $uuid
     * @return array
     */
    public function getServicedependenciesForExport($uuid = null) {
        $query = $this->find()
            ->contain([
                'Services'      =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->contain([
                                'Hosts' => function (Query $q) {
                                    return $q->enableAutoFields(false)
                                        ->select(['Hosts.uuid']);
                                }
                            ])
                            ->where([
                                'Services.disabled' => 0
                            ])
                            ->select([
                                'id',
                                'uuid'
                            ]);
                    },
                'Servicegroups' =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'id',
                                'uuid'
                            ]);
                    },
                'Timeperiods'   =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid']);
                    }
            ])
            ->select([
                'id',
                'uuid',
                'inherits_parent',
                'execution_fail_on_ok',
                'execution_fail_on_warning',
                'execution_fail_on_critical',
                'execution_fail_on_unknown',
                'execution_fail_on_pending',
                'execution_none',
                'notification_fail_on_ok',
                'notification_fail_on_warning',
                'notification_fail_on_critical',
                'notification_fail_on_unknown',
                'notification_fail_on_pending',
                'notification_none'
            ]);
        if ($uuid !== null) {
            $query->where([
                'Servicedependencies.uuid' => $uuid
            ]);
        }
        $query->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int|null $id
     * @param int|null $serviceId
     * @return bool
     */
    public function isServicedependencyBroken($id = null, $serviceId = null) {
        if (!$this->exists(['Servicedependencies.id' => $id]) && $id !== null) {
            throw new NotFoundException();
        }
        $query = $this->find()
            ->contain([
                'Services' =>
                    function (Query $q) use ($serviceId) {
                        if ($serviceId !== null) {
                            $q->where([
                                'Services.id !=' => $serviceId
                            ]);
                        }
                        return $q->enableAutoFields(false)
                            ->where([
                                'Services.disabled' => 0
                            ])
                            ->select(['id']);
                    },
            ])->select([
                'id'
            ])->where([
                'Servicedependencies.id' => $id
            ])->first();

        $services = $query->get('services');
        $masterServicesForCfg = [];
        $dependentServicesForCfg = [];
        foreach ($services as $service) {
            /** @var Service $service */
            if ($service->get('_joinData')->get('dependent') === 0) {
                $masterServicesForCfg[] = $service->get('id');
            } else {
                $dependentServicesForCfg[] = $service->get('id');
            }
        }

        return empty($masterServicesForCfg) || empty($dependentServicesForCfg);
    }

    /**
     * @param $id
     * @return array
     */
    public function getServicedependencyById($id) {
        $query = $this->find()
            ->where([
                'Servicedependencies.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByServicedependencies($timeperiodId) {
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
    public function getServicedependenciesByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Servicedependencies.id'
            ])
            ->where([
                'timeperiod_id' => $timeperiodId,

            ]);

        if (!empty($MY_RIGHTS)) {
            $query->where(['Servicedependencies.container_id IN' => $MY_RIGHTS]);
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
    public function getServicedependenciesByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Servicedependencies.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Servicedependencies.' . $index,
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Servicedependencies.container_id IN' => $MY_RIGHTS
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
            $list[$row[$index]] = __('Service dependency #{0}', $row['id']);
        }

        return $list;
    }
}
