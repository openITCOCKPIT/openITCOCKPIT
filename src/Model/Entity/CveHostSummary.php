<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CveHostSummary Entity
 *
 * Result of the last evaluation of one host, used for the host list. All counters count CVEs, not packages.
 *
 * @property int $id
 * @property int $host_id
 * @property string $platform
 * @property string $status
 * @property string $color
 * @property string|null $fingerprint
 * @property string|null $feed_digest
 * @property \Cake\I18n\DateTime|null $inventory_last_update
 * @property \Cake\I18n\DateTime|null $evaluated
 * @property \Cake\I18n\DateTime|null $failed
 * @property string|null $error_message
 * @property int $critical_count
 * @property int $high_count
 * @property int $medium_count
 * @property int $low_count
 * @property int $unknown_count
 * @property int $open_count
 * @property int $red_count
 * @property int $action_count
 * @property int $kev_count
 * @property int $exploit_count
 * @property int $fixable_count
 * @property int $accepted_count
 * @property int $not_affected_count
 * @property int $in_progress_count
 * @property int $installations
 * @property int $evaluated_installations
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Host $host
 */
class CveHostSummary extends Entity {
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
        'host_id'                 => true,
        'platform'                => true,
        'status'                  => true,
        'color'                   => true,
        'fingerprint'             => true,
        'feed_digest'             => true,
        'inventory_last_update'   => true,
        'evaluated'               => true,
        'failed'                  => true,
        'error_message'           => true,
        'critical_count'          => true,
        'high_count'              => true,
        'medium_count'            => true,
        'low_count'               => true,
        'unknown_count'           => true,
        'open_count'              => true,
        'red_count'               => true,
        'action_count'            => true,
        'kev_count'               => true,
        'exploit_count'           => true,
        'fixable_count'           => true,
        'accepted_count'          => true,
        'not_affected_count'      => true,
        'in_progress_count'       => true,
        'installations'           => true,
        'evaluated_installations' => true,
        'created'                 => true,
        'modified'                => true,
        'host'                    => true,
    ];
}
