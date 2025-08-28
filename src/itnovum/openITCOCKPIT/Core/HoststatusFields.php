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


class HoststatusFields {

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var DbBackend
     */
    private $DbBackend;

    /**
     * @var bool
     */
    private $selectedWildcard = false;

    public function __construct(DbBackend $DbBackend) {
        $this->DbBackend = $DbBackend;
    }

    private function addField($field) {
        $this->fields[] = $field;
    }

    public function getFields() {
        if ($this->selectedWildcard) {
            return [];
        }
        return $this->fields;
    }

    public function wildcard() {
        $this->selectedWildcard = true;
        return $this;
    }

    public function acknowledgementType() {
        $this->addField('Hoststatus.acknowledgement_type');
        return $this;
    }

    public function activeChecksEnabled() {
        $this->addField('Hoststatus.active_checks_enabled');
        return $this;
    }

    public function checkCommand() {
        $this->addField('Hoststatus.check_command');
        return $this;
    }

    public function checkTimeperiod() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Hoststatus.check_timeperiod');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Hoststatus.check_timeperiod_object_id');
        }
        return $this;
    }

    public function currentCheckAttempt() {
        $this->addField('Hoststatus.current_check_attempt');
        return $this;
    }

    public function currentNotificationNumber() {
        $this->addField('Hoststatus.current_notification_number');
        return $this;
    }

    public function currentState() {
        $this->addField('Hoststatus.current_state');
        return $this;
    }

    public function eventHandler() {
        $this->addField('Hoststatus.event_handler');
        return $this;
    }

    public function eventHandlerEnabled() {
        $this->addField('Hoststatus.event_handler_enabled');
        return $this;
    }

    public function executionTime() {
        $this->addField('Hoststatus.execution_time');
        return $this;
    }

    public function flapDetectionEnabled() {
        $this->addField('Hoststatus.flap_detection_enabled');
        return $this;
    }

    public function isFlapping() {
        $this->addField('Hoststatus.is_flapping');
        return $this;
    }

    public function isHardstate() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Hoststatus.is_hardstate');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Hoststatus.state_type');
        }
        return $this;
    }

    public function isPassiveCheck() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Hoststatus.is_passive_check');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Hoststatus.check_type');
        }
        return $this;
    }

    public function lastCheck() {
        $this->addField('Hoststatus.last_check');
        return $this;
    }

    public function lastHardState() {
        $this->addField('Hoststatus.last_hard_state');
        return $this;
    }

    public function lastHardStateChange() {
        $this->addField('Hoststatus.last_hard_state_change');
        return $this;
    }

    public function lastNotification() {
        $this->addField('Hoststatus.last_notification');
        return $this;
    }

    public function lastStateChange() {
        $this->addField('Hoststatus.last_state_change');
        return $this;
    }

    public function lastTimeDown() {
        $this->addField('Hoststatus.last_time_down');
        return $this;
    }

    public function lastTimeUnreachable() {
        $this->addField('Hoststatus.last_time_unreachable');
        return $this;
    }

    public function lastTimeUp() {
        $this->addField('Hoststatus.last_time_up');
        return $this;
    }

    public function latency() {
        $this->addField('Hoststatus.latency');
        return $this;
    }

    public function longOutput() {
        $this->addField('Hoststatus.long_output');
        return $this;
    }

    public function maxCheckAttempts() {
        $this->addField('Hoststatus.max_check_attempts');
        return $this;
    }

    public function nextCheck() {
        $this->addField('Hoststatus.next_check');
        return $this;
    }

    public function nextNotification() {
        $this->addField('Hoststatus.next_notification');
        return $this;
    }

    public function node_name() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Hoststatus.node_name');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Hoststatus.instance_id');
        }
        return $this;
    }

    public function normalCheckInterval() {
        $this->addField('Hoststatus.normal_check_interval');
        return $this;
    }

    public function notificationsEnabled() {
        $this->addField('Hoststatus.notifications_enabled');
        return $this;
    }

    public function obsessOverHost() {
        $this->addField('Hoststatus.obsess_over_host');
        return $this;
    }

    public function output() {
        $this->addField('Hoststatus.output');
        return $this;
    }

    public function passiveChecksEnabled() {
        $this->addField('Hoststatus.passive_checks_enabled');
        return $this;
    }

    public function percentStateChange() {
        $this->addField('Hoststatus.percent_state_change');
        return $this;
    }

    public function perfdata() {
        $this->addField('Hoststatus.perfdata');
        return $this;
    }

    public function problemHasBeenAcknowledged() {
        $this->addField('Hoststatus.problem_has_been_acknowledged');
        return $this;
    }

    public function processPerformanceData() {
        $this->addField('Hoststatus.process_performance_data');
        return $this;
    }

    public function retryCheckInterval() {
        $this->addField('Hoststatus.retry_check_interval');
        return $this;
    }

    public function scheduledDowntimeDepth() {
        $this->addField('Hoststatus.scheduled_downtime_depth');
        return $this;
    }

    public function statusUpdateTime() {
        $this->addField('Hoststatus.status_update_time');
        return $this;
    }
}
