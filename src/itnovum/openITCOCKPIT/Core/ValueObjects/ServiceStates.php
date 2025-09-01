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

class ServiceStates {

    /**
     * @var array
     */
    private $stateNames = [
        0 => 'ok',
        1 => 'warning',
        2 => 'critical',
        3 => 'unknown'
    ];

    /**
     * @var array
     */
    private $stateIds = [
        'ok'       => 0,
        'warning'  => 1,
        'critical' => 2,
        'unknown'  => 3
    ];

    /**
     * @var array
     */
    private $states = [
        'ok'       => false,
        'warning'  => false,
        'critical' => false,
        'unknown'  => false
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
    public function hasOk() {
        return $this->states['ok'];
    }

    /**
     * @return bool
     */
    public function hasWarning() {
        return $this->states['warning'];
    }

    /**
     * @return bool
     */
    public function hasCritical() {
        return $this->states['critical'];
    }

    /**
     * @return bool
     */
    public function hasUnknown() {
        return $this->states['unknown'];
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
        if ($this->hasOk()) {
            $states[] = 0;
        }
        if ($this->hasWarning()) {
            $states[] = 1;
        }
        if ($this->hasCritical()) {
            $states[] = 2;
        }
        if ($this->hasUnknown()) {
            $states[] = 3;
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
