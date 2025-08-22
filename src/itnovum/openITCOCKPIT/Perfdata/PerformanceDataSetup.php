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

namespace itnovum\openITCOCKPIT\Perfdata;

class PerformanceDataSetup {
    /** @var Metric */
    public $metric;
    /** @var Scale */
    public $scale;
    /** @var Threshold */
    public $warn;
    /** @var Threshold */
    public $crit;

    /**
     * I'll return the object in an array base representation.
     * @return array
     */
    public function toArray(): array {
        return [
            'metric' => $this->metric->toArray(),
            'scale'  => $this->scale->toArray(),
            'warn'   => $this->warn->toArray(),
            'crit'   => $this->crit->toArray()
        ];
    }

    public function getWarnString(): string {
        return "{$this->scale->type}<{$this->crit->low}<{$this->warn->low}<{$this->warn->high}<{$this->crit->high}";
    }
}
