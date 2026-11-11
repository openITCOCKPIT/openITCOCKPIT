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

use DateTimeImmutable;
use itnovum\openITCOCKPIT\Vuln\Policy\Classification;
use itnovum\openITCOCKPIT\Vuln\Policy\Color;
use itnovum\openITCOCKPIT\Vuln\Policy\Decision;
use itnovum\openITCOCKPIT\Vuln\Policy\DecisionRules;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingClassifier;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingFacts;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingView;
use itnovum\openITCOCKPIT\Vuln\Policy\PolicyVersion;
use itnovum\openITCOCKPIT\Vuln\Policy\Reason;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;
use PHPUnit\Framework\TestCase;

class DecisionRulesTest extends TestCase {

    use FindingFactory;

    private const NOW = '2026-11-06 12:00:00';

    /**
     * A red finding: high priority, the vendor has not fixed it yet
     */
    private function redFinding(): FindingFacts {
        return $this->finding(['severity' => 3, 'fix_state' => 'needed', 'not_fixed_yet' => true]);
    }

    /**
     * A decision that was made for redFinding()
     */
    private function decision(array $overrides = []): Decision {
        return Decision::fromRow($overrides + [
            'state'          => Decision::ACCEPTED,
            'review_after'   => '2026-12-01',
            'severity'       => 3,
            'fixed_in'       => null,
            'fix_state'      => 'needed',
            'not_fixed_yet'  => true,
            'policy_version' => PolicyVersion::CURRENT,
        ]);
    }

    private function apply(FindingFacts $finding, ?Decision $decision): Classification {
        $classification = (new FindingClassifier())->classify($finding);
        return (new DecisionRules())->apply($classification, $finding, $decision, new DateTimeImmutable(self::NOW));
    }

    public function testWithoutDecisionNothingChanges(): void {
        $finding = $this->redFinding();
        $classification = (new FindingClassifier())->classify($finding);

        $this->assertSame($classification, (new DecisionRules())->apply($classification, $finding, null, new DateTimeImmutable(self::NOW)));
    }

    public function testAccepted(): void {
        $classification = $this->apply($this->redFinding(), $this->decision());

        $this->assertSame(Rule::Accepted, $classification->rule);
        $this->assertSame(Color::Gray, $classification->color());
        $this->assertTrue($classification->has(Reason::DECISION_ACCEPTED));
        $this->assertFalse($classification->requiresReview());
    }

    public function testNotAffected(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['state' => Decision::NOT_AFFECTED]));

        $this->assertSame(Rule::NotAffected, $classification->rule);
        $this->assertSame(10, $classification->rank());
    }

    public function testExpiredDecisionKeepsRedFindingsRed(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['review_after' => '2026-11-06']));

        $this->assertSame(Rule::HighPriority, $classification->rule);
        $this->assertSame(Color::Red, $classification->color());
        $this->assertTrue($classification->has(Reason::DECISION_ACCEPTED | Reason::DECISION_EXPIRED));
        $this->assertTrue($classification->requiresReview());
    }

    public function testChangedFixEndsTheDecision(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['fixed_in' => 'x']));

        $this->assertSame(FindingView::Action, $classification->view());
        $this->assertTrue($classification->has(Reason::DECISION_FIX_CHANGED));
    }

    public function testChangedSeverityEndsTheDecision(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['severity' => 2]));

        $this->assertSame(FindingView::Action, $classification->view());
        $this->assertTrue($classification->has(Reason::DECISION_SEVERITY_CHANGED));
    }

    public function testChangedVendorStateEndsTheDecision(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['not_fixed_yet' => false]));

        $this->assertTrue($classification->has(Reason::DECISION_VENDOR_STATE_CHANGED));
        $this->assertTrue($classification->requiresReview());
    }

    public function testNewPolicyVersionEndsTheDecision(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['policy_version' => 'old']));

        $this->assertSame(FindingView::Action, $classification->view());
        $this->assertTrue($classification->has(Reason::DECISION_POLICY_CHANGED));
    }

    public function testExpiredDecisionOfAFindingWithoutFixBecomesAYellowTask(): void {
        $finding = $this->finding();
        $decision = $this->decision(['review_after' => '2026-01-01', 'severity' => 2, 'fix_state' => null, 'not_fixed_yet' => false]);

        $classification = $this->apply($finding, $decision);

        $this->assertSame(Rule::ReviewRequired, $classification->rule);
        $this->assertSame(Color::Yellow, $classification->color());
        $this->assertSame(6, $classification->rank());
        $this->assertTrue($classification->requiresReview());
    }

    public function testDecisionEndsOnItsReviewDate(): void {
        $decision = $this->decision(['review_after' => '2026-11-06']);

        $this->assertTrue($decision->isExpired(new DateTimeImmutable('2026-11-06 00:00:01')));
        $this->assertFalse($decision->isExpired(new DateTimeImmutable('2026-11-05 23:59:59')));
    }

    public function testInProgressKeepsTheClassification(): void {
        $finding = $this->redFinding();
        $original = (new FindingClassifier())->classify($finding);

        $classification = $this->apply($finding, $this->decision(['state' => Decision::IN_PROGRESS]));

        $this->assertSame($original->rule, $classification->rule);
        $this->assertSame(Color::Red, $classification->color());
        $this->assertTrue($classification->isInProgress());
        $this->assertFalse($classification->requiresReview());
    }

    public function testInProgressSurvivesAChangedFinding(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['state' => Decision::IN_PROGRESS, 'fixed_in' => 'x', 'severity' => 2]));

        $this->assertTrue($classification->isInProgress());
        $this->assertFalse($classification->requiresReview());
    }

    public function testInProgressSurvivesANewPolicyVersion(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['state' => Decision::IN_PROGRESS, 'policy_version' => 'old']));

        $this->assertTrue($classification->isInProgress());
    }

    public function testInProgressOverdueKeepsRedAndRequiresReview(): void {
        $classification = $this->apply($this->redFinding(), $this->decision(['state' => Decision::IN_PROGRESS, 'review_after' => '2026-11-06']));

        $this->assertFalse($classification->isInProgress());
        $this->assertTrue($classification->requiresReview());
        $this->assertSame(Color::Red, $classification->color());
        $this->assertTrue($classification->has(Reason::DECISION_IN_PROGRESS | Reason::DECISION_EXPIRED));
    }

    public function testInProgressOverdueWithoutFixBecomesAYellowTask(): void {
        $classification = $this->apply($this->finding(), $this->decision(['state' => Decision::IN_PROGRESS, 'review_after' => '2026-01-01']));

        $this->assertSame(Rule::ReviewRequired, $classification->rule);
    }

    public function testInProgressWithoutFixStaysInItsView(): void {
        $classification = $this->apply($this->finding(), $this->decision(['state' => Decision::IN_PROGRESS]));

        $this->assertSame(FindingView::Unfixed, $classification->view());
        $this->assertTrue($classification->isInProgress());
    }

    public function testDecisionAcceptsDateObjects(): void {
        $decision = $this->decision(['review_after' => new DateTimeImmutable('2026-12-01')]);

        $this->assertSame('2026-12-01', $decision->reviewAfter->format('Y-m-d'));
    }
}
