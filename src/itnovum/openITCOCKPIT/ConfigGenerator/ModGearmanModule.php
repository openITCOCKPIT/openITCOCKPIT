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


class ModGearmanModule extends ConfigGenerator implements ConfigInterface {

    protected $templateDir = 'nagios';

    protected $template = 'mod_gearman_neb.conf';

    /**
     * @var string
     */
    protected $linkedOutfile = '/opt/openitc/etc/mod_gearman/mod_gearman_neb.conf';

    /**
     * @var string
     */
    protected $realOutfile = '/opt/openitc/etc/mod_gearman/mod_gearman_neb.conf';

    /**
     * @var string
     */
    protected $commentChar = '#';

    // booleans can be "yes", "on", "true" or "1"
    // https://github.com/sni/mod_gearman/blob/b91e29261616d891e7b3727bd13b8f454b10b2c8/common/utils.c#L367-L383

    protected $defaults = [
        'bool' => [
            'distribute_eventhandler'  => 0,
            'distribute_notifications' => 0,
            'distribute_services'      => 1,
            'distribute_hosts'         => 1,
            'export_perfdata'          => 0,

            'enable_encryption' => 1,
            'use_uniq_jobs'     => 1,

        ],

        'int' => [
            'result_workers' => 1,
        ],

        'string' => [
            'debug_level'                => '0',     // We use a string because of the selectbox
            'orphaned_checks_returncode' => '2',     // We use a string because of the selectbox
            'localhostgroups'            => 'empty', // This is a workaround as the config generator does currently not support empty values
            'localservicegroups'         => 'empty'  // This is a workaround as the config generator does currently not support empty values
        ],
    ];

    protected $dbKey = 'ModGearmanModule';


    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        //$error = [];
        //$fakeModelName = 'Configfile';
        //if (isset($data['string']) && is_array($data['string'])) {
        //    foreach ($data['string'] as $field => $value) {
        //        if ($field === 'service_check_timeout_state') {
        //            if (!in_array($value, ['c', 'u', 'w', 'o'], true)) {
        //                $error[$fakeModelName][$field][] = __('Value out of range (c, u, w, o)');
        //            }
        //        }
        //    }
        //}

        //if (!empty($error)) {
        //    return $error;
        //}

        return true;
    }

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'modgearman-module';
    }

    public function getDefaults() {
        $default = parent::getDefaults();
        return $default;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'distribute_eventhandler'  => 'Determine if execution of eventhandlers should distribute across multiple workers. ',
            'distribute_notifications' => 'Determine if execution of notification scripts should distribute across multiple workers. (Not recommended)',
            'distribute_services'      => 'Determine if execution of service checks should distribute across multiple workers.',
            'distribute_hosts'         => 'Determine if execution of host checks should distribute across multiple workers.',
            'export_perfdata'          => 'If enabled, Mod-Gearman will export performance data into the \'perfdata\' queue. This can be useful if you need to process performance data with an external program.',

            'enable_encryption' => 'By default all messages are AES-256-ECB encrypted. The key can be found in the file /opt/openitc/etc/mod_gearman/secret.file',
            'use_uniq_jobs'     => 'Using uniq keys prevents the gearman queues from filling up when there is no worker. However, gearmand seems to have problems with the uniq key and sometimes jobs get stuck in the queue. Disable this option when you run into problems with stuck jobs but make sure your worker are running.',

            'debug_level'                => 'Increase the verbosity of the module. 0 = only errors, 1 = debug messages, 2 = trace messages, 3 = trace and all gearman related logs will be printed to stdout.',
            'result_workers'             => 'Number of result worker threads.',
            'orphaned_checks_returncode' => 'Set return code of orphaned checks.',

            'localhostgroups'    => 'A comma separated list of host group UUIDs to bypass Mod-Gearman. It is recommended to use the WORKER custom variable instead',
            'localservicegroups' => 'A comma separated list of service group UUIDs to bypass Mod-Gearman. It is recommended to use the WORKER custom variable instead',
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

        return $this->saveConfigFile($configToExport);
    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        // Currently there is no migration for existing mod_gearman_neb.conf files
        // Just return our default values
        return $this->mergeDbResultWithDefaultConfiguration($dbRecords);
    }

}
