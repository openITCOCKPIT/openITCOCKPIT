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


use Cake\Core\Configure;

class GraphiteConfig {

    /**
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * @var int
     */
    private $port = 8888;

    /**
     * @var string
     */
    private $graphitePrefix;

    /**
     * @var bool
     */
    private $useHttps;

    /**
     * @var bool
     */
    private $useProxy;

    /**
     * @var bool
     */
    private $ignoreSslCertificate;


    public function __construct() {

        Configure::load('graphite');
        $configFromFile = Configure::read('graphite');

        $this->host = $configFromFile['graphite_web_host'];
        $this->port = $configFromFile['graphite_web_port'];
        $this->graphitePrefix = $configFromFile['graphite_prefix'];
        $this->useHttps = $configFromFile['use_https'];
        $this->useProxy = $configFromFile['use_proxy'];
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getGraphitePrefix() {
        return $this->graphitePrefix;
    }

    /**
     * @return bool
     */
    public function isUseHttps() {
        return $this->useHttps;
    }

    /**
     * @return bool
     */
    public function isUseProxy() {
        return $this->useProxy;
    }

    /**
     * @return bool
     */
    public function isIgnoreSslCertificate() {
        return !$this->ignoreSslCertificate;
    }


    public function getBaseUrl() {
        return sprintf(
            '%s%s:%s',
            $this->isUseHttps() ? 'https://' : 'http://',
            $this->host,
            $this->port
        );
    }

}
