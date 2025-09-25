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

use App\itnovum\openITCOCKPIT\Monitoring\PrometheusExporter\PrometheusExporter;
use Cake\Http\Exception\MethodNotAllowedException;

/**
 * Metrics Controller
 *
 */
class MetricsController extends AppController {

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {
        // Scraped by Prometheus
        if (!$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        $this->disableAutoRender();

        // Collect metrics for external Prometheus
        $PrometheusExporter = new PrometheusExporter();

        // Return with plain text response
        $this->response = $this->response->withStringBody($PrometheusExporter->getMetrics());
        $this->response = $this->response->withType('text');
    }

    public function info() {
        // Used by the frontend
        // Will only display help information about this controller


        $PrometheusExporter = new PrometheusExporter();
        $metrics = $PrometheusExporter->getMetrics();

        $this->set('metrics', $metrics);
        $this->set('systemname', $this->getSystemname());

        if ($this->isApiRequest()) {
            $this->set('serverAddress', $_SERVER['SERVER_ADDR']);
            $this->viewBuilder()->setOption('serialize', ['metrics', 'systemname', 'serverAddress']);
        }
    }

}
