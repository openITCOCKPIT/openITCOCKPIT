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

declare(strict_types=1);

namespace Statusengine3Module\Model\Entity;

use Cake\ORM\Entity;
use itnovum\openITCOCKPIT\Core\ValueObjects\NotificationReasonTypes;

/**
 * NotificationServicesLog Entity
 *
 * @property string $hostname
 * @property string $service_description
 * @property int $start_time
 * @property int $start_time_usec
 * @property int $end_time
 * @property int|null $state
 * @property int|null|NotificationReasonTypes $reason_type
 * @property bool $is_escalated
 * @property int $contacts_notified_count
 * @property string|null $output
 * @property string|null $ack_author
 * @property string|null $ack_data
 */
class NotificationServicesLog extends Entity {
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
        'end_time'                => true,
        'state'                   => true,
        'reason_type'             => true,
        'is_escalated'            => true,
        'contacts_notified_count' => true,
        'output'                  => true,
        'ack_author'              => true,
        'ack_data'                => true,
    ];
}
