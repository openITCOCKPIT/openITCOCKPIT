<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Documentation Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $content
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Documentation extends Entity {

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
        'uuid'     => true,
        'content'  => true,
        'created'  => true,
        'modified' => true
    ];
}
