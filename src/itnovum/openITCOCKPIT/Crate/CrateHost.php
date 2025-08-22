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

class CrateHost implements CrateValueObject {

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $containerId;

    /**
     * @var array
     */
    private $containerIds = [];

    /**
     * @var int
     */
    private $disabled;

    /**
     * @var int
     */
    private $satelliteId;

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
    private $hosttemplate_id;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var int
     */
    private $tagsFromTemplate = 0;

    /**
     * CrateHost constructor.
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
     * @param string $uuid
     */
    public function setUuid($uuid) {
        $this->uuid = $uuid;
    }

    /**
     * @param string $address
     */
    public function setAddress($address) {
        $this->address = $address;
    }

    /**
     * @param int $containerId
     */
    public function setContainerId($containerId) {
        $this->containerId = (int)$containerId;
    }

    /**
     * @param array $containerIds
     */
    public function setContainerIds($containerIds) {
        $containerIdsAsInt = [];
        foreach ($containerIds as $containerId) {
            $containerIdsAsInt[] = (int)$containerId;
        }

        if (is_numeric($this->containerId)) {
            if (!in_array($this->containerId, $containerIdsAsInt, true)) {
                $containerIds[] = $this->containerId;
            }
        }

        $this->containerIds = $containerIdsAsInt;
    }

    /**
     * @param int $disabled
     */
    public function setDisabled($disabled) {
        $this->disabled = (int)$disabled;
    }

    /**
     * @param int $satelliteId
     */
    public function setSatelliteId($satelliteId) {
        $this->satelliteId = (int)$satelliteId;
    }

    /**
     * @param int $activeChecksEnabled
     */
    public function setActiveChecksEnabled($activeChecksEnabled) {
        $this->activeChecksEnabled = (int)$activeChecksEnabled;
    }

    /**
     * @param int $hosttemplate_id
     */
    public function setHosttemplateId($hosttemplate_id) {
        $this->hosttemplate_id = (int)$hosttemplate_id;
    }

    /**
     * @param string $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }

    /**
     * @param int $activeChecksEnabledFromTemplate
     */
    public function setActiveChecksEnabledFromTemplate($activeChecksEnabledFromTemplate) {
        $this->activeChecksEnabledFromTemplate = (int)$activeChecksEnabledFromTemplate;
    }

    /**
     * @param int $tagsFromTemplate
     */
    public function setTagsFromTemplate($tagsFromTemplate) {
        $this->tagsFromTemplate = (int)$tagsFromTemplate;
    }


    /**
     * @param array $host
     */
    public function setDataFromFindResult($host) {
        $this->setName($host['Host']['name']);
        $this->setUuid($host['Host']['uuid']);
        $this->setAddress($host['Host']['address']);
        $this->setContainerId($host['Host']['container_id']);
        $this->setDisabled($host['Host']['disabled']);
        $this->setHosttemplateId($host['Host']['hosttemplate_id']);
        $this->setSatelliteId($host['Host']['satellite_id']);

        $this->setActiveChecksEnabled($host['Host']['active_checks_enabled']);
        $this->setActiveChecksEnabledFromTemplate(0);
        if ($host['Host']['active_checks_enabled'] === null || $host['Host']['active_checks_enabled'] === '') {
            $this->setActiveChecksEnabled($host['Hosttemplate']['active_checks_enabled']);
            $this->setActiveChecksEnabledFromTemplate(1);
        }

        $this->setTags($host['Host']['tags']);
        $this->setTagsFromTemplate(0);
        if ($host['Host']['tags'] === null || $host['Host']['tags'] === '') {
            $this->setTags($host['Hosttemplate']['tags']);
            $this->setTagsFromTemplate(1);
        }

        $containerIds = \Hash::extract($host['Container'], '{n}.id');
        $this->setContainerIds($containerIds);
    }

    /**
     * @return array
     */
    public function getFindQuery() {
        return [
            'recursive'  => -1,
            'fields'     => [
                'Host.id',
                'Host.name',
                'Host.uuid',
                'Host.hosttemplate_id',
                'Host.address',
                'Host.container_id',
                'Host.active_checks_enabled',
                'Host.tags',
                'Host.disabled',
                'Host.satellite_id'
            ],
            'contain'    => [
                'Container'    => [
                    'fields' => [
                        'id',
                        'name',
                    ],
                ],
                'Hosttemplate' => [
                    'fields' => [
                        'Hosttemplate.active_checks_enabled',
                        'Hosttemplate.tags'
                    ]
                ]
            ],
            'conditions' => [
                'Host.id' => $this->id
            ],
        ];
    }

    public function getDataForSave() {
        return [
            'CrateHost' => [
                'id'                                  => $this->id,
                'name'                                => $this->name,
                'uuid'                                => $this->uuid,
                'address'                             => $this->address,
                'active_checks_enabled'               => $this->activeChecksEnabled,
                'active_checks_enabled_from_template' => $this->activeChecksEnabledFromTemplate,
                'satellite_id'                        => $this->satelliteId,
                'container_ids'                       => $this->containerIds,
                'container_id'                        => $this->containerId,
                'tags'                                => $this->tags,
                'tags_from_template'                  => $this->tagsFromTemplate,
                'hosttemplate_id'                     => $this->hosttemplate_id,
                'disabled'                            => $this->disabled
            ]
        ];
    }

}
