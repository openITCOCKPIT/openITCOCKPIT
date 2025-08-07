<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Proxy Entity
 *
 * @property int $id
 * @property string $ipaddress
 * @property int $port
 * @property bool $enabled
 */
class Proxy extends Entity
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
        'ipaddress' => true,
        'port' => true,
        'enabled' => true
    ];
}
