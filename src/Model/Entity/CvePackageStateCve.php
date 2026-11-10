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
 * CvePackageStateCve Entity
 *
 * One CVE that affects one package state, including the fix reported by the vendor.
 *
 * @property int $id
 * @property int $cve_package_state_id
 * @property int $cve_id
 * @property string|null $fixed_in
 * @property string|null $fix_state
 * @property bool $not_fixed_yet
 * @property int|null $severity
 * @property string|null $severity_source
 *
 * @property \App\Model\Entity\CvePackageState $cve_package_state
 * @property \App\Model\Entity\Cve $cve
 */
class CvePackageStateCve extends Entity {
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
        'cve_package_state_id' => true,
        'cve_id'               => true,
        'fixed_in'             => true,
        'fix_state'            => true,
        'not_fixed_yet'        => true,
        'severity'             => true,
        'severity_source'      => true,
        'cve_package_state'    => true,
        'cve'                  => true,
    ];
}
