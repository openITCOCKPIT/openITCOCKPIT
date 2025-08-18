<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Hosttemplatecommandargumentvalue Entity
 *
 * @property int $id
 * @property int $commandargument_id
 * @property int $hosttemplate_id
 * @property string $value
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Commandargument $commandargument
 * @property \App\Model\Entity\Hosttemplate $hosttemplate
 */
class Hosttemplatecommandargumentvalue extends Entity {

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
        'commandargument_id' => true,
        'hosttemplate_id'    => true,
        'value'              => true,
        'created'            => true,
        'modified'           => true,
        'commandargument'    => true,
        'hosttemplate'       => true
    ];
}
