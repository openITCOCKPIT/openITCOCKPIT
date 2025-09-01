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

use App\itnovum\openITCOCKPIT\Monitoring\Naemon\ExternalCommands;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemdowntimesTable;
use App\Model\Table\SystemsettingsTable;
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
use itnovum\openITCOCKPIT\Core\MonitoringEngine\StatusDat;

/**
 * RecurringDowntimes command.
 */
class RecurringDowntimesCommand extends Command implements CronjobInterface {

    /**
     * @var StatusDat
     */
    private $StatusDat;

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

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $record = $SystemsettingsTable->getSystemsettingByKey('MONITORING.STATUS_DAT');
        $statusdatPath = $record->get('value');

        try {
            $this->StatusDat = new StatusDat($statusdatPath);
            $this->StatusDat->parseDowntimes();
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
        /** @var SystemdowntimesTable $SystemdowntimesTable */
        $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $systemdowntimes = $SystemdowntimesTable->getRecurringDowntimesForCronjob();
        $ExternalCommands = new ExternalCommands();

        foreach ($systemdowntimes as $systemdowntime) {
            /** @var \App\Model\Entity\Systemdowntime $systemdowntime */
            $currentWeekday = date('N');
            $currentdayOfMonth = date('j');

            if ($systemdowntime->hasWeekdays() === true && $systemdowntime->hasDayOfMonth() === true) {
                if (in_array($currentWeekday, $systemdowntime->getWeekdays(), true) && in_array($currentdayOfMonth, $systemdowntime->getDayOfMonth(), true)) {
                    //Example: Today is the 5 day of month and this is a monday
                    if ($systemdowntime->isTimestampInThePast($systemdowntime->getScheduledEndTime()) === true) {
                        //Skip downtime if scheduled end time is in the past
                        continue;
                    }
                    //Checking if the downtime is already set in Nagios/Naemon
                    if ($this->StatusDat->checkIfRecurringDowntimeWasScheduled($systemdowntime->get('id'), $systemdowntime->get('comment')) === false) {
                        switch ((int)$systemdowntime->get('objecttype_id')) {
                            case OBJECT_HOST:
                                try {
                                    $host = $HostsTable->getHostByIdOrFail($systemdowntime->get('object_id'));
                                    $ExternalCommands->setHostDowntime([
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
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_HOSTGROUP:
                                try {
                                    $hostgroupUuid = $HostgroupsTable->getHostgroupUuidById($systemdowntime->get('object_id'));
                                    $ExternalCommands->setHostgroupDowntime([
                                        'hostgroupUuid' => $hostgroupUuid,
                                        'start'         => $systemdowntime->getScheduledStartTime(),
                                        'end'           => $systemdowntime->getScheduledEndTime(),
                                        'comment'       => $systemdowntime->getRecurringDowntimeComment(),
                                        'author'        => $systemdowntime->get('author'),
                                        'downtimetype'  => $systemdowntime->getDowntimetypeId(),
                                    ]);
                                } catch (RecordNotFoundException $e) {
                                    // The object for recurring downtime was deleted, so we delete the downtime
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }

                                break;

                            case OBJECT_SERVICE:
                                try {
                                    $service = $ServicesTable->getServiceByIdForExternalCommand($systemdowntime->get('object_id'));
                                    $ExternalCommands->setServiceDowntime([
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
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_NODE: //Node / Container downtimes
                                if (!$ContainersTable->existsById($systemdowntime->get('object_id'))) {
                                    $SystemdowntimesTable->delete($systemdowntime);
                                    break;
                                }

                                $childrenContainers = [
                                    $systemdowntime->get('object_id')
                                ];
                                if ($systemdowntime->get('is_recursive') == 1) {
                                    //Recursive container lookup is enabled
                                    //Lookup all child containers the user has write permissions to to select the hosts and create
                                    //the downtimes

                                    if ($systemdowntime->get('object_id') == ROOT_CONTAINER) {
                                        $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
                                    } else {
                                        $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds($systemdowntime->get('object_id'));
                                        $childrenContainers = $ContainersTable->removeRootContainer($childrenContainers);
                                    }
                                }


                                $hosts = $HostsTable->getHostsByContainerId($childrenContainers, 'list', 'uuid');
                                $hostUuids = array_keys($hosts);

                                $ExternalCommands->setContainerDowntime([
                                    'hostUuids'    => $hostUuids,
                                    'start'        => $systemdowntime->getScheduledStartTime(),
                                    'end'          => $systemdowntime->getScheduledEndTime(),
                                    'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                                    'author'       => $systemdowntime->get('author'),
                                    'downtimetype' => $systemdowntime->getDowntimetypeId()
                                ]);
                                break;

                            case OBJECT_SATELLITE: //Satellite downtimes
                                if (Plugin::isLoaded('DistributeModule')) {
                                    /** @var SatellitesTable $SatellitesTable */
                                    $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
                                    if (!$SatellitesTable->existsById($systemdowntime->get('object_id'))) {
                                        $SystemdowntimesTable->delete($systemdowntime);
                                        break;
                                    }
                                    try {
                                        $hosts = $HostsTable->getHostsBySatelliteId($systemdowntime->get('object_id'));
                                        $hostUuids = Hash::extract($hosts, '{n}[disabled=0].uuid');
                                        //satellite has same options as container downtimes
                                        $ExternalCommands->setContainerDowntime([
                                            'hostUuids'    => $hostUuids,
                                            'start'        => $systemdowntime->getScheduledStartTime(),
                                            'end'          => $systemdowntime->getScheduledEndTime(),
                                            'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                                            'author'       => $systemdowntime->get('author'),
                                            'downtimetype' => $systemdowntime->getDowntimetypeId()
                                        ]);
                                        break;

                                    } catch (RecordNotFoundException $e) {
                                        // The object for recurring downtime was deleted, so we delete the downtime
                                        $SystemdowntimesTable->delete($systemdowntime);
                                    }
                                }
                                break;

                        }
                    }
                }
            }

            if ($systemdowntime->hasWeekdays() === true && $systemdowntime->hasDayOfMonth() === false) {
                if (in_array($currentWeekday, $systemdowntime->getWeekdays(), true)) {
                    //Example: today is monday
                    //Checking if the downtime is already set in Nagios/Naemon and make sure scheduled end time is not in the past
                    if ($systemdowntime->isTimestampInThePast($systemdowntime->getScheduledEndTime()) === true) {
                        //Skip downtime if scheduled end time is in the past
                        continue;
                    }

                    if ($this->StatusDat->checkIfRecurringDowntimeWasScheduled($systemdowntime->get('id'), $systemdowntime->get('comment')) === false) {
                        switch ((int)$systemdowntime->get('objecttype_id')) {
                            case OBJECT_HOST:
                                try {
                                    $host = $HostsTable->getHostByIdOrFail($systemdowntime->get('object_id'));
                                    $ExternalCommands->setHostDowntime([
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
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_HOSTGROUP:
                                try {
                                    $hostgroupUuid = $HostgroupsTable->getHostgroupUuidById($systemdowntime->get('object_id'));
                                    $ExternalCommands->setHostgroupDowntime([
                                        'hostgroupUuid' => $hostgroupUuid,
                                        'start'         => $systemdowntime->getScheduledStartTime(),
                                        'end'           => $systemdowntime->getScheduledEndTime(),
                                        'comment'       => $systemdowntime->getRecurringDowntimeComment(),
                                        'author'        => $systemdowntime->get('author'),
                                        'downtimetype'  => $systemdowntime->getDowntimetypeId(),
                                    ]);
                                } catch (RecordNotFoundException $e) {
                                    // The object for recurring downtime was deleted, so we delete the downtime
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }

                                break;

                            case OBJECT_SERVICE:
                                try {
                                    $service = $ServicesTable->getServiceByIdForExternalCommand($systemdowntime->get('object_id'));
                                    $ExternalCommands->setServiceDowntime([
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
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_NODE: //Node / Container downtimes
                                if (!$ContainersTable->existsById($systemdowntime->get('object_id'))) {
                                    $SystemdowntimesTable->delete($systemdowntime);
                                    break;
                                }

                                $childrenContainers = [
                                    $systemdowntime->get('object_id')
                                ];
                                if ($systemdowntime->get('is_recursive') == 1) {
                                    //Recursive container lookup is enabled
                                    //Lookup all child containers the user has write permissions to to select the hosts and create
                                    //the downtimes

                                    if ($systemdowntime->get('object_id') == ROOT_CONTAINER) {
                                        $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
                                    } else {
                                        $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds($systemdowntime->get('object_id'));
                                        $childrenContainers = $ContainersTable->removeRootContainer($childrenContainers);
                                    }
                                }


                                $hosts = $HostsTable->getHostsByContainerId($childrenContainers, 'list', 'uuid');
                                $hostUuids = array_keys($hosts);

                                $ExternalCommands->setContainerDowntime([
                                    'hostUuids'    => $hostUuids,
                                    'start'        => $systemdowntime->getScheduledStartTime(),
                                    'end'          => $systemdowntime->getScheduledEndTime(),
                                    'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                                    'author'       => $systemdowntime->get('author'),
                                    'downtimetype' => $systemdowntime->getDowntimetypeId()
                                ]);
                                break;

                            case OBJECT_SATELLITE: //Satellite downtimes
                                if (Plugin::isLoaded('DistributeModule')) {
                                    /** @var SatellitesTable $SatellitesTable */
                                    $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
                                    if (!$SatellitesTable->existsById($systemdowntime->get('object_id'))) {
                                        $SystemdowntimesTable->delete($systemdowntime);
                                        break;
                                    }
                                    try {
                                        $hosts = $HostsTable->getHostsBySatelliteId($systemdowntime->get('object_id'));
                                        $hostUuids = Hash::extract($hosts, '{n}[disabled=0].uuid');
                                        //satellite has same options as container downtimes
                                        $ExternalCommands->setContainerDowntime([
                                            'hostUuids'    => $hostUuids,
                                            'start'        => $systemdowntime->getScheduledStartTime(),
                                            'end'          => $systemdowntime->getScheduledEndTime(),
                                            'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                                            'author'       => $systemdowntime->get('author'),
                                            'downtimetype' => $systemdowntime->getDowntimetypeId()
                                        ]);
                                        break;
                                    } catch (RecordNotFoundException $e) {
                                        // The object for recurring downtime was deleted, so we delete the downtime
                                        $SystemdowntimesTable->delete($systemdowntime);
                                    }
                                }
                                break;
                        }
                    }
                }
            }

            if ($systemdowntime->hasDayOfMonth() === true && $systemdowntime->hasWeekdays() === false) {
                if (in_array($currentdayOfMonth, $systemdowntime->getDayOfMonth(), true)) {
                    //Example: today the 6 or 10 or 30 day of the current month

                    //Checking if the downtime is already set in Nagios/Naemon and make sure scheduled end time is not in the past
                    if ($systemdowntime->isTimestampInThePast($systemdowntime->getScheduledEndTime()) === true) {
                        //Skip downtime if scheduled end time is in the past
                        continue;
                    }

                    //Checking if the downtime is allready set in nagios
                    if ($this->StatusDat->checkIfRecurringDowntimeWasScheduled($systemdowntime->get('id'), $systemdowntime->get('comment')) === false) {
                        switch ((int)$systemdowntime->get('objecttype_id')) {
                            case OBJECT_HOST:
                                try {
                                    //$hostUuid = $HostsTable->getHostUuidById($systemdowntime->get('object_id'));
                                    $host = $HostsTable->getHostByIdOrFail($systemdowntime->get('object_id'));
                                    $ExternalCommands->setHostDowntime([
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
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_HOSTGROUP:
                                try {
                                    $hostgroupUuid = $HostgroupsTable->getHostgroupUuidById($systemdowntime->get('object_id'));
                                    $ExternalCommands->setHostgroupDowntime([
                                        'hostgroupUuid' => $hostgroupUuid,
                                        'start'         => $systemdowntime->getScheduledStartTime(),
                                        'end'           => $systemdowntime->getScheduledEndTime(),
                                        'comment'       => $systemdowntime->getRecurringDowntimeComment(),
                                        'author'        => $systemdowntime->get('author'),
                                        'downtimetype'  => $systemdowntime->getDowntimetypeId(),
                                    ]);
                                } catch (RecordNotFoundException $e) {
                                    // The object for recurring downtime was deleted, so we delete the downtime
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_SERVICE:
                                try {
                                    $service = $ServicesTable->getServiceByIdForExternalCommand($systemdowntime->get('object_id'));
                                    $ExternalCommands->setServiceDowntime([
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
                                    $SystemdowntimesTable->delete($systemdowntime);
                                }
                                break;

                            case OBJECT_NODE: //Node / Container downtimes
                                if (!$ContainersTable->existsById($systemdowntime->get('object_id'))) {
                                    $SystemdowntimesTable->delete($systemdowntime);
                                    break;
                                }

                                $childrenContainerId = [
                                    $systemdowntime->get('object_id')
                                ];
                                if ($systemdowntime->get('is_recursive') == 1) {
                                    //Recursive container lookup is enabled
                                    //Lookup all child containers the user has write permissions to to select the hosts and create
                                    //the downtimes

                                    if ($systemdowntime->get('object_id') == ROOT_CONTAINER) {
                                        $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
                                    } else {
                                        $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds($systemdowntime->get('object_id'));
                                        $childrenContainers = $ContainersTable->removeRootContainer($childrenContainers);
                                    }
                                }


                                $hosts = $HostsTable->getHostsByContainerId($childrenContainers, 'list', 'uuid');
                                $hostUuids = array_keys($hosts);

                                $ExternalCommands->setContainerDowntime([
                                    'hostUuids'    => $hostUuids,
                                    'start'        => $systemdowntime->getScheduledStartTime(),
                                    'end'          => $systemdowntime->getScheduledEndTime(),
                                    'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                                    'author'       => $systemdowntime->get('author'),
                                    'downtimetype' => $systemdowntime->getDowntimetypeId()
                                ]);
                                break;

                            case OBJECT_SATELLITE: //Satellite downtimes
                                if (Plugin::isLoaded('DistributeModule')) {
                                    /** @var SatellitesTable $SatellitesTable */
                                    $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
                                    if (!$SatellitesTable->existsById($systemdowntime->get('object_id'))) {
                                        $SystemdowntimesTable->delete($systemdowntime);
                                        break;
                                    }
                                    try {
                                        $hosts = $HostsTable->getHostsBySatelliteId($systemdowntime->get('object_id'));
                                        $hostUuids = Hash::extract($hosts, '{n}[disabled=0].uuid');
                                        //satellite has same options as container downtimes

                                        $ExternalCommands->setContainerDowntime([
                                            'hostUuids'    => $hostUuids,
                                            'start'        => $systemdowntime->getScheduledStartTime(),
                                            'end'          => $systemdowntime->getScheduledEndTime(),
                                            'comment'      => $systemdowntime->getRecurringDowntimeComment(),
                                            'author'       => $systemdowntime->get('author'),
                                            'downtimetype' => $systemdowntime->getDowntimetypeId()
                                        ]);
                                        break;

                                    } catch (RecordNotFoundException $e) {
                                        // The object for recurring downtime was deleted, so we delete the downtime
                                        $SystemdowntimesTable->delete($systemdowntime);
                                    }
                                }
                                break;
                        }
                    }
                }
            }
        }
    }
}
