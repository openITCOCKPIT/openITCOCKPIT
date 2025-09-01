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

use App\Model\Table\LdapgroupsTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsersTable;
use Cake\Cache\Cache;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\LdapgroupFilter;
use itnovum\openITCOCKPIT\Filter\UsercontainerrolesFilter;

/**
 * Class UsercontainerrolesController
 * @package App\Controller
 */
class UsercontainerrolesController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $UsercontainerrolesFilter = new UsercontainerrolesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $UsercontainerrolesFilter->getPage());

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');
        $all_usercontainerroles = $UsercontainerrolesTable->getUsercontainerRolesIndex(
            $UsercontainerrolesFilter,
            $PaginateOMat,
            $this->MY_RIGHTS
        );
        $containerWithWritePermissions = array_filter($this->MY_RIGHTS_LEVEL, function ($v) {
            return $v == WRITE_RIGHT;
        }, ARRAY_FILTER_USE_BOTH);
        $containerWithWritePermissions = array_keys($containerWithWritePermissions);
        $containerWithWritePermissionByUserContainerRoles = [];
        foreach ($all_usercontainerroles as $index => $usercontainerrole) {
            $userRoleContainerIdsWithWritePermission = Hash::extract($usercontainerrole['containers'], '{n}._joinData[permission_level=2].container_id');
            if (!$this->hasRootPrivileges && !empty(array_diff($userRoleContainerIdsWithWritePermission, $containerWithWritePermissions))) {
                unset($all_usercontainerroles[$index]);
                continue; //insufficient user (container) rights
            }
            $all_usercontainerroles[$index]['allow_edit'] = $this->hasRootPrivileges;
            if ($this->hasRootPrivileges === false) {
                foreach ($usercontainerrole['containers'] as $key => $container) {
                    if ($this->isWritableContainer($container['id'])) {
                        $all_usercontainerroles[$index]['allow_edit'] = $this->isWritableContainer($container['id']);
                        break;
                    }
                    $all_usercontainerroles[$index]['allow_edit'] = false;
                }
            }
            foreach ($usercontainerrole['users'] as $userIndex => $user) {
                $usercontainerrole['users'][$userIndex]['allow_edit'] = $this->hasRootPrivileges;
                if ($this->hasRootPrivileges === false) {
                    //Check permissions for non ROOT Users
                    $containerWithPermissionLevelByUserContainerRoles = Hash::combine(
                        $user['usercontainerroles'],
                        '{n}.containers.{n}._joinData.container_id',
                        '{n}.containers.{n}._joinData.permission_level'
                    );
                    $notPermittedContainer = array_filter($containerWithPermissionLevelByUserContainerRoles, function ($v, $k) {
                        return (!isset($this->MY_RIGHTS_LEVEL[$k]) || (isset($this->MY_RIGHTS_LEVEL[$k]) && $this->MY_RIGHTS_LEVEL[$k] < $v));

                    }, ARRAY_FILTER_USE_BOTH);

                    if (!empty($notPermittedContainer)) {
                        $usercontainerrole['users'][$userIndex]['allow_edit'] = false;
                    } else {
                        $containerWithWritePermissionByUserContainerRoles = array_keys($containerWithWritePermissionByUserContainerRoles);
                        $container = Hash::extract(
                            $user['containers'],
                            '{n}.id'
                        );
                        $container = array_unique(array_merge($container, $containerWithPermissionLevelByUserContainerRoles));
                        foreach ($container as $containerId) {
                            if ($this->isWritableContainer($containerId)) {
                                $usercontainerrole['users'][$userIndex]['allow_edit'] = true;
                                break;
                            }
                        }
                    }
                }
            }

            $all_usercontainerroles[$index]['users'] = $usercontainerrole['users'];
        }
        // return an array instead of a list
        $all_usercontainerroles = array_values($all_usercontainerroles);
        $this->set('all_usercontainerroles', $all_usercontainerroles);
        $toJson = ['paging', 'all_usercontainerroles'];
        if ($this->isScrollRequest()) {
            $toJson = ['scroll', 'all_usercontainerroles'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }


    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        if ($this->request->is('post') || $this->request->is('put')) {
            /** @var UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('Users');

            $data = $this->request->getData('Usercontainerrole', []);
            if (!isset($data['ContainersUsercontainerrolesMemberships'])) {
                $data['ContainersUsercontainerrolesMemberships'] = [];
            }
            $data['containers'] = $UsersTable->containerPermissionsForSave(
                $data['ContainersUsercontainerrolesMemberships'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );

            $usercontainerrole = $UsercontainerrolesTable->newEmptyEntity();
            $usercontainerrole = $UsercontainerrolesTable->patchEntity($usercontainerrole, $data);
            $UsercontainerrolesTable->save($usercontainerrole);
            if ($usercontainerrole->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $usercontainerrole->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            Cache::clear('permissions');
            $this->set('usercontainerrole', $usercontainerrole);
            $this->viewBuilder()->setOption('serialize', ['usercontainerrole']);
        }
    }

    /**
     * @param int|null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        if (!$UsercontainerrolesTable->existsById($id)) {
            throw new MethodNotAllowedException('Invalid User Container Role');
        }

        $usercontainerrole = $UsercontainerrolesTable->getUserContainerRoleForEdit($id);

        if (!$this->allowedByContainerId($usercontainerrole['Usercontainerrole']['containers']['_ids'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return user container roles information
            $this->set('usercontainerrole', $usercontainerrole['Usercontainerrole']);
            $this->viewBuilder()->setOption('serialize', ['usercontainerrole']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            /** @var UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('Users');

            //autofill restricted containers for save
            $autofillRights = [];
            if (!$this->hasRootPrivileges) {
                if (!empty($usercontainerrole['Usercontainerrole']['ContainersUsercontainerrolesMemberships'])) {
                    foreach ($usercontainerrole['Usercontainerrole']['ContainersUsercontainerrolesMemberships'] as $containerId => $permissionLevel) {
                        if (!isset($this->MY_RIGHTS_LEVEL[$containerId])) {
                            $autofillRights[] = [
                                'id'        => $containerId,
                                '_joinData' => [
                                    'permission_level' => $permissionLevel
                                ]
                            ];
                        }
                    }
                }
            }

            $usercontainerrole = $UsercontainerrolesTable->get($id);

            $data = $this->request->getData('Usercontainerrole', []);
            if (!isset($data['ContainersUsercontainerrolesMemberships'])) {
                $data['ContainersUsercontainerrolesMemberships'] = [];
            }
            $data['containers'] = $UsersTable->containerPermissionsForSave(
                $data['ContainersUsercontainerrolesMemberships'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );
            $data['containers'] = array_merge($data['containers'], $autofillRights);

            $usercontainerrole->setAccess('id', false);

            $usercontainerrole = $UsercontainerrolesTable->patchEntity($usercontainerrole, $data);
            $UsercontainerrolesTable->save($usercontainerrole);
            if ($usercontainerrole->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $usercontainerrole->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            Cache::clear('permissions');
            $this->set('usercontainerrole', $usercontainerrole);
            $this->viewBuilder()->setOption('serialize', ['usercontainerrole']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        if (!$UsercontainerrolesTable->existsById($id)) {
            throw new MethodNotAllowedException('Invalid User Container Role');
        }

        $usercontainerrole = $UsercontainerrolesTable->getUserContainerRoleForEdit($id);

        if (!$this->allowedByContainerId($usercontainerrole['Usercontainerrole']['containers']['_ids'])) {
            $this->render403();
            return;
        }

        $usercontainerrole = $UsercontainerrolesTable->get($id);
        if ($UsercontainerrolesTable->delete($usercontainerrole)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /**
     * @param int|null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $usercontainerroles = $UsercontainerrolesTable->getUserContainerRolesForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('usercontainerroles', $usercontainerroles);
            $this->viewBuilder()->setOption('serialize', ['usercontainerroles']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $postData = $this->request->getData('data');
            /** @var UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('Users');
            foreach ($postData as $index => $usercontainerroleData) {
                if (!isset($usercontainerroleData['Usercontainerrole']['id'])) {
                    //Create/clone Usercontainerrole
                    $sourceUsercontainerroleId = $usercontainerroleData['Source']['id'];
                    $sourceUsercontainerrole = $UsercontainerrolesTable->getSourceUserContainerRoleForCopy($sourceUsercontainerroleId, $MY_RIGHTS);


                    $newUsercontainerroleData = [
                        'name'       => $usercontainerroleData['Usercontainerrole']['name'],
                        'containers' => $UsersTable->containerPermissionsForSave(
                            $sourceUsercontainerrole['ContainersUsercontainerrolesMemberships'],
                            $this->hasRootPrivileges,
                            $this->MY_RIGHTS_LEVEL
                        ),
                        'ldapgroups' => [
                            '_ids' => $sourceUsercontainerrole['ldapgroups']['_ids']
                        ]
                    ];

                    $newUsercontainerroleEntity = $UsercontainerrolesTable->newEntity($newUsercontainerroleData);
                }

                $action = 'copy';
                if (isset($usercontainerroleData['Usercontainerrole']['id'])) {
                    //Update existing Usercontainerrole
                    //This happens, if a user copy multiple Usercontainerroles, and one run into an validation error
                    //All Usercontainerroles without validation errors got already saved to the database
                    $newUsercontainerroleEntity = $UsercontainerrolesTable->get($usercontainerroleData['Usercontainerrole']['id']);
                    $newUsercontainerroleEntity->setAccess('*', false);
                    $newUsercontainerroleEntity->setAccess(['name'], true);

                    $newUsercontainerroleEntity = $UsercontainerrolesTable->patchEntity($newUsercontainerroleEntity, $usercontainerroleData['Usercontainerrole']);
                    $action = 'edit';
                }
                $UsercontainerrolesTable->save($newUsercontainerroleEntity);

                $postData[$index]['Error'] = [];
                if ($newUsercontainerroleEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newUsercontainerroleEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Usercontainerrole']['id'] = $newUsercontainerroleEntity->get('id');
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

    public function loadLdapgroupsForAngular() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $isLdapAuth = $SystemsettingsTable->isLdapAuth();
        $ldapgroups = [];

        if ($isLdapAuth === true) {
            $selected = $this->request->getQuery('selected', []);
            $LdapgroupFilter = new LdapgroupFilter($this->request);
            $where = $LdapgroupFilter->ajaxFilter();

            /** @var $LdapgroupsTable LdapgroupsTable */
            $LdapgroupsTable = TableRegistry::getTableLocator()->get('Ldapgroups');
            $ldapgroups = $LdapgroupsTable->getLdapgroupsForAngular($where, $selected);

            $ldapgroups = Api::makeItJavaScriptAble($ldapgroups);
        }

        $this->set('isLdapAuth', $isLdapAuth);
        $this->set('ldapgroups', $ldapgroups);
        $this->viewBuilder()->setOption('serialize', ['ldapgroups', 'isLdapAuth']);
    }

}
