<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Cronschedule Entity
 *
 * @property int $id
 * @property int|null $cronjob_id
 * @property int|null $is_running
 * @property \Cake\I18n\DateTime $start_time
 * @property \Cake\I18n\DateTime $end_time
 *
 * @property \App\Model\Entity\Cronjob $cronjob
 */
class Cronschedule extends Entity {

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
        'cronjob_id' => true,
        'is_running' => true,
        'start_time' => true,
        'end_time'   => true,
        'cronjob'    => true
    ];
}
