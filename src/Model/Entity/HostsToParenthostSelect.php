<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * HostsToParenthost Entity
 *
 * @property int $id
 * @property int $host_id
 * @property int $parenthost_id
 *
 * @property \App\Model\Entity\Host $host
 * @property \App\Model\Entity\Parenthost $parenthost
 */
class HostsToParenthostSelect extends Entity {
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
        'host_id'       => true,
        'parenthost_id' => true,
        'host'          => true,
        'parenthost'    => true,
    ];
}
