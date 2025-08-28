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


use itnovum\openITCOCKPIT\Core\System\Health\MonitoringEngine;

/**
 * Class phpNSTAMaster
 * @package itnovum\openITCOCKPIT\ConfigGenerator
 * @deprecated phpNSTA got replaced with the go NSTA version !
 */
class phpNSTAMaster extends ConfigGenerator implements ConfigInterface {

    protected $templateDir = 'phpNSTA';

    protected $template = 'config.php.tpl';

    protected $linkedOutfile = '/opt/openitc/etc/phpnsta/config.php';

    protected $realOutfile = '/opt/openitc/phpNSTA/config.php';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'string' => [
            'use_spooldir'          => '3',
            'logrotate_date_format' => 'd_m_Y_H_i',
            'date_format'           => 'd.m.Y H:i:s',
            'ssh_username'          => 'nagios',
            'private_path'          => '/var/lib/nagios/.ssh/id_rsa',
            'public_path'           => '/var/lib/nagios/.ssh/id_rsa.pub',
            'port_range'            => '55000-55500',
            'supervisor_username'   => 'phpNSTA',
            'supervisor_password'   => 'phpNSTAsSecretPassword',
            'tsync_every'           => 'hour',
            'loglevel'              => '12',
        ],
        'int'    => [
            'cleanup_fileage' => 15,
            'max_checks'      => 200,
            'max_threads'     => 20,
            'ssh_port'        => 22,
        ],
        'bool'   => [
            'use_ssh_tunnel'   => 1,
            'synchronize_time' => 1
        ]
    ];

    protected $dbKey = 'phpNSTAMaster';

    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        $error = [];
        $fakeModelName = 'Configfile';
        if (isset($data['string']) && is_array($data['string'])) {
            foreach ($data['string'] as $field => $value) {

                switch ($field) {
                    case 'use_spooldir':
                        if ($value < 1 || $value > 3) {
                            $error[$fakeModelName][$field][] = __('Value out of range (1-3)');
                        }
                        break;

                    case 'loglevel':
                        if ($value < -1 || $value > 12) {
                            $error[$fakeModelName][$field][] = __('Value out of range (1-3)');
                        }
                        break;
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
        return 'phpnsta-master-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'logrotate_date_format' => __('Date format for filename of rotaed logfiles. Germany: d_m_Y_H_i, USA: m_d_Y_H_i'),
            'date_format'           => __('Date format of the timestamp in the logfile. Germany: d.m.Y H:i:s, USA: m.d.Y H:i:s'),
            'cleanup_fileage'       => __('Determines in days, how long old log files should be keeped.'),
            'use_spooldir'          => __('Determines how to submit check result data to Naemon/Nagios.'),
            'max_checks'            => __('Bulk size of submitted passive check results.'),
            'max_threads'           => __('Maximum number of worker processes. Recommendation: One process per satellite.'),
            'use_ssh_tunnel'        => __('Use an SSH tunnel for each satellite for secure connection.'),
            'ssh_username'          => __('Username used to established SSH connection.'),
            'private_path'          => __('Private key file used to established SSH connection.'),
            'public_path'           => __('Public key file used to established SSH connection.'),
            'ssh_port'              => __('SSH Port of remote hosts'),
            'port_range'            => __('The local ports where each satellite system gets bind to'),
            'supervisor_username'   => __('Username used for supervisor XML-RPC API.'),
            'supervisor_password'   => __('Password of supervisor XML-RPC API'),
            'synchronize_time'      => __('Synchronize the system clock to each connected satellite system'),
            'tsync_every'           => __('System time synchronization interval')
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

        // replacement of ps -eaf because it takes ps too long to display the username in an LDAP based setup
        // https://www.ibm.com/support/pages/apar/IJ08995
        // we have no need for the username, so we can use the faster ps -eo command
        $grepCommand = 'ps -eo command | grep "/opt/openitc/nagios/bin/naemon -d /opt/openitc/etc/nagios/nagios.cfg" |grep -v "grep"';
        $MonitoringEngine = new MonitoringEngine();
        if ($MonitoringEngine->isNagios()) {
            $grepCommand = 'ps -eo command | grep "/opt/openitc/nagios/bin/nagios -d /opt/openitc/etc/nagios/nagios.cfg" |grep -v "grep"';

            if ($configToExport['use_spooldir'] == '3') {
                //Query handler not supported by Nagios 4.x
                //Fallback to nagios.cmd
                $configToExport['use_spooldir'] = '2';
            }

        }

        $configToExport['grep_command'] = $grepCommand;
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

        require_once $this->linkedOutfile;
        if (!isset($config)) {
            return false;
        }
        $configFromFile = $config; //$config gets defined in required file

        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        if ($config['string']['loglevel'] != $configFromFile['LOG']['loglevel']) {
            $config['string']['loglevel'] = $configFromFile['LOG']['loglevel'];
        }

        if ($config['string']['logrotate_date_format'] != $configFromFile['LOG']['logrotate_date_format']) {
            $config['string']['logrotate_date_format'] = $configFromFile['LOG']['logrotate_date_format'];
        }

        if ($config['string']['date_format'] != $configFromFile['LOG']['date_format']) {
            $config['string']['date_format'] = $configFromFile['LOG']['date_format'];
        }

        if ($config['int']['cleanup_fileage'] != $configFromFile['LOG']['cleanup_fileage']) {
            $config['int']['cleanup_fileage'] = $configFromFile['LOG']['cleanup_fileage'];
        }

        if ($config['string']['use_spooldir'] != $configFromFile['NAGIOS']['use_spooldir']) {
            $config['string']['use_spooldir'] = $configFromFile['NAGIOS']['use_spooldir'];
        }

        if ($config['int']['max_checks'] != $configFromFile['NAGIOS']['max_checks']) {
            $config['int']['max_checks'] = $configFromFile['NAGIOS']['max_checks'];
        }

        if ($config['int']['max_threads'] != $configFromFile['CPU']['max_threads']) {
            $config['int']['max_threads'] = $configFromFile['CPU']['max_threads'];
        }

        if ($config['string']['ssh_username'] != $configFromFile['SSH']['username']) {
            $config['string']['ssh_username'] = $configFromFile['SSH']['username'];
        }

        if ($config['string']['private_path'] != $configFromFile['SSH']['private_path']) {
            $config['string']['private_path'] = $configFromFile['SSH']['private_path'];
        }

        if ($config['string']['public_path'] != $configFromFile['SSH']['public_path']) {
            $config['string']['public_path'] = $configFromFile['SSH']['public_path'];
        }

        if ($config['string']['port_range'] != $configFromFile['SSH']['port_range']) {
            $config['string']['port_range'] = $configFromFile['SSH']['port_range'];
        }

        if ($config['int']['ssh_port'] != $configFromFile['SSH']['port']) {
            $config['int']['ssh_port'] = $configFromFile['SSH']['port'];
        }

        if ($config['bool']['use_ssh_tunnel'] != $configFromFile['SSH']['use_ssh_tunnel']) {
            $config['bool']['use_ssh_tunnel'] = $configFromFile['SSH']['use_ssh_tunnel'];
        }

        if ($config['string']['supervisor_username'] != $configFromFile['SUPERVISOR']['username']) {
            $config['string']['supervisor_username'] = $configFromFile['SUPERVISOR']['username'];
        }

        if ($config['string']['supervisor_password'] != $configFromFile['SUPERVISOR']['password']) {
            $config['string']['supervisor_password'] = $configFromFile['SUPERVISOR']['password'];
        }

        if ($config['bool']['synchronize_time'] != $configFromFile['TSYNC']['synchronize_time']) {
            $config['bool']['synchronize_time'] = $configFromFile['TSYNC']['synchronize_time'];
        }

        if ($config['string']['tsync_every'] != $configFromFile['TSYNC']['every']) {
            $config['string']['tsync_every'] = $configFromFile['TSYNC']['every'];
        }

        return $config;
    }

}
