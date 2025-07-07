<?php
// Copyright (C) <2015>  <it-novum GmbH>
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

declare(strict_types=1);

namespace App\Controller;

use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Interfaces\HoststatusTableInterface;
use App\Model\Entity\Changelog;
use App\Model\Entity\Hostgroup;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicesTable;
use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use ImportModule\Model\Table\ImportedHostgroupsTable;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\HostConditions;
use itnovum\openITCOCKPIT\Core\HostgroupConditions;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\CumulatedValue;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\ContainerPermissions;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\ServiceStateSummary;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostFilter;
use itnovum\openITCOCKPIT\Filter\HostgroupFilter;
use itnovum\openITCOCKPIT\Filter\HosttemplateFilter;
use Statusengine2Module\Model\Table\ServicestatusTable;

/**
 * Class HostgroupsController
 * @package App\Controller
 */
class HostgroupsController extends AppController {


    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $HostgroupFilter = new HostgroupFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostgroupFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $hostgroups = $HostgroupsTable->getHostgroupsIndex($HostgroupFilter, $PaginateOMat, $MY_RIGHTS);
        $all_hostgroups = [];
        foreach ($hostgroups as $hostgroup) {
            $hostgroup['allowEdit'] = $this->hasPermission('edit', 'hostgroups');
            if ($this->hasRootPrivileges === false && $hostgroup['allowEdit'] === true) {
                $hostgroup['allowEdit'] = $this->allowedByContainerId($hostgroup->get('container')->get('parent_id'));
            }

            $hostgroup['hasSLAHosts'] = false;
            if (Plugin::isLoaded('SLAModule')) {
                $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($hostgroup->get('id'), $MY_RIGHTS);
                if (!empty($hostIds)) {
                    $hostgroup['hasSLAHosts'] = $HostsTable->hasSLAHosts($hostIds) > 0;
                }
            }

            // code for cmdb label
            $additionalInformationExists = false;

            if (Plugin::isLoaded('ImportModule')) {
                /** @var ImportedHostgroupsTable $ImportedHostgroupsTable */
                $ImportedHostgroupsTable = TableRegistry::getTableLocator()->get('ImportModule.ImportedHostgroups');
                $additionalInformationExists = $ImportedHostgroupsTable->existsImportedHostgroupByHostgroupId($hostgroup->get('id'));
            }

            $hostgroup['additionalInformationExists'] = $additionalInformationExists;
            $all_hostgroups[] = $hostgroup;
        }
        $this->set('all_hostgroups', $all_hostgroups);
        $this->viewBuilder()->setOption('serialize', ['all_hostgroups']);
    }

    /**
     * @param int|null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        if (!$HostgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Hostgroup'));
        }

        $hostgroup = $HostgroupsTable->get($id, [
            'contain' => [
                'Containers'
            ]
        ]);

        if (!$this->allowedByContainerId($hostgroup->get('container')->get('parent_id'))) {
            $this->render403();
            return;
        }

        $this->set('hostgroup', $hostgroup);
        $this->viewBuilder()->setOption('serialize', ['hostgroup']);
    }

    public function extended() {
        if (!$this->isApiRequest()) {
            $User = new User($this->getUser());
            $this->set('username', $User->getFullName());
        }
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }


        if ($this->request->is('post')) {
            $User = new User($this->getUser());

            /** @var $HostgroupsTable HostgroupsTable */
            $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
            $hostgroup = $HostgroupsTable->newEmptyEntity();
            $hostgroup = $HostgroupsTable->patchEntity($hostgroup, $this->request->getData('Hostgroup'));
            $hostgroup->set('uuid', UUID::v4());
            $hostgroup->get('container')->set('containertype_id', CT_HOSTGROUP);

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $requestData = $this->request->getData();

            $hostgroup = $HostgroupsTable->createHostgroup($hostgroup, $requestData, $User->getId());

            if ($hostgroup->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostgroup->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                Cache::clear('permissions');

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostgroup); // REST API ID serialization
                    return;
                }
            }
            $this->set('hostgroup', $hostgroup);
            $this->viewBuilder()->setOption('serialize', ['hostgroup']);
        }
    }


    /**
     * @param int|null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest() && $id === null) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        if (!$HostgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Hostgroup'));
        }

        $hostgroup = $HostgroupsTable->getHostgroupForEdit($id);
        $hostgroupForChangelog = $hostgroup;

        if (!$this->allowedByContainerId($hostgroup['Hostgroup']['container']['parent_id'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return host group information
            $this->set('hostgroup', $hostgroup);
            $this->viewBuilder()->setOption('serialize', ['hostgroup']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Update hostgroup data
            $User = new User($this->getUser());

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $hostgroupEntity = $HostgroupsTable->get($id, [
                'contain' => [
                    'Containers'
                ]
            ]);

            $hostgroupEntity->setAccess('uuid', false);
            $hostgroupEntity = $HostgroupsTable->patchEntity($hostgroupEntity, $this->request->getData('Hostgroup'));
            $hostgroupEntity->id = $id;

            $requestData = $this->request->getData();

            $hostgroupEntity = $HostgroupsTable->updateHostgroup(
                $hostgroupEntity,
                $requestData,
                $hostgroupForChangelog,
                $User->getId()
            );

            if ($hostgroupEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostgroupEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostgroupEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('hostgroup', $hostgroupEntity);
            $this->viewBuilder()->setOption('serialize', ['hostgroup']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$HostgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Hostgroup'));
        }

        $ContainersTable->acquireLock();

        $hostgroup = $HostgroupsTable->getHostgroupById($id);
        $container = $ContainersTable->get($hostgroup->get('container')->get('id'), [
            'contain' => [
                'Hostgroups'
            ]
        ]);

        if (!$this->allowedByContainerId($hostgroup->get('container')->get('parent_id'))) {
            $this->render403();
            return;
        }

        if ($ContainersTable->allowDelete($container->id, CT_HOSTGROUP)) {
            if ($ContainersTable->delete($container)) {
                $User = new User($this->getUser());
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'delete',
                    'hostgroups',
                    $id,
                    OBJECT_HOSTGROUP,
                    $container->get('parent_id'),
                    $User->getId(),
                    $container->get('name'),
                    [
                        'Hostgroup' => $hostgroup->toArray()
                    ]
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                $this->set('success', true);
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            }
            $this->response = $this->response->withStatus(500);
            $this->set('success', false);
            $this->viewBuilder()->setOption('serialize', ['success']);

        } else {
            $this->response = $this->response->withStatus(500);
            $this->set('success', false);
            $this->set('message', __('Container is not empty'));
            $this->set('containerId', $container->id);
            $this->viewBuilder()->setOption('serialize', ['success', 'message', 'containerId']);
        }
    }


    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function loadHostgroupWithHostsById($id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        /** @var $ServicestatusTable ServicestatusTable */
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();

        if (!$HostgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Hostgroup'));
        }

        $hostgroup = $HostgroupsTable->getHostgroupById($id);
        $hasSLAHosts = false;
        if (Plugin::isLoaded('SLAModule')) {
            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $MY_RIGHTS = $this->MY_RIGHTS;
            if ($this->hasRootPrivileges) {
                $MY_RIGHTS = [];
            }
            $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($hostgroup->get('id'), $MY_RIGHTS);
            if (!empty($hostIds)) {
                $hasSLAHosts = $HostsTable->hasSLAHosts($hostIds) > 0;
            }
        }

        $User = new User($this->getUser());
        $UserTime = UserTime::fromUser($User);

        $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($id);


        $HostFilter = new HostFilter($this->request);
        $HostConditions = new HostConditions();

        $HostConditions->setIncludeDisabled(false);
        $HostConditions->setHostIds($hostIds);
        $HostConditions->setContainerIds($this->MY_RIGHTS);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostFilter->getPage());

        $all_hosts = [];
        $hosts = [];

        $hostgroupHoststatusOverview = [
            0 => null,
            1 => null,
            2 => null
        ];

        if (!empty($hostIds)) {
            if ($this->DbBackend->isNdoUtils()) {
                /** @var $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $hosts = $HostsTable->getHostsIndex($HostFilter, $HostConditions);
            }

            if ($this->DbBackend->isStatusengine3()) {
                /** @var $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $hosts = $HostsTable->getHostsIndexStatusengine3($HostFilter, $HostConditions, $PaginateOMat);
                $hostgroupServicestatusAllHosts = $HostsTable->getHostStatusGlobalOverview($HostFilter, $HostConditions);
                foreach ($hostgroupServicestatusAllHosts as $hoststatusGroupByState) {
                    $state = (int)$hoststatusGroupByState['Hoststatus']['current_state'];
                    $hostgroupHoststatusOverview[$state] = (int)$hoststatusGroupByState['count'];
                }
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }
        }

        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields->currentState();


        foreach ($hosts as $host) {
            $Host = new Host($host);

            $serviceUuids = $ServicesTable->getServiceUuidsOfHostByHostId($Host->getId());
            $servicestatus = $ServicestatusTable->byUuid($serviceUuids, $ServicestatusFields);
            $ServicestatusObjects = Servicestatus::fromServicestatusByUuid($servicestatus);
            $serviceStateSummary = ServiceStateSummary::getServiceStateSummary($ServicestatusObjects, false);

            $CumulatedValue = new CumulatedValue($serviceStateSummary['state']);
            $serviceStateSummary['cumulatedState'] = $CumulatedValue->getKeyFromCumulatedValue();

            $serviceStateSummary['state'] = array_combine(
                [
                    'ok',
                    'warning',
                    'critical',
                    'unknown'
                ],
                $serviceStateSummary['state']
            );

            $Hoststatus = new Hoststatus($host['Host']['Hoststatus'], $UserTime);


            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $Host->getContainerIds());
                $allowEdit = $ContainerPermissions->hasPermission();
            }

            $tmpRecord = [
                'Host'                 => $Host->toArray(),
                'Hoststatus'           => $Hoststatus->toArray(),
                'ServicestatusSummary' => $serviceStateSummary
            ];
            $tmpRecord['Host']['allow_edit'] = $allowEdit;

            $all_hosts[] = $tmpRecord;
        }

        //Merge host status count to status names
        $hostgroupHoststatusOverview = array_combine([
            'up',
            'down',
            'unreachable'
        ], $hostgroupHoststatusOverview);

        $hostgroup = $hostgroup->toArray();

        $hostgroup['allowEdit'] = $this->hasPermission('edit', 'hostgroups');
        if ($this->hasRootPrivileges === false && $hostgroup['allowEdit'] === true) {
            $hostgroup['allowEdit'] = $this->allowedByContainerId($hostgroup['container']['parent_id']);
        }

        $data = [
            'Hostgroup'     => $hostgroup,
            'Hosts'         => $all_hosts,
            'StatusSummary' => $hostgroupHoststatusOverview,
            'hasSLAHosts'   => $hasSLAHosts
        ];


        $this->set('hostgroup', $data);
        $this->set('username', $User->getFullName());
        $this->viewBuilder()->setOption('serialize', ['hostgroup', 'username']);
    }


    /**
     * @throws MissingDbBackendException
     */
    public function listToPdf() {

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var $HoststatusTable HoststatusTableInterface */
        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $MY_RIGHTS = [];
        if (!$this->hasRootPrivileges) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $HostgroupFilter = new HostgroupFilter($this->request);

        $hostgroups = $HostgroupsTable->getHostgroupsIndex($HostgroupFilter, null, $MY_RIGHTS);

        $User = new User($this->getUser());

        $numberOfHostgroups = sizeof($hostgroups);
        $numberOfHosts = 0;

        $all_hostgroups = [];
        foreach ($hostgroups as $hostgroup) {
            /** @var Hostgroup $hostgroup */

            $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($hostgroup->get('id'));

            $HostFilter = new HostFilter($this->request);
            $HostConditions = new HostConditions();

            $HostConditions->setIncludeDisabled(false);
            $HostConditions->setHostIds($hostIds);
            $HostConditions->setContainerIds($this->MY_RIGHTS);

            $hosts = [];
            if (!empty($hostIds)) {
                if ($this->DbBackend->isNdoUtils()) {
                    $hosts = $HostsTable->getHostsIndex($HostFilter, $HostConditions);
                }

                if ($this->DbBackend->isStatusengine3()) {
                    $hosts = $HostsTable->getHostsIndexStatusengine3($HostFilter, $HostConditions);
                }

                if ($this->DbBackend->isCrateDb()) {
                    throw new MissingDbBackendException('MissingDbBackendException');
                }
            }

            $numberOfHosts += sizeof($hosts);

            $hostgroupHostUuids = Hash::extract($hosts, '{n}.Host.uuid');
            $HoststatusFields = new HoststatusFields($this->DbBackend);
            $HoststatusFields->wildcard();
            $hoststatusOfHostgroup = $HoststatusTable->byUuids($hostgroupHostUuids, $HoststatusFields);

            $all_hostgroups[] = [
                'Hostgroup'  => $hostgroup->toArray(),
                'Hosts'      => $hosts,
                'Hoststatus' => $hoststatusOfHostgroup
            ];
        }

        $this->set('hostgroups', $all_hostgroups);
        $this->set('numberOfHostgroups', $numberOfHostgroups);
        $this->set('numberOfHosts', $numberOfHosts);
        $this->set('User', $User);

        $this->viewBuilder()->setOption(
            'pdfConfig',
            [
                'download' => true,
                'filename' => __('Hostgroups_') . date('dmY_his') . '.pdf',
            ]
        );
    }

    public function listToCsv() {
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var $HoststatusTable HoststatusTableInterface */
        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $MY_RIGHTS = [];
        if (!$this->hasRootPrivileges) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $HostgroupFilter = new HostgroupFilter($this->request);

        $hostgroups = $HostgroupsTable->getHostgroupsIndex($HostgroupFilter, null, $MY_RIGHTS);
        $User = new User($this->getUser());
        $UserTime = new UserTime($User->getTimezone(), $User->getDateformat());


        $all_hostgroups = [];
        foreach ($hostgroups as $hostgroup) {
            /** @var Hostgroup $hostgroup */

            $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($hostgroup->get('id'));

            $HostFilter = new HostFilter($this->request);
            $HostConditions = new HostConditions();

            $HostConditions->setIncludeDisabled(false);
            $HostConditions->setHostIds($hostIds);
            $HostConditions->setContainerIds($this->MY_RIGHTS);

            $hosts = [];
            if (!empty($hostIds)) {
                if ($this->DbBackend->isNdoUtils()) {
                    $hosts = $HostsTable->getHostsIndex($HostFilter, $HostConditions);
                }

                if ($this->DbBackend->isStatusengine3()) {
                    $hosts = $HostsTable->getHostsIndexStatusengine3($HostFilter, $HostConditions);
                }

                if ($this->DbBackend->isCrateDb()) {
                    throw new MissingDbBackendException('MissingDbBackendException');
                }
            }

            $hostgroupHostUuids = Hash::extract($hosts, '{n}.Host.uuid');
            $HoststatusFields = new HoststatusFields($this->DbBackend);
            $HoststatusFields->wildcard();
            $hoststatusOfHostgroup = $HoststatusTable->byUuids($hostgroupHostUuids, $HoststatusFields);

            foreach ($hosts as $host) {
                $statusdata = [];
                if (isset($hoststatusOfHostgroup[$host['Host']['uuid']]['Hoststatus'])) {
                    $statusdata = $hoststatusOfHostgroup[$host['Host']['uuid']]['Hoststatus'];
                }
                $Hoststatus = new Hoststatus($statusdata);


                $all_hostgroups[] = [
                    $hostgroup->container->name,
                    $hostgroup->id,
                    $hostgroup->uuid,
                    $hostgroup->container->parent_id,

                    $host['Host']['name'],
                    $host['Host']['id'],
                    $host['Host']['uuid'],
                    $host['Host']['address'],
                    $host['Host']['description'],
                    $host['Hosttemplate']['id'],
                    $host['Hosttemplate']['name'],
                    $host['Host']['satellite_id'],
                    $host['Host']['container_id'],

                    $Hoststatus->currentState(),
                    $Hoststatus->isAcknowledged() ? 1 : 0,
                    $Hoststatus->isInDowntime() ? 1 : 0,
                    $Hoststatus->getLastCheck(),
                    $UserTime->format($Hoststatus->getLastCheck()),
                    $Hoststatus->getNextCheck(),
                    $UserTime->format($Hoststatus->getNextCheck()),
                    $Hoststatus->isActiveChecksEnabled() ? 1 : 0,
                    $Hoststatus->getOutput()
                ];
            }
        }

        $header = [
            'hostgroup_name',
            'hostgroup_id',
            'hostgroup_uuid',
            'hostgroup_container_parent_id',

            'host_name',
            'host_id',
            'host_uuid',
            'host_address',
            'host_description',
            'hosttemplate_id',
            'hosttemplate_name',
            'satellite_id',
            'container_id',

            'current_state',
            'problem_has_been_acknowledged',
            'in_downtime',
            'last_check',
            'last_check_formatted',
            'next_check',
            'next_check_formatted',
            'active_checks_enabled',
            'output'
        ];

        $this->set('data', $all_hostgroups);

        $filename = __('Hostgroups_') . date('dmY_his') . '.csv';
        $this->setResponse($this->getResponse()->withDownload($filename));
        $this->viewBuilder()
            ->setClassName('CsvView.Csv')
            ->setOptions([
                'delimiter' => ';', // Excel prefers ; over ,
                'bom'       => true, // Fix UTF-8 umlauts in Excel
                'serialize' => 'data',
                'header'    => $header,
            ]);
    }

    public function addHostsToHostgroup() {
        //Only ship template
        return;
    }

    public function append() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        if ($this->request->is('post')) {
            $id = $this->request->getData('Hostgroup.id');
            $hostIds = $this->request->getData('Hostgroup.hosts._ids');
            if (!is_array($hostIds)) {
                $hostIds = [$hostIds];
            }

            if (empty($hostIds)) {
                //No hosts to add
                return;
            }

            /** @var $HostgroupsTable HostgroupsTable */
            $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

            if (!$HostgroupsTable->existsById($id)) {
                throw new NotFoundException(__('Invalid Hostgroup'));
            }

            $ContainersTable->acquireLock();

            $hostgroup = $HostgroupsTable->getHostgroupForEdit($id);
            $hostgroupForChangelog = $hostgroup;
            if (!$this->allowedByContainerId($hostgroup['Hostgroup']['container']['parent_id'])) {
                $this->render403();
                return;
            }

            //Merge new hosts with existing hosts from host group
            $hostIds = array_unique(array_merge(
                $hostgroup['Hostgroup']['hosts']['_ids'],
                $hostIds
            ));

            $containerId = $hostgroup['Hostgroup']['container']['parent_id'];

            if ($containerId == ROOT_CONTAINER) {
                //Don't panic! Only root users can edit /root objects ;)
                //So no loss of selected hosts/host templates
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true, [
                    CT_GLOBAL,
                    CT_TENANT,
                    CT_LOCATION,
                    CT_NODE
                ]);
            } else {
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, false, [
                    CT_GLOBAL,
                    CT_TENANT,
                    CT_LOCATION,
                    CT_NODE
                ]);
            }

            $hostIdsToSave = [];
            foreach ($hostIds as $hostId) {
                $host = $HostsTable->getHostSharing($hostId);
                foreach ($host['Host']['hosts_to_containers_sharing']['_ids'] as $hostContainerId) {
                    if (in_array($hostContainerId, $containerIds, true)) {
                        $hostIdsToSave[] = $hostId;
                        continue 2;
                    }
                }
            }


            $User = new User($this->getUser());
            $hostgroupEntity = $HostgroupsTable->get($id);

            $hostgroupEntity->setAccess('uuid', false);
            $hostgroupEntity = $HostgroupsTable->patchEntity($hostgroupEntity, [
                'hosts' => [
                    '_ids' => $hostIdsToSave
                ]
            ]);
            $hostgroupEntity->id = $id;
            $HostgroupsTable->save($hostgroupEntity);
            if ($hostgroupEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostgroupEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                $fakeRequest = [
                    'Hostgroup' => [
                        'hosts' => [
                            '_ids' => $hostIdsToSave
                        ]
                    ]
                ];

                //No errors
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'hostgroups',
                    $hostgroupEntity->id,
                    OBJECT_HOSTGROUP,
                    $hostgroup['Hostgroup']['container']['parent_id'],
                    $User->getId(),
                    $hostgroup['Hostgroup']['container']['name'],
                    array_merge($HostgroupsTable->resolveDataForChangelog($fakeRequest), $fakeRequest),
                    array_merge($HostgroupsTable->resolveDataForChangelog($hostgroupForChangelog), $hostgroupForChangelog)
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostgroupEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('hostgroup', $hostgroupEntity);
            $this->viewBuilder()->setOption('serialize', ['hostgroup']);
        }
    }

    /**
     * @param int|null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $hostgroups = $HostgroupsTable->getHostgroupsForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('hostgroups', $hostgroups);
            $this->viewBuilder()->setOption('serialize', ['hostgroups']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();


            $postData = $this->request->getData('data');
            $User = new User($this->getUser());
            $userId = $User->getId();

            foreach ($postData as $index => $hostgroupData) {
                if (!isset($hostgroupData['Hostgroup']['id'])) {
                    //Create/clone hostgroup
                    $sourceHostgroupId = $hostgroupData['Source']['id'];
                    $sourceHostgroup = $HostgroupsTable->getSourceHostgroupForCopy($sourceHostgroupId, $MY_RIGHTS);

                    $newHostgroupData = [
                        'description'   => $hostgroupData['Hostgroup']['description'],
                        'hostgroup_url' => $sourceHostgroup['hostgroup_url'],
                        'uuid'          => UUID::v4(),
                        'container'     => [
                            'name'             => $hostgroupData['Hostgroup']['container']['name'],
                            'containertype_id' => CT_HOSTGROUP,
                            'parent_id'        => $sourceHostgroup['container']['parent_id']
                        ],
                        'hosts'         => [
                            '_ids' => $sourceHostgroup['hosts']['_ids']
                        ],
                        'hosttemplates' => [
                            '_ids' => $sourceHostgroup['hosttemplates']['_ids']
                        ],
                    ];

                    $newHostgroupEntity = $HostgroupsTable->newEntity($newHostgroupData);

                }

                $action = 'copy';
                if (isset($hostgroupData['Hostgroup']['id'])) {
                    //Update existing hostgroup
                    //This happens, if a user copy multiple hostgroups, and one run into an validation error
                    //All hostgroups without validation errors got already saved to the database
                    $newHostgroupEntity = $HostgroupsTable->get($hostgroupData['Hostgroup']['id'], [
                        'contain' => [
                            'Containers'
                        ]
                    ]);
                    $newHostgroupEntity->setAccess('*', false);
                    $newHostgroupEntity->container->setAccess('*', false);
                    $newHostgroupEntity->container->setAccess('name', true);
                    $newHostgroupEntity = $HostgroupsTable->patchEntity($newHostgroupEntity, $hostgroupData['Hostgroup']);
                    $newHostgroupData = $newHostgroupEntity->toArray();
                    $action = 'edit';
                }
                $HostgroupsTable->save($newHostgroupEntity);

                $postData[$index]['Error'] = [];
                if ($newHostgroupEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newHostgroupEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Hostgroup']['id'] = $newHostgroupEntity->get('id');

                    /** @var  ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        'hostgroups',
                        $postData[$index]['Hostgroup']['id'],
                        OBJECT_HOSTGROUP,
                        $newHostgroupEntity->get('container')->get('parent_id'),
                        $userId,
                        $newHostgroupEntity->get('container')->get('name'),
                        ['Hostgroup' => $newHostgroupData]
                    );
                    if ($changelog_data) {
                        /** @var Changelog $changelogEntry */
                        $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                        $ChangelogsTable->save($changelogEntry);
                    }
                }
            }
        }

        if ($hasErrors) {
            $this->response = $this->response->withStatus(400);
        }
        Cache::clear('permissions');
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    /**
     * @throws Exception
     */
    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOSTGROUP, [], $this->hasRootPrivileges);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOSTGROUP, [], $this->hasRootPrivileges);
        }
        $containers = Api::makeItJavaScriptAble($containers);


        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    public function loadHosts() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            // ITC-2124
            $containerId = $this->request->getData('containerId');
            $selected = $this->request->getData('selected');
        } else {
            // Keep the API stable for GET
            $selected = $this->request->getQuery('selected');
            $containerId = $this->request->getQuery('containerId');
        }

        $HostFilter = new HostFilter($this->request);

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if ($containerId == ROOT_CONTAINER) {
            //Don't panic! Only root users can edit /root objects ;)
            //So no loss of selected hosts/host templates
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true, [
                CT_GLOBAL,
                CT_TENANT,
                CT_LOCATION,
                CT_NODE
            ]);
        } else {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, false, [
                CT_GLOBAL,
                CT_TENANT,
                CT_LOCATION,
                CT_NODE
            ]);
        }

        $HostCondition = new HostConditions($HostFilter->ajaxFilter());
        $HostCondition->setContainerIds($containerIds);
        $hosts = Api::makeItJavaScriptAble(
            $HostsTable->getHostsForAngular($HostCondition, $selected)
        );
        $this->set('hosts', $hosts);
        $this->viewBuilder()->setOption('serialize', ['hosts']);
    }

    /**
     * @return void
     */
    public function loadHosttemplates() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $containerId = $this->request->getQuery('containerId');
        $selected = $this->request->getQuery('selected');
        $HosttemplateFilter = new HosttemplateFilter($this->request);

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        $containerIds = [ROOT_CONTAINER, $containerId];
        if ($containerId == ROOT_CONTAINER) {
            //Don't panic! Only root users can edit /root objects ;)
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
        }
        $hosttemplates = Api::makeItJavaScriptAble(
            $HosttemplatesTable->getHosttemplatesForAngular($containerIds, $HosttemplateFilter, $selected)
        );

        $this->set('hosttemplates', $hosttemplates);
        $this->viewBuilder()->setOption('serialize', ['hosttemplates']);
    }

    public function loadHostgroupsByString($onlyHostgroupssWithWritePermission = false) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $selected = $this->request->getQuery('selected');

        $HostgroupFilter = new HostgroupFilter($this->request);

        $HostgroupCondition = new HostgroupConditions($HostgroupFilter->indexFilter());

        if ($onlyHostgroupssWithWritePermission) {
            $HostgroupCondition->setContainerIds($this->getWriteContainers());
        } else {
            $HostgroupCondition->setContainerIds($this->MY_RIGHTS);
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $hostgroups = Api::makeItJavaScriptAble(
            $HostgroupsTable->getHostgroupsForAngular($HostgroupCondition, $selected, true)
        );

        $this->set('hostgroups', $hostgroups);
        $this->viewBuilder()->setOption('serialize', ['hostgroups']);
    }

    public function loadHostgroupsByStringAndContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $selected = $this->request->getQuery('selected');
        $containerId = (int)$this->request->getQuery('containerId');

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, true, [CT_HOSTGROUP]);

        $tenantContainerIds = [];

        foreach ($containerIds as $container_id) {
            if ($container_id != ROOT_CONTAINER) {
                // Get contaier id of the tenant container
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load contactgroups and contacts
                $path = $ContainersTable->getPathByIdAndCacheResult($container_id, 'HostgroupHostgroupsByContainerIdAjax');

                // Tenant host groups are available for all users of a tenant (oITC V2 legacy)
                $tenantContainerIds[] = $path[1]['id'];
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);
        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));


        $HostgroupFilter = new HostgroupFilter($this->request);

        $HostgroupCondition = new HostgroupConditions($HostgroupFilter->indexFilter());
        $HostgroupCondition->setContainerIds($containerIds);

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $hostgroups = Api::makeItJavaScriptAble(
            $HostgroupsTable->getHostgroupsForAngular($HostgroupCondition, $selected, true)
        );

        $this->set('hostgroups', $hostgroups);
        $this->viewBuilder()->setOption('serialize', ['hostgroups']);
    }

    public function loadHostgroupsByContainerId() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $containerId = $this->request->getQuery('containerId');
        $HostgroupFilter = new HostgroupFilter($this->request);
        $resolveContainerIds = $this->request->getQuery('resolveContainerIds', false);


        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        if ($containerId == ROOT_CONTAINER) {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true, [CT_HOSTGROUP]);
        } else {
            if ($resolveContainerIds) {
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, true, [CT_HOSTGROUP]);
                $containerIds = array_merge($containerIds, [ROOT_CONTAINER, $containerId]);
            } else {
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, false, [CT_HOSTGROUP]);
            }
        }
        $HostgroupCondition = new HostgroupConditions($HostgroupFilter->indexFilter());
        $HostgroupCondition->setContainerIds($containerIds);

        $hostgroups = $HostgroupsTable->getHostgroupsByContainerIdNew($HostgroupCondition);
        $hostgroups = Api::makeItJavaScriptAble($hostgroups);

        $this->set('hostgroups', $hostgroups);
        $this->viewBuilder()->setOption('serialize', ['hostgroups']);
    }

    public function loadAdditionalInformation() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $id = $this->request->getQuery('id');

        $additionalInformationExists = false;

        if (Plugin::isLoaded('ImportModule')) {
            /** @var ImportedHostgroupsTable $ImportedHostgroupsTable */
            $ImportedHostgroupsTable = TableRegistry::getTableLocator()->get('ImportModule.ImportedHostgroups');
            $additionalInformationExists = $ImportedHostgroupsTable->existsImportedHostgroupByHostgroupId($id);
        }

        $this->set('AdditionalInformationExists', $additionalInformationExists);
        $this->viewBuilder()->setOption('serialize', ['AdditionalInformationExists']);
    }
}
