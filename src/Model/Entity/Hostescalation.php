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
 * @property int $first_notification
 * @property int $last_notification
 * @property int $notification_interval
 * @property int $escalate_on_recovery
 * @property int $escalate_on_down
 * @property int $escalate_on_unreachable
 * @property Host $hosts
 * @property Hostgroup $hostgroups
 * @property Contact $contacts
 * @property Contactgroup $contactgroups
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\TimeperiodTimerange[] $timeperiod_timeranges
 */
class Hostescalation extends Entity {

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
        'uuid'                    => true,
        'container_id'            => true,
        'timeperiod_id'           => true,
        'first_notification'      => true,
        'last_notification'       => true,
        'notification_interval'   => true,
        'escalate_on_recovery'    => true,
        'escalate_on_down'        => true,
        'escalate_on_unreachable' => true,
        'contacts'                => true,
        'contactgroups'           => true,
        'hosts'                   => true,
        'hostgroups'              => true,
        'timeperiods'             => true,
        'created'                 => true,
        'modified'                => true
    ];

    /**
     * @return string
     */
    public function getHostEscalationStringForCfg() {
        $cfgValues = [];
        $fields = [
            'escalate_on_recovery'    => 'r',
            'escalate_on_down'        => 'd',
            'escalate_on_unreachable' => 'u'
        ];
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        return implode(',', $cfgValues);
    }

}
