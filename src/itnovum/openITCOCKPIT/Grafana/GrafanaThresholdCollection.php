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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\Grafana;


use itnovum\openITCOCKPIT\Perfdata\PerformanceDataSetup;
use itnovum\openITCOCKPIT\Perfdata\ScaleType;

class GrafanaThresholdCollection {
    private const COLOR_CRITICAL = '#CC0101';
    private const COLOR_WARNING = '#ffbb33';
    private const COLOR_SUCCESS = '#00C851';


    /** @var PerformanceDataSetup|null */
    private $setup;

    public function __construct(GrafanaTargetCollection $targetCollection) {
        $this->setup = self::getSetup($targetCollection);
    }


    /**
     * I will return the Threshold definitions for Grafana that are configured in the setup of the instance.
     *
     * @return array
     */
    final public function getThresholds(): array {
        // It's a trap!
        if (!$this->setup instanceof PerformanceDataSetup) {
            return [];
        }
        switch ($this->setup->scale->type) {
            case ScaleType::W_O:
                return [
                    self::getArea(null, self::COLOR_WARNING),
                    self::getArea($this->setup->warn->low, self::COLOR_SUCCESS),
                ];
            case ScaleType::C_W_O:
                return [
                    self::getArea(null, self::COLOR_CRITICAL),
                    self::getArea($this->setup->crit->low, self::COLOR_WARNING),
                    self::getArea($this->setup->warn->low, self::COLOR_SUCCESS),
                ];
            case ScaleType::O_W:
                return [
                    self::getArea(null, self::COLOR_SUCCESS),
                    self::getArea($this->setup->warn->low, self::COLOR_WARNING),
                ];
            case ScaleType::O_W_C:
                return [
                    self::getArea(null, self::COLOR_SUCCESS),
                    self::getArea($this->setup->warn->low, self::COLOR_WARNING),
                    self::getArea($this->setup->crit->low, self::COLOR_CRITICAL),
                ];
            case ScaleType::C_W_O_W_C:
                return [
                    self::getArea(null, self::COLOR_CRITICAL),
                    self::getArea($this->setup->crit->low, self::COLOR_WARNING),
                    self::getArea($this->setup->warn->low, self::COLOR_SUCCESS),
                    self::getArea($this->setup->warn->high, self::COLOR_WARNING),
                    self::getArea($this->setup->crit->high, self::COLOR_CRITICAL),
                ];
            case ScaleType::O_W_C_W_O:
                return [
                    self::getArea(null, self::COLOR_SUCCESS),
                    self::getArea($this->setup->crit->low, self::COLOR_WARNING),
                    self::getArea($this->setup->warn->low, self::COLOR_CRITICAL),
                    self::getArea($this->setup->warn->high, self::COLOR_WARNING),
                    self::getArea($this->setup->crit->high, self::COLOR_SUCCESS),
                ];
            case ScaleType::O:
            default:
                return [];
        }
    }

    /**
     * I will check the targets of the given $targetCollection for matching setup objectss.
     *
     * If ALL Setups match, I will return it.
     * If ANY Setup varies, I will return NULL.
     *
     * This prevents a single-use setup of being used for multiple targets.
     * @param GrafanaTargetCollection $targetCollection
     * @return PerformanceDataSetup|null
     */
    private static function getSetup(GrafanaTargetCollection $targetCollection): ?PerformanceDataSetup {
        $theSetup = null;
        foreach ($targetCollection->getTargets() as $target) {
            $mySetup = $target->getSetup();
            if ($mySetup === null) {
                continue;
            }
            $myWarnString = $mySetup->getWarnString();
            // First setup counts.
            if ($theSetup === null) {
                $theSetup = $mySetup;
                continue;
            }
            // If any different setup is found, invalidate the setup and stop the loop.
            if ($theSetup->getWarnString() !== $myWarnString) {
                $theSetup = null;
                break;
            }
        }
        return $theSetup;
    }

    /**
     * I am here for legibility.
     *
     * @param float|null $from
     * @param string $color
     * @return array
     */
    private static function getArea(?float $from, string $color): array {
        return [
            "color" => $color,
            "value" => $from
        ];
    }
}
