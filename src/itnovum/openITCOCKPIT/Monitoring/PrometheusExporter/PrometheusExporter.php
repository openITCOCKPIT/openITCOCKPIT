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

namespace App\itnovum\openITCOCKPIT\Monitoring\PrometheusExporter;

use Cake\Core\Configure;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\System\Health\CpuLoad;
use itnovum\openITCOCKPIT\Core\System\Health\MemoryUsage;
use itnovum\openITCOCKPIT\Core\System\Health\MonitoringEngine;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

class PrometheusExporter {

    public function getMetrics() {
        $start = microtime(true);

        // Collect Naemon metrics
        $MonitoringEngine = new MonitoringEngine();
        $stats = $MonitoringEngine->runNagiostats();

        Configure::load('gearman');

        $CpuLoad = new CpuLoad();

        $MemoryUsage = new MemoryUsage();
        $memory = $MemoryUsage->getMemoryUsage();

        //Collect process information
        $GearmanClient = new Gearman();
        $gearmanReachable = $GearmanClient->ping();

        //Collect gearman queue information
        $gearmanStatus = [];
        $output = null;
        if ($gearmanReachable) {
            exec('gearadmin --status -h ' . escapeshellarg(env('OITC_GEARMAN_ADDRESS', 'localhost')), $output);
            //Parse output
            $trash = array_pop($output);
            foreach ($output as $line) {
                $queueDetails = explode("\t", $line);
                $gearmanStatus[$queueDetails[0]] = [
                    'jobs'    => $queueDetails[1],
                    'running' => $queueDetails[2],
                    'worker'  => $queueDetails[3],
                ];
            }
        }

        $end = microtime(true);
        $collectorDuration = $end - $start;

        $registry = new CollectorRegistry(new InMemory());

        // Add some general info about openITCOCKPIT itself
        $gauge = $registry->registerGauge(
            'openITCOCKPIT',
            'info',
            'Information about the openITCOCKPIT environment',
            [
                'version',
                'containerized'
            ]
        );
        $gauge->set(1, [OPENITCOCKPIT_VERSION, intval(IS_CONTAINER)]);

        $gauge = $registry->registerGauge(
            'openITCOCKPIT',
            'collector_duration_seconds',
            'Time in seconds the collector needed to fetch all metrics'
        );
        $gauge->set($collectorDuration);

        // Add node metrics
        $gauge = $registry->registerGauge(
            'node',
            'load1',
            '1m load average'
        );
        $gauge->set($CpuLoad->getLoad1());

        $gauge = $registry->registerGauge(
            'node',
            'load5',
            '5m load average'
        );
        $gauge->set($CpuLoad->getLoad1());

        $gauge = $registry->registerGauge(
            'node',
            'load15',
            '15m load average'
        );
        $gauge->set($CpuLoad->getLoad1());

        $gauge = $registry->registerGauge(
            'node',
            'memory_total_bytes',
            'Total amount of memory in bytes'
        );
        $gauge->set($memory['memory']['total'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'memory_used_bytes',
            'Total amount of used memory in bytes'
        );
        $gauge->set($memory['memory']['used'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'memory_free_bytes',
            'Total amount of free memory in bytes'
        );
        $gauge->set($memory['memory']['free'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'memory_buffers_bytes',
            'Total amount of buffers memory in bytes'
        );
        $gauge->set($memory['memory']['buffers'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'memory_cached_bytes',
            'Total amount of cached memory in bytes'
        );
        $gauge->set($memory['memory']['cached'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'memory_used_percentage',
            'Memory used as percentage'
        );
        $gauge->set($memory['memory']['percentage']);

        $gauge = $registry->registerGauge(
            'node',
            'swap_total_bytes',
            'Total amount of swap space in bytes'
        );
        $gauge->set($memory['swap']['total'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'swap_used_bytes',
            'Amount of used swap space in bytes'
        );
        $gauge->set($memory['swap']['used'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'swap_free_bytes',
            'Amount of free swap space in bytes'
        );
        $gauge->set($memory['swap']['free'] * 1024 * 1024);

        $gauge = $registry->registerGauge(
            'node',
            'swap_used_percentage',
            'Swap used as percentage'
        );
        $gauge->set($memory['swap']['percentage']);

        // Add naemon stats metrics

        //dd($stats);
        $gauge = $registry->registerGauge(
            'naemon',
            'info',
            'Information about the naemon environment',
            [
                'version',
            ]
        );
        $gauge->set(1, [$stats['NAGIOSVERSION'] ?? '']);

        $gauge = $registry->registerGauge(
            'naemon',
            'pid',
            'PID of the running Naemon process'
        );
        $gauge->set($stats['NAGIOSPID'] ?? 0);


        $gauge = $registry->registerGauge(
            'naemon',
            'hosts_total',
            'Total number of hosts'
        );
        $gauge->set($stats['NUMHOSTS'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'services_total',
            'Total number of services'
        );
        $gauge->set($stats['NUMSERVICES'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'hosts_up',
            'Total number hosts UP'
        );
        $gauge->set($stats['NUMHSTUP'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'hosts_down',
            'Total number hosts DOWN'
        );
        $gauge->set($stats['NUMHSTDOWN'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'hosts_unreachable',
            'Total number hosts UNREACHABLE'
        );
        $gauge->set($stats['NUMHSTUNR'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'services_ok',
            'Total number services OK'
        );
        $gauge->set($stats['NUMSVCOK'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'services_warning',
            'Total number services WARNING'
        );
        $gauge->set($stats['NUMSVCWARN'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'services_critical',
            'Total number services CRITICAL'
        );
        $gauge->set($stats['NUMSVCCRIT'] ?? 0);

        $gauge = $registry->registerGauge(
            'naemon',
            'services_unknown',
            'Total number services UNKNOWN'
        );
        $gauge->set($stats['NUMSVCUNKN'] ?? 0);

        foreach (['MIN', 'MAX', 'AVG'] as $k) {
            $gauge = $registry->registerGauge(
                'naemon',
                'active_host_check_latency_' . strtolower($k) . '_seconds',
                'Active host check latency in seconds ' . $k
            );

            $v = $stats[$k . 'ACTHSTLAT'] ?? 0;
            $v = $v / 1000; //convert ms into seconds
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_service_check_latency_' . strtolower($k) . '_seconds',
                'Active service check latency in seconds ' . $k
            );

            $v = $stats[$k . 'ACTSVCLAT'] ?? 0;
            $v = $v / 1000; //convert ms into seconds
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_host_execution_time_' . strtolower($k) . '_seconds',
                'Active host check execution time in seconds ' . $k
            );

            $v = $stats[$k . 'ACTHSTEXT'] ?? 0;
            $v = $v / 1000; //convert ms into seconds
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_service_execution_time_' . strtolower($k) . '_seconds',
                'Active service check execution time in seconds ' . $k
            );

            $v = $stats[$k . 'ACTSVCEXT'] ?? 0;
            $v = $v / 1000; //convert ms into seconds
            $gauge->set($v);
        }

        foreach (['1', '5', '15'] as $k) {
            $gauge = $registry->registerGauge(
                'naemon',
                'active_host_check_last_' . strtolower($k) . '_minutes_total',
                'Number of total active host checks in last ' . $k . ' minutes'
            );

            $v = $stats['NUMHSTACTCHK' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_on_demand_host_check_last_' . strtolower($k) . '_minutes_total',
                'Number of on-demand active host checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMOACTHSTCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_cached_host_check_last_' . strtolower($k) . '_minutes_total',
                'Number of cached host checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMCACHEDHSTCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_parallel_host_check_last_' . strtolower($k) . '_minutes_total',
                'Number of parallel host checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMPARHSTCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_serial_host_check_last_' . strtolower($k) . '_minutes_total',
                'Number of serial host checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMSERHSTCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'passive_host_check_occuring_last_' . strtolower($k) . '_minutes_total',
                'Number of passive host checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMPSVHSTCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'passive_host_check_last_' . strtolower($k) . '_minutes_total',
                'Number of hosts passively checked in last ' . $k . ' minutes'
            );

            $v = $stats['NUMHSTPSVCHK' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_service_check_last_' . strtolower($k) . '_minutes_total',
                'Number of total active service checks in last ' . $k . ' minutes'
            );

            $v = $stats['NUMSVCACTCHK' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_on_demand_service_check_last_' . strtolower($k) . '_minutes_total',
                'Number of on-demand active service checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMOACTSVCCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_cached_service_check_last_' . strtolower($k) . '_minutes_total',
                'Number of cached service checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMCACHEDSVCCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'active_scheduled_service_check_last_' . strtolower($k) . '_minutes_total',
                'Number of scheduled active service checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMSACTSVCCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'passive_service_check_occurring_last_' . strtolower($k) . '_minutes_total',
                'Number of passive service checks occurring in last ' . $k . ' minutes'
            );

            $v = $stats['NUMPSVSVCCHECKS' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'passive_service_check_last_' . strtolower($k) . '_minutes_total',
                'Number of services passively checked in last ' . $k . ' minutes'
            );

            $v = $stats['NUMSVCPSVCHK' . $k . 'M'] ?? 0;
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'naemon',
                'external_commands_processed_last_' . strtolower($k) . '_minutes_total',
                'Number of external commands processed in last ' . $k . ' minutes'
            );

            $v = $stats['NUMEXTCMDS' . $k . 'M'] ?? 0;
            $gauge->set($v);
        }

        // Add Gearman information
        foreach ($gearmanStatus as $queueName => $queueStats) {
            // Prometheus does not like any special character
            $queueName = preg_replace('/[^a-zA-Z0-9_]/', '_', $queueName);

            $gauge = $registry->registerGauge(
                'gearman',
                $queueName . '_jobs',
                'Total number of jobs that are queued'
            );

            $v = $queueStats['jobs'];
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'gearman',
                $queueName . '_jobs_running',
                'Total number of currently running jobs'
            );

            $v = $queueStats['running'];
            $gauge->set($v);

            $gauge = $registry->registerGauge(
                'gearman',
                $queueName . '_available_worker',
                'Total number of available worker'
            );

            $v = $queueStats['worker'];
            $gauge->set($v);
        }

        $renderer = new RenderTextFormat();
        return $renderer->render($registry->getMetricFamilySamples());

    }

}
