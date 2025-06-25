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
 * Class CreateMapgenerator
 * Created:
 * oitc migrations create -p MapModule CreateMapgenerator
 *
 * Usage:
 * openitcockpit-update
 */
class CreateMapgenerator extends AbstractMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        if (!$this->hasTable('mapgenerators')) {
            $this->table('mapgenerators')
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('interval', 'integer', [
                    'default' => '90',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('type', 'integer', [
                    'default' => '1',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('items_per_line', 'integer', [
                    'default' => '10',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('has_generated_maps', 'integer', [
                    'default' => '0',
                    'limit'   => 5,
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

        if (!$this->hasTable('mapgenerator_levels')) {
            $this->table('mapgenerator_levels')
                ->addPrimaryKey(['id'])
                ->addColumn('mapgenerator_id', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('divider', 'string', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('mapgenerators_to_start_containers')) {
            $this->table('mapgenerators_to_start_containers')
                ->addPrimaryKey(['id'])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('mapgenerator_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->addIndex(
                    [
                        'mapgenerator_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('mapgenerators_to_containers')) {
            $this->table('mapgenerators_to_containers')
                ->addPrimaryKey(['id'])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('mapgenerator_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->addIndex(
                    [
                        'mapgenerator_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('mapgenerators_to_maps')) {
            $this->table('mapgenerators_to_maps')
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('mapgenerator_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'map_id',
                    ]
                )
                ->addIndex(
                    [
                        'mapgenerator_id',
                    ]
                )
                ->create();
        }
    }
}
