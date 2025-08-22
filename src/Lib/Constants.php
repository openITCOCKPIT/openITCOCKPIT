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

namespace App\Lib;


class Constants {

    /**
     * @var array
     */
    private $containersWithProperties;

    public function __construct() {
        $this->defineCommandConstants();

        $this->defineRootContainer();
        $this->defineContainerTypeIds();
        $this->defineContainerPermissionTypes();

        $this->defineObjects();
        $this->defineHosttemplateTypes();
        $this->defineHostTypes();
        $this->defineServiceTypes();

        $this->defineModules();

        $this->defineAjaxLimit();

        $this->attachContainerpropertiesToContainers();
    }

    /**
     * return all matching container types as array for a given object.
     * Example:
     * Object OBJECT_USER will return:
     * [CT_GLOBAL, CT_TENANT, CT_NODE] === [1,2,5]
     *
     * @param int $object Constant defined in self::defineObjects()
     * @param int|array $exclude Array of container types to exclude from the result (Hosstgroup in Host::add())
     *
     * @return array                Array with container types that can handle the given object type.
     */
    public function containerProperties($object = null, $exclude = []) {
        if (!empty($exclude)) {
            if (!is_array($exclude)) {
                $exclude = [$exclude];
            }
        }

        if ($object !== null) {
            $return = [];
            foreach ($this->containersWithProperties as $container) {
                if ($object & $container['properties']) {
                    if (!in_array($container['container_type'], $exclude)) {
                        $return[] = $container['container_type'];
                    }
                }
            }
            return $return;
        }
        return [];
    }

    private function defineCommandConstants() {
        $this->define([
            'CHECK_COMMAND'        => 1,
            'HOSTCHECK_COMMAND'    => 2,
            'NOTIFICATION_COMMAND' => 3,
            'EVENTHANDLER_COMMAND' => 4,
        ]);
    }

    private function defineRootContainer() {
        if (!defined('ROOT_CONTAINER')) {
            define('ROOT_CONTAINER', 1);
        }
    }

    private function defineContainerTypeIds() {
        $this->define([
            'CT_GLOBAL'               => 1,
            'CT_TENANT'               => 2,
            'CT_LOCATION'             => 3,
            'CT_DEVICEGROUP'          => 4,
            'CT_NODE'                 => 5,
            'CT_CONTACTGROUP'         => 6,
            'CT_HOSTGROUP'            => 7,
            'CT_SERVICEGROUP'         => 8,
            'CT_SERVICETEMPLATEGROUP' => 9,
            'CT_RESOURCEGROUP'        => 10
        ]);
    }

    private function defineContainerPermissionTypes() {
        $this->define([
            'READ_RIGHT'  => 1 << 0,
            'WRITE_RIGHT' => 1 << 1,
        ]);
    }

    private function defineObjects() {
        $this->define([
            'OBJECT_TENANT'                    => 1 << 0,
            'OBJECT_USER'                      => 1 << 1,
            'OBJECT_NODE'                      => 1 << 2,
            'OBJECT_LOCATION'                  => 1 << 3,
            'OBJECT_DEVICEGROUP'               => 1 << 4,
            'OBJECT_CONTACT'                   => 1 << 5,
            'OBJECT_CONTACTGROUP'              => 1 << 6,
            'OBJECT_TIMEPERIOD'                => 1 << 7,
            'OBJECT_HOST'                      => 1 << 8,
            'OBJECT_HOSTTEMPLATE'              => 1 << 9,
            'OBJECT_HOSTGROUP'                 => 1 << 10,
            'OBJECT_SERVICE'                   => 1 << 11,
            'OBJECT_SERVICETEMPLATE'           => 1 << 12,
            'OBJECT_SERVICEGROUP'              => 1 << 13,
            'OBJECT_COMMAND'                   => 1 << 14,
            'OBJECT_SATELLITE'                 => 1 << 15,
            'OBJECT_SERVICETEMPLATEGROUP'      => 1 << 16,
            'OBJECT_HOSTESCALATION'            => 1 << 17,
            'OBJECT_SERVICEESCALATION'         => 1 << 18,
            'OBJECT_HOSTDEPENDENCY'            => 1 << 19,
            'OBJECT_SERVICEDEPENDENCY'         => 1 << 20,
            'OBJECT_EXPORT'                    => 1 << 21,  // Changelog only  ImportModule
            'OBJECT_HOSTDEFAULT'               => 1 << 22,  // Changelog only ImportModule
            'OBJECT_IMPORTER'                  => 1 << 23,  // Changelog only ImportModule
            'OBJECT_IMPORTEDHOST'              => 1 << 24,  // Changelog only ImportModule
            'OBJECT_EXTERNALSYSTEM'            => 1 << 25,  // Changelog only ImportModule
            'OBJECT_EXTERNALMONITORING'        => 1 << 26,  // Changelog only ImportModule
            'OBJECT_STARTIMPORTDATA'           => 1 << 27,  // Changelog only ImportModule
            'OBJECT_SYNCHRONIZEWITHMONITORING' => 1 << 28,  // Changelog only ImportModule
            'OBJECT_AGENTCHECK'                => 1 << 29,  // Changelog only ImportModule
            'OBJECT_IMPORTEDHOSTGROUP'         => 1 << 30,  // Changelog only ImportModule
            'OBJECT_RESOURCE'                  => 1 << 31,  // ScmModule
            'OBJECT_RESOURCEGROUP'             => 1 << 32,  // ScmModule
        ]);
    }

    private function defineHosttemplateTypes() {
        $this->define([
            'GENERIC_HOSTTEMPLATE' => 1 << 0, //1
            'EVK_HOSTTEMPLATE'     => 1 << 1, //2
            'SLA_HOSTTEMPLATE'     => 1 << 2  //4
        ]);
    }

    private function defineHostTypes() {
        $this->define([
            'GENERIC_HOST' => 1 << 0, //1
            'EVK_HOST'     => 1 << 1, //2
            'SLA_HOST'     => 1 << 2  //4
        ]);
    }

    private function defineServiceTypes() {
        $this->define($this->getServiceTypes());
    }

    private function defineModules() {
        $this->define($this->getModuleConstants());
    }


    /**
     * @return array
     */
    public function getServiceTypes() {
        return [
            'GENERIC_SERVICE'    => 1 << 0, //1
            'EVK_SERVICE'        => 1 << 1, //2
            'SLA_SERVICE'        => 1 << 2, //4
            'MK_SERVICE'         => 1 << 3, //8
            'OITC_AGENT_SERVICE' => 1 << 4, //16
            'PROMETHEUS_SERVICE' => 1 << 5, //32
            'EXTERNAL_SERVICE'   => 1 << 6  //64
        ];
    }

    public function getModuleConstants() {
        return [
            'CORE'                    => 0 << 0,
            'AUTOREPORT_MODULE'       => 1 << 0,
            'EVENTCORRELATION_MODULE' => 1 << 1,
            'IMPORT_MODULE'           => 1 << 2,
            'SLA_MODULE'              => 1 << 3,
            'SCM_MODULE'              => 1 << 4
        ];
    }

    private function attachContainerpropertiesToContainers() {
        $this->containersWithProperties = [
            "GLOBAL_CONTAINER"               => [
                'properties'     => OBJECT_TENANT ^ OBJECT_USER ^ OBJECT_CONTACT ^ OBJECT_CONTACTGROUP ^ OBJECT_TIMEPERIOD ^ OBJECT_HOST ^ OBJECT_HOSTTEMPLATE ^ OBJECT_HOSTGROUP ^ OBJECT_SERVICE ^ OBJECT_SERVICETEMPLATE ^ OBJECT_SERVICEGROUP ^ OBJECT_SATELLITE ^ OBJECT_SERVICETEMPLATEGROUP ^ OBJECT_HOSTESCALATION ^ OBJECT_SERVICEESCALATION ^ OBJECT_HOSTDEPENDENCY ^ OBJECT_SERVICEDEPENDENCY ^ OBJECT_RESOURCEGROUP,
                'container_type' => CT_GLOBAL,
            ],
            "TENANT_CONTAINER"               => [
                'properties'     => OBJECT_LOCATION ^ OBJECT_NODE ^ OBJECT_USER ^ OBJECT_CONTACT ^ OBJECT_CONTACTGROUP ^ OBJECT_TIMEPERIOD ^ OBJECT_HOST ^ OBJECT_HOSTTEMPLATE ^ OBJECT_HOSTGROUP ^ OBJECT_SERVICE ^ OBJECT_SERVICETEMPLATE ^ OBJECT_SERVICEGROUP ^ OBJECT_SATELLITE ^ OBJECT_SERVICETEMPLATEGROUP ^ OBJECT_HOSTESCALATION ^ OBJECT_SERVICEESCALATION ^ OBJECT_HOSTDEPENDENCY ^ OBJECT_SERVICEDEPENDENCY ^ OBJECT_RESOURCEGROUP,
                'container_type' => CT_TENANT,
            ],
            "LOCATION_CONTAINER"             => [
                'properties'     => OBJECT_LOCATION ^ OBJECT_NODE ^ OBJECT_USER ^ OBJECT_CONTACT ^ OBJECT_CONTACTGROUP ^ OBJECT_TIMEPERIOD ^ OBJECT_HOST ^ OBJECT_HOSTGROUP ^ OBJECT_SERVICEGROUP ^ OBJECT_SATELLITE ^ OBJECT_HOSTTEMPLATE ^ OBJECT_SERVICETEMPLATE ^ OBJECT_SERVICETEMPLATEGROUP ^ OBJECT_RESOURCEGROUP,
                'container_type' => CT_LOCATION,
            ],
            "NODE_CONTAINER"                 => [
                'properties'     => OBJECT_LOCATION ^ OBJECT_NODE ^ OBJECT_USER ^ OBJECT_CONTACT ^ OBJECT_CONTACTGROUP ^ OBJECT_TIMEPERIOD ^ OBJECT_HOST ^ OBJECT_HOSTGROUP ^ OBJECT_SERVICEGROUP ^ OBJECT_SATELLITE ^ OBJECT_HOSTTEMPLATE ^ OBJECT_SERVICETEMPLATE ^ OBJECT_SERVICETEMPLATEGROUP ^ OBJECT_RESOURCEGROUP,
                'container_type' => CT_NODE,
            ],
            'CONTACTGROUP_CONTAINER'         => [
                'properties'     => OBJECT_CONTACT,
                'container_type' => CT_CONTACTGROUP,
            ],
            'HOSTGROUP_CONTAINER'            => [
                'properties'     => OBJECT_HOST,
                'container_type' => CT_HOSTGROUP,
            ],
            'SERVICEGROUP_CONTAINER'         => [
                'properties'     => OBJECT_SERVICE,
                'container_type' => CT_SERVICEGROUP,
            ],
            'SERVICETEMPLATEGROUP_CONTAINER' => [
                'properties'     => OBJECT_SERVICETEMPLATE,
                'container_type' => CT_SERVICETEMPLATEGROUP,
            ],
            'RESOURCEGROUP_CONTAINER'        => [
                'properties'     => OBJECT_USER ^ OBJECT_RESOURCE,
                'container_type' => CT_RESOURCEGROUP,
            ],
        ];
    }

    public function defineAjaxLimit() {
        $this->define([
            'ITN_AJAX_LIMIT' => 150
        ]);
    }

    /**
     * @param array $constants
     */
    private function define($constants = []) {
        foreach ($constants as $constantName => $constantValue) {
            if (!defined($constantName)) {
                define($constantName, $constantValue);
            }
        }
    }
}
