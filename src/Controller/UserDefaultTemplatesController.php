<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

declare(strict_types=1);

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Filter\UserDefaultTemplatesFilter;
use App\Model\Table\LdapgroupsTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UserDefaultTemplatesTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Filter\LdapgroupFilter;

/**
 * UserDefaultTemplates Controller
 * @package App\Controller
 * @property \App\Model\Table\UserDefaultTemplatesTable $UserDefaultTemplates
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class UserDefaultTemplatesController extends AppController {

    public function index(): void {

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');
        $UserDefaultTemplatesFilter = new UserDefaultTemplatesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $UserDefaultTemplatesFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            // root users can see all users
            $MY_RIGHTS = [];
        }
        $all_userdefaulttemplates = $UserDefaultTemplatesTable->getUserDefaultTemplatesIndex($UserDefaultTemplatesFilter, $PaginateOMat, $MY_RIGHTS);

        foreach ($all_userdefaulttemplates as $index => $user_default_template) {
            $allowEdit = $this->hasRootPrivileges;
            if ($this->hasRootPrivileges === false) {
                $allowEdit = false;
                foreach ($user_default_template['containers'] as $container) {
                    if ($this->isWritableContainer($container['id'])) {
                        $allowEdit = true;
                        break;
                    }
                }
            }
            $all_userdefaulttemplates[$index]['allow_edit'] = $allowEdit;
        }

        $this->set('all_userdefaulttemplates', $all_userdefaulttemplates);
        $this->viewBuilder()->setOption('serialize', ['all_userdefaulttemplates']);
    }

    public function add(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if ($this->request->is('post') || $this->request->is('put')) {

            $data = $this->request->getData('Userdefaulttemplate', []);
            if (!isset($data['UserDefaultTemplatesToUserContainers'])) {
                $data['UserDefaultTemplatesToUserContainers'] = [];
            }
            $data['user_containers'] = $UsersTable->containerPermissionsForSave(
                $data['UserDefaultTemplatesToUserContainers'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );

            $userDefaultTemplateEntity = $UserDefaultTemplatesTable->newEmptyEntity();
            $userDefaultTemplateEntity = $UserDefaultTemplatesTable->patchEntity($userDefaultTemplateEntity, $data);
            $UserDefaultTemplatesTable->save($userDefaultTemplateEntity);
            if ($userDefaultTemplateEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $userDefaultTemplateEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            //No errors
            if ($this->isJsonRequest()) {
                $this->serializeCake4Id($userDefaultTemplateEntity); // REST API ID serialization
                return;
            }

            $this->set('userDefaultTemplate', $userDefaultTemplateEntity);
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate']);

        }
    }

    public function edit($id = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');


        if (!$UserDefaultTemplatesTable->existsById($id)) {
            throw new NotFoundException(__('User Default Template not found'));
        }

        $userDefaultTemplate = $UserDefaultTemplatesTable->getUserDefaultTemplateForEdit($id);
        $containersToCheck = $userDefaultTemplate['UserDefaultTemplate']['containers']['_ids']; //Containers defined by the user itself

        $notPermittedUserContainerIds = [];
        if (!$this->hasRootPrivileges) {
            $notPermittedUserContainerIds = array_diff(
                $containersToCheck,
                $this->getWriteContainers()
            );
        }

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return user default template information
            $this->set('userDefaultTemplate', $userDefaultTemplate['UserDefaultTemplate']);
            $this->set('notPermittedUserContainerIds', array_values(array_map('intval', $notPermittedUserContainerIds))); // Make sure its a int array for Angular
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate', 'notPermittedUserContainerIds']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('Userdefaulttemplate', []);
            if (!isset($data['UserDefaultTemplatesToUserContainers'])) {
                $data['UserDefaultTemplatesToUserContainers'] = [];
            }

            if (!$this->hasRootPrivileges) {
                $containerIdsWithWritePermissions = array_filter($this->MY_RIGHTS_LEVEL, function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);
                $userToEditContainerIdsWithWritePermissions = array_filter($data['UserDefaultTemplatesToUserContainers'], function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);

                $notPermittedUserContainerIds = array_keys(
                    array_diff_key($userToEditContainerIdsWithWritePermissions, $containerIdsWithWritePermissions)
                );

                foreach ($data['UserDefaultTemplatesToUserContainers'] as $key => $value) {
                    // do not overwrite container settings if the user does not have sufficient rights
                    if (in_array($key, $notPermittedUserContainerIds, true)) {
                        continue;
                    }
                    // reverting write permission to read permission due to insufficient user permission rights
                    if ($key !== ROOT_CONTAINER && !array_key_exists($key, $containerIdsWithWritePermissions) && $value > 1) {
                        $data['UserDefaultTemplatesToUserContainers'][$key] = READ_RIGHT;
                    }
                }
            }
            $data['user_containers'] = $UsersTable->containerPermissionsForSave(
                $data['UserDefaultTemplatesToUserContainers'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );


            $userDefaultTemplate = $UserDefaultTemplatesTable->get($id);
            $userDefaultTemplate->id = $id;
            $userDefaultTemplate = $UserDefaultTemplatesTable->patchEntity($userDefaultTemplate, $data);


            $UserDefaultTemplatesTable->save($userDefaultTemplate);

            if ($userDefaultTemplate->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $userDefaultTemplate->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            // No errors
            $this->set('userDefaultTemplate', $userDefaultTemplate);
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        if (!$UserDefaultTemplatesTable->existsById($id)) {
            throw new NotFoundException(__('User default template not found'));
        }
        $userDefaultTemplate = $UserDefaultTemplatesTable->getUserDefaultTemplateById($id);
        $containerIdsToCheck = Hash::extract($userDefaultTemplate, 'containers.{n}.id');
        if (!$this->allowedByContainerId($containerIdsToCheck)) {
            $this->render403();
            return;
        }

        $userDefaultTemplateEntity = $UserDefaultTemplatesTable->get($id);
        if ($UserDefaultTemplatesTable->delete($userDefaultTemplateEntity)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function loadContainerRolesByLdapGroupIds() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Usercontainerroles.name'
            ]
        ]);
        $ldapGroupIds = $this->request->getQuery('ldapGroupIds', []);

        $ucr = $UsercontainerrolesTable->getUsercontainerrolesAsListByLdapGroupIds(
            $GenericFilter,
            $ldapGroupIds
        );

        $usercontainerrolesByLdapGroup = [];
        foreach ($ucr as $key => $userContainerRole) {
            foreach ($userContainerRole['ldapgroups'] as $ldapGroup) {
                if (!isset($usercontainerrolesByLdapGroup[$ldapGroup['id']])) {
                    $usercontainerrolesByLdapGroup[$ldapGroup['id']] = [
                        'id' => $ldapGroup['id'],
                        'cn' => $ldapGroup['cn'],
                        'dn' => $ldapGroup['dn']
                    ];
                }

                $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key] = [
                    'id'                   => $key,
                    'name'                 => $userContainerRole['name'],
                    'containerPermissions' => $UsercontainerrolesTable->getContainerPermissionsByUserContainerRoleIds($key)[$key] ?? []
                ];
                if ($this->hasRootPrivileges) {
                    $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key] = Hash::insert(
                        $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key],
                        'containerPermissions.containers.{n}.allowView',
                        true
                    );
                    $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['allowEdit'] = true;
                } else {
                    $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['allowEdit'] = null;
                    if (!empty($usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['containerPermissions']['containers'])) {
                        foreach ($usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['containerPermissions']['containers'] as $containerKey => $container) {
                            if ($this->isWritableContainer($container['id'])) {
                                if (is_null($usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['allowEdit']) ||
                                    $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['allowEdit'] !== false) {
                                    $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['allowEdit'] = $this->isWritableContainer($container['id']);
                                }
                            } else {
                                $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['allowEdit'] = false;
                            }
                            $allowView = in_array($container['id'], $this->MY_RIGHTS, true);
                            $path = $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['containerPermissions']['containers'][$containerKey]['path'];
                            $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['containerPermissions']['containers'][$containerKey]['allowView'] = $allowView;
                            $usercontainerrolesByLdapGroup[$ldapGroup['id']]['containerroles'][$key]['containerPermissions']['containers'][$containerKey]['path'] = ($allowView) ? $path : __('RESTRICTED CONTAINER');

                        }
                    }
                }
            }
        }

        foreach ($usercontainerrolesByLdapGroup as $key => $userContainerRole) {
            $usercontainerrolesByLdapGroup[$key]['containerroles'] = array_values($userContainerRole['containerroles']);
        }

        $usercontainerrolesByLdapGroup = Api::makeItJavaScriptAble($usercontainerrolesByLdapGroup);


        $this->set('usercontainerrolesByLdapGroup', $usercontainerrolesByLdapGroup);
        $this->viewBuilder()->setOption('serialize', ['usercontainerrolesByLdapGroup']);
    }

    public function loadLdapgroupsWithContainerRolesForAngular() {
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

            /** @var LdapgroupsTable $LdapgroupsTable */
            $LdapgroupsTable = TableRegistry::getTableLocator()->get('Ldapgroups');
            $ldapgroups = $LdapgroupsTable->getLdapgroupsWitContainerRolesForAngular($where, $selected);

            $ldapgroups = Api::makeItJavaScriptAble($ldapgroups);
        }

        $this->set('isLdapAuth', $isLdapAuth);
        $this->set('ldapgroups', $ldapgroups);
        $this->viewBuilder()->setOption('serialize', ['ldapgroups', 'isLdapAuth']);
    }
}
