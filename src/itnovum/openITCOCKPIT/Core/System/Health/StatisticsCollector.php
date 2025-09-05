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


use App\Lib\PluginManager;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;

/**
 * Class StatisticsCollector
 * @package itnovum\openITCOCKPIT\Core\System\Health
 */
class StatisticsCollector {

    /**
     * @var HostsTable
     */
    private $HostsTable;

    /**
     * @var ServicesTable
     */
    private $ServicesTable;

    public function __construct(HostsTable $HostsTable, ServicesTable $ServicesTable) {
        $this->HostsTable = $HostsTable;
        $this->ServicesTable = $ServicesTable;
    }

    public function getData() {
        $openITCOCKPITVersion = OPENITCOCKPIT_VERSION;

        $numberOfHosts = $this->HostsTable->getHostsCountForStats();
        $numberOfServices = $this->ServicesTable->getServicesCountForStats();


        $CpuLoad = new CpuLoad();
        $Memory = new MemoryUsage();
        $MonitoringEngine = new MonitoringEngine();
        $LsbRelease = new LsbRelease();
        $SystemId = new SystemId();

        $memory = $Memory->getMemoryUsage();

        $modulePlugins = PluginManager::getAvailablePlugins();

        $MysqlHealth = new MySQLHealth($this->HostsTable);

        return [
            'system_id'         => $SystemId->getSystemId(),
            'cpu'               => [
                'load'            => $CpuLoad->getLoad(),
                'number_of_cores' => $CpuLoad->getNumberOfCores(),
                'model_name'      => $CpuLoad->getModel(),
                'architecture'    => $CpuLoad->getArchitecture()
            ],
            'memory'            => [
                'memory' => $memory['memory'],
                'swap'   => $memory['swap'],
                'unit'   => 'mb'

            ],
            'monitoring_engine' => [
                'version_string' => $MonitoringEngine->getMonitoringEngine()
            ],
            'php'               => [
                'version_string' => phpversion()
            ],
            'oitc'              => [
                'version'            => $openITCOCKPITVersion,
                'number_of_hosts'    => $numberOfHosts,
                'number_of_services' => $numberOfServices,
                'plugins'            => array_values($modulePlugins),
            ],
            'operating_system'  => [
                'vendor'   => $LsbRelease->getVendor(),
                'version'  => $LsbRelease->getVersion(),
                'codename' => $LsbRelease->getCodename()
            ],
            'mysql'             => $MysqlHealth->getAllMetrics(),
            'containerized'     => (IS_CONTAINER) ? true : false
        ];
    }

}
