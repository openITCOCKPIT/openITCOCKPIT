<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

namespace itnovum\openITCOCKPIT\Core\ValueObjects;

/**
 * Class NotificationReasonTypes
 * @link https://github.com/statusengine/worker/blob/2e6809639046197f1d495d629806fcc630950f92/src/ValueObjects/Notification.php#L217-L230
 */
class NotificationReasonTypes {

    public const NOTIFICATION_NORMAL = 0;
    public const NOTIFICATION_ACKNOWLEDGEMENT = 1;
    public const NOTIFICATION_FLAPPINGSTART = 2;
    public const NOTIFICATION_FLAPPINGSTOP = 3;
    public const NOTIFICATION_FLAPPINGDISABLED = 4;
    public const NOTIFICATION_DOWNTIMESTART = 5;
    public const NOTIFICATION_DOWNTIMEEND = 6;
    public const NOTIFICATION_DOWNTIMECANCELLED = 7;
    public const NOTIFICATION_CUSTOM = 8;
}
