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

use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemdowntimesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\AngularRequest;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\SystemdowntimesConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\ContainerPermissions;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\Systemdowntime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\SystemdowntimesFilter;


/**
 * Class SystemdowntimesController
 * @package App\Controller
 */
class SystemdowntimesController extends AppController {

    public function host() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $AngularRequest = new AngularRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularRequest->getPage());

        $SystemdowntimesFilter = new SystemdowntimesFilter($this->request);
        $Conditions = new SystemdowntimesConditions();

        //Process conditions
        if (!$this->hasRootPrivileges) {
            $Conditions->setContainerIds($this->MY_RIGHTS);
        }
        $Conditions->setOrder($AngularRequest->getOrderForPaginator('Systemdowntimes.from_time', 'desc'));
        $Conditions->setConditions($SystemdowntimesFilter->hostFilter());

        /** @var $SystemdowntimesTable SystemdowntimesTable */
        $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');

        $recurringHostDowntimes = $SystemdowntimesTable->getRecurringHostDowntimes($Conditions, $PaginateOMat);

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Prepare data for API
        $all_host_recurring_downtimes = [];

        foreach ($recurringHostDowntimes as $recurringHostDowntime) {
            if (!isset($recurringHostDowntime['Hosts'])) {
                continue;
            }
            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $containerIds = explode(',', $recurringHostDowntime['container_ids']);
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $containerIds);
                $allowEdit = $ContainerPermissions->hasPermission();
            }
            $Host = new Host($recurringHostDowntime['Hosts']);
            $Systemdowntime = new Systemdowntime($recurringHostDowntime, $UserTime);

            $tmpRecord = [
                'Host'           => $Host->toArray(),
                'Systemdowntime' => $Systemdowntime->toArray()
            ];
            $tmpRecord['Host']['allow_edit'] = $allowEdit;
            $all_host_recurring_downtimes[] = $tmpRecord;
        }
        $this->set('all_host_recurring_downtimes', $all_host_recurring_downtimes);
        $toJson = ['all_host_recurring_downtimes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_host_recurring_downtimes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function service() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $AngularRequest = new AngularRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularRequest->getPage());

        $SystemdowntimesFilter = new SystemdowntimesFilter($this->request);
        $Conditions = new SystemdowntimesConditions();

        //Process conditions
        if (!$this->hasRootPrivileges) {
            $Conditions->setContainerIds($this->MY_RIGHTS);
        }
        $Conditions->setOrder($AngularRequest->getOrderForPaginator('Systemdowntimes.from_time', 'desc'));
        $Conditions->setConditions($SystemdowntimesFilter->serviceFilter());

        /** @var $SystemdowntimesTable SystemdowntimesTable */
        $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
        $recurringServiceDowntimes = $SystemdowntimesTable->getRecurringServiceDowntimes($Conditions, $PaginateOMat);

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Prepare data for API
        $all_service_recurring_downtimes = [];
        foreach ($recurringServiceDowntimes as $recurringServiceDowntime) {
            if (!isset($recurringServiceDowntime['Services'])) {
                continue;
            }

            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $containerIds = explode(',', $recurringServiceDowntime['container_ids']);
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $containerIds);
                $allowEdit = $ContainerPermissions->hasPermission();
            }

            $Service = new Service($recurringServiceDowntime['Services'], $recurringServiceDowntime['servicename'], $allowEdit);
            $Host = new Host($recurringServiceDowntime['Hosts'], $allowEdit);
            $Systemdowntime = new Systemdowntime($recurringServiceDowntime, $UserTime);

            $tmpRecord = [
                'Service'        => $Service->toArray(),
                'Host'           => $Host->toArray(),
                'Systemdowntime' => $Systemdowntime->toArray()
            ];
            $tmpRecord['Host']['allow_edit'] = $allowEdit;
            $all_service_recurring_downtimes[] = $tmpRecord;
        }


        $this->set('all_service_recurring_downtimes', $all_service_recurring_downtimes);
        $toJson = ['all_service_recurring_downtimes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_service_recurring_downtimes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function hostgroup() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $AngularRequest = new AngularRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularRequest->getPage());

        $SystemdowntimesFilter = new SystemdowntimesFilter($this->request);
        $Conditions = new SystemdowntimesConditions();

        //Process conditions
        if (!$this->hasRootPrivileges) {
            $Conditions->setContainerIds($this->MY_RIGHTS);
        }
        $Conditions->setOrder($AngularRequest->getOrderForPaginator('Systemdowntimes.from_time', 'desc'));
        $Conditions->setConditions($SystemdowntimesFilter->hostgroupFilter());

        /** @var $SystemdowntimesTable SystemdowntimesTable */
        $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');

        $recurringHostgroupDowntimes = $SystemdowntimesTable->getRecurringHostgroupDowntimes($Conditions, $PaginateOMat);

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Prepare data for API
        $all_hostgroup_recurring_downtimes = [];
        foreach ($recurringHostgroupDowntimes as $recurringHostgroupDowntime) {
            if (!isset($recurringHostgroupDowntime['hostgroup'])) {
                continue;
            }

            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, [$recurringHostgroupDowntime['hostgroup']['container_id']]);
                $allowEdit = $ContainerPermissions->hasPermission();
            }

            $Systemdowntime = new Systemdowntime($recurringHostgroupDowntime, $UserTime);

            $tmpRecord = [
                'Container'      => $recurringHostgroupDowntime['hostgroup']['container'],
                'Hostgroup'      => $recurringHostgroupDowntime['hostgroup'],
                'Systemdowntime' => $Systemdowntime->toArray()
            ];
            $tmpRecord['Hostgroup']['allow_edit'] = $allowEdit;
            $all_hostgroup_recurring_downtimes[] = $tmpRecord;
        }


        $this->set('all_hostgroup_recurring_downtimes', $all_hostgroup_recurring_downtimes);
        $toJson = ['all_hostgroup_recurring_downtimes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_hostgroup_recurring_downtimes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function node() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $AngularRequest = new AngularRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularRequest->getPage());

        $SystemdowntimesFilter = new SystemdowntimesFilter($this->request);
        $Conditions = new SystemdowntimesConditions();

        //Process conditions
        if (!$this->hasRootPrivileges) {
            $Conditions->setContainerIds($this->MY_RIGHTS);
        }
        $Conditions->setOrder($AngularRequest->getOrderForPaginator('Systemdowntimes.from_time', 'desc'));
        $Conditions->setConditions($SystemdowntimesFilter->nodeFilter());

        /** @var $SystemdowntimesTable SystemdowntimesTable */
        $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');

        $recurringNodeDowntimes = $SystemdowntimesTable->getRecurringNodeDowntimes($Conditions, $PaginateOMat);
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Prepare data for API
        $all_node_recurring_downtimes = [];
        foreach ($recurringNodeDowntimes as $recurringNodeDowntime) {
            if (!isset($recurringNodeDowntime['container'])) {
                continue;
            }

            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, [$recurringNodeDowntime['id']]);
                $allowEdit = $ContainerPermissions->hasPermission();
            }

            $Systemdowntime = new Systemdowntime($recurringNodeDowntime, $UserTime);

            $tmpRecord = [
                'Container'      => $recurringNodeDowntime['container'],
                'Systemdowntime' => $Systemdowntime->toArray()
            ];
            $tmpRecord['Container']['allow_edit'] = $allowEdit;
            $all_node_recurring_downtimes[] = $tmpRecord;
        }


        $this->set('all_node_recurring_downtimes', $all_node_recurring_downtimes);
        $toJson = ['all_node_recurring_downtimes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_node_recurring_downtimes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function addHostdowntime() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            /** @var $SystemdowntimesTable SystemdowntimesTable */
            $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
            $data = $this->request->getData('Systemdowntime');


            if (!isset($data['object_id']) || empty($data['object_id'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'object_id' => [
                        '_empty' => __('You have to select at least on object.')
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if (!is_array($data['object_id'])) {
                $data['object_id'] = [$data['object_id']];
            }

            if (isset($data['weekdays']) && is_array($data['weekdays'])) {
                $data['weekdays'] = implode(',', $data['weekdays']);
            }

            $User = new User($this->getUser());

            $data['author'] = $User->getFullName();

            $objectIds = $data['object_id'];
            unset($data['object_id']);

            $Entities = [];
            foreach ($objectIds as $objectId) {
                $tmpData = $data;
                $tmpData['object_id'] = $objectId;
                $Entity = $SystemdowntimesTable->newEntity($tmpData);
                if ($Entity->hasErrors()) {
                    //On entity has an error so ALL entities has an error!
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $Entity->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }

                //No errors
                $Entities[] = $Entity;
            }

            $isRecurringDowntime = $data['is_recurring'] === 1 || $data['is_recurring'] === '1';
            $success = true;

            $UserTime = $User->getUserTime();

            if ($isRecurringDowntime) {
                //Recurring downtimes will get saved to the database
                foreach ($Entities as $Entity) {
                    /** @var \App\Model\Entity\Systemdowntime $Entity */
                    $Entity->shiftFromTime($UserTime);
                }

                $success = $SystemdowntimesTable->saveMany($Entities);
            } else {
                $offset = $UserTime->getUserTimeToServerOffset();

                //Normal downtimes will be passed to the monitoring engine
                $GearmanClient = new Gearman();
                /** @var $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                foreach ($Entities as $Entity) {
                    $hostUuid = $HostsTable->getHostUuidById($Entity->get('object_id'));
                    $start = strtotime(
                        sprintf(
                            '%s %s',
                            $Entity->get('from_date'),
                            $Entity->get('from_time')
                        ));
                    $end = strtotime(
                        sprintf('%s %s',
                            $Entity->get('to_date'),
                            $Entity->get('to_time')
                        ));

                    $payload = [
                        'hostUuid'     => $hostUuid,
                        'downtimetype' => $Entity->get('downtimetype_id'),
                        'start'        => $start - $offset,
                        'end'          => $end - $offset,
                        'comment'      => $Entity->get('comment'),
                        'author'       => $Entity->get('author'),
                    ];
                    $GearmanClient->sendBackground('createHostDowntime', $payload);
                }
            }

            $this->set('success', $success);
            $this->viewBuilder()->setOption('serialize', ['success']);
        }
    }

    public function addHostgroupdowntime() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            /** @var $SystemdowntimesTable SystemdowntimesTable */
            $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
            $data = $this->request->getData('Systemdowntime');


            if (!isset($data['object_id']) || empty($data['object_id'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'object_id' => [
                        '_empty' => __('You have to select at least on object.')
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if (!is_array($data['object_id'])) {
                $data['object_id'] = [$data['object_id']];
            }

            if (isset($data['weekdays']) && is_array($data['weekdays'])) {
                $data['weekdays'] = implode(',', $data['weekdays']);
            }

            $User = new User($this->getUser());

            $data['author'] = $User->getFullName();

            $objectIds = $data['object_id'];
            unset($data['object_id']);

            $Entities = [];
            foreach ($objectIds as $objectId) {
                $tmpData = $data;
                $tmpData['object_id'] = $objectId;
                $Entity = $SystemdowntimesTable->newEntity($tmpData);
                if ($Entity->hasErrors()) {
                    //On entity has an error so ALL entities has an error!
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $Entity->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }

                //No errors
                $Entities[] = $Entity;
            }

            $isRecurringDowntime = $data['is_recurring'] === 1 || $data['is_recurring'] === '1';
            $success = true;

            $UserTime = $User->getUserTime();

            if ($isRecurringDowntime) {
                //Recurring downtimes will get saved to the database
                foreach ($Entities as $Entity) {
                    /** @var \App\Model\Entity\Systemdowntime $Entity */
                    $Entity->shiftFromTime($UserTime);
                }
                $success = $SystemdowntimesTable->saveMany($Entities);
            } else {
                //Normal downtimes will be passed to the monitoring engine
                $GearmanClient = new Gearman();
                /** @var $HostgroupsTable HostgroupsTable */
                $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

                $offset = $UserTime->getUserTimeToServerOffset();

                foreach ($Entities as $Entity) {
                    $start = strtotime(
                        sprintf(
                            '%s %s',
                            $Entity->get('from_date'),
                            $Entity->get('from_time')
                        ));
                    $end = strtotime(
                        sprintf('%s %s',
                            $Entity->get('to_date'),
                            $Entity->get('to_time')
                        ));

                    $hostgroupUuid = $HostgroupsTable->getHostgroupUuidById($Entity->get('object_id'));
                    $payload = [
                        'hostgroupUuid' => $hostgroupUuid,
                        'downtimetype'  => $Entity->get('downtimetype_id'),
                        'start'         => $start - $offset,
                        'end'           => $end - $offset,
                        'comment'       => $Entity->get('comment'),
                        'author'        => $Entity->get('author')
                    ];

                    $GearmanClient->sendBackground('createHostgroupDowntime', $payload);
                }
            }

            $this->set('success', $success);
            $this->viewBuilder()->setOption('serialize', ['success']);
        }
    }

    public function addServicedowntime() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            /** @var $SystemdowntimesTable SystemdowntimesTable */
            $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
            $data = $this->request->getData('Systemdowntime');


            if (!isset($data['object_id']) || empty($data['object_id'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'object_id' => [
                        '_empty' => __('You have to select at least on object.')
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if (!is_array($data['object_id'])) {
                $data['object_id'] = [$data['object_id']];
            }

            if (isset($data['weekdays']) && is_array($data['weekdays'])) {
                $data['weekdays'] = implode(',', $data['weekdays']);
            }

            $User = new User($this->getUser());

            $data['author'] = $User->getFullName();

            $objectIds = $data['object_id'];
            unset($data['object_id']);

            $Entities = [];
            foreach ($objectIds as $objectId) {
                $tmpData = $data;
                $tmpData['object_id'] = $objectId;
                $Entity = $SystemdowntimesTable->newEntity($tmpData);
                if ($Entity->hasErrors()) {
                    //On entity has an error so ALL entities has an error!
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $Entity->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }

                //No errors
                $Entities[] = $Entity;
            }

            $isRecurringDowntime = $data['is_recurring'] === 1 || $data['is_recurring'] === '1';
            $success = true;

            $UserTime = $User->getUserTime();

            if ($isRecurringDowntime) {
                //Recurring downtimes will get saved to the database
                foreach ($Entities as $Entity) {
                    /** @var \App\Model\Entity\Systemdowntime $Entity */
                    $Entity->shiftFromTime($UserTime);
                }
                $success = $SystemdowntimesTable->saveMany($Entities);
            } else {
                //Normal downtimes will be passed to the monitoring engine
                $GearmanClient = new Gearman();
                /** @var $ServicesTable ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');

                $offset = $UserTime->getUserTimeToServerOffset();

                foreach ($Entities as $Entity) {
                    $start = strtotime(
                        sprintf(
                            '%s %s',
                            $Entity->get('from_date'),
                            $Entity->get('from_time')
                        ));
                    $end = strtotime(
                        sprintf('%s %s',
                            $Entity->get('to_date'),
                            $Entity->get('to_time')
                        ));

                    $service = $ServicesTable->getServiceByIdForDowntimeCreation($Entity->get('object_id'));

                    $hostUuid = $service->get('host')->get('uuid');
                    $serviceUuid = $service->get('uuid');

                    $payload = [
                        'hostUuid'    => $hostUuid,
                        'serviceUuid' => $serviceUuid,
                        'start'       => $start - $offset,
                        'end'         => $end - $offset,
                        'comment'     => $Entity->get('comment'),
                        'author'      => $Entity->get('author')
                    ];

                    $GearmanClient->sendBackground('createServiceDowntime', $payload);
                }
            }

            $this->set('success', $success);
            $this->viewBuilder()->setOption('serialize', ['success']);
        }
    }

    public function addContainerdowntime() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            /** @var $SystemdowntimesTable SystemdowntimesTable */
            $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');
            $data = $this->request->getData('Systemdowntime');


            if (!isset($data['object_id']) || empty($data['object_id'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'object_id' => [
                        '_empty' => __('You have to select at least on object.')
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if (!is_array($data['object_id'])) {
                $data['object_id'] = [$data['object_id']];
            }

            if (isset($data['weekdays']) && is_array($data['weekdays'])) {
                $data['weekdays'] = implode(',', $data['weekdays']);
            }

            $User = new User($this->getUser());

            $data['author'] = $User->getFullName();

            $objectIds = $data['object_id'];
            unset($data['object_id']);

            $Entities = [];
            foreach ($objectIds as $objectId) {
                $tmpData = $data;
                $tmpData['object_id'] = $objectId;
                $Entity = $SystemdowntimesTable->newEntity($tmpData);
                if ($Entity->hasErrors()) {
                    //On entity has an error so ALL entities has an error!
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $Entity->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }

                //No errors
                $Entities[] = $Entity;
            }

            $isRecurringDowntime = $data['is_recurring'] === 1 || $data['is_recurring'] === '1';
            $success = true;

            $UserTime = $User->getUserTime();

            if ($isRecurringDowntime) {
                //Recurring downtimes will get saved to the database
                foreach ($Entities as $Entity) {
                    /** @var \App\Model\Entity\Systemdowntime $Entity */
                    $Entity->shiftFromTime($UserTime);
                }

                $success = $SystemdowntimesTable->saveMany($Entities);
            } else {
                //Normal downtimes will be passed to the monitoring engine
                $GearmanClient = new Gearman();
                /** @var $ContainersTable ContainersTable */
                $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
                /** @var $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                $offset = $UserTime->getUserTimeToServerOffset();

                foreach ($Entities as $Entity) {

                    $containerId = $Entity->get('object_id');
                    $containerIds = [$containerId];
                    if ($Entity->get('is_recursive') == 1) {
                        //Recursive container lookup is enabled
                        //Lookup all child containers the user has write permissions to to select the hosts and create
                        //the downtimes

                        if ($containerId == ROOT_CONTAINER) {
                            $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
                        } else {
                            $childrenContainers = $ContainersTable->resolveChildrenOfContainerIds($containerId);
                            $childrenContainers = $ContainersTable->removeRootContainer($childrenContainers);
                        }

                        foreach ($childrenContainers as $childrenContainerId) {
                            if (isset($this->MY_RIGHTS_LEVEL[$childrenContainerId]) || $this->MY_RIGHTS_LEVEL[$childrenContainerId] === WRITE_RIGHT) {
                                $containerIds[] = $childrenContainerId;
                            }
                        }
                    }


                    $hosts = $HostsTable->getHostsByContainerId($containerIds, 'list', 'uuid');
                    if (!empty($hosts)) {
                        $start = strtotime(
                            sprintf(
                                '%s %s',
                                $Entity->get('from_date'),
                                $Entity->get('from_time')
                            ));
                        $end = strtotime(
                            sprintf('%s %s',
                                $Entity->get('to_date'),
                                $Entity->get('to_time')
                            ));

                        foreach ($hosts as $hostUuid => $hostName) {
                            $payload = [
                                'hostUuid'     => $hostUuid,
                                'downtimetype' => $Entity->get('downtimetype_id'),
                                'start'        => $start - $offset,
                                'end'          => $end - $offset,
                                'comment'      => $Entity->get('comment'),
                                'author'       => $Entity->get('author'),
                            ];
                            $GearmanClient->sendBackground('createHostDowntime', $payload);
                        }
                    }
                }
            }

            $this->set('success', $success);
            $this->viewBuilder()->setOption('serialize', ['success']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $SystemdowntimesTable SystemdowntimesTable */
        $SystemdowntimesTable = TableRegistry::getTableLocator()->get('Systemdowntimes');

        if (!$SystemdowntimesTable->existsById($id)) {
            throw new NotFoundException(__('Invalide Systemdowntime'));
        }

        $systemdowntime = $SystemdowntimesTable->get($id);
        if ($SystemdowntimesTable->delete($systemdowntime)) {
            $this->set('success', true);
            $this->set('message', __('Systemdowntime successfully deleted'));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('message', __('Error while deleting systemdowntime'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }
}
