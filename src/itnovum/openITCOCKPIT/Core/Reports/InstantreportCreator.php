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


namespace App\itnovum\openITCOCKPIT\Core\Reports;


use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Model\Table\InstantreportsTable;
use App\Model\Table\SystemfailuresTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\DowntimeHostConditions;
use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Reports\DaterangesCreator;
use itnovum\openITCOCKPIT\Core\Reports\DowntimesMerger;
use itnovum\openITCOCKPIT\Core\Reports\StatehistoryConverter;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\StatehistoryHostConditions;
use itnovum\openITCOCKPIT\Core\StatehistoryServiceConditions;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryHost;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryService;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use Statusengine2Module\Model\Entity\DowntimeHost;
use Statusengine2Module\Model\Table\StatehistoryHostsTable;
use Statusengine2Module\Model\Table\StatehistoryServicesTable;

class InstantreportCreator {

    /**
     * @var UserTime
     */
    private $UserTime;

    /**
     * @var array
     */
    private $MY_RIGHTS = [];

    private $hasRootPrivileges = false;

    /**
     * @var DbBackend
     */
    private $DbBackend;

    public function __construct(UserTime $UserTime, array $MY_RIGHTS, bool $hasRootPrivileges) {
        $this->UserTime = $UserTime;
        $this->MY_RIGHTS = $MY_RIGHTS;
        $this->hasRootPrivileges = $hasRootPrivileges;
        $this->DbBackend = new DbBackend();
    }

    /**
     * @param $instantReportId
     * @param $fromDate
     * @param $toDate
     * @return array|void
     * @throws MissingDbBackendException
     */
    public function createReport($instantReportId, $fromDate, $toDate) {
        $UserTime = $this->UserTime;
        $reportData = [];
        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        $MY_RIGHTS = [];
        if (!$this->hasRootPrivileges) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        if (!$InstantreportsTable->existsById($instantReportId)) {
            throw new NotFoundException(__('Instant report not found'));
        }
        $instantReport = $InstantreportsTable->getInstantreportByIdCake4($instantReportId);
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        $timeperiod = $TimeperiodsTable->getTimeperiodWithTimerangesById($instantReport->get('timeperiod_id'));
        if (empty($timeperiod['Timeperiod']['timeperiod_timeranges'])) {
            return [
                'error' => [
                    'timeperiod_id' => [
                        'empty' => 'There are no time frames defined. Time evaluation report data is not available for the selected period.'
                    ]
                ]
            ];
        }
        $instantReportObjects = $InstantreportsTable->getHostsAndServicesByInstantreport($instantReport, $MY_RIGHTS);
        if (empty($instantReportObjects)) {
            return [
                'error' => [
                    'instantreport_objects' => [
                        'empty' => 'There are no elements for instant report available.'
                    ]
                ]
            ];
        }

        $timeSlices = Hash::insert(
            DaterangesCreator::createDateRanges(
                $fromDate,
                $toDate,
                $timeperiod['Timeperiod']['timeperiod_timeranges']
            ), '{n}.is_downtime', false
        );
        $totalTime = Hash::apply(
            array_map(function ($timeSlice) {
                return $timeSlice['end'] - $timeSlice['start'];
            }, $timeSlices),
            '{n}',
            'array_sum'
        );

        //User server timezone but with user date format
        $ServerTime = new UserTime(date_default_timezone_get(), $UserTime->getFormatString());
        $reportDetails = [
            'name'       => $instantReport->get('name'),
            'evaluation' => $instantReport->get('evaluation'),
            'type'       => $instantReport->get('type'),
            'summary'    => $instantReport->get('summary'),
            'totalTime'  => $totalTime,
            'from'       => $ServerTime->format($fromDate),
            'to'         => $ServerTime->format($toDate)
        ];

        $globalDowntimes = [];
        if ($instantReport->get('downtimes') === 1) {
            /** @var $SystemfailuresTable SystemfailuresTable */
            $SystemfailuresTable = TableRegistry::getTableLocator()->get('Systemfailures');
            $globalDowntimes = $SystemfailuresTable->getSystemfailuresForReporting(
                $fromDate,
                $toDate
            );
        }
        $evalutionTypes = [
            'only_hosts'        => 1,
            'host_and_services' => 2,
            'only_services'     => 3
        ];

        $instantReportObjects['Hosts'] = Hash::sort($instantReportObjects['Hosts'], '{n}.name', 'ASC');
        foreach ($instantReportObjects['Hosts'] as $hostId => $instantReportHostData) {
            $hostUuid = $instantReportHostData['uuid'];
            $allStatehistories = [];

            if ($reportDetails['evaluation'] !== $evalutionTypes['only_services'] ||
                ($reportDetails['evaluation'] === $evalutionTypes['only_services'] && !empty($instantReportHostData['Services']))) {
                $reportData[$hostUuid]['Host'] = [
                    'id'   => $instantReportHostData['id'],
                    'name' => $instantReportHostData['name']
                ];
            }
            if ($reportDetails['evaluation'] !== $evalutionTypes['only_services']) {
                if ($instantReport->get('downtimes') === 1) {
                    $DowntimeHostConditions = new DowntimeHostConditions();
                    $DowntimeHostConditions->setFrom($fromDate);
                    $DowntimeHostConditions->setTo($toDate);
                    $DowntimeHostConditions->setContainerIds($this->MY_RIGHTS);
                    $DowntimeHostConditions->includeCancelledDowntimes();
                    $DowntimeHostConditions->setOrder(['DowntimeHosts.scheduled_start_time' => 'ASC']);
                    $DowntimeHostConditions->setHostUuid($instantReportHostData['uuid']);
                    /** @var DowntimehistoryHostsTableInterface $DowntimehistoryHostsTable */
                    $DowntimehistoryHostsTable = $this->DbBackend->getDowntimehistoryHostsTable();
                    $instantReportObjects['Hosts'][$hostId]['downtimes'] = $DowntimehistoryHostsTable->getDowntimesForReporting(
                        $DowntimeHostConditions
                    );
                    $hostDowntimeFormatted = [];
                    /** @var  $hostDowntimeObject DowntimeHost */
                    foreach ($instantReportObjects['Hosts'][$hostId]['downtimes'] as $hostDowntimeObject) {
                        $hostDowntimeFormatted[] = [
                            'DowntimeHost' => [
                                'id'                   => $hostDowntimeObject->get('downtimehistory_id'),
                                'author_name'          => $hostDowntimeObject->get('author_name'),
                                'scheduled_start_time' => $hostDowntimeObject->get('scheduled_start_time'),
                                'scheduled_end_time'   => $hostDowntimeObject->get('scheduled_end_time'),
                                'comment_data'         => $hostDowntimeObject->get('comment_data'),
                                'was_started'          => true,
                                'was_cancelled'        => false
                            ]
                        ];
                    }
                    /** @var  $downtimes DowntimesMerger */
                    $downtimes = DowntimesMerger::mergeDowntimesWithSystemfailures(
                        'DowntimeHost',
                        $hostDowntimeFormatted,
                        $globalDowntimes
                    );
                    $downtimesAndSystemfailures = [];
                    foreach ($downtimes as $downtime) {
                        $DowntimeHost = new Downtime($downtime['DowntimeHost']);
                        $downtimesAndSystemfailures[] = [
                            'DowntimeHost' => $DowntimeHost->toArray()
                        ];
                    }

                    $downtimesFiltered = DaterangesCreator::mergeTimeOverlapping(
                        array_map(
                            function ($downtime) {
                                return [
                                    'start_time' => $downtime['DowntimeHost']['scheduledStartTime'],
                                    'end_time'   => $downtime['DowntimeHost']['scheduledEndTime'],
                                ];
                            },
                            $downtimesAndSystemfailures
                        )
                    );


                    $timeSlices = DaterangesCreator::setDowntimesInTimeslices(
                        $timeSlices,
                        $downtimesFiltered
                    );
                }
                //Process conditions
                /** @var $StatehistoryHostConditions StatehistoryHostConditions */
                $StatehistoryHostConditions = new StatehistoryHostConditions();
                $StatehistoryHostConditions->setOrder(['StatehistoryHosts.state_time' => 'asc']);
                if ($instantReport->get('reflection') === 2) { // type 2 hard state only
                    $StatehistoryHostConditions->setHardStateTypeAndUpState(true); // 1 => Hard State
                }
                $StatehistoryHostConditions->setFrom($fromDate);
                $StatehistoryHostConditions->setTo($toDate);
                $StatehistoryHostConditions->setHostUuid($instantReportHostData['uuid']);

                /** @var StatehistoryHostsTable $StatehistoryHostsTable */
                $StatehistoryHostsTable = $this->DbBackend->getStatehistoryHostsTable();

                /** @var \Statusengine2Module\Model\Entity\StatehistoryHost[] $statehistoriesHost */
                $statehistoriesHost = $StatehistoryHostsTable->getStatehistoryIndex($StatehistoryHostConditions);


                $HoststatusTable = $this->DbBackend->getHoststatusTable();
                $HoststatusFields = new HoststatusFields($this->DbBackend);
                $HoststatusFields
                    ->currentState()
                    ->lastHardState()
                    ->isHardstate()
                    ->lastStateChange();
                $hoststatus = $HoststatusTable->byUuid($instantReportHostData['uuid'], $HoststatusFields);

                $record = $StatehistoryHostsTable->getLastRecord($StatehistoryHostConditions);
                if (!empty($record)) {
                    //Check the current host status to see if it would be newer than the last status history record
                    if (!empty($hoststatus)) {
                        /** @var Hoststatus $Hoststatus */
                        $Hoststatus = new Hoststatus($hoststatus['Hoststatus']);
                        $hostStatusLastStateChange = $Hoststatus->getLastStateChange();

                        if ($record->get('state_time') < $hostStatusLastStateChange && $hostStatusLastStateChange <= $fromDate) {
                            $stateHistoryHostTmp = [
                                'StatehistoryHost' => [
                                    'state_time'      => $fromDate,
                                    'state'           => $Hoststatus->currentState(),
                                    'last_state'      => $Hoststatus->currentState(),
                                    'last_hard_state' => $Hoststatus->getLastHardState(),
                                    'state_type'      => (int)$Hoststatus->isHardState()
                                ]
                            ];

                            /** @var StatehistoryHost $StatehistoryHost */
                            $StatehistoryHost = new StatehistoryHost($stateHistoryHostTmp['StatehistoryHost']);
                            $statehistoriesHost[] = $StatehistoryHost;
                        } else {
                            $statehistoriesHost[] = $record->set('state_time', $fromDate);
                        }
                    }
                }

                if (empty($statehistoriesHost) && !empty($hoststatus)) {
                    /** @var Hoststatus $Hoststatus */
                    $Hoststatus = new Hoststatus($hoststatus['Hoststatus']);
                    if ($Hoststatus->getLastStateChange() <= $fromDate) {
                        $stateHistoryHostTmp = [
                            'StatehistoryHost' => [
                                'state_time'      => $fromDate,
                                'state'           => $Hoststatus->currentState(),
                                'last_state'      => $Hoststatus->currentState(),
                                'last_hard_state' => $Hoststatus->getLastHardState(),
                                'state_type'      => (int)$Hoststatus->isHardState()
                            ]
                        ];

                        /** @var StatehistoryHost $StatehistoryHost */
                        $StatehistoryHost = new StatehistoryHost($stateHistoryHostTmp['StatehistoryHost']);
                        $statehistoriesHost[] = $StatehistoryHost;
                    }
                }

                foreach ($statehistoriesHost as $statehistoryHost) {
                    /** @var StatehistoryHostsTable|StatehistoryHost $statehistoryHost */
                    $StatehistoryHost = new StatehistoryHost($statehistoryHost->toArray());
                    $allStatehistories[] = $StatehistoryHost->toArray();
                }


                $reportData[$hostUuid]['Host']['reportData'] = StatehistoryConverter::generateReportData(
                    $timeSlices,
                    $allStatehistories,
                    ($instantReport->get('reflection') === 2),
                    true
                );

                $reportData[$hostUuid]['Host']['reportData']['percentage'] = StatehistoryConverter::getPercentageValues(
                    $reportData[$hostUuid]['Host']['reportData'],
                    $totalTime,
                    true
                );
            }
            if (!empty($instantReportHostData['Services'])) {
                $instantReportHostData['Services'] = Hash::sort($instantReportHostData['Services'], '{n}.name', 'ASC');
                foreach ($instantReportHostData['Services'] as $serviceId => $service) {
                    $allStatehistories = [];
                    if ($instantReport->get('downtimes') === 1) {
                        $DowntimeServiceConditions = new DowntimeServiceConditions();
                        $DowntimeServiceConditions->setFrom($fromDate);
                        $DowntimeServiceConditions->setTo($toDate);
                        $DowntimeServiceConditions->setContainerIds($this->MY_RIGHTS);
                        $DowntimeServiceConditions->includeCancelledDowntimes();
                        $DowntimeServiceConditions->setOrder(['DowntimeServices.scheduled_start_time' => 'asc']);
                        $DowntimeServiceConditions->setServiceUuid($service['uuid']);
                        /** @var DowntimehistoryHostsTableInterface $DowntimehistoryHostsTable */
                        $DowntimehistoryServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();
                        $instantReportObjects['Hosts'][$hostId]['Services'][$serviceId]['downtimes'] = $DowntimehistoryServicesTable->getDowntimesForReporting(
                            $DowntimeServiceConditions
                        );
                        $serviceDowntimeFormatted = [];
                        /** @var  $hostDowntimeObject DowntimeHost */
                        foreach ($instantReportObjects['Hosts'][$hostId]['Services'][$serviceId]['downtimes'] as $serviceDowntimeObject) {
                            $serviceDowntimeFormatted[] = [
                                'DowntimeService' => [
                                    'id'                   => $serviceDowntimeObject->get('downtimehistory_id'),
                                    'author_name'          => $serviceDowntimeObject->get('author_name'),
                                    'scheduled_start_time' => $serviceDowntimeObject->get('scheduled_start_time'),
                                    'scheduled_end_time'   => $serviceDowntimeObject->get('scheduled_end_time'),
                                    'comment_data'         => $serviceDowntimeObject->get('comment_data'),
                                    'was_started'          => true,
                                    'was_cancelled'        => false
                                ]
                            ];
                        }
                        /** @var  $downtimes DowntimesMerger */
                        $downtimes = DowntimesMerger::mergeDowntimesWithSystemfailures(
                            'DowntimeService',
                            $serviceDowntimeFormatted,
                            $globalDowntimes
                        );
                        $downtimesAndSystemfailures = [];
                        foreach ($downtimes as $downtime) {
                            $DowntimeService = new Downtime($downtime['DowntimeService']);
                            $downtimesAndSystemfailures[] = [
                                'DowntimeService' => $DowntimeService->toArray()
                            ];
                        }

                        $downtimesFiltered = DaterangesCreator::mergeTimeOverlapping(
                            array_map(
                                function ($downtime) {
                                    return [
                                        'start_time' => $downtime['DowntimeService']['scheduledStartTime'],
                                        'end_time'   => $downtime['DowntimeService']['scheduledEndTime'],
                                    ];
                                },
                                $downtimesAndSystemfailures
                            )
                        );


                        $timeSlices = DaterangesCreator::setDowntimesInTimeslices(
                            $timeSlices,
                            $downtimesFiltered
                        );
                    }
                    $serviceUuid = $service['uuid'];

                    /** @var $StatehistoryServiceConditions StatehistoryServiceConditions */
                    $StatehistoryServiceConditions = new StatehistoryServiceConditions();
                    $StatehistoryServiceConditions->setOrder(['StatehistoryServices.state_time' => 'asc']);
                    if ($instantReport->get('reflection') === 2) { // type 2 hard state only
                        $StatehistoryServiceConditions->setHardStateTypeAndOkState(true); // 1 => Hard State !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                    }
                    $StatehistoryServiceConditions->setFrom($fromDate);
                    $StatehistoryServiceConditions->setTo($toDate);
                    $StatehistoryServiceConditions->setServiceUuid($serviceUuid);

                    /** @var StatehistoryServicesTable $StatehistoryServicesTable */
                    $StatehistoryServicesTable = $this->DbBackend->getStatehistoryServicesTable();

                    /** @var \Statusengine2Module\Model\Entity\StatehistoryService[] $statehistoriesService */
                    $statehistoriesService = $StatehistoryServicesTable->getStatehistoryIndex($StatehistoryServiceConditions);

                    $ServicestatusTable = $this->DbBackend->getServicestatusTable();
                    $ServicestatusFields = new ServicestatusFields($this->DbBackend);
                    $ServicestatusFields
                        ->currentState()
                        ->lastHardState()
                        ->isHardstate()
                        ->lastStateChange();
                    $servicestatus = $ServicestatusTable->byUuid($serviceUuid, $ServicestatusFields);

                    $record = $StatehistoryServicesTable->getLastRecord($StatehistoryServiceConditions);
                    if (!empty($record)) {
                        //Check the current service status to see if it would be newer than the last status history record
                        if (!empty($servicestatus)) {
                            $Servicestatus = new Servicestatus($servicestatus['Servicestatus']);
                            $serviceStatusLastStateChange = $Servicestatus->getLastStateChange();

                            if ($record->get('state_time') < $serviceStatusLastStateChange && $serviceStatusLastStateChange <= $fromDate) {
                                $stateHistoryServiceTmp = [
                                    'StatehistoryService' => [
                                        'state_time'      => $fromDate,
                                        'state'           => $Servicestatus->currentState(),
                                        'last_state'      => $Servicestatus->currentState(),
                                        'last_hard_state' => $Servicestatus->getLastHardState(),
                                        'state_type'      => (int)$Servicestatus->isHardState()
                                    ]
                                ];

                                $StatehistoryService = new StatehistoryService($stateHistoryServiceTmp['StatehistoryService']);
                                $statehistoriesService[] = $StatehistoryService;
                            } else {
                                $statehistoriesService[] = $record->set('state_time', $fromDate);
                            }
                        }
                    }


                    if (empty($statehistoriesService) && !empty($servicestatus)) {
                        $Servicestatus = new Servicestatus($servicestatus['Servicestatus']);
                        if ($Servicestatus->getLastStateChange() <= $fromDate) {
                            $stateHistoryServiceTmp = [
                                'StatehistoryService' => [
                                    'state_time'      => $fromDate,
                                    'state'           => $Servicestatus->currentState(),
                                    'last_state'      => $Servicestatus->currentState(),
                                    'last_hard_state' => $Servicestatus->getLastHardState(),
                                    'state_type'      => (int)$Servicestatus->isHardState()
                                ]
                            ];

                            $StateHistoryService = new StatehistoryService($stateHistoryServiceTmp['StatehistoryService']);
                            $statehistoriesService[] = $StateHistoryService;
                        }
                    }

                    foreach ($statehistoriesService as $statehistoryService) {
                        /** @var StatehistoryServicesTable|StatehistoryService $statehistoryService */
                        $StatehistoryService = new StatehistoryService($statehistoryService->toArray());
                        $allStatehistories[] = $StatehistoryService->toArray();
                    }

                    $reportData[$hostUuid]['Host']['Services'][$serviceUuid]['Service']['name'] = $service['name'];
                    $reportData[$hostUuid]['Host']['Services'][$serviceUuid]['Service']['id'] = $service['id'];
                    $reportData[$hostUuid]['Host']['Services'][$serviceUuid]['Service']['reportData'] = StatehistoryConverter::generateReportData(
                        $timeSlices,
                        $allStatehistories,
                        ($instantReport->get('reflection') === 2),
                        false
                    );
                    $reportData[$hostUuid]['Host']['Services'][$serviceUuid]['Service']['reportData']['percentage'] = StatehistoryConverter::getPercentageValues(
                        $reportData[$hostUuid]['Host']['Services'][$serviceUuid]['Service']['reportData'],
                        $totalTime,
                        false
                    );
                }
            }
        }

        $instantReportData = $reportData;
        unset($reportData);
        $reportData['hosts'] = $instantReportData;
        $reportSummaryData = [];
        if ($instantReport->get('summary') === 1) {
            $hostsData = [];
            $servicesData = [];
            $reportSummaryData = [
                'summary_hosts'    => null,
                'summary_services' => null

            ];

            foreach ($reportData['hosts'] as $reportHostData) {
                if (!empty($reportHostData['Host']['reportData'])) {
                    $hostsData[] = [
                        $reportHostData['Host']['reportData'][0],
                        $reportHostData['Host']['reportData'][1],
                        $reportHostData['Host']['reportData'][2]
                    ];
                }
                if (!empty($reportHostData['Host']['Services'])) {
                    foreach ($reportHostData['Host']['Services'] as $serviceData) {
                        if (!empty($serviceData['Service']['reportData'])) {
                            $servicesData[] = [
                                $serviceData['Service']['reportData'][0],
                                $serviceData['Service']['reportData'][1],
                                $serviceData['Service']['reportData'][2],
                                $serviceData['Service']['reportData'][3],
                            ];
                        }
                    }
                }
            }
            if (!empty($hostsData)) {
                $stateSummary = StatehistoryConverter::hostStateSummary($hostsData);
                $reportSummaryData['summary_hosts']['reportData'] = $stateSummary;
                $reportSummaryData['summary_hosts']['reportData']['percentage'] = StatehistoryConverter::getPercentageValues(
                    $stateSummary,
                    array_sum($stateSummary),
                    true
                );
            }
            if (!empty($servicesData)) {
                $stateSummary = StatehistoryConverter::serviceStateSummary($servicesData);
                $reportSummaryData['summary_services']['reportData'] = $stateSummary;
                $reportSummaryData['summary_services']['reportData']['percentage'] = StatehistoryConverter::getPercentageValues(
                    $stateSummary,
                    array_sum($stateSummary),
                    false
                );
            }
            $reportData['hosts'] = [];
        }

        $reportData['reportDetails'] = array_merge($reportDetails, $reportSummaryData);
        return $reportData;
    }
}
