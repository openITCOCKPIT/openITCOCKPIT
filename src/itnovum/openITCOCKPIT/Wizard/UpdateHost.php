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
use App\Model\Table\ChangelogsTable;
use App\Model\Table\HostsTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\Comparison\HostComparisonForSave;
use itnovum\openITCOCKPIT\Core\Merger\HostMergerForView;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;

class UpdateHost {


    public static function save(
        array $host,
        array $hosttemplate,
        array $postData,
        array $customVariablestoCheck,
        HostsTable $HostsTable,
        User $User
    ) {

        $HostMergerForView = new HostMergerForView($host, $hosttemplate);
        $mergedHost = $HostMergerForView->getDataForView();

        $hostForChangelog = $mergedHost;

        //Update custom variables
        $currentHostCustomVariables = Hash::combine($mergedHost['Host']['customvariables'], '{n}.name', '{n}');
        foreach ($customVariablestoCheck as $hostCustomvariableName => $postDataKey) {
            //ignore custom variables with empty value
            if(empty(trim($postData[$postDataKey]))){
                continue;
            }
            if (isset($currentHostCustomVariables[$hostCustomvariableName])) {
                $currentHostCustomVariables[$hostCustomvariableName]['value'] = $postData[$postDataKey];
            } else {
                $currentHostCustomVariables[$hostCustomvariableName] = [
                    'name'          => $hostCustomvariableName,
                    'value'         => $postData[$postDataKey],
                    'objecttype_id' => OBJECT_HOST,
                    'password'      => (preg_match('/(?i)(password|pass|secret|pw)/', $hostCustomvariableName)) ? 1 : 0
                ];
            }
        }
        $mergedHost['Host']['customvariables'] = array_values($currentHostCustomVariables);
        $HostComparisonForSave = new HostComparisonForSave($mergedHost, $hosttemplate);

        $dataForSave = $HostComparisonForSave->getDataForSaveForAllFields();
        //Add required fields for validation
        $dataForSave['hosttemplate_flap_detection_enabled'] = $hosttemplate['Hosttemplate']['flap_detection_enabled'];
        $dataForSave['hosttemplate_flap_detection_on_up'] = $hosttemplate['Hosttemplate']['flap_detection_on_up'];
        $dataForSave['hosttemplate_flap_detection_on_down'] = $hosttemplate['Hosttemplate']['flap_detection_on_down'];
        $dataForSave['hosttemplate_flap_detection_on_unreachable'] = $hosttemplate['Hosttemplate']['flap_detection_on_unreachable'];

        //Update contact data

        $hostEntity = $HostsTable->get($host['Host']['id']);

        $hostEntity = $HostsTable->patchEntity($hostEntity, $dataForSave);


        $HostsTable->save($hostEntity);
        if ($hostEntity->hasErrors()) {
            return $hostEntity->getErrors();
        } else {
            //No errors
            /** @var  ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'edit',
                'hosts',
                $hostEntity->get('id'),
                OBJECT_HOST,
                $hostEntity->get('container_id'),
                $User->getId(),
                $hostEntity->get('name'),
                array_merge($HostsTable->resolveDataForChangelog($mergedHost), $mergedHost),
                array_merge($HostsTable->resolveDataForChangelog($hostForChangelog), $hostForChangelog)
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);
            }

            return $hostEntity;
        }
    }

}
