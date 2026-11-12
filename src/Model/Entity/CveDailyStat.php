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
 * CveDailyStat Entity
 *
 * Values of one host on one day, used for the trend. Every evaluation of the day updates the row.
 *
 * @property int $id
 * @property \Cake\I18n\Date $stat_date
 * @property int $host_id
 * @property int $open_count
 * @property int $action_count
 * @property int $red_count
 * @property int $kev_count
 * @property int $new_count
 * @property int $resolved_count
 * @property \Cake\I18n\DateTime $created First evaluation of the day
 * @property \Cake\I18n\DateTime $modified Last evaluation or classification of the day
 */
class CveDailyStat extends Entity {
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
        'stat_date'      => true,
        'host_id'        => true,
        'open_count'     => true,
        'action_count'   => true,
        'red_count'      => true,
        'kev_count'      => true,
        'new_count'      => true,
        'resolved_count' => true,
    ];
}
