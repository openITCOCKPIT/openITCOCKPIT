<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace Statusengine3Module\Model\Entity;

use Cake\ORM\Entity;

/**
 * Servicestatus Entity
 *
 * @property string $hostname
 * @property string $service_description
 * @property int $status_update_time
 * @property string|null $output
 * @property string|null $long_output
 * @property string|null $perfdata
 * @property int|null $current_state
 * @property int|null $current_check_attempt
 * @property int|null $max_check_attempts
 * @property int $last_check
 * @property int $next_check
 * @property bool|null $is_passive_check
 * @property int $last_state_change
 * @property int $last_hard_state_change
 * @property int|null $last_hard_state
 * @property bool|null $is_hardstate
 * @property int $last_notification
 * @property int $next_notification
 * @property bool|null $notifications_enabled
 * @property bool|null $problem_has_been_acknowledged
 * @property bool|null $acknowledgement_type
 * @property bool|null $passive_checks_enabled
 * @property bool|null $active_checks_enabled
 * @property bool|null $event_handler_enabled
 * @property bool|null $flap_detection_enabled
 * @property bool|null $is_flapping
 * @property float|null $latency
 * @property float|null $execution_time
 * @property int|null $scheduled_downtime_depth
 * @property bool|null $process_performance_data
 * @property bool|null $obsess_over_service
 * @property int|null $normal_check_interval
 * @property int|null $retry_check_interval
 * @property string|null $check_timeperiod
 * @property string|null $node_name
 * @property int $last_time_ok
 * @property int $last_time_warning
 * @property int $last_time_critical
 * @property int $last_time_unknown
 * @property int|null $current_notification_number
 * @property float|null $percent_state_change
 * @property string|null $event_handler
 * @property string|null $check_command
 */
class Servicestatus extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'status_update_time'            => true,
        'output'                        => true,
        'long_output'                   => true,
        'perfdata'                      => true,
        'current_state'                 => true,
        'current_check_attempt'         => true,
        'max_check_attempts'            => true,
        'last_check'                    => true,
        'next_check'                    => true,
        'is_passive_check'              => true,
        'last_state_change'             => true,
        'last_hard_state_change'        => true,
        'last_hard_state'               => true,
        'is_hardstate'                  => true,
        'last_notification'             => true,
        'next_notification'             => true,
        'notifications_enabled'         => true,
        'problem_has_been_acknowledged' => true,
        'acknowledgement_type'          => true,
        'passive_checks_enabled'        => true,
        'active_checks_enabled'         => true,
        'event_handler_enabled'         => true,
        'flap_detection_enabled'        => true,
        'is_flapping'                   => true,
        'latency'                       => true,
        'execution_time'                => true,
        'scheduled_downtime_depth'      => true,
        'process_performance_data'      => true,
        'obsess_over_service'           => true,
        'normal_check_interval'         => true,
        'retry_check_interval'          => true,
        'check_timeperiod'              => true,
        'node_name'                     => true,
        'last_time_ok'                  => true,
        'last_time_warning'             => true,
        'last_time_critical'            => true,
        'last_time_unknown'             => true,
        'current_notification_number'   => true,
        'percent_state_change'          => true,
        'event_handler'                 => true,
        'check_command'                 => true,
    ];
}
