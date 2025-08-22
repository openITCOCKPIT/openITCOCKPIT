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


use Cake\Utility\Hash;

class StatehistoryConverter {

    /**
     * @param array $timeSlices
     * @param array $stateHistoryArray
     * @param bool $hardStateOnly
     * @param bool $isHost
     * @return array
     */
    public static function generateReportData(array $timeSlices, array $stateHistoryArray, $hardStateOnly, $isHost = false) {
        $evaluationData = array_fill(0, ($isHost) ? 3 : 4, 0); // host states => 0,1,2; service states => 0,1,2,3

        $stateOk = 0;
        $stateUnknown = ($isHost) ? 2 : 3;//if the end of date in the future
        $outageState = ($isHost) ? 1 : 2; // 1 for host down and 2 for service critical
        $setInitialState = false;
        $currentState = 0;

        foreach ($timeSlices as $timeSliceKey => $timeSlice) {
            $time = $timeSlice['start'];
            if ($time > strtotime('today 24:00:00')) { // ignore time_slice in the future
                $currentState = $stateUnknown;
            }
            reset($stateHistoryArray);
            foreach ($stateHistoryArray as $key => $stateHistory) {
                $stateTimeTimestamp = $stateHistory['state_time'];
                if (!$setInitialState) {
                    $currentState = $stateHistory['last_state'];
                    if ($hardStateOnly && $stateHistory['last_state'] != 0) {
                        $currentState = $stateHistory['last_hard_state'];
                    }
                    $currentState = ($currentState == -1) ? 0 : $currentState;
                    $setInitialState = true;
                }
                if ($stateTimeTimestamp >= $timeSlice['end']) {
                    // if state time after time slice
                    break;
                }
                if ($stateTimeTimestamp <= $timeSlice['start']) {
                    if ($stateHistory['state'] == 0 || !$hardStateOnly || ($hardStateOnly && $stateHistory['is_hardstate'])) {
                        $currentState = $stateHistory['state'];
                    }
                    unset($stateHistoryArray[$key]);
                    continue;
                }

                //if outage in downtime add time for state "ok"
                $evaluationData[$currentState] += $stateTimeTimestamp - $time;
                if ($stateHistory['state'] == 0 || !$hardStateOnly || ($hardStateOnly && $stateHistory['is_hardstate'])) {
                    $currentState = $stateHistory['state'];
                }
                $time = $stateTimeTimestamp;
                unset($stateHistoryArray[$key]);

            }

            $evaluationData[$currentState] += $timeSlice['end'] - $time;

        }
        unset($timeSlices, $stateHistory);
        return $evaluationData;
    }


    /**
     * @param array $timeSlices
     * @param array $stateHistoryArray
     * @param bool $hardStateOnly
     * @param bool $isHost
     * @param bool $outagesSummary
     * @return mixed
     */
    public static function generateReportDataWithOutages(array $timeSlices, array $stateHistoryArray, $hardStateOnly, bool $isHost = false, bool $keepDetails = false) {
        $stateArray = array_fill(0, ($isHost) ? 3 : 4, 0); // host states => 0,1,2; service statea => 0,1,2,3
        $stateOk = 0;
        $stateUnknown = ($isHost) ? 2 : 3;//if the end of date in the future
        $evaluationData = Hash::merge($stateArray, [
            'outages' => [],
        ]);
        $outageState = ($isHost) ? 1 : 2; // 1 for host down and 2 for service critical
        $setInitialState = false;
        $currentState = 0;
        $outageCounter = 0;
        $lastOutput = '';
        $lastIsHardstate = true;
        foreach ($timeSlices as $timeSliceKey => $timeSlice) {
            $time = $timeSlice['start'];
            if ($time > strtotime('today 24:00:00')) { // ignore time_slice in the future
                $currentState = $stateUnknown;
            }
            $isDowntime = $timeSlice['is_downtime'] ?? false;
            reset($stateHistoryArray);
            foreach ($stateHistoryArray as $key => $stateHistory) {
                $stateTimeTimestamp = $stateHistory['state_time'];
                if ($stateHistory['state'] === $outageState) {
                    $lastOutput = $stateHistory['output'];
                    $lastIsHardstate = $stateHistory['is_hardstate'];
                }
                if (!$setInitialState) {
                    $currentState = $stateHistory['last_state'];
                    if ($hardStateOnly && $stateHistory['last_state'] != 0) {
                        $currentState = $stateHistory['last_hard_state'];
                    }
                    $currentState = ($currentState == -1) ? 0 : $currentState;
                    $setInitialState = true;
                }
                if ($stateTimeTimestamp >= $timeSlice['end']) {
                    // if state time after time slice
                    break;
                }
                if ($stateTimeTimestamp <= $timeSlice['start']) {
                    if ($stateHistory['state'] == 0 || !$hardStateOnly || ($hardStateOnly && $stateHistory['is_hardstate'])) {
                        $currentState = $stateHistory['state'];
                    }

                    unset($stateHistoryArray[$key]);
                    continue;
                }

                //if outage in downtime add time for state "ok"
                if (($currentState == $outageState) && $isDowntime) {
                    $evaluationData[0] += $stateTimeTimestamp - $time;
                    //$current_state = $state_ok;
                    $evaluationData['outages'][$outageCounter] = [
                        'start'        => $time,
                        'end'          => $stateTimeTimestamp,
                        'is_downtime'  => $isDowntime,
                        'output'       => $keepDetails ? $lastOutput : null,
                        'is_hardstate' => $keepDetails ? $lastIsHardstate : null
                    ];
                    $outageCounter++;
                } else {
                    $evaluationData[$currentState] += $stateTimeTimestamp - $time;
                    if ($currentState == $outageState && ($stateTimeTimestamp - $time) > 0) {
                        $evaluationData['outages'][$outageCounter] = [
                            'start'        => $time,
                            'end'          => $stateTimeTimestamp,
                            'is_downtime'  => $isDowntime,
                            'output'       => $keepDetails ? $lastOutput : null,
                            'is_hardstate' => $keepDetails ? $lastIsHardstate : null
                        ];
                        $outageCounter++;
                    }
                }
                if ($stateHistory['state'] == 0 || !$hardStateOnly || ($hardStateOnly && $stateHistory['is_hardstate'])) {
                    $currentState = $stateHistory['state'];
                }
                $time = $stateTimeTimestamp;
                unset($stateHistoryArray[$key]);

            }
            //if outage in downtime add time for state "ok"
            if ($currentState == $outageState && $isDowntime) {
                $evaluationData[$stateOk] += $timeSlice['end'] - $time;
                $evaluationData['outages'][$outageCounter] = [
                    'start'        => $time,
                    'end'          => $timeSlice['end'],
                    'is_downtime'  => $isDowntime,
                    'output'       => $keepDetails ? $lastOutput : null,
                    'is_hardstate' => $keepDetails ? $lastIsHardstate : null
                ];
                $outageCounter++;
            } else {
                $evaluationData[$currentState] += $timeSlice['end'] - $time;
                if ($currentState == $outageState) {
                    $evaluationData['outages'][$outageCounter] = [
                        'start'        => $time,
                        'end'          => $timeSlice['end'],
                        'is_downtime'  => $isDowntime,
                        'output'       => $keepDetails ? $lastOutput : null,
                        'is_hardstate' => $keepDetails ? $lastIsHardstate : null
                    ];
                    $outageCounter++;
                }
            }
        }
        $evaluationData['outages_summarized_in_downtime'] = self::mergeOutages(
            Hash::extract($evaluationData, 'outages.{n}[is_downtime=1]')
        );
        $evaluationData['outages'] = self::mergeOutages(
            Hash::extract($evaluationData, 'outages.{n}[is_downtime=0]')
        );
        if (!empty($evaluationData['outages'])) {
            $evaluationData['maximum_outage_duration'] = Hash::apply(
                array_map(function ($outage) {
                    return $outage['end'] - $outage['start'];
                }, $evaluationData['outages']),
                '{n}',
                'max'
            );
        }
        unset($timeSlices, $stateHistory);
        return $evaluationData;
    }


    /**
     * @param $outageArray
     * @return array
     */
    public static function mergeOutages($outageArray) {
        $newOutagesArray = [];
        $tmpOutageStart = 0;
        $tmpOutageEnd = 0;
        $arrayCounter = 0;
        foreach ($outageArray as $key => $outage) {
            $outageStart = $outage['start'];
            $outageEnd = $outage['end'];
            if ($tmpOutageStart == 0 && $tmpOutageEnd == 0) {
                $tmpOutageStart = $outageStart;
                $tmpOutageEnd = $outageEnd;
                $newOutagesArray[$arrayCounter] = [
                    'start'        => $outageStart,
                    'end'          => $outageEnd,
                    'is_downtime'  => $outage['is_downtime'],
                    'output'       => $outage['output'],
                    'is_hardstate' => $outage['is_hardstate']
                ];
                continue;
            }
            if ($outageStart >= $tmpOutageStart && $outageEnd <= $tmpOutageEnd) {
                continue;
            } else if ($outageStart >= $tmpOutageStart && $outageStart <= $tmpOutageEnd && $outageEnd > $tmpOutageEnd) {
                $tmpOutageEnd = $outageEnd;
                $newOutagesArray[$arrayCounter] = [
                    'start'        => $tmpOutageStart,
                    'end'          => $outageEnd,
                    'is_downtime'  => $outage['is_downtime'],
                    'output'       => $outage['output'],
                    'is_hardstate' => $outage['is_hardstate']
                ];
            } else if ($outageStart > $tmpOutageStart && $outageStart > $tmpOutageEnd) {
                $arrayCounter++;
                $tmpOutageStart = $outageStart;
                $tmpOutageEnd = $outageEnd;
                $newOutagesArray[$arrayCounter] = [
                    'start'        => $outageStart,
                    'end'          => $outageEnd,
                    'is_downtime'  => $outage['is_downtime'],
                    'output'       => $outage['output'],
                    'is_hardstate' => $outage['is_hardstate']
                ];
            }
        }
        return $newOutagesArray;
    }

    public static function getPercentageValues($values, $totalTime, $isHost = true) {
        if ($isHost) {
            $humanStates = [
                0 => __('Up'),
                1 => __('Down'),
                2 => __('Unreachable')
            ];
        } else {
            $humanStates = [
                0 => __('Ok'),
                1 => __('Warning'),
                2 => __('Critical'),
                3 => __('Unknown')
            ];
        }

        foreach ($values as $state => $value) {
            $values[$state] = sprintf('%s (%s%%)',
                $humanStates[$state],
                floatval(number_format($value / $totalTime * 100, 3))
            );
        }
        return $values;
    }

    public static function hostStateSummary($hostsStatesHistoryEntries) {
        $totalHostsData = [
            0 => 0,
            1 => 0,
            2 => 0
        ];
        foreach ($hostsStatesHistoryEntries as $hostStatesHistoryEntries) {
            foreach ($hostStatesHistoryEntries as $state => $hostStatesHistoryEntry) {
                $totalHostsData[$state] += $hostStatesHistoryEntry;
            }
        }
        return $totalHostsData;
    }

    public static function serviceStateSummary($servicesStatesHistoryEntries) {
        $totalServicesData = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0
        ];
        foreach ($servicesStatesHistoryEntries as $serviceStatesHistoryEntries) {
            foreach ($serviceStatesHistoryEntries as $state => $serviceStatesHistoryEntry) {
                $totalServicesData[$state] += $serviceStatesHistoryEntry;
            }
        }
        return $totalServicesData;
    }
}
