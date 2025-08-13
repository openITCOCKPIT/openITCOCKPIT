<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Systemfailure Entity
 *
 * @property int $id
 * @property \Cake\I18n\DateTime $start_time
 * @property \Cake\I18n\DateTime $end_time
 * @property string $comment
 * @property int|null $user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\User $user
 */
class Systemfailure extends Entity {
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
        'start_time' => true,
        'end_time'   => true,
        'comment'    => true,
        'user_id'    => true,
        'created'    => true,
        'modified'   => true,
        'user'       => true
    ];
}
