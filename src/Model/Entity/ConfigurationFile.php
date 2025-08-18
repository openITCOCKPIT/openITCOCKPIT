<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ConfigurationFile Entity
 *
 * @property int $id
 * @property string $config_file
 * @property string $key
 * @property string $value
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class ConfigurationFile extends Entity
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
        'config_file' => true,
        'key' => true,
        'value' => true,
        'created' => true,
        'modified' => true
    ];
}
