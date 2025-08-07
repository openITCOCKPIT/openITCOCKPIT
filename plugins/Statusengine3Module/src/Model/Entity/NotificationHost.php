<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace Statusengine3Module\Model\Entity;

use Cake\ORM\Entity;
use itnovum\openITCOCKPIT\Core\ValueObjects\NotificationReasonTypes;

/**
 * NotificationHost Entity
 *
 * @property int $id
 * @property string|null $hostname
 * @property string|null $contact_name
 * @property string|null $command_name
 * @property string|null $command_args
 * @property int|null $state
 * @property int $start_time
 * @property int $end_time
 * @property int|null|NotificationReasonTypes $reason_type
 * @property string|null $output
 * @property string|null $ack_author
 * @property string|null $ack_data
 */
class NotificationHost extends Entity {
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
        'hostname'     => true,
        'contact_name' => true,
        'command_name' => true,
        'command_args' => true,
        'state'        => true,
        'end_time'     => true,
        'reason_type'  => true,
        'output'       => true,
        'ack_author'   => true,
        'ack_data'     => true,
    ];
}
