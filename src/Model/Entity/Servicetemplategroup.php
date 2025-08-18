<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Servicetemplategroup Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $container_id
 * @property string|null $description
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\Servicetemplate[] $servicetemplates
 */
class Servicetemplategroup extends Entity {
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
        'created'          => true,
        'modified'         => true,
        'container'        => true,
        'servicetemplates' => true
    ];
}
