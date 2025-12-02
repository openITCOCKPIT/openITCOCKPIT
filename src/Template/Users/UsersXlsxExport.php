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

use App\Model\Table\ContainersTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\TableRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class UsersXlsxExport {
    private Spreadsheet $Spreadsheet;
    private UsersTable $UsersTable;
    private ContainersTable $ContainersTable;
    private UsergroupsTable $UsergroupsTable;

    private array $Users;

    private array $Containers;

    private array $UserRoles;

    private array $Permissions;

    public function __construct() {
        $this->Spreadsheet = new Spreadsheet();
        $this->UsersTable = TableRegistry::getTableLocator()->get('Users');
        $this->ContainersTable = TableRegistry::getTableLocator()->get('Containers');
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
        $this->fetchData();

        $this->UsersSheet();
        $this->UserRolesSheet();
        $this->ContainersSheet();


        $writer = new Xlsx($this->Spreadsheet);
        $writer->save($fileName);
    }

    /**
     * @return void
     * @throws MissingEntityException
     */
    private function fetchData(): void {
        // Till now this is mock data.

        $this->Users = [
            1 => [
                'name'                  => 'Administrator',
                'firstname'             => 'Super',
                'lastname'              => 'User',
                'email'                 => 'root@localhost',
                'user_role_id'          => 1123455,
                'user_role'             => 'Administrator',
                'is_ldap_user'          => true,
                'UserRoleThroughLdapID' => 1,
                'UserRoleThroughLdap'   => 'Administrator'
            ],
            2 => [
                'name' => 'Foo Bar'
            ],
            3 => [
                'name' => 'Foo Baz'
            ],
            4 => [
                'name' => 'Corey Tailor'
            ],
            5 => [
                'name' => 'Jane Doe'
            ],
            6 => [
                'name' => 'John Doe'
            ]
        ];

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

        $this->UserRoles = [
            1 => [
                'name' => 'Administrator'
            ],
            2 => [
                'name' => 'Administrator_light'
            ],
            3 => [
                'name' => 'Viewer'
            ],
        ];

        $this->Permissions = [
            [
                'module'      => '',
                'controller'  => 'Servicetemplates',
                'action'      => 'index',
                'permissions' => [
                    1 => true,
                    2 => false,
                    3 => true
                ]
            ],
            [
                'module'      => '',
                'controller'  => 'Servicetemplates',
                'action'      => 'view',
                'permissions' => [
                    1 => true,
                    2 => true,
                    3 => true
                ]
            ],
            [
                'module'      => '',
                'controller'  => 'Servicetemplates',
                'action'      => 'add',
                'permissions' => [
                    1 => true,
                    2 => true,
                    3 => false
                ]
            ],
            [
                'module'      => '',
                'controller'  => 'Servicetemplates',
                'action'      => 'edit',
                'permissions' => [
                    1 => true,
                    2 => true,
                    3 => false
                ]
            ],
            [
                'module'      => '',
                'controller'  => 'Servicetemplates',
                'action'      => 'delete',
                'permissions' => [
                    1 => true,
                    2 => false,
                    3 => false
                ]
            ],
            [
                'module'      => 'Eventcorrelation',
                'controller'  => 'Eventcorrelation',
                'action'      => 'index',
                'permissions' => [
                    1 => true,
                    2 => true,
                    3 => true
                ]
            ],
        ];
    }

    /**
     * I will build the entire Sheet "Users".
     * @return void
     */
    private function UsersSheet(): void {
        $sheet = $this->Spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');
        $row = 1;
        $col = 1;

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
            $col = 1;

            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserId}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['firstname']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['lastname']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['email']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['user_role_id']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['user_role']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['is_ldap_user']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['UserRoleThroughLdapID']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['UserRoleThroughLdap']}");
        }
    }

    /**
     * I will build the entire Sheet "User Roles".
     * @return void
     */
    private function UserRolesSheet(): void {
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('User Roles');
        $row = 1;
        $col = 1;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), '(Module) + Controller');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Action');
        foreach ($this->UserRoles as $UserRoleId => $UserRole) {
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserRole['name']} [ID $UserRoleId]");
        }

        // Body Rows
        foreach ($this->Permissions as $Permission) {
            $row++;
            $col = 1;

            $moduleControllerString = $Permission['controller'];
            if ($Permission['module']) {
                $moduleControllerString = "{$Permission['module']}/{$Permission['controller']}";
            }

            $sheet->setCellValue(self::getCellPosition($col++, $row), "$moduleControllerString");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$Permission['action']}");
            foreach ($this->UserRoles as $UserRoleId => $UserRole) {
                $cellValue = 'YES';
                if ($Permission['permissions'][$UserRoleId] === false) {
                    $cellValue = 'NO';
                }
                $sheet->setCellValue(self::getCellPosition($col++, $row), $cellValue);
            }
        }
    }

    /**
     * I will build the entire Sheet "Containers".
     * @return void
     */
    private function ContainersSheet(): void {
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('Containers');
        $row = 1;
        $col = 1;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Container ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Container');
        foreach ($this->Users as $UserRoleID => $UserRole) {
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserRole['name']} [ID $UserRoleID]");
        }

        // Body Rows
        foreach ($this->Containers as $ContainerID => $Container) {
            $row++;
            $col = 1;

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
        return $letters . $row;
    }
}
