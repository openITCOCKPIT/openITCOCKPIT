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

namespace itnovum\openITCOCKPIT\ConfigGenerator;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Statusengine4Cfg extends ConfigGenerator implements ConfigInterface {

    protected $templateDir = 'statusengine4';

    protected $template = 'config.yml.tpl';

    protected $realOutfile = '/opt/openitc/statusengine4/worker/etc/config.yml';

    protected $linkedOutfile = '/opt/openitc/etc/statusengine/config.yml';

    /**
     * @var string
     */
    protected $commentChar = '#';

    protected $defaults = [
        'int' => [
            'gearman_max_concurrent_jobs_per_queue' => 1,

            'mysql_batch_size'     => 500,
            'mysql_max_open_conns' => 25,

            'graphite_port' => 2003
        ],

        'string' => [
            'graphite_address'    => '127.0.0.1',
            'graphite_prefix'     => 'openitcockpit',
            'listen_addr'         => '127.0.0.1:8091',
            'metrics_listen_addr' => '127.0.0.1:9105',
            'command_listen_addr' => '127.0.0.1:8092',
        ],

        'string_array' => [
            'api_keys'         => [], // The default can be a string array. When the value is from the DB it is a comma separated list of API keys for the readonly /ws WebSocket Event Stream
            'command_api_keys' => [] // API keys for the writable /commands HTTP endpoint
        ]
    ];

    protected $dbKey = 'Statusengine4Cfg';

    public function customValidationRules($data) {
        $error = [];
        $fakeModelName = 'Configfile';

        foreach (['api_keys', 'command_api_keys'] as $field) {
            if (!empty($data['string_array'][$field])) {
                if (!is_array($data['string_array'][$field])) {
                    $error[$fakeModelName][$field][] = 'The value must be an array of strings.';
                    break;
                }

                // We have to ensure that array as comma separated string is not longer than 2000 characters, because the DB field is a varchar(2000)
                if (sizeof($data['string_array'][$field]) > 25) {
                    $error[$fakeModelName][$field][] = 'The array must not contain more than 25 elements.';
                    break;
                }

                // Make sure the API key does not contain spaces or special characters
                foreach ($data['string_array'][$field] as $index => $apiKey) {
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $apiKey)) {
                        $error[$field][$index][$field] = __('This field can only contain alphanumeric characters (a-z, A-Z, 0-9) and no spaces or special characters.');
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
     * @deprecated Not used by Angular frontend anymore (AngularJS legacy)
     */
    public function getAngularDirective() {
        return 'statusengine4-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'gearman_max_concurrent_jobs_per_queue' => 'Maximum number of Gearman job handlers running at once, PER QUEUE. This is not a throughput knob. When set to more than 1, the order of events is not guaranteed.',
            'mysql_batch_size'                      => 'Rows buffered per table before a bulk INSERT is flushed ahead of a 250ms ticker. Max value is 700.',
            'mysql_max_open_conns'                  => 'Maximum number of open MySQL connections Keep this below the server\'s max_connections, it applies per worker process.',
            'api_keys'                              => 'API key to get access to the readonly /ws WebSocket Event Stream. Use a strong random string without spaces or special characters.',
            'listen_addr'                           => 'Address the readonly WebSocket HTTP server (/ws) listens on. Set it to \':8091\' to listen on all interfaces.',
            'metrics_listen_addr'                   => 'Address the readonly Prometheus /metrics HTTP server listens on. Set it to \':9105\' to listen on all interfaces.',
            'command_listen_addr'                   => 'Address the external-command HTTP server (/commands) listens on. This is a writable endpoint to pass external commands. Set it to \':8092\' to listen on all interfaces.',
            'command_api_keys'                      => 'API key to get writable access to the /commands HTTP endpoint. Use a strong random string without spaces or special characters.',
            'graphite_port'                         => 'Carbon cache port number',
            'graphite_address'                      => 'Carbon cache server address',
            'graphite_prefix'                       => 'Prefix added to every metric stored in carbon (Required for Grafana)'
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
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

        $success = true;

        $FileHeader = new FileHeader();
        $configToExport['STATIC_FILE_HEADER'] = $FileHeader->getHeader($this->commentChar);

        $mcp = new \App\itnovum\openITCOCKPIT\Database\MysqlConfigFileParser();
        $ini_file = $mcp->parse_mysql_cnf('/opt/openitc/etc/mysql/mysql.cnf');

        $configToExport['mysql_host'] = $ini_file['host'];
        $configToExport['mysql_user'] = $ini_file['user'];
        $configToExport['mysql_password'] = $ini_file['password'];
        $configToExport['mysql_database'] = $ini_file['database'];

        // Statusengine expects the API Keys to be in array
        // Currently the Config File Generator can not handle array
        // so we have to convert the keys to array here
        $configToExport['api_keys'] = [];
        $configToExport['command_api_keys'] = [];
        foreach (['ws_api_key', 'command_api_key'] as $keyPrefix) {
            foreach ([1, 2, 3] as $keyNumber) {
                $keyName = $keyPrefix . '_' . $keyNumber;
                if (!empty($configToExport[$keyName])) {
                    if ($keyPrefix === 'ws_api_key') {
                        $configToExport['api_keys'][] = $configToExport[$keyName];
                    } else {
                        $configToExport['command_api_keys'][] = $configToExport[$keyName];
                    }
                }
            }
        }

        /*
         * Write:
         * - config.yml
         */
        $loader = new FilesystemLoader([
            $this->getTemplatePath()
        ]);
        $twig = new Environment($loader, ['debug' => true]);

        // /opt/openitc/statusengine4/worker/etc/config.yml
        $ConfigSymlink = new ConfigSymlink($this->realOutfile, $this->linkedOutfile);
        if (!file_put_contents($this->realOutfile, $twig->render($this->getTemplateName(), $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();


        return $success;

    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        return $this->mergeDbResultWithDefaultConfiguration($dbRecords);
    }

}
