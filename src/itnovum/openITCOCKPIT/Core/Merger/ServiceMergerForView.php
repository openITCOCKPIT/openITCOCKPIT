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

namespace itnovum\openITCOCKPIT\Core\Merger;

use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\CustomVariableMerger;

/**
 * Class ServiceMergerForView
 *
 * Compares a given service with a given service template
 * Replace null values in $service array with the corresponding value of $servicetemplate
 *
 * @package itnovum\openITCOCKPIT\Core\Comparison
 */
class ServiceMergerForView {

    /**
     * @var array
     */
    private $service;

    /**
     * @var array
     */
    private $servicetemplate;

    /**
     * @var boolean
     */
    private $hasOwnContacts = false;

    /**
     * @var bool
     */
    private $hasOwnCustomvariables = false;

    /**
     * Contacts and contact groups of the host
     * @var array
     */
    private $hostContactAndContactgroups = [];

    /**
     * Contacts and contact groups of the host template
     * @var array
     */
    private $hosttemplateContactAndContactgroups = [];

    /**
     * @var bool
     */
    private $areContactsInheritedFromHosttemplate = false;

    /**
     * @var bool
     */
    private $areContactsInheritedFromHost = false;

    /**
     * @var bool
     */
    private $areContactsInheritedFromServicetemplate = false;

    /**
     * ServiceMergerForView constructor.
     * @param array $service
     * @param array $servicetemplate ServicetemplatesTable::$getServicetemplateForDiff()
     */
    public function __construct($service, $servicetemplate, $hostContactAndContactgroups = [], $hosttemplateContactAndContactgroups = []) {
        $this->service = $service['Service'];
        $this->servicetemplate = $servicetemplate['Servicetemplate'];

        if (empty($hostContactAndContactgroups)) {
            $hostContactAndContactgroups = [
                'contacts'      => [
                    '_ids' => []
                ],
                'contactgroups' => [
                    '_ids' => []
                ]
            ];
        }

        if (empty($hosttemplateContactAndContactgroups)) {
            $hosttemplateContactAndContactgroups = [
                'contacts'      => [
                    '_ids' => []
                ],
                'contactgroups' => [
                    '_ids' => []
                ]
            ];
        }

        $this->hostContactAndContactgroups = $hostContactAndContactgroups;
        $this->hosttemplateContactAndContactgroups = $hosttemplateContactAndContactgroups;
    }

    /**
     * @return array
     */
    public function getDataForView() {
        $data = $this->service;
        $data = array_merge($data, $this->getServiceBasicFields());
        $contactsAndContactgroups = $this->getDataForContactsAndContactgroups();
        $data['contacts'] = $contactsAndContactgroups['contacts'];
        $data['contactgroups'] = $contactsAndContactgroups['contactgroups'];
        $data['own_contacts'] = (int)$this->hasOwnContacts;
        $data['own_contactgroups'] = (int)$this->hasOwnContacts;
        $data['servicegroups'] = $this->getDataForServicegroups();
        $data['servicecommandargumentvalues'] = $this->getDataForCommandarguments();
        $data['customvariables'] = $this->getDataForCustomvariables();
        $data['own_customvariables'] = (int)$this->hasOwnCustomvariables;

        return [
            'Service' => $data
        ];
    }

    /**
     * @return array
     */
    public function getServiceBasicFields() {
        $fields = [
            'name',
            'description',
            'command_id',
            'eventhandler_command_id',
            'check_interval',
            'retry_interval',
            'max_check_attempts',
            'first_notification_delay',
            'notification_interval',
            'notify_on_recovery',
            'notify_on_warning',
            'notify_on_critical',
            'notify_on_unknown',
            'notify_on_flapping',
            'notify_on_downtime',
            'flap_detection_enabled',
            'flap_detection_on_ok',
            'flap_detection_on_warning',
            'flap_detection_on_critical',
            'flap_detection_on_unknown',
            'low_flap_threshold',
            'high_flap_threshold',
            'process_performance_data',
            'freshness_checks_enabled',
            'freshness_threshold',
            'passive_checks_enabled',
            'event_handler_enabled',
            'active_checks_enabled',
            'notifications_enabled',
            'notes',
            'priority',
            'check_period_id',
            'notify_period_id',
            'tags',
            'service_url',
            'is_volatile',
            'sla_relevant'
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($this->service[$field] === null || $this->service[$field] === '') {
                $data[$field] = $this->servicetemplate[$field];
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDataForContacts() {
        return $this->getDataForContactsAndContactgroups()['contacts'];
    }

    /**
     * @return array
     */
    public function getDataForContactgroups() {
        return $this->getDataForContactsAndContactgroups()['contactgroups'];
    }

    /**
     * @return bool
     */
    public function hasOwnContactsAndContactgroups() {
        return $this->hasOwnContacts;
    }

    /**
     * With Nagios 4 (and Naemon) the inheritance of contacts and contact groups has changed.
     * Service and Services can now only inherit contacts AND contact groups at the same time.
     *
     * This will not get fixed anymore.
     *
     * See https://github.com/naemon/naemon-core/pull/92
     * @return array
     */
    public function getDataForContactsAndContactgroups() {
        $this->areContactsInheritedFromHost = false;
        $this->areContactsInheritedFromHosttemplate = false;
        $this->areContactsInheritedFromServicetemplate = false;
        $this->hasOwnContacts = false;

        if (!empty($this->service['contacts']['_ids']) || empty(!$this->service['contactgroups']['_ids'])) {
            $this->hasOwnContacts = true;

            return [
                'contacts'      => [
                    '_ids' => $this->service['contacts']['_ids']
                ],
                'contactgroups' => [
                    '_ids' => $this->service['contactgroups']['_ids']
                ]
            ];
        }

        if (!empty($this->servicetemplate['contacts']['_ids']) || empty(!$this->servicetemplate['contactgroups']['_ids'])) {
            $this->hasOwnContacts = false;
            $this->areContactsInheritedFromServicetemplate = true;

            return [
                'contacts'      => [
                    '_ids' => $this->servicetemplate['contacts']['_ids']
                ],
                'contactgroups' => [
                    '_ids' => $this->servicetemplate['contactgroups']['_ids']
                ]
            ];
        }

        if (!empty($this->hostContactAndContactgroups['contacts']['_ids']) || empty(!$this->hostContactAndContactgroups['contactgroups']['_ids'])) {
            $this->hasOwnContacts = false;
            $this->areContactsInheritedFromHost = true;

            return [
                'contacts'      => [
                    '_ids' => $this->hostContactAndContactgroups['contacts']['_ids']
                ],
                'contactgroups' => [
                    '_ids' => $this->hostContactAndContactgroups['contactgroups']['_ids']
                ]
            ];
        }

        $this->hasOwnContacts = false;
        $this->areContactsInheritedFromHosttemplate = true;

        return [
            'contacts'      => [
                '_ids' => $this->hosttemplateContactAndContactgroups['contacts']['_ids']
            ],
            'contactgroups' => [
                '_ids' => $this->hosttemplateContactAndContactgroups['contactgroups']['_ids']
            ]
        ];

    }

    /**
     * @return array
     */
    public function getDataForServicegroups() {
        if (empty($this->service['servicegroups']['_ids'])) {
            return [
                '_ids' => $this->servicetemplate['servicegroups']['_ids']
            ];
        }

        //Service use own service groups
        return [
            '_ids' => $this->service['servicegroups']['_ids']
        ];
    }

    /**
     * @return array
     */
    public function getDataForCustomvariables() {
        if (empty($this->service['customvariables'])) {
            $this->hasOwnCustomvariables = false;
            return $this->servicetemplate['customvariables'];
        }

        $this->hasOwnCustomvariables = true;
        if (empty($this->servicetemplate['customvariables'])) {
            //Service template has no custom variables.
            return $this->service['customvariables'];
        }

        //Merge service custom variables and service template custom variables
        $CustomVariablesMerger = new CustomVariableMerger(
            $this->service['customvariables'],
            $this->servicetemplate['customvariables']
        );

        $CustomVariablesRepository = $CustomVariablesMerger->getCustomVariablesMergedAsRepository();
        return $CustomVariablesRepository->getAllCustomVariablesAsArray();
    }

    /**
     * @return bool
     */
    public function hasOwnCustomvariables() {
        return $this->hasOwnCustomvariables;
    }

    /**
     * @return array
     */
    public function getDataForCommandarguments() {
        if (empty($this->service['servicecommandargumentvalues'])) {
            if (($this->service['command_id'] === $this->servicetemplate['command_id'] || $this->service['command_id'] === null)) {
                return $this->servicetemplate['servicetemplatecommandargumentvalues'];
            }
        }
        $commandArgumentValuesDiff = array_diff(
            Hash::extract($this->servicetemplate['servicetemplatecommandargumentvalues'], '{n}.commandargument_id'),
            Hash::extract($this->service['servicecommandargumentvalues'], '{n}.commandargument_id')
        );
        if (!empty($commandArgumentValuesDiff) &&
            ($this->service['command_id'] === $this->servicetemplate['command_id'] || $this->service['command_id'] === null)) {
            //add missing (new) command argument values from host template to host command argument values
            foreach ($commandArgumentValuesDiff as $commandArgumentValueId) {
                $key = array_search($commandArgumentValueId, array_column($this->servicetemplate['servicetemplatecommandargumentvalues'], 'commandargument_id'), true);
                $this->service['servicecommandargumentvalues'][] = [
                    'commandargument_id' => $this->servicetemplate['servicetemplatecommandargumentvalues'][$key]['commandargument_id'],
                    'host_id'            => $this->service['id'],
                    'value'              => $this->servicetemplate['servicetemplatecommandargumentvalues'][$key]['value'],
                    'commandargument'    => $this->servicetemplate['servicetemplatecommandargumentvalues'][$key]['commandargument']
                ];
            }
        }
        return $this->service['servicecommandargumentvalues'];
    }

    /**
     * @return bool
     */
    public function areContactsInheritedFromHosttemplate() {
        return $this->areContactsInheritedFromHosttemplate;
    }

    /**
     * @return bool
     */
    public function areContactsInheritedFromHost() {
        return $this->areContactsInheritedFromHost;
    }

    /**
     * @return bool
     */
    public function areContactsInheritedFromServicetemplate() {
        return $this->areContactsInheritedFromServicetemplate;
    }

}
