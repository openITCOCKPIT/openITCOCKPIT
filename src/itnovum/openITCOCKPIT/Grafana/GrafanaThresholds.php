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


class GrafanaThresholds {

    /**
     * @var int|null
     */
    private $warning;

    /**
     * @var int|null
     */
    private $critical;


    /**
     * GrafanaThresholds constructor.
     * @param $warning
     * @param $critical
     */
    public function __construct($warning, $critical) {
        $this->warning = $warning;
        $this->critical = $critical;
    }

    /**
     * @return bool
     */
    public function hasWarning() {
        return (!empty($this->warning) && is_numeric($this->warning));
    }

    /**
     * @return bool
     */
    public function hasCritical() {
        return (!empty($this->critical) && is_numeric($this->critical));
    }

    /**
     * @param $asString
     * @return int|null
     */
    public function getWarning($asString = false) {
        if ($asString) {
            // php8.1 Implicit conversion from float 3.1 to int loses precision
            return (string)$this->warning;
        }

        return $this->warning;
    }

    /**
     * @param $asString
     * @return int|null
     */
    public function getCritical($asString = false) {
        if ($asString) {
            // php8.1 Implicit conversion from float 3.1 to int loses precision
            return (string)$this->critical;
        }

        return $this->critical;
    }

}
