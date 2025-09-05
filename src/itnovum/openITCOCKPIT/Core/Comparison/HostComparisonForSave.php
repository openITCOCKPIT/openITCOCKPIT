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

namespace itnovum\openITCOCKPIT\Core\Comparison;

use itnovum\openITCOCKPIT\Core\CustomVariableDiffer;

/**
 * Class HostComparison
 *
 * Compares a given host with a given host template
 * Replace equal values with null in the $host array.
 *
 * @package itnovum\openITCOCKPIT\Core\Comparison
 */
class HostComparisonForSave {

    /**
     * @var array
     */
    private $host;

    /**
     * @var array
     */
    private $hosttemplate;

    /**
     * @var bool
     */
    private $hasOwnContacts = false;

    /**
     * @var bool
     */
    private $hasOwnCustomvariables = false;


    /**
     * @var bool
     */
    private $newHost;


    /**
     * HostComparison constructor.
     * @param array $host
     * @param array $hosttemplate HosttemplatesTable::$getHosttemplateForDiff()
     */
    public function __construct($host, $hosttemplate, $newHost = false) {
        $this->host = $host['Host'];
        $this->hosttemplate = $hosttemplate['Hosttemplate'];
        $this->newHost = $newHost;
    }

    /**
     * @return array
     */
    public function getDataForSaveForAllFields() {
        $data = $this->getHostBasicFields();

        $contactsAndContactgroups = $this->getDataForContactsAndContactgroups();
        $data['contacts'] = $contactsAndContactgroups['contacts'];
        $data['contactgroups'] = $contactsAndContactgroups['contactgroups'];
        $data['hostgroups'] = $this->getDataForHostgroups();
        $data['hostcommandargumentvalues'] = $this->getDataForCommandarguments();
        $data['customvariables'] = $this->getDataForCustomvariables();
        $data['prometheus_exporters'] = $this->getDataForPrometheusExporters();

        //Add host default data
        $data['host_type'] = isset($this->host['host_type']) ? $this->host['host_type'] : GENERIC_HOST;
        $data['usage_flag'] = isset($this->host['usage_flag']) ? $this->host['usage_flag'] : 0;
        $data['own_contacts'] = (int)$this->hasOwnContacts;
        $data['own_contactgroups'] = (int)$this->hasOwnContacts;
        $data['own_customvariables'] = (int)$this->hasOwnCustomvariables;
        $data['name'] = $this->host['name'];
        $data['hosttemplate_id'] = $this->host['hosttemplate_id'];
        $data['address'] = $this->host['address'];
        $data['container_id'] = $this->host['container_id'];
        $data['hosts_to_containers_sharing'] = isset($this->host['hosts_to_containers_sharing']) ? $this->host['hosts_to_containers_sharing'] : [];
        $data['satellite_id'] = isset($this->host['satellite_id']) ? $this->host['satellite_id'] : 0;

        if (isset($data['hosts_to_containers_sharing']['_ids'])) {
            $data['hosts_to_containers_sharing']['_ids'][] = $data['container_id'];
            $data['hosts_to_containers_sharing']['_ids'] = array_unique($data['hosts_to_containers_sharing']['_ids']);
        }

        //only for new host (add)
        if (!isset($data['hosts_to_containers_sharing']['_ids']) && $this->newHost === true) {
            $data['hosts_to_containers_sharing']['_ids'][] = $data['container_id'];
        }

        $data['parenthosts'] = isset($this->host['parenthosts']) ? $this->host['parenthosts'] : [];

        return $data;
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
            'freshness_checks_enabled',
            'freshness_threshold',
            'host_url',
            'notifications_enabled',
            'sla_id'
        ];

        $data = [];
        foreach ($fields as $field) {
            if (isset($this->host[$field]) && $this->host[$field] != $this->hosttemplate[$field]) {
                $data[$field] = $this->host[$field];
            } else {
                $data[$field] = null;
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
        // for easy host add in wizard - use from template
        if (!isset($this->host['contacts']['_ids']) && !isset($this->host['contactgroups']['_ids'])) {
            return [
                'contacts'      => [
                    '_ids' => []
                ],
                'contactgroups' => [
                    '_ids' => []
                ]
            ];
        }

        //Where contacts changed or edited?
        $contactsDiff = array_diff($this->host['contacts']['_ids'], $this->hosttemplate['contacts']['_ids']);
        if (empty($contactsDiff)) {
            //Check if contacts got removed
            $contactsDiff = array_diff($this->hosttemplate['contacts']['_ids'], $this->host['contacts']['_ids']);
        }
        $this->hasOwnContacts = !empty($contactsDiff);

        //Where contact groups changed or edited?
        $contactgroupsDiff = array_diff($this->host['contactgroups']['_ids'], $this->hosttemplate['contactgroups']['_ids']);
        if (empty($contactgroupsDiff)) {
            //Check if contact groups got removed
            $contactgroupsDiff = array_diff($this->hosttemplate['contactgroups']['_ids'], $this->host['contactgroups']['_ids']);
        }

        if ($this->hasOwnContacts === false) {
            $this->hasOwnContacts = !empty($contactgroupsDiff);
        }

        if (!empty($contactsDiff) && !empty($contactgroupsDiff)) {
            //Contacts AND contact groups where modified
            //Due to https://github.com/naemon/naemon-core/pull/92
            //always save contacts AND contactgroups on a diff

            return [
                'contacts'      => [
                    '_ids' => $this->host['contacts']['_ids'],
                ],
                'contactgroups' => [
                    '_ids' => $this->host['contactgroups']['_ids']
                ]
            ];
        }

        if (empty($contactsDiff) && !empty($contactgroupsDiff)) {
            //Contact groups have been modified
            //Due to https://github.com/naemon/naemon-core/pull/92
            //always save contacts AND contactgroups on a diff

            return [
                'contacts'      => [
                    '_ids' => $this->hosttemplate['contacts']['_ids']
                ],
                'contactgroups' => [
                    '_ids' => $this->host['contactgroups']['_ids']
                ]
            ];
        }

        if (empty($contactgroupsDiff) && !empty($contactsDiff)) {
            //Contacts have been modified
            //Due to https://github.com/naemon/naemon-core/pull/92
            //always save contacts AND contactgroups on a diff

            return [
                'contacts'      => [
                    '_ids' => $this->host['contacts']['_ids'],
                ],
                'contactgroups' => [
                    '_ids' => $this->hosttemplate['contactgroups']['_ids']
                ]
            ];
        }

        return [
            'contacts'      => [
                '_ids' => []
            ],
            'contactgroups' => [
                '_ids' => []
            ]
        ];
    }

    /**
     * @return array
     */
    public function getDataForHostgroups() {
        // for easy host add in wizard - use from template
        if (!isset($this->host['hostgroups']['_ids'])) {
            return [
                '_ids' => []
            ];
        }

        //Where hostgroups changed or edited?
        $hostgroupsDiff = array_diff($this->host['hostgroups']['_ids'], $this->hosttemplate['hostgroups']['_ids']);
        if (empty($hostgroupsDiff)) {
            //Check if host groups got removed
            $hostgroupsDiff = array_diff($this->hosttemplate['hostgroups']['_ids'], $this->host['hostgroups']['_ids']);
        }

        if (!empty($hostgroupsDiff)) {
            //Host use own host groups
            return [
                '_ids' => $this->host['hostgroups']['_ids']
            ];
        }

        return [
            '_ids' => []
        ];
    }

    /**
     * @return array
     */
    public function getDataForCustomvariables() {
        // for easy host add in wizard - use from template
        if (!isset($this->host['customvariables'])) {
            $this->host['customvariables'] =  $this->hosttemplate['customvariables'];
        }

        $customVariableDiffer = new CustomVariableDiffer(
            $this->host['customvariables'],
            $this->hosttemplate['customvariables']
        );

        $customvariables = $customVariableDiffer->getCustomVariablesToSaveAsRepository();
        if ($customvariables->getSize() === 0) {
            //No diff
            return [];
        }

        $this->hasOwnCustomvariables = true;

        return $customvariables->getAllCustomVariablesAsArray();
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
        // for easy host add in wizard - use from template
        if (!isset($this->host['command_id'])) {
            return [];
        }

        if ($this->host['command_id'] != $this->hosttemplate['command_id']) {
            //Different check command than the host template uses.
            //Definitely the command arguments has changed
            return $this->host['hostcommandargumentvalues'];
        }

        $hostCommandArguments = [];
        $hosttemplateCommandArguments = [];
        foreach ($this->host['hostcommandargumentvalues'] as $hcargv) {
            $hostCommandArguments[$hcargv['commandargument_id']] = $hcargv['value'];
        }

        foreach ($this->hosttemplate['hosttemplatecommandargumentvalues'] as $htcargv) {
            $hosttemplateCommandArguments[$htcargv['commandargument_id']] = $htcargv['value'];
        }

        $diff = array_diff($hostCommandArguments, $hosttemplateCommandArguments);
        if (empty($diff)) {
            $diff = array_diff($hosttemplateCommandArguments, $hostCommandArguments);
        }

        if (empty($diff)) {
            return [];
        }

        //There is a diff, save all command argument values for this host
        return $this->host['hostcommandargumentvalues'];
    }

    /**
     * @return array
     */
    public function getDataForPrometheusExporters() {
        // for easy host add in wizard - use from template
        if (!isset($this->host['prometheus_exporters']['_ids'])) {
            return [
                '_ids' => []
            ];
        }

        //Where prometheus_exporters changed or edited?
        $prometheusExportersDiff = array_diff($this->host['prometheus_exporters']['_ids'], $this->hosttemplate['prometheus_exporters']['_ids']);
        if (empty($prometheusExportersDiff)) {
            //Check if Prometheus exporter got removed
            $prometheusExportersDiff = array_diff($this->hosttemplate['prometheus_exporters']['_ids'], $this->host['prometheus_exporters']['_ids']);
        }

        if (!empty($prometheusExportersDiff)) {
            //Host use own Prometheus exporters
            return [
                '_ids' => $this->host['prometheus_exporters']['_ids']
            ];
        }

        return [
            '_ids' => []
        ];
    }

}
