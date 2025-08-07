<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Database\Expression\Comparison;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServiceescalationsFilter;

/**
 * Serviceescalations Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ServiceescalationsTable|\Cake\ORM\Association\HasMany $Services
 * @property \App\Model\Table\ServiceescalationsTable|\Cake\ORM\Association\HasMany $Servicegroups
 *
 * @method \App\Model\Entity\Serviceescalation get($primaryKey, $options = [])
 * @method \App\Model\Entity\Serviceescalation newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Serviceescalation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Serviceescalation|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Serviceescalation|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Serviceescalation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Serviceescalation[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Serviceescalation findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServiceescalationsTable extends Table {

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

        $this->setTable('serviceescalations');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);
        $this->belongsTo('Timeperiods', [
            'foreignKey' => 'timeperiod_id',
            'joinType'   => 'LEFT'
        ]);
        $this->belongsToMany('Contacts', [
            'joinTable'    => 'contacts_to_serviceescalations',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('Contactgroups', [
            'joinTable'    => 'contactgroups_to_serviceescalations',
            'saveStrategy' => 'replace'
        ]);

        $this->belongsToMany('Services', [
            'className'    => 'Services',
            'through'      => 'ServiceescalationsServiceMemberships',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('ServicesExcluded', [
            'className'        => 'Services',
            'through'          => 'ServiceescalationsServiceMemberships',
            'targetForeignKey' => 'service_id',
            'saveStrategy'     => 'replace'
        ]);
        $this->belongsToMany('Servicegroups', [
            'through'      => 'ServiceescalationsServicegroupMemberships',
            'saveStrategy' => 'replace'

        ]);
        $this->belongsToMany('ServicegroupsExcluded', [
            'className'        => 'Servicegroups',
            'through'          => 'ServiceescalationsServicegroupMemberships',
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
            ->add('contacts', 'custom', [
                'rule'    => [$this, 'atLeastOne'],
                'message' => __('You must specify at least one contact or contact group.')
            ]);

        $validator
            ->add('contactgroups', 'custom', [
                'rule'    => [$this, 'atLeastOne'],
                'message' => __('You must specify at least one contact or contact group.')
            ]);

        $validator
            ->add('services', 'custom', [
                'rule'    => [$this, 'atLeastOneServiceOrServicegroup'],
                'message' => __('You have to choose at least one service or one service group.')
            ]);

        $validator
            ->add('servicegroups', 'custom', [
                'rule'    => [$this, 'atLeastOneServiceOrServicegroup'],
                'message' => __('You have to choose at least one service or one service group.')
            ]);


        $validator
            ->integer('first_notification')
            ->greaterThan('first_notification', 0)
            ->lessThanField('first_notification', 'last_notification', __('The first notification must be before the last notification.'),
                function ($context) {
                    return !($context['data']['last_notification'] === 0);
                })
            ->requirePresence('first_notification')
            ->allowEmptyString('first_notification', null, false);

        $validator
            ->integer('last_notification')
            ->greaterThanOrEqual('last_notification', 0)
            ->greaterThanField('last_notification', 'first_notification', __('The first notification must be before the last notification.'),
                function ($context) {
                    return !($context['data']['last_notification'] === 0);
                })
            ->requirePresence('last_notification')
            ->allowEmptyString('last_notification', null, false);

        $validator
            ->integer('notification_interval')
            ->greaterThan('notification_interval', 0)
            ->requirePresence('notification_interval')
            ->allowEmptyString('notification_interval', null, false);

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
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for contacts and or contact groups
     */
    public function atLeastOne($value, $context) {
        return !empty($context['data']['contacts']['_ids']) || !empty($context['data']['contactgroups']['_ids']);
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for services and or service groups
     */
    public function atLeastOneServiceOrServicegroup($value, $context) {
        return !empty($context['data']['services']) || !empty($context['data']['servicegroups']);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Serviceescalations.id' => $id]);
    }

    /**
     * @param ServiceescalationsFilter $ServiceescalationsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServiceescalationsIndex(ServiceescalationsFilter $ServiceescalationsFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Contacts'         => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Contacts.id',
                            'Contacts.name'
                        ]);
                },
                'Contactgroups'    => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'Contactgroups.id',
                                'Containers.name'
                            ]);
                    },
                ],
                'Timeperiods'      => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Timeperiods.id',
                            'Timeperiods.name'
                        ]);
                },
                'Services'         => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'ServiceescalationsServiceMemberships.excluded' => 0
                        ])
                        ->innerJoinWith('Servicetemplates')
                        ->innerJoinWith('Hosts')
                        ->select([
                            'Services.id',
                            'servicename' => $q->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                            'Services.disabled'
                        ]);
                },
                'ServicesExcluded' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'ServiceescalationsServiceMemberships.excluded' => 1
                        ])
                        ->innerJoinWith('Servicetemplates')
                        ->innerJoinWith('Hosts')
                        ->select([
                            'ServicesExcluded.id',
                            'servicename' => $q->newExpr('CONCAT(Hosts.name, "/", IF(ServicesExcluded.name IS NULL, Servicetemplates.name, ServicesExcluded.name))'),
                            'ServicesExcluded.disabled'
                        ]);
                },

                'Servicegroups'         => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'ServiceescalationsServicegroupMemberships.excluded' => 0
                            ])
                            ->select([
                                'Servicegroups.id',
                                'Containers.name'
                            ]);
                    },
                ],
                'ServicegroupsExcluded' => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'ServiceescalationsServicegroupMemberships.excluded' => 1
                            ])
                            ->select([
                                'ServicegroupsExcluded.id',
                                'Containers.name'
                            ]);
                    },
                ]
            ])
            ->groupBy('Serviceescalations.id')
            ->disableHydration();
        $indexFilter = $ServiceescalationsFilter->indexFilter();
        $containFilter = [
            'Servicegroups.name'         => '',
            'ServicegroupsExcluded.name' => ''
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
                'ServiceescalationsServiceMemberships.excluded' => 0,
                $where
            ]);

        }

        unset($indexFilter['Services.servicename LIKE']);

        if (!empty($indexFilter['ServicesExcluded.servicename LIKE'])) {
            $query->innerJoinWith('ServicesExcluded', function (Query $q) use ($indexFilter) {
                return $q->innerJoinWith('Hosts')
                    ->innerJoinWith('Servicetemplates');
            });
            $where = new Comparison(
                'CONCAT(Hosts.name, "/", IF(ServicesExcluded.name IS NULL, Servicetemplates.name, ServicesExcluded.name))',
                $indexFilter['ServicesExcluded.servicename LIKE'],
                'string',
                'LIKE'
            );
            $query->where([
                'ServiceescalationsServiceMemberships.excluded' => 1,
                $where
            ]);
        }

        unset($indexFilter['ServicesExcluded.servicename LIKE']);


        if (!empty($indexFilter['Servicegroups.name LIKE'])) {
            $containFilter['Servicegroups.name'] = [
                'Containers.name LIKE' => $indexFilter['Servicegroups.name LIKE']
            ];
            $query->innerJoinWith('Servicegroups.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'ServiceescalationsServicegroupMemberships.excluded' => 0,
                    $containFilter['Servicegroups.name']
                ]);
            });
            unset($indexFilter['Servicegroups.name LIKE']);
        }
        if (!empty($indexFilter['ServicegroupsExcluded.name LIKE'])) {
            $containFilter['ServicegroupsExcluded.name'] = [
                'Containers.name LIKE' => $indexFilter['ServicegroupsExcluded.name LIKE']
            ];
            $query->innerJoinWith('ServicegroupsExcluded.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'ServiceescalationsServicegroupMemberships.excluded' => 1,
                    $containFilter['ServicegroupsExcluded.name']
                ]);
            });
            unset($indexFilter['ServicegroupsExcluded.name LIKE']);
        }
        if (!empty($MY_RIGHTS)) {
            $indexFilter['Serviceescalations.container_id IN'] = $MY_RIGHTS;
        }

        if (!empty($indexFilter['Serviceescalations.notification_interval LIKE'])) {
            $query->where(
                ['Serviceescalations.notification_interval LIKE' => $indexFilter['Serviceescalations.notification_interval LIKE']],
                ['Serviceescalations.notification_interval' => 'string']
            );
            unset($indexFilter['Serviceescalations.notification_interval LIKE']);
        }

        $query->where($indexFilter);


        $query->orderBy($ServiceescalationsFilter->getOrderForPaginator('Serviceescalations.id', 'asc'));
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
     * @param array|int $services
     * @param array|int $excluded_services
     * @return array
     */
    public function parseServiceMembershipData($services = [], $excluded_services = []) {
        $servicemembershipData = [];
        foreach ($services as $serviceId) {
            $servicemembershipData[] = [
                'id'        => $serviceId,
                '_joinData' => [
                    'excluded' => 0
                ]
            ];
        }
        foreach ($excluded_services as $excluded_serviceId) {
            $servicemembershipData[] = [
                'id'        => $excluded_serviceId,
                '_joinData' => [
                    'excluded' => 1
                ]
            ];
        }
        return $servicemembershipData;
    }

    /**
     * @param array $servicegroups
     * @param array $excluded_servicegroups
     * @return array
     */
    public function parseServicegroupMembershipData($servicegroups = [], $excluded_servicegroups = []) {
        $servicegroupmembershipData = [];
        foreach ($servicegroups as $servicegroupId) {
            $servicegroupmembershipData[] = [
                'id'        => $servicegroupId,
                '_joinData' => [
                    'excluded' => 0
                ]
            ];
        }
        foreach ($excluded_servicegroups as $excluded_servicegroupId) {
            $servicegroupmembershipData[] = [
                'id'        => $excluded_servicegroupId,
                '_joinData' => [
                    'excluded' => 1
                ]
            ];
        }
        return $servicegroupmembershipData;
    }

    /**
     * @param null|string $uuid
     * @return array
     */
    public function getServiceescalationsForExport($uuid = null) {
        $query = $this->find()
            ->contain([
                'Services'      =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->contain('Hosts')
                            ->where([
                                'Hosts.disabled'    => 0,
                                'Services.disabled' => 0
                            ])
                            ->select([
                                'Hosts.uuid',
                                'Services.id',
                                'Services.uuid'
                            ]);
                    },
                'Servicegroups' =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid', 'id'])
                            ->contain(
                                [
                                    'Services'         => function (Query $q) {
                                        return $q->enableAutoFields(false)
                                            ->select([
                                                'Services.id'
                                            ])->where(['Services.disabled' => 0]);
                                    },
                                    'Servicetemplates' => function (Query $q) {
                                        return $q->enableAutoFields(false)
                                            ->select([
                                                'Servicetemplates.id'
                                            ])
                                            ->contain([
                                                'Services' => function (Query $query) {
                                                    $query
                                                        ->enableAutoFields(false)
                                                        ->select([
                                                            'Services.id',
                                                            'Services.servicetemplate_id'
                                                        ])->where(['Services.disabled' => 0]);
                                                    $query
                                                        ->leftJoinWith('Servicegroups')
                                                        ->whereNull('Servicegroups.id');
                                                    return $query;
                                                }
                                            ]);
                                    }

                                ]
                            );
                    },
                'Timeperiods'   =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid']);
                    },
                'Contacts'      =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid']);
                    },
                'Contactgroups' =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid']);
                    }
            ])
            ->select([
                'id',
                'uuid',
                'timeperiod_id',
                'first_notification',
                'last_notification',
                'notification_interval',
                'escalate_on_recovery',
                'escalate_on_warning',
                'escalate_on_critical',
                'escalate_on_unknown'
            ]);
        if ($uuid !== null) {
            $query->where([
                'Serviceescalations.uuid' => $uuid
            ]);
        }
        $query->all();
        return $query;
    }

    /**
     * @param int|null $id
     * @param int|null $serviceId
     * @return bool
     */
    public function isServiceescalationBroken($id = null, $serviceId = null) {
        if (!$this->exists(['Serviceescalations.id' => $id]) && $id !== null) {
            throw new \NotFoundException();
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
                                'Services.disabled'                             => 0,
                                'ServiceescalationsServiceMemberships.excluded' => 0
                            ])
                            ->select(['id']);
                    },
            ])->select([
                'id'
            ])->where([
                ['Serviceescalations.id' => $id]
            ])
            ->first();

        $services = $query->get('services');

        return empty($services);
    }

    /**
     * @param int $contactId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getServiceescalationsByContactId($contactId, $MY_RIGHTS = [], $enableHydration = true) {

        /** @var ContactsToServiceescalationsTable $ContactsToServiceescalationsTable */
        $ContactsToServiceescalationsTable = TableRegistry::getTableLocator()->get('ContactsToServiceescalations');

        $query = $ContactsToServiceescalationsTable->find()
            ->select([
                'serviceescalation_id'
            ])
            ->where([
                'contact_id' => $contactId
            ])
            ->groupBy([
                'serviceescalation_id'
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $serviceescalationIds = Hash::extract($result, '{n}.serviceescalation_id');

        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where([
            'Serviceescalations.id IN' => $serviceescalationIds
        ]);

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Serviceescalations.container_id IN' => $MY_RIGHTS]);
            }
            return $q;
        });

        $query->enableHydration($enableHydration);
        $query->orderBy([
            'Containers.name' => 'asc'
        ]);

        $result = $query->all();

        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @param $id
     * @return array
     */
    public function getServiceescalationById($id) {
        $query = $this->find()
            ->where([
                'Serviceescalations.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByServiceescalations($timeperiodId) {
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
    public function getServiceescalationsByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Serviceescalations.id'
            ])
            ->where([
                'timeperiod_id' => $timeperiodId,

            ]);

        if (!empty($MY_RIGHTS)) {
            $query->where(['Serviceescalations.container_id IN' => $MY_RIGHTS]);
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
    public function getServiceescalationsByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Serviceescalations.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Serviceescalations.' . $index,
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Serviceescalations.container_id IN' => $MY_RIGHTS
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
            $list[$row[$index]] = __('Service escalation #{0}', $row['id']);
        }

        return $list;
    }
}
