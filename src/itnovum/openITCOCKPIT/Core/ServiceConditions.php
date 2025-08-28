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

namespace itnovum\openITCOCKPIT\Core;


use App\itnovum\openITCOCKPIT\Database\SanitizeOrder;

class ServiceConditions {

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $notConditions = [];

    /**
     * @var bool
     */
    private $includeDisabled = false;

    /**
     * @var array
     */
    private $containerIds = [];

    /**
     * @var array
     */
    private $serviceIds = [];

    /**
     * @var array
     */
    private $order = [];

    /**
     * @var int
     */
    private $hostId;

    /**
     * @var array
     */
    private $hostIds = [];

    /**
     * @var string
     */
    private $hostnameRegex = '';

    /**
     * @var string
     */
    private $servicenameRegex = '';

    /**
     * HostConditions constructor.
     * @param array $conditions
     */
    public function __construct($conditions = []) {
        $this->conditions = $conditions;
    }

    /**
     * @return array
     */
    public function getConditions() {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     */
    public function setNotConditions($conditions) {
        $this->notConditions = $conditions;
    }

    /**
     * @return array
     */
    public function getNotConditions() {
        if ($this->hasNotConditions()) {
            return $this->notConditions;
        }
        return [];
    }

    /**
     * @return boolean
     */
    public function hasNotConditions() {
        return !empty($this->notConditions);
    }

    /**
     * @return boolean
     */
    public function includeDisabled() {
        return $this->includeDisabled;
    }

    /**
     * @param boolean $includeDisabled
     */
    public function setIncludeDisabled($includeDisabled) {
        $this->includeDisabled = $includeDisabled;
    }

    /**
     * @return array
     */
    public function getContainerIds() {
        return $this->containerIds;
    }

    /**
     * @param array $containerIds
     */
    public function setContainerIds($containerIds) {
        $this->containerIds = $containerIds;
    }

    /**
     * @param array $order
     */
    public function setOrder($order = []) {
        $this->order = $order;
    }

    /**
     * @return array
     */
    public function getOrder() {
        return SanitizeOrder::filterOrderArray($this->order);
    }

    /**
     * @return int
     */
    public function getHostId() {
        return $this->hostId;
    }

    /**
     * @param int $hostId
     */
    public function setHostId($hostId) {
        $this->hostId = $hostId;
    }

    /**
     * @return array
     */
    public function getHostIds() {
        return $this->hostIds;
    }

    /**
     * @param array $hostIds
     */
    public function setHostIds($hostIds) {
        $this->hostIds = $hostIds;
    }

    /**
     * @return array
     */
    public function getServiceIds() {
        return $this->serviceIds;
    }

    /**
     * @param array $serviceIds
     */
    public function setServiceIds($serviceIds) {
        $this->serviceIds = $serviceIds;
    }

    /**
     * @return string
     */
    public function getHostnameRegex() {
        return $this->hostnameRegex;
    }

    /**
     * @param string $hostnameRegex
     */
    public function setHostnameRegex($hostnameRegex) {
        $this->hostnameRegex = $hostnameRegex;
    }

    /**
     * @return string
     */
    public function getServicenameRegex() {
        return $this->servicenameRegex;
    }

    /**
     * @param string $servicenameRegex
     */
    public function setServicenameRegex($servicenameRegex) {
        $this->servicenameRegex = $servicenameRegex;
    }

}
