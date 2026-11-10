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
 * CvePackageState Entity
 *
 * One distinct package state of the Linux inventory, shared by all hosts that have it installed.
 *
 * @property int $id
 * @property string $state_hash
 * @property string $os_name
 * @property string $os_version
 * @property bool $is_kernel
 * @property string|null $kernel_release
 * @property string $name
 * @property string $version
 * @property string|null $architecture
 * @property string|null $source_package
 * @property string|null $source_version
 * @property string|null $modularity_label
 * @property string|null $feed_digest
 * @property \Cake\I18n\DateTime|null $checked
 * @property int $cve_count
 * @property int|null $max_severity
 * @property bool $has_kev
 * @property string|null $error_message
 * @property \Cake\I18n\DateTime $last_seen
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\CvePackageStateCve[] $cve_package_state_cves
 */
class CvePackageState extends Entity {
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
        'state_hash'             => true,
        'os_name'                => true,
        'os_version'             => true,
        'is_kernel'              => true,
        'kernel_release'         => true,
        'name'                   => true,
        'version'                => true,
        'architecture'           => true,
        'source_package'         => true,
        'source_version'         => true,
        'modularity_label'       => true,
        'feed_digest'            => true,
        'checked'                => true,
        'cve_count'              => true,
        'max_severity'           => true,
        'has_kev'                => true,
        'error_message'          => true,
        'last_seen'              => true,
        'created'                => true,
        'modified'               => true,
        'cve_package_state_cves' => true,
    ];

    /**
     * Returns true if the result of this package state belongs to the given Vuls database
     *
     * @param string $feedDigest
     * @return bool
     */
    public function isCheckedWithFeed(string $feedDigest): bool {
        return $this->feed_digest === $feedDigest && $this->checked !== null;
    }
}
