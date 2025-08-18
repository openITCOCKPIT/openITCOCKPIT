<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Contact Entity
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $notify_on_warning
 * @property int|null $notify_on_critical
 * @property int|null $notify_on_recovery
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class SystemHealthUser extends Entity {

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
        'user_id'            => true,
        'notify_on_recovery' => true,
        'notify_on_warning'  => true,
        'notify_on_critical' => true,
        'created'            => true,
        'modified'           => true,
    ];
}
