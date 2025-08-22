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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\Grafana;


class GrafanaTargetCollection {

    /**
     * @var array of GrafanaTargetInterface's
     */
    private $targets = [];

    /**
     * @var array
     */
    private $abc = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
        'U', 'V', 'W', 'Y', 'X', 'Z'
    ];

    /**
     * @return array
     */
    public function addTarget(GrafanaTargetInterface $grafanaTarget) {
        $this->targets[] = $grafanaTarget;
    }

    /**
     * @return array
     */
    public function getTargetsAsArray() {
        $targetsArray = [];
        foreach ($this->targets as $key => $grafanaTarget) {
            /**
             * @var GrafanaTargetInterface $grafanaTarget
             */
            if (isset($this->abc[$key])) {
                $refId = $this->abc[$key];
            } else {
                $refId = sprintf('A%s', $key);
            }

            $targetsArray[] = $grafanaTarget->toJson($refId);
        }
        return $targetsArray;
    }


    /**
     * @return GrafanaTargetInterface[]
     */
    public function getTargets() {
        return $this->targets;
    }

    /**
     * @return bool
     */
    public function canDisplayUnits() {
        $units = $this->getUnits();
        if (sizeof($units) <= 2) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getUnits() {
        $units = [];
        foreach ($this->targets as $target) {
            /** @var GrafanaTargetInterface $target */

            //Filter duplicate units
            $units[$target->getUnit()] = true;
        }

        return array_keys($units);
    }
}
