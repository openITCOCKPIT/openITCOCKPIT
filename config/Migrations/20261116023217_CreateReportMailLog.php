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

/**
 * Central log of all report emails (Instant Reports, Autoreports, SLA Reports, ...).
 *
 * One row per sent email. Recipients are stored as a JSON snapshot
 * ([{"user_id": 12, "email": "jdoe@example.org"}, ...]) so the log still shows
 * who received the email at the time of sending, even if report recipients,
 * users or email addresses change later on.
 *
 * report_id has no foreign key on purpose, as the report tables may belong to
 * modules that are not installed.
 */
class CreateReportMailLog extends BaseMigration {

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
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void {

        if (!$this->hasTable('report_mail_log')) {
            $this->table('report_mail_log')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 20,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('report_type', 'string', [
                    'comment' => 'instantreport, autoreport, sla, ...',
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                ])
                ->addColumn('report_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('report_name', 'string', [
                    'comment' => 'Snapshot of the report name at the time of sending',
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'comment' => 'Snapshot of the report container_id, used for permission checks',
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('recipients', 'text', [
                    'comment' => 'JSON snapshot: [{"user_id": int|null, "name": string|null, "email": string}, ...]',
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('subject', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('has_been_sent', 'integer', [
                    'default' => 0,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('message', 'string', [
                    'comment' => 'Error message if the email could not be sent, otherwise info by which mail system it was accepted',
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => true,
                ])
                ->addColumn('report_start', 'datetime', [
                    'comment' => 'Start of the reporting period',
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('report_end', 'datetime', [
                    'comment' => 'End of the reporting period',
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('send_time', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'report_type',
                        'report_id',
                        'send_time',
                    ]
                )
                ->addIndex(
                    [
                        'has_been_sent',
                        'send_time',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->addIndex(
                    [
                        'send_time',
                    ]
                )
                ->create();
        }
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void {
        if ($this->hasTable('report_mail_log')) {
            $this->table('report_mail_log')->drop()->save();
        }
    }
}
