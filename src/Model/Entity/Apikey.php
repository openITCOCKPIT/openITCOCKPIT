<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Apikey Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $apikey
 * @property string|null $description
 * @property \Cake\I18n\DateTime|null $last_use
 *
 * @property \App\Model\Entity\User $user
 */
class Apikey extends Entity {

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
        'user_id'     => true,
        'apikey'      => true,
        'description' => true,
        'last_use'    => true,
        'user'        => true
    ];
}
