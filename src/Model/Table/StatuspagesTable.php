<?php
// Copyright (C) <2015>  <it-novum GmbH>
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
declare(strict_types=1);

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\AcknowledgementHost;
use itnovum\openITCOCKPIT\Core\Views\AcknowledgementService;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Filter\StatuspagesFilter;


/**
 * Statuspages Model
 *
 * @method \App\Model\Entity\Statuspage newEmptyEntity()
 * @method \App\Model\Entity\Statuspage newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Statuspage[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Statuspage get($primaryKey, $options = [])
 * @method \App\Model\Entity\Statuspage findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Statuspage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Statuspage[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Statuspage|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Statuspage saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Statuspage[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statuspage[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statuspage[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statuspage[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StatuspagesTable extends Table {
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statuspages');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);


        $this->belongsToMany('Hosts', [
            'className'        => 'Hosts',
            'foreignKey'       => 'statuspage_id',
            'targetForeignKey' => 'host_id',
            'joinTable'        => 'statuspages_to_hosts',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Services', [
            'className'        => 'Services',
            'foreignKey'       => 'statuspage_id',
            'targetForeignKey' => 'service_id',
            'joinTable'        => 'statuspages_to_services',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Hostgroups', [
            'className'        => 'Hostgroups',
            'foreignKey'       => 'statuspage_id',
            'targetForeignKey' => 'hostgroup_id',
            'joinTable'        => 'statuspages_to_hostgroups',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Servicegroups', [
            'className'        => 'Servicegroups',
            'foreignKey'       => 'statuspage_id',
            'targetForeignKey' => 'servicegroup_id',
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
            ->scalar('description')
            ->maxLength('description', 1000)
            ->allowEmptyString('description');

        $validator
            ->scalar('public_title')
            ->maxLength('public_title', 255)
            ->allowEmptyString('public_title');

        $validator
            ->boolean('public')
            ->notEmptyString('public');

        $validator
            ->boolean('show_downtimes')
            ->allowEmptyString('show_downtimes');

        $validator
            ->boolean('show_downtime_comments')
            ->allowEmptyString('show_downtime_comments');

        $validator
            ->boolean('show_acknowledgements')
            ->allowEmptyString('show_acknowledgements');

        $validator
            ->boolean('show_acknowledgement_comments')
            ->allowEmptyString('show_acknowledgement_comments');

        $validator
            ->add('selected_hosts', 'custom', [
                'rule'    => [$this, 'atLeastOneConfigurationItem'],
                'message' => __('You must select at least one configuration item for status page.')
            ]);
        $validator
            ->add('selected_services', 'custom', [
                'rule'    => [$this, 'atLeastOneConfigurationItem'],
                'message' => __('You must select at least one configuration item for status page.')
            ]);
        $validator
            ->add('selected_hostgroups', 'custom', [
                'rule'    => [$this, 'atLeastOneConfigurationItem'],
                'message' => __('You must select at least one configuration item for status page.')
            ]);
        $validator
            ->add('selected_servicegroups', 'custom', [
                'rule'    => [$this, 'atLeastOneConfigurationItem'],
                'message' => __('You must select at least one configuration item for status page.')
            ]);

        return $validator;
    }

    /**
     * @param $value
     * @param $context
     * @return bool
     */
    public function atLeastOneConfigurationItem($value, $context) {
        return !empty(Hash::filter(Hash::extract($context['data'], '{s}._ids')));
    }

    /**
     * @param Validator $validator
     * @return Validator
     */
    public function validationAlias(Validator $validator): Validator {

        return $validator;
    }

    /**
     * @param StatuspagesFilter $StatuspagesFilter
     * @param $PaginateOMat | null
     * @param $MY_RIGHTS
     * @return array
     */
    public function getStatuspagesIndex(StatuspagesFilter $StatuspagesFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $indexFilter = $StatuspagesFilter->indexFilter();
        $query = $this->find()
            ->contain(['Hosts', 'Services', 'Hostgroups', 'Servicegroups'])
            ->where($indexFilter);

        if (!empty($MY_RIGHTS)) {
            $query->where(['Statuspages.container_id IN' => $MY_RIGHTS]);
        }

        $query->order($StatuspagesFilter->getOrderForPaginator('Statuspages.id', 'asc'))
            ->disableHydration();

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
        return $this->exists(['Statuspages.id' => $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isPublic($id) {
        $conditions = ['Statuspages.id' => $id, 'Statuspages.public' => 1];
        $query = $this->find()->where($conditions)->first();
        if (empty($query)) {
            return false;
        }
        return true;
    }

    /**
     * @param int $id
     * @param array $MY_RIGHTS
     * @param UserTime $UserTime
     * @return array
     */
    public function getStatuspageForView(int $id, array $MY_RIGHTS, UserTime $UserTime) {
        $statuspage = $this->getStatuspageWithAllObjects($id, $MY_RIGHTS);

        $showDowntimes = $statuspage['show_downtimes'];
        $showDowntimeComments = $statuspage['show_downtime_comments'];
        $showAcknowledgements = $statuspage['show_acknowledgements'];
        $showAcknowledgementComments = $statuspage['show_acknowledgement_comments'];

        // Merge all host and service uuids to select the host and service status
        $hostUuids = [];
        $serviceUuids = [];
        foreach ($statuspage['hosts'] as $key => $host) {
            $hostUuids[$host['id']] = $host['uuid'];
            $statuspage['hosts'][$key]['host_uuids'] = [
                $host['uuid'] => null // We make this to have the same code for hosts, host groups, service groups and services
            ];
            $statuspage['hosts'][$key]['service_uuids'] = [];
            foreach ($host['services'] as $service) {
                $serviceUuids[$service['id']] = $service['uuid'];
                $statuspage['hosts'][$key]['service_uuids'][$service['uuid']] = null;
            }
        }

        foreach ($statuspage['services'] as $key => $service) {
            $serviceUuids[$service['id']] = $service['uuid'];
            $hostUuids[$service['host']['id']] = $service['host']['uuid'];

            // We make this to have the same code for hosts, host groups, service groups and services
            $statuspage['services'][$key]['host_uuids'] = [
                $service['host']['uuid'] => null
            ];
            $statuspage['services'][$key]['service_uuids'] = [
                $service['uuid'] => null
            ];
        }

        foreach ($statuspage['hostgroups'] as $key => $hostgroup) {
            $statuspage['hostgroups'][$key]['host_uuids'] = [];
            $statuspage['hostgroups'][$key]['service_uuids'] = [];
            foreach ($hostgroup['hosts'] as $host) {
                $hostUuids[$host['id']] = $host['uuid']; // Store all host uuids for the status query
                $statuspage['hostgroups'][$key]['host_uuids'][$host['uuid']] = null; // store the status of the host in here to determine the worst host status
                foreach ($host['services'] as $service) {
                    $serviceUuids[$service['id']] = $service['uuid'];
                    $statuspage['hostgroups'][$key]['service_uuids'][$service['uuid']] = null;
                }
            }

            foreach ($hostgroup['hosttemplates'] as $hosttemplate) {
                foreach ($hosttemplate['hosts'] as $host) {
                    $hostUuids[$host['id']] = $host['uuid'];
                    $statuspage['hostgroups'][$key]['host_uuids'][$host['uuid']] = null;
                    foreach ($host['services'] as $service) {
                        $serviceUuids[$service['id']] = $service['uuid'];
                        $statuspage['hostgroups'][$key]['service_uuids'][$service['uuid']] = null;
                    }
                }
            }
        }

        foreach ($statuspage['servicegroups'] as $key => $servicegroup) {
            $statuspage['servicegroups'][$key]['host_uuids'] = [];
            $statuspage['servicegroups'][$key]['service_uuids'] = [];
            foreach ($servicegroup['services'] as $service) {
                $serviceUuids[$service['id']] = $service['uuid'];
                $statuspage['servicegroups'][$key]['service_uuids'][$service['uuid']] = null;
                $hostUuids[$service['host']['id']] = $service['host']['uuid'];
                $statuspage['servicegroups'][$key]['host_uuids'][$service['host']['uuid']] = null;
            }

            foreach ($servicegroup['servicetemplates'] as $servicetemplate) {
                foreach ($servicetemplate['services'] as $service) {
                    $serviceUuids[$service['id']] = $service['uuid'];
                    $statuspage['servicegroups'][$key]['service_uuids'][$service['uuid']] = null;
                    $hostUuids[$service['host']['id']] = $service['host']['uuid'];
                    $statuspage['servicegroups'][$key]['host_uuids'][$service['host']['uuid']] = null;
                }
            }
        }

        // Query host and service status for all objects in two queries
        $DbBackend = new DbBackend();
        $HoststatusTable = $DbBackend->getHoststatusTable();
        $ServicestatusTable = $DbBackend->getServicestatusTable();

        $HoststatusFields = new HoststatusFields($DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->problemHasBeenAcknowledged()
            ->scheduledDowntimeDepth();

        $ServicestatusFields = new ServicestatusFields($DbBackend);
        $ServicestatusFields
            ->currentState()
            ->isHardstate()
            ->problemHasBeenAcknowledged()
            ->scheduledDowntimeDepth();

        $AllHoststatus = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
        $AckHostUuids = Hash::extract($AllHoststatus, '{s}.Hoststatus[problem_has_been_acknowledged=true].hostname');
        $DowntimeHostUuids = Hash::extract($AllHoststatus, '{s}.Hoststatus[scheduled_downtime_depth>0].hostname');

        $AllServicestatus = $ServicestatusTable->byUuids($serviceUuids, $ServicestatusFields);
        $AckServiceUuids = Hash::extract($AllServicestatus, '{s}.Servicestatus[problem_has_been_acknowledged=true].service_description');
        $DowntimeServiceUuids = Hash::extract($AllServicestatus, '{s}.Servicestatus[scheduled_downtime_depth>0].service_description');

        // Query Acknowledgements for all objects
        $AcknowledgementHostsTable = $DbBackend->getAcknowledgementHostsTable();
        $AcknowledgementServicesTable = $DbBackend->getAcknowledgementServicesTable();

        $AllHostAcknowledgemens = [];
        $AllServiceAcknowledgemens = [];
        if ($showAcknowledgements) {
            $AllHostAcknowledgemens = $AcknowledgementHostsTable->byUuids($AckHostUuids);
            $AllServiceAcknowledgemens = $AcknowledgementServicesTable->byUuids($AckServiceUuids);
        }

        // Query Downtimes for all objects
        $DowntimehistoryHostsTable = $DbBackend->getDowntimehistoryHostsTable();
        $DowntimehistoryServicesTable = $DbBackend->getDowntimehistoryServicesTable();

        $AllHostDowntimes = [];
        $AllServiceDowntimes = [];
        $AllPlannedHostDowntimes = [];
        $AllPlannedServiceDowntimes = [];

        if ($showDowntimes) {
            // Query all currently running downtimes
            $AllHostDowntimes = $DowntimehistoryHostsTable->byUuidsNoJoins($DowntimeHostUuids, true);
            $AllServiceDowntimes = $DowntimehistoryServicesTable->byUuidsNoJoins($DowntimeServiceUuids, true);

            // Query all planned downtimes for all objects
            $AllPlannedHostDowntimes = $DowntimehistoryHostsTable->getPlannedDowntimes($hostUuids, time(), (time() + (3600 * 24 * 10)));
            $AllPlannedServiceDowntimes = $DowntimehistoryServicesTable->getPlannedDowntimes($serviceUuids, time(), (time() + (3600 * 24 * 10)));
        }


        foreach ($statuspage['hosts'] as $host) {
            $hostUuids[$host['id']] = $host['uuid'];
            foreach ($host['services'] as $service) {
                $serviceUuids[$service['id']] = $service['uuid'];
            }
        }
        foreach ($statuspage['services'] as $service) {
            $serviceUuids[$service['id']] = $service['uuid'];
            $hostUuids[$service['host']['id']] = $service['host']['uuid'];
        }

        // Calculate worst state per object
        // Cumulate all object types
        foreach (['hosts', 'services', 'hostgroups', 'servicegroups'] as $objectType) {
            foreach ($statuspage[$objectType] as $index => $objectGroup) {
                $statuspage[$objectType][$index]['state_summary'] = [
                    'hosts'    => [
                        'state'                    => [
                            0 => 0, // Up
                            1 => 0, // Down
                            2 => 0, // Unreachable
                        ],
                        'acknowledgements'         => 0,
                        'acknowledgement_details'  => [],
                        'downtimes'                => 0,
                        'downtime_details'         => [],
                        'planned_downtime_details' => [],
                        'total'                    => 0, // Total amount of hosts
                        'problems'                 => 0, // Hosts in none up state
                        'cumulatedStateId'         => -1,
                        'cumulatedStateName'       => __('Not in Monitoring'),
                    ],
                    'services' => [
                        'state'                    => [
                            0 => 0, // OK
                            1 => 0, // Warning
                            2 => 0, // Critical
                            3 => 0, // Unknown
                        ],
                        'acknowledgements'         => 0,
                        'acknowledgement_details'  => [],
                        'downtimes'                => 0,
                        'downtime_details'         => [],
                        'planned_downtime_details' => [],
                        'total'                    => 0, // Total amount of services
                        'problems'                 => 0, // Services in none ok state
                        'cumulatedStateId'         => -1,
                        'cumulatedStateName'       => __('Not in Monitoring'),
                    ]
                ];

                foreach ($objectGroup['host_uuids'] as $hostUuid => $v) {
                    if (isset($AllPlannedHostDowntimes[$hostUuid])) {
                        $statuspage[$objectType][$index]['state_summary']['hosts']['planned_downtime_details'] = array_merge(
                            $statuspage[$objectType][$index]['state_summary']['hosts']['planned_downtime_details'],
                            $AllPlannedHostDowntimes[$hostUuid]
                        );
                    }
                    if (!isset($AllHoststatus[$hostUuid]['Hoststatus'])) {
                        continue;
                    }

                    $Hoststatus = new Hoststatus($AllHoststatus[$hostUuid]['Hoststatus']);
                    $statuspage[$objectType][$index]['state_summary']['hosts']['total']++;

                    $statuspage[$objectType][$index]['state_summary']['hosts']['state'][$Hoststatus->currentState()]++;

                    if ($Hoststatus->currentState() > 0) {
                        $statuspage[$objectType][$index]['state_summary']['hosts']['problems']++;
                    }

                    if ($Hoststatus->isAcknowledged() && $Hoststatus->currentState() > 0) {
                        $statuspage[$objectType][$index]['state_summary']['hosts']['acknowledgements']++;
                        if (isset($AllHostAcknowledgemens[$hostUuid])) {
                            $statuspage[$objectType][$index]['state_summary']['hosts']['acknowledgement_details'][] = (new AcknowledgementHost(
                                $AllHostAcknowledgemens[$hostUuid]
                            ))->toArray();
                        }
                    }
                    if ($Hoststatus->isInDowntime()) {
                        $statuspage[$objectType][$index]['state_summary']['hosts']['downtimes']++;
                        if (isset($AllHostDowntimes[$hostUuid])) {
                            $statuspage[$objectType][$index]['state_summary']['hosts']['downtime_details'][] = array_merge((new Downtime(
                                $AllHostDowntimes[$hostUuid]
                            ))->toArray(), ['hostname' => $hostUuid]);
                        }
                    }
                }

                foreach ($objectGroup['service_uuids'] as $serviceUuid => $v) {
                    if ($showDowntimes) {
                        if (isset($AllPlannedServiceDowntimes[$serviceUuid])) {
                            $statuspage[$objectType][$index]['state_summary']['services']['planned_downtime_details'] = array_merge(
                                $statuspage[$objectType][$index]['state_summary']['services']['planned_downtime_details'],
                                $AllPlannedServiceDowntimes[$serviceUuid]
                            );
                        }
                    }

                    if (!isset($AllServicestatus[$serviceUuid]['Servicestatus'])) {
                        continue;
                    }

                    $Servicestatus = new Servicestatus($AllServicestatus[$serviceUuid]['Servicestatus']);
                    $statuspage[$objectType][$index]['state_summary']['services']['total']++;

                    $statuspage[$objectType][$index]['state_summary']['services']['state'][$Servicestatus->currentState()]++;

                    if ($Servicestatus->currentState() > 0) {
                        $statuspage[$objectType][$index]['state_summary']['services']['problems']++;
                    }
                    if ($showAcknowledgements) {
                        if ($Servicestatus->isAcknowledged() && $Servicestatus->currentState() > 0) {
                            $statuspage[$objectType][$index]['state_summary']['services']['acknowledgements']++;
                            if (isset($AllServiceAcknowledgemens[$serviceUuid])) {
                                $statuspage[$objectType][$index]['state_summary']['services']['acknowledgement_details'][] = (new AcknowledgementService(
                                    $AllServiceAcknowledgemens[$serviceUuid]
                                ))->toArray();
                            }
                        }
                    }
                    if ($showDowntimes) {
                        if ($Servicestatus->isInDowntime()) {
                            $statuspage[$objectType][$index]['state_summary']['services']['downtimes']++;
                            if (isset($AllServiceDowntimes[$serviceUuid])) {
                                $statuspage[$objectType][$index]['state_summary']['services']['downtime_details'][] = array_merge((new Downtime(
                                    $AllServiceDowntimes[$serviceUuid]
                                ))->toArray(), ['name' => $serviceUuid]);
                            }
                        }
                    }
                }
            }
        }

        // Set cumulatedState state for hosts and services
        $itemTypes = [
            'hosts'         => 'host',
            'services'      => 'service',
            'hostgroups'    => 'hostgroup',
            'servicegroups' => 'servicegroup'
        ];

        $stateIcons = [
            -1 => 'fa-solid fa-eye-low-vision',
            0  => 'fa-solid fa-check',
            1  => 'fa-solid fa-triangle-exclamation',
            2  => 'fa-solid fa-bolt',
            3  => 'fa-solid fa-question',
        ];

        $stateNames = [
            'hosts'    => [
                -1 => __('Not in Monitoring'),
                0  => __('Operational'),
                1  => __('Major Outage'),
                2  => __('Unknown'),
            ],
            'services' => [
                -1 => __('Not in Monitoring'),
                0  => __('Operational'),
                1  => __('Performance Issues'),
                2  => __('Major Outage'),
                3  => __('Unknown'),
            ]
        ];

        $stateColors = [
            'hosts'    => [
                -1 => 'not-monitored',
                0  => 'up',
                1  => 'down',
                2  => 'unreachable'
            ],
            'services' => [
                -1 => 'not-monitored',
                0  => 'ok',
                1  => 'warning',
                2  => 'critical',
                3  => 'unknown'
            ]
        ];

        // $items is used by all views
        $items = [];

        foreach (['hosts', 'services', 'hostgroups', 'servicegroups'] as $objectType) {
            foreach ($statuspage[$objectType] as $index => $objectGroup) {
                $name = $objectGroup['name'];
                if (!empty($objectGroup['_joinData']['display_alias'])) {
                    $name = $objectGroup['_joinData']['display_alias'];
                }

                $item = [
                    'type'                => $itemTypes[$objectType], // Legacy at its best
                    'id'                  => $objectGroup['id'],
                    'name'                => $name,
                    'cumulatedStateName'  => $stateNames['hosts'][-1], // State for humans
                    'cumulatedColorId'    => -1, // Numeric state representation
                    'cumulatedColor'      => 'not-monitored', // For texts, backgrounds and shadows
                    'isAcknowledge'       => false,
                    //'acknowledgedProblemsText' => __('State is not acknowledged'),
                    'acknowledgeComment'  => [],
                    'scheduledStartTime'  => null,
                    'scheduledEndTime'    => null,
                    'comment'             => null,
                    'isInDowntime'        => false,
                    'downtimeData'        => [],
                    'plannedDowntimeData' => [],

                ];

                // Get the worst host state
                foreach ($objectGroup['state_summary']['hosts']['state'] as $state => $stateCount) {
                    if ($stateCount > 0) {
                        $statuspage[$objectType][$index]['state_summary']['hosts']['cumulatedStateId'] = $state;
                        $statuspage[$objectType][$index]['state_summary']['hosts']['cumulatedStateName'] = $stateNames['hosts'][$state];
                    }
                }
                // Get the worst service state
                foreach ($objectGroup['state_summary']['services']['state'] as $state => $stateCount) {
                    if ($stateCount > 0) {
                        $statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateId'] = $state;
                        $statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateName'] = $stateNames['services'][$state];
                    }
                }

                // If the host is up -> use worst service state
                // IF host is down (or unreachable) use the host state (service state not needed in this case)
                // This is the same behavior as we use on Maps

                // Host is UP - use cumulated service status (just like on maps)
                // Merge host state and service state into one single cumulated state
                $cumulatedStateId = $statuspage[$objectType][$index]['state_summary']['hosts']['cumulatedStateId'];
                $cumulatedStateName = $statuspage[$objectType][$index]['state_summary']['hosts']['cumulatedStateName'];
                $item['cumulatedStateName'] = $cumulatedStateName;
                $item['cumulatedColorId'] = $cumulatedStateId;
                $item['cumulatedColor'] = $stateColors['hosts'][$cumulatedStateId];

                //only relevant for host and host groups
                if (in_array($objectType, ['hosts', 'hostgroups'], true)) {
                    if ($showDowntimes) {
                        if (count($objectGroup['state_summary']['hosts']['planned_downtime_details']) > 0) {
                            $plannedDowntimeDataHosts = [];
                            foreach ($objectGroup['state_summary']['hosts']['planned_downtime_details'] as $planned) {
                                $downtimePlannedDataHost = [];
                                $downtimePlannedDataHost['scheduledStartTimestamp'] = $planned['scheduled_start_time'];
                                $downtimePlannedDataHost['scheduledStartTime'] = $UserTime->format($planned['scheduled_start_time'] ?? 0);
                                $downtimePlannedDataHost['scheduledEndTime'] = $UserTime->format($planned['scheduled_end_time'] ?? 0);
                                $downtimePlannedDataHost['comment'] = ($showDowntimeComments)
                                    ? $planned['comment_data'] : __('Upcoming maintenance');
                                $plannedDowntimeDataHosts[] = $downtimePlannedDataHost;
                            }
                            $plannedDowntimeDataHosts = Hash::sort(
                                $plannedDowntimeDataHosts,
                                '{n}.scheduledStartTimestamp', 'asc'
                            );
                            $item['plannedDowntimeData'] = $plannedDowntimeDataHosts;
                        }

                        if (count($objectGroup['state_summary']['hosts']['downtime_details']) > 0) {
                            $downtimeDataHosts = [];
                            foreach ($objectGroup['state_summary']['hosts']['downtime_details'] as $currentDowntime) {
                                $downtimeDataHost = [];
                                $downtimeDataHost['scheduledStartTimestamp'] = $currentDowntime['scheduledStartTime'];
                                $downtimeDataHost['scheduledStartTime'] = $UserTime->format($currentDowntime['scheduledStartTime'] ?? 0);
                                $downtimeDataHost['scheduledEndTime'] = $UserTime->format($currentDowntime['scheduledEndTime'] ?? 0);
                                $downtimeDataHost['comment'] = ($showDowntimeComments)
                                    ? $currentDowntime['commentData'] : __('Work in progress');
                                $downtimeDataHosts[] = $downtimeDataHost;
                            }
                            $downtimeDataHosts = Hash::sort(
                                $downtimeDataHosts,
                                '{n}.scheduledStartTimestamp', 'asc'
                            );
                            $item['isInDowntime'] = true;
                            $item['downtimeData'] = $downtimeDataHosts;
                        }
                    }
                    if ($showAcknowledgements) {
                        if ($objectGroup['state_summary']['hosts']['acknowledgements'] > 0) {
                            if ($objectType === 'hosts') {
                                $item['isAcknowledge'] = true;
                            }
                            foreach ($objectGroup['state_summary']['hosts']['acknowledgement_details'] as $currentAcknowledgement) {
                                // $item['acknowledgedProblemsText'] = __('State is acknowledged');
                                $item['acknowledgeComment'][] = ($showAcknowledgementComments)
                                    ? $currentAcknowledgement['comment_data'] : __('Investigating issue');
                            }

                            $problems = $objectGroup['state_summary']['hosts']['problems'];
                            if ($problems > 0) {
                                $problemsAcknowledged = $objectGroup['state_summary']['hosts']['acknowledgements'];
                                $item['acknowledgedProblemsText'] = __('{0} of {1} problems acknowledged', $problemsAcknowledged, $problems);
                            }
                        }
                    }

                }


                if ($statuspage[$objectType][$index]['state_summary']['hosts']['cumulatedStateId'] > 0 &&
                    in_array($objectType, ['hosts', 'hostgroups'], true)) {
                    // Host is down or unreachable - use the host status only
                    // +1 shifts a host state into a service state so we can use a single array
                    $item['cumulatedColorId'] = $cumulatedStateId + 1;

                } else {
                    // Set initial state for service or service groups
                    if (in_array($objectType, ['services', 'servicegroups'], true)) {
                        $cumulatedStateId = $statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateId'];
                        $cumulatedStateName = $statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateName'];
                        $item['cumulatedStateName'] = $cumulatedStateName;
                        $item['cumulatedColorId'] = $cumulatedStateId;
                        $item['cumulatedColor'] = $stateColors['services'][$cumulatedStateId];
                    }

                    // All hosts are up - Is there a service with an issue?
                    if ($statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateId'] > 0) {
                        $cumulatedStateId = $statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateId'];
                        $cumulatedStateName = $statuspage[$objectType][$index]['state_summary']['services']['cumulatedStateName'];
                        $item['cumulatedStateName'] = $cumulatedStateName;
                        $item['cumulatedColorId'] = $cumulatedStateId;
                        $item['cumulatedColor'] = $stateColors['services'][$cumulatedStateId];
                    }
                }
                if ($showAcknowledgements) {
                    if (in_array($objectType, ['hostgroups', 'hosts'])) {
                        //eg. host is up, but serviceproblems
                        $serviceProblems = $objectGroup['state_summary']['services']['problems'];
                        $hostProblems = $objectGroup['state_summary']['hosts']['problems'];
                        $problems = $serviceProblems + $hostProblems;
                        if ($problems > 0) {
                            $serviceProblemsAcknowledged = $objectGroup['state_summary']['services']['acknowledgements'];
                            $hostProblemsAcknowledged = $objectGroup['state_summary']['hosts']['acknowledgements'];
                            $acknwledged = $serviceProblemsAcknowledged + $hostProblemsAcknowledged;
                            $item['acknowledgedProblemsText'] = __('{0} of {1} problems acknowledged', $acknwledged, $problems);
                        }

                        if ($objectGroup['state_summary']['hosts']['acknowledgements'] > 0
                            || $objectGroup['state_summary']['services']['acknowledgements'] > 0) {
                            foreach ($objectGroup['state_summary']['services']['acknowledgement_details'] as $currentAcknowledgement) {
                                // $item['acknowledgedProblemsText'] = __('State is acknowledged');
                                $item['acknowledgeComment'][] = ($showAcknowledgementComments)
                                    ? $currentAcknowledgement['comment_data'] : __('Investigating issue');
                            }
                        }
                    }
                    if (in_array($objectType, ['servicegroups', 'services'])) {
                        //eg. host is up, but serviceproblems
                        $serviceProblems = $objectGroup['state_summary']['services']['problems'];
                        if ($serviceProblems > 0) {
                            $serviceProblemsAcknowledged = $objectGroup['state_summary']['services']['acknowledgements'];
                            $item['acknowledgedProblemsText'] = __('{0} of {1} problems acknowledged', $serviceProblemsAcknowledged, $serviceProblems);
                        }

                        if ($objectGroup['state_summary']['services']['acknowledgements'] > 0) {
                            foreach ($objectGroup['state_summary']['services']['acknowledgement_details'] as $currentAcknowledgement) {
                                // $item['acknowledgedProblemsText'] = __('State is acknowledged');
                                $item['acknowledgeComment'][] = ($showAcknowledgementComments)
                                    ? $currentAcknowledgement['comment_data'] : __('Investigating issue');
                            }
                        }
                    }

                }

                if ($showDowntimes) {
                    if (in_array($objectType, ['services', 'hosts', 'servicegroups', 'hostgroups'])) {
                        if ($objectGroup['state_summary']['services']['downtimes'] > 0) {
                            $downtimeDataServices = [];
                            foreach ($objectGroup['state_summary']['services']['downtime_details'] as $downtime) {
                                $downtimeDataService = [];
                                $downtimeDataService['scheduledStartTimestamp'] = $downtime['scheduledStartTime'];
                                $downtimeDataService['scheduledStartTime'] = $UserTime->format($downtime['scheduledStartTime'] ?? 0);
                                $downtimeDataService['scheduledEndTime'] = $UserTime->format($downtime['scheduledEndTime'] ?? 0);
                                $downtimeDataService['comment'] = ($showDowntimeComments)
                                    ? $downtime['commentData'] : __('Work in progress');
                                if ($objectType === 'services') {
                                    $item['isInDowntime'] = true;
                                }
                                $downtimeDataServices[] = $downtimeDataService;
                            }
                            if (!empty($item['downtimeData'])) {
                                $downtimeDataServices = array_merge(
                                    $downtimeDataServices,
                                    $item['downtimeData']
                                );
                            }
                            $downtimeDataServices = Hash::sort(
                                $downtimeDataServices,
                                '{n}.scheduledStartTimestamp', 'asc'
                            );
                            $item['downtimeData'] = $downtimeDataServices;
                        }
                        if (count($objectGroup['state_summary']['services']['planned_downtime_details']) > 0) {
                            $plannedDowntimeDataServices = [];
                            foreach ($objectGroup['state_summary']['services']['planned_downtime_details'] as $planned) {
                                $downtimePlannedDataService = [];
                                $downtimePlannedDataService['scheduledStartTimestamp'] = $planned['scheduled_start_time'];
                                $downtimePlannedDataService['scheduledStartTime'] = $UserTime->format($planned['scheduled_start_time'] ?? 0);
                                $downtimePlannedDataService['scheduledEndTime'] = $UserTime->format($planned['scheduled_end_time'] ?? 0);
                                $downtimePlannedDataService['comment'] = ($showDowntimeComments)
                                    ? $planned['comment_data'] : __('Upcoming maintenance');
                                $plannedDowntimeDataServices[] = $downtimePlannedDataService;
                            }
                            if (!empty($item['plannedDowntimeData'])) {
                                $plannedDowntimeDataServices = array_merge(
                                    $plannedDowntimeDataServices,
                                    $item['plannedDowntimeData']
                                );
                            }
                            $plannedDowntimeDataServices = Hash::sort(
                                $plannedDowntimeDataServices,
                                '{n}.scheduledStartTimestamp', 'asc'
                            );
                            $item['plannedDowntimeData'] = $plannedDowntimeDataServices;
                        }
                    }
                }

                $items[] = $item;
            }
        }

        if (empty($items)) {
            return [
                'statuspage' => [
                    'name'                        => $statuspage['name'],
                    'description'                 => $statuspage['description'],
                    'public_title'                => $statuspage['public_title'],
                    'public'                      => $statuspage['public'],
                    'showDowntimes'               => $statuspage['show_downtimes'],
                    'showDowntimeComments'        => $statuspage['show_downtime_comments'],
                    'showAcknowledgements'        => $statuspage['show_acknowledgements'],
                    'showAcknowledgementComments' => $statuspage['show_acknowledgement_comments'],
                    'cumulatedColorId'            => -1,
                    'cumulatedColor'              => 'primary',
                    'cumulatedHumanStatus'        => __('Not in Monitoring'),
                    'cumulatedIcon'               => 'fa-solid fa-eye-low-vision',
                ],
                'items'      => [],
            ];
        }

        $items = Hash::sort($items, '{n}.cumulatedColorId', 'desc');

        $statuspageView = [
            'statuspage' => [
                'name'                        => $statuspage['name'],
                'description'                 => $statuspage['description'],
                'public_title'                => $statuspage['public_title'],
                'public'                      => $statuspage['public'],
                'showDowntimes'               => $statuspage['show_downtimes'],
                'showDowntimeComments'        => $statuspage['show_downtime_comments'],
                'showAcknowledgements'        => $statuspage['show_acknowledgements'],
                'showAcknowledgementComments' => $statuspage['show_acknowledgement_comments'],
                'cumulatedColorId'            => $items[0]['cumulatedColorId'] ?? -1,
                'cumulatedColor'              => $items[0]['cumulatedColor'],
                'cumulatedHumanStatus'        => $items[0]['cumulatedStateName'],
                'cumulatedIcon'               => $stateIcons[$items[0]['cumulatedColorId']] ?? 'fa-solid fa-eye-low-vision',
            ],
            'items'      => $items,
        ];
        return $statuspageView;
    }

    /**
     * @param int $id
     * @param array $MY_RIGHTS
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getStatuspageWithAllObjects(int $id, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->contain('Containers', function (Query $q) {
                $q->select([
                    'Containers.id',
                    'Containers.name'
                ]);
                return $q;
            })
            ->contain('Hosts', function (Query $q) use ($MY_RIGHTS) {
                $q
                    ->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.name'
                    ]);
                if (!empty($MY_RIGHTS)) {
                    $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                        'HostsToContainersSharing.host_id = Hosts.id'
                    ]);
                    $q->where([
                        'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                    ]);
                }
                $q->contain([
                    'HostsToContainersSharing',
                    'Services' => function (Query $q) {
                        return $q->where([
                            'Services.disabled' => 0
                        ])
                            ->select([
                                'Services.id',
                                'Services.uuid',
                                'Services.host_id'
                            ]);
                    }
                ])->where([
                    'Hosts.disabled' => 0
                ]);

                return $q;
            })
            ->contain('Services', function (Query $q) use ($MY_RIGHTS) {
                return $q
                    ->select([
                        'Services.id',
                        'Services.uuid',
                        'Services.host_id',
                        'name' => $q->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
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
                    ])
                    ->group(['Services.id']);
            })
            ->contain('Hostgroups', function (Query $q) use ($MY_RIGHTS) {
                return $q
                    ->select([
                        'Hostgroups.id',
                        'Hostgroups.uuid',
                        'Containers.name',
                        'name' => 'Containers.name'
                    ])
                    ->contain([
                        'Containers'    => function (Query $q) {
                            return $q->select([
                                'Containers.id',
                                'Containers.name'
                            ]);
                        },
                        'Hosts'         => function (Query $q) use ($MY_RIGHTS) {
                            $q->select([
                                'Hosts.id',
                                'Hosts.uuid',
                                'Hosts.name'
                            ]);
                            if (!empty($MY_RIGHTS)) {
                                $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                    'HostsToContainersSharing.host_id = Hosts.id'
                                ]);
                                $q->where([
                                    'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                ]);
                            }
                            $q->contain([
                                'HostsToContainersSharing',
                                'Services' => function (Query $q) {
                                    return $q->where([
                                        'Services.disabled' => 0
                                    ])
                                        ->select([
                                            'Services.id',
                                            'Services.uuid',
                                            'Services.host_id'
                                        ]);
                                }
                            ])->where([
                                'Hosts.disabled' => 0
                            ]);
                            return $q;
                        },
                        'Hosttemplates' => function (Query $q) use ($MY_RIGHTS) {
                            return $q->enableAutoFields(false)
                                ->select([
                                    'id'
                                ])
                                ->contain([
                                    'Hosts' => function (Query $query) use ($MY_RIGHTS) {
                                        $query->select([
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

                                        $query
                                            ->disableAutoFields()
                                            ->select([
                                                'Hosts.id',
                                                'Hosts.uuid',
                                                'Hosts.name',
                                                'Hosts.hosttemplate_id'
                                            ])
                                            ->contain([
                                                'HostsToContainersSharing',
                                                'Services' => function (Query $q) {
                                                    return $q->select([
                                                        'Services.id',
                                                        'Services.uuid',
                                                        'Services.host_id'
                                                    ]);
                                                }
                                            ]);
                                        $query
                                            ->leftJoinWith('Hostgroups')
                                            ->whereNull('Hostgroups.id');
                                        return $query;
                                    }
                                ]);
                        }
                    ]);
            })
            ->contain('Servicegroups', function (Query $q) use ($MY_RIGHTS) {
                return $q
                    ->select([
                        'Servicegroups.id',
                        'Servicegroups.uuid',
                        'Containers.name',
                        'name' => 'Containers.name'
                    ])
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
                    ]);
            })
            ->where([
                'Statuspages.id' => $id
            ])
            ->disableHydration();

        return $query->firstOrFail();
    }

    public function getStatuspageForEdit($id) {
        $query = $this->find()
            ->where([
                'Statuspages.id' => $id
            ])
            ->contain([
                'Hostgroups'    => function (Query $query) {
                    return $query->select([
                        'Hostgroups.id'
                    ]);
                },
                'Servicegroups' => function (Query $query) {
                    return $query->select([
                        'Servicegroups.id'
                    ]);
                },
                'Hosts'         => function (Query $query) {
                    return $query->select([
                        'Hosts.id'
                    ]);
                },
                'Services'      => function (Query $query) {
                    return $query->select([
                        'Services.id'
                    ]);
                }
            ])
            ->disableHydration()
            ->first();

        $statuspage = $query;
        $statuspage['selected_hostgroups'] = [
            '_ids' => Hash::extract($query, 'hostgroups.{n}.id')
        ];


        $statuspage['selected_hosts'] = [
            '_ids' => Hash::extract($query, 'hosts.{n}.id')
        ];

        $statuspage['selected_servicegroups'] = [
            '_ids' => Hash::extract($query, 'servicegroups.{n}.id')
        ];
        $statuspage['selected_services'] = [
            '_ids' => Hash::extract($query, 'services.{n}.id')
        ];

        return [
            'Statuspage' => $statuspage
        ];
    }
}
