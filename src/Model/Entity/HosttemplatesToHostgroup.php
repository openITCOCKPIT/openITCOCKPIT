<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * HosttemplatesToHostgroup Entity
 *
 * @property int $id
 * @property int $hosttemplate_id
 * @property int $hostgroup_id
 *
 * @property \App\Model\Entity\Hosttemplate $hosttemplate
 * @property \App\Model\Entity\Hostgroup $hostgroup
 */
class HosttemplatesToHostgroup extends Entity {
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
        'hosttemplate_id' => true,
        'hostgroup_id'    => true,
        'hosttemplate'    => true,
        'hostgroup'       => true,
    ];
}
