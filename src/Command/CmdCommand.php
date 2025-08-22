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

declare(strict_types=1);

namespace App\Command;

use App\Model\Table\ServicesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Monitoring\QueryHandler;

/**
 * Cmd command.
 */
class CmdCommand extends Command {

    /**
     * Used to store external commands if the real nagios.cmd does not exists
     * For example if Nagios/Naemon is not running
     * @var array
     */
    private $externalCommandsQueue = [];

    /**
     * @var array
     */
    private $serviceCache = [];

    /**
     * @var resource|null
     */
    private $cmdPipe = null;

    /**
     * Path of oitc.cmd
     * @var string
     */
    private $externalCommandFile = '/opt/openitc/nagios/var/rw/oitc.cmd';

    /**
     * External command file of Nagios or Naemon (nagios.cmd)
     * @var string
     */
    private $nagiosCmd = '/opt/openitc/nagios/var/rw/nagios.cmd';

    /**
     * @var ConsoleIo
     */
    private $io;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('pipe', [
            'short'   => 'p',
            'help'    => 'Path of the oitc.cmd',
            'default' => $this->externalCommandFile
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->io = $io;

        $pipeFile = $args->getOption('pipe');
        if ($pipeFile !== $this->externalCommandFile) {
            $this->externalCommandFile = $args->getOption('pipe');

            Log::info('CmdCommand: Starting with not default external command file: "' . $this->externalCommandFile . '"');
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        $this->nagiosCmd = $systemsettings['MONITORING']['MONITORING.CMD'];

        $this->createPipe();
        $this->buildServiceCache();
        $this->daemonize();

        /*$this->processCommand("[1426072654] ACKNOWLEDGE_HOST_PROBLEM;host1;2;1;1;Some One;Some Acknowledgement Comment\n");
        $this->processCommand("[1426072654] RESTART_PROGRAM\n");
        $this->processCommand("[1426072654] ACKNOWLEDGE_SVC_PROBLEM;host1;service1;2;1;1;Some One;Some Acknowledgement Comment\n");
        $this->processCommand("[1426072654] DEL_HOST_DOWNTIME;1\n");
        $this->processCommand("[".time()."] SCHEDULE_FORCED_SVC_CHECK;host1;Random;".time()."\n");
        $this->processCommand("[".time()."] SCHEDULE_FORCED_SVC_CHECK;da6defe4-3a44-4df2-8195-25de55ad9379;Random;".time()."\n");
        $this->processCommand("[".time()."] SCHEDULE_FORCED_SVC_CHECK;da6defe4-3a44-4df2-8195-25de55ad9379;c6466a47-8b67-49d9-8e6a-897dfa720351;".time()."\n");
        $this->processCommand("[1426148428] SCHEDULE_FORCED_SVC_CHECK;bdb0558c-7c64-49ca-8f26-4ccd88a8c887;tenplate_args;1426148428\n"); */
    }

    /**
     * Create an cache with all service names as an array.
     * Array key is Host-UUID + Service Name as sha265 hash
     * hash('sha256', $service['host']['uuid'] . $serviceName);
     *
     * @return    void
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function buildServiceCache() {
        Log::info('CmdCommand: Build up service cache');

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $query = $ServicesTable->find();
        $query
            ->select([
                'id',
                'uuid',
                'name'
            ])
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    $query->select([
                        'id',
                        'name'
                    ]);
                    return $query;
                },
                'Hosts'            => function (Query $query) {
                    $query->select([
                        'id',
                        'uuid',
                        'name'
                    ]);
                    return $query;
                }
            ])
            ->where([
                'Services.disabled' => 0
            ])
            ->disableHydration()
            ->all();

        foreach ($query as $service) {
            $this->appendCache($service);
        }

        Log::info('CmdCommand: Created service cache');
    }

    /**
     * @param array $service
     */
    public function appendCache(array $service) {
        $serviceName = $service['name'];
        if ($serviceName === null || $serviceName === '') {
            $serviceName = $service['servicetemplate']['name'];
        }

        $hostAndServiceHash = hash('sha256', $service['host']['uuid'] . $serviceName);

        if (!isset($this->serviceCache[$hostAndServiceHash])) {
            $this->serviceCache[$hostAndServiceHash] = $service['uuid'];
        } else {
            Log::warning('CmdCommand: Duplicate service "' . $serviceName . '" on host "' . $service['host']['name'] . '" (' . $service['host']['uuid'] . ')');
        }
    }

    /**
     * Search the cache for a service by Host-UUID and the service description as string
     * If a service is not found in the cache, a SQL query is fired up to lookup the UUID of the service
     * and store the result in the cache
     *
     * @param string $hostUuid the UUID of the host
     * @param string $serviceDescription the service description (for humans not a uuid e.g. Lan-Ping)
     *
     * @return    string|false return service uuid if found in cache, or false if no result is found
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function searchCache($hostUuid, $serviceDescription) {
        $hostAndServiceHash = hash('sha256', $hostUuid . $serviceDescription);
        if (isset($this->serviceCache[$hostAndServiceHash])) {
            return $this->serviceCache[$hostAndServiceHash];
        }

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        //Reconnect datasource to avoid "MySQL has gone away"
        // CakePHP has a ReconnectStrategy which should make this code obsolet.
        //$connection = $ServicesTable->getConnection();
        //$connection->getDriver()->disconnect();
        //$connection->getDriver()->connect();

        $query = $ServicesTable->find();
        $query
            ->select([
                'id',
                'uuid',
                'name',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
            ])
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    $query->select([
                        'id',
                        'name'
                    ]);
                    return $query;
                },
                'Hosts'            => function (Query $query) {
                    $query->select([
                        'id',
                        'uuid',
                        'name'
                    ]);
                    return $query;
                }
            ])
            ->where([
                'Hosts.uuid'        => $hostUuid,
                'Services.disabled' => 0
            ])
            ->having([
                'servicename' => $serviceDescription
            ])
            ->disableHydration()
            ->first();

        $service = $query->toArray();

        if (empty($service) || !isset($service[0])) {
            return false;
        }

        $this->appendCache($service[0]);
        return $service[0]['uuid'];
    }


    /**
     *
     * @param string $commandAsString the external command read from oitc.cmd
     *
     * @return    void
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function processCommand($commandAsString) {
        //Parse the external command read from oitc.cmd
        $commandAsArray = $this->parseCommand($commandAsString);
        if ($commandAsArray) {
            //Try to map human service description to service UUID
            $NagCommandAsString = $this->buildCmdCommand($commandAsArray);

            //Log::debug('CmdCommand: ' . $NagCommandAsString);

            //Pass the command to nagios.cmd
            if ($NagCommandAsString) {
                $this->writeToMonitoringCmd($NagCommandAsString);
            }
        } else {
            Log::warning('CmdCommand: External command parse error. Command: "' . $commandAsString . '"');
        }
    }

    /**
     * Parse the external nagios command and return it as an array
     * array(
     *    'timestamp' => '[123456789]',
     *    'command'   => 'ACKNOWLEDGE_SVC_PROBLEM',
     *    'params'    => array(
     *        0 => 'host1',
     *        1 => 'service1',
     *        n => 'argn'
     *   )
     * )
     *
     * @param string $command the external command you want to parse
     * @return array|bool
     */
    public function parseCommand(string $command) {
        $command = trim($command);

        if ($command === '') {
            return false;
        }


        //Get timestamp part
        $command = explode(' ', $command, 2);
        if (sizeof($command) !== 2) {
            Log::warning('CmdCommand: External command parse error. Command: "' . $command . '"');
            return false;
        }

        $timestamp = $command[0];
        $command = explode(';', $command[1]);

        $externalCommand = $command[0];
        unset($command[0]);
        $params = $command;

        return [
            'timestamp' => $timestamp,
            'command'   => $externalCommand,
            'params'    => $params,
        ];

    }


    /**
     * This function check which command we got from the oitc.cmd. If it is a service command
     * the function check if there is a human service_description in it and try to replace the service description
     * with the service uuid from the serviceCache.
     *
     * All other external commands will get forwarded directly to nagios.cmd
     *
     * @param array $commandAsString retuned from $this->parseCommand()
     *
     * @return    string a external command wich can be piped to the nagios.cmd
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function buildCmdCommand(array $commandAsArray) {
        //We only catch service commands, to replace the service description with a uuid
        //All other commands gets passed to monitorings nagios.cmd/naemon.cmd file

        //We use a switch because may be we need some exceptions in future or so

        switch ($commandAsArray['command']) {
            case 'ACKNOWLEDGE_SVC_PROBLEM':
            case 'ADD_SVC_COMMENT':
            case 'CHANGE_CUSTOM_SVC_VAR':
            case 'CHANGE_MAX_SVC_CHECK_ATTEMPTS':
            case 'CHANGE_NORMAL_SVC_CHECK_INTERVAL':
            case 'CHANGE_RETRY_SVC_CHECK_INTERVAL':
            case 'CHANGE_SVC_CHECK_COMMAND':
            case 'CHANGE_SVC_CHECK_TIMEPERIOD':
            case 'CHANGE_SVC_EVENT_HANDLER':
            case 'CHANGE_SVC_MODATTR':
            case 'CHANGE_SVC_NOTIFICATION_TIMEPERIOD':
            case 'DEL_ALL_SVC_COMMENTS':
            case 'DELAY_SVC_NOTIFICATION':
            case 'DISABLE_PASSIVE_SVC_CHECKS':
            case 'DISABLE_SVC_CHECK':
            case 'DISABLE_SVC_EVENT_HANDLER':
            case 'DISABLE_SVC_FLAP_DETECTION':
            case 'DISABLE_SVC_NOTIFICATIONS':
            case 'ENABLE_PASSIVE_SVC_CHECKS':
            case 'ENABLE_SVC_CHECK':
            case 'ENABLE_SVC_EVENT_HANDLER':
            case 'ENABLE_SVC_FLAP_DETECTION':
            case 'ENABLE_SVC_NOTIFICATIONS':
            case 'PROCESS_SERVICE_CHECK_RESULT':
            case 'REMOVE_SVC_ACKNOWLEDGEMENT':
            case 'SCHEDULE_FORCED_SVC_CHECK':
            case 'SCHEDULE_SVC_CHECK':
            case 'SCHEDULE_SVC_DOWNTIME':
            case 'SEND_CUSTOM_SVC_NOTIFICATION':
            case 'SET_SVC_NOTIFICATION_NUMBER':
            case 'START_OBSESSING_OVER_SVC':
            case 'STOP_OBSESSING_OVER_SVC':

                /* Average external command syntax
                 * <hostname (uuid)>;<service_description (human name)>;<params>
                 */
                //We may be have a uuid, lets check this with preg_match
                if (strlen($commandAsArray['params'][2]) == 36) {
                    if (preg_match(UUID::regex(), $commandAsArray['params'][2])) {
                        //Yes, we have a uuid, so we can submit the command without touch it
                        return $this->_buildNagCommand($commandAsArray);
                    }
                }

                // The user passed a service description, so we need to check, if we can find the uuid for this service
                $serviceUuid = $this->searchCache($commandAsArray['params'][1], $commandAsArray['params'][2]);
                if ($serviceUuid) {
                    //Yes, we found a service uuid
                    $commandAsArray['params'][2] = $serviceUuid;

                    return $this->_buildNagCommand($commandAsArray);
                } else {
                    //could not find service uuid
                    Log::error(sprintf(
                        'CmdCommand: Could not find service for given service description [%s] and host uuid [%s]',
                        $commandAsArray['params'][2],
                        $commandAsArray['params'][1]
                    ));
                    return false;
                }

                break;

            default:
                return $this->_buildNagCommand($commandAsArray);
                break;
        }
    }

    /**
     * Build up a Nagios/Naemon command in the syntax for nagios.cmd/naemon.cmd
     *
     * @param array $commandAsArray the external command you want to convert in a Nagios command
     *
     * @return    string return the external command ready for nagios.cmd
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    private function _buildNagCommand($commandAsArray) {
        if (!empty($commandAsArray['params'])) {

            foreach ($commandAsArray['params'] as $key => $value) {
                $commandAsArray['params'][$key] = $this->fixUtf8($value);
            }

            return $commandAsArray['timestamp'] . ' ' . $commandAsArray['command'] . ';' . implode(';', $commandAsArray['params']) . "\n";
        }

        return $commandAsArray['timestamp'] . ' ' . $commandAsArray['command'] . "\n";
    }

    /**
     * Create the oitc.cmd named pipe file and store the resource in $this->cmdPipe
     * If a oitc.cmd exits the file will be deleted
     * @return    bool
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function createPipe() {
        $this->closePipe();

        umask(0);
        if (!posix_mkfifo($this->externalCommandFile, 0660)) {
            Log::error('CmdCommand: FATAL ERROR: Could not create named pipe file!');
            exit(1);
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        chown($this->externalCommandFile, $systemsettings['MONITORING']['MONITORING.USER']);
        chgrp($this->externalCommandFile, $systemsettings['WEBSERVER']['WEBSERVER.GROUP']);

        $this->cmdPipe = fopen($this->externalCommandFile, 'r+');
        if ($this->cmdPipe) {
            return true;
        }
        return false;
    }

    /**
     * Close the named pipe file and close the file handler
     * @return    bool
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function closePipe() {
        if (is_resource($this->cmdPipe)) {
            fclose($this->cmdPipe);
        }

        if (file_exists($this->externalCommandFile)) {
            if (unlink($this->externalCommandFile)) {
                return true;
            }
        }

        return !file_exists($this->externalCommandFile);
    }

    /**
     * Create a daemon, which reads from the socket, process data and pass it to nagios.cmd
     * @return    void
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function daemonize() {
        declare(ticks=1);

        pcntl_signal(SIGTERM, [$this, 'sig_handler']);
        pcntl_signal(SIGINT, [$this, 'sig_handler']);

        stream_set_blocking($this->cmdPipe, false);

        Log::info('CmdCommand: Finished daemonizing... [My PID = ' . getmypid() . ']');

        while (true) {
            pcntl_signal_dispatch();
            $commands = [];
            while (true) {
                pcntl_signal_dispatch();

                //Read as long as we receive text.
                //If fgets return false, we leave the while
                $read = fgets($this->cmdPipe);
                if ($read) {
                    $commands[] = $read;
                } else {
                    //gets returned false, exit while
                    break;
                }

            }
            foreach ($commands as $command) {
                $this->processCommand($command);
            }

            //sleep, to save CPU time (500ms)
            usleep(500000);
        }
    }

    /**
     * The signal handler is called by the linux kernel or pcntl_signal_dispatch and will handel singals^^
     * Delete oitc.cmd and exit on SIGTERM and SIGINT
     *
     * @param int $signo , the signal catched by pcntl_signal_dispatch()
     *
     * @return    void
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function sig_handler($signo) {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                if (!$this->closePipe()) {
                    Log::warning('CmdCommand: Could not delete my named pipe file!');
                }
                exit(0);
                break;

            default:
                Log::error('CmdCommand: Signal not supported yet!');
                break;
        }
    }

    /**
     * Write a given command to nagios.cmd
     * If the cmd file not exists, commands gets cached in
     * $this->externalCommandsQueue array and passed to nagios.cmd
     * if its back
     *
     * @param string $$commandAsString nagios external command
     *
     * @return    void
     * @author    Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since     3.0.1
     */
    public function writeToMonitoringCmd(string $commandAsString = '') {
        if (file_exists($this->nagiosCmd)) {
            $cmd = fopen($this->nagiosCmd, 'a+');

            if (strpos($commandAsString, 'PROCESS_SERVICE_CHECK_RESULT') > 0 && strpos($commandAsString, '\n') > 0) {
                //Log::debug('Send to query handler');

                $commandAsString = str_replace('\n', "\n", $commandAsString); // YES replace fake \n with real \n

                // Passive Service check which contains longoutput (Checkmk 2.x)
                // https://github.com/naemon/naemon-core/issues/144
                // https://github.com/NagiosEnterprises/nagioscore/issues/141

                $query = '#command run ';
                $query .= $commandAsString;

                $QueryHandler = new QueryHandler('/opt/openitc/nagios/var/rw/nagios.qh');
                if ($QueryHandler->exists()) {
                    $QueryHandler->connect();
                    $QueryHandler->send($query, false);
                }
                unset($QueryHandler);
            } else {
                fwrite($cmd, $commandAsString);
                //Write command to nagios.cmd
            }

            if (!empty($this->externalCommandsQueue)) {
                //Process cache, becasue nagios.cmd was not there for a while...
                foreach ($this->externalCommandsQueue as $command) {
                    fwrite($cmd, $command);
                }
                $this->externalCommandsQueue = [];
            }
            fclose($cmd);
        } else {
            $this->externalCommandsQueue[] = $commandAsString;
        }
    }

    /**
     * @param $str
     * @return false|string
     */
    public function fixUtf8($str) {
        if (mb_detect_encoding($str) !== 'UTF-8') {
            return mb_convert_encoding($str, 'UTF-8');
        }
        return $str;
    }

    /**
     * This are all known Nagios external commands (11.03.2015)
     * @param string $command
     * @param array $params
     */
    /*
    public function buildCmdCommand(string $command, array $params = []) {
        switch ($command) {
            case 'ACKNOWLEDGE_HOST_PROBLEM':
                break;
            case 'ACKNOWLEDGE_SVC_PROBLEM':
                break;
            case 'ADD_HOST_COMMENT':
                break;
            case 'ADD_SVC_COMMENT':
                break;
            case 'CHANGE_CONTACT_HOST_NOTIFICATION_TIMEPERIOD':
                break;
            case 'CHANGE_CONTACT_MODATTR':
                break;
            case 'CHANGE_CONTACT_MODHATTR':
                break;
            case 'CHANGE_CONTACT_MODSATTR':
                break;
            case 'CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD':
                break;
            case 'CHANGE_CUSTOM_CONTACT_VAR':
                break;
            case 'CHANGE_CUSTOM_HOST_VAR':
                break;
            case 'CHANGE_CUSTOM_SVC_VAR':
                break;
            case 'CHANGE_GLOBAL_HOST_EVENT_HANDLER':
                break;
            case 'CHANGE_GLOBAL_SVC_EVENT_HANDLER':
                break;
            case 'CHANGE_HOST_CHECK_COMMAND':
                break;
            case 'CHANGE_HOST_CHECK_TIMEPERIOD':
                break;
            case 'CHANGE_HOST_EVENT_HANDLER':
                break;
            case 'CHANGE_HOST_MODATTR':
                break;
            case 'CHANGE_MAX_HOST_CHECK_ATTEMPTS':
                break;
            case 'CHANGE_MAX_SVC_CHECK_ATTEMPTS':
                break;
            case 'CHANGE_NORMAL_HOST_CHECK_INTERVAL':
                break;
            case 'CHANGE_NORMAL_SVC_CHECK_INTERVAL':
                break;
            case 'CHANGE_RETRY_HOST_CHECK_INTERVAL':
                break;
            case 'CHANGE_RETRY_SVC_CHECK_INTERVAL':
                break;
            case 'CHANGE_SVC_CHECK_COMMAND':
                break;
            case 'CHANGE_SVC_CHECK_TIMEPERIOD':
                break;
            case 'CHANGE_SVC_EVENT_HANDLER':
                break;
            case 'CHANGE_SVC_MODATTR':
                break;
            case 'CHANGE_SVC_NOTIFICATION_TIMEPERIOD':
                break;
            case 'DELAY_HOST_NOTIFICATION':
                break;
            case 'DELAY_SVC_NOTIFICATION':
                break;
            case 'DEL_ALL_HOST_COMMENTS':
                break;
            case 'DEL_ALL_SVC_COMMENTS':
                break;
            case 'DEL_HOST_COMMENT':
                break;
            case 'DEL_HOST_DOWNTIME':
                break;
            case 'DEL_SVC_COMMENT':
                break;
            case 'DEL_SVC_DOWNTIME':
                break;
            case 'DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST':
                break;
            case 'DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS':
                break;
            case 'DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS':
                break;
            case 'DISABLE_CONTACT_HOST_NOTIFICATIONS':
                break;
            case 'DISABLE_CONTACT_SVC_NOTIFICATIONS':
                break;
            case 'DISABLE_EVENT_HANDLERS':
                break;
            case 'DISABLE_FAILURE_PREDICTION':
                break;
            case 'DISABLE_FLAP_DETECTION':
                break;
            case 'DISABLE_HOSTGROUP_HOST_CHECKS':
                break;
            case 'DISABLE_HOSTGROUP_HOST_NOTIFICATIONS':
                break;
            case 'DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS':
                break;
            case 'DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS':
                break;
            case 'DISABLE_HOSTGROUP_SVC_CHECKS':
                break;
            case 'DISABLE_HOSTGROUP_SVC_NOTIFICATIONS':
                break;
            case 'DISABLE_HOST_AND_CHILD_NOTIFICATIONS':
                break;
            case 'DISABLE_HOST_CHECK':
                break;
            case 'DISABLE_HOST_EVENT_HANDLER':
                break;
            case 'DISABLE_HOST_FLAP_DETECTION':
                break;
            case 'DISABLE_HOST_FRESHNESS_CHECKS':
                break;
            case 'DISABLE_HOST_NOTIFICATIONS':
                break;
            case 'DISABLE_HOST_SVC_CHECKS':
                break;
            case 'DISABLE_HOST_SVC_NOTIFICATIONS':
                break;
            case 'DISABLE_NOTIFICATIONS':
                break;
            case 'DISABLE_PASSIVE_HOST_CHECKS':
                break;
            case 'DISABLE_PASSIVE_SVC_CHECKS':
                break;
            case 'DISABLE_PERFORMANCE_DATA':
                break;
            case 'DISABLE_SERVICEGROUP_HOST_CHECKS':
                break;
            case 'DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS':
                break;
            case 'DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS':
                break;
            case 'DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS':
                break;
            case 'DISABLE_SERVICEGROUP_SVC_CHECKS':
                break;
            case 'DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS':
                break;
            case 'DISABLE_SERVICE_FLAP_DETECTION':
                break;
            case 'DISABLE_SERVICE_FRESHNESS_CHECKS':
                break;
            case 'DISABLE_SVC_CHECK':
                break;
            case 'DISABLE_SVC_EVENT_HANDLER':
                break;
            case 'DISABLE_SVC_FLAP_DETECTION':
                break;
            case 'DISABLE_SVC_NOTIFICATIONS':
                break;
            case 'ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST':
                break;
            case 'ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS':
                break;
            case 'ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS':
                break;
            case 'ENABLE_CONTACT_HOST_NOTIFICATIONS':
                break;
            case 'ENABLE_CONTACT_SVC_NOTIFICATIONS':
                break;
            case 'ENABLE_EVENT_HANDLERS':
                break;
            case 'ENABLE_FAILURE_PREDICTION':
                break;
            case 'ENABLE_FLAP_DETECTION':
                break;
            case 'ENABLE_HOSTGROUP_HOST_CHECKS':
                break;
            case 'ENABLE_HOSTGROUP_HOST_NOTIFICATIONS':
                break;
            case 'ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS':
                break;
            case 'ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS':
                break;
            case 'ENABLE_HOSTGROUP_SVC_CHECKS':
                break;
            case 'ENABLE_HOSTGROUP_SVC_NOTIFICATIONS':
                break;
            case 'ENABLE_HOST_AND_CHILD_NOTIFICATIONS':
                break;
            case 'ENABLE_HOST_CHECK':
                break;
            case 'ENABLE_HOST_EVENT_HANDLER':
                break;
            case 'ENABLE_HOST_FLAP_DETECTION':
                break;
            case 'ENABLE_HOST_FRESHNESS_CHECKS':
                break;
            case 'ENABLE_HOST_NOTIFICATIONS':
                break;
            case 'ENABLE_HOST_SVC_CHECKS':
                break;
            case 'ENABLE_HOST_SVC_NOTIFICATIONS':
                break;
            case 'ENABLE_NOTIFICATIONS':
                break;
            case 'ENABLE_PASSIVE_HOST_CHECKS':
                break;
            case 'ENABLE_PASSIVE_SVC_CHECKS':
                break;
            case 'ENABLE_PERFORMANCE_DATA':
                break;
            case 'ENABLE_SERVICEGROUP_HOST_CHECKS':
                break;
            case 'ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS':
                break;
            case 'ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS':
                break;
            case 'ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS':
                break;
            case 'ENABLE_SERVICEGROUP_SVC_CHECKS':
                break;
            case 'ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS':
                break;
            case 'ENABLE_SERVICE_FRESHNESS_CHECKS':
                break;
            case 'ENABLE_SVC_CHECK':
                break;
            case 'ENABLE_SVC_EVENT_HANDLER':
                break;
            case 'ENABLE_SVC_FLAP_DETECTION':
                break;
            case 'ENABLE_SVC_NOTIFICATIONS':
                break;
            case 'PROCESS_FILE':
                break;
            case 'PROCESS_HOST_CHECK_RESULT':
                break;
            case 'PROCESS_SERVICE_CHECK_RESULT':
                break;
            case 'READ_STATE_INFORMATION':
                break;
            case 'REMOVE_HOST_ACKNOWLEDGEMENT':
                break;
            case 'REMOVE_SVC_ACKNOWLEDGEMENT':
                break;
            case 'RESTART_PROGRAM':
                break;
            case 'SAVE_STATE_INFORMATION':
                break;
            case 'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME':
                break;
            case 'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME':
                break;
            case 'SCHEDULE_FORCED_HOST_CHECK':
                break;
            case 'SCHEDULE_FORCED_HOST_SVC_CHECKS':
                break;
            case 'SCHEDULE_FORCED_SVC_CHECK':
                break;
            case 'SCHEDULE_HOSTGROUP_HOST_DOWNTIME':
                break;
            case 'SCHEDULE_HOSTGROUP_SVC_DOWNTIME':
                break;
            case 'SCHEDULE_HOST_CHECK':
                break;
            case 'SCHEDULE_HOST_DOWNTIME':
                break;
            case 'SCHEDULE_HOST_SVC_CHECKS':
                break;
            case 'SCHEDULE_HOST_SVC_DOWNTIME':
                break;
            case 'SCHEDULE_SERVICEGROUP_HOST_DOWNTIME':
                break;
            case 'SCHEDULE_SERVICEGROUP_SVC_DOWNTIME':
                break;
            case 'SCHEDULE_SVC_CHECK':
                break;
            case 'SCHEDULE_SVC_DOWNTIME':
                break;
            case 'SEND_CUSTOM_HOST_NOTIFICATION':
                break;
            case 'SEND_CUSTOM_SVC_NOTIFICATION':
                break;
            case 'SET_HOST_NOTIFICATION_NUMBER':
                break;
            case 'SET_SVC_NOTIFICATION_NUMBER':
                break;
            case 'SHUTDOWN_PROGRAM':
                break;
            case 'START_ACCEPTING_PASSIVE_HOST_CHECKS':
                break;
            case 'START_ACCEPTING_PASSIVE_SVC_CHECKS':
                break;
            case 'START_EXECUTING_HOST_CHECKS':
                break;
            case 'START_EXECUTING_SVC_CHECKS':
                break;
            case 'START_OBSESSING_OVER_HOST':
                break;
            case 'START_OBSESSING_OVER_HOST_CHECKS':
                break;
            case 'START_OBSESSING_OVER_SVC':
                break;
            case 'START_OBSESSING_OVER_SVC_CHECKS':
                break;
            case 'STOP_ACCEPTING_PASSIVE_HOST_CHECKS':
                break;
            case 'STOP_ACCEPTING_PASSIVE_SVC_CHECKS':
                break;
            case 'STOP_EXECUTING_HOST_CHECKS':
                break;
            case 'STOP_EXECUTING_SVC_CHECKS':
                break;
            case 'STOP_OBSESSING_OVER_HOST':
                break;
            case 'STOP_OBSESSING_OVER_HOST_CHECKS':
                break;
            case 'STOP_OBSESSING_OVER_SVC':
                break;
            case 'STOP_OBSESSING_OVER_SVC_CHECKS':
                break;
        }
    }
    */

}
