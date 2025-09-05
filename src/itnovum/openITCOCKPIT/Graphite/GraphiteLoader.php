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

namespace itnovum\openITCOCKPIT\Graphite;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;

class GraphiteLoader {

    /**
     * @var GraphiteConfig
     */
    private $GraphiteConfig;

    /**
     * @var bool
     */
    private $hideNullValues = true;

    /**
     * @var string
     * Start time in seconds
     */
    private $from = 3600;

    /**
     * @var bool
     */
    private $useAbsoluteDate = false;

    /**
     * @var int
     * Start timestamp
     */
    private $start = 0;

    /**
     * @var int
     * End timestamp
     */
    private $end = 0;

    /**
     * @var bool
     */
    private $useJsTimestamp = false;

    /**
     * @var bool
     */
    private $useIsoTimestamp = false;

    /**
     * @var int
     */
    private $maxDataPoints = 20000;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * GraphiteLoader constructor.
     * @param GraphiteConfig $GraphiteConfig
     */
    public function __construct(GraphiteConfig $GraphiteConfig, $debug = false) {
        $this->GraphiteConfig = $GraphiteConfig;
        $this->debug = $debug;
    }

    /**
     * @return bool
     */
    public function isNullValueHidden() {
        return $this->hideNullValues;
    }

    /**
     * @param bool $hideNullValues
     */
    public function setHideNullValues($hideNullValues) {
        $this->hideNullValues = (bool)$hideNullValues;
    }

    /**
     * @param $useJsTimestamp
     * @return bool
     */
    public function setUseJsTimestamp($useJsTimestamp) {
        return $this->useJsTimestamp = (bool)$useJsTimestamp;
    }

    /**
     * @param $useIsoTimestamp
     * @return bool
     */
    public function setUseIsoTimestamp($useIsoTimestamp) {
        return $this->useIsoTimestamp = (bool)$useIsoTimestamp;
    }

    /**
     * @param int $from
     * Start value in seconds from now
     */
    public function setFrom($from) {
        $this->useAbsoluteDate = false;
        $this->from = (int)$from;
    }

    /**
     * @return bool
     */
    public function isUseJsTimestamp() {
        return $this->useJsTimestamp;
    }

    /**
     * @return bool
     */
    public function isUseIsoTimestamp() {
        return $this->useIsoTimestamp;
    }

    /**
     * @param int $start
     * @param int $end
     */
    public function setAbsoluteDate($start, $end) {
        $this->useAbsoluteDate = true;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return int
     */
    public function getMaxDataPoints() {
        return $this->maxDataPoints;
    }

    /**
     * @param int $maxDataPoints
     */
    public function setMaxDataPoints($maxDataPoints) {
        $this->maxDataPoints = $maxDataPoints;
    }


    /**
     * @param array $proxySettings
     * @param array $queryOptions
     * @return Client
     */
    private function getHttpClient($proxySettings, $queryOptions) {
        $options = [
            'verify' => $this->GraphiteConfig->isIgnoreSslCertificate()
        ];
        if ($this->GraphiteConfig->isUseProxy() && !(empty($proxySettings['ipaddress']) & empty($proxySettings['port']))) {
            $options['proxy'] = [
                'http'  => sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port']),
                'https' => sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port'])
            ];
        } else {
            $options['proxy'] = [
                'http'  => false,
                'https' => false
            ];
        }

        $options['query'] = $queryOptions;
        if (!isset($options['query']['tz'])) {
            // Query data from Graphite API in PHPs default timezone
            $options['query']['tz'] = date_default_timezone_get();
        }

        return new Client($options);
    }

    /**
     * @param GraphiteMetric $GraphiteMetric
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSeriesRaw(GraphiteMetric $GraphiteMetric) {
        $options = $this->getBaseRequestOptions();
        $options['target'] = sprintf(
            '%s.%s',
            $this->GraphiteConfig->getGraphitePrefix(),
            $GraphiteMetric->getMetricPath()
        );

        return $this->normalizeData($this->sendRequest($options));
    }

    /**
     * @param GraphiteMetric $GraphiteMetric
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSeriesAvg(GraphiteMetric $GraphiteMetric) {
        $options = $this->getBaseRequestOptions();
        $options['target'] = sprintf(
            'consolidateBy(%s.%s, "average")',
            $this->GraphiteConfig->getGraphitePrefix(),
            $GraphiteMetric->getMetricPath()
        );

        return $this->normalizeData($this->sendRequest($options));
    }

    /**
     * @param GraphiteMetric $GraphiteMetric
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSeriesMin(GraphiteMetric $GraphiteMetric) {
        $options = $this->getBaseRequestOptions();
        //$options['target'] = sprintf(
        //    'minSeries(%s.%s)',
        //    $this->GraphiteConfig->getGraphitePrefix(),
        //    $GraphiteMetric->getMetricPath()
        //);

        $options['target'] = sprintf(
            'consolidateBy(%s.%s, "min")',
            $this->GraphiteConfig->getGraphitePrefix(),
            $GraphiteMetric->getMetricPath()
        );

        return $this->normalizeData($this->sendRequest($options));
    }

    /**
     * @param GraphiteMetric $GraphiteMetric
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSeriesMax(GraphiteMetric $GraphiteMetric) {
        $options = $this->getBaseRequestOptions();
        $options['target'] = sprintf(
            'consolidateBy(%s.%s, "max")',
            $this->GraphiteConfig->getGraphitePrefix(),
            $GraphiteMetric->getMetricPath()
        );
        return $this->normalizeData($this->sendRequest($options));
    }

    /**
     * @param GraphiteMetric $GraphiteMetric
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSeriesAsCounter(GraphiteMetric $GraphiteMetric) {
        $options = $this->getBaseRequestOptions();

        $options['target'] = sprintf(
        //'scale(nonNegativeDerivative(keepLastValue(%s.%s)), 0.125)',
            'nonNegativeDerivative(keepLastValue(%s.%s))',
            $this->GraphiteConfig->getGraphitePrefix(),
            $GraphiteMetric->getMetricPath()
        );

        return $this->normalizeData($this->sendRequest($options));
    }

    /**
     * @return array
     */
    private function getBaseRequestOptions() {
        $options = [
            'format'        => 'json',
            'maxDataPoints' => $this->maxDataPoints
        ];

        if ($this->useAbsoluteDate) {
            $options['from'] = date('H:i_Ymd', $this->start);
            $options['until'] = date('H:i_Ymd', $this->end);
        } else {
            $options['from'] = sprintf('-%ss', $this->from);

        }

        if ($this->hideNullValues) {
            $options['noNullPoints'] = 'true';
        }

        return $options;
    }

    /**
     * @param array $options
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest($options = []) {
        $client = $this->getHttpClient([], $options);
        $request = new Request('GET', $this->GraphiteConfig->getBaseUrl() . '/render');
        try {
            $response = $client->send($request);
        } catch (\Exception $e) {
            if ($e instanceof ClientException) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                return $responseBody;
            }
            return $e->getMessage();
        }

        if ($response->getStatusCode() == 200) {
            return \json_decode($response->getBody()->getContents(), true);
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function normalizeData($data) {
        $normalizedData = [];

        if (is_array($data)) {
            foreach ($data as $metric) {
                foreach ($metric['datapoints'] as $datapoint) {
                    if ($this->hideNullValues && $datapoint[0] === null) {
                        continue;
                    }
                    $timestamp = $datapoint[1];
                    if ($this->debug) {
                        $normalizedData[date('d.m.Y H:i:s', $timestamp)] = $datapoint[0];
                    } else {
                        if ($this->useJsTimestamp) {
                            $timestamp = $timestamp * 1000;
                        }
                        if ($this->useIsoTimestamp) {
                            $timestamp = date('c', $timestamp);
                        }
                        $normalizedData[$timestamp] = $datapoint[0];
                    }

                }
            }
        }
        return $normalizedData;
    }

    /**
     * @param string $hostUuid
     * @param string $serviceUuid
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findMetricsByUuid($hostUuid, $serviceUuid) {
        $client = $this->getHttpClient([], [
            'query' => sprintf(
                '%s.%s.%s.*',
                $this->GraphiteConfig->getGraphitePrefix(),
                $hostUuid,
                $serviceUuid
            )
        ]);

        $request = new Request('GET', $this->GraphiteConfig->getBaseUrl() . '/metrics/find');
        try {
            $response = $client->send($request);
        } catch (\Exception $e) {
            return [];
        }

        $metrics = [];
        if ($response->getStatusCode() == 200) {
            foreach (\json_decode($response->getBody()->getContents(), true) as $metric) {
                $metrics[] = $metric['text'];
            }
        }

        return $metrics;
    }

}
