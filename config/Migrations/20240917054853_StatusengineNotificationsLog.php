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

declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class StatusengineNotificationsLog
 * This creates tables for Statusengine 3. The table structure can be modified with new migrations later on.
 * The partitions will be created and managed by the CleanupCronjob.
 *
 * Created:
 * oitc migrations create StatusengineNotificationsLog
 *
 * Usage:
 * openitcockpit-update
 */
class StatusengineNotificationsLog extends \Migrations\BaseMigration {
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
    public $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {

        if (!$this->hasTable('statusengine_host_notifications_log')) {
            $this->table('statusengine_host_notifications_log')
                ->addColumn('hostname', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('start_time', 'biginteger', [
                    'limit'  => 20,
                    'null'   => false,
                    'signed' => true,
                ])
                ->addColumn('start_time_usec', 'integer', [
                    'limit'   => 10,
                    'null'    => false,
                    'signed'  => false,
                    'default' => 0,
                ])
                ->addPrimaryKey(['hostname', 'start_time', 'start_time_usec'])
                ->addColumn('end_time', 'biginteger', [
                    'limit'  => 20,
                    'null'   => false,
                    'signed' => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'null'    => true,
                    'default' => 0,
                    'signed'  => false,
                ])
                ->addColumn('reason_type', 'smallinteger', [
                    'null'    => true,
                    'default' => 0,
                    'signed'  => false,
                ])
                ->addColumn('is_escalated', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('contacts_notified_count', 'smallinteger', [
                    'null'    => false,
                    'default' => 0,
                    'signed'  => false,
                ])
                ->addColumn('output', 'string', [
                    'null'    => true,
                    'default' => null,
                    'limit'   => 1024,
                ])
                ->addColumn('ack_author', 'string', [
                    'null'    => true,
                    'default' => null,
                    'limit'   => 1024,
                ])
                ->addColumn('ack_data', 'string', [
                    'null'    => true,
                    'default' => null,
                    'limit'   => 1024,
                ])
                ->addIndex(
                    [
                        'hostname',
                    ], ['name' => 'hostname']
                )
                ->addIndex(
                    [
                        'start_time',
                        'end_time',
                        'reason_type',
                        'state',
                    ], ['name' => 'filter']
                )
                ->create();
        }

        if (!$this->hasTable('statusengine_service_notifications_log')) {
            $this->table('statusengine_service_notifications_log')
                ->addColumn('hostname', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('service_description', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('start_time', 'biginteger', [
                    'limit'  => 20,
                    'null'   => false,
                    'signed' => true,
                ])
                ->addColumn('start_time_usec', 'integer', [
                    'limit'   => 10,
                    'null'    => false,
                    'signed'  => false,
                    'default' => 0,
                ])
                ->addPrimaryKey(['hostname', 'service_description', 'start_time', 'start_time_usec'])
                ->addColumn('end_time', 'biginteger', [
                    'limit'  => 20,
                    'null'   => false,
                    'signed' => true,
                ])
                ->addColumn('state', 'smallinteger', [
                    'null'    => true,
                    'default' => 0,
                    'signed'  => false,
                ])
                ->addColumn('reason_type', 'smallinteger', [
                    'null'    => true,
                    'default' => 0,
                    'signed'  => false,
                ])
                ->addColumn('is_escalated', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('contacts_notified_count', 'smallinteger', [
                    'null'    => false,
                    'default' => 0,
                    'signed'  => false,
                ])
                ->addColumn('output', 'string', [
                    'null'    => true,
                    'default' => null,
                    'limit'   => 1024,
                ])
                ->addColumn('ack_author', 'string', [
                    'null'    => true,
                    'default' => null,
                    'limit'   => 1024,
                ])
                ->addColumn('ack_data', 'string', [
                    'null'    => true,
                    'default' => null,
                    'limit'   => 1024,
                ])
                ->addIndex(
                    [
                        'hostname',
                        'service_description',
                    ], ['name' => 'servicename']
                )
                ->addIndex(
                    [
                        'start_time',
                        'end_time',
                        'reason_type',
                        'state',
                    ], ['name' => 'filter']
                )
                ->create();
        }


    }
}
