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

namespace itnovum\openITCOCKPIT\Core\ValueObjects;


use itnovum\openITCOCKPIT\Exceptions\UnknownStateException;

class StateTypes {

    /**
     * @var array
     */
    private $stateTypeNames = [
        0 => 'soft',
        1 => 'hard'
    ];

    /**
     * @var array
     */
    private $stateTypeIds = [
        'soft' => 0,
        'hard' => 1
    ];

    /**
     * @var array
     */
    private $stateTypes = [
        'soft' => false,
        'hard' => false
    ];

    /**
     * @param int $state
     * @param bool $value
     */
    public function setStateType($stateType, $value) {
        $key = $this->getStateTypeNameByStateType($stateType);
        $this->stateTypes[$key] = $value;
    }

    /**
     * @return bool
     */
    public function hasSoft() {
        return $this->stateTypes['soft'];
    }

    /**
     * @return bool
     */
    public function hasHard() {
        return $this->stateTypes['hard'];
    }

    /**
     * @return array
     */
    public function asArray() {
        return $this->stateTypes;
    }

    /**
     * @return array
     */
    public function asIntegerArray() {
        $stateTypes = [];
        if ($this->hasSoft()) {
            $stateTypes[] = 0;
        }
        if ($this->hasHard()) {
            $stateTypes[] = 1;
        }
        return $stateTypes;
    }

    /**
     * @return array
     */
    public function getAvailableStateTypeIds() {
        return $this->stateTypeIds;
    }

    /**
     * @param int $stateType
     * @return string
     * @throws UnknownStateException
     */
    public function getStateTypeNameByStateType($stateType) {
        if (isset($this->stateTypeNames[$stateType])) {
            return $this->stateTypeNames[$stateType];
        }

        throw new UnknownStateException(sprintf('State type "%s" is not a valid state type id', $stateType));
    }

}
