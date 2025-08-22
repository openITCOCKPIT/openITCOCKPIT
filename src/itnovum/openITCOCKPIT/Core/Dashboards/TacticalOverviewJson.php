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


class TacticalOverviewJson extends DashboardJsonStandardizer {

    /**
     * @var array
     * Add new fields to this list
     * oITC will take care of the rest of the work
     */
    protected $fields = [
        'Host'         => [
            'name'          => '',
            'name_regex'    => false,
            'address'       => '',
            'address_regex' => false,
            'keywords'      => '',
            'not_keywords'  => ''
        ],
        'Service'      => [
            'servicename'       => '',
            'servicename_regex' => false,
            'keywords'          => '',
            'not_keywords'      => ''
        ],
        'Hostgroup'    => [
            '_ids' => ''
        ],
        'Servicegroup' => [
            '_ids' => ''
        ]
    ];

    /**
     * @param array $request
     * @return array
     */
    public function standardizedData($request = []) {
        if (isset($request['Hostgroup']['_ids']) && is_array($request['Hostgroup']['_ids'])) {
            $request['Hostgroup']['_ids'] = array_filter(
                $request['Hostgroup']['_ids'], function ($value) {
                return $value > 0;
            });

            // POST request to save to database
            $request['Hostgroup']['_ids'] = implode(',', $request['Hostgroup']['_ids']);
        }
        if (isset($request['Servicegroup']['_ids']) && is_array($request['Servicegroup']['_ids'])) {
            $request['Servicegroup']['_ids'] = array_filter(
                $request['Servicegroup']['_ids'], function ($value) {
                return $value > 0;
            });
            // POST request to save to database
            $request['Servicegroup']['_ids'] = implode(',', $request['Servicegroup']['_ids']);
        }
        return $this->_standardizedData($this->fields, $request);
    }
}
