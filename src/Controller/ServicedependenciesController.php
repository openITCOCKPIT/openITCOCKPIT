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
use App\Model\Table\ServicedependenciesTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServicedependenciesFilter;

/**
 * Class ServicedependenciesController
 * @package App\Controller
 */
class ServicedependenciesController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');

        $ServicedependenciesFilter = new ServicedependenciesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServicedependenciesFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $servicedependencies = $ServicedependenciesTable->getServicedependenciesIndex($ServicedependenciesFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($servicedependencies as $index => $servicedependency) {
            $servicedependencies[$index]['allowEdit'] = $this->isWritableContainer($servicedependency['container_id']);
        }

        $this->set('all_servicedependencies', $servicedependencies);
        $toJson = ['all_servicedependencies', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_servicedependencies', 'scroll'];
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

        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');

        if (!$ServicedependenciesTable->existsById($id)) {
            throw new NotFoundException(__('Service dependency not found'));
        }

        $servicedependency = $ServicedependenciesTable->getServicedependencyById($id);
        if (!$this->allowedByContainerId(Hash::extract($servicedependency, 'Servicedependency.container_id'))) {
            $this->render403();
            return;
        }

        $this->set('servicedependency', $servicedependency);
        $this->viewBuilder()->setOption('serialize', ['servicedependency']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('post')) {
            /** @var ServicedependenciesTable $ServicedependenciesTable */
            $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
            $data = [];
            $data['services'] = $ServicedependenciesTable->parseServiceMembershipData(
                $this->request->getData('Servicedependency.services._ids'),
                $this->request->getData('Servicedependency.services_dependent._ids')
            );
            $data['servicegroups'] = $ServicedependenciesTable->parseServicegroupMembershipData(
                $this->request->getData('Servicedependency.servicegroups._ids'),
                $this->request->getData('Servicedependency.servicegroups_dependent._ids')
            );

            $validateRequestEntity = $ServicedependenciesTable->newEntity($this->request->getData('Servicedependency'));
            $validateRequestEntity->set('uuid', UUID::v4());

            if ($validateRequestEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $validateRequestEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }
            $data = array_merge($this->request->getData('Servicedependency'), $data);
            $servicedependency = $ServicedependenciesTable->newEntity($data);
            $servicedependency->set('uuid', UUID::v4());
            $ServicedependenciesTable->save($servicedependency);

            if ($servicedependency->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $servicedependency->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($servicedependency); // REST API ID serialization
                    return;
                }
            }
            $this->set('servicedependency', $servicedependency);
            $this->viewBuilder()->setOption('serialize', ['servicedependency']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
        if (!$ServicedependenciesTable->existsById($id)) {
            throw new NotFoundException('Service dependency not found');
        }
        $servicedependency = $ServicedependenciesTable->get($id, contain: [
            'Services'      => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id', 'name']);
            },
            'Servicegroups' => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id']);
            },
        ]);

        if (!$this->allowedByContainerId($servicedependency->get('container_id'))) {
            $this->render403();
            return;
        }
        if ($this->request->is('post')) {
            $data['services'] = $ServicedependenciesTable->parseServiceMembershipData(
                $this->request->getData('Servicedependency.services._ids'),
                $this->request->getData('Servicedependency.services_dependent._ids')
            );
            $data['servicegroups'] = $ServicedependenciesTable->parseServicegroupMembershipData(
                $this->request->getData('Servicedependency.servicegroups._ids'),
                $this->request->getData('Servicedependency.servicegroups_dependent._ids')
            );

            $validateRequestEntity = $ServicedependenciesTable->patchEntity(
                $servicedependency,
                $this->request->getData('Servicedependency')
            );
            if ($validateRequestEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $validateRequestEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data = array_merge($this->request->getData('Servicedependency'), $data);
            $servicedependency = $ServicedependenciesTable->patchEntity($servicedependency, $data);
            $ServicedependenciesTable->save($servicedependency);

            if ($servicedependency->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $servicedependency->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($servicedependency); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('servicedependency', $servicedependency);
        $this->viewBuilder()->setOption('serialize', ['servicedependency']);
    }

    /**
     * @param null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');

        if (!$ServicedependenciesTable->existsById($id)) {
            throw new NotFoundException(__('Service dependency not found'));
        }

        $servicedependency = $ServicedependenciesTable->getServicedependencyById($id);
        if (!$this->allowedByContainerId(Hash::extract($servicedependency, 'Servicedependency.container_id'))) {
            $this->render403();
            return;
        }
        $servicedependencyEntity = $ServicedependenciesTable->get($id);
        if ($ServicedependenciesTable->delete($servicedependencyEntity)) {
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
        /** @var ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $servicegroups = $ServicegroupsTable->getServicegroupsByContainerId($containerIds, 'list', 'id');
        $servicegroups = Api::makeItJavaScriptAble($servicegroups);
        $servicegroupsDependent = $servicegroups;

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);

        $this->set('servicegroups', $servicegroups);
        $this->set('servicegroupsDependent', $servicegroupsDependent);
        $this->set('timeperiods', $timeperiods);
        $this->viewBuilder()->setOption('serialize', [
            'servicegroups',
            'servicegroupsDependent',
            'timeperiods'
        ]);
    }

    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_SERVICE, [], $this->hasRootPrivileges, [CT_HOSTGROUP, CT_SERVICEGROUP, CT_CONTACTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_SERVICE, [], $this->hasRootPrivileges, [CT_HOSTGROUP, CT_SERVICEGROUP, CT_CONTACTGROUP]);
        }

        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }
}
