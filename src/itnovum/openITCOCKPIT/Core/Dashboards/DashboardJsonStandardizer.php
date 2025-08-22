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

namespace itnovum\openITCOCKPIT\Core\Dashboards;


class DashboardJsonStandardizer {

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @param array $request
     * @return array
     */
    public function standardizedData($request = []) {
        return $this->_standardizedData($this->fields, $request);
    }

    /**
     * @param array $fields
     * @param array $request
     * @return array
     */
    protected function _standardizedData($fields, $request) {
        $result = [];
        foreach ($fields as $key => $value) {
            if (is_array($value) && isset($request[$key])) {
                $result[$key] = $this->_standardizedData($fields[$key], $request[$key]);
            } else {
                if (isset($request[$key])) {
                    switch (gettype($fields[$key])) {
                        case 'boolean':
                            $result[$key] = (bool)$request[$key];
                            break;
                        case 'integer':
                            $result[$key] = (int)$request[$key];
                            break;
                        default:
                            $result[$key] = (string)$request[$key];
                    }
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

}
