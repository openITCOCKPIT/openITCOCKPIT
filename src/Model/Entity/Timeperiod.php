<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Timeperiod Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $container_id
 * @property string $name
 * @property string|null $description
 * @property int|null $calendar_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\TimeperiodTimerange[] $timeperiod_timeranges
 */
class Timeperiod extends Entity {

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
        'uuid'                  => true,
        'container_id'          => true,
        'name'                  => true,
        'description'           => true,
        'calendar_id'           => true,
        'created'               => true,
        'modified'              => true,
        'timeperiod_timeranges' => true

    ];
}
