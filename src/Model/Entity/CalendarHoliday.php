<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CalendarHoliday Entity
 *
 * @property int $id
 * @property int $calendar_id
 * @property string $name
 * @property int $default_holiday
 * @property \Cake\I18n\Date $date
 *
 * @property \App\Model\Entity\Calendar $calendar
 */
class CalendarHoliday extends Entity {
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
        'calendar_id'     => true,
        'name'            => true,
        'default_holiday' => true,
        'date'            => true,
        'calendar'        => true
    ];
}
