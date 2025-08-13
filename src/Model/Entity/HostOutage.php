<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * HostOutage Entity
 *
 * @property int $host_id
 * @property int $start_time
 * @property int $state_time_usec
 * @property int $end_time
 * @property string|null $output
 * @property bool $is_hardstate
 * @property bool $in_downtime
 *
 * @property \App\Model\Entity\Host $host
 */
class HostOutage extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'end_time'     => true,
        'output'       => true,
        'is_hardstate' => true,
        'in_downtime'  => true,
        'host'         => true,
    ];
}
