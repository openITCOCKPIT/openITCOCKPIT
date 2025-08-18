<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Servicegroup Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $container_id
 * @property string $description
 * @property string|null $servicegroup_url
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\Service[] $services
 * @property \App\Model\Entity\Servicetemplate[] $servicetemplates
 */
class Servicegroup extends Entity {
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
        'uuid'             => true,
        'container_id'     => true,
        'description'      => true,
        'servicegroup_url' => true,
        'container'        => true,
        'services'         => true,
        'servicetemplates' => true
    ];
}
