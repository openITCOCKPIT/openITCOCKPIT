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
 * CveCpeMapping Entity
 *
 * Which installed applications belong to a CPE product, with up to two conditions.
 *
 * @property int $id
 * @property int $cve_cpe_product_id
 * @property string $platform
 * @property string $first_field
 * @property string $first_match
 * @property string $first_value
 * @property string|null $second_field
 * @property string|null $second_match
 * @property string|null $second_value
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\CveCpeProduct $cve_cpe_product
 */
class CveCpeMapping extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'cve_cpe_product_id' => true,
        'platform'           => true,
        'first_field'        => true,
        'first_match'        => true,
        'first_value'        => true,
        'second_field'       => true,
        'second_match'       => true,
        'second_value'       => true,
        'cve_cpe_product'    => true,
    ];
}
