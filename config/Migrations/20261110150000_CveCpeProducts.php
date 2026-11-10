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

/**
 * Class CveCpeProducts
 *
 * Rules that map installed Windows and macOS applications to their CPE, so Vuls can find their CVEs.
 * Linux packages and Apple apps do not need them.
 *
 * - cve_cpe_products:      One CPE product, e.g. vendor "google" and product "chrome"
 * - cve_cpe_mappings:      Which installed applications belong to the product, with up to two conditions
 * - cve_cpe_product_names: How the product is written in CVE descriptions, e.g. "Google Chrome".
 *                          Used by the PlatformFilter to drop CVEs of other operating systems
 *
 * The default products get created by the InstallSeed (itnovum\openITCOCKPIT\InitialDatabase\CveCpeProduct).
 * Customers can add their own products.
 *
 * up() and down() are used instead of change(), so a rollback removes the tables again.
 *
 * Created:
 * oitc migrations create CveCpeProducts
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class CveCpeProducts extends BaseMigration {

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
     * @return void
     */
    public function up(): void {

        if (!$this->hasTable('cve_cpe_products')) {
            $this->table('cve_cpe_products')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('vendor', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => false,
                    'comment' => 'Vendor part of the CPE, e.g. google. Stored unescaped'
                ])
                ->addColumn('product', 'string', [
                    'default' => null,
                    'limit'   => 100,
                    'null'    => false,
                    'comment' => 'Product part of the CPE, e.g. chrome or notepad++. Stored unescaped'
                ])
                ->addColumn('enabled', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Disabled products are not checked. Disable default products instead of deleting them, the seed would create them again'
                ])
                ->addColumn('is_default', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Created by the seed, not by a user'
                ])
                ->addColumn('verified', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'The CPE found at least one CVE in the Vuls database. A product that never finds anything may be misspelled'
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'vendor',
                        'product',
                    ],
                    ['unique' => true]
                )
                ->create();
        }

        if (!$this->hasTable('cve_cpe_mappings')) {
            $this->table('cve_cpe_mappings')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('cve_cpe_product_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('platform', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'windows or macos'
                ])
                ->addColumn('first_field', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'name, publisher (Windows only) or bundle_id (macOS only)'
                ])
                ->addColumn('first_match', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'equals, starts_with, contains or ends_with. Case-insensitive'
                ])
                ->addColumn('first_value', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('second_field', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => true,
                    'comment' => 'Optional second condition, both conditions have to match'
                ])
                ->addColumn('second_match', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => true,
                ])
                ->addColumn('second_value', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'cve_cpe_product_id',
                    ]
                )
                ->addIndex(
                    [
                        'platform',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_cpe_product_names')) {
            $this->table('cve_cpe_product_names')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('cve_cpe_product_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Product as written in CVE descriptions, e.g. "Microsoft Edge (Chromium-based)". Case-insensitive'
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'cve_cpe_product_id',
                        'name',
                    ],
                    ['unique' => true]
                )
                ->create();
        }
    }

    /**
     * @return void
     */
    public function down(): void {
        foreach (['cve_cpe_product_names', 'cve_cpe_mappings', 'cve_cpe_products'] as $tableName) {
            if ($this->hasTable($tableName)) {
                $this->table($tableName)->drop()->save();
            }
        }
    }
}
