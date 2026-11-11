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

declare(strict_types=1);

namespace itnovum\openITCOCKPIT\Vuln\Policy;

/**
 * State of a host for the host list, see HostRules::state().
 */
readonly class HostState {

    /**
     * @param AssessmentStatus $status
     * @param Color $color Gray if the data is stale or nothing could be evaluated
     * @param int $sortOrder Urgent hosts first, then stale hosts, hosts without evaluation last
     * @param int|null $inventoryAgeDays null if the age of the inventory is unknown
     */
    public function __construct(
        public AssessmentStatus $status,
        public Color      $color,
        public int        $sortOrder,
        public ?int       $inventoryAgeDays
    ) {
    }
}
