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
 * Class CveEvaluation
 *
 * The tables of CveDetection store what Vuls reports, shared by all hosts. These tables store the evaluation
 * of each host: which findings a host has, how urgent they are, since when they exist and what a human decided.
 *
 * - cve_host_findings:  One finding per host, CVE and component, including classification and life cycle
 * - cve_decisions:      Accepted risk, not affected or confirmed and in progress, bound to exactly one finding
 * - cve_host_summaries: Result of the last evaluation of each host, used for the host list
 * - cve_host_coverage:  What could not be evaluated and why, one row per item
 * - cve_daily_stats:    Values per host and day for the trend
 * - cve_feeds:          Vuls databases that have been used, to detect an outdated feed
 *
 * up() and down() are used instead of change(), so a rollback removes the tables again.
 *
 * Created:
 * oitc migrations create CveEvaluation
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class CveEvaluation extends BaseMigration {

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
     * @return void
     */
    public function up(): void {

        if (!$this->hasTable('cve_host_findings')) {
            $this->table('cve_host_findings')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('finding_hash', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                    'comment' => 'sha256 of host, CVE and component. A unique index over the fields itself would be too long'
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('cve_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'cves.id'
                ])
                ->addColumn('component_type', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'package, os or app'
                ])
                ->addColumn('component_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Source package for Linux packages, e.g. openssl'
                ])
                ->addColumn('component_version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Every version is its own finding, so an update resolves the finding of the old version'
                ])
                ->addColumn('is_kernel', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('is_running', 'boolean', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'Kernel packages only: false if the kernel is installed but not running. NULL if unknown or no kernel'
                ])
                ->addColumn('fixed_in', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('fix_state', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('not_fixed_yet', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('update_available', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'The package manager of the host offers an update for the component'
                ])
                ->addColumn('update_version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('severity', 'tinyinteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                    'comment' => '0 none, 1 low, 2 medium, 3 high, 4 critical. NULL if unknown'
                ])
                ->addColumn('severity_source', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => true,
                ])
                ->addColumn('view_name', 'string', [
                    'default' => 'action',
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'action, unfixed, accepted or not_affected'
                ])
                ->addColumn('color', 'string', [
                    'default' => 'yellow',
                    'limit'   => 8,
                    'null'    => false,
                    'comment' => 'red, yellow or gray'
                ])
                ->addColumn('rule_rank', 'tinyinteger', [
                    'default' => 6,
                    'limit'   => null,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Rule of the policy that classified the finding, 1 is the most urgent. Identifies the text in the UI'
                ])
                ->addColumn('review_required', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'The decision of this finding has expired or the finding has changed since the decision'
                ])
                ->addColumn('policy_version', 'string', [
                    'default' => null,
                    'limit'   => 32,
                    'null'    => false,
                    'comment' => 'Version of the policy that classified the finding, e.g. vendor-priority-v3'
                ])
                ->addColumn('first_seen', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('last_seen', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('resolved_at', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'Set by a complete evaluation that did not report the finding anymore. NULL while the finding is open'
                ])
                ->addColumn('reopened_at', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('reopen_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'finding_hash',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'host_id',
                        'resolved_at',
                        'rule_rank',
                    ]
                )
                ->addIndex(
                    [
                        'cve_id',
                        'resolved_at',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_decisions')) {
            $this->table('cve_decisions')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('cve_host_finding_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'The decision only applies to this finding: host, CVE, component and version'
                ])
                ->addColumn('state', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'accepted, not_affected or in_progress'
                ])
                ->addColumn('owner', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Responsible person or team'
                ])
                ->addColumn('reason', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('review_after', 'date', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Review date, for in_progress the due date. The decision ends on this day'
                ])
                ->addColumn('evidence_url', 'string', [
                    'default' => null,
                    'limit'   => 512,
                    'null'    => true,
                    'comment' => 'Link to the analysis, required for not_affected'
                ])
                ->addColumn('severity', 'tinyinteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                    'comment' => 'Severity of the finding at the time of the decision'
                ])
                ->addColumn('fixed_in', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Fixed version of the finding at the time of the decision'
                ])
                ->addColumn('fix_state', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Vendor state of the finding at the time of the decision'
                ])
                ->addColumn('not_fixed_yet', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('policy_version', 'string', [
                    'default' => null,
                    'limit'   => 32,
                    'null'    => false,
                    'comment' => 'A new version of the policy ends accepted and not_affected decisions'
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                    'comment' => 'User who made the decision'
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'cve_host_finding_id',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'state',
                        'review_after',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_host_summaries')) {
            $this->table('cve_host_summaries')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('platform', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'linux, windows or macos'
                ])
                ->addColumn('status', 'string', [
                    'default' => null,
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'complete, partial, not_evaluable or failed'
                ])
                ->addColumn('color', 'string', [
                    'default' => 'gray',
                    'limit'   => 8,
                    'null'    => false,
                    'comment' => 'red, yellow, green or gray'
                ])
                ->addColumn('fingerprint', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => true,
                    'comment' => 'sha256 of inventory, feed and policy version of the last evaluation. Unchanged hosts are skipped'
                ])
                ->addColumn('feed_digest', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('inventory_last_update', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('evaluated', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'Last successful evaluation. The counters belong to this evaluation'
                ])
                ->addColumn('failed', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'Last failed evaluation. The counters of the last successful evaluation stay visible'
                ])
                ->addColumn('error_message', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('critical_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('high_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('medium_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('low_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('unknown_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('open_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Open CVEs, accepted CVEs included. All counters count CVEs, not packages'
                ])
                ->addColumn('red_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('action_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('kev_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('exploit_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('fixable_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('accepted_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('not_affected_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('in_progress_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('installations', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Installed packages or apps of the host'
                ])
                ->addColumn('evaluated_installations', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Installed packages or apps that could be checked'
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'red_count',
                        'action_count',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_host_coverage')) {
            $this->table('cve_host_coverage')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('type', 'string', [
                    'default' => null,
                    'limit'   => 32,
                    'null'    => false,
                    'comment' => 'reason, missing_metadata, inactive_kernel, unmapped_app or other_platform_cve'
                ])
                ->addColumn('value', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'e.g. the reason code, the package name or the CVE id'
                ])
                ->addIndex(
                    [
                        'host_id',
                        'type',
                    ]
                )
                ->addIndex(
                    [
                        'type',
                        'value',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_daily_stats')) {
            $this->table('cve_daily_stats')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('stat_date', 'date', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('open_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('action_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('red_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('kev_count', 'integer', ['default' => 0, 'limit' => 11, 'null' => false, 'signed' => false])
                ->addColumn('new_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Findings that appeared on this day, reopened findings included'
                ])
                ->addColumn('resolved_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Findings that got resolved on this day'
                ])
                ->addIndex(
                    [
                        'stat_date',
                        'host_id',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'host_id',
                        'stat_date',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_feeds')) {
            $this->table('cve_feeds')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('digest', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Digest of the Vuls database'
                ])
                ->addColumn('feed_date', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'When the Vuls database was built. Hosts turn gray if the current feed is too old'
                ])
                ->addColumn('is_current', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'First use of this Vuls database'
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'digest',
                    ],
                    ['unique' => true]
                )
                ->create();
        }
    }

    /**
     * @return void
     */
    public function down(): void {
        foreach (['cve_feeds', 'cve_daily_stats', 'cve_host_coverage', 'cve_host_summaries', 'cve_decisions', 'cve_host_findings'] as $tableName) {
            if ($this->hasTable($tableName)) {
                $this->table($tableName)->drop()->save();
            }
        }
    }
}
