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

namespace itnovum\openITCOCKPIT\Core\Views;


use Cake\I18n\FrozenTime;

class Downtime {

    /**
     * @var string
     */
    private $authorName;

    /**
     * @var string
     */
    private $commentData;

    /**
     * @var int|string
     */
    private $entryTime;

    /**
     * @var int|string
     */
    private $scheduledStartTime;

    /**
     * @var int|string
     */
    private $scheduledEndTime;

    /**
     * @var int|string
     */
    private $actualEndTime;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var bool
     */
    private $wasStarted;

    /**
     * @var int
     */
    private $internalDowntimeId;

    /**
     * @var int
     */
    private $downtimehistoryId;

    /**
     * @var bool
     */
    private $wasCancelled;

    /**
     * @var bool
     */
    private $allowEdit = false;


    /**
     * @var UserTime|null
     */
    private $UserTime;

    /**
     * Downtime constructor.
     * @param $downtime
     * @param bool $allowEdit
     */
    public function __construct($downtime, $allowEdit = false, $UserTime = null) {
        if (isset($downtime['author_name'])) {
            $this->authorName = $downtime['author_name'];
        }

        if (isset($downtime['comment_data'])) {
            $this->commentData = $downtime['comment_data'];
        }

        if (isset($downtime['entry_time'])) {
            $this->entryTime = $downtime['entry_time'];
        }

        if (isset($downtime['scheduled_start_time'])) {
            $this->scheduledStartTime = $downtime['scheduled_start_time'];
        }

        if (isset($downtime['scheduled_end_time'])) {
            $this->scheduledEndTime = $downtime['scheduled_end_time'];
        }

        if (isset($downtime['actual_end_time'])) {
            $this->actualEndTime = $downtime['actual_end_time'];
        }

        if (isset($downtime['duration'])) {
            $this->duration = (int)$downtime['duration'];
        }

        if (isset($downtime['was_started'])) {
            $this->wasStarted = (bool)$downtime['was_started'];
        }

        if (isset($downtime['internal_downtime_id'])) {
            $this->internalDowntimeId = (int)$downtime['internal_downtime_id'];
        }

        if (isset($downtime['downtimehistory_id'])) {
            $this->downtimehistoryId = (int)$downtime['downtimehistory_id'];
        }

        if (isset($downtime['was_cancelled'])) {
            $this->wasCancelled = (bool)$downtime['was_cancelled'];
        }
        $this->allowEdit = $allowEdit;

        $this->UserTime = $UserTime;
    }

    /**
     * @return string
     */
    public function getAuthorName() {
        return $this->authorName;
    }

    /**
     * @return string
     */
    public function getCommentData() {
        return $this->commentData;
    }

    /**
     * @return int
     */
    public function getEntryTime() {
        if ($this->entryTime === null) {
            return 0;
        }

        if (!is_numeric($this->entryTime)) {
            if ($this->entryTime instanceof FrozenTime) {
                $this->entryTime = $this->entryTime->timestamp;
            } else {
                $this->entryTime = strtotime($this->entryTime);
            }
        }
        return $this->entryTime;
    }

    /**
     * @return int
     */
    public function getScheduledStartTime() {
        if ($this->scheduledStartTime === null) {
            return 0;
        }

        if (!is_numeric($this->scheduledStartTime)) {
            if ($this->scheduledStartTime instanceof FrozenTime) {
                $this->scheduledStartTime = $this->scheduledStartTime->timestamp;
            } else {
                $this->scheduledStartTime = strtotime($this->scheduledStartTime);
            }
        }
        return $this->scheduledStartTime;
    }

    /**
     * @return int
     */
    public function getScheduledEndTime() {
        if ($this->scheduledEndTime === null) {
            return 0;
        }

        if (!is_numeric($this->scheduledEndTime)) {
            if ($this->scheduledEndTime instanceof FrozenTime) {
                $this->scheduledEndTime = $this->scheduledEndTime->timestamp;
            } else {
                $this->scheduledEndTime = strtotime($this->scheduledEndTime);
            }
        }
        return $this->scheduledEndTime;
    }

    /**
     * @return int
     */
    public function getActualEndTime() {
        if ($this->actualEndTime === null) {
            return 0;
        }

        if (!is_numeric($this->actualEndTime)) {
            if ($this->actualEndTime instanceof FrozenTime) {
                $this->actualEndTime = $this->actualEndTime->timestamp;
            } else {
                $this->actualEndTime = strtotime($this->actualEndTime);
            }
        }
        return $this->actualEndTime;
    }

    /**
     * @return int
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * @return bool
     */
    public function wasStarted() {
        return $this->wasStarted;
    }

    /**
     * @return int
     */
    public function getInternalDowntimeId() {
        return $this->internalDowntimeId;
    }

    /**
     * @return int
     */
    public function getDowntimehistoryId() {
        return $this->downtimehistoryId;
    }

    /**
     * @return bool
     */
    public function wasCancelled() {
        return $this->wasCancelled;
    }

    /**
     * @return bool
     */
    public function isCancellable() {
        return $this->getScheduledEndTime() > time() && $this->wasCancelled === false;
    }

    /**
     * @return bool
     */
    public function isRunning() {
        if ($this->wasStarted() === true && $this->isCancellable()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isExpired() {
        if ($this->wasStarted() && $this->getScheduledEndTime() < time() && $this->wasCancelled() === false) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function allowEdit() {
        return $this->allowEdit;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        if (isset($arr['UserTime'])) {
            unset($arr['UserTime']);
        }

        if ($this->UserTime !== null) {
            $arr['scheduledStartTime'] = $this->UserTime->format($this->getScheduledStartTime());
            $arr['scheduledEndTime'] = $this->UserTime->format($this->getScheduledEndTime());
            $arr['actualEndTime'] = $this->UserTime->format($this->getActualEndTime());
            $arr['entryTime'] = $this->UserTime->format($this->getEntryTime());
            $arr['durationHuman'] = $this->UserTime->secondsInHumanShort($this->getDuration());
        } else {
            $arr['scheduledStartTime'] = $this->getScheduledStartTime();
            $arr['scheduledEndTime'] = $this->getScheduledEndTime();
            $arr['actualEndTime'] = $this->getActualEndTime();
            $arr['entryTime'] = $this->getEntryTime();
            $arr['durationHuman'] = $this->getDuration();
        }
        $arr['isCancellable'] = $this->isCancellable();
        $arr['isRunning'] = $this->isRunning();
        $arr['isExpired'] = $this->isExpired();
        return $arr;
    }

}
