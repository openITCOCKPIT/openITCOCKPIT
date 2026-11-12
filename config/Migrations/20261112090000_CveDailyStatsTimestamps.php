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
 * Class CveDailyStatsTimestamps
 *
 * cve_daily_stats has one row per host and day (stat_date), every evaluation of the day updates it.
 * created and modified tell when the first and the last evaluation of the day ran.
 * Existing rows get their day at midnight, the real time is unknown.
 *
 * up() and down() are used instead of change(), so a rollback removes the columns again.
 *
 * Created:
 * oitc migrations create CveDailyStatsTimestamps
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class CveDailyStatsTimestamps extends BaseMigration {

    /**
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('cve_daily_stats')) {
            return;
        }

        $table = $this->table('cve_daily_stats');
        if ($table->hasColumn('created')) {
            return;
        }

        // Nullable first, existing rows need a value before the columns can be NOT NULL
        $table
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null'    => true,
                'after'   => 'resolved_count',
                'comment' => 'First evaluation of the day'
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null'    => true,
                'after'   => 'created',
                'comment' => 'Last evaluation or classification of the day'
            ])
            ->update();

        $this->execute('UPDATE cve_daily_stats SET created = stat_date, modified = stat_date');

        $table
            ->changeColumn('created', 'datetime', [
                'default' => null,
                'null'    => false,
                'comment' => 'First evaluation of the day'
            ])
            ->changeColumn('modified', 'datetime', [
                'default' => null,
                'null'    => false,
                'comment' => 'Last evaluation or classification of the day'
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void {
        if (!$this->hasTable('cve_daily_stats')) {
            return;
        }

        $table = $this->table('cve_daily_stats');
        if ($table->hasColumn('created')) {
            $table
                ->removeColumn('created')
                ->removeColumn('modified')
                ->update();
        }
    }
}
