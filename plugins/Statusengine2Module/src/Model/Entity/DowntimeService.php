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

namespace Statusengine2Module\Model\Entity;

use Cake\ORM\Entity;

/**
 * DowntimeService Entity
 *
 * @property int $downtimehistory_id
 * @property int $instance_id
 * @property int $downtime_type
 * @property int $object_id
 * @property \Cake\I18n\DateTime $entry_time
 * @property string $author_name
 * @property string $comment_data
 * @property int $internal_downtime_id
 * @property int $triggered_by_id
 * @property int $is_fixed
 * @property int $duration
 * @property \Cake\I18n\DateTime $scheduled_start_time
 * @property \Cake\I18n\DateTime $scheduled_end_time
 * @property int $was_started
 * @property \Cake\I18n\DateTime $actual_start_time
 * @property int $actual_start_time_usec
 * @property \Cake\I18n\DateTime $actual_end_time
 * @property int $actual_end_time_usec
 * @property int $was_cancelled
 *
 * @property \Statusengine2Module\Model\Entity\ObjectEntity $object
 */
class DowntimeService extends Entity {
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
        'instance_id'            => true,
        'downtime_type'          => true,
        'object_id'              => true,
        'entry_time'             => true,
        'author_name'            => true,
        'comment_data'           => true,
        'internal_downtime_id'   => true,
        'triggered_by_id'        => true,
        'is_fixed'               => true,
        'duration'               => true,
        'scheduled_start_time'   => true,
        'scheduled_end_time'     => true,
        'was_started'            => true,
        'actual_start_time'      => true,
        'actual_start_time_usec' => true,
        'actual_end_time'        => true,
        'actual_end_time_usec'   => true,
        'was_cancelled'          => true,
        'instance'               => true,
        'object'                 => true,
        'internal_downtime'      => true,
        'triggered_by'           => true
    ];
}
