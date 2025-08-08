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

namespace Statusengine2Module\Model\Entity;

use Cake\ORM\Entity;

/**
 * NagiosContactnotification Entity
 *
 * @property int $contactnotification_id
 * @property int $instance_id
 * @property int $notification_id
 * @property int $contact_object_id
 * @property \Cake\I18n\DateTime $start_time
 * @property int $start_time_usec
 * @property \Cake\I18n\DateTime $end_time
 * @property int $end_time_usec
 *
 * @property \Statusengine2Module\Model\Entity\Contactnotification $contactnotification
 * @property \Statusengine2Module\Model\Entity\Notification $notification
 * @property \Statusengine2Module\Model\Entity\ObjectEntity $contact_object
 */
class Contactnotification extends Entity {
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
        'instance_id'         => true,
        'notification_id'     => true,
        'contact_object_id'   => true,
        'start_time_usec'     => true,
        'end_time'            => true,
        'end_time_usec'       => true,
        'contactnotification' => true,
        'instance'            => true,
        'notification'        => true,
        'contact_object'      => true,
    ];
}
