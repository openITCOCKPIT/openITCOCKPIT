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
    private array $containersWithProperties = [];

    public function __construct() {
        $this->defineAllConstants();

        $this->attachContainerpropertiesToContainers();
    }

    /**
     * Define all constants used in openITCOCKPIT.
     * But only once.
     * @return void
     */
    private function defineAllConstants(): void {
        if (!defined('OITC_INITIALIZED')) {
            define('OITC_INITIALIZED', true);

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
        }
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
    public function containerProperties($object = null, $exclude = []): array {
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

    private function defineCommandConstants(): void {
        define('CHECK_COMMAND', 1);
        define('HOSTCHECK_COMMAND', 2);
        define('NOTIFICATION_COMMAND', 3);
        define('EVENTHANDLER_COMMAND', 4);
    }

    private function defineRootContainer(): void {
        define('ROOT_CONTAINER', 1);
    }

    private function defineContainerTypeIds(): void {
        define('CT_GLOBAL', 1);
        define('CT_TENANT', 2);
        define('CT_LOCATION', 3);
        define('CT_DEVICEGROUP', 4);
        define('CT_NODE', 5);
        define('CT_CONTACTGROUP', 6);
        define('CT_HOSTGROUP', 7);
        define('CT_SERVICEGROUP', 8);
        define('CT_SERVICETEMPLATEGROUP', 9);
        define('CT_RESOURCEGROUP', 10);
    }

    private function defineContainerPermissionTypes(): void {
        define('READ_RIGHT', 1 << 0);
        define('WRITE_RIGHT', 1 << 1);
    }

    private function defineObjects(): void {
        define('OBJECT_TENANT', 1 << 0);
        define('OBJECT_USER', 1 << 1);
        define('OBJECT_NODE', 1 << 2);
        define('OBJECT_LOCATION', 1 << 3);
        define('OBJECT_DEVICEGROUP', 1 << 4);
        define('OBJECT_CONTACT', 1 << 5);
        define('OBJECT_CONTACTGROUP', 1 << 6);
        define('OBJECT_TIMEPERIOD', 1 << 7);
        define('OBJECT_HOST', 1 << 8);
        define('OBJECT_HOSTTEMPLATE', 1 << 9);
        define('OBJECT_HOSTGROUP', 1 << 10);
        define('OBJECT_SERVICE', 1 << 11);
        define('OBJECT_SERVICETEMPLATE', 1 << 12);
        define('OBJECT_SERVICEGROUP', 1 << 13);
        define('OBJECT_COMMAND', 1 << 14);
        define('OBJECT_SATELLITE', 1 << 15);
        define('OBJECT_SERVICETEMPLATEGROUP', 1 << 16);
        define('OBJECT_HOSTESCALATION', 1 << 17);
        define('OBJECT_SERVICEESCALATION', 1 << 18);
        define('OBJECT_HOSTDEPENDENCY', 1 << 19);
        define('OBJECT_SERVICEDEPENDENCY', 1 << 20);
        define('OBJECT_EXPORT', 1 << 21);                    // Changelog only ImportModule
        define('OBJECT_HOSTDEFAULT', 1 << 22);               // Changelog only ImportModule
        define('OBJECT_IMPORTER', 1 << 23);                  // Changelog only ImportModule
        define('OBJECT_IMPORTEDHOST', 1 << 24);              // Changelog only ImportModule
        define('OBJECT_EXTERNALSYSTEM', 1 << 25);            // Changelog only ImportModule
        define('OBJECT_EXTERNALMONITORING', 1 << 26);        // Changelog only ImportModule
        define('OBJECT_STARTIMPORTDATA', 1 << 27);           // Changelog only ImportModule
        define('OBJECT_SYNCHRONIZEWITHMONITORING', 1 << 28); // Changelog only ImportModule
        define('OBJECT_AGENTCHECK', 1 << 29);                // Changelog only ImportModule
        define('OBJECT_IMPORTEDHOSTGROUP', 1 << 30);         // Changelog only ImportModule
        define('OBJECT_RESOURCE', 1 << 31);                  // ScmModule
        define('OBJECT_RESOURCEGROUP', 1 << 32);             // ScmModule
        define('OBJECT_CALENDAR', 1 << 33);
    }

    private function defineHosttemplateTypes(): void {
        define('GENERIC_HOSTTEMPLATE', 1 << 0); //1
        define('EVK_HOSTTEMPLATE', 1 << 1);     //2
        define('SLA_HOSTTEMPLATE', 1 << 2);     //4
    }

    private function defineHostTypes(): void {
        define('GENERIC_HOST', 1 << 0); //1
        define('EVK_HOST', 1 << 1);     //2
        define('SLA_HOST', 1 << 2);     //4
    }

    private function defineServiceTypes(): void {
        define('GENERIC_SERVICE', 1 << 0);    //1
        define('EVK_SERVICE', 1 << 1);        //2
        define('SLA_SERVICE', 1 << 2);        //4
        define('MK_SERVICE', 1 << 3);         //8
        define('OITC_AGENT_SERVICE', 1 << 4); //16
        define('PROMETHEUS_SERVICE', 1 << 5); //32
        define('EXTERNAL_SERVICE', 1 << 6);   //64
    }

    private function defineModules(): void {
        define('CORE', 0 << 0);
        define('AUTOREPORT_MODULE', 1 << 0);
        define('EVENTCORRELATION_MODULE', 1 << 1);
        define('IMPORT_MODULE', 1 << 2);
        define('SLA_MODULE', 1 << 3);
        define('SCM_MODULE', 1 << 4);
    }


    /**
     * @return array
     */
    public function getServiceTypes(): array {
        return [
            'GENERIC_SERVICE'    => GENERIC_SERVICE,
            'EVK_SERVICE'        => EVK_SERVICE,
            'SLA_SERVICE'        => SLA_SERVICE,
            'MK_SERVICE'         => MK_SERVICE,
            'OITC_AGENT_SERVICE' => OITC_AGENT_SERVICE,
            'PROMETHEUS_SERVICE' => PROMETHEUS_SERVICE,
            'EXTERNAL_SERVICE'   => EXTERNAL_SERVICE
        ];
    }

    public function getModuleConstants(): array {
        return [
            'CORE'                    => CORE,
            'AUTOREPORT_MODULE'       => AUTOREPORT_MODULE,
            'EVENTCORRELATION_MODULE' => EVENTCORRELATION_MODULE,
            'IMPORT_MODULE'           => IMPORT_MODULE,
            'SLA_MODULE'              => SLA_MODULE,
            'SCM_MODULE'              => SCM_MODULE
        ];
    }

    private function attachContainerpropertiesToContainers(): void {
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

    public function defineAjaxLimit(): void {
        define('ITN_AJAX_LIMIT', 150);
    }

}
