<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Changelog Entity
 *
 * @property int $id
 * @property string $model
 * @property string $action
 * @property int $object_id
 * @property int|null $objecttype_id
 * @property int|null $user_id
 * @property string $data
 * @property string $name
 * @property int $module_flag
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\User $user
 */
class Changelog extends Entity {
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
        'model'         => true,
        'action'        => true,
        'object_id'     => true,
        'objecttype_id' => true,
        'user_id'       => true,
        'data'          => true,
        'name'          => true,
        'module_flag'   => true,
        'created'       => true,
        'user'          => true,
        'containers'    => true
    ];

    public function jsonSerialize(): array {
        $data = $this->extract($this->getVisible());
        if (isset($data['data'])) {
            $data['data_unserialized'] = unserialize($data['data']);
        }
        return $data;
    }
}
