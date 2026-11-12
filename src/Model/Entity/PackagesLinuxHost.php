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
 * PackagesLinuxHost Entity
 *
 * @property int $id
 * @property int $package_linux_id
 * @property int $host_id
 * @property string $current_version
 * @property string $available_version
 * @property bool $needs_update
 * @property bool $is_security_update
 * @property bool $is_patch
 * @property string $source_package
 * @property string $source_version
 * @property string $architecture
 * @property string $modularity_label
 * @property string $source_rpm
 * @property \Cake\I18n\DateTime $modified
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Host $host
 */
class PackagesLinuxHost extends Entity {
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
        'package_linux_id'     => true,
        'host_id'              => true,
        'current_version'      => true,
        'available_version'    => true,
        'needs_update'         => true,
        'is_security_update'   => true,
        'is_patch'             => true,
        'source_package'       => true,
        'source_version'       => true,
        'architecture'         => true,
        'modularity_label'     => true,
        'source_rpm'           => true,
        'modified'             => true,
        'created'              => true,
        'host'                 => true,
    ];
}
