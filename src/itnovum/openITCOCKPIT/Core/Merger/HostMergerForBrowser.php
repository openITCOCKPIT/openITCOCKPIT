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

use itnovum\openITCOCKPIT\Core\CustomVariableMerger;

/**
 * Class HostMergerForView
 *
 * Compares a given host with a given host template
 * Replace null values in $host array with the corresponding value of $hosttemplate
 *
 * @package itnovum\openITCOCKPIT\Core\Comparison
 */
class HostMergerForBrowser {

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
        $this->host = $host;
        $this->hosttemplate = $hosttemplate;
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
            'host_url',
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
        if (empty($this->host['contacts']) && empty($this->host['contactgroups'])) {
            $this->hasOwnContacts = false;
            $this->areContactsInheritedFromHosttemplate = true;

            return [
                'contacts'      => $this->hosttemplate['contacts'],
                'contactgroups' => $this->hosttemplate['contactgroups']
            ];
        } else {
            $this->hasOwnContacts = true;
            $this->areContactsInheritedFromHosttemplate = false;

            return [
                'contacts'      => $this->host['contacts'],
                'contactgroups' => $this->host['contactgroups']
            ];
        }
    }

    /**
     * @return array
     */
    public function getDataForHostgroups() {
        if (empty($this->host['hostgroups'])) {
            //Host use own host groups
            return $this->hosttemplate['hostgroups'];
        }

        return $this->host['hostgroups'];
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
            return $this->hosttemplate['hosttemplatecommandargumentvalues'];
        }

        return $this->host['hostcommandargumentvalues'];

    }

    /**
     * @return bool
     */
    public function areContactsInheritedFromHosttemplate() {
        return $this->areContactsInheritedFromHosttemplate;
    }

    public function areContactsFromHost(){
        return !$this->areContactsInheritedFromHosttemplate;
    }

}
