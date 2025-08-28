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

class Servicestatus {

    private $currentState = null;

    private $lastHardState = null;

    private $isFlapping;

    private $problemHasBeenAcknowledged;

    private $scheduledDowntimeDepth;

    private $lastCheck;

    private $nextCheck;

    private $activeChecksEnabled;

    private $lastHardStateChange;

    private $last_state_change;

    private $processPerformanceData;

    private $state_type;

    private $acknowledgement_type;

    private $flap_detection_enabled;

    private $notifications_enabled;

    private $current_check_attempt;

    private $output;

    private $long_output;

    private $perfdata;

    private $latency;

    private $max_check_attempts;

    private $last_time_ok;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    public function __construct($data, $UserTime = null) {
        if (isset($data['current_state'])) {
            $this->currentState = (int)$data['current_state'];
        }

        if (isset($data['is_flapping'])) {
            $this->isFlapping = (bool)$data['is_flapping'];
        }

        if (isset($data['problem_has_been_acknowledged'])) {
            $this->problemHasBeenAcknowledged = $data['problem_has_been_acknowledged'];
        }

        if (isset($data['scheduled_downtime_depth'])) {
            $this->scheduledDowntimeDepth = (int)$data['scheduled_downtime_depth'];
        }

        if (isset($data['last_check'])) {
            $this->lastCheck = $data['last_check'];
        }

        if (isset($data['next_check'])) {
            $this->nextCheck = $data['next_check'];
        }

        if (isset($data['active_checks_enabled'])) {
            $this->activeChecksEnabled = $data['active_checks_enabled'];
        }

        if (isset($data['last_hard_state_change'])) {
            $this->lastHardStateChange = $data['last_hard_state_change'];
        }

        if (isset($data['last_hard_state'])) {
            $this->lastHardState = (int)$data['last_hard_state'];
        }

        if (isset($data['last_state_change'])) {
            $this->last_state_change = $data['last_state_change'];
        }

        if (isset($data['process_performance_data'])) {
            $this->processPerformanceData = $data['process_performance_data'];
        }

        if (isset($data['state_type'])) {
            $this->state_type = $data['state_type'];
        }

        if (isset($data['is_hardstate'])) {
            $this->state_type = $data['is_hardstate'];
        }

        // 0 = ACKNOWLEDGEMENT_NONE
        // 1 = ACKNOWLEDGEMENT_NORMAL
        // 2 = ACKNOWLEDGEMENT_STICKY
        if (isset($data['acknowledgement_type'])) {
            $this->acknowledgement_type = (int)$data['acknowledgement_type'];
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

        if (isset($data['output'])) {
            $this->output = $data['output'];
        }

        if (isset($data['long_output'])) {
            $this->long_output = $data['long_output'];
        }

        if (isset($data['perfdata'])) {
            $this->perfdata = $data['perfdata'];
        }

        if (isset($data['latency'])) {
            $this->latency = $data['latency'];
        }

        if (isset($data['max_check_attempts'])) {
            $this->max_check_attempts = (int)$data['max_check_attempts'];
        }

        if (isset($data['last_time_ok'])) {
            $this->last_time_ok = $data['last_time_ok'];
        }

        $this->UserTime = $UserTime;
    }

    /**
     * @param $result
     * @return array with Servicestatus objects
     */
    public static function fromServicestatusByUuid($result) {
        if (empty($result)) {
            return [];
        }

        if (isset($result['Servicestatus'])) {
            //find()->first() result
            return [
                new self($result['Servicestatus'])
            ];
        }

        //Result is from find()->all();
        $ServicestatusObjects = [];
        foreach ($result as $record) {
            $ServicestatusObjects[] = new self($record['Servicestatus']);
        }

        return $ServicestatusObjects;
    }

    /**
     * @return bool
     */
    public function isHardState() {
        return (bool)$this->state_type;
    }

    public function getServiceFlappingIconColored($class = '') {
        $stateColors = [
            0 => 'ok',
            1 => 'warning',
            2 => 'critical',
            3 => 'unknown',
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
     * Return the CSS class for the current service status
     * <span class="<?php echo $this->ServiceStatusColor($uuid); ?>"></span>
     *
     * @param string $uuid of the object
     * @param array $servicestauts , if not given the $servicestatus array of the current view will be used (default)
     *
     * @return string CSS class of the color
     * @author Daniel Ziegler <daniel.ziegler@it-novum.com>
     * @since  3.0
     */
    public function ServiceStatusColor() {
        if ($this->currentState === null) {
            return 'text-primary';
        }

        switch ($this->currentState) {
            case 0:
                return 'ok';

            case 1:
                return 'warning';

            case 2:
                return 'critical';

            default:
                return 'unknown';
        }
    }

    /**
     * Return the status background color for a Service
     *
     * @param int $state the current status of a Service
     *
     * @return string
     */
    function ServiceStatusBackgroundColor() {
        if ($this->currentState === null) {
            return 'bg-primary';
        }

        $background_color = [
            0 => 'bg-ok',
            1 => 'bg-warning',
            2 => 'bg-critical',
            3 => 'bg-unknown',
        ];

        return $background_color[$this->currentState()];
    }

    /**
     * Return the host state as string
     * @return string (up, down, unreachable)
     */
    function ServiceStatusAsString() {
        if ($this->currentState === null) {
            return 'not in monitoring';
        }
        $human_state = [
            0 => 'ok',
            1 => 'warning',
            2 => 'critical',
            3 => 'unknown'
        ];

        return $human_state[$this->currentState];
    }

    public function currentState() {
        //Check for random exit codes like 255...
        if ($this->currentState > 3) {
            return 3;
        }
        return $this->currentState;
    }

    public function isAcknowledged() {
        return (bool)$this->problemHasBeenAcknowledged;
    }

    public function isInDowntime() {
        if ($this->scheduledDowntimeDepth > 0) {
            return true;
        }

        return false;
    }

    public function getLastHardStateChange() {
        if (!is_numeric($this->lastHardStateChange) && !is_null($this->lastHardStateChange)) {
            return strtotime($this->lastHardStateChange);
        }
        return $this->lastHardStateChange;
    }

    public function getLastHardState() {
        return $this->lastHardState;
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
        return (bool)$this->activeChecksEnabled;
    }

    public function processPerformanceData() {
        return (bool)$this->processPerformanceData;
    }

    /**
     * @return bool
     */
    public function isFlapping() {
        return (bool)$this->isFlapping;
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

    public function getCurrentCheckAttempt() {
        return $this->current_check_attempt;
    }

    /**
     * @return mixed
     */
    public function getOutput() {
        return $this->output;
    }

    /**
     * @return mixed
     */
    public function getLongOutput() {
        return $this->long_output;
    }

    public function getPerfdata() {
        return $this->perfdata;
    }

    public function getLatency() {
        return $this->latency;
    }

    public function getLastTimeOk() {
        if (!is_numeric($this->last_time_ok) && !is_null($this->last_time_ok)) {
            return strtotime($this->last_time_ok);
        }
        return $this->last_time_ok;
    }

    /**
     * @return bool
     */
    public function isInMonitoring() {
        return !is_null($this->currentState);
    }

    public function getMaxCheckAttempts() {
        return $this->max_check_attempts;
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
            $arr['last_time_ok'] = $this->UserTime->format($this->getLastTimeOk());
            $arr['lastCheck'] = $this->UserTime->format($this->getLastCheck());
            $arr['nextCheck'] = $this->UserTime->format($this->getNextCheck());
            $arr['lastHardStateChangeInWords'] = $this->UserTime->secondsInHumanShort(time() - $this->getLastHardStateChange());
            $arr['last_state_change_in_words'] = $this->UserTime->secondsInHumanShort(time() - $this->getLastStateChange());
            $arr['lastCheckInWords'] = $this->UserTime->timeAgoInWords($this->getLastCheck());
            $arr['nextCheckInWords'] = $this->UserTime->timeAgoInWords($this->getNextCheck());
        } else {
            $arr['lastHardStateChange'] = $this->getLastHardStateChange();
            $arr['last_state_change'] = $this->getLastStateChange();
            $arr['last_time_ok'] = $this->getLastTimeOk();
            $arr['lastCheck'] = $this->getLastCheck();
            $arr['nextCheck'] = $this->getNextCheck();
        }

        $arr['isHardstate'] = $this->isHardState();
        $arr['problemHasBeenAcknowledged'] = $this->isAcknowledged();
        $arr['isInMonitoring'] = $this->isInMonitoring();
        $arr['humanState'] = $this->ServiceStatusAsString();
        $arr['cssClass'] = $this->ServiceStatusBackgroundColor();
        $arr['textClass'] = $this->ServiceStatusColor();

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
            1 => 'warning',
            2 => 'critical',
            3 => 'unknown',
        ];

        if ($this->isFlapping()) {
            if ($this->currentState !== null && $this->currentState >= 0) {
                return '<span class="' . $stateColors[$this->currentState] . '"><span class="flapping_airport ' . $class . ' ' . $stateColors[$this->currentState] . '"><i class="fas fa-circle ' . $stateColors[$this->currentState] . '"></i> <i class="far fa-circle ' . $stateColors[$this->currentState] . '"></i></span></span>';
            }

            return '<span class="' . $stateColors[$this->currentState] . '"><span class="flapping_airport text-primary ' . $class . '"><i class="fas fa-circle ' . $stateColors[$this->currentState] . '"></i> <i class="far fa-circle ' . $stateColors[$this->currentState] . '"></i></span></span>';
        }

        return '';
    }

    /**
     * @param int $state
     */
    public function setCurrentState($state) {
        $this->currentState = (int)$state;
    }

    /**
     * @param string $output
     */
    public function setOutput($output) {
        $this->output = $output;
    }
}
