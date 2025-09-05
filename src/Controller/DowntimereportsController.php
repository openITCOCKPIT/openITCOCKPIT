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

namespace App\Controller;

use App\Form\DowntimereportForm;
use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Model\Table\HostsTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\CakePHP\Set;
use itnovum\openITCOCKPIT\Core\DowntimeHostConditions;
use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Reports\DaterangesCreator;
use itnovum\openITCOCKPIT\Core\Reports\DowntimeReportBarChartWidgetDataPreparer;
use itnovum\openITCOCKPIT\Core\Reports\DowntimeReportPieChartWidgetDataPreparer;
use itnovum\openITCOCKPIT\Core\Reports\StatehistoryConverter;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\StatehistoryHostConditions;
use itnovum\openITCOCKPIT\Core\StatehistoryServiceConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryHost;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryService;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use Statusengine2Module\Model\Entity\DowntimeService;
use Statusengine2Module\Model\Table\StatehistoryHostsTable;

/**
 * Class DowntimereportsController
 * @package App\Controller
 */
class DowntimereportsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {

            $downtimeReportForm = new DowntimereportForm();
            $downtimeReportForm->execute($this->request->getData());

            $User = new User($this->getUser());
            $UserTime = UserTime::fromUser($User);

            if (!empty($downtimeReportForm->getErrors())) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $downtimeReportForm->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            /** @var $TimeperiodsTable TimeperiodsTable */
            $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
            $timeperiod = $TimeperiodsTable->getTimeperiodWithTimerangesById($this->request->getData('timeperiod_id'));
            if (empty($timeperiod['Timeperiod']['timeperiod_timeranges'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'timeperiod_id' => [
                        'empty' => 'There are no time frames defined. Time evaluation report data is not available for the selected period.'
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $fromDate = strtotime($this->request->getData('from_date') . ' 00:00:00');
            $toDate = strtotime($this->request->getData('to_date') . ' 23:59:59');
            if ($toDate > time()) {
                $toDate = time();
            }
            $evaluationType = $this->request->getData('evaluation_type');
            $reflectionState = $this->request->getData('reflection_state');

            $hostsUuids = $HostsTable->getHostsByContainerId($this->MY_RIGHTS, 'list', 'uuid');
            if (empty($hostsUuids)) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'hosts' => [
                        'empty' => 'There are no hosts for downtime report available.'
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }
            $downtimeReport = $this->createReport(
                $fromDate,
                $toDate,
                $evaluationType,
                $reflectionState,
                $timeperiod['Timeperiod']['timeperiod_timeranges'],
                $hostsUuids,
                $UserTime
            );

            if ($downtimeReport === null) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'no_downtimes' => [
                        'empty' => __('No downtimes within specified time found ({0} - {1}) !',
                            date('d.m.Y', $fromDate),
                            date('d.m.Y', $toDate)
                        )
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('downtimeReport', $downtimeReport);
            $this->viewBuilder()->setOption('serialize', ['downtimeReport']);
        }
    }

    /**
     * @throws MissingDbBackendException
     */
    public function createPdfReport() {
        //Rewrite GET to "POST"
        $downtimeReportForm = new DowntimereportForm();
        $downtimeReportForm->execute($this->request->getQuery('data', []));

        $User = new User($this->getUser());
        $UserTime = UserTime::fromUser($User);

        if (!empty($downtimeReportForm->getErrors())) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $downtimeReportForm->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if (!$TimeperiodsTable->existsById($this->request->getQuery('data.timeperiod_id', 0))) {
            throw new NotFoundException('Timperiod not found!');
        }

        $timeperiod = $TimeperiodsTable->getTimeperiodWithTimerangesById($this->request->getQuery('data.timeperiod_id', 0));
        if (empty($timeperiod['Timeperiod']['timeperiod_timeranges'])) {

            $this->response = $this->response->withStatus(400);
            $this->set('error', [
                'timeperiod_id' => [
                    'empty' => 'There are no time frames defined. Time evaluation report data is not available for the selected period.'
                ]
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $fromDate = strtotime($this->request->getQuery('data.from_date', date('d.m.Y')) . ' 00:00:00');
        $toDate = strtotime($this->request->getQuery('data.to_date', date('d.m.Y')) . ' 23:59:59');
        if ($toDate > time()) {
            $toDate = time();
        }

        $this->set('fromDate', $fromDate);
        $this->set('toDate', $toDate);


        $evaluationType = $this->request->getQuery('data.evaluation_type', 0);
        $reflectionState = $this->request->getQuery('data.reflection_state', 0);

        $hostsUuids = $HostsTable->getHostsByContainerId($this->MY_RIGHTS, 'list', 'uuid');
        if (empty($hostsUuids)) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', [
                'hosts' => [
                    'empty' => 'There are no hosts for downtime report available.'
                ]
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }
        if ($this->isJsonRequest()) {
            //Only validate parameters
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }
        //User server timezone but with user date format
        $ServerTime = new UserTime(date_default_timezone_get(), $UserTime->getFormatString());

        $downtimeReport = $this->createReport(
            $fromDate,
            $toDate,
            $evaluationType,
            $reflectionState,
            $timeperiod['Timeperiod']['timeperiod_timeranges'],
            $hostsUuids,
            $UserTime
        );

        if ($downtimeReport === null) {
            $this->set('error', [
                'no_downtimes' => [
                    'empty' => __('! No downtimes within specified time found ({0} - {1}) !',
                        date('d.m.Y', $fromDate),
                        date('d.m.Y', $toDate)
                    )
                ]
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);
        }

        $this->set('downtimeReport', $downtimeReport);
        $this->set('UserTime', $ServerTime);

        $this->viewBuilder()->setOption(
            'pdfConfig',
            [
                'download' => true,
                'filename' => __('Downtimereport_') . date('dmY_his') . '.pdf',
            ]
        );
    }


    /**
     * @param int $fromDate timestamp
     * @param int $toDate timestamp
     * @param int $evaluationType 0 => hosts only, 1 => hosts and service
     * @param int $reflectionState 1 => hard and soft state, 2 => only hard states
     * @param array $timeperiodRanges
     * @param array $hostsUuids
     * @param UserTime $UserTime
     * @return array $reportData
     * @throws MissingDbBackendException
     */
    protected function createReport($fromDate, $toDate, $evaluationType, $reflectionState, $timeperiodRanges, $hostsUuids, $UserTime) {
        $DowntimeHostConditions = new DowntimeHostConditions();
        $DowntimeHostConditions->setFrom($fromDate);
        $DowntimeHostConditions->setTo($toDate);
        $DowntimeHostConditions->setContainerIds($this->MY_RIGHTS);
        $DowntimeHostConditions->setOrder(['DowntimeHosts.scheduled_start_time' => 'asc']);
        $DowntimeHostConditions->setHostUuid(array_keys($hostsUuids));
        /** @var DowntimehistoryHostsTableInterface $DowntimehistoryHostsTable */
        $DowntimehistoryHostsTable = $this->DbBackend->getDowntimehistoryHostsTable();


        $downtimes = [
            'Hosts' => []
        ];

        foreach ($DowntimehistoryHostsTable->getDowntimesForReporting($DowntimeHostConditions) as $downtime) {
            $downtime = $downtime->toArray();

            $DowntimeView = new Downtime($downtime);
            $downtimes['Hosts'][] = [
                'author_name'          => $DowntimeView->getAuthorName(),
                'comment_data'         => $DowntimeView->getCommentData(),
                'entry_time'           => date('c', $DowntimeView->getEntryTime()),
                'scheduled_start_time' => date('c', $DowntimeView->getScheduledStartTime()),
                'scheduled_end_time'   => date('c', $DowntimeView->getScheduledEndTime()),
                'duration'             => $DowntimeView->getDuration(),
                'was_started'          => (int)$DowntimeView->wasStarted(),
                'internal_downtime_id' => $DowntimeView->getInternalDowntimeId(),
                'was_cancelled'        => (int)$DowntimeView->wasCancelled(),
                'Hosts'                => $downtime['Hosts'],
                'HostsToContainers'    => $downtime['HostsToContainers']
            ];
        }

        $hosts = [];
        $services = [];
        $reportData = [];
        foreach ($downtimes['Hosts'] as $hostDowntime) {
            $hosts[$hostDowntime['Hosts']['uuid']] = [
                'Host' => $hostDowntime['Hosts']
            ];
        }
        if ($evaluationType == 1) { //Evaluation with services
            $DowntimeServiceConditions = new DowntimeServiceConditions();
            $DowntimeServiceConditions->setFrom($fromDate);
            $DowntimeServiceConditions->setTo($toDate);
            $DowntimeServiceConditions->setContainerIds($this->MY_RIGHTS);
            $DowntimeServiceConditions->setOrder(['DowntimeServices.scheduled_start_time' => 'asc']);
            $DowntimeServiceConditions->setHostUuid(array_keys($hostsUuids));
            /** @var DowntimehistoryHostsTableInterface $DowntimehistoryHostsTable */
            $DowntimehistoryServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();

            $downtimes['Services'] = [];
            /** @var DowntimeService $serviceDowntime */
            foreach ($DowntimehistoryServicesTable->getDowntimesForReporting($DowntimeServiceConditions) as $serviceDowntime) {
                $serviceDowntime = $serviceDowntime->toArray();
                $DowntimeView = new Downtime($serviceDowntime);

                $downtimes['Services'][] = [
                    'author_name'          => $DowntimeView->getAuthorName(),
                    'comment_data'         => $DowntimeView->getCommentData(),
                    'entry_time'           => date('c', $DowntimeView->getEntryTime()),
                    'scheduled_start_time' => date('c', $DowntimeView->getScheduledStartTime()),
                    'scheduled_end_time'   => date('c', $DowntimeView->getScheduledEndTime()),
                    'duration'             => $DowntimeView->getDuration(),
                    'was_started'          => (int)$DowntimeView->wasStarted(),
                    'internal_downtime_id' => $DowntimeView->getInternalDowntimeId(),
                    'was_cancelled'        => (int)$DowntimeView->wasCancelled(),
                    'Services'             => $serviceDowntime['Services'],
                    'Hosts'                => $serviceDowntime['Hosts'],
                    'Servicetemplates'     => $serviceDowntime['Servicetemplates'],
                    'servicename'          => !empty($serviceDowntime['Services']['name']) ? $serviceDowntime['Services']['name'] : $serviceDowntime['Servicetemplates']['name']
                ];

                $hosts[$serviceDowntime['Hosts']['uuid']] = $serviceDowntime['Hosts'];
                $services[$serviceDowntime['Services']['uuid']] = [
                    'Service'         => $serviceDowntime['Services'],
                    'Host'            => $serviceDowntime['Hosts'],
                    'Servicetemplate' => $serviceDowntime['Servicetemplates']
                ];

            }
        }
        if (empty($downtimes['Hosts']) && empty($downtimes['Services'])) {
            return;
        }

        $timeSlices = DaterangesCreator::createDateRanges(
            $fromDate,
            $toDate,
            $timeperiodRanges
        );
        $totalTime = Hash::apply(
            array_map(function ($timeSlice) {
                return $timeSlice['end'] - $timeSlice['start'];
            }, $timeSlices),
            '{n}',
            'array_sum'
        );
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        foreach (array_keys($hosts) as $uuid) {
            $allStatehistories = [];
            $host = Set::classicExtract(
                $HostsTable->getHostByUuid($uuid, false),
                '{(id|address|name|description)}'
            );

            //Process conditions
            $Conditions = new StatehistoryHostConditions();
            $Conditions->setOrder(['StatehistoryHosts.state_time' => 'asc']);
            if ($reflectionState === 2) { // type 2 hard state only
                $Conditions->setHardStateTypeAndUpState(true); // 1 => Hard State
            }
            $Conditions->setFrom($fromDate);
            $Conditions->setTo($toDate);
            $Conditions->setHostUuid($uuid);

            $StatehistoryHostsTable = $this->DbBackend->getStatehistoryHostsTable();

            /** @var \Statusengine2Module\Model\Entity\StatehistoryHost[] $statehistoriesHost */
            $statehistoriesHost = $StatehistoryHostsTable->getStatehistoryIndex($Conditions);
            if (empty($statehistoriesHost)) {
                $record = $StatehistoryHostsTable->getLastRecord($Conditions);
                if (!empty($record)) {
                    $statehistoriesHost[] = $record->set('state_time', $fromDate);
                }
            }

            if (empty($statehistoriesHost)) {
                $HoststatusTable = $this->DbBackend->getHoststatusTable();
                $HoststatusFields = new HoststatusFields($this->DbBackend);
                $HoststatusFields
                    ->currentState()
                    ->lastHardState()
                    ->isHardstate()
                    ->lastStateChange();
                $hoststatus = $HoststatusTable->byUuid($uuid, $HoststatusFields);
                if (!empty($hoststatus)) {
                    $Hoststatus = new Hoststatus($hoststatus['Hoststatus']);
                    if ($Hoststatus->getLastStateChange() <= $fromDate) {
                        $stateHistoryHostTmp = [
                            'StatehistoryHost' => [
                                'state_time'      => $fromDate,
                                'state'           => $Hoststatus->currentState(),
                                'last_state'      => $Hoststatus->currentState(),
                                'last_hard_state' => $Hoststatus->getLastHardState(),
                                'state_type'      => (int)$Hoststatus->isHardState(),
                            ]
                        ];

                        $StatehistoryHost = new StatehistoryHost($stateHistoryHostTmp['StatehistoryHost']);
                        $statehistoriesHost[] = $StatehistoryHost;
                    }
                }
            }
            foreach ($statehistoriesHost as $index => $statehistoryHost) {
                /** @var StatehistoryHostsTable|StatehistoryHost $statehistoryHost */
                $StatehistoryHost = new StatehistoryHost($statehistoryHost->toArray());
                $allStatehistories[] = $StatehistoryHost->toArray();
            }

            $reportData[$uuid]['Host'] = $host;
            $reportData[$uuid]['Host']['reportData'] = StatehistoryConverter::generateReportData(
                $timeSlices,
                $allStatehistories,
                ($reflectionState === 2),
                true
            );
        }

        foreach (array_keys($services) as $uuid) {
            $allStatehistories = [];
            //Process conditions
            $Conditions = new StatehistoryServiceConditions();
            $Conditions->setOrder(['StatehistoryServices.state_time' => 'asc']);
            if ($reflectionState === 2) { // type 2 hard state only
                $Conditions->setHardStateTypeAndOkState(true); // 1 => Hard State
            }
            $Conditions->setFrom($fromDate);
            $Conditions->setTo($toDate);
            $Conditions->setServiceUuid($uuid);

            $StatehistoryServicesTable = $this->DbBackend->getStatehistoryServicesTable();
            $statehistoriesService = $StatehistoryServicesTable->getStatehistoryIndex($Conditions);

            if (empty($statehistoriesService)) {
                $record = $StatehistoryServicesTable->getLastRecord($Conditions);
                if (!empty($record)) {
                    $statehistoriesService[] = $record->set('state_time', $fromDate);
                }
            }
            if (empty($statehistoriesService)) {
                $ServicestatusTable = $this->DbBackend->getServicestatusTable();
                $ServicestatusFields = new ServicestatusFields($this->DbBackend);
                $ServicestatusFields
                    ->currentState()
                    ->lastHardState()
                    ->isHardstate()
                    ->lastStateChange();
                $servicestatus = $ServicestatusTable->byUuid($uuid, $ServicestatusFields);
                if (!empty($servicestatus)) {
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
            }

            foreach ($statehistoriesService as $statehistoryService) {
                /** @var \Statusengine2Module\Model\Entity\StatehistoryService|StatehistoryService $statehistoryService */
                $StatehistoryService = new StatehistoryService($statehistoryService->toArray());
                $allStatehistories[] = $StatehistoryService->toArray();
            }
            $reportData[$services[$uuid]['Host']['uuid']]['Services'][$uuid] = $services[$uuid];

            $reportData[$services[$uuid]['Host']['uuid']]['Services'][$uuid]['Service']['reportData'] = StatehistoryConverter::generateReportData(
                $timeSlices,
                $allStatehistories,
                ($reflectionState === 2),
                false
            );
        }
        $downtimeReport = [];
        foreach ($reportData as $reportResult) {
            if ($reportResult['Host']['reportData'][1] > 0) {
                $downtimeReport['hostsWithOutages'][] = $reportResult;
            } else {
                $downtimeReport['hostsWithoutOutages'][] = $reportResult;
            }
        }
        if (!empty($downtimeReport['hostsWithOutages'])) {
            $downtimeReport['hostsWithOutages'] = Hash::sort(
                $downtimeReport['hostsWithOutages'],
                '{n}.Host.reportData.1',
                'desc'
            );


            $hostsWithOutagesChunk = $downtimeReport['hostsWithOutages'];
            $downtimeReport['hostsWithOutages'] = [];
            foreach (array_chunk($hostsWithOutagesChunk, 10) as $hostsChunk) {
                $hostBarChartData = DowntimeReportBarChartWidgetDataPreparer::getDataForHostsBarChart(
                    $hostsChunk,
                    $totalTime
                );

                $hosts = [];
                foreach ($hostsChunk as $index => $host) {
                    if (isset($host['Services'])) {
                        foreach ($host['Services'] as $uuid => $serviceData) {
                            $host['Services'][$uuid]['pieChartData'] = DowntimeReportPieChartWidgetDataPreparer::getDataForServicePieChart(
                                $serviceData,
                                $totalTime,
                                $UserTime
                            );
                        }
                    }

                    $tmpHost = $host;
                    $tmpHost['pieChartData'] = DowntimeReportPieChartWidgetDataPreparer::getDataForHostPieChartWidget(
                        $host,
                        $totalTime,
                        $UserTime
                    );

                    $hosts[] = $tmpHost;
                }

                $downtimeReport['hostsWithOutages'][] = [
                    'hosts'            => $hosts,
                    'hostBarChartData' => $hostBarChartData
                ];
            }
        }
        if (!empty($downtimeReport['hostsWithoutOutages'])) {
            $hostsWithoutOutages = $downtimeReport['hostsWithoutOutages'];
            $downtimeReport['hostsWithoutOutages'] = [];
            $hosts = [];
            foreach ($hostsWithoutOutages as $index => $host) {
                if (isset($host['Services'])) {
                    foreach ($host['Services'] as $uuid => $serviceData) {
                        $host['Services'][$uuid]['pieChartData'] = DowntimeReportPieChartWidgetDataPreparer::getDataForServicePieChart(
                            $serviceData,
                            $totalTime,
                            $UserTime
                        );
                    }
                }

                $tmpHost = $host;
                $tmpHost['pieChartData'] = DowntimeReportPieChartWidgetDataPreparer::getDataForHostPieChartWidget(
                    $host,
                    $totalTime,
                    $UserTime
                );

                $hosts[] = $tmpHost;
            }
            $downtimeReport['hostsWithoutOutages'] = [
                'hosts' => $hosts
            ];
        }

        $downtimeReport['totalTime'] = $totalTime;
        $downtimeReport['downtimes'] = $downtimes;
        return $downtimeReport;
    }
    
}
