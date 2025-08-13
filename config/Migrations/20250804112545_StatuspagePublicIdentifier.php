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
 * Class StatuspagePublicIdentifier
 *
 * Created:
 * oitc migrations create StatuspagePublicIdentifier
 *
 * Usage:
 * openitcockpit-update
 */
class StatuspagePublicIdentifier extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        if ($this->hasTable('statuspages')) {
            $this->table('statuspages')
                ->addColumn('uuid', 'string', [
                    'after'   => 'id',
                    'default' => null,
                    'limit'   => 37,
                    'null'    => true,
                ])
                ->addColumn('public_identifier', 'string', [
                    'after'   => 'public_title',
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                // Unfortunatly we cannot use 'unique' here, because the uuid is a new column,
                // and we cannot guarantee that the uuid is unique in the existing data.
                // However, the InstallSeed will ensure that all existing status pages will
                // have a UUID - so we can create a unique index in a future migration.
                // For example with openITCOCKP'IT 5.1.0 we can add the unique index
                //['unique' => true]
                )

                // https://dev.mysql.com/doc/refman/8.4/en/create-index.html#create-index-unique
                // > A UNIQUE index permits multiple NULL values for columns that can contain NULL.
                // This is exactly what we want for the optional public_identifier column.
                ->addIndex(
                    [
                        'public_identifier',
                    ],
                    ['unique' => true]
                )
                ->update();
        }
    }
}
