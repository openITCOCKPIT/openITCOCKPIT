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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\Grafana;


use itnovum\openITCOCKPIT\Perfdata\PerformanceDataSetup;

interface GrafanaTargetInterface {


    /**
     * GrafanaTargetInterface constructor.
     * @param $target
     * @param GrafanaTargetUnit $grafanaTargetUnit
     * @param GrafanaThresholds $grafanaThresholds
     * @param null $alias
     * @param null|string $color
     */
    public function __construct($target, GrafanaTargetUnit $grafanaTargetUnit, GrafanaThresholds $grafanaThresholds, $alias = null, $color = null);

    /**
     * @return string
     */
    public function getTarget();

    /**
     * @param $alias
     */
    public function setAlias($alias);

    /**
     * @return string
     */
    public function getAlias();

    /**
     * @return string
     */
    public function getUnit();

    /**
     * @return GrafanaThresholds
     */
    public function getThresholds();

    /**
     * @return null|string
     */
    public function getColor();

    /**
     * @return PerformanceDataSetup|null
     */
    public function getSetup(): ?PerformanceDataSetup;

    /**
     * @param string $refId
     * @return array
     */
    public function toJson(string $refId);
}
