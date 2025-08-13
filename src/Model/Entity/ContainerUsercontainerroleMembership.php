<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContainerUserMembership Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $container_id
 * @property int $permission_level
 */
class ContainerUsercontainerroleMembership extends Entity
{

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
        'usercontainerrole_id' => true,
        'container_id' => true,
        'permission_level' => true
    ];
}
