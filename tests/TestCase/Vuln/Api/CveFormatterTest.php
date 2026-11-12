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

namespace App\Test\TestCase\Vuln\Api;

use DateTimeImmutable;
use itnovum\openITCOCKPIT\Vuln\Api\CveFormatter;
use itnovum\openITCOCKPIT\Vuln\Policy\Reason;
use PHPUnit\Framework\TestCase;

class CveFormatterTest extends TestCase {

    private function summary(array $values = []): array {
        return $values + [
                'id'                    => '3',
                'host_id'               => '539',
                'platform'              => 'linux',
                'status'                => 'complete',
                'color'                 => 'red',
                'fingerprint'           => 'abc',
                'inventory_last_update' => new DateTimeImmutable('2026-11-11 08:00:00'),
                'red_count'             => '12',
                'open_count'            => '964',
                'host'                  => ['id' => 539, 'name' => 'alma'],
            ];
    }

    private function finding(array $values = []): array {
        return $values + [
                'id'               => '7',
                'host_id'          => '539',
                'cve_id'           => '3155',
                'component_type'   => 'package',
                'component_name'   => 'brotli',
                'component_version' => '1.0.9-9.el9_7',
                'is_kernel'        => 0,
                'is_running'       => null,
                'fixed_in'         => '1.0.9-10.el9_8.1',
                'fix_state'        => null,
                'not_fixed_yet'    => 0,
                'update_available' => 1,
                'update_version'   => '1.0.9-10.el9_8.1',
                'severity'         => '3',
                'rule'             => 'cisa_kev',
                'reasons'          => (string)(Reason::CISA_KEV | Reason::FIX_KNOWN),
                'rule_rank'        => '1',
                'review_required'  => 0,
                'cve'              => ['id' => '3155', 'cve_id' => 'CVE-2026-111497', 'kev_cisa' => 1, 'kev_vulncheck' => 0, 'cvss_score' => '9.2', 'metasploit_count' => '0', 'verified_exploit_count' => '1', 'poc_exploit_count' => '83'],
                'host'             => ['id' => 539, 'name' => 'alma'],
                'cve_decision'     => null,
            ];
    }

    public function testHostGetsTheStateAtTheTimeOfTheRequest(): void {
        $formatter = new CveFormatter();
        $now = new DateTimeImmutable('2026-11-11 12:00:00');

        $host = $formatter->host($this->summary(), new DateTimeImmutable('2026-11-10'), $now);
        $this->assertSame(['status' => 'evaluated', 'color' => 'red', 'sort_order' => $host['state']['sort_order'], 'inventory_age_days' => 0], $host['state']);
        $this->assertSame(12, $host['red_count']);
        $this->assertSame(539, $host['host_id']);
        $this->assertArrayNotHasKey('fingerprint', $host, 'Internal value');

        $stale = $formatter->host($this->summary(), new DateTimeImmutable('2026-10-01'), $now);
        $this->assertSame('stale', $stale['state']['status'], 'An old Vuls database makes the host stale');
        $this->assertSame('gray', $stale['state']['color']);
        $this->assertSame('red', $stale['color'], 'The stored color stays visible');
    }

    public function testFindingGetsCodesAndRecommendation(): void {
        $finding = (new CveFormatter())->finding($this->finding());

        $this->assertSame(['cisa_kev', 'fix_known'], $finding['reason_codes']);
        $this->assertSame(['action' => 'install_update', 'version' => '1.0.9-10.el9_8.1'], $finding['recommendation']);
        $this->assertSame(3, $finding['severity']);
        $this->assertFalse($finding['is_kernel']);
        $this->assertNull($finding['is_running']);
        $this->assertTrue($finding['cve']['kev_cisa']);
        $this->assertSame(9.2, $finding['cve']['cvss_score']);
        $this->assertNull($finding['decision']);
        $this->assertArrayNotHasKey('cve_decision', $finding);
    }

    public function testDecisionWithUser(): void {
        $finding = (new CveFormatter())->finding($this->finding([
            'rule'         => 'accepted',
            'cve_decision' => ['id' => '5', 'cve_host_finding_id' => '7', 'state' => 'accepted', 'user_id' => '1', 'not_fixed_yet' => 0, 'severity' => '3', 'user' => ['id' => '1', 'firstname' => 'Ada', 'lastname' => 'Lovelace']],
        ]));

        $this->assertSame(['id' => 1, 'name' => 'Ada Lovelace'], $finding['decision']['user']);
        $this->assertSame(['action' => 'review_decision', 'version' => null], $finding['recommendation']);
    }

    public function testUnknownRuleHasNoRecommendation(): void {
        $this->assertNull((new CveFormatter())->finding($this->finding(['rule' => 'rule_of_an_old_policy']))['recommendation']);
    }

    public function testAggregatesAreTyped(): void {
        $row = (new CveFormatter())->aggregate(['cve_count' => '3', 'is_kernel' => '1.0', 'highest_severity' => null, 'cvss_score' => '7.5', 'update_version' => '6.8.0-149.149']);

        $this->assertSame(['cve_count' => 3, 'is_kernel' => true, 'highest_severity' => null, 'cvss_score' => 7.5, 'update_version' => '6.8.0-149.149'], $row);
    }

    public function testRecommendationAcrossHostsOnlyNamesAVersionThatFixes(): void {
        $formatter = new CveFormatter();

        $update = $formatter->recommendationRow(['component_type' => 'package', 'is_kernel' => '0', 'usable_update' => '1', 'update_version' => '2.0']);
        $this->assertSame(['action' => 'update_package', 'version' => '2.0'], $update['recommendation']);

        $notFixed = $formatter->recommendationRow(['component_type' => 'package', 'is_kernel' => '0', 'usable_update' => '0', 'update_version' => '2.0']);
        $this->assertSame(['action' => 'check_mitigation', 'version' => null], $notFixed['recommendation']);
    }

    public function testHostComponent(): void {
        $component = (new CveFormatter())->hostComponent([
            'component_type'   => 'package',
            'is_kernel'        => '1',
            'is_running'       => '0',
            'usable_update'    => '1',
            'update_version'   => '6.8.0-150',
            'cve_count'        => '40',
            'highest_severity' => '4',
        ], 'action');

        $this->assertSame(['action' => 'remove_or_update_kernel', 'version' => null], $component['recommendation']);
        $this->assertFalse($component['is_running']);
        $this->assertSame(40, $component['cve_count']);
    }
}
