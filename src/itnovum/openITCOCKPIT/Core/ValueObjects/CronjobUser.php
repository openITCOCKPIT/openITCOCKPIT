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
 * This class is build to have a User object when commands run as Cronjob.
 * The User object is needed for the Changelog.
 */
class CronjobUser extends User {

    public function __construct() {
        $timezone = date_default_timezone_get();
        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        $this->recursiveBrowser = false;
        $this->fullName = 'Cronjob';
        $this->id = 0;
        $this->timezone = $timezone;
        $this->dateformat = 'H:i:s - d.m.Y';
        $this->usergroupId = 0;
    }

    public function getUsergroupId(): int {
        return 0;
    }

    public function getUserAvatar() {
        return WWW_ROOT . 'img' . DS . 'logos' . DS . 'openitcockpit-logo-url.png';
    }

}
