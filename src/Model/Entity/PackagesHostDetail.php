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
 * PackagesHostDetail Entity
 *
 * @property int $id
 * @property int $host_id
 * @property string $os_type
 * @property string $os_name
 * @property string $os_version
 * @property string $os_family
 * @property string $kernel_release
 * @property string $packages_hash
 * @property string $agent_version
 * @property bool $reboot_required
 * @property int $system_uptime
 * @property \Cake\I18n\DateTime $last_update
 * @property int $available_updates
 * @property int $available_security_updates
 * @property string|null $last_error
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Host $host
 */
class PackagesHostDetail extends Entity {
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
        'host_id'                    => true,
        'os_type'                    => true,
        'os_name'                    => true,
        'os_version'                 => true,
        'os_family'                  => true,
        'kernel_release'             => true,
        'packages_hash'              => true,
        'agent_version'              => true,
        'reboot_required'            => true,
        'system_uptime'              => true,
        'last_update'                => true,
        'available_updates'          => true,
        'available_security_updates' => true,
        'last_error'                 => true,
        'created'                    => true,
        'modified'                   => true,
        'host'                       => true,
    ];
}
