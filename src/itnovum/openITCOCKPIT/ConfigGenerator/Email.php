<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace App\itnovum\openITCOCKPIT\ConfigGenerator;


use Cake\Core\Configure;
use itnovum\openITCOCKPIT\ConfigGenerator\ConfigGenerator;
use itnovum\openITCOCKPIT\ConfigGenerator\ConfigInterface;

class Email extends ConfigGenerator implements ConfigInterface, ContainerConfigInterface {

    protected $templateDir = 'config';

    protected $template = 'email.php.tpl';

    /**
     * @var string
     */
    protected $linkedOutfile = CONFIG . 'email.php';

    /**
     * @var string
     */
    protected $realOutfile = CONFIG . 'email.php';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'string' => [
            'className' => '\Cake\Mailer\Transport\MailTransport::class',
            'host'      => '127.0.0.1',
            'username'  => '',
            'password'  => '',
        ],
        'int'    => [
            'port'    => 25,
            'timeout' => 30
        ],
        'bool'   => [
            'tls' => false
        ]
    ];

    protected $dbKey = 'DbBackend';

    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        $error = [];
        $fakeModelName = 'Configfile';
        if (isset($data['string']) && is_array($data['string'])) {
            foreach ($data['string'] as $field => $value) {
                if ($field === 'dbbackend') {
                    if (!in_array($value, ['Nagios', 'Crate', 'Statusengine3', 'Statusengine4'], true)) {
                        $error[$fakeModelName][$field][] = __('Value out of range (Nagios, Crate, Statusengine3, Statusengine4)');
                    }
                }
            }
        }

        if (!empty($error)) {
            return $error;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'email-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'email' => __('E-Mail configuration used by openITCOCKPIT'),
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
    }

    public function getValuesFromEnvironment() {
        return [
            [
                'key'   => 'dbbackend',
                'value' => env('OITC_DB_BACKEND', 'Statusengine4'),
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

        // We use var_export, to get a quoted string that will not break the php config file if the password contains ' or " oder \ etc.
        //'exported_password' => var_export($rawPassword, true),

        foreach ($config as $type => $fields) {
            foreach ($fields as $key => $value) {
                $configToExport[$key] = $value;
            }
        }

        return $this->saveConfigFile($configToExport);
    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        //Collect email configuration
        Configure::load('email', 'default', false);
        $mailConfig = Configure::read('EmailTransport');

        $className = $mailConfig['default']['className'];
        $host = $mailConfig['default']['host'];
        $port = (int)$mailConfig['default']['port'];
        $username = $mailConfig['default']['username'];
        $password = $mailConfig['default']['password'];


        if (!file_exists($this->linkedOutfile)) {
            return false;
        }

        return $config;
    }

}
