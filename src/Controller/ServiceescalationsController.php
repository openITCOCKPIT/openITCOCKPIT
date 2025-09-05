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
use App\Model\Table\ServiceescalationsTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Filter\ServiceescalationsFilter;

/**
 * Class ServiceescalationsController
 * @package App\Controller
 */
class ServiceescalationsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var ServiceescalationsTable $ServiceescalationsTable */
        $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');

        $ServiceescalationsFilter = new ServiceescalationsFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceescalationsFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $serviceescalations = $ServiceescalationsTable->getServiceescalationsIndex($ServiceescalationsFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($serviceescalations as $index => $serviceescalation) {
            $serviceescalations[$index]['allowEdit'] = $this->isWritableContainer($serviceescalation['container_id']);
        }

        $this->set('all_serviceescalations', $serviceescalations);
        $toJson = ['all_serviceescalations', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_serviceescalations', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ServiceescalationsTable $ServiceescalationsTable */
        $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');

        if (!$ServiceescalationsTable->existsById($id)) {
            throw new NotFoundException(__('Service escalation not found'));
        }

        $serviceescalation = $ServiceescalationsTable->getServiceescalationById($id);
        if (!$this->allowedByContainerId(Hash::extract($serviceescalation, 'Serviceescalation.container_id'))) {
            $this->render403();
            return;
        }

        $this->set('serviceescalation', $serviceescalation);
        $this->viewBuilder()->setOption('serialize', ['serviceescalation']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            /** @var ServiceescalationsTable $ServiceescalationsTable */
            $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');

            $serviceescalationRequest = $this->request->getData();
            $serviceescalationRequest['Serviceescalation']['uuid'] = UUID::v4();

            $data['services'] = $ServiceescalationsTable->parseServiceMembershipData(
                $this->request->getData('Serviceescalation.services._ids'),
                $this->request->getData('Serviceescalation.services_excluded._ids')
            );
            $data['servicegroups'] = $ServiceescalationsTable->parseServicegroupMembershipData(
                $this->request->getData('Serviceescalation.servicegroups._ids'),
                $this->request->getData('Serviceescalation.servicegroups_excluded._ids')
            );

            $data = array_merge($serviceescalationRequest['Serviceescalation'], $data);
            $serviceescalation = $ServiceescalationsTable->newEntity($data);
            $ServiceescalationsTable->save($serviceescalation);

            if ($serviceescalation->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $serviceescalation->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($serviceescalation); // REST API ID serialization
                    return;
                }
            }
            $this->set('serviceescalation', $serviceescalation);
            $this->viewBuilder()->setOption('serialize', ['serviceescalation']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var ServiceescalationsTable $ServiceescalationsTable */
        $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');
        if (!$ServiceescalationsTable->existsById($id)) {
            throw new NotFoundException('Service escalation not found');
        }

        $serviceescalation = $ServiceescalationsTable->get($id, contain: [
            'Services'      => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id', 'name']);
            },
            'Servicegroups' => function (Query $q) {
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

        if (!$this->allowedByContainerId($serviceescalation->get('container_id'))) {
            $this->render403();
            return;
        }

        if ($this->request->is('post')) {
            $data['services'] = $ServiceescalationsTable->parseServiceMembershipData(
                $this->request->getData('Serviceescalation.services._ids'),
                $this->request->getData('Serviceescalation.services_excluded._ids')
            );
            $data['servicegroups'] = $ServiceescalationsTable->parseServicegroupMembershipData(
                $this->request->getData('Serviceescalation.servicegroups._ids'),
                $this->request->getData('Serviceescalation.servicegroups_excluded._ids')
            );

            $data = array_merge($this->request->getData('Serviceescalation'), $data);

            $serviceescalation = $ServiceescalationsTable->patchEntity($serviceescalation, $data);

            $ServiceescalationsTable->save($serviceescalation);

            if ($serviceescalation->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $serviceescalation->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($serviceescalation); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('serviceescalation', $serviceescalation);
        $this->viewBuilder()->setOption('serialize', ['serviceescalation']);
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServiceescalationsTable ServiceescalationsTable */
        $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');

        if (!$ServiceescalationsTable->exists($id)) {
            throw new NotFoundException(__('Service escalation not found'));
        }

        $serviceescalation = $ServiceescalationsTable->getServiceescalationById($id);
        if (!$this->allowedByContainerId(Hash::extract($serviceescalation, 'Serviceescalation.container_id'))) {
            $this->render403();
            return;
        }
        $serviceescalationEntity = $ServiceescalationsTable->get($id);
        if ($ServiceescalationsTable->delete($serviceescalationEntity)) {
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
        /** @var ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $servicegroups = $ServicegroupsTable->getServicegroupsByContainerId($containerIds, 'list', 'id');
        $servicegroups = Api::makeItJavaScriptAble($servicegroups);

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);

        $contacts = $ContactsTable->contactsByContainerId($containerIds, 'list');
        $contacts = Api::makeItJavaScriptAble($contacts);

        $contactgroups = $ContactgroupsTable->getContactgroupsByContainerId($containerIds, 'list', 'id');
        $contactgroups = Api::makeItJavaScriptAble($contactgroups);

        $this->set('servicegroups', $servicegroups);
        $this->set('timeperiods', $timeperiods);
        $this->set('contacts', $contacts);
        $this->set('contactgroups', $contactgroups);

        $this->viewBuilder()->setOption('serialize', [
            'servicegroups',
            'timeperiods',
            'contacts',
            'contactgroups'
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

    public function loadExcludedServicesByContainerIdAndServicegroupIdsForOptionGroup() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId');
        $servicegroupIds = $this->request->getQuery('servicegroupIds');

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'servicename'
            ]
        ]);

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');


        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        if ($containerId == ROOT_CONTAINER) {
            //Don't panic! Only root users can edit /root objects ;)
            //So no loss of selected hosts/host templates
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true, [
                CT_GLOBAL,
                CT_TENANT,
                CT_NODE
            ]);
        } else {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, false, [
                CT_GLOBAL,
                CT_TENANT,
                CT_NODE
            ]);
        }


        $excludedServices = $ServicesTable->getServicesByContainerIdAndServicegroupIds(
            $containerIds,
            $servicegroupIds,
            'list',
            'id',
            $GenericFilter->genericFilters()
        );

        $reorderServicesArray = [];
        foreach ($excludedServices as $service) {
            if (!isset($reorderServicesArray[$service['_matchingData']['Hosts']['name']])) {
                $reorderServicesArray[$service['_matchingData']['Hosts']['name']] = [
                    'label' => $service['_matchingData']['Hosts']['name'],
                    'value' => $service['_matchingData']['Hosts']['id'],
                    'items' => []
                ];
            }
            $serviceName = $service['servicename'];
            if ($service['disabled'] == 1) {
                $serviceName .= '🔌';
            }
            $reorderServicesArray[$service['_matchingData']['Hosts']['name']]['items'][] = [
                'label'    => $serviceName,
                'value'    => $service['id'],
                'disabled' => ($service['disabled'] == 1) // if needed can be configured by parameter
            ];
        }


        $reorderServicesArray = Hash::sort($reorderServicesArray, '{s}', 'asc', 'natural');
        $excludedServices = array_values($reorderServicesArray);

        $this->set(compact(['excludedServices']));
        $this->viewBuilder()->setOption('serialize', ['excludedServices']);
    }


    /**
     * @return void
     * @deprecated
     */
    public function loadExcludedServicesByContainerIdAndServicegroupIds() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId');
        $servicegroupIds = $this->request->getQuery('servicegroupIds');

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');


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


        $excludedServices = $ServicesTable->getServicesByContainerIdAndServicegroupIds($containerIds, $servicegroupIds, 'list', 'id');
        $excludedServices = Api::makeItJavaScriptAble($excludedServices);


        $this->set(compact(['excludedServices']));
        $this->viewBuilder()->setOption('serialize', ['excludedServices']);
    }

    public function loadExcludedServicegroupsByContainerIdAndServiceIds() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId');
        $serviceIds = $this->request->getQuery('serviceIds');

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');


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
        $excludedServicegroups = $ServicegroupsTable->getServicegroupsByContainerIdAndServiceIds($containerIds, $serviceIds, 'list', 'id');
        $excludedServicegroups = Api::makeItJavaScriptAble($excludedServicegroups);


        $this->set(compact(['excludedServicegroups']));
        $this->viewBuilder()->setOption('serialize', ['excludedServicegroups']);
    }
}
