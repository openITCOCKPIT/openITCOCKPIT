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

namespace itnovum\openITCOCKPIT\Agent;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class AgentConfiguration
 * @package itnovum\openITCOCKPIT\Agent
 */
class AgentConfiguration {

    /**
     * The version of the JSON configuration
     * Increase this value whenever a new field gets added (or an existing one gets removed) to the json data
     * Also add a migration for old records => self::migration()
     *
     * @var string
     */
    private $config_version = '3.1.0';

    /**
     * This defines all agent configuration options with the corresponding default values
     *
     * Add new config options to this array.
     * If you add a new option, increase self::$config_version !!!
     * Also update self::migration()
     *
     * Keep the keys unique across all data types !!!
     *
     * @var array
     */
    private $fields = [
        'string' => [
            'bind_address'         => '0.0.0.0', // Bind address of the Agents web server
            'username'             => '',        // Username used for basic auth
            'password'             => '',        // Password used for basic auth
            'push_oitc_server_url' => '',        // Server Address of the openITCOCKPIT Server "https://demo.openitcockpit.io"
            'push_oitc_api_key'    => '',        // API Key used by the Agent to push results
            'operating_system'     => 'linux',   // OS of the Agent for right restart commands and path variables
            'push_proxy_address'   => '',        // Proxy Server to use for the Agent in push mode "http://proxy.master.dns:8080"
            'customchecks_path'    => '',        //Path to custom checks config
            'ssl_certfile'         => '',        // Path to the certificate file the agent should use to enable HTTPS (only used if use_autossl is false)
            'ssl_keyfile'          => '',        // Path to the key file the agent should use to enable HTTPS (only used if use_autossl is false)
            'autossl_folder'       => '',        // If set, autossl_csr_file, autossl_crt_file, autossl_key_file and autossl_ca_file gets ignored
            'autossl_csr_file'     => '',        // Path to Certificate Signing Request file if use_autossl is true
            'autossl_crt_file'     => '',        // Path to certificate file if use_autossl is true
            'autossl_key_file'     => '',        // Path to private key if use_autossl is true
            'autossl_ca_file'      => '',        // Path to server ca file if use_autossl is true
        ],
        'bool'   => [
            'enable_push_mode'               => false, // If the agent is running in push mode
            'use_proxy'                      => false, // If the oITC Server should use a proxy server to query the agent in Pull mode
            'enable_remote_config_update'    => false, // Allow to push a new configuration to the Agent (dangerous)
            'use_http_basic_auth'            => false, // Enable basic auth for the Agents web server
            'push_verify_server_certificate' => false, // If the agent should verify the openITCOCKPIT Servers SSL certificate (Requires valid certificates like Let's Encrypt)
            'push_enable_webserver'          => false, // Do not enable the webserver if the agent is running in PUSH mode
            'push_webserver_use_https'       => true,  // Start the webserver on the Agent in Push mode with HTTPS (requires ssl_certfile and ssl_keyfile to be set)
            'use_autossl'                    => true,  // Use autossl Pull mode only
            'verify_autossl_expiry'          => false, // Verify autossl certificate expiry date (only if use_autossl=true)
            'use_https'                      => false, // This sets use_autossl=false and requires ssl_certfile and ssl_keyfile to start the agent with HTTPS and custom certs (e.g from Let's Encrypt)
            'use_https_verify'               => false, // Disable certificate validation when use_https=true (Requires valid certificates like Let's Encrypt)

            // Checks
            'cpustats'                       => true, // Enable CPU checks
            'memory'                         => true, // Enable memory check
            'swap'                           => true, // Enable Swap check
            'processstats'                   => true, // Enable process checks
            'netstats'                       => true, // Enable network checks
            'netio'                          => true, // Enable network traffic checks
            'diskstats'                      => true, // Enable disk usage checks
            'diskio'                         => true, // Enable disk load checks
            'systemdservices'                => true, // Enable Systemd checks (Linux)
            'launchdservices'                => true, // Enable launchd checks (macOS)
            'winservices'                    => true, // Enable Windows Services checks
            'wineventlog'                    => false, // Enable Windows Event Log checks (Disabled by default due to bluescreens see OA-40)
            'sensorstats'                    => true, //Enable monitoring of temperature and battery sensors
            'dockerstats'                    => false, // Enable docker checks
            'libvirt'                        => true, // Enable libvirt checks (requires libvirt build),
            'userstats'                      => true, // Enable logged in users check
            'ntp'                            => true, // Enable NTP check
        ],
        'int'    => [
            'bind_port'      => 3333, // Bind port of the Agents web server
            'check_interval' => 30,   // Interval in seconds how often the Agent should execute all default checks
            'push_timeout'   => 10,   // HTTP Timeout in seconds the Agent uses to push check results to the server
        ],
        'array'  => [
            'win_eventlog_types' => [  // Types that should be monitored in the Windows Event log
                'System', 'Application', 'Security',
            ]
        ]
    ];

    /**
     * Holds the json configuration of the agent
     * @var array
     */
    private $json = [];

    /**
     * This function gets called directly after the JSON data got received from the database
     * It ensures to update old JSON versions to to current json version.
     *
     * Whenever you add a new field to the json, you have to add a migration path in here
     *
     * @param array $json
     * @return array
     */
    private function migration($json) {
        // Example migration to mmigrate data grom config_version < 3.0.0 to config version 3.0.0
        //if (version_compare($this->config_version, $json['config_version']) > 0) {
        //    if (!isset($json['int']['bind_port'])) {
        //        $json['int']['bind_port'] = 3333;
        //    }
        //}

        // Add new field "verify_autossl_expiry" if not exists (added in config version 3.1.0)
        if (isset($json) && isset($json['bool'])) {
            if (!array_key_exists('verify_autossl_expiry', $json['bool'])) {
                $json['bool']['verify_autossl_expiry'] = false;
            }
        }

        return $json;
    }

    /**
     * Used to set a config from the Wizard
     *
     * @param array $json
     */
    public function setConfigForJson($json) {
        $this->json = $json;
    }

    /**
     * Convert php array to json for database
     * @return string
     * @throws \RuntimeException
     */
    public function marshal() {
        foreach ($this->fields as $dataType => $fields) {
            foreach ($fields as $fieldName => $defaultValue) {

                // Check for missing fields
                if (!isset($this->json[$dataType][$fieldName])) {
                    $this->json[$dataType][$fieldName] = $defaultValue;
                }

                // Fix all data types to make string "1" to int 1 etc
                switch ($dataType) {
                    case 'string':
                        $this->json[$dataType][$fieldName] = (string)$this->json[$dataType][$fieldName];
                        break;
                    case 'int':
                        $this->json[$dataType][$fieldName] = (int)$this->json[$dataType][$fieldName];
                        break;
                    case 'bool':
                        $this->json[$dataType][$fieldName] = $this->toBool($this->json[$dataType][$fieldName]);
                        break;
                    case 'array':
                        $this->json[$dataType][$fieldName] = (array)$this->json[$dataType][$fieldName];
                        break;
                    default:
                        throw new \RuntimeException(sprintf('Unsupported datatype: "%s"', $dataType));
                }
            }
        }

        if ($this->json['bool']['use_http_basic_auth'] === false) {
            $this->json['string']['username'] = '';
            $this->json['string']['password'] = '';
        }

        $this->json['string']['config_version'] = $this->config_version;
        return json_encode($this->json);
    }

    /**
     * Convert JSON from database to php array
     * @param string $jsonDataStr
     * @return array
     * @throws \RuntimeException
     */
    public function unmarshal($jsonDataStr) {
        if (empty($jsonDataStr)) {
            $jsonDataStr = '{}'; //empty json object
        }

        $json = json_decode($jsonDataStr, true);
        $json = $this->migration($json);

        foreach ($this->fields as $dataType => $fields) {
            foreach ($fields as $fieldName => $defaultValue) {

                // Check for missing fields
                if (!isset($json[$dataType][$fieldName])) {
                    $json[$dataType][$fieldName] = $defaultValue;
                }

                // Fix all data types to make string "1" to int 1 etc
                switch ($dataType) {
                    case 'string':
                        $json[$dataType][$fieldName] = (string)$json[$dataType][$fieldName];
                        break;
                    case 'int':
                        $json[$dataType][$fieldName] = (int)$json[$dataType][$fieldName];
                        break;
                    case 'bool':
                        $json[$dataType][$fieldName] = $this->toBool($json[$dataType][$fieldName]);
                        break;
                    case 'array':
                        $json[$dataType][$fieldName] = (array)$json[$dataType][$fieldName];
                        break;
                    default:
                        throw new \RuntimeException(sprintf('Unsupported datatype: "%s"', $dataType));
                }
            }
        }

        $this->json = $json;
        return $json;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function toBool($value) {
        $value = (string)$value;
        if (in_array(strtolower($value), ['true', 'yes', 'on', '1'], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param bool $value
     * @return string
     */
    private function toBoolIni($value) {
        if ($value) {
            return 'True';
        }

        return 'False';
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getAsIni() {
        $loader = new FilesystemLoader([
            APP . 'config_templates' . DS . 'agent' . DS
        ]);

        // Define defaults that only exits in the config.ini
        $templateVars = [
            'http_basic_auth_credentials' => '',
            'certfile'                    => '',
            'keyfile'                     => '',
        ];

        foreach ($this->json as $dataType => $fields) {
            foreach ($fields as $fieldName => $defaultValue) {
                // Fix all data types for ini file
                switch ($dataType) {
                    case 'bool':
                        $templateVars[$fieldName] = $this->toBoolIni($this->json[$dataType][$fieldName]);
                        break;
                    case 'array':
                        $templateVars[$fieldName] = implode(',', $this->json[$dataType][$fieldName]);
                        break;
                    default:
                        //No action required for strings and integers
                        $templateVars[$fieldName] = $this->json[$dataType][$fieldName];
                }
            }
        }

        if ($this->json['bool']['enable_push_mode'] === false) {
            // Agent is in PUSH mode
            if ($this->json['bool']['use_https']) {
                $templateVars['certfile'] = $this->json['string']['ssl_certfile'];
                $templateVars['keyfile'] = $this->json['string']['ssl_keyfile'];
            }
        }

        if ($this->json['bool']['use_http_basic_auth'] === true) {
            $templateVars['http_basic_auth_credentials'] = sprintf(
                '%s:%s',
                $this->json['string']['username'],
                $this->json['string']['password']
            );
        }

        if ($this->json['bool']['enable_push_mode'] === true) {
            // Agent is in PULL mode
            if ($this->json['bool']['push_enable_webserver'] && $this->json['bool']['push_webserver_use_https']) {
                $templateVars['certfile'] = $this->json['string']['ssl_certfile'];
                $templateVars['keyfile'] = $this->json['string']['ssl_keyfile'];
            }
        }


        $twig = new Environment($loader, ['debug' => true]);
        return $twig->render('agent.ini', $templateVars);
    }
}
