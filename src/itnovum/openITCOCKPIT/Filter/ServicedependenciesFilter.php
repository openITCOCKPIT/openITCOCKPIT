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


class ServicedependenciesFilter extends Filter {

    /**
     * @return array
     */
    public function indexFilter() {
        $filters = [
            'equals' => [
                'Servicedependencies.id',
                'Servicedependencies.inherits_parent',
                'Servicedependencies.execution_fail_on_ok',
                'Servicedependencies.execution_fail_on_warning',
                'Servicedependencies.execution_fail_on_critical',
                'Servicedependencies.execution_fail_on_unknown',
                'Servicedependencies.execution_fail_on_pending',
                'Servicedependencies.execution_none',
                'Servicedependencies.notification_fail_on_ok',
                'Servicedependencies.notification_fail_on_warning',
                'Servicedependencies.notification_fail_on_critical',
                'Servicedependencies.notification_fail_on_unknown',
                'Servicedependencies.notification_fail_on_pending',
                'Servicedependencies.notification_none'
            ],
            'like'   => [
                'Services.servicename',
                'ServicesDependent.servicename',
                'Servicegroups.name',
                'ServicegroupsDependent.name'
            ],
        ];


        return $this->getConditionsByFilters($filters);
    }

}
