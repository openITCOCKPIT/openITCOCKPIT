<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrganizationalChartConnection Entity
 *
 * @property int $id
 * @property int|null $organizational_chart_id
 * @property int|null $organizational_chart_input_node_id
 * @property int|null $organizational_chart_output_node_id
 * @property \Cake\I18n\FrozenTime $modified
 * @property \Cake\I18n\FrozenTime $created
 *
 * @property \App\Model\Entity\OrganizationalChart $organizational_chart
 */
class OrganizationalChartConnection extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'organizational_chart_id'             => true,
        'organizational_chart_input_node_id'  => true,
        'organizational_chart_output_node_id' => true,
        'modified'                            => true,
        'created'                             => true,
        'organizational_chart'                => true,
    ];
}
