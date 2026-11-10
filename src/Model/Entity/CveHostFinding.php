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
 * CveHostFinding Entity
 *
 * One finding of a host: a CVE that affects one component in one version.
 * Classification and life cycle are evaluated per host, the Vuls result itself is shared, see CvePackageState.
 *
 * @property int $id
 * @property string $finding_hash
 * @property int $host_id
 * @property int $cve_id
 * @property string $component_type
 * @property string $component_name
 * @property string $component_version
 * @property bool $is_kernel
 * @property bool|null $is_running
 * @property string|null $fixed_in
 * @property string|null $fix_state
 * @property bool $not_fixed_yet
 * @property bool $update_available
 * @property string|null $update_version
 * @property int|null $severity
 * @property string|null $severity_source
 * @property string $view_name
 * @property string $color
 * @property int $rule_rank
 * @property bool $review_required
 * @property string $policy_version
 * @property \Cake\I18n\DateTime $first_seen
 * @property \Cake\I18n\DateTime $last_seen
 * @property \Cake\I18n\DateTime|null $resolved_at
 * @property \Cake\I18n\DateTime|null $reopened_at
 * @property int $reopen_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Cve $cve
 * @property \App\Model\Entity\CveDecision|null $cve_decision
 * @property \App\Model\Entity\Host $host
 */
class CveHostFinding extends Entity {
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
        'finding_hash'      => true,
        'host_id'           => true,
        'cve_id'            => true,
        'component_type'    => true,
        'component_name'    => true,
        'component_version' => true,
        'is_kernel'         => true,
        'is_running'        => true,
        'fixed_in'          => true,
        'fix_state'         => true,
        'not_fixed_yet'     => true,
        'update_available'  => true,
        'update_version'    => true,
        'severity'          => true,
        'severity_source'   => true,
        'view_name'         => true,
        'color'             => true,
        'rule_rank'         => true,
        'review_required'   => true,
        'policy_version'    => true,
        'first_seen'        => true,
        'last_seen'         => true,
        'resolved_at'       => true,
        'reopened_at'       => true,
        'reopen_count'      => true,
        'created'           => true,
        'modified'          => true,
        'cve'               => true,
        'cve_decision'      => true,
        'host'              => true,
    ];
}
