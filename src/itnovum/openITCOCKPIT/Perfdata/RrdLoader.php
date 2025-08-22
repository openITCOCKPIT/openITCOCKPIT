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

/**
 * Class RrdLoader
 * This is the old Rrd Model which was ported into a class to be
 * backward compatible to Rrdtool even with CakePHP 4
 *
 * All unsed methods like PNG rendering were removed.
 * This got also a bit refactored but is still legacy code!
 *
 * @package itnovum\openITCOCKPIT\Perfdata
 */
class RrdLoader {

    const REDUCE_METHOD_STEPS = 1;
    const REDUCE_METHOD_AVERAGE = 2;

    /**
     * @param string $hostUuid
     * @param string $serviceUuid
     * @param int $starttime Unix time stamp
     * @param int $endtime Unix time stamp
     * @param string $type (min|max|avg)
     * @param int $stepsize (default=60)
     * @return array
     */
    public function getPerfDataFiles($hostUuid, $serviceUuid, $starttime, $endtime, $type = 'avg', $stepsize = 60) {
        if (empty($hostUuid) || empty($serviceUuid)) {
            return [];
        }

        if (!is_dir(sprintf('/opt/openitc/nagios/share/perfdata/%s', $hostUuid))) {
            return [];
        }

        $basePath = sprintf('/opt/openitc/nagios/share/perfdata/%s/', $hostUuid);
        $files = glob($basePath . '*.{rrd,xml}', GLOB_BRACE);
        if (sizeof($files) !== 2) {
            //No .rrd and .xml file found for given host and service uuod
            return [];
        }

        foreach ($files as $file) {
            if (substr($file, -4) === '.rrd') {
                $rrdFile = $file;
            } else {
                $xmlFile = $file;
            }
        }

        $xml_data = [
            'xml_data' => $this->getPerfDataStructure($xmlFile)
        ];

        $perfdata_from_rrd = $this->getPerfDataFromRrd($rrdFile, $starttime, $endtime, $type, $stepsize);

        return array_merge($xml_data, $perfdata_from_rrd);
    }

    /**
     * @param string $rrdFile
     * @return array|bool
     */
    private function getPerfDataStructure($rrdFile) {
        $rrdStructure = [];

        $xml = simplexml_load_file($rrdFile);
        if ($xml === false) {
            return [];
        }
        //Convert XML to array
        $xml = json_decode(json_encode($xml), true);

        $defaultXmlRrdStructure = ['ds', 'name', 'label', 'unit', 'act', 'warn', 'crit', 'min', 'max'];
        if (isset($xml['DATASOURCE'])) {
            foreach ($xml['DATASOURCE'] as $datasource) {
                array_push($rrdStructure, array_combine($defaultXmlRrdStructure, [
                    $datasource['DS'],
                    $datasource['NAME'],
                    $datasource['LABEL'],
                    empty($datasource['UNIT']) ? '' : $datasource['UNIT'],
                    empty($datasource['ACT']) ? null : $datasource['ACT'],
                    empty($datasource['WARN']) ? null : $datasource['WARN'],
                    empty($datasource['CRIT']) ? null : $datasource['CRIT'],
                    empty($datasource['MIN']) ? null : $datasource['MIN'],
                    empty($datasource['MAX']) ? null : $datasource['MAX']
                ]));
            }
        }

        return $rrdStructure;
    }


    /**
     * @param string $rrdFile
     * @param int $starttime Unix time stamp
     * @param int $endtime Unix time stamp
     * @param string $type (min|max|avg)
     * @param int $stepsize (default=60)
     * @return array
     */
    private function getPerfDataFromRrd($rrdFile, $starttime, $endtime, $type = 'avg', $stepsize = 60) {
        switch ($type) {
            case 'MIN':
            case 'min':
                $type = 'MIN';
                break;

            case 'MAX':
            case 'max':
                $type = 'MAX';
                break;

            default:
                $type = 'AVERAGE';
                break;
        }

        $result = $this->fixResolution($starttime, $endtime, $stepsize);
        // I dont trust list($start, $end) = $this->fixResolution($starttime, $endtime, $stepsize);
        $start = $result[0];
        $end = $result[1];

        $options = [
            $type,
            '--resolution',
            $stepsize,
            '--start',
            $start,
            '--end',
            $end,
        ];


        $perfdata = rrd_fetch($rrdFile, $options);


        if (empty($perfdata) || empty($perfdata['data'])) {
            return [];
        }


        $dataSources = array_keys($perfdata['data']); //save memory
        foreach ($dataSources as $dsIndex) {
            foreach (array_keys($perfdata['data'][$dsIndex]) as $timestamp) {
                if (is_nan($perfdata['data'][$dsIndex][$timestamp])) {
                    $perfdata['data'][$dsIndex][$timestamp] = null;
                }
            }
        }

        return $perfdata;
    }


    /**
     * The start and end time have to be a multiple of the given resolution.
     * This function fixes the given start and end time based on the given resolution.
     *
     * @param int $start Unix timestamp
     * @param int $end Unix timestamp
     * @param int $resolution RRD step size
     * @return array
     */
    private function fixResolution($start, $end, $resolution) {
        $fixed_start = $this->fixValueForResolution($start, $resolution);
        $fixed_end = $this->fixValueForResolution($end, $resolution);

        return [$fixed_start, $fixed_end];
    }

    /**
     * @param int $value
     * @param int $resolution
     * @return int
     */
    private function fixValueForResolution($value, $resolution) {
        return ((int)($value / $resolution)) * $resolution;
    }


    /**
     * @param array $data
     * @param int $limit
     * @param int $technique
     * @return array
     */
    public function reduceData($data, $limit = 500, $technique = self::REDUCE_METHOD_AVERAGE) {
        switch ($technique) {
            case self::REDUCE_METHOD_STEPS:
                return $this->reduceDataBySteps($data, $limit);
            case self::REDUCE_METHOD_AVERAGE:
                return $this->reduceDataByAverage($data, $limit);
            default:
                return $data;
        }
    }

    /**
     * @param array $data
     * @param int $limit
     * @return array
     */
    private function reduceDataByAverage($data, $limit = 500) {
        $data_count = count($data);
        if ($data_count <= $limit) {
            return $data;
        }

        $percent = $data_count / $limit;
        $step_size = ceil($percent);

        $i = 1;
        $result = [];
        $average_value_of_last_step = 0;
        $average_time_of_last_step = 0;
        foreach ($data as $timestamp => $value) {
            $average_value_of_last_step += $value;
            $average_time_of_last_step += $timestamp;
            if ($i % $step_size == 0) {
                $result[(int)($average_time_of_last_step / $step_size)] = $average_value_of_last_step / $step_size;

                $average_value_of_last_step = 0;
                $average_time_of_last_step = 0;
            }
            $i++;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param int $limit
     * @return array
     */
    private function reduceDataBySteps($data, $limit = 500) {
        $data_count = count($data);
        if ($data_count <= $limit) {
            return $data;
        }

        $percent = $data_count / $limit;
        $steps = ceil($percent);

        $i = 0;
        $result = [];
        foreach ($data as $key => $value) {
            if ($i % $steps == 0) {
                $result[$key] = $value;
            }
            $i++;
        }

        return $result;
    }

}
