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

class DowntimesMerger {

    /**
     * @param string $key
     * @param array $downtimes
     * @param array $systemfailures
     * @return array
     */
    public static function mergeDowntimesWithSystemfailures($key = 'DowntimeHost', $downtimes = [], $systemfailures = []) {
        if (empty($systemfailures)) {
            return $downtimes;
        }
        //If downtimes are empty set $useTimetamp = true, systemfailures (start_time and end_time) are always in mysql datetime format
        $useTimetamp = true;
        if (!empty($downtimes)) {
            $useTimetamp = is_numeric($downtimes[0][$key]['scheduled_start_time']);
        }
        foreach ($systemfailures as $systemfailure) {
            $start = strtotime($systemfailure['Systemfailure']['start_time']);
            $end = strtotime($systemfailure['Systemfailure']['end_time']);
            if ($useTimetamp === true) {
                $scheduled_start_time = $start;
                $scheduled_end_time = $end;
            }
            if ($useTimetamp === false) {
                $scheduled_start_time = date('Y-m-d H:i:s', $start);
                $scheduled_end_time = date('Y-m-d H:i:s', $end);
            }
            $duration = $end - $start;
            $downtimes[] = [
                $key => [
                    'author_name'          => 'Unknown',
                    'comment_data'         => $systemfailure['Systemfailure']['comment'],
                    'scheduled_start_time' => $scheduled_start_time,
                    'scheduled_end_time'   => $scheduled_end_time,
                    'duration'             => $duration,
                    'was_started'          => (($start < time()) ? true : false),
                    'was_cancelled'        => false
                ]
            ];
        }
        return Hash::sort($downtimes, '{n}.' . $key . '.scheduled_start_time', 'ASC');
    }
}
