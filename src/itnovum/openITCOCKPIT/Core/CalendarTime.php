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

namespace itnovum\openITCOCKPIT\Core;

class CalendarTime {

    /**
     * @var array
     */
    private $weekdays;

    /**
     * @var array
     */
    private $months;

    /**
     * CalendarTime constructor.
     */
    public function __construct() {
        $this->weekdays = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            7 => __('Sunday'),
        ];

        $this->months = [
            1  => __('January'),
            2  => __('February'),
            3  => __('March'),
            4  => __('April'),
            5  => __('May'),
            6  => __('June'),
            7  => __('July'),
            8  => __('August'),
            9  => __('September'),
            10 => __('October'),
            11 => __('November'),
            12 => __('December')
        ];
    }

    /**
     * @return string[]
     */
    private function getWeekDaysShort() {
        return array_map(function (string $value): string {
            return substr($value, 0, 2);
        }, $this->weekdays);
    }

    /**
     * @return array
     */
    private function getWeekDays() {
        return $this->weekdays;
    }

    /**
     * @return array
     */
    private function getMonths() {
        return $this->months;
    }

    public function getDateDetailsByTimestamp(int $timestamp, bool $extended = false) {
        $dayNumber = (int)date('j', $timestamp);
        $dateDetails = [
            'dayNumber'           => $dayNumber,
            'weekday'             => $this->weekdays[date('N', $timestamp)],
            'monthName'           => $this->months[date('n', $timestamp)],
            'start'               => date('d.m.Y H:i:s', strtotime('01' . date('.m.Y', $timestamp) . ' 00:00:00')),
            'end'                 => date('d.m.Y H:i:s', strtotime(date('t.m.Y', $timestamp) . ' 00:00:00')),
            'today_timestamp'     => strtotime(date('d.m.Y', $timestamp) . ' 00:00:00'),
            'yesterday_timestamp' => strtotime(date('d.m.Y', $timestamp) . ' 00:00:00 yesterday'),
            'start_timestamp'     => strtotime('01' . date('.m.Y', $timestamp) . ' 00:00:00'),
            'end_timestamp'       => strtotime(date('t.m.Y', $timestamp) . ' 00:00:00')
        ];
        if (!$extended) {
            return $dateDetails;
        }

        $currentDay = new \DateTime(date('Y-m-d', $timestamp));
        if ($dayNumber > 1) {
            $dayDifference = $dayNumber - 1;
            $currentDay = $currentDay->modify('-' . $dayDifference . ' day');
        }

        $totalDays = (int)date('t', $timestamp);
        $days = [];
        for ($i = 1; $i <= $totalDays; $i++) {
            $weekDay = (int)date('N', $currentDay->getTimestamp());
            $weekNumber = date('W', $currentDay->getTimestamp());
            if ($i === 1 && $weekDay > 1) { //autofill weekdays at start of the week if first day is not monday
                for ($j = 1; $j < $weekDay; $j++) {
                    $days[$weekNumber][] = [
                        'day'       => null,
                        'weekday'   => null,
                        'timestamp' => null
                    ];
                }
            }
            $days[$weekNumber][] = [
                'day'       => $i,
                'weekday'   => $weekDay,
                'timestamp' => $currentDay->getTimestamp()
            ];
            $currentDay = $currentDay->modify('+1 day');

            if ($i === $totalDays && $weekDay < 7) { //autofill weekdays at end of the week if last day is not sunday
                for ($j = $weekDay; $j < 7; $j++) {
                    $days[$weekNumber][] = [
                        'day'       => null,
                        'weekday'   => null,
                        'timestamp' => null
                    ];
                }
            }
        }

        $dateDetails['days'] = [];
        foreach ($days as $cw => $daysPerWeek) {
            // Keep order in javascript (array not object)
            $dateDetails['days'][] = [
                'cw'   => $cw,
                'days' => $daysPerWeek
            ];
        }

        //$dateDetails['days'] = $days;
        $dateDetails['weekdayNames'] = $this->getWeekDaysShort();

        return $dateDetails;
    }
}
