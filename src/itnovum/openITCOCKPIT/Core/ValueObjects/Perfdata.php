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


namespace itnovum\openITCOCKPIT\Core\ValueObjects;


class Perfdata {

    /**
     * @var string
     */
    private $label;

    /**
     * @var int
     */
    private $currentValue;

    /**
     * @var string|null
     */
    private $unit;

    /**
     * @var int|null
     */
    private $warning;

    /**
     * @var int|null
     */
    private $critical;

    /**
     * @var int|null
     */
    private $min;

    /**
     * @var int|null
     */
    private $max;

    /**
     * Perfdata constructor.
     * @param $label
     * @param $currentValue
     * @param $unit
     * @param $warning
     * @param $critical
     * @param $min
     * @param $max
     */
    public function __construct($label, $currentValue, $unit = null, $warning = null, $critical = null, $min = null, $max = null) {
        $this->label = $label;
        $this->currentValue = $currentValue;
        $this->unit = $unit;
        $this->warning = $warning;
        $this->critical = $critical;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @param $label
     * @param $perfdata
     * @return Perfdata
     */
    public static function fromArray($label, $perfdata) {
        $currentValue = null;
        $unit = null;
        $warning = null;
        $critical = null;
        $min = null;
        $max = null;
        if (!empty($perfdata['current'])) {
            $currentValue = $perfdata['current'];
        }
        if (!empty($perfdata['unit'])) {
            $unit = $perfdata['unit'];
        }

        if (!empty($perfdata['warn'])) {
            $warning = $perfdata['warn'];
        }
        if (!empty($perfdata['crit'])) {
            $critical = $perfdata['crit'];
        }

        if (!empty($perfdata['warning'])) {
            $warning = $perfdata['warning'];
        }
        if (!empty($perfdata['critical'])) {
            $critical = $perfdata['critical'];
        }

        if (!empty($perfdata['min'])) {
            $min = $perfdata['min'];
        }
        if (!empty($perfdata['max'])) {
            $max = $perfdata['max'];
        }

        return new self($label, $currentValue, $unit, $warning, $critical, $min, $max);

    }

    /**
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    public function getReplacedLabel() {
        $pattern = '/[^a-zA-Z^0-9\-\.]/';
        return preg_replace($pattern, '_', $this->label);
    }

    /**
     * @return int
     */
    public function getCurrentValue() {
        return $this->currentValue;
    }

    /**
     * @return null|string
     */
    public function getUnit() {
        return $this->unit;
    }

    /**
     * @return int|null
     */
    public function getWarning() {
        return $this->warning;
    }

    /**
     * @return int|null
     */
    public function getCritical() {
        return $this->critical;
    }

    /**
     * @return int|null
     */
    public function getMin() {
        return $this->min;
    }

    /**
     * @return int|null
     */
    public function getMax() {
        return $this->max;
    }
}
