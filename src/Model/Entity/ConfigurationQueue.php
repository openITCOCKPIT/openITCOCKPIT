<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ConfigurationQueue Entity
 *
 * @property int $id
 * @property string $task
 * @property string $data
 * @property string|null $json_data
 * @property string|null $module
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class ConfigurationQueue extends Entity
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
        'task' => true,
        'data' => true,
        'json_data' => true,
        'module' => true,
        'created' => true,
        'modified' => true
    ];
}
