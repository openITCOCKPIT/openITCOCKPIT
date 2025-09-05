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

namespace App\itnovum\openITCOCKPIT\Monitoring\Naemon;


use App\Model\Entity\Service;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\Downtime;

class ExternalCommands {

    /**
     * @var array
     */
    private $gearmanConfig;

    public function __construct() {
        Configure::load('gearman');
        $this->gearmanConfig = Configure::read('gearman');

    }

    /**
     * Create an external command to reschedule a host or the host with all Services
     * ### Options
     * - `uuid`            The UUID of the host you want to reschedule
     * - `type`            The type of the external command ('hostOnly' or 'hostAndServices')
     * - `satellite_id`    The id of the satellite system
     *
     * @param array $options with the options
     * @param int $timestamp timestamp, when Naemon should reschedule the host
     */
    public function rescheduleHost(array $options = [], $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $host = $HostsTable->getHostForRescheduling($options['uuid']);

        if (empty($host)) {
            // Host not found
            return false;
        }

        if ($options['type'] == 'hostOnly') {
            //SCHEDULE_FORCED_HOST_CHECK
            // ITC-2525
            if ($host->getValueOf('active_checks_enabled')) {
                $payload = [
                    'Command' => 'schedule_check',
                    'Data'    => [
                        'host_name'     => $options['uuid'],
                        'schedule_time' => $timestamp
                    ]
                ];
                $this->toQueue($payload, $options['satellite_id']);
            }
        } else {
            //SCHEDULE_FORCED_HOST_CHECK
            // ITC-2525
            if ($host->getValueOf('active_checks_enabled')) {
                $payload = [
                    'Command' => 'schedule_check',
                    'Data'    => [
                        'host_name'     => $options['uuid'],
                        'schedule_time' => $timestamp
                    ]
                ];
                $this->toQueue($payload, $options['satellite_id']);
            }

            //SCHEDULE_FORCED_HOST_SVC_CHECKS
            // ITC-2525
            // The issue with SCHEDULE_FORCED_HOST_SVC_CHECKS is, that it will also reschedule passive services
            // this will lead to unknown service status

            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');
            $services = $ServicesTable->getServicesForRescheduling($options['uuid']);
            foreach ($services as $service) {
                /** @var Service $service */
                if ($service->getValueOf('active_checks_enabled')) {
                    $payload = [
                        'Command' => 'raw',
                        'Data'    => sprintf('SCHEDULE_FORCED_SVC_CHECK;%s;%s;%s', $options['uuid'], $service->get('uuid'), $timestamp)
                    ];
                    $this->toQueue($payload, $options['satellite_id']);
                }
            }
        }
        return true;
    }

    /**
     * Transfer a passive host check result to Naemon
     * ### Options
     * - `uuid`              The UUID of the host you want to submit the checresult
     * - `comment`           The comment for the passive check result (Example: 'test alert')
     * - `state`             The state of the passive checl result (0, 1 or 2)
     * - `forceHardstate`    If the Host should be forced into hard state (for testing notifications) (values: 1, 0,
     * ture or false)
     * - `repetitions`       the number of repetitions as interger value (normaly the number of max_check_attempts to
     * force hard state)
     *
     * @param array $options with the options
     * @return bool
     */
    public function passiveTransferHostCheckresult(array $options) {
        $_options = [
            'comment'        => 'No comment given',
            'long_output'    => '',
            'state'          => 2,
            'forceHardstate' => 0,
            'repetitions'    => 1
        ];
        $options = Hash::merge($_options, $options);

        $options['forceHardstate'] = (int)$options['forceHardstate'];
        $options['repetitions'] = (int)$options['repetitions'];

        if (!isset($options['satellite_id'])) {
            if (Plugin::isLoaded('DistributeModule')) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $satelliteId = $HostsTable->getSatelliteIdByUuid($options['uuid']);
                if ($satelliteId === null) {
                    //Host not found
                    return false;
                }
                $options['satellite_id'] = $satelliteId;
            } else {
                $options['satellite_id'] = 0;
            }
        }

        if ($options['forceHardstate'] === 1 && $options['repetitions'] >= 1) {
            for ($i = 0; $i < $options['repetitions']; $i++) {
                //PROCESS_HOST_CHECK_RESULT
                $payload = [
                    'Command' => 'check_result',
                    'Data'    => [
                        'host_name'     => $options['uuid'],
                        'output'        => $options['comment'],
                        'long_output'   => $options['long_output'],
                        'check_type'    => 1, //https://github.com/naemon/naemon-core/blob/cec6e10cbee9478de04b4cf5af29e83d47b5cfd9/src/naemon/common.h#L330-L334
                        'return_code'   => $options['state'],
                        'start_time'    => time(),
                        'end_time'      => time(),
                        'early_timeout' => 0,
                        'latency'       => 0,
                        'exited_ok'     => 1
                    ]
                ];
                $this->toQueue($payload, $options['satellite_id']);
            }
        } else {
            //PROCESS_HOST_CHECK_RESULT
            $payload = [
                'Command' => 'check_result',
                'Data'    => [
                    'host_name'     => $options['uuid'],
                    'output'        => $options['comment'],
                    'long_output'   => $options['long_output'],
                    'check_type'    => 1, //https://github.com/naemon/naemon-core/blob/cec6e10cbee9478de04b4cf5af29e83d47b5cfd9/src/naemon/common.h#L330-L334
                    'return_code'   => $options['state'],
                    'start_time'    => time(),
                    'end_time'      => time(),
                    'early_timeout' => 0,
                    'latency'       => 0,
                    'exited_ok'     => 1
                ]
            ];
            $this->toQueue($payload, $options['satellite_id']);
        }
        return true;
    }


    /**
     * Transfer a passive service check result to Naemon
     * ### Options
     * - `hostUuid`            The UUID of the host
     * - `serviceUuid`         The UUID of the service you want to submit the checkresult
     * - `comment`             The comment for the passive check result (Example: 'test alert')
     * - `state`               The state of the passive check result (0, 1, 2 or 3)
     * - `forceHardstate`      If the Host should be forced into hard state (for testing notifications) (values: 1, 0,
     * ture or false)
     * - `repetitions`         the number of repetitions as interger value (normaly the number of max_check_attempts to
     * force hard state)
     *
     * @param array $options with the options
     * @return bool
     */
    public function passiveTransferServiceCheckresult(array $options) {
        $_options = [
            'comment'        => 'No comment given',
            'long_output'    => '',
            'state'          => 3,
            'forceHardstate' => 0,
            'repetitions'    => 1
        ];
        $options = Hash::merge($_options, $options);

        $options['forceHardstate'] = (int)$options['forceHardstate'];
        $options['repetitions'] = (int)$options['repetitions'];

        if (!isset($options['satellite_id'])) {
            if (Plugin::isLoaded('DistributeModule')) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $satelliteId = $HostsTable->getSatelliteIdByUuid($options['hostUuid']);
                if ($satelliteId === null) {
                    //Host not found
                    return false;
                }
                $options['satellite_id'] = $satelliteId;
            } else {
                $options['satellite_id'] = 0;
            }
        }

        if ($options['forceHardstate'] === 1 && $options['repetitions'] >= 1) {
            for ($i = 0; $i < $options['repetitions']; $i++) {
                //PROCESS_SERVICE_CHECK_RESULT
                $payload = [
                    'Command' => 'check_result',
                    'Data'    => [
                        'host_name'           => $options['hostUuid'],
                        'service_description' => $options['serviceUuid'],
                        'output'              => $options['comment'],
                        'long_output'         => $options['long_output'],
                        'check_type'          => 1, //https://github.com/naemon/naemon-core/blob/cec6e10cbee9478de04b4cf5af29e83d47b5cfd9/src/naemon/common.h#L330-L334
                        'return_code'         => $options['state'],
                        'start_time'          => time(),
                        'end_time'            => time(),
                        'early_timeout'       => 0,
                        'latency'             => 0,
                        'exited_ok'           => 1
                    ]
                ];
                $this->toQueue($payload, $options['satellite_id']);
            }
        } else {
            //PROCESS_SERVICE_CHECK_RESULT
            $payload = [
                'Command' => 'check_result',
                'Data'    => [
                    'host_name'           => $options['hostUuid'],
                    'service_description' => $options['serviceUuid'],
                    'output'              => $options['comment'],
                    'long_output'         => $options['long_output'],
                    'check_type'          => 1, //https://github.com/naemon/naemon-core/blob/cec6e10cbee9478de04b4cf5af29e83d47b5cfd9/src/naemon/common.h#L330-L334
                    'return_code'         => $options['state'],
                    'start_time'          => time(),
                    'end_time'            => time(),
                    'early_timeout'       => 0,
                    'latency'             => 0,
                    'exited_ok'           => 1
                ]
            ];
            $this->toQueue($payload, $options['satellite_id']);
        }
        return true;
    }


    /**
     * Enables or disabled the flap detection for the given host UUID
     * ### Options
     * - `uuid`            The UUID of the host you want to enable/disable flap detection
     * - `condition`       1 = enable or 0 disable flap detection
     *
     * @param array $options with the options
     */
    public function enableOrDisableHostFlapdetection(array $options) {
        $_options = ['condition' => 1];
        $options = Hash::merge($_options, $options);

        $options['condition'] = (int)$options['condition'];

        if ($options['condition'] === 1) {
            $payload = [
                'Command' => 'raw',
                'Data'    => sprintf('ENABLE_HOST_FLAP_DETECTION;%s', $options['uuid'])
            ];
            $this->toQueue($payload, 0);
            return true;
        }

        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf('DISABLE_HOST_FLAP_DETECTION;%s', $options['uuid'])
        ];
        $this->toQueue($payload, 0);
        return true;
    }

    /**
     * Enables or disabled the flap detection for the given UUID
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service you want to enable/disable flap detection
     * - `condition`       1 = enable or 0 disable flap detection
     *
     * @param array $options with the options
     */
    public function enableOrDisableServiceFlapdetection(array $options) {
        $_options = ['condition' => 1];
        $options = Hash::merge($_options, $options);

        $options['condition'] = (int)$options['condition'];

        if ($options['condition'] === 1) {
            $payload = [
                'Command' => 'raw',
                'Data'    => sprintf('ENABLE_SVC_FLAP_DETECTION;%s;%s', $options['hostUuid'], $options['serviceUuid'])
            ];
            $this->toQueue($payload, 0);
            return true;
        }

        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf('DISABLE_SVC_FLAP_DETECTION;%s;%s', $options['hostUuid'], $options['serviceUuid'])
        ];
        $this->toQueue($payload, 0);
        return true;
    }

    /**
     * Create an external command to reschedule a service of an host
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service you want to reschedule
     * - `satellite_id`    The satellite_id
     *
     * @param array $options with the options
     * @param int $timestamp when Naemon should reschedule the host
     */
    public function rescheduleService(array $options, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }

        //SCHEDULE_FORCED_SVC_CHECK
        // ITC-2525
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $service = $ServicesTable->getServiceForRescheduling($options['serviceUuid']);
        if (empty($service)) {
            // Service not found
            return false;
        }

        if ($service->getValueOf('active_checks_enabled')) {
            $payload = [
                'Command' => 'schedule_check',
                'Data'    => [
                    'host_name'           => $options['hostUuid'],
                    'service_description' => $options['serviceUuid'],
                    'schedule_time'       => $timestamp,
                ]
            ];
            $this->toQueue($payload, $options['satellite_id']);
        }
        return true;
    }

    /**
     * Send a custom host notification
     * ### Options
     * - `hostUuid`    The UUID of the host you want to send a notification
     * - `type`        The notification type (0 = default, 1 = broadcast, 2 = forced, 3 = broadcast and forced)
     * - `author`      The author of the message
     * - `comment`     The comment of the message
     *
     * @param array $options with the options
     * @link https://assets.nagios.com/downloads/nagioscore/docs/externalcmds/cmdinfo.php?command_id=134
     */
    public function sendCustomHostNotification(array $options) {
        $type = (int)$options['type'];

        //SEND_CUSTOM_HOST_NOTIFICATION
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'SEND_CUSTOM_HOST_NOTIFICATION;%s;%s;%s;%s',
                $options['hostUuid'],
                $type,
                $options['author'],
                $options['comment']
            )
        ];
        $this->toQueue($payload, 0);
        return true;
    }

    /**
     * Send a custom service notification
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service you want to send a notification
     * - `type`            The notification type (0 = default, 1 = broadcast, 2 = forced, 3 = broadcast and forced
     * - `author`          The author of the message
     * - `comment`         The comment of the message
     *
     * @param array $options with the options
     * @link https://assets.nagios.com/downloads/nagioscore/docs/externalcmds/cmdinfo.php?command_id=134
     */
    public function sendCustomServiceNotification(array $options) {
        $type = (int)$options['type'];

        //SEND_CUSTOM_SVC_NOTIFICATION
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'SEND_CUSTOM_SVC_NOTIFICATION;%s;%s;%s;%s;%s',
                $options['hostUuid'],
                $options['serviceUuid'],
                $type,
                $options['author'],
                $options['comment']
            )
        ];
        $this->toQueue($payload, 0);
        return true;
    }

    /**
     * Set an acknowledgment for the given host
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `author`          The author of the ack
     * - `comment`         The comment of the ack
     * - `sticky`          Integer if sticky or not (0 or 2)
     * - `type`            The type of the external command ('hostOnly' or 'hostAndServices')
     *
     * @param array $options with the options
     * @return bool
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function setHostAck(array $options) {
        $_options = [
            'type'   => 'hostOnly',
            'notify' => true,
        ];

        $options = Hash::merge($_options, $options);

        if (!isset($options['satellite_id'])) {
            if (Plugin::isLoaded('DistributeModule')) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $satelliteId = $HostsTable->getSatelliteIdByUuid($options['hostUuid']);
                if ($satelliteId === null) {
                    //Host not found
                    return false;
                }
                $options['satellite_id'] = $satelliteId;
            } else {
                $options['satellite_id'] = 0;
            }
        }

        $notify = 1;
        if (!$options['notify']) {
            $notify = 0;
        }

        // ACKNOWLEDGE_HOST_PROBLEM
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'ACKNOWLEDGE_HOST_PROBLEM;%s;%s;%s;1;%s;%s',
                $options['hostUuid'],
                $options['sticky'],
                $notify,
                $options['author'],
                $options['comment']
            )
        ];

        //Set Host Ack on the Mastersystem
        $this->toQueue($payload, 0);
        if ($options['satellite_id'] > 0) {
            //Also set Host Ack on the Satellite system
            $this->toQueue($payload, $options['satellite_id']);
        }


        if ($options['type'] == 'hostAndServices') {
            //Set ACK for host + services

            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            try {
                $host = $HostsTable->getHostWithServicesByUuid($options['hostUuid']);
            } catch (RecordNotFoundException $e) {
                Log::error(sprintf('SudoServer: No host with uuid "%s" found!', $options['uuid']));
                return false;
            }

            $DbBackend = new DbBackend();
            $ServicestatusTable = $DbBackend->getServicestatusTable();
            $ServicestatusFields = new ServicestatusFields($DbBackend);
            $ServicestatusFields->currentState();

            $serviceUuids = [];
            foreach ($host->get('services') as $service) {
                /** @var Service $service */
                $serviceUuids[] = $service->get('uuid');
            }

            if (!empty($serviceUuids)) {
                $servicestatus = $ServicestatusTable->byUuids($serviceUuids, $ServicestatusFields);

                foreach ($serviceUuids as $serviceUuid) {
                    if (isset($servicestatus[$serviceUuid]['Servicestatus']['current_state'])) {
                        if ($servicestatus[$serviceUuid]['Servicestatus']['current_state'] > 0) {
                            $this->setServiceAck([
                                'hostUuid'     => $options['hostUuid'],
                                'serviceUuid'  => $serviceUuid,
                                'author'       => $options['author'],
                                'comment'      => $options['comment'],
                                'sticky'       => $options['sticky'],
                                'notify'       => (bool)$notify,
                                'satellite_id' => $options['satellite_id']
                            ]);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Set an acknowledgment for the given service
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service you want to acknowledge
     * - `author`          The author of the ack
     * - `comment`         The comment of the ack
     * - `sticky`          Integer if sticky or not (0 or 2)
     *
     * @param array $options with the options
     * @return bool
     */
    public function setServiceAck(array $options) {
        if (!isset($options['satellite_id'])) {
            if (Plugin::isLoaded('DistributeModule')) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $satelliteId = $HostsTable->getSatelliteIdByUuid($options['hostUuid']);
                if ($satelliteId === null) {
                    //Host not found
                    return false;
                }
                $options['satellite_id'] = $satelliteId;
            } else {
                $options['satellite_id'] = 0;
            }
        }

        $notify = 1;
        if (isset($options['notify'])) {
            if (!$options['notify']) {
                $notify = 0;
            }
        }

        // ACKNOWLEDGE_SVC_PROBLEM
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'ACKNOWLEDGE_SVC_PROBLEM;%s;%s;%s;%s;1;%s;%s',
                $options['hostUuid'],
                $options['serviceUuid'],
                $options['sticky'],
                $notify,
                $options['author'],
                $options['comment']
            )
        ];

        //Set Service Ack on the Mastersystem
        $this->toQueue($payload, 0);
        if ($options['satellite_id'] > 0) {
            //Also set Service Ack on the Satellite system
            $this->toQueue($payload, $options['satellite_id']);
        }

        return true;
    }


    /**
     * Set an downtime for the given host
     * ### Options
     * - `hostUuid`       The UUID of the host you want to set a downtime for
     * - `start`          Start time as unix timestamp
     * - `end`            End time as unix timestamp
     * - `duration`       Duration of the downtime (auto calc if empty!)
     * - `author`         The author of the downtime
     * - `comment`        The comment of the downtime
     * - `downtimetype`   The type of the downtime as int (0 => default, 1 => 'Host inc. services, 2 => triggered, 3 =>
     * non-triggered)
     *
     * @param array $options with the options
     * @return bool
     */
    public function setHostDowntime(array $options) {
        $_options = [
            'duration'     => $options['end'] - $options['start'],
            'downtimetype' => 0,
        ];

        $options = Hash::merge($_options, $options);

        if (!isset($options['satellite_id'])) {
            if (Plugin::isLoaded('DistributeModule')) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $satelliteId = $HostsTable->getSatelliteIdByUuid($options['hostUuid']);
                if ($satelliteId === null) {
                    //Host not found
                    return false;
                }
                $options['satellite_id'] = $satelliteId;
            } else {
                $options['satellite_id'] = 0;
            }
        }

        switch ($options['downtimetype']) {
            case 0:
                //Host only

                //SCHEDULE_HOST_DOWNTIME
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        'SCHEDULE_HOST_DOWNTIME;%s;%s;%s;1;0;%s;%s;%s',
                        $options['hostUuid'],
                        $options['start'],
                        $options['end'],
                        $options['duration'],
                        $options['author'],
                        $options['comment']
                    )
                ];

                //Set Host downtime on the Mastersystem
                $this->toQueue($payload, 0);
                if ($options['satellite_id'] > 0) {
                    //Also set Host downtime on the Satellite system
                    $this->toQueue($payload, $options['satellite_id']);
                }

                break;

            case 1:
                //Host inc services
                //SCHEDULE_HOST_DOWNTIME
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        'SCHEDULE_HOST_DOWNTIME;%s;%s;%s;1;0;%s;%s;%s',
                        $options['hostUuid'],
                        $options['start'],
                        $options['end'],
                        $options['duration'],
                        $options['author'],
                        $options['comment']
                    )
                ];
                //Set Host downtime on the Mastersystem
                $this->toQueue($payload, 0);
                if ($options['satellite_id'] > 0) {
                    //Also set Host downtime on the Satellite system
                    $this->toQueue($payload, $options['satellite_id']);
                }

                //SCHEDULE_HOST_SVC_DOWNTIME
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        'SCHEDULE_HOST_SVC_DOWNTIME;%s;%s;%s;1;0;%s;%s;%s',
                        $options['hostUuid'],
                        $options['start'],
                        $options['end'],
                        $options['duration'],
                        $options['author'],
                        $options['comment']
                    )
                ];
                //Set Host+Services downtime on the Mastersystem
                $this->toQueue($payload, 0);
                if ($options['satellite_id'] > 0) {
                    //Also set Host+Services downtime on the Satellite system
                    $this->toQueue($payload, $options['satellite_id']);
                }
                break;

            case 2:
                //Host triggered
                //SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME;%s;%s;%s;1;0;%s;%s;%s',
                        $options['hostUuid'],
                        $options['start'],
                        $options['end'],
                        $options['duration'],
                        $options['author'],
                        $options['comment']
                    )
                ];
                //Set Host downtime on the Mastersystem
                $this->toQueue($payload, 0);
                if ($options['satellite_id'] > 0) {
                    //Also set Host downtime on the Satellite system
                    $this->toQueue($payload, $options['satellite_id']);
                }
                break;

            case 3:
                //Host non triggerd
                //SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME;%s;%s;%s;1;0;%s;%s;%s',
                        $options['hostUuid'],
                        $options['start'],
                        $options['end'],
                        $options['duration'],
                        $options['author'],
                        $options['comment']
                    )
                ];
                //Set Host downtime on the Mastersystem
                $this->toQueue($payload, 0);
                if ($options['satellite_id'] > 0) {
                    //Also set Host downtime on the Satellite system
                    $this->toQueue($payload, $options['satellite_id']);
                }
                break;
        }
        return true;
    }

    /**
     * Set an downtime for the given hostgroup
     * ### Options
     * - `hostgroupUuid`    The UUID of the hostgroup you want to set a downtime for
     * - `start`            Start time as unix timestamp
     * - `end`              End time as unix timestamp
     * - `duration`         Duration of the downtime (auto calc if empty!)
     * - `author`           The author of the downtime
     * - `comment`          The comment of the downtime
     * - `downtimetype`     The type of the downtime as int (0 => hosts only, 1 => 'Host inc. services)
     *
     * @param array $options with the options
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function setHostgroupDowntime(array $options) {
        $_options = [
            'duration'     => $options['end'] - $options['start'],
            'downtimetype' => 0,
        ];

        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        $hostgroup = $HostgroupsTable->getHostsByHostgroupUuidForExternalcommandsIncludeingHosttemplateHosts(
            $options['hostgroupUuid']
        );

        if (isset($hostgroup['hosts'])) {
            foreach ($hostgroup['hosts'] as $host) {
                $this->setHostDowntime([
                    'hostUuid'     => $host['uuid'],
                    'start'        => $options['start'],
                    'end'          => $options['end'],
                    'comment'      => $options['comment'],
                    'author'       => $options['author'],
                    'downtimetype' => $options['downtimetype'],
                    'satellite_id' => $host['satellite_id']
                ]);
            }
        }
    }

    /**
     * Set an downtime for the given service
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service you want set a downtime for
     * - `start`           Start time as unix timestamp
     * - `end`             End time as unix timestamp
     * - `duration`        Duration of the downtime (auto calc if empty!)
     * - `author`          The author of the downtime
     * - `comment`         The comment of the downtime
     *
     * @param array $options with the options
     * @return bool
     */
    public function setServiceDowntime(array $options) {
        $_options['duration'] = $options['end'] - $options['start'];
        $options = Hash::merge($_options, $options);

        if (!isset($options['satellite_id'])) {
            if (Plugin::isLoaded('DistributeModule')) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $satelliteId = $HostsTable->getSatelliteIdByUuid($options['hostUuid']);
                if ($satelliteId === null) {
                    //Host not found
                    return false;
                }
                $options['satellite_id'] = $satelliteId;
            } else {
                $options['satellite_id'] = 0;
            }
        }

        //SCHEDULE_SVC_DOWNTIME
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'SCHEDULE_SVC_DOWNTIME;%s;%s;%s;%s;1;0;%s;%s;%s',
                $options['hostUuid'],
                $options['serviceUuid'],
                $options['start'],
                $options['end'],
                $options['duration'],
                $options['author'],
                $options['comment']
            )
        ];
        //Set Service downtime on the Mastersystem
        $this->toQueue($payload, 0);
        if ($options['satellite_id'] > 0) {
            //Also set Service downtime on the Satellite system
            $this->toQueue($payload, $options['satellite_id']);
        }
        return true;
    }

    /**
     * @param $options
     */
    public function setContainerDowntime(array $options) {
        $_options = [
            'duration'     => $options['end'] - $options['start'],
            'downtimetype' => 0,
        ];

        $options = Hash::merge($_options, $options);

        $hostUuids = [];
        if (!empty($options['hostUuids'])) {
            $hostUuids = $options['hostUuids'];
        }

        switch ($options['downtimetype']) {
            case 0:
                //Host only
                foreach ($hostUuids as $hostUuid) {
                    $this->setHostDowntime([
                        'hostUuid'     => $hostUuid,
                        'start'        => $options['start'],
                        'end'          => $options['end'],
                        'comment'      => $options['comment'],
                        'author'       => $options['author'],
                        'downtimetype' => 0,
                    ]);
                }

                break;
            case 1:
                //Host inc services
                foreach ($hostUuids as $hostUuid) {
                    $this->setHostDowntime([
                        'hostUuid'     => $hostUuid,
                        'start'        => $options['start'],
                        'end'          => $options['end'],
                        'comment'      => $options['comment'],
                        'author'       => $options['author'],
                        'downtimetype' => 1,
                    ]);
                }

                break;
            default:
                foreach ($hostUuids as $hostUuid) {
                    $this->setHostDowntime([
                        'hostUuid'     => $hostUuid,
                        'start'        => $options['start'],
                        'end'          => $options['end'],
                        'comment'      => $options['comment'],
                        'author'       => $options['author'],
                        'downtimetype' => $options['downtimetype'],
                    ]);
                }
                break;
        }

    }

    /**
     * Create an external command to disable host or host + services notifications
     * ### Options
     * - `uuid`        The UUID of the host you want to reschedule
     * - `type`        The type of the external command ('hostOnly' or 'hostAndServices')
     *
     * @param array $options with the options
     */
    public function disableHostNotifications(array $options = []) {
        //DISABLE_HOST_NOTIFICATIONS
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'DISABLE_HOST_NOTIFICATIONS;%s',
                $options['uuid']
            )
        ];
        $this->toQueue($payload, 0);

        if ($options['type'] == 'hostAndServices') {
            //DISABLE_HOST_SVC_NOTIFICATIONS
            $payload = [
                'Command' => 'raw',
                'Data'    => sprintf(
                    'DISABLE_HOST_SVC_NOTIFICATIONS;%s',
                    $options['uuid']
                )
            ];
            $this->toQueue($payload, 0);
        }
        return true;
    }

    /**
     * Create an external command to enable host or host + services notifications
     * ### Options
     * - `uuid`        The UUID of the host
     * - `type`        The type of the external command ('hostOnly' or 'hostAndServices')
     *
     * @param array $options with the options
     */
    public function enableHostNotifications(array $options = []) {
        //ENABLE_HOST_NOTIFICATIONS
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'ENABLE_HOST_NOTIFICATIONS;%s',
                $options['uuid']
            )
        ];
        $this->toQueue($payload, 0);

        if ($options['type'] == 'hostAndServices') {
            //ENABLE_HOST_SVC_NOTIFICATIONS
            $payload = [
                'Command' => 'raw',
                'Data'    => sprintf(
                    'ENABLE_HOST_SVC_NOTIFICATIONS;%s',
                    $options['uuid']
                )
            ];
            $this->toQueue($payload, 0);

        }
        return true;
    }

    /**
     * Create an external command to disable service notifications of given service
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service
     *
     * @param array $options with the options
     */
    public function disableServiceNotifications($options = []) {
        //DISABLE_SVC_NOTIFICATIONS
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'DISABLE_SVC_NOTIFICATIONS;%s;%s',
                $options['hostUuid'],
                $options['serviceUuid']
            )
        ];
        $this->toQueue($payload, 0);
        return true;
    }

    /**
     * Create an external command to enable service notifications of given service
     * ### Options
     * - `hostUuid`        The UUID of the host
     * - `serviceUuid`     The UUID of the service
     *
     * @param array $options with the options
     */
    public function enableServiceNotifications(array $options = []): bool {
        //ENABLE_SVC_NOTIFICATIONS
        $payload = [
            'Command' => 'raw',
            'Data'    => sprintf(
                'ENABLE_SVC_NOTIFICATIONS;%s;%s',
                $options['hostUuid'],
                $options['serviceUuid']
            )
        ];
        $this->toQueue($payload, 0);
        return true;
    }

    /**
     * Delete a host downtime by given internal_downtime_id
     * ### Parameters
     * - `internal_downtime_id`        The internal id of the monitoring engine
     *
     * @param int $internal_downtime_id with the options
     * @param int $satellite_id
     * @param string $hostUuid
     * @param array $downtime the details of the downtime
     */
    public function deleteHostDowntime(int $internal_downtime_id, int $satellite_id, string $hostUuid, array $downtime) {
        if ($internal_downtime_id > 0) {
            //DEL_HOST_DOWNTIME
            $payload = [
                'Command' => 'delete_downtime',
                'Data'    => [
                    'host_name'  => $hostUuid,
                    'start_time' => $downtime['scheduledStartTime'],
                    'end_time'   => $downtime['scheduledEndTime'],
                    'comment'    => $downtime['commentData']
                ]
            ];

            // Delete downtime on the master system
            $this->toQueue($payload, 0);

            if ($satellite_id > 0) {
                // Delete downtime on the satellite system
                $this->toQueue($payload, $satellite_id);
            }
        }
    }

    /**
     * Delete a service downtime by give internal_downtime_id
     * ### Parameters
     * - `internal_downtime_id`        The internal id of the monitoring engine
     *
     * @param int $internal_downtime_id with the options
     * @param int $satellite_id
     * @param string $hostUuid
     * @param string $serviceUuid
     * @param array $downtime the details of the downtime
     */
    public function deleteServiceDowntime(int $internal_downtime_id, int $satellite_id, string $hostUuid, string $serviceUuid, array $downtime) {
        if ($internal_downtime_id > 0) {
            //DEL_SVC_DOWNTIME
            $payload = [
                'Command' => 'delete_downtime',
                'Data'    => [
                    'host_name'           => $hostUuid,
                    'service_description' => $serviceUuid,
                    'start_time'          => $downtime['scheduledStartTime'],
                    'end_time'            => $downtime['scheduledEndTime'],
                    'comment'             => $downtime['commentData']
                ]
            ];

            // Delete downtime on the master system
            $this->toQueue($payload, 0);

            if ($satellite_id > 0) {
                // Delete downtime on the satellite system
                $this->toQueue($payload, $satellite_id);
            }
        }
    }

    /**
     * Send a custom command to Statusengine Broker Module
     *
     * This is used by https://xxx.xxx.xxx.xxx/nagios_module/cmd/submit/ for example
     *
     * ### Parameters
     * - `command`        Name of the Naemon external command as string
     * - `parameters`     Parameters for this command as array (for implode(';', $parameters))
     *                    Info: Check Plugins/NagiosModule/CmdController.php function __externalCommands
     *
     * @param array $$payload with the options
     * @return bool
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function runCmdCommand(array $command) {
        $command['parameters'] = (array)$command['parameters'];
        $satelliteId = $command['satelliteId'] ?? null;

        if ($satelliteId === null && isset($command['parameters']['hostUuid'])) {
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $satelliteId = $HostsTable->getSatelliteIdByUuid($command['parameters']['hostUuid']);
            if ($satelliteId === null) {
                //Host not found
                return false;
            }
        }

        //Is this a command we also need to transfer to the satellite system?

        switch ($command['command']) {
            // Schedule downtime on the master or satellite system
            case 'SCHEDULE_HOST_DOWNTIME':
            case 'SCHEDULE_HOST_SVC_DOWNTIME':
            case 'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME':
            case 'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME':
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        '%s;%s;%s;%s;%s;%s;%s;%s;%s',
                        $command['command'],
                        $command['parameters']['hostUuid'],
                        $command['parameters']['start_time'],
                        $command['parameters']['end_time'],
                        $command['parameters']['fixed'],
                        $command['parameters']['trigger_id'],
                        $command['parameters']['duration'],
                        $command['parameters']['author'],
                        $command['parameters']['comment']
                    )
                ];

                // Create downtime on the master system
                $this->toQueue($payload, 0);
                if ($satelliteId > 0) {
                    //Also create downtime on the satellite system
                    $this->toQueue($payload, $satelliteId);
                }
                break;

            case 'SCHEDULE_SVC_DOWNTIME':
                $payload = [
                    'Command' => 'raw',
                    'Data'    => sprintf(
                        '%s;%s;%s;%s;%s;%s;%s;%s;%s;%s',
                        $command['command'],
                        $command['parameters']['hostUuid'],
                        $command['parameters']['serviceUuid'],
                        $command['parameters']['start_time'],
                        $command['parameters']['end_time'],
                        $command['parameters']['fixed'],
                        $command['parameters']['trigger_id'],
                        $command['parameters']['duration'],
                        $command['parameters']['author'],
                        $command['parameters']['comment']
                    )
                ];

                // Create downtime on the master system
                $this->toQueue($payload, 0);
                if ($satelliteId > 0) {
                    //Also create downtime on the satellite system
                    $this->toQueue($payload, $satelliteId);
                }
                break;


            case 'DEL_HOST_DOWNTIME':
                $DbBackend = new DbBackend();
                $DowntimeHostsTable = $DbBackend->getDowntimehistoryHostsTable();
                $DowntimeServicesTable = $DbBackend->getDowntimehistoryServicesTable();

                $downtime = $DowntimeHostsTable->getHostUuidWithDowntimeByInternalDowntimeId($command['parameters']['downtime_id']);
                $Downtime = new Downtime($downtime['DowntimeHosts']);
                if (empty($downtime)) {
                    //Downtime not found
                    return false;
                }

                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                try {
                    $host = $HostsTable->getHostByUuid($downtime['Hosts']['uuid']);
                } catch (RecordNotFoundException $e) {
                    //Host not found
                    return false;
                }

                //DEL_HOST_DOWNTIME
                $payload = [
                    'Command' => 'delete_downtime',
                    'Data'    => [
                        'host_name'  => $host->get('uuid'),
                        'start_time' => $Downtime->getScheduledStartTime(),
                        'end_time'   => $Downtime->getScheduledEndTime(),
                        'comment'    => $Downtime->getCommentData()
                    ]
                ];
                // Delete downtime on the master system
                $this->toQueue($payload, 0);
                if ($satelliteId > 0) {
                    //Also delete downtime on the satellite system
                    $this->toQueue($payload, $satelliteId);
                }

                if ($command['parameters']['include_services'] == 1) {
                    $servicesInternalDowntimeIds = $DowntimeServicesTable->getServiceDowntimesByHostAndDowntime(
                        $host->get('id'),
                        $Downtime
                    );

                    //Delete corresponding service downtimes
                    foreach ($servicesInternalDowntimeIds as $serviceInternalDowntimeId) {
                        $serviceDowntimeArray = $DowntimeServicesTable->getHostAndServiceUuidWithDowntimeByInternalDowntimeId($serviceInternalDowntimeId);
                        if (!empty($serviceDowntimeArray)) {
                            $ServiceDowntime = new Downtime($serviceDowntimeArray['DowntimeServices']);

                            //DEL_SVC_DOWNTIME
                            $payload = [
                                'Command' => 'delete_downtime',
                                'Data'    => [
                                    'host_name'           => $host->get('uuid'),
                                    'service_description' => $serviceDowntimeArray['Services']['uuid'],
                                    'start_time'          => $ServiceDowntime->getScheduledStartTime(),
                                    'end_time'            => $ServiceDowntime->getScheduledEndTime(),
                                    'comment'             => $ServiceDowntime->getCommentData()
                                ]
                            ];
                            // Delete downtime on the master system
                            $this->toQueue($payload, 0);
                            if ($satelliteId > 0) {
                                //Also delete downtime on the satellite system
                                $this->toQueue($payload, $satelliteId);
                            }
                        }
                    }
                }
                break;

            case 'DEL_SVC_DOWNTIME':
                //Only delete Service downtime
                $DbBackend = new DbBackend();
                $DowntimeServicesTable = $DbBackend->getDowntimehistoryServicesTable();
                $serviceDowntimeArray = $DowntimeServicesTable->getHostAndServiceUuidWithDowntimeByInternalDowntimeId($command['parameters']['downtime_id']);

                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                if (!empty($serviceDowntimeArray)) {
                    $ServiceDowntime = new Downtime($serviceDowntimeArray['DowntimeServices']);

                    try {
                        $host = $HostsTable->getHostByUuid($serviceDowntimeArray['Hosts']['uuid']);
                    } catch (RecordNotFoundException $e) {
                        //Host not found
                        return false;
                    }

                    $payload = [
                        'Command' => 'delete_downtime',
                        'Data'    => [
                            'host_name'           => $host->get('uuid'),
                            'service_description' => $serviceDowntimeArray['Services']['uuid'],
                            'start_time'          => $ServiceDowntime->getScheduledStartTime(),
                            'end_time'            => $ServiceDowntime->getScheduledEndTime(),
                            'comment'             => $ServiceDowntime->getCommentData()
                        ]
                    ];
                    // Delete downtime on the master system
                    $this->toQueue($payload, 0);
                    if ($host->get('satellite_id') > 0) {
                        //Also delete downtime on the satellite system
                        $this->toQueue($payload, $host->get('satellite_id'));
                    }
                }
                break;

            //Execute commands on the Master and the Satellite System
            case 'ACKNOWLEDGE_HOST_PROBLEM':
            case 'ACKNOWLEDGE_SVC_PROBLEM':
            case 'REMOVE_HOST_ACKNOWLEDGEMENT':
            case 'REMOVE_SVC_ACKNOWLEDGEMENT':
                $payload = [
                    'Command' => 'raw',
                    'Data'    => $command['command'] . ';' . implode(';', $command['parameters'])
                ];
                // Set or delete ack on the master system
                $this->toQueue($payload, 0);
                if ($satelliteId > 0) {
                    //Also set or delete ack on the satellite system
                    $this->toQueue($payload, $satelliteId);
                }
                break;

            //Execute commands on the Master or the Satellite System
            case 'SCHEDULE_FORCED_HOST_CHECK':

                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $host = $HostsTable->getHostForRescheduling($command['parameters']['hostUuid']);
                if (empty($host)) {
                    //Host not found
                    break;
                }

                if ($host->getValueOf('active_checks_enabled')) {
                    $payload = [
                        'Command' => 'schedule_check',
                        'Data'    => [
                            'host_name'     => $command['parameters']['hostUuid'],
                            'schedule_time' => $command['parameters']['check_time'] ?? time()
                        ]
                    ];
                    $this->toQueue($payload, $satelliteId);
                }
                break;

            //Execute commands on the Master or the Satellite System
            case 'SCHEDULE_FORCED_SVC_CHECK':
                /** @var ServicesTable $ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                $service = $ServicesTable->getServiceForRescheduling($command['parameters']['serviceUuid']);
                if (empty($service)) {
                    // Service not found
                    break;
                }

                if ($service->getValueOf('active_checks_enabled')) {
                    $payload = [
                        'Command' => 'schedule_check',
                        'Data'    => [
                            'host_name'           => $command['parameters']['hostUuid'],
                            'service_description' => $command['parameters']['serviceUuid'],
                            'schedule_time'       => $command['parameters']['check_time'] ?? time()
                        ]
                    ];
                    $this->toQueue($payload, $satelliteId);
                }
                break;

            //Execute commands on the Master or the Satellite System
            case 'SCHEDULE_FORCED_HOST_SVC_CHECKS':
                $checktime = $command['parameters']['check_time'] ?? time();

                /** @var ServicesTable $ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                $services = $ServicesTable->getServicesForRescheduling($command['parameters']['hostUuid']);
                foreach ($services as $service) {
                    /** @var Service $service */
                    if ($service->getValueOf('active_checks_enabled')) {
                        $payload = [
                            'Command' => 'raw',
                            'Data'    => sprintf('SCHEDULE_FORCED_SVC_CHECK;%s;%s;%s', $command['parameters']['hostUuid'], $service->get('uuid'), $checktime)
                        ];
                        $this->toQueue($payload, $satelliteId);
                    }
                }

                break;

            // Command needs to be execute on the master or the satellite system
            case 'PROCESS_HOST_CHECK_RESULT':
            case 'PROCESS_SERVICE_CHECK_RESULT':
                $payload = [
                    'Command' => 'check_result',
                    'Data'    => [
                        'host_name'     => $command['parameters']['hostUuid'],
                        'output'        => $command['parameters']['plugin_output'],
                        'long_output'   => $command['parameters']['long_output'],
                        'check_type'    => 1, //https://github.com/naemon/naemon-core/blob/cec6e10cbee9478de04b4cf5af29e83d47b5cfd9/src/naemon/common.h#L330-L334
                        'return_code'   => $command['parameters']['status_code'],
                        'start_time'    => time(),
                        'end_time'      => time(),
                        'early_timeout' => 0,
                        'latency'       => 0,
                        'exited_ok'     => 1
                    ]
                ];

                if ($command['command'] === 'PROCESS_SERVICE_CHECK_RESULT') {
                    $payload['Data']['service_description'] = $command['parameters']['serviceUuid'];
                }

                // Send to Master or Satellite queue
                $this->toQueue($payload, $satelliteId);
                break;

            //Only execute this command on the master system
            default:
                $payload = [
                    'Command' => 'raw',
                    'Data'    => $command['command'] . ';' . implode(';', $command['parameters'])
                ];
                $this->toQueue($payload, 0);

                break;
        }

        return true;
    }

    /**
     * Delete a host acknowledgement by given hostUuid
     *
     * @param string $hostUuid
     * @param int $satellite_id
     */
    public function deleteHostAcknowledgement(string $hostUuid, int $satellite_id) {
        //REMOVE_HOST_ACKNOWLEDGEMENT
        $payload = [
            'Command' => 'raw',
            'Data'    => 'REMOVE_HOST_ACKNOWLEDGEMENT;' . $hostUuid
        ];

        // Delete acknowledgement on the master system
        $this->toQueue($payload, 0);

        if ($satellite_id > 0) {
            // Delete acknowledgement on the satellite system
            $this->toQueue($payload, $satellite_id);
        }
    }

    /**
     * Delete a service acknowledgement by given host and serviceUuid
     *
     * @param string $hostUuid
     * @param string $serviceUuid
     * @param int $satellite_id
     */
    public function deleteServiceAcknowledgement(string $hostUuid, string $serviceUuid, int $satellite_id) {
        //REMOVE_SVC_ACKNOWLEDGEMENT
        $payload = [
            'Command' => 'raw',
            'Data'    => 'REMOVE_SVC_ACKNOWLEDGEMENT;' . $hostUuid . ';' . $serviceUuid
        ];

        // Delete acknowledgement on the master system
        $this->toQueue($payload, 0);

        if ($satellite_id > 0) {
            // Delete acknowledgement on the satellite system
            $this->toQueue($payload, $satellite_id);
        }
    }

    public function toQueue(array $payload, $satelliteId = 0) {
        $GearmanClient = new \GearmanClient();
        $GearmanClient->addServer($this->gearmanConfig['address'], $this->gearmanConfig['port']);
        $GearmanClient->setTimeout(5000);

        if ($payload['Command'] === 'raw') {
            // This is like passing an external command to nagios.cmd or naemon.cmd
            // Its the exact same syntax so we need to prefix the data with the current timestamp

            $payload['Data'] = sprintf('[%s] %s', time(), $payload['Data']);
        }

        if ($satelliteId > 0) {
            // Command gets send to sat system
            $payload['SatelliteID'] = $satelliteId;
            $GearmanClient->doBackground('statusngin_cmd_sattx', json_encode($payload));

        } else {
            // Command is for the master system
            $GearmanClient->doBackground('statusngin_cmd', json_encode($payload));
        }
    }

    /**
     * @param array $payload
     * @param int $satelliteId
     */
    public function toQueueBulk(array $payload, $satelliteId = 0) {
        $GearmanClient = new \GearmanClient();
        $GearmanClient->addServer($this->gearmanConfig['address'], $this->gearmanConfig['port']);
        $GearmanClient->setTimeout(5000);

        if ($satelliteId > 0) {
            // Command gets send to sat system
            $GearmanClient->doBackground('statusngin_cmd_sattx', json_encode($payload));

        } else {
            // Command is for the master system
            $GearmanClient->doBackground('statusngin_cmd', json_encode($payload));
        }
    }

}
