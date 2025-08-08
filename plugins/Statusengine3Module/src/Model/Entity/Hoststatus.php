<?php
declare(strict_types=1);

namespace Statusengine3Module\Model\Entity;

use Cake\ORM\Entity;

/**
 * Hoststatus Entity
 *
 * @property string $hostname
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
 * @property int|null $acknowledgement_type
 * @property bool|null $passive_checks_enabled
 * @property bool|null $active_checks_enabled
 * @property bool|null $event_handler_enabled
 * @property bool|null $flap_detection_enabled
 * @property bool|null $is_flapping
 * @property float|null $latency
 * @property float|null $execution_time
 * @property int|null $scheduled_downtime_depth
 * @property bool|null $process_performance_data
 * @property bool|null $obsess_over_host
 * @property int|null $normal_check_interval
 * @property int|null $retry_check_interval
 * @property string|null $check_timeperiod
 * @property string|null $node_name
 * @property int $last_time_up
 * @property int $last_time_down
 * @property int $last_time_unreachable
 * @property int|null $current_notification_number
 * @property float|null $percent_state_change
 * @property string|null $event_handler
 * @property string|null $check_command
 */
class Hoststatus extends Entity {
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
        'obsess_over_host'              => true,
        'normal_check_interval'         => true,
        'retry_check_interval'          => true,
        'check_timeperiod'              => true,
        'node_name'                     => true,
        'last_time_up'                  => true,
        'last_time_down'                => true,
        'last_time_unreachable'         => true,
        'current_notification_number'   => true,
        'percent_state_change'          => true,
        'event_handler'                 => true,
        'check_command'                 => true,
    ];
}
