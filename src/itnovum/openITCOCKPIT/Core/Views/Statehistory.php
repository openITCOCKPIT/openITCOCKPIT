<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace itnovum\openITCOCKPIT\Core\Views;

use Cake\I18n\FrozenTime;

abstract class Statehistory {


    /**
     * @var int
     */
    private $current_check_attempt;

    private $last_hard_state;

    /**
     * @var int
     */
    private $last_state;

    /**
     * @var string
     */
    private $long_output;

    /**
     * @var int
     */
    private $max_check_attempts;

    /**
     * @var string
     */
    private $output;

    /**
     * @var int
     */
    private $state;

    private $state_change;

    private $state_time;

    /**
     * @var bool
     */
    private $is_hardstate;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    /**
     * StatehistoryHost constructor.
     * @param array $data
     */
    public function __construct($data, $UserTime = null) {
        if (isset($data['current_check_attempt'])) {
            $this->current_check_attempt = (int)$data['current_check_attempt'];
        }

        if (isset($data['last_hard_state'])) {
            $this->last_hard_state = $data['last_hard_state'];
        }

        if (isset($data['last_state'])) {
            $this->last_state = (int)$data['last_state'];
        }

        if (isset($data['long_output'])) {
            $this->long_output = $data['long_output'];
        }

        if (isset($data['max_check_attempts'])) {
            $this->max_check_attempts = (int)$data['max_check_attempts'];
        }

        if (isset($data['output'])) {
            $this->output = $data['output'];
        }

        if (isset($data['state'])) {
            $this->state = (int)$data['state'];
        }

        if (isset($data['state_change'])) {
            $this->state_change = $data['state_change'];
        }

        if (isset($data['state_time'])) {
            $this->state_time = $data['state_time'];
        }

        if (isset($data['state_type'])) {
            $this->is_hardstate = (bool)$data['state_type'];
        }

        if (isset($data['is_hardstate'])) {
            $this->is_hardstate = (bool)$data['is_hardstate'];
        }

        $this->UserTime = $UserTime;
    }

    /**
     * @return int
     */
    public function getCurrentCheckAttempt() {
        return $this->current_check_attempt;
    }

    /**
     * @return mixed
     */
    public function getLastHardState() {
        return $this->last_hard_state;
    }

    /**
     * @return int
     */
    public function getLastState() {
        return $this->last_state;
    }

    /**
     * @return string
     */
    public function getLongOutput() {
        return $this->long_output;
    }

    /**
     * @return int
     */
    public function getMaxCheckAttempts() {
        return $this->max_check_attempts;
    }

    /**
     * @return string
     */
    public function getOutput() {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @return mixed
     */
    public function getStateChange() {
        return $this->state_change;
    }

    /**
     * @return mixed
     */
    public function getStateTime() {
        if (!is_numeric($this->state_time)) {
            if ($this->state_time instanceof \Cake\I18n\DateTime) {
                $this->state_time = $this->state_time->timestamp;
            } else {
                $this->state_time = strtotime($this->state_time);
            }
        }
        return $this->state_time;
    }

    /**
     * @return boolean
     */
    public function isHardstate() {
        return $this->is_hardstate;
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
            $arr['state_time'] = $this->UserTime->format($this->getStateTime());
        } else {
            $arr['state_time'] = $this->getStateTime();
        }
        return $arr;
    }

}
