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
use Cake\Core\Configure;

class Gearman extends ConfigGenerator implements ConfigInterface, ContainerConfigInterface {
    /** @var string */
    protected $templateDir = 'config';
    /** @var string */
    protected $template = 'gearman.php.tpl';
    /** @var string */
    protected $realOutfile = CONFIG . 'gearman.php';
    /** @var string */
    protected $linkedOutfile = CONFIG . 'gearman.php';
    /** @var string */
    protected $commentChar = '//';
    /** @var array */
    protected $defaults = [
        'string' => [
            'address' => '127.0.0.1',
            'pidfile' => '/var/run/oitc_gearmanworker.pid',
        ],
        'int'    => [
            'port'   => 4730,
            'worker' => 5
        ],
    ];

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'gearman-cfg';
    }

    protected $dbKey = 'Gearman';

    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        return true;
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public function getHelpText($key) {
        $help = [
            'address' => __('The host address where the gearman-job-server is running.'),
            'pidfile' => __('Process id file used by the gearman_worker.'),
            'port'    => __('Port number of gearman-job-server.'),
            'worker'  => __('Number of gearman_worker processes.')
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
    }

    public function getValuesFromEnvironment() {
        return [
            [
                'key'   => 'address',
                'value' => env('OITC_GEARMAN_ADDRESS', 'gearmand'),
            ],
            [
                'key'   => 'port',
                'value' => env('OITC_GEARMAN_PORT', 4730),
            ],
            [
                'key'   => 'worker',
                'value' => env('OITC_GEARMAM_WORKER', 5),
            ]
        ];
    }

    /**
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
                $configToExport[$key] = $value;
            }
        }
        return $this->saveConfigFile($configToExport);
    }


    /**
     * @param array $dbRecords
     * @return array|bool
     */
    public function migrate($dbRecords) {
        if (!file_exists($this->linkedOutfile)) {
            return false;
        }
        return $this->mergeDbResultWithDefaultConfiguration($dbRecords);
    }
}
