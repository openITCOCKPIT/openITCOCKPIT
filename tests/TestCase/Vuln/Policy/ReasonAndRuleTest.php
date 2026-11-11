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
use itnovum\openITCOCKPIT\Vuln\Policy\FindingView;
use itnovum\openITCOCKPIT\Vuln\Policy\Reason;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ReasonAndRuleTest extends TestCase {

    /**
     * The values are stored in the database, changing one would change the meaning of stored findings
     */
    public function testValuesOfReasonsNeverChange(): void {
        $this->assertSame(1, Reason::CISA_KEV);
        $this->assertSame(1 << 11, Reason::PRODUCT_MATCH);
        $this->assertSame(1 << 12, Reason::DECISION_ACCEPTED);
        $this->assertSame(1 << 19, Reason::DECISION_POLICY_CHANGED);
    }

    public function testEveryReasonIsASingleBitOfItsOwn(): void {
        // Public reasons only: NAMES is the private list of names, REVIEW_CAUSES a combination of reasons
        $constants = [];
        foreach ((new ReflectionClass(Reason::class))->getReflectionConstants() as $constant) {
            if ($constant->isPublic() && $constant->getName() !== 'REVIEW_CAUSES') {
                $constants[$constant->getName()] = $constant->getValue();
            }
        }

        $seen = 0;
        foreach ($constants as $name => $value) {
            $this->assertSame(1, substr_count(decbin($value), '1'), $name . ' must be a single bit');
            $this->assertSame(0, $seen & $value, $name . ' uses a bit of another reason');
            $seen |= $value;
        }

        // Every reason has a name for logs
        $this->assertCount(count($constants), Reason::toNames($seen));
    }

    public function testHasChecksAllGivenBits(): void {
        $reasons = Reason::CISA_KEV | Reason::FIX_KNOWN;

        $this->assertTrue(Reason::has($reasons, Reason::CISA_KEV));
        $this->assertTrue(Reason::has($reasons, Reason::CISA_KEV | Reason::FIX_KNOWN));
        $this->assertFalse(Reason::has($reasons, Reason::CISA_KEV | Reason::VULNCHECK_KEV));
    }

    public function testRankColorAndViewOfEveryRule(): void {
        $expected = [
            // Rule, rank, color, view as in the rule table of vuls/REGELWERK.md
            [Rule::CisaKev, 1, Color::Red, FindingView::Action],
            [Rule::WeaponizedExploit, 2, Color::Red, FindingView::Action],
            [Rule::HighPriority, 3, Color::Red, FindingView::Action],
            [Rule::VulnCheckKev, 4, Color::Yellow, FindingView::Action],
            [Rule::InactiveKernel, 5, Color::Yellow, FindingView::Action],
            [Rule::PriorityUnknown, 6, Color::Yellow, FindingView::Action],
            [Rule::ReviewRequired, 6, Color::Yellow, FindingView::Action],
            [Rule::RemedyAvailable, 7, Color::Yellow, FindingView::Action],
            [Rule::NoFix, 8, Color::Gray, FindingView::Unfixed],
            [Rule::Accepted, 9, Color::Gray, FindingView::Accepted],
            [Rule::NotAffected, 10, Color::Gray, FindingView::NotAffected],
        ];

        $this->assertCount(count(Rule::cases()), $expected);
        foreach ($expected as [$rule, $rank, $color, $view]) {
            $this->assertSame($rank, $rule->rank(), $rule->value);
            $this->assertSame($color, $rule->color(), $rule->value);
            $this->assertSame($view, $rule->view(), $rule->value);
        }
    }
}
