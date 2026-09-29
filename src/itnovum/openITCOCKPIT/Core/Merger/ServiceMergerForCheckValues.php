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
 * Class ServiceMergerForView
 *
 * Compares a given service with a given service template
 * Replace null values in $service array with the corresponding value of $servicetemplate
 *
 * @package itnovum\openITCOCKPIT\Core\Comparison
 */
class ServiceMergerForCheckValues {

    /**
     * @var array
     */
    private $service;

    /**
     * @var array
     */
    private $servicetemplate;

    /**
     * ServiceMergerForView constructor.
     * @param array $service
     * @param array $servicetemplate ServicetemplatesTable::$getServicetemplateForDiff()
     */
    public function __construct($service, $servicetemplate) {
        $this->service = $service;
        $this->servicetemplate = $servicetemplate;
    }

    /**
     * @return array
     */
    public function getDataForView() {
        $data = $this->service;
        $data = array_merge($data, $this->getServiceBasicFields());

        return [
            'Service' => $data
        ];
    }

    /**
     * @return array
     */
    public function getServiceBasicFields() {
        $fields = [
            'check_interval',
            'retry_interval',
            'max_check_attempts',
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($this->service[$field] === null || $this->service[$field] === '') {
                $data[$field] = $this->servicetemplate[$field];
            }
        }

        return $data;
    }

}
