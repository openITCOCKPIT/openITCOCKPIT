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

use App\itnovum\openITCOCKPIT\Supervisor\Supervisorctl;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Supervisor command.
 */
class SupervisorCommand extends Command {
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addArgument('command', [
            'help'     => 'Command to execute',
            'required' => true,
            'choices'  => ['start', 'stop', 'restart', 'status'],
        ]);

        $parser->addArgument('service_name', [
            'help'     => 'Command to execute',
            'required' => true,
            'choices'  => [
                // If you edit this like, also make sure to edit
                // Supervisorctl::getSupervisorApiEndpointByServiceName()

                // openITCOCKPIT background
                'oitc_cmd',
                'sudo_server',
                'gearman_worker',
                'event-collectd',     //todo
                'push_notification',
                'prometheus_bridge',  //todo
                'customalert_worker', //todo

                // Monitoring Engines
                'naemon',
                'prometheus',
                'nsta',

                // Statusengine
                'statusengine',

                // puppeteer / PDF generation
                'openitcockpit-node',

                // System
                'nginx',
                'php-fpm',
                'snmptrapd',
                'snmptt',
            ],
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
        $command = $args->getArgument('command');
        $serviceName = $args->getArgument('service_name');

        $Supervisorctl = new Supervisorctl();

        switch ($command) {
            case 'start':
                $result = $Supervisorctl->start($serviceName);
                if ($result === true) {
                    $io->out(sprintf('Service %s started ', $serviceName), 0);
                    $io->success('successfully.', 1);
                    break;
                }

                print_r($result);
                break;

            case 'stop':
                $result = $Supervisorctl->stop($serviceName);
                if ($result === true) {
                    $io->out(sprintf('Service %s stopped ', $serviceName), 0);
                    $io->success('successfully.', 1);
                    break;
                }

                print_r($result);
                break;

            case 'restart':
                $result = $Supervisorctl->stop($serviceName);
                if ($result === true) {
                    $io->out(sprintf('Service %s stopped ', $serviceName), 0);
                    $io->success('successfully.', 1);
                } else {
                    print_r($result);
                }

                $result = $Supervisorctl->start($serviceName);
                if ($result === true) {
                    $io->out(sprintf('Service %s started ', $serviceName), 0);
                    $io->success('successfully.', 1);
                } else {
                    print_r($result);
                }
                break;

            default:
                $result = $Supervisorctl->status($serviceName);
                if ($result['statename'] === 'RUNNING') {
                    $io->out(sprintf('Service %s is ', $serviceName), 0);
                    $io->success('running. ', 0);
                    $io->out($result['description']);
                    break;
                }

                if ($result['statename'] === 'STOPPED') {
                    $io->out(sprintf('Service %s is ', $serviceName), 0);
                    $io->error('stopped.');
                    break;
                }

                // Unknown state
                print_r($result);
        }

    }
}
