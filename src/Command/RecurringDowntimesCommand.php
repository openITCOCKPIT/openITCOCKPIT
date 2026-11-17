<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

use App\itnovum\openITCOCKPIT\Monitoring\Naemon\ExternalCommands;
use App\Model\Entity\Systemdowntime;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemdowntimesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DistributeModule\Model\Table\SatellitesTable;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;

/**
 * RecurringDowntimes command.
 */
class RecurringDowntimesCommand extends Command implements CronjobInterface {
    private SatellitesTable $satellitesTable;
    private ContainersTable $containersTable;
    private ServicesTable $servicesTable;
    private HostgroupsTable $hostgroupsTable;
    private HostsTable $hostsTable;
    private SystemdowntimesTable $systemdowntimesTable;
    private ExternalCommands $externalCommands;

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

        try {
            $this->systemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
            $this->containersTable = TableRegistry::getTableLocator()->get('Containers');
            $this->hostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $this->servicesTable = TableRegistry::getTableLocator()->get('Services');
            $this->hostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

            $this->externalCommands = new ExternalCommands();

        } catch (\RuntimeException $e) {
            $io->out('<red>' . $e->getMessage() . '</red>');
            $io->hr();

            return 1;
        }

        $io->out('Create recurring downtimes...', 0);

        $this->createMissingRecurringDowntimes();
        $io->success('   Ok');
        $io->hr();
    }

    public function createMissingRecurringDowntimes() {
        $systemdowntimes = $this->systemdowntimesTable->getRecurringDowntimesForCronjob();

        foreach ($systemdowntimes as $systemdowntime) {
            /** @var \App\Model\Entity\Systemdowntime $systemdowntime */
            $currentWeekday = date('N');
            $currentdayOfMonth = date('j');

            if ($systemdowntime->hasWeekdays() && $systemdowntime->hasDayOfMonth()) {
                if (in_array($currentWeekday, $systemdowntime->getWeekdays(), true) && in_array($currentdayOfMonth, $systemdowntime->getDayOfMonth(), true)) {
                    //Example: Today is the 5 day of month and this is a monday
                    if ($systemdowntime->isTimestampInThePast($systemdowntime->getScheduledEndTime()) === true) {
                        //Skip downtime if scheduled end time is in the past
                        continue;
                    }
                    //Checking if the downtime is already set in Nagios/Naemon
                    if (1 === 2) {
                        $this->delegateDowntime($systemdowntime);
                    }
                }
            }

            if ($systemdowntime->hasWeekdays() && !$systemdowntime->hasDayOfMonth()) {
                if (in_array($currentWeekday, $systemdowntime->getWeekdays(), true)) {
                    //Example: today is monday
                    //Checking if the downtime is already set in Nagios/Naemon and make sure scheduled end time is not in the past
                    if ($systemdowntime->isTimestampInThePast($systemdowntime->getScheduledEndTime())) {
                        //Skip downtime if scheduled end time is in the past
                        continue;
                    }

                    if (1 === 2) {
                        $this->delegateDowntime($systemdowntime);
                    }
                }
            }

            if ($systemdowntime->hasDayOfMonth() && !$systemdowntime->hasWeekdays()) {
                if (in_array($currentdayOfMonth, $systemdowntime->getDayOfMonth(), true)) {
                    //Example: today the 6 or 10 or 30 day of the current month

                    //Checking if the downtime is already set in Nagios/Naemon and make sure scheduled end time is not in the past
                    if ($systemdowntime->isTimestampInThePast($systemdowntime->getScheduledEndTime())) {
                        //Skip downtime if scheduled end time is in the past
                        continue;
                    }

                    //Checking if the downtime is allready set in nagios
                    if (1 === 2) {
                        $this->delegateDowntime($systemdowntime);
                    }
                }
            }
        }
    }

    /**
     * @param Systemdowntime $systemdowntime
     * @return void
     */
    private function delegateDowntime(Systemdowntime $systemdowntime) {
        switch ((int)$systemdowntime->get('objecttype_id')) {
            case OBJECT_HOST:
                $this->setHostDowntime($systemdowntime);
                break;

            case OBJECT_HOSTGROUP:
                $this->setHostgroupDowntime($systemdowntime);
                break;

            case OBJECT_SERVICE:
                $this->setServiceDowntime($systemdowntime);
                break;

            case OBJECT_NODE: //Node / Container downtimes
                $this->setNodeDowntime($systemdowntime);
                break;

            case OBJECT_SATELLITE: //Satellite downtimes
                $this->setSatelliteDowntime($systemdowntime);
                break;
        }
    }

    /**
     * @param Systemdowntime $systemdowntime
     * @return void
     */
    private function setHostDowntime(Systemdowntime $systemdowntime): void {
        try {
            $host = $this->hostsTable->getHostByIdOrFail($systemdowntime->get('object_id'));
            $this->externalCommands->setHostDowntime([
                'hostUuid'     => $host->get('uuid'),
                'start'        => $systemdowntime->getScheduledStartTime(),
                'end'          => $systemdowntime->getScheduledEndTime(),
                'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                'author'       => $systemdowntime->get('author'),
                'downtimetype' => $systemdowntime->getDowntimetypeId(),
                'satellite_id' => $host->get('satellite_id')
            ]);
        } catch (RecordNotFoundException $e) {
            // The object for recurring downtime was deleted, so we delete the downtime
            $this->systemdowntimesTable->delete($systemdowntime);
        }
    }

    /**
     * @param Systemdowntime $systemdowntime
     * @return void
     */
    private function setHostgroupDowntime(Systemdowntime $systemdowntime): void {
        try {
            $hostgroupUuid = $this->hostgroupsTable->getHostgroupUuidById($systemdowntime->get('object_id'));
            $this->externalCommands->setHostgroupDowntime([
                'hostgroupUuid' => $hostgroupUuid,
                'start'         => $systemdowntime->getScheduledStartTime(),
                'end'           => $systemdowntime->getScheduledEndTime(),
                'comment'       => $systemdowntime->getRecurringDowntimeComment(),
                'author'        => $systemdowntime->get('author'),
                'downtimetype'  => $systemdowntime->getDowntimetypeId(),
            ]);
        } catch (RecordNotFoundException $e) {
            // The object for recurring downtime was deleted, so we delete the downtime
            $this->systemdowntimesTable->delete($systemdowntime);
        }
    }

    /**
     * @param Systemdowntime $systemdowntime
     * @return void
     */
    private function setServiceDowntime(Systemdowntime $systemdowntime): void {
        try {
            $service = $this->servicesTable->getServiceByIdForExternalCommand($systemdowntime->get('object_id'));
            $this->externalCommands->setServiceDowntime([
                'hostUuid'     => $service->get('host')->get('uuid'),
                'serviceUuid'  => $service->get('uuid'),
                'start'        => $systemdowntime->getScheduledStartTime(),
                'end'          => $systemdowntime->getScheduledEndTime(),
                'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                'author'       => $systemdowntime->get('author'),
                'downtimetype' => $systemdowntime->getDowntimetypeId(),
                'satellite_id' => $service->get('host')->get('satellite_id')
            ]);
        } catch (RecordNotFoundException $e) {
            // The object for recurring downtime was deleted, so we delete the downtime
            $this->systemdowntimesTable->delete($systemdowntime);
        }
    }

    /**
     * @param Systemdowntime $systemdowntime
     * @return void
     */
    private function setNodeDowntime(Systemdowntime $systemdowntime): void {
        if (!$this->containersTable->existsById($systemdowntime->get('object_id'))) {
            $this->systemdowntimesTable->delete($systemdowntime);
            return;
        }

        $childrenContainers = [
            $systemdowntime->get('object_id')
        ];
        if ($systemdowntime->get('is_recursive') == 1) {
            //Recursive container lookup is enabled
            //Lookup all child containers the user has write permissions to to select the hosts and create
            //the downtimes

            if ($systemdowntime->get('object_id') == ROOT_CONTAINER) {
                $childrenContainers = $this->containersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
            } else {
                $childrenContainers = $this->containersTable->resolveChildrenOfContainerIds($systemdowntime->get('object_id'));
                $childrenContainers = $this->containersTable->removeRootContainer($childrenContainers);
            }
        }


        $hosts = $this->hostsTable->getHostsByContainerId($childrenContainers, 'list', 'uuid');
        $hostUuids = array_keys($hosts);

        $this->externalCommands->setContainerDowntime([
            'hostUuids'    => $hostUuids,
            'start'        => $systemdowntime->getScheduledStartTime(),
            'end'          => $systemdowntime->getScheduledEndTime(),
            'comment'      => $systemdowntime->getRecurringDowntimeComment(),
            'author'       => $systemdowntime->get('author'),
            'downtimetype' => $systemdowntime->getDowntimetypeId()
        ]);
    }

    /**
     * @param Systemdowntime $systemdowntime
     * @return void
     */
    private function setSatelliteDowntime(Systemdowntime $systemdowntime): void {
        if (!Plugin::isLoaded('DistributeModule')) {
            return;
        }
        if (!$this->satellitesTable->existsById($systemdowntime->get('object_id'))) {
            $this->systemdowntimesTable->delete($systemdowntime);
            return;
        }
        try {
            $hosts = $this->hostsTable->getHostsBySatelliteId($systemdowntime->get('object_id'));
            $hostUuids = Hash::extract($hosts, '{n}[disabled=0].uuid');
            //satellite has same options as container downtimes

            $this->externalCommands->setContainerDowntime([
                'hostUuids'    => $hostUuids,
                'start'        => $systemdowntime->getScheduledStartTime(),
                'end'          => $systemdowntime->getScheduledEndTime(),
                'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                'author'       => $systemdowntime->get('author'),
                'downtimetype' => $systemdowntime->getDowntimetypeId()
            ]);
        } catch (RecordNotFoundException $e) {
            // The object for recurring downtime was deleted, so we delete the downtime
            $this->systemdowntimesTable->delete($systemdowntime);
        }
    }
}
