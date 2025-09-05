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
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatecommandargumentvaluesTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Core\Plugin;
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
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HosttemplateFilter;

class HosttemplatesController extends AppController {

    use PluginManagerTableTrait;

    public function index() {
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }


        $HosttemplateFilter = new HosttemplateFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HosttemplateFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $hosttemplates = $HosttemplatesTable->getHosttemplatesIndex($HosttemplateFilter, $PaginateOMat, $MY_RIGHTS);
        $typesForView = $HosttemplatesTable->getHosttemplateTypesWithStyles();


        foreach ($hosttemplates as $index => $hosttemplate) {
            $hosttemplates[$index]['Hosttemplate']['allow_edit'] = true;
            $hosttemplates[$index]['Hosttemplate']['type'] = $typesForView[$hosttemplate['Hosttemplate']['hosttemplatetype_id']];
            if ($this->hasRootPrivileges === false) {
                $hosttemplates[$index]['Hosttemplate']['allow_edit'] = $this->isWritableContainer($hosttemplate['Hosttemplate']['container_id']);
            }
        }


        $this->set('all_hosttemplates', $hosttemplates);

        $toJson = ['all_hosttemplates', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_hosttemplates', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param null|int $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        if (!$HosttemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid host template'));
        }

        $hosttemplate = $HosttemplatesTable->getHosttemplateById($id, [
            'Containers',
            'Hosttemplatecommandargumentvalues',
            'Customvariables'
        ]);


        if (!$this->allowedByContainerId($hosttemplate['Container']['id'])) {
            throw new ForbiddenException('403 Forbidden');
        }

        $this->set('hosttemplate', $hosttemplate);
        $this->viewBuilder()->setOption('serialize', ['hosttemplate']);
    }

    /**
     * @param null|int $hosttemplatetype_id
     * @todo $hosttemplatetype_id as request parameter ???
     */
    public function add($hosttemplatetype_id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }
        if ($this->request->is('get')) {
            /** @var HosttemplatesTable $HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

            $this->set('types', Api::makeItJavaScriptAble(
                $HosttemplatesTable->getHosttemplateTypesWithStyles()
            ));
            $this->viewBuilder()->setOption('serialize', ['types']);
        }
        if ($this->request->is('post')) {
            /** @var $HosttemplatesTable HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

            $hosttemplate = $HosttemplatesTable->newEmptyEntity();
            $hosttemplate = $HosttemplatesTable->patchEntity($hosttemplate, $this->request->getData('Hosttemplate'));
            $hosttemplate->set('uuid', UUID::v4());

            $User = new User($this->getUser());
            $requestData = $this->request->getData();

            $hosttemplate = $HosttemplatesTable->createHosttemplate($hosttemplate, $requestData, $User->getId());

            if ($hosttemplate->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hosttemplate->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hosttemplate); // REST API ID serialization
                    return;
                }
            }
            $this->set('hosttemplate', $hosttemplate);
            $this->viewBuilder()->setOption('serialize', ['hosttemplate']);
        }
    }

    /**
     * @param null|int $id
     * @param null|int $hosttemplatetype_id
     */
    public function edit($id = null, $hosttemplatetype_id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');

        if (!$HosttemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Host template not found'));
        }

        $hosttemplate = $HosttemplatesTable->getHosttemplateForEdit($id);
        $hosttemplateForChangeLog = $hosttemplate;

        if (!$this->allowedByContainerId($hosttemplate['Hosttemplate']['container_id'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return host template information
            $commands = $CommandsTable->getCommandByTypeAsList(HOSTCHECK_COMMAND);
            $this->set('commands', Api::makeItJavaScriptAble($commands));
            $this->set('hosttemplate', $hosttemplate);
            $this->set('types', Api::makeItJavaScriptAble(
                $HosttemplatesTable->getHosttemplateTypesWithStyles()
            ));
            $this->viewBuilder()->setOption('serialize', ['hosttemplate', 'commands', 'types']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Update host template data
            $User = new User($this->getUser());

            $hosttemplateEntity = $HosttemplatesTable->get($id);
            $hosttemplateEntity->setAccess('uuid', false);
            $hosttemplateEntity = $HosttemplatesTable->patchEntity($hosttemplateEntity, $this->request->getData('Hosttemplate'));
            $hosttemplateEntity->id = $id;

            $requestData = $this->request->getData();

            $hosttemplateEntity = $HosttemplatesTable->updateHosttemplate(
                $hosttemplateEntity,
                $requestData,
                $hosttemplateForChangeLog,
                $User->getId()
            );

            if ($hosttemplateEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hosttemplateEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hosttemplateEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('hosttemplate', $hosttemplateEntity);
            $this->viewBuilder()->setOption('serialize', ['hosttemplate']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        if (!$HosttemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Host template not found'));
        }

        $hosttemplate = $HosttemplatesTable->get($id);

        if (!$this->allowedByContainerId($hosttemplate->get('container_id'))) {
            $this->render403();
            return;
        }

        if (!$HosttemplatesTable->allowDelete($id)) {
            $usedBy = [
                [
                    'baseUrl' => '#',
                    'state'   => 'HosttemplatesUsedBy',
                    'message' => __('Used by other objects'),
                    'module'  => 'Core'
                ]
            ];

            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Issue while deleting host template'));
            $this->set('usedBy', $usedBy);
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
            return;
        }

        $User = new User($this->getUser());

        if ($HosttemplatesTable->__delete($hosttemplate, $User)) {
            $this->set('success', true);
            $this->set('message', __('Host template successfully deleted'));
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->set('success', false);
        $this->set('message', __('Error while deleting host template'));
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    /**
     * @param null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $hosttemplates = $HosttemplatesTable->getHosttemplatesForCopy(func_get_args(), $MY_RIGHTS);
            /** @var $CommandsTable CommandsTable */
            $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
            $commands = $CommandsTable->getCommandByTypeAsList(HOSTCHECK_COMMAND);
            $this->set('hosttemplates', $hosttemplates);
            $this->set('commands', Api::makeItJavaScriptAble($commands));
            $this->viewBuilder()->setOption('serialize', ['hosttemplates', 'commands']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $Cache = new KeyValueStore();

            $postData = $this->request->getData('data');
            $User = new User($this->getUser());

            foreach ($postData as $index => $hosttemplateData) {
                if (!isset($hosttemplateData['Hosttemplate']['id'])) {
                    //Create/clone hosttemplate
                    $sourceHosttemplateId = $hosttemplateData['Source']['id'];
                    if (!$Cache->has($sourceHosttemplateId)) {
                        $sourceHosttemplate = $HosttemplatesTable->getHosttemplateForEdit($sourceHosttemplateId);
                        $sourceHosttemplate = $sourceHosttemplate['Hosttemplate'];
                        unset($sourceHosttemplate['id'], $sourceHosttemplate['uuid']);

                        foreach ($sourceHosttemplate['hosttemplatecommandargumentvalues'] as $i => $hosttemplatecommandargumentvalues) {
                            unset($sourceHosttemplate['hosttemplatecommandargumentvalues'][$i]['id']);
                            unset($sourceHosttemplate['hosttemplatecommandargumentvalues'][$i]['hosttemplate_id']);
                        }

                        foreach ($sourceHosttemplate['customvariables'] as $i => $customvariable) {
                            unset($sourceHosttemplate['customvariables'][$i]['id']);
                            unset($sourceHosttemplate['customvariables'][$i]['object_id']);
                        }

                        $Cache->set($sourceHosttemplateId, $sourceHosttemplate);
                    }

                    $sourceHosttemplate = $Cache->get($sourceHosttemplateId);

                    $newHosttemplateData = $sourceHosttemplate;
                    $newHosttemplateData['uuid'] = UUID::v4();
                    $newHosttemplateData['name'] = $hosttemplateData['Hosttemplate']['name'];
                    $newHosttemplateData['description'] = $hosttemplateData['Hosttemplate']['description'];
                    $newHosttemplateData['command_id'] = $hosttemplateData['Hosttemplate']['command_id'];
                    if (!empty($hosttemplateData['Hosttemplate']['hosttemplatecommandargumentvalues'])) {
                        $newHosttemplateData['hosttemplatecommandargumentvalues'] = $hosttemplateData['Hosttemplate']['hosttemplatecommandargumentvalues'];
                    }

                    $newHosttemplateEntity = $HosttemplatesTable->newEntity($newHosttemplateData);
                }

                $action = 'copy';
                if (isset($hosttemplateData['Hosttemplate']['id'])) {
                    //Update existing hosttemplates
                    //This happens, if a user copy multiple hosttemplates, and one run into an validation error
                    //All hosttemplates without validation errors got already saved to the database
                    $newHosttemplateEntity = $HosttemplatesTable->get($hosttemplateData['Hosttemplate']['id']);
                    $newHosttemplateEntity->setAccess('*', false);
                    $newHosttemplateEntity->setAccess(['name', 'description', 'command_id', 'hosttemplatecommandargumentvalues'], true);
                    $newHosttemplateEntity = $HosttemplatesTable->patchEntity($newHosttemplateEntity, $hosttemplateData['Hosttemplate']);
                    $newHosttemplateData = $newHosttemplateEntity->toArray();
                    $action = 'edit';
                }
                $HosttemplatesTable->save($newHosttemplateEntity);

                $postData[$index]['Error'] = [];
                if ($newHosttemplateEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newHosttemplateEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Hosttemplate']['id'] = $newHosttemplateEntity->get('id');

                    /** @var  ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        'hosttemplates',
                        $postData[$index]['Hosttemplate']['id'],
                        OBJECT_HOSTTEMPLATE,
                        [$newHosttemplateEntity->get('container_id')],
                        $User->getId(),
                        $newHosttemplateEntity->get('name'),
                        ['Hosttemplate' => $newHosttemplateData]
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

    /**
     * @param null $id
     */
    public function usedBy($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HosttemplatesTable->existsById($id)) {
            throw new NotFoundException(__('Host template not found'));
        }

        $hosttemplate = $HosttemplatesTable->get($id);

        if (!$this->allowedByContainerId($hosttemplate->get('container_id'))) {
            $this->render403();
            return;
        }

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $HosttemplateFilter = new HosttemplateFilter($this->request);
        $filter = $HosttemplateFilter->usedByFilter();

        $includeDisabled = true;
        if (isset($filter['Hosts.disabled']) && $filter['Hosts.disabled'] === 0) {
            $includeDisabled = false;
        }

        $hosts = $HostsTable->getHostsForHosttemplateUsedBy($id, $MY_RIGHTS, $includeDisabled);

        $all_hosts = [];
        foreach ($hosts as $host) {
            $Host = new Host($host);

            if ($this->hasRootPrivileges) {
                $allowEdit = true;
            } else {
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $Host->getContainerIds());
                $allowEdit = $ContainerPermissions->hasPermission();
            }
            $tmpRecord = [
                'Host' => $Host->toArray()
            ];
            $tmpRecord['Host']['allow_edit'] = $allowEdit;
            $all_hosts[] = $tmpRecord;
        }


        $this->set(compact(['all_hosts', 'hosttemplate']));
        $this->viewBuilder()->setOption('serialize', ['all_hosts', 'hosttemplate']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

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
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

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

        if ($container_id == ROOT_CONTAINER) {
            // ITC-2819
            // Hosts in the /root container can be a member of any host group
            $hostgroups = $HostgroupsTable->getHostgroupsAsList();
            $hostgroups = Api::makeItJavaScriptAble($hostgroups);
        } else {
            $hostgroups = $HostgroupsTable->getHostgroupsByContainerId($containerIds, 'list', 'id');
            $hostgroups = Api::makeItJavaScriptAble($hostgroups);
        }

        $exporters = [];
        if (Plugin::isLoaded('PrometheusModule')) {
            /** @var \PrometheusModule\Model\Table\PrometheusExportersTable $PrometheusExportersTable */
            $PrometheusExportersTable = TableRegistry::getTableLocator()->get('PrometheusModule.PrometheusExporters');

            $exporters = $PrometheusExportersTable->getExportersByContainerId($containerIds, 'list', 'id');
            $exporters = Api::makeItJavaScriptAble($exporters);
        }
        $slas = [];
        if (Plugin::isLoaded('SLAModule')) {
            /** @var \SLAModule\Model\Table\SlasTable $SlasTable */
            $SlasTable = TableRegistry::getTableLocator()->get('SLAModule.Slas');

            $slas = $SlasTable->getSlasByContainerId($containerIds, 'list', 'id');
            $slas = Api::makeItJavaScriptAble($slas);
        }

        $this->set('timeperiods', $timeperiods);
        $this->set('checkperiods', $checkperiods);
        $this->set('contacts', $contacts);
        $this->set('contactgroups', $contactgroups);
        $this->set('hostgroups', $hostgroups);
        $this->set('exporters', $exporters);
        $this->set('slas', $slas);
        $this->viewBuilder()->setOption('serialize', [
            'timeperiods',
            'checkperiods',
            'contacts',
            'contactgroups',
            'hostgroups',
            'exporters',
            'slas'
        ]);
    }

    /**
     * @param int|null $hosttemplateId
     * @throws Exception
     */
    public function loadContainers($hosttemplateId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOSTTEMPLATE, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOSTTEMPLATE, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        }

        $areContainersRestricted = false;
        if (is_numeric($hosttemplateId)) {
            //Edit mode

            /** @var $HosttemplatesTable HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

            $hosttemplatesContainerId = $HosttemplatesTable->getContainerIdById($hosttemplateId);
            $usedContainerIds = $HostsTable->getHostPrimaryContainerIdsByHosttemplateId($hosttemplateId);

            if (!empty($usedContainerIds)) {
                //This host template is used by some hosts.
                //Container options needs to be needs to be restricted if the hosts are using some sub containers...
                $restrictedContainers = [];
                foreach ($containers as $containerId => $path) {
                    $containerId = (int)$containerId;
                    if (in_array($containerId, [ROOT_CONTAINER, $hosttemplatesContainerId], true)) {
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
        $commands = $CommandsTable->getCommandByTypeAsList(HOSTCHECK_COMMAND);

        $this->set('commands', Api::makeItJavaScriptAble($commands));
        $this->viewBuilder()->setOption('serialize', ['commands']);
    }

    /**
     * @param null $commandId
     * @param null $hosttemplateId
     */
    public function loadCommandArguments($commandId = null, $hosttemplateId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $CommandargumentsTable CommandargumentsTable */
        $CommandargumentsTable = TableRegistry::getTableLocator()->get('Commandarguments');

        //HosttemplatecommandargumentvaluesTable

        if (!$CommandsTable->existsById($commandId)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $hosttemplatecommandargumentvalues = [];

        if ($hosttemplateId != null) {
            //User passed an hosttemplateId, so we are in a non add mode!
            //Check if the hosttemplate has defined command arguments

            /** @var $HosttemplatecommandargumentvaluesTable HosttemplatecommandargumentvaluesTable */
            $HosttemplatecommandargumentvaluesTable = TableRegistry::getTableLocator()->get('Hosttemplatecommandargumentvalues');

            $hosttemplateCommandArgumentValues = $HosttemplatecommandargumentvaluesTable->getByHosttemplateIdAndCommandId($hosttemplateId, $commandId);

            foreach ($hosttemplateCommandArgumentValues as $hosttemplateCommandArgumentValue) {
                $hosttemplatecommandargumentvalues[] = [
                    'commandargument_id' => $hosttemplateCommandArgumentValue['commandargument_id'],
                    'hosttemplate_id'    => $hosttemplateCommandArgumentValue['hosttemplate_id'],
                    'value'              => $hosttemplateCommandArgumentValue['value'],
                    'commandargument'    => [
                        'name'       => $hosttemplateCommandArgumentValue['commandargument']['name'],
                        'human_name' => $hosttemplateCommandArgumentValue['commandargument']['human_name'],
                        'command_id' => $hosttemplateCommandArgumentValue['commandargument']['command_id'],
                    ]
                ];
            }
        }

        //Get command arguments
        $commandarguments = $CommandargumentsTable->getByCommandId($commandId);
        if (empty($hosttemplatecommandargumentvalues)) {
            //Hosttemplate has no command arguments defined
            //Or we are in hosttemplates/add ?

            //Load command arguments of the check command
            foreach ($commandarguments as $commandargument) {
                $hosttemplatecommandargumentvalues[] = [
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

        // Merge new command arguments that are missing in the host template to host template command arguments
        // and remove old command arguments that don't exists in the command anymore.
        $filteredCommandArgumentsValules = [];
        foreach ($commandarguments as $commandargument) {
            $valueExists = false;
            foreach ($hosttemplatecommandargumentvalues as $hosttemplatecommandargumentvalue) {
                if ($commandargument['Commandargument']['id'] === $hosttemplatecommandargumentvalue['commandargument_id']) {
                    $filteredCommandArgumentsValules[] = $hosttemplatecommandargumentvalue;
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

        $hosttemplatecommandargumentvalues = $filteredCommandArgumentsValules;
        $hosttemplatecommandargumentvalues = Hash::sort(
            $hosttemplatecommandargumentvalues,
            '{n}.commandargument.name',
            'asc',
            'natural'
        );

        $this->set('hosttemplatecommandargumentvalues', $hosttemplatecommandargumentvalues);
        $this->viewBuilder()->setOption('serialize', ['hosttemplatecommandargumentvalues']);
    }

    /**
     * @param int|null $containerId
     */
    public function loadHosttemplates($containerId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $containerId = $this->request->getQuery('containerId');
        $HosttemplateFilter = new HosttemplateFilter($this->request);

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        $containerIds = [ROOT_CONTAINER, $containerId];
        if ($containerId == ROOT_CONTAINER) {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
        }

        $hosttemplates = Api::makeItJavaScriptAble(
            $HosttemplatesTable->getHosttemplatesForAngular($containerIds, $HosttemplateFilter)
        );

        $this->set('hosttemplates', $hosttemplates);
        $this->viewBuilder()->setOption('serialize', ['hosttemplates']);
    }

}
