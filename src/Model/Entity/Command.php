<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Command Entity
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $command_line
 * @property int $command_type
 * @property string|null $human_args
 * @property string $uuid
 * @property string|null $description
 *
 * @property \App\Model\Entity\Commandargument[] $commandarguments
 * @property \App\Model\Entity\Host[] $hosts
 * @property \App\Model\Entity\Hosttemplate[] $hosttemplates
 * @property \App\Model\Entity\Service[] $services
 * @property \App\Model\Entity\Servicetemplate[] $servicetemplates
 */
class Command extends Entity {

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
        'name'             => true,
        'command_line'     => true,
        'command_type'     => true,
        'human_args'       => true,
        'uuid'             => true,
        'description'      => true,
        'commandarguments' => true,
        'hosts'            => true,
        'hosttemplates'    => true,
        'services'         => true,
        'servicetemplates' => true
    ];
}
