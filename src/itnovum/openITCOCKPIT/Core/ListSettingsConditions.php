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
use itnovum\openITCOCKPIT\Core\ValueObjects\StateTypes;

abstract class ListSettingsConditions {

    /**
     * @var array
     */
    protected $containerIds = [];

    /**
     * @var array
     */
    protected $stateTypes = [];

    /**
     * @var int
     */
    protected $limit = 30;

    /**
     * @var array
     */
    protected $states = [];

    /**
     * @var array
     */
    protected $order = [];

    /**
     * @var int
     */
    protected $from = 0;

    /**
     * @var int
     */
    protected $to = 0;

    protected $conditions = [];

    /**
     * @param int $limit
     */
    public function setLimit($limit = null) {
        if ($this->limit !== null) {
            $this->limit = $limit;
        }
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }


    /**
     * @return array
     */
    public function getStateTypes() {
        if (!is_array($this->stateTypes)) {
            return [$this->stateTypes];
        }

        return $this->stateTypes;
    }

    /**
     * @param StateTypes $StateTypes
     */
    public function setStateTypes(StateTypes $StateTypes) {
        if (sizeof($StateTypes->asIntegerArray()) == 2) {
            $this->stateTypes = [];
            return;
        }
        $this->stateTypes = $StateTypes->asIntegerArray();
    }

    /**
     * @return array
     */
    public function getStates() {
        if (!is_array($this->states)) {
            return [$this->states];
        }
        return $this->states;
    }

    /**
     * @return array
     */
    public function getOrder() {
        return SanitizeOrder::filterOrderArray($this->order);
    }

    /**
     * @param array $order
     */
    public function setOrder($order = []) {
        if (!empty($order)) {
            $this->order = $order;
        }
    }

    /**
     * @return int
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * @param int $from
     */
    public function setFrom($from) {
        $this->from = $from;
    }

    /**
     * @return int
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * @param int $to
     */
    public function setTo($to) {
        $this->to = $to;
    }

    /**
     * @return array
     */
    public function getContainerIds() {
        if (!is_array($this->containerIds)) {
            return [$this->containerIds];
        }
        return $this->containerIds;
    }

    /**
     * @param array $containerIds
     */
    public function setContainerIds($containerIds) {
        $this->containerIds = $containerIds;
    }

    /**
     * @return bool
     */
    public function hasContainerIds() {
        return !empty($this->containerIds);
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
    public function setConditions($conditions) {
        $this->conditions = $conditions;
    }

    /**
     * @return bool
     */
    public function hasConditions() {
        return !empty($this->conditions);
    }

}

