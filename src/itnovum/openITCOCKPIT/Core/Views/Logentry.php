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

namespace itnovum\openITCOCKPIT\Core\Views;


use itnovum\openITCOCKPIT\Core\ValueObjects\LogentryTypes;

class Logentry {

    private $id = null;

    private $logentry_time = null;

    private $entry_time = null;

    private $logentry_type = null;

    private $logentry_data = null;


    /**
     * @var UserTime
     */
    private $UserTime;

    /**
     * Logentry constructor.
     * @param array $logentry
     * @param null $UserTime
     */
    public function __construct($logentry, $UserTime = null) {
        if (isset($logentry['id'])) {
            $this->id = (int)$logentry['id'];
        }

        if (isset($logentry['logentry_time'])) {
            $this->logentry_time = $logentry['logentry_time'];
        }

        if (isset($logentry['entry_time'])) {
            $this->entry_time = $logentry['entry_time'];
        }

        if (isset($logentry['logentry_type'])) {
            $this->logentry_type = (int)$logentry['logentry_type'];
        }

        if (isset($logentry['logentry_data'])) {
            $this->logentry_data = $logentry['logentry_data'];
        }

        $this->UserTime = $UserTime;
    }

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return null
     */
    public function getLogentryTime() {
        return $this->logentry_time;
    }

    /**
     * @return null
     */
    public function getEntryTime() {
        return $this->entry_time;
    }

    /**
     * @return int|null
     */
    public function getLogentryType() {
        return $this->logentry_type;
    }

    /**
     * @return null
     */
    public function getLogentryData() {
        return $this->logentry_data;
    }


    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        if (isset($arr['UserTime'])) {
            unset($arr['UserTime']);
        }

        $LogentryType = new LogentryTypes();
        $typesArray = $LogentryType->getTypes();
        $typesArray[514] = __('External command failed');
        $typesArray[6] = __('Timeperiod transition');

        $arr['logentry_type_string'] = $typesArray[$this->getLogentryType()];

        if ($this->UserTime !== null) {
            $arr['entry_time'] = $this->UserTime->format($this->getEntryTime());
            $arr['logentry_time'] = $this->UserTime->format($this->getLogentryTime());
        }
        return $arr;
    }

}
