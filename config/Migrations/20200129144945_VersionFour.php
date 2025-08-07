<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class VersionFour
 * Update an openITCOCKPIT 3.7.2 database to 4.0
 * https://github.com/openITCOCKPIT/openITCOCKPIT/releases/tag/openITCOCKPIT-3.7.2
 *
 * Run migration:
 * oitc4 migrations migrate
 */
class VersionFour extends \Migrations\BaseMigration {

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
    public $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void {
        $this->table('deleted_hosts')
            ->changeColumn('name', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => false,
            ])
            ->update();

        $this->table('deleted_services')
            ->changeColumn('name', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => false,
            ])
            ->update();

        $this->table('hostescalations')
            ->changeColumn('timeperiod_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => true,
            ])
            ->update();

        $this->table('locations')
            ->changeColumn('latitude', 'float', [
                'default' => null,
                'limit'   => null,
                'null'    => true,
            ])
            ->changeColumn('longitude', 'float', [
                'default' => null,
                'limit'   => null,
                'null'    => true,
            ])
            ->update();

        $this->table('serviceescalations')
            ->changeColumn('timeperiod_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => true,
            ])
            ->update();

        $this->table('services')
            ->changeColumn('is_volatile', 'integer', [
                'default' => null,
                'limit'   => 1,
                'null'    => true,
            ])
            ->update();

        $this->table('servicetemplates')
            ->changeColumn('flap_detection_on_critical', 'integer', [
                'default' => '0',
                'limit'   => 1,
                'null'    => false,
            ])
            ->changeColumn('is_volatile', 'integer', [
                'default' => '0',
                'limit'   => 1,
                'null'    => false,
            ])
            ->changeColumn('check_freshness', 'integer', [
                'default' => '0',
                'limit'   => 1,
                'null'    => false,
            ])
            ->update();

        $this->table('users')
            ->removeColumn('status')
            ->removeColumn('linkedin_id')
            ->changeColumn('password', 'string', [
                'default' => '',
                'limit'   => 255,
                'null'    => false,
            ])
            ->changeColumn('recursive_browser', 'boolean', [
                'default' => '0',
                'length'  => null,
                'limit'   => null,
                'null'    => false,
            ])
            ->addColumn('i18n', 'string', [
                'after'   => 'timezone',
                'default' => 'en_US',
                'length'  => 100,
                'null'    => false
            ])
            ->update();
        if (!$this->hasTable('agentchecks')) {
            $this->table('agentchecks')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('plugin_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('agentconfigs')) {
            $this->table('agentconfigs')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('port', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('use_https', 'boolean', [
                    'default' => false,
                    'null'    => false,
                ])
                ->addColumn('insecure', 'boolean', [
                    'default' => true,
                    'null'    => true,
                ])
                ->addColumn('basic_auth', 'boolean', [
                    'default' => false,
                    'null'    => true,
                ])
                ->addColumn('proxy', 'boolean', [
                    'default' => true,
                    'null'    => false,
                ])
                ->addColumn('username', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('password', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('usercontainerroles')) {
            $this->table('usercontainerroles')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 10,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('usercontainerroles_to_containers')) {
            $this->table('usercontainerroles_to_containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('usercontainerrole_id', 'integer', [
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
                        'usercontainerrole_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('users_to_usercontainerroles')) {
            $this->table('users_to_usercontainerroles')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('usercontainerrole_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'user_id',
                    ]
                )
                ->addIndex(
                    [
                        'usercontainerrole_id',
                    ]
                )
                ->create();
        }

        $this->table('automaps')
            ->addColumn('use_paginator', 'boolean', [
                'after'   => 'group_by_host',
                'default' => '1',
                'length'  => null,
                'null'    => false,
            ])
            ->update();

        $this->table('systemdowntimes')
            ->addColumn('is_recursive', 'integer', [
                'after'   => 'duration',
                'default' => '0',
                'length'  => 11,
                'null'    => false,
            ])
            ->update();

        $this->table('users')
            ->addColumn('is_active', 'boolean', [
                'after'   => 'showstatsinmenu',
                'default' => '0',
                'length'  => null,
                'null'    => false,
            ])
            ->update();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void {

        $this->table('automaps')
            ->removeColumn('use_paginator')
            ->update();

        $this->table('deleted_hosts')
            ->changeColumn('name', 'string', [
                'default' => null,
                'length'  => 64,
                'null'    => false,
            ])
            ->update();

        $this->table('deleted_services')
            ->changeColumn('name', 'string', [
                'default' => null,
                'length'  => 64,
                'null'    => false,
            ])
            ->update();

        $this->table('hostescalations')
            ->changeColumn('timeperiod_id', 'integer', [
                'default' => null,
                'length'  => 11,
                'null'    => false,
            ])
            ->update();

        $this->table('locations')
            ->changeColumn('latitude', 'float', [
                'default' => '0',
                'length'  => null,
                'null'    => true,
            ])
            ->changeColumn('longitude', 'float', [
                'default' => '0',
                'length'  => null,
                'null'    => true,
            ])
            ->update();

        $this->table('serviceescalations')
            ->changeColumn('timeperiod_id', 'integer', [
                'default' => null,
                'length'  => 11,
                'null'    => false,
            ])
            ->update();

        $this->table('services')
            ->changeColumn('is_volatile', 'boolean', [
                'default' => null,
                'length'  => null,
                'null'    => true,
            ])
            ->update();

        $this->table('servicetemplates')
            ->changeColumn('flap_detection_on_critical', 'boolean', [
                'default' => '0',
                'length'  => null,
                'null'    => false,
            ])
            ->changeColumn('is_volatile', 'boolean', [
                'default' => '0',
                'length'  => null,
                'null'    => false,
            ])
            ->changeColumn('check_freshness', 'boolean', [
                'default' => '0',
                'length'  => null,
                'null'    => false,
            ])
            ->update();

        $this->table('systemdowntimes')
            ->removeColumn('is_recursive')
            ->update();

        $this->table('users')
            ->addColumn('status', 'integer', [
                'after'   => 'usergroup_id',
                'default' => '1',
                'length'  => 3,
                'null'    => false,
            ])
            ->addColumn('linkedin_id', 'string', [
                'after'   => 'phone',
                'default' => null,
                'length'  => 45,
                'null'    => true,
            ])
            ->changeColumn('password', 'string', [
                'default' => null,
                'length'  => 45,
                'null'    => false,
            ])
            ->changeColumn('recursive_browser', 'integer', [
                'default' => '0',
                'length'  => 1,
                'null'    => false,
            ])
            ->removeColumn('is_active')
            ->update();

        $this->table('agentchecks')->drop()->save();
        $this->table('agentconfigs')->drop()->save();
        $this->table('usercontainerroles')->drop()->save();
        $this->table('usercontainerroles_to_containers')->drop()->save();
        $this->table('users_to_usercontainerroles')->drop()->save();
    }
}
