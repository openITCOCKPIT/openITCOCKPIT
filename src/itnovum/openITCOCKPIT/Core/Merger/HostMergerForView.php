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
 * Class HostMergerForView
 *
 * Compares a given host with a given host template
 * Replace null values in $host array with the corresponding value of $hosttemplate
 *
 * @package itnovum\openITCOCKPIT\Core\Comparison
 */
class HostMergerForView {

    /**
     * @var array
     */
    private $host;

    /**
     * @var array
     */
    private $hosttemplate;

    /**
     * @var boolean
     */
    private $hasOwnContacts = false;

    /**
     * @var bool
     */
    private $hasOwnCustomvariables = false;

    /**
     * @var bool
     */
    private $areContactsInheritedFromHosttemplate = false;

    /**
     * HostComparison constructor.
     * @param array $host
     * @param array $hosttemplate HosttemplatesTable::$getHosttemplateForDiff()
     */
    public function __construct($host, $hosttemplate) {
        $this->host = $host['Host'];
        $this->hosttemplate = $hosttemplate['Hosttemplate'];
    }

    /**
     * @return array
     */
    public function getDataForView() {
        $data = $this->host;
        $data = array_merge($data, $this->getHostBasicFields());
        $contactsAndContactgroups = $this->getDataForContactsAndContactgroups();
        $data['contacts'] = $contactsAndContactgroups['contacts'];
        $data['contactgroups'] = $contactsAndContactgroups['contactgroups'];
        $data['own_contacts'] = (int)$this->hasOwnContacts;
        $data['own_contactgroups'] = (int)$this->hasOwnContacts;
        $data['hostgroups'] = $this->getDataForHostgroups();
        $data['hostcommandargumentvalues'] = $this->getDataForCommandarguments();
        $data['customvariables'] = $this->getDataForCustomvariables();
        $data['own_customvariables'] = (int)$this->hasOwnCustomvariables;
        $data['prometheus_exporters'] = $this->getDataForPrometheusExporters();
        return [
            'Host' => $data
        ];
    }

    /**
     * @return array
     */
    public function getHostBasicFields() {
        $fields = [
            'description',
            'command_id',
            'check_interval',
            'retry_interval',
            'max_check_attempts',
            'notification_interval',
            'notify_on_down',
            'notify_on_unreachable',
            'notify_on_recovery',
            'notify_on_flapping',
            'notify_on_downtime',
            'flap_detection_enabled',
            'flap_detection_on_up',
            'flap_detection_on_down',
            'flap_detection_on_unreachable',
            'notes',
            'priority',
            'check_period_id',
            'notify_period_id',
            'tags',
            'active_checks_enabled',
            'host_url',
            'freshness_checks_enabled',
            'freshness_threshold',
            'notifications_enabled',
            'sla_id'
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($this->host[$field] === null || $this->host[$field] === '') {
                $data[$field] = $this->hosttemplate[$field];
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
     * Host and Services can now only inherit contacts AND contact groups at the same time.
     *
     * This will not get fixed anymore.
     *
     * See https://github.com/naemon/naemon-core/pull/92
     * @return array
     */
    public function getDataForContactsAndContactgroups() {
        if (empty($this->host['contacts']['_ids']) && empty($this->host['contactgroups']['_ids'])) {
            $this->hasOwnContacts = false;
            $this->areContactsInheritedFromHosttemplate = true;

            return [
                'contacts'      => [
                    '_ids' => $this->hosttemplate['contacts']['_ids']
                ],
                'contactgroups' => [
                    '_ids' => $this->hosttemplate['contactgroups']['_ids']
                ]
            ];
        } else {
            $this->hasOwnContacts = true;
            $this->areContactsInheritedFromHosttemplate = false;

            return [
                'contacts'      => [
                    '_ids' => $this->host['contacts']['_ids']
                ],
                'contactgroups' => [
                    '_ids' => $this->host['contactgroups']['_ids']
                ]
            ];
        }
    }

    /**
     * @return array
     */
    public function getDataForHostgroups() {
        if (empty($this->host['hostgroups']['_ids'])) {
            //Host use own host groups
            return [
                '_ids' => $this->hosttemplate['hostgroups']['_ids']
            ];
        }

        return [
            '_ids' => $this->host['hostgroups']['_ids']
        ];
    }

    /**
     * @return array
     */
    public function getDataForCustomvariables() {
        if (empty($this->host['customvariables'])) {
            $this->hasOwnCustomvariables = false;
            return $this->hosttemplate['customvariables'];
        }

        $this->hasOwnCustomvariables = true;
        if (empty($this->hosttemplate['customvariables'])) {
            //Host template has no custom variables.
            return $this->host['customvariables'];
        }

        //Merge host custom variables and host template custom variables
        $CustomVariablesMerger = new CustomVariableMerger(
            $this->host['customvariables'],
            $this->hosttemplate['customvariables']
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
        if (empty($this->host['hostcommandargumentvalues'])) {
            if (($this->host['command_id'] === $this->hosttemplate['command_id'] || $this->host['command_id'] === null)) {
                return $this->hosttemplate['hosttemplatecommandargumentvalues'];
            }
        }
        $commandArgumentValuesDiff = array_diff(
            Hash::extract($this->hosttemplate['hosttemplatecommandargumentvalues'], '{n}.commandargument_id'),
            Hash::extract($this->host['hostcommandargumentvalues'], '{n}.commandargument_id')
        );
        if (!empty($commandArgumentValuesDiff) &&
            ($this->host['command_id'] === $this->hosttemplate['command_id'] || $this->host['command_id'] === null)) {
            //add missing (new) command argument values from host template to host command argument values
            foreach ($commandArgumentValuesDiff as $commandArgumentValueId) {
                $key = array_search($commandArgumentValueId, array_column($this->hosttemplate['hosttemplatecommandargumentvalues'], 'commandargument_id'), true);
                $this->host['hostcommandargumentvalues'][] = [
                    'commandargument_id' => $this->hosttemplate['hosttemplatecommandargumentvalues'][$key]['commandargument_id'],
                    'host_id'            => $this->host['id'],
                    'value'              => $this->hosttemplate['hosttemplatecommandargumentvalues'][$key]['value'],
                    'commandargument'    => $this->hosttemplate['hosttemplatecommandargumentvalues'][$key]['commandargument']
                ];
            }
        }
        return $this->host['hostcommandargumentvalues'];
    }

    /**
     * @return bool
     */
    public function areContactsInheritedFromHosttemplate() {
        return $this->areContactsInheritedFromHosttemplate;
    }

    /**
     * @return array
     */
    public function getDataForPrometheusExporters() {
        if (empty($this->host['prometheus_exporters']['_ids'])) {
            //Host use Prometheus exporters of the Host template
            return [
                '_ids' => $this->hosttemplate['prometheus_exporters']['_ids']
            ];
        }

        return [
            '_ids' => $this->host['prometheus_exporters']['_ids']
        ];
    }

}
