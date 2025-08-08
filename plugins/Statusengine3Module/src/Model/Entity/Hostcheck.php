<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace Statusengine3Module\Model\Entity;

use Cake\ORM\Entity;

/**
 * Hostcheck Entity
 *
 * @property string $hostname
 * @property int $start_time
 * @property int|null $state
 * @property bool|null $is_hardstate
 * @property int $end_time
 * @property string|null $output
 * @property int|null $timeout
 * @property bool|null $early_timeout
 * @property float|null $latency
 * @property float|null $execution_time
 * @property string|null $perfdata
 * @property string|null $command
 * @property int|null $current_check_attempt
 * @property int|null $max_check_attempts
 * @property string|null $long_output
 */
class Hostcheck extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'state'                 => true,
        'is_hardstate'          => true,
        'end_time'              => true,
        'output'                => true,
        'timeout'               => true,
        'early_timeout'         => true,
        'latency'               => true,
        'execution_time'        => true,
        'perfdata'              => true,
        'command'               => true,
        'current_check_attempt' => true,
        'max_check_attempts'    => true,
        'long_output'           => true,
    ];
}
