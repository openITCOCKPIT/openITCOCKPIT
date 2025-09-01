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

namespace itnovum\openITCOCKPIT\Perfdata;


use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\PerfdataBackend;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Graphite\GraphiteConfig;
use itnovum\openITCOCKPIT\Graphite\GraphiteLoader;
use itnovum\openITCOCKPIT\Graphite\GraphiteMetric;
use Statusengine\PerfdataParser;

/**
 * Class PerfdataLoader
 * @package itnovum\openITCOCKPIT\Perfdata
 * @property \Servicestatus $Servicestatus
 */
class PerfdataLoader {

    const REDUCE_METHOD_STEPS = 1;
    const REDUCE_METHOD_AVERAGE = 2;
    const MAX_RESPONSE_GRAPH_POINTS = 1000;

    /**
     * @var DbBackend
     */
    private $DbBackend;

    /**
     * @var PerfdataBackend
     */
    private $PerfdataBackend;

    /**
     * PerfdataLoader constructor.
     * @param DbBackend $DbBackend
     * @param PerfdataBackend $PerfdataBackend
     * @param null|string $gauge
     */
    public function __construct(DbBackend $DbBackend, PerfdataBackend $PerfdataBackend) {
        $this->DbBackend = $DbBackend;
        $this->PerfdataBackend = $PerfdataBackend;
    }


    /**
     * @param string $hostUuid
     * @param string $serviceUuid
     * @param int $start
     * @param int $end
     * @param bool $jsTimestamp
     * @param string $type
     * @param null $gauge
     * @param bool $scale
     * @param string|null $forcedUnit
     * @param bool $debug
     * @param bool $isoTimestamp
     * @return array
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPerfdataByUuid($hostUuid, $serviceUuid, $start, $end, $jsTimestamp = false, $type = 'avg', $gauge = null, $scale = true, $forcedUnit = null, $debug = false, bool $isoTimestamp = false) {
        if ($gauge === '') {
            $gauge = null;
        }

        $performance_data = [];
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();

        if ($this->PerfdataBackend->isWhisper()) {
            $ServicestatusFields = new ServicestatusFields($this->DbBackend);
            $ServicestatusFields->perfdata();
            $servicestatus = $ServicestatusTable->byUuid($serviceUuid, $ServicestatusFields);

            if (!empty($servicestatus)) {

                $GraphiteConfig = new GraphiteConfig();
                $GraphiteLoader = new GraphiteLoader($GraphiteConfig, $debug);

                if (!empty($servicestatus['Servicestatus']['perfdata'])) {
                    //Use parse perfdata string from database
                    $PerfdataParser = new PerfdataParser($servicestatus['Servicestatus']['perfdata']);
                    $perfdataMetadata = $PerfdataParser->parse();
                } else {
                    //Query graphite backend for available metrics - for example if service state is unknown
                    $metrics = $GraphiteLoader->findMetricsByUuid($hostUuid, $serviceUuid);

                    $perfdataMetadata = [];
                    foreach ($metrics as $metric) {
                        $perfdataMetadata[$metric] = [
                            'current'  => null,
                            'unit'     => null,
                            'warning'  => null,
                            'critical' => null,
                            'min'      => null,
                            'max'      => null
                        ];
                    }
                }

                $GraphiteLoader->setUseJsTimestamp($jsTimestamp);
                $GraphiteLoader->setUseIsoTimestamp($isoTimestamp);
                $GraphiteLoader->setAbsoluteDate($start, $end);

                foreach ($perfdataMetadata as $metricName => $metric) {
                    if ($gauge !== null) {
                        if ($gauge !== $metricName) {
                            continue;
                        }
                    }

                    $GraphiteMetric = new GraphiteMetric(
                        $hostUuid,
                        $serviceUuid,
                        $metricName
                    );

                    $datasource = [
                        'ds'     => $metricName,
                        'name'   => $metricName,
                        'label'  => $metricName,
                        'metric' => $metricName, // ITC-2824 make classical metrics look the same
                        'unit'   => $metric['unit'],
                        'act'    => $metric['current'],
                        'warn'   => $metric['warning'],
                        'crit'   => $metric['critical'],
                        'min'    => $metric['min'],
                        'max'    => $metric['max'],
                    ];

                    if ($metric['unit'] === 'c') {
                        //Counter datatype
                        $performance_data[] = [
                            'datasource' => $datasource,
                            'data'       => $GraphiteLoader->getSeriesAsCounter($GraphiteMetric)
                        ];
                    } else {
                        //Default Gauge
                        switch ($type) {
                            case 'min':
                                $performance_data[] = [
                                    'datasource' => $datasource,
                                    'data'       => $GraphiteLoader->getSeriesMin($GraphiteMetric)
                                ];
                                break;

                            case 'max':
                                $performance_data[] = [
                                    'datasource' => $datasource,
                                    'data'       => $GraphiteLoader->getSeriesMax($GraphiteMetric)
                                ];
                                break;

                            default:
                                $performance_data[] = [
                                    'datasource' => $datasource,
                                    'data'       => $GraphiteLoader->getSeriesAvg($GraphiteMetric)
                                ];
                                break;
                        }
                    }
                }
            }
        }

        if ($this->PerfdataBackend->isRrdtool()) {
            if (file_exists(sprintf('/opt/openitc/nagios/share/perfdata/%s/%s.rrd', $hostUuid, $serviceUuid))) {

                $RrdLoader = new RrdLoader();
                $rrd_data = $RrdLoader->getPerfDataFiles($hostUuid, $serviceUuid, $start, $end, $type);

                $limit = (int)self::MAX_RESPONSE_GRAPH_POINTS / sizeof($rrd_data['data']);
                foreach ($rrd_data['xml_data'] as $dataSource) {
                    if ($gauge !== null) {
                        if ($gauge !== $dataSource['name']) {
                            continue;
                        }
                    }

                    $tmpData = $RrdLoader->reduceData($rrd_data['data'][$dataSource['ds']], $limit, self::REDUCE_METHOD_AVERAGE);
                    $data = [];

                    foreach ($tmpData as $timestamp => $value) {
                        if ($value !== null) {
                            if ($debug === true) {
                                $data[date('d.m.Y H:i:s', $timestamp)] = $value;
                            } else {
                                if ($jsTimestamp) {
                                    $data[($timestamp * 1000)] = $value;
                                } else if ($isoTimestamp) {
                                    $data[date('c', $timestamp)] = $value;
                                } else {
                                    $data[$timestamp] = $value;
                                }
                            }
                        }
                    }

                    $performance_data[] = [
                        'datasource' => $dataSource,
                        'data'       => $data
                    ];
                }
            }
        }

        if ($scale === true) {
            foreach ($performance_data as $index => $gauge) {
                $UnitScaler = new UnitScaler($gauge);

                if ($forcedUnit === null) {
                    $performance_data[$index] = $UnitScaler->scale();
                } else {
                    $performance_data[$index] = $UnitScaler->scaleToUnit($forcedUnit);
                }
            }
        }

        return $performance_data;
    }

}
