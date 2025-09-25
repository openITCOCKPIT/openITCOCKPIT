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

use App\Model\Table\ContactsTable;

/**
 * Class Contact
 * @package itnovum\openITCOCKPIT\InitialDatabase
 * @property ContactsTable $Table
 */
class Contact extends Importer {

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
                'id'                                 => '1',
                'uuid'                               => '152aecaf-e981-4b0b-8e05-86972868547d',
                'name'                               => 'info',
                'description'                        => 'info contact',
                'email'                              => 'openitcockpit@localhost.local',
                'phone'                              => '',
                'user_id'                            => null,
                'host_timeperiod_id'                 => '1',
                'service_timeperiod_id'              => '1',
                'host_notifications_enabled'         => '1',
                'service_notifications_enabled'      => '1',
                'notify_service_recovery'            => '1',
                'notify_service_warning'             => '1',
                'notify_service_unknown'             => '1',
                'notify_service_critical'            => '1',
                'notify_service_flapping'            => '0',
                'notify_service_downtime'            => '0',
                'notify_host_recovery'               => '1',
                'notify_host_down'                   => '1',
                'notify_host_unreachable'            => '1',
                'notify_host_flapping'               => '0',
                'notify_host_downtime'               => '0',
                'host_push_notifications_enabled'    => '0',
                'service_push_notifications_enabled' => '0',
                'customvariables'                    => [],
                'containers'                         => [
                    '_ids' => [1]
                ],
                'host_commands'                      => [
                    '_ids' => [1]
                ],
                'service_commands'                   => [
                    '_ids' => [2]
                ],
            ]
        ];

        return $data;
    }
}
