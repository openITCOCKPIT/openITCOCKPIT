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

namespace itnovum\openITCOCKPIT\Filter;


class ServiceFilter extends Filter {

    /**
     * @return array
     */
    public function indexFilter() {
        $filters = [
            'bool'           => [
                'Servicestatus.problem_has_been_acknowledged',
                'Servicestatus.notifications_enabled',
                'Servicestatus.active_checks_enabled'
            ],
            'like'           => [
                'Servicestatus.output',
                'servicedescription'
            ],
            'rlike'          => [
                'Hosts.keywords',
                'keywords'
            ],
            'notrlike'       => [
                'Hosts.not_keywords',
                'not_keywords'
            ],
            'equals'         => [
                'Hosts.id',
                'Hosts.satellite_id',
                'Services.id',
                'Services.uuid',
                'Services.disabled',
                'servicepriority',
                'Services.service_type',
            ],
            'downtime'       => [
                'Servicestatus.scheduled_downtime_depth',
            ],
            'state'          => [
                'Servicestatus.current_state'
            ],
            'like_or_rlike'  => [
                'Hosts.name',
                'servicename'
            ],
            'interval_older' => [
                'Servicestatus.last_state_change'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return array
     */
    public function notMonitoredFilter() {
        $filters = [
            'like_or_rlike' => [
                'Hosts.name',
                'servicename',
            ],
            'equals'        => [
                'Hosts.id',
                'Services.uuid'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return array
     */
    public function disabledFilter() {
        return $this->notMonitoredFilter();
    }

    /**
     * @return array
     */
    public function deletedFilter() {
        $filters = [
            'equals' => [
                'DeletedServices.host_id',
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return array
     */
    public function ajaxFilter() {
        $filters = [
            'like'   => [
                'servicename',
            ],
            'equals' => [
                'Services.uuid',
                'Services.disabled'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

}
