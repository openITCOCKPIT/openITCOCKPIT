<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Usercontainerrole Entity
 *
 * @property int $id
 * @property string $name
 *
 * @property \App\Model\Entity\UsercontainerrolesToContainer[] $usercontainerroles_to_containers
 * @property \App\Model\Entity\UsersToUsercontainerrole[] $users_to_usercontainerroles
 */
class Usercontainerrole extends Entity {
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
        'name'                             => true,
        'usercontainerroles_to_containers' => true,
        'users_to_usercontainerroles'      => true,
        'containers'                       => true,
        'ldapgroups'                       => true
    ];
}
