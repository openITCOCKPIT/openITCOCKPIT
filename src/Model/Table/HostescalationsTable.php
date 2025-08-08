<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostescalationsFilter;

/**
 * Hostescalations Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\HostescalationTable|\Cake\ORM\Association\HasMany $Hosts
 * @property \App\Model\Table\HostescalationTable|\Cake\ORM\Association\HasMany $Hostgroups
 *
 * @method \App\Model\Entity\Hostescalation get($primaryKey, $options = [])
 * @method \App\Model\Entity\Hostescalation newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Hostescalation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Hostescalation|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hostescalation|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hostescalation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Hostescalation[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Hostescalation findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class HostescalationsTable extends Table {

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

        $this->setTable('hostescalations');
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
            'joinTable'    => 'contacts_to_hostescalations',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('Contactgroups', [
            'joinTable'    => 'contactgroups_to_hostescalations',
            'saveStrategy' => 'replace'
        ]);

        $this->belongsToMany('Hosts', [
            'className'    => 'Hosts',
            'through'      => 'HostescalationsHostMemberships',
            'saveStrategy' => 'replace'
        ]);
        $this->belongsToMany('HostsExcluded', [
            'className'        => 'Hosts',
            'through'          => 'HostescalationsHostMemberships',
            'targetForeignKey' => 'host_id',
            'saveStrategy'     => 'replace'
        ]);
        $this->belongsToMany('Hostgroups', [
            'through'      => 'HostescalationsHostgroupMemberships',
            'saveStrategy' => 'replace'

        ]);
        $this->belongsToMany('HostgroupsExcluded', [
            'className'        => 'Hostgroups',
            'through'          => 'HostescalationsHostgroupMemberships',
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
            ->add('hosts', 'custom', [
                'rule'    => [$this, 'atLeastOneHostOrHostgroup'],
                'message' => __('You have to choose at least one host or one host group.')
            ]);

        $validator
            ->add('hostgroups', 'custom', [
                'rule'    => [$this, 'atLeastOneHostOrHostgroup'],
                'message' => __('You have to choose at least one host or one host group.')
            ]);

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
     * Custom validation rule for hosts and or host groups
     */
    public function atLeastOneHostOrHostgroup($value, $context) {
        return !empty($context['data']['hosts']) || !empty($context['data']['hostgroups']);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Hostescalations.id' => $id]);
    }

    /**
     * @param HostescalationsFilter $HostescalationsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostescalationsIndex(HostescalationsFilter $HostescalationsFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Contacts'      => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Contacts.id',
                            'Contacts.name'
                        ]);
                },
                'Contactgroups' => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'Contactgroups.id',
                                'Containers.name'
                            ]);
                    },
                ],
                'Timeperiods'   => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Timeperiods.id',
                            'Timeperiods.name'
                        ]);
                },
                'Hosts'         => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'HostescalationsHostMemberships.excluded' => 0
                        ])
                        ->select([
                            'Hosts.id',
                            'Hosts.name',
                            'Hosts.disabled'
                        ]);
                },
                'HostsExcluded' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->where([
                            'HostescalationsHostMemberships.excluded' => 1
                        ])
                        ->select([
                            'HostsExcluded.id',
                            'HostsExcluded.name',
                            'HostsExcluded.disabled'
                        ]);
                },

                'Hostgroups'         => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'HostescalationsHostgroupMemberships.excluded' => 0
                            ])
                            ->select([
                                'Hostgroups.id',
                                'Containers.name'
                            ]);
                    },
                ],
                'HostgroupsExcluded' => [
                    'Containers' => function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'HostescalationsHostgroupMemberships.excluded' => 1
                            ])
                            ->select([
                                'HostgroupsExcluded.id',
                                'Containers.name'
                            ]);
                    },
                ]
            ])
            ->select([
                'Hostescalations.id',
                'Hostescalations.uuid',
                'Hostescalations.container_id',
                'Hostescalations.first_notification',
                'Hostescalations.last_notification',
                'Hostescalations.last_notification',
                'Hostescalations.notification_interval',
                'Hostescalations.escalate_on_recovery',
                'Hostescalations.escalate_on_down',
                'Hostescalations.escalate_on_unreachable'
            ])
            ->groupBy('Hostescalations.id')
            ->disableHydration();

        $indexFilter = $HostescalationsFilter->indexFilter();
        $containFilter = [
            'Hosts.name'              => '',
            'HostsExcluded.name'      => '',
            'Hostgroups.name'         => '',
            'HostgroupsExcluded.name' => ''
        ];
        if (!empty($indexFilter['Hosts.name LIKE'])) {
            $containFilter['Hosts.name'] = [
                'Hosts.name LIKE' => $indexFilter['Hosts.name LIKE']
            ];
            $query->innerJoinWith('Hosts', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostescalationsHostMemberships.excluded' => 0,
                    $containFilter['Hosts.name']
                ]);
            });
            unset($indexFilter['Hosts.name LIKE']);
        }

        if (!empty($indexFilter['HostsExcluded.name LIKE'])) {
            $containFilter['HostsExcluded.name'] = [
                'HostsExcluded.name LIKE' => $indexFilter['HostsExcluded.name LIKE']
            ];
            $query->innerJoinWith('HostsExcluded', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostescalationsHostMemberships.excluded' => 1,
                    $containFilter['HostsExcluded.name']
                ]);
            });
            unset($indexFilter['HostsExcluded.name LIKE']);

        }
        if (!empty($indexFilter['Hostgroups.name LIKE'])) {
            $containFilter['Hostgroups.name'] = [
                'Containers.name LIKE' => $indexFilter['Hostgroups.name LIKE']
            ];
            $query->innerJoinWith('Hostgroups.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostescalationsHostgroupMemberships.excluded' => 0,
                    $containFilter['Hostgroups.name']
                ]);
            });
            unset($indexFilter['Hostgroups.name LIKE']);
        }
        if (!empty($indexFilter['HostgroupsExcluded.name LIKE'])) {
            $containFilter['HostgroupsExcluded.name'] = [
                'Containers.name LIKE' => $indexFilter['HostgroupsExcluded.name LIKE']
            ];
            $query->innerJoinWith('HostgroupsExcluded.Containers', function (Query $q) use ($containFilter) {
                return $q->where([
                    'HostescalationsHostgroupMemberships.excluded' => 1,
                    $containFilter['HostgroupsExcluded.name']
                ]);
            });
            unset($indexFilter['HostgroupsExcluded.name LIKE']);
        }

        if (!empty($MY_RIGHTS)) {
            $indexFilter['Hostescalations.container_id IN'] = $MY_RIGHTS;
        }
        if (!empty($indexFilter['Hostescalations.notification_interval LIKE'])) {
            $query->where(
                ['Hostescalations.notification_interval LIKE' => $indexFilter['Hostescalations.notification_interval LIKE']],
                ['Hostescalations.notification_interval' => 'string']
            );
            unset($indexFilter['Hostescalations.notification_interval LIKE']);
        }
        $query->where($indexFilter);
        $query->orderBy($HostescalationsFilter->getOrderForPaginator('Hostescalations.id', 'asc'));
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
     * @param array|int $hosts
     * @param array|int $excluded_hosts
     * @return array
     */
    public function parseHostMembershipData($hosts = [], $excluded_hosts = []) {
        $hostmembershipData = [];
        foreach ($hosts as $host) {
            $hostmembershipData[] = [
                'id'        => $host,
                '_joinData' => [
                    'excluded' => 0
                ]
            ];
        }
        foreach ($excluded_hosts as $excluded_host) {
            $hostmembershipData[] = [
                'id'        => $excluded_host,
                '_joinData' => [
                    'excluded' => 1
                ]
            ];
        }
        return $hostmembershipData;
    }

    /**
     * @param array $hostgroups
     * @param array $excluded_hostgroups
     * @return array
     */
    public function parseHostgroupMembershipData($hostgroups = [], $excluded_hostgroups = []) {
        $hostgroupmembershipData = [];
        foreach ($hostgroups as $hostgroup) {
            $hostgroupmembershipData[] = [
                'id'        => $hostgroup,
                '_joinData' => [
                    'excluded' => 0
                ]
            ];
        }
        foreach ($excluded_hostgroups as $excluded_hostgroup) {
            $hostgroupmembershipData[] = [
                'id'        => $excluded_hostgroup,
                '_joinData' => [
                    'excluded' => 1
                ]
            ];
        }
        return $hostgroupmembershipData;
    }

    /**
     * @param $uuid
     * @return Query
     */
    public function getHostescalationsForExport($uuid = null) {
        $query = $this->find()
            ->contain([
                'Hosts'         =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->where([
                                'Hosts.disabled' => 0
                            ])
                            ->select(['uuid', 'id']);
                    },
                'Hostgroups'    =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select(['uuid', 'id'])
                            ->contain(
                                [
                                    'Hosts'         => function (Query $q) {
                                        return $q->enableAutoFields(false)
                                            ->select([
                                                'Hosts.id'
                                            ])->where(['Hosts.disabled' => 0]);
                                    },
                                    'Hosttemplates' => function (Query $q) {
                                        return $q->enableAutoFields(false)
                                            ->select([
                                                'Hosttemplates.id'
                                            ])
                                            ->contain([
                                                'Hosts' => function (Query $query) {
                                                    $query
                                                        ->enableAutoFields(false)
                                                        ->select([
                                                            'Hosts.id',
                                                            'Hosts.hosttemplate_id'
                                                        ])->where(['Hosts.disabled' => 0]);
                                                    $query
                                                        ->leftJoinWith('Hostgroups')
                                                        ->whereNull('Hostgroups.id');
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
                'escalate_on_down',
                'escalate_on_unreachable'
            ]);
        if ($uuid !== null) {
            $query->where([
                'Hostescalations.uuid' => $uuid
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
    public function isHostescalationBroken($id = null, $hostId = null) {
        if (!$this->exists(['Hostescalations.id' => $id]) && $id !== null) {
            throw new \NotFoundException();
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
                                'Hosts.disabled'                          => 0,
                                'HostescalationsHostMemberships.excluded' => 0
                            ])
                            ->select(['id']);
                    },
            ])->select([
                'id'
            ])->where([
                ['Hostescalations.id' => $id]
            ])
            ->first();

        $hosts = $query->get('hosts');

        return empty($hosts);
    }

    /**
     * @param int $contactId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getHostescalationsByContactId($contactId, $MY_RIGHTS = [], $enableHydration = true) {

        /** @var ContactsToHostescalationsTable $ContactsToHostescalationsTable */
        $ContactsToHostescalationsTable = TableRegistry::getTableLocator()->get('ContactsToHostescalations');

        $query = $ContactsToHostescalationsTable->find()
            ->select([
                'hostescalation_id'
            ])
            ->where([
                'contact_id' => $contactId
            ])
            ->groupBy([
                'hostescalation_id'
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $hostescalationIds = Hash::extract($result, '{n}.hostescalation_id');

        $query = $this->find('all');
        $query->contain(['Containers']);

        $query->where([
            'Hostescalations.id IN' => $hostescalationIds
        ]);

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Hostescalations.container_id IN' => $MY_RIGHTS]);
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
    public function getHostescalationById($id) {
        $query = $this->find()
            ->where([
                'Hostescalations.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByHostescalations($timeperiodId) {
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
    public function getHostescalationsByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Hostescalations.id'
            ])
            ->where([
                'timeperiod_id' => $timeperiodId,

            ]);

        if (!empty($MY_RIGHTS)) {
            $query->where(['Hostescalations.container_id IN' => $MY_RIGHTS]);
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
    public function getHostescalationsByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Hostescalations.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Hostescalations.' . $index,
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Hostescalations.container_id IN' => $MY_RIGHTS
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
            $list[$row[$index]] = __('Host escalation #{0}', $row['id']);
        }

        return $list;
    }
}
