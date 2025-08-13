<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\MonitoringEngine\NagiosConfigDefaults;
use itnovum\openITCOCKPIT\Core\MonitoringEngine\NagiosConfigGenerator;
use itnovum\openITCOCKPIT\Core\System\Health\MonitoringEngine;

/**
 * NagiosExport command.
 */
class NagiosExportCommand extends Command {
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

        $parser->addOption('verify', ['help' => 'Verify Nagios/Naemon configuration files and exit.', 'boolean' => true]);
        $parser->addOption('resource', ['help' => 'Only write the file /opt/openitc/nagios/etc/resource.cfg', 'boolean' => true, 'default' => false]);
        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     * @throws \Exception
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        Configure::load('nagios');

        if ($args->getOption('verify')) {
            $io->info(__('Verifying monitoring configuration files, please standby...'));

            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $systemsettings = $SystemsettingsTable->findAsArray();

            $naemonBin = Configure::read('nagios.basepath') . Configure::read('nagios.bin') . Configure::read('nagios.nagios_bin');
            $naemonCfg = Configure::read('nagios.nagios_cfg');

            $cmd = sprintf(
                'sudo -u %s %s -v %s',
                escapeshellarg($systemsettings['MONITORING']['MONITORING.USER']),
                $naemonBin,
                $naemonCfg
            );

            exec($cmd, $output, $rc);
            foreach ($output as $line) {
                $io->out($line);
            }

            if ($rc > 0) {
                Log::error('Error with Naemon / Nagios configuration! Verification failed!');
            }

            exit($rc);
        }

        if ($args->getOption('resource')) {
            $io->info('Generating /opt/openitc/nagios/etc/resource.cfg');
            $NagiosConfigGenerator = new NagiosConfigGenerator();
            $NagiosConfigGenerator->exportMacros();
            exit(0);
        }

        $io->info('Generating new monitoring configuration. This could take awhile...');

        //Export complete Naemon/Nagios configuration
        $NagiosConfigGenerator = new NagiosConfigGenerator();
        $NagiosConfigDefaults = new NagiosConfigDefaults();

        $NagiosConfigGenerator->beforeExportExternalTasks();
        $NagiosConfigGenerator->deleteAllConfigfiles();

        $NagiosConfigDefaults->execute();

        $NagiosConfigGenerator->exportHosttemplates();
        $NagiosConfigGenerator->exportHosts();
        $NagiosConfigGenerator->exportCommands();
        $NagiosConfigGenerator->exportContacts();
        $NagiosConfigGenerator->exportContactgroups();
        $NagiosConfigGenerator->exportTimeperiods();
        $NagiosConfigGenerator->exportHostgroups();
        $NagiosConfigGenerator->exportHostescalations();
        $NagiosConfigGenerator->exportMacros();
        $NagiosConfigGenerator->exportServicetemplates();
        $NagiosConfigGenerator->exportServices();
        $NagiosConfigGenerator->exportServiceescalations();
        $NagiosConfigGenerator->exportServicegroups();
        $NagiosConfigGenerator->exportHostdependencies();
        $NagiosConfigGenerator->exportServicedependencies();
        $NagiosConfigGenerator->afterExportExternalTasks();

        $MonitoringEngine = new MonitoringEngine();
        $io->success('Monitoring configuration successfully generated.');
        if ($MonitoringEngine->isNaemon()) {
            $io->success('Execute "systemctl reload naemon" to load the new configuration');
        } else {
            $io->success('Execute "systemctl reload nagios" to load the new configuration');
        }
        exit(0);
    }


}
