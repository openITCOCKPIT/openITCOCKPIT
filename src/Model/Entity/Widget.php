<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Widget Entity
 *
 * @property int $id
 * @property int $dashboard_tab_id
 * @property int $type_id
 * @property int|null $host_id
 * @property int|null $service_id
 * @property int $row
 * @property int $col
 * @property int $width
 * @property int $height
 * @property string $title
 * @property string $color
 * @property string $directive
 * @property string $icon
 * @property string|null $json_data
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\DashboardTab $dashboard_tab
 * @property \App\Model\Entity\Type $type
 * @property \App\Model\Entity\Host $host
 * @property \App\Model\Entity\Service $service
 */
class Widget extends Entity {
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
        'dashboard_tab_id' => true,
        'type_id'          => true,
        'host_id'          => true,
        'service_id'       => true,
        'row'              => true,
        'col'              => true,
        'width'            => true,
        'height'           => true,
        'title'            => true,
        'color'            => true,
        'directive'        => true,
        'icon'             => true,
        'json_data'        => true,
        'created'          => true,
        'modified'         => true,
        'dashboard_tab'    => true,
        'type'             => true,
        'host'             => true,
        'service'          => true,
    ];
}
