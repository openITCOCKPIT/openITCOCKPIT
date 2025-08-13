<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServicetemplatesToServicegroup Entity
 *
 * @property int $id
 * @property int $servicetemplate_id
 * @property int $servicegroup_id
 *
 * @property \App\Model\Entity\Servicetemplate $servicetemplate
 * @property \App\Model\Entity\Servicegroup $servicegroup
 */
class ServicetemplatesToServicegroup extends Entity
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
        'servicetemplate_id' => true,
        'servicegroup_id' => true,
        'servicetemplate' => true,
        'servicegroup' => true,
    ];
}
