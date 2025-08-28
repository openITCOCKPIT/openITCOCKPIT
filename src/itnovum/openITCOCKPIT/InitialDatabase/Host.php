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

use App\Model\Table\HostsTable;

/**
 * Class Host
 * @package itnovum\openITCOCKPIT\InitialDatabase
 * @property HostsTable $Table
 */
class Host extends Importer {

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
                'id'                            => '1',
                'uuid'                          => 'c36b8048-93ce-4385-ac19-ab5c90574b77',
                'container_id'                  => '1',
                'name'                          => 'default host',
                'description'                   => null,
                'hosttemplate_id'               => '1',
                'address'                       => '127.0.0.1',
                'command_id'                    => null,
                'eventhandler_command_id'       => null,
                'timeperiod_id'                 => null,
                'check_interval'                => null,
                'retry_interval'                => null,
                'max_check_attempts'            => null,
                'first_notification_delay'      => null,
                'notification_interval'         => null,
                'notify_on_down'                => null,
                'notify_on_unreachable'         => null,
                'notify_on_recovery'            => null,
                'notify_on_flapping'            => null,
                'notify_on_downtime'            => null,
                'flap_detection_enabled'        => null,
                'flap_detection_on_up'          => null,
                'flap_detection_on_down'        => null,
                'flap_detection_on_unreachable' => null,
                'low_flap_threshold'            => null,
                'high_flap_threshold'           => null,
                'process_performance_data'      => null,
                'freshness_checks_enabled'      => null,
                'freshness_threshold'           => null,
                'passive_checks_enabled'        => null,
                'event_handler_enabled'         => null,
                'active_checks_enabled'         => null,
                'retain_status_information'     => null,
                'retain_nonstatus_information'  => null,
                'notifications_enabled'         => null,
                'notes'                         => null,
                'priority'                      => null,
                'check_period_id'               => null,
                'notify_period_id'              => null,
                'tags'                          => null,
                'own_contacts'                  => '0',
                'own_contactgroups'             => '0',
                'own_customvariables'           => '0',
                'host_url'                      => '',
                'satellite_id'                  => '0',
                'host_type'                     => '1',
                'disabled'                      => '0',
                'usage_flag'                    => '0',
                'created'                       => '2015-01-15 19:26:32',
                'modified'                      => '2015-01-15 19:26:32',
                'hostcommandargumentvalues'     => [],
                'customvariables'               => [],
                'hostgroups'                    => [],
                'contactgroups'                 => [],
                'contacts'                      => [],
                'hosts_to_containers_sharing'   => [
                    '_ids' => [
                        (int)0 => '1'
                    ]
                ]
            ]
        ];

        return $data;
    }
}
