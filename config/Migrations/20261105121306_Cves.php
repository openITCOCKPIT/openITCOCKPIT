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
 * Class Cves
 *
 * Created:
 * oitc migrations create Cves
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class Cves extends BaseMigration {

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
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
     *
     * @return void
     */
    public function change(): void {

        if ($this->hasTable('packages_linux_hosts')) {
            $this->table('packages_linux_hosts')
                ->addColumn('source_package', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'is_patch',
                    'comment' => 'The name of the source package that was used to build this binary package'
                ])
                ->addColumn('source_version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'source_package',
                    'comment' => 'The version of the source package that was used to build this binary package'
                ])
                ->addColumn('architecture', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'source_version',
                    'comment' => 'The architecture of the package'
                ])
                ->addColumn('modularity_label', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'architecture',
                    'comment' => 'The RPM modularity label in NSVC format, used to identify the module build context of the package'
                ])
                ->addColumn('source_rpm', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'modularity_label',
                    'comment' => 'The name of the source RPM package that was used to build this binary package'
                ])
                ->update();
        }

        if ($this->hasTable('packages_host_details')) {
            $this->table('packages_host_details')
                ->addColumn('kernel_release', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'os_family',
                    'comment' => 'Currently running kernel release'
                ])
                ->addColumn('packages_hash', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => true,
                    'after'   => 'kernel_release',
                    'comment' => 'sha256 hash of all packages to detect changes in the package list'
                ])
                ->update();
        }

        if ($this->hasTable('macos_apps_hosts')) {
            $this->table('macos_apps_hosts')
                ->addColumn('bundle_id', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'after'   => 'version',
                    'comment' => 'CFBundleIdentifier of the app, e.g. com.apple.Safari. Unlike the name it does not depend on the language'
                ])
                ->update();
        }

    }
}
