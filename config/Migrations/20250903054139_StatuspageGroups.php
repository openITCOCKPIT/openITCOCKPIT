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

declare(strict_types=1);

/**
 * Class StatuspageGroups
 * This creates tables for StatuspageGroups. The table structure can be modified with new migrations later on.
 *
 * Created:
 * oitc migrations create StatuspageGroups
 *
 * Usage:
 * oitc migrations migrate
 */
class StatuspageGroups extends \Migrations\BaseMigration {
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
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('statuspagegroups')) {
            $this->table('statuspagegroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('container_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
                ])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->create();
        }

        if (!$this->hasTable('statuspagegroup_collections')) {
            $this->table('statuspagegroup_collections')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('statuspagegroup_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
                ])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 255,
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
                        'statuspagegroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('statuspagegroup_categories')) {
            $this->table('statuspagegroup_categories')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('statuspagegroup_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
                ])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
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
                        'statuspagegroup_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('statuspages_to_statuspagegroups')) {
            $this->table('statuspages_to_statuspagegroups')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('statuspagegroup_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
                ])
                ->addColumn('collection_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
                ])
                ->addColumn('category_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
                ])
                ->addColumn('statuspage_id', 'integer', [
                    'limit' => 11,
                    'null'  => false,
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
                        'statuspagegroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'collection_id',
                    ]
                )
                ->addIndex(
                    [
                        'category_id',
                    ]
                )
                ->addIndex(
                    [
                        'statuspage_id',
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
        if ($this->hasTable('statuspagegroups')) {
            $this->table('statuspagegroups')->drop()->save();
        }
        if ($this->hasTable('statuspagegroup_collections')) {
            $this->table('statuspagegroup_collections')->drop()->save();
        }
        if ($this->hasTable('statuspagegroup_categories')) {
            $this->table('statuspagegroup_categories')->drop()->save();
        }
        if ($this->hasTable('statuspages_to_statuspagegroups')) {
            $this->table('statuspages_to_statuspagegroups')->drop()->save();
        }
    }
}
