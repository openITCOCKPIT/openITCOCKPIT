<?php declare(strict_types=1);
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

namespace App\itnovum\openITCOCKPIT\Perfdata;

use \InvalidArgumentException;
use itnovum\openITCOCKPIT\Perfdata\Metric;
use itnovum\openITCOCKPIT\Perfdata\PerformanceDataSetup;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Perfdata\Scale;
use itnovum\openITCOCKPIT\Perfdata\ScaleType;
use itnovum\openITCOCKPIT\Perfdata\Threshold;

final class NagiosAdapter extends PerformanceDataAdapter {


    /**
     * @inheritDoc
     */
    function getPerformanceData(Service $service, array $performanceData): PerformanceDataSetup {
        $warn = $performanceData['warn'] ?? $performanceData['warning'] ?? '';
        $crit = $performanceData['crit'] ?? $performanceData['critical'] ?? '';


        // $warn and $crit could be an int or float in case we do not have a range threshold.
        // to make substr and explode work on php8.2 we have to cast the values to be strings
        $warn = (string)$warn;
        $crit = (string)$crit;

        // Check if the inverted threshold is required.
        $inverted = false;
        if (substr($warn, 0, 1) === '@' && substr($crit, 0, 1) === '@') {
            $inverted = true;
        }
        // Split nagios Thresholds
        $warnArr = explode(':', $warn);
        $critArr = explode(':', $crit);

        // Make better names.
        $warnLo = isset($warnArr[0]) && strlen($warnArr[0]) > 0 ? (float)str_replace('@', '', $warnArr[0]) : null;
        $warnHi = isset($warnArr[1]) && strlen($warnArr[1]) > 0 ? (float)str_replace('@', '', $warnArr[1]) : null;
        $critLo = isset($critArr[0]) && strlen($critArr[0]) > 0 ? (float)str_replace('@', '', $critArr[0]) : null;
        $critHi = isset($critArr[1]) && strlen($critArr[1]) > 0 ? (float)str_replace('@', '', $critArr[1]) : null;

        $current = (float)($performanceData['act'] ?? $performanceData['current'] ?? null);
        $unit = (string)($performanceData['unit'] ??'');
        $name = (string)($performanceData['name'] ?? $performanceData['metric'] ?? null);
        $scaleArray = explode(':', (string)($performanceData['min'] ?? ''));
        $scaleMin = $performanceData['min'] ?? null;
        $scaleMax = $performanceData['max'] ?? null;

        if ($scaleMin === null || $scaleMax === null) {
            // Maybe the scale range came from the performance data min and needs splitting from ":"...
            if (is_numeric($scaleArray[0] ?? false) && is_numeric($scaleArray[1] ?? false)) {
                $scaleMin = (float)$scaleArray[0];
                $scaleMax = (float)$scaleArray[1];
            } // Otherwise, we let the scale range be derived from min and max thresholds.
            else {
                $proposeMin = ScaleType::findMin($scaleMin, $critHi, $critLo, $warnHi, $warnLo);
                $proposeMax = ScaleType::findMax($scaleMax, $critHi, $critLo, $warnHi, $warnLo);

                // Trap for the case where the min and max are NULL or are invalid.
                if ($proposeMax !== null && $proposeMin !== null && $proposeMax > $proposeMin) {
                    $scaleMin = $proposeMin;
                    $scaleMax = $proposeMax;
                } else {
                    $scaleMin = null;
                    $scaleMax = null;
                }
                // Always use 0-100 for % units
                if ($unit === '%') {
                    $scaleMin = 0;
                    $scaleMax = 100;
                }
            }
        } else {
            $scaleMin = (float)$scaleMin;
            $scaleMax = (float)$scaleMax;
        }

        // Edge case: The scale range is too small.
        if ($scaleMax - $scaleMin < 10) {
            $scaleMin = null;
            $scaleMax = null;
        }

        // Create Setup
        $setup = new PerformanceDataSetup();
        $setup->metric = new Metric($current, $unit, $name);
        $setup->warn = new Threshold($warnLo, $warnHi);
        $setup->crit = new Threshold($critLo, $critHi);
        $setup->scale = new Scale($scaleMin, $scaleMax, null, $inverted);

        // Fetch the ScaleType. If not working, reset it to the default one.
        try {
            $setup->scale->type = ScaleType::get($inverted, $setup->warn, $setup->crit);
        } catch (InvalidArgumentException $e) {
            // Ignored
        }

        return $setup;
    }

}
