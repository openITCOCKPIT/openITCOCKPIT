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


class Systemdowntime {

    private $id;

    private $objecttypeId;

    private $objectId;

    private $downtimeTypeId;

    private $weekdays = [];

    private $weekdaysHuman = [];

    private $dayOfMonth = [];

    private $startTime;

    private $duration;

    private $comment;

    private $author;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    /**
     * Systemdowntime constructor.
     * @param array $systemdowntime
     * @param UserTime|null $UserTime
     */
    public function __construct($systemdowntime, $UserTime = null) {
        $this->id = (int)$systemdowntime['id'];
        $this->objecttypeId = (int)$systemdowntime['objecttype_id'];
        $this->objectId = (int)$systemdowntime['object_id'];
        $this->downtimeTypeId = (int)$systemdowntime['downtimetype_id'];


        if ($systemdowntime['weekdays'] === null) {
            $systemdowntime['weekdays'] = '';
        }
        $this->weekdays = explode(',', $systemdowntime['weekdays']);
        if (empty($systemdowntime['weekdays'])) {
            $this->weekdays = [];
        }

        $this->weekdaysHuman = $this->getHumanWeekDays();

        if ($systemdowntime['day_of_month'] === null) {
            $systemdowntime['day_of_month'] = '';
        }
        $this->dayOfMonth = explode(',', $systemdowntime['day_of_month']);
        if (empty($systemdowntime['day_of_month'])) {
            $this->dayOfMonth = [];
        }
        $this->startTime = $systemdowntime['from_time'];
        $this->duration = (int)$systemdowntime['duration'];
        $this->comment = $systemdowntime['comment'];
        $this->author = $systemdowntime['author'];
        $this->UserTime = $UserTime;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getObjecttypeId() {
        return $this->objecttypeId;
    }

    /**
     * @return int
     */
    public function getObjectId() {
        return $this->objectId;
    }

    /**
     * @return int
     */
    public function getDowntimeTypeId() {
        return $this->downtimeTypeId;
    }

    /**
     * @return array
     */
    public function getWeekdays() {
        return $this->weekdays;
    }

    /**
     * @return array
     */
    public function getDayOfMonth() {
        return $this->dayOfMonth;
    }

    /**
     * @return mixed
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * @return mixed
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @return mixed
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * @return array
     */
    private function getHumanWeekDays() {
        $weekdays = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            7 => __('Sunday'),
        ];

        $result = [];
        foreach ($this->weekdays as $day) {
            $result[] = $weekdays[$day];
        }
        return $result;
    }

    public function toArray() {
        $arr = get_object_vars($this);
        if ($this->UserTime instanceof UserTime) {
            $offset = $this->UserTime->getUserTimeToServerOffset();

            $startTime = strtotime($this->startTime);
            $startTime = $startTime + $offset;

            $arr['startTime'] = date('H:i', $startTime);
        }

        return $arr;
    }
}
