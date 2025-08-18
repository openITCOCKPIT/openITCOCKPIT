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
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\Instantreport;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\InstantreportFilter;

/**
 * Instantreports Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\Instantreport get($primaryKey, $options = [])
 * @method \App\Model\Entity\Instantreport newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Instantreport[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Instantreport|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Instantreport saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Instantreport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Instantreport[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Instantreport findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class InstantreportsTable extends Table {
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

        $this->setTable('instantreports');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('Timeperiods', [
            'foreignKey' => 'timeperiod_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Users', [
            'joinTable'    => 'instantreports_to_users',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Hostgroups', [
            'joinTable'    => 'instantreports_to_hostgroups',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Hosts', [
            'joinTable'    => 'instantreports_to_hosts',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Servicegroups', [
            'joinTable'    => 'instantreports_to_servicegroups',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Services', [
            'joinTable'    => 'instantreports_to_services',
            'saveStrategy' => 'replace'
        ])->setDependent(true);
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
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('timeperiod_id')
            ->allowEmptyString('timeperiod_id', null, false)
            ->requirePresence('timeperiod_id')
            ->greaterThan('timeperiod_id', 0);

        $validator
            ->add('send_email', 'custom', [
                'rule'    => [$this, 'atLeastOneUser'],
                'message' => __('You must specify at least one user')
            ]);

        $validator
            ->greaterThan('send_interval', 0,
                __('Please select a valid interval type'),
                function ($context) {
                    return ($context['data']['send_email'] === 1 && $context['data']['send_interval']);
                });


        $validator
            ->add('hostgroups', 'custom', [
                'rule'    => [$this, 'atLeastOneHostgroup'],
                'message' => __('You must specify at least one host group')
            ]);

        $validator
            ->add('hosts', 'custom', [
                'rule'    => [$this, 'atLeastOneHost'],
                'message' => __('You must specify at least one host')
            ]);

        $validator
            ->add('servicegroups', 'custom', [
                'rule'    => [$this, 'atLeastOneServicegroup'],
                'message' => __('You must specify at least one service group')
            ]);

        $validator
            ->add('services', 'custom', [
                'rule'    => [$this, 'atLeastOneService'],
                'message' => __('You must specify at least one service')
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
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for users if send_email is true
     */
    public function atLeastOneUser($value, $context) {
        // XNOR Operator (false and false) = true and (true and true) = true
        // if send_email true and user list is not empty, if send_mail = 1 and user list is empty
        return !(!(($context['data']['send_email'] === 1) ^ empty($context['data']['users']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneHostgroup($value, $context) {
        return !(!(($context['data']['type'] === 1) ^ empty($context['data']['hostgroups']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneHost($value, $context) {
        return !(!(($context['data']['type'] === 2) ^ empty($context['data']['hosts']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneServicegroup($value, $context) {
        return !(!(($context['data']['type'] === 3) ^ empty($context['data']['servicegroups']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneService($value, $context) {
        return !(!(($context['data']['type'] === 4) ^ empty($context['data']['services']['_ids'])));
    }

    /**
     * @param InstantreportFilter $InstantreportFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getInstantreportsIndex(InstantreportFilter $InstantreportFilter, PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Timeperiods' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Timeperiods.id',
                            'Timeperiods.name'
                        ]);
                },
                'Users'       => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Users.id',
                            'Users.firstname',
                            'Users.lastname'
                        ]);
                }
            ])
            ->select([
                'Instantreports.id',
                'Instantreports.container_id',
                'Instantreports.name',
                'Instantreports.evaluation',
                'Instantreports.type',
                'Instantreports.reflection',
                'Instantreports.downtimes',
                'Instantreports.summary',
                'Instantreports.send_email',
                'Instantreports.send_interval'
            ])
            ->groupBy('Instantreports.id')
            ->disableHydration();

        $indexFilter = $InstantreportFilter->indexFilter();


        if (!empty($MY_RIGHTS)) {
            $indexFilter['Instantreports.container_id IN'] = $MY_RIGHTS;
        }

        $query->where($indexFilter);
        $query->orderBy(
            array_merge(
                $InstantreportFilter->getOrderForPaginator('Instantreports.name', 'asc'),
                ['Instantreports.id' => 'asc']
            )

        );
        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }

        }
        return $result;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Instantreports.id' => $id]);
    }

    /**
     * @param $id
     * @return array
     */
    public function getInstantreportById($id) {
        $query = $this->find()
            ->where([
                'Instantreports.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $id
     * @param bool $enableHydration
     * @return array| Instantreport
     */
    public function getInstantreportByIdCake4($id, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Instantreports.id' => $id
            ])
            ->contain([
                'Hostgroups'    => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'id'
                        ]);
                },
                'Hosts'         => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'id'
                        ]);
                },
                'Servicegroups' => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'id'
                        ]);
                },
                'Services'      => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'id'
                        ]);
                }
            ])
            ->enableHydration($enableHydration)
            ->first();
        return $query;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getInstantreportForEdit($id) {
        $query = $this->find()
            ->select([
                'Instantreports.id',
                'Instantreports.name',
                'Instantreports.container_id',
                'Instantreports.evaluation',
                'Instantreports.type',
                'Instantreports.timeperiod_id',
                'Instantreports.reflection',
                'Instantreports.downtimes',
                'Instantreports.summary',
                'Instantreports.send_email',
                'Instantreports.send_interval'
            ])
            ->where([
                'Instantreports.id' => $id
            ])
            ->contain([
                'Users',
                'Hostgroups',
                'Hosts',
                'Servicegroups',
                'Services'
            ])
            ->disableHydration()
            ->first();

        $instantreport = $query;

        $instantreport['users'] = [
            '_ids' => Hash::extract($query, 'users.{n}.id')
        ];
        $instantreport['hostgroups'] = [
            '_ids' => Hash::extract($query, 'hostgroups.{n}.id')
        ];
        $instantreport['hosts'] = [
            '_ids' => Hash::extract($query, 'hosts.{n}.id')
        ];
        $instantreport['servicegroups'] = [
            '_ids' => Hash::extract($query, 'servicegroups.{n}.id')
        ];
        $instantreport['services'] = [
            '_ids' => Hash::extract($query, 'services.{n}.id')
        ];
        return [
            'Instantreport' => $instantreport
        ];
    }


    /**
     * @param Instantreport $instantReport
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsAndServicesByInstantreport(Instantreport $instantReport, $MY_RIGHTS = []) {
        $instantReportObjects = [
            'Hosts' => []
        ];
        switch ($instantReport->get('type')) {
            case 1: //Host groups
                /** @var  $HostgroupsTable HostgroupsTable */
                $hostgroupsIds = Hash::extract($instantReport, 'hostgroups.{n}.id');
                if (empty($hostgroupsIds)) {
                    return $instantReportObjects;
                }

                $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
                $hostgroups = $HostgroupsTable->find()
                    ->contain([
                        'Containers',
                        // Get all hosts that are in host group through the host template AND
                        // which does NOT have any own host groups
                        'Hosttemplates' => function (Query $query) use ($instantReport, $MY_RIGHTS) {
                            $query->disableAutoFields()
                                ->select([
                                    'id'
                                ])
                                ->contain([
                                    'Hosts' => function (Query $query) use ($instantReport, $MY_RIGHTS) {
                                        $query
                                            ->disableAutoFields()
                                            ->select([
                                                'Hosts.id',
                                                'Hosts.uuid',
                                                'Hosts.name',
                                                'Hosts.hosttemplate_id'
                                            ]);
                                        if (!empty($MY_RIGHTS)) {
                                            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                                'HostsToContainersSharing.host_id = Hosts.id'
                                            ]);
                                            $query->where([
                                                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                            ]);
                                        }
                                        $query
                                            ->leftJoinWith('Hostgroups')
                                            ->whereNull('Hostgroups.id');
                                        if ($instantReport->get('evaluation') > 1) {
                                            $query->contain([
                                                'Services' => [
                                                    'fields'           => [
                                                        'Services.id',
                                                        'Services.host_id',
                                                        'Services.uuid',
                                                        'Services.name'
                                                    ],
                                                    'Servicetemplates' => [
                                                        'fields' => [
                                                            'Servicetemplates.name'
                                                        ]
                                                    ]
                                                ]
                                            ]);
                                        }
                                        return $query;
                                    }
                                ]);
                            return $query;
                        },
                        // Get all hosts from host group
                        'Hosts'         => function (Query $query) use ($instantReport, $MY_RIGHTS) {
                            $query->disableAutoFields()
                                ->select([
                                    'Hosts.id',
                                    'Hosts.uuid',
                                    'Hosts.name'
                                ]);
                            if (!empty($MY_RIGHTS)) {
                                $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                    'HostsToContainersSharing.host_id = Hosts.id'
                                ]);
                                $query->where([
                                    'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                ]);
                            }
                            if ($instantReport->get('evaluation') > 1) {
                                $query->contain([
                                    'Services' => [
                                        'fields'           => [
                                            'Services.id',
                                            'Services.host_id',
                                            'Services.uuid',
                                            'Services.name'
                                        ],
                                        'Servicetemplates' => [
                                            'fields' => [
                                                'Servicetemplates.name'
                                            ]
                                        ]
                                    ]
                                ]);
                            }
                            return $query;
                        }
                    ])
                    ->where([
                        'Hostgroups.id IN ' => $hostgroupsIds
                    ]);
                if (!empty($MY_RIGHTS)) {
                    $hostgroups->andWhere([
                        'Containers.parent_id IN' => $MY_RIGHTS
                    ]);
                }
                $hostgroups
                    ->disableHydration()
                    ->all()
                    ->toArray();
                foreach ($hostgroups as $hostgroup) {
                    foreach ($hostgroup['hosts'] as $host) {
                        $instantReportObjects['Hosts'][$host['id']] = [
                            'id'   => $host['id'],
                            'uuid' => $host['uuid'],
                            'name' => $host['name']
                        ];
                        if (!empty($host['services'])) {
                            foreach ($host['services'] as $service) {
                                $instantReportObjects['Hosts'][$host['id']]['Services'][$service['id']] = [
                                    'id'   => $service['id'],
                                    'uuid' => $service['uuid'],
                                    'name' => ($service['name']) ? $service['name'] : $service['servicetemplate']['name']
                                ];
                            }
                        }
                    }
                    if (!empty($hostgroup['hosttemplates'])) {
                        foreach ($hostgroup['hosttemplates'] as $hosttemplate) {
                            foreach ($hosttemplate['hosts'] as $host) {
                                if (isset($instantReportObjects['Hosts'][$host['id']])) {
                                    continue;
                                }
                                $instantReportObjects['Hosts'][$host['id']] = [
                                    'id'   => $host['id'],
                                    'uuid' => $host['uuid'],
                                    'name' => $host['name']
                                ];
                                if (!empty($host['services'])) {
                                    foreach ($host['services'] as $service) {
                                        if (isset($instantReportObjects['Hosts'][$host['id']]['Services'][$service['id']])) {
                                            continue;
                                        }
                                        $instantReportObjects['Hosts'][$host['id']]['Services'][$service['id']] = [
                                            'id'   => $service['id'],
                                            'uuid' => $service['uuid'],
                                            'name' => ($service['name']) ? $service['name'] : $service['servicetemplate']['name']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 2: //Hosts
                $hostsIds = Hash::extract($instantReport, 'hosts.{n}.id');
                if (empty($hostsIds)) {
                    return $instantReportObjects;
                }
                /** @var  $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                if ($instantReport->get('evaluation') === 1) {
                    $hosts = $HostsTable
                        ->find()
                        ->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.uuid',
                            'Hosts.name'
                        ])
                        ->where([
                            'Hosts.id IN' => $hostsIds
                        ]);

                    if (!empty($MY_RIGHTS)) {
                        $hosts->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                        ]);
                    }
                    $hosts
                        ->disableHydration()
                        ->all()
                        ->toArray();
                } else {
                    $hosts = $HostsTable
                        ->find()
                        ->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.uuid',
                            'Hosts.name'
                        ])
                        ->contain([
                            'Services' => [
                                'fields'           => [
                                    'Services.id',
                                    'Services.host_id',
                                    'Services.uuid',
                                    'Services.name'
                                ],
                                'Servicetemplates' => [
                                    'fields' => [
                                        'Servicetemplates.name'
                                    ]
                                ]
                            ]
                        ])
                        ->where([
                            'Hosts.id IN' => $hostsIds
                        ]);
                    if (!empty($MY_RIGHTS)) {
                        $hosts->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                        ]);
                    }
                    $hosts
                        ->disableHydration()
                        ->all()
                        ->toArray();
                }
                foreach ($hosts as $host) {
                    $instantReportObjects['Hosts'][$host['id']] = [
                        'id'   => $host['id'],
                        'uuid' => $host['uuid'],
                        'name' => $host['name']
                    ];
                    if (!empty($host['services'])) {
                        foreach ($host['services'] as $service) {
                            $instantReportObjects['Hosts'][$host['id']]['Services'][$service['id']] = [
                                'id'   => $service['id'],
                                'uuid' => $service['uuid'],
                                'name' => ($service['name']) ? $service['name'] : $service['servicetemplate']['name']
                            ];
                        }
                    }
                }
                break;
            case 3: //Service groups
                /** @var  $ServicegroupsTable ServicegroupsTable */
                $servicegroupsIds = Hash::extract($instantReport, 'servicegroups.{n}.id');
                if (empty($servicegroupsIds)) {
                    return $instantReportObjects;
                }
                $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

                $servicegroups = $ServicegroupsTable->find()
                    ->contain([
                        'Containers',
                        // Get all services that are in this service group through the service template AND
                        // which does NOT have any own service groups
                        'Servicetemplates' => function (Query $query) use ($MY_RIGHTS) {
                            $query->disableAutoFields()
                                ->select([
                                    'Servicetemplates.id'
                                ])
                                ->contain([
                                    'Services' => function (Query $query) use ($MY_RIGHTS) {
                                        $query->disableAutoFields()
                                            ->select([
                                                'Services.id',
                                                'Services.uuid',
                                                'Services.name',
                                                'Services.servicetemplate_id',
                                                'Servicegroups.id'
                                            ]);

                                        $query->contain([
                                            'Servicetemplates' => [
                                                'fields' => [
                                                    'Servicetemplates.name'
                                                ]
                                            ],
                                            'Hosts'            => function (Query $q) use ($MY_RIGHTS) {
                                                $q->disableAutoFields()
                                                    ->select([
                                                        'Hosts.id',
                                                        'Hosts.uuid',
                                                        'Hosts.name'
                                                    ]);
                                                if (empty($MY_RIGHTS)) {
                                                    return $q;
                                                }
                                                $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                                    'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                                ]);
                                                return $q;
                                            }
                                        ]);
                                        $query->leftJoinWith('Servicegroups')
                                            ->whereNull('Servicegroups.id');
                                        return $query;
                                    }
                                ]);
                            return $query;
                        },
                        // Get all services from this service group
                        'Services'         => function (Query $query) use ($MY_RIGHTS) {
                            $query->disableAutoFields()
                                ->contain([
                                    'Servicetemplates' => [
                                        'fields' => [
                                            'Servicetemplates.name'
                                        ]
                                    ],
                                    'Hosts'            => function (Query $q) use ($MY_RIGHTS) {
                                        $q->disableAutoFields()
                                            ->select([
                                                'Hosts.id',
                                                'Hosts.uuid',
                                                'Hosts.name'
                                            ]);
                                        if (empty($MY_RIGHTS)) {
                                            return $q;
                                        }
                                        $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                        ]);
                                        return $q;
                                    }
                                ])
                                ->select([
                                    'Services.id',
                                    'Services.uuid',
                                    'Services.name'
                                ]);
                            return $query;
                        }
                    ])
                    ->where([
                        'Servicegroups.id IN ' => $servicegroupsIds
                    ]);
                if (!empty($MY_RIGHTS)) {
                    $servicegroups->andWhere([
                        'Containers.parent_id IN' => $MY_RIGHTS
                    ]);
                }
                $servicegroups
                    ->disableHydration()
                    ->all()
                    ->toArray();
                foreach ($servicegroups as $servicegroup) {
                    // services from service group
                    foreach ($servicegroup['services'] as $service) {

                        if (!array_key_exists($service['Hosts']['id'], $instantReportObjects['Hosts'])) {
                            $instantReportObjects['Hosts'][$service['Hosts']['id']] = [
                                'id'   => $service['Hosts']['id'],
                                'uuid' => $service['Hosts']['uuid'],
                                'name' => $service['Hosts']['name']
                            ];
                        }
                        if ($instantReport->get('evaluation') > 1) {
                            $instantReportObjects['Hosts'][$service['Hosts']['id']]['Services'][$service['id']] = [
                                'id'   => $service['id'],
                                'uuid' => $service['uuid'],
                                'name' => ($service['name']) ? $service['name'] : $service['Servicetemplates']['name']
                            ];
                        }
                    }

                    // services from service group over service template association
                    if (!empty($servicegroup['servicetemplates'])) {
                        foreach ($servicegroup['servicetemplates'] as $servicetemplateWithServices) {
                            foreach ($servicetemplateWithServices['services'] as $service) {
                                if (!array_key_exists($service['host']['id'], $instantReportObjects['Hosts'])) {
                                    $instantReportObjects['Hosts'][$service['host']['id']] = [
                                        'id'   => $service['host']['id'],
                                        'uuid' => $service['host']['uuid'],
                                        'name' => $service['host']['name']
                                    ];
                                }
                                if ($instantReport->get('evaluation') > 1) {

                                    $instantReportObjects['Hosts'][$service['host']['id']]['Services'][$service['id']] = [
                                        'id'   => $service['id'],
                                        'uuid' => $service['uuid'],
                                        'name' => ($service['name']) ? $service['name'] : $service['Servicetemplates']['name']
                                    ];
                                }

                            }
                        }
                    }

                }
                break;
            case 4: //Services
                $servicesIds = Hash::extract($instantReport, 'services.{n}.id');
                if (empty($servicesIds)) {
                    return $instantReportObjects;
                }
                /** @var  $ServicesTable ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                $services = $ServicesTable->find()
                    ->select([
                        'Services.id',
                        'Services.name',
                        'Services.uuid'
                    ])
                    ->contain([
                        'Servicetemplates' => [
                            'fields' => [
                                'Servicetemplates.name'
                            ]
                        ],
                        'Hosts'            => function (Query $q) use ($MY_RIGHTS) {
                            $q->disableAutoFields()
                                ->select([
                                    'Hosts.id',
                                    'Hosts.uuid',
                                    'Hosts.name'
                                ]);
                            if (empty($MY_RIGHTS)) {
                                return $q;
                            }
                            $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                            ]);
                            return $q;
                        }
                    ])
                    ->where([
                        'Services.id IN ' => $servicesIds
                    ])
                    ->distinct()
                    ->disableHydration()
                    ->all()
                    ->toArray();

                foreach ($services as $service) {
                    if (!isset($instantReportObjects['Hosts'])) {
                        $instantReportObjects['Hosts'] = [];
                    }
                    if (!array_key_exists($service['host']['id'], $instantReportObjects['Hosts'])) {
                        $instantReportObjects['Hosts'][$service['host']['id']] = [
                            'id'   => $service['host']['id'],
                            'uuid' => $service['host']['uuid'],
                            'name' => $service['host']['name']
                        ];
                    }
                    if ($instantReport->get('evaluation') > 1) {
                        $instantReportObjects['Hosts'][$service['host']['id']]['Services'][$service['id']] = [
                            'id'   => $service['id'],
                            'uuid' => $service['uuid'],
                            'name' => ($service['name']) ? $service['name'] : $service['servicetemplate']['name']
                        ];
                    }
                }
                break;
        }
        return $instantReportObjects;
    }

    /**
     * @param $timeperiodId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getInstantreportsByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Instantreports.id',
                'Instantreports.name'
            ])
            ->where([
                'Instantreports.timeperiod_id' => $timeperiodId
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'Instantreports.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->orderBy('Instantreports.name', 'asc');
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
    public function getInstantreportsByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Instantreports.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Instantreports.' . $index,
            'Instantreports.name'
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Instantreports.container_id IN' => $MY_RIGHTS
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
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }

    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedInstantreportsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }

    /**
     * @param int $hostId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getInstantReportsByHostId(int $hostId, array $MY_RIGHTS): array {
        $query = $this->find();
        $query->select([
            'Instantreports.id',
            'Instantreports.name'
        ])
            ->innerJoin(
                ['InstantreportsToHosts' => 'instantreports_to_hosts'],
                [
                    'InstantreportsToHosts.instantreport_id = Instantreports.id',
                    'InstantreportsToHosts.host_id' => $hostId,
                ]
            );

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Instantreports.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->disableHydration();

        return $query->toArray();
    }

    /**
     * @param int $serviceId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getInstantReportsByServiceId(int $serviceId, array $MY_RIGHTS): array {
        $query = $this->find();
        $query->select([
            'Instantreports.id',
            'Instantreports.name'
        ])
            ->innerJoin(
                ['Instantreportstoservices' => 'instantreports_to_services'],
                [
                    "Instantreportstoservices.service_id" => $serviceId,
                    'Instantreportstoservices.instantreport_id = Instantreports.id'
                ]
            );

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Instantreports.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->disableHydration();

        return $query->toArray();
    }

    /**
     * @param InstantreportFilter $InstantreportFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getInstantreportsForAngular($MY_RIGHTS = []) {
        $query = $this->find('list')
            ->select([
                'Instantreports.id',
                'Instantreports.container_id',
                'Instantreports.name',
            ])
            ->groupBy('Instantreports.id')
            ->disableHydration();

        $where = [];
        if (!empty($MY_RIGHTS)) {
            $where['Instantreports.container_id IN'] = $MY_RIGHTS;
        }

        $query->where($where);
        $query->orderBy([
            'Instantreports.name' => 'asc'
        ]);

        return $query->toArray();

    }

}
