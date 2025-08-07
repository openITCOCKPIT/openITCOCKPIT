<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TimeperiodTimerange Entity
 *
 * @property int $id
 * @property int $timeperiod_id
 * @property int $day
 * @property string $start
 * @property string $end
 *
 * @property \App\Model\Entity\Timeperiod $timeperiod
 */
class TimeperiodTimerange extends Entity {

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
        'timeperiod_id' => true,
        'day'           => true,
        'start'         => true,
        'end'           => true,
        'timeperiod'    => true,
    ];
}
