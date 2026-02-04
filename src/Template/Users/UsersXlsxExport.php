<?php declare(strict_types=1);
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

namespace App\Template\Users;

use Acl\Model\Table\AcosTable;
use App\Lib\AclDependencies;
use App\Model\Entity\User;
use App\Model\Entity\Usergroup;
use App\Model\Table\ContainersTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Ldap\LdapClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class UsersXlsxExport {
    private array $MY_RIGHTS;
    private bool $hasRootPrivileges;
    private Spreadsheet $Spreadsheet;
    private UsercontainerrolesTable $UsercontainerrolesTable;
    private UsergroupsTable $UsergroupsTable;
    private array $Users = [];
    private array $Containers = [];
    private array $ContainerRoles = [];
    /** @var Usergroup[] */
    private array $UserRoles = [];
    /** @var int[][] */
    private array $UserRoleAcos = [];
    private array $Permissions = [];
    private array $Modules = [];
    private array $ContainerTree = [];

    /**
     * I am the container permission matrix. I will look like this:
     * $this->ContainerPermissions = [
     *     42 => [  // CONTAINER ID
     *         13 => 2  // USER ID => PERMISSION LEVEL
     *     ]
     * ]
     */
    private array $ContainerPermissions = [];

    public function __construct(array $MY_RIGHTS, bool $hasRootPrivileges) {
        $this->MY_RIGHTS = $MY_RIGHTS;
        $this->hasRootPrivileges = $hasRootPrivileges;
        $this->Spreadsheet = new Spreadsheet();

        $this->UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');
        $this->UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');
    }

    /**
     * I will generate the entire export in one method.
     * This means, I will...
     *   - Fetch data from CakePHP Tables
     *   - Build Sheets
     *   - Save the XLSX file to the given $fileName.
     *
     * @param string $fileName
     * @return void
     * @throws MissingEntityException
     */
    public function export(string $fileName): void {
        $this->UsersSheet();
        $this->UserRolesSheet();
        $this->ContainersSheet();

        $writer = new Xlsx($this->Spreadsheet);
        $writer->save($fileName);
    }

    /**
     * I will build the entire Sheet "Users".
     * @return void
     */
    private function UsersSheet(): void {
        $this->buildUserData();
        $sheet = $this->Spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        // Header Row
        $lines = [
            [
                'User ID',
                'First name',
                'Last name',
                'Mail',
                'User role ID',
                'User role / Fallback User role',
                'LDAP User',
                'User role through LDAP ID',
                'User role through LDAP',
            ]
        ];
        // Body Rows
        foreach ($this->Users as $User) {
            $lines[] = [
                h($User['id']),
                h($User['firstname']),
                h($User['lastname']),
                h($User['email']),
                h($User['usergroup']['id']),
                h($User['usergroup']['name']),
                ($User['samaccountname'] ? 'YES' : 'NO'),
                h($User['UserRoleThroughLdap']['id'] ?? ''),
                h($User['UserRoleThroughLdap']['name'] ?? ''),
            ];
        }
        $sheet->fromArray($lines, null, 'A1', false);
    }

    /**
     * I will build the data for the Users Sheet.
     * @return void
     */
    private function buildUserData(): void {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $all_tmp_users = $UsersTable->getUsersExport($this->MY_RIGHTS);

        // Initialized later, so it only connects on demand, but ONCE.
        $LdapClient = false;
        foreach ($all_tmp_users as $_user) {
            /** @var User $_user */
            $user = $_user->toArray();

            if ($LdapClient !== false && !empty($user['samaccountname'])) {
                // If not already done, get LdapClient, we need it now.
                if (!$LdapClient) {
                    $LdapClient = $this->getLdapClient();
                    if ($LdapClient === null) {
                        // LDAP CLIENT CANNOT BE LOADED, Skip on this case by setting the ldapClient to a dummy value.
                        $LdapClient = false;
                        continue;
                    }
                }
                $ldapUser = $LdapClient->getUser($user['samaccountname'], true);
                if (!$ldapUser) {
                    continue;
                }

                $ldapUser = $this->fetchLdapUserAttributes($ldapUser);
                $user = array_merge($user, $ldapUser);
            }
            $user['name'] = "{$user['firstname']} {$user['lastname']}";
            $this->Users[] = $user;
        }
    }

    private function fetchLdapUserAttributes(array $ldapUser): array {
        $ldapUser['userContainerRoleContainerPermissionsLdap'] = $this->UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
            $ldapUser['memberof']
        );

        $permissions = [];
        foreach ($ldapUser['userContainerRoleContainerPermissionsLdap'] as $userContainerRole) {
            foreach ($userContainerRole['containers'] as $container) {
                if (isset($permissions[$container['id']])) {
                    //Container permission is already set.
                    //Only overwrite it, if it is a WRITE_RIGHT
                    if ($container['_joinData']['permission_level'] === WRITE_RIGHT) {
                        $permissions[$container['id']] = $container;
                    }
                } else {
                    //Container is not yet in permissions - add it
                    $permissions[$container['id']] = $container;
                }
                $permissions[$container['id']]['user_roles'][$userContainerRole['id']] = [
                    'id'   => $userContainerRole['id'],
                    'name' => $userContainerRole['name']
                ];
            }
        }
        $ldapUser['userContainerRoleContainerPermissionsLdap'] = $permissions;

        // Load matching user role (Adminisgtrator, Viewer, etc...)
        $ldapUser['UserRoleThroughLdap'] = $this->UsergroupsTable->getUsergroupByLdapUserMemberOf($ldapUser['memberof']);

        return $ldapUser;
    }

    /**
     * I will build the entire Sheet "User Roles".
     * @return void
     */
    private function UserRolesSheet(): void {
        $this->buildUserRolesData();
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('User Roles');

        // Header Row
        $header = [
            '(Module) + Controller',
            'Action'
        ];
        foreach ($this->UserRoles as $UserRole) {
            $header[] = h($UserRole['name']) . '[ID ' . h($UserRole['id']) . ']';
        }

        $lines = [
            $header
        ];
        // Body Rows
        foreach ($this->Permissions as $Permission) {
            $moduleControllerString = h($Permission['controller']);
            if ($Permission['module']) {
                $moduleControllerString = '(' . h($Permission['module']) . ') / ' . h($Permission['controller']);
            }

            $line = [
                $moduleControllerString,
                h($Permission['action'])
            ];
            foreach ($this->UserRoles as $UserRole) {
                if ($this->userRoleHasPermission($UserRole, $Permission['id'])) {
                    $line[] = 'YES';
                    continue;
                }
                $line[] = 'NO';
            }

            $lines[] = $line;
        }
        $sheet->fromArray($lines, null, 'A1', false);
    }

    /**
     * I will build the data for the User Roles Sheet.
     * @return void
     */
    private function buildUserRolesData(): void {
        $this->UserRoles = $this->UsergroupsTable->find()
            ->contain([
                'Aros'       => [
                    'Acos'
                ],
                'Ldapgroups' => [
                    'fields' => [
                        'Ldapgroups.id'
                    ]
                ]
            ])
            ->disableHydration()
            ->all()
            ->toArray();
        foreach ($this->UserRoles as $UserRole) {
            $this->UserRoleAcos[$UserRole['id']] = array_unique(Hash::extract($UserRole, 'aro.acos.{n}.id'));
        }

        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');
        $acos = $AcosTable->find('threaded')
            ->disableHydration()
            ->all();
        $AclDependencies = new AclDependencies();
        $AclDList = $AclDependencies->filterAcosForFrontend($acos->toArray());
        foreach ($AclDList as $AclD) {
            if ($AclD['children']) {
                $this->addPermissionRow($AclD);
            }
        }
        // Sort $this->Permissions by module, controller, action
        usort($this->Permissions, static function (array $a, array $b) {
            return strcmp($a['module'] . $a['controller'], $b['module'] . $b['controller']);
        });
    }

    /**
     * I will build the entire Sheet "Containers".
     * @return void
     */
    private function ContainersSheet(): void {
        $this->buildContainersData();
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('Containers');

        $lines = [
            [
                h('Container ID'),
                h('Container'),
            ]
        ];

        // Header Row
        foreach ($this->Users as $User) {
            $lines[0][] = h($User['name']) . ' [ID ' . h($User['id']) . ']';
        }

        // Body Rows
        foreach ($this->Containers as $Container) {
            $line = [
                h($Container['id']),
                h($Container['name']),
            ];

            foreach ($this->Users as $User) {
                $permissionText = $this->getPermissionLevel($Container, $User);
                $line[] = $permissionText;
            }
            $lines[] = $line;
        }
        $sheet->fromArray($lines, null, 'A1', false);
    }

    private function getPermissionLevel(array|null $Container, array $User): string {
        $permission = 0;
        // As long as there's and a parent container was found...
        while (!$permission && $Container) {
            // Check if we have permission now.
            $permission = (int)($this->ContainerPermissions[$Container['id']][$User['id']] ?? 0);

            // Check again with parent Container.
            $Container = $this->getParentContainer($Container);
        }

        return match ($permission) {
            1 => 'R',
            2 => 'RW',
            default => '',
        };
    }

    /**
     * I will build the data for the Containers Sheet.
     * @return void
     */
    private function buildContainersData(): void {
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        if ($this->hasRootPrivileges === true) {
            $Containers = $ContainersTable->find()
                ->where(['Containers.containertype_id IN' => [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE]])
                ->orderBy(['Containers.id ASC'])
                ->disableHydration()
                ->toArray();
        } else {
            $Containers = $ContainersTable->find()
                ->andWhere([
                    'Containers.containertype_id IN' => [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE],
                    'Containers.id IN '              => $this->MY_RIGHTS
                ])
                ->orderBy(['Containers.id ASC'])
                ->disableHydration()
                ->toArray();
        }

        foreach ($Containers as &$Container) {
            $Container['name'] = '/' . $ContainersTable->treePath($Container['id'], '/');

            $this->Containers[$Container['id']] = $Container;
            $this->ContainerTree[$Container['id']] = $Container;
        }

        $this->ContainerTree = $this->buildContainerTree();

        $this->ContainerRoles = $this
            ->UsercontainerrolesTable
            ->find()
            ->contain(['Containers', 'Users'])
            ->disableHydration()
            ->toArray();

        $this->buildPermissionsMatrix();
    }

    private function getParentContainer(array $Container): array|null {
        if ($Container['parent_id']) {
            return $this->Containers[$Container['parent_id']] ?? null;
        }
        return null;
    }

    private function buildContainerTree(): array {
        // Ensure every object has a children array
        foreach ($this->ContainerTree as $id => &$Container) {
            $Container = (object)$Container;
            $Container->children = [];
        }

        $ContainerTree = [];
        foreach ($this->ContainerTree as $id => $Container) {
            $Container = (object)$Container;
            $Container->children = [];
            if (!empty($Container->parent_id)) {
                $this->ContainerTree[$Container->parent_id]->children[$id] = $Container;
            } else {
                // Root node
                $ContainerTree[$id] = $Container;
            }
        }
        return $ContainerTree;
    }


    /**
     * I will traverse ContainerRoles and Users to build the ContainerPermissions matrix.
     * @return void
     */
    private function buildPermissionsMatrix(): void {
        foreach ($this->Users as $User) {
            foreach ($User['usercontainerroles'] as $UCR) {
                if ($UCR['_joinData']['through_ldap']) {
                    foreach ($UCR['containers'] as $Container) {
                        $this->ContainerPermissions[(int)$Container['id']][(int)$User['id']] = (int)$Container['_joinData']['permission_level'];
                    }
                }
            }
        }

        // Load permissions from Container Roles
        foreach ($this->ContainerRoles as $ContainerRoles) {
            foreach ($ContainerRoles['users'] as $User) {
                foreach ($ContainerRoles['containers'] as $Container) {
                    $this->ContainerPermissions[(int)$Container['id']][(int)$User['id']] = (int)$Container['_joinData']['permission_level'];
                }
            }
        }

        // Override explicitly given permissions from Users
        foreach ($this->Users as $User) {
            foreach ($User['containers'] as $UserContainer) {
                $this->ContainerPermissions[(int)$UserContainer['id']][(int)$User['id']] = (int)$UserContainer['_joinData']['permission_level'];
            }
        }
    }
    
    /**
     * If openITCOCKPIT is configured to use LDAP, I will return an instance of LdapClient.
     * @return LdapClient|null
     */
    private function getLdapClient(): LdapClient|null {
        try {
            /** @var SystemsettingsTable $SystemSettingsTable */
            $SystemSettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            return LdapClient::fromSystemsettings($SystemSettingsTable->findAsArraySection('FRONTEND'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * I will check, if the given UserRole has the given Permission.
     * @param Usergroup $UserRole
     * @param int $permissionId
     * @return bool
     */
    private function userRoleHasPermission(array $UserRole, int $permissionId): bool {
        return in_array($permissionId, $this->UserRoleAcos[$UserRole['id']], true);
    }

    /**
     * I will add the given $Permission. If there are children in it, I will call myself recursively.
     * @param array $Permission
     * @param string $controller
     * @return void
     */
    private function addPermissionRow(array $Permission, string $controller = ''): void {
        if (str_contains($controller, 'Module')) {
            $this->Modules[$Permission['id']] = $controller;
        }
        if ($Permission['children']) {
            foreach ($Permission['children'] as $Child) {
                $this->addPermissionRow($Child, $Permission['alias']);
            }
            return;
        }
        $this->Permissions[] = [
            'module'     => $this->Modules[$Permission['parent_id']] ?? '',
            'id'         => $Permission['id'],
            'controller' => $controller,
            'action'     => $Permission['alias']
        ];
    }
}
