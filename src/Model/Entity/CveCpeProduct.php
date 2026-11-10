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
 * CveCpeProduct Entity
 *
 * One CPE product, e.g. vendor "google" and product "chrome", with the rules that map installed applications
 * to it and the names it has in CVE descriptions.
 *
 * @property int $id
 * @property string $vendor
 * @property string $product
 * @property bool $enabled
 * @property bool $is_default
 * @property bool $verified
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\CveCpeMapping[] $cve_cpe_mappings
 * @property \App\Model\Entity\CveCpeProductName[] $cve_cpe_product_names
 */
class CveCpeProduct extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * is_default and verified are set by the seed and the CVE check, not by users.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'vendor'                => true,
        'product'               => true,
        'enabled'               => true,
        'cve_cpe_mappings'      => true,
        'cve_cpe_product_names' => true,
    ];
}
