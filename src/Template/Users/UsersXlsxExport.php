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
use App\Model\Table\ContainersTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Ldap\LdapClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class UsersXlsxExport {
    private Spreadsheet $Spreadsheet;
    private UsersTable $UsersTable;
    private UsercontainerrolesTable $UsercontainerrolesTable;
    private ContainersTable $ContainersTable;
    private UsergroupsTable $UsergroupsTable;
    private AcosTable $AcosTable;
    private SystemsettingsTable $SystemsettingsTable;

    private array $Users;

    private array $Containers;

    private array $UserRoles;

    private array $Permissions;
    private array $MY_RIGHTS;
    private bool $hasRootPrivileges;

    public function __construct(array $MY_RIGHTS, bool $hasRootPrivileges) {
        $this->MY_RIGHTS = $MY_RIGHTS;
        $this->hasRootPrivileges = $hasRootPrivileges;
        $this->Spreadsheet = new Spreadsheet();

        $this->SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $this->UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');
        $this->UsersTable = TableRegistry::getTableLocator()->get('Users');
        $this->ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $this->UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');
        $this->AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');
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

    private function buildUserRolesData(): void {
        // Till now this is mock data.

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
            ->all()->toArray();

        $acos = $this->AcosTable->find('threaded')
            ->disableHydration()
            ->all();
        $AclDependencies = new AclDependencies();
        $AclDList = $AclDependencies->filterAcosForFrontend($acos->toArray());
        foreach ($AclDList as $AclD) {
            if ($AclD['children']) {
                $this->addPermissionRow($AclD);
            }
        }
    }

    private function buildUserData(): void {
        $all_tmp_users = $this->UsersTable->getUsersExport($this->MY_RIGHTS);
        $LdapClient = $this->getLdapClient();
        foreach ($all_tmp_users as $_user) {
            /** @var User $_user */
            $user = $_user->toArray();


            if ($LdapClient && !empty($user['samaccountname'])) {
                $ldapUser = $LdapClient->getUser($user['samaccountname'], true);
                if (!$ldapUser) {
                    continue;
                }


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

                $user = array_merge($user, $ldapUser);
            }
            $this->Users[] = $user;
        }
    }

    private function buildContainersData(): void {
        $this->Containers = [
            1 => [
                'name'  => '/root',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ],
            2 => [
                'name'  => '/root/openITCOCKPIT',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ],
            3 => [
                'name'  => '/root/openITCOCKPIT/Berlin',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ],
            4 => [
                'name'  => '/root/openITCOCKPIT/Frankfurt',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ],
            5 => [
                'name'  => '/root/openITCOCKPIT/Fulda',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ],
            6 => [
                'name'  => '/root/openITCOCKPIT/Fulda/Demo',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ],
            7 => [
                'name'  => '/root/openITCOCKPIT/Hamburg',
                'Users' => [
                    1 => 'RW',
                    2 => 'R',
                    3 => 'RW',
                    4 => 'R',
                    5 => null,
                ]
            ]
        ];
    }

    /**
     * I will build the entire Sheet "Users".
     * @return void
     */
    private function UsersSheet(): void {
        $this->buildUserData();
        $sheet = $this->Spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');
        $row = 0;
        $col = 0;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'First name');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Last name');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Mail');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User Role ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User role / Fallback User role');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Is LDAP User');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User role through LDAP ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User role through LDAP');

        // Body Rows
        foreach ($this->Users as $UserId => $User) {
            $row++;
            $col = 0;

            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserId}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['firstname']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['lastname']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['email']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['usergroup']['id']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['usergroup']['name']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), $User['samaccountname'] ? 'YES' : 'NO');
            $sheet->setCellValue(self::getCellPosition($col++, $row), $User['UserRoleThroughLdap']['id'] ?? '');
            $sheet->setCellValue(self::getCellPosition($col++, $row), $User['UserRoleThroughLdap']['name'] ?? '');
        }
    }

    /**
     * I will build the entire Sheet "User Roles".
     * @return void
     */
    private function UserRolesSheet(): void {
        $this->buildUserRolesData();
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('User Roles');
        $row = 0;
        $col = 0;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), '(Module) + Controller');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Action');
        foreach ($this->UserRoles as $UserRole) {
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserRole['name']} [ID {$UserRole['id']}]");
        }

        // Body Rows
        foreach ($this->Permissions as $Permission) {
            $row++;
            $col = 0;

            $moduleControllerString = $Permission['controller'];
            if ($Permission['module']) {
                $moduleControllerString = "{$Permission['module']}/{$Permission['controller']}";
            }

            $sheet->setCellValue(self::getCellPosition($col++, $row), "$moduleControllerString");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$Permission['action']}");
            foreach ($this->UserRoles as $UserRole) {
                $cellValue = 'dont know';
                $sheet->setCellValue(self::getCellPosition($col++, $row), $cellValue);
            }
        }
    }

    private function addPermissionRow(array $Permission, string $controller = ''): void {
        if ($Permission['alias'] === 'controllers') {
            foreach ($Permission['children'] as $Child) {
                $this->addPermissionRow($Child);
            }
            return;
        }
        if ($Permission['children']) {
            foreach ($Permission['children'] as $Child) {
                $this->addPermissionRow($Child, $Permission['alias']);
            }
            return;
        }
        $this->Permissions [] = [
            'module'      => '',
            'id'          => $Permission['id'],
            'controller'  => $controller,
            'action'      => $Permission['alias'],
            'permissions' => [
                1 => true,
                2 => false,
                3 => true
            ]
        ];
    }

    /**
     * I will build the entire Sheet "Containers".
     * @return void
     */
    private function ContainersSheet(): void {
        $this->buildContainersData();
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('Containers');
        $row = 0;
        $col = 0;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Container ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Container');
        foreach ($this->Users as $UserRoleID => $UserRole) {
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserRole['name']} [ID $UserRoleID]");
        }

        // Body Rows
        foreach ($this->Containers as $ContainerID => $Container) {
            $row++;
            $col = 0;

            $sheet->setCellValue(self::getCellPosition($col++, $row), "$ContainerID");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$Container['name']}");
            foreach ($this->Users as $UserId => $User) {
                $permission = $Container['Users'][$UserId] ?? '';
                $sheet->setCellValue(self::getCellPosition($col++, $row), $permission);
            }
        }
    }

    /**
     * I will return the Excel Cell Position like A1, B2, C3, ...
     * @param int $col
     * @param int $row
     * @return string
     */
    private static function getCellPosition(int $col, int $row): string {
        $letters = '';
        while ($col >= 0) {
            $letters = chr(($col % 26) + 65) . $letters;
            $col = (int)($col / 26) - 1;
        }
        return $letters . $row + 1;
    }


    /**
     * If openITCOCKPIT is configured to use LDAP, I will return an instance of LdapClient.
     * @return LdapClient|null
     */
    private function getLdapClient(): LdapClient|null {
        try {
            return LdapClient::fromSystemsettings($this->SystemsettingsTable->findAsArraySection('FRONTEND'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
