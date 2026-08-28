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

use Migrations\BaseMigration;

class CreateUserDefaultTemplates extends BaseMigration {

    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void {

        if (!$this->hasTable('user_default_templates')) {
            $this->table('user_default_templates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 10,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('usergroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('timezone', 'string', [
                    'default' => 'Europe/Berlin',
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('i18n', 'string', [
                    'default' => 'en_US',
                    'length'  => 100,
                    'null'    => false
                ])
                ->addColumn('dateformat', 'string', [
                    'default' => 'H:i:s - d.m.Y',
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('showstatsinmenu', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('dashboard_tab_rotation', 'integer', [
                    'default' => '0',
                    'limit'   => 10,
                    'null'    => false,
                ])
                ->addColumn('paginatorlength', 'integer', [
                    'default' => '25',
                    'limit'   => 4,
                    'null'    => false,
                ])
                ->addColumn('recursive_browser', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('is_oauth', 'boolean', [
                    'default' => '0',
                    'length'  => null,
                    'null'    => false
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->create();
        }

        if (!$this->hasTable('user_default_templates_to_user_containers')) {
            $this->table('user_default_templates_to_user_containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('user_default_template_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('permission_level', 'integer', [
                    'default' => '1',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'user_default_template_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('ldapgroups_to_user_default_templates')) {
            $this->table('ldapgroups_to_user_default_templates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('ldapgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('user_default_template_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('user_default_templates_to_containers')) {
            $this->table('user_default_templates_to_containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('user_default_template_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'user_default_template_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }
    }
}
