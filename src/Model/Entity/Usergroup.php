<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Usergroup Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\User[] $users
 */
class Usergroup extends Entity {

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
        'name'        => true,
        'description' => true,
        'created'     => true,
        'modified'    => true,
        'users'       => true,
        'ldapgroups'  => true
    ];

    public function parentNode() {
        return null;
    }
}
