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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace itnovum\openITCOCKPIT\Core\MonitoringEngine;

use Cake\Core\Configure;
use itnovum\openITCOCKPIT\CakePHP\File;

/**
 * Class NagiosConfigDefaults
 * @package itnovum\openITCOCKPIT\Core\MonitoringEngine
 */
class NagiosConfigDefaults {

    /**
     * @var array
     */
    private $conf = [];

    private $defaultFiles = [
        '8147201e91c4dcf7c016ba2ddeac3fd7e72edacc' => 'defaultHost',
        '689bfdd01af8a21c4a4706c5117849c2fc2c3f38' => 'defaultService',
        '59132ffe6197bee769d97779a14140cfb890fd7b' => 'default24x7',
        'b6f6cd2bf046d23cc2a49fd2e7fb82251d8fdb75' => 'defaultHostcheck',
        '69077998d62c4da5de0af5212a1c1df3c2a6e5fb' => 'defaultNone',
        '12f498cb0b48930be9bdfd598de58974f3f062d5' => 'defaultContact',
        '358db775dc4b1ecbc4e02a3d448d2f119f515269' => 'defaultNotificationCommand',
        '474d000935152d9c5a49ff5f2f998c5ce925b1ca' => 'service_perfdata_file_processing_command',
        '46c47b8b8836cfdb948dbb8c34bebb647b8ec0c8' => 'service_perfdata_command',
        '2106cf0bf26a82af262c4078e6d9f94eded84d2a' => 'check_fresh',
        '89fdde0b28373dc4f361cfb810b35342cc2c3232' => 'defaultContactgroup',
    ];

    public function __construct() {
        Configure::load('nagios');

        $this->conf = [
            'path'     => Configure::read('nagios.basepath') . Configure::read('nagios.etc') . Configure::read('nagios.export.config'),
            'suffix'   => Configure::read('nagios.export.suffix'),
            'defaults' => 'defaults/',
        ];
    }

    public function execute() {
        if (!is_dir($this->conf['path'] . $this->conf['defaults'])) {
            mkdir($this->conf['path'] . $this->conf['defaults']);
        }

        foreach ($this->defaultFiles as $defaultFile => $functionName) {
            $file = new File($this->conf['path'] . $this->conf['defaults'] . $defaultFile . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
                $content = $this->fileHeader();
                $content .= $this->{$functionName}();
                $file->write($content);
                $file->close();
            }
        }
    }

    public function fileHeader() {
        return "    ; ! DO NOT DELETE OR MODIFY THIS FILE                         !
    ; ! THIS DEFINITION IS A TEMPLATE FOR ALL HOSTS AND SERVICES  !
    ; ! DO NOT DELETE OR MODIFY THIS FILE                         !\n\n\n";
    }

    public function defaultHost() {
        return "
define host{
    name                               8147201e91c4dcf7c016ba2ddeac3fd7e72edacc ;generic-host
    notifications_enabled              1
    event_handler_enabled              1
    flap_detection_enabled             0
    process_perf_data                  0
    retain_status_information          1
    retain_nonstatus_information       1
    notification_period                59132ffe6197bee769d97779a14140cfb890fd7b
    register                           0
    max_check_attempts                 3
    check_command                      b6f6cd2bf046d23cc2a49fd2e7fb82251d8fdb75
    notification_period                69077998d62c4da5de0af5212a1c1df3c2a6e5fb
    notification_interval              7200
    notification_options               d,u,r
    check_period                       59132ffe6197bee769d97779a14140cfb890fd7b
    check_interval                     3600
    retry_interval                     60
    passive_checks_enabled             1
    initial_state                      u
}";
    }

    public function defaultService() {
        return "
define service{
    name                               689bfdd01af8a21c4a4706c5117849c2fc2c3f38 ;generic-service
    active_checks_enabled              1
    passive_checks_enabled             1
    parallelize_check                  1
    obsess_over_service                1
    check_freshness                    0
    notifications_enabled              1
    event_handler_enabled              1
    flap_detection_enabled             1
    process_perf_data                  0
    retain_status_information          1
    retain_nonstatus_information       1
    is_volatile                        0
    register                           0
    check_period                       59132ffe6197bee769d97779a14140cfb890fd7b
    max_check_attempts                 3
    check_interval                     300
    retry_interval                     60
    notification_options               w,u,c,r
    notification_interval              7200
    notification_period                59132ffe6197bee769d97779a14140cfb890fd7b
    register                           0
    initial_state                      u
}";
    }

    public function default24x7() {
        return "
define timeperiod{
    timeperiod_name                    59132ffe6197bee769d97779a14140cfb890fd7b
    alias                              24x7
    sunday                             00:00-24:00
    monday                             00:00-24:00
    tuesday                            00:00-24:00
    wednesday                          00:00-24:00
    thursday                           00:00-24:00
    friday                             00:00-24:00
    saturday                           00:00-24:00
}";
    }

    public function defaultHostcheck() {
        return "
define command{
    command_name                       b6f6cd2bf046d23cc2a49fd2e7fb82251d8fdb75
    command_line                       echo 'No host check defined. This is the fallback host check please check your configuration'
}
";
    }

    public function defaultNone() {
        return "
define timeperiod{
    timeperiod_name                    69077998d62c4da5de0af5212a1c1df3c2a6e5fb
    alias                              none
}";
    }

    public function defaultContact() {
        return "
define contact{
    contact_name                       12f498cb0b48930be9bdfd598de58974f3f062d5
    alias                              none
    service_notification_period        59132ffe6197bee769d97779a14140cfb890fd7b
    host_notification_period           59132ffe6197bee769d97779a14140cfb890fd7b
    service_notification_options       u
    host_notification_options          u
    service_notification_commands      358db775dc4b1ecbc4e02a3d448d2f119f515269
    host_notification_commands         358db775dc4b1ecbc4e02a3d448d2f119f515269
    email                              openitcockpit@localhost
}";
    }

    public function defaultNotificationCommand() {
        return "
define command{
    command_name                       358db775dc4b1ecbc4e02a3d448d2f119f515269
    command_line                       /bin/true
}";
    }

    public function service_perfdata_file_processing_command() {
        return "
define command{
    command_name                       474d000935152d9c5a49ff5f2f998c5ce925b1ca
    command_line                       " . Configure::read('nagios.export.service_perfdata_file_processing_command') . "
}";
    }

    public function service_perfdata_command() {
        return "
define command{
    command_name                       46c47b8b8836cfdb948dbb8c34bebb647b8ec0c8
    command_line                       " . Configure::read('nagios.export.service_perfdata_command') . "
}";
    }

    public function check_fresh() {
        return "
define command{
    command_name                       2106cf0bf26a82af262c4078e6d9f94eded84d2a
    command_line                       " . Configure::read('nagios.export.check_fresh') . "
	}";
    }

    public function defaultContactgroup() {
        return "
define contactgroup{
    contactgroup_name                 89fdde0b28373dc4f361cfb810b35342cc2c3232
    alias                             89fdde0b28373dc4f361cfb810b35342cc2c3232
    members                           12f498cb0b48930be9bdfd598de58974f3f062d5
}";
    }
}
