<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * UsersToOrganizationalChartNode Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $organizational_chart_node_id
 * @property int $is_manager
 * @property int $user_role
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\OrganizationalChartNode $organizational_chart_node
 */
class UsersToOrganizationalChartNode extends Entity
{
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
        'user_id' => true,
        'organizational_chart_node_id' => true,
        'is_manager' => true,
        'user_role' => true,
        'user' => true,
        'organizational_chart_node' => true,
    ];

    public const USER = 1 << 0;             // 1
    public const MANAGER = 1 << 1;          // 2
    public const REGION_MANAGER = 1 << 2;   // 4

}
