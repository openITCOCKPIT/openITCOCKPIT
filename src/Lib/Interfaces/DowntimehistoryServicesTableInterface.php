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

namespace App\Lib\Interfaces;


use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\Views\Downtime;

interface DowntimehistoryServicesTableInterface {

    /**
     * @param DowntimeServiceConditions $DowntimeServiceConditions
     * @param null $PaginateOMat
     * @return array
     */
    public function getDowntimes(DowntimeServiceConditions $DowntimeServiceConditions, $PaginateOMat = null);

    /**
     * @param int $hostId
     * @param Downtime $Downtime
     * @return array
     */
    public function getServiceDowntimesByHostAndDowntime($hostId, Downtime $Downtime);

    /**
     * @param DowntimeServiceConditions $DowntimeServiceConditions
     * @param bool $enableHydration
     * @param bool $disableResultsCasting
     * @return array
     */
    public function getDowntimesForReporting(DowntimeServiceConditions $DowntimeServiceConditions, $enableHydration = true, $disableResultsCasting = false);

    /**
     * @param null $uuid
     * @param bool $isRunning
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function byServiceUuid($uuid = null, $isRunning = false);

    /**
     * @param int $internalDowntimeId
     * @return array
     */
    public function getHostAndServiceUuidWithDowntimeByInternalDowntimeId($internalDowntimeId);

    /**
     * @param array $uuids
     * @param bool $isRunning
     * @return array
     */
    public function byUuidsNoJoins($uuids, $isRunning = false);

    /**
     * @param $uuids
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    public function getPlannedDowntimes($uuids, int $startTimestamp, int $endTimestamp);

}
