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


use itnovum\openITCOCKPIT\Core\Views\UserTime;

/**
 * Class DowntimeReportPieChartWidgetDataPreparer
 * @package itnovum\openITCOCKPIT\Core\Reports
 */
class DowntimeReportPieChartWidgetDataPreparer {

    /**
     * @param $hostData
     * @param $totalTime
     * @param UserTime $UserTime
     * @return array $pieChartData format ['labels'=>[], 'data' => [], 'availability', 'widgetOverview' => []]
     * @throws \Exception
     */
    public static function getDataForHostPieChartWidget($hostData, $totalTime, UserTime $UserTime) {
        if (empty($hostData['Host']['reportData'])) {
            return [];
        }
        return [
            'labels'         => [
                __('Up'),
                __('Down'),
                __('Unreachable')
            ],
            'data'           => self::calculatePercentageValue($totalTime, $hostData['Host']['reportData']),
            'availability'   => self::calculateAvailability(
                $totalTime,
                $hostData['Host']['reportData'][1] // <<---- time in seconds with host state 'DOWN'
            ),
            'widgetOverview' => self::getStateTimeAsDataset(
                $hostData['Host']['reportData'],
                $totalTime,
                $UserTime
            )
        ];
    }

    /**
     * @param $serviceData
     * @param $totalTime
     * @param UserTime $UserTime
     * @return array $pieChartData format ['labels'=>[], 'data' => [], 'availability', 'widgetOverview' => []]
     * @throws \Exception
     */
    public static function getDataForServicePieChart($serviceData, $totalTime, UserTime $UserTime) {
        if (empty($serviceData['Service']['reportData'])) {
            return [];
        }
        return [
            'labels'         => [
                __('Ok'),
                __('Warning'),
                __('Critical'),
                __('Unknown')
            ],
            'data'           => self::calculatePercentageValue($totalTime, $serviceData['Service']['reportData']),
            'availability'   => self::calculateAvailability(
                $totalTime,
                $serviceData['Service']['reportData'][2] // <<---- time in seconds with service state 'CRITICAL'
            ),
            'widgetOverview' => self::getStateTimeAsDataset(
                $serviceData['Service']['reportData'],
                $totalTime,
                $UserTime
            )
        ];
    }

    /**
     * @param $totalTime
     * @param $compareValues
     * @param UserTime $UserTime
     * @return array value as percent and human readable values
     * @throws \Exception
     */
    private static function getStateTimeAsDataset($compareValues, $totalTime, UserTime $UserTime) {
        $dataSet = [];
        foreach ($compareValues as $state => $value) {
            /** @var UserTime $UserTime */
            $dataSet[$state] = [
                'percent' => number_format(($value / $totalTime * 100), 3),
                'human'   => $UserTime->secondsInHumanShort($value)
            ];
        }
        return $dataSet;
    }

    /**
     * @param $totalTime
     * @param $outageTime
     * @return string availability in percent
     */
    private static function calculateAvailability($totalTime, $outageTime) {
        return number_format((($totalTime - $outageTime) / $totalTime) * 100);
    }

    /**
     * @param $totalTime
     * @param $stateData
     * @return array $percentValues [0 => 10, 1 => 80, 3 => 10]
     */
    private static function calculatePercentageValue($totalTime, $stateData) {
        $percentValues = [];
        foreach ($stateData as $state => $secondsValue) {
            $percentValues[$state] = number_format($secondsValue / $totalTime * 100, 3);
        }
        return $percentValues;
    }
}
