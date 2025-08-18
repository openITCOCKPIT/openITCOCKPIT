<?php

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\SystemdowntimesConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Systemdowntimes Model
 *
 *
 * @method \App\Model\Entity\Systemdowntime get($primaryKey, $options = [])
 * @method \App\Model\Entity\Systemdowntime newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Systemdowntime[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Systemdowntime|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Systemdowntime saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Systemdowntime patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Systemdowntime[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Systemdowntime findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SystemdowntimesTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('systemdowntimes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Hosts',
        ]);

        $this->belongsTo('Services', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Services',
        ]);

        $this->belongsTo('Hostgroups', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Hostgroups',
        ]);

        $this->belongsTo('Containers', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Containers',
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
            ->integer('objecttype_id')
            ->allowEmptyString('objecttype_id', null, false)
            ->inList('objecttype_id', [OBJECT_HOST, OBJECT_SERVICE, OBJECT_HOSTGROUP, OBJECT_NODE, OBJECT_SATELLITE]);

        $validator
            ->integer('downtimetype_id')
            ->allowEmptyString('downtimetype_id', null, false)
            ->inList('downtimetype_id', [0, 1]);

        $validator
            ->integer('object_id')
            ->allowEmptyString('object_id', null, false)
            ->greaterThan('object_id', 0, __('Please select at least on object.'));

        $validator
            ->scalar('weekdays')
            ->maxLength('weekdays', 255)
            ->notEmptyString('weekdays', __('You must select at least one day of the week or one day of the month, or both'), function ($context) {
                if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                    //Only required for recurring downtimes
                    return empty($context['data']['day_of_month']);
                }
                return false;
            })
            ->add('weekdays', 'custom', [
                'rule'    => function ($value, $context) {
                    if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                        //Only required for recurring downtimes

                        if (!isset($context['data']['weekdays'])) {
                            return false;
                        }

                        $weekdays = explode(',', $context['data']['weekdays']);
                        $validDays = [1, 2, 3, 4, 5, 6, 7];
                        foreach ($weekdays as $weekday) {
                            $weekday = (int)$weekday;
                            if (!in_array($weekday, $validDays, true)) {
                                return false;
                            }
                        }
                        return true;
                    }

                    return true;
                },
                'message' => __('Weekdays needs to be in range (1-7).')
            ]);

        $validator
            ->scalar('day_of_month')
            ->maxLength('day_of_month', 255)
            ->notEmptyString('day_of_month', __('You must select at least one day of the week or one day of the month, or both'), function ($context) {
                if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                    //Only required for recurring downtimes
                    return empty($context['data']['weekdays']);
                }
                return false;
            })
            ->add('day_of_month', 'custom', [
                'rule'    => function ($value, $context) {
                    if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                        //Only required for recurring downtimes
                        if (isset($context['data']['day_of_month'])) {
                            $days = explode(',', $context['data']['day_of_month']);
                            $validDays = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31];
                            foreach ($days as $day) {
                                $day = (int)$day;
                                if (!in_array($day, $validDays, true)) {
                                    return false;
                                }
                            }
                            return true;
                        }
                    }

                    return true;
                },
                'message' => __('Weekdays needs to be in range (1-31).')
            ]);

        $validator
            ->scalar('from_time')
            ->maxLength('from_time', 255)
            ->requirePresence('from_time', 'create')
            ->add('from_time', 'custom', [
                'rule'    => function ($value, $context) {
                    if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                        //Recurring downtimes only have a start time (no date)
                        preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $context['data']['from_time'], $matches);
                        if (!empty($matches)) {
                            return true;
                        }

                        return false;
                    }

                    if (!isset($context['data']['from_date']) || !isset($context['data']['from_time'])) {
                        return false;
                    }

                    $datetime = $context['data']['from_date'] . ' ' . $context['data']['from_time'];
                    $timestamp = strtotime($datetime);
                    if ($timestamp === false) {
                        return false;
                    }

                    if (is_numeric($timestamp) && $timestamp > 0) {
                        return true;
                    }
                    return false;
                },
                'message' => __('Start time needs to be a valid date.')
            ]);

        $validator
            ->scalar('to_time')
            ->maxLength('to_time', 255)
            ->add('to_time', 'custom', [
                'rule'    => function ($value, $context) {
                    if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                        //Recurring dont have to_time
                        return true;
                    }

                    if (!isset($context['data']['to_date']) || !isset($context['data']['to_time'])) {
                        return false;
                    }

                    $datetime = $context['data']['to_date'] . ' ' . $context['data']['to_time'];
                    $timestamp = strtotime($datetime);
                    if ($timestamp === false) {
                        return false;
                    }

                    if (is_numeric($timestamp) && $timestamp > 0) {
                        return true;
                    }
                    return false;
                },
                'message' => __('End time needs to be a valid date.')
            ])
            ->add('to_time', 'custom2', [
                'rule'    => function ($value, $context) {
                    if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                        //Recurring dont have to_time
                        return true;
                    }

                    if (!isset($context['data']['from_date']) || !isset($context['data']['from_time'])) {
                        return false;
                    }

                    if (!isset($context['data']['to_date']) || !isset($context['data']['to_time'])) {
                        return false;
                    }


                    $fromDatetime = $context['data']['from_date'] . ' ' . $context['data']['from_time'];
                    $fromTimestamp = strtotime($fromDatetime);
                    if ($fromTimestamp === false) {
                        return false;
                    }

                    $toDatetime = $context['data']['to_date'] . ' ' . $context['data']['to_time'];
                    $toTimestamp = strtotime($toDatetime);
                    if ($toTimestamp === false) {
                        return false;
                    }

                    if (is_numeric($fromTimestamp) && is_numeric($toTimestamp) && $fromTimestamp > 0 && $toTimestamp > 0) {
                        return $fromTimestamp < $toTimestamp;
                    }
                    return false;
                },
                'message' => __('"From" date should be greater then "to" date.')
            ]);

        $validator
            ->integer('duration')
            ->greaterThan('duration', 0, __('Duration needs to be greater than zero.'), function ($context) {
                if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                    //Only required for recurring downtimes
                    return true;
                }
                return false;
            })
            ->notEmptyString('duration', null, function ($context) {
                if (isset($context['data']['is_recurring']) && $context['data']['is_recurring'] === 1) {
                    //Only required for recurring downtimes
                    return true;
                }
                return false;
            });

        $validator
            ->scalar('comment')
            ->maxLength('comment', 255)
            ->notEmptyString('comment');

        $validator
            ->scalar('author')
            ->maxLength('author', 255)
            ->notEmptyString('author');

        $validator
            ->integer('is_recursive')
            ->inList('is_recursive', [0, 1], __('This field needs to be 0 or 1'))
            ->notEmptyString('is_recursive', null, function ($context) {
                if (isset($context['data']['objecttype_id']) && $context['data']['objecttype_id'] == OBJECT_NODE) {
                    //Only required for container downtimes
                    return false;
                }
                return true;
            });

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
     * @param SystemdowntimesConditions $SystemdowntimesConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getRecurringHostDowntimes(SystemdowntimesConditions $SystemdowntimesConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $SystemdowntimesConditions->getContainerIds();
        $query = $this->find();
        $query->select([
            'Systemdowntimes.id',
            'Systemdowntimes.objecttype_id',
            'Systemdowntimes.object_id',
            'Systemdowntimes.downtimetype_id',
            'Systemdowntimes.weekdays',
            'Systemdowntimes.day_of_month',
            'Systemdowntimes.from_time',
            'Systemdowntimes.to_time',
            'Systemdowntimes.duration',
            'Systemdowntimes.comment',
            'Systemdowntimes.author',
            'Hosts.id',
            'Hosts.name',
            'Hosts.uuid',
            'container_ids' => $query->newExpr('GROUP_CONCAT(DISTINCT HostsToContainersSharing.container_id)')
        ])
            ->innerJoin(['Hosts' => 'hosts'], [
                'Hosts.id = Systemdowntimes.object_id'
            ]);
        $containerJoinConditions = [
            'HostsToContainersSharing.host_id = Hosts.id'
        ];
        if (!empty($MY_RIGHTS)) {
            $containerJoinConditions[] = [
                'HostsToContainersSharing.container_id IN ' => $MY_RIGHTS
            ];
        }

        $query->innerJoin([
            'HostsToContainersSharing' => 'hosts_to_containers'
        ], $containerJoinConditions
        )
            ->andWhere([
                'Systemdowntimes.objecttype_id' => OBJECT_HOST
            ]);
        if ($SystemdowntimesConditions->hasConditions()) {
            $query->andWhere($SystemdowntimesConditions->getConditions());
        }

        $query->groupBy([
            'Systemdowntimes.id'
        ]);

        $query->orderBy(
            array_merge(
                $SystemdowntimesConditions->getOrder(),
                ['Systemdowntimes.id' => 'asc']
            )

        )->disableHydration();

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
     * @param SystemdowntimesConditions $SystemdowntimesConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getRecurringServiceDowntimes(SystemdowntimesConditions $SystemdowntimesConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $SystemdowntimesConditions->getContainerIds();

        $query = $this->find();
        $query->select([
            'Systemdowntimes.id',
            'Systemdowntimes.objecttype_id',
            'Systemdowntimes.object_id',
            'Systemdowntimes.downtimetype_id',
            'Systemdowntimes.weekdays',
            'Systemdowntimes.day_of_month',
            'Systemdowntimes.from_time',
            'Systemdowntimes.to_time',
            'Systemdowntimes.duration',
            'Systemdowntimes.comment',
            'Systemdowntimes.author',
            'Services.id',
            'Services.uuid',
            'container_ids' => $query->newExpr('GROUP_CONCAT(DISTINCT HostsToContainersSharing.container_id)'),
            'servicename'   => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.disabled',
            'Hosts.satellite_id'
        ])
            ->innerJoin(['Services' => 'services'], [
                'Services.id = Systemdowntimes.object_id'
            ])
            ->innerJoin(['Hosts' => 'hosts'], [
                'Hosts.id = Services.host_id'
            ]);
        $containerJoinConditions = [
            'HostsToContainersSharing.host_id = Hosts.id'
        ];
        if (!empty($MY_RIGHTS)) {
            $containerJoinConditions[] = [
                'HostsToContainersSharing.container_id IN ' => $MY_RIGHTS
            ];
        }

        $query->innerJoin([
            'HostsToContainersSharing' => 'hosts_to_containers'
        ], $containerJoinConditions
        );

        $query->innerJoin(['Servicetemplates' => 'servicetemplates'], [
            'Servicetemplates.id = Services.servicetemplate_id'
        ])
            ->andWhere([
                'Systemdowntimes.objecttype_id' => OBJECT_SERVICE
            ]);
        $where = $SystemdowntimesConditions->getConditions();
        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }

        if (!empty($having)) {
            $query->having($having);
        }

        $query->groupBy([
            'Systemdowntimes.id'
        ])
            ->orderBy(
                array_merge(
                    $SystemdowntimesConditions->getOrder(),
                    ['Systemdowntimes.id' => 'asc']
                )

            )->disableHydration();
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
     * @param SystemdowntimesConditions $SystemdowntimesConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getRecurringHostgroupDowntimes(SystemdowntimesConditions $SystemdowntimesConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $SystemdowntimesConditions->getContainerIds();

        $query = $this->find();
        $query->select([
            'Systemdowntimes.id',
            'Systemdowntimes.objecttype_id',
            'Systemdowntimes.object_id',
            'Systemdowntimes.downtimetype_id',
            'Systemdowntimes.weekdays',
            'Systemdowntimes.day_of_month',
            'Systemdowntimes.from_time',
            'Systemdowntimes.to_time',
            'Systemdowntimes.duration',
            'Systemdowntimes.comment',
            'Systemdowntimes.author',
        ])
            ->contain([
                'Hostgroups' => function (Query $query) {
                    $query
                        ->disableAutoFields()
                        ->select([
                            'Hostgroups.id',
                            'Hostgroups.uuid',
                            'Hostgroups.description',
                            'Hostgroups.container_id'
                        ])
                        ->contain([
                            'Containers' => function (Query $query) {
                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Containers.id',
                                        'Containers.containertype_id',
                                        'Containers.name'
                                    ]);
                                return $query;
                            }
                        ]);
                    return $query;
                }
            ])
            ->andWhere([
                'Systemdowntimes.objecttype_id' => OBJECT_HOSTGROUP
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Hostgroups.container_id IN' => $MY_RIGHTS
            ]);
        }

        if ($SystemdowntimesConditions->hasConditions()) {
            $query->andWhere($SystemdowntimesConditions->getConditions());
        }

        $query->groupBy([
            'Systemdowntimes.id'
        ])
            ->orderBy(
                array_merge(
                    $SystemdowntimesConditions->getOrder(),
                    ['Systemdowntimes.id' => 'asc']
                )
            )->disableHydration();

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
     * @param SystemdowntimesConditions $SystemdowntimesConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getRecurringNodeDowntimes(SystemdowntimesConditions $SystemdowntimesConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $SystemdowntimesConditions->getContainerIds();

        $query = $this->find();
        $query->select([
            'Systemdowntimes.id',
            'Systemdowntimes.objecttype_id',
            'Systemdowntimes.object_id',
            'Systemdowntimes.downtimetype_id',
            'Systemdowntimes.weekdays',
            'Systemdowntimes.day_of_month',
            'Systemdowntimes.from_time',
            'Systemdowntimes.to_time',
            'Systemdowntimes.duration',
            'Systemdowntimes.comment',
            'Systemdowntimes.author',
        ])
            ->contain([
                'Containers' => function (Query $query) {
                    $query
                        ->disableAutoFields()
                        ->select([
                            'Containers.id',
                            'Containers.containertype_id',
                            'Containers.name'
                        ]);
                    return $query;
                }
            ])
            ->andWhere([
                'Systemdowntimes.objecttype_id' => OBJECT_NODE
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        if ($SystemdowntimesConditions->hasConditions()) {
            $query->andWhere($SystemdowntimesConditions->getConditions());
        }

        $query->groupBy([
            'Systemdowntimes.id'
        ])
            ->orderBy(
                array_merge(
                    $SystemdowntimesConditions->getOrder(),
                    ['Systemdowntimes.id' => 'asc']
                )
            )->disableHydration();

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
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Systemdowntimes.id' => $id]);
    }

    /**
     * @return array
     */
    public function getRecurringDowntimesForCronjob() {
        $query = $this->find()
            ->all();
        return $query->toArray();
    }

}
