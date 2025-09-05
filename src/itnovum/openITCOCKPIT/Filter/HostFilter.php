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


class HostFilter extends Filter {

    /**
     * @return array
     */
    public function indexFilter() {
        $filters = [
            'bool'           => [
                'Hoststatus.problem_has_been_acknowledged',
                'Hoststatus.notifications_enabled',
                'Hoststatus.active_checks_enabled',
                'Hoststatus.is_hardstate'
            ],
            'like'           => [
                'Hosts.description',
                'Hoststatus.output',
                'hostdescription',
            ],
            'rlike'          => [
                'Hosts.keywords'
            ],
            'notrlike'       => [
                'Hosts.not_keywords'
            ],
            'equals'         => [
                'Hosts.id',
                'Hosts.uuid',
                'Hosts.disabled',
                'Hosts.satellite_id',
                'hostpriority',
                'Hosts.host_type'
            ],
            'downtime'       => [
                'Hoststatus.scheduled_downtime_depth',
            ],
            'state'          => [
                'Hoststatus.current_state'
            ],
            'like_or_rlike'  => [
                'Hosts.name',
                'Hosts.address',
            ],
            'interval_older' => [
                'Hoststatus.last_state_change'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return array
     */
    public function notMonitoredFilter() {
        $filters = [
            'like'          => [
                'Hosts.description',
            ],
            'equals'        => [
                'Hosts.id',
                'Hosts.satellite_id'
            ],
            'like_or_rlike' => [
                'Hosts.name',
                'Hosts.address',
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return array
     */
    public function deletedFilter() {
        $filters = [
            'like_or_rlike' => [
                'DeletedHosts.name'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return array
     */
    public function disabledFilter() {
        $filters = [
            'like'          => [
                'Hosts.description',
            ],
            'like_or_rlike' => [
                'Hosts.name',
                'Hosts.address',
            ],
            'equals'        => [
                'Hosts.id',
                'Hosts.satellite_id'
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
                'Hosts.name',
            ],
            'equals' => [
                'Hosts.uuid',
                'Hosts.disabled'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

}
