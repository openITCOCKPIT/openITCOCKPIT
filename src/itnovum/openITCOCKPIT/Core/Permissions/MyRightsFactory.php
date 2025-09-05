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


namespace App\itnovum\openITCOCKPIT\Core\Permissions;


use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

class MyRightsFactory {
    /**
     * @param $userId
     * @param $userGroupId
     * @return array
     */
    public static function getUserPermissions($userId, $usergroupId) {
        $userId = (int)$userId;
        $usergroupId = (int)$usergroupId;

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $hasRootPrivileges = false;

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $user = $UsersTable->getUserById($userId);

        //unify the usercontainerrole permissions
        $usercontainerrolePermissions = [];
        foreach ($user['usercontainerroles'] as $usercontainerrole) {
            foreach ($usercontainerrole['containers'] as $usercontainerroleContainer) {
                $currentId = $usercontainerroleContainer['id'];
                if (isset($usercontainerrolePermissions[$currentId])) {
                    //highest usercontainerrole permission wins
                    if ($usercontainerrolePermissions[$currentId]['_joinData']['permission_level'] < $usercontainerroleContainer['_joinData']['permission_level']) {
                        $usercontainerrolePermissions[$currentId] = $usercontainerroleContainer;
                        continue;
                    }
                } else {
                    $usercontainerrolePermissions[$currentId] = $usercontainerroleContainer;
                }
            }
        }

        //merge permissions from usercontainerrole with the user container permissions
        //User container permissions override permissions from the role
        $containerPermissions = [];
        $containerPermissionsUser = [];
        foreach ($usercontainerrolePermissions as $usercontainerrolePermission) {
            $containerPermissions[$usercontainerrolePermission['id']] = $usercontainerrolePermission;
        }
        foreach ($user['containers'] as $container) {
            $containerPermissionsUser[$container['id']] = $container;
        }

        $containerPermissions = $containerPermissionsUser + $containerPermissions;

        // The ROOT_CONTAINER is always available for ALL users. (READ permissions)
        // For this reason, in the Web-GUI the only option for the ROOT_CONTAINER is to grant WRITE permissions.
        // It is unnecessary to select the ROOT_CONTAINER, which is available to all users by default with READ permissions.
        $MY_RIGHTS = [ROOT_CONTAINER];
        $MY_RIGHTS_LEVEL = [ROOT_CONTAINER => READ_RIGHT];

        foreach ($containerPermissions as $container) {
            $container = $container['_joinData'];
            $MY_RIGHTS[] = (int)$container['container_id'];
            $MY_RIGHTS_LEVEL[(int)$container['container_id']] = $container['permission_level'];

            if ((int)$container['container_id'] === ROOT_CONTAINER) {
                $MY_RIGHTS_LEVEL[ROOT_CONTAINER] = WRITE_RIGHT;
                $hasRootPrivileges = true;
            }

            foreach ($ContainersTable->getChildren($container['container_id']) as $childContainer) {
                $MY_RIGHTS[] = (int)$childContainer['id'];
                $MY_RIGHTS_LEVEL[(int)$childContainer['id']] = $container['permission_level'];
            }
        }

        $userPermissions = [
            'MY_RIGHTS'         => array_unique($MY_RIGHTS),
            'MY_RIGHTS_LEVEL'   => $MY_RIGHTS_LEVEL,
            'PERMISSIONS'       => self::getAcoPermissions($usergroupId),
            'hasRootPrivileges' => $hasRootPrivileges
        ];

        return $userPermissions;
    }

    /**
     * @param int $usergroupId
     * @return array
     */
    protected static function getAcoPermissions(int $usergroupId) {
        /** @var ArosTable $ArosTable */
        $ArosTable = TableRegistry::getTableLocator()->get('Acl.Aros');
        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');

        $AcosAros = $ArosTable->find()
            ->where([
                'Aros.foreign_key' => $usergroupId
            ])
            ->contain([
                'Acos' => function (Query $query) {
                    return $query->where([
                        '_create' => '1'
                    ]);
                }
            ])
            ->disableHydration()
            ->first();

        $acos = $AcosAros['acos'];

        $acoIdsOfUsergroup = Hash::combine($acos, '{n}.id', '{n}.id');
        unset($acos, $AcosAros);

        $acos = $AcosTable->find('threaded')
            ->disableHydration()
            ->all();

        $acos = $acos->toArray();

        $permissions = [];
        foreach ($acos as $usergroupAcos) {
            foreach ($usergroupAcos['children'] as $controllerAcos) {
                $controllerName = strtolower($controllerAcos['alias']);
                if (!strpos($controllerName, 'module')) {
                    //Core
                    foreach ($controllerAcos['children'] as $actionAcos) {
                        //Check if the user group is allowd for $actionAcos action
                        if (!isset($acoIdsOfUsergroup[$actionAcos['id']])) {
                            continue;
                        }
                        $actionName = strtolower($actionAcos['alias']);
                        $permissions[$controllerName][$actionName] = $actionName;
                    }
                } else {
                    //Plugin / Module
                    $pluginName = Inflector::underscore($controllerName);
                    $pluginAcos = $controllerAcos;
                    foreach ($pluginAcos['children'] as $controllerAcos) {

                        $controllerName = strtolower($controllerAcos['alias']);
                        foreach ($controllerAcos['children'] as $actionAcos) {
                            //Check if the user group is allowd for $actionAcos action
                            if (!isset($acoIdsOfUsergroup[$actionAcos['id']])) {
                                continue;
                            }
                            $actionName = strtolower($actionAcos['alias']);
                            $permissions[$pluginName][$controllerName][$actionName] = $actionName;
                        }
                    }
                }
            }
        }

        return $permissions;
    }
}
