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

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\TenantsTable;
use Cake\Cache\Cache;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\TenantFilter;


/**
 * Class TenantsController
 * @package App\Controller
 */
class TenantsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $TenantsTable TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');
        $TenantFilter = new TenantFilter($this->request);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $TenantFilter->getPage());
        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $all_tenants = $TenantsTable->getTenantsIndex($TenantFilter, $PaginateOMat, $MY_RIGHTS);

        foreach ($all_tenants as $key => $tenant) {
            $all_tenants[$key]['Tenant']['allowEdit'] = false;
            $tenantContainerId = $tenant['Tenant']['container_id'];
            if (isset($this->MY_RIGHTS_LEVEL[$tenantContainerId])) {
                if ((int)$this->MY_RIGHTS_LEVEL[$tenantContainerId] === WRITE_RIGHT) {
                    $all_tenants[$key]['Tenant']['allowEdit'] = true;
                }
            }
        }

        $this->set('all_tenants', $all_tenants);
        $this->set('hasRootPrivileges', $this->hasRootPrivileges);
        $this->viewBuilder()->setOption('serialize', ['all_tenants', 'hasRootPrivileges']);
    }

    /**
     * @param $id
     */
    public function view($id) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $TenantsTable TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');

        if (!$TenantsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid tenant'));
        }

        $tenant = $TenantsTable->getTenantById($id);

        if (!$this->allowedByContainerId($tenant['container_id'])) {
            $this->render403();
            return;
        }

        $this->set('tenant', $tenant);
        $this->viewBuilder()->setOption('serialize', ['tenant']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if (!$this->hasRootPrivileges) {
            // Tenants are always create in the root container so only root users are allowed to create tenants
            $this->render403();
            return;
        }

        /** @var $TenantsTable TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $tenant = $TenantsTable->newEmptyEntity();
            $tenant = $TenantsTable->patchEntity($tenant, $this->request->getData());

            $tenant->container->parent_id = ROOT_CONTAINER;
            $tenant->container->containertype_id = CT_TENANT;

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $TenantsTable->save($tenant);
            if ($tenant->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $tenant->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                $User = new User($this->getUser());

                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'add',
                    'tenants',
                    $tenant->get('id'),
                    OBJECT_TENANT,
                    [ROOT_CONTAINER, $tenant->get('container_id')],
                    $User->getId(),
                    $tenant->container->name,
                    [
                        'tenant' => $tenant->toArray()
                    ]
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                Cache::clear('permissions');

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($tenant); // REST API ID serialization
                    return;
                }
            }
            $this->set('tenant', $tenant);
            $this->viewBuilder()->setOption('serialize', ['tenant']);
        }
    }

    /**
     * @param null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $TenantsTable TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');

        if (!$TenantsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid tenant'));
        }

        if ($this->request->is('get')) {
            $tenant = $TenantsTable->getTenantById($id);

            if (!$this->allowedByContainerId($tenant['container_id'])) {
                $this->render403();
                return;
            }

            $this->set('tenant', $tenant);
            $this->viewBuilder()->setOption('serialize', ['tenant']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $oldTenant = $TenantsTable->get($id, contain: ['Containers']);
            $oldTenantForChangelog = $oldTenant->toArray();
            if (!$this->allowedByContainerId($oldTenant->get('container_id'))) {
                $this->render403();
                return;
            }

            $tenant = $TenantsTable->patchEntity($oldTenant, $this->request->getData());

            $tenant->container_id = $oldTenant->get('container_id');
            $tenant->container->id = $oldTenant->get('container_id');
            $tenant->container->parent_id = ROOT_CONTAINER;
            $tenant->container->containertype_id = CT_TENANT;

            $TenantsTable->save($tenant);
            if ($tenant->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $tenant->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                $User = new User($this->getUser());
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'tenants',
                    $tenant->get('id'),
                    OBJECT_TENANT,
                    [ROOT_CONTAINER, $tenant->get('container_id')],
                    $User->getId(),
                    $tenant->container->name,
                    [
                        'tenant' => $tenant->toArray()
                    ],
                    [
                        'tenant' => $oldTenantForChangelog
                    ]
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                Cache::clear('permissions');

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($tenant); // REST API ID serialization
                    return;
                }
            }
            $this->set('tenant', $tenant);
            $this->viewBuilder()->setOption('serialize', ['tenant']);
        }
    }

    /**
     * @param null $id
     */
    public function delete($containerId = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var TenantsTable $TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');


        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $ContainersTable->acquireLock();

        try {
            $tenant = $TenantsTable->getTenantByContainerId($containerId);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('Invalid tenant'));
        }
        $tenantForChangelog = $tenant;

        if (!$this->allowedByContainerId($tenant['container']['id'])) {
            $this->render403();
            return;
        }


        if ($ContainersTable->allowDelete($tenant['container']['id'], CT_TENANT)) {

            if ($ContainersTable->delete($ContainersTable->get($tenant['container']['id']))) {
                Cache::clear('permissions');

                $User = new User($this->getUser());
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'delete',
                    'tenants',
                    $containerId,
                    OBJECT_TENANT,
                    [ROOT_CONTAINER, $tenantForChangelog['container_id']],
                    $User->getId(),
                    $tenantForChangelog['container']['name'],
                    []
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                $this->set('message', __('Tenant deleted successfully'));
                $this->viewBuilder()->setOption('serialize', ['message']);
                return;
            }
            $this->response = $this->response->withStatus(400);
            $this->set('message', __('Could not delete tenant'));
            $this->viewBuilder()->setOption('serialize', ['message']);
        }


        //both types must be host, otherwise the serviceUsedBy site with the host id will be displayed which results in an error
        $usedBy = [
            [
                'baseUrl' => '#',
                'state'   => 'ContainersShowDetails',
                'message' => __('Used by other objects'),
                'module'  => 'Core'
            ]
        ];
        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('id', $containerId);
        $this->set('message', __('Issue while deleting tenant'));
        $this->set('usedBy', $usedBy);
        $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
    }
}
