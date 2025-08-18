<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Location Entity
 *
 * @property int $id
 * @property int $container_id
 * @property string $uuid
 * @property string|null $description
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $timezone
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 */
class Location extends Entity {

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
        'container_id' => true,
        'uuid'         => true,
        'description'  => true,
        'latitude'     => true,
        'longitude'    => true,
        'timezone'     => true,
        'container'    => true,
        'created'      => true,
        'modified'     => true
    ];
}
