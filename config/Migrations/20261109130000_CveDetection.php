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
 * Class CveDetection
 *
 * Tables to store the CVEs that Vuls reports for the Linux inventory:
 *
 * packages_linux_hosts --(cve_package_state_id)--> cve_package_states <-- cve_package_state_cves --> cves
 *
 * These tables store the Vuls results, shared for all hosts so we do not have to query Vuls for every host with the same package.
 *
 * - cve_package_states:     Every distinct package state of all hosts, stored only once
 * - cve_package_state_cves: Which CVE affects which package state, including the fix
 * - cves:                   Master data of each CVE
 *
 * up() and down() are used instead of change(), so a rollback removes the tables again.
 *
 * Created:
 * oitc migrations create CveDetection
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class CveDetection extends BaseMigration {

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

        if (!$this->hasTable('cves')) {
            $this->table('cves')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('cve_id', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                    'comment' => 'e.g. CVE-2026-1234'
                ])
                ->addColumn('summary', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('published', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('cvss_score', 'decimal', [
                    'default'   => null,
                    'precision' => 3,
                    'scale'     => 1,
                    'null'      => true,
                    'comment'   => 'CVSS v3 or v4 base score, NULL if no source provides a score'
                ])
                ->addColumn('cvss_vector', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('cvss_source', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => true,
                    'comment' => 'Source of the CVSS score, e.g. nvd or vulncheck'
                ])
                ->addColumn('kev_cisa', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Listed in the CISA Known Exploited Vulnerabilities catalog'
                ])
                ->addColumn('kev_cisa_added', 'date', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                ])
                ->addColumn('kev_vulncheck', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Listed in the VulnCheck KEV catalog'
                ])
                ->addColumn('metasploit_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false
                ])
                ->addColumn('verified_exploit_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Exploits from Nuclei or Exploit-DB'
                ])
                ->addColumn('poc_exploit_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Proof of concepts from GitHub or Trickest'
                ])
                ->addColumn('source_link', 'string', [
                    'default' => null,
                    'limit'   => 512,
                    'null'    => true,
                ])
                ->addColumn('feed_digest', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Digest of the Vuls database the master data was taken from'
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
                        'cve_id',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'kev_cisa',
                        'cvss_score',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_package_states')) {
            $this->table('cve_package_states')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('state_hash', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => false,
                    'comment' => 'sha256 of all fields that change the result of Vuls (operating system, package and running kernel for kernel packages)'
                ])
                ->addColumn('os_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Name of the operating system as reported by the agent, e.g. ubuntu or almalinux'
                ])
                ->addColumn('os_version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Version of the operating system as reported by the agent, e.g. 24.04'
                ])
                ->addColumn('is_kernel', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Vuls only evaluates kernel packages of the running kernel'
                ])
                ->addColumn('kernel_release', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Running kernel release, only set for kernel packages. NULL for kernel packages means the running kernel is unknown'
                ])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Name of the binary package'
                ])
                ->addColumn('version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Installed version of the binary package'
                ])
                ->addColumn('architecture', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('source_package', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('source_version', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('modularity_label', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('feed_digest', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Digest of the Vuls database of the last successful check. NULL if the package state has never been checked'
                ])
                ->addColumn('checked', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'Time of the last successful check against Vuls'
                ])
                ->addColumn('cve_count', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'Number of rows in cve_package_state_cves, to mark packages without a join'
                ])
                ->addColumn('max_severity', 'tinyinteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                    'comment' => 'Highest severity of all CVEs: 0 none, 1 low, 2 medium, 3 high, 4 critical. NULL if unknown or no CVE'
                ])
                ->addColumn('has_kev', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'At least one CVE is listed in the CISA KEV catalog'
                ])
                ->addColumn('error_message', 'text', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'comment' => 'Error of the last failed check. The last successful result stays untouched'
                ])
                ->addColumn('last_seen', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'Last time at least one host had this package state installed. Used to remove unused package states'
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
                        'state_hash',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'os_name',
                        'os_version',
                        'is_kernel',
                    ]
                )
                ->addIndex(
                    [
                        'last_seen',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('cve_package_state_cves')) {
            $this->table('cve_package_state_cves')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('cve_package_state_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false
                ])
                ->addColumn('cve_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'comment' => 'cves.id'
                ])
                ->addColumn('fixed_in', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Version that fixes the CVE, NULL if no fix is known'
                ])
                ->addColumn('fix_state', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'State reported by the vendor, e.g. "Will not fix" or "Affected"'
                ])
                ->addColumn('not_fixed_yet', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                    'comment' => 'The vendor has not released a fix yet'
                ])
                ->addColumn('severity', 'tinyinteger', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => true,
                    'signed'  => false,
                    'comment' => '0 none, 1 low, 2 medium, 3 high, 4 critical. NULL if unknown. Depends on the distribution, so it is stored per package state'
                ])
                ->addColumn('severity_source', 'string', [
                    'default' => null,
                    'limit'   => 64,
                    'null'    => true,
                    'comment' => 'Source of the severity, e.g. ubuntu, debian, redhat or nvd'
                ])
                ->addIndex(
                    [
                        'cve_package_state_id',
                        'cve_id',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'cve_id',
                        'cve_package_state_id',
                    ]
                )
                ->create();
        }

        if ($this->hasTable('packages_linux_hosts')) {
            $table = $this->table('packages_linux_hosts');
            if (!$table->hasColumn('cve_package_state_id')) {
                $table
                    ->addColumn('cve_package_state_id', 'biginteger', [
                        'default' => null,
                        'limit'   => 11,
                        'null'    => true,
                        'signed'  => false,
                        'after'   => 'source_rpm',
                        'comment' => 'cve_package_states.id, set by the CVE check. NULL if the package could not be checked'
                    ])
                    ->addIndex(
                        [
                            'cve_package_state_id',
                        ]
                    )
                    ->update();
            }
        }
    }

    /**
     * @return void
     */
    public function down(): void {
        if ($this->hasTable('packages_linux_hosts')) {
            $table = $this->table('packages_linux_hosts');
            if ($table->hasColumn('cve_package_state_id')) {
                $table
                    ->removeIndex(['cve_package_state_id'])
                    ->removeColumn('cve_package_state_id')
                    ->update();
            }
        }

        foreach (['cve_package_state_cves', 'cve_package_states', 'cves'] as $tableName) {
            if ($this->hasTable($tableName)) {
                $this->table($tableName)->drop()->save();
            }
        }
    }
}
