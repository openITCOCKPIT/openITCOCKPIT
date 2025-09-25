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

namespace itnovum\openITCOCKPIT\Core\Views;


use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\PerfdataBackend;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Graphite\GraphiteConfig;
use itnovum\openITCOCKPIT\Graphite\GraphiteLoader;
use itnovum\openITCOCKPIT\Perfdata\PerfdataLoader;

class PerfdataChecker {

    /**
     * @var string
     */
    private $hostUuid;

    /**
     * @var string
     */
    private $serviceUuid;

    /**
     * @var PerfdataBackend
     */
    private $PerfdataBackend;

    /**
     * @var Servicestatus
     */
    private $Servicestatus;

    /**
     * @var DbBackend
     */
    private $DbBackend;

    /**
     * @var int|null
     */
    private $serviceType;

    /**
     * PerfdataChecker constructor.
     * @param Host $Host
     * @param Service $Service
     * @param PerfdataBackend $PerfdataBackend
     * @param Servicestatus $Servicestatus
     * @param DbBackend $DbBackend
     * @param int|null $serviceType
     */
    public function __construct(Host $Host, Service $Service, PerfdataBackend $PerfdataBackend, Servicestatus $Servicestatus, DbBackend $DbBackend, ?int $serviceType = null) {
        $this->hostUuid = $Host->getUuid();
        $this->serviceUuid = $Service->getUuid();
        $this->PerfdataBackend = $PerfdataBackend;
        $this->Servicestatus = $Servicestatus;
        $this->DbBackend = $DbBackend;
        $this->serviceType = $serviceType;
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function hasPerfdata() {
        if($this->serviceType !== null){
            if($this->serviceType === PROMETHEUS_SERVICE){
                //Prometheus Services always have Graphs - that's how Prometheus work
                return true;
            }
        }

        if ($this->PerfdataBackend->isRrdtool()) {
            return file_exists(sprintf('/opt/openitc/nagios/share/perfdata/%s/%s.rrd', $this->hostUuid, $this->serviceUuid));
        }

        if($this->PerfdataBackend->isWhisper()) {
            if (!empty($this->Servicestatus->getPerfdata())) {
                return true;
            }

            //Query Graphite if metrics exists
            $GraphiteConfig = new GraphiteConfig();
            $GraphiteLoader = new GraphiteLoader($GraphiteConfig);
            return !empty($GraphiteLoader->findMetricsByUuid($this->hostUuid, $this->serviceUuid));

        }

        return !empty($this->Servicestatus->getPerfdata());
    }

}
