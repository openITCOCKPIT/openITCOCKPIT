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

namespace App\Test\TestCase\Vuln\Policy;

use itnovum\openITCOCKPIT\Vuln\Policy\Color;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingClassifier;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingView;
use itnovum\openITCOCKPIT\Vuln\Policy\Reason;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;
use PHPUnit\Framework\TestCase;

/**
 * Every row of the rule table in vuls/REGELWERK.md
 */
class FindingClassifierTest extends TestCase {

    use FindingFactory;

    private function rule(array $overrides = []): Rule {
        return (new FindingClassifier())->classify($this->finding($overrides))->rule;
    }

    public function testRule1CisaKev(): void {
        $this->assertSame(Rule::CisaKev, $this->rule(['kev_cisa' => true]));
    }

    public function testRule1CisaKevOnRunningKernelIsRed(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['kev_cisa' => true, 'is_kernel' => true, 'is_running' => true]));
        $this->assertSame(Rule::CisaKev, $classification->rule);
        $this->assertSame(Color::Red, $classification->color());
    }

    public function testUnknownRunningKernelCountsAsActive(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['kev_cisa' => true, 'is_kernel' => true, 'is_running' => null]));
        $this->assertSame(Rule::CisaKev, $classification->rule);
        $this->assertTrue($classification->has(Reason::RUNNING_KERNEL_UNKNOWN));
    }

    public function testRule2Metasploit(): void {
        $this->assertSame(Rule::WeaponizedExploit, $this->rule(['severity' => 3, 'metasploit_count' => 1]));
    }

    public function testRule2ExploitDbOrNuclei(): void {
        $this->assertSame(Rule::WeaponizedExploit, $this->rule(['severity' => 3, 'verified_exploit_count' => 1]));
    }

    public function testRule2RequiresHighPriority(): void {
        $this->assertSame(Rule::NoFix, $this->rule(['severity' => 2, 'metasploit_count' => 1]));
    }

    public function testRule3HighPriority(): void {
        $this->assertSame(Rule::HighPriority, $this->rule(['severity' => 3]));
    }

    public function testRule3RunningKernel(): void {
        $this->assertSame(Rule::HighPriority, $this->rule(['severity' => 4, 'is_kernel' => true, 'is_running' => true]));
    }

    public function testRule4VulnCheckKevIsYellow(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['kev_vulncheck' => true]));
        $this->assertSame(Rule::VulnCheckKev, $classification->rule);
        $this->assertSame(Color::Yellow, $classification->color());
    }

    public function testCisaKevWinsOverVulnCheckKev(): void {
        $this->assertSame(Rule::CisaKev, $this->rule(['kev_vulncheck' => true, 'kev_cisa' => true]));
    }

    public function testRule5InactiveKernelWithHighPriority(): void {
        $this->assertSame(Rule::InactiveKernel, $this->rule(['severity' => 3, 'is_kernel' => true, 'is_running' => false]));
    }

    public function testRule5InactiveKernelWithKev(): void {
        $this->assertSame(Rule::InactiveKernel, $this->rule(['kev_cisa' => true, 'is_kernel' => true, 'is_running' => false]));
    }

    public function testRule6PriorityUnknown(): void {
        $this->assertSame(Rule::PriorityUnknown, $this->rule(['severity' => null]));
    }

    public function testUnknownPriorityStaysATaskDespiteFix(): void {
        $this->assertSame(Rule::PriorityUnknown, $this->rule(['severity' => null, 'fixed_in' => '1.2']));
    }

    public function testRule7Fix(): void {
        $this->assertSame(Rule::RemedyAvailable, $this->rule(['fixed_in' => '1.2']));
    }

    public function testRule7Update(): void {
        $this->assertSame(Rule::RemedyAvailable, $this->rule(['update_available' => true]));
    }

    public function testNotFixedYetIsNoFix(): void {
        $this->assertSame(Rule::NoFix, $this->rule(['fixed_in' => '1.2', 'not_fixed_yet' => true]));
    }

    public function testUpdateIsNoFixIfTheVendorSaysNotFixed(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['update_available' => true, 'not_fixed_yet' => true]));
        $this->assertSame(Rule::NoFix, $classification->rule);
        // The reasons explain why the update did not count
        $this->assertTrue($classification->has(Reason::UPDATE_AVAILABLE | Reason::VENDOR_NOT_FIXED));
    }

    public function testRule8NoFix(): void {
        $classification = (new FindingClassifier())->classify($this->finding());
        $this->assertSame(Rule::NoFix, $classification->rule);
        $this->assertSame(FindingView::Unfixed, $classification->view());
        $this->assertSame(8, $classification->rank());
    }

    public function testProductMatchHasNoFixData(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['component_type' => 'app']));
        $this->assertSame(Rule::NoFix, $classification->rule);
        $this->assertTrue($classification->has(Reason::PRODUCT_MATCH));
    }

    public function testProofOfConceptDoesNotEscalate(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['poc_exploit_count' => 3]));
        $this->assertSame(Rule::NoFix, $classification->rule);
        $this->assertTrue($classification->has(Reason::PROOF_OF_CONCEPT));
    }

    public function testClassificationCarriesAllReasonsOfTheFinding(): void {
        $classification = (new FindingClassifier())->classify($this->finding(['severity' => 3, 'fixed_in' => '1.2', 'poc_exploit_count' => 1]));

        $this->assertSame(Rule::HighPriority, $classification->rule);
        $this->assertSame(Reason::HIGH_PRIORITY | Reason::FIX_KNOWN | Reason::PROOF_OF_CONCEPT, $classification->reasons);
        $this->assertSame(['PROOF_OF_CONCEPT', 'HIGH_PRIORITY', 'FIX_KNOWN'], Reason::toNames($classification->reasons));
    }
}
