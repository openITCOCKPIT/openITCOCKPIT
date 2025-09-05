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

declare(strict_types=1);

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Perfdata\NagiosAdapter;
use App\Model\Table\ServicesTable;
use Cake\Core\Plugin;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Perfdata\PerfdataLoader;
use PrometheusModule\Lib\PrometheusAdapter;


/**
 * Class GraphgeneratorsController
 * @package App\Controller
 */
class GraphgeneratorsController extends AppController {

    /**
     * New method for AngularJS
     * @throws GuzzleException
     */
    public function getPerfdataByUuid() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $hostUuid = $this->request->getQuery('host_uuid', '');
        $serviceUuid = $this->request->getQuery('service_uuid', '');
        $hours = $this->request->getQuery('hours', '');
        $start = (int)$this->request->getQuery('start', '');
        $end = (int)$this->request->getQuery('end', '');
        $jsTimestamp = (bool)$this->request->getQuery('jsTimestamp', 0);
        $isoTimestamp = (bool)$this->request->getQuery('isoTimestamp', 0);
        $gauge = $this->request->getQuery('gauge', '');
        $scale = $this->request->getQuery('scale', 'true') === 'true';
        $forcedUnit = $this->request->getQuery('forcedUnit', null);
        $aggregation = $this->request->getQuery('aggregation', 'avg');
        $debug = $this->request->getQuery('debug', 'false') === 'true';

        if (is_numeric($hours)) {
            $hours = (int)$hours;
            $start = time() - ($hours * 3600);
            $end = time();
        }

        if ($start === null || $end === null) {
            $start = time() - (3 * 3600);
            $end = time();
        }

        try {
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            $service = $ServicesTable->getServiceByUuid($serviceUuid);
            $Service = new Service($service);

            if (Plugin::isLoaded('PrometheusModule') && $Service->getServiceType() === PROMETHEUS_SERVICE) {
                $PerfdataLoader = new \PrometheusModule\Lib\PrometheusPerfdataLoader();
                $adapter = new PrometheusAdapter();
                // @todo: Both dataLoaders may share one interface for future (?)
                $performance_data = $PerfdataLoader->getPerfdataByUuid($Service, $start, $end, $jsTimestamp, $scale, $forcedUnit, $debug, $gauge, $isoTimestamp);
            } else {
                $PerfdataLoader = new PerfdataLoader($this->DbBackend, $this->PerfdataBackend);
                $adapter = new NagiosAdapter();
                $performance_data = $PerfdataLoader->getPerfdataByUuid($hostUuid, $serviceUuid, $start, $end, $jsTimestamp, $aggregation, $gauge, $scale, $forcedUnit, $debug, $isoTimestamp);
            }
            // Generate Setup
            foreach ($performance_data as $index => $object) {
                if (!isset($object['datasource'])) {
                    continue;
                }
                $performance_data[$index]['datasource']['setup'] = $adapter->getPerformanceData($Service, $object['datasource'])->toArray();
            }
            $this->set('performance_data', $performance_data);
            $this->viewBuilder()->setOption('serialize', ['performance_data']);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $performance_data[] = [
                'datasource' => [],
                'data'       => []
            ];
        }


        $this->set('performance_data', $performance_data);
        $this->viewBuilder()->setOption('serialize', ['performance_data']);
    }
}
