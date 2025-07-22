<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use Migrations\AbstractMigration;

/**
 * Class OrganizationalCharts
 * This creates tables for OrganizationalCharts. The table structure can be modified with new migrations later on.
 *
 * Created:
 * oitc migrations create OrganizationalCharts
 *
 * Usage:
 * oitc migrations migrate
 */
class OrganizationalCharts extends AbstractMigration {
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
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('organizational_charts')) {
            $this->table('organizational_charts')
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
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('organizational_chart_nodes')) {
            $this->table('organizational_chart_nodes')
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
                ->addColumn('organizational_chart_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('containertype_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('recursive', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('x_position', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('y_position', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('modified', 'datetime', [
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
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'organizational_chart_id',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('organizational_chart_connections')) {
            $this->table('organizational_chart_connections')
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
                ->addColumn('organizational_chart_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('organizational_chart_input_node_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('organizational_chart_output_node_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('modified', 'datetime', [
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
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'organizational_chart_id',
                    ]
                )
                ->addIndex(
                    [
                        'organizational_chart_input_node_id',
                    ]
                )
                ->addIndex(
                    [
                        'organizational_chart_output_node_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('users_to_organizational_chart_nodes')) {
            $this->table('users_to_organizational_chart_nodes')
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
                ->addColumn('organizational_chart_node_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('user_role', 'integer', [
                    'default' => 1 << 0,
                    'limit'   => 4,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'organizational_chart_node_id',
                    ]
                )
                ->addIndex(
                    [
                        'user_id',
                    ]
                )
                ->addIndex(
                    [
                        'user_role',
                    ]
                )
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
        if ($this->hasTable('organizational_charts')) {
            $this->table('organizational_charts')->drop()->save();
        }
        if ($this->hasTable('organizational_chart_nodes')) {
            $this->table('organizational_chart_nodes')->drop()->save();
        }
        if ($this->hasTable('organizational_chart_connections')) {
            $this->table('organizational_chart_connections')->drop()->save();
        }
        if ($this->hasTable('users_to_organizational_chart_nodes')) {
            $this->table('users_to_organizational_chart_nodes')->drop()->save();
        }
    }
}
