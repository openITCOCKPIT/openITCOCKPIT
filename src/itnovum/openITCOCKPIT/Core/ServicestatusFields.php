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


class ServicestatusFields {

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
        if ($this->selectedWildcard) {
            return [];
        }
        $this->fields[] = $field;
    }

    public function getFields() {
        return $this->fields;
    }

    public function wildcard() {
        $this->selectedWildcard = true;
        return $this;
    }

    public function statusUpdateTime() {
        $this->addField('Servicestatus.status_update_time');
        return $this;
    }

    public function output() {
        $this->addField('Servicestatus.output');
        return $this;
    }

    public function longOutput() {
        $this->addField('Servicestatus.long_output');
        return $this;
    }

    public function perfdata() {
        $this->addField('Servicestatus.perfdata');
        return $this;
    }

    public function currentState() {
        $this->addField('Servicestatus.current_state');
        return $this;
    }

    public function currentCheckAttempt() {
        $this->addField('Servicestatus.current_check_attempt');
        return $this;
    }

    public function maxCheckAttempts() {
        $this->addField('Servicestatus.max_check_attempts');
        return $this;
    }

    public function lastCheck() {
        $this->addField('Servicestatus.last_check');
        return $this;
    }

    public function nextCheck() {
        $this->addField('Servicestatus.next_check');
        return $this;
    }

    public function isPassiveCheck() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Servicestatus.is_passive_check');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Servicestatus.check_type');
        }
        return $this;
    }

    public function lastStateChange() {
        $this->addField('Servicestatus.last_state_change');
        return $this;
    }

    public function lastHardStateChange() {
        $this->addField('Servicestatus.last_hard_state_change');
        return $this;
    }

    public function lastHardState() {
        $this->addField('Servicestatus.last_hard_state');
        return $this;
    }

    public function isHardstate() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Servicestatus.is_hardstate');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Servicestatus.state_type');
        }
        return $this;
    }

    public function lastNotification() {
        $this->addField('Servicestatus.last_notification');
        return $this;
    }

    public function nextNotification() {
        $this->addField('Servicestatus.next_notification');
        return $this;
    }

    public function notificationsEnabled() {
        $this->addField('Servicestatus.notifications_enabled');
        return $this;
    }

    public function problemHasBeenAcknowledged() {
        $this->addField('Servicestatus.problem_has_been_acknowledged');
        return $this;
    }

    public function acknowledgementType() {
        $this->addField('Servicestatus.acknowledgement_type');
        return $this;
    }

    public function passiveChecksEnabled() {
        $this->addField('Servicestatus.passive_checks_enabled');
        return $this;
    }

    public function activeChecksEnabled() {
        $this->addField('Servicestatus.active_checks_enabled');
        return $this;
    }

    public function eventHandlerEnabled() {
        $this->addField('Servicestatus.event_handler_enabled');
        return $this;
    }

    public function flapDetectionEnabled() {
        $this->addField('Servicestatus.flap_detection_enabled');
        return $this;
    }

    public function isFlapping() {
        $this->addField('Servicestatus.is_flapping');
        return $this;
    }

    public function latency() {
        $this->addField('Servicestatus.latency');
        return $this;
    }

    public function executionTime() {
        $this->addField('Servicestatus.execution_time');
        return $this;
    }

    public function scheduledDowntimeDepth() {
        $this->addField('Servicestatus.scheduled_downtime_depth');
        return $this;
    }

    public function processPerformanceData() {
        $this->addField('Servicestatus.process_performance_data');
        return $this;
    }

    public function obsessOverService() {
        $this->addField('Servicestatus.obsess_over_service');
        return $this;
    }

    public function normalCheckInterval() {
        $this->addField('Servicestatus.normal_check_interval');
        return $this;
    }

    public function retryCheckInterval() {
        $this->addField('Servicestatus.retry_check_interval');
        return $this;
    }

    public function checkTimeperiod() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Servicestatus.check_timeperiod');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Servicestatus.check_timeperiod_object_id');
        }
        return $this;
    }

    public function nodeName() {
        if ($this->DbBackend->isCrateDB() || $this->DbBackend->isStatusengine3()) {
            $this->addField('Servicestatus.node_name');
        }

        if ($this->DbBackend->isNdoUtils()) {
            $this->addField('Servicestatus.instance_id');
        }
        return $this;
    }

    public function lastTimeOk() {
        $this->addField('Servicestatus.last_time_ok');
        return $this;
    }

    public function lastTimeWarning() {
        $this->addField('Servicestatus.last_time_warning');
        return $this;
    }

    public function lastTimeCritical() {
        $this->addField('Servicestatus.last_time_critical');
        return $this;
    }

    public function lastTimeUnknown() {
        $this->addField('Servicestatus.last_time_unknown');
        return $this;
    }

    public function currentNotificationNumber() {
        $this->addField('Servicestatus.current_notification_number');
        return $this;
    }

    public function percentStateChange() {
        $this->addField('Servicestatus.percent_state_change');
        return $this;
    }

    public function eventHandler() {
        $this->addField('Servicestatus.event_handler');
        return $this;
    }

    public function checkCommand() {
        $this->addField('Servicestatus.check_command');
        return $this;
    }
}
