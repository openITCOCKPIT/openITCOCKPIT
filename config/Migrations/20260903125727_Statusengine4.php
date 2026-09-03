<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

declare(strict_types=1);

use Migrations\BaseMigration;

class Statusengine4 extends BaseMigration {

    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void {

        if (!$this->hasTable('statusengine_dbversion')) {
            $this->table('statusengine_dbversion')
                ->addColumn('id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('dbversion', 'string', [
                    'default' => '3.0.0',
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->create();
        }

        if (!$this->hasTable('statusengine_host_acknowledgements')) {
            $this->table('statusengine_host_acknowledgements')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('entry_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['hostname', 'entry_time', 'entry_time_usec'])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('author_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('comment_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('acknowledgement_type', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_sticky', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('persistent_comment', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('notify_contacts', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('end_time', 'biginteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addIndex(
                    $this->index('hostname')
                        ->setName('hostname')
                )
                ->addIndex(
                    $this->index('entry_time')
                        ->setName('entry_time')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_host_downtimehistory')) {
            $this->table('statusengine_host_downtimehistory')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('internal_downtime_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['hostname', 'internal_downtime_id', 'scheduled_start_time', 'node_name'])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('entry_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('author_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('comment_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('triggered_by_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_fixed', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('duration', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('was_started', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('actual_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('actual_end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('was_cancelled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'entry_time',
                        'entry_time_usec',
                        'scheduled_start_time',
                        'scheduled_end_time',
                        'was_cancelled',
                    ])
                        ->setName('reports')
                )
                ->addIndex(
                    $this->index([
                        'hostname',
                        'scheduled_start_time',
                        'scheduled_end_time',
                        'was_cancelled',
                    ])
                        ->setName('list')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_host_notifications')) {
            $this->table('statusengine_host_notifications')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('start_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['hostname', 'start_time', 'start_time_usec'])
                ->addColumn('contact_name', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('command_name', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('command_args', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('reason_type', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('ack_author', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('ack_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index('hostname')
                        ->setName('hostname')
                )
                ->addIndex(
                    $this->index('start_time')
                        ->setName('start_time')
                )
                ->create();
        }

        // The Table statusengine_host_notifications_log will be created in 20240917054853_StatusengineNotificationsLog.php

        if (!$this->hasTable('statusengine_host_scheduleddowntimes')) {
            $this->table('statusengine_host_scheduleddowntimes')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('internal_downtime_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['hostname', 'internal_downtime_id', 'scheduled_start_time', 'node_name'])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('author_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('comment_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('triggered_by_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_fixed', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('duration', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('was_started', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('actual_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->create();
        }

        if (!$this->hasTable('statusengine_host_statehistory')) {
            $this->table('statusengine_host_statehistory')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('state_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('state_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['hostname', 'state_time', 'state_time_usec'])
                ->addColumn('state_change', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('is_hardstate', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('current_check_attempt', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('max_check_attempts', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('last_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('last_hard_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('long_output', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'state_time',
                    ])
                        ->setName('hostname_time')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_hostchecks')) {
            $this->table('statusengine_hostchecks')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('start_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['hostname', 'start_time', 'start_time_usec'])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('is_hardstate', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('timeout', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('early_timeout', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('latency', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('execution_time', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('perfdata', 'string', [
                    'default' => null,
                    'limit'   => 2048,
                    'null'    => true,
                ])
                ->addColumn('command', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('current_check_attempt', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('max_check_attempts', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('long_output', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'start_time',
                    ])
                        ->setName('hostname')
                )
                ->addIndex(
                    $this->index([
                        'start_time',
                        'end_time',
                    ])
                        ->setName('times')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_hoststatus')) {
            $this->table('statusengine_hoststatus')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['hostname'])
                ->addColumn('status_update_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('long_output', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addColumn('perfdata', 'string', [
                    'default' => null,
                    'limit'   => 2048,
                    'null'    => true,
                ])
                ->addColumn('current_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('current_check_attempt', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('max_check_attempts', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('last_check', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('next_check', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('is_passive_check', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('last_state_change', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_hard_state_change', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_hard_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_hardstate', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('last_notification', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('next_notification', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('notifications_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('problem_has_been_acknowledged', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('acknowledgement_type', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('passive_checks_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('active_checks_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('event_handler_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('is_flapping', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('latency', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('execution_time', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('scheduled_downtime_depth', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('process_performance_data', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('obsess_over_host', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('normal_check_interval', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('retry_check_interval', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('check_timeperiod', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('last_time_up', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_time_down', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_time_unreachable', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('current_notification_number', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('percent_state_change', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('event_handler', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('check_command', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'current_state',
                        'node_name',
                    ])
                        ->setName('current_state_node')
                )
                ->addIndex(
                    $this->index([
                        'problem_has_been_acknowledged',
                        'scheduled_downtime_depth',
                        'current_state',
                    ])
                        ->setName('issues')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_logentries')) {
            $this->table('statusengine_logentries')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => null,
                    'null'          => false,
                    'signed'        => false,
                ])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addPrimaryKey(['id', 'entry_time'])
                ->addColumn('logentry_type', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('logentry_data', 'string', [
                    'default' => null,
                    'limit'   => 2048,
                    'null'    => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'entry_time',
                        'node_name',
                    ])
                        ->setName('logentries_se')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_nodes')) {
            $this->table('statusengine_nodes')
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['node_name'])
                ->addColumn('node_version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('node_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->create();
        }

        if (!$this->hasTable('statusengine_perfdata')) {
            $this->table('statusengine_perfdata')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('label', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('timestamp', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('timestamp_unix', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('value', 'float', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('unit', 'string', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'service_description',
                        'label',
                        'timestamp_unix',
                    ])
                        ->setName('metric')
                )
                ->addIndex(
                    $this->index('timestamp_unix')
                        ->setName('timestamp_unix')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_service_acknowledgements')) {
            $this->table('statusengine_service_acknowledgements')
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('entry_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['service_description', 'entry_time', 'entry_time_usec'])
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('author_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('comment_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('acknowledgement_type', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_sticky', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('persistent_comment', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('notify_contacts', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('end_time', 'biginteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'service_description',
                    ])
                        ->setName('servicename')
                )
                ->addIndex(
                    $this->index('entry_time')
                        ->setName('entry_time')
                )
                ->addIndex(
                    $this->index([
                        'service_description',
                        'entry_time',
                    ])
                        ->setName('servicedesc_time')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_service_downtimehistory')) {
            $this->table('statusengine_service_downtimehistory')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('internal_downtime_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['hostname', 'service_description', 'internal_downtime_id', 'scheduled_start_time', 'node_name'])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('entry_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('author_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('comment_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('triggered_by_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_fixed', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('duration', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('was_started', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('actual_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('actual_end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('was_cancelled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'service_description',
                        'entry_time',
                        'entry_time_usec',
                        'scheduled_start_time',
                        'scheduled_end_time',
                        'was_cancelled',
                    ])
                        ->setName('reports')
                )
                ->addIndex(
                    $this->index([
                        'service_description',
                        'scheduled_start_time',
                        'scheduled_end_time',
                        'was_cancelled',
                    ])
                        ->setName('report')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_service_notifications')) {
            $this->table('statusengine_service_notifications')
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('start_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['service_description', 'start_time', 'start_time_usec'])
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('contact_name', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('command_name', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('command_args', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('reason_type', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('ack_author', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('ack_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index('start_time')
                        ->setName('start_time')
                )
                ->addIndex(
                    $this->index([
                        'hostname',
                        'service_description',
                    ])
                        ->setName('servicename')
                )
                ->create();
        }

        // The Table statusengine_service_notifications_log will be created in 20240917054853_StatusengineNotificationsLog.php

        if (!$this->hasTable('statusengine_service_scheduleddowntimes')) {
            $this->table('statusengine_service_scheduleddowntimes')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('internal_downtime_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['hostname', 'service_description', 'internal_downtime_id', 'scheduled_start_time', 'node_name'])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('author_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('comment_data', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('triggered_by_id', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_fixed', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('duration', 'integer', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('scheduled_end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('was_started', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('actual_start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->create();
        }

        if (!$this->hasTable('statusengine_service_statehistory')) {
            $this->table('statusengine_service_statehistory')
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('state_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('state_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['service_description', 'state_time', 'state_time_usec'])
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('state_change', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('is_hardstate', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('current_check_attempt', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('max_check_attempts', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('last_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('last_hard_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('long_output', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'service_description',
                        'state_time',
                    ])
                        ->setName('host_servicename_time')
                )
                ->addIndex(
                    $this->index([
                        'service_description',
                        'state_time',
                    ])
                        ->setName('servicename_time')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_servicechecks')) {
            $this->table('statusengine_servicechecks')
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('start_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('start_time_usec', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addPrimaryKey(['service_description', 'start_time', 'start_time_usec'])
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('is_hardstate', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('end_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('timeout', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('early_timeout', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('latency', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('execution_time', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('perfdata', 'string', [
                    'default' => null,
                    'limit'   => 2048,
                    'null'    => true,
                ])
                ->addColumn('command', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('current_check_attempt', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('max_check_attempts', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('long_output', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'hostname',
                        'service_description',
                        'start_time',
                    ])
                        ->setName('servicename')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_servicestatus')) {
            $this->table('statusengine_servicestatus')
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('service_description', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addPrimaryKey(['hostname', 'service_description'])
                ->addColumn('status_update_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('output', 'string', [
                    'default' => null,
                    'limit'   => 1024,
                    'null'    => true,
                ])
                ->addColumn('long_output', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addColumn('perfdata', 'string', [
                    'default' => null,
                    'limit'   => 2048,
                    'null'    => true,
                ])
                ->addColumn('current_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('current_check_attempt', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('max_check_attempts', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('last_check', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('next_check', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('is_passive_check', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('last_state_change', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_hard_state_change', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_hard_state', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('is_hardstate', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('last_notification', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('next_notification', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('notifications_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('problem_has_been_acknowledged', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('acknowledgement_type', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('passive_checks_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('active_checks_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('event_handler_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('flap_detection_enabled', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('is_flapping', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('latency', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('execution_time', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('scheduled_downtime_depth', 'smallinteger', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('process_performance_data', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('obsess_over_service', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('normal_check_interval', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('retry_check_interval', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('check_timeperiod', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('last_time_ok', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_time_warning', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_time_critical', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('last_time_unknown', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('current_notification_number', 'integer', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                ])
                ->addColumn('percent_state_change', 'float', [
                    'default' => '0',
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => true,
                ])
                ->addColumn('event_handler', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('check_command', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index('service_description')
                        ->setName('service_description')
                )
                ->addIndex(
                    $this->index([
                        'current_state',
                        'node_name',
                    ])
                        ->setName('current_state_node')
                )
                ->addIndex(
                    $this->index([
                        'problem_has_been_acknowledged',
                        'scheduled_downtime_depth',
                        'current_state',
                    ])
                        ->setName('issues')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_tasks')) {
            $this->table('statusengine_tasks')
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('node_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('entry_time', 'biginteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->addColumn('type', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('payload', 'string', [
                    'default' => null,
                    'limit'   => 8192,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index('uuid')
                        ->setName('uuid')
                )
                ->addIndex(
                    $this->index('node_name')
                        ->setName('node_name')
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_users')) {
            $this->table('statusengine_users')
                ->addColumn('username', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('password', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addIndex(
                    $this->index([
                        'username',
                        'password',
                    ])
                        ->setName('username')
                )
                ->create();
        }

    }
}
