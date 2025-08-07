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

class Hostcheck {

    /**
     * @var string
     */
    private $command;

    /**
     * @var int
     */
    private $current_check_attempt;

    /**
     * @var bool
     */
    private $early_timeout;

    /**
     * @var int|string
     */
    private $end_time;

    /**
     * @var float
     */
    private $execution_time;

    /**
     * @var bool
     */
    private $is_hardstate;

    /**
     * @var float
     */
    private $latency;

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
     * @var string
     */
    private $perfdata;

    /**
     * @var int|string
     */
    private $start_time;

    /**
     * @var int
     */
    private $state;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    /**
     * StatehistoryHost constructor.
     * @param array $data
     */
    public function __construct($data, $UserTime = null) {
        if (isset($data['command'])) {
            $this->command = $data['command'];
        }

        if (isset($data['early_timeout'])) {
            $this->early_timeout = (bool)$data['early_timeout'];
        }

        if (isset($data['execution_time'])) {
            $this->execution_time = (float)$data['execution_time'];
        }

        if (isset($data['long_output'])) {
            $this->long_output = $data['long_output'];
        }

        if (isset($data['current_check_attempt'])) {
            $this->current_check_attempt = (int)$data['current_check_attempt'];
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

        if (isset($data['end_time'])) {
            $this->end_time = $data['end_time'];
        }

        if (isset($data['start_time'])) {
            $this->start_time = $data['start_time'];
        }

        if (isset($data['state_type'])) {
            $this->is_hardstate = (bool)$data['state_type'];
        }

        if (isset($data['is_hardstate'])) {
            $this->is_hardstate = (bool)$data['is_hardstate'];
        }

        if (isset($data['latency'])) {
            $this->latency = (float)$data['latency'];
        }

        if (isset($data['perfdata'])) {
            $this->perfdata = $data['perfdata'];
        }

        if (isset($data['timeout'])) {
            $this->timeout = (int)$data['timeout'];
        }

        $this->UserTime = $UserTime;
    }

    /**
     * @return boolean
     */
    public function isHardstate() {
        return $this->is_hardstate;
    }

    /**
     * @return string
     */
    public function getCommand() {
        return $this->command;
    }

    /**
     * @return int
     */
    public function getCurrentCheckAttempt() {
        return $this->current_check_attempt;
    }

    /**
     * @return boolean
     */
    public function isEarlyTimeout() {
        return $this->early_timeout;
    }

    /**
     * @return int|string
     */
    public function getEndTime() {
        if(!is_numeric($this->end_time)){
            if($this->end_time instanceof \Cake\I18n\DateTime){
                $this->end_time = $this->end_time->timestamp;
            }else{
                $this->end_time = strtotime($this->end_time);
            }
        }

        return $this->end_time;
    }

    /**
     * @return float
     */
    public function getExecutionTime() {
        return $this->execution_time;
    }

    /**
     * @return float
     */
    public function getLatency() {
        return $this->latency;
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
     * @return string
     */
    public function getPerfdata() {
        return $this->perfdata;
    }

    /**
     * @return int|string
     */
    public function getStartTime() {
        if (!is_numeric($this->start_time)) {
            if ($this->start_time instanceof \Cake\I18n\DateTime) {
                $this->start_time = $this->start_time->timestamp;
            } else {
                $this->start_time = strtotime($this->start_time);
            }
        }

        return $this->start_time;
    }

    /**
     * @return int
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @return int
     */
    public function getTimeout() {
        return $this->timeout;
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
