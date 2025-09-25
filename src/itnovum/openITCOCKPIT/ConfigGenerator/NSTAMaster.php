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

namespace itnovum\openITCOCKPIT\ConfigGenerator;


use App\itnovum\openITCOCKPIT\ConfigGenerator\ContainerConfigInterface;
use itnovum\openITCOCKPIT\Core\System\Health\MonitoringEngine;

/**
 * Class NSTAMaster
 * NSTA written in Go by Johannes
 * @package itnovum\openITCOCKPIT\ConfigGenerator
 */
class NSTAMaster extends ConfigGenerator implements ConfigInterface,ContainerConfigInterface {

    protected $templateDir = 'NSTA';

    protected $template = 'nsta.ini.tpl';

    protected $linkedOutfile = '/opt/openitc/etc/nsta/nsta.ini';

    protected $realOutfile = '/opt/openitc/etc/nsta/nsta.ini';

    /**
     * @var string
     */
    protected $commentChar = ';';

    protected $defaults = [
        'string' => [
            'listen_http'  => '127.0.0.1:7473',
            'listen_https' => '127.0.0.1:7474',
            'tls_key'      => '/etc/ssl/private/ssl-cert-snakeoil.key',
            'tls_cert'     => '/etc/ssl/certs/ssl-cert-snakeoil.pem'
        ],
        'bool'   => [
            'use_nginx_proxy' => 1
        ]
    ];

    protected $dbKey = 'NSTAMaster';

    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        return true;
    }

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'nsta-master-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'listen_http'     => __('Bind address of the HTTP interface. Default: 127.0.0.1:7473'),
            'listen_https'    => __('Bind address of the HTTP interface. Default: 127.0.0.1:7474'),
            'tls_key'         => __('TLS certificate key file to be used. Default: /etc/ssl/private/ssl-cert-snakeoil.key'),
            'tls_cert'        => __('TLS certificate file to be used. Default: /etc/ssl/certs/ssl-cert-snakeoil.pem'),
            'use_nginx_proxy' => __('Use openITCOCKPIT\'s preconfigured webserver as revers proxy for NSTA.')
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
    }

    public function getValuesFromEnvironment() {
        return [
            [
                'key'   => 'listen_http',
                'value' => env('OITC_NSTA_LISTEN_HTTP', '127.0.0.1:7473'),
            ],
            [
                'key'   => 'listen_https',
                'value' => env('OITC_NSTA_LISTEN_HTTPS', '127.0.0.1:7474'),
            ],
            [
                'key'   => 'tls_key',
                'value' => env('OITC_NSTA_TLS_KEY', '/etc/ssl/private/ssl-cert-snakeoil.key'),
            ],
            [
                'key'   => 'tls_cert',
                'value' => env('OITC_NSTA_TLS_CERT', '/etc/ssl/certs/ssl-cert-snakeoil.pem'),
            ],
            [
                'key'   => 'use_nginx_proxy',
                'value' => env('OITC_NSTA_USE_NGINX_PROXY', 1),
            ]
        ];
    }

    /**
     * Save the configuration as text file on disk
     *
     * @param array $dbRecords
     * @return bool|int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function writeToFile($dbRecords) {
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        if($config['bool']['use_nginx_proxy'] == 1){
            //This is only configureable if the user is not going to use the openITCOCKPIT default nginx config
            $config['string']['listen_http'] = '127.0.0.1:7473';
            $config['string']['listen_https'] = '127.0.0.1:7474';
            $config['string']['tls_key'] = '/etc/ssl/private/ssl-cert-snakeoil.key';
            $config['string']['tls_cert'] = '/etc/ssl/certs/ssl-cert-snakeoil.pem';
        }


        $configToExport = [];
        foreach ($config as $type => $fields) {
            foreach ($fields as $key => $value) {
                switch ($type) {
                    case 'bool':
                        $configToExport[$key] = $this->asBoolString($value);
                        break;

                    default:
                        $configToExport[$key] = $value;
                        break;

                }
            }
        }

        return $this->saveConfigFile($configToExport);
    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        return $this->mergeDbResultWithDefaultConfiguration($dbRecords);
    }

}
