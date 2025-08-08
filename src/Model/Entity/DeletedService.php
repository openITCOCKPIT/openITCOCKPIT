<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * DeletedService Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string $host_uuid
 * @property int $servicetemplate_id
 * @property int $host_id
 * @property string $name
 * @property string|null $description
 * @property int|null $deleted_perfdata
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class DeletedService extends Entity {

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
        'uuid'               => true,
        'host_uuid'          => true,
        'servicetemplate_id' => true,
        'host_id'            => true,
        'name'               => true,
        'description'        => true,
        'deleted_perfdata'   => true,
        'created'            => true,
        'modified'           => true
    ];
}
