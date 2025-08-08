<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Commandargument Entity
 *
 * @property int $id
 * @property int $command_id
 * @property string $name
 * @property string $human_name
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Command $command
 * @property \App\Model\Entity\Hostcommandargumentvalue[] $hostcommandargumentvalues
 * @property \App\Model\Entity\Hosttemplatecommandargumentvalue[] $hosttemplatecommandargumentvalues
 * @property \App\Model\Entity\Servicecommandargumentvalue[] $servicecommandargumentvalues
 * @property \App\Model\Entity\Serviceeventcommandargumentvalue[] $serviceeventcommandargumentvalues
 * @property \App\Model\Entity\Servicetemplatecommandargumentvalue[] $servicetemplatecommandargumentvalues
 * @property \App\Model\Entity\Servicetemplateeventcommandargumentvalue[] $servicetemplateeventcommandargumentvalues
 */
class Commandargument extends Entity
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
        'command_id' => true,
        'name' => true,
        'human_name' => true,
        'created' => true,
        'modified' => true,
        'command' => true,
        'hostcommandargumentvalues' => true,
        'hosttemplatecommandargumentvalues' => true,
        'servicecommandargumentvalues' => true,
        'serviceeventcommandargumentvalues' => true,
        'servicetemplatecommandargumentvalues' => true,
        'servicetemplateeventcommandargumentvalues' => true
    ];
}
