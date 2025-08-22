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

use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Service;
use App\Model\Entity\Servicedependency;
use App\Model\Entity\Serviceescalation;
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
use itnovum\openITCOCKPIT\Core\Comparison\ServiceComparisonForSave;
use itnovum\openITCOCKPIT\Core\ServiceConditions;
use itnovum\openITCOCKPIT\Core\ServicestatusConditions;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Services Model
 *
 * @property \App\Model\Table\ServicetemplatesTable|\Cake\ORM\Association\BelongsTo $Servicetemplates
 * @property \App\Model\Table\HostsTable|\Cake\ORM\Association\BelongsTo $Hosts
 * @property |\Cake\ORM\Association\BelongsTo $Commands
 * @property |\Cake\ORM\Association\BelongsTo $EventhandlerCommands
 * @property |\Cake\ORM\Association\BelongsTo $NotifyPeriods
 * @property |\Cake\ORM\Association\BelongsTo $CheckPeriods
 * @property |\Cake\ORM\Association\HasMany $ContactgroupsToServices
 * @property |\Cake\ORM\Association\HasMany $ContactsToServices
 * @property |\Cake\ORM\Association\HasMany $Eventcorrelations
 * @property |\Cake\ORM\Association\HasMany $GrafanaUserdashboardMetrics
 * @property |\Cake\ORM\Association\HasMany $InstantreportsToServices
 * @property \CheckmkModule\Model\Table\MkservicedataTable|\Cake\ORM\Association\HasMany $Mkservicedata
 * @property |\Cake\ORM\Association\HasMany $NagiosServiceContactgroups
 * @property |\Cake\ORM\Association\HasMany $NagiosServiceContacts
 * @property |\Cake\ORM\Association\HasMany $NagiosServiceParentservices
 * @property |\Cake\ORM\Association\HasMany $NagiosServices
 * @property ServicecommandargumentvaluesTable|\Cake\ORM\Association\HasMany $Servicecommandargumentvalues
 * @property |\Cake\ORM\Association\HasMany $Serviceeventcommandargumentvalues
 * @property |\Cake\ORM\Association\HasMany $ServicesToAutoreports
 * @property |\Cake\ORM\Association\HasMany $ServicesToServicedependencies
 * @property |\Cake\ORM\Association\HasMany $ServicesToServiceescalations
 * @property |\Cake\ORM\Association\HasMany $ServicesToServicegroups
 * @property |\Cake\ORM\Association\HasMany $Widgets
 *
 * @method \App\Model\Entity\Service get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Service newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Service[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Service|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Service saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Service patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Service[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Service findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServicesTable extends Table {

    use CustomValidationTrait;
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

        $this->setTable('services');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Servicetemplates', [
            'foreignKey' => 'servicetemplate_id',
            'joinType'   => 'INNER'
        ]);
        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Contactgroups', [
            'className'        => 'Contactgroups',
            'foreignKey'       => 'service_id',
            'targetForeignKey' => 'contactgroup_id',
            'joinTable'        => 'contactgroups_to_services',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Contacts', [
            'className'        => 'Contacts',
            'foreignKey'       => 'service_id',
            'targetForeignKey' => 'contact_id',
            'joinTable'        => 'contacts_to_services',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Servicegroups', [
            'className'        => 'Servicegroups',
            'foreignKey'       => 'service_id',
            'targetForeignKey' => 'servicegroup_id',
            'joinTable'        => 'services_to_servicegroups',
            'saveStrategy'     => 'replace'
        ]);

        /*
        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);*/

        $this->belongsTo('CheckPeriod', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'check_period_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('NotifyPeriod', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'notify_period_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('CheckCommand', [
            'className'  => 'Commands',
            'foreignKey' => 'command_id',
            'joinType'   => 'INNER'
        ]);

        $this->hasMany('Customvariables', [
            'conditions'   => [
                'objecttype_id' => OBJECT_SERVICE
            ],
            'foreignKey'   => 'object_id',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('Servicecommandargumentvalues', [
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('Serviceeventcommandargumentvalues', [
            'foreignKey'   => 'service_id',
            'saveStrategy' => 'replace'
        ]);

        $this->hasMany('ServiceescalationsServiceMemberships', [
            'foreignKey' => 'service_id'
        ]);

        $this->hasMany('ServicedependenciesServiceMemberships', [
            'foreignKey' => 'service_id'
        ]);

        $this->hasMany('Widgets', [
            'foreignKey' => 'service_id'
        ]);

        $this->belongsToMany('Statuspages', [
            'className'        => 'Statuspages',
            'foreignKey'       => 'service_id',
            'targetForeignKey' => 'statuspage_id',
            'joinTable'        => 'statuspages_to_services',
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
            ->requirePresence('host_id', 'create')
            ->integer('host_id')
            ->allowEmptyString('host_id', null, false);

        $validator
            ->requirePresence('servicetemplate_id', 'create')
            ->integer('servicetemplate_id')
            ->allowEmptyString('servicetemplate_id', null, false);

        $validator
            ->scalar('name')
            ->maxLength('name', 1500)
            ->requirePresence('name', false)
            ->allowEmptyString('name', __('Please enter a service name.'), function ($context) {
                if (array_key_exists('name', $context['data'])) {
                    if ($context['data']['name'] === '') {
                        return false;
                    }

                    if ($context['data']['name'] === null) {
                        return true;
                    }
                }

                return true;
            });


        $validator
            ->integer('priority')
            ->requirePresence('priority', false)
            ->range('priority', [1, 5], __('This value must be between 1 and 5'))
            ->allowEmptyString('priority', null, true);

        $validator
            ->integer('max_check_attempts')
            ->requirePresence('max_check_attempts', false)
            ->greaterThanOrEqual('max_check_attempts', 1, __('This value need to be at least 1'))
            ->allowEmptyString('max_check_attempts', null, true);

        $validator
            ->numeric('notification_interval')
            ->requirePresence('notification_interval', false)
            ->greaterThanOrEqual('notification_interval', 0, __('This value need to be at least 0'))
            ->allowEmptyString('notification_interval', null, true);

        $validator
            ->numeric('check_interval')
            ->requirePresence('check_interval', false)
            ->greaterThanOrEqual('check_interval', 1, __('This value need to be at least 1'))
            ->allowEmptyString('check_interval', null, true);

        $validator
            ->numeric('retry_interval')
            ->requirePresence('retry_interval', false)
            ->greaterThanOrEqual('retry_interval', 1, __('This value need to be at least 1'))
            ->allowEmptyString('retry_interval', null, true);

        $validator
            ->integer('check_period_id')
            ->requirePresence('check_period_id', false)
            ->greaterThan('check_period_id', 0, __('Please select a check period'))
            ->allowEmptyString('check_period_id', null, true);

        $validator
            ->integer('command_id')
            ->requirePresence('command_id', false)
            ->greaterThan('command_id', 0, __('Please select a check command'))
            ->allowEmptyString('command_id', null, true);

        $validator
            ->integer('eventhandler_command_id')
            ->requirePresence('eventhandler_command_id', false)
            ->allowEmptyString('eventhandler_command_id', null, true);

        $validator
            ->integer('notify_period_id')
            ->requirePresence('notify_period_id', false)
            ->greaterThan('notify_period_id', 0, __('Please select a notify period'))
            ->allowEmptyString('notify_period_id', null, true);

        $validator
            ->boolean('notify_on_recovery')
            ->requirePresence('notify_on_recovery', false)
            ->allowEmptyString('notify_on_recovery', null, true)
            ->add('notify_on_recovery', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_warning')
            ->requirePresence('notify_on_warning', false)
            ->allowEmptyString('notify_on_warning', null, true)
            ->add('notify_on_warning', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_critical')
            ->requirePresence('notify_on_critical', false)
            ->allowEmptyString('notify_on_critical', null, true)
            ->add('notify_on_critical', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_unknown')
            ->requirePresence('notify_on_unknown', false)
            ->allowEmptyString('notify_on_unknown', null, true)
            ->add('notify_on_unknown', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_flapping')
            ->requirePresence('notify_on_flapping', false)
            ->allowEmptyString('notify_on_flapping', null, true)
            ->add('notify_on_flapping', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_downtime')
            ->requirePresence('notify_on_downtime', false)
            ->allowEmptyString('notify_on_downtime', null, true)
            ->add('notify_on_downtime', 'custom', [
                'rule'    => [$this, 'checkNotificationOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('flap_detection_enabled')
            ->requirePresence('flap_detection_enabled', 'create')
            ->allowEmptyString('flap_detection_enabled', null, true);

        $validator
            ->allowEmptyString('flap_detection_on_ok', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsService(null, $context);
            })
            ->add('flap_detection_on_ok', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->allowEmptyString('flap_detection_on_warning', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsService(null, $context);
            })
            ->add('flap_detection_on_warning', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->allowEmptyString('flap_detection_on_critical', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsService(null, $context);
            })
            ->add('flap_detection_on_critical', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->allowEmptyString('flap_detection_on_unknown', __('You must specify at least one flap detection option.'), function ($context) {
                return $this->checkFlapDetectionOptionsService(null, $context);
            })
            ->add('flap_detection_on_unknown', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptionsService'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->numeric('low_flap_threshold')
            ->requirePresence('low_flap_threshold', false)
            ->allowEmptyString('low_flap_threshold', null, true);

        $validator
            ->numeric('high_flap_threshold')
            ->requirePresence('high_flap_threshold', false)
            ->allowEmptyString('high_flap_threshold', null, true);

        $validator
            ->boolean('process_performance_data')
            ->requirePresence('process_performance_data', false)
            ->allowEmptyString('process_performance_data', null, true);

        $validator
            ->boolean('passive_checks_enabled')
            ->requirePresence('passive_checks_enabled', false)
            ->allowEmptyString('passive_checks_enabled', null, true);

        $validator
            ->boolean('event_handler_enabled')
            ->requirePresence('event_handler_enabled', false)
            ->allowEmptyString('event_handler_enabled', null, true);

        $validator
            ->boolean('active_checks_enabled')
            ->requirePresence('active_checks_enabled', false)
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
            ->scalar('service_url')
            ->requirePresence('service_url', false)
            ->allowEmptyString('service_url', null, true)
            ->maxLength('service_url', 255);

        $validator
            ->allowEmptyString('customvariables', null, true)
            ->add('customvariables', 'custom', [
                'rule'    => [$this, 'checkMacroNames'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('Macro name needs to be unique')
            ]);

        $validator
            ->boolean('is_volatile')
            ->requirePresence('is_volatile', false)
            ->allowEmptyString('is_volatile', null, true);

        $validator
            ->boolean('freshness_checks_enabled')
            ->requirePresence('freshness_checks_enabled', false)
            ->allowEmptyString('freshness_checks_enabled', null, true);

        $validator
            ->integer('freshness_threshold')
            ->greaterThan('freshness_threshold', 0, __('This field cannot be 0'))
            ->allowEmptyString('freshness_threshold', null, true);

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
        $rules->add($rules->existsIn(['servicetemplate_id'], 'Servicetemplates'));
        $rules->add($rules->existsIn(['host_id'], 'Hosts'));
        $rules->add($rules->existsIn(['command_id'], 'CheckCommand'));
        $rules->add($rules->existsIn(['eventhandler_command_id'], 'CheckCommand'));
        $rules->add($rules->existsIn(['notify_period_id'], 'NotifyPeriod'));
        $rules->add($rules->existsIn(['check_period_id'], 'CheckPeriod'));

        return $rules;
    }

    /**
     * @param int $servicetemplateId
     * @return array
     */
    public function getHostPrimaryContainerIdsByServicetemplateId($servicetemplateId) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Hosts.id',
                'Hosts.container_id',
            ])
            ->contain([
                'Hosts'
            ])
            ->where([
                'Services.servicetemplate_id' => $servicetemplateId
            ])
            ->disableHydration()
            ->all();

        $query = $query->toArray();

        if (empty($query)) {
            return [];
        }

        $result = [];
        foreach ($query as $row) {
            $result[$row['id']] = (int)$row['host']['container_id'];
        }

        return $result;
    }

    /**
     * @param int $servicetemplateId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicesWithHostForServicetemplateUsedBy($servicetemplateId, $MY_RIGHTS = [], $includeDisabled = false) {
        $where = [
            'Services.servicetemplate_id' => $servicetemplateId
        ];
        if ($includeDisabled === false) {
            $where['Services.disabled'] = 0;
        }

        $query = $this->find('all');
        $query->select([
            'Services.id',
            'Services.name',
            'Services.disabled',
            'Services.host_id'
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
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    $query->enableAutoFields(false)
                        ->select([
                            'Servicetemplates.id',
                            'Servicetemplates.name',
                        ]);
                    return $query;
                }
            ])
            ->where($where)
            ->groupBy([
                'Services.id'
            ])
            ->disableHydration();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        return $result;
    }


    /**
     * @param ServiceConditions $ServiceConditions
     * @param array|int $selected
     * @param bool $returnEmptyArrayIfMyRightsIsEmpty
     * @return array|null
     */
    public function getServicesForAngularCake4(ServiceConditions $ServiceConditions, $selected = [], $returnEmptyArrayIfMyRightsIsEmpty = false) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $selected = array_filter($selected);

        if ($returnEmptyArrayIfMyRightsIsEmpty) {
            if (empty($ServiceConditions->getContainerIds())) {
                return [];
            }
        }


        $where = $ServiceConditions->getConditions();

        if (!empty($selected)) {
            $where['NOT'] = [
                'Services.id IN' => $selected
            ];
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }

        $query = $this->find();

        if (isset($where['servicename rlike'])) {
            $query->where(new ComparisonExpression(
                'CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))',
                $where['servicename rlike'],
                'string',
                'rlike'
            ));
            unset($where['servicename rlike']);
        }

        $query
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                return $q->where([
                    'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                ]);
            })
            ->innerJoinWith('Servicetemplates')
            ->select([
                'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                'Services.id',
                'Services.disabled',
                'Hosts.id',
                'Hosts.name'
            ])
            ->where(
                $where
            );
        if (!empty($having)) {
            $query->having($having);
        }
        $query->orderBy([
            'servicename' => 'asc'
        ])
            ->limit(ITN_AJAX_LIMIT)
            ->disableHydration()
            ->all();

        $servicesWithLimit = [];
        $selectedServices = [];
        $results = $this->emptyArrayIfNull($query->toArray());

        foreach ($results as $result) {
            $servicesWithLimit[$result['id']] = $result;
        }

        if (!empty($selected)) {
            $query = $this->find();
            $query
                ->innerJoinWith('Hosts')
                ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                    return $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                })
                ->innerJoinWith('Servicetemplates')
                ->select([
                    'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                    'Services.id',
                    'Services.disabled',
                    'Hosts.id',
                    'Hosts.name'
                ])
                ->where([
                    'Services.id IN' => $selected
                ])
                ->orderBy([
                    'servicename' => 'asc'
                ])
                ->disableHydration()
                ->all();
            $results = $this->emptyArrayIfNull($query->toArray());
            foreach ($results as $result) {
                $selectedServices[$result['id']] = $result;
            }

        }
        $services = $servicesWithLimit + $selectedServices;
        $serviceIds = array_keys($services);

        array_multisort(
            array_column($services, 'servicename'), SORT_ASC, SORT_NATURAL, $services, $serviceIds
        );
        $services = array_combine($serviceIds, $services);
        return $services;
    }


    /**
     * @param ServiceConditions $ServiceConditions
     * @param array|int $selected
     * @return array|null
     */
    public function getServicesForAngular(ServiceConditions $ServiceConditions, $selected = []) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $selected = array_filter($selected);

        $where = $ServiceConditions->getConditions();

        if (!empty($selected)) {
            $where['NOT'] = [
                'Services.id IN' => $selected
            ];
        }

        if ($ServiceConditions->hasNotConditions()) {
            $where['NOT'] = Hash::merge(($where['NOT'] ?? []), $ServiceConditions->getNotConditions());
        }

        if (!empty($where['NOT'])) {
            // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
            $where['NOT'] = [
                'OR' => $where['NOT']
            ];
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        $query = $this->find();

        if (isset($where['servicename rlike'])) {
            $query->where(new ComparisonExpression(
                'CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))',
                $where['servicename rlike'],
                'string',
                'rlike'
            ));
            unset($where['servicename rlike']);
        }


        $query
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                return $q->where([
                    'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                ]);
            })
            ->innerJoinWith('Servicetemplates')
            ->select([
                'Services.id',
                'Services.name',
                'Services.disabled',
                'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                'Hosts.id',
                'Hosts.name',
                'Servicetemplates.name'
            ])
            ->where(
                $where
            )
            ->orderBy([
                'servicename' => 'asc'
            ])
            ->limit(ITN_AJAX_LIMIT);

        if (!empty($having)) {
            $query->having($having);
        }

        $query
            ->disableHydration()
            ->all();

        $servicesWithLimit = [];
        $selectedServices = [];
        $results = $this->emptyArrayIfNull($query->toArray());
        foreach ($results as $result) {
            $servicesWithLimit[$result['id']] = $result;
        }
        if (!empty($selected)) {
            $query = $this->find();
            $query
                ->innerJoinWith('Hosts')
                ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                    return $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                })
                ->innerJoinWith('Servicetemplates')
                ->select([
                    'Services.id',
                    'Services.name',
                    'Services.disabled',
                    'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                    'Hosts.id',
                    'Hosts.name',
                    'Servicetemplates.name'
                ])
                ->where([
                    'Services.id IN' => $selected
                ])
                ->orderBy([
                    'servicename' => 'asc'
                ])
                ->disableHydration()
                ->all();

            $results = $this->emptyArrayIfNull($query->toArray());
            foreach ($results as $result) {
                $selectedServices[$result['id']] = $result;
            }

        }
        $services = $servicesWithLimit + $selectedServices;
        $serviceIds = array_keys($services);

        array_multisort(
            array_column($services, 'servicename'), SORT_ASC, SORT_NATURAL, $services, $serviceIds
        );
        $services = array_combine($serviceIds, $services);
        $serviceFormated = [];
        foreach ($services as $serviceId => $serviceData) {
            $serviceFormated[$serviceId] = [
                'Service'         => [
                    'id'          => $serviceData['id'],
                    'name'        => $serviceData['name'],
                    'servicename' => $serviceData['name'] ?? $serviceData['_matchingData']['Servicetemplates']['name'],
                    'disabled'    => $serviceData['disabled']
                ],
                'Host'            => [
                    'id'   => $serviceData['_matchingData']['Hosts']['id'],
                    'name' => $serviceData['_matchingData']['Hosts']['name']
                ],
                'Servicetemplate' => [
                    'name' => $serviceData['_matchingData']['Servicetemplates']['name']
                ]
            ];
        }
        return $serviceFormated;
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
            'Contact'         => [],
            'Contactgroup'    => [],
            'CheckPeriod'     => [],
            'NotifyPeriod'    => [],
            'CheckCommand'    => [],
            'Servicegroup'    => [],
            'Servicetemplate' => []
        ];


        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var $ServicegroupsTable ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');


        if (!empty($dataToParse['Service']['contacts']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($ContactsTable->getContactsAsList($dataToParse['Service']['contacts']['_ids']) as $contactId => $contactName) {
                    $extDataForChangelog['Contact'][] = [
                        'id'   => $contactId,
                        'name' => $contactName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Service']['contacts']['_ids'] as $contactId) {
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

        if (!empty($dataToParse['Service']['contactgroups']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($ContactgroupsTable->getContactgroupsAsList($dataToParse['Service']['contactgroups']['_ids']) as $contactgroupId => $contactgroupName) {
                    $extDataForChangelog['Contactgroup'][] = [
                        'id'   => $contactgroupId,
                        'name' => $contactgroupName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Service']['contactgroups']['_ids'] as $contactgroupId) {
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

        if (!empty($dataToParse['Service']['check_period_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Service']['check_period_id']) as $timeperiodId => $timeperiodName) {
                    $extDataForChangelog['CheckPeriod'] = [
                        'id'   => $timeperiodId,
                        'name' => $timeperiodName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_TIMEPERIOD, $dataToParse['Service']['check_period_id'])) {
                    foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Service']['check_period_id']) as $timeperiodId => $timeperiodName) {
                        $Cache->set(OBJECT_TIMEPERIOD, $dataToParse['Service']['check_period_id'], [
                            'id'   => $timeperiodId,
                            'name' => $timeperiodName
                        ]);
                    }
                }
                $extDataForChangelog['CheckPeriod'] = $Cache->get(OBJECT_TIMEPERIOD, $dataToParse['Service']['check_period_id']);
            }
        }

        if (!empty($dataToParse['Service']['notify_period_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Service']['notify_period_id']) as $timeperiodId => $timeperiodName) {
                    $extDataForChangelog['NotifyPeriod'] = [
                        'id'   => $timeperiodId,
                        'name' => $timeperiodName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_TIMEPERIOD, $dataToParse['Service']['notify_period_id'])) {
                    foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Service']['notify_period_id']) as $timeperiodId => $timeperiodName) {
                        $Cache->set(OBJECT_TIMEPERIOD, $dataToParse['Service']['notify_period_id'], [
                            'id'   => $timeperiodId,
                            'name' => $timeperiodName
                        ]);
                    }
                }
                $extDataForChangelog['NotifyPeriod'] = $Cache->get(OBJECT_TIMEPERIOD, $dataToParse['Service']['notify_period_id']);
            }
        }

        if (!empty($dataToParse['Service']['command_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($CommandsTable->getCommandByIdAsList($dataToParse['Service']['command_id']) as $commandId => $commandName) {
                    $extDataForChangelog['CheckCommand'] = [
                        'id'   => $commandId,
                        'name' => $commandName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_COMMAND, $dataToParse['Service']['command_id'])) {
                    foreach ($CommandsTable->getCommandByIdAsList($dataToParse['Service']['command_id']) as $commandId => $commandName) {
                        $Cache->set(OBJECT_COMMAND, $dataToParse['Service']['command_id'], [
                            'id'   => $commandId,
                            'name' => $commandName
                        ]);
                    }
                }
                $extDataForChangelog['CheckCommand'] = $Cache->get(OBJECT_COMMAND, $dataToParse['Service']['command_id']);
            }
        }

        if (!empty($dataToParse['Service']['servicegroups']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($ServicegroupsTable->getServicegroupsAsList($dataToParse['Service']['servicegroups']['_ids']) as $servicegroupId => $servicegroupName) {
                    $extDataForChangelog['Servicegroup'][] = [
                        'id'   => $servicegroupId,
                        'name' => $servicegroupName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Service']['servicegroups']['_ids'] as $servicegroupId) {
                    if (!$Cache->has(OBJECT_SERVICEGROUP, $servicegroupId)) {
                        foreach ($ServicegroupsTable->getServicegroupsAsList($servicegroupId) as $servicegroupName) {
                            $Cache->set(OBJECT_SERVICEGROUP, $servicegroupId, [
                                'id'   => $servicegroupId,
                                'name' => $servicegroupName
                            ]);
                        }
                    }
                    $extDataForChangelog['Servicegroup'][] = $Cache->get(OBJECT_SERVICEGROUP, $servicegroupId);
                }
            }
        }

        if (!empty($dataToParse['Service']['servicetemplate_id'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($ServicetemplatesTable->getServicetemplatesAsList($dataToParse['Service']['servicetemplate_id']) as $servicetemplateId => $servicetemplateName) {
                    $extDataForChangelog['Servicetemplate'][] = [
                        'id'   => $servicetemplateId,
                        'name' => $servicetemplateName
                    ];
                }
            } else {
                // Used the passed Cache instance
                if (!$Cache->has(OBJECT_SERVICETEMPLATE, $dataToParse['Service']['servicetemplate_id'])) {
                    foreach ($ServicetemplatesTable->getServicetemplatesAsList($dataToParse['Service']['servicetemplate_id']) as $servicetemplateId => $servicetemplateName) {
                        $Cache->set(OBJECT_SERVICETEMPLATE, $dataToParse['Service']['servicetemplate_id'], [
                            'id'   => $servicetemplateId,
                            'name' => $servicetemplateName
                        ]);
                    }
                }
                $extDataForChangelog['Servicetemplate'][] = $Cache->get(OBJECT_SERVICETEMPLATE, $dataToParse['Service']['servicetemplate_id']);
            }
        }

        return $extDataForChangelog;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Services.id' => $id]);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getServiceForEdit($id) {
        $query = $this->find()
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Contactgroups',
                'Contacts',
                'Servicegroups',
                'Customvariables',
                'Servicecommandargumentvalues'      => [
                    'Commandarguments'
                ],
                'Serviceeventcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->disableHydration()
            ->first();

        $service = $query;
        $service['servicegroups'] = [
            '_ids' => Hash::extract($query, 'servicegroups.{n}.id')
        ];
        $service['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];
        $service['contactgroups'] = [
            '_ids' => Hash::extract($query, 'contactgroups.{n}.id')
        ];

        return [
            'Service' => $service
        ];
    }

    /**
     * @param int $id
     * @param bool $enableHydration
     * @return Service|null
     */
    public function getServiceById($id, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Hosts' => [
                    'HostsToContainersSharing'
                ],
                'Servicetemplates'
            ])
            ->enableHydration($enableHydration)
            ->first();
        return $query;
    }

    /**
     * @param $id
     * @param bool $enableHydration
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getServicesByHostIdForAgent($id, $servicetemplateType = OITC_AGENT_SERVICE, $enableHydration = true) {
        $query = $this->find()
            ->contain([
                'Servicecommandargumentvalues',
                'Servicetemplates' => function (Query $query) use ($servicetemplateType) {
                    $query->contain([
                        //'CheckCommand',
                        'Agentchecks'
                    ])->where([
                        'Servicetemplates.servicetemplatetype_id' => $servicetemplateType
                    ]);
                    return $query;
                }
            ])
            ->where([
                'Services.host_id' => $id,
            ])
            ->enableAutoFields()
            ->enableHydration($enableHydration)
            ->all();
        return $query;
    }

    /**
     * @param $id
     * @param bool $enableHydration
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getActiveServicesByHostId($id, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Services.host_id'  => $id,
                'Services.disabled' => 0
            ])
            ->enableHydration($enableHydration)
            ->all();
        return $query;
    }

    /**
     * @param array $ids
     * @param bool $enableHydration
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getActiveServicesByHostIds(array $ids, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Services.host_id IN' => $ids,
                'Services.disabled'   => 0
            ])
            ->enableHydration($enableHydration)
            ->all();
        return $query;
    }

    /**
     * @param $id
     * @return array|Service|null
     */
    public function getServiceByIdForPermissionsCheck($id) {
        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.name',
                'Services.uuid',
                'Services.servicetemplate_id',

                'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
            ])
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Hosts'            => function (Query $query) {
                    $query->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.name',
                        'Hosts.container_id',
                        'Hosts.satellite_id'
                    ])
                        ->contain([
                            'HostsToContainersSharing'
                        ]);
                    return $query;
                },
                'Servicetemplates' => function (Query $query) {
                    $query->select([
                        'Servicetemplates.id',
                        'Servicetemplates.name',
                    ]);
                    return $query;
                }
            ]);

        return $query->first();
    }

    /**
     * @param int $id
     * @return array|\Cake\Datasource\EntityInterface
     * @throws RecordNotFoundException
     */
    public function getServiceByIdForExternalCommand($id) {
        return $this->find()
            ->select([
                'Services.id',
                'Services.uuid',
            ])
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Hosts' => function (Query $query) {
                    $query->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.satellite_id'
                    ]);
                    return $query;
                }
            ])
            ->firstOrFail();
    }

    /**
     * @param string $uuid
     * @return array|\Cake\Datasource\EntityInterface
     * @throws RecordNotFoundException
     */
    public function getServiceByUuidForExternalCommand($uuid) {
        return $this->find()
            ->select([
                'Services.id',
                'Services.uuid',
            ])
            ->where([
                'Services.uuid' => $uuid
            ])
            ->contain([
                'Hosts' => function (Query $query) {
                    $query->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.satellite_id'
                    ]);
                    return $query;
                }
            ])
            ->firstOrFail();
    }

    /**
     * @param $id
     * @return array|Service|null
     */
    public function getServiceByIdWithHostAndServicetemplate($id) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.name',
                'Services.uuid',
                'Services.servicetemplate_id',
                'Services.service_url',
                'Services.service_type'
            ])
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Hosts'            => function (Query $query) {
                    $query->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.container_id',
                        'Hosts.name',
                        'Hosts.address'
                    ])
                        ->contain([
                            'HostsToContainersSharing'
                        ]);
                    return $query;
                },
                'Servicetemplates' => function (Query $query) {
                    $query->select([
                        'Servicetemplates.id',
                        'Servicetemplates.uuid',
                        'Servicetemplates.name',
                        'Servicetemplates.service_url'
                    ]);
                    return $query;
                }
            ])
            ->first();

        return $query;
    }

    /**
     * @param $hostId
     * @return array
     */
    public function getServicesForExportByHostId($hostId) {
        $where = [
            'Services.host_id'  => $hostId,
            'Services.disabled' => 0
        ];

        $query = $this->find()
            ->where($where)
            ->contain([
                //'Servicetemplates'                  =>
                //    function (Query $q) {
                //        return $q->enableAutoFields(false)->select(['id', 'uuid', 'check_interval', 'command_id']);
                //    },
                'Contactgroups'                     =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid']);
                    },
                'Contacts'                          =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid']);
                    },
                'Servicegroups'                     =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)->select(['id', 'uuid']);
                    },
                'Customvariables',
                'Servicecommandargumentvalues'      => [
                    'Commandarguments'
                ],
                'Serviceeventcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->all();

        return $this->emptyArrayIfNull($query);
    }

    /**
     * @param array $ids
     * @param array $containerIds
     * @return array
     */
    public function getServicesForCopy($ids, $containerIds = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $query = $this->find()
            ->select([
                'Services.id',
                'Services.name',
                'Services.description',
                'Services.command_id',
                'Services.active_checks_enabled'
            ])
            ->contain([
                'Servicetemplates' => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'Servicetemplates.id',
                            'Servicetemplates.name',
                            'Servicetemplates.description',
                            'Servicetemplates.command_id',
                            'Servicetemplates.active_checks_enabled'
                        ])
                        ->contain([
                            'Servicetemplatecommandargumentvalues' => [
                                'Commandarguments'
                            ]
                        ]);
                },
                'Hosts'            => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.name'
                        ]);
                },

                'Servicecommandargumentvalues' => [
                    'Commandarguments'
                ]
            ]);

        $where = [
            'Services.id IN'               => $ids,
            'Services.service_type NOT IN' => [MK_SERVICE]
        ];

        if (!empty($containerIds)) {
            $where['Servicetemplates.container_id IN'] = $containerIds;
        }

        $query
            ->where($where)
            ->orderBy(['Services.id' => 'asc'])
            ->disableHydration()
            ->all();
        $query = $query->toArray();
        if ($query === null) {
            return [];
        }

        $result = [];

        $serviceFields = [
            'id',
            'name',
            'description',
            'command_id',
            'active_checks_enabled'
        ];

        foreach ($query as $service) {
            foreach ($serviceFields as $serviceField) {
                if ($service[$serviceField] === null || $service[$serviceField] === '') {
                    $service[$serviceField] = $service['servicetemplate'][$serviceField];
                }

                //Duplicate the name for front end to display the original service name
                $service['_name'] = $service['name'];
            }

            //Compare service command arguments
            $servicecommandargumentvalues = $service['servicecommandargumentvalues'];
            if (empty($servicecommandargumentvalues)) {
                $servicecommandargumentvalues = $service['servicetemplate']['servicetemplatecommandargumentvalues'];
            }

            if (!empty($servicecommandargumentvalues)) {
                //Remove ids for front end
                foreach ($servicecommandargumentvalues as $index => $servicecommandargumentvalue) {
                    unset($servicecommandargumentvalues[$index]['id']);

                    if (isset($servicecommandargumentvalues[$index]['service_id'])) {
                        unset($servicecommandargumentvalues[$index]['service_id']);
                    }

                    if (isset($servicecommandargumentvalues[$index]['servicetemplate_id'])) {
                        unset($servicecommandargumentvalues[$index]['servicetemplate_id']);
                    }
                }
            }

            $service['servicecommandargumentvalues'] = $servicecommandargumentvalues;

            $result[] = $service;
        }

        return $result;

    }

    /**
     * @param Service $Service
     * @param User $User
     * @return bool
     */
    public function __delete(Service $Service, User $User) {
        $servicename = $Service->get('name');

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if ($servicename === null || $servicename === '') {
            $servicetemplate = $ServicetemplatesTable->get($Service->get('servicetemplate_id'));
            $servicename = $servicetemplate->get('name');
        }

        $servicedependencies = $Service->get('servicedependencies_service_memberships');
        $servicedependenciesToDelete = [];

        if (!empty($servicedependencies)) {
            /** @var $ServicedependenciesTable ServicedependenciesTable */
            $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
            /** @var  $servicedependency Servicedependency */
            foreach ($servicedependencies as $servicedependency) {
                $servicedependencyId = $servicedependency->get('servicedependency_id');
                $servicedependencyIsBroken = $ServicedependenciesTable->isServicedependencyBroken(
                    $servicedependencyId,
                    $Service->get('id')
                );
                if ($servicedependencyIsBroken === true) {
                    $servicedependenciesToDelete[] = $servicedependency;
                }
            }
        }

        $serviceescalations = $Service->get('serviceescalations_service_memberships');
        $serviceescalationsToDelete = [];
        if (!empty($serviceescalations)) {
            /** @var $ServiceescalationsTable ServiceescalationsTable */
            $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');
            /** @var $serviceescalation Serviceescalation */
            foreach ($serviceescalations as $serviceescalation) {
                $serviceescalationId = $serviceescalation->get('serviceescalation_id');
                $serviceescalationIsBroken = $ServiceescalationsTable->isServiceescalationBroken(
                    $serviceescalationId,
                    $Service->get('id')
                );
                if ($serviceescalationIsBroken === true) {
                    $serviceescalationsToDelete[] = $serviceescalation;
                }
            }
        }

        if (!$this->delete($Service)) {
            return false;
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $DocumentationsTable DocumentationsTable */
        $DocumentationsTable = TableRegistry::getTableLocator()->get('Documentations');
        /** @var $DeletedServicesTable DeletedServicesTable */
        $DeletedServicesTable = TableRegistry::getTableLocator()->get('DeletedServices');
        /** @var $ChangelogsTable ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $host = $HostsTable->get($Service->get('host_id'));

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'delete',
            'services',
            $Service->get('id'),
            OBJECT_SERVICE,
            $host->get('container_id'),
            $User->getId(),
            $host->get('name') . '/' . $servicename,
            [
                'Service' => $Service->toArray()
            ]
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        if ($DocumentationsTable->existsByUuid($Service->get('uuid'))) {
            $DocumentationsTable->delete($DocumentationsTable->getDocumentationByUuid($Service->get('uuid')));
        }

        $this->_clenupServiceEscalationAndDependency($servicedependenciesToDelete, $serviceescalationsToDelete);

        //Save service to DeletedServicesTable
        $data = $DeletedServicesTable->newEntity([
            'uuid'               => $Service->get('uuid'),
            'host_uuid'          => $host->get('uuid'),
            'servicetemplate_id' => $Service->get('servicetemplate_id'),
            'host_id'            => $Service->get('host_id'),
            'name'               => $servicename,
            'description'        => $Service->get('description'),
            'deleted_perfdata'   => 0
        ]);
        $DeletedServicesTable->save($data);

        return true;
    }

    /**
     * @param string $uuid
     * @param bool $enableHydration
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getServiceByUuid($uuid, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Services.uuid' => $uuid
            ])
            ->contain([
                'Hosts' => [
                    'HostsToContainersSharing'
                ],
                'Servicetemplates'

            ])
            ->enableHydration($enableHydration)
            ->firstOrFail();
        return $query;
    }

    public function getServiceUuidsOfHostByHostId($hostId) {
        $query = $this->find('list',
            keyField: 'id',
            valueField: 'uuid')
            ->where([
                'Services.host_id' => $hostId
            ])
            ->disableHydration();
        return $query->toArray();
    }

    /**
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getServicesForDisabled(ServiceConditions $ServiceConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();
        $where['Services.disabled'] = 1;

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }
        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.disabled',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })->innerJoinWith('Servicetemplates');

        if (isset($where['servicename rlike'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                $where['servicename rlike'],
                'string',
                'rlike'
            ));
            unset($where['servicename rlike']);
        }

        if (!empty($where)) {
            $query->where($where);
        }

        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )
        );

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
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getServiceNotMonitored(ServiceConditions $ServiceConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();
        $where['Services.disabled'] = 0;

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.disabled',
                'Services.active_checks_enabled',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',
                'Servicetemplates.active_checks_enabled',

                'Objects.object_id',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->leftJoin(['Objects' => 'nagios_objects'], [
                'Objects.name2 = Services.uuid',
                'Objects.objecttype_id' => 2
            ])
            ->whereNull('Objects.object_id');

        if (!empty($where)) {
            $query->where($where);
        }

        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }
        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )
        );

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
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getServiceNotMonitoredStatusengine3(ServiceConditions $ServiceConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();
        $where['Services.disabled'] = 0;

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.service_type',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',
                'Servicetemplates.active_checks_enabled',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->leftJoin(['Servicestatus' => 'statusengine_servicestatus'], [
                'Servicestatus.service_description = Services.uuid'
            ])
            ->whereNull('Servicestatus.service_description');

        if (isset($where['servicename rlike'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                $where['servicename rlike'],
                'string',
                'rlike'
            ));
            unset($where['servicename rlike']);
        }

        if (!empty($where)) {
            $query->where($where);
        }

        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )
        );

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
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getServiceIndex(ServiceConditions $ServiceConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();

        $where['Services.disabled'] = 0;
        if ($ServiceConditions->getServiceIds()) {
            $serviceIds = $ServiceConditions->getServiceIds();
            if (!is_array($serviceIds)) {
                $serviceIds = [$serviceIds];
            }

            $where['Services.id IN'] = $serviceIds;
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.description',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.tags',
                'Services.priority',
                'Services.service_type',
                'servicename'        => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
                'servicepriority'    => $query->newExpr('IF(Services.priority IS NULL, Servicetemplates.priority, Services.priority)'),
                'servicedescription' => $query->newExpr('IF(Services.description IS NULL, Servicetemplates.description, Services.description)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',
                'Servicetemplates.description',
                'Servicetemplates.active_checks_enabled',
                'Servicetemplates.tags',
                'Servicetemplates.priority',

                'Objects.object_id',

                'Servicestatus.current_state',
                'Servicestatus.last_check',
                'Servicestatus.next_check',
                'Servicestatus.last_hard_state_change',
                'Servicestatus.last_state_change',
                'Servicestatus.output',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.state_type',
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.acknowledgement_type',
                'Servicestatus.is_flapping',
                'Servicestatus.perfdata',
                'Servicestatus.notifications_enabled',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
                'Hosts.notes'
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(['Objects' => 'nagios_objects'], [
                'Objects.name2 = Services.uuid',
                'Objects.objecttype_id' => 2
            ])
            ->innerJoin(['Servicestatus' => 'nagios_servicestatus'], [
                'Servicestatus.service_object_id = Objects.object_id',
            ]);

        if (isset($where['keywords rlike'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['keywords rlike'],
                'string',
                'rlike'
            ));
            unset($where['keywords rlike']);
        }

        if (isset($where['not_keywords not rlike'])) {
            $query->andWhere(new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['not_keywords not rlike'],
                'string',
                'not rlike'
            ));
            unset($where['not_keywords not rlike']);
        }

        if (isset($where['servicepriority IN'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.priority IS NULL), Servicetemplates.priority, Services.priority)',
                $where['servicepriority IN'],
                'integer[]',
                'IN'
            );
            unset($where['servicepriority IN']);
        }

        if (isset($where['servicedescription LIKE'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.description IS NULL), Servicetemplates.description, Services.description)',
                $where['servicedescription LIKE'],
                'string',
                'like'
            );
            unset($where['servicedescription LIKE']);
        }

        if (!empty($where['Servicestatus.state_older_than >']) && is_numeric($where['Servicestatus.state_older_than >']) && $where['Servicestatus.state_older_than >'] > 0) {
            $intervalUnit = 'MINUTE';
            if (in_array($where['Servicestatus.state_older_than_unit >'], ['SECOND', 'MINUTE', 'HOUR', 'DAY'], true)) {
                $intervalUnit = $where['Servicestatus.state_older_than_unit >'];
            }
            $query->where([
                sprintf('Servicestatus.last_state_change <= UNIX_TIMESTAMP(DATE(NOW() - INTERVAL %s %s))',
                    $where['Servicestatus.state_older_than >'],
                    $intervalUnit
                )
            ]);

            unset($where['Servicestatus.state_older_than >']);
        }

        unset($where['Servicestatus.state_older_than_unit >']);

        if (!empty($where)) {
            $query->andWhere($where);
        }


        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )
        );

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
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getServiceIndexStatusengine3(ServiceConditions $ServiceConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();
        $where['Services.disabled'] = 0;
        if ($ServiceConditions->getServiceIds()) {
            $serviceIds = $ServiceConditions->getServiceIds();
            if (!is_array($serviceIds)) {
                $serviceIds = [$serviceIds];
            }

            $where['Services.id IN'] = $serviceIds;
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }
        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.description',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.tags',
                'Services.priority',
                'Services.service_type',
                'servicename'        => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
                'servicepriority'    => $query->newExpr('IF(Services.priority IS NULL, Servicetemplates.priority, Services.priority)'),
                'servicedescription' => $query->newExpr('IF(Services.description IS NULL, Servicetemplates.description, Services.description)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',
                'Servicetemplates.description',
                'Servicetemplates.active_checks_enabled',
                'Servicetemplates.tags',
                'Servicetemplates.priority',

                'Servicestatus.current_state',
                'Servicestatus.last_check',
                'Servicestatus.next_check',
                'Servicestatus.last_hard_state_change',
                'Servicestatus.last_state_change',
                'Servicestatus.output',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.is_hardstate',
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.acknowledgement_type',
                'Servicestatus.is_flapping',
                'Servicestatus.perfdata',
                'Servicestatus.notifications_enabled',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
                'Hosts.satellite_id',
                'Hosts.notes'
            ])
            ->innerJoin(['Hosts' => 'hosts'], [
                'Hosts.id = Services.host_id',
            ])
            ->innerJoin(['Hosttemplates' => 'hosttemplates'], [
                'Hosttemplates.id = Hosts.hosttemplate_id',
            ])
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(['Servicestatus' => 'statusengine_servicestatus'], [
                'Servicestatus.service_description = Services.uuid'
            ]);

        if (isset($where['servicename rlike'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                $where['servicename rlike'],
                'string',
                'rlike'
            ));
            unset($where['servicename rlike']);
        }
        if (isset($where['keywords rlike'])) {
            $compareValue = $where['keywords rlike'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $compareValue,
                'string',
                'rlike'
            );
            unset($where['keywords rlike']);
        }

        if (isset($where['not_keywords not rlike'])) {
            $compareValue = $where['not_keywords not rlike'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $compareValue,
                'string',
                'not rlike'
            );
            unset($where['not_keywords not rlike']);
        }

        if (isset($where['Hosts.keywords rlike'])) {
            $compareValue = $where['Hosts.keywords rlike'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $compareValue,
                'string',
                'rlike'
            );
            unset($where['Hosts.keywords rlike']);
        }

        if (isset($where['Hosts.not_keywords not rlike'])) {
            $compareValue = $where['Hosts.not_keywords not rlike'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Hosts.tags IS NULL OR Hosts.tags=""), Hosttemplates.tags, Hosts.tags)',
                $compareValue,
                'string',
                'not rlike'
            );
            unset($where['Hosts.not_keywords not rlike']);
        }

        if (isset($where['servicepriority IN'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.priority IS NULL), Servicetemplates.priority, Services.priority)',
                $where['servicepriority IN'],
                'integer[]',
                'IN'
            );
            unset($where['servicepriority IN']);
        }

        if (isset($where['servicedescription LIKE'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.description IS NULL), Servicetemplates.description, Services.description)',
                $where['servicedescription LIKE'],
                'string',
                'like'
            );
            unset($where['servicedescription LIKE']);
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }


        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )
        );

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
     * @param array $ids
     * @param bool $prefixWithHostname
     * @return array
     */
    public function getServicesAsList($ids = [], $prefixWithHostname = false) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
                'Hosts.name'
            ])
            ->innerJoinWith('Servicetemplates')
            ->innerJoinWith('Hosts')
            ->disableHydration();
        if (!empty($ids)) {
            $query->where([
                'Services.id IN' => $ids
            ]);
        }

        $records = $query->toArray();

        if (empty($records) || is_null($records)) {
            return [];
        }

        $result = [];
        foreach ($records as $row) {
            if ($prefixWithHostname === true) {
                $result[$row['id']] = $row['_matchingData']['Hosts']['name'] . '/' . $row['servicename'];
            } else {
                $result[$row['id']] = $row['servicename'];
            }
        }

        return $result;
    }

    /**
     * Check if the service was part of an serviceescalation or servicedependency
     * If yes, cake delete the records by it self, but may be we have an empty serviceescalation or servicegroup now.
     * Nagios don't relay like this so we need to check this and delete the service escalation or service dependency if empty
     *
     * @param array $servicedependenciesMembershipToDelete
     * @param array $serviceescalationsMembershipToDelete
     */
    public function _clenupServiceEscalationAndDependency($servicedependenciesMembershipToDelete = [], $serviceescalationsMembershipToDelete = []) {
        if (!empty($servicedependenciesMembershipToDelete)) {
            /** @var $ServicedependenciesTable ServicedependenciesTable */
            $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
            foreach ($servicedependenciesMembershipToDelete as $servicedependencyMembership) {
                if ($ServicedependenciesTable->existsById($servicedependencyMembership->get('servicedependency_id'))) {
                    $servicedependency = $ServicedependenciesTable->get($servicedependencyMembership->get('servicedependency_id'));
                    $ServicedependenciesTable->delete($servicedependency);
                }
            }
        }

        if (!empty($serviceescalationsMembershipToDelete)) {
            /* @var $ServiceescalationsTable ServiceescalationsTable */
            $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');
            foreach ($serviceescalationsMembershipToDelete as $serviceescalationMembership) {
                if ($ServiceescalationsTable->existsById($serviceescalationMembership->get('serviceescalation_id'))) {
                    $serviceescalation = $ServiceescalationsTable->get($serviceescalationMembership->get('serviceescalation_id'));
                    $ServiceescalationsTable->delete($serviceescalation);
                }
            }
        }
    }

    /**
     * @param int $commandId
     * @return bool
     */
    public function isCommandUsedByService($commandId) {
        $count = $this->find()
            ->where([
                'Services.command_id' => $commandId,
            ])->count();

        if ($count > 0) {
            return true;
        }

        $count = $this->find()
            ->where([
                'Services.eventhandler_command_id' => $commandId,
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
    public function getServicesByCommandId($commandId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find();
        $query->select([
            'Services.id',
            'Services.name',
            'Services.uuid',
            'Servicetemplates.id',
            'Servicetemplates.name',
            'Servicetemplates.uuid',
            'Hosts.id',
            'Hosts.name',
            'Hosts.uuid',
            'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
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
            ->innerJoinWith('Servicetemplates')
            ->where([
                'OR' => [
                    ['Services.command_id' => $commandId],
                    ['Services.eventhandler_command_id' => $commandId]
                ]
            ])
            ->enableHydration($enableHydration)
            ->orderBy([
                'Hosts.name'  => 'asc',
                'servicename' => 'asc',
                'Services.id' => 'asc'
            ])
            ->groupBy(['Services.id'])
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int $contactId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getServicesByContactId($contactId, $MY_RIGHTS = [], $enableHydration = true) {

        /** @var ContactsToServicesTable $ContactsToServicesTable */
        $ContactsToServicesTable = TableRegistry::getTableLocator()->get('ContactsToServices');

        $query = $ContactsToServicesTable->find()
            ->select([
                'service_id'
            ])
            ->where([
                'contact_id' => $contactId
            ])
            ->groupBy([
                'service_id'
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $serviceIds = Hash::extract($result, '{n}.service_id');

        $query = $this->find('all');
        $query->where([
            'Services.id IN' => $serviceIds
        ]);
        $query->select([
            'Services.id',
            'Services.uuid',
            'Services.name',
            'Services.host_id',

            'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

            'Servicetemplates.id',
            'Servicetemplates.uuid',
            'Servicetemplates.name',

            'Hosts.name',
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.description',
            'Hosts.address',
            'Hosts.disabled',
        ]);
        $query
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                    ]);
                }
                return $q;
            })
            ->contain([
                'Servicetemplates'
            ]);


        $query->enableHydration($enableHydration);
        $query->orderBy([
            'servicename' => 'asc',
            'Services.id' => 'asc'
        ]);
        $query->groupBy([
            'Services.id'
        ]);

        $result = $query->all();

        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @param ServiceConditions $ServiceConditions
     * @param ServicestatusConditions $ServicestatusConditions
     * @param null $PaginateOMat
     * @return array
     */
    public function getServiceForCurrentReport(ServiceConditions $ServiceConditions, ServicestatusConditions $ServicestatusConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();

        $where['Services.disabled'] = 0;
        if ($ServiceConditions->getServiceIds()) {
            $serviceIds = $ServiceConditions->getServiceIds();
            if (!is_array($serviceIds)) {
                $serviceIds = [$serviceIds];
            }

            $where['Services.id IN'] = $serviceIds;
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.description',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.service_type',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.name',
                'Servicetemplates.active_checks_enabled',

                'Objects.object_id',

                'Servicestatus.current_state',
                'Servicestatus.last_check',
                'Servicestatus.next_check',
                'Servicestatus.last_hard_state_change',
                'Servicestatus.last_state_change',
                'Servicestatus.output',
                'Servicestatus.perfdata',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.state_type',
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.acknowledgement_type',
                'Servicestatus.is_flapping',
                'Servicestatus.current_check_attempt',
                'Servicestatus.max_check_attempts',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(['Objects' => 'nagios_objects'], [
                'Objects.name2 = Services.uuid',
                'Objects.objecttype_id' => 2
            ])
            ->innerJoin(['Servicestatus' => 'nagios_servicestatus'], [
                'Servicestatus.service_object_id = Objects.object_id',
            ]);

        if (isset($where['keywords rlike'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['keywords rlike'],
                'string',
                'rlike'
            ));
            unset($where['keywords rlike']);
        }

        if (isset($where['not_keywords not rlike'])) {
            $query->andWhere(new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['not_keywords not rlike'],
                'string',
                'not rlike'
            ));
            unset($where['not_keywords not rlike']);
        }

        if ($ServicestatusConditions->hasConditions()) {
            $whereServicestatusConditions = $ServicestatusConditions->getConditions();
            if (isset($whereServicestatusConditions['Servicestatus.current_state'])) {
                $query->andWhere([
                    'Servicestatus.current_state IN ' => array_values($whereServicestatusConditions['Servicestatus.current_state'])
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.scheduled_downtime_depth'])) {
                $query->andWhere([
                    'Servicestatus.scheduled_downtime_depth' => 0
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.scheduled_downtime_depth > '])) {
                $query->andWhere([
                    'Servicestatus.scheduled_downtime_depth > ' => 0
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.problem_has_been_acknowledged'])) {
                $query->andWhere([
                    'Servicestatus.problem_has_been_acknowledged' => $whereServicestatusConditions['Servicestatus.problem_has_been_acknowledged']
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.active_checks_enabled'])) {
                $query->andWhere([
                    'Servicestatus.active_checks_enabled' => $whereServicestatusConditions['Servicestatus.active_checks_enabled']
                ]);
            }
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }

        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )

        );

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
     * @param ServiceConditions $ServiceConditions
     * @param ServicestatusConditions $ServicestatusConditions
     * @param null $PaginateOMat
     * @return array
     */
    public function getServiceForCurrentReportStatusengine3(ServiceConditions $ServiceConditions, ServicestatusConditions $ServicestatusConditions, $PaginateOMat = null) {
        $where = $ServiceConditions->getConditions();

        $where['Services.disabled'] = 0;
        if ($ServiceConditions->getServiceIds()) {
            $serviceIds = $ServiceConditions->getServiceIds();
            if (!is_array($serviceIds)) {
                $serviceIds = [$serviceIds];
            }

            $where['Services.id IN'] = $serviceIds;
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }


        if ($ServiceConditions->getHostId()) {
            $where['Services.host_id'] = $ServiceConditions->getHostId();
        }

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.description',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.service_type',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.name',
                'Servicetemplates.active_checks_enabled',

                'Servicestatus.current_state',
                'Servicestatus.last_check',
                'Servicestatus.next_check',
                'Servicestatus.last_hard_state_change',
                'Servicestatus.last_state_change',
                'Servicestatus.output',
                'Servicestatus.perfdata',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.is_hardstate',
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.acknowledgement_type',
                'Servicestatus.is_flapping',
                'Servicestatus.current_check_attempt',
                'Servicestatus.max_check_attempts',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(['Servicestatus' => 'statusengine_servicestatus'], [
                'Servicestatus.service_description = Services.uuid'
            ]);

        if (isset($where['keywords rlike'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['keywords rlike'],
                'string',
                'rlike'
            ));
            unset($where['keywords rlike']);
        }

        if (isset($where['not_keywords not rlike'])) {
            $query->andWhere(new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['not_keywords not rlike'],
                'string',
                'not rlike'
            ));
            unset($where['not_keywords not rlike']);
        }

        if ($ServicestatusConditions->hasConditions()) {
            $whereServicestatusConditions = $ServicestatusConditions->getConditions();

            if (isset($whereServicestatusConditions['Servicestatus.current_state IN'])) {
                $query->andWhere([
                    'Servicestatus.current_state IN ' => array_values($whereServicestatusConditions['Servicestatus.current_state IN'])
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.scheduled_downtime_depth'])) {
                $query->andWhere([
                    'Servicestatus.scheduled_downtime_depth' => 0
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.scheduled_downtime_depth >'])) {
                $query->andWhere([
                    'Servicestatus.scheduled_downtime_depth > ' => 0
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.problem_has_been_acknowledged'])) {
                $query->andWhere([
                    'Servicestatus.problem_has_been_acknowledged' => $whereServicestatusConditions['Servicestatus.problem_has_been_acknowledged']
                ]);
            }
            if (isset($whereServicestatusConditions['Servicestatus.active_checks_enabled'])) {
                $query->andWhere([
                    'Servicestatus.active_checks_enabled' => $whereServicestatusConditions['Servicestatus.active_checks_enabled']
                ]);
            }
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }

        $query->disableHydration();
        $query->groupBy(['Services.id']);

        if (!empty($having)) {
            $query->having($having);
        }

        $query->orderBy(
            array_merge(
                $ServiceConditions->getOrder(),
                ['Services.id' => 'asc']
            )
        );

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

    public function getAllOitcAgentServicesByHostIdForExport($hostId) {
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $query = $this->find()
            ->select([
                'Services.id',
                'Services.name',
                'Services.servicetemplate_id',
                'Services.uuid',

                'Servicetemplates.id',
                'Servicetemplates.name',
                'Servicetemplates.uuid',

                'Agentchecks.id',
                'Agentchecks.name',
                'Agentchecks.servicetemplate_id',
                'Agentchecks.plugin_name'
            ])
            ->contain([
                'Servicetemplates'             => [
                    'Agentchecks',
                    'Servicetemplatecommandargumentvalues' => [
                        'Commandarguments'
                    ]
                ],
                'Servicecommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->where([
                'Services.host_id'      => $hostId,
                'Services.service_type' => OITC_AGENT_SERVICE
            ])
            ->disableHydration()
            ->all();

        $services = $this->emptyArrayIfNull($query->toArray());


        foreach ($services as $index => $service) {
            if (!empty($service['servicecommandargumentvalues'])) {
                //Arguments from service
                $commandArgumentValuesDiff = array_diff(
                    Hash::extract($service['servicetemplate']['servicetemplatecommandargumentvalues'], '{n}.commandargument_id'),
                    Hash::extract($service['servicecommandargumentvalues'], '{n}.commandargument_id')
                );
                if (!empty($commandArgumentValuesDiff)) {
                    //add missing (new) command argument values from service template to service command argument values
                    foreach ($commandArgumentValuesDiff as $commandArgumentValueId) {
                        $key = array_search(
                            $commandArgumentValueId,
                            array_column($service['servicetemplate']['servicetemplatecommandargumentvalues'], 'commandargument_id'), true
                        );
                        $service['servicecommandargumentvalues'][] = $service['servicetemplate']['servicetemplatecommandargumentvalues'][$key];
                    }
                }
                $servicecommandargumentvalues = $service['servicecommandargumentvalues'];
            } else {
                //Use arguments from service template
                $servicecommandargumentvalues = $service['servicetemplate']['servicetemplatecommandargumentvalues'];
            }

            $servicecommandargumentvalues = Hash::sort($servicecommandargumentvalues, '{n}.commandargument.name', 'asc', 'natural');
            $servicecommandargumentvalues = Hash::extract($servicecommandargumentvalues, '{n}.value');

            $services[$index]['args_for_config'] = $servicecommandargumentvalues;
        }

        return $services;
    }

    /**
     * @param bool $includeDisabled
     * @return int|null
     */
    public function getServicesCountForStats($includeDisabled = true) {
        $query = $this->find();
        if ($includeDisabled === false) {
            $query->where([
                'Services.disabled' => 0
            ]);
        }

        return $query->count();
    }

    public function getServiceByIdForDowntimeCreation($id) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.uuid'
            ])
            ->where([
                'Services.id' => $id,
            ])
            ->contain([
                'Hosts' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.name',
                            'Hosts.uuid'
                        ]);
                    return $query;
                }
            ])
            ->first();
        return $query;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getServiceForBrowser($id) {
        $query = $this->find()
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Contactgroups'                     => [
                    'Containers'
                ],
                'Contacts'                          => [
                    'Containers'
                ],
                'Servicegroups',
                'Customvariables',
                'Servicecommandargumentvalues'      => [
                    'Commandarguments'
                ],
                'Serviceeventcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->disableHydration()
            ->first();

        $service = $query;

        return $service;
    }

    /**
     * @param $id
     * @return array|Service|null
     */
    public function getServiceByIdForTimeline($id) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.name',
                'Services.uuid',
                'Services.servicetemplate_id',
                'Services.check_period_id',
                'Services.notify_period_id',
            ])
            ->where([
                'Services.id' => $id
            ])
            ->contain([
                'Hosts'            => function (Query $query) {
                    $query->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.container_id'
                    ])
                        ->contain([
                            'HostsToContainersSharing'
                        ]);
                    return $query;
                },
                'Servicetemplates' => function (Query $query) {
                    $query->select([
                        'Servicetemplates.id',
                        'Servicetemplates.check_period_id',
                        'Servicetemplates.notify_period_id'
                    ]);
                    return $query;
                }
            ])
            ->first();

        return $query;
    }

    /**
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @param string $type (all or count, list is NOT supported!)
     * @return int|array
     */
    public function getServiceWithStatusByRegularExpressionStatusengine2(ServiceConditions $ServiceConditions, $PaginateOMat = null, $type = 'all') {
        $where = [
            'Hosts.disabled'    => 0,
            'Hosts.name REGEXP' => $ServiceConditions->getHostnameRegex(),
            'Services.disabled' => 0
        ];
        $where = Hash::merge($where, $ServiceConditions->getConditions());

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.description',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.tags',
                'Services.priority',
                'Services.service_type',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',
                'Servicetemplates.description',
                'Servicetemplates.active_checks_enabled',

                'Objects.object_id',

                'Servicestatus.current_state',
                'Servicestatus.last_check',
                'Servicestatus.next_check',
                'Servicestatus.last_hard_state_change',
                'Servicestatus.last_state_change',
                'Servicestatus.output',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.state_type',
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.acknowledgement_type',
                'Servicestatus.is_flapping',
                'Servicestatus.perfdata',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(['Objects' => 'nagios_objects'], [
                'Objects.name2 = Services.uuid',
                'Objects.objecttype_id' => 2
            ])
            ->innerJoin(['Servicestatus' => 'nagios_servicestatus'], [
                'Servicestatus.service_object_id = Objects.object_id',
            ]);

        $query->where($where);
        $query->having([
            'servicename REGEXP' => $ServiceConditions->getServicenameRegex()
        ]);

        $hostIdsFromServiceCondition = [];
        if (!empty($ServiceConditions->getHostId())) {
            $hostIdsFromServiceCondition[$ServiceConditions->getHostId()] = $ServiceConditions->getHostId();
        }
        if (!empty($ServiceConditions->getHostIds())) {
            $hostIds = $ServiceConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }
            $hostIdsFromServiceCondition = array_merge(
                $hostIdsFromServiceCondition,
                $hostIds
            );
        }

        if (!empty($hostIdsFromServiceCondition)) {
            $query->where(['Hosts.id IN' => $hostIdsFromServiceCondition]);
        }

        if ($type === 'all') {
            $query->orderBy([
                'servicename' => 'asc',
                'Services.id' => 'asc'
            ]);
        }

        $query->groupBy([
            'Services.id'
        ]);

        if ($type === 'count') {
            return $query->count();
        }


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
     * @param ServiceConditions $ServiceConditions
     * @param null|PaginateOMat $PaginateOMat
     * @param string $type (all or count, list is NOT supported!)
     * @return int|array
     */
    public function getServiceWithStatusByRegularExpressionStatusengine3(ServiceConditions $ServiceConditions, $PaginateOMat = null, $type = 'all') {
        $where = [
            'Hosts.disabled'    => 0,
            'Hosts.name REGEXP' => $ServiceConditions->getHostnameRegex(),
            'Services.disabled' => 0
        ];
        $where = Hash::merge($where, $ServiceConditions->getConditions());

        $query = $this->find();
        $query
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.name',
                'Services.host_id',
                'Services.description',
                'Services.disabled',
                'Services.active_checks_enabled',
                'Services.service_type',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

                'Servicetemplates.id',
                'Servicetemplates.uuid',
                'Servicetemplates.name',
                'Servicetemplates.description',
                'Servicetemplates.active_checks_enabled',

                'Servicestatus.current_state',
                'Servicestatus.last_check',
                'Servicestatus.next_check',
                'Servicestatus.last_hard_state_change',
                'Servicestatus.last_state_change',
                'Servicestatus.output',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.is_hardstate',
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.acknowledgement_type',
                'Servicestatus.is_flapping',
                'Servicestatus.perfdata',

                'Hosts.name',
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.description',
                'Hosts.address',
                'Hosts.disabled',
            ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(['Servicestatus' => 'statusengine_servicestatus'], [
                'Servicestatus.service_description = Services.uuid'
            ]);

        $query->where($where);
        $query->having([
            'servicename REGEXP' => $ServiceConditions->getServicenameRegex()
        ]);

        $hostIdsFromServiceCondition = [];
        if (!empty($ServiceConditions->getHostId())) {
            $hostIdsFromServiceCondition[$ServiceConditions->getHostId()] = $ServiceConditions->getHostId();
        }
        if (!empty($ServiceConditions->getHostIds())) {
            $hostIds = $ServiceConditions->getHostIds();
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }
            $hostIdsFromServiceCondition = array_merge(
                $hostIdsFromServiceCondition,
                $hostIds
            );
        }
        if (!empty($hostIdsFromServiceCondition)) {
            $query->where(['Hosts.id IN' => $hostIdsFromServiceCondition]);
        }
        if ($type === 'all') {
            $query->orderBy([
                'Hosts.name'  => 'asc',
                'servicename' => 'asc',
                'Services.id' => 'asc'
            ]);
        }

        $query->groupBy([
            'Services.id'
        ]);

        if ($type === 'count') {
            return $query->count();
        }

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
     * @param array $MY_RIGHTS
     * @param array $conditions
     * @return int
     */
    public function getServicestatusCountBySelectedStatus($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'count' => $query->newExpr('COUNT(DISTINCT Servicestatus.service_object_id)')
            ])
            ->where([
                'Services.disabled'       => 0,
                'ServiceObject.is_active' => 1,
            ])
            ->join([
                'a' => [
                    'table'      => 'nagios_objects',
                    'type'       => 'INNER',
                    'alias'      => 'ServiceObject',
                    'conditions' => 'ServiceObject.name2 = Services.uuid',
                ],
                'b' => [
                    'table'      => 'nagios_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_object_id = ServiceObject.object_id',
                ],
            ])
            ->innerJoinWith('Servicetemplates')
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                return $q->where([
                    'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                ]);
            })
            ->groupBy([
                'Servicestatus.current_state',
            ])
            ->disableHydration();

        $where = [];
        if (!empty($conditions['Host']['name'])) {
            $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
        }
        if (!empty($conditions['Service']['name'])) {
            $query->andWhere(
                $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name) LIKE :servicename')
            );
            $query->bind(':servicename', sprintf('%%%s%%', $conditions['Service']['name']));
        }


        $where['Servicestatus.current_state'] = $conditions['Servicestatus']['current_state'];

        if ($where['Servicestatus.current_state'] > 0) {
            if ($conditions['Servicestatus']['acknowledged'] ^ $conditions['Servicestatus']['not_acknowledged']) {
                $hasBeenAcknowledged = (int)($conditions['Servicestatus']['acknowledged'] === true);
                $where['Servicestatus.problem_has_been_acknowledged'] = $hasBeenAcknowledged;
            }

            if ($conditions['Servicestatus']['in_downtime'] ^ $conditions['Servicestatus']['not_in_downtime']) {
                $inDowntime = $conditions['Servicestatus']['in_downtime'] === true;
                if ($inDowntime === false) {
                    $where['Servicestatus.scheduled_downtime_depth'] = 0;
                } else {
                    $where['Servicestatus.scheduled_downtime_depth > '] = 0;
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
    public function getServicestatusCountBySelectedStatusStatusengine3($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'count' => $query->newExpr('COUNT(DISTINCT Servicestatus.service_description)')
            ])
            ->where([
                'Services.disabled' => 0
            ])
            ->join([
                'b' => [
                    'table'      => 'statusengine_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_description = Services.uuid',
                ],
            ])
            ->innerJoinWith('Servicetemplates')
            ->innerJoinWith('Hosts');
        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                return $q->where([
                    'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                ]);
            });
        }
        $query->groupBy([
            'Servicestatus.current_state',
        ])
            ->disableHydration();

        $where = [];
        if (!empty($conditions['Host']['name'])) {
            $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
        }
        if (!empty($conditions['Service']['name'])) {
            $query->andWhere(
                $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name) LIKE :servicename')
            );
            $query->bind(':servicename', sprintf('%%%s%%', $conditions['Service']['name']));
        }


        $where['Servicestatus.current_state'] = $conditions['Servicestatus']['current_state'];

        if ($where['Servicestatus.current_state'] > 0) {
            if ($conditions['Servicestatus']['acknowledged'] ^ $conditions['Servicestatus']['not_acknowledged']) {
                $hasBeenAcknowledged = (int)($conditions['Servicestatus']['acknowledged'] === true);
                $where['Servicestatus.problem_has_been_acknowledged'] = $hasBeenAcknowledged;
            }

            if ($conditions['Servicestatus']['in_downtime'] ^ $conditions['Servicestatus']['not_in_downtime']) {
                $inDowntime = $conditions['Servicestatus']['in_downtime'] === true;
                if ($inDowntime === false) {
                    $where['Servicestatus.scheduled_downtime_depth'] = 0;
                } else {
                    $where['Servicestatus.scheduled_downtime_depth > '] = 0;
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
    public function getServiceIdsBySelectedStatusExtendedStatusengine3($MY_RIGHTS, $conditions) {

        $query = $this->find();
        $query
            ->select([
                'Services.id'
            ])
            ->innerJoinWith('Servicetemplates')
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Services.host_id = Hosts.id'])
            ->innerJoin(
                ['Hosttemplates' => 'hosttemplates'],
                ['Hosts.hosttemplate_id = Hosttemplates.id'])
            ->join([
                'b' => [
                    'table'      => 'statusengine_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_description = Services.uuid',
                ]
            ])
            ->where([
                'Services.disabled' => 0,
                'Hosts.disabled'    => 0
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

        $where['Servicestatus.current_state'] = $conditions['Servicestatus']['current_state'];

        if ($where['Servicestatus.current_state'] > 0) {
            if ($conditions['Servicestatus']['acknowledged'] ^ $conditions['Servicestatus']['not_acknowledged']) {
                $hasBeenAcknowledged = (int)($conditions['Servicestatus']['acknowledged'] === true);
                $where['Servicestatus.problem_has_been_acknowledged'] = $hasBeenAcknowledged;
            }

            if ($conditions['Servicestatus']['in_downtime'] ^ $conditions['Servicestatus']['not_in_downtime']) {
                $inDowntime = $conditions['Servicestatus']['in_downtime'] === true;
                if ($inDowntime === false) {
                    $where['Servicestatus.scheduled_downtime_depth'] = 0;
                } else {
                    $where['Servicestatus.scheduled_downtime_depth > '] = 0;
                }
            }
        }

        if (!empty($conditions['Servicegroup']['_ids'])) {
            $servicegroupIds = explode(',', $conditions['Servicegroup']['_ids']);
            $query->select([
                'servicegroup_ids' => $query->newExpr(
                    'IF(GROUP_CONCAT(ServiceToServicegroups.servicegroup_id) IS NULL,
                    GROUP_CONCAT(ServicetemplatesToServicegroups.servicegroup_id),
                    GROUP_CONCAT(ServiceToServicegroups.servicegroup_id))'),
                'count'            => $query->newExpr(
                    'SELECT COUNT(servicegroups.id)
                                FROM servicegroups
                                WHERE FIND_IN_SET (servicegroups.id,IF(GROUP_CONCAT(ServiceToServicegroups.servicegroup_id) IS NULL,
                                GROUP_CONCAT(ServicetemplatesToServicegroups.servicegroup_id),
                                GROUP_CONCAT(ServiceToServicegroups.servicegroup_id)))
                                AND servicegroups.id IN (' . implode(', ', $servicegroupIds) . ')')
            ]);
            $query->join([
                'services_to_servicegroups'         => [
                    'table'      => 'services_to_servicegroups',
                    'type'       => 'LEFT',
                    'alias'      => 'ServiceToServicegroups',
                    'conditions' => 'ServiceToServicegroups.service_id = Services.id',
                ],
                'servicetemplates_to_servicegroups' => [
                    'table'      => 'servicetemplates_to_servicegroups',
                    'type'       => 'LEFT',
                    'alias'      => 'ServicetemplatesToServicegroups',
                    'conditions' => 'ServicetemplatesToServicegroups.servicetemplate_id = Services.servicetemplate_id',
                ]
            ]);
            $query->having([
                'servicegroup_ids IS NOT NULL',
                'count > 0'
            ]);
            $query->groupBy('Services.id');
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

        if (!empty($conditions['Service']['servicename'])) {
            if (isset($conditions['Service']['servicename_regex']) && $conditions['Service']['servicename_regex'] === true || $conditions['Service']['servicename_regex'] === 'true') {
                if ($this->isValidRegularExpression($conditions['Service']['servicename'])) {
                    $where[] = new ComparisonExpression(
                        'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                        $conditions['Service']['servicename'],
                        'string',
                        'RLIKE'
                    );
                }
            } else {
                $where[] = new ComparisonExpression(
                    'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                    sprintf('%%%s%%', $conditions['Service']['servicename']),
                    'string',
                    'LIKE'
                );
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
        if (!empty($conditions['Service']['keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $conditions['Service']['keywords'],
                'string',
                'RLIKE'
            );
        }

        if (!empty($conditions['Service']['not_keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $conditions['Service']['not_keywords'],
                'string',
                'NOT RLIKE'
            );
        }

        if (!empty($conditions['Servicestatus']['state_older_than']) && is_numeric($conditions['Servicestatus']['state_older_than']) && $conditions['Servicestatus']['state_older_than'] > 0) {
            $intervalUnit = 'MINUTE';
            if (in_array($conditions['Servicestatus']['state_older_than_unit'], ['SECOND', 'MINUTE', 'HOUR', 'DAY'], true)) {
                $intervalUnit = $conditions['Servicestatus']['state_older_than_unit'];
            }
            $query->where([
                sprintf('Servicestatus.last_state_change <= UNIX_TIMESTAMP(DATE(NOW() - INTERVAL %s %s))',
                    $conditions['Servicestatus']['state_older_than'],
                    $intervalUnit
                )
            ]);
        }
        $query->andWhere($where)
            ->groupBy(['Services.id'])
            ->disableHydration();
        $result = $query->all();

        return $this->emptyArrayIfNull(Hash::extract($result->toArray(), '{n}.id'));
    }


    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByService($timeperiodId) {
        $count = $this->find()
            ->where([
                'OR' => [
                    'Services.check_period_id'  => $timeperiodId,
                    'Services.notify_period_id' => $timeperiodId
                ]
            ])->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array $hostIds
     * @return array
     */
    public function getServicesByHostIdForDelete($hostIds = [], $enableHydration = false) {
        if (!is_array($hostIds)) {
            $hostIds = [$hostIds];
        }
        if (empty($hostIds)) {
            return [];
        }
        $hostIds = array_unique($hostIds);

        $query = $this->find()
            ->select([
                'Services.id',
                'Services.name',
                'Services.usage_flag',
                'Services.servicetemplate_id',
                'Services.service_type'
            ])->where([
                'Services.host_id IN' => $hostIds
            ])->enableHydration($enableHydration);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * @param array $hostIds
     * @return array
     */
    public function getServicesByHostIdForCopy($hostIds = []) {
        if (!is_array($hostIds)) {
            $hostIds = [$hostIds];
        }
        if (empty($hostIds)) {
            return [];
        }
        $hostIds = array_unique($hostIds);

        $query = $this->find()
            ->select([
                'Services.id'
            ])->where([
                'Services.host_id IN'   => $hostIds,
                'Services.service_type' => GENERIC_SERVICE
            ])->disableHydration();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * @param array $hostIds
     * @return array
     */
    public function getServiceNamesByHostIdForWizard($hostIds = [], $enableHydration = false) {
        if (!is_array($hostIds)) {
            $hostIds = [$hostIds];
        }
        if (empty($hostIds)) {
            return [];
        }
        $hostIds = array_unique($hostIds);

        $query = $this->find();
        $query->select([
            'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)')
        ])->where([
            'Services.host_id IN' => $hostIds
        ])->contain([
            'Servicetemplates'
        ])->enableHydration($enableHydration);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        $serviceNames = [];
        foreach ($result as $value) {
            $serviceNames[] = $value['servicename'];
        }
        return array_unique($serviceNames);
    }

    /**
     * @param $ids
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getServicesByIdsForMapeditor($ids, array $MY_RIGHTS = [], $enableHydration = false) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->join([
                [
                    'table'      => 'hosts',
                    'alias'      => 'Hosts',
                    'type'       => 'INNER',
                    'conditions' => [
                        'Hosts.id = Services.host_id',
                    ],
                ],
                [
                    'table'      => 'hosts_to_containers',
                    'alias'      => 'HostsToContainers',
                    'type'       => 'INNER',
                    'conditions' => [
                        'HostsToContainers.host_id = Hosts.id',
                    ],
                ]
            ])
            ->select([
                'Services.id',
                'Services.uuid',
                'Hosts.id',
                'Hosts.uuid',
                'HostsToContainers.container_id'
            ])->where([
                'Services.id IN'    => $ids,
                'Services.disabled' => 0
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'HostsToContainers.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->enableHydration($enableHydration);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * @param $ids
     * @param bool $enableHydration
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicesByIdsForMapsumary($ids, $MY_RIGHTS = [], $enableHydration = false) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->join([
                [
                    'table'      => 'hosts',
                    'alias'      => 'Hosts',
                    'type'       => 'INNER',
                    'conditions' => [
                        'Hosts.id = Services.host_id',
                    ],
                ],
                [
                    'table'      => 'hosts_to_containers',
                    'alias'      => 'HostsToContainers',
                    'type'       => 'INNER',
                    'conditions' => [
                        'HostsToContainers.host_id = Hosts.id',
                    ],
                ],
                [
                    'table'      => 'servicetemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Servicetemplates',
                    'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                ],
            ]);
        $query->select([
            'Services.id',
            'Services.uuid',
            'Services.name',
            'Servicetemplates.name',
            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',
            'HostsToContainers.container_id',
            'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
        ])->where([
            'Services.id IN'    => $ids,
            'Services.disabled' => 0
        ]);
        if (!empty($MY_RIGHTS)) {
            $query->where([
                'HostsToContainers.container_id IN' => $MY_RIGHTS
            ]);
        }
        $query->groupBy([
            'Services.id'
        ]);
        $query->enableHydration($enableHydration);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * @param int $timeperiodId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getServicesByTimeperiodId($timeperiodId, $MY_RIGHTS = [], $enableHydration = true) {
        $query = $this->find('all');
        $query->where([
            'OR' => [
                'Services.check_period_id'  => $timeperiodId,
                'Services.notify_period_id' => $timeperiodId
            ]
        ]);
        $query->select([
            'Services.id',
            'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),

            'Hosts.name',
            'Hosts.id',
        ]);
        $query
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                    ]);
                }
                return $q;
            })
            ->contain([
                'Servicetemplates'
            ]);


        $query->enableHydration($enableHydration);
        $query->orderBy([
            'servicename' => 'asc',
            'Services.id' => 'asc'
        ]);
        $query->groupBy([
            'Services.id'
        ]);

        $result = $query->all();

        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @param int $id
     * @param int $USAGE_FLAG
     * @return Service|bool
     */
    public function setUsageFlagById($id, int $USAGE_FLAG) {
        $service = $this->get($id);
        $currentFlag = $service->get('usage_flag');

        if ($currentFlag & $USAGE_FLAG) {
            //Service already has the flag for given module
            return true;
        } else {
            $newFlag = $currentFlag + $USAGE_FLAG;
            $service->set('usage_flag', $newFlag);
            return $this->save($service);
        }
    }

    /**
     * @param int $id
     * @param int $USAGE_FLAG
     * @return Service|bool
     */
    public function removeUsageFlagById($id, int $USAGE_FLAG) {
        $service = $this->get($id);
        $currentFlag = $service->get('usage_flag');

        if ($currentFlag & $USAGE_FLAG) {
            $newFlag = $currentFlag - $USAGE_FLAG;
            if ($newFlag < 0) {
                $newFlag = 0;
            }

            $service->set('usage_flag', $newFlag);
            return $this->save($service);
        }

        return true;
    }

    /**
     * @param $host_id
     * @param bool $enableActiveChecksEnabledCondition
     * @return array
     */
    public function getServicesForCheckmk($host_id) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.name',
                'Services.servicetemplate_id',
                'Services.active_checks_enabled'
            ])
            ->contain([
                'Servicetemplates' => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'Servicetemplates.id',
                            'Servicetemplates.name',
                            'Servicetemplates.active_checks_enabled'
                        ]);
                },
            ]);

        $query->where(function (QueryExpression $exp) {
            return $exp
                ->add(
                    'IF(Services.active_checks_enabled IS NULL, Servicetemplates.active_checks_enabled, Services.active_checks_enabled) = 0'
                );
        });

        $query
            ->where([
                'Services.host_id'      => $host_id,
                'Services.service_type' => MK_SERVICE
            ])
            ->orderBy(['Services.id' => 'asc'])
            ->disableHydration()
            ->all();
        if (empty($query) || $query === null) {
            return [];
        }
        return $query->toArray();
    }

    /**
     * @param $host_id
     * @param $servicetemplate_id
     * @return bool
     */
    public function hostHasServiceByServicetemplateId($host_id, $servicetemplate_id) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.host_id',
                'Services.servicetemplate_id'
            ])
            ->where([
                'Services.host_id'            => $host_id,
                'Services.servicetemplate_id' => $servicetemplate_id,
            ])->first();

        return !empty($query);
    }

    /**
     * @param $host_id
     * @param $servicetemplate_id
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getServicesOfHostByServicetemplateId($host_id, $servicetemplate_id) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.host_id',
                'Services.servicetemplate_id'
            ])
            ->where([
                'Services.host_id'            => $host_id,
                'Services.servicetemplate_id' => $servicetemplate_id,
            ])->all();

        return $query;
    }

    /**
     * @param array $serviceIds
     * @return array
     */
    public function getServiceUuidsByServiceIds($serviceIds = []) {
        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }

        if (empty($serviceIds)) {
            return [];
        }

        $query = $this->find('list',
            keyField: 'id',
            valueField: 'uuid')
            ->where([
                'Services.id IN' => $serviceIds
            ])
            ->disableHydration();

        return $query->toArray();
    }

    /**
     * @param array $serviceIds
     * @return array
     */
    public function getServicesByIds($serviceIds = [], $enableHydration = true) {
        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }

        if (empty($serviceIds)) {
            return [];
        }

        $query = $this->find()
            ->where([
                'Services.id IN' => $serviceIds
            ])
            ->contain([
                'Hosts' => [
                    'HostsToContainersSharing'
                ],
                'Servicetemplates'
            ])
            ->enableHydration($enableHydration)
            ->all();

        if (empty($query)) {
            return [];
        }

        return $query->toArray();
    }

    /**
     * @return array
     */
    public function getServiceTypes() {
        $types = $this->getServiceTypesWithStyles();
        return array_combine(array_keys($types), Hash::extract($types, '{n}.title'));
    }

    /**
     * @return array
     */
    public function getServiceTypesWithStyles() {
        $types[GENERIC_SERVICE] = [
            'title' => __('Generic service'),
            'color' => 'text-generic',
            'class' => 'border-generic',
            'icon'  => ['fas', 'cog'],
        ];

        if (Plugin::isLoaded('EventcorrelationModule')) {
            $types[EVK_SERVICE] = [
                'title'  => __('EVC service'),
                'color'  => 'text-evc',
                'class'  => 'border-evc',
                'icon'   => ['fas', 'sitemap'],
                'rotate' => 90
            ];
        }

        if (Plugin::isLoaded('CheckmkModule')) {
            $types[MK_SERVICE] = [
                'title' => __('Checkmk service'),
                'color' => 'text-mk',
                'class' => 'border-mk',
                'icon'  => ['fas', 'search-plus'],
            ];
        }

        if (Plugin::isLoaded('PrometheusModule')) {
            $types[PROMETHEUS_SERVICE] = [
                'title' => __('Prometheus service'),
                'color' => 'text-prometheus',
                'class' => 'border-prometheus',
                'icon'  => ['fas', 'burn'],
            ];
        }

        $types[OITC_AGENT_SERVICE] = [
            'title' => __('Agent service'),
            'color' => 'text-agent',
            'class' => 'border-agent',
            'icon'  => ['fas', 'user-secret'],
        ];

        if (Plugin::isLoaded('ImportModule')) {
            $types[EXTERNAL_SERVICE] = [
                'title' => __('External service'),
                'color' => 'text-external',
                'class' => 'border-external',
                'icon'  => ['fas', 'tower-observation'],
            ];
        }

        return $types;
    }

    /**
     * @param array $servicetemplateIds
     * @param int $hostId
     * @param int $userId
     * @return array
     *
     * return looks like
     * [
     *     'newServiceIds' => [
     *         1, 2, 3, 1337, ...
     *     ],
     *     'errors' => [
     *         $service->getErrors()
     *     ]
     * ]
     */
    public function createServiceByServicetemplateIds($servicetemplateIds, $hostId, $userId = 0) {
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $HosttemplatesTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $host = $HostsTable->get($hostId);
        $hostContactsAndContactgroupsById = $HostsTable->getContactsAndContactgroupsById($host->get('id'));
        $hosttemplateContactsAndContactgroupsById = $HosttemplatesTable->getContactsAndContactgroupsById($host->get('hosttemplate_id'));

        $newServiceIds = [];
        $errors = [];
        foreach ($servicetemplateIds as $servicetemplateId) {
            $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($servicetemplateId);

            $servicename = $servicetemplate['Servicetemplate']['name'];

            $serviceData = ServiceComparisonForSave::getServiceSkeleton($hostId, $servicetemplateId);
            $ServiceComparisonForSave = new ServiceComparisonForSave(
                ['Service' => $serviceData],
                $servicetemplate,
                $hostContactsAndContactgroupsById,
                $hosttemplateContactsAndContactgroupsById
            );
            $serviceData = $ServiceComparisonForSave->getDataForSaveForAllFields();
            $serviceData['uuid'] = UUID::v4();

            //Add required fields for validation
            $serviceData['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
            $serviceData['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
            $serviceData['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
            $serviceData['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
            $serviceData['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

            $service = $this->newEntity($serviceData);

            $this->save($service);
            if ($service->hasErrors()) {
                $errors[] = $service->getErrors();
            } else {
                //No errors

                $extDataForChangelog = $this->resolveDataForChangelog(['Service' => $serviceData]);
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'add',
                    'services',
                    $service->get('id'),
                    OBJECT_SERVICE,
                    $host->get('container_id'),
                    $userId,
                    $host->get('name') . '/' . $servicename,
                    array_merge(['Service' => $serviceData], $extDataForChangelog)
                );

                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                $newServiceIds[] = $service->get('id');
            }
        }

        return [
            'newServiceIds' => $newServiceIds,
            'errors'        => $errors
        ];
    }

    /**
     * @param array $servicetemplateIds
     * @param int $hostId
     * @param int $userId
     * @return array
     *
     * return looks like
     * [
     *     'disabledServiceIds' => [
     *         1, 2, 3, 1337, ...
     *     ],
     *     'errors' => [
     *         $service->getErrors()
     *     ]
     * ]
     */
    public function disableServiceByServicetemplateIds($servicetemplateIds, $hostId, $userId = 0) {
        if (!is_array($servicetemplateIds)) {
            $servicetemplateIds = [$servicetemplateIds];
        }
        /** @var $HosttemplatesTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $host = $HostsTable->get($hostId);
        $servicesToDisable = $this->find()
            ->contain('Servicetemplates')
            ->where([
                'Services.host_id'               => $hostId,
                'Services.servicetemplate_id IN' => $servicetemplateIds,
                'Services.disabled'              => 0
            ])
            ->all();

        $disabledServiceIds = [];
        $errors = [];

        if (!empty($servicesToDisable)) {
            /** @var ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');
            foreach ($servicesToDisable as $service) {
                $service->set('disabled', 1);
                $this->save($service);
                if ($service->hasErrors()) {
                    $errors[] = $service->getErrors();
                } else {
                    // has no errors
                    $serviceName = !empty($service->get('name')) ? $service->get('name') : $service->get('servicetemplate')->get('name');
                    $serviceId = $service->get('id');
                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        'deactivate',
                        'services',
                        $serviceId,
                        OBJECT_SERVICE,
                        $host['container_id'],
                        $userId,
                        $host['name'] . '/' . $serviceName,
                        []
                    );
                    if ($changelog_data) {
                        /** @var Changelog $changelogEntry */
                        $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                        $ChangelogsTable->save($changelogEntry);
                    }
                    $disabledServiceIds[] = $serviceId;
                }
            }
        }

        return [
            'disabledServiceIds' => $disabledServiceIds,
            'errors'             => $errors
        ];
    }

    /**
     * @param $hostId
     * @param $containerIds
     * @return array
     */
    public function cleanupServicesByHostIdAndRemovedContainerIdsTest($hostId, $containerIds) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $query = $this->find()
            ->select([
                'Services.id',
                'Servicegroups.id'
            ])
            ->innerJoinWith('Servicegroups', function (Query $q) {
                return $q->innerJoinWith('Containers');
            })
            ->where([
                'Services.host_id'         => $hostId,
                'Containers.parent_id IN ' => $containerIds
            ])->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * Check if the service was part of a service groups outside of new permissions
     * If yes, records must be deleted for valid configuration
     *
     * @param $hostId
     * @param $removedContainerIds
     * @param $userId
     */
    public function _cleanupServicesByHostIdAndRemovedContainerIds($hostId, $removedContainerIds, $userId) {
        if (!is_array($removedContainerIds)) {
            $removedContainerIds = [$removedContainerIds];
        }
        $query = $this->find()
            ->select([
                'Services.id',
                'Servicegroups.id'
            ])
            ->innerJoinWith('Servicegroups', function (Query $q) {
                return $q->innerJoinWith('Containers');
            })
            ->where([
                'Services.host_id'         => $hostId,
                'Containers.parent_id IN ' => $removedContainerIds
            ])
            ->disableHydration()
            ->all();

        $recordsToDelete = $this->emptyArrayIfNull($query->toArray());
        if (empty($recordsToDelete)) {
            return;
        }
        $recordsToDelete = Hash::combine(
            $recordsToDelete,
            null,
            '{n}.id',
            '{n}._matchingData.Servicegroups.id'

        );
        /** @var ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

        foreach ($recordsToDelete as $servicegroupId => $serviceIdsToDelete) {
            $servicegroupEntity = $ServicegroupsTable->get($servicegroupId, contain: [
                'Containers',
                'Services'
            ]);
            $servicegroupServiceIds = Hash::extract($servicegroupEntity->get('services'), '{n}.id');
            $newServiceIds = array_diff(
                $servicegroupServiceIds,
                $serviceIdsToDelete
            );

            $servicegroupEntity = $ServicegroupsTable->patchEntity($servicegroupEntity, [
                'services' => [
                    '_ids' => $newServiceIds
                ]
            ]);

            $ServicegroupsTable->save($servicegroupEntity);
            if (!$servicegroupEntity->hasErrors()) {
                //No errors
                /** @var ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'servicegroups',
                    $servicegroupEntity->id,
                    OBJECT_SERVICEGROUP,
                    $servicegroupEntity->get('container')->get('parent_id'),
                    $userId,
                    $servicegroupEntity->get('container')->get('name'),
                    /** Create changelog for only service changes */
                    $ServicegroupsTable->resolveDataForChangelog([
                        'Servicegroup' => [
                            'services' => [
                                '_ids' => $newServiceIds
                            ]
                        ]
                    ]),
                    $ServicegroupsTable->resolveDataForChangelog([
                        'Servicegroup' => [
                            'services' => [
                                '_ids' => $servicegroupServiceIds
                            ]
                        ]
                    ])
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }
            }
        }
    }

    /**
     *
     * @param int $hostId
     * @return array
     */
    public function getAgentServicesByHostId($hostId) {
        $query = $this->find()
            ->contain([
                'Servicecommandargumentvalues',
                'Servicetemplates' => [
                    'Servicetemplatecommandargumentvalues'
                ]
            ])
            ->where([
                'Services.host_id'      => $hostId,
                'Services.service_type' => OITC_AGENT_SERVICE
            ])
            ->disableHydration()
            ->all();


        $services = $this->emptyArrayIfNull($query->toArray());
        foreach ($services as $index => $service) {
            if (empty($service['servicecommandargumentvalues'])) {
                if (($service['command_id'] === $service['servicetemplate']['command_id'] || $service['command_id'] === null)) {
                    $services[$index]['servicecommandargumentvalues'] = $service['servicetemplate']['servicetemplatecommandargumentvalues'];
                }
            }
        }

        return $services;
    }

    /**
     * @param $servicetemplateId
     * @param $commandId
     */
    public function updateServiceCommandIdIfServiceHasOwnCommandArguments($servicetemplateId, $commandId) {
        $query = $this->find()
            ->select([
                'Services.id'
            ])
            ->contain([
                'Servicecommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->where([
                'Services.command_id IS NULL',
                'Services.servicetemplate_id' => $servicetemplateId
            ])
            ->disableHydration()
            ->all();

        $query = $query->toArray();

        if (!empty($query)) {
            $serviceIds = [];
            foreach ($query as $row) {
                if (!empty($row['servicecommandargumentvalues'])) {
                    $serviceIds[] = (int)$row['id'];
                }
            }
            if (!empty($serviceIds)) {
                $this->updateAll([
                    'command_id' => $commandId
                ], [
                    'id IN' => $serviceIds
                ]);
            }
        }
    }

    /**
     * @param $servicetemplateId
     * @param $commandId
     */
    public function updateServiceEventhandlerCommandIdIfServiceHasOwnEventhandlerCommandArguments($servicetemplateId, $eventhandlerCommandId) {
        $query = $this->find()
            ->select([
                'Services.id'
            ])
            ->contain([
                'Serviceeventcommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->where([
                'Services.eventhandler_command_id IS NULL',
                'Services.servicetemplate_id' => $servicetemplateId
            ])
            ->disableHydration()
            ->all();

        $query = $query->toArray();

        if (!empty($query)) {
            $serviceIds = [];
            foreach ($query as $row) {
                if (!empty($row['serviceeventcommandargumentvalues'])) {
                    $serviceIds[] = (int)$row['id'];
                }
            }
            if (!empty($serviceIds)) {
                $this->updateAll([
                    'eventhandler_command_id' => $eventhandlerCommandId
                ], [
                    'id IN' => $serviceIds
                ]);
            }
        }
    }

    /**
     * @param int $hostId
     * @param int[] $excludedServiceIds
     * @return array
     */
    public function getListOfServiceNamesForUniqueCheck($hostId, $excludedServiceIds = []) {
        if (!is_array($excludedServiceIds)) {
            $excludedServiceIds = [$excludedServiceIds];
        }

        $where = [
            'Services.host_id' => $hostId
        ];

        if (!empty($excludedServiceIds)) {
            $where['Services.id NOT in'] = $excludedServiceIds;
        }

        $query = $this->find();
        $query
            ->select([
                'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
                'Services.id',
            ])
            ->where(
                $where
            )
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Servicetemplates')
            ->disableHydration();

        $result = [];
        foreach ($query->all() as $item) {
            $result[$item['id']] = $item['servicename'];
        }
        return $result;
    }

    /**
     * @param $servicestatus
     * @param bool $extended show details ('acknowledged', 'in downtime', ...
     * @return array
     */
    public function getServiceStateSummary($servicestatus, bool $extended = true): array {
        $serviceStateSummary = [
            'state'            => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0
            ],
            'acknowledged'     => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0
            ],
            'in_downtime'      => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0
            ],
            'not_handled'      => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0
            ],
            'total'            => 0,
            'cumulative_state' => -1 // not monitored
        ];
        if ($extended === true) {
            $serviceStateSummary = [
                'state'            => [
                    0            => 0,
                    1            => 0,
                    2            => 0,
                    3            => 0,
                    'serviceIds' => [
                        0 => [],
                        1 => [],
                        2 => [],
                        3 => []
                    ]
                ],
                'acknowledged'     => [
                    0            => 0,
                    1            => 0,
                    2            => 0,
                    3            => 0,
                    'serviceIds' => [
                        0 => [],
                        1 => [],
                        2 => [],
                        3 => []
                    ]
                ],
                'in_downtime'      => [
                    0            => 0,
                    1            => 0,
                    2            => 0,
                    3            => 0,
                    'serviceIds' => [
                        0 => [],
                        1 => [],
                        2 => [],
                        3 => []
                    ]
                ],
                'not_handled'      => [
                    0                 => 0,
                    1                 => 0,
                    2                 => 0,
                    3                 => 0,
                    'serviceIds'      => [
                        0 => [],
                        1 => [],
                        2 => [],
                        3 => []
                    ],
                    'totalServiceIds' => []
                ],
                'passive'          => [
                    0            => 0,
                    1            => 0,
                    2            => 0,
                    3            => 0,
                    'serviceIds' => [
                        0 => [],
                        1 => [],
                        3 => [],
                        2 => []
                    ]
                ],
                'total'            => 0,
                'cumulative_state' => -1 // not monitored
            ];
        }
        if (empty($servicestatus)) {
            return $serviceStateSummary;
        }
        foreach ($servicestatus as $service) {
            //Check for random exit codes like 255...
            if ($service['Servicestatus']['current_state'] > 2) {
                $service['Servicestatus']['current_state'] = 3;
            }
            $serviceStateSummary['state'][$service['Servicestatus']['current_state']]++;
            if ($extended === true) {
                $serviceStateSummary['state']['serviceIds'][$service['Servicestatus']['current_state']][] = $service['id'];
                if ($service['Servicestatus']['current_state'] > 0) {
                    if ($service['Servicestatus']['problem_has_been_acknowledged'] > 0) {
                        $serviceStateSummary['acknowledged'][$service['Servicestatus']['current_state']]++;
                        $serviceStateSummary['acknowledged']['serviceIds'][$service['Servicestatus']['current_state']][] = $service['id'];
                    } else if ($service['Servicestatus']['problem_has_been_acknowledged'] == 0 && $service['Servicestatus']['scheduled_downtime_depth'] == 0) {
                        $serviceStateSummary['not_handled'][$service['Servicestatus']['current_state']]++;
                        $serviceStateSummary['not_handled']['serviceIds'][$service['Servicestatus']['current_state']][] = $service['id'];
                        $serviceStateSummary['not_handled']['totalServiceIds'][] = $service['id'];
                    }
                }
                if ($service['Servicestatus']['scheduled_downtime_depth'] > 0) {
                    $serviceStateSummary['in_downtime'][$service['Servicestatus']['current_state']]++;
                    $serviceStateSummary['in_downtime']['serviceIds'][$service['Servicestatus']['current_state']][] = $service['id'];
                }
                if ($service['Servicestatus']['active_checks_enabled'] == 0) {
                    $serviceStateSummary['passive'][$service['Servicestatus']['current_state']]++;
                    $serviceStateSummary['passive']['serviceIds'][$service['Servicestatus']['current_state']][] = $service['id'];
                }
            } else {
                if ($service['Servicestatus']['current_state'] > 0) {
                    if ($service['Servicestatus']['problem_has_been_acknowledged'] > 0) {
                        $serviceStateSummary['acknowledged'][$service['Servicestatus']['current_state']]++;
                    } else if ($service['Servicestatus']['problem_has_been_acknowledged'] == 0 && $service['Servicestatus']['scheduled_downtime_depth'] == 0) {
                        $serviceStateSummary['not_handled'][$service['Servicestatus']['current_state']]++;
                    }
                }
                if ($service['Servicestatus']['scheduled_downtime_depth'] > 0) {
                    $serviceStateSummary['in_downtime'][$service['Servicestatus']['current_state']]++;
                }
            }
            if ($serviceStateSummary['cumulative_state'] < $service['Servicestatus']['current_state']) {
                $serviceStateSummary['cumulative_state'] = $service['Servicestatus']['current_state'];
            }
            $serviceStateSummary['total']++;
        }
        return $serviceStateSummary;
    }

    /**
     * @param $MY_RIGHTS
     * @param $conditions
     * @return array
     */
    public function getServicesWithStatusByConditions($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'Services.uuid'
            ])
            ->where([
                'Services.disabled' => 0
            ])
            ->join([
                'a'                => [
                    'table'      => 'nagios_objects',
                    'type'       => 'INNER',
                    'alias'      => 'ServiceObject',
                    'conditions' => 'ServiceObject.name2 = Services.uuid',
                ],
                'b'                => [
                    'table'      => 'nagios_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_object_id = ServiceObject.object_id',
                ],
                'servicetemplates' => [
                    'table'      => 'servicetemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Servicetemplates',
                    'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                ]
            ])->contain([
                'Hosts'
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                    ]);
                }
                return $q;
            });
        }

        if (!empty($conditions['Servicegroup']['_ids'])) {
            $servicegroupIds = explode(',', $conditions['Servicegroup']['_ids']);
            $query->select([
                'servicegroup_ids' => $query->newExpr(
                    'IF(GROUP_CONCAT(ServiceToServicegroups.servicegroup_id) IS NULL,
                    GROUP_CONCAT(ServicetemplatesToServicegroups.servicegroup_id),
                    GROUP_CONCAT(ServiceToServicegroups.servicegroup_id))'),
                'count'            => $query->newExpr(
                    'SELECT COUNT(servicegroups.id)
                                FROM servicegroups
                                WHERE FIND_IN_SET (servicegroups.id,IF(GROUP_CONCAT(ServiceToServicegroups.servicegroup_id) IS NULL,
                                GROUP_CONCAT(ServicetemplatesToServicegroups.servicegroup_id),
                                GROUP_CONCAT(ServiceToServicegroups.servicegroup_id)))
                                AND servicegroups.id IN (' . implode(', ', $servicegroupIds) . ')')
            ]);
            $query->join([
                'services_to_servicegroups'         => [
                    'table'      => 'services_to_servicegroups',
                    'type'       => 'LEFT',
                    'alias'      => 'ServiceToServicegroups',
                    'conditions' => 'ServiceToServicegroups.service_id = Services.id',
                ],
                'servicetemplates_to_servicegroups' => [
                    'table'      => 'servicetemplates_to_servicegroups',
                    'type'       => 'LEFT',
                    'alias'      => 'ServicetemplatesToServicegroups',
                    'conditions' => 'ServicetemplatesToServicegroups.servicetemplate_id = Servicetemplates.id',
                ]
            ]);
            $query->having([
                'servicegroup_ids IS NOT NULL',
                'count > 0'
            ]);
            $query->groupBy('Services.id');
        }

        if (isset($where['Services.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['Services.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Services.keywords rlike']);
        }

        if (isset($where['Services.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['Services.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Services.not_keywords not rlike']);
        }

        $where = [];
        if (!empty($conditions['Service']['servicename'])) {
            $query->having([
                'servicename LIKE' => $conditions['Service']['servicename']
            ]);
        }
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
    public function getServicesWithStatusByConditionsStatusengine3($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $where = [];
        $query
            ->select([
                'Services.host_id',
                'Services.id',
                'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
                'Servicestatus.current_state',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.problem_has_been_acknowledged'
            ]);
        $query->where([
            'Services.disabled' => 0
        ])
            ->join([
                'b'                => [
                    'table'      => 'statusengine_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_description = Services.uuid',
                ],
                'servicetemplates' => [
                    'table'      => 'servicetemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Servicetemplates',
                    'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                ],
                'hosts'            => [
                    'table'      => 'hosts',
                    'type'       => 'INNER',
                    'alias'      => 'Hosts',
                    'conditions' => 'Hosts.id = Services.host_id',
                ]
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                    ]);
                }
                return $q;
            });
        }

        if (!empty($conditions['Hostgroup'])) {
            $query->join([
                    'hosttemplates' => [
                        'table'      => 'hosttemplates',
                        'type'       => 'INNER',
                        'alias'      => 'Hosttemplates',
                        'conditions' => 'Hosttemplates.id = Hosts.hosttemplate_id',
                    ]
                ]
            );
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
                    'host_count'    => $hostgroups->find()->select([$query->func()->count('Hostgroups.id')])
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
                'host_count > 0'
            ]);
        }


        if (!empty($conditions['Servicegroup'])) {
            $servicegroups = $this->fetchTable('Servicegroups');
            $servicegroupIds = [];
            if (!empty($conditions['Servicegroup']['_ids'])) {
                $servicegroupIds = explode(',', $conditions['Servicegroup']['_ids']);
            }
            $whereForCount = [
                $query->newExpr('FIND_IN_SET (Servicegroups.id,IF(GROUP_CONCAT(ServiceToServicegroups.servicegroup_id) IS NULL,
                                GROUP_CONCAT(ServicetemplatesToServicegroups.servicegroup_id),
                                GROUP_CONCAT(ServiceToServicegroups.servicegroup_id)))')
            ];

            if (!empty($servicegroupIds)) {
                $whereForCount[] = ['Servicegroups.id IN' => $servicegroupIds];
            }

            if (!empty($conditions['Servicegroup']['keywords'])) {
                $whereForCount[] = new ComparisonExpression(
                    'IF((Servicegroups.tags IS NOT NULL), Servicegroups.tags, "")',
                    $conditions['Servicegroup']['keywords'],
                    'string',
                    'RLIKE'

                );
            }

            if (!empty($conditions['Servicegroup']['not_keywords'])) {
                $whereForCount[] = new ComparisonExpression(
                    'IF((Servicegroups.tags IS NOT NULL), Servicegroups.tags, "")',
                    $conditions['Servicegroups']['not_keywords'],
                    'string',
                    'NOT RLIKE'
                );
            }
            if (!empty($whereForCount)) {
                $query->select([
                    'servicegroup_ids' => $query->newExpr(
                        'IF(GROUP_CONCAT(ServiceToServicegroups.servicegroup_id) IS NULL,
                    GROUP_CONCAT(ServicetemplatesToServicegroups.servicegroup_id),
                    GROUP_CONCAT(ServiceToServicegroups.servicegroup_id))'),
                    'service_count'    => $servicegroups->find()->select([$query->func()->count('Servicegroups.id')])
                        ->where($whereForCount)
                ]);

            }
            $query->join([
                'services_to_servicegroups'         => [
                    'table'      => 'services_to_servicegroups',
                    'type'       => 'LEFT',
                    'alias'      => 'ServiceToServicegroups',
                    'conditions' => 'ServiceToServicegroups.service_id = Services.id',
                ],
                'servicetemplates_to_servicegroups' => [
                    'table'      => 'servicetemplates_to_servicegroups',
                    'type'       => 'LEFT',
                    'alias'      => 'ServicetemplatesToServicegroups',
                    'conditions' => 'ServicetemplatesToServicegroups.servicetemplate_id = Servicetemplates.id',
                ]
            ]);
            $query->having([
                'servicegroup_ids IS NOT NULL',
                'service_count > 0'
            ]);
        }

        if (isset($conditions['Services.keywords rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['Services.keywords rlike'],
                'string',
                'RLIKE'
            );
            unset($where['Services.keywords rlike']);
        }

        if (isset($conditions['Services.not_keywords not rlike'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $where['Services.not_keywords not rlike'],
                'string',
                'NOT RLIKE'
            );
            unset($where['Services.not_keywords not rlike']);
        }

        if (!empty($conditions['Service']['keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $conditions['Service']['keywords'],
                'string',
                'RLIKE'
            );
        }

        if (!empty($conditions['Service']['not_keywords'])) {
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $conditions['Service']['not_keywords'],
                'string',
                'NOT RLIKE'
            );
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
                $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['Host']['name']);
            }
        }

        if (!empty($conditions['Service']['servicename'])) {
            if (isset($conditions['Service']['servicename_regex']) && $conditions['Service']['servicename_regex'] === true || $conditions['Service']['servicename_regex'] === 'true') {
                if ($this->isValidRegularExpression($conditions['Service']['servicename'])) {
                    $query->having([
                        new ComparisonExpression(
                            'servicename',
                            $conditions['Service']['servicename'],
                            'string',
                            'RLIKE'
                        )
                    ]);
                }
            } else {
                $query->having([
                    'servicename LIKE' => sprintf('%%%s%%', $conditions['Service']['servicename'])
                ]);
            }
        }

        $query->andWhere($where);
        $query->groupBy('Services.id');

        $query->disableHydration();
        $result = $query->all();
        if ($result === null) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * @param string $hostUuid
     * @return array[Service]
     */
    public function getServicesForRescheduling(string $hostUuid) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.servicetemplate_id',
                'Services.active_checks_enabled',
            ])
            ->innerJoinWith('Hosts', function (Query $query) use ($hostUuid) {
                $query->where([
                    'Hosts.uuid' => $hostUuid
                ]);
                return $query;
            })
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Servicetemplates.id',
                            'Servicetemplates.active_checks_enabled',
                        ]);
                    return $query;
                }
            ]);
        $query->all();

        return $query->toArray();
    }

    /**
     * @param string $serviceUuid
     * @return Service|null
     */
    public function getServiceForRescheduling(string $serviceUuid) {
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.uuid',
                'Services.servicetemplate_id',
                'Services.active_checks_enabled',
            ])
            ->where([
                'Services.uuid' => $serviceUuid
            ])
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Servicetemplates.id',
                            'Servicetemplates.active_checks_enabled',
                        ]);
                    return $query;
                }
            ]);
        return $query->first();
    }

    /**
     * @param $hostId
     * @param $serviceTypes
     * @param false $enableHydration
     * @return array
     */
    public function getServicesByHostIdAndServiceTypeForAllocation($hostId, $serviceTypes, $enableHydration = false) {
        if (!is_array($serviceTypes)) {
            $serviceTypes = [$serviceTypes];
        }
        $query = $this->find()
            ->select([
                'Services.id',
                'Services.host_id',
                'Services.disabled',
                'Services.servicetemplate_id'
            ])
            ->where([
                'Services.host_id'         => $hostId,
                'Services.service_type IN' => $serviceTypes
            ])
            ->enableAutoFields()
            ->enableHydration($enableHydration)
            ->all();

        return $query->toArray();

    }

    /**
     * @param $containerIds
     * @param $servicegroupIds
     * @param $type
     * @param $index
     * @param $where
     * @return array
     */
    public function getServicesByContainerIdAndServicegroupIds($containerIds, $servicegroupIds, $type = 'all', $index = 'id', $where = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $containerIds = array_unique($containerIds);
        if (!is_array($servicegroupIds)) {
            $servicegroupIds = [$servicegroupIds];
        }

        $_where = [
            'Hosts.disabled IN'    => [0],
            'Services.disabled IN' => [0]
        ];
        if (!empty($where['servicename LIKE'])) {
            $_where[] = new ComparisonExpression(
                'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                sprintf('%%%s%%', $where['servicename LIKE']),
                'string',
                'LIKE'
            );
            unset($where['servicename LIKE']);

        }


        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Services.' . $index,
            'Hosts.id',
            'Hosts.name',
            'Services.disabled',
            'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))')
        ])
            ->innerJoinWith('Hosts')
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($containerIds) {
                if (!empty($MY_RIGHTS)) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $containerIds
                    ]);
                }
                return $q;
            })
            ->innerJoinWith('Servicetemplates')
            ->innerJoinWith('Servicegroups')
            ->where([
                'Servicegroups.id IN' => $servicegroupIds
            ]);

        $query->where($where);

        $query->disableHydration();
        $query->groupBy(['Services.id']);
        $query->orderBy([
            'servicename' => 'asc',
            'Services.id' => 'asc'
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
            $list[$row[$index]] = $row;
        }

        return $list;
    }

    /**
     * @param ServiceConditions $ServiceConditions
     * @param array|int $selected
     * @param bool $returnEmptyArrayIfMyRightsIsEmpty
     * @return array|null
     * @deprecated since ITC-2819
     * See https://github.com/openITCOCKPIT/openITCOCKPIT/pull/1377/files?diff=split&w=0 how to restore <= 4.4.1 behavior
     */
    public function getServicesForServicegroupForAngular(ServiceConditions $ServiceConditions, $selected = [], $returnEmptyArrayIfMyRightsIsEmpty = false) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $selected = array_filter($selected);

        if (empty($ServiceConditions->getContainerIds())) {
            return [];
        }


        $where = $ServiceConditions->getConditions();

        if (!empty($selected)) {
            $where['NOT'] = [
                'Services.id IN' => $selected
            ];
        }

        $having = null;
        if (isset($where['servicename LIKE'])) {
            $having = [
                'servicename LIKE' => $where['servicename LIKE']
            ];
            unset($where['servicename LIKE']);
        }

        $query = $this->find();
        $query
            ->innerJoinWith('Hosts', function (Query $q) use ($ServiceConditions) {
                return $q->where([
                    'Hosts.container_id IN ' => $ServiceConditions->getContainerIds()
                ]);
            })
            ->innerJoinWith('Servicetemplates')
            ->select([
                'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                'Services.id',
                'Services.disabled',
                'Hosts.name'
            ])
            ->where(
                $where
            );
        if (!empty($having)) {
            $query->having($having);
        }
        $query->orderBy([
            'servicename' => 'asc'
        ])
            ->limit(ITN_AJAX_LIMIT)
            ->disableHydration()
            ->all();

        $servicesWithLimit = [];
        $selectedServices = [];
        $results = $this->emptyArrayIfNull($query->toArray());

        foreach ($results as $result) {
            $servicesWithLimit[$result['id']] = $result;
        }

        if (!empty($selected)) {
            $query = $this->find();
            $query
                ->innerJoinWith('Hosts', function (Query $q) use ($ServiceConditions) {
                    return $q->where([
                        'Hosts.container_id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                })
                ->innerJoinWith('Servicetemplates')
                ->select([
                    'servicename' => $query->newExpr('CONCAT(Hosts.name, "/", IF(Services.name IS NULL, Servicetemplates.name, Services.name))'),
                    'Services.id',
                    'Services.disabled',
                    'Hosts.name'
                ])
                ->where([
                    'Services.id IN' => $selected
                ])
                ->orderBy([
                    'servicename' => 'asc'
                ])
                ->disableHydration()
                ->all();
            $results = $this->emptyArrayIfNull($query->toArray());
            foreach ($results as $result) {
                $selectedServices[$result['id']] = $result;
            }

        }
        $services = $servicesWithLimit + $selectedServices;
        $serviceIds = array_keys($services);

        array_multisort(
            array_column($services, 'servicename'), SORT_ASC, SORT_NATURAL, $services, $serviceIds
        );
        $services = array_combine($serviceIds, $services);
        return $services;
    }

    /**
     * @param $MY_RIGHTS
     * @param $conditions
     * @return array
     */
    public function getServicesForDesktopWithStatusByConditionsStatusengine3($MY_RIGHTS, $conditions) {
        $query = $this->find();
        $query
            ->select([
                'Services.host_id',
                'Services.id',
                'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
                'Servicestatus.current_state',
                'Servicestatus.scheduled_downtime_depth',
                'Servicestatus.active_checks_enabled',
                'Servicestatus.problem_has_been_acknowledged'
            ]);
        $query->where([
            'Services.disabled' => 0
        ])
            ->join([
                'b'                => [
                    'table'      => 'statusengine_servicestatus',
                    'type'       => 'INNER',
                    'alias'      => 'Servicestatus',
                    'conditions' => 'Servicestatus.service_description = Services.uuid',
                ],
                'servicetemplates' => [
                    'table'      => 'servicetemplates',
                    'type'       => 'INNER',
                    'alias'      => 'Servicetemplates',
                    'conditions' => 'Servicetemplates.id = Services.servicetemplate_id',
                ]
            ])->contain([
                'Hosts'
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $MY_RIGHTS
                    ]);
                }
                return $q;
            });
        }
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
                $where['Hosts.name LIKE'] = sprintf('%%%s%%', $conditions['filter[Hosts.name]']);
            }
        }

        if (!empty($conditions['filter[servicename]'])) {
            if (isset($conditions['filter[servicename_regex]']) && $conditions['filter[servicename_regex]'] === true || $conditions['filter[servicename_regex]'] === 'true') {
                if ($this->isValidRegularExpression($conditions['filter[servicename]'])) {
                    $query->having([
                        new ComparisonExpression(
                            'servicename',
                            $conditions['filter[servicename]'],
                            'string',
                            'RLIKE'
                        )
                    ]);
                }
            } else {
                $query->having([
                    'servicename LIKE' => sprintf('%%%s%%', $conditions['filter[servicename]'])
                ]);
            }
        }

        $where[] = ['Servicestatus.current_state IN' => $conditions['filter[Servicestatus.current_state][]']];

        if ($conditions['filter[Servicestatus.problem_has_been_acknowledged]'] != 'ignore') {
            $where[] = ['Servicestatus.problem_has_been_acknowledged' => $conditions['filter[Servicestatus.problem_has_been_acknowledged]']];
        }
        if ($conditions['filter[Servicestatus.scheduled_downtime_depth]'] === true) {
            $where[] = ['Servicestatus.scheduled_downtime_depth >' => 0];
        }
        if ($conditions['filter[Servicestatus.scheduled_downtime_depth]'] === false) {
            $where[] = ['Servicestatus.scheduled_downtime_depth' => 0];
        }
        if (!empty($conditions['filter[Services.keywords][]'])) {
            $compareValue = $conditions['filter[Services.keywords][]'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                //implode(',',$conditions['filter[Services.keywords][]']),
                $compareValue,
                'string',
                'RLIKE'
            );
        }

        if (!empty($conditions['filter[Services.not_keywords][]'])) {
            $compareValue = $conditions['filter[Services.not_keywords][]'];
            if (is_string($compareValue)) {
                $compareValue = explode(',', $compareValue);
            }
            $compareValue = sprintf('.*(%s).*', implode('|', $compareValue));
            $where[] = new ComparisonExpression(
                'IF((Services.tags IS NULL OR Services.tags=""), Servicetemplates.tags, Services.tags)',
                $compareValue,
                'string',
                'NOT RLIKE'
            );
        }
        $query->andWhere($where);
        $query->groupBy('Services.id');

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
     * @param $id
     * @param bool $enableHydration
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getActiveServicesWithSlaRelevanceWithServicetemplateByHostId($id, $enableHydration = true) {
        $query = $this->find();
        $query->select([
            'Services.id',
            'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
        ])
            ->contain('Servicetemplates')
            ->where([
                'Services.host_id'  => $id,
                'Services.disabled' => 0,
                $query->newExpr('IF(Services.sla_relevant IS NULL, Servicetemplates.sla_relevant, Services.sla_relevant) = 1')
            ])
            ->orderBy([
                'servicename',
                'Services.id'
            ])
            ->enableHydration($enableHydration)
            ->all();
        return $query;
    }

    /**
     * @param $id
     * @return array
     */
    public function getActiveAndSlaRelavantServicesIdsByHostIdAsList($id) {
        //$list = $this->MODEL->find('list', ['keyField' => 'id', 'valueField' => ('user_type')])->toArray();
        $query = $this->find('list',
            keyField: 'id',
            valueField: 'id');
        $query->select([
            'Services.id',
            'is_sla_relevant' => $query->newExpr('IF(Services.sla_relevant IS NULL, Servicetemplates.sla_relevant, Services.sla_relevant)')
        ])
            ->contain(['Servicetemplates'])
            ->where([
                'Services.host_id'  => $id,
                'Services.disabled' => 0,
                $query->newExpr('IF(Services.sla_relevant IS NULL, Servicetemplates.sla_relevant, Services.sla_relevant) = 1')
            ])
            ->disableHydration()
            ->all();
        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param ServiceConditions $ServiceConditions
     * @return array
     */
    public function getServiceStatusGlobalOverview(ServiceConditions $ServiceConditions): array {
        $where = $ServiceConditions->getConditions();
        $where['Services.disabled'] = 0;
        if ($ServiceConditions->getServiceIds()) {
            $serviceIds = $ServiceConditions->getServiceIds();
            if (!is_array($serviceIds)) {
                $serviceIds = [$serviceIds];
            }

            $where['Services.id IN'] = $serviceIds;
        }

        $query = $this->find();
        $query->select([
            'Servicestatus.current_state',
            'count' => $query->newExpr('COUNT(DISTINCT Servicestatus.service_description)'),
        ])
            ->contain('Servicetemplates')
            ->innerJoin(['Hosts' => 'hosts'], [
                'Hosts.id = Services.host_id',
            ])
            ->innerJoinWith('Hosts.HostsToContainersSharing', function (Query $q) use ($ServiceConditions) {
                if (!empty($ServiceConditions->getContainerIds())) {
                    $q->where([
                        'HostsToContainersSharing.id IN ' => $ServiceConditions->getContainerIds()
                    ]);
                }
                return $q;
            })
            ->innerJoin(['Servicestatus' => 'statusengine_servicestatus'], [
                'Servicestatus.service_description = Services.uuid'
            ]);
        if (isset($where['servicename LIKE'])) {
            $query->where(new ComparisonExpression(
                'IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)',
                $where['servicename LIKE'],
                'string',
                'like'
            ));
            unset($where['servicename LIKE']);
        }


        if (!empty($where)) {
            $query->andWhere($where);
        }

        $query->disableHydration();
        $query->groupBy(['Servicestatus.current_state']);
        return $this->emptyArrayIfNull($query->toArray());
    }
}
