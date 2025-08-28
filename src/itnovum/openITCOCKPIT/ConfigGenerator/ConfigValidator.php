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

namespace itnovum\openITCOCKPIT\ConfigGenerator;


class ConfigValidator {

    /**
     * @param $value
     * @return bool
     */
    public function assertString($value) {
        return is_string($value);
    }

    /**
     * @param $value
     * @return bool
     */
    public function assertStringNotEmpty($value) {
        return (strlen($value) > 0);
    }

    /**
     * @param $value
     * @return bool
     */
    public function assertNumeric($value) {
        return is_numeric($value);
    }

    /**
     * @param $value
     * @param bool $strict
     * @return bool
     */
    public function assertFloat($value, $strict = false) {
        if ($strict === true) {
            return is_float($value);
        }

        if (is_float($value) || is_int($value)) {
            return true;
        }
        return false;
    }

    /**
     * @param $value
     * @return bool
     */
    public function assertInt($value) {
        return is_int($value);
    }

    /**
     * @param $value
     * @return bool
     */
    public function assertBool($value, $strict = false) {
        if ($strict === true) {
            return is_bool($value);
        }

        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return true;
        }

        return false;
    }

    /**
     * @param $value
     * @param $assertion
     * @param bool $strict
     * @return bool
     */
    public function assertEq($value, $assertion, $strict = true) {
        if ($strict === true) {
            return $value === $assertion;
        }

        return $value == $assertion;
    }

    /**
     * @param $value
     * @return bool
     */
    public function assertNull($value) {
        return $value === null;
    }

    /**
     * @param $value
     * @return bool
     */
    public function assetArray($value) {
        return is_array($value);
    }

    /**
     * @param $key
     * @param $arr
     * @return bool
     */
    public function assertArrayKeyExists($key, $arr) {
        return isset($arr[$key]);
    }

}
