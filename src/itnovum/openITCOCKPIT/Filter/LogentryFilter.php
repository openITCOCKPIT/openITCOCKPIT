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


class LogentryFilter extends Filter {

    /**
     * @var array
     */
    private $uuids = [];

    /**
     * @return array
     */
    public function indexFilter() {
        $filters = [
            'equals' => [
                'Logentries.logentry_type',
            ],
            'like'   => [
                'Logentries.logentry_data'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * @return bool
     */
    public function hasHostIdFilter() {
        return !empty($this->getHostIds());
    }

    /**
     * @return array
     */
    public function getHostIds() {
        $filter = $this->Request->getQuery('filter', []);
        $hostIds = [];

        if (isset($filter['Host.id'])) {
            $hostIds = $filter['Host.id'];

            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
                array_unique($hostIds);
            }
        }

        return $hostIds;
    }

    /**
     * @param array|string $uuids
     */
    public function addUuidsToMatching($uuids) {
        if (!is_array($uuids)) {
            $uuids = [$uuids];
        }

        foreach ($uuids as $uuid) {
            $this->uuids[] = $uuid;
        }
    }

    /**
     * @return array
     */
    public function getMatchingUuids(){
        return $this->uuids;
    }

}
