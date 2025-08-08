<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Register Entity
 *
 * @property int $id
 * @property string $license
 * @property bool $accepted
 * @property bool $apt
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Register extends Entity {

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
        'license'  => true,
        'accepted' => true,
        'apt'      => true,
        'created'  => true,
        'modified' => true
    ];
}
