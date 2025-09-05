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

namespace itnovum\openITCOCKPIT\Core\Views;


use itnovum\openITCOCKPIT\Core\Servicestatus;

class ServiceStateSummary {

    /**
     * @param array $servicestatusArray array of \itnovum\openITCOCKPIT\Core\Servicestatus objects
     * @param bool $extended show details ('acknowledged', 'in downtime', ...)
     * @return array
     */
    public static function getServiceStateSummary($servicestatusArray, $extended = true) {
        $serviceStateSummary = [
            'state' => [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
            ],
            'total' => 0
        ];
        if ($extended === true) {
            $serviceStateSummary = [
                'state'        => [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                ],
                'acknowledged' => [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                ],
                'in_downtime'  => [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                ],
                'not_handled'  => [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                ],
                'passive'      => [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                ],
                'total'        => 0
            ];
        }
        if (empty($servicestatusArray)) {
            return $serviceStateSummary;
        }
        foreach ($servicestatusArray as $Servicestatus) {
            /** @var $Servicestatus Servicestatus */
            $serviceStateSummary['state'][$Servicestatus->currentState()]++;
            if ($extended === true) {
                if ($Servicestatus->currentState() > 0) {
                    if ($Servicestatus->isAcknowledged()) {
                        $serviceStateSummary['acknowledged'][$Servicestatus->currentState()]++;
                    } else {
                        $serviceStateSummary['not_handled'][$Servicestatus->currentState()]++;
                    }
                }

                if ($Servicestatus->isInDowntime()) {
                    $serviceStateSummary['in_downtime'][$Servicestatus->currentState()]++;
                }
                if ($Servicestatus->isActiveChecksEnabled() === false) {
                    $serviceStateSummary['passive'][$Servicestatus->currentState()]++;
                }
            }
            $serviceStateSummary['total']++;
        }
        return $serviceStateSummary;
    }

}
