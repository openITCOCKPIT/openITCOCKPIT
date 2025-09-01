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


use Cake\Utility\Hash;

class Host {

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
    private $hostname;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $hosttemplate_id;

    /**
     * @var int|bool
     */
    private $active_checks_enabled;

    /**
     * @var int
     */
    private $satelliteId;

    /**
     * @var int
     */
    private $containerId;

    /**
     * @var array
     */
    private $containerIds;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var int
     */
    private $usageFlag;

    /**
     * @var bool
     */
    private $allow_edit = false;

    /**
     * @var bool
     */
    private $disabled = false;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var string|null
     */
    private $notes;

    /**
     * Host constructor.
     * @param array $host
     * @param bool $allowEdit
     */
    public function __construct($host, $allowEdit = false) {
        $this->allow_edit = $allowEdit;

        if (isset($host['id']) && isset($host['uuid'])) {
            //Cake4 result...
            $host = [
                'Host' => $host
            ];
        }

        if (isset($host['Host']['id'])) {
            $this->id = $host['Host']['id'];
        }

        if (isset($host['Host']['uuid'])) {
            $this->uuid = $host['Host']['uuid'];
        }

        if (isset($host['Host']['name'])) {
            $this->hostname = $host['Host']['name'];
        }

        if (isset($host['Host']['address'])) {
            $this->address = $host['Host']['address'];
        }

        if (empty($host['Host']['description']) && isset($host['Hosttemplate']['description'])) {
            $this->description = $host['Hosttemplate']['description'];
        } else {
            $this->description = $host['Host']['description'] ?? null;
        }

        if (empty($host['Host']['notes']) && isset($host['Hosttemplate']['notes'])) {
            $this->notes = $host['Hosttemplate']['notes'];
        } else {
            $this->notes = $host['Host']['notes'] ?? null;
        }

        if (isset($host['Host']['hosttemplate_id'])) {
            $this->hosttemplate_id = (int)$host['Host']['hosttemplate_id'];
        }

        if (empty($host['Host']['active_checks_enabled']) && isset($host['Host']['hosttemplate']['active_checks_enabled'])) {
            $this->active_checks_enabled = (bool)$host['Host']['hosttemplate']['active_checks_enabled'];
        } else if (empty($host['Host']['active_checks_enabled']) && isset($host['Hosttemplate']['active_checks_enabled'])) {
            $this->active_checks_enabled = (bool)$host['Hosttemplate']['active_checks_enabled'];
        } else {
            if (!empty($host['Host']['active_checks_enabled'])) {
                $this->active_checks_enabled = (bool)$host['Host']['active_checks_enabled'];
            }
        }


        if (isset($host['Host']['satellite_id'])) {
            $this->satelliteId = (int)$host['Host']['satellite_id'];
        }

        if (isset($host['Host']['container_id'])) {
            $this->containerId = (int)$host['Host']['container_id'];
        }

        if (isset($host['Container'])) {
            //MySQL
            $this->containerIds = Hash::extract($host, 'Container.{n}.HostsToContainer.container_id');
        }

        if (isset($host['HostsToContainers'])) {
            //MySQL
            $this->containerIds = [];
            foreach ($host['HostsToContainers'] as $container_id) {
                $this->containerIds[] = $container_id;
            }
        }

        if (isset($host['Host']['Container'])) {
            //MySQL belongsTo
            $this->containerIds = Hash::extract($host['Host']['Container'], '{n}.HostsToContainer.container_id');
        }

        if (isset($host['Host']['container_ids'])) {
            //CrateDB
            $this->containerIds = $host['Host']['container_ids'];
        }

        if (isset($host['Host']['hosts_to_containers_sharing'])) {
            //MySQL and Cake4
            $this->containerIds = Hash::extract($host['Host']['hosts_to_containers_sharing'], '{n}.id');
        }

        if (isset($host['Host']['tags'])) {
            $this->tags = $host['Host']['tags'];
        }

        if (isset($host['Host']['usage_flag'])) {
            $this->usageFlag = (int)$host['Host']['usage_flag'];
        }

        if (isset($host['Host']['disabled'])) {
            $this->disabled = (bool)$host['Host']['disabled'];
        }

        if (empty($host['Host']['priority']) && isset($host['Host']['hosttemplate']['priority'])) {
            $this->priority = $host['Host']['hosttemplate']['priority'];
        } else if (empty($host['Host']['priority']) && isset($host['Hosttemplate']['priority'])) {
            $this->priority = $host['Hosttemplate']['priority'];
        } else {
            if (!empty($host['Host']['priority'])) {
                $this->priority = $host['Host']['priority'];
            }
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
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getNotes() {
        return $this->notes;
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
    public function getSatelliteId() {
        return $this->satelliteId;
    }

    /**
     * @return bool
     */
    public function isSatelliteHost() {
        if ($this->satelliteId === null) {
            return false;
        }
        return ((int)$this->satelliteId !== 0);
    }

    /**
     * @return int
     */
    public function getContainerId() {
        return $this->containerId;
    }

    /**
     * @return array
     */
    public function getContainerIds() {
        return $this->containerIds;
    }

    /**
     * @return string
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @return int
     */
    public function getUsageFlag(): int {
        return $this->usageFlag ?? 0;
    }

    /**
     * @return bool|int
     */
    public function isDisabled() {
        return $this->disabled;
    }

    /**
     * @return int
     */
    public function getHosttemplateId() {
        return $this->hosttemplate_id;
    }

    /**
     * @return int
     */
    public function getPriority() {
        return $this->priority;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        $arr['is_satellite_host'] = $this->isSatelliteHost();
        $arr['name'] = $this->hostname;
        return $arr;
    }

}
