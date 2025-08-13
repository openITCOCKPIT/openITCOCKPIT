<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Ldapgroup Entity
 *
 * @property int $id
 * @property string $cn
 * @property string $dn
 * @property string $description
 *
 * @property \App\Model\Entity\LdapgroupsToUsercontainerrole[] $ldapgroups_to_usercontainerroles
 */
class Ldapgroup extends Entity
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
        'cn' => true,
        'dn' => true,
        'description' => true,
        'ldapgroups_to_usercontainerroles' => true,
    ];
}
