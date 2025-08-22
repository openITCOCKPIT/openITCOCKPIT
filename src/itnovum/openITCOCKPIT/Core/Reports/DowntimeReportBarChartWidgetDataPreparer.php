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

namespace itnovum\openITCOCKPIT\Core\Reports;


/**
 * Class DowntimeReportBarChartWidgetDataPreparer
 * @package itnovum\openITCOCKPIT\Core\Reports
 */
class DowntimeReportBarChartWidgetDataPreparer {
    /**
     * @param $hostData
     * @param $totalTime
     * @return $barChartData array format ['labels'=>[], 'availability'=>[], 'up'=>[], 'down'=>[], 'unreachble' => []]
     */
    public static function getDataForHostsBarChart($hostData, $totalTime) {
        $barChartData = [];
        $labels = array_fill(0, 10, '');
        $defaultSet = array_fill(0, 10, 0); //Availability or status (single) bar chart
        $barChartData = [
            'labels'   => $labels,
            'datasets' => [
                'availability' => [
                    'label' => __('Availability in %'),
                    'type'  => 'line',
                    'data'  => []
                ],
                0              => [
                    'label' => 'Up',
                    'type'  => 'bar',
                    'data'  => $defaultSet
                ],
                1              => [
                    'label' => 'Down',
                    'type'  => 'bar',
                    'data'  => $defaultSet
                ],
                2              => [
                    'label' => 'Unreachable',
                    'type'  => 'bar',
                    'data'  => $defaultSet
                ]
            ]
        ];

        foreach ($hostData as $index => $host) {
            $barChartData['labels'][$index] = $host['Host']['name'];
            $barChartData['datasets']['availability']['data'][$index] = self::calculateAvailability(
                $totalTime,
                $host['Host']['reportData'][1] // <<---- time in seconds with host state 'DOWN'
            );

            foreach ($host['Host']['reportData'] as $state => $value) {
                if ($value === 0) {
                    $barChartData['datasets'][$state]['data'][$index] = $value;
                } else {
                    $barChartData['datasets'][$state]['data'][$index] = self::calculatePercentvalue(
                        $totalTime,
                        $value
                    );
                }

            }
        }

        return $barChartData;
    }

    /**
     * @param $totalTime
     * @param $outageTime
     * @return string availability in percent
     */
    private static function calculateAvailability($totalTime, $outageTime) {
        return number_format((($totalTime - $outageTime) / $totalTime) * 100, 3);
    }


    /**
     * @param $totalTimeInSeconds
     * @param $stateTimeInSecond
     * @return string
     */
    private static function calculatePercentvalue($totalTimeInSeconds, $stateTimeInSecond) {
        return number_format(($stateTimeInSecond / $totalTimeInSeconds) * 100, 3);
    }
}
