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

class AfterExport extends ConfigGenerator implements ConfigInterface, ContainerConfigInterface {

    protected $templateDir = 'config';

    protected $template = 'after_export.php.tpl';

    protected $realOutfile = CONFIG . 'after_export.php';

    protected $linkedOutfile = CONFIG . 'after_export.php';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'string' => [
            'username'        => 'nagios',
            'private_key'     => '/var/lib/nagios/.ssh/id_rsa',
            'public_key'      => '/var/lib/nagios/.ssh/id_rsa.pub',
            'restart_command' => 'sudo /opt/openitc/nagios/bin/restart-monitoring.sh',
        ],
        'int'    => [
            'remote_port' => 22
        ]
    ];

    protected $dbKey = 'AfterExport';

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
        return 'after-export-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'username'        => __('SSH user used to establish SSH connection with satellite systems.'),
            'private_key'     => __('Private key file used to establish SSH connection.'),
            'public_key'      => __('Public key file used to establish SSH connection.'),
            'restart_command' => __('Remote command to restart monitoring engine and run remote after export tasks.'),
            'remote_port'     => __('Port number of remote SSH server.')
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
    }

    public function getValuesFromEnvironment() {
        return [
            [
                'key'   => 'username',
                'value' => env('OITC_AFTER_EXPORT_SSH_USERNAME', 'nagios'),
            ],
            [
                'key'   => 'private_key',
                'value' => env('OITC_AFTER_EXPORT_SSH_PRIVATE_KEY', '/var/lib/nagios/.ssh/id_rsa'),
            ],
            [
                'key'   => 'public_key',
                'value' => env('OITC_AFTER_EXPORT_SSH_PUBLIC_KEY', '/var/lib/nagios/.ssh/id_rsa.pub'),
            ],
            [
                'key'   => 'restart_command',
                'value' => env('OITC_AFTER_EXPORT_RESTART_COMMAND', 'sudo /opt/openitc/nagios/bin/restart-monitoring.sh'),
            ],
            [
                'key'   => 'remote_port',
                'value' => env('OITC_AFTER_EXPORT_SSH_REMOTE_PORT', 22),
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
                $configToExport[$key] = $value;
            }
        }

        $realFile = CONFIG . 'after_export.php';
        return $this->saveConfigFile($configToExport);
    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        if (!file_exists($this->linkedOutfile)) {
            return false;
        }
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        Configure::load('after_export');
        $configFromFile = Configure::read('after_export');

        foreach ($config['string'] as $field => $value) {
            if (isset($configFromFile['SSH'][$field])) {
                if ($config['string'][$field] != $configFromFile['SSH'][$field]) {
                    $config['string'][$field] = $configFromFile['SSH'][$field];
                }
            }
        }

        if (isset($configFromFile['SSH']['port'])) {
            if ($config['int']['remote_port'] != $configFromFile['SSH']['port']) {
                $config['int']['remote_port'] = $configFromFile['SSH']['port'];
            }
        }

        return $config;
    }

}
