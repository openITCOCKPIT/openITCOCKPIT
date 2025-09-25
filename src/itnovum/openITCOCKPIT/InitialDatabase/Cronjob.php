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

namespace itnovum\openITCOCKPIT\InitialDatabase;

use App\Model\Table\CronjobsTable;

/**
 * Class Cronjob
 * @package itnovum\openITCOCKPIT\InitialDatabase
 * @property CronjobsTable $Table
 */
class Cronjob extends Importer {

    /**
     * @return bool
     */
    public function import() {
        if ($this->isTableEmpty()) {
            $data = $this->getData();
            foreach ($data as $record) {
                $entity = $this->Table->newEmptyEntity();
                $entity = $this->patchEntityAndKeepAllIds($entity, $record);
                $this->Table->save($entity);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getData() {
        $data = [
            (int)0 => [
                'id'           => '1',
                'task'         => 'CleanupTemp',
                'plugin'       => 'Core',
                'interval'     => '10',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '1',
                    'cronjob_id' => '1',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:49:02',
                    'end_time'   => '2020-01-29 09:49:02'
                ]
            ],
            (int)1 => [
                'id'           => '2',
                'task'         => 'DatabaseCleanup',
                'plugin'       => 'Core',
                'interval'     => '1440',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '2',
                    'cronjob_id' => '2',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:29:01',
                    'end_time'   => '2020-01-29 09:29:02'
                ]
            ],
            (int)2 => [
                'id'           => '3',
                'task'         => 'RecurringDowntimes',
                'plugin'       => 'Core',
                'interval'     => '10',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '3',
                    'cronjob_id' => '3',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:50:01',
                    'end_time'   => '2020-01-29 09:50:01'
                ]
            ],
            (int)3 => [
                'id'           => '4',
                'task'         => 'InstantReport',
                'plugin'       => 'Core',
                'interval'     => '1440',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '4',
                    'cronjob_id' => '4',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:29:02',
                    'end_time'   => '2020-01-29 09:29:02'
                ]
            ],
            (int)5 => [
                'id'           => '6',
                'task'         => 'CpuLoad',
                'plugin'       => 'Core',
                'interval'     => '15',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '6',
                    'cronjob_id' => '6',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:45:02',
                    'end_time'   => '2020-01-29 09:45:02'
                ]
            ],
            (int)6 => [
                'id'           => '7',
                'task'         => 'VersionCheck',
                'plugin'       => 'Core',
                'interval'     => '1440',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '7',
                    'cronjob_id' => '7',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:29:03',
                    'end_time'   => '2020-01-29 09:29:03'
                ]
            ],
            (int)7 => [
                'id'           => '8',
                'task'         => 'SystemHealth',
                'plugin'       => 'Core',
                'interval'     => '1',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '8',
                    'cronjob_id' => '8',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:58:01',
                    'end_time'   => '2020-01-29 09:58:02'
                ]
            ],
            (int)8 => [
                'id'           => '9',
                'task'         => 'SystemMetrics',
                'plugin'       => 'Core',
                'interval'     => '240',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '9',
                    'cronjob_id' => '9',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:29:04',
                    'end_time'   => '2020-01-29 09:29:04'
                ]
            ],
            (int)9 => [
                'id'           => '10',
                'task'         => 'ConfigGenerator',
                'plugin'       => 'Core',
                'interval'     => '1',
                'enabled'      => '1',
                'cronschedule' => [
                    'id'         => '10',
                    'cronjob_id' => '10',
                    'is_running' => '0',
                    'start_time' => '2020-01-29 09:58:02',
                    'end_time'   => '2020-01-29 09:58:02'
                ]
            ]
        ];

        return $data;
    }
}
