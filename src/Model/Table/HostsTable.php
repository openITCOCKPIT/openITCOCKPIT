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

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Host;
use App\Model\Entity\Hostdependency;
use App\Model\Entity\Hostescalation;
use Cake\Core\Plugin;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\HostConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostFilter;

/**
 * Hosts Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\HosttemplatesTable|\Cake\ORM\Association\BelongsTo $Hosttemplates
 * @property \App\Model\Table\CommandsTable|\Cake\ORM\Association\BelongsTo $Commands
 * @property \App\Model\Table\EventhandlerCommandsTable|\Cake\ORM\Association\BelongsTo $EventhandlerCommands
 * @property \App\Model\Table\TimeperiodsTable|\Cake\ORM\Association\BelongsTo $Timeperiods
 * @property \App\Model\Table\TimeperiodsTable|\Cake\ORM\Association\BelongsTo $CheckPeriods
 * @property \App\Model\Table\TimeperiodsTable|\Cake\ORM\Association\BelongsTo $NotifyPeriods
 * @property \App\Model\Table\SatellitesTable|\Cake\ORM\Association\BelongsTo $Satellites
 * @property \App\Model\Table\ContactgroupsToHostsTable|\Cake\ORM\Association\HasMany $ContactgroupsToHosts
 * @property \App\Model\Table\ContactsToHostsTable|\Cake\ORM\Association\HasMany $ContactsToHosts
 * @property \App\Model\Table\DeletedHostsTable|\Cake\ORM\Association\HasMany $DeletedHosts
 * @property \App\Model\Table\ServicesTable|\Cake\ORM\Association\HasMany $Services
 *
 * @method \App\Model\Entity\Host get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Host newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Host[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Host|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Host|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Host patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Host[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Host findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class HostsTable extends Table {

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;
    use CustomValidationTrait;
    use PluginManagerTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('hosts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('HostsToContainersSharing', [
            'className'        => 'Containers',
            'joinTable'        => 'hosts_to_containers',
            'foreignKey'       => 'host_id',
            'targetForeignKey' => 'container_id',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Contactgroups', [
            'className'        => 'Contactgroups',
            'foreignKey'       => 'host_id',
            'targetForeignKey' => 'contactgroup_id',
            'joinTable'        => 'contactgroups_to_hosts',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Contacts', [
            'className'        => 'Contacts',
            'foreignKey'       => 'host_id',
            'targetForeignKey' => 'contact_id',
            'joinTable'        => 'contacts_to_hosts',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Hostgroups', [
            'className'        => 'Hostgroups',
            'foreignKey'       => 'host_id',
            'targetForeignKey' => 'hostgroup_id',
            'joinTable'        => 'hosts_to_hostgroups',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);


        $this->belongsToMany('Parenthosts', [
            'className'        => 'Hosts',
            'foreignKey'       => 'host_id',
            'targetForeignKey' => 'parenthost_id',
            'joinTable'        => 'hosts_to_parenthosts',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('ChildHosts', [
            'className'        => 'Hosts',
            'foreignKey'       => 'parenthost_id',
            'targetForeignKey' => 'host_id',
            'joinTable'        => 'hosts_to_parenthosts',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsTo('Hosttemplates', [
            'foreignKey' => 'hosttemplate_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('CheckPeriod', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'check_period_id',
            'joinType'   => 'LEFT OUTER'
        ]);

        $this->belongsTo('NotifyPeriod', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'notify_period_id',
            'joinType'   => 'LEFT OUTER'
        ]);

        $this->belongsTo('CheckCommand', [
            'className'  => 'Commands',
            'foreignKey' => 'command_id',
            'joinType'   => 'INNER'
        ]);

        $this->hasMany('Customvariables', [
            'conditions'   => [
                'objecttype_id' => OBJECT_HOST
            ],
            'foreignKey'   => 'object_id',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('Hostcommandargumentvalues', [
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('Services', [
            'foreignKey' => 'host_id',
        ])->setDependent(true);

        $this->hasMany('HostescalationsHostMemberships', [
            'foreignKey' => 'host_id'
        ]);

        $this->hasMany('HostdependenciesHostMemberships', [
            'foreignKey' => 'host_id'
        ]);

        $this->hasOne('Agentconfigs', [
            'foreignKey' => 'host_id',
        ])->setDependent(true);

        $this->belongsToMany('Statuspages', [
            'className'        => 'Statuspages',
            'foreignKey'       => 'host_id',
            'targetForeignKey' => 'statuspage_id',
            'joinTable'        => 'statuspages_to_hosts',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        /*$this->hasOne('Agenthostscache', [
            'foreignKey' => 'hostuuid',
            'bindingKey' => 'uuid'
        ])->setDependent(true);

        $this->hasOne('Agentconnector', [
            'foreignKey' => 'hostuuid',
            'bindingKey' => 'uuid'
        ])->setDependent(true);*/

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
            ->integer('hosttemplate_id')
            ->requirePresence('hosttemplate_id', 'create')
            ->allowEmptyString('hosttemplate_id', null, false);

        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->allowEmptyString('name', null, false);

        $validator
            ->scalar('address')
            ->maxLength('address', 255)
            ->requirePresence('address', 'create')
            ->allowEmptyString('address', null, false);

        $validator
            ->allowEmptyString('description', null, true);

        $validator
            ->integer('priority')
            ->requirePresence('priority', 'create')
            ->range('priority', [1, 5], __('This value must be between 1 and 5'))
            ->allowEmptyString('priority');

        $validator
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->integer('max_check_attempts')
            ->requirePresence('max_check_attempts', 'create')
            ->greaterThanOrEqual('max_check_attempts', 1, __('This value need to be at least 1'))
            ->allowEmptyString('max_check_attempts', null, true);

        $validator
            ->numeric('notification_interval')
            ->requirePresence('notification_interval', 'create')
            ->greaterThanOrEqual('notification_interval', 0, __('This value need to be at least 0'))
            ->allowEmptyString('notification_interval', null, true);

        $validator
            ->integer('check_interval')
            ->requirePresence('check_interval', 'create')
            ->greaterThanOrEqual('check_interval', 1, __('This value need to be at least 1'))
            ->allowEmptyString('check_interval', null, true);

        $validator
            ->integer('retry_interval')
            ->requirePresence('retry_interval', 'create')
            ->greaterThanOrEqual('retry_interval', 1, __('This value need to be at least 1'))
            ->allowEmptyString('retry_interval', null, true);

        $validator
            ->integer('check_period_id')
            ->requirePresence('check_period_id', 'create')
            ->greaterThan('check_period_id', 0, __('Please select a check period'))
            ->allowEmptyString('check_period_id', null, true);

        $validator
            ->integer('command_id')
            ->requirePresence('command_id', 'create')
            ->greaterThan('command_id', 0, __('Please select a check command'))
            ->allowEmptyString('command_id', null, true);

        $validator
            ->integer('notify_period_id')
            ->requirePresence('notify_period_id', 'create')
            ->greaterThan('notify_period_id', 0, __('Please select a notify period'))
            ->allowEmptyString('notify_period_id', null, true);

        $validator
            ->boolean('notify_on_recovery')
            ->requirePresence('notify_on_recovery', 'create')
            ->allowEmptyString('notify_on_recovery', null, true)
            ->add('notify_on_recovery', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_down')
            ->requirePresence('notify_on_down', 'create')
            ->allowEmptyString('notify_on_down', null, true)
            ->add('notify_on_down', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_unreachable')
            ->requirePresence('notify_on_unreachable', 'create')
            ->allowEmptyString('notify_on_unreachable', null, true)
            ->add('notify_on_unreachable', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_flapping')
            ->requirePresence('notify_on_flapping', 'create')
            ->allowEmptyString('notify_on_flapping', null, true)
            ->add('notify_on_flapping', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_downtime')
            ->requirePresence('notify_on_downtime', 'create')
            ->allowEmptyString('notify_on_downtime', null, true)
            ->add('notify_on_downtime', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('flap_detection_enabled')
            ->requirePresence('flap_detection_enabled', 'create')
            ->allowEmptyString('flap_detection_enabled', null, true);

        $validator
            ->allowEmptyString('flap_detection_on_up', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsHost(null, $context);
            })
            ->add('flap_detection_on_up', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->allowEmptyString('flap_detection_on_down', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsHost(null, $context);
            })
            ->add('flap_detection_on_down', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->allowEmptyString('flap_detection_on_unreachable', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsHost(null, $context);
            })
            ->add('flap_detection_on_unreachable', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsHost'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);


        $validator
            ->numeric('low_flap_threshold')
            ->allowEmptyString('low_flap_threshold');

        $validator
            ->numeric('high_flap_threshold')
            ->allowEmptyString('high_flap_threshold');

        $validator
            ->boolean('process_performance_data')
            ->requirePresence('process_performance_data', false)
            ->allowEmptyString('process_performance_data', null, true);

        $validator
            ->boolean('freshness_checks_enabled')
            ->requirePresence('freshness_checks_enabled', false)
            ->allowEmptyString('freshness_checks_enabled', null, true);

        $validator
            ->integer('freshness_threshold')
            ->allowEmptyString('freshness_threshold');

        $validator
            ->boolean('passive_checks_enabled')
            ->allowEmptyString('passive_checks_enabled');

        $validator
            ->boolean('event_handler_enabled')
            ->allowEmptyString('event_handler_enabled');

        $validator
            ->boolean('active_checks_enabled')
            ->requirePresence('active_checks_enabled', 'create')
            ->allowEmptyString('active_checks_enabled', null, true);

        $validator
            ->scalar('notes')
            ->requirePresence('notes', false)
            ->allowEmptyString('notes', null, true)
            ->maxLength('notes', 255);

        $validator
            ->scalar('tags')
            ->requirePresence('tags', false)
            ->allowEmptyString('tags', null, true)
            ->maxLength('tags', 255);

        $validator
            ->scalar('host_url')
            ->requirePresence('host_url', false)
            ->allowEmptyString('host_url', null, true)
            ->maxLength('host_url', 255);


        $validator
            ->allowEmptyString('customvariables', null, true)
            ->add('customvariables', 'custom', [
                'rule'    => [$this, 'checkMacroNames'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('Macro name needs to be unique')
            ]);

        $validator
            ->boolean('own_contacts')
            ->requirePresence('own_contacts', 'create')
            ->allowEmptyString('own_contacts', null, false);

        $validator
            ->boolean('own_contactgroups')
            ->requirePresence('own_contactgroups', 'create')
            ->allowEmptyString('own_contactgroups', null, false);

        $validator
            ->boolean('own_customvariables')
            ->requirePresence('own_customvariables', 'create')
            ->allowEmptyString('own_customvariables', null, false);

        $validator
            ->integer('host_type')
            ->requirePresence('host_type', 'create')
            ->allowEmptyString('host_type', null, false);

        $validator
            ->boolean('disabled')
            ->allowEmptyString('disabled');

        $validator
            ->integer('usage_flag')
            ->requirePresence('usage_flag', 'create')
            ->allowEmptyString('usage_flag', null, false);

        $validator
            ->add('parenthosts', 'custom', [
                'rule'    => function ($value, $context) {
                    if (!isset($context['data']['id'])) {
                        //Not an update (new hosts can't be in an parent hosts loop) so nothing to check
                        return true;
                    }

                    if (empty($value['_ids'])) {
                        //No parent hosts selected - no loop
                        return true;
                    }

                    //Parent host ids of current host
                    return !$this->hasParentLoop2($value['_ids'], $context['data']['id']);
                },
                'message' => __('Parent/child loop detected.')
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
        $rules->add($rules->isUnique(['uuid']));

        return $rules;
    }

    /**
     * @param int $id
     * @return array|Host|null
     */
    public function getHostById($id) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain('HostsToContainersSharing')
            ->first();
        return $query;
    }

    /**
     * @param int $id
     * @return array|\Cake\Datasource\EntityInterface
     * @throws RecordNotFoundException
     */
    public function getHostByIdOrFail($id) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain('HostsToContainersSharing')
            ->firstOrFail();
        return $query;
    }

    /**
     * @param string $uuid
     * @param bool $enableHydration
     * @return array|Host
     */
    public function getHostByUuid($uuid, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Hosts.uuid' => $uuid
            ])
            ->contain('HostsToContainersSharing')
            ->enableHydration($enableHydration)
            ->firstOrFail();
        return $query;
    }

    /**
     * @param string $uuid
     * @param bool $enableHydration
     * @return array|Host
     */
    public function getHostWithServicesByUuid($uuid, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Hosts.uuid' => $uuid
            ])
            ->contain([
                'HostsToContainersSharing',
                'Services'
            ])
            ->enableHydration($enableHydration)
            ->firstOrFail();
        return $query;
    }

    /**
     * @param string $uuid
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array|Host
     */
    public function getHostsWithServicesByIdsForMapeditor($ids, array $MY_RIGHTS = [], $enableHydration = true) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->where([
                'Hosts.id IN'    => $ids,
                'Hosts.disabled' => 0,
            ])
            ->contain([
                'HostsToContainersSharing',
                'Services' => function (Query $q) {
                    return $q->where([
                        'Services.disabled' => 0
                    ]);
                }
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->enableHydration($enableHydration);

        $result = $query->all();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param string $uuid
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array|Host
     */
    public function getHostsWithServicesByIdsForMapsumary($ids, $MY_RIGHTS = [], $enableHydration = true) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->where([
                'Hosts.id IN'    => $ids,
                'Hosts.disabled' => 0,
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing',
            'Services' => function (Query $q) {
                return $q
                    ->join([
                        [
                            'table'      => 'servicetemplates',
                            'type'       => 'INNER',
                            'alias'      => 'Servicetemplates',
                            'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                        ],
                    ])
                    ->select([
                        'Services.id',
                        'Services.name',
                        'Services.uuid',
                        'Services.host_id',
                        'Servicetemplates.name'
                    ])
                    ->where([
                        'Services.disabled' => 0
                    ]);
            }
        ])
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name'
            ])
            ->groupBy([
                'Hosts.id'
            ])
            ->enableHydration($enableHydration);

        $result = $query->all();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param int $id
     * @return array|Host|null
     */
    public function getHostByIdForPermissionCheck($id) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'Hosts.address',
                'Hosts.container_id',
                'Hosts.satellite_id'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain('HostsToContainersSharing')
            ->first();
        return $query;
    }

    /**
     * @param int $id
     * @return array|Host|null
     */
    public function getHostByIdWithHosttemplate($id) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing',
                'Hosttemplates'
            ])
            ->first();
        return $query;
    }

    /**
     * @param int|array $ids
     * @return array
     */
    public function getHostsByIds($ids, $useHydration = true) {
        if (empty($ids)) {
            return [];
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find()
            ->where([
                'Hosts.id IN' => $ids
            ])
            ->contain('HostsToContainersSharing')
            ->enableHydration($useHydration)
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @param int $hosttemplateId
     * @return array
     */
    public function getHostPrimaryContainerIdsByHosttemplateId($hosttemplateId) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.container_id',
                'Hosts.hosttemplate_id'
            ])
            ->where([
                'Hosts.hosttemplate_id' => $hosttemplateId
            ])
            ->disableHydration()
            ->all();

        $query = $query->toArray();

        if (empty($query)) {
            return [];
        }

        $result = [];
        foreach ($query as $row) {
            $result[$row['id']] = (int)$row['container_id'];
        }

        return $result;
    }

    /**
     * @param int $hosttemplateId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsForHosttemplateUsedBy($hosttemplateId, $MY_RIGHTS = [], $includeDisabled = false) {
        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.container_id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.address',
            'Hosts.disabled'
        ]);

        $where = [
            'Hosts.hosttemplate_id' => $hosttemplateId
        ];
        if ($includeDisabled === false) {
            $where['Hosts.disabled'] = 0;
        }

        $query->where($where);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain('HostsToContainersSharing');
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
        ]);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsIndex(HostFilter $HostFilter, HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.active_checks_enabled',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id',
            'Hosts.tags',
            'Hosts.usage_flag',
            'Hosts.priority',
            'Hosts.notes',
            'Hosts.host_type',
            'Hoststatus.current_state',
            'Hoststatus.last_check',
            'Hoststatus.next_check',
            'Hoststatus.last_hard_state_change',
            'Hoststatus.last_state_change',
            'Hoststatus.output',
            'Hoststatus.scheduled_downtime_depth',
            'Hoststatus.active_checks_enabled',
            'Hoststatus.state_type',
            'Hoststatus.is_flapping',
            'Hoststatus.problem_has_been_acknowledged',
            'Hoststatus.acknowledgement_type',
            'Hoststatus.notifications_enabled'
        ]);

        $query->join([
            'a' => [
                'table'      => 'nagios_objects',
                'type'       => 'INNER',
                'alias'      => 'HostObject',
                'conditions' => 'Hosts.uuid = HostObject.name1 AND HostObject.objecttype_id = 1'
            ],
            'b' => [
                'table'      => 'nagios_hoststatus',
                'type'       => 'LEFT OUTER',
                'alias'      => 'Hoststatus',
                'conditions' => 'Hoststatus.host_object_id = HostObject.object_id',
            ]
        ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->contain([
            'HostsToContainersSharing',
            'Hosttemplates' => [
                'fields' => [
                    'Hosttemplates.id',
                    'Hosttemplates.uuid',
                    'Hosttemplates.name',
                    'Hosttemplates.description',
                    'Hosttemplates.notes',
                    'Hosttemplates.active_checks_enabled',
                    'Hosttemplates.tags',
                    'Hosttemplates.priority',
                    'hostpriority'    => $query->newExpr('IF(Hosts.priority IS NULL, Hosttemplates.priority, Hosts.priority)'),
                    'hostdescription' => $query->newExpr('IF(Hosts.description IS NULL, Hosttemplates.description, Hosts.description)')
                ]
            ]
        ]);

        $where = $HostFilter->indexFilter();
        $where['Hosts.disabled'] = (int)$HostConditions->includeDisabled();
        if ($HostConditions->getHostIds()) {
            $hostIds = $HostConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }

            $where['Hosts.id IN'] = $hostIds;
        }

        if (isset($where['Hosts.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Hosts.keywords rlike']);
        }

        if (isset($where['Hosts.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Hosts.not_keywords not rlike']);
        }

        if (isset($where['hostpriority IN'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.priority IS NULL), Hosttemplates.priority, Hosts.priority)',
                $where['hostpriority IN'],
                'integer[]',
                'IN'
            );
            unset($where['hostpriority IN']);
        }

        if (isset($where['hostdescription LIKE'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.description IS NULL OR Hosts.description=""), Hosttemplates.description, Hosts.description)',
                $where['hostdescription LIKE'],
                'string',
                'LIKE'
            );
            unset($where['hostdescription LIKE']);
        }

        if (isset($where['Hoststatus.state_older_than >']) && is_numeric($where['Hoststatus.state_older_than >']) && $where['Hoststatus.state_older_than >'] > 0) {
            $intervalUnit = 'MINUTE';
            if (in_array($where['Hoststatus.state_older_than_unit >'], ['SECOND', 'MINUTE', 'HOUR', 'DAY'], true)) {
                $intervalUnit = $where['Hoststatus.state_older_than_unit >'];
            }
            $query->where([
                sprintf('Hoststatus.last_state_change <= UNIX_TIMESTAMP(DATE(NOW() - INTERVAL %s %s))',
                    $where['Hoststatus.state_older_than >'],
                    $intervalUnit
                )
            ]);

            unset($where['Hoststatus.state_older_than >']);
        }

        unset($where['Hoststatus.state_older_than_unit >']);

        $query->where($where);

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy(
            array_merge(
                $HostFilter->getOrderForPaginator('Hoststatus.current_state', 'desc'),
                ['Hosts.id' => 'asc']
            )
        );

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsIndexStatusengine3(HostFilter $HostFilter, HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();
        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.active_checks_enabled',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id',
            'Hosts.hosttemplate_id',
            'Hosts.tags',
            'Hosts.priority',
            //'keywords'     => 'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
            //'not_keywords' => 'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
            'Hosts.notes',
            'Hosts.host_type',
            'Hoststatus.current_state',
            'Hoststatus.last_check',
            'Hoststatus.next_check',
            'Hoststatus.last_hard_state_change',
            'Hoststatus.last_state_change',
            'Hoststatus.output',
            'Hoststatus.scheduled_downtime_depth',
            'Hoststatus.active_checks_enabled',
            'Hoststatus.is_hardstate',
            'Hoststatus.is_flapping',
            'Hoststatus.problem_has_been_acknowledged',
            'Hoststatus.acknowledgement_type',
            'Hoststatus.notifications_enabled'
        ]);

        $query->join([
            'b' => [
                'table'      => 'statusengine_hoststatus',
                'type'       => 'INNER',
                'alias'      => 'Hoststatus',
                'conditions' => 'Hoststatus.hostname = Hosts.uuid',
            ]
        ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->contain([
            'HostsToContainersSharing',
            'Hosttemplates' => [
                'fields' => [
                    'Hosttemplates.id',
                    'Hosttemplates.uuid',
                    'Hosttemplates.name',
                    'Hosttemplates.description',
                    'Hosttemplates.notes',
                    'Hosttemplates.active_checks_enabled',
                    'Hosttemplates.tags',
                    'Hosttemplates.priority',
                    'hostpriority'    => $query->newExpr('IF(Hosts.priority IS NULL, Hosttemplates.priority, Hosts.priority)'),
                    'hostdescription' => $query->newExpr('IF(Hosts.description IS NULL, Hosttemplates.description, Hosts.description)')
                ]
            ]
        ]);

        $where = $HostFilter->indexFilter();
        $where['Hosts.disabled'] = (int)$HostConditions->includeDisabled();
        if ($HostConditions->getHostIds()) {
            $hostIds = $HostConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }

            $where['Hosts.id IN'] = $hostIds;
        }

        if (isset($where['Hosts.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Hosts.keywords rlike']);
        }
        if (isset($where['Hosts.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Hosts.not_keywords not rlike']);
        }
        if (isset($where['hostpriority IN'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.priority IS NULL), Hosttemplates.priority, Hosts.priority)',
                $where['hostpriority IN'],
                'integer[]',
                'IN'
            );
            unset($where['hostpriority IN']);
        }

        if (isset($where['hostdescription LIKE'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.description IS NULL OR Hosts.description=""), Hosttemplates.description, Hosts.description)',
                $where['hostdescription LIKE'],
                'string',
                'LIKE'
            );
            unset($where['hostdescription LIKE']);
        }

        $query->where($where);
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy(
            array_merge(
                $HostFilter->getOrderForPaginator('Hoststatus.current_state', 'desc'),
                ['Hosts.id' => 'asc']
            )
        );

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsNotMonitored(HostFilter $HostFilter, HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id'
        ]);

        $query->join([
            'a' => [
                'table'      => 'nagios_objects',
                'type'       => 'LEFT OUTER',
                'alias'      => 'HostObject',
                'conditions' => 'Hosts.uuid = HostObject.name1 AND HostObject.objecttype_id = 1'
            ]
        ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->contain([
            'HostsToContainersSharing',
            'Hosttemplates' => [
                'fields' => [
                    'Hosttemplates.id',
                    'Hosttemplates.uuid',
                    'Hosttemplates.name',
                    'Hosttemplates.description',
                    'Hosttemplates.active_checks_enabled',
                ]
            ]

        ]);

        $where = $HostFilter->disabledFilter();
        $where['Hosts.disabled'] = (int)$HostConditions->includeDisabled();
        $where[] = 'HostObject.name1 IS NULL';
        $query->where($where);

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy(
            array_merge(
                $HostFilter->getOrderForPaginator('Hosts.name', 'asc'),
                ['Hosts.id' => 'asc']
            )
        );

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsNotMonitoredStatusengine3(HostFilter $HostFilter, HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id'
        ]);

        $query->join([
            'a' => [
                'table'      => 'statusengine_hoststatus',
                'type'       => 'LEFT OUTER',
                'alias'      => 'Hoststatus',
                'conditions' => 'Hosts.uuid = Hoststatus.hostname'
            ]
        ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->contain([
            'HostsToContainersSharing',
            'Hosttemplates' => [
                'fields' => [
                    'Hosttemplates.id',
                    'Hosttemplates.uuid',
                    'Hosttemplates.name',
                    'Hosttemplates.description',
                    'Hosttemplates.active_checks_enabled',
                ]
            ]

        ]);

        $where = $HostFilter->disabledFilter();
        $where['Hosts.disabled'] = (int)$HostConditions->includeDisabled();
        $where[] = 'Hoststatus.hostname IS NULL';
        $query->where($where);

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy(
            array_merge(
                $HostFilter->getOrderForPaginator('Hosts.name', 'asc'),
                ['Hosts.id' => 'asc']
            )
        );

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsDisabled(HostFilter $HostFilter, HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id'
        ]);

        $where = $HostFilter->disabledFilter();
        $where['Hosts.disabled'] = (int)$HostConditions->includeDisabled();

        $query->where($where);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing',
            'Hosttemplates' => [
                'fields' => [
                    'Hosttemplates.id',
                    'Hosttemplates.name'
                ]
            ]

        ]);
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy(
            array_merge(
                $HostFilter->getOrderForPaginator('Hosts.name', 'asc'),
                ['Hosts.id' => 'asc']
            )
        );

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsByHostConditions(HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.active_checks_enabled',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id',
            'Hosts.tags',
            'Hosts.disabled'
        ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ]);

        $where = $HostConditions->getWhereForFind();

        if ($HostConditions->getHostIds()) {
            $hostIds = $HostConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }

            $where['Hosts.id IN'] = $hostIds;
        }

        if (isset($where['Hosts.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Hosts.keywords rlike']);
        }

        if (isset($where['Hosts.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Hosts.not_keywords not rlike']);
        }


        $query->where($where);

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        if (!empty($HostConditions->getOrder())) {
            $query->orderBy(
                array_merge(
                    $HostConditions->getOrder(),
                    ['Hosts.id' => 'asc']
                )
            );
        }

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getHostsByHostConditionsWithServices(HostConditions $HostConditions, $PaginateOMat = null) {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.active_checks_enabled',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id',
            'Hosts.tags',
        ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ]);

        $where = $HostConditions->getWhereForFind();

        if ($HostConditions->getHostIds()) {
            $hostIds = $HostConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }

            $where['Hosts.id IN'] = $hostIds;
        }

        if (isset($where['Hosts.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Hosts.keywords rlike']);
        }

        if (isset($where['Hosts.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Hosts.not_keywords not rlike']);
        }


        $query->where($where);

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
        ]);

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param string $index
     * @param array $where
     * @return array
     */
    public function getHostsByContainerId($containerIds = [], $type = 'all', $index = 'id', $where = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $containerIds = array_unique($containerIds);

        $_where = [
            'Hosts.disabled IN' => [0]
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Hosts.' . $index,
            'Hosts.name',
            'Hosts.container_id'
        ]);

        $query->where($where);
        if (!empty($containerIds)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $containerIds
            ]);
        }
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
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
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }

    /**
     * @param array $containerIds
     * @return array
     */
    public function getHostsByContainerIdForDelete($containerIds = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $containerIds = array_unique($containerIds);

        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.name'
            ])->where([
                'Hosts.container_id IN' => $containerIds
            ])->disableHydration();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * This method will lookup the name of objects based on the given ID.
     *
     * Objects like ObjectsCache are passed by reference in php which is very good for performance
     * The Cache is optional (to be backwards compatible) and should be used, whenever this method get's called in a look
     * A good example for Caching is the Import Module for example
     *
     * @param array $dataToParse
     * @param ObjectsCache|null $Cache
     * @return array|array[]
     */
    public function resolveDataForChangelog(array $dataToParse, ?ObjectsCache $Cache = null) {
        $extDataForChangelog = [
            'Contact'      => [],
            'Contactgroup' => [],
            'CheckPeriod'  => [],
            'NotifyPeriod' => [],
            'CheckCommand' => [],
            'Hostgroup'    => [],
            'Hosttemplate' => [],
            'Parenthost'   => []
        ];

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');


        if (!empty($dataToParse['Host']['contacts']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($ContactsTable->getContactsAsList($dataToParse['Host']['contacts']['_ids']) as $contactId => $contactName) {
                    $extDataForChangelog['Contact'][] = [
                        'id'   => $contactId,
                        'name' => $contactName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Host']['contacts']['_ids'] as $contactId) {
                    if (!$Cache->has(OBJECT_CONTACT, $contactId)) {
                        foreach ($ContactsTable->getContactsAsList($contactId) as $contactName) {
                            $Cache->set(OBJECT_CONTACT, $contactId, [
                                'id'   => $contactId,
                                'name' => $contactName
                            ]);
                        }
                    }
                    $extDataForChangelog['Contact'][] = $Cache->get(OBJECT_CONTACT, $contactId);
                }
            }
        }

        if (!empty($dataToParse['Host']['contactgroups']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($ContactgroupsTable->getContactgroupsAsList($dataToParse['Host']['contactgroups']['_ids']) as $contactgroupId => $contactgroupName) {
                    $extDataForChangelog['Contactgroup'][] = [
                        'id'   => $contactgroupId,
                        'name' => $contactgroupName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Host']['contactgroups']['_ids'] as $contactgroupId) {
                    if (!$Cache->has(OBJECT_CONTACTGROUP, $contactgroupId)) {
                        foreach ($ContactgroupsTable->getContactgroupsAsList($contactgroupId) as $contactgroupName) {
                            $Cache->set(OBJECT_CONTACTGROUP, $contactgroupId, [
                                'id'   => $contactgroupId,
                                'name' => $contactgroupName
                            ]);
                        }
                    }
                    $extDataForChangelog['Contactgroup'][] = $Cache->get(OBJECT_CONTACTGROUP, $contactgroupId);
                }
            }
        }

        if (!empty($dataToParse['Host']['check_period_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Host']['check_period_id']) as $timeperiodId => $timeperiodName) {
                    $extDataForChangelog['CheckPeriod'] = [
                        'id'   => $timeperiodId,
                        'name' => $timeperiodName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_TIMEPERIOD, $dataToParse['Host']['check_period_id'])) {
                    foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Host']['check_period_id']) as $timeperiodId => $timeperiodName) {
                        $Cache->set(OBJECT_TIMEPERIOD, $dataToParse['Host']['check_period_id'], [
                            'id'   => $timeperiodId,
                            'name' => $timeperiodName
                        ]);
                    }
                }
                $extDataForChangelog['CheckPeriod'] = $Cache->get(OBJECT_TIMEPERIOD, $dataToParse['Host']['check_period_id']);
            }
        }

        if (!empty($dataToParse['Host']['notify_period_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Host']['notify_period_id']) as $timeperiodId => $timeperiodName) {
                    $extDataForChangelog['NotifyPeriod'] = [
                        'id'   => $timeperiodId,
                        'name' => $timeperiodName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_TIMEPERIOD, $dataToParse['Host']['notify_period_id'])) {
                    foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Host']['notify_period_id']) as $timeperiodId => $timeperiodName) {
                        $Cache->set(OBJECT_TIMEPERIOD, $dataToParse['Host']['notify_period_id'], [
                            'id'   => $timeperiodId,
                            'name' => $timeperiodName
                        ]);
                    }
                }
                $extDataForChangelog['NotifyPeriod'] = $Cache->get(OBJECT_TIMEPERIOD, $dataToParse['Host']['notify_period_id']);
            }
        }

        if (!empty($dataToParse['Host']['command_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($CommandsTable->getCommandByIdAsList($dataToParse['Host']['command_id']) as $commandId => $commandName) {
                    $extDataForChangelog['CheckCommand'] = [
                        'id'   => $commandId,
                        'name' => $commandName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_COMMAND, $dataToParse['Host']['command_id'])) {
                    foreach ($CommandsTable->getCommandByIdAsList($dataToParse['Host']['command_id']) as $commandId => $commandName) {
                        $Cache->set(OBJECT_COMMAND, $dataToParse['Host']['command_id'], [
                            'id'   => $commandId,
                            'name' => $commandName
                        ]);
                    }
                }
                $extDataForChangelog['CheckCommand'] = $Cache->get(OBJECT_COMMAND, $dataToParse['Host']['command_id']);
            }
        }

        if (!empty($dataToParse['Host']['hostgroups']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($HostgroupsTable->getHostgroupsAsList($dataToParse['Host']['hostgroups']['_ids']) as $hostgroupId => $hostgroupName) {
                    $extDataForChangelog['Hostgroup'][] = [
                        'id'   => $hostgroupId,
                        'name' => $hostgroupName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Host']['hostgroups']['_ids'] as $hostgroupId) {
                    if (!$Cache->has(OBJECT_HOSTGROUP, $hostgroupId)) {
                        foreach ($HostgroupsTable->getHostgroupsAsList($hostgroupId) as $hostgroupName) {
                            $Cache->set(OBJECT_HOSTGROUP, $hostgroupId, [
                                'id'   => $hostgroupId,
                                'name' => $hostgroupName
                            ]);
                        }
                    }
                    $extDataForChangelog['Hostgroup'][] = $Cache->get(OBJECT_HOSTGROUP, $hostgroupId);
                }
            }
        }

        if (!empty($dataToParse['Host']['parenthosts']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($this->getHostsAsList($dataToParse['Host']['parenthosts']['_ids']) as $parentHostId => $parentHostName) {
                    $extDataForChangelog['Parenthost'][] = [
                        'id'   => $parentHostId,
                        'name' => $parentHostName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Host']['parenthosts']['_ids'] as $parentHostId) {
                    if (!$Cache->has(OBJECT_HOST, $parentHostId)) {
                        foreach ($this->getHostsAsList($parentHostId) as $parentHostName) {
                            $Cache->set(OBJECT_HOST, $parentHostId, [
                                'id'   => $parentHostId,
                                'name' => $parentHostName
                            ]);
                        }
                    }
                    $extDataForChangelog['Parenthost'][] = $Cache->get(OBJECT_HOST, $parentHostId);
                }
            }
        }

        if (!empty($dataToParse['Host']['hosttemplate_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($HosttemplatesTable->getHosttemplatesAsList($dataToParse['Host']['hosttemplate_id']) as $hosttemplateId => $hosttemplateName) {
                    $extDataForChangelog['Hosttemplate'] = [
                        'id'   => $hosttemplateId,
                        'name' => $hosttemplateName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_HOSTTEMPLATE, $dataToParse['Host']['hosttemplate_id'])) {
                    foreach ($HosttemplatesTable->getHosttemplatesAsList($dataToParse['Host']['hosttemplate_id']) as $hosttemplateId => $hosttemplateName) {
                        $Cache->set(OBJECT_HOSTTEMPLATE, $dataToParse['Host']['hosttemplate_id'], [
                            'id'   => $hosttemplateId,
                            'name' => $hosttemplateName
                        ]);
                    }
                }
                $extDataForChangelog['Hosttemplate'] = $Cache->get(OBJECT_HOSTTEMPLATE, $dataToParse['Host']['hosttemplate_id']);
            }
        }

        return $extDataForChangelog;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getHostsAsList($ids = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.name'
            ])
            ->disableHydration();
        if (!empty($ids)) {
            $query->where([
                'Hosts.id IN' => $ids
            ]);
        }

        return $this->formatListAsCake2($query->toArray());
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHostForEdit($id) {
        $contain = [
            'Contactgroups',
            'Contacts',
            'Hostgroups',
            'Customvariables',
            'Parenthosts',
            'HostsToContainersSharing',
            'Hostcommandargumentvalues' => [
                'Commandarguments'
            ]
        ];

        if (Plugin::isLoaded('PrometheusModule')) {
            $contain[] = 'PrometheusExporters';
        };

        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain($contain)
            ->disableHydration()
            ->first();

        $host = $query;
        $host['hostgroups'] = [
            '_ids' => Hash::extract($query, 'hostgroups.{n}.id')
        ];
        $host['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];
        $host['contactgroups'] = [
            '_ids' => Hash::extract($query, 'contactgroups.{n}.id')
        ];
        $host['parenthosts'] = [
            '_ids' => Hash::extract($query, 'parenthosts.{n}.id')
        ];
        $host['hosts_to_containers_sharing'] = [
            '_ids' => Hash::extract($query, 'hosts_to_containers_sharing.{n}.id')
        ];
        $host['prometheus_exporters'] = [
            '_ids' => Hash::extract($query, 'prometheus_exporters.{n}.id')
        ];

        return [
            'Host' => $host
        ];
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHostForServiceEdit($id) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing',
            ])
            ->disableHydration()
            ->first();

        $host = $query;
        $host['hosts_to_containers_sharing'] = [
            '_ids' => Hash::extract($query, 'hosts_to_containers_sharing.{n}.id')
        ];

        return [
            'Host' => $host
        ];
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getHostForBrowser($id) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'Contactgroups'             => [
                    'Containers'
                ],
                'Contacts'                  => [
                    'Containers'
                ],
                'Hostgroups',
                'Customvariables',
                'Parenthosts',
                'HostsToContainersSharing',
                'Hostcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->disableHydration()
            ->first();

        return $query;
    }

    /**
     * @param HostConditions $HostConditions
     * @param int|array $selected
     * @param bool $returnEmptyArrayIfMyRightsIsEmpty
     * @return array|null
     */
    public function getHostsForAngular(HostConditions $HostConditions, $selected = [], $returnEmptyArrayIfMyRightsIsEmpty = false) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }

        $query = $this->find('list');
        $MY_RIGHTS = $HostConditions->getContainerIds();

        if ($returnEmptyArrayIfMyRightsIsEmpty === true) {
            if (empty($MY_RIGHTS)) {
                //User has no permissions to edit hosts/services
                return [];
            }
        }

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ]);
        $where = $HostConditions->getWhereForFind();
        if (is_array($selected)) {
            $selected = array_filter($selected);
        }
        if (!empty($selected)) {
            $where['NOT'] = [
                'Hosts.id IN' => $selected
            ];
        }

        if ($HostConditions->hasNotConditions()) {
            if (!empty($where['NOT'])) {
                $where['NOT'] = array_merge($where['NOT'], $HostConditions->getNotConditions());
            } else {
                if (!empty($HostConditions->getNotConditions())) {
                    $where['NOT'] = $HostConditions->getNotConditions();
                }
            }
        }

        if (!empty($where['NOT'])) {
            // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
            $where['NOT'] = [
                'OR' => $where['NOT']
            ];
        }
        if (!empty($where)) {
            $query->where($where);
        }
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
        ]);
        $query->limit(ITN_AJAX_LIMIT);

        $hostsWithLimit = $query->toArray();

        $selectedHosts = [];
        if (!empty($selected)) {
            $query = $this->find('list');
            $MY_RIGHTS = $HostConditions->getContainerIds();
            if (!empty($MY_RIGHTS)) {
                $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                    'HostsToContainersSharing.host_id = Hosts.id'
                ]);
                $query->where([
                    'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                ]);
            }
            $query->contain([
                'HostsToContainersSharing'
            ]);
            $where = [
                'Hosts.id IN' => $selected
            ];
            if ($HostConditions->includeDisabled() === false) {
                $where['Hosts.disabled'] = 0;
            }
            $satelliteId = $HostConditions->getSatelliteId();
            if ($satelliteId !== null) {
                $where['Hosts.satellite_id'] = $satelliteId;
            }
            if ($HostConditions->hasNotConditions()) {
                if (!empty($where['NOT'])) {
                    $where['NOT'] = array_merge($where['NOT'], $HostConditions->getNotConditions());
                } else {
                    $where['NOT'] = $HostConditions->getNotConditions();
                }
            }

            if (!empty($where['NOT'])) {
                // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
                $where['NOT'] = [
                    'OR' => $where['NOT']
                ];
            }

            if (!empty($where)) {
                $query->where($where);
            }
            $query->groupBy(['Hosts.id']);
            $query->orderBy([
                'Hosts.name' => 'asc',
                'Hosts.id'   => 'asc'
            ]);

            $selectedHosts = $query->toArray();

        }

        $hosts = $hostsWithLimit + $selectedHosts;
        asort($hosts, SORT_FLAG_CASE | SORT_NATURAL);
        return $hosts;
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     * @param null|string $uuid
     * @return array|Query
     */
    public function getHostsForExport($limit = null, $offset = null, $uuid = null) {
        $where = [
            'Hosts.disabled' => 0
        ];
        if ($uuid !== null) {
            $where['Hosts.uuid'] = $uuid;
        }

        $query = $this->find()
            ->where($where)
            ->contain([
                'Hosttemplates'             =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid', 'check_interval', 'command_id']);
                    },
                'Contactgroups'             =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid']);
                    },
                'Contacts'                  =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid']);
                    },
                'Hostgroups'                =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid']);
                    },
                'Customvariables',
                'Parenthosts'               =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid', 'disabled', 'satellite_id']);
                    },
                'Hostcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ]);

        if ($limit !== null) {
            $query->limit($limit);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }

        $query->all();
        return $query;
    }

    /**
     * @return Query
     */
    public function getHostsForServiceExport() {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.satellite_id',
            ])
            ->where([
                'Hosts.disabled' => 0
            ])
            ->all();

        return $query;
    }

    /**
     * @return int|null
     */
    public function getHostsCountForExport() {
        $query = $this->find()
            ->where([
                'Hosts.disabled' => 0
            ])
            ->count();

        return $query;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHostSharing($id) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'Hosts.container_id',
                'Hosts.host_type'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing',
            ])
            ->disableHydration()
            ->first();

        $host = $query;
        $host['hosts_to_containers_sharing'] = [
            '_ids' => Hash::extract($query, 'hosts_to_containers_sharing.{n}.id')
        ];

        return [
            'Host' => $host
        ];
    }

    /**
     * @param int $id
     * @return string
     */
    public function getHostUuidById($id) {
        $query = $this->find()
            ->select([
                'Hosts.uuid',
            ])
            ->where([
                'Hosts.id' => $id
            ]);

        $host = $query->firstOrFail();

        return $host->get('uuid');
    }

    /**
     * @param $uuid
     * @return int
     */
    public function getHostIdByUuid($uuid) {
        $query = $this->find()
            ->select([
                'Hosts.id',
            ])
            ->where([
                'Hosts.uuid' => $uuid
            ]);

        $host = $query->firstOrFail();

        return $host->get('id');
    }

    /**
     * @param int $hostId
     * @return int
     */
    public function getHostPrimaryContainerIdByHostId($hostId) {
        $host = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.container_id',
            ])
            ->where([
                'Hosts.id' => $hostId
            ])
            ->firstOrFail();

        return $host->get('container_id');
    }

    /**
     * @param int $hostId
     * @return array
     */
    public function getHostContainerIdsByHostId($hostId) {
        /** @var Host $host */
        $host = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.container_id'
            ])
            ->contain([
                'HostsToContainersSharing',
            ])
            ->where([
                'Hosts.id' => $hostId
            ])
            ->firstOrFail();

        return $host->getContainerIds();
    }

    /**
     * @param int $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getContactsAndContactgroupsById($id) {
        $query = $this->find()
            ->select([
                'Hosts.id'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'Contactgroups',
                'Contacts'
            ])
            ->disableHydration()
            ->firstOrFail();

        $host = $query;
        $host['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];
        $host['contactgroups'] = [
            '_ids' => Hash::extract($query, 'contactgroups.{n}.id')
        ];

        return $host;
    }

    /**
     * @param int $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getContactsAndContactgroupsByIdForServiceBrowser($id) {
        $query = $this->find()
            ->select([
                'Hosts.id'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'Contactgroups' => [
                    'Containers'
                ],
                'Contacts'      => [
                    'Containers'
                ]
            ])
            ->disableHydration()
            ->firstOrFail();

        $host = $query;

        return $host;
    }

    /**
     * @param int $hostId
     * @return array
     */
    public function getServicesForServicetemplateAllocation($hostId) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $hostId
            ])
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'Hosts.address'
            ])
            ->contain([
                'Services' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Services.id',
                            'Services.name',
                            'Services.host_id',
                            'Services.servicetemplate_id',
                            'Services.disabled',
                            'Services.service_type'
                        ])
                        ->contain([
                            'Servicetemplates' => function (Query $query) {
                                $query->disableAutoFields()
                                    ->select([
                                        'Servicetemplates.id',
                                        'Servicetemplates.name'
                                    ]);
                                return $query;
                            }
                        ]);
                    return $query;
                }
            ])
            ->disableHydration()
            ->first();

        $result = $query;
        $result['services'] = [];

        //Use servicetemplate id as array key
        foreach ($query['services'] as $service) {
            $result['services'][$service['servicetemplate_id']] = $service;
        }

        return $result;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Hosts.id' => $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsByIdAndType($id, $type) {
        return $this->exists([
            'Hosts.id'        => $id,
            'Hosts.host_type' => $type
        ]);
    }

    /**
     * @param int $commandId
     * @return bool
     */
    public function isCommandUsedByHost($commandId) {
        $count = $this->find()
            ->where([
                'Hosts.command_id' => $commandId,
            ])->count();

        if ($count > 0) {
            return true;
        }

        $count = $this->find()
            ->where([
                'Hosts.eventhandler_command_id' => $commandId,
            ])->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param int $commandId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getHostsByCommandId($commandId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.name',
                'Hosts.uuid'
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing',
        ]);

        $query->andWhere([
            'OR' => [
                ['Hosts.command_id' => $commandId],
                ['Hosts.eventhandler_command_id' => $commandId]
            ]
        ])
            ->orderBy([
                'Hosts.name' => 'asc',
                'Hosts.id'   => 'asc'
            ])
            ->enableHydration($enableHydration)
            ->groupBy(['Hosts.id'])
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int $contactId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getHostsByContactId($contactId, $MY_RIGHTS = [], $enableHydration = true) {

        /** @var ContactsToHostsTable $ContactsToHostsTable */
        $ContactsToHostsTable = TableRegistry::getTableLocator()->get('ContactsToHosts');

        $query = $ContactsToHostsTable->find()
            ->select([
                'host_id'
            ])
            ->where([
                'contact_id' => $contactId
            ])
            ->groupBy([
                'host_id'
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $hostIds = Hash::extract($result, '{n}.host_id');

        $query = $this->find('all');
        $where = [
            'Hosts.id IN' => $hostIds
        ];

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ]);

        $query->where($where);
        $query->enableHydration($enableHydration);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
        ]);
        $query->groupBy([
            'Hosts.id'
        ]);

        $result = $query->all();

        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @return array
     */
    public function getHostsThatUseOitcAgentInPullModeForExport() {
        $query = $this->find()
            ->disableHydration()
            ->select([
                'Hosts.id',
                'Hosts.name',
                'Hosts.uuid',
                'Hosts.address',
                'Agentconfigs.id',
                'Agentconfigs.host_id',
                'Agentconfigs.use_push_mode',
            ])
            ->contain([
                'Services' => function (Query $q) {
                    $q
                        ->select([
                            'Services.id',
                            'Services.uuid',
                            'Services.host_id',
                            'Services.servicetemplate_id',
                            'Services.service_type'
                        ])
                        ->where([
                            'Services.service_type' => OITC_AGENT_SERVICE
                        ]);
                    return $q;
                }
            ])
            ->innerJoinWith('Agentconfigs')
            ->where([
                'Agentconfigs.use_push_mode' => 0
            ])
            ->groupBy([
                'Hosts.id'
            ]);
        $query->all();

        $rawHosts = $query->toArray();
        if ($rawHosts === null) {
            return [];
        }

        $hosts = [];
        foreach ($rawHosts as $host) {
            $hosts[$host['id']] = $host;
        }

        return $hosts;
    }

    /**
     * @param int $satelliteId
     * @return array
     */
    public function getHostsThatUseOitcAgentForExport(int $satelliteId = 0) {
        $query = $this->find()
            ->disableHydration()
            ->select([
                'Hosts.id',
                'Hosts.name',
                'Hosts.uuid',
                'Hosts.address',
                'Agentconfigs.id',
                'Agentconfigs.host_id',
                'Agentconfigs.config',
            ])
            ->innerJoinWith('Agentconfigs')
            ->where([
                'Hosts.satellite_id' => $satelliteId
            ]);
        $query->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param bool $includeDisabled
     * @return int|null
     */
    public function getHostsCountForStats($includeDisabled = true) {
        $query = $this->find();
        if ($includeDisabled === false) {
            $query->where([
                'Hosts.disabled' => 0
            ]);
        }

        return $query->count();
    }

    /**
     * @param int $id
     * @return array|Host|null
     */
    public function getHostByIdForServiceBrowser($id) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'Hosts.address',
                'Hosts.container_id',
                'Hosts.satellite_id'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing',
                'Contacts',
                'Contactgroups'
            ])
            ->first();
        return $query;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @param null|PaginateOMat $PaginateOMat
     * @param string $type (all or count, list is NOT supported!)
     * @return int|array
     */
    public function getHostsByRegularExpression(HostFilter $HostFilter, HostConditions $HostConditions, $PaginateOMat = null, $type = 'all') {
        $MY_RIGHTS = $HostConditions->getContainerIds();

        $query = $this->find('all');
        $query->select([
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'Hosts.description',
            'Hosts.address',
            'Hosts.satellite_id',
            'Hosts.container_id'
        ]);

        $where = [
            'Hosts.disabled'    => (int)$HostConditions->includeDisabled(),
            'Hosts.name REGEXP' => $HostConditions->getHostnameRegex()
        ];

        $query->where($where);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing',
            'Hosttemplates' => [
                'fields' => [
                    'Hosttemplates.id',
                    'Hosttemplates.name'
                ]
            ]

        ]);
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        if ($type === 'all') {
            $query->orderBy(
                array_merge(
                    $HostFilter->getOrderForPaginator('Hosts.name', 'asc'),
                    ['Hosts.id' => 'asc']
                )
            );
        }
        if (!empty($HostConditions->getHostIds())) {
            $query->where(['Hosts.id IN' => $HostConditions->getHostIds()]);
        }
        if ($type === 'count') {
            $count = $query->count();
            return $count;
        }

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }
        return $result;
    }

    /**
     * @param array $MY_RIGHTS
     * @param bool $includeOkState
     * @return array
     */
    public function getHoststatusCount($MY_RIGHTS, $includeOkState = false) {
        $hoststatusCount = [
            '1' => 0,
            '2' => 0,
        ];
        if ($includeOkState === true) {
            $hoststatusCount['0'] = 0;
        }

        $query = $this->find();
        $query
            ->select([
                'Hoststatus.current_state',
                'Hosts.id',
                'count' => $query->newExpr('COUNT(DISTINCT Hoststatus.host_object_id)'),
            ])
            ->where([
                'Hosts.disabled'       => 0,
                'HostObject.is_active' => 1,
            ])
            ->join([
                'a' => [
                    'table'      => 'nagios_objects',
                    'type'       => 'INNER',
                    'alias'      => 'HostObject',
                    'conditions' => 'Hosts.uuid = HostObject.name1 AND HostObject.objecttype_id = 1'
                ],
                'b' => [
                    'table'      => 'nagios_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.host_object_id = HostObject.object_id',
                ]
            ]);

        if (!empty($MY_RIGHTS)) {
            $query
                ->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                    'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                ]);
        }

        $query
            ->contain([
                'HostsToContainersSharing'
            ])
            ->groupBy([
                'Hoststatus.current_state',
            ])
            ->disableHydration();

        if ($includeOkState === false) {
            $query->andWhere([
                'Hoststatus.current_state >' => 0
            ]);
        }


        $hoststatusCountResult = $query->all();

        foreach ($hoststatusCountResult as $hoststatus) {
            $hoststatusCount[$hoststatus['Hoststatus']['current_state']] = (int)$hoststatus['count'];
        }
        return $hoststatusCount;
    }

    /**
     * @param array $MY_RIGHTS
     * @param bool $includeOkState
     * @return array
     */
    public function getServicestatusCount($MY_RIGHTS, $includeOkState = false) {
        $servicestatusCount = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
        ];
        if ($includeOkState === true) {
            $servicestatusCount['0'] = 0;
        }

        $query = $this->find();
        $query
            ->select([
                'Servicestatus.current_state',
                'Hosts.id',
                'count' => $query->newExpr('COUNT(DISTINCT Servicestatus.service_object_id)'),
            ])
            ->where([
                'Services.disabled'       => 0,
                'ServiceObject.is_active' => 1,
            ])
            ->join([
                'a' => [
                    'table'      => 'services',
                    'type'       => 'INNER',
                    'alias'      => 'Services',
                    'conditions' => 'Services.host_id = Hosts.id',
                ],
                'b' => [
                    'table'      => 'nagios_objects',
                    'type'       => 'INNER',
                    'alias'      => 'ServiceObject',
                    'conditions' => 'ServiceObject.name2 = Services.uuid',
                ],
                'c' => [
                    'table'      => 'nagios_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_object_id = ServiceObject.object_id',
                ],
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ])
            ->groupBy([
                'Servicestatus.current_state',
            ])
            ->disableHydration();

        if ($includeOkState === false) {
            $query->andWhere([
                'Servicestatus.current_state >' => 0
            ]);
        }

        $servicestatusCountResult = $query->all();

        foreach ($servicestatusCountResult as $servicestatus) {
            $servicestatusCount[$servicestatus['Servicestatus']['current_state']] = (int)$servicestatus['count'];
        }
        return $servicestatusCount;
    }

    /**
     * @param array $MY_RIGHTS
     * @param bool $includeOkState
     * @return array
     */
    public function getHoststatusCountStatusengine3($MY_RIGHTS, $includeOkState = false) {
        $hoststatusCount = [
            '1' => 0,
            '2' => 0,
        ];
        if ($includeOkState === true) {
            $hoststatusCount['0'] = 0;
        }

        $query = $this->find();
        $query
            ->select([
                'Hoststatus.current_state',
                'Hosts.id',
                'count' => $query->newExpr('COUNT(DISTINCT Hoststatus.hostname)'),
            ])
            ->where([
                'Hosts.disabled' => 0
            ])
            ->join([
                'b' => [
                    'table'      => 'statusengine_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.hostname = Hosts.uuid',
                ]
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
            ->contain([
                'HostsToContainersSharing'
            ])
            ->groupBy([
                'Hoststatus.current_state',
            ])
            ->disableHydration();

        if ($includeOkState === false) {
            $query->andWhere([
                'Hoststatus.current_state >' => 0
            ]);
        }


        $hoststatusCountResult = $query->all();

        foreach ($hoststatusCountResult as $hoststatus) {
            $hoststatusCount[$hoststatus['Hoststatus']['current_state']] = (int)$hoststatus['count'];
        }
        return $hoststatusCount;
    }

    /**
     * @param array $MY_RIGHTS
     * @param bool $includeOkState
     * @return array
     */
    public function getServicestatusCountStatusengine3($MY_RIGHTS, $includeOkState = false) {
        $servicestatusCount = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
        ];
        if ($includeOkState === true) {
            $servicestatusCount['0'] = 0;
        }

        $query = $this->find();
        $query
            ->select([
                'Servicestatus.current_state',
                'Hosts.id',
                'count' => $query->newExpr('COUNT(DISTINCT Servicestatus.service_description)'),
            ])
            ->where([
                'Services.disabled' => 0
            ])
            ->join([
                'a' => [
                    'table'      => 'services',
                    'type'       => 'INNER',
                    'alias'      => 'Services',
                    'conditions' => 'Services.host_id = Hosts.id',
                ],
                'c' => [
                    'table'      => 'statusengine_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_description = Services.uuid',
                ],
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ])
            ->groupBy([
                'Servicestatus.current_state',
            ])
            ->disableHydration();

        if ($includeOkState === false) {
            $query->andWhere([
                'Servicestatus.current_state >' => 0
            ]);
        }

        $servicestatusCountResult = $query->all();

        foreach ($servicestatusCountResult as $servicestatus) {
            $servicestatusCount[$servicestatus['Servicestatus']['current_state']] = (int)$servicestatus['count'];
        }
        return $servicestatusCount;
    }

    /**
     * @param array $MY_RIGHTS
     * @param array $conditions
     * @return int
     */
    public function getHoststatusCountBySelectedStatus($MY_RIGHTS, $conditions) {

        $query = $this->find();
        $query
            ->select([
                'count' => $query->newExpr('COUNT(DISTINCT Hoststatus.host_object_id)'),
                'Hosts.id'
            ])
            ->where([
                'HostObject.is_active' => 1,
                'Hosts.disabled'       => 0
            ])
            ->join([
                'a' => [
                    'table'      => 'nagios_objects',
                    'type'       => 'INNER',
                    'alias'      => 'HostObject',
                    'conditions' => 'Hosts.uuid = HostObject.name1 AND HostObject.objecttype_id = 1'
                ],
                'b' => [
                    'table'      => 'nagios_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.host_object_id = HostObject.object_id',
                ]
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ])
            ->disableHydration();

        $where = [];
        if (!empty($conditions['Host']['name'])) {
            $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
        }

        $where['Hoststatus.current_state'] = $conditions['Hoststatus']['current_state'];

        if ($where['Hoststatus.current_state'] > 0) {
            if ($conditions['Hoststatus']['acknowledged'] ^ $conditions['Hoststatus']['not_acknowledged']) {
                $hasBeenAcknowledged = (int)($conditions['Hoststatus']['acknowledged'] === true);
                $where['Hoststatus.problem_has_been_acknowledged'] = $hasBeenAcknowledged;
            }

            if ($conditions['Hoststatus']['in_downtime'] ^ $conditions['Hoststatus']['not_in_downtime']) {
                $inDowntime = $conditions['Hoststatus']['in_downtime'] === true;
                if ($inDowntime === false) {
                    $where['Hoststatus.scheduled_downtime_depth'] = 0;
                } else {
                    $where['Hoststatus.scheduled_downtime_depth > '] = 0;
                }
            }
        };

        $query->andWhere($where);
        $result = $query->first();

        if ($result === null) {
            return 0;
        }

        return $result['count'];
    }

    /**
     * @param array $MY_RIGHTS
     * @param array $conditions
     * @return int
     */
    public function getHoststatusCountBySelectedStatusStatusengine3($MY_RIGHTS, $conditions) {

        $query = $this->find();
        $query
            ->select([
                'count' => $query->newExpr('COUNT(DISTINCT Hoststatus.hostname)'),
                'Hosts.id'
            ])
            ->where([
                'Hosts.disabled' => 0
            ])
            ->join([
                'b' => [
                    'table'      => 'statusengine_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.hostname = Hosts.uuid',
                ]
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ])
            ->disableHydration();

        $where = [];
        if (!empty($conditions['Host']['name'])) {
            $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
        }

        $where['Hoststatus.current_state'] = $conditions['Hoststatus']['current_state'];

        if ($where['Hoststatus.current_state'] > 0) {
            if ($conditions['Hoststatus']['acknowledged'] ^ $conditions['Hoststatus']['not_acknowledged']) {
                $hasBeenAcknowledged = (int)($conditions['Hoststatus']['acknowledged'] === true);
                $where['Hoststatus.problem_has_been_acknowledged'] = $hasBeenAcknowledged;
            }

            if ($conditions['Hoststatus']['in_downtime'] ^ $conditions['Hoststatus']['not_in_downtime']) {
                $inDowntime = $conditions['Hoststatus']['in_downtime'] === true;
                if ($inDowntime === false) {
                    $where['Hoststatus.scheduled_downtime_depth'] = 0;
                } else {
                    $where['Hoststatus.scheduled_downtime_depth > '] = 0;
                }
            }
        }

        $query->andWhere($where);
        $result = $query->first();

        if ($result === null) {
            return 0;
        }

        return $result['count'];
    }

    /**
     * @param array $MY_RIGHTS
     * @param array $conditions
     * @return int
     */
    public function getHostIdsBySelectedStatusExtendedStatusengine3($MY_RIGHTS, $conditions) {

        $query = $this->find();
        $query
            ->select([
                'Hosts.id'
            ])
            ->innerJoinWith('Hosttemplates')
            ->join([
                'b' => [
                    'table'      => 'statusengine_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.hostname = Hosts.uuid',
                ]
            ])
            ->where([
                'Hosts.disabled' => 0
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
            if (!empty($conditions['Container']['_ids'])) {
                $query->where([
                    'HostsToContainersSharing.container_id IN' => $conditions['Container']['_ids']
                ]);
            }
        } else if (!empty($conditions['Container']['_ids'])) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $containerIds = explode(',', $conditions['Container']['_ids']);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $containerIds
            ]);
        }

        $where = [];

        $where['Hoststatus.current_state'] = $conditions['Hoststatus']['current_state'];

        if ($where['Hoststatus.current_state'] > 0) {
            if ($conditions['Hoststatus']['acknowledged'] ^ $conditions['Hoststatus']['not_acknowledged']) {
                $hasBeenAcknowledged = (int)($conditions['Hoststatus']['acknowledged'] === true);
                $where['Hoststatus.problem_has_been_acknowledged'] = $hasBeenAcknowledged;
            }

            if ($conditions['Hoststatus']['in_downtime'] ^ $conditions['Hoststatus']['not_in_downtime']) {
                $inDowntime = $conditions['Hoststatus']['in_downtime'] === true;
                if ($inDowntime === false) {
                    $where['Hoststatus.scheduled_downtime_depth'] = 0;
                } else {
                    $where['Hoststatus.scheduled_downtime_depth > '] = 0;
                }
            }
        }

        if (!empty($conditions['Hostgroup']['_ids'])) {
            $hostgroupIds = explode(',', $conditions['Hostgroup']['_ids']);
            $query->select([
                'hostgroup_ids' => $query->newExpr(
                    'IF(GROUP_CONCAT(HostToHostgroups.hostgroup_id) IS NULL,
                    GROUP_CONCAT(HosttemplatesToHostgroups.hostgroup_id),
                    GROUP_CONCAT(HostToHostgroups.hostgroup_id))'),
                'count'         => $query->newExpr(
                    'SELECT COUNT(hostgroups.id)
                                FROM hostgroups
                                WHERE FIND_IN_SET (hostgroups.id,IF(GROUP_CONCAT(HostToHostgroups.hostgroup_id) IS NULL,
                                GROUP_CONCAT(HosttemplatesToHostgroups.hostgroup_id),
                                GROUP_CONCAT(HostToHostgroups.hostgroup_id)))
                                AND hostgroups.id IN (' . implode(', ', $hostgroupIds) . ')')
            ]);
            $query->join([
                'hosts_to_hostgroups'         => [
                    'table'      => 'hosts_to_hostgroups',
                    'type'       => 'LEFT',
                    'alias'      => 'HostToHostgroups',
                    'conditions' => 'HostToHostgroups.host_id = Hosts.id',
                ],
                'hosttemplates_to_hostgroups' => [
                    'table'      => 'hosttemplates_to_hostgroups',
                    'type'       => 'LEFT',
                    'alias'      => 'HosttemplatesToHostgroups',
                    'conditions' => 'HosttemplatesToHostgroups.hosttemplate_id = Hosts.hosttemplate_id',
                ]
            ]);
            $query->having([
                'hostgroup_ids IS NOT NULL',
                'count > 0'
            ]);
            $query->groupBy('Hosts.id');
        }

        if (!empty($conditions['Host']['name'])) {
            if (isset($conditions['Host']['name_regex']) && $conditions['Host']['name_regex'] === true || $conditions['Host']['name_regex'] === 'true') {
                if ($this->isValidRegularExpression($conditions['Host']['name'])) {
                    $where[] = new ComparisonExpression(
                        'Hosts.name',
                        $conditions['Host']['name'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                // Use LIKE
                $where[] = new ComparisonExpression(
                    'Hosts.name',
                    sprintf('%%%s%%', $conditions['Host']['name']),
                    'string',
                    'LIKE'
                );
            }
        }

        if (!empty($conditions['Host']['address'])) {
            if (isset($conditions['Host']['address_regex']) && $conditions['Host']['address_regex'] === true || $conditions['Host']['address_regex'] === 'true') {
                if ($this->isValidRegularExpression($conditions['Host']['address'])) {
                    $where[] = new ComparisonExpression(
                        'Hosts.address',
                        $conditions['Host']['address'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                $where['Hosts.address LIKE'] = sprintf('%%%s%%', $conditions['Host']['address']);
            }
        }

        if (!empty($conditions['Host']['keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $conditions['Host']['keywords'],
                'string',
                'RLIKE'
            );
        }

        if (!empty($conditions['Host']['not_keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $conditions['Host']['not_keywords'],
                'string',
                'NOT RLIKE'
            );
        }

        if (!empty($conditions['Hoststatus']['state_older_than']) && is_numeric($conditions['Hoststatus']['state_older_than']) && $conditions['Hoststatus']['state_older_than'] > 0) {
            $intervalUnit = 'MINUTE';
            if (in_array($conditions['Hoststatus']['state_older_than_unit'], ['SECOND', 'MINUTE', 'HOUR', 'DAY'], true)) {
                $intervalUnit = $conditions['Hoststatus']['state_older_than_unit'];
            }
            $query->where([
                //  sprintf('Hoststatus.last_state_change <= NOW() - INTERVAL %s %s',
                sprintf('Hoststatus.last_state_change <= UNIX_TIMESTAMP(DATE(NOW() - INTERVAL %s %s))',
                    $conditions['Hoststatus']['state_older_than'],
                    $intervalUnit
                )
            ]);
        }
        $query->andWhere($where)
            ->groupBy(['Hosts.id'])
            ->disableHydration();
        $result = $query->all();

        return $this->emptyArrayIfNull(Hash::extract($result->toArray(), '{n}.id'));
    }

    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByHost($timeperiodId) {
        $count = $this->find()
            ->where([
                'OR' => [
                    'Hosts.check_period_id'  => $timeperiodId,
                    'Hosts.notify_period_id' => $timeperiodId
                ]
            ])->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.name',
                'Hosts.description',
                'Hosts.address',
                'Hosts.host_url'
            ])
            ->where(['Hosts.id IN' => $ids])
            ->orderBy(['Hosts.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query
            ->groupBy(['Hosts.id'])
            ->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getHostsForEditDetails($ids = []) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.hosttemplate_id',
                'Hosts.container_id',
                'Hosts.description',
                'Hosts.host_url',
                'Hosts.tags',
                'Hosts.check_interval',
                'Hosts.retry_interval',
                'Hosts.max_check_attempts',
                'Hosts.notification_interval',
                'Hosts.notes',
                'Hosts.priority'
            ])
            ->contain([
                'HostsToContainersSharing' => [
                    'fields' => [
                        'HostsToContainers.host_id',
                        'HostsToContainers.container_id'
                    ]
                ],
                'Contacts'                 => [
                    'fields' => [
                        'ContactsToHosts.host_id',
                        'Contacts.id'
                    ]
                ],
                'Contactgroups'            => [
                    'fields' => [
                        'ContactgroupsToHosts.host_id',
                        'Contactgroups.id'
                    ]
                ],
                'Hosttemplates'            => [
                    'fields'        => [
                        'Hosttemplates.id',
                        'Hosttemplates.description',
                        'Hosttemplates.host_url',
                        'Hosttemplates.tags',
                        'Hosttemplates.check_interval',
                        'Hosttemplates.retry_interval',
                        'Hosttemplates.max_check_attempts',
                        'Hosttemplates.notification_interval',
                        'Hosttemplates.notes',
                        'Hosttemplates.priority'
                    ],
                    'Contacts'      => [
                        'fields' => [
                            'ContactsToHosttemplates.hosttemplate_id',
                            'Contacts.id'
                        ]
                    ],
                    'Contactgroups' => [
                        'fields' => [
                            'ContactgroupsToHosttemplates.hosttemplate_id',
                            'Contactgroups.id'
                        ]
                    ],
                ]
            ])
            ->where(['Hosts.id IN' => $ids])
            ->orderBy(['Hosts.id' => 'asc'])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHostDetailsForCopy($id) {
        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing'  =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id']);
                    },
                'Contactgroups'             =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id']);
                    },
                'Contacts'                  =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id']);
                    },
                'Hostgroups'                =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id']);
                    },
                'Customvariables',
                'Parenthosts'               =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select([
                            'id',
                            'name',
                            'satellite_id'
                        ]);
                    },
                'Hostcommandargumentvalues' => [
                    'Commandarguments'
                ],
                'CheckPeriod',
                'NotifyPeriod'
            ])
            ->firstOrFail();
        return $query;
    }

    /**
     * @param array $host
     * @param array $hosttemplate
     * @return array
     */
    public function getDataForChangelogCopy($host, $hosttemplate) {
        $fieldsToCheck = [
            'check_interval',
            'retry_interval',
            'max_check_attempts',
            'notification_interval',
            'notify_on_up',
            'notify_on_down',
            'notify_on_unreachable',
            'flap_detection_notifications_enabled',
            'flap_detection_on_up',
            'flap_detection_on_down',
            'flap_detection_on_unreachable',
            'notes',
            'priority',
            'tags',
            'active_checks_enabled'
        ];
        $hostcommandargumentvalue = [];
        if (!empty($host['hostcommandargumentvalues'])) {
            $hostcommandargumentvalue = $host['hostcommandargumentvalues'];
        } else {
            if (isset($host['command_id']) && ($host['command_id'] === $hosttemplate['Hosttemplate']['command_id'] || $host['command_id'] === null)) {
                $hostcommandargumentvalue = $hosttemplate['Hosttemplatecommandargumentvalue'];
            }
        }

        foreach ($hosttemplate['Hosttemplate'] as $fieldName => $fieldValue) {
            if (!in_array($fieldName, $fieldsToCheck)) {
                continue;
            }
            $dataForChangelog['Host'][$fieldName] = $fieldValue;
            if (isset($host[$fieldName]) && !empty($host[$fieldName])) {
                $dataForChangelog['Host'][$fieldName] = $host[$fieldName];

            }
            //$dataForChangelog['Host'][$fieldName] = (!empty($host[$fieldName]))?$host[$fieldName]:$hosttemplate[$fieldName];
        }
        $dataForChangelog = [
            'Host'                     => Hash::merge($dataForChangelog['Host'], $hosttemplate['Hosttemplate']),
            'Contact'                  => (!empty($host['contacts'])) ? $host['contacts'] : $hosttemplate['contacts'],
            'Contactgroup'             => (!empty($host['contactgroups'])) ? $host['contactgroups'] : $hosttemplate['contactgroups'],
            'Customvariable'           => (!empty($host['customvariables'])) ? $host['customvariables'] : $hosttemplate['customvariables'],
            'Hostcommandargumentvalue' => $hostcommandargumentvalue,
            'Hosttemplate'             => $hosttemplate['Hosttemplate'],
            'Hostgroup'                => (!empty($host['hostgroups'])) ? $host['hostgroups'] : $hosttemplate['hostgroups'],
            'Parenthost'               => $host['parenthosts'],
            'CheckPeriod'              => (empty($host['CheckPeriod'])) ? $hosttemplate['CheckPeriod'] : $host['CheckPeriod'],
            'NotifyPeriod'             => (empty($host['NotifyPeriod'])) ? $hosttemplate['NotifyPeriod'] : $host['NotifyPeriod'],
            'CheckCommand'             => (empty($host['CheckCommand'])) ? $hosttemplate['CheckCommand'] : $host['CheckCommand'],
        ];
        return $dataForChangelog;
    }

    /**
     * @param $hoststatus
     * @param bool $extended show details ('acknowledged', 'in downtime', ...)
     * @return array
     */
    public function getHostStateSummary($hoststatus, bool $extended = true) {
        $hostStateSummary = [
            'state'            => [
                0 => 0,
                1 => 0,
                2 => 0
            ],
            'acknowledged'     => [
                0 => 0,
                1 => 0,
                2 => 0
            ],
            'in_downtime'      => [
                0 => 0,
                1 => 0,
                2 => 0
            ],
            'not_handled'      => [
                0 => 0,
                1 => 0,
                2 => 0
            ],
            'total'            => 0,
            'cumulative_state' => -1 // not monitored
        ];
        if ($extended === true) {
            $hostStateSummary = [
                'state'            => [
                    0         => 0,
                    1         => 0,
                    2         => 0,
                    'hostIds' => [
                        0 => [],
                        1 => [],
                        2 => []
                    ]
                ],
                'acknowledged'     => [
                    0         => 0,
                    1         => 0,
                    2         => 0,
                    'hostIds' => [
                        0 => [],
                        1 => [],
                        2 => []
                    ]
                ],
                'in_downtime'      => [
                    0         => 0,
                    1         => 0,
                    2         => 0,
                    'hostIds' => [
                        0 => [],
                        1 => [],
                        2 => []
                    ]
                ],
                'not_handled'      => [
                    0              => 0,
                    1              => 0,
                    2              => 0,
                    'hostIds'      => [
                        0 => [],
                        1 => [],
                        2 => []
                    ],
                    'totalHostIds' => []
                ],
                'passive'          => [
                    0         => 0,
                    1         => 0,
                    2         => 0,
                    'hostIds' => [
                        0 => [],
                        1 => [],
                        2 => []
                    ]
                ],
                'total'            => 0,
                'cumulative_state' => -1 // not monitored
            ];
        }
        if (empty($hoststatus)) {
            return $hostStateSummary;
        }
        foreach ($hoststatus as $host) {
            //Check for random exit codes like 255...
            if ($host['Hoststatus']['current_state'] > 2) {
                $host['Hoststatus']['current_state'] = 2;
            }
            $hostStateSummary['state'][$host['Hoststatus']['current_state']]++;
            if ($extended === true) {
                $hostStateSummary['state']['hostIds'][$host['Hoststatus']['current_state']][] = $host['id'];
                if ($host['Hoststatus']['current_state'] > 0) {
                    if ($host['Hoststatus']['problem_has_been_acknowledged'] > 0) {
                        $hostStateSummary['acknowledged'][$host['Hoststatus']['current_state']]++;
                        $hostStateSummary['acknowledged']['hostIds'][$host['Hoststatus']['current_state']][] = $host['id'];
                    } else if ($host['Hoststatus']['problem_has_been_acknowledged'] == 0 && $host['Hoststatus']['scheduled_downtime_depth'] == 0) {
                        $hostStateSummary['not_handled'][$host['Hoststatus']['current_state']]++;
                        $hostStateSummary['not_handled']['hostIds'][$host['Hoststatus']['current_state']][] = $host['id'];
                        $hostStateSummary['not_handled']['totalHostIds'][] = $host['id'];
                    }
                }

                if ($host['Hoststatus']['scheduled_downtime_depth'] > 0) {
                    $hostStateSummary['in_downtime'][$host['Hoststatus']['current_state']]++;
                    $hostStateSummary['in_downtime']['hostIds'][$host['Hoststatus']['current_state']][] = $host['id'];
                }
                if ($host['Hoststatus']['active_checks_enabled'] == 0) {
                    $hostStateSummary['passive'][$host['Hoststatus']['current_state']]++;
                    $hostStateSummary['passive']['hostIds'][$host['Hoststatus']['current_state']][] = $host['id'];
                }
            } else {
                if ($host['Hoststatus']['current_state'] > 0) {
                    if ($host['Hoststatus']['problem_has_been_acknowledged'] > 0) {
                        $hostStateSummary['acknowledged'][$host['Hoststatus']['current_state']]++;
                    } else if ($host['Hoststatus']['problem_has_been_acknowledged'] == 0 && $host['Hoststatus']['scheduled_downtime_depth'] == 0) {
                        $hostStateSummary['not_handled'][$host['Hoststatus']['current_state']]++;
                    }
                }
                if ($host['Hoststatus']['scheduled_downtime_depth'] > 0) {
                    $hostStateSummary['in_downtime'][$host['Hoststatus']['current_state']]++;
                }
            }
            if ($hostStateSummary['cumulative_state'] < $host['Hoststatus']['current_state']) {
                $hostStateSummary['cumulative_state'] = $host['Hoststatus']['current_state'];
            }
            $hostStateSummary['total']++;
        }
        return $hostStateSummary;
    }

    /**
     * @return array
     */
    public function parentHostsWithChildIds() {
        $query = $this->find()
            ->join([
                [
                    'table'      => 'hosts_to_parenthosts',
                    'alias'      => 'Parenthosts',
                    'type'       => 'INNER',
                    'conditions' => [
                        'Hosts.id = Parenthosts.parenthost_id',
                    ],
                ],
                [
                    'table'      => 'hosts',
                    'alias'      => 'HostToParenthostParent',
                    'type'       => 'INNER',
                    'conditions' => [
                        'Parenthosts.parenthost_id = HostToParenthostParent.id',
                    ],
                ],
                [
                    'table'      => 'hosts',
                    'alias'      => 'HostToParenthostChild',
                    'type'       => 'INNER',
                    'conditions' => [
                        'Parenthosts.host_id = HostToParenthostChild.id',
                    ],
                ]
            ])
            ->select([
                'HostToParenthostChild.id',
                'HostToParenthostParent.id'
            ])
            ->disableHydration()
            ->all();

        if (empty($query)) {
            return [];
        }
        return $query->toArray();
    }

    public function getHostsForStatusmaps($conditions = [], $containerIds = [], $allHostIds = [], $count = false, $limit = null, $offset = null) {
        $query = $this->find()
            ->contain([
                'Parenthosts' => function (Query $q) {
                    return $q->select([
                        'Parenthosts.id'
                    ]);
                }
            ])
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
                'Hosts.satellite_id',
            ]);

        if (!empty($containerIds)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $containerIds
            ]);
        }

        $query->contain('HostsToContainersSharing');

        if (!empty($allHostIds)) {
            $query->where([
                'Hosts.id IN' => $allHostIds
            ]);
        }
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        if ($limit !== null) {
            $query->limit($limit);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }
        $query->groupBy('Hosts.id');
        $query->disableHydration()->all();

        if ($count === true) {
            return $query->count();
        }

        if (empty($query)) {
            return [];
        }
        return $query->toArray();
    }


    /**
     * @param $id
     * @return array
     */
    public function getHostByIdWithDetails($id) {
        $contain = [
            'Contactgroups',
            'Contacts',
            'Hostgroups',
            'Customvariables',
            'Parenthosts',
            'HostsToContainersSharing',
            'Hostcommandargumentvalues' => [
                'Commandarguments'
            ],
            'CheckPeriod',
            'NotifyPeriod'
        ];

        if (Plugin::isLoaded('PrometheusModule')) {
            $contain[] = 'PrometheusExporters';
        };

        $query = $this->find()
            ->where([
                'Hosts.id' => $id
            ])
            ->contain($contain)
            ->disableHydration()
            ->first();

        $host = $query;
        $host['hostgroups'] = [
            '_ids' => Hash::extract($query, 'hostgroups.{n}.id')
        ];
        $host['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];
        $host['contactgroups'] = [
            '_ids' => Hash::extract($query, 'contactgroups.{n}.id')
        ];
        $host['parenthosts'] = [
            '_ids' => Hash::extract($query, 'parenthosts.{n}.id')
        ];
        $host['hosts_to_containers_sharing'] = [
            '_ids' => Hash::extract($query, 'hosts_to_containers_sharing.{n}.id')
        ];
        $host['prometheus_exporters'] = [
            '_ids' => Hash::extract($query, 'prometheus_exporters.{n}.id')
        ];

        return [
            'Host' => $host
        ];
    }

    /**
     * @param int $timeperiodId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getHostsByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.name'
            ])
            ->where([
                'OR' => [
                    'Hosts.check_period_id'  => $timeperiodId,
                    'Hosts.notify_period_id' => $timeperiodId
                ]
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->contain([
                'HostsToContainersSharing'
            ])->where([
                'HostsToContainers.container_id IN' => $MY_RIGHTS
            ])->groupBy([
                'Host.id'
            ]);
        }

        $query->enableHydration($enableHydration);

        $result = $query->all();
        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @param int $id
     * @param int $USAGE_FLAG
     * @return Host|bool
     */
    public function setUsageFlagById($id, int $USAGE_FLAG) {
        $host = $this->get($id);
        $currentFlag = $host->get('usage_flag');

        if ($currentFlag & $USAGE_FLAG) {
            //Host already has the flag for given module
            return true;
        } else {
            $newFlag = $currentFlag + $USAGE_FLAG;
            $host->set('usage_flag', $newFlag);
            return $this->save($host);
        }
    }

    /**
     * @param int $id
     * @param int $USAGE_FLAG
     * @return Host|bool
     */
    public function removeUsageFlagById($id, int $USAGE_FLAG) {
        $host = $this->get($id);
        $currentFlag = $host->get('usage_flag');

        if ($currentFlag & $USAGE_FLAG) {
            $newFlag = $currentFlag - $USAGE_FLAG;
            if ($newFlag < 0) {
                $newFlag = 0;
            }

            $host->set('usage_flag', $newFlag);
            return $this->save($host);
        }

        return true;
    }

    /**
     * @param Host $Host
     * @param User $User
     * @return bool
     */
    public function __delete(Host $Host, User $User) {
        $hostdependencies = $Host->get('hostdependencies_host_memberships');
        $hostdependenciesToDelete = [];

        if (!empty($hostdependencies)) {
            /** @var HostdependenciesTable $HostdependenciesTable */
            $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
            /** @var Hostdependency $Hostdependency */
            foreach ($hostdependencies as $Hostdependency) {
                $hostdependencyId = $Hostdependency->get('hostdependency_id');
                $hostdependencyIsBroken = $HostdependenciesTable->isHostdependencyBroken(
                    $hostdependencyId,
                    $Host->get('id')
                );
                if ($hostdependencyIsBroken === true) {
                    $hostdependenciesToDelete[] = $Hostdependency;
                }
            }
        }

        $hostescalations = $Host->get('hostescalations_host_memberships');
        $hostescalationsToDelete = [];
        if (!empty($hostescalations)) {
            /** @var HostescalationsTable $HostescalationsTable */
            $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');
            /** @var Hostescalation $Hostescalation */
            foreach ($hostescalations as $Hostescalation) {
                $hostescalationId = $Hostescalation->get('hostescalation_id');
                $hostescalationIsBroken = $HostescalationsTable->isHostescalationBroken(
                    $hostescalationId,
                    $Host->get('id')
                );
                if ($hostescalationIsBroken === true) {
                    $hostescalationsToDelete[] = $Hostescalation;
                }
            }
        }

        if (!$this->delete($Host)) {
            return false;
        }

        /** @var DocumentationsTable $DocumentationsTable */
        $DocumentationsTable = TableRegistry::getTableLocator()->get('Documentations');
        /** @var DeletedHostsTable $DeletedHostsTable */
        $DeletedHostsTable = TableRegistry::getTableLocator()->get('DeletedHosts');
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'delete',
            'hosts',
            $Host->get('id'),
            OBJECT_HOST,
            $Host->get('container_id'),
            $User->getId(),
            $Host->get('name'),
            [
                'Host' => $Host->toArray()
            ]
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        if ($DocumentationsTable->existsByUuid($Host->get('uuid'))) {
            $DocumentationsTable->delete($DocumentationsTable->getDocumentationByUuid($Host->get('uuid')));
        }

        $this->_clenupHostEscalationAndDependency($hostdependenciesToDelete, $hostescalationsToDelete);

        //Save host to DeletedHostsTable
        $data = $DeletedHostsTable->newEntity([
            'host_id'          => $Host->get('id'),
            'uuid'             => $Host->get('uuid'),
            'hosttemplate_id'  => $Host->get('hosttemplate_id'),
            'name'             => $Host->get('name'),
            'description'      => $Host->get('description'),
            'deleted_perfdata' => 0,
        ]);
        $DeletedHostsTable->save($data);

        return true;
    }

    /**
     * Check if the host was part of an hostescalation or hostdependency
     * If yes, cake delete the records by it self, but may be we have an empty hostescalation or hostgroup now.
     * Nagios don't relay like this so we need to check this and delete the host escalation or host dependency if empty
     *
     * @param array $hostdependenciesMembershipToDelete
     * @param array $hostescalationsMembershipToDelete
     */
    public function _clenupHostEscalationAndDependency($hostdependenciesMembershipToDelete = [], $hostscalationsMembershipToDelete = []) {
        if (!empty($hostdependenciesMembershipToDelete)) {
            /** @var HostdependenciesTable $HostdependenciesTable */
            $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
            foreach ($hostdependenciesMembershipToDelete as $hostdependencyMembership) {
                if ($HostdependenciesTable->existsById($hostdependencyMembership->get('hostdependency_id'))) {
                    $hostdependency = $HostdependenciesTable->get($hostdependencyMembership->get('hostdependency_id'));
                    $HostdependenciesTable->delete($hostdependency);
                }
            }
        }

        if (!empty($hostscalationsMembershipToDelete)) {
            /* @var HostescalationsTable $HostescalationsTable */
            $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');
            foreach ($hostscalationsMembershipToDelete as $hostescalationMembership) {
                if ($HostescalationsTable->existsById($hostescalationMembership->get('hostescalation_id'))) {
                    $hostescalation = $HostescalationsTable->get($hostescalationMembership->get('hostescalation_id'));
                    $HostescalationsTable->delete($hostescalation);
                }
            }
        }
    }

    /**
     * @param $id
     * @return array|Host|null
     */
    public function getHostByIdForTimeline($id) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.name',
                'Hosts.uuid',
                'Hosts.container_id',
                'Hosts.hosttemplate_id',
                'Hosts.check_period_id',
                'Hosts.notify_period_id',
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing',
                'Hosttemplates' => function (Query $query) {
                    $query->select([
                        'Hosttemplates.id',
                        'Hosttemplates.check_period_id',
                        'Hosttemplates.notify_period_id'
                    ]);
                    return $query;
                }
            ])
            ->first();

        return $query;
    }

    /**
     * @param int $satelliteId
     * @return array
     */
    public function getHostsBySatelliteId($satelliteId) {
        $query = $this->find()
            ->where([
                'Hosts.satellite_id' => $satelliteId
            ])
            ->contain('HostsToContainersSharing')
            ->all();
        return $query->toArray();
    }

    /**
     * @param int $satelliteId
     * @return array
     */
    public function getHostsBySatelliteIdForDelete($satelliteId) {
        $query = $this->find()
            ->where([
                'Hosts.satellite_id' => $satelliteId
            ])
            ->contain([
                'HostescalationsHostMemberships',
                'HostdependenciesHostMemberships',
                'HostsToContainersSharing'
            ])
            ->all();
        return $query->toArray();
    }

    /**
     * @param string $uuid
     * @return null|int
     */
    public function getSatelliteIdByUuid(string $uuid) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.satellite_id'
            ])
            ->where([
                'Hosts.uuid' => $uuid
            ])
            ->disableHydration()
            ->first();

        if ($query === null) {
            return null;
        }

        return $query['satellite_id'];
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param array $MY_RIGHTS
     * @param array $where
     * @return array
     */
    public function getHostsByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Hosts.disabled IN'  => [0],
            'Hosts.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Hosts.' . $index,
            'Hosts.name'
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
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
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }

    /**
     * @param $id
     * @return array|Host|null
     */
    public function getHostByIdForCheckmk($id) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                'Hosts.address',
                'Hosts.satellite_id',
                'Hosts.container_id',
                'Hosts.hosttemplate_id'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->first();
        return $query;
    }

    /**
     * @param $uuid
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getHostByUuidForCheckmkNagiosExportCommand($uuid) {
        $query = $this->find()
            ->enableAutoFields()
            ->contain([
                'Hosttemplates' => function (Query $query) {
                    return $query->contain([
                        'Customvariables'
                    ]);
                },
                'Customvariables',
            ])
            ->where([
                'Hosts.uuid' => $uuid
            ])
            ->first();
        return $query;
    }

    /**
     * @return array
     */
    public function getHostsForCheckmkNagiosExportCommand() {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.satellite_id',
            ])
            ->contain([
                'Services' => function (Query $query) {
                    return $query->select([
                        'Services.id',
                        'Services.host_id',
                        'Services.uuid',
                        'Services.name',
                    ])->where([
                        'Services.disabled'    => 0,
                        'Services.name IS NOT' => null,
                    ]);
                }
            ])
            ->where([
                'Hosts.disabled' => 0,
            ])
            ->where(function (QueryExpression $exp) {
                return $exp->gt('Hosts.satellite_id', 0);
            })
            ->all();

        if (empty($query)) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param int $hostTypeId
     * @param array $MY_RIGHTS
     * @param array $where
     * @return array
     */
    public function getHostsByTypeId($hostTypeId = GENERIC_HOST, $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Hosts.disabled IN' => [0],
            'Hosts.host_type'   => $hostTypeId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
        ]);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getHostTypes() {
        $types = $this->getHostTypesWithStyles();
        return array_combine(array_keys($types), Hash::extract($types, '{n}.title'));
    }

    /**
     * @return array
     */
    public function getHostTypesWithStyles() {
        $types[GENERIC_HOST] = [
            'title'  => __('Generic host'),
            'color'  => 'text-generic',
            'class'  => 'border-generic',
            'icon'   => ['fas', 'cog'],
            'rotate' => 0
        ];

        if (Plugin::isLoaded('EventcorrelationModule')) {
            $types[EVK_HOST] = [
                'title'  => __('EVC host'),
                'color'  => 'text-evc',
                'class'  => 'border-evc',
                'icon'   => ['fas', 'sitemap'],
                'rotate' => 90
            ];
        }
        return $types;
    }

    /**
     * Returns an array of all parent host ids of a given host id
     *
     * @param int $hostId
     * @return array
     */
    public function getParentHostIdsByHostId($hostId) {
        /** @var HostsToParenthostsSelectTable $HostsToParenthostsTable */
        $HostsToParenthostsTable = TableRegistry::getTableLocator()->get('HostsToParenthosts');

        $query = $HostsToParenthostsTable->find()
            ->where([
                'host_id' => $hostId
            ])
            ->disableHydration()
            ->all();

        if (empty($query)) {
            return [];
        }

        return Hash::extract($query->toArray(), '{n}.parenthost_id');
    }

    /**
     * @param int[] $parentHostIds
     * @param int $hostId
     * @return bool
     */
    public function hasParentLoop2($parentHostIds, $hostId) {
        $parentHostIds = $this->castToIntArray($parentHostIds);
        $hostId = (int)$hostId;

        if (in_array($hostId, $parentHostIds, true)) {
            // given $hostId is used by another host as parent
            return true;
        }


        foreach ($parentHostIds as $parentHostId) {
            if ($this->hasParentLoop2($this->getParentHostIdsByHostId($parentHostId), $hostId)) {
                // Loop via parent of a parent
                return true;
            }
        }

        //No parent loop detected
        return false;

    }

    /**
     * @param int $hostId
     * @param int|null $originalHostId
     * @return bool
     */
    public function hasParentLoop($hostId, $originalHostId = null) {
        $hostId = (int)$hostId;
        $parentHostIds = $this->getParentHostIdsByHostId($hostId);

        if (in_array($hostId, $parentHostIds, true)) {
            // given $hostId is used by another host as parent
            return true;
        }


        foreach ($parentHostIds as $parentHostId) {
            if ($parentHostId === $originalHostId) {
                //Got a loop
                return true;
            }

            if ($this->hasParentLoop($parentHostId, $originalHostId)) {
                // Loop via parent of a parent
                return true;
            }
        }

        //No parent loop detected
        return false;

    }

    /**
     * @param array|int $arr
     * @return int[]
     */
    protected function castToIntArray($arr) {
        if (!is_array($arr)) {
            $arr = [$arr];
        }

        $intArr = [];
        foreach ($arr as $item) {
            $item = (int)$item;
            $intArr[$item] = $item;
        }
        return $intArr;
    }

    /**
     * @param int $hostId
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getServicesByHostIdForAllocation($hostId) {
        $query = $this->find()
            ->contain([
                'Services' => function (Query $query) {
                    return $query
                        ->select([
                            'Services.id',
                            'Services.host_id',
                            'Services.disabled',
                            'Services.servicetemplate_id'
                        ]);
                }
            ])
            ->where([
                'Hosts.id' => $hostId
            ])
            ->disableHydration()
            ->first();

        return $query;
    }

    /**
     * @param string $uuid
     * @param bool $enableHydration
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getPushAgentRecordByHostUuidForFreshnessCheck($uuid, $enableHydration = true) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Agentconfigs.id',
                'Agentconfigs.host_id',
                'PushAgents.id',
                'PushAgents.agentconfig_id',
                'PushAgents.last_update'
            ])
            ->innerJoinWith('Agentconfigs', function (Query $q) {
                $q->innerJoinWith('PushAgents');
                return $q;
            })
            ->where([
                'Hosts.uuid' => $uuid
            ])
            ->enableHydration($enableHydration);

        $result = $query->first();

        return $result;

    }

    /**
     * @param $hosttemplateId
     * @param $commandId
     */
    public function updateHostCommandIdIfHostHasOwnCommandArguments($hosttemplateId, $commandId) {
        $query = $this->find()
            ->select([
                'Hosts.id'
            ])
            ->contain([
                'Hostcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->where([
                'Hosts.command_id IS NULL',
                'Hosts.hosttemplate_id' => $hosttemplateId
            ])
            ->disableHydration()
            ->all();

        $query = $query->toArray();

        if (!empty($query)) {
            $hostIds = [];
            foreach ($query as $row) {
                if (!empty($row['hostcommandargumentvalues'])) {
                    $hostIds[] = (int)$row['id'];
                }
            }
            if (!empty($hostIds)) {
                $this->updateAll([
                    'command_id' => $commandId
                ], [
                    'id IN' => $hostIds
                ]);
            }
        }
    }

    /**
     * @param string $hostname
     * @param int[] $MY_RIGHTS
     * @param int[] $hostIdsToExclude
     * @return array
     */
    public function isHostnameUnique($hostname, $MY_RIGHTS = [], $hostIdsToExclude = []) {
        if (!is_array($hostIdsToExclude)) {
            $hostIdsToExclude = [$hostIdsToExclude];
        }

        $query = $this->find();

        $where = [
            'Hosts.name' => $hostname
        ];

        if (!empty($hostIdsToExclude)) {
            $where['Hosts.id NOT IN'] = $hostIdsToExclude;
        }

        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain('HostsToContainersSharing');
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);

        $result = $query->count();

        if ($result > 0) {
            return false;
        }
        return true;
    }

    /**
     * @param $MY_RIGHTS
     * @param $conditions
     * @return array
     */
    public function getHostsWithStatusByConditions($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'Hosts.uuid'
            ])
            ->where([
                'Hosts.disabled' => 0
            ])
            ->join([
                'a'             => [
                    'table'      => 'nagios_objects',
                    'type'       => 'INNER',
                    'alias'      => 'HostObject',
                    'conditions' => 'Hosts.uuid = HostObject.name1 AND HostObject.objecttype_id = 1'
                ],
                'b'             => [
                    'table'      => 'nagios_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.host_object_id = HostObject.object_id',
                ],
                'hosttemplates' => [
                    'table'      => 'hosttemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Hosttemplates',
                    'conditions' => 'Hosttemplates.id = Hosts.hosttemplate_id',
                ]
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->contain([
            'HostsToContainersSharing'
        ]);

        $query->contain([
            'HostsToContainersSharing'
        ]);
        if (!empty($conditions['Hostgroup']['_ids'])) {
            $hostgroupIds = explode(',', $conditions['Hostgroup']['_ids']);
            $query->select([
                'hostgroup_ids' => $query->newExpr(
                    'IF(GROUP_CONCAT(HostToHostgroups.hostgroup_id) IS NULL,
                    GROUP_CONCAT(HosttemplatesToHostgroups.hostgroup_id),
                    GROUP_CONCAT(HostToHostgroups.hostgroup_id))'),
                'count'         => $query->newExpr(
                    'SELECT COUNT(hostgroups.id)
                                FROM hostgroups
                                WHERE FIND_IN_SET (hostgroups.id,IF(GROUP_CONCAT(HostToHostgroups.hostgroup_id) IS NULL,
                                GROUP_CONCAT(HosttemplatesToHostgroups.hostgroup_id),
                                GROUP_CONCAT(HostToHostgroups.hostgroup_id)))
                                AND hostgroups.id IN (' . implode(', ', $hostgroupIds) . ')')
            ]);
            $query->join([
                'hosts_to_hostgroups'         => [
                    'table'      => 'hosts_to_hostgroups',
                    'type'       => 'LEFT',
                    'alias'      => 'HostToHostgroups',
                    'conditions' => 'HostToHostgroups.host_id = Hosts.id',
                ],
                'hosttemplates_to_hostgroups' => [
                    'table'      => 'hosttemplates_to_hostgroups',
                    'type'       => 'LEFT',
                    'alias'      => 'HosttemplatesToHostgroups',
                    'conditions' => 'HosttemplatesToHostgroups.hosttemplate_id = Hosttemplates.id',
                ]
            ]);
            $query->having([
                'hostgroup_ids IS NOT NULL',
                'count > 0'
            ]);
            $query->groupBy('Hosts.id');
        }

        if (isset($where['Hosts.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Hosts.keywords rlike']);
        }

        if (isset($where['Hosts.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $where['Hosts.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Hosts.not_keywords not rlike']);
        }

        $query->disableHydration();

        $where = [];
        if (!empty($conditions['Host']['name'])) {
            $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
        }
        $query->andWhere($where);
        $query->disableHydration();
        $result = $query->all();
        if ($result === null) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * @param $MY_RIGHTS
     * @param $conditions
     * @return array
     */
    public function getHostsWithStatusByConditionsStatusengine3($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'Hosts.id',
                'Hoststatus.current_state',
                'Hoststatus.scheduled_downtime_depth',
                'Hoststatus.active_checks_enabled',
                'Hoststatus.problem_has_been_acknowledged'
            ]);
        $query->where([
            'Hosts.disabled' => 0
        ])
            ->join([
                'b'             => [
                    'table'      => 'statusengine_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.hostname = Hosts.uuid',
                ],
                'hosttemplates' => [
                    'table'      => 'hosttemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Hosttemplates',
                    'conditions' => 'Hosttemplates.id = Hosts.hosttemplate_id',
                ]
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->contain([
            'HostsToContainersSharing'
        ]);

        if (!empty($conditions['Hostgroup'])) {
            $conditions['Hostgroup'] = Hash::filter($conditions['Hostgroup']);
        }
        if (!empty($conditions['Hostgroup'])) {
            $hostgroups = $this->fetchTable('Hostgroups');
            $hostgroupIds = [];
            if (!empty($conditions['Hostgroup']['_ids'])) {
                $hostgroupIds = explode(',', $conditions['Hostgroup']['_ids']);
            }
            $whereForCount = [
                $query->newExpr('FIND_IN_SET (Hostgroups.id,IF(GROUP_CONCAT(HostToHostgroups.hostgroup_id) IS NULL,
                                GROUP_CONCAT(HosttemplatesToHostgroups.hostgroup_id),
                                GROUP_CONCAT(HostToHostgroups.hostgroup_id)))')
            ];

            if (!empty($hostgroupIds)) {
                $whereForCount[] = ['Hostgroups.id IN' => $hostgroupIds];
            }

            if (!empty($conditions['Hostgroup']['keywords'])) {
                $whereForCount[] = new ComparisonExpression(
                    'IF((Hostgroups.tags IS NOT NULL), Hostgroups.tags, "")',
                    $conditions['Hostgroup']['keywords'],
                    'string',
                    'RLIKE'

                );
            }

            if (!empty($conditions['Hostgroup']['not_keywords'])) {
                $whereForCount[] = new ComparisonExpression(
                    'IF((Hostgroups.tags IS NOT NULL), Hostgroups.tags, "")',
                    $conditions['Hostgroup']['not_keywords'],
                    'string',
                    'NOT RLIKE'
                );
            }

            if (!empty($whereForCount)) {
                $query->select([
                    'hostgroup_ids' => $query->newExpr(
                        'IF(GROUP_CONCAT(HostToHostgroups.hostgroup_id) IS NULL,
                    GROUP_CONCAT(HosttemplatesToHostgroups.hostgroup_id),
                    GROUP_CONCAT(HostToHostgroups.hostgroup_id))'),
                    'count'         => $hostgroups->find()->select([$query->func()->count('Hostgroups.id')])
                        ->where($whereForCount)
                ]);

            }

            $query->join([
                'hosts_to_hostgroups'         => [
                    'table'      => 'hosts_to_hostgroups',
                    'type'       => 'LEFT',
                    'alias'      => 'HostToHostgroups',
                    'conditions' => 'HostToHostgroups.host_id = Hosts.id',
                ],
                'hosttemplates_to_hostgroups' => [
                    'table'      => 'hosttemplates_to_hostgroups',
                    'type'       => 'LEFT',
                    'alias'      => 'HosttemplatesToHostgroups',
                    'conditions' => 'HosttemplatesToHostgroups.hosttemplate_id = Hosttemplates.id',
                ]
            ]);
            $query->having([
                'hostgroup_ids IS NOT NULL',
                'count > 0'
            ]);
        }

        $where = [];


        if (!empty($conditions['Host']['name'])) {
            if (isset($conditions['Host']['name_regex']) && $conditions['Host']['name_regex'] === true || $conditions['Host']['name_regex'] === 'true') {
                if ($this->isValidRegularExpression($conditions['Host']['name'])) {
                    $where[] = new ComparisonExpression(
                        'Hosts.name',
                        $conditions['Host']['name'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                // Use LIKE
                $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
            }
        }

        if (!empty($conditions['Host']['address'])) {
            if (isset($conditions['Host']['address_regex']) && $conditions['Host']['address_regex'] === true || $conditions['Host']['address_regex'] === 'true') {
                if ($this->isValidRegularExpression($conditions['Host']['address'])) {
                    $where[] = new ComparisonExpression(
                        'Hosts.address',
                        $conditions['Host']['address'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                $where['Hosts.address LIKE'] = sprintf('%%%s%%', $conditions['Host']['address']);
            }
        }

        if (!empty($conditions['Host']['keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $conditions['Host']['keywords'],
                'string',
                'RLIKE'
            );
        }

        if (!empty($conditions['Host']['not_keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $conditions['Host']['not_keywords'],
                'string',
                'NOT RLIKE'
            );
        }
        $query->andWhere($where);
        $query->groupBy('Hosts.id');
        $query->disableHydration();
        $result = $query->all();
        if ($result === null) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * @param string $hostUuid
     * @return Host|null
     */
    public function getHostForRescheduling(string $hostUuid) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.hosttemplate_id',
                'Hosts.active_checks_enabled',
            ])
            ->where([
                'Hosts.uuid' => $hostUuid
            ])
            ->contain([
                'Hosttemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Hosttemplates.id',
                            'Hosttemplates.active_checks_enabled',
                        ]);
                    return $query;
                }
            ]);
        return $query->first();
    }

    /**
     * @param array $containerIds
     * @param array $where
     * @return array
     */
    public function getParenthostsForDashboard($containerIds = [], $where = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $joins = [
            [
                'table'      => 'hosts_to_parenthosts',
                'type'       => 'INNER',
                'alias'      => 'Parenthosts',
                'conditions' => 'Parenthosts.parenthost_id = Hosts.id'
            ]
        ];
        if (!empty($containerIds)) {
            $joins[] = [
                'table'      => 'hosts_to_containers',
                'alias'      => 'HostsToContainers',
                'type'       => 'LEFT',
                'conditions' => [
                    'HostsToContainers.host_id = Hosts.id',
                ],
            ];
            $query['conditions'][''] = $containerIds;
        }


        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',
                //'Parenthosts.parenthost_id'
            ])
            ->distinct('Hosts.uuid')
            ->join($joins);


        if (!empty($where)) {
            $query->where($where);
        }

        if (!empty($containerIds)) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $containerIds
            ]);
        }

        $query->disableHydration();
        $query->all();

        return $query->toArray();
    }

    /**
     * @param $containerIds
     * @param $hostgroupIds
     * @param $type
     * @param $index
     * @param $where
     * @return array
     */
    public function getHostsByContainerIdAndHostgroupIds($containerIds, $hostgroupIds, $type = 'all', $index = 'id', $where = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $containerIds = array_unique($containerIds);

        if (!is_array($hostgroupIds)) {
            $hostgroupIds = [$hostgroupIds];
        }

        $_where = [
            'Hosts.disabled IN' => [0]
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Hosts.' . $index,
            'Hosts.name'
        ])->innerJoinWith('Hostgroups')
            ->where([
                'Hostgroups.id IN' => $hostgroupIds
            ]);

        $query->where($where);
        if (!empty($containerIds)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $containerIds
            ]);
        }
        $query->disableHydration();
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
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
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }

    /**
     * @param HostConditions $HostConditions
     * @param int|array $selected
     * @param bool $returnEmptyArrayIfMyRightsIsEmpty
     * @return array|null
     * @deprecated since ITC-2819
     * See https://github.com/openITCOCKPIT/openITCOCKPIT/pull/1377/files?diff=split&w=0 how to restore <= 4.4.1 behavior
     */
    public function getHostsForHostgroupForAngular(HostConditions $HostConditions, $selected = []) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }

        $query = $this->find('list');
        $containerIds = $HostConditions->getContainerIds();
        if (empty($containerIds)) {
            //User has no permissions to edit hosts/services
            return [];
        }
        $query->where([
            'Hosts.container_id IN' => $containerIds
        ]);

        $where = $HostConditions->getWhereForFind();
        if (is_array($selected)) {
            $selected = array_filter($selected);
        }
        if (!empty($selected)) {
            $where['NOT'] = [
                'Hosts.id IN' => $selected
            ];
        }


        if (!empty($where['NOT'])) {
            // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
            $where['NOT'] = [
                'OR' => $where['NOT']
            ];
        }
        if (!empty($where)) {
            $query->where($where);
        }
        $query->groupBy(['Hosts.id']);
        $query->orderBy([
            'Hosts.name' => 'asc',
            'Hosts.id'   => 'asc'
        ]);
        $query->limit(ITN_AJAX_LIMIT);

        $hostsWithLimit = $query->toArray();

        $selectedHosts = [];
        if (!empty($selected)) {
            $query = $this->find('list');

            $query->where([
                'Hosts.container_id IN' => $containerIds
            ]);

            $where = [
                'Hosts.id IN' => $selected
            ];
            if ($HostConditions->includeDisabled() === false) {
                $where['Hosts.disabled'] = 0;
            }

            if (!empty($where['NOT'])) {
                // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
                $where['NOT'] = [
                    'OR' => $where['NOT']
                ];
            }

            if (!empty($where)) {
                $query->where($where);
            }
            $query->groupBy(['Hosts.id']);
            $query->orderBy([
                'Hosts.name' => 'asc',
                'Hosts.id'   => 'asc'
            ]);

            $selectedHosts = $query->toArray();

        }

        $hosts = $hostsWithLimit + $selectedHosts;
        asort($hosts, SORT_FLAG_CASE | SORT_NATURAL);
        return $hosts;
    }

    /**
     * @param $MY_RIGHTS
     * @param $conditions
     * @return array
     */
    public function getHostsForDesktopWithStatusByConditionsStatusengine3($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'Hosts.id',
                'Hoststatus.current_state',
                'Hoststatus.scheduled_downtime_depth',
                'Hoststatus.active_checks_enabled',
                'Hoststatus.problem_has_been_acknowledged'
            ]);
        $query->where([
            'Hosts.disabled' => 0
        ])
            ->join([
                'b'             => [
                    'table'      => 'statusengine_hoststatus',
                    'type'       => 'INNER',
                    'alias'      => 'Hoststatus',
                    'conditions' => 'Hoststatus.hostname = Hosts.uuid',
                ],
                'hosttemplates' => [
                    'table'      => 'hosttemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Hosttemplates',
                    'conditions' => 'Hosttemplates.id = Hosts.hosttemplate_id',
                ]
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->contain([
            'HostsToContainersSharing'
        ]);

        $where = [];

        if (!empty($conditions['filter[Hosts.name]'])) {
            if (isset($conditions['filter[Hosts.name_regex]']) && $conditions['filter[Hosts.name_regex]'] === true || $conditions['filter[Hosts.name_regex]'] === 'true') {
                if ($this->isValidRegularExpression($conditions['filter[Hosts.name]'])) {
                    $where[] = new ComparisonExpression(
                        'Hosts.name',
                        $conditions['filter[Hosts.name]'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                // Use LIKE
                $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['filter[Hosts.name]']);
            }
        }

        if (!empty($conditions['filter[Hosts.address]'])) {
            if (isset($conditions['filter[Hosts.address_regex']) && $conditions['filter[Hosts.address_regex]'] === true || $conditions['filter[Hosts.address_regex]'] === 'true') {
                if ($this->isValidRegularExpression($conditions['filter[Hosts.address]'])) {
                    $where[] = new ComparisonExpression(
                        'Hosts.address',
                        $conditions['filter[Hosts.address]'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                $where['Hosts.address LIKE'] = sprintf('%%%s%%', $conditions['filter[Hosts.address]']);
            }
        }

        $where[] = ['Hoststatus.current_state IN' => $conditions['filter[Hoststatus.current_state][]']];
        if ($conditions['filter[Hoststatus.problem_has_been_acknowledged]'] != 'ignore') {
            $where[] = ['Hoststatus.problem_has_been_acknowledged' => $conditions['filter[Hoststatus.problem_has_been_acknowledged]']];
        }
        if ($conditions['filter[Hoststatus.scheduled_downtime_depth]'] === true) {
            $where[] = ['Hoststatus.scheduled_downtime_depth >' => 0];
        }
        if ($conditions['filter[Hoststatus.scheduled_downtime_depth]'] === false) {
            $where[] = ['Hoststatus.scheduled_downtime_depth' => 0];
        }
        if (!empty($conditions['filter[Hosts.keywords][]'])) {
            $compareValue = $conditions['filter[Hosts.keywords][]'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                //implode(',',$conditions['filter[Hosts.keywords][]']),
                $compareValue,
                'string',
                'RLIKE'
            );
        }
        if (!empty($conditions['filter[Hosts.not_keywords][]'])) {
            $compareValue = $conditions['filter[Hosts.not_keywords][]'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                //implode(',',$conditions['filter[Hosts.not_keywords][]']),
                $compareValue,
                'string',
                'NOT RLIKE'
            );
        }

        $query->andWhere($where);
        $query->groupBy('Hosts.id');
        $query->disableHydration();
        $result = $query->all();
        if ($result === null) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * @param $regEx
     * @return bool
     */
    private function isValidRegularExpression($regEx) {
        return @preg_match('`' . $regEx . '`', '') !== false;
    }

    /**
     * @param HostFilter $HostFilter
     * @param HostConditions $HostConditions
     * @return array
     */
    public function getHostStatusGlobalOverview(HostFilter $HostFilter, HostConditions $HostConditions): array {
        $MY_RIGHTS = $HostConditions->getContainerIds();
        $where = $HostFilter->indexFilter();
        $where['Hosts.disabled'] = 0;
        if ($HostConditions->getHostIds()) {
            $hostIds = $HostConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }

            $where['Hosts.id IN'] = $hostIds;
        }
        $query = $this->find();
        $query->select([
            'Hoststatus.current_state',
            'count' => $query->newExpr('COUNT(DISTINCT Hoststatus.hostname)'),
        ])
            ->innerJoin(['Hoststatus' => 'statusengine_hoststatus'], [
                'Hoststatus.hostname = Hosts.uuid'
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }
        if (!empty($where)) {
            $query->andWhere($where);
        }

        $query->disableHydration();
        $query->groupBy(['Hoststatus.current_state']);
        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHostForSlaBrowserTabWithHosttemplateById($id) {
        $query = $this->find()
            ->select([
                'Hosts.id',
                'Hosts.container_id',
                'Hosts.sla_id',
                'Hosttemplates.id',
                'Hosttemplates.sla_id'
            ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'HostsToContainersSharing',
                'Hosttemplates'
            ])
            ->disableHydration()
            ->first();

        $host = $query;
        $host['hosts_to_containers_sharing'] = [
            '_ids' => Hash::extract($query, 'hosts_to_containers_sharing.{n}.id')
        ];
        return $host;
    }

    /**
     * @param $id
     * @return bool
     */
    public function isHostInSla($id): bool {
        $query = $this->find();
        $query->select([
            'is_sla_host' => $query->newExpr('IF(Hosts.sla_id IS NULL, Hosttemplates.sla_id, Hosts.sla_id) > 0'),
        ])
            ->where([
                'Hosts.id' => $id
            ])
            ->contain([
                'Hosttemplates'
            ])
            ->disableHydration();
        $result = $query->first();
        return isset($result['is_sla_host']) && $result['is_sla_host'];
    }

    /**
     * @param array $hostIds
     */
    public function hasSLAHosts($hostIds) {
        if (!is_array($hostIds)) {
            $hostIds = [$hostIds];
        }
        $query = $this->find();

        return $query->select(['Hosts.id'])
            ->contain(['Hosttemplates'])
            ->where([
                'Hosts.id IN' => $hostIds,
                'IF(Hosts.sla_id IS NULL, Hosttemplates.sla_id, Hosts.sla_id) > 0'
            ])->count();
    }

}
