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

namespace itnovum\openITCOCKPIT\Crate;

class CrateService implements CrateValueObject {

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $nameFromTemplate = 0;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var int
     */
    private $disabled;

    /**
     * @var int
     */
    private $hostId;

    /**
     * @var int
     */
    private $activeChecksEnabled;

    /**
     * @var int
     */
    private $activeChecksEnabledFromTemplate = 0;

    /**
     * @var int
     */
    private $servicetemplate_id;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var int
     */
    private $tagsFromTemplate = 0;

    /**
     * CrateService constructor.
     * @param int $id
     */
    public function __construct($id) {
        $this->id = (int)$id;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param int $nameFromTemplate
     */
    public function setNameFromTemplate($nameFromTemplate) {
        $this->nameFromTemplate = (int)$nameFromTemplate;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid) {
        $this->uuid = $uuid;
    }

    /**
     * @param int $disabled
     */
    public function setDisabled($disabled) {
        $this->disabled = (int)$disabled;
    }

    /**
     * @param int $hostId
     */
    public function setHostId($hostId) {
        $this->hostId = (int)$hostId;
    }

    /**
     * @param int $activeChecksEnabled
     */
    public function setActiveChecksEnabled($activeChecksEnabled) {
        $this->activeChecksEnabled = (int)$activeChecksEnabled;
    }

    /**
     * @param int $activeChecksEnabledFromTemplate
     */
    public function setActiveChecksEnabledFromTemplate($activeChecksEnabledFromTemplate) {
        $this->activeChecksEnabledFromTemplate = (int)$activeChecksEnabledFromTemplate;
    }

    /**
     * @param int $servicetemplate_id
     */
    public function setServicetemplateId($servicetemplate_id) {
        $this->servicetemplate_id = (int)$servicetemplate_id;
    }

    /**
     * @param string $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }

    /**
     * @param int $tagsFromTemplate
     */
    public function setTagsFromTemplate($tagsFromTemplate) {
        $this->tagsFromTemplate = (int)$tagsFromTemplate;
    }


    /**
     * @param array $service
     */
    public function setDataFromFindResult($service) {
        $this->setName($service['Service']['name']);
        $this->setNameFromTemplate(0);
        if ($service['Service']['name'] === null || $service['Service']['name'] === '') {
            $this->setName($service['Servicetemplate']['name']);
            $this->setNameFromTemplate(1);
        }

        $this->setHostId($service['Service']['host_id']);
        $this->setUuid($service['Service']['uuid']);
        $this->setDisabled($service['Service']['disabled']);
        $this->setServicetemplateId($service['Service']['servicetemplate_id']);

        $this->setActiveChecksEnabled($service['Service']['active_checks_enabled']);
        $this->setActiveChecksEnabledFromTemplate(0);
        if ($service['Service']['active_checks_enabled'] === null || $service['Service']['active_checks_enabled'] === '') {
            $this->setActiveChecksEnabled($service['Servicetemplate']['active_checks_enabled']);
            $this->setActiveChecksEnabledFromTemplate(1);
        }

        $this->setTags($service['Service']['tags']);
        $this->setTagsFromTemplate(0);
        if ($service['Service']['tags'] === null || $service['Service']['tags'] === '') {
            $this->setTags($service['Servicetemplate']['tags']);
            $this->setTagsFromTemplate(1);
        }
    }

    /**
     * @return array
     */
    public function getFindQuery() {
        return [
            'recursive'  => -1,
            'fields'     => [
                'Service.id',
                'Service.uuid',
                'Service.name',
                'Service.servicetemplate_id',
                'Service.active_checks_enabled',
                'Service.tags',
                'Service.host_id',
                'Service.disabled'
            ],
            'contain'    => [
                'Servicetemplate' => [
                    'fields' => [
                        'Servicetemplate.id',
                        'Servicetemplate.name',
                        'Servicetemplate.active_checks_enabled',
                        'Servicetemplate.tags',
                    ]
                ]
            ],
            'conditions' => [
                'Service.id' => $this->id
            ]
        ];
    }

    public function getDataForSave() {
        return [
            'CrateService' => [
                'id'                                  => $this->id,
                'uuid'                                => $this->uuid,
                'name'                                => $this->name,
                'servicetemplate_id'                  => $this->servicetemplate_id,
                'host_id'                             => $this->hostId,
                'name_from_template'                  => $this->nameFromTemplate,
                'active_checks_enabled'               => $this->activeChecksEnabled,
                'active_checks_enabled_from_template' => $this->activeChecksEnabledFromTemplate,
                'tags'                                => $this->tags,
                'tags_from_template'                  => $this->tagsFromTemplate,
                'disabled'                            => $this->disabled
            ]
        ];
    }

}
