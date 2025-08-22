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

namespace itnovum\openITCOCKPIT\Core\Views;


class Hosttemplate {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int|bool
     */
    private $active_checks_enabled;

    /**
     * @var int
     */
    private $containerId;

    /**
     * @var string
     */
    private $tags;


    public function __construct($hosttemplate) {
        if(isset($hosttemplate['Host']['hosttemplate'])){
            //Cake4 contain
            $hosttemplate = [
                'Hosttemplate' => $hosttemplate['Host']['hosttemplate']
            ];
        }

        if (isset($hosttemplate['Hosttemplate']['id'])) {
            $this->id = $hosttemplate['Hosttemplate']['id'];
        }

        if (isset($hosttemplate['Hosttemplate']['uuid'])) {
            $this->uuid = $hosttemplate['Hosttemplate']['uuid'];
        }

        if (isset($hosttemplate['Hosttemplate']['name'])) {
            $this->name = $hosttemplate['Hosttemplate']['name'];
        }


        if (isset($hosttemplate['Hosttemplate']['description'])) {
            $this->description = $hosttemplate['Hosttemplate']['description'];
        }

        if (isset($hosttemplate['Hosttemplate']['active_checks_enabled'])) {
            $this->active_checks_enabled = (bool)$hosttemplate['Hosttemplate']['active_checks_enabled'];
        }


        if (isset($hosttemplate['Hosttemplate']['container_id'])) {
            $this->containerId = $hosttemplate['Hosttemplate']['container_id'];
        }

        if (isset($hosttemplate['Hosttemplate']['tags'])) {
            $this->tags = $hosttemplate['Hosttemplate']['tags'];
        }

    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return bool|int
     */
    public function isActiveChecksEnabled() {
        return $this->active_checks_enabled;
    }


    /**
     * @return int
     */
    public function getContainerId() {
        return $this->containerId;
    }

    /**
     * @return string
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
