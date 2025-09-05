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

declare(strict_types=1);

namespace App\Command;

use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\Service;

/*************************************/
/*           LEGACY WARNING          */
/* This is legacy code and will be   */
/* removed with a newer version      */
/*************************************/

/**
 * SmsNotification command.
 * @deprecated Please use a newer SMS solution. This feature will be removed with a newer version
 *
 * Usage:
 *  Host:
 *   oitc sms_notification --address 128.1.1.85 -m nrpe --type host --hostname c36b8048-93ce-4385-ac19-ab5c90574b77 --contactpager 0049123456789 --notificationtype PROBLEM
 *
 *  Monitoring command:
 *   /opt/openitc/frontend/bin/cake sms_notification -q --address 128.1.1.85 -m nrpe --type Host --contactpager $CONTACTPAGER$ --hostname "$HOSTNAME$" --notificationtype "$NOTIFICATIONTYPE$"
 *
 *  Services:
 *   oitc sms_notification --address 128.1.1.85 -m nrpe --type service --hostname c36b8048-93ce-4385-ac19-ab5c90574b77 --servicedesc 74f14950-a58f-4f18-b6c3-5cfa9dffef4e --contactpager 0049123456789 --notificationtype PROBLEM
 *
 *  Monitoring command:
 *   /opt/openitc/frontend/bin/cake sms_notification -q --address 128.1.1.85 -m nrpe --type Service --contactpager $CONTACTPAGER$ --hostname "$HOSTNAME$" --servicedesc "$SERVICEDESC$" --notificationtype "$NOTIFICATIONTYPE$"
 */
class SmsNotificationCommand extends Command {

    /*************************************/
    /*           LEGACY WARNING          */
    /* This is legacy code and will be   */
    /* removed with a newer version      */
    /*************************************/

    /**
     * @var array
     */
    private $config;

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

        $parser->addOptions([
            'address'          => ['help' => __d('oitc_console', 'IP address of the SMS gateway')],
            'type'             => ['short' => 't', 'help' => __d('oitc_console', 'Type of the notification host or service')],
            'notificationtype' => ['help' => __d('oitc_console', 'Notification type of monitoring engine')],
            'method'           => ['short' => 'm', 'help' => __d('oitc_console', 'Transport method for example NRPE')],
            'hostname'         => ['help' => __d('oitc_console', 'Host uuid you want to send a notification')],
            'contactpager'     => ['help' => __d('oitc_console', 'recivers mail address')],
            'servicedesc'      => ['help' => __d('oitc_console', 'Service uuid you want to notify')],
        ]);


        return $parser;
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int|void|null
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->config = [
            'nrpe' => [
                'port'                     => 5666,
                'timeout'                  => 120,
                'check_nrpe'               => '/opt/openitc/nagios/libexec/check_nrpe',
                'command_template_host'    => ' -H %s -p %s -t %s -c send_sms -a %s %s %s %s %s "cmd ak %s"',
                'command_template_service' => ' -H %s -p %s -t %s -c send_sms -a %s %s %s %s %s "cmd ak %s %s"',
                'date_format'              => 'd.m.Y H:i:s',
            ],
        ];

        $DbBackend = new DbBackend();


        $address = $args->getOption('address');
        $type = strtolower($args->getOption('type'));

        if ($type === 'host') {
            $hostUuid = $args->getOption('hostname');
            $Host = $this->getHost($hostUuid);

            $HoststatusTable = $DbBackend->getHoststatusTable();

            $HoststatusFields = new HoststatusFields($DbBackend);
            $HoststatusFields->output();

            $hoststatus = $HoststatusTable->byUuid($hostUuid, $HoststatusFields);

            $args = vsprintf($this->config['nrpe']['command_template_host'], [
                escapeshellarg($address),
                escapeshellarg((string)$this->config['nrpe']['port']),
                escapeshellarg((string)$this->config['nrpe']['timeout']),
                escapeshellarg($args->getOption('contactpager')),
                escapeshellarg($args->getOption('notificationtype')),
                escapeshellarg($Host->getHostname()),
                escapeshellarg($hoststatus['Hoststatus']['output']),
                escapeshellarg(date($this->config['nrpe']['date_format'])),
                escapeshellarg($Host->getHostname()),
            ]);

            $command = $this->config['nrpe']['check_nrpe'] . $args;
            //debug($command);
            exec($command);
        }

        if ($type === 'service') {
            $hostUuid = $args->getOption('hostname');
            $serviceUuid = $args->getOption('servicedesc');

            $Host = $this->getHost($hostUuid);
            $Service = $this->getService($serviceUuid);

            $ServicestatusTable = $DbBackend->getServicestatusTable();


            $ServicestatusFields = new ServicestatusFields($DbBackend);
            $ServicestatusFields->output();
            $servicestatus = $ServicestatusTable->byUuid($serviceUuid, $ServicestatusFields);
            $args = vsprintf($this->config['nrpe']['command_template_service'], [
                escapeshellarg($address),
                escapeshellarg((string)$this->config['nrpe']['port']),
                escapeshellarg((string)$this->config['nrpe']['timeout']),
                escapeshellarg($args->getOption('contactpager')),
                escapeshellarg($args->getOption('notificationtype')),
                escapeshellarg($Host->getHostname() . '/' . $Service->getServicename()),
                escapeshellarg($servicestatus['Servicestatus']['output']),
                escapeshellarg(date($this->config['nrpe']['date_format'])),
                escapeshellarg($Host->getHostname()),
                escapeshellarg($Service->getServicename()),
            ]);

            $command = $this->config['nrpe']['check_nrpe'] . $args;
            //debug($command);
            exec($command);
        }
    }

    /**
     * @param string $hostUuid
     * @return Host
     */
    public function getHost(string $hostUuid) {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        try {
            $host = $HostsTable->getHostByUuid($hostUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new \RuntimeException(sprintf('Host with uuid "%s" could not be found!', $hostUuid));
        }

        return new Host($host);
    }

    /**
     * @param string $serviceUuid
     * @return Service
     */
    public function getService(string $serviceUuid) {
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        try {
            $service = $ServicesTable->getServiceByUuid($serviceUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new \RuntimeException(sprintf('Service with uuid "%s" could not be found!', $serviceUuid));
        }

        return new Service($service);
    }

}
