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

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplategroupsTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\Cache\Cache;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\Comparison\ServiceComparisonForSave;
use itnovum\openITCOCKPIT\Core\HostgroupConditions;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\ServicetemplategroupsConditions;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostgroupFilter;
use itnovum\openITCOCKPIT\Filter\ServicetemplateFilter;
use itnovum\openITCOCKPIT\Filter\ServicetemplategroupsFilter;

/**
 * Class ServicetemplategroupsController
 * @package App\Controller
 */
class ServicetemplategroupsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        $ServicetemplategroupsFilter = new ServicetemplategroupsFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServicetemplategroupsFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $servicetemplategroups = $ServicetemplategroupsTable->getServicetemplategroupsIndex($ServicetemplategroupsFilter, $PaginateOMat, $MY_RIGHTS);

        $all_servicetemplategroups = [];
        foreach ($servicetemplategroups as $servicetemplategroup) {
            $servicetemplategroup['allow_edit'] = $this->hasPermission('edit', 'servicetemplategroups');
            if ($this->hasRootPrivileges === false) {
                $servicetemplategroup['allow_edit'] = $this->isWritableContainer($servicetemplategroup['container']['parent_id']);
            }
            $all_servicetemplategroups[] = $servicetemplategroup;
        }

        $this->set('all_servicetemplategroups', $all_servicetemplategroups);
        $this->viewBuilder()->setOption('serialize', ['all_servicetemplategroups']);
    }

    /**
     * @param int|null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        if (!$ServicetemplategroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service template group'));
        }

        $servicetemplategroup = $ServicetemplategroupsTable->getServicetemplategroupForView($id);
        if (!$this->allowedByContainerId($servicetemplategroup->get('Containers')['parent_id'])) {
            $this->render403();
            return;
        }

        $this->set('servicetemplategroup', $servicetemplategroup);
        $this->viewBuilder()->setOption('serialize', ['servicetemplategroup']);
    }

    /**
     * @throws \Exception
     */
    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');


        $servicetemplategroup = $ServicetemplategroupsTable->newEmptyEntity();
        $servicetemplategroup = $ServicetemplategroupsTable->patchEntity($servicetemplategroup, $this->request->getData('Servicetemplategroup'));
        $servicetemplategroup->set('uuid', UUID::v4());
        $servicetemplategroup->get('container')->set('containertype_id', CT_SERVICETEMPLATEGROUP);

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $ContainersTable->acquireLock();

        $User = new User($this->getUser());
        $requestData = $this->request->getData();

        $servicetemplategroup = $ServicetemplategroupsTable->createServicetemplategroup($servicetemplategroup, $requestData, $User->getId());

        if ($servicetemplategroup->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $servicetemplategroup->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        } else {
            //No errors

            if ($this->isJsonRequest()) {
                $this->serializeCake4Id($servicetemplategroup); // REST API ID serialization
                return;
            }
        }
        $this->set('servicetemplategroup', $servicetemplategroup);
        $this->viewBuilder()->setOption('serialize', ['servicetemplategroup']);
    }

    /**
     * @param int|null $id
     * @throws \Exception
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        if (!$ServicetemplategroupsTable->existsById($id)) {
            throw new NotFoundException(__('Service template group not found'));
        }

        $servicetemplategroup = $ServicetemplategroupsTable->getServicetemplategroupForEdit($id);
        $servicetemplategroupForChangeLog = $servicetemplategroup;

        if (!$this->isWritableContainer($servicetemplategroup['Servicetemplategroup']['container']['parent_id'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return service template group information
            $this->set('servicetemplategroup', $servicetemplategroup);
            $this->viewBuilder()->setOption('serialize', ['servicetemplategroup']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Update service template group data
            $User = new User($this->getUser());

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $servicetemplategroupEntity = $ServicetemplategroupsTable->get($id, contain: [
                'Containers'
            ]);
            $servicetemplategroupEntity->setAccess('uuid', false);
            $servicetemplategroupEntity = $ServicetemplategroupsTable->patchEntity($servicetemplategroupEntity, $this->request->getData('Servicetemplategroup'));
            $servicetemplategroupEntity->id = $id;

            $requestData = $this->request->getData();

            $servicetemplategroupEntity = $ServicetemplategroupsTable->updateServicetemplategroup(
                $servicetemplategroupEntity,
                $requestData,
                $servicetemplategroupForChangeLog,
                $User->getId()
            );

            if ($servicetemplategroupEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $servicetemplategroupEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($servicetemplategroupEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('servicetemplategroup', $servicetemplategroupEntity);
            $this->viewBuilder()->setOption('serialize', ['servicetemplategroup']);
        }
    }

    public function append() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        if ($this->request->is('post')) {
            $id = $this->request->getData('Servicetemplategroup.id');
            $servicetemplateIds = $this->request->getData('Servicetemplategroup.servicetemplates._ids');
            if (!is_array($servicetemplateIds)) {
                $servicetemplateIds = [$servicetemplateIds];
            }

            if (empty($servicetemplateIds)) {
                //No service templates to add
                return;
            }

            /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
            $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');
            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            /** @var $ServicetemplatesTable ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

            if (!$ServicetemplategroupsTable->existsById($id)) {
                throw new NotFoundException(__('Invalid service template group'));
            }

            $ContainersTable->acquireLock();

            $servicetemplategroup = $ServicetemplategroupsTable->getServicetemplategroupForEdit($id);
            $servicetemplategroupForChangelog = $servicetemplategroup;
            if (!$this->allowedByContainerId($servicetemplategroup['Servicetemplategroup']['container']['parent_id'])) {
                $this->render403();
                return;
            }

            //Merge new service templates with existing service templates from service template group
            $servicetemplateIds = array_unique(array_merge(
                $servicetemplategroup['Servicetemplategroup']['servicetemplates']['_ids'],
                $servicetemplateIds
            ));

            $containerId = $servicetemplategroup['Servicetemplategroup']['container']['parent_id'];

            if ($containerId == ROOT_CONTAINER) {
                //Don't panic! Only root users can edit /root objects ;)
                //So no loss of selected service templates
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

            $servicetemplatesIdsToSave = [];
            foreach ($servicetemplateIds as $servicetemplateId) {
                $servicetemplate = $ServicetemplatesTable->find()
                    ->select([
                        'Servicetemplates.id',
                        'Servicetemplates.container_id'
                    ])
                    ->where([
                        'Servicetemplates.id' => $servicetemplateId
                    ])
                    ->firstOrFail();

                if (in_array($servicetemplate->get('container_id'), $containerIds, true)) {
                    $servicetemplatesIdsToSave[] = $servicetemplateId;
                }
            }


            $User = new User($this->getUser());
            $servicetemplategroupEntity = $ServicetemplategroupsTable->get($id);

            $servicetemplategroupEntity->setAccess('uuid', false);
            $servicetemplategroupEntity = $ServicetemplategroupsTable->patchEntity($servicetemplategroupEntity, [
                'servicetemplates' => [
                    '_ids' => $servicetemplatesIdsToSave
                ]
            ]);
            $servicetemplategroupEntity->id = $id;
            $ServicetemplategroupsTable->save($servicetemplategroupEntity);
            if ($servicetemplategroupEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $servicetemplategroupEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                $fakeRequest = [
                    'Servicetemplategroup' => [
                        'description'      => $servicetemplategroup['Servicetemplategroup']['description'],
                        'servicetemplates' => [
                            '_ids' => $servicetemplatesIdsToSave
                        ]
                    ]
                ];

                //No errors
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'servicetemplategroups',
                    $servicetemplategroupEntity->id,
                    OBJECT_SERVICETEMPLATEGROUP,
                    $servicetemplategroup['Servicetemplategroup']['container']['parent_id'],
                    $User->getId(),
                    $servicetemplategroup['Servicetemplategroup']['container']['name'],
                    array_merge($ServicetemplategroupsTable->resolveDataForChangelog($fakeRequest), $fakeRequest),
                    array_merge($ServicetemplategroupsTable->resolveDataForChangelog($servicetemplategroupForChangelog), $servicetemplategroupForChangelog)
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($servicetemplategroupEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('servicetemplategroup', $servicetemplategroupEntity);
            $this->viewBuilder()->setOption('serialize', ['servicetemplategroup']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        if (!$ServicetemplategroupsTable->existsById($id)) {
            throw new NotFoundException(__('Service template group not found'));
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $ContainersTable->acquireLock();

        $servicetemplategroupEntity = $ServicetemplategroupsTable->get($id, contain: [
            'Containers'
        ]);

        if (!$this->isWritableContainer($servicetemplategroupEntity->get('container')->get('parent_id'))) {
            $this->render403();
            return;
        }

        $container = $ContainersTable->get($servicetemplategroupEntity->get('container')->get('id'), contain: [
            'Servicetemplategroups'
        ]);
        if ($ContainersTable->allowDelete($container->id, CT_SERVICETEMPLATEGROUP)) {
            if ($ContainersTable->delete($container)) {
                $User = new User($this->getUser());
                Cache::clear('permissions');
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'delete',
                    'servicetemplategroups',
                    $id,
                    OBJECT_SERVICETEMPLATEGROUP,
                    $servicetemplategroupEntity->get('container')->get('parent_id'),
                    $User->getId(),
                    $servicetemplategroupEntity->get('container')->get('name'),
                    $servicetemplategroupEntity->toArray()
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

    public function copy() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var ServicetemplategroupsTable $ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $servicetemplategroups = $ServicetemplategroupsTable->getServicetemplategroupsForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('servicetemplategroups', $servicetemplategroups);
            $this->viewBuilder()->setOption('serialize', ['servicetemplategroups']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $postData = $this->request->getData('data');
            $User = new User($this->getUser());
            $userId = $User->getId();

            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            foreach ($postData as $index => $servicetemplategroupData) {
                if (!isset($servicetemplategroupData['Servicetemplategroup']['id'])) {
                    //Create/clone service template group
                    $sourceServicetemplategroupId = $servicetemplategroupData['Source']['id'];
                    $sourceServicetemplategroup = $ServicetemplategroupsTable->getSourceServicetemplategroupForCopy($sourceServicetemplategroupId, $MY_RIGHTS);

                    $newServicetemplategroupData = [
                        'description'      => $servicetemplategroupData['Servicetemplategroup']['description'],
                        'uuid'             => UUID::v4(),
                        'container'        => [
                            'name'             => $servicetemplategroupData['Servicetemplategroup']['container']['name'],
                            'containertype_id' => CT_SERVICETEMPLATEGROUP,
                            'parent_id'        => $sourceServicetemplategroup['container']['parent_id']
                        ],
                        'servicetemplates' => [
                            '_ids' => $sourceServicetemplategroup['servicetemplates']['_ids']
                        ]
                    ];

                    $newServicetemplategroupEntity = $ServicetemplategroupsTable->newEntity($newServicetemplategroupData);

                }

                $action = 'copy';
                if (isset($servicetemplategroupData['Servicetemplategroup']['id'])) {
                    //Update existing servicetemplategroup
                    //This happens, if a user copy multiple servicetemplategroup, and one run into an validation error
                    //All servicetemplategroup without validation errors got already saved to the database
                    $newServicetemplategroupEntity = $ServicetemplategroupsTable->get($servicetemplategroupData['Servicetemplategroup']['id'], contain: [
                        'Containers'
                    ]);
                    $newServicetemplategroupEntity->setAccess('*', false);
                    $newServicetemplategroupEntity->setAccess('description', true);
                    $newServicetemplategroupEntity->container->setAccess('*', false);
                    $newServicetemplategroupEntity->container->setAccess('name', true);
                    $newServicetemplategroupEntity = $ServicetemplategroupsTable->patchEntity($newServicetemplategroupEntity, $servicetemplategroupData['Servicetemplategroup']);
                    $newServicetemplategroupData = $newServicetemplategroupEntity->toArray();
                    $action = 'edit';
                }
                $ServicetemplategroupsTable->save($newServicetemplategroupEntity);

                $postData[$index]['Error'] = [];
                if ($newServicetemplategroupEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newServicetemplategroupEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Servicetemplategroup']['id'] = $newServicetemplategroupEntity->get('id');

                    /** @var  ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        'servicetemplategroups',
                        $postData[$index]['Servicetemplategroup']['id'],
                        OBJECT_SERVICETEMPLATEGROUP,
                        $newServicetemplategroupEntity->get('container')->get('parent_id'),
                        $userId,
                        $newServicetemplategroupEntity->get('container')->get('name'),
                        ['Servicetemplategroup' => $newServicetemplategroupData]
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

    /********************************
     *      ALLOCATION METHODS      *
     ********************************/

    /**
     * @param int|null $servicetemplategroupId
     */
    public function allocateToHost($servicetemplategroupId = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$ServicetemplategroupsTable->existsById($servicetemplategroupId)) {
            throw new NotFoundException('Invalid service template group');
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return list of services that will be created by the system

            $hostId = $this->request->getQuery('hostId');
            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException('Invalid host');
            }

            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $hostPrimaryContainerId = $HostsTable->getHostPrimaryContainerIdByHostId($hostId);
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($hostPrimaryContainerId);

            //Add the tenant container id to include timeperiod and service templates etc from the tenant (oITC V2 legacy)
            if ($hostPrimaryContainerId != ROOT_CONTAINER) {
                $path = $ContainersTable->getPathById($hostPrimaryContainerId);
                if (isset($path[1]) && $path[1]['containertype_id'] == CT_TENANT) {
                    $tenantContainerId = $path[1]['id'];
                    $containerIds[] = $tenantContainerId;
                }
            }

            $servicetemplategroup = $ServicetemplategroupsTable->getServicetemplatesforAllocation($servicetemplategroupId, $containerIds);
            $targetHostwithServices = $HostsTable->getServicesForServicetemplateAllocation($hostId);

            $servicetemplatesForDeploy = [];
            foreach ($servicetemplategroup['servicetemplates'] as $servicetemplate) {
                $doesServicetemplateExistsOnTargetHost = false;
                $doesServicetemplateExistsOnTargetHostAndIsDisabled = false;
                $createServiceOnTargetHost = true;

                $doesServicetemplateExistsOnTargetHost = isset($targetHostwithServices['services'][$servicetemplate['id']]);
                if (isset($targetHostwithServices['services'][$servicetemplate['id']])) {
                    $doesServicetemplateExistsOnTargetHostAndIsDisabled = (bool)$targetHostwithServices['services'][$servicetemplate['id']]['disabled'];
                }

                if ($doesServicetemplateExistsOnTargetHost || $doesServicetemplateExistsOnTargetHostAndIsDisabled) {
                    $createServiceOnTargetHost = false;
                }

                $servicetemplatesForDeploy[] = [
                    'servicetemplate'                                    => $servicetemplate,
                    'doesServicetemplateExistsOnTargetHost'              => $doesServicetemplateExistsOnTargetHost,
                    'doesServicetemplateExistsOnTargetHostAndIsDisabled' => $doesServicetemplateExistsOnTargetHostAndIsDisabled,
                    'createServiceOnTargetHost'                          => $createServiceOnTargetHost
                ];
            }

            $this->set('servicetemplatesForDeploy', $servicetemplatesForDeploy);
            $this->viewBuilder()->setOption('serialize', ['servicetemplatesForDeploy']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Create the services out of the service template group on the selected host

            $hostId = $this->request->getData('Host.id');
            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException('Invalid host');
            }

            $servicetemplateIds = $this->request->getData('Servicetemplates._ids');
            if (empty($servicetemplateIds)) {
                //No service templates selected...
                $this->set('success', false);
                $this->set('message', __('No service template ids set'));
                $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                return;
            }

            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            $User = new User($this->getUser());

            $result = $ServicesTable->createServiceByServicetemplateIds($servicetemplateIds, $hostId, $User->getId());
            $newServiceIds = $result['newServiceIds'];
            $errors = $result['errors'];

            $this->set('success', true);
            $this->set('services', ['_ids' => $newServiceIds]);
            $this->set('hostId', $hostId);
            $this->set('errors', $errors);
            $this->viewBuilder()->setOption('serialize', ['success', 'services', 'hostId', 'errors']);
            return;
        }
    }

    /**
     * @param int|null $servicetemplategroupId
     */
    public function allocateToHostgroup($servicetemplategroupId = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        if (!$ServicetemplategroupsTable->existsById($servicetemplategroupId)) {
            throw new NotFoundException('Invalid service template group');
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return list of services that will be created by the system

            $hostgroupId = $this->request->getQuery('hostgroupId');
            if (!$HostgroupsTable->existsById($hostgroupId)) {
                throw new NotFoundException('Invalid host group');
            }

            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($hostgroupId);

            $hosts = [];
            foreach ($hostIds as $hostId) {
                $host = $HostsTable->get($hostId);
                $viewHost = new Host($host->toArray());
                if ($viewHost->isDisabled()) {
                    continue;
                }

                $containerIds = $ContainersTable->resolveChildrenOfContainerIds($viewHost->getContainerId());

                //Add the tenant container id to include timeperiod and service templates etc from the tenant (oITC V2 legacy)
                if ($viewHost->getContainerId() != ROOT_CONTAINER) {
                    $path = $ContainersTable->getPathById($viewHost->getContainerId());
                    if (isset($path[1]) && $path[1]['containertype_id'] == CT_TENANT) {
                        $tenantContainerId = $path[1]['id'];
                        $containerIds[] = $tenantContainerId;
                    }
                }

                $servicetemplategroup = $ServicetemplategroupsTable->getServicetemplatesforAllocation($servicetemplategroupId, $containerIds);

                $targetHostwithServices = $HostsTable->getServicesForServicetemplateAllocation($hostId);

                $servicetemplatesForDeploy = [];
                $areAllCreateServiceOnTargetHostTrue = true;
                foreach ($servicetemplategroup['servicetemplates'] as $servicetemplate) {
                    $doesServicetemplateExistsOnTargetHost = false;
                    $doesServicetemplateExistsOnTargetHostAndIsDisabled = false;
                    $createServiceOnTargetHost = true;

                    $doesServicetemplateExistsOnTargetHost = isset($targetHostwithServices['services'][$servicetemplate['id']]);
                    if (isset($targetHostwithServices['services'][$servicetemplate['id']])) {
                        $doesServicetemplateExistsOnTargetHostAndIsDisabled = (bool)$targetHostwithServices['services'][$servicetemplate['id']]['disabled'];
                    }

                    if ($doesServicetemplateExistsOnTargetHost || $doesServicetemplateExistsOnTargetHostAndIsDisabled) {
                        $createServiceOnTargetHost = false;
                        $areAllCreateServiceOnTargetHostTrue = false;
                    }

                    $servicetemplatesForDeploy[] = [
                        'servicetemplate'                                    => $servicetemplate,
                        'doesServicetemplateExistsOnTargetHost'              => $doesServicetemplateExistsOnTargetHost,
                        'doesServicetemplateExistsOnTargetHostAndIsDisabled' => $doesServicetemplateExistsOnTargetHostAndIsDisabled,
                        'createServiceOnTargetHost'                          => $createServiceOnTargetHost
                    ];
                }

                $hosts[] = [
                    'host'                                => $viewHost->toArray(),
                    'areAllCreateServiceOnTargetHostTrue' => $areAllCreateServiceOnTargetHostTrue,
                    'services'                            => $servicetemplatesForDeploy
                ];
            }

            $this->set('hostsWithServicetemplatesForDeploy', $hosts);
            $this->viewBuilder()->setOption('serialize', ['hostsWithServicetemplatesForDeploy']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Create the services out of the service template group on the selected host

            $hostId = $this->request->getData('Host.id');
            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException('Invalid host');
            }

            $servicetemplateIds = $this->request->getData('Servicetemplates._ids');
            if (empty($servicetemplateIds)) {
                //No service templates selected...
                $this->set('success', false);
                $this->set('message', __('No service template ids set'));
                $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                return;
            }

            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            $User = new User($this->getUser());

            $result = $ServicesTable->createServiceByServicetemplateIds($servicetemplateIds, $hostId, $User->getId());
            $newServiceIds = $result['newServiceIds'];
            $errors = $result['errors'];


            $this->set('success', true);
            $this->set('services', ['_ids' => $newServiceIds]);
            $this->set('hostId', $hostId);
            $this->set('errors', $errors);
            $this->viewBuilder()->setOption('serialize', ['success', 'services', 'hostId', 'errors']);
            return;
        }
    }

    /**
     * @param int|null $servicetemplategroupId
     */
    public function allocateToMatchingHostgroup($servicetemplategroupId = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $User = new User($this->getUser());

        $ServicetemplateCache = new KeyValueStore();
        $ObjectsCacheChangelog = new ObjectsCache();

        if (!$ServicetemplategroupsTable->existsById($servicetemplategroupId)) {
            throw new NotFoundException('Invalid service template group');
        }

        $servicetemplategroupName = $ServicetemplategroupsTable->getServicetemplategroupNameById($servicetemplategroupId);

        //Find matching host group
        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        try {
            $hostgroup = $HostgroupsTable->getHostgroupByName($servicetemplategroupName, $MY_RIGHTS);
        } catch (RecordNotFoundException $e) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('message', __('No matching host group "{0}" found.', $servicetemplategroupName));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        $hostgroupId = $hostgroup->get('id');

        //Gather list of services that will be created by the system
        if (!$HostgroupsTable->existsById($hostgroupId)) {
            throw new NotFoundException('Invalid host group');
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $hostIds = $HostgroupsTable->getHostIdsByHostgroupId($hostgroupId);

        $newServiceIds = [];
        $errors = [];
        foreach ($hostIds as $hostId) {
            $host = $HostsTable->get($hostId);
            $viewHost = new Host($host->toArray());
            if ($viewHost->isDisabled()) {
                continue;
            }

            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($viewHost->getContainerId());

            //Add the tenant container id to include timeperiod and service templates etc from the tenant (oITC V2 legacy)
            if ($viewHost->getContainerId() != ROOT_CONTAINER) {
                $path = $ContainersTable->getPathById($viewHost->getContainerId());
                if (isset($path[1]) && $path[1]['containertype_id'] == CT_TENANT) {
                    $tenantContainerId = $path[1]['id'];
                    $containerIds[] = $tenantContainerId;
                }
            }

            $hostContactsAndContactgroupsById = $HostsTable->getContactsAndContactgroupsById($host->get('id'));
            $hosttemplateContactsAndContactgroupsById = $HosttemplatesTable->getContactsAndContactgroupsById($host->get('hosttemplate_id'));

            $servicetemplategroup = $ServicetemplategroupsTable->getServicetemplatesforAllocation($servicetemplategroupId, $containerIds);
            $targetHostwithServices = $HostsTable->getServicesForServicetemplateAllocation($hostId);

            foreach ($servicetemplategroup['servicetemplates'] as $servicetemplate) {
                $doesServicetemplateExistsOnTargetHost = false;
                $doesServicetemplateExistsOnTargetHostAndIsDisabled = false;
                $createServiceOnTargetHost = true;

                $doesServicetemplateExistsOnTargetHost = isset($targetHostwithServices['services'][$servicetemplate['id']]);
                if (isset($targetHostwithServices['services'][$servicetemplate['id']])) {
                    $doesServicetemplateExistsOnTargetHostAndIsDisabled = (bool)$targetHostwithServices['services'][$servicetemplate['id']]['disabled'];
                }

                if ($doesServicetemplateExistsOnTargetHost || $doesServicetemplateExistsOnTargetHostAndIsDisabled) {
                    $createServiceOnTargetHost = false;
                }

                if ($createServiceOnTargetHost === true) {
                    //Create the Service

                    $servicetemplateId = $servicetemplate['id'];

                    if (!$ServicetemplateCache->has($servicetemplateId)) {
                        $ServicetemplateCache->set($servicetemplateId, $ServicetemplatesTable->getServicetemplateForDiff($servicetemplateId));
                    }

                    $servicetemplate = $ServicetemplateCache->get($servicetemplateId);
                    $servicename = $servicetemplate['Servicetemplate']['name'];

                    $serviceData = ServiceComparisonForSave::getServiceSkeleton($hostId, $servicetemplateId);
                    $ServiceComparisonForSave = new ServiceComparisonForSave(
                        ['Service' => $serviceData],
                        $servicetemplate,
                        $hostContactsAndContactgroupsById,
                        $hosttemplateContactsAndContactgroupsById
                    );
                    $serviceData = $ServiceComparisonForSave->getDataForSaveForAllFields();
                    $serviceData['uuid'] = UUID::v4();

                    //Add required fields for validation
                    $serviceData['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
                    $serviceData['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
                    $serviceData['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
                    $serviceData['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
                    $serviceData['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

                    $service = $ServicesTable->newEntity($serviceData);

                    $ServicesTable->save($service);
                    if ($service->hasErrors()) {
                        $errors[] = $service->getErrors();
                    } else {
                        //No errors

                        $extDataForChangelog = $ServicesTable->resolveDataForChangelog(['Service' => $serviceData], $ObjectsCacheChangelog);
                        /** @var  ChangelogsTable $ChangelogsTable */
                        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                        $changelog_data = $ChangelogsTable->parseDataForChangelog(
                            'add',
                            'services',
                            $service->get('id'),
                            OBJECT_SERVICE,
                            $host->get('container_id'),
                            $User->getId(),
                            $host->get('name') . '/' . $servicename,
                            array_merge(['Service' => $serviceData], $extDataForChangelog)
                        );

                        if ($changelog_data) {
                            /** @var Changelog $changelogEntry */
                            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                            $ChangelogsTable->save($changelogEntry);
                        }

                        $newServiceIds[] = $service->get('id');
                    }


                }

            }
        }

        $this->set('success', true);
        $this->set('services', ['_ids' => $newServiceIds]);
        $this->set('message', __('Created {0} new services', sizeof($newServiceIds)));
        $this->set('errors', $errors);
        $this->viewBuilder()->setOption('serialize', ['success', 'services', 'errors', 'message']);
    }


    /****************************
     *       AJAX METHODS       *
     ****************************/

    /**
     * @throws \Exception
     */
    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_SERVICETEMPLATEGROUP, [], $this->hasRootPrivileges);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_SERVICETEMPLATEGROUP, [], $this->hasRootPrivileges);
        }

        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
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


    public function loadServicetemplategroupsByString() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $selected = $this->request->getQuery('selected');

        $ServicetemplategroupsFilter = new ServicetemplategroupsFilter($this->request);

        $ServicetemplategroupsConditions = new ServicetemplategroupsConditions($ServicetemplategroupsFilter->indexFilter());
        $ServicetemplategroupsConditions->setContainerIds($this->MY_RIGHTS);

        /** @var $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        $servicetemplategroups = Api::makeItJavaScriptAble(
            $ServicetemplategroupsTable->getServicetemplategroupsForAngular($ServicetemplategroupsConditions, $selected)
        );

        $this->set('servicetemplategroups', $servicetemplategroups);
        $this->viewBuilder()->setOption('serialize', ['servicetemplategroups']);
    }

    public function loadHostgroupsByString() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $selected = $this->request->getQuery('selected');

        $HostgroupFilter = new HostgroupFilter($this->request);

        $HostgroupCondition = new HostgroupConditions($HostgroupFilter->indexFilter());

        //Only get host groups where the user has write permissions
        $HostgroupCondition->setContainerIds($this->getWriteContainers());


        $hostgroups = Api::makeItJavaScriptAble(
            $HostgroupsTable->getHostgroupsForAngular($HostgroupCondition, $selected)
        );

        $this->set('hostgroups', $hostgroups);
        $this->viewBuilder()->setOption('serialize', ['hostgroups']);
    }
}
