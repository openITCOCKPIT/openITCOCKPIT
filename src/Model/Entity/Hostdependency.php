<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Timeperiod Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $container_id
 * @property int $timeperiod_id
 * @property int $inherits_parent
 * @property int $execution_fail_on_up
 * @property int $execution_fail_on_down
 * @property int $execution_fail_on_unreachable
 * @property int $execution_fail_on_pending
 * @property int $execution_none
 * @property int $notification_fail_on_up
 * @property int $notification_fail_on_down
 * @property int $notification_fail_on_unreachable
 * @property int $notification_fail_on_pending
 * @property int $notification_none
 * @property Host $hosts
 * @property Timeperiod $timeperiods
 * @property Hostgroup $hostgroups
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\TimeperiodTimerange[] $timeperiod_timeranges
 */
class Hostdependency extends Entity {

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
        'uuid'                             => true,
        'container_id'                     => true,
        'timeperiod_id'                    => true,
        'inherits_parent'                  => true,
        'execution_fail_on_up'             => true,
        'execution_fail_on_down'           => true,
        'execution_fail_on_unreachable'    => true,
        'execution_fail_on_pending'        => true,
        'execution_none'                   => true,
        'notification_fail_on_up'          => true,
        'notification_fail_on_down'        => true,
        'notification_fail_on_unreachable' => true,
        'notification_fail_on_pending'     => true,
        'notification_none'                => true,
        'hosts'                            => true,
        'hostgroups'                       => true,
        'timeperiods'                      => true,
        'created'                          => true,
        'modified'                         => true
    ];

    /**
     * @return string
     */
    public function getExecutionFailureCriteriaForCfg() {
        $cfgValues = [];
        $fields = [
            'execution_fail_on_up'          => 'o',
            'execution_fail_on_down'        => 'd',
            'execution_fail_on_unreachable' => 'u',
            'execution_fail_on_pending'     => 'p',
            'execution_none'                => 'n'
        ];
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        return implode(',', $cfgValues);
    }

    /**
     * @return string
     */
    public function getNotificationFailureCriteriaForCfg() {
        $cfgValues = [];
        $fields = [
            'notification_fail_on_up'          => 'o',
            'notification_fail_on_down'        => 'd',
            'notification_fail_on_unreachable' => 'u',
            'notification_fail_on_pending'     => 'p',
            'notification_none'                => 'n'
        ];

        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        return implode(',', $cfgValues);
    }
}
