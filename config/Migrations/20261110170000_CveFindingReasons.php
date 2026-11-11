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
 * Class CveFindingReasons
 *
 * Stores why a finding got classified the way it did, see itnovum\openITCOCKPIT\Vuln\Policy:
 * - rule:    the rule of the policy that matched, e.g. high_priority. rule_rank alone is not unique,
 *            priority_unknown and review_required share rank 6
 * - reasons: every fact that applies to the finding as Reason bits, e.g. CISA_KEV | FIX_KNOWN
 *
 * up() and down() are used instead of change(), so a rollback removes the columns again.
 *
 * Created:
 * oitc migrations create CveFindingReasons
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class CveFindingReasons extends BaseMigration {

    /**
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('cve_host_findings')) {
            return;
        }

        $table = $this->table('cve_host_findings');
        if (!$table->hasColumn('rule')) {
            $table
                ->addColumn('rule', 'string', [
                    'default' => null,
                    'limit'   => 32,
                    'null'    => false,
                    'after'   => 'view_name',
                    'comment' => 'Rule of the policy that matched, see itnovum\openITCOCKPIT\Vuln\Policy\Rule'
                ])
                ->addColumn('reasons', 'integer', [
                    'default' => 0,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false,
                    'after'   => 'rule',
                    'comment' => 'Every fact that applies to the finding as bits, see itnovum\openITCOCKPIT\Vuln\Policy\Reason. Values never change'
                ])
                ->update();
        }
    }

    /**
     * @return void
     */
    public function down(): void {
        if (!$this->hasTable('cve_host_findings')) {
            return;
        }

        $table = $this->table('cve_host_findings');
        if ($table->hasColumn('rule')) {
            $table
                ->removeColumn('rule')
                ->removeColumn('reasons')
                ->update();
        }
    }
}
