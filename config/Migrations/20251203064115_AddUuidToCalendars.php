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

use Migrations\BaseMigration;

/**
 * Class AddUuidToCalendars
 *
 * Created:
 * oitc migrations create AddUuidToCalendars
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class AddUuidToCalendars extends BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        if ($this->hasTable('calendars')) {
            $this->table('calendars')
                ->addColumn('uuid', 'string', [
                    'after'   => 'id',
                    'default' => null,
                    'limit'   => 37,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ]
                )->update();
        }
    }
}
