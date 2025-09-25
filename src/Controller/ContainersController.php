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
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Changelog;
use App\Model\Entity\Container;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContactgroupsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\LocationsTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\ServicetemplategroupsTable;
use App\Model\Table\TenantsTable;
use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DistributeModule\Model\Table\SatellitesTable;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use ScmModule\Model\Table\ResourcegroupsTable;


/**
 * Class ContainersController
 * @property Container $Container
 */
class ContainersController extends AppController {

    public function index() {
        return;
    }


    /**
     * @param null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$ContainersTable->existsById($id)) {
            throw new NotFoundException(__('Invalid container'));
        }
        $container = $ContainersTable->get($id);
        if (!$this->allowedByContainerId($container->get('id'))) {
            throw new ForbiddenException('403 Forbidden');
        }

        $this->set('container', $container);
        $this->viewBuilder()->setOption('serialize', ['container']);
    }


    public function add() {
        if ($this->request->is('GET')) {
            //Only ship HTML Template
            return;
        }

        if (!$this->request->is('post') && !$this->request->is('put')) {
            throw new MethodNotAllowedException();
        }

        if (!$this->isJsonRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $container = $ContainersTable->newEntity($this->request->getData('Container'));

        if (!$this->isWritableContainer($container->get('parent_id'))) {
            $this->render403();
            return;
        }

        $ContainersTable->acquireLock();

        $ContainersTable->save($container);
        if ($container->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->serializeCake4ErrorMessage($container);
            return;
        } else {
            $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

            /** @var  ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'add',
                'containers',
                $container->get('id'),
                OBJECT_NODE,
                [$container->get('parent_id'), $container->get('id')],
                $User->getId(),
                $container->get('name'),
                [
                    'container' => $container->toArray()
                ]
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);
            }
        }

        Cache::clear('permissions');
        $this->serializeCake4Id($container);
    }

    public function edit() {
        if (!$this->isAngularJsRequest()) {
            return;
        }
        if ($this->request->is('post')) {

            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            $containerId = (int)$this->request->getData('Container.id', 0);

            if (!$ContainersTable->existsById($containerId)) {
                throw new NotFoundException(__('Invalid container'));
            }

            $ContainersTable->acquireLock();

            $container = $ContainersTable->get($containerId);
            $containerForChangelog = $container->toArray();

            if (!$this->isWritableContainer($container->get('id'))) {
                $this->render403();
                return;
            }

            $container->setAccess('id', false);
            $container->setAccess('parent_id', false);
            $container = $ContainersTable->patchEntity($container, $this->request->getData('Container'));

            $ContainersTable->save($container);
            if ($container->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->serializeCake4ErrorMessage($container);
                return;
            } else {
                $User = new User($this->getUser());
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');
                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'containers',
                    $container->get('id'),
                    OBJECT_NODE,
                    [$container->get('parent_id'), $container->get('id')],
                    $User->getId(),
                    $container->get('name'),
                    [
                        'container' => $container->toArray()
                    ],
                    [
                        'container' => $containerForChangelog
                    ]
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }
            }

            Cache::clear('permissions');
            $this->serializeCake4Id($container);
        }
    }

    /**
     * THIS IS ONLY FOR ContainersIndex Action !!!
     */
    public function loadContainersByContainerId($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        if (!$this->hasRootPrivileges && intval($id) === ROOT_CONTAINER) {
            $this->render403();
            return;
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var  $TenantsTable TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');
        /** @var  $LocationsTable LocationsTable */
        $LocationsTable = TableRegistry::getTableLocator()->get('Locations');
        /** @var  $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var  $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var  $ServicegroupsTable ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');
        /** @var  $ServicetemplategroupsTable ServicetemplategroupsTable */
        $ServicetemplategroupsTable = TableRegistry::getTableLocator()->get('Servicetemplategroups');

        if (Plugin::isLoaded('ScmModule')) {
            /** @var  $ResourcegroupsTable ResourcegroupsTable */
            $ResourcegroupsTable = TableRegistry::getTableLocator()->get('ScmModule.Resourcegroups');
        }

        if (!$ContainersTable->existsById($id)) {
            throw new NotFoundException(__('Container not found'));
        }

        $parent = [$ContainersTable->get($id)->toArray()];
        $parent[0]['linkedId'] = $parent[0]['id'];
        if ($parent[0]['containertype_id'] == CT_TENANT) {
            $parent[0]['linkedId'] = $TenantsTable->getTenantIdByContainerId($parent[0]['id']);

        } else if ($parent[0]['containertype_id'] == CT_LOCATION) {
            $parent[0]['linkedId'] = $LocationsTable->getLocationIdByContainerId($parent[0]['id']);

        }
        $parent[0]['allowEdit'] = false;

        if (isset($this->MY_RIGHTS_LEVEL[$parent[0]['id']])) {
            if ((int)$this->MY_RIGHTS_LEVEL[$parent[0]['id']] === WRITE_RIGHT) {
                $parent[0]['allowEdit'] = true;
                $parent[0]['elements'] = ($parent[0]['rght'] - $parent[0]['lft']) / 2 - 0.5;
            }
        }
        $containers = $ContainersTable->getChildren($id);
        foreach ($containers as $key => $container) {
            $containers[$key]['allowEdit'] = false;
            $containerId = $container['id'];
            if (isset($this->MY_RIGHTS_LEVEL[$containerId])) {
                if ((int)$this->MY_RIGHTS_LEVEL[$containerId] === WRITE_RIGHT) {
                    $containers[$key]['allowEdit'] = true;
                    switch ($containers[$key]['containertype_id']) {
                        case CT_GLOBAL:
                            $containers[$key]['linkedId'] = ROOT_CONTAINER;
                            $containers[$key]['elements'] = ($container['rght'] - $container['lft']) / 2 - 0.5;
                            break;
                        case CT_TENANT:
                            $tenantId = $TenantsTable->getTenantIdByContainerId($container['id']);
                            $containers[$key]['linkedId'] = $tenantId;
                            $containers[$key]['elements'] = ($container['rght'] - $container['lft']) / 2 - 0.5;
                            break;
                        case CT_LOCATION:
                            $locationId = $LocationsTable->getLocationIdByContainerId($container['id']);
                            $containers[$key]['linkedId'] = $locationId;
                            $containers[$key]['elements'] = ($container['rght'] - $container['lft']) / 2 - 0.5;
                            break;
                        case CT_NODE:
                            $containers[$key]['linkedId'] = $container['id'];
                            $containers[$key]['elements'] = ($container['rght'] - $container['lft']) / 2 - 0.5;
                            break;
                        case CT_CONTACTGROUP:
                            $contactGroup = $ContactgroupsTable->getContactgroupByContainerId($container['id']);
                            if (!empty($contactGroup)) {
                                $containers[$key]['linkedId'] = $contactGroup['id'];
                                $containers[$key]['contacts'] = sizeof($contactGroup['contacts']);
                            }
                            break;
                        case CT_HOSTGROUP:
                            $hostGroup = $HostgroupsTable->getHostgroupByContainerId($container['id']);
                            if (!empty($hostGroup)) {
                                $containers[$key]['linkedId'] = $hostGroup['id'];
                                $containers[$key]['hosts'] = sizeof($hostGroup['hosts']);
                                $containers[$key]['hosttemplates'] = sizeof($hostGroup['hosttemplates']);
                            }
                            break;
                        case CT_SERVICEGROUP:
                            $serviceGroup = $ServicegroupsTable->getServicegroupByContainerId($container['id']);
                            if (!empty($serviceGroup)) {
                                $containers[$key]['linkedId'] = $serviceGroup['id'];
                                $containers[$key]['services'] = sizeof($serviceGroup['services']);
                                $containers[$key]['servicetemplates'] = sizeof($serviceGroup['servicetemplates']);
                            }
                            break;
                        case CT_SERVICETEMPLATEGROUP:
                            $serviceTemplateGroup = $ServicetemplategroupsTable->getServicetemplategroupByContainerId($container['id']);
                            if (!empty($serviceTemplateGroup)) {
                                $containers[$key]['linkedId'] = $serviceTemplateGroup['id'];
                                $containers[$key]['servicetemplates'] = sizeof($serviceTemplateGroup['servicetemplates']);
                            }
                            break;
                        //ScmModule
                        case CT_RESOURCEGROUP:
                            if (Plugin::isLoaded('ScmModule')) {
                                $resourceGroup = $ResourcegroupsTable->getResourcegroupByContainerId($container['id']);
                                if (!empty($resourceGroup)) {
                                    $containers[$key]['linkedId'] = $resourceGroup['id'];
                                    $containers[$key]['resources'] = sizeof($resourceGroup['resources']);
                                    $containers[$key]['users'] = sizeof($resourceGroup['users']);
                                }
                            }
                            break;
                    }
                }
            }
        }
        $hasChilds = true;
        if (empty($containers) && !empty($parent[0])) {
            $containers = $parent[0];
            $hasChilds = false;
        }

        //Add Container alias like in Cake2 for Hash::nest
        $cake2Containers = [];
        foreach ($containers as $container) {
            $cake2Containers[] = [
                'Container' => $container
            ];
        }

        $nest = Hash::nest($cake2Containers);

        $parent['children'] = ($hasChilds) ? $nest : [];

        //Gormat result like CakePHP2 for Frontend
        $result = [
            0 => [
                'Container' => $parent[0],
                'children'  => $parent['children']
            ]
        ];


        $this->set('nest', $result);
        $this->viewBuilder()->setOption('serialize', ['nest']);
    }


    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->find()
                ->where(['Containers.containertype_id IN' => [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE]])
                ->disableHydration()
                ->toArray();
        } else {
            $containers = $ContainersTable->find()
                ->andWhere([
                    'Containers.containertype_id IN' => [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE],
                    'Containers.id IN '              => $this->MY_RIGHTS
                ])
                ->disableHydration()
                ->toArray();
        }

        $paths = [];
        foreach ($containers as $container) {
            $paths[$container['id']] = '/' . $ContainersTable->treePath($container['id'], '/');
        }
        natcasesort($paths);
        if (!$this->hasRootPrivileges) {
            unset($paths[ROOT_CONTAINER]);
        }

        $containers = Api::makeItJavaScriptAble($paths);

        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    /**
     * Randers the selectbox with all the nodes and path of the tenant
     * ### Options
     * Please check at ContainersTable->easyPath()
     *
     * @param int $id of the tenant
     * @param array $options Array of options and HTML attributes.
     *
     * @author Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since  3.0
     */
    public function byTenantForSelect($id = null, $options = []) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $this->set('paths', $ContainersTable->easyPath($ContainersTable->resolveChildrenOfContainerIds($id), OBJECT_NODE));
        $this->viewBuilder()->setOption('serialize', ['paths']);
    }

    /**
     * @param null $id
     */
    public function delete($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $ContainersTable->acquireLock();

        $container = $ContainersTable->find()
            ->where([
                'Containers.id'    => $id,
                'Containers.id IN' => $this->getWriteContainers()
            ])
            ->first();

        if (empty($container)) {
            $this->render403();
            return;
        }
        $containerForChangelog = $container->toArray();

        //check if the current container contains subcontainers
        $deletionAllowed = $ContainersTable->allowDelete($id, $container->containertype_id);
        if ($deletionAllowed) {
            Cache::clear('permissions');
            if ($ContainersTable->delete($container)) {
                Cache::clear('permissions');

                $User = new User($this->getUser());
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'delete',
                    'containers',
                    $id,
                    OBJECT_NODE,
                    [$containerForChangelog['parent_id'], $id],
                    $User->getId(),
                    $containerForChangelog['name'],
                    []
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }


                $this->set('success', true);
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            } else {
                $this->response = $this->response->withStatus(400);
                $this->set('success', false);
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            }
        }
        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('message', __('Container is not empty'));
        $this->set('containerId', $id);
        $this->set('id', $id);
        $this->viewBuilder()->setOption('serialize', ['success', 'message', 'containerId', 'id']);
        return;

    }

    public function loadContainersForAngular() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $onlyWithWritePermissions = $this->request->getQuery('onlyWritePermissions') === 'true';

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers =
                $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        }

        if ($onlyWithWritePermissions === true) {
            foreach ($containers as $containerId => $containerName) {
                if (!isset($this->MY_RIGHTS_LEVEL[$containerId]) || $this->MY_RIGHTS_LEVEL[$containerId] !== WRITE_RIGHT) {
                    unset($containers[$containerId]);
                }
            }
        }

        $containers = Api::makeItJavaScriptAble($containers);
        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    /**
     * @param null $id
     */
    public function showDetails($id = null) {
        if (!$this->isApiRequest() && $id === null) {
            //Only ship HTML template for angular
            return;
        }
        if (!$this->allowedByContainerId($id)) {
            $this->render403();
            return;
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$ContainersTable->existsById($id)) {
            throw new NotFoundException(__('Invalid container'));
        }
        $containersAsTree = $this->request->getQuery('asTree', 'true') === 'true';

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $container = $ContainersTable->getContainerById($id, $MY_RIGHTS);
        $subContainers = $ContainersTable->getContainerWithAllChildren($id, $MY_RIGHTS);
        if ($containersAsTree === false) {
            $containersWithChilds = Hash::filter($subContainers);
            $this->set('containersWithChilds', $containersWithChilds);
            $this->viewBuilder()->setOption('serialize', ['containersWithChilds']);
        } else {
            $containerMap = $ContainersTable->getContainerMap($container, $subContainers);
            $this->set('containerMap', $containerMap);
            $this->viewBuilder()->setOption('serialize', ['containerMap']);
        }
    }

    /**
     * @throws \Exception
     */
    public function loadSatellitesByContainerIds() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $containerIds = $this->request->getQuery('containerIds', []);
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerIds, false);

        foreach ($containerIds as $containerId) {
            if (!$ContainersTable->existsById($containerId)) {
                throw new NotFoundException(__('Invalid container'));
            }
        }

        $satellites = [];
        if (Plugin::isLoaded('DistributeModule')) {
            /** @var $SatellitesTable SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
            $satellites = $SatellitesTable->getSatellitesAsListWithDescription($containerIds);
        }
        $satellites = Api::makeItJavaScriptAble($satellites);

        $this->set('satellites', $satellites);
        $this->viewBuilder()->setOption('serialize', [
            'satellites'
        ]);
    }
}
