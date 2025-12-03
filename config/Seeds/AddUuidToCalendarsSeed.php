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

use App\Model\Table\CalendarsTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\UUID;
use Migrations\BaseSeed;

/**
 * Class AddUuidToCalendarsSeed
 *
 * Created:
 * oitc bake seed --table calendars  AddUuidToCalendarsSeed
 *
 * Apply:
 * oitc migrations seed
 */
class AddUuidToCalendarsSeed extends BaseSeed {
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/4/en/seeding.html
     *
     * @return void
     */
    public function run(): void {
        // ITC-2696 Add UUID to existing Calendars
        /** @var CalendarsTable $CalendarsTable */
        $CalendarsTable = TableRegistry::getTableLocator()->get('Calendars');
        $calendars = $CalendarsTable->find()
            ->whereNull(['Calendars.uuid'])
            ->all();
        foreach ($calendars as $calendar) {
            $calendar->set('uuid', UUID::v4());
            $CalendarsTable->save($calendar);
        }
    }
}
