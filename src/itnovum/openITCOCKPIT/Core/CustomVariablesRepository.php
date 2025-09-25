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


use itnovum\openITCOCKPIT\Core\ValueObjects\CustomVariable;

class CustomVariablesRepository {
    /**
     * @var array
     */
    private $customvariables = [];

    public function __construct() {
    }

    public function addCustomVariable(CustomVariable $CustomVariable) {
        $this->customvariables[] = $CustomVariable;
    }

    /**
     * @return array
     */
    public function getAllCustomVariables() {
        return $this->customvariables;
    }

    /**
     * @return array
     */
    public function getAllCustomVariablesAsArray() {
        $customVariables = [];
        foreach ($this->customvariables as $customvariable) {
            /** @var $customvariable CustomVariable */
            $customVariables[] = $customvariable->asArray();
        }

        return $customVariables;
    }

    /**
     * @param string $name
     *
     * @return CustomVariable|false
     */
    public function getByVariableName($name) {
        foreach ($this->customvariables as $customvariable) {
            if ($customvariable->getName() == $name) {
                return $customvariable;
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function deleteByVariableName($name) {
        $customvariables = [];
        foreach ($this->customvariables as $customvariable) {
            if ($customvariable->getName() != $name) {
                $customvariables[] = $customvariable;
            }
        }
        $this->customvariables = $customvariables;
    }

    /**
     * @return int
     */
    public function getSize() {
        return sizeof($this->customvariables);
    }

}
