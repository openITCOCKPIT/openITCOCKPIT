<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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
use App\Model\Table\HostdependenciesTable;
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
use itnovum\openITCOCKPIT\Filter\HostdependenciesFilter;

/**
 * Class HostdependenciesController
 * @package App\Controller
 */
class HostdependenciesController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $HostdependenciesTable HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');

        $HostdependenciesFilter = new HostdependenciesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostdependenciesFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $hostdependencies = $HostdependenciesTable->getHostdependenciesIndex($HostdependenciesFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($hostdependencies as $index => $hostdependency) {
            $hostdependencies[$index]['allowEdit'] = $this->isWritableContainer($hostdependency['container_id']);
        }


        $this->set('all_hostdependencies', $hostdependencies);
        $toJson = ['all_hostdependencies', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_hostdependencies', 'scroll'];
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

        /** @var $HostdependenciesTable HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');

        if (!$HostdependenciesTable->exists($id)) {
            throw new NotFoundException(__('Host dependency not found'));
        }

        $hostdependency = $HostdependenciesTable->getHostdependencyById($id);
        if (!$this->allowedByContainerId(Hash::extract($hostdependency, 'Hostdependency.container_id'))) {
            $this->render403();
            return;
        }

        $this->set('hostdependency', $hostdependency);
        $this->viewBuilder()->setOption('serialize', ['hostdependency']);

    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('post')) {
            /** @var HostdependenciesTable $HostdependenciesTable */
            $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
            $data = [];
            $data['hosts'] = $HostdependenciesTable->parseHostMembershipData(
                $this->request->getData('Hostdependency.hosts._ids'),
                $this->request->getData('Hostdependency.hosts_dependent._ids')
            );
            $data['hostgroups'] = $HostdependenciesTable->parseHostgroupMembershipData(
                $this->request->getData('Hostdependency.hostgroups._ids'),
                $this->request->getData('Hostdependency.hostgroups_dependent._ids')
            );

            $validateRequestEntity = $HostdependenciesTable->newEntity($this->request->getData('Hostdependency'));
            $validateRequestEntity->set('uuid', UUID::v4());

            if ($validateRequestEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $validateRequestEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data = array_merge($this->request->getData('Hostdependency'), $data);
            $hostdependency = $HostdependenciesTable->newEntity($data);
            $hostdependency->set('uuid', UUID::v4());
            $HostdependenciesTable->save($hostdependency);

            if ($hostdependency->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostdependency->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostdependency); // REST API ID serialization
                    return;
                }
            }
            $this->set('hostdependency', $hostdependency);
            $this->viewBuilder()->setOption('serialize', ['hostdependency']);
        }
    }

    /**
     * @param null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
        if (!$HostdependenciesTable->existsById($id)) {
            throw new NotFoundException('Host dependency not found');
        }
        $hostdependency = $HostdependenciesTable->get($id, contain: [
            'Hosts'      => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id', 'name']);
            },
            'Hostgroups' => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id']);
            },
        ]);

        if (!$this->allowedByContainerId($hostdependency->get('container_id'))) {
            $this->render403();
            return;
        }
        if ($this->request->is('post')) {
            /** @var HostdependenciesTable $HostdependenciesTable */
            $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
            $data['hosts'] = $HostdependenciesTable->parseHostMembershipData(
                $this->request->getData('Hostdependency.hosts._ids'),
                $this->request->getData('Hostdependency.hosts_dependent._ids')
            );
            $data['hostgroups'] = $HostdependenciesTable->parseHostgroupMembershipData(
                $this->request->getData('Hostdependency.hostgroups._ids'),
                $this->request->getData('Hostdependency.hostgroups_dependent._ids')
            );

            $validateRequestEntity = $HostdependenciesTable->patchEntity(
                $hostdependency,
                $this->request->getData('Hostdependency')
            );
            if ($validateRequestEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $validateRequestEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data = array_merge($this->request->getData('Hostdependency'), $data);
            $hostdependency = $HostdependenciesTable->patchEntity($hostdependency, $data);
            $HostdependenciesTable->save($hostdependency);

            if ($hostdependency->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostdependency->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostdependency); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('hostdependency', $hostdependency);
        $this->viewBuilder()->setOption('serialize', ['hostdependency']);
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');

        if (!$HostdependenciesTable->existsById($id)) {
            throw new NotFoundException(__('Host dependency not found'));
        }

        $hostdependency = $HostdependenciesTable->getHostdependencyById($id);
        if (!$this->allowedByContainerId(Hash::extract($hostdependency, 'Hostdependency.container_id'))) {
            $this->render403();
            return;
        }
        $hostdependencyEntity = $HostdependenciesTable->get($id);
        if ($HostdependenciesTable->delete($hostdependencyEntity)) {
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
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $hostgroups = $HostgroupsTable->getHostgroupsByContainerId($containerIds, 'list', 'id');
        $hostgroups = Api::makeItJavaScriptAble($hostgroups);
        $hostgroupsDependent = $hostgroups;

        $hosts = $HostsTable->getHostsByContainerId($containerIds, 'list');
        $hosts = Api::makeItJavaScriptAble($hosts);
        $hostsDependent = $hosts;

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);

        $this->set('hosts', $hosts);
        $this->set('hostsDependent', $hostsDependent);
        $this->set('hostgroups', $hostgroups);
        $this->set('hostgroupsDependent', $hostgroupsDependent);
        $this->set('timeperiods', $timeperiods);
        $this->viewBuilder()->setOption('serialize', [
            'hosts',
            'hostsDependent',
            'hostgroups',
            'hostgroupsDependent',
            'timeperiods'
        ]);
    }

    /**
     * @throws \Exception
     */
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

}
