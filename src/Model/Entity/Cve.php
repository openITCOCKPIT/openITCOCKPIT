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
 * Cve Entity
 *
 * Master data of one CVE as reported by Vuls.
 *
 * @property int $id
 * @property string $cve_id
 * @property string|null $summary
 * @property \Cake\I18n\DateTime|null $published
 * @property string|null $cvss_score
 * @property string|null $cvss_vector
 * @property string|null $cvss_source
 * @property bool $kev_cisa
 * @property \Cake\I18n\Date|null $kev_cisa_added
 * @property bool $kev_vulncheck
 * @property int $metasploit_count
 * @property int $verified_exploit_count
 * @property int $poc_exploit_count
 * @property string|null $source_link
 * @property string|null $feed_digest
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\CvePackageStateCve[] $cve_package_state_cves
 */
class Cve extends Entity {
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
        'cve_id'                 => true,
        'summary'                => true,
        'published'              => true,
        'cvss_score'             => true,
        'cvss_vector'            => true,
        'cvss_source'            => true,
        'kev_cisa'               => true,
        'kev_cisa_added'         => true,
        'kev_vulncheck'          => true,
        'metasploit_count'       => true,
        'verified_exploit_count' => true,
        'poc_exploit_count'      => true,
        'source_link'            => true,
        'feed_digest'            => true,
        'created'                => true,
        'modified'               => true,
        'cve_package_state_cves' => true,
    ];
}
