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

use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Traits\DatabasePartitionsTrait;
use App\Model\Table\SystemsettingsTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;

/**
 * DatabaseCleanup command.
 */
class DatabaseCleanupCommand extends Command implements CronjobInterface {

    use DatabasePartitionsTrait;

    /**
     * @var array
     */
    private $_systemsettings;

    /**
     * @var DbBackend
     */
    private $DbBackend;

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
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $this->_systemsettings = $SystemsettingsTable->findAsArray();

        $this->DbBackend = new DbBackend();

        if ($this->DbBackend->isNdoUtils()) {
            // NdoUtils or Statusengine 2
            // NDOUtils and Statusengine 2 is LEGACY and not used anymore. Do not update or change any NDO related code
            $tables = [
                $this->DbBackend->getServicechecksTable(),
                $this->DbBackend->getHostchecksTable(),
                $this->DbBackend->getStatehistoryHostsTable(),
                //$this->DbBackend->getStatehistoryServicesTable(), //There is only one table in NDO schema
                $this->DbBackend->getLogentriesTable(),
                $this->DbBackend->getNotificationHostsTable(),
                //$this->DbBackend->getNotificationServicesTable(), //There is only one table in NDO schema
                TableRegistry::getTableLocator()->get('Statusengine2Module.Contactnotifications'), //NDO only tables
                TableRegistry::getTableLocator()->get('Statusengine2Module.Contactnotificationmethods') //NDO only tables
            ];

            $this->checkAndCreatePartitionsMySQL($tables, $io);
        }

        if ($this->DbBackend->isStatusengine3()) {
            $tables = [
                $this->DbBackend->getServicechecksTable(),
                $this->DbBackend->getHostchecksTable(),
                $this->DbBackend->getStatehistoryHostsTable(),
                $this->DbBackend->getStatehistoryServicesTable(),
                $this->DbBackend->getLogentriesTable(),
                $this->DbBackend->getNotificationHostsTable(),
                $this->DbBackend->getNotificationHostsLogTable(),
                $this->DbBackend->getNotificationServicesTable(),
                $this->DbBackend->getNotificationServicesLogTable()
            ];

            // Check for p_max partition in new tables
            $this->checkAndCreateInitialPartition($this->DbBackend->getNotificationHostsLogTable(), 'start_time', $io);
            $this->checkAndCreateInitialPartition($this->DbBackend->getNotificationServicesLogTable(), 'start_time', $io);

            $this->checkAndCreatePartitionsMySQLStatusengine3($tables, $io);
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('CrateDB is not implemented yet!');

            $tables = [
                $this->DbBackend->getServicechecksTable(),
                $this->DbBackend->getHostchecksTable(),
                $this->DbBackend->getStatehistoryHostsTable(),
                $this->DbBackend->getStatehistoryServicesTable(),
                $this->DbBackend->getLogentriesTable(),
                $this->DbBackend->getNotificationHostsTable(),
                $this->DbBackend->getNotificationServicesTable(),
            ];

            $this->cleanupCrateDb($tables);
        }

    }

    /**
     * @param Table $Table
     * @param string $columnName Value of PARTITION BY column
     * @param ConsoleIo $io
     * @return void
     */
    public function checkAndCreateInitialPartition(Table $Table, string $columnName, ConsoleIo $io) {
        /***********************************************/
        /************ UNIX TIMESTAMP TABLES ************/
        /***********************************************/

        // Make sure that the initial partition exists
        // This is for tables using a unix timestamp as partition by value
        $partitions = $this->getPartitionsByTable($Table);
        if (empty($partitions)) {
            // This table has no partitions yet (fresh installation of the module)
            // Execute "ALTER TABLE" to create the initial partition "p_max"
            // This way we can still use cakephp/migrations to manage the schema
            $this->alterTableAndCreateFirstPartitionByUnixtimestampUnsafe($Table, $columnName);
        }
    }

    public function checkAndCreatePartitionsMySQL(array $tables, ConsoleIo $io) {
        foreach ($tables as $Table) {
            /** @var Table $Table */
            $Connection = $Table->getConnection();
            $partitions = $this->getPartitionsByTable($Table);

            //Check if partition for current week exists
            $currentMysqlPartitionStartDate = date('Y-m-d H:i:s', strtotime('00:00:00 next monday'));
            $currentPartitionName = 'p_' . date('o_W');

            $io->out('Checking for partition ' . $currentPartitionName . ' in Table "' . $Table->getTable() . '"...', 0);
            if (!in_array($currentPartitionName, $partitions, true)) {
                try {
                    $Connection->execute("ALTER TABLE " . $Connection->config()['database'] . "." . $Table->getTable() . " REORGANIZE PARTITION p_max INTO (PARTITION " . $currentPartitionName . " VALUES LESS THAN (TO_DAYS('" . $currentMysqlPartitionStartDate . "')), PARTITION p_max values LESS THAN (MAXVALUE));");
                    $io->success('   Ok');
                } catch (\Exception $e) {
                    Log::error('DatabaseCleanupCommand: MySQL Error: ' . $e->getMessage());
                }
            } else {
                $io->success('   Ok');
            }


            // Attention: look close before you change the date parameters!!!
            // Think that there's coming a new year on 31.12 !!!
            // TO_DAYS('2014-12-29 00:00:00')
            $mysqlPartitionStartDate = date('Y-m-d H:i:s', strtotime('00:00:00 next monday +1 week'));
            //p_2015_01
            $newPartitionName = 'p_' . date('o_W', strtotime('next monday'));

            $io->out('Checking for partition ' . $newPartitionName . ' in Table "' . $Table->getTable() . '"...', 0);

            //Checking if this partition already exists
            if (!in_array($newPartitionName, $partitions, true)) {
                //This partition does not exist and we need to create it
                $io->out('');
                $io->info('Partition does not exists, will create it....', 0);

                $Connection->execute("ALTER TABLE " . $Connection->config()['database'] . "." . $Table->getTable() . " REORGANIZE PARTITION p_max INTO (PARTITION " . $newPartitionName . " VALUES LESS THAN (TO_DAYS('" . $mysqlPartitionStartDate . "')), PARTITION p_max values LESS THAN (MAXVALUE));");
                $io->success('   Ok');
                //ALTER TABLE ptn_test DROP PARTITION p_2015_01
            } else {
                $io->success('   Ok');
            }
            $this->cleanupPartition($Table, $partitions, $io);
            $io->hr();
        }
    }

    public function checkAndCreatePartitionsMySQLStatusengine3(array $tables, ConsoleIo $io) {
        foreach ($tables as $Table) {
            /** @var Table $Table */
            $Connection = $Table->getConnection();
            $partitions = $this->getPartitionsByTable($Table);

            //Check if partition for current week exists
            $currentMysqlPartitionStartDate = strtotime('00:00:00 next monday');
            $currentMysqlPartitionStartDate = intdiv($currentMysqlPartitionStartDate, 86400);

            $currentPartitionName = 'p_' . date('o_W');

            $io->out('Checking for partition ' . $currentPartitionName . ' in Table "' . $Table->getTable() . '"...', 0);
            if (!in_array($currentPartitionName, $partitions, true)) {
                try {                                                                                                                                                                                                               // < next monday 00:00
                    $Connection->execute("ALTER TABLE " . $Connection->config()['database'] . "." . $Table->getTable() . " REORGANIZE PARTITION p_max INTO (PARTITION " . $currentPartitionName . " VALUES LESS THAN (" . $currentMysqlPartitionStartDate . "), PARTITION p_max values LESS THAN (MAXVALUE));");
                    $io->success('   Ok');
                } catch (\Exception $e) {
                    Log::error('DatabaseCleanupCommand: MySQL Error: ' . $e->getMessage());
                }
            } else {
                $io->success('   Ok');
            }


            // Attention: look close before you change the date parameters!!!
            // Think that there's coming a new year on 31.12 !!!
            // TO_DAYS('2014-12-29 00:00:00')
            $mysqlPartitionStartDate = strtotime('00:00:00 next monday +1 week');
            $mysqlPartitionStartDate = intdiv($mysqlPartitionStartDate, 86400);
            //p_2015_01
            $newPartitionName = 'p_' . date('o_W', strtotime('next monday'));

            $io->out('Checking for partition ' . $newPartitionName . ' in Table "' . $Table->getTable() . '"...', 0);

            //Checking if this partition already exists
            if (!in_array($newPartitionName, $partitions, true)) {
                //This partition does not exist and we need to create it
                $io->out('');
                $io->info('Partition does not exists, will create it....', 0);

                $Connection->execute("ALTER TABLE " . $Connection->config()['database'] . "." . $Table->getTable() . " REORGANIZE PARTITION p_max INTO (PARTITION " . $newPartitionName . " VALUES LESS THAN (" . $mysqlPartitionStartDate . "), PARTITION p_max values LESS THAN (MAXVALUE));");
                $io->success('   Ok');
                //ALTER TABLE ptn_test DROP PARTITION p_2015_01
            } else {
                $io->success('   Ok');
            }
            $this->cleanupPartition($Table, $partitions, $io);
            $io->hr();
        }
    }

    /**
     * @param Table $Table
     * @param array $partitions
     * @param ConsoleIo $io
     */
    public function cleanupPartition(Table $Table, $partitions, ConsoleIo $io) {
        $io->out('Checking age of partitions...');

        $systemsettingsKey = '';
        switch ($Table->getAlias()) {
            case 'StatehistoryHosts':
            case 'StatehistoryServices':
                $systemsettingsKey = 'STATEHISTORIES';
                break;

            case 'Hostchecks':
                $systemsettingsKey = 'HOSTCHECKS';
                break;
            case 'Servicechecks':
                $systemsettingsKey = 'SERVICECHECKS';
                break;
            case 'Logentries':
                $systemsettingsKey = 'LOGENTRIES';
                break;
            case 'NotificationHosts':
            case 'NotificationHostsLog': // SE3 only
            case 'NotificationServices':
            case 'NotificationServicesLog': // SE3 only
            case 'Contactnotifications': // NDO only
            case 'Contactnotificationmethods': // NDO only
                $systemsettingsKey = 'NOTIFICATIONS';
                break;
        }

        if (!isset($this->_systemsettings['ARCHIVE']['ARCHIVE.AGE.' . $systemsettingsKey])) {
            throw new \RuntimeException('ARCHIVE.AGE.' . $systemsettingsKey . ' key not found in Systemsettings for Table "' . $Table->getAlias() . '"!');
        }

        $maxAgeInWeeks = $this->_systemsettings['ARCHIVE']['ARCHIVE.AGE.' . $systemsettingsKey];

        $maxAgeTimestamp = strtotime('00:00:00 last monday -' . ($maxAgeInWeeks + 1) . ' week');

        //Valide date
        if ($maxAgeTimestamp) {
            $Connection = $Table->getConnection();

            //$partitions = ['p_2014_48', 'p_2014_49', 'p_2014_50', 'p_2014_51', 'p_2015_01', 'p_2015_02', 'p_2015_03'];
            $comparePartitionName = 'p_' . date('o_W', $maxAgeTimestamp);

            if (is_array($partitions)) {
                foreach ($partitions as $partition) {
                    //NEVER EVER DROP THIS PARTITION!!!
                    if ($partition == 'p_max') {
                        continue;
                    }
                    if (!version_compare($comparePartitionName, $partition, '<')) {
                        $io->setStyle('red', ['text' => 'red', 'blink' => false]);

                        $io->out('<red>Drop partition ' . $partition . '</red>');
                        $this->dropPartitionByNameUnsafe($Table, $partition);
                    }
                }
            }

            $io->success('Cleanup done');
        }
    }

    /**
     * @param array[Table] $tables
     * @return void
     * @deprecated CrateDB is not implemented anymore!
     */
    public function cleanupCrateDb(array $tables) {
        $this->out('Checking age of partitions...');

        $this->deletePartitionsFromCrateDb($this->StatehistoryHost, 'STATEHISTORIES');
        $this->deletePartitionsFromCrateDb($this->StatehistoryService, 'STATEHISTORIES');
        $this->deletePartitionsFromCrateDb($this->Hostcheck, 'HOSTCHECKS');
        $this->deletePartitionsFromCrateDb($this->Servicecheck, 'SERVICECHECKS');
        $this->deletePartitionsFromCrateDb($this->Logentry, 'LOGENTRIES');
        $this->deletePartitionsFromCrateDb($this->NotificationHost, 'NOTIFICATIONS');
        $this->deletePartitionsFromCrateDb($this->NotificationService, 'NOTIFICATIONS');
        //$this->deletePartitionsFromCrateDb($this->NotificationService, 'NOTIFICATIONS');

        $this->out('<green>Cleanup done</green>');
        $this->hr();
    }

    /**
     * @param Model $Model
     * @param string $systemsettingsKey
     * @deprecated CrateDB is not implemented anymore!
     */
    public function deletePartitionsFromCrateDb(Model $Model, $systemsettingsKey) {
        $maxAgeInWeeks = $this->_systemsettings['ARCHIVE']['ARCHIVE.AGE.' . $systemsettingsKey];

        $maxAgeTimestamp = strtotime('00:00:00 last monday -' . ($maxAgeInWeeks + 1) . ' week');
        $maxAgeTimestamp = $maxAgeTimestamp * 1000; //Java timestamp CrateDB


        $partitionsToDelete = [];
        foreach ($Model->getPartitions() as $record) {
            if (isset($record['values']['day']) && $record['values']['day'] < $maxAgeTimestamp) {
                $partitionsToDelete[] = $record['values']['day'];
            }
        }

        foreach ($partitionsToDelete as $partitionConditionTimestamp) {
            $this->out(sprintf(
                '<red>Drop partition %s from table %s%s</red>',
                $partitionConditionTimestamp,
                $Model->tablePrefix,
                $Model->useTable
            ));
            if (!$Model->dropPartition($partitionConditionTimestamp)) {
                $this->out('<error>Error while dropping partition</error>');
            }
        }
    }
}
