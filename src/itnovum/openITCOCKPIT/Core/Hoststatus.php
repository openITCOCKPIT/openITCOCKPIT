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

namespace itnovum\openITCOCKPIT\Core;

use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Core\Views\UserTime;

class Hoststatus {

    /**
     * @var null|int
     */
    private $currentState = null;

    /**
     * @var bool
     */
    private $isFlapping;

    /**
     * @var bool
     */
    private $problemHasBeenAcknowledged;

    /**
     * @var string
     */
    private $scheduledDowntimeDepth;

    /**
     * @var string
     */
    private $lastCheck;

    /**
     * @var string
     */
    private $nextCheck;

    /**
     * @var bool
     */
    private $activeChecksEnabled;

    /**
     * @var int
     */
    private $lastHardState;

    /**
     * @var string
     */
    private $lastHardStateChange;

    /**
     * @var string
     */
    private $last_state_change;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $long_output;

    /**
     * @var int
     */
    private $acknowledgement_type;

    /**
     * @var int
     */
    private $state_type;

    /**
     * @var bool
     */
    private $flap_detection_enabled;

    /**
     * @var bool
     */
    private $notifications_enabled;

    /**
     * @var int
     */
    private $current_check_attempt;

    /**
     * @var int
     */
    private $max_check_attempts;

    /**
     * @var float
     */
    private $latency;

    /**
     * @var string
     */
    private $last_time_up;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    public function __construct($data, $UserTime = null) {
        if (isset($data['current_state'])) {
            $this->currentState = (int)$data['current_state'];
            if ($data['current_state'] === null) {
                $this->currentState = null;
            }
        }

        if (isset($data['is_flapping'])) {
            $this->isFlapping = (bool)$data['is_flapping'];
        }

        if (isset($data['problem_has_been_acknowledged'])) {
            $this->problemHasBeenAcknowledged = $data['problem_has_been_acknowledged'];
        }

        if (isset($data['scheduled_downtime_depth'])) {
            $this->scheduledDowntimeDepth = $data['scheduled_downtime_depth'];
        }

        if (isset($data['last_check'])) {
            $this->lastCheck = $data['last_check'];
        }

        if (isset($data['next_check'])) {
            $this->nextCheck = $data['next_check'];
        }

        if (isset($data['active_checks_enabled'])) {
            $this->activeChecksEnabled = (bool)$data['active_checks_enabled'];
        }

        if (isset($data['last_hard_state'])) {
            $this->lastHardState = (int)$data['last_hard_state'];
        }

        if (isset($data['last_hard_state_change'])) {
            $this->lastHardStateChange = $data['last_hard_state_change'];
        }

        if (isset($data['last_state_change'])) {
            $this->last_state_change = $data['last_state_change'];
        }

        // 0 = ACKNOWLEDGEMENT_NONE
        // 1 = ACKNOWLEDGEMENT_NORMAL
        // 2 = ACKNOWLEDGEMENT_STICKY
        if (isset($data['acknowledgement_type'])) {
            $this->acknowledgement_type = (int)$data['acknowledgement_type'];
        }

        if (isset($data['output'])) {
            $this->output = $data['output'];
        }

        if (isset($data['state_type'])) {
            $this->state_type = $data['state_type'];
        }

        if (isset($data['is_hardstate'])) {
            $this->state_type = $data['is_hardstate'];
        }

        if (isset($data['flap_detection_enabled'])) {
            $this->flap_detection_enabled = (bool)$data['flap_detection_enabled'];
        }

        if (isset($data['notifications_enabled'])) {
            $this->notifications_enabled = (bool)$data['notifications_enabled'];
        }

        if (isset($data['current_check_attempt'])) {
            $this->current_check_attempt = $data['current_check_attempt'];
        }

        if (isset($data['max_check_attempts'])) {
            $this->max_check_attempts = $data['max_check_attempts'];
        }

        if (isset($data['long_output'])) {
            $this->long_output = $data['long_output'];
        }

        if (isset($data['latency'])) {
            $this->latency = (float)$data['latency'];
        }

        if (isset($data['last_time_up'])) {
            $this->last_time_up = $data['last_time_up'];
        }

        $this->UserTime = $UserTime;
    }

    public function getHostFlappingIconColored($class = '') {
        $stateColors = [
            0 => 'ok',
            1 => 'critical',
            2 => 'unreachable',
        ];
        if ($this->isFlapping() === true) {
            if ($this->currentState !== null) {
                return '<span class="flapping_airport ' . $class . ' ' . $stateColors[$this->currentState] . '"><i class="fa-solid fa-circle ' . $stateColors[$this->currentState] . '"></i> <i class="fa-regular fa-circle ' . $stateColors[$this->currentState] . '"></i></span>';
            }

            return '<span class="flapping_airport text-primary ' . $class . '"><i class="fa-solid fa-circle ' . $stateColors[$this->currentState] . '"></i> <i class="fa-regular fa-circle ' . $stateColors[$this->currentState] . '"></i></span>';
        }
        return '';
    }

    /**
     * Return the CSS class for the current host status
     * <span class="<?php echo $this->HostStatusColor($uuid); ?>"></span>
     *
     * @param string $uuid of the object
     * @param array $hoststatus , if not given the $hoststatus array of the current view will be used (default)
     *
     * @return string CSS class of the color
     * @author Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since  3.0
     */
    public function HostStatusColor() {
        if ($this->currentState === null) {
            return 'text-primary';
        }

        switch ($this->currentState) {
            case 0:
                return 'up';

            case 1:
                return 'down';

            default:
                return 'unreachable';
        }
    }

    /**
     * Return the status background color for a Host
     *
     * @param int $state the current status of a Host
     *
     * @return string
     */
    function HostStatusBackgroundColor() {
        if ($this->currentState === null) {
            return 'bg-primary';
        }

        $background_color = [
            0 => 'bg-up',
            1 => 'bg-down',
            2 => 'bg-unreachable',
        ];

        return $background_color[$this->currentState()];
    }

    /**
     * Return the host state as string
     * @return  string (up, down, unreachable)
     */
    function HostStatusAsString() {
        if ($this->currentState === null) {
            return 'not in monitoring';
        }
        $human_state = [
            0 => 'up',
            1 => 'down',
            2 => 'unreachable',
        ];

        return $human_state[$this->currentState];
    }

    public function currentState() {
        //Check for random exit codes like 255...
        if ($this->currentState > 2) {
            return 2;
        }
        return $this->currentState;
    }

    public function isAcknowledged() {
        return (bool)$this->problemHasBeenAcknowledged;
    }

    /**
     * @return bool
     */
    public function isInDowntime() {
        if ($this->scheduledDowntimeDepth > 0) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHardState() {
        return (bool)$this->state_type;
    }

    public function getLastHardState() {
        return $this->lastHardState;
    }

    public function getLastHardStateChange() {
        if (!is_numeric($this->lastHardStateChange) && !is_null($this->lastHardStateChange)) {
            return strtotime($this->lastHardStateChange);
        }
        return $this->lastHardStateChange;
    }

    public function getLastStateChange() {
        if (!is_numeric($this->last_state_change) && !is_null($this->last_state_change)) {
            return strtotime($this->last_state_change);
        }
        return $this->last_state_change;
    }

    public function getLastCheck() {
        if (!is_numeric($this->lastCheck) && !is_null($this->lastCheck)) {
            return strtotime($this->lastCheck);
        }
        return $this->lastCheck;
    }

    public function getNextCheck() {
        if (!is_numeric($this->nextCheck) && !is_null($this->nextCheck)) {
            return strtotime($this->nextCheck);
        }
        return $this->nextCheck;
    }

    public function isActiveChecksEnabled() {
        return $this->activeChecksEnabled;
    }

    /**
     * @return bool
     */
    public function isFlapping() {
        return (bool)$this->isFlapping;
    }

    /**
     * In the statusengine_hoststatus and statusengine_servicestatus tables, this field describes if an acknowledgement
     * is normal or sticky.
     *
     * 0 = ACKNOWLEDGEMENT_NONE
     * 1 = ACKNOWLEDGEMENT_NORMAL
     * 2 = ACKNOWLEDGEMENT_STICKY
     *
     * This is a different behavior than the statusengine_host_acknowledgements and statusengine_service_acknowledgements
     * tables have.
     * This is already a mess in the Naemon Core itself :(
     *
     * @return int
     */
    public function getAcknowledgementType() {
        return $this->acknowledgement_type;
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
    public function getLongOutput() {
        return $this->long_output;
    }

    /**
     * @return int
     */
    public function getStateType() {
        return $this->state_type;
    }

    /**
     * @return bool
     */
    public function isFlapDetectionEnabled() {
        return (bool)$this->flap_detection_enabled;
    }

    /**
     * @return bool
     */
    public function isNotificationsEnabled() {
        return (bool)$this->notifications_enabled;
    }

    public function getCurrentCheckAttempts() {
        return $this->current_check_attempt;
    }

    public function getMaxCheckAttempts() {
        return $this->max_check_attempts;
    }


    public function getLatency() {
        return $this->latency;
    }

    public function getLastTimeUp() {
        if (!is_numeric($this->last_time_up) && !is_null($this->last_time_up)) {
            return strtotime($this->last_time_up);
        }
        return $this->last_time_up;
    }

    /**
     * @return bool
     */
    public function isInMonitoring() {
        return !is_null($this->currentState);
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
            $arr['lastHardStateChange'] = $this->UserTime->format($this->getLastHardStateChange());
            $arr['last_state_change'] = $this->UserTime->format($this->getLastStateChange());
            $arr['last_time_up'] = $this->UserTime->format($this->getLastTimeUp());
            $arr['lastCheck'] = $this->UserTime->format($this->getLastCheck());
            $arr['nextCheck'] = $this->UserTime->format($this->getNextCheck());
            $arr['lastHardStateChangeInWords'] = $this->UserTime->secondsInHumanShort(time() - $this->getLastHardStateChange());
            $arr['last_state_change_in_words'] = $this->UserTime->secondsInHumanShort(time() - $this->getLastStateChange());
            $arr['lastCheckInWords'] = $this->UserTime->timeAgoInWords($this->getLastCheck());
            $arr['nextCheckInWords'] = $this->UserTime->timeAgoInWords($this->getNextCheck());
        } else {
            $arr['lastHardStateChange'] = $this->getLastHardStateChange();
            $arr['last_state_change'] = $this->getLastStateChange();
            $arr['last_time_up'] = $this->getLastTimeUp();
            $arr['lastCheck'] = $this->getLastCheck();
            $arr['nextCheck'] = $this->getNextCheck();
        }

        $arr['isHardstate'] = $this->isHardState();
        $arr['problemHasBeenAcknowledged'] = $this->isAcknowledged();
        $arr['isInMonitoring'] = $this->isInMonitoring();
        $arr['humanState'] = $this->HostStatusAsString();
        $arr['cssClass'] = $this->HostStatusBackgroundColor();
        $arr['textClass'] = $this->HostStatusColor();

        $BBCodeParser = new BBCodeParser();
        $arr['outputHtml'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($this->getOutput(), true));
        return $arr;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArrayForBrowser() {
        $arr = $this->toArray();
        $arr['lastHardStateChange'] = $this->UserTime->secondsInHumanShort(time() - $this->getLastHardStateChange());
        $arr['lastHardStateChangeUser'] = $this->UserTime->format($this->getLastHardStateChange());

        $arr['last_state_change'] = $this->UserTime->secondsInHumanShort(time() - $this->getLastStateChange());
        $arr['last_state_change_user'] = $this->UserTime->format($this->getLastStateChange());

        $arr['lastCheck'] = $this->UserTime->timeAgoInWords($this->getLastCheck());
        $arr['lastCheckUser'] = $this->UserTime->format($this->getLastCheck());

        $arr['nextCheck'] = $this->UserTime->timeAgoInWords($this->getNextCheck());
        $arr['nextCheckUser'] = $this->UserTime->format($this->getNextCheck());
        return $arr;
    }

    /**
     * @param string $class
     * @return string
     */
    public function getFlappingIconColored($class = '') {

        $stateColors = [
            0 => 'ok',
            1 => 'critical',
            2 => '',
        ];

        if ($this->isFlapping) {
            if ($this->currentState !== null && $this->currentState >= 0) {
                return '<span class="flapping_airport ' . $class . ' ' . $stateColors[$this->currentState] . '"><i class="fas fa-circle ' . $stateColors[$this->currentState] . '"></i> <i class="far fa-circle ' . $stateColors[$this->currentState] . '"></i></span>';
            }

            return '<span class="flapping_airport text-primary ' . $class . '"><i class="fas fa-circle ' . $stateColors[$this->currentState] . '"></i> <i class="far fa-circle ' . $stateColors[$this->currentState] . '"></i></span>';
        }

        return '';
    }
}
