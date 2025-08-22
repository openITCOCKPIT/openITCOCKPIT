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

use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\CommandargumentsTable;
use App\Model\Table\CommandsTable;
use App\Model\Table\ContactgroupsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatecommandargumentvaluesTable;
use App\Model\Table\ServicetemplateeventcommandargumentvaluesTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\ContainerPermissions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServicetemplateFilter;

/**
 * Class ServicetemplatesController
 * @package App\Controller
 */
class ServicetemplatesController extends AppController {

    use PluginManagerTableTrait;

    public function index() {
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template

            $this->set('types', $ServicetemplatesTable->getServicetemplateTypes());
            return;
        }

        $ServicetemplateFilter = new ServicetemplateFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServicetemplateFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $servicetemplates = $ServicetemplatesTable->getServicetemplatesIndex($ServicetemplateFilter, $PaginateOMat, $MY_RIGHTS);
        $typesForView = $ServicetemplatesTable->getServicetemplateTypesWithStyles();


        foreach ($servicetemplates as $index => $servicetemplate) {
            $servicetemplates[$index]['Servicetemplate']['allow_edit'] = true;
            $servicetemplates[$index]['Servicetemplate']['type'] = $typesForView[$servicetemplate['Servicetemplate']['servicetemplatetype_id']];

            if ($this->hasRootPrivileges === false) {
                $servicetemplates[$index]['Servicetemplate']['allow_edit'] = $this->isWritableContainer($servicetemplate['Servicetemplate']['container_id']);
            }
        }


        $this->set('all_servicetemplates', $servicetemplates);
        $toJson = ['all_servicetemplates', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_servicetemplates', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if (!$ServicetemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service template'));
        }

        $servicetemplate = $ServicetemplatesTable->getServicetemplateById($id, [
            'Containers',
            'Servicetemplatecommandargumentvalues',
            'Servicetemplateeventcommandargumentvalues',
            'Customvariables'
        ]);


        if (!$this->allowedByContainerId($servicetemplate['Container']['id'])) {
            throw new ForbiddenException('403 Forbidden');
        }

        $this->set('servicetemplate', $servicetemplate);
        $this->viewBuilder()->setOption('serialize', ['servicetemplate']);
    }


    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('get')) {
            /** @var ServicetemplatesTable $ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

            $this->set('types', Api::makeItJavaScriptAble(
                $ServicetemplatesTable->getServicetemplateTypesWithStyles()
            ));
            $this->viewBuilder()->setOption('serialize', ['types']);
        }

        if ($this->request->is('post')) {
            /** @var ServicetemplatesTable $ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

            $request = $this->request->getData('Servicetemplate', []);
            if (!isset($request['servicetemplatetype_id'])) {
                $request['servicetemplatetype_id'] = GENERIC_SERVICE;
            }

            $servicetemplate = $ServicetemplatesTable->newEmptyEntity();
            $servicetemplate = $ServicetemplatesTable->patchEntity($servicetemplate, $request);
            $servicetemplate->set('uuid', UUID::v4());

            $User = new User($this->getUser());
            $requestData = $this->request->getData();

            $servicetemplate = $ServicetemplatesTable->createServicetemplate($servicetemplate, $requestData, $User->getId());

            if ($servicetemplate->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $servicetemplate->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($servicetemplate); // REST API ID serialization
                    return;
                }
            }
            $this->set('types', Api::makeItJavaScriptAble(
                $ServicetemplatesTable->getServicetemplateTypes()
            ));
            $this->set('servicetemplate', $servicetemplate);
            $this->viewBuilder()->setOption('serialize', ['servicetemplate', 'types']);
        }
    }

    /**
     * @param int|null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');

        if (!$ServicetemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Service template not found'));
        }

        $servicetemplate = $ServicetemplatesTable->getServicetemplateForEdit($id);
        $servicetemplateForChangeLog = $servicetemplate;

        if (!$this->allowedByContainerId($servicetemplate['Servicetemplate']['container_id'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return service template information
            $commands = $CommandsTable->getCommandByTypeAsList(CHECK_COMMAND);

            //Use foreach because of arra_merge remove the keys and adding None after getCommandByTypeAsList()
            //will display "None" as the last element in the select box
            $eventhandlerCommands = [
                0 => __('None')
            ];
            foreach ($CommandsTable->getCommandByTypeAsList(EVENTHANDLER_COMMAND) as $eventhandlerCommndId => $eventhandlerCommandName) {
                $eventhandlerCommands[$eventhandlerCommndId] = $eventhandlerCommandName;
            }


            $this->set('commands', Api::makeItJavaScriptAble($commands));
            $this->set('eventhandlerCommands', Api::makeItJavaScriptAble($eventhandlerCommands));
            $this->set('servicetemplate', $servicetemplate);
            $this->set('types', Api::makeItJavaScriptAble(
                $ServicetemplatesTable->getServicetemplateTypesWithStyles()
            ));
            $this->viewBuilder()->setOption('serialize', ['servicetemplate', 'commands', 'eventhandlerCommands', 'types']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Update service template data
            $User = new User($this->getUser());

            $servicetemplateEntity = $ServicetemplatesTable->get($id);
            $servicetemplateEntity->setAccess('uuid', false);
            $servicetemplateEntity = $ServicetemplatesTable->patchEntity($servicetemplateEntity, $this->request->getData('Servicetemplate'));
            $servicetemplateEntity->id = $id;

            $requestData = $this->request->getData();

            $servicetemplateEntity = $ServicetemplatesTable->updateServicetemplate(
                $servicetemplateEntity,
                $requestData,
                $servicetemplateForChangeLog,
                $User->getId()
            );

            if ($servicetemplateEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $servicetemplateEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($servicetemplateEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('servicetemplate', $servicetemplateEntity);
            $this->viewBuilder()->setOption('serialize', ['servicetemplate']);
        }
    }


    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if (!$ServicetemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Service template not found'));
        }

        $servicetemplate = $ServicetemplatesTable->get($id);

        if (!$this->allowedByContainerId($servicetemplate->get('container_id'))) {
            $this->render403();
            return;
        }

        if (!$ServicetemplatesTable->allowDelete($id)) {
            $usedBy = [
                [
                    'baseUrl' => '#',
                    'state'   => 'ServicetemplatesUsedBy',
                    'message' => __('Used by other objects'),
                    'module'  => 'Core'
                ]
            ];

            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Issue while deleting service template'));
            $this->set('usedBy', $usedBy);
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
            return;
        }

        $User = new User($this->getUser());

        if ($ServicetemplatesTable->__delete($servicetemplate, $User)) {
            $this->set('success', true);
            $this->set('message', __('Service template successfully deleted'));
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->set('success', false);
        $this->set('message', __('Error while deleting service template'));
        $this->viewBuilder()->setOption('serialize', ['success']);
    }


    /**
     * @param int|null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $servicetemplates = $ServicetemplatesTable->getServicetemplatesForCopy(func_get_args(), $MY_RIGHTS);
            /** @var $CommandsTable CommandsTable */
            $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
            $commands = $CommandsTable->getCommandByTypeAsList(CHECK_COMMAND);
            $eventhandlerCommands = $CommandsTable->getCommandByTypeAsList(EVENTHANDLER_COMMAND);
            $this->set('servicetemplates', $servicetemplates);
            $this->set('commands', Api::makeItJavaScriptAble($commands));
            $this->set('eventhandlerCommands', Api::makeItJavaScriptAble($eventhandlerCommands));
            $this->viewBuilder()->setOption('serialize', ['servicetemplates', 'commands', 'eventhandlerCommands']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $Cache = new KeyValueStore();

            $postData = $this->request->getData('data');
            $User = new User($this->getUser());

            foreach ($postData as $index => $servicetemplateData) {
                if (!isset($servicetemplateData['Servicetemplate']['id'])) {
                    //Create/clone servicetemplate
                    $sourceServicetemplateId = $servicetemplateData['Source']['id'];
                    if (!$Cache->has($sourceServicetemplateId)) {
                        $sourceServicetemplate = $ServicetemplatesTable->getServicetemplateForEdit($sourceServicetemplateId);
                        $sourceServicetemplate = $sourceServicetemplate['Servicetemplate'];
                        unset($sourceServicetemplate['id'], $sourceServicetemplate['uuid']);

                        foreach ($sourceServicetemplate['servicetemplatecommandargumentvalues'] as $i => $servicetemplatecommandargumentvalues) {
                            unset($sourceServicetemplate['servicetemplatecommandargumentvalues'][$i]['id']);
                            unset($sourceServicetemplate['servicetemplatecommandargumentvalues'][$i]['servicetemplate_id']);
                        }

                        foreach ($sourceServicetemplate['servicetemplateeventcommandargumentvalues'] as $i => $servicetemplateeventcommandargumentvalues) {
                            unset($sourceServicetemplate['servicetemplateeventcommandargumentvalues'][$i]['id']);
                            unset($sourceServicetemplate['servicetemplateeventcommandargumentvalues'][$i]['servicetemplate_id']);
                        }

                        foreach ($sourceServicetemplate['customvariables'] as $i => $customvariable) {
                            unset($sourceServicetemplate['customvariables'][$i]['id']);
                            unset($sourceServicetemplate['customvariables'][$i]['object_id']);
                        }

                        $Cache->set($sourceServicetemplateId, $sourceServicetemplate);
                    }

                    $sourceServicetemplate = $Cache->get($sourceServicetemplateId);

                    $newServicetemplateData = $sourceServicetemplate;
                    $newServicetemplateData['uuid'] = UUID::v4();
                    $newServicetemplateData['template_name'] = $servicetemplateData['Servicetemplate']['template_name'];
                    $newServicetemplateData['name'] = $servicetemplateData['Servicetemplate']['name'];
                    $newServicetemplateData['description'] = $servicetemplateData['Servicetemplate']['description'];
                    $newServicetemplateData['command_id'] = $servicetemplateData['Servicetemplate']['command_id'];
                    if (!empty($servicetemplateData['Servicetemplate']['servicetemplatecommandargumentvalues'])) {
                        $newServicetemplateData['servicetemplatecommandargumentvalues'] = $servicetemplateData['Servicetemplate']['servicetemplatecommandargumentvalues'];
                    }

                    $newServicetemplateEntity = $ServicetemplatesTable->newEntity($newServicetemplateData);
                }

                $action = 'copy';
                if (isset($servicetemplateData['Servicetemplate']['id'])) {
                    //Update existing servicetemplates
                    //This happens, if a user copy multiple servicetemplates, and one run into an validation error
                    //All servicetemplates without validation errors got already saved to the database
                    $newServicetemplateEntity = $ServicetemplatesTable->get($servicetemplateData['Servicetemplate']['id']);
                    $newServicetemplateEntity->setAccess('*', false);
                    $newServicetemplateEntity->setAccess(['template_name', 'name', 'description', 'command_id', 'servicetemplatecommandargumentvalues'], true);
                    $newServicetemplateEntity = $ServicetemplatesTable->patchEntity($newServicetemplateEntity, $servicetemplateData['Servicetemplate']);
                    $newServicetemplateData = $newServicetemplateEntity->toArray();
                    $action = 'edit';
                }
                $ServicetemplatesTable->save($newServicetemplateEntity);

                $postData[$index]['Error'] = [];
                if ($newServicetemplateEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newServicetemplateEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Servicetemplate']['id'] = $newServicetemplateEntity->get('id');

                    /** @var  ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        'servicetemplates',
                        $postData[$index]['Servicetemplate']['id'],
                        OBJECT_SERVICETEMPLATE,
                        [ROOT_CONTAINER],
                        $User->getId(),
                        $newServicetemplateEntity->get('template_name'),
                        ['Servicetemplate' => $newServicetemplateData]
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
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    public function addServicetemplatesToServicetemplategroup() {
        //Only ship HTML Template
        return;
    }


    /**
     * @param int|null $id
     */
    public function usedBy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if (!$ServicetemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service template'));
        }

        $servicetemplate = $ServicetemplatesTable->get($id);

        $ServicetemplateFilter = new ServicetemplateFilter($this->request);
        $filter = $ServicetemplateFilter->usedByFilter();

        $includeDisabled = true;
        if (isset($filter['Services.disabled']) && $filter['Services.disabled'] === 0) {
            $includeDisabled = false;
        }

        $MY_RIGHTS = [];
        if (!$this->hasRootPrivileges) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $services = $ServicesTable->getServicesWithHostForServicetemplateUsedBy($id, $MY_RIGHTS, $includeDisabled);

        if (empty($services)) {
            //No services found or no permissions
            $this->set('servicetemplate', $servicetemplate);
            $this->set('hostsWithServices', []);
            $this->set('count', 0);
            $this->viewBuilder()->setOption('serialize', ['hostsWithServices', 'servicetemplate', 'count']);
            return;
        }
        $hostIds = array_unique(Hash::extract($services, '{n}.host_id'));
        $tmpHosts = $HostsTable->getHostsByIds($hostIds, false);

        foreach ($tmpHosts as $index => $host) {
            $hostContainerIds = Hash::extract($host['hosts_to_containers_sharing'], '{n}.id');
            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $hostContainerIds);
                $allowEdit = $ContainerPermissions->hasPermission();
            }
            $tmpHosts[$index]['allow_edit'] = $allowEdit;
        }

        $hosts = [];
        foreach ($tmpHosts as $host) {
            $hosts[$host['id']] = $host;
        }
        //Merge hosts into service array
        foreach ($services as $index => $service) {
            $services[$index]['servicename'] = $service['name'];
            if ($service['name'] === '' || $service['name'] === null) {
                $services[$index]['servicename'] = $service['servicetemplate']['name'];
            }
            $services[$index]['host'] = $hosts[$service['host_id']];
            $services[$index]['hostname'] = $hosts[$service['host_id']]['name'];
        }

        $groupByHost = [];
        foreach ($services as $service) {
            $hostId = $service['host_id'];
            if (!isset($groupByHost[$hostId])) {
                $groupByHost[$hostId] = $service['host'];
                $groupByHost[$hostId]['services'] = [];
            }

            unset($service['host']);
            $groupByHost[$hostId]['services'][] = $service;
        }

        // Remove $hostId from the key to get an array instead of object
        $groupByHost = array_values($groupByHost);

        $this->set('servicetemplate', $servicetemplate);
        $this->set('hostsWithServices', $groupByHost);
        $this->set('count', sizeof($services));
        $this->viewBuilder()->setOption('serialize', ['hostsWithServices', 'servicetemplate', 'count']);
        return;
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    /**
     * @param int|null $servicetemplateId
     * @throws Exception
     */
    public function loadContainers($servicetemplateId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_SERVICETEMPLATE, [], $this->hasRootPrivileges, [CT_SERVICETEMPLATEGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_SERVICETEMPLATE, [], $this->hasRootPrivileges, [CT_SERVICETEMPLATEGROUP]);
        }

        $areContainersRestricted = false;
        if (is_numeric($servicetemplateId)) {
            //Edit mode

            /** @var $ServicetemplatesTable ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            $servicetemplatesContainerId = $ServicetemplatesTable->getContainerIdById($servicetemplateId);
            $usedContainerIds = $ServicesTable->getHostPrimaryContainerIdsByServicetemplateId($servicetemplateId);

            if (!empty($usedContainerIds)) {
                //This service template is used by some some.
                //Container options needs to be needs to be restricted if the services/hosts are using some sub containers...
                $restrictedContainers = [];
                foreach ($containers as $containerId => $path) {
                    $containerId = (int)$containerId;
                    if (in_array($containerId, [ROOT_CONTAINER, $servicetemplatesContainerId], true)) {
                        $restrictedContainers[$containerId] = $path;
                    } else {
                        $areContainersRestricted = true;
                    }
                }
                $containers = $restrictedContainers;
            }
        }


        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->set('areContainersRestricted', $areContainersRestricted);
        $this->viewBuilder()->setOption('serialize', ['containers', 'areContainersRestricted']);
    }

    public function loadCommands() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        $commands = $CommandsTable->getCommandByTypeAsList(CHECK_COMMAND);

        $eventhandlerCommands = [
            0 => __('None')
        ];

        //Use foreach because of arra_merge remove the keys and adding None after getCommandByTypeAsList()
        //will display "None" as the last element in the select box
        foreach ($CommandsTable->getCommandByTypeAsList(EVENTHANDLER_COMMAND) as $eventhandlerCommndId => $eventhandlerCommandName) {
            $eventhandlerCommands[$eventhandlerCommndId] = $eventhandlerCommandName;
        }

        $this->set('commands', Api::makeItJavaScriptAble($commands));
        $this->set('eventhandlerCommands', Api::makeItJavaScriptAble($eventhandlerCommands));
        $this->viewBuilder()->setOption('serialize', ['commands', 'eventhandlerCommands']);
    }

    /**
     * @param int|null $commandId
     * @param int|null $servicetemplateId
     */
    public function loadCommandArguments($commandId = null, $servicetemplateId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $CommandargumentsTable CommandargumentsTable */
        $CommandargumentsTable = TableRegistry::getTableLocator()->get('Commandarguments');

        //ServicetemplatecommandargumentvaluesTable

        if (!$CommandsTable->existsById($commandId)) {
            throw new NotFoundException(__('Invalid command'));
        }
        $servicetemplatecommandargumentvalues = [];

        if ($servicetemplateId != null) {
            //User passed an servicetemplateId, so we are in a non add mode!
            //Check if the servicetemplate has defined command arguments

            /** @var $ServicetemplatecommandargumentvaluesTable ServicetemplatecommandargumentvaluesTable */
            $ServicetemplatecommandargumentvaluesTable = TableRegistry::getTableLocator()->get('Servicetemplatecommandargumentvalues');

            $servicetemplateCommandArgumentValues = $ServicetemplatecommandargumentvaluesTable->getByServicetemplateIdAndCommandId($servicetemplateId, $commandId);
            foreach ($servicetemplateCommandArgumentValues as $servicetemplateCommandArgumentValue) {
                $servicetemplatecommandargumentvalues[] = [
                    'commandargument_id' => $servicetemplateCommandArgumentValue['commandargument_id'],
                    'servicetemplate_id' => $servicetemplateCommandArgumentValue['servicetemplate_id'],
                    'value'              => $servicetemplateCommandArgumentValue['value'],
                    'commandargument'    => [
                        'name'       => $servicetemplateCommandArgumentValue['commandargument']['name'],
                        'human_name' => $servicetemplateCommandArgumentValue['commandargument']['human_name'],
                        'command_id' => $servicetemplateCommandArgumentValue['commandargument']['command_id'],
                    ]
                ];
            }
        }

        //Get command arguments
        $commandarguments = $CommandargumentsTable->getByCommandId($commandId);
        if (empty($servicetemplatecommandargumentvalues)) {
            //Servicetemplate has no command arguments defined
            //Or we are in servicetemplates/add ?

            //Load command arguments of the check command
            foreach ($commandarguments as $commandargument) {
                $servicetemplatecommandargumentvalues[] = [
                    'commandargument_id' => $commandargument['Commandargument']['id'],
                    'value'              => '',
                    'commandargument'    => [
                        'name'       => $commandargument['Commandargument']['name'],
                        'human_name' => $commandargument['Commandargument']['human_name'],
                        'command_id' => $commandargument['Commandargument']['command_id'],
                    ]
                ];
            }
        }

        // Merge new command arguments that are missing in the service template to service template command arguments
        // and remove old command arguments that don't exists in the command anymore.
        $filteredCommandArgumentsValules = [];
        foreach ($commandarguments as $commandargument) {
            $valueExists = false;
            foreach ($servicetemplatecommandargumentvalues as $servicetemplatecommandargumentvalue) {
                if ($commandargument['Commandargument']['id'] === $servicetemplatecommandargumentvalue['commandargument_id']) {
                    $filteredCommandArgumentsValules[] = $servicetemplatecommandargumentvalue;
                    $valueExists = true;
                }
            }
            if (!$valueExists) {
                $filteredCommandArgumentsValules[] = [
                    'commandargument_id' => $commandargument['Commandargument']['id'],
                    'value'              => '',
                    'commandargument'    => [
                        'name'       => $commandargument['Commandargument']['name'],
                        'human_name' => $commandargument['Commandargument']['human_name'],
                        'command_id' => $commandargument['Commandargument']['command_id'],
                    ]
                ];
            }
        }
        $servicetemplatecommandargumentvalues = $filteredCommandArgumentsValules;

        $servicetemplatecommandargumentvalues = Hash::sort(
            $servicetemplatecommandargumentvalues,
            '{n}.commandargument.name',
            'asc',
            'natural'
        );

        $this->set('servicetemplatecommandargumentvalues', $servicetemplatecommandargumentvalues);
        $this->viewBuilder()->setOption('serialize', ['servicetemplatecommandargumentvalues']);
    }

    /**
     * @param int|null $commandId
     * @param int|null $servicetemplateId
     */
    public function loadEventhandlerCommandArguments($commandId = null, $servicetemplateId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $CommandargumentsTable CommandargumentsTable */
        $CommandargumentsTable = TableRegistry::getTableLocator()->get('Commandarguments');

        //ServicetemplateeventcommandargumentvaluesTable

        if (!$CommandsTable->existsById($commandId)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $servicetemplateeventhandlercommandargumentvalues = [];

        if ($servicetemplateId != null) {
            //User passed an servicetemplateId, so we are in a non add mode!
            //Check if the servicetemplate has defined command arguments for the event handler

            /** @var $ServicetemplateeventcommandargumentvaluesTable ServicetemplateeventcommandargumentvaluesTable */
            $ServicetemplateeventcommandargumentvaluesTable = TableRegistry::getTableLocator()->get('Servicetemplateeventcommandargumentvalues');

            $servicetemplateEventhandlerCommandArgumentValues = $ServicetemplateeventcommandargumentvaluesTable->getByServicetemplateIdAndCommandId($servicetemplateId, $commandId);

            foreach ($servicetemplateEventhandlerCommandArgumentValues as $servicetemplateEventhandlerCommandArgumentValue) {
                $servicetemplateeventhandlercommandargumentvalues[] = [
                    'commandargument_id' => $servicetemplateEventhandlerCommandArgumentValue['commandargument_id'],
                    'servicetemplate_id' => $servicetemplateEventhandlerCommandArgumentValue['servicetemplate_id'],
                    'value'              => $servicetemplateEventhandlerCommandArgumentValue['value'],
                    'commandargument'    => [
                        'name'       => $servicetemplateEventhandlerCommandArgumentValue['commandargument']['name'],
                        'human_name' => $servicetemplateEventhandlerCommandArgumentValue['commandargument']['human_name'],
                        'command_id' => $servicetemplateEventhandlerCommandArgumentValue['commandargument']['command_id'],
                    ]
                ];
            }
        }

        //Get command arguments
        if (empty($servicetemplateeventhandlercommandargumentvalues)) {
            //Servicetemplate has no command arguments defined
            //Or we are in servicetemplates/add ?

            //Load event handler command arguments of the check command
            foreach ($CommandargumentsTable->getByCommandId($commandId) as $commandargument) {
                $servicetemplateeventhandlercommandargumentvalues[] = [
                    'commandargument_id' => $commandargument['Commandargument']['id'],
                    'value'              => '',
                    'commandargument'    => [
                        'name'       => $commandargument['Commandargument']['name'],
                        'human_name' => $commandargument['Commandargument']['human_name'],
                        'command_id' => $commandargument['Commandargument']['command_id'],
                    ]
                ];
            }
        }

        $this->set('servicetemplateeventhandlercommandargumentvalues', $servicetemplateeventhandlercommandargumentvalues);
        $this->viewBuilder()->setOption('serialize', ['servicetemplateeventhandlercommandargumentvalues']);
    }

    /**
     * @param int|null $container_id
     */
    public function loadElementsByContainerId($container_id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var $ServicegroupsTable ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

        if (!$ContainersTable->existsById($container_id)) {
            throw new NotFoundException(__('Invalid Container'));
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($container_id);

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);
        $checkperiods = $timeperiods;

        $contacts = $ContactsTable->contactsByContainerId($containerIds, 'list');
        $contacts = Api::makeItJavaScriptAble($contacts);

        $contactgroups = $ContactgroupsTable->getContactgroupsByContainerId($containerIds, 'list', 'id');
        $contactgroups = Api::makeItJavaScriptAble($contactgroups);

        $servicegroups = $ServicegroupsTable->getServicegroupsByContainerId($containerIds, 'list');
        $servicegroups = Api::makeItJavaScriptAble($servicegroups);

        $this->set('timeperiods', $timeperiods);
        $this->set('checkperiods', $checkperiods);
        $this->set('contacts', $contacts);
        $this->set('contactgroups', $contactgroups);
        $this->set('servicegroups', $servicegroups);
        $this->viewBuilder()->setOption('serialize', ['timeperiods', 'checkperiods', 'contacts', 'contactgroups', 'servicegroups']);
    }

    /**
     * @param int|null $containerId
     */
    public function loadServicetemplatesByContainerId($containerId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId');
        $selected = $this->request->getQuery('selected');
        $ServicetemplateFilter = new ServicetemplateFilter($this->request);

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $containerIds = [ROOT_CONTAINER, $containerId];
        if ($containerId == ROOT_CONTAINER) {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
        }
        $servicetemplates = Api::makeItJavaScriptAble(
            $ServicetemplatesTable->getServicetemplatesForAngular($containerIds, $ServicetemplateFilter, $selected)
        );
        $this->set('servicetemplates', $servicetemplates);
        $this->viewBuilder()->setOption('serialize', ['servicetemplates']);
    }
}
