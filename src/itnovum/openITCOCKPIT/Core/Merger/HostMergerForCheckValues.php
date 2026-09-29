<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace App\itnovum\openITCOCKPIT\Core\Merger;

/**
 * Class HostMergerForView
 *
 * Compares a given host with a given host template
 * Replace null values in $host array with the corresponding value of $hosttemplate
 *
 * @package itnovum\openITCOCKPIT\Core\Comparison
 */
class HostMergerForCheckValues {

    /**
     * @var array
     */
    private $host;

    /**
     * @var array
     */
    private $hosttemplate;

    /**
     * HostComparison constructor.
     * @param array $host
     * @param array $hosttemplate HosttemplatesTable::$getHosttemplateForDiff()
     */
    public function __construct($host, $hosttemplate) {
        $this->host = $host;
        $this->hosttemplate = $hosttemplate;
    }

    /**
     * @return array
     */
    public function getDataForView() {
        $data = $this->host;
        $data = array_merge($data, $this->getHostBasicFields());

        return $data;
    }

    /**
     * @return array
     */
    public function getHostBasicFields() {
        $fields = [
            'check_interval',
            'retry_interval',
            'max_check_attempts'
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($this->host[$field] === null || $this->host[$field] === '') {
                $data[$field] = $this->hosttemplate[$field];
            }
        }

        return $data;
    }

}
