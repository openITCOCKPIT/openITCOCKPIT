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

use App\Model\Table\ConfigurationFilesTable;
use App\Model\Table\ConfigurationQueueTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\ConfigGenerator\ConfigInterface;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;

/**
 * ConfigGenerator command.
 */
class ConfigGeneratorCommand extends Command implements CronjobInterface {
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
        $io->setStyle('red', ['text' => 'red', 'blink' => false]);

        $io->out('Check for pending configuration files...');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        /** @var ConfigurationQueueTable $ConfigurationQueueTable */
        $ConfigurationQueueTable = TableRegistry::getTableLocator()->get('ConfigurationQueue');

        /** @var ConfigurationFilesTable $ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');

        $configFilesToGenerate = $ConfigurationQueueTable->getConfigFilesToGenerate();

        foreach ($configFilesToGenerate as $record) {
            $configFile = $record['data'];
            $classPath = 'itnovum\openITCOCKPIT\ConfigGenerator\%s';
            if (isset($record['module']) && $record['module'] !== null && $record['module'] !== '') {
                $classPath = '\\' . $record['module'] . '\Lib\ConfigGenerator\%s';
            }

            $className = sprintf($classPath, $configFile);
            if (!class_exists($className)) {
                throw new NotFoundException('Config file not found');
            }

            /** @var ConfigInterface $ConfigFileObject */
            $ConfigFileObject = new $className();
            $config = $ConfigurationFilesTable->getConfigValuesByConfigFile($ConfigFileObject->getDbKey());

            $io->out(sprintf('Generate %s   ', $ConfigFileObject->getLinkedOutfile()), 0);
            $ConfigFileObject->writeToFile($config);
            $io->success('    Ok');

            $this->restartByConfigFile($configFile, $systemsettings, $io);

            $ConfigurationQueueTable->delete($ConfigurationQueueTable->get($record['id']));
        }
        $io->success('Ok');
        $io->hr();
    }

    /**
     * @param $configFile
     * @param $systemsettings
     * @param ConsoleIo $io
     *
     * !!!
     * If you change this method, also change it in src/Command/ConfigGeneratorShellCommand.php
     * !!!
     */
    public function restartByConfigFile($configFile, $systemsettings, ConsoleIo $io) {
        switch ($configFile) {
            case 'NagiosCfg':
                $command = $systemsettings['MONITORING']['MONITORING.RESTART'];
                $this->restartService($command, 'Restart Nagios/Naemon core', $io);
                break;

            case 'AfterExport':
                $command = $systemsettings['INIT']['INIT.GEARMAN_WORKER_RESTART'];
                $this->restartService($command, 'Restart gearman_worker service', $io);
                break;

            case 'phpNSTAMaster':
                $command = $systemsettings['INIT']['INIT.PHPNSTA_RESTART'];
                $this->restartService($command, 'Restart phpNSTA service', $io);
                break;

            case 'GraphingDocker':
                $command = $systemsettings['INIT']['INIT.OITC_GRAPHING_RESTART'];
                $this->restartService($command, 'Restart and rebuild openITCOCKPIT-Graphing Docker Containers', $io);
                break;

            case 'StatusengineCfg':
                $command = $systemsettings['INIT']['INIT.STATUSENGINE_RESTART'];
                $this->restartService($command, 'Restart Statusengine service', $io);
                break;

            case 'SnmpTrapCfgs_snmptrapd':
            case 'SnmpTrapCfgs_snmptrapdConf':
                if (isset($systemsettings['INIT']['INIT.SNMPTRAPD_RESTART'])) {
                    $command = $systemsettings['INIT']['INIT.SNMPTRAPD_RESTART'];
                    $this->restartService($command, 'Restart snmptrapd service', $io);
                }
                break;

            case 'SnmpTrapCfgs_snmpttIni':
                if (isset($systemsettings['INIT']['INIT.SNMPTT_RESTART'])) {
                    $command = $systemsettings['INIT']['INIT.SNMPTT_RESTART'];
                    $this->restartService($command, 'Restart snmptt service', $io);
                }
                break;

            case 'PrometheusCfgs_prometheus':
                $this->restartService('prometheus.service', 'Restart Prometheus service', $io);
                $this->restartService('prometheus_bridge.service', 'Restart Prometheus Bridge service', $io);
                break;

            case 'NSTAMaster':
                $command = $systemsettings['INIT']['INIT.NSTA_RESTART'];
                $this->restartService($command, 'Restart NSTA service', $io);
                break;

            case 'Gearman':
                $command = 'systemctl restart gearman_worker';
                $this->restartService($command, 'Restart gearman_worker service', $io);
                break;

            default:
                break;
        }
    }

    /**
     * @param $command
     * @param $outputTxt
     * @param ConsoleIo $io
     */
    private function restartService($command, $outputTxt, ConsoleIo $io) {
        $io->out($outputTxt . '   ', 0);
        exec(escapeshellcmd($command), $output, $returncode);
        if ($returncode > 0) {
            $io->out('<red>Error</red>');
        } else {
            $io->success('Ok');
        }
    }

}
