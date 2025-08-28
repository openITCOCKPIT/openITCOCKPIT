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

use App\itnovum\openITCOCKPIT\Database\Backup;
use App\Model\Table\ContainersTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * ContainerRecover command.
 *
 * Usage:
 * oitc container_recover --check
 * oitc container_recover --recover
 */
class ContainerRecoverCommand extends Command {
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOptions([
            'check'   => ['help' => 'Will check if any any errors are within the container tree', 'boolean' => true, 'default' => false],
            'recover' => ['help' => 'Will recover the container tree using the parent_id field', 'boolean' => true, 'default' => false],
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

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        // Run --check option
        if ($args->getOption('check') === true) {

            $orphanedContainers = $ContainersTable->getOrphanedContainers();
            if (empty($orphanedContainers)) {
                $io->success('No orphaned containers found.');
                $io->success('Nothing to do.');

                // Exit command
                return 0;
            }


            $io->error('Orphaned containers found! Checking if an automatically repair is possible...');
            $io->info('This is a read-only operation. No data is being changed.');
            $autoRepairPossible = true;
            foreach ($orphanedContainers as $orphanedContainer) {
                // This container has a parent_id that does not exist.
                // Before it is safe to delete this container, we need to make sure that it is not in used by any object
                // Such as hosts, host groups, service templates, service template groups etc...
                $io->info(sprintf(
                    'Found orphaned Container "%s" (ID: %s, Type: %s)',
                    $orphanedContainer['name'],
                    $orphanedContainer['id'],
                    $orphanedContainer['containertype_id']
                ));

                if ($ContainersTable->isOrphanedContainerInUse($orphanedContainer['id'])) {
                    $autoRepairPossible = false;
                    $io->error(sprintf(
                        'The container "%s" (ID: %s, Type: %s) is in use by other objects. Please see the file /opt/openitc/frontend/logs/cli-error.log for more information',
                        $orphanedContainer['name'],
                        $orphanedContainer['id'],
                        $orphanedContainer['containertype_id']
                    ));
                }
            }

            if ($autoRepairPossible === false) {
                $io->hr();
                $io->out('Containers tree has operphand children.');
                $io->out('An automatically recovery is not possible due to the containers are used by other elements.');
                $io->out('Please contact commercial support or join our community Discord https://discord.gg/G8KhxKuQ9G');
                $io->hr();
            } else {
                $io->hr();
                $io->success('Automatically repair is possible!');
                $io->out('Run "oitc container_recover --recover" to remove orphaned containers.');
                $io->out('A backup of your database will be created BEFORE any modifications are done.');
                $io->hr();
            }

            // Exit command with bad exit code due to errors
            return 1;
        }

        // Run --recover option
        if ($args->getOption('recover') === true) {
            // Create backup first
            $io->info('Start backup of MySQL database...');
            Configure::load('nagios');
            $MysqlBackup = new Backup();
            $filename = Configure::read('nagios.export.backupTarget') . '/' . 'container_recover_' . date('d.m.y_H_i_s') . '.sql';
            $return = $MysqlBackup->createMysqlDump($filename);
            if ($return['returncode'] === 0) {
                $io->success('MySQL Backup created at: ' . $filename);

                $autoRepairPossible = true;
                $orphanedContainers = $ContainersTable->getOrphanedContainers();

                if(empty($orphanedContainers)){
                    $io->success('No orphaned containers found.');
                    $io->success('Nothing to do.');
                    return 0;
                }

                foreach ($orphanedContainers as $orphanedContainer)
                    if ($ContainersTable->isOrphanedContainerInUse($orphanedContainer['id'])) {
                        $autoRepairPossible = false;
                    }

                if ($autoRepairPossible === false) {
                    $io->error('Container is in use! Run the command "oitc container_recover --check"');
                    $io->error('ABORTING - No data has ben changed.');
                    return 1;
                }

                // We have orphaned containers - by all are safe to delete
                $io->info(sprintf('Found %s orphaned containers, but all are safe to delete', sizeof($orphanedContainers)));
                $ContainersTable->removeBehavior('Tree');

                $noErrors = true;
                foreach ($orphanedContainers as $orphanedContainer) {
                    $entity = $ContainersTable->get($orphanedContainer['id']);
                    if ($ContainersTable->delete($entity)) {
                        $io->success(sprintf(
                            'Orphaned Container "%s" (ID: %s, Type: %s) deleted successfully',
                            $orphanedContainer['name'],
                            $orphanedContainer['id'],
                            $orphanedContainer['containertype_id']
                        ));
                    } else {
                        $noErrors = false;
                        $io->error(sprintf('Could not delete orphaned container "%s" (ID: %s, Type: %s)!!',
                            $orphanedContainer['name'],
                            $orphanedContainer['id'],
                            $orphanedContainer['containertype_id']
                        ));
                    }
                }

                if ($noErrors === true) {
                    $ContainersTable->addBehavior('Tree');
                    $ContainersTable->recover();
                    $io->hr();
                    $io->success('Recovery successful');
                    return 0;
                }

                $io->hr();
                $io->error('Errors occurred - tree not recovered!');
                return 1;
            }

            $io->error('Error while creating MySQL dump. EXIT NOW!');
            return 1;
        }

        // Print help if no option is passed
        // Valid options are --check or --recover
        $this->displayHelp($this->getOptionParser(), $args, $io);
        return 0;
    }
}
