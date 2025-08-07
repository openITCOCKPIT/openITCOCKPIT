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
 * Class Initial
 *
 * Initial create:
 * oitc4 bake migration_snapshot Initial
 *
 * Create a new diff
 * oitc4 bake migration_diff NaeName
 *
 * Run migration:
 * oitc4 migrations migrate
 */
class Initial extends \Migrations\BaseMigration {

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
        if (!$this->hasTable('acos')) {
            $this->table('acos')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 10,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('parent_id', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('model', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('foreign_key', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('alias', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('lft', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('rght', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->create();
        }

        if (!$this->hasTable('apikeys')) {
            $this->table('apikeys')
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
                ->addColumn('apikey', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'apikey',
                        'user_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('aros')) {
            $this->table('aros')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 10,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('parent_id', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('model', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('foreign_key', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('alias', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('lft', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('rght', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->create();
        }

        if (!$this->hasTable('aros_acos')) {
            $this->table('aros_acos')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 10,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('aro_id', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => false,
                ])
                ->addColumn('aco_id', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => false,
                ])
                ->addColumn('_create', 'string', [
                    'default' => '0',
                    'limit'   => 2,
                    'null'    => false,
                ])
                ->addColumn('_read', 'string', [
                    'default' => '0',
                    'limit'   => 2,
                    'null'    => false,
                ])
                ->addColumn('_update', 'string', [
                    'default' => '0',
                    'limit'   => 2,
                    'null'    => false,
                ])
                ->addColumn('_delete', 'string', [
                    'default' => '0',
                    'limit'   => 2,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'aro_id',
                        'aco_id',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('automaps')) {
            $this->table('automaps')
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
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('host_regex', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('service_regex', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('show_ok', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('show_warning', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('show_critical', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('show_unknown', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('show_acknowledged', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('show_downtime', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('show_label', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('group_by_host', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('font_size', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('recursive', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
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

        if (!$this->hasTable('calendar_holidays')) {
            $this->table('calendar_holidays')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('calendar_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('default_holiday', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('date', 'date', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('calendars')) {
            $this->table('calendars')
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
                ->addColumn('description', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'container_id',
                        'name',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('changelogs')) {
            $this->table('changelogs')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('model', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('action', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('objecttype_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('data', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('name', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'created',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('changelogs_to_containers')) {
            $this->table('changelogs_to_containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('changelog_id', 'integer', [
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
                        'changelog_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('commandarguments')) {
            $this->table('commandarguments')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => false,
                ])
                ->addColumn('human_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'command_id',
                        'name',
                        'human_name',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('commands')) {
            $this->table('commands')
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
                    'null'    => true,
                ])
                ->addColumn('command_line', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('command_type', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('human_args', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('description', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('configuration_files')) {
            $this->table('configuration_files')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('config_file', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('key', 'string', [
                    'default' => null,
                    'limit'   => 2000,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 2000,
                    'null'    => false,
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

        if (!$this->hasTable('configuration_queue')) {
            $this->table('configuration_queue')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('task', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('data', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('json_data', 'string', [
                    'default' => null,
                    'limit'   => 2000,
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

        if (!$this->hasTable('contactgroups')) {
            $this->table('contactgroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('contactgroups_to_hostescalations')) {
            $this->table('contactgroups_to_hostescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'hostescalation_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contactgroups_to_hosts')) {
            $this->table('contactgroups_to_hosts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contactgroups_to_hosttemplates')) {
            $this->table('contactgroups_to_hosttemplates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hosttemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'hosttemplate_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contactgroups_to_serviceescalations')) {
            $this->table('contactgroups_to_serviceescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('serviceescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'serviceescalation_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contactgroups_to_services')) {
            $this->table('contactgroups_to_services')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'service_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contactgroups_to_servicetemplates')) {
            $this->table('contactgroups_to_servicetemplates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'servicetemplate_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts')) {
            $this->table('contacts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('email', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('phone', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('host_timeperiod_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_timeperiod_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_notifications_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('service_notifications_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_service_recovery', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_service_warning', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_service_unknown', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_service_critical', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_service_flapping', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_service_downtime', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_host_recovery', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_host_down', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_host_unreachable', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_host_flapping', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_host_downtime', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('host_push_notifications_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('service_push_notifications_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'user_id',
                        'host_push_notifications_enabled',
                        'service_push_notifications_enabled',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_contactgroups')) {
            $this->table('contacts_to_contactgroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('contactgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'contactgroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_containers')) {
            $this->table('contacts_to_containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
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
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_hostcommands')) {
            $this->table('contacts_to_hostcommands')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'command_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_hostescalations')) {
            $this->table('contacts_to_hostescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'hostescalation_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_hosts')) {
            $this->table('contacts_to_hosts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_hosttemplates')) {
            $this->table('contacts_to_hosttemplates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hosttemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'hosttemplate_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_servicecommands')) {
            $this->table('contacts_to_servicecommands')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'command_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_serviceescalations')) {
            $this->table('contacts_to_serviceescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('serviceescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'serviceescalation_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_services')) {
            $this->table('contacts_to_services')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'service_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('contacts_to_servicetemplates')) {
            $this->table('contacts_to_servicetemplates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('contact_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'contact_id',
                    ]
                )
                ->addIndex(
                    [
                        'servicetemplate_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('containers')) {
            $this->table('containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('containertype_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('parent_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('lft', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('rght', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('cronjobs')) {
            $this->table('cronjobs')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('task', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('plugin', 'string', [
                    'default' => 'Core',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('interval', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('enabled', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('cronschedules')) {
            $this->table('cronschedules')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('cronjob_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('is_running', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('start_time', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('end_time', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('customvariables')) {
            $this->table('customvariables')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('objecttype_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
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

        if (!$this->hasTable('dashboard_tabs')) {
            $this->table('dashboard_tabs')
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
                ->addColumn('position', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('shared', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('source_tab_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('check_for_updates', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('last_update', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('locked', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
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

        if (!$this->hasTable('deleted_hosts')) {
            $this->table('deleted_hosts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('hosttemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('deleted_perfdata', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('deleted_services')) {
            $this->table('deleted_services')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('host_uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('deleted_perfdata', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('documentations')) {
            $this->table('documentations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('content', 'text', [
                    'default' => null,
                    'limit'   => null,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('exports')) {
            $this->table('exports')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('task', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('text', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('finished', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('successfully', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'task',
                        'text',
                        'finished',
                        'successfully',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('graph_collections')) {
            $this->table('graph_collections')
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
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'id',
                        'name',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('graph_tmpl_to_graph_collection')) {
            $this->table('graph_tmpl_to_graph_collection')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('graphgen_tmpl_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('graph_collection_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('graphgen_tmpl_confs')) {
            $this->table('graphgen_tmpl_confs')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('graphgen_tmpl_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('data_sources', 'string', [
                    'default' => null,
                    'limit'   => 256,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('graphgen_tmpls')) {
            $this->table('graphgen_tmpls')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => false,
                ])
                ->addColumn('relative_time', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('hostcommandargumentvalues')) {
            $this->table('hostcommandargumentvalues')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('commandargument_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
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

        if (!$this->hasTable('hostdependencies')) {
            $this->table('hostdependencies')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('inherits_parent', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('execution_fail_on_up', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_down', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_unreachable', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_pending', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_none', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_up', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_down', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_unreachable', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_pending', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_none', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('hostescalations')) {
            $this->table('hostescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('first_notification', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('last_notification', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('notification_interval', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_recovery', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_down', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_unreachable', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('hostgroups')) {
            $this->table('hostgroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('hostgroup_url', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('hostgroups_to_hostdependencies')) {
            $this->table('hostgroups_to_hostdependencies')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('hostgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostdependency_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('dependent', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'hostgroup_id',
                        'dependent',
                    ]
                )
                ->addIndex(
                    [
                        'hostdependency_id',
                        'dependent',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hostgroups_to_hostescalations')) {
            $this->table('hostgroups_to_hostescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('hostgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('excluded', 'integer', [
                    'default' => '0',
                    'limit'   => 4,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'hostgroup_id',
                        'excluded',
                    ]
                )
                ->addIndex(
                    [
                        'hostescalation_id',
                        'excluded',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hosts')) {
            $this->table('hosts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
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
                ->addColumn('hosttemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('address', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => false,
                ])
                ->addColumn('command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('eventhandler_command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('check_interval', 'integer', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => true,
                ])
                ->addColumn('retry_interval', 'integer', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => true,
                ])
                ->addColumn('max_check_attempts', 'integer', [
                    'default' => null,
                    'limit'   => 3,
                    'null'    => true,
                ])
                ->addColumn('first_notification_delay', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('notification_interval', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('notify_on_down', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_unreachable', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_recovery', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_flapping', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_downtime', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_up', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_down', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_unreachable', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('low_flap_threshold', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('high_flap_threshold', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('process_performance_data', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('freshness_checks_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('freshness_threshold', 'integer', [
                    'default' => null,
                    'limit'   => 8,
                    'null'    => true,
                ])
                ->addColumn('passive_checks_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('event_handler_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('active_checks_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('retain_status_information', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('retain_nonstatus_information', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('notifications_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('notes', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('priority', 'integer', [
                    'default' => null,
                    'limit'   => 2,
                    'null'    => true,
                ])
                ->addColumn('check_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('notify_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('tags', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('own_contacts', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('own_contactgroups', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('own_customvariables', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('host_url', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('satellite_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('host_type', 'integer', [
                    'default' => '1',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('disabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('usage_flag', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('hosts_to_containers')) {
            $this->table('hosts_to_containers')
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
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hosts_to_hostdependencies')) {
            $this->table('hosts_to_hostdependencies')
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
                    'null'    => false,
                ])
                ->addColumn('hostdependency_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('dependent', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                        'dependent',
                    ]
                )
                ->addIndex(
                    [
                        'hostdependency_id',
                        'dependent',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hosts_to_hostescalations')) {
            $this->table('hosts_to_hostescalations')
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
                    'null'    => false,
                ])
                ->addColumn('hostescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('excluded', 'integer', [
                    'default' => '0',
                    'limit'   => 4,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                        'excluded',
                    ]
                )
                ->addIndex(
                    [
                        'hostescalation_id',
                        'excluded',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hosts_to_hostgroups')) {
            $this->table('hosts_to_hostgroups')
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
                    'null'    => false,
                ])
                ->addColumn('hostgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->addIndex(
                    [
                        'hostgroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hosts_to_parenthosts')) {
            $this->table('hosts_to_parenthosts')
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
                    'null'    => false,
                ])
                ->addColumn('parenthost_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->addIndex(
                    [
                        'parenthost_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('hosttemplatecommandargumentvalues')) {
            $this->table('hosttemplatecommandargumentvalues')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('commandargument_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hosttemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
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

        if (!$this->hasTable('hosttemplates')) {
            $this->table('hosttemplates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('hosttemplatetype_id', 'integer', [
                    'default' => '1',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('command_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('check_command_args', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
                ])
                ->addColumn('eventhandler_command_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('check_interval', 'integer', [
                    'default' => '1',
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('retry_interval', 'integer', [
                    'default' => '3',
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('max_check_attempts', 'integer', [
                    'default' => '1',
                    'limit'   => 3,
                    'null'    => false,
                ])
                ->addColumn('first_notification_delay', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('notification_interval', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('notify_on_down', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_unreachable', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_recovery', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_flapping', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_downtime', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_up', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_down', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_unreachable', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('low_flap_threshold', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('high_flap_threshold', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('process_performance_data', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('freshness_checks_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('freshness_threshold', 'integer', [
                    'default' => '0',
                    'limit'   => 8,
                    'null'    => true,
                ])
                ->addColumn('passive_checks_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('event_handler_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('active_checks_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('retain_status_information', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('retain_nonstatus_information', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('notifications_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('notes', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('priority', 'integer', [
                    'default' => '1',
                    'limit'   => 2,
                    'null'    => false,
                ])
                ->addColumn('check_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('notify_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('tags', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_url', 'string', [
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('hosttemplates_to_hostgroups')) {
            $this->table('hosttemplates_to_hostgroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('hosttemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'hosttemplate_id',
                    ]
                )
                ->addIndex(
                    [
                        'hostgroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('instantreports')) {
            $this->table('instantreports')
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
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('evaluation', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('type', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('reflection', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('downtimes', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('summary', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('send_email', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('send_interval', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('last_send_date', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
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

        if (!$this->hasTable('instantreports_to_hostgroups')) {
            $this->table('instantreports_to_hostgroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('instantreport_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'instantreport_id',
                    ]
                )
                ->addIndex(
                    [
                        'hostgroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('instantreports_to_hosts')) {
            $this->table('instantreports_to_hosts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('instantreport_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'instantreport_id',
                    ]
                )
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('instantreports_to_servicegroups')) {
            $this->table('instantreports_to_servicegroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('instantreport_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicegroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'instantreport_id',
                    ]
                )
                ->addIndex(
                    [
                        'servicegroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('instantreports_to_services')) {
            $this->table('instantreports_to_services')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('instantreport_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'instantreport_id',
                    ]
                )
                ->addIndex(
                    [
                        'service_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('instantreports_to_users')) {
            $this->table('instantreports_to_users')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('instantreport_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'instantreport_id',
                    ]
                )
                ->addIndex(
                    [
                        'user_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('locations')) {
            $this->table('locations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('latitude', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('longitude', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('timezone', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('macros')) {
            $this->table('macros')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('password', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
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


        if (!$this->hasTable('proxies')) {
            $this->table('proxies')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('ipaddress', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('port', 'integer', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('enabled', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('registers')) {
            $this->table('registers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('license', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('accepted', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('apt', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
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

        if (!$this->hasTable('servicecommandargumentvalues')) {
            $this->table('servicecommandargumentvalues')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('commandargument_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
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

        if (!$this->hasTable('servicedependencies')) {
            $this->table('servicedependencies')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('inherits_parent', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('execution_fail_on_ok', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_warning', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_unknown', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_critical', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_fail_on_pending', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('execution_none', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_ok', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_warning', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_unknown', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_critical', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_fail_on_pending', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notification_none', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('serviceescalations')) {
            $this->table('serviceescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('first_notification', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('last_notification', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('notification_interval', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_recovery', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_warning', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_unknown', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('escalate_on_critical', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('serviceeventcommandargumentvalues')) {
            $this->table('serviceeventcommandargumentvalues')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('commandargument_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
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

        if (!$this->hasTable('servicegroups')) {
            $this->table('servicegroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('servicegroup_url', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('servicegroups_to_servicedependencies')) {
            $this->table('servicegroups_to_servicedependencies')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('servicegroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicedependency_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('dependent', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'servicegroup_id',
                        'dependent',
                    ]
                )
                ->addIndex(
                    [
                        'servicedependency_id',
                        'dependent',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('servicegroups_to_serviceescalations')) {
            $this->table('servicegroups_to_serviceescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('servicegroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('serviceescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('excluded', 'integer', [
                    'default' => '0',
                    'limit'   => 4,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'servicegroup_id',
                        'excluded',
                    ]
                )
                ->addIndex(
                    [
                        'serviceescalation_id',
                        'excluded',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('services')) {
            $this->table('services')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 1500,
                    'null'    => true,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('check_command_args', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
                ])
                ->addColumn('eventhandler_command_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('notify_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('check_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('check_interval', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('retry_interval', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('max_check_attempts', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('first_notification_delay', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('notification_interval', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('notify_on_warning', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_unknown', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_critical', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_recovery', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_flapping', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('notify_on_downtime', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('is_volatile', 'boolean', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_ok', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_warning', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_unknown', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_on_critical', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('low_flap_threshold', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('high_flap_threshold', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('process_performance_data', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('freshness_checks_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 8,
                    'null'    => true,
                ])
                ->addColumn('freshness_threshold', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('passive_checks_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('event_handler_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('active_checks_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('notifications_enabled', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('notes', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('priority', 'integer', [
                    'default' => null,
                    'limit'   => 2,
                    'null'    => true,
                ])
                ->addColumn('tags', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('own_contacts', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('own_contactgroups', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('own_customvariables', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('service_url', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('service_type', 'integer', [
                    'default' => '1',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('disabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addColumn('usage_flag', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'uuid',
                        'host_id',
                        'disabled',
                    ]
                )
                ->addIndex(
                    [
                        'host_id',
                        'disabled',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('services_to_servicedependencies')) {
            $this->table('services_to_servicedependencies')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicedependency_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('dependent', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'service_id',
                        'dependent',
                    ]
                )
                ->addIndex(
                    [
                        'servicedependency_id',
                        'dependent',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('services_to_serviceescalations')) {
            $this->table('services_to_serviceescalations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('serviceescalation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('excluded', 'integer', [
                    'default' => '0',
                    'limit'   => 4,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'service_id',
                        'excluded',
                    ]
                )
                ->addIndex(
                    [
                        'serviceescalation_id',
                        'excluded',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('services_to_servicegroups')) {
            $this->table('services_to_servicegroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicegroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'service_id',
                    ]
                )
                ->addIndex(
                    [
                        'servicegroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('servicetemplatecommandargumentvalues')) {
            $this->table('servicetemplatecommandargumentvalues')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('commandargument_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
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

        if (!$this->hasTable('servicetemplateeventcommandargumentvalues')) {
            $this->table('servicetemplateeventcommandargumentvalues')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('commandargument_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
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

        if (!$this->hasTable('servicetemplategroups')) {
            $this->table('servicetemplategroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('servicetemplates')) {
            $this->table('servicetemplates')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('template_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('servicetemplatetype_id', 'integer', [
                    'default' => '1',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('check_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('notify_period_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('command_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('check_command_args', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
                ])
                ->addColumn('checkcommand_info', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('eventhandler_command_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('check_interval', 'integer', [
                    'default' => '1',
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('retry_interval', 'integer', [
                    'default' => '3',
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('max_check_attempts', 'integer', [
                    'default' => '1',
                    'limit'   => 3,
                    'null'    => false,
                ])
                ->addColumn('first_notification_delay', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('notification_interval', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('notify_on_warning', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_unknown', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_critical', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_recovery', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_flapping', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('notify_on_downtime', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_ok', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_warning', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_unknown', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('flap_detection_on_critical', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('low_flap_threshold', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('high_flap_threshold', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('process_performance_data', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('freshness_checks_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('freshness_threshold', 'integer', [
                    'default' => '0',
                    'limit'   => 8,
                    'null'    => true,
                ])
                ->addColumn('passive_checks_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('event_handler_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('active_checks_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('retain_status_information', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('retain_nonstatus_information', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('notifications_enabled', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('notes', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('priority', 'integer', [
                    'default' => null,
                    'limit'   => 2,
                    'null'    => true,
                ])
                ->addColumn('tags', 'string', [
                    'default' => null,
                    'limit'   => 1500,
                    'null'    => true,
                ])
                ->addColumn('service_url', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('is_volatile', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('check_freshness', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('servicetemplates_to_servicegroups')) {
            $this->table('servicetemplates_to_servicegroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicegroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'servicetemplate_id',
                    ]
                )
                ->addIndex(
                    [
                        'servicegroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('servicetemplates_to_servicetemplategroups')) {
            $this->table('servicetemplates_to_servicetemplategroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('servicetemplate_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('servicetemplategroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'servicetemplategroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'servicetemplate_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('systemdowntimes')) {
            $this->table('systemdowntimes')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('objecttype_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('downtimetype_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('weekdays', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('day_of_month', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('from_time', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('to_time', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('duration', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('comment', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('author', 'string', [
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

        if (!$this->hasTable('systemfailures')) {
            $this->table('systemfailures')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('start_time', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('end_time', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('comment', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('user_id', 'integer', [
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

        if (!$this->hasTable('systemsettings')) {
            $this->table('systemsettings')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('key', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('info', 'string', [
                    'default' => null,
                    'limit'   => 1500,
                    'null'    => false,
                ])
                ->addColumn('section', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
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

        if (!$this->hasTable('tenants')) {
            $this->table('tenants')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('is_active', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('number_users', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('max_users', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('number_hosts', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('number_services', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('firstname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('lastname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('street', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('zipcode', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => true,
                ])
                ->addColumn('city', 'string', [
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

        if (!$this->hasTable('timeperiod_timeranges')) {
            $this->table('timeperiod_timeranges')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('timeperiod_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('day', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
                    'null'    => false,
                ])
                ->addColumn('start', 'string', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('end', 'string', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('timeperiods')) {
            $this->table('timeperiods')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 6,
                    'null'    => false,
                ])
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
                ->addColumn('calendar_id', 'integer', [
                    'default' => '0',
                    'limit'   => 6,
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
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('usergroups')) {
            $this->table('usergroups')
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
                ->addColumn('description', 'string', [
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

        if (!$this->hasTable('users')) {
            $this->table('users')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 10,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('usergroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('status', 'integer', [
                    'default' => '1',
                    'limit'   => 3,
                    'null'    => false,
                ])
                ->addColumn('email', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => false,
                ])
                ->addColumn('password', 'string', [
                    'default' => null,
                    'limit'   => 45,
                    'null'    => false,
                ])
                ->addColumn('firstname', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => false,
                ])
                ->addColumn('lastname', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => false,
                ])
                ->addColumn('position', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('company', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('phone', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('linkedin_id', 'string', [
                    'default' => null,
                    'limit'   => 45,
                    'null'    => true,
                ])
                ->addColumn('timezone', 'string', [
                    'default' => 'Europe/Berlin',
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('dateformat', 'string', [
                    'default' => 'H:i:s - d.m.Y',
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('image', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('onetimetoken', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => true,
                ])
                ->addColumn('samaccountname', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => true,
                ])
                ->addColumn('ldap_dn', 'string', [
                    'default' => null,
                    'limit'   => 512,
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

        if (!$this->hasTable('users_to_containers')) {
            $this->table('users_to_containers')
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
                        'user_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('widgets')) {
            $this->table('widgets')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('dashboard_tab_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('type_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('row', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('col', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('width', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('height', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('title', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('color', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('directive', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('icon', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('json_data', 'string', [
                    'default' => null,
                    'limit'   => 2000,
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
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void {
        $this->table('acos')->drop()->save();
        $this->table('apikeys')->drop()->save();
        $this->table('aros')->drop()->save();
        $this->table('aros_acos')->drop()->save();
        $this->table('automaps')->drop()->save();
        $this->table('calendar_holidays')->drop()->save();
        $this->table('calendars')->drop()->save();
        $this->table('changelogs')->drop()->save();
        $this->table('changelogs_to_containers')->drop()->save();
        $this->table('commandarguments')->drop()->save();
        $this->table('commands')->drop()->save();
        $this->table('configuration_files')->drop()->save();
        $this->table('configuration_queue')->drop()->save();
        $this->table('contactgroups')->drop()->save();
        $this->table('contactgroups_to_hostescalations')->drop()->save();
        $this->table('contactgroups_to_hosts')->drop()->save();
        $this->table('contactgroups_to_hosttemplates')->drop()->save();
        $this->table('contactgroups_to_serviceescalations')->drop()->save();
        $this->table('contactgroups_to_services')->drop()->save();
        $this->table('contactgroups_to_servicetemplates')->drop()->save();
        $this->table('contacts')->drop()->save();
        $this->table('contacts_to_contactgroups')->drop()->save();
        $this->table('contacts_to_containers')->drop()->save();
        $this->table('contacts_to_hostcommands')->drop()->save();
        $this->table('contacts_to_hostescalations')->drop()->save();
        $this->table('contacts_to_hosts')->drop()->save();
        $this->table('contacts_to_hosttemplates')->drop()->save();
        $this->table('contacts_to_servicecommands')->drop()->save();
        $this->table('contacts_to_serviceescalations')->drop()->save();
        $this->table('contacts_to_services')->drop()->save();
        $this->table('contacts_to_servicetemplates')->drop()->save();
        $this->table('containers')->drop()->save();
        $this->table('cronjobs')->drop()->save();
        $this->table('cronschedules')->drop()->save();
        $this->table('customvariables')->drop()->save();
        $this->table('dashboard_tabs')->drop()->save();
        $this->table('deleted_hosts')->drop()->save();
        $this->table('deleted_services')->drop()->save();
        $this->table('documentations')->drop()->save();
        $this->table('exports')->drop()->save();
        $this->table('graph_collections')->drop()->save();
        $this->table('graph_tmpl_to_graph_collection')->drop()->save();
        $this->table('graphgen_tmpl_confs')->drop()->save();
        $this->table('graphgen_tmpls')->drop()->save();
        $this->table('hostcommandargumentvalues')->drop()->save();
        $this->table('hostdependencies')->drop()->save();
        $this->table('hostescalations')->drop()->save();
        $this->table('hostgroups')->drop()->save();
        $this->table('hostgroups_to_hostdependencies')->drop()->save();
        $this->table('hostgroups_to_hostescalations')->drop()->save();
        $this->table('hosts')->drop()->save();
        $this->table('hosts_to_containers')->drop()->save();
        $this->table('hosts_to_hostdependencies')->drop()->save();
        $this->table('hosts_to_hostescalations')->drop()->save();
        $this->table('hosts_to_hostgroups')->drop()->save();
        $this->table('hosts_to_parenthosts')->drop()->save();
        $this->table('hosttemplatecommandargumentvalues')->drop()->save();
        $this->table('hosttemplates')->drop()->save();
        $this->table('hosttemplates_to_hostgroups')->drop()->save();
        $this->table('instantreports')->drop()->save();
        $this->table('instantreports_to_hostgroups')->drop()->save();
        $this->table('instantreports_to_hosts')->drop()->save();
        $this->table('instantreports_to_servicegroups')->drop()->save();
        $this->table('instantreports_to_services')->drop()->save();
        $this->table('instantreports_to_users')->drop()->save();
        $this->table('locations')->drop()->save();
        $this->table('macros')->drop()->save();
        $this->table('proxies')->drop()->save();
        $this->table('registers')->drop()->save();
        $this->table('servicecommandargumentvalues')->drop()->save();
        $this->table('servicedependencies')->drop()->save();
        $this->table('serviceescalations')->drop()->save();
        $this->table('serviceeventcommandargumentvalues')->drop()->save();
        $this->table('servicegroups')->drop()->save();
        $this->table('servicegroups_to_servicedependencies')->drop()->save();
        $this->table('servicegroups_to_serviceescalations')->drop()->save();
        $this->table('services')->drop()->save();
        $this->table('services_to_servicedependencies')->drop()->save();
        $this->table('services_to_serviceescalations')->drop()->save();
        $this->table('services_to_servicegroups')->drop()->save();
        $this->table('servicetemplatecommandargumentvalues')->drop()->save();
        $this->table('servicetemplateeventcommandargumentvalues')->drop()->save();
        $this->table('servicetemplategroups')->drop()->save();
        $this->table('servicetemplates')->drop()->save();
        $this->table('servicetemplates_to_servicegroups')->drop()->save();
        $this->table('servicetemplates_to_servicetemplategroups')->drop()->save();
        $this->table('systemdowntimes')->drop()->save();
        $this->table('systemfailures')->drop()->save();
        $this->table('systemsettings')->drop()->save();
        $this->table('tenants')->drop()->save();
        $this->table('timeperiod_timeranges')->drop()->save();
        $this->table('timeperiods')->drop()->save();
        $this->table('usergroups')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('users_to_containers')->drop()->save();
        $this->table('widgets')->drop()->save();
    }
}
