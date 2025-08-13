<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Command;

use App\Lib\DebugConfigNagiosTask;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * Dump command.
 */
class DebugCommand extends Command {

    /**
     * @var ConsoleIo
     */
    private $io;

    private $conf = [];

    /**
     * @var DebugConfigNagiosTask
     */
    private $DebugConfigNagios;

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
            'tail'      => ['boolean' => true, 'help' => __d('oitc_console', 'Tail and parse monitoring logfile')],
            'tailf'     => ['short' => 't', 'boolean' => true, 'help' => __d('oitc_console', 'Tailf and parse monitoring logfile')],
            'stdin'     => ['short' => 's', 'boolean' => true, 'help' => __d('oitc_console', 'Read and translate from stdin. Example: cat file.cfg | oitc debug -s')],
            'timestamp' => ['boolean' => true, 'help' => __d('oitc_console', 'Replace timestamps in output - only for --stdin')],
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
        $io->setStyle('red_bold', ['text' => 'red', 'bold' => true]);
        $this->io = $io;

        Configure::load('nagios');
        $this->conf = Configure::read('nagios.export');

        /** @var DebugConfigNagiosTask DebugConfigNagios */
        $this->DebugConfigNagios = new DebugConfigNagiosTask($io);

        if ($args->getOption('stdin')) {
            $this->DebugConfigNagios->setup($this->conf);
            $replaceTimestamps = $args->getOption('timestamp') === true;
            $this->DebugConfigNagios->translateStdin($replaceTimestamps);
            exit(0);
        }
        if ($args->getOption('debug')) {
            $this->monitoringMenu();
        }
        if ($args->getOption('tail')) {
            $this->DebugConfigNagios->setup($this->conf);
            $this->DebugConfigNagios->parseMonitoringLogfile();
        }
        if ($args->getOption('tailf')) {
            $this->DebugConfigNagios->setup($this->conf);
            $this->DebugConfigNagios->tailf();
        }

        $this->monitoringMenu();
    }

    private function monitoringMenu() {
        $this->io->out(__d('oitc_console', '[T]ail and parse monitoring log file'));
        $this->io->out(__d('oitc_console', '[TF] Tail -f and parse monitoring log file'));
        $this->io->out(__d('oitc_console', '[H] Debug host configuration files'));
        $this->io->out(__d('oitc_console', '[HG] Debug host group configuration files'));
        $this->io->out(__d('oitc_console', '[HT] Debug host template configuration files'));
        $this->io->out(__d('oitc_console', '[S] Debug service configuration files'));
        $this->io->out(__d('oitc_console', '[SG] Debug service group configuration files'));
        $this->io->out(__d('oitc_console', '[ST] Debug service template configuration files'));
        $this->io->out(__d('oitc_console', '[TP] Debug time period configuration files'));
        $this->io->out(__d('oitc_console', '[CM] Debug command configuration files'));
        $this->io->out(__d('oitc_console', '[C] Debug contact configuration files'));
        $this->io->out(__d('oitc_console', '[CG] Debug contact group configuration files'));
        $this->io->out(__d('oitc_console', '[HE] Debug host escalation configuration files'));
        $this->io->out(__d('oitc_console', '[SE] Debug service escalation configuration files'));
        $this->io->out(__d('oitc_console', '[HD] Debug host dependency configuration files'));
        $this->io->out(__d('oitc_console', '[SD] Debug service dependency configuration files'));
        $this->io->out(__d('oitc_console', '[UUID] Search object by UUID'));
        $this->io->out(__d('oitc_console', '[Q]uit'));

        $menuSelection = strtoupper($this->io->askChoice(
            __d('oitc_console',
                'What would you like to do?'),
            ['T', 'TF', 'H', 'HG', 'HT', 'S', 'SG', 'ST', 'TP', 'CM', 'C', 'CG', 'HE', 'SE', 'HD', 'SD', 'UUID', 'Q']));
        $this->DebugConfigNagios->setup($this->conf);
        switch ($menuSelection) {
            case 'T':
                $this->DebugConfigNagios->parseMonitoringLogfile();
                break;
            case 'TF':
                $this->DebugConfigNagios->tailf();
                break;
            case 'H':
                $this->DebugConfigNagios->debug('Hosts', 'hosts');
                break;
            case 'HG':
                $this->DebugConfigNagios->debug('Hostgroups', 'hostgroups');
                break;
            case 'HT':
                $this->DebugConfigNagios->debug('Hosttemplates', 'hosttemplates');
                break;
            case 'S':
                $this->DebugConfigNagios->debug('Services', 'services');
                break;
            case 'SG':
                $this->DebugConfigNagios->debug('Servicegroups', 'servicegroups');
                break;
            case 'ST':
                $this->DebugConfigNagios->debug('Servicetemplates', 'servicetemplates');
                break;
            case 'TP':
                $this->DebugConfigNagios->debug('Timeperiods', 'timeperiods');
                break;
            case 'CM':
                $this->DebugConfigNagios->debug('Commands', 'commands');
                break;
            case 'C':
                $this->DebugConfigNagios->debug('Contacts', 'contacts');
                break;
            case 'CG':
                $this->DebugConfigNagios->debug('Contactgroups', 'contactgroups');
                break;
            case 'HE':
                $this->DebugConfigNagios->debug('Hostescalations', 'hostescalations');
                break;
            case 'SE':
                $this->DebugConfigNagios->debug('Serviceescalations', 'serviceescalations');
                break;
            case 'HD':
                $this->DebugConfigNagios->debug('Hostdependencies', 'hostdependencies');
                break;
            case 'SD':
                $this->DebugConfigNagios->debug('Servicedependencies', 'servicedependencies');
                break;
            case 'UUID':
                $this->DebugConfigNagios->debugByUuid();
                break;
            case 'Q':
                return $this->_exit();
            default:
                $this->io->out(__d('oitc_console', 'You have made an invalid selection. Please choose by entering T or B.'));
        }

        $this->io->hr();
        $this->monitoringMenu();
    }

    private function _exit() {
        $this->io->out(__d('oitc_console', 'Hopefully i was helpful'));
        $this->io->out(__d('oitc_console', 'Thanks for using me, bye'));
        exit();
    }
}
