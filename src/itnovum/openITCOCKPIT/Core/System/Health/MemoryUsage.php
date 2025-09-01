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

namespace itnovum\openITCOCKPIT\Core\System\Health;


class MemoryUsage {


    public function getMemoryUsage($memoryWarningInPercent = null, $memoryCriticalInPercent = null, $swapWarningInPercent = null, $swapCriticalInPercent = null) {
        $memory = [
            'memory' => [
                'total'   => 0,
                'used'    => 0,
                'free'    => 0,
                'buffers' => 0,
                'cached'  => 0,
            ],
            'swap'   => [
                'total' => 0,
                'used'  => 0,
                'free'  => 0,
            ]
        ];


        $data = explode("\n", file_get_contents("/proc/meminfo"));
        $meminfo = [];
        foreach ($data as $line) {
            $temp = explode(":", $line);
            if (isset($temp[1])) {
                $meminfo[$temp[0]] = intval(trim(intval(substr($temp[1], 0, strpos($temp[1], "kB")))) / 1024);
            }
        }

        $cached = $meminfo['Cached'] + $meminfo['SReclaimable'] - $meminfo['Shmem'];
        $used = $meminfo['MemTotal'] - $meminfo['MemFree'] - $meminfo['Buffers'] - $meminfo['Cached'] - $meminfo['SReclaimable'];
        $percentage = $used / $meminfo['MemTotal'] * 100;

        $state = 'ok';
        $memoryWarningInPercent = (is_numeric($memoryWarningInPercent) && $memoryWarningInPercent > 0) ? $memoryWarningInPercent : 80;
        if ($percentage > $memoryWarningInPercent) {
            $state = 'warning';
        }
        $memoryCriticalInPercent = (is_numeric($memoryCriticalInPercent) && $memoryCriticalInPercent > 0) ? $memoryCriticalInPercent : 90;
        if ($percentage > $memoryCriticalInPercent) {
            $state = 'critical';
        }

        $memory['memory'] = [
            'total'      => $meminfo['MemTotal'],
            'used'       => $used,
            'free'       => $meminfo['MemFree'],
            'buffers'    => $meminfo['Buffers'],
            'cached'     => $cached,
            'percentage' => round($percentage),
            'state'      => $state
        ];


        $swapUsed = $meminfo['SwapTotal'] - $meminfo['SwapFree'];
        $memory['swap'] = [
            'total'      => $meminfo['SwapTotal'],
            'used'       => $swapUsed,
            'free'       => $meminfo['SwapFree'],
            'percentage' => 0,
        ];
        if ($meminfo['Active'] > 0 && $swapUsed > 0) {
            $memory['swap']['percentage'] = round($swapUsed / $meminfo['SwapTotal'] * 100);
        }

        $memory['swap']['state'] = 'ok';
        $swapWarningInPercent = (is_numeric($swapWarningInPercent) && $swapWarningInPercent > 0) ? $swapWarningInPercent : 40;
        if ($memory['swap']['percentage'] > $swapWarningInPercent) {
            $memory['swap']['state'] = 'warning';
        }
        $swapCriticalInPercent = (is_numeric($swapCriticalInPercent) && $swapCriticalInPercent > 0) ? $swapCriticalInPercent : 50;
        if ($memory['swap']['percentage'] > $swapCriticalInPercent) {
            $memory['swap']['state'] = 'critical';
        }

        return $memory;
    }

}
