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


class Service {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string|null
     */
    private $servicename = null;

    /**
     * @var string|null
     */
    private $hostname = null;

    /**
     * @var string|null
     */
    private $description = null;

    /**
     * @var int|bool|null
     */
    private $active_checks_enabled = null;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var int
     */
    private $host_id;

    /**
     * @var int
     */
    private int $usageFlag;

    /**
     * @var bool
     */
    private $allow_edit = false;

    /**
     * @var bool
     */
    private $disabled = false;

    private $serviceType = GENERIC_SERVICE;

    /**
     * @var int
     */
    private $priority;

    /**
     * Service constructor.
     * @param $service
     * @param null $servicename
     * @param bool $allowEdit
     */
    public function __construct($service, $servicename = null, $allowEdit = false) {
        if (isset($service['CrateService'])) {
            $service['Service'] = $service['CrateService'];
        }
        $this->allow_edit = $allowEdit;

        if (isset($service['id']) && isset($service['uuid'])) {
            //Cake4 result...

            $service = [
                'Service' => $service
            ];

            if (isset($service['Service']['servicetemplate'])) {
                $service['Servicetemplate'] = $service['Service']['servicetemplate'];
            }
            if (isset($service['Service']['Servicetemplates'])) {
                $service['Servicetemplate'] = $service['Service']['Servicetemplates'];
            }

            if (isset($service['Service']['_matchingData']['Servicetemplates'])) {
                $service['Servicetemplate'] = $service['Service']['_matchingData']['Servicetemplates'];
            }

            if (isset($service['Service']['host'])) {
                $service['Host'] = $service['Service']['host'];
            }
            if (isset($service['Service']['Hosts'])) {
                $service['Host'] = $service['Service']['Hosts'];
            }

            if (isset($service['Service']['_matchingData']['Hosts'])) {
                $service['Host'] = $service['Service']['_matchingData']['Hosts'];
            }
        }

        if (isset($service['Service']['id'])) {
            $this->id = $service['Service']['id'];
        }

        if (isset($service['Service']['uuid'])) {
            $this->uuid = $service['Service']['uuid'];
        }

        if (isset($service['Service']['name'])) {
            $this->servicename = $service['Service']['name'];
        }

        if ($this->servicename === null || $this->servicename === '') {
            if (isset($service['Servicetemplate']['name'])) {
                $this->servicename = $service['Servicetemplate']['name'];
            }
        }

        if (isset($service['Service']['servicename'])) {
            $this->servicename = $service['Service']['servicename'];
        }

        if ($servicename !== null) {
            $this->servicename = $servicename;
        }

        if (isset($service['Service']['description'])) {
            $this->description = $service['Service']['description'];
        }

        if ($this->description === null || $this->description === '') {
            if (isset($service['Servicetemplate']['description'])) {
                $this->description = $service['Servicetemplate']['description'];
            }
        }

        if (isset($service['Service']['active_checks_enabled']) && $service['Service']['active_checks_enabled'] !== '') {
            $this->active_checks_enabled = (bool)$service['Service']['active_checks_enabled'];
        }

        if ($this->active_checks_enabled === null && isset($service['Servicetemplate']['active_checks_enabled'])) {
            $this->active_checks_enabled = (bool)$service['Servicetemplate']['active_checks_enabled'];
        }

        if (isset($service['Service']['tags'])) {
            $this->tags = $service['Service']['tags'];
        }

        if (isset($service['Host']['id'])) {
            $this->host_id = (int)$service['Host']['id'];
        }

        if (isset($service['Service']['usage_flag'])) {
            $this->usageFlag = (int)$service['Service']['usage_flag'];
        }

        if (isset($service['Host']['name'])) {
            $this->hostname = $service['Host']['name'];
        }

        if (isset($service['Service']['disabled'])) {
            $this->disabled = (bool)$service['Service']['disabled'];
        }

        if (isset($service['Service']['service_type'])) {
            $this->serviceType = (int)$service['Service']['service_type'];
        }

        if (!empty($service['Servicetemplate']['priority'])) {
            $this->priority = $service['Servicetemplate']['priority'];
        }

        if (empty($service['Service']['priority']) && isset($service['Service']['_matchingData']['Servicetemplates']['priority'])) {
            $this->priority = $service['Service']['_matchingData']['Servicetemplates']['priority'];
        } else {
            if (!empty($service['Service']['priority'])) {
                $this->priority = $service['Service']['priority'];
            }
        }
        if (!empty($service['Service']['servicepriority'])) {
            $this->priority = $service['Service']['servicepriority'];
        }
    }

    public static function fromServiceNotification($serviceNotification) {
        $servicename = null;
        if (isset($serviceNotification['NotificationService']['servicename'])) {
            $servicename = $serviceNotification['NotificationService']['servicename'];
        }

        return new self($serviceNotification, $servicename);
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
    public function getServicename() {
        return $this->servicename;
    }

    /**
     * @return string
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * @return string | null
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
     * @return string
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @return int
     */
    public function getHostId() {
        return $this->host_id;
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
    public function getServiceType() {
        return $this->serviceType;
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
        return get_object_vars($this);
    }

}
