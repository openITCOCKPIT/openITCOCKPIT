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

namespace App\itnovum\openITCOCKPIT\Core\Dashboards;


use itnovum\openITCOCKPIT\Core\Dashboards\DashboardJsonStandardizer;

class HostStatusOverviewExtendedJson extends DashboardJsonStandardizer {

    /**
     * @var array
     * Add new fields to this list
     * oITC will take care of the rest of the work
     */
    protected $fields = [
        'Host'       => [
            'name'          => '',
            'name_regex'    => false,
            'address'       => '',
            'address_regex' => false,
            'keywords'      => '',
            'not_keywords'  => ''
        ],
        'Hoststatus' => [
            'current_state'         => 0,
            'acknowledged'          => false,
            'not_acknowledged'      => false,
            'in_downtime'           => false,
            'not_in_downtime'       => false,
            'state_older_than'      => null,
            'state_older_than_unit' => 'MINUTE'
        ],
        'Container'  => [
            '_ids' => ''
        ],
        'Hostgroup'  => [
            '_ids' => ''
        ]
    ];

    /**
     * @param array $request
     * @return array
     */
    public function standardizedData($request = []) {
        if (isset($request['Container']['_ids']) && is_array($request['Container']['_ids'])) {
            $request['Container']['_ids'] = array_filter(
                $request['Container']['_ids'], function ($value) {
                return $value > 0;
            });
            // POST request to save to database
            $request['Container']['_ids'] = implode(',', $request['Container']['_ids']);
        }
        if (isset($request['Hostgroup']['_ids']) && is_array($request['Hostgroup']['_ids'])) {
            $request['Hostgroup']['_ids'] = array_filter(
                $request['Hostgroup']['_ids'], function ($value) {
                return $value > 0;
            });

            // POST request to save to database
            $request['Hostgroup']['_ids'] = implode(',', $request['Hostgroup']['_ids']);
        }
        if (empty($request['Host']['name'])) {
            $request['Host']['name_regex'] = false;
        }
        if (empty($request['Host']['address'])) {
            $request['Host']['address_regex'] = false;
        }
        return $this->_standardizedData($this->fields, $request);
    }
}
