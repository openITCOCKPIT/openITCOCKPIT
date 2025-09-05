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
use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\ConfigGenerator\ConfigInterface;
use itnovum\openITCOCKPIT\ConfigGenerator\GeneratorRegistry;

/**
 * ConfigGeneratorShell command.
 */
class ConfigGeneratorShellCommand extends Command {

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
            'generate'           => ['help' => "Will generate all configuration files from database", 'boolean' => true, 'default' => false],
            'generate-container' => ['help' => "Will generate all configuration files from environment variables. Only use this option if openITCOCKPIT is running inside a container.", 'boolean' => true, 'default' => false],
            'reload'             => ['help' => "Reload services, where a new configuration file was generated for", 'boolean' => true, 'default' => false],
            'migrate'            => ['help' => 'Will migrate existing configuration files to database', 'boolean' => true, 'default' => false]
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
        $io->setStyle('red', ['text' => 'red', 'blink' => false]);
        $hadJob = false;
        if ($args->getOption('migrate')) {
            $hadJob = true;
            try {
                $this->migrate($io);
            } catch (\Exception $e) {
                $io->out('<red>' . $e->getMessage() . '</red>');
            }
        }

        if ($args->getOption('generate')) {
            // Full installation of openITCOCKPIT (via apt, dnf or git clone)
            $hadJob = true;
            if ($args->getOption('reload')) {
                $this->generateAndReload($io);
            } else {
                $this->generate($io);
            }
        }

        if ($args->getOption('generate-container')) {
            // openITCOCKPIT is running inside a docker container
            $hadJob = true;
            $this->generateFromEnvironmentVariables($io);
        }

        if (!$hadJob) {
            $this->displayHelp($this->getOptionParser(), $args, $io);
        }
    }

    /**
     * @param ConsoleIo $io
     */
    private function generate(ConsoleIo $io) {
        $io->out('Generate all configuration files...    ');
        /** @var ConfigurationFilesTable $ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');

        $GeneratorRegistry = new GeneratorRegistry();

        foreach ($GeneratorRegistry->getAllConfigFiles() as $ConfigFileObject) {
            /** @var ConfigInterface $ConfigFileObject */
            $io->out(sprintf('Generate %s   ', $ConfigFileObject->getLinkedOutfile()), 0);
            $ConfigFileObject->writeToFile($ConfigurationFilesTable->getConfigValuesByConfigFile($ConfigFileObject->getDbKey()));
            $io->success('Ok');
        }
    }

    /**
     * @param ConsoleIo $io
     */
    private function generateFromEnvironmentVariables(ConsoleIo $io) {
        $io->out('Generate all configuration files from environment variables...    ');
        /** @var ConfigurationFilesTable $ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');

        $GeneratorRegistry = new GeneratorRegistry();

        foreach ($GeneratorRegistry->getAllConfigFilesForContainer() as $ConfigFileObject) {
            /** @var ConfigInterface $ConfigFileObject */
            $io->out(sprintf('Generate %s   ', $ConfigFileObject->getLinkedOutfile()), 0);
            $ConfigFileObject->writeToFile($ConfigFileObject->getValuesFromEnvironment());
            $io->success('Ok');
        }
    }

    /**
     * @param ConsoleIo $io
     */
    private function generateAndReload(ConsoleIo $io) {
        $io->out('Generate all configuration files...    ');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        /** @var $ConfigurationFilesTable ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');

        $GeneratorRegistry = new GeneratorRegistry();

        foreach ($GeneratorRegistry->getAllConfigFiles() as $ConfigFileObject) {
            /** @var ConfigInterface $ConfigFileObject */
            $io->out(sprintf('Generate %s', $ConfigFileObject->getLinkedOutfile()));
            $ConfigFileObject->writeToFile($ConfigurationFilesTable->getConfigValuesByConfigFile($ConfigFileObject->getDbKey()));
            $this->restartByConfigFile($ConfigFileObject->getDbKey(), $systemsettings, $io);
        }
    }


    /**
     * @param ConsoleIo $io
     * @throws \Exception
     */
    private function migrate(ConsoleIo $io) {
        $io->out('Migrate existing configuration files to database...    ');
        /** @var $ConfigurationFilesTable ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');

        $GeneratorRegistry = new GeneratorRegistry();
        foreach ($GeneratorRegistry->getAllConfigFiles() as $ConfigFileObject) {
            /** @var ConfigInterface $ConfigFileObject */
            $io->out(sprintf('Processing %s   ', $ConfigFileObject->getLinkedOutfile()), 0);

            $dbConfig = $ConfigurationFilesTable->getConfigValuesByConfigFile($ConfigFileObject->getDbKey());
            $config = $ConfigFileObject->migrate($dbConfig);
            if (is_array($config)) {
                $configFileForDatabase = $ConfigFileObject->convertRequestForSaveAll($config);
                $ConfigurationFilesTable->saveConfigurationValuesForConfigFile($ConfigFileObject->getDbKey(), $configFileForDatabase);
                $io->success('Ok');
            } else {
                $io->info('Skipping');
            }
        }
    }

    /**
     * @param $configFile
     * @param $systemsettings
     * @param ConsoleIo $io
     *
     * !!!
     * If you change this method, also change it in src/Command/ConfigGeneratorCommand.php
     * !!!
     */
    private function restartByConfigFile($configFile, $systemsettings, ConsoleIo $io) {
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

            case 'PhpFpmOitc':
                $version = substr(PHP_VERSION, 0, 3);
                $command = sprintf("systemctl restart php%s-fpm", $version);
                $this->restartService($command, sprintf('Restart php%s-fpm service', $version), $io);
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
