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

abstract class Notification {

    /**
     * @var int
     */
    private $state;

    /**
     * @var string
     */
    private $output;

    /**
     * @var int|string
     */
    private $start_time;

    /**
     * @var int|null
     */
    private $reason_type = null;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    /**
     * Notification constructor.
     * @param $data
     * @param UserTime|null $UserTime
     */
    public function __construct($data, $UserTime = null) {

        if (isset($data['state'])) {
            $this->state = (int)$data['state'];
        }

        if (isset($data['output'])) {
            $this->output = $data['output'];
        }

        if (isset($data['Contactnotifications']['start_time'])) {
            $this->start_time = $data['Contactnotifications']['start_time'];
        }

        if (isset($data['start_time'])) {
            $this->start_time = $data['start_time'];
        }

        if (isset($data['reason_type'])) {
            $this->reason_type = $data['reason_type'];
        }

        $this->UserTime = $UserTime;
    }

    /**
     * @return int
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getOutput() {
        return $this->output;
    }

    public function getReasonType(): mixed {
        return $this->reason_type;
    }

    public function getReasonTypeString(): string {
        if ($this->reason_type === null) {
            return '';
        }

        // Source: https://github.com/naemon/naemon-core/blob/1e854668f5153aa465cecd95335338411f6aaf02/src/naemon/notifications.h#L67-L77
        switch ($this->reason_type) {
            case 0:
                return 'Alert';
            case 1:
                return 'Acknowledgement set';
            case 2:
                return 'Start flapping';
            case 3:
                return 'Stop flapping';
            case 4:
                return 'Flapping disabled';
            case 5:
                return 'Downtime start';
            case 6:
                return 'Downtime end';
            case 7:
                return 'Downtime cancelled';
            case 8:
                return 'Custom';
            default:
                return '';
        }
    }

    /**
     * @return int|string
     */
    public function getStartTime() {
        if (!is_numeric($this->start_time)) {
            if ($this->start_time instanceof FrozenTime) {
                $this->start_time = $this->start_time->timestamp;
            } else {
                $this->start_time = strtotime($this->start_time);
            }
        }

        return $this->start_time;
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
            $arr['start_time'] = $this->UserTime->format($this->getStartTime());
        } else {
            $arr['start_time'] = $this->getStartTime();
        }

        return $arr;
    }

}
