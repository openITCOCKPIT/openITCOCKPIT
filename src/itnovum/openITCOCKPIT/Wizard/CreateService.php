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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace App\itnovum\openITCOCKPIT\Wizard;

use App\Model\Entity\Changelog;
use App\Model\Entity\Host;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\Comparison\ServiceComparisonForSave;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;

class CreateService {

    /**
     * @param Host $host
     * @param array $services
     * @param array $postData
     * @param array $customVariablestoCheck
     * @param HostsTable $HostsTable
     * @param HosttemplatesTable $HosttemplatesTable
     * @param ServicesTable $ServicesTable
     * @param ServicetemplatesTable $ServicetemplatesTable
     * @param User $User
     */
    public static function saveMany(
        Host $host,
        array $services,
        array $postData,
        array $customVariablestoCheck,
        HostsTable $HostsTable,
        HosttemplatesTable $HosttemplatesTable,
        ServicesTable $ServicesTable,
        ServicetemplatesTable $ServicetemplatesTable,
        User $User
    ) {
        foreach ($services as $service) {
            $service = [
                'Service' => $service
            ];

            $service['Service']['host_id'] = $host->id; //Do not trust the user input!

            if (empty($service['Service']['servicetemplate_id'])) {
                continue;
            }

            if (!$ServicetemplatesTable->existsById($service['Service']['servicetemplate_id'])) {
                continue;
            }

            $service['Host'] = [
                'id'   => $host->id,
                'name' => $host->name
            ];

            self::save(
                $host,
                $service,
                $postData,
                $customVariablestoCheck,
                $HostsTable,
                $HosttemplatesTable,
                $ServicesTable,
                $ServicetemplatesTable,
                $User
            );
        }
    }

    /**
     * @param Host $host
     * @param array $serviceData
     * @param array $postData
     * @param array $customVariablestoCheck
     * @param HostsTable $HostsTable
     * @param HosttemplatesTable $HosttemplatesTable
     * @param ServicesTable $ServicesTable
     * @param ServicetemplatesTable $ServicetemplatesTable
     * @param User $User
     * @return \App\Model\Entity\Service|array
     */
    public static function save(
        Host $host,
        array $servicePostData,
        array $postData,
        array $customVariablestoCheck,
        HostsTable $HostsTable,
        HosttemplatesTable $HosttemplatesTable,
        ServicesTable $ServicesTable,
        ServicetemplatesTable $ServicetemplatesTable,
        User $User
    ) {
        $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($servicePostData['Service']['servicetemplate_id']);
        //Add custom variables
        $currentServicetemplateCustomVariables = Hash::combine($servicetemplate['Servicetemplate']['customvariables'], '{n}.name', '{n}');
        foreach ($customVariablestoCheck as $serviceCustomvariableName => $postDataKey)
            if (isset($currentServicetemplateCustomVariables[$serviceCustomvariableName])) {
                $currentServicetemplateCustomVariables[$serviceCustomvariableName]['value'] = $postData[$postDataKey];
            } else {
                $currentServicetemplateCustomVariables[$serviceCustomvariableName] = [
                    'name'          => $serviceCustomvariableName,
                    'value'         => $postData[$postDataKey],
                    'objecttype_id' => OBJECT_SERVICE,
                    'password'      => (preg_match('/(?i)(password|pass)/', $serviceCustomvariableName)) ? 1 : 0
                ];
            }

        $servicePostData['Service']['customvariables'] = $currentServicetemplateCustomVariables;

        $ServiceComparisonForSave = new ServiceComparisonForSave(
            $servicePostData,
            $servicetemplate,
            $HostsTable->getContactsAndContactgroupsById($host->get('id')),
            $HosttemplatesTable->getContactsAndContactgroupsById($host->get('hosttemplate_id'))
        );
        $serviceData = $ServiceComparisonForSave->getDataForSaveForAllFields();
        $serviceData['uuid'] = UUID::v4();

        //Add required fields for validation
        $serviceData['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
        $serviceData['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
        $serviceData['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
        $serviceData['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
        $serviceData['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

        $serviceData['service_type'] = GENERIC_SERVICE;

        $service = $ServicesTable->newEntity($serviceData);

        $ServicesTable->save($service);
        if ($service->hasErrors()) {
            return $service->getErrors();
        } else {
            //No errors
            $extDataForChangelog = $ServicesTable->resolveDataForChangelog($serviceData);
            /** @var  ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'add',
                'services',
                $service->get('id'),
                OBJECT_SERVICE,
                $host->get('container_id'),
                $User->getId(),
                $host->get('name') . '/' . $servicePostData['Service']['name'],
                array_merge($servicePostData, $extDataForChangelog)
            );

            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);
            }

            return $service;
        }
    }

}
