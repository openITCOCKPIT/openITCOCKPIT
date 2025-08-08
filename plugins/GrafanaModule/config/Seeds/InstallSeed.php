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

/**
 * Class InstallSeed
 *
 * Created:
 * oitc4 bake seed -p GrafanaModule --table commands --data Install
 *
 * Apply:
 * oitc4 migrations seed -p GrafanaModule
 */
class InstallSeed extends \Migrations\BaseSeed {
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/3/en/index.html#seed-seeding-your-database
     *
     * @return void
     */
    public function run(): void {
        //Cronjobs
        $table = $this->table('cronjobs');

        $data = [
            [
                'task'     => 'GrafanaDashboard',
                'plugin'   => 'GrafanaModule',
                'interval' => '720',
                'enabled'  => '1',
            ]
        ];

        //Check if records exists
        foreach ($data as $index => $record) {
            $QueryBuilder = $this->getAdapter()->getSelectBuilder();

            $stm = $QueryBuilder->select('*')
                ->from($table->getName())
                ->where([
                    'plugin' => $record['plugin'],
                    'task'   => $record['task']
                ])
                ->execute();
            $result = $stm->fetchAll('assoc');

            if (empty($result)) {
                $table->insert($record)->save();
            }
        }
    }
}
