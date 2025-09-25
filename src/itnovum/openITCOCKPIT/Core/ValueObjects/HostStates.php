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

class HostStates {

    /**
     * @var array
     */
    private $stateNames = [
        0 => 'recovery',
        1 => 'down',
        2 => 'unreachable'
    ];

    /**
     * @var array
     */
    private $stateIds = [
        'recovery'    => 0,
        'down'        => 1,
        'unreachable' => 2
    ];

    /**
     * @var array
     */
    private $states = [
        'recovery'    => false,
        'down'        => false,
        'unreachable' => false
    ];

    /**
     * @param int $state
     * @param bool $value
     */
    public function setState($state, $value) {
        $key = $this->getStateNameByState($state);
        $this->states[$key] = $value;
    }

    /**
     * @return bool
     */
    public function hasRecovery() {
        return $this->states['recovery'];
    }

    /**
     * @return bool
     */
    public function hasDown() {
        return $this->states['down'];
    }

    /**
     * @return bool
     */
    public function hasUnreachable() {
        return $this->states['unreachable'];
    }

    /**
     * @return array
     */
    public function asArray() {
        return $this->states;
    }


    /**
     * @return array
     */
    public function asIntegerArray() {
        $states = [];
        if ($this->hasRecovery()) {
            $states[] = 0;
        }
        if ($this->hasDown()) {
            $states[] = 1;
        }
        if ($this->hasUnreachable()) {
            $states[] = 2;
        }
        return $states;
    }

    /**
     * @return array
     */
    public function getAvailableStateIds() {
        return $this->stateIds;
    }

    /**
     * @param int $state
     * @return string
     * @throws UnknownStateException
     */
    public function getStateNameByState($state) {
        if (isset($this->stateNames[$state])) {
            return $this->stateNames[$state];
        }

        throw new UnknownStateException(sprintf('State "%s" is not a valid state id', $state));
    }

}
