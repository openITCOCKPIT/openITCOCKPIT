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

class GraphiteWeb extends ConfigGenerator implements ConfigInterface,ContainerConfigInterface {

    protected $templateDir = 'config';

    protected $template = 'graphite.php.tpl';


    /**
     * @var string
     */
    protected $linkedOutfile = CONFIG . 'graphite.php';

    /**
     * @var string
     */
    protected $realOutfile = CONFIG . 'graphite.php';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'string' => [
            'graphite_web_host' => '127.0.0.1',
            'graphite_prefix'   => 'openitcockpit'
        ],
        'int'    => [
            'graphite_web_port' => 8888
        ],
        'bool'   => [
            'use_https' => false,
            'use_proxy' => false
        ]
    ];

    protected $dbKey = 'GraphiteWeb';

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
        return 'graphite-web-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'graphite_web_host' => __('IP-Address of the Graphite-Web server openITCOCKPIT should use to query data.'),
            'graphite_web_port' => __('Port of the Graphite-Web server.'),
            'graphite_prefix'   => __('Prefix added to every metric stored in carbon'),
            'use_https'         => __('Use HTTPS to connect to Graphite-Web server.'),
            'use_proxy'         => __('Use configured proxy server to connect to Graphite-Web server.')
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
    }

    public function getValuesFromEnvironment() {
        return [
            [
                'key'   => 'graphite_web_host',
                'value' => env('OITC_GRAPHITE_WEB_ADDRESS', '127.0.0.1'),
            ],
            [
                'key'   => 'graphite_prefix',
                'value' => env('OITC_GRAPHITE_PREFIX', 'openitcockpit'),
            ],
            [
                'key'   => 'graphite_web_port',
                'value' => env('OITC_GRAPHITE_WEB_PORT', 8888),
            ],
            [
                'key'   => 'use_https',
                'value' => env('OITC_GRAPHITE_USE_HTTPS', 0),
            ],
            [
                'key'   => 'use_proxy',
                'value' => env('OITC_GRAPHITE_USE_PROXY', 0),
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
