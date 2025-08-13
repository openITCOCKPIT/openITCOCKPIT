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

use App\Model\Table\CommandsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\CronjobsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\MacrosTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\TimeperiodsTable;
use App\Model\Table\UsergroupsTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Error\Debugger;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\DbBackend;

/**
 * Dump command.
 */
class DumpCommand extends Command {

    /**
     * @var DbBackend
     */
    private $DbBackend;

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

        $parser->addOptions([
            'macros'           => ['help' => 'Dump all $USERx$ macros of the database into a file on disk', 'boolean' => true, 'default' => false],
            'cronjobs'         => ['help' => 'Dump all cronjobs of the database into a file on disk', 'boolean' => true, 'default' => false],
            'systemsettings'   => ['help' => 'Dump all systemsettings of the database into a file on disk', 'boolean' => true, 'default' => false],
            'commands'         => ['help' => 'Dump all commands of the database into a file on disk', 'boolean' => true, 'default' => false],
            'container'        => ['help' => 'Dump all container of the database into a file on disk', 'boolean' => true, 'default' => false],
            'timeperiods'      => ['help' => 'Dump all timeperiods of the database into a file on disk', 'boolean' => true, 'default' => false],
            'contacts'         => ['help' => 'Dump all contacts of the database into a file on disk', 'boolean' => true, 'default' => false],
            'usergroups'       => ['help' => 'Dump all usergroups of the database into a file on disk', 'boolean' => true, 'default' => false],
            'hosttemplates'    => ['help' => 'Dump all hosttemplates of the database into a file on disk', 'boolean' => true, 'default' => false],
            'hosts'            => ['help' => 'Dump all hosts of the database into a file on disk', 'boolean' => true, 'default' => false],
            'servicetemplates' => ['help' => 'Dump all servicetemplates of the database into a file on disk', 'boolean' => true, 'default' => false],
            'services'         => ['help' => 'Dump all services of the database into a file on disk', 'boolean' => true, 'default' => false],

            'all' => ['help' => 'Dump all data of listed options of the database into a file on disk', 'boolean' => true, 'default' => false],
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

        //       !!! IMPORTANT !!!
        // Make sure to keep the right order
        // Same as in InstallSeed.php of the Core!

        $this->DbBackend = new DbBackend();
        $this->io = $io;
        $all = $args->getOption('all');

        if ($args->getOption('macros') === true || $all) {
            $this->macros();
        }

        if ($args->getOption('cronjobs') === true || $all) {
            $this->cronjobs();
        }

        if ($args->getOption('systemsettings') === true || $all) {
            $this->systemsettings();
        }

        if ($args->getOption('commands') === true || $all) {
            $this->commands();
        }

        if ($args->getOption('container') === true || $all) {
            $this->container();
        }

        if ($args->getOption('timeperiods') === true || $all) {
            $this->timeperiods();
        }

        if ($args->getOption('contacts') === true || $all) {
            $this->contacts();
        }

        if ($args->getOption('usergroups') === true || $all) {
            $this->usergroups();
        }

        if ($args->getOption('hosttemplates') === true || $all) {
            $this->hosttemplates();
        }

        if ($args->getOption('hosts') === true || $all) {
            $this->hosts();
        }

        if ($args->getOption('servicetemplates') === true || $all) {
            $this->servicetemplates();
        }

        if ($args->getOption('services') === true || $all) {
            $this->services();
        }


        //Handle all logic before this foreach!!
        foreach ($args->getOptions() as $option) {
            if ($option === true) {

                //One option like --help or --commands was set.
                exit(0);
            }
        }

        $io->error('No options set. Use --help to get more information about this command.');

    }

    public function macros() {
        /** @var MacrosTable $MacrosTable */
        $MacrosTable = TableRegistry::getTableLocator()->get('Macros');


        $macros = $MacrosTable->find()
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($macros->toArray(), 'macros');
    }

    public function cronjobs() {
        /** @var CronjobsTable $CronjobsTable */
        $CronjobsTable = TableRegistry::getTableLocator()->get('Cronjobs');


        $cronjobs = $CronjobsTable->find()
            ->contain([
                'Cronschedules' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                }
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($cronjobs->toArray(), 'cronjobs');
    }

    public function systemsettings() {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');


        $systemsettings = $SystemsettingsTable->find()
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($systemsettings->toArray(), 'systemsettings');
    }

    public function commands() {
        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');


        $commands = $CommandsTable->find()
            ->contain([
                'Commandarguments' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                }
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($commands->toArray(), 'commands');
    }

    public function container() {
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');


        $container = $ContainersTable->find()
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($container->toArray(), 'containers');
    }

    public function timeperiods() {
        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');


        $timeperiods = $TimeperiodsTable->find()
            ->contain([
                'TimeperiodTimeranges' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                }
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($timeperiods->toArray(), 'timeperiods');
    }

    public function contacts() {
        /** @var ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');

        $timeperiods = $ContactsTable->find()
            ->contain([
                'Containers'      => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Customvariables' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                }
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($timeperiods->toArray(), 'contacts');
    }

    public function usergroups() {
        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        $usergroups = $UsergroupsTable->find()
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $this->dumpToFile($usergroups->toArray(), 'usergroups');
    }

    public function hosttemplates() {
        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        $hosttemplates = $HosttemplatesTable->find()
            ->contain([
                'Contacts'                          => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Contactgroups'                     => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Hostgroups'                        => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Customvariables'                   => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Hosttemplatecommandargumentvalues' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $hosttemplates = $hosttemplates->toArray();

        $onlyIds = [
            'contacts',
            'contactgroups',
            'hostgroups'
        ];
        foreach ($hosttemplates as $index => $hosttemplate) {
            foreach ($onlyIds as $fieldName) {
                if (!empty($hosttemplate[$fieldName])) {
                    $hosttemplates[$index][$fieldName] = [
                        '_ids' => Hash::extract($hosttemplate[$fieldName], '{n}.id')
                    ];
                }
            }
        }

        $this->dumpToFile($hosttemplates, 'hosttemplates');
    }

    public function hosts() {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $hosts = $HostsTable->find()
            ->contain([
                'HostsToContainersSharing'  => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Contacts'                  => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Contactgroups'             => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Hostgroups'                => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Customvariables'           => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Hostcommandargumentvalues' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $hosts = $hosts->toArray();

        $onlyIds = [
            'contacts',
            'contactgroups',
            'hostgroups'
        ];
        foreach ($hosts as $index => $host) {
            if (!empty($host['hosts_to_containers_sharing'])) {
                $hosts[$index]['hosts_to_containers_sharing'] = [
                    '_ids' => array_unique(Hash::extract($host['hosts_to_containers_sharing'], '{n}._joinData.container_id'))
                ];
            }

            foreach ($onlyIds as $fieldName) {
                if (!empty($host[$fieldName])) {
                    $hosts[$index][$fieldName] = [
                        '_ids' => Hash::extract($host[$fieldName], '{n}.id')
                    ];
                }
            }
        }

        $this->dumpToFile($hosts, 'hosts');
    }

    public function servicetemplates() {
        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $servicetemplates = $ServicetemplatesTable->find()
            ->contain([
                'Contacts'                                  => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Contactgroups'                             => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Servicegroups'                             => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Customvariables'                           => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Servicetemplatecommandargumentvalues'      => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Servicetemplateeventcommandargumentvalues' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $servicetemplates = $servicetemplates->toArray();

        $onlyIds = [
            'contacts',
            'contactgroups',
            'servicegroups'
        ];
        foreach ($servicetemplates as $index => $servicetemplate) {
            foreach ($onlyIds as $fieldName) {
                if (!empty($servicetemplate[$fieldName])) {
                    $servicetemplates[$index][$fieldName] = [
                        '_ids' => Hash::extract($servicetemplate[$fieldName], '{n}.id')
                    ];
                }
            }
        }

        $this->dumpToFile($servicetemplates, 'servicetemplates');
    }

    public function services() {
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $services = $ServicesTable->find()
            ->contain([
                'Contacts'                          => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Contactgroups'                     => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Servicegroups'                     => function (Query $query) {
                    $query->select(['id']);
                    $query->disableResultsCasting();
                    return $query;
                },
                'Customvariables'                   => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Servicecommandargumentvalues'      => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
                'Serviceeventcommandargumentvalues' => function (Query $query) {
                    $query->disableResultsCasting();
                    return $query;
                },
            ])
            ->disableHydration()
            ->disableResultsCasting()
            ->all();

        $services = $services->toArray();

        $onlyIds = [
            'contacts',
            'contactgroups',
            'servicegroups'
        ];
        foreach ($services as $index => $service) {
            foreach ($onlyIds as $fieldName) {
                if (!empty($service[$fieldName])) {
                    $services[$index][$fieldName] = [
                        '_ids' => Hash::extract($service[$fieldName], '{n}.id')
                    ];
                }
            }
        }

        $this->dumpToFile($services, 'services');
    }

    private function dumpToFile(array $data, string $filename) {
        $fullFilename = sprintf('/tmp/dump_%s.php', $filename);
        $this->io->info('Dumping data to: ' . $fullFilename, 0);

        $file = fopen($fullFilename, 'w+');
        fwrite($file, '<?php' . PHP_EOL . '$data = ');
        fwrite($file, Debugger::exportVar($data, 10));
        fwrite($file, ';' . PHP_EOL);
        fclose($file);

        $this->io->success('    Done');
    }
}
