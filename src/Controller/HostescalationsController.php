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

use App\Model\Table\ContactgroupsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostescalationsTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostescalationsFilter;


/**
 * Class HostescalationsController
 * @package App\Controller
 */
class HostescalationsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var HostescalationsTable $HostescalationsTable */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');

        $HostescalationsFilter = new HostescalationsFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostescalationsFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $hostescalations = $HostescalationsTable->getHostescalationsIndex($HostescalationsFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($hostescalations as $index => $hostescalation) {
            $hostescalations[$index]['allowEdit'] = $this->isWritableContainer($hostescalation['container_id']);
        }

        $this->set('all_hostescalations', $hostescalations);
        $toJson = ['all_hostescalations', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_hostescalations', 'scroll'];
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

        /** @var HostescalationsTable $HostescalationsTable */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');

        if (!$HostescalationsTable->existsById($id)) {
            throw new NotFoundException(__('Host escalation not found'));
        }

        $hostescalation = $HostescalationsTable->getHostescalationById($id);
        if (!$this->allowedByContainerId(Hash::extract($hostescalation, 'Hostescalation.container_id'))) {
            $this->render403();
            return;
        }

        $this->set('hostescalation', $hostescalation);
        $this->viewBuilder()->setOption('serialize', ['hostescalation']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            /** @var HostescalationsTable $HostescalationsTable */
            $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');

            $hostescalationRequest = $this->request->getData();
            $hostescalationRequest['Hostescalation']['uuid'] = UUID::v4();

            $data['hosts'] = $HostescalationsTable->parseHostMembershipData(
                $this->request->getData('Hostescalation.hosts._ids', []),
                $this->request->getData('Hostescalation.hosts_excluded._ids', [])
            );
            $data['hostgroups'] = $HostescalationsTable->parseHostgroupMembershipData(
                $this->request->getData('Hostescalation.hostgroups._ids', []),
                $this->request->getData('Hostescalation.hostgroups_excluded._ids', [])
            );

            $data = array_merge($hostescalationRequest['Hostescalation'], $data);
            $hostescalation = $HostescalationsTable->newEntity($data);
            $HostescalationsTable->save($hostescalation);

            if ($hostescalation->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostescalation->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostescalation); // REST API ID serialization
                    return;
                }
            }
            $this->set('hostescalation', $hostescalation);
            $this->viewBuilder()->setOption('serialize', ['hostescalation']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var HostescalationsTable $HostescalationsTable */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');
        if (!$HostescalationsTable->existsById($id)) {
            throw new NotFoundException('Host escalation not found');
        }

        $hostescalation = $HostescalationsTable->get($id, contain: [
            'Hosts'         => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id', 'name']);
            },
            'Hostgroups'    => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id']);
            },
            'Contacts'      => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id']);
            },
            'Contactgroups' => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id']);
            },
        ]);

        if (!$this->allowedByContainerId($hostescalation->get('container_id'))) {
            $this->render403();
            return;
        }

        if ($this->request->is('post')) {
            $data['hosts'] = $HostescalationsTable->parseHostMembershipData(
                $this->request->getData('Hostescalation.hosts._ids'),
                $this->request->getData('Hostescalation.hosts_excluded._ids')
            );
            $data['hostgroups'] = $HostescalationsTable->parseHostgroupMembershipData(
                $this->request->getData('Hostescalation.hostgroups._ids'),
                $this->request->getData('Hostescalation.hostgroups_excluded._ids')
            );

            $data = array_merge($this->request->getData('Hostescalation'), $data);
            $hostescalation = $HostescalationsTable->patchEntity($hostescalation, $data);
            $HostescalationsTable->save($hostescalation);

            if ($hostescalation->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostescalation->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostescalation); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('hostescalation', $hostescalation);
        $this->viewBuilder()->setOption('serialize', ['hostescalation']);
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var HostescalationsTable $HostescalationsTable */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');

        if (!$HostescalationsTable->exists($id)) {
            throw new NotFoundException(__('Host escalation not found'));
        }

        $hostescalation = $HostescalationsTable->getHostescalationById($id);
        if (!$this->allowedByContainerId(Hash::extract($hostescalation, 'Hostescalation.container_id'))) {
            $this->render403();
            return;
        }
        $hostescalationEntity = $HostescalationsTable->get($id);
        if ($HostescalationsTable->delete($hostescalationEntity)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    public function loadElementsByContainerId($containerId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException(__('This is only allowed via API.'));
            return;
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var ContactgroupsTable $ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid hosttemplate'));
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $hostgroups = $HostgroupsTable->getHostgroupsByContainerId($containerIds, 'list', 'id');
        $hostgroups = Api::makeItJavaScriptAble($hostgroups);

        $hosts = $HostsTable->getHostsByContainerId($containerIds, 'list');
        $hosts = Api::makeItJavaScriptAble($hosts);

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);

        $contacts = $ContactsTable->contactsByContainerId($containerIds, 'list');
        $contacts = Api::makeItJavaScriptAble($contacts);

        $contactgroups = $ContactgroupsTable->getContactgroupsByContainerId($containerIds, 'list', 'id');
        $contactgroups = Api::makeItJavaScriptAble($contactgroups);

        $this->set(compact(['hosts', 'hostgroups', 'timeperiods', 'contacts', 'contactgroups']));
        $this->viewBuilder()->setOption('serialize', ['hosts', 'hostgroups', 'timeperiods', 'contacts', 'contactgroups']);
    }

    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP, CT_CONTACTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP, CT_CONTACTGROUP]);
        }

        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }


    public function loadExcludedHostsByContainerIdAndHostgroupIds() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId');
        $hostgroupIds = $this->request->getQuery('hostgroupIds');

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');


        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

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


        $excludedHosts = $HostsTable->getHostsByContainerIdAndHostgroupIds($containerIds, $hostgroupIds, 'list', 'id');
        $excludedHosts = Api::makeItJavaScriptAble($excludedHosts);


        $this->set(compact(['excludedHosts']));
        $this->viewBuilder()->setOption('serialize', ['excludedHosts']);
    }

    public function loadExcludedHostgroupsByContainerIdAndHostIds() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId');
        $hostIds = $this->request->getQuery('hostIds');

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');


        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

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
        $excludedHostgroups = $HostgroupsTable->getHostgroupsByContainerIdAndHostIds($containerIds, $hostIds, 'list', 'id');
        $excludedHostgroups = Api::makeItJavaScriptAble($excludedHostgroups);


        $this->set(compact(['excludedHostgroups']));
        $this->viewBuilder()->setOption('serialize', ['excludedHostgroups']);
    }
}
