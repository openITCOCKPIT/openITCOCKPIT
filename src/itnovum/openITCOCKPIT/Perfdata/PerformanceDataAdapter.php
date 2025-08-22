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

use itnovum\openITCOCKPIT\Perfdata\PerformanceDataSetup;
use itnovum\openITCOCKPIT\Core\Views\Service;

abstract class PerformanceDataAdapter {


    /**
     * I will use the given $service and $performanceData to create a PerformanceDataSetup object.
     *
     * ATTENTION: I actually REQUIRE the $performanceData to be passed, since this identifies the exact metric already.
     * Otherwise this method would blow up with additional parameters for the metric and the logic to fech data.
     *
     * @param Service $service
     * @param array $performanceData
     * @return PerformanceDataSetup
     */
    abstract public function getPerformanceData(Service $service, array $performanceData): PerformanceDataSetup;

}
