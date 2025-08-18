<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Lib\Traits\BitFlagTrait;
use Cake\ORM\Entity;

/**
 * DashboardTab Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $position
 * @property string $name
 * @property bool $shared
 * @property int|null $source_tab_id
 * @property int|null $check_for_updates
 * @property int|null $last_update
 * @property bool $locked
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property int|null $flags
 * @property  int|null $container_id
 * @property  int[]|null $usergroups
 * @property  int[]|null $allocated_users
 *
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Widget[] $widgets
 */
class DashboardTab extends Entity {

    use BitFlagTrait;

    /**
     * @deprecated
     */
    public const FLAG_BLANK = 0 << 0;        // 0
    public const FLAG_ALLOCATED = 1 << 0;    // 1
    public const FLAG_PINNED = 1 << 1;       // 2

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
        'user_id'           => true,
        'position'          => true,
        'name'              => true,
        'shared'            => true,
        'source_tab_id'     => true,
        'check_for_updates' => true,
        'last_update'       => true,
        'locked'            => true,
        'created'           => true,
        'modified'          => true,
        'user'              => true,
        'source_tab'        => true,
        'widgets'           => true,
        'flags'             => true,
        'container_id'      => true,
        'usergroups'        => true,
        'allocated_users'   => true,
    ];
}
