<?php

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Servicegroup;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServicegroupConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServicegroupFilter;

/**
 * Servicegroups Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ServicesTable|\Cake\ORM\Association\HasMany $Services
 * @property \App\Model\Table\ServicetemplatesTable|\Cake\ORM\Association\HasMany $Servicetemplates
 *
 * @method \App\Model\Entity\Servicegroup get($primaryKey, $options = [])
 * @method \App\Model\Entity\Servicegroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Servicegroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Servicegroup|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicegroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicegroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Servicegroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Servicegroup findOrCreate($search, callable $callback = null, $options = [])
 */
class ServicegroupsTable extends Table {

    use PaginationAndScrollIndexTrait;
    use PluginManagerTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('servicegroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Services', [
            'className'        => 'Services',
            'foreignKey'       => 'servicegroup_id',
            'targetForeignKey' => 'service_id',
            'joinTable'        => 'services_to_servicegroups',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Servicetemplates', [
            'className'        => 'Servicetemplates',
            'foreignKey'       => 'servicegroup_id',
            'targetForeignKey' => 'servicetemplate_id',
            'joinTable'        => 'servicetemplates_to_servicegroups',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Statuspages', [
            'className'        => 'Statuspages',
            'foreignKey'       => 'servicegroup_id',
            'targetForeignKey' => 'statuspage_id',
            'joinTable'        => 'statuspages_to_servicegroups',
            'saveStrategy'     => 'replace'
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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', 'create')
            ->allowEmptyString('description', null, true);

        $validator
            ->scalar('servicegroup_url')
            ->maxLength('servicegroup_url', 255)
            ->allowEmptyString('servicegroup_url')
            ->url('servicegroup_url', __('Not a valid URL.'));

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
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param string $index
     * @return array|\Cake\ORM\Query
     * @deprecated Use self::getServicegroupsByContainerId()
     */
    public function servicegroupsByContainerId($containerIds = [], $type = 'all', $index = 'container_id') {
        return $this->getServicegroupsByContainerId($containerIds, $type, $index);
    }


    /**
     * @param array|int $containerIds
     * @param string $type
     * @param string $index
     * @return array|null
     */
    public function getServicegroupsByContainerId($containerIds = [], $type = 'all', $index = 'id') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $tenantContainerIds = [];

        foreach ($containerIds as $container_id) {
            if ($container_id != ROOT_CONTAINER) {
                // Get contaier id of the tenant container
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load contactgroups and contacts
                $path = $ContainersTable->getPathByIdAndCacheResult($container_id, 'ServicegroupServicegroupsByContainerId');

                // Tenant service groups are available for all users of a tenant (oITC V2 legacy)
                $tenantContainerIds[] = $path[1]['id'];
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);
        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));


        switch ($type) {
            case 'all':
                $query = $this->find()
                    ->contain([
                        'Containers'
                    ])
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_SERVICEGROUP,
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();

                return $this->emptyArrayIfNull($query->toArray());


            default:
                $query = $this->find()
                    ->contain([
                        'Containers'
                    ])
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_SERVICEGROUP,
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();

                $query = $query->toArray();
                if (empty($query)) {
                    $query = [];
                }


                $return = [];
                foreach ($query as $servicegroup) {
                    if ($index === 'id') {
                        $return[$servicegroup['id']] = $servicegroup['container']['name'];
                    } else {
                        $return[$servicegroup['container_id']] = $servicegroup['container']['name'];
                    }
                }

                return $return;
        }
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getServicegroupsAsList($ids = [], $MY_RIGHTS = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->select([
                'Servicegroups.id',
                'Containers.name'
            ])
            ->contain(['Containers'])
            ->disableHydration();

        $where = [];
        if (!empty($ids)) {
            $where = [
                'Servicegroups.id IN'         => $ids,
                'Containers.containertype_id' => CT_SERVICEGROUP
            ];
        }

        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        if (!empty($where)) {
            $query->where($where);
        }

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $list = [];
        foreach ($result as $row) {
            $list[$row['id']] = $row['container']['name'];
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getServicegroupsForExport() {
        $query = $this->find()
            ->select([
                'Servicegroups.id',
                'Servicegroups.uuid',
                'Servicegroups.description'
            ])
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param ServicegroupFilter $ServicegroupFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicegroupsIndex(ServicegroupFilter $ServicegroupFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where($ServicegroupFilter->indexFilter());

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Containers.parent_id IN' => $MY_RIGHTS]);
            }
            return $q;
        });


        $query->disableHydration();
        $query->orderBy($ServicegroupFilter->getOrderForPaginator('Containers.name', 'asc'));


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

    /**
     * @param ServicegroupFilter $ServicegroupFilter
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicegroupsForPdf(ServicegroupFilter $ServicegroupFilter, $MY_RIGHTS = []) {

        $query = $this->find()
            ->contain([
                'Services',
                'Containers'
            ]);

        $where = $ServicegroupFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }
        $query->where($where)
            ->orderBy(
                $ServicegroupFilter->getOrderForPaginator('Containers.name', 'asc')
            )->disableHydration();

        $data = $query->all();
        return $this->emptyArrayIfNull($data->toArray());
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Servicegroups.id' => $id]);
    }

    /**
     * @param array $dataToParse
     * @return array
     */
    public function resolveDataForChangelog($dataToParse = []) {
        $extDataForChangelog = [
            'Service'         => [],
            'Servicetemplate' => [],
        ];

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if (!empty($dataToParse['Servicegroup']['services']['_ids'])) {
            foreach ($ServicesTable->getServicesAsList($dataToParse['Servicegroup']['services']['_ids'], true) as $serviceId => $serviceName) {
                $extDataForChangelog['Service'][] = [
                    'id'   => $serviceId,
                    'name' => $serviceName
                ];
            }
        }

        if (!empty($dataToParse['Servicegroup']['servicetemplates']['_ids'])) {
            foreach ($ServicetemplatesTable->getServicetemplatesAsList($dataToParse['Servicegroup']['servicetemplates']['_ids']) as $servicetemplateId => $servicetemplateName) {
                $extDataForChangelog['Servicetemplate'][] = [
                    'id'   => $servicetemplateId,
                    'name' => $servicetemplateName
                ];
            }
        }

        return $extDataForChangelog;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getServicegroupForEdit($id) {
        $query = $this->find()
            ->where([
                'Servicegroups.id' => $id
            ])
            ->contain([
                'Services',
                'Servicetemplates',
                'Containers'
            ])
            ->disableHydration()
            ->first();

        $servicegroup = $query;
        $servicegroup['services'] = [
            '_ids' => Hash::extract($query, 'services.{n}.id')
        ];
        $servicegroup['servicetemplates'] = [
            '_ids' => Hash::extract($query, 'servicetemplates.{n}.id')
        ];

        return [
            'Servicegroup' => $servicegroup
        ];
    }

    /**
     * @param int $id
     * @return \App\Model\Entity\Servicegroup
     */
    public function getServicegroupById($id) {
        return $this->get($id, contain: [
            'Containers'
        ]);
    }

    /**
     * @param $id
     * @return array
     */
    public function getServicegroupByIdForMapeditor($id, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        $query = $this->find()
            ->contain([
                'Containers'       => function (Query $q) use ($MY_RIGHTS) {
                    $q->select([
                        'Containers.id',
                        'Containers.name'
                    ]);
                    if (!empty($MY_RIGHTS)) {
                        return $q->where(['Containers.parent_id IN' => $MY_RIGHTS]);
                    }
                    return $q;
                },
                'Services'         => function (Query $q) use ($MY_RIGHTS) {
                    $q->select([
                        'Services.id',
                        'Services.uuid',
                        'Services.name',
                        'Services.host_id'
                    ])
                        ->contain([
                            'Servicetemplates' => function (Query $q) {
                                return $q->select([
                                    'Servicetemplates.id',
                                    'Servicetemplates.name'
                                ]);
                            },
                            'Hosts'            => function (Query $q) {
                                return $q->select([
                                    'Hosts.id',
                                    'Hosts.uuid',
                                    'Hosts.name'
                                ])
                                    ->contain([
                                        'HostsToContainersSharing'
                                    ]);
                            }

                        ])
                        ->innerJoinWith('Hosts')
                        ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                            if (!empty($MY_RIGHTS)) {
                                $q->where([
                                    'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                                ]);
                            }
                            return $q;
                        })
                        ->where([
                            'Services.disabled' => 0
                        ]);
                    return $q;
                },
                'Servicetemplates' => function (Query $q) use ($MY_RIGHTS) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'id'
                        ])
                        ->contain([
                            'Services' => function (Query $query) use ($MY_RIGHTS) {
                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Services.id',
                                        'Services.servicetemplate_id',
                                        'Services.uuid',
                                        'Services.name',
                                        'Services.host_id'
                                    ])
                                    ->contain([
                                        'Servicetemplates' => function (Query $q) {
                                            return $q->select([
                                                'Servicetemplates.id',
                                                'Servicetemplates.name'
                                            ]);
                                        },
                                        'Hosts'            => function (Query $q) {
                                            return $q->select([
                                                'Hosts.id',
                                                'Hosts.uuid',
                                                'Hosts.name'
                                            ])
                                                ->contain([
                                                    'HostsToContainersSharing'
                                                ]);
                                        }
                                    ])
                                    ->innerJoinWith('Hosts')
                                    ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                                        if (!empty($MY_RIGHTS)) {
                                            $q->where([
                                                'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                                            ]);
                                        }
                                        return $q;
                                    })
                                    ->where([
                                        'Services.disabled' => 0,
                                        'Hosts.disabled'    => 0
                                    ]);
                                $query
                                    ->leftJoinWith('Servicegroups')
                                    ->whereNull('Servicegroups.id');
                                return $query;
                            }
                        ]);
                }
            ])
            ->where([
                'Servicegroups.id' => $id
            ])
            ->select([
                'Servicegroups.id',
                'Servicegroups.description'
            ]);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param $id
     * @param array $MY_RIGHTS
     * @return array|Servicegroup
     */
    public function getServicegroupsByServicegroupForMaps($id, $MY_RIGHTS = []) {
        $where = [
            'Servicegroups.id' => $id
        ];
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        $servicegroup = $this->find()
            ->select([
                'Servicegroups.id',
                'Servicegroups.description',
                'Containers.name',
            ])
            ->contain([
                'Containers',
                'Services'         => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Services.id',
                            'Services.uuid',
                            'Services.name'
                        ])
                        ->contain([
                            'Hosts'            => function (Query $q) {
                                return $q->enableAutoFields(false)
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid',
                                        'Hosts.name'
                                    ])
                                    ->contain(['HostsToContainersSharing'])
                                    ->where(['Hosts.disabled' => 0]);
                            },
                            'Servicetemplates' => function (Query $q) {
                                return $q->enableAutoFields(false)
                                    ->select([
                                        'Servicetemplates.name'
                                    ]);
                            },
                        ])
                        ->where(['Services.disabled' => 0]);
                },
                'Servicetemplates' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'id'
                        ])
                        ->contain([
                            'Services' => function (Query $query) {
                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Services.id',
                                        'Services.servicetemplate_id',
                                        'Services.uuid',
                                        'Services.name'
                                    ])
                                    ->contain([
                                        'Servicetemplates' => function (Query $q) {
                                            return $q->select([
                                                'Servicetemplates.id',
                                                'Servicetemplates.name'
                                            ]);
                                        },
                                        'Hosts'            => function (Query $q) {
                                            return $q->contain([
                                                'HostsToContainersSharing'
                                            ])->select([
                                                'Hosts.id',
                                                'Hosts.uuid',
                                                'Hosts.name'
                                            ])->where([
                                                'Hosts.disabled' => 0
                                            ]);
                                        }
                                    ])
                                    ->where([
                                        'Services.disabled' => 0
                                    ]);
                                $query
                                    ->leftJoinWith('Servicegroups')
                                    ->whereNull('Servicegroups.id');
                                return $query;
                            }
                        ]);
                }
            ])
            ->where($where)
            ->disableHydration()
            ->firstOrFail();

        return $servicegroup;
    }

    /**
     * @param $id
     * @param $MY_RIGHTS
     * @return array
     */
    public function getServiceIdsByServicegroupForMaps($id, $MY_RIGHTS = []) {
        $where = [
            'Servicegroups.id' => $id
        ];
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        $servicegroup = $this->find()
            ->select([
                'Servicegroups.id'
            ])
            ->contain([
                'Containers',
                'Services'         => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Services.id'
                        ])
                        ->contain([
                            'Hosts'            => function (Query $q) {
                                return $q->enableAutoFields(false)
                                    ->select([
                                        'Hosts.id'
                                    ])
                                    ->contain(['HostsToContainersSharing'])
                                    ->where(['Hosts.disabled' => 0]);
                            },
                            'Servicetemplates' => function (Query $q) {
                                return $q->enableAutoFields(false)
                                    ->select([
                                        'Servicetemplates.name'
                                    ]);
                            },
                        ])
                        ->where(['Services.disabled' => 0]);
                },
                'Servicetemplates' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'id'
                        ])
                        ->contain([
                            'Services' => function (Query $query) {
                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Services.id',
                                        'Services.servicetemplate_id'
                                    ])
                                    ->contain([
                                        'Servicetemplates' => function (Query $q) {
                                            return $q->select([
                                                'Servicetemplates.id'
                                            ]);
                                        },
                                        'Hosts'            => function (Query $q) {
                                            return $q->contain([
                                                'HostsToContainersSharing'
                                            ])->select([
                                                'Hosts.id'
                                            ])->where([
                                                'Hosts.disabled' => 0
                                            ]);
                                        }
                                    ])
                                    ->where([
                                        'Services.disabled' => 0
                                    ]);
                                $query
                                    ->leftJoinWith('Servicegroups')
                                    ->whereNull('Servicegroups.id');
                                return $query;
                            }
                        ]);
                }
            ])
            ->where($where)
            ->disableHydration()
            ->first();

        if ($servicegroup === null) {
            return [];
        }

        return array_unique(array_merge(
            Hash::extract($servicegroup, 'services.{n}.id'),
            Hash::extract($servicegroup, 'servicetemplates.{n}.services.{n}.id')
        ));
    }

    /**
     * @param ServicegroupConditions $ServicegroupConditions
     * @param array $selected
     * @return array
     */
    public function getServicegroupsForAngular(ServicegroupConditions $ServicegroupConditions, $selected = []) {

        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->where([
                'Containers.containertype_id' => CT_SERVICEGROUP,
            ]);

        if (!empty($ServicegroupConditions->getContainerIds())) {
            $query->andWhere([
                'Containers.parent_id IN' => $ServicegroupConditions->getContainerIds()
            ]);
        }

        if (!empty($ServicegroupConditions->getConditions())) {
            $query->andWhere(
                $ServicegroupConditions->getConditions()
            );
        }

        $query
            ->orderBy([
                'Containers.name' => 'asc',
                'Containers.id'   => 'asc'
            ])
            ->groupBy([
                'Containers.id'
            ])
            ->disableHydration()
            ->limit(ITN_AJAX_LIMIT)
            ->all();

        $result = $query->toArray();

        $resultAslist = [];
        foreach ($result as $record) {
            $resultAslist[$record['id']] = $record['container']['name'];
        }

        if (!empty($selected)) {
            $query = $this->find()
                ->contain([
                    'Containers'
                ])
                ->where([
                    'Servicegroups.id IN'         => $selected,
                    'Containers.containertype_id' => CT_SERVICEGROUP,
                ]);

            if (!empty($ServicegroupConditions->getContainerIds())) {
                $query->andWhere([
                    'Containers.parent_id IN' => $ServicegroupConditions->getContainerIds()
                ]);
            }

            $query
                ->orderBy([
                    'Containers.name' => 'asc',
                    'Containers.id'   => 'asc'
                ])
                ->groupBy([
                    'Containers.id'
                ])
                ->disableHydration()
                ->limit(ITN_AJAX_LIMIT)
                ->all();


            foreach ($query->toArray() as $selectedServicegroup) {
                $resultAslist[$selectedServicegroup['id']] = $selectedServicegroup['container']['name'];
            }

        }

        return $resultAslist;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getServiceIdsByServicegroupId($id) {
        $servicegroup = $this->find()
            ->contain([
                // Get all services that are in this service group through the service template AND
                // which does NOT have any own service groups
                'Servicetemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'id',
                        ])
                        ->contain([
                            'Services' => function (Query $query) {
                                $query->disableAutoFields()
                                    ->select([
                                        'Services.id',
                                        'Services.uuid',
                                        'Services.servicetemplate_id',
                                        'Servicegroups.id'
                                    ])
                                    ->leftJoinWith('Servicegroups')
                                    ->whereNull('Servicegroups.id');
                                return $query;
                            }
                        ]);
                    return $query;
                },

                // Get all services from this service group
                'Services'         => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Services.id',
                            'Services.uuid',
                        ]);
                    return $query;
                }
            ])
            ->where([
                'Servicegroups.id' => $id
            ])
            ->disableHydration()
            ->first();


        $serviceIds = array_unique(array_merge(
            Hash::extract($servicegroup, 'services.{n}.id'),
            Hash::extract($servicegroup, 'servicetemplates.{n}.services.{n}.id')
        ));

        return $serviceIds;
    }

    /**
     * @param $id
     * @param $satelliteId
     * @return array
     */
    public function getServicesByServicegroupIdAndSatelliteId($id, $satelliteId) {
        $servicegroup = $this->find()
            ->contain([
                // Get all services that are in this service group through the service template AND
                // which does NOT have any own service groups
                'Servicetemplates' => function (Query $query) use ($satelliteId) {
                    $query->disableAutoFields()
                        ->select([
                            'id',
                        ])
                        ->contain([
                            'Services' => function (Query $query) use ($satelliteId) {
                                $query->disableAutoFields()
                                    ->select([
                                        'Services.id',
                                        'Services.uuid',
                                        'Services.servicetemplate_id',
                                        'Servicegroups.id'
                                    ])
                                    ->innerJoinWith('Hosts')
                                    ->where(['Hosts.satellite_id' => $satelliteId])
                                    ->leftJoinWith('Servicegroups')
                                    ->whereNull('Servicegroups.id');
                                return $query;
                            }
                        ]);
                    return $query;
                },

                // Get all services from this service group
                'Services'         => function (Query $query) use ($satelliteId) {
                    $query->disableAutoFields()
                        ->select([
                            'Services.id',
                            'Services.uuid',
                        ])
                        ->innerJoinWith('Hosts')
                        ->where(['Hosts.satellite_id' => $satelliteId]);
                    return $query;
                }
            ])
            ->where([
                'Servicegroups.id' => $id
            ])
            ->disableHydration()
            ->first();

        $services = [];

        if (!empty($servicegroup['services'])) {
            foreach ($servicegroup['services'] as $service) {
                $services[$service['id']] = $service;
            }
        }

        if (!empty($servicegroup['servicetemplates'])) {
            foreach ($servicegroup['servicetemplates'] as $servicetemplate) {
                foreach ($servicetemplate['services'] as $service) {
                    $services[$service['id']] = $service;
                }
            }
        }

        return $services;
    }

    /**
     * @param int $serviceId
     * @param int|array $servicegroupIds
     * @return bool
     */
    public function isServiceInServicegroup($serviceId, $servicegroupIds) {
        if (!is_array($servicegroupIds)) {
            $servicegroupIds = [$servicegroupIds];
        }
        $servicegroupIds = array_unique($servicegroupIds);

        /** @var ServicesToServicegroupsTable $ServicesToServicegroupsTable */
        $ServicesToServicegroupsTable = TableRegistry::getTableLocator()->get('ServicesToServicegroups');

        $count = $ServicesToServicegroupsTable->find()
            ->where([
                'service_id'         => $serviceId,
                'servicegroup_id IN' => $servicegroupIds
            ])
            ->count();

        if ($count > 0) {
            return true;
        }

        // Through servicetemplate maybe?
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $service = $ServicesTable->get($serviceId, contain: [
            'Servicegroups'
        ]);

        if (empty($service->get('servicegroups'))) {
            /** @var ServicetemplatesToServicegroupsTable $ServicetemplatesToServicegroupsTable */
            $ServicetemplatesToServicegroupsTable = TableRegistry::getTableLocator()->get('ServicetemplatesToServicegroups');
            $count = $ServicetemplatesToServicegroupsTable->find()
                ->where([
                    'servicetemplate_id' => $service->get('servicetemplate_id'),
                    'servicegroup_id IN' => $servicegroupIds
                ])
                ->count();

            return $count > 0;
        }

        return false;
    }

    /**
     * @param $containerId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicegroupByContainerId($containerId, $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Servicegroups.id'
            ])
            ->contain([
                'Services'         => function (Query $query) {
                    return $query
                        ->disableAutoFields()
                        ->select(['id']);
                },
                'Servicetemplates' => function (Query $query) {
                    return $query
                        ->disableAutoFields()
                        ->select(['id']);
                }
            ])
            ->where([
                'Servicegroups.container_id' => $containerId
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $q->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $q;
            });
        }
        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param string $index
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicegroupsByContainerIdExact($containerId, $type = 'all', $index = 'container_id', $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Servicegroups.id',
                'Containers.id',
                'Containers.name'
            ])
            ->contain([
                'Containers'
            ])
            ->where([
                'Containers.parent_id'        => $containerId,
                'Containers.containertype_id' => CT_SERVICEGROUP
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $query->orderBy([
            'Containers.name' => 'asc'
        ]);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        if ($type === 'all') {
            return $result;
        }

        $list = [];
        foreach ($result as $row) {
            if ($index === 'id') {
                $list[$row['id']] = $row['container']['name'];
            } else {
                $list[$row['container']['id']] = $row['container']['name'];
            }
        }

        return $list;
    }

    /**
     * @param $containerIds
     * @param $hostIds
     * @param $type
     * @param $index
     * @return array
     */
    public function getServicegroupsByContainerIdAndServiceIds($containerIds, $serviceIds, $type = 'all', $index = 'container_id') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $tenantContainerIds = [];

        foreach ($containerIds as $container_id) {
            if ($container_id != ROOT_CONTAINER) {
                // Get contaier id of the tenant container
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load contactgroups and contacts
                $path = $ContainersTable->getPathByIdAndCacheResult($container_id, 'ServicegroupServicegroupsByContainerId');

                // Tenant host groups are available for all users of a tenant (oITC V2 legacy)
                $tenantContainerIds[] = $path[1]['id'];
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);
        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));


        switch ($type) {
            case 'all':
                $query = $this->find()
                    ->contain([
                        'Containers',
                        'Services' => function (Query $query) use ($containerIds, $serviceIds) {
                            $query->disableAutoFields()
                                ->select([
                                    'Services.id',
                                    'Services.uuid',
                                ])
                                ->innerJoinWith('Hosts')
                                ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($containerIds, $serviceIds) {
                                    if (!empty($MY_RIGHTS)) {
                                        $q->where([
                                            'HostsToContainersSharing.id IN ' => $containerIds
                                        ]);
                                    }
                                    return $q;
                                })->where([
                                    'Services.id IN ' => $serviceIds
                                ]);
                            return $query;
                        }
                    ])
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_SERVICEGROUP
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();
                return $this->emptyArrayIfNull($query->toArray());
            default:
                $query = $this->find()
                    ->contain([
                        'Containers',
                        'Services' => function (Query $query) use ($containerIds, $serviceIds) {
                            $query->disableAutoFields()
                                ->select([
                                    'Services.id',
                                    'Services.uuid',
                                ])
                                ->innerJoinWith('Hosts')
                                ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($containerIds, $serviceIds) {
                                    if (!empty($MY_RIGHTS)) {
                                        $q->where([
                                            'HostsToContainersSharing.id IN ' => $containerIds
                                        ]);
                                    }
                                    return $q;
                                })->where([
                                    'Services.id IN ' => $serviceIds
                                ]);
                            return $query;
                        }
                    ])
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_SERVICEGROUP
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();
                $query = $query->toArray();
                if (empty($query)) {
                    $query = [];
                }
                $return = [];
                foreach ($query as $servicegroup) {
                    if ($index === 'id') {
                        $return[$servicegroup['id']] = $servicegroup['container']['name'];
                    } else {
                        $return[$servicegroup['container_id']] = $servicegroup['container']['name'];
                    }
                }

                return $return;
        }
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicegroupsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['Servicegroups.id IN' => $ids])
            ->contain([
                'Containers'
            ])
            ->orderBy(['Servicegroups.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    public function getSourceServicegroupForCopy($id, array $MY_RIGHTS) {
        $query = $this->find()
            ->where(['Servicegroups.id' => $id])
            ->contain([
                'Services',
                'Servicetemplates',
                'Containers'
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $result = $query->firstOrFail();
        $servicegroup = $result;
        $servicegroup['services'] = [
            '_ids' => Hash::extract($result, 'services.{n}.id')
        ];
        $servicegroup['servicetemplates'] = [
            '_ids' => Hash::extract($result, 'servicetemplates.{n}.id')
        ];
        return $servicegroup;
    }

    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedServicegroupsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }

    /**
     * @param int $serviceId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServiceGroupsByServiceId(int $serviceId, array $MY_RIGHTS): array {
        $query = $this->find()
            ->select([
                'Servicegroups.id',
                'Containers.name'
            ])
            ->innerJoin(
                ['ServicesToServicegroupsTable' => 'services_to_servicegroups'],
                [
                    'ServicesToServicegroupsTable.servicegroup_id = Servicegroups.id',
                    "ServicesToServicegroupsTable.service_id" => $serviceId
                ]
            )
            ->innerJoin(
                ['Containers' => 'containers'],
                [
                    'Servicegroups.container_id = Containers.id'
                ]
            )
            ->disableHydration();
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        $return = [];
        foreach ($query->toArray() as $result) {
            $return[] = [
                'name' => $result['Containers']['name'],
                'id'   => $result['id']
            ];
        }
        return $return;
    }

    /**
     * This method provides a unified way to create new servicegroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Servicegroup $entity The entity that will be saved by the Table
     * @param array $servicegroup The servicegroup as array ( [ Servicegroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Servicegroup
     */
    public function createServicegroup(Servicegroup $entity, array $servicegroup, int $userId): Servicegroup {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $extDataForChangelog = $this->resolveDataForChangelog($servicegroup);

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'add',
            'servicegroups',
            $entity->get('id'),
            OBJECT_SERVICEGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($servicegroup, $extDataForChangelog)
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }
        return $entity;
    }

    /**
     * This method provides a unified way to update an existing servicegroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Servicegroup $entity The entity that will be updated by the Table
     * @param array $newServicegroup The new servicegroup as array ( [ Servicegroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param array $oldServicegroup The old servicegroup as array ( [ Servicegroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Servicegroup
     */
    public function updateServicegroup(Servicegroup $entity, array $newServicegroup, array $oldServicegroup, int $userId): Servicegroup {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'edit',
            'servicegroups',
            $entity->get('id'),
            OBJECT_SERVICEGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($this->resolveDataForChangelog($newServicegroup), $newServicegroup),
            array_merge($this->resolveDataForChangelog($oldServicegroup), $oldServicegroup)
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        return $entity;
    }
}
