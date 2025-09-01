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

use App\itnovum\openITCOCKPIT\Filter\SystemHealthUsersFilter;
use App\Model\Table\SystemHealthUsersTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * SystemHealthUsers Controller
 *
 */
class SystemHealthUsersController extends AppController {
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var SystemHealthUsersTable $SystemHealthUsersTable */
        $SystemHealthUsersTable = TableRegistry::getTableLocator()->get('SystemHealthUsers');

        $SystemHealthUsersFilter = new SystemHealthUsersFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $SystemHealthUsersFilter->getPage());

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $all_tmp_users = $SystemHealthUsersTable->getSystemHealthUsersIndex($SystemHealthUsersFilter, $PaginateOMat, $MY_RIGHTS);
        $all_users = [];

        $User = new User($this->getUser());

        foreach ($all_tmp_users as $index => $_user) {
            /** @var \App\Model\Entity\User $_user */
            $user = $_user->toArray();
            $user['allow_edit'] = $this->hasRootPrivileges;

            if ($this->hasRootPrivileges === false) {
                //Check permissions for non ROOT Users
                $containerWithWritePermissionByUserContainerRoles = Hash::combine(
                    $user['usercontainerroles'],
                    '{n}.containers.{n}._joinData.container_id',
                    '{n}.containers.{n}._joinData.permission_level'
                );

                $notPermittedContainer = array_filter($containerWithWritePermissionByUserContainerRoles, function ($v, $k) {
                    return (!isset($this->MY_RIGHTS_LEVEL[$k]) || (isset($this->MY_RIGHTS_LEVEL[$k]) && $this->MY_RIGHTS_LEVEL[$k] < $v));

                }, ARRAY_FILTER_USE_BOTH);
                if (!empty($notPermittedContainer)) {
                    $user['allow_edit'] = false;
                } else {
                    $containerWithWritePermissionByUserContainerRoles = array_keys($containerWithWritePermissionByUserContainerRoles);

                    $container = Hash::extract(
                        $user['containers'],
                        '{n}.id'
                    );

                    $container = array_unique(array_merge($container, $containerWithWritePermissionByUserContainerRoles));
                    foreach ($container as $containerId) {
                        if ($this->isWritableContainer($containerId)) {
                            $user['allow_edit'] = true;
                            break;
                        }
                    }
                }
            }
            $all_users[] = $user;
        }

        $this->set('all_users', $all_users);
        $this->viewBuilder()->setOption('serialize', ['all_users']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function add() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {

            /** @var $SystemHealthUsersTable SystemHealthUsersTable */
            $SystemHealthUsersTable = TableRegistry::getTableLocator()->get('SystemHealthUsers');

            $systemHealthUserData = $this->request->getData('SystemHealthUser');

            if (empty($systemHealthUserData['user_ids'])) {

                $this->response = $this->response->withStatus(400);
                $this->set('error', ['users' => [__('At least one user is required.')]]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;

            }

            foreach ($systemHealthUserData['user_ids'] as $userId) {

                $systemHealthUser = $SystemHealthUsersTable->newEmptyEntity();

                if ($SystemHealthUsersTable->existsByUserId($userId)) {
                    $systemHealthUser = $SystemHealthUsersTable->getSystemHealthUserByUserId($userId);
                }

                $systemHealthUser = $SystemHealthUsersTable->patchEntity($systemHealthUser, $systemHealthUserData);
                $systemHealthUser->set('user_id', $userId);

                $SystemHealthUsersTable->save($systemHealthUser);
                if ($systemHealthUser->hasErrors()) {
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $systemHealthUser->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }
            }

            $this->set('systemHealthUser', $systemHealthUser);
            $this->viewBuilder()->setOption('serialize', ['systemHealthUser']);
        }
    }

    /**
     * Edit method
     *
     * @param string|null $id System Health user id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $SystemHealthUsersTable SystemHealthUsersTable */
        $SystemHealthUsersTable = TableRegistry::getTableLocator()->get('SystemHealthUsers');

        if (!$SystemHealthUsersTable->existsById($id)) {
            throw new NotFoundException(__('System health user not found'));
        }

        $systemHealthUser = $SystemHealthUsersTable->getUserForEdit($id);

        $containersToCheck = array_unique(
            array_merge(
                $systemHealthUser['usercontainerroles_containerids']['_ids'], //Container Ids through Container Roles
                $systemHealthUser['containers']['_ids'] //Containers defined by the user itself
            )
        );

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        unset($systemHealthUser['containers']);
        unset($systemHealthUser['usercontainerroles']);
        unset($systemHealthUser['usercontainerroles_ldap']);
        unset($systemHealthUser['usercontainerroles_containerids']);

        if ($this->request->is('get')) {

            $user = $systemHealthUser['user'];
            unset($systemHealthUser['user']);

            $this->set('systemHealthUser', $systemHealthUser);
            $this->set('user', $user);

            $this->viewBuilder()->setOption('serialize', ['systemHealthUser', 'user']);
        }

        if ($this->request->is('post')) {

            $requestData = $this->request->getData('SystemHealthUser');
            $systemHealthUser = $SystemHealthUsersTable->get($id);

            $systemHealthUser = $SystemHealthUsersTable->patchEntity($systemHealthUser, $requestData);
            $SystemHealthUsersTable->save($systemHealthUser);
            if ($systemHealthUser->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $systemHealthUser->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('systemHealthUser', $systemHealthUser);

            $this->viewBuilder()->setOption('serialize', ['systemHealthUser']);
        }

    }

    /**
     * Delete method
     *
     * @param string|null $id System Health user id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $SystemHealthUsersTable SystemHealthUsersTable */
        $SystemHealthUsersTable = TableRegistry::getTableLocator()->get('SystemHealthUsers');

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if (!$SystemHealthUsersTable->existsById($id)) {
            throw new NotFoundException(__('System health user not found'));
        }

        $systemHealthUser = $SystemHealthUsersTable->get($id);

        $user = $UsersTable->getUserForPermissionCheck($systemHealthUser['user_id']);
        $containersToCheck = array_unique(array_merge(
            $user['usercontainerroles_containerids']['_ids'], //Container Ids through Container Roles
            $user['containers']['_ids']) //Containers defined by the user itself
        );

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        if ($SystemHealthUsersTable->delete($systemHealthUser)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);

            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function loadUsers() {

        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $tmp_users = $UsersTable->getUsersForSystemHealth($MY_RIGHTS);
        $users = [];

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        foreach ($tmp_users as $index => $_user) {
            /** @var \App\Model\Entity\User $_user */
            $user = $_user->toArray();

            if ($this->hasRootPrivileges === false) {
                //Check permissions for non ROOT Users
                $containerWithWritePermissionByUserContainerRoles = Hash::combine(
                    $user['usercontainerroles'],
                    '{n}.containers.{n}._joinData.container_id',
                    '{n}.containers.{n}._joinData.permission_level'
                );

                $notPermittedContainer = array_filter($containerWithWritePermissionByUserContainerRoles, function ($v, $k) {
                    return (!isset($this->MY_RIGHTS_LEVEL[$k]) || (isset($this->MY_RIGHTS_LEVEL[$k]) && $this->MY_RIGHTS_LEVEL[$k] < $v));

                }, ARRAY_FILTER_USE_BOTH);
                if (!empty($notPermittedContainer)) {
                    continue;
                } else {
                    $containerWithWritePermissionByUserContainerRoles = array_keys($containerWithWritePermissionByUserContainerRoles);

                    $container = Hash::extract(
                        $user['containers'],
                        '{n}.id'
                    );

                    $container = array_unique(array_merge($container, $containerWithWritePermissionByUserContainerRoles));
                    foreach ($container as $containerId) {
                        if ($this->isWritableContainer($containerId)) {
                            break;
                        }
                    }
                }
            }
            $users[] = $user;
        }

        $this->set('users', $users);
        $this->viewBuilder()->setOption('serialize', ['users']);

    }

}
