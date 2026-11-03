<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

declare(strict_types=1);

namespace NagiosModule\Controller;

use App\itnovum\openITCOCKPIT\Monitoring\Naemon\ExternalCommands;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Resolver\NameToUuidResolver;

class CmdController extends AppController {


    function index() {
        $externalCommands = $this->getExternalCommandsList();

        $this->set('externalCommands', $externalCommands);
        $this->viewBuilder()->setOption('serialize', ['externalCommands']);
    }

    public function submit() {
        if (!$this->request->is('post') && !$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('get')) {
            $data = $this->request->getQueryParams();
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
        }

        $externalCommand = $this->getExternalCommandsList();

        if (!isset($data) || !is_array($data)) {
            throw new BadRequestException();
        }

        if (!isset($data['command'])) {
            throw new BadRequestException();
        }

        if (!isset($externalCommand[$data['command']])) {
            throw new NotFoundException('Command not found');
        }

        if ($data['command'] === 'ACKNOWLEDGE_OTRS_HOST_SVC_PROBLEM') {
            throw new NotFoundException('Unsupported command. Please use /nagios_module/cmd/ack');
        }

        $args = Hash::merge($externalCommand[$data['command']], $data);

        if (isset($args['apikey'])) {
            unset($args['apikey']);
        }

        $command = $args['command'];
        unset($args['command']);

        // ITC-3373 Check if hostUuid and serviceUuid are valid UUIDs.
        // Otherwise, the user passed a human name such as "localhost" or "PING" and we try to resolve it.
        if (isset($args['hostUuid']) || isset($args['serviceUuid'])) {
            $Resolver = NameToUuidResolver::createResolver();
            if (isset($args['serviceUuid'])) {
                // Resolve both host and service name into a UUID (if needed)
                $result = $Resolver->resolveHostAndServicename($args['hostUuid'], $args['serviceUuid']);
                if ($result['hostUuid'] === false || $result['serviceUuid'] === false) {
                    Log::error(sprintf('CmdController: Could not resolve service name for "%s/%s" into a UUID', $args['hostUuid'], $args['serviceUuid']));
                    throw new BadRequestException(sprintf('CmdController: Could not resolve service name for "%s/%s" into a UUID', $args['hostUuid'], $args['serviceUuid']));
                }

                $args['hostUuid'] = $result['hostUuid'];
                $args['serviceUuid'] = $result['serviceUuid'];
            } else {
                // Resolve host name into a UUID (if needed)
                $hostUuid = $Resolver->resolveHostname($args['hostUuid']);
                if ($hostUuid === false) {
                    Log::error(sprintf('CmdController: Could not resolve hostname "%s" into a UUID', $args['hostUuid']));
                    throw new BadRequestException(sprintf('CmdController: Could not resolve hostname "%s" into a UUID', $args['hostUuid']));
                }
                $args['hostUuid'] = $hostUuid;
            }
        }

        // Command is now ready to submit to sudo_server
        // Auto-Lookup if Master System or SAT system (satelliteId=null)
        $GearmanClient = new Gearman();
        $GearmanClient->sendBackground('cmd_external_command', [
            'command'     => $command,
            'parameters'  => $args,
            'satelliteId' => null
        ]);


        $this->set('message', __('Command added successfully to queue'));
        $this->set('command', $command);
        $this->set('args', $args);
        $this->viewBuilder()->setOption('serialize', [
            'message',
            'command',
            'args'
        ]);
    }

    public function submit_bulk() {
        if (!$this->request->is('post') && !$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('get')) {
            $data = $this->request->getQueryParams();
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
        }

        if (!isset($data) || !is_array($data)) {
            throw new BadRequestException();
        }

        if (isset($data['apikey'])) {
            unset($data['apikey']);
        }

        $externalCommand = $this->getExternalCommandsList();
        //if true there is some stuff in there which should not (eg. single command instead of a bulk command)
        $nonNumericArray = false;
        $argsForResponse = [];
        foreach ($data as $key => $value) {

            if (!isset($externalCommand[$value['command']])) {
                throw new NotFoundException('Command not found');
            }

            if (!isset($value['command'])) {
                throw new BadRequestException();
            }

            if ($value['command'] === 'ACKNOWLEDGE_OTRS_HOST_SVC_PROBLEM') {
                throw new NotFoundException('Unsupported command. Please use /nagios_module/cmd/ack');
            }

            if (!is_numeric($key)) {
                $nonNumericArray = true;
                break;
            }

            $command = $value['command'];
            unset($value['command']);

            $args = Hash::merge($externalCommand[$command], $value);
            // Command is now ready to submit to sudo_server
            // Auto-Lookup if Master System or SAT system (satelliteId=null)
            $GearmanClient = new Gearman();
            $GearmanClient->sendBackground('cmd_external_command', [
                'command'     => $command,
                'parameters'  => $args,
                'satelliteId' => null
            ]);

            //just response related
            $response = array_merge($args, ['command' => $command]);
            $argsForResponse[] = $response;
        }

        if ($nonNumericArray == true) {
            throw new BadRequestException();
        }

        $this->set('message', __('Commands added successfully to queue'));
        $this->set('args', $argsForResponse);
        $this->viewBuilder()->setOption('serialize', [
            'message',
            'args'
        ]);
    }

    public function submit_bulk_naemon() {
        if (!$this->request->is('post') && !$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('get')) {
            $data = $this->request->getQueryParams();
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
        }

        if (!isset($data)) {
            throw new BadRequestException();
        }
        $externalNaemonCommands = new ExternalCommands();
        $authUser = new User($this->getUser());

        foreach ($data as $value) {
            $event = null;
            switch ($value['command']) {
                case 'rescheduleHost':
                    $result = $externalNaemonCommands->rescheduleHost(['uuid' => $value['hostUuid'], 'type' => $value['type'], 'satellite_id' => $value['satelliteId']]);
                    break;
                case 'rescheduleService':
                    $result = $externalNaemonCommands->rescheduleService(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid'], 'satellite_id' => $value['satelliteId']]);
                    //$actionType = 'check_rescheduled';

                    break;
                case 'submitHostDowntime':
                    $result = $externalNaemonCommands->setHostDowntime(['hostUuid' => $value['hostUuid'], 'start' => $value['start'], 'end' => $value['end'], 'comment' => $value['comment'], 'author' => $value['author'], 'downtimetype' => $value['downtimetype']]);
                    $event = ['type' => 'downtime_created', 'model' => 'Host'];
                    break;
                case 'submitServiceDowntime':
                    $result = $externalNaemonCommands->setServiceDowntime(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid'], 'start' => $value['start'], 'end' => $value['end'], 'comment' => $value['comment'], 'author' => $value['author']]);
                    //$actionType = 'downtime';
                    break;
                case 'submitHoststateAck':
                    $result = $externalNaemonCommands->setHostAck(['hostUuid' => $value['hostUuid'], 'comment' => $value['comment'], 'author' => $value['author'], 'sticky' => $value['sticky'], 'type' => $value['hostAckType'], 'notify' => $value['notify']]);
                    break;
                case 'submitServicestateAck':
                    $result = $externalNaemonCommands->setServiceAck(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid'], 'comment' => $value['comment'], 'author' => $value['author'], 'sticky' => $value['sticky'], 'notify' => $value['notify']]);
                    //$actionType = 'acknowledgement';
                    break;
                case 'commitPassiveServiceResult':
                    $result = $externalNaemonCommands->passiveTransferServiceCheckresult(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid'], 'comment' => $value['plugin_output'], 'state' => $value['status_code'], 'forceHardstate' => $value['forceHardstate'], 'repetitions' => $value['maxCheckAttempts']]);
                    //$actionType = 'check_result';
                    break;
                case 'commitPassiveResult':
                    $result = $externalNaemonCommands->passiveTransferHostCheckresult(['uuid' => $value['hostUuid'], 'comment' => $value['plugin_output'], 'state' => $value['status_code'], 'forceHardstate' => $value['forceHardstate'], 'repetitions' => $value['maxCheckAttempts']]);
                    break;
                case 'sendCustomServiceNotification':
                    $result = $externalNaemonCommands->sendCustomServiceNotification(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid'], 'type' => $value['options'], 'author' => $value['author'], 'comment' => $value['comment']]);
                    break;
                case 'sendCustomHostNotification':
                    $result = $externalNaemonCommands->sendCustomHostNotification(['hostUuid' => $value['hostUuid'], 'type' => $value['options'], 'author' => $value['author'], 'comment' => $value['comment']]);
                    break;
                case 'enableOrDisableHostFlapdetection':
                    $result = $externalNaemonCommands->enableOrDisableHostFlapdetection(['uuid' => $value['hostUuid'], 'condition' => $value['condition']]);
                    $event['type'] = $value['condition'] === 0 ? 'flapdetection_disabled' : 'flapdetection_enabled';
                    $event['model'] = 'Host';

                    break;
                case 'enableOrDisableServiceFlapdetection':
                    $result = $externalNaemonCommands->enableOrDisableServiceFlapdetection(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid'], 'condition' => $value['condition']]);
                    //$actionType = 'flapdetection';
                    break;
                case 'submitEnableHostNotifications':
                    $result = $externalNaemonCommands->enableHostNotifications(['uuid' => $value['hostUuid'], 'type' => $value['type']]);
                    $event = ['type' => 'notification_enabled', 'model' => 'Host'];
                    break;
                case 'submitDisableHostNotifications':
                    $result = $externalNaemonCommands->disableHostNotifications(['uuid' => $value['hostUuid'], 'type' => $value['type']]);
                    $event = ['type' => 'notification_disabled', 'model' => 'Host'];
                    break;
                case 'submitDisableServiceNotifications':
                    $result = $externalNaemonCommands->disableServiceNotifications(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid']]);
                    break;
                case 'submitEnableServiceNotifications':
                    $result = $externalNaemonCommands->enableServiceNotifications(['hostUuid' => $value['hostUuid'], 'serviceUuid' => $value['serviceUuid']]);
                    break;
                default:
                    $result = false;
                    break;
            }
            if (!$result) {
                throw new BadRequestException('Could not send command');
            }

            if (!empty($event)) {
                $externalNaemonCommands->logEvent($event + [
                    'uuid' => $value['hostUuid'],
                    'serviceUuid' => $value['serviceUuid'] ?? null,
                    'userId' => $authUser->getId(),
                    'user_name' => $authUser->getFullName()
                ]);
            }
        }
        $this->set('message', __('Commands added successfully to queue'));
        $this->viewBuilder()->setOption('serialize', [
            'message',
        ]);
    }

    /**
     * Used by OTRS
     * Only supported command is ACKNOWLEDGE_OTRS_HOST_SVC_PROBLEM.
     * For all other commands use the submit() method above!
     */
    public function ack() {
        if (!$this->request->is('post') && !$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('get')) {
            $data = $this->request->getQueryParams();
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
        }

        $externalCommand = $this->getExternalCommandsList();

        if (!isset($data) || !is_array($data)) {
            throw new BadRequestException();
        }

        if (!isset($data['command'])) {
            throw new BadRequestException();
        }

        if (!isset($externalCommand[$data['command']])) {
            throw new NotFoundException('Command not found');
        }

        $args = $data;
        if (isset($args['apikey'])) {
            unset($args['apikey']);
        }

        $command = $args['command'];
        unset($args['command']);

        if ($command !== 'ACKNOWLEDGE_OTRS_HOST_SVC_PROBLEM') {
            throw new NotFoundException('Unsupported command');
        }

        //Check for required parameters
        $argsToCheck = ['cmdType', 'hostUuid', 'comment', 'author'];
        foreach ($argsToCheck as $arg) {
            if (!isset($args[$arg])) {
                throw new NotFoundException($arg . ' is missing!');
            }
        }

        $defaults = [
            'cmdType'        => 33,
            'internalMethod' => 'ack',
            'sticky'         => 0,
            'notify'         => 1,
            'persistent'     => 1,
            'author'         => 'OTRS',
            'comment'        => '',
            'com_data'       => ''
        ];

        $args = Hash::merge($defaults, $args);

        $GearmanClient = new Gearman();

        // Auto-Lookup if Master System or SAT system (satelliteId=null)

        //If serviceUUID isn't set it's an ACK for Hosts
        if (!isset($args['serviceUuid']) || $args['serviceUuid'] === '') {
            $GearmanClient->sendBackground('cmd_external_command', [
                'command'     => 'ACKNOWLEDGE_HOST_PROBLEM',
                'parameters'  => [
                    'hostUuid'   => $args['hostUuid'],
                    'sticky'     => $args['sticky'],
                    'notify'     => $args['notify'],
                    'persistent' => $args['persistent'],
                    'author'     => $args['author'],
                    'comment'    => $args['comment'],
                    //'com_data'   => $args['com_data'],
                ],
                'satelliteId' => null
            ]);
        } else {
            $GearmanClient->sendBackground('cmd_external_command', [
                'command'     => 'ACKNOWLEDGE_SVC_PROBLEM',
                'parameters'  => [
                    'hostUuid'    => $args['hostUuid'],
                    'serviceUuid' => $args['serviceUuid'],
                    'sticky'      => $args['sticky'],
                    'notify'      => $args['notify'],
                    'persistent'  => $args['persistent'],
                    'author'      => $args['author'],
                    'comment'     => $args['comment'],
                    //'com_data'    => $args['com_data']
                ],
                'satelliteId' => null
            ]);
        }

        $this->set('message', __('Command added successfully to queue'));
        $this->set('command', $command);
        $this->set('args', $args);
        $this->viewBuilder()->setOption('serialize', [
            'message',
            'command',
            'args'
        ]);
    }

    private function getExternalCommandsList() {
        return [
            'ACKNOWLEDGE_HOST_PROBLEM'                       => ['hostUuid' => null, 'sticky' => 0, 'notify' => 1, 'persistent' => 1, 'author' => null, 'comment' => null],
            'ACKNOWLEDGE_SVC_PROBLEM'                        => ['hostUuid' => null, 'serviceUuid' => null, 'sticky' => 0, 'notify' => 1, 'persistent' => 1, 'author' => null, 'comment' => null],
            'ACKNOWLEDGE_OTRS_HOST_SVC_PROBLEM'              => ['cmdType' => null, 'internalMethod' => 'ack', 'hostUuid' => null, 'serviceUuid' => null, 'sticky' => 0, 'notify' => 1, 'persistent' => 1, 'author' => null, 'comment' => null, 'com_data' => null],
            'DISABLE_FLAP_DETECTION'                         => [],
            'DISABLE_HOST_CHECK'                             => ['hostUuid' => null],
            'DISABLE_NOTIFICATIONS'                          => [],
            'DISABLE_HOST_NOTIFICATIONS'                     => ['hostUuid' => null],
            'DISABLE_HOST_SVC_NOTIFICATIONS'                 => ['hostUuid' => null],
            'DISABLE_HOST_FLAP_DETECTION'                    => ['hostUuid' => null],
            //'DISABLE_PERFORMANCE_DATA'          => [],
            'DISABLE_SERVICE_FRESHNESS_CHECKS'               => [],
            'DISABLE_SVC_CHECK'                              => ['hostUuid' => null, 'serviceUuid' => null],
            'DISABLE_SVC_FLAP_DETECTION'                     => ['hostUuid' => null, 'serviceUuid' => null],
            'DISABLE_SVC_NOTIFICATIONS'                      => ['hostUuid' => null, 'serviceUuid' => null],
            'ENABLE_FLAP_DETECTION'                          => [],
            'ENABLE_HOST_FLAP_DETECTION'                     => ['hostUuid' => null],
            'ENABLE_HOST_CHECK'                              => ['hostUuid' => null],
            'ENABLE_NOTIFICATIONS'                           => [],
            'ENABLE_HOST_NOTIFICATIONS'                      => ['hostUuid' => null],
            'ENABLE_HOST_SVC_NOTIFICATIONS'                  => ['hostUuid' => null],
            //'ENABLE_PERFORMANCE_DATA'           => [],
            'ENABLE_SERVICE_FRESHNESS_CHECKS'                => [],
            'ENABLE_SVC_CHECK'                               => ['hostUuid' => null, 'serviceUuid' => null],
            'ENABLE_SVC_FLAP_DETECTION'                      => ['hostUuid' => null, 'serviceUuid' => null],
            'ENABLE_SVC_NOTIFICATIONS'                       => ['hostUuid' => null, 'serviceUuid' => null],
            'PROCESS_HOST_CHECK_RESULT'                      => ['hostUuid' => null, 'status_code' => null, 'plugin_output' => null, 'long_output' => ''],
            'PROCESS_SERVICE_CHECK_RESULT'                   => ['hostUuid' => null, 'serviceUuid' => null, 'status_code' => null, 'plugin_output' => null, 'long_output' => ''],
            'REMOVE_HOST_ACKNOWLEDGEMENT'                    => ['hostUuid' => null],
            'REMOVE_SVC_ACKNOWLEDGEMENT'                     => ['hostUuid' => null, 'serviceUuid' => null],
            //'RESTART_PROGRAM'                   => [],
            'SCHEDULE_HOST_DOWNTIME'                         => ['hostUuid' => null, 'start_time' => null, 'end_time' => null, 'fixed' => 1, 'trigger_id' => 0, 'duration' => null, 'author' => null, 'comment' => null],
            'SCHEDULE_HOST_SVC_DOWNTIME'                     => ['hostUuid' => null, 'start_time' => null, 'end_time' => null, 'fixed' => 1, 'trigger_id' => 0, 'duration' => null, 'author' => null, 'comment' => null],
            'SCHEDULE_SVC_DOWNTIME'                          => ['hostUuid' => null, 'serviceUuid' => null, 'start_time' => null, 'end_time' => null, 'fixed' => 1, 'trigger_id' => 0, 'duration' => null, 'author' => null, 'comment' => null],
            'DEL_HOST_DOWNTIME'                              => ['downtime_id' => null, 'include_services' => 1],
            'DEL_SVC_DOWNTIME'                               => ['downtime_id' => null],
            'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME'           => ['hostUuid' => null, 'start_time' => null, 'end_time' => null, 'fixed' => 1, 'trigger_id' => 0, 'duration' => null, 'author' => null, 'comment' => null],
            'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME' => ['hostUuid' => null, 'start_time' => null, 'end_time' => null, 'fixed' => 1, 'trigger_id' => 0, 'duration' => null, 'author' => null, 'comment' => null],
            'SCHEDULE_FORCED_HOST_CHECK'                     => ['hostUuid' => null, 'check_time' => null],
            'SCHEDULE_FORCED_HOST_SVC_CHECKS'                => ['hostUuid' => null, 'check_time' => null],
            'SCHEDULE_FORCED_SVC_CHECK'                      => ['hostUuid' => null, 'serviceUuid' => null, 'check_time' => null],
            'SEND_CUSTOM_HOST_NOTIFICATION'                  => ['hostUuid' => null, 'options' => 0, 'author' => null, 'comment' => null],
            'SEND_CUSTOM_SVC_NOTIFICATION'                   => ['hostUuid' => null, 'serviceUuid' => null, 'options' => 0, 'author' => null, 'comment' => null],
        ];
    }
}
