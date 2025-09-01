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

use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Lib\Interfaces\DowntimehistoryServicesTableInterface;
use App\Model\Table\HostsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\HostDowntimesControllerRequest;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\ServiceDowntimesControllerRequest;
use itnovum\openITCOCKPIT\Core\DowntimeHostConditions;
use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\ContainerPermissions;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Class DowntimesController
 * @package App\Controller
 */
class DowntimesController extends AppController {

    public function host() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $HostDowntimesControllerRequest = new HostDowntimesControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostDowntimesControllerRequest->getPage());

        //Process conditions
        $DowntimeHostConditions = new DowntimeHostConditions();
        $DowntimeHostConditions->setFrom($HostDowntimesControllerRequest->getFrom());
        $DowntimeHostConditions->setTo($HostDowntimesControllerRequest->getTo());
        $DowntimeHostConditions->setHideExpired($HostDowntimesControllerRequest->hideExpired());
        $DowntimeHostConditions->setIsRunning($HostDowntimesControllerRequest->isRunning());
        $DowntimeHostConditions->setContainerIds($this->MY_RIGHTS);
        $DowntimeHostConditions->setOrder($HostDowntimesControllerRequest->getOrderForPaginator('DowntimeHosts.scheduled_start_time', 'desc'));
        $DowntimeHostConditions->setConditions($HostDowntimesControllerRequest->getIndexFilters());

        /** @var DowntimehistoryHostsTableInterface $DowntimehistoryHostsTable */
        $DowntimehistoryHostsTable = $this->DbBackend->getDowntimehistoryHostsTable();
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $hostDowntimes = $DowntimehistoryHostsTable->getDowntimes($DowntimeHostConditions, $PaginateOMat);

        //Load containers for hosts, for non root users
        $hostContainers = [];
        $hostIds = [];
        if (!empty($hostDowntimes) && $this->hasRootPrivileges === false) {
            foreach ($hostDowntimes as $record) {
                $hostIds[] = $record->get('Hosts')['id'];
            }

            $hostIds = array_unique($hostIds);

            foreach ($hostIds as $hostId) {
                $host = $HostsTable->getHostByIdForPermissionCheck($hostId);
                $hostContainers[$host->get('id')] = $host->getContainerIds();
            }
        }

        //Prepare data for API
        $all_host_downtimes = [];
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        foreach ($hostDowntimes as $hostDowntime) {
            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $containerIds = [];
                if (isset($hostContainers[$hostDowntime->get('Hosts')['id']])) {
                    $containerIds = $hostContainers[$hostDowntime->get('Hosts')['id']];
                }

                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $containerIds);
                $allowEdit = $ContainerPermissions->hasPermission();
            }

            $Host = new Host($hostDowntime->get('Hosts'), $allowEdit);
            $HostDowntime = new Downtime($hostDowntime->toArray(), $allowEdit, $UserTime);

            $all_host_downtimes[] = [
                'Host'         => $Host->toArray(),
                'DowntimeHost' => $HostDowntime->toArray()
            ];
        }


        $this->set('all_host_downtimes', $all_host_downtimes);
        $toJson = ['all_host_downtimes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_host_downtimes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function service() {
        if (!$this->isAngularJsRequest()) {
            return;
        }

        $ServiceDowntimesControllerRequest = new ServiceDowntimesControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceDowntimesControllerRequest->getPage());

        //Process conditions
        $DowntimeServiceConditions = new DowntimeServiceConditions();
        $DowntimeServiceConditions->setFrom($ServiceDowntimesControllerRequest->getFrom());
        $DowntimeServiceConditions->setTo($ServiceDowntimesControllerRequest->getTo());
        $DowntimeServiceConditions->setHideExpired($ServiceDowntimesControllerRequest->hideExpired());
        $DowntimeServiceConditions->setIsRunning($ServiceDowntimesControllerRequest->isRunning());
        $DowntimeServiceConditions->setContainerIds($this->MY_RIGHTS);
        $DowntimeServiceConditions->setOrder($ServiceDowntimesControllerRequest->getOrderForPaginator('DowntimeServices.scheduled_start_time', 'desc'));
        $DowntimeServiceConditions->setConditions($ServiceDowntimesControllerRequest->getIndexFilters());

        /** @var DowntimehistoryServicesTableInterface $DowntimehistoryServicesTable */
        $DowntimehistoryServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $serviceDowntimes = $DowntimehistoryServicesTable->getDowntimes($DowntimeServiceConditions, $PaginateOMat);

        //Load containers for hosts, for non root users
        $hostContainers = [];
        $hostIds = [];
        if (!empty($serviceDowntimes) && $this->hasRootPrivileges === false && $this->hasPermission('edit', 'services')) {
            foreach ($serviceDowntimes as $record) {
                $hostIds[] = $record->get('Hosts')['id'];
            }

            $hostIds = array_unique($hostIds);

            foreach ($hostIds as $hostId) {
                $host = $HostsTable->getHostByIdForPermissionCheck($hostId);
                $hostContainers[$host->get('id')] = $host->getContainerIds();
            }
        }


        //Prepare data for API
        $all_service_downtimes = [];
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        foreach ($serviceDowntimes as $serviceDowntime) {
            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $containerIds = [];
                if (isset($hostContainers[$serviceDowntime->get('Hosts')['id']])) {
                    $containerIds = $hostContainers[$serviceDowntime->get('Hosts')['id']];
                }
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $containerIds);
                $allowEdit = $ContainerPermissions->hasPermission();
            }


            $Host = new Host($serviceDowntime->get('Hosts'), $allowEdit);
            $Service = new Service($serviceDowntime->get('Services'), $serviceDowntime->get('servicename'), $allowEdit);
            $ServiceDowntime = new Downtime($serviceDowntime->toArray(), $allowEdit, $UserTime);

            $all_service_downtimes[] = [
                'Host'            => $Host->toArray(),
                'Service'         => $Service->toArray(),
                'DowntimeService' => $ServiceDowntime->toArray()
            ];
        }

        $this->set('all_service_downtimes', $all_service_downtimes);
        $toJson = ['all_service_downtimes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_service_downtimes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function validateDowntimeInputFromAngular() {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $error = ['Downtime' => []];
        $data = $this->request->getData();
        if (!isset($data['comment']) || strlen($data['comment']) === 0) {
            $error['Downtime']['comment'][] = __('Comment can not be empty');
        }

        if (!isset($data['from_date']) || strlen($data['from_date']) === 0) {
            $error['Downtime']['from_date'][] = __('Start date can not be empty');
        }

        if (!isset($data['from_time']) || strlen($data['from_time']) === 0) {
            $error['Downtime']['from_time'][] = __('Start time can not be empty');
        }

        if (!isset($data['to_date']) || strlen($data['to_date']) === 0) {
            $error['Downtime']['to_date'][] = __('End date can not be empty');
        }

        if (!isset($data['to_time']) || strlen($data['to_time']) === 0) {
            $error['Downtime']['to_time'][] = __('End time can not be empty');
        }

        if (empty($error['Downtime'])) {
            $start = sprintf('%s %s', $data['from_date'], $data['from_time']);
            $end = sprintf('%s %s', $data['to_date'], $data['to_time']);
            if (strtotime($start) === false) {
                $error['Downtime']['from_date'][] = __('Date is not valid');
            }

            if (strtotime($end) === false) {
                $error['Downtime']['to_date'][] = __('Date is not valid');
            }
        }

        if (!empty($error['Downtime'])) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('error', $error);
            $this->viewBuilder()->setOption('serialize', ['error', 'success']);
            return;

        }
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();
        $offset = $UserTime->getUserTimeToServerOffset();

        $start = strtotime($start);
        $end = strtotime($end);
        $js_start = $start - $offset;
        $js_end = $end - $offset;

        $start = date('d.m.Y H:i', $start - $offset);
        $end = date('d.m.Y H:i', $end - $offset);

        $this->set('success', true);
        $this->set('error', $error);
        $this->set('start', $start);
        $this->set('end', $end);
        $this->set('js_start', $js_start);
        $this->set('js_end', $js_end);

        $this->viewBuilder()->setOption('serialize', ['error', 'success', 'start', 'end', 'js_start', 'js_end']);
    }

    /**
     * @param int $internalDowntimeId
     * @throws MissingDbBackendException
     */
    public function delete($internalDowntimeId = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        if ($internalDowntimeId === null || !is_numeric($internalDowntimeId)) {
            throw new \InvalidArgumentException('$internalDowntimeId needs to be an integer!');
        }

        if ($this->request->getData('type') === null) {
            throw new \InvalidArgumentException('Parameter type is missing');
        }

        $GearmanClient = new Gearman();

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $DowntimeHostsTable = $this->DbBackend->getDowntimehistoryHostsTable();
        $DowntimeServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();

        switch ($this->request->getData('type')) {
            case 'host':
                $includeServices = $this->request->getData('includeServices', false);
                //Find current downtime
                $downtime = $DowntimeHostsTable->getHostUuidWithDowntimeByInternalDowntimeId($internalDowntimeId);
                if (empty($downtime)) {
                    $this->set('success', false);
                    $this->set('message', __('Could not find downtime'));
                    $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                    return;
                }

                $Downtime = new Downtime($downtime['DowntimeHosts']);

                try {
                    $host = $HostsTable->getHostByUuid($downtime['Hosts']['uuid']);
                } catch (RecordNotFoundException $e) {
                    $this->set('success', false);
                    $this->set('message', $e->getMessage());
                    $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                    return;
                }

                if ($includeServices) {
                    $servicesInternalDowntimeIds = $DowntimeServicesTable->getServiceDowntimesByHostAndDowntime(
                        $host->get('id'),
                        $Downtime
                    );

                    //Delete Host downtime
                    $GearmanClient->sendBackground('deleteHostDowntime', [
                        'internal_downtime_id' => $internalDowntimeId,
                        'satellite_id'         => $host->get('satellite_id'),
                        'hostUuid'             => $host->get('uuid'),
                        'downtime'             => $Downtime->toArray()
                    ]);

                    //Delete corresponding service downtimes
                    foreach ($servicesInternalDowntimeIds as $serviceInternalDowntimeId) {
                        $serviceDowntimeArray = $DowntimeServicesTable->getHostAndServiceUuidWithDowntimeByInternalDowntimeId($serviceInternalDowntimeId);
                        if (!empty($serviceDowntimeArray)) {
                            $ServiceDowntime = new Downtime($serviceDowntimeArray['DowntimeServices']);

                            $GearmanClient->sendBackground('deleteServiceDowntime', [
                                'internal_downtime_id' => $internalDowntimeId,
                                'satellite_id'         => $host->get('satellite_id'),
                                'hostUuid'             => $host->get('uuid'),
                                'serviceUuid'          => $serviceDowntimeArray['Services']['uuid'],
                                'downtime'             => $ServiceDowntime->toArray()
                            ]);
                        }
                    }
                } else {
                    //Only delete Host downtime
                    $GearmanClient->sendBackground('deleteHostDowntime', [
                        'internal_downtime_id' => $internalDowntimeId,
                        'satellite_id'         => $host->get('satellite_id'),
                        'hostUuid'             => $host->get('uuid'),
                        'downtime'             => $Downtime->toArray()
                    ]);
                }
                break;

            default:
                //Only delete Service downtime
                $serviceDowntimeArray = $DowntimeServicesTable->getHostAndServiceUuidWithDowntimeByInternalDowntimeId($internalDowntimeId);
                if (!empty($serviceDowntimeArray)) {
                    $ServiceDowntime = new Downtime($serviceDowntimeArray['DowntimeServices']);

                    try {
                        $host = $HostsTable->getHostByUuid($serviceDowntimeArray['Hosts']['uuid']);
                    } catch (RecordNotFoundException $e) {
                        $this->set('success', false);
                        $this->set('message', $e->getMessage());
                        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                        return;
                    }

                    $GearmanClient->sendBackground('deleteServiceDowntime', [
                        'internal_downtime_id' => $internalDowntimeId,
                        'satellite_id'         => $host->get('satellite_id'),
                        'hostUuid'             => $host->get('uuid'),
                        'serviceUuid'          => $serviceDowntimeArray['Services']['uuid'],
                        'downtime'             => $ServiceDowntime->toArray()
                    ]);
                }

                break;
        }
        $this->set('success', true);
        $this->set('message', __('Successfully'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }
    
}
