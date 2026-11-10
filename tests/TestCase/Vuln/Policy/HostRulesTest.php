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
use itnovum\openITCOCKPIT\Vuln\Policy\CveRollup;
use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;
use itnovum\openITCOCKPIT\Vuln\Policy\HostRules;
use itnovum\openITCOCKPIT\Vuln\Policy\HostStatus;
use itnovum\openITCOCKPIT\Vuln\Policy\HostTotals;
use itnovum\openITCOCKPIT\Vuln\Policy\Reason;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;
use PHPUnit\Framework\TestCase;

class HostRulesTest extends TestCase {

    use FindingFactory;

    private function totals(array $values = []): HostTotals {
        $totals = new HostTotals();
        foreach ($values as $field => $value) {
            $totals->$field = $value;
        }
        return $totals;
    }

    public function testColor(): void {
        $rules = new HostRules();

        $this->assertSame(Color::Red, $rules->color(EvaluationStatus::Complete, $this->totals(['red' => 1, 'action' => 1])));
        $this->assertSame(Color::Yellow, $rules->color(EvaluationStatus::Partial, $this->totals(['action' => 1])));
        $this->assertSame(Color::Green, $rules->color(EvaluationStatus::Complete, $this->totals()));
        $this->assertSame(Color::Gray, $rules->color(EvaluationStatus::NotEvaluable, $this->totals()));
        $this->assertSame(Color::Gray, $rules->color(EvaluationStatus::Failed, $this->totals(['action' => 2])));
    }

    public function testFailedEvaluationDoesNotHideRedFindings(): void {
        $this->assertSame(Color::Red, (new HostRules())->color(EvaluationStatus::Failed, $this->totals(['red' => 1])));
    }

    public function testState(): void {
        $rules = new HostRules();
        $now = new DateTimeImmutable('2026-11-06 12:00:00');
        $fresh = new DateTimeImmutable('2026-11-06 11:00:00');
        $feed = new DateTimeImmutable('2026-11-04');

        $state = $rules->state(EvaluationStatus::Complete, Color::Red, $fresh, $feed, $now);
        $this->assertSame(HostStatus::Evaluated, $state->status);
        $this->assertSame(Color::Red, $state->color);

        $this->assertSame(HostStatus::Partial, $rules->state(EvaluationStatus::Partial, Color::Yellow, $fresh, $feed, $now)->status);
        $this->assertSame(HostStatus::NotEvaluated, $rules->state(EvaluationStatus::NotEvaluable, Color::Gray, null, $feed, $now)->status);
    }

    public function testOldInventoryIsStaleAndGray(): void {
        $state = (new HostRules())->state(EvaluationStatus::Complete, Color::Red, new DateTimeImmutable('2026-11-03 11:00:00'), new DateTimeImmutable('2026-11-04'), new DateTimeImmutable('2026-11-06 12:00:00'));

        $this->assertSame(HostStatus::Stale, $state->status);
        $this->assertSame(Color::Gray, $state->color);
        $this->assertSame(3, $state->inventoryAgeDays);
    }

    public function testFeedOlderThanSevenDaysIsStale(): void {
        $now = new DateTimeImmutable('2026-11-06 12:00:00');
        $fresh = new DateTimeImmutable('2026-11-06 11:00:00');
        $oldFeed = new DateTimeImmutable('2026-10-01');

        $this->assertSame(HostStatus::Stale, (new HostRules())->state(EvaluationStatus::Complete, Color::Red, $fresh, $oldFeed, $now)->status);
        $this->assertSame(HostStatus::Stale, (new HostRules())->state(EvaluationStatus::Complete, Color::Red, $fresh, null, $now)->status);
        $this->assertSame(HostStatus::Evaluated, (new HostRules(48, 60))->state(EvaluationStatus::Complete, Color::Red, $fresh, $oldFeed, $now)->status);
    }

    public function testUrgentHostsFirstAndHostsWithoutEvaluationLast(): void {
        $rules = new HostRules();
        $now = new DateTimeImmutable('2026-11-06 12:00:00');
        $fresh = new DateTimeImmutable('2026-11-06 11:00:00');
        $feed = new DateTimeImmutable('2026-11-04');

        $red = $rules->state(EvaluationStatus::Complete, Color::Red, $fresh, $feed, $now)->sortOrder;
        $green = $rules->state(EvaluationStatus::Complete, Color::Green, $fresh, $feed, $now)->sortOrder;
        $stale = $rules->state(EvaluationStatus::Complete, Color::Red, null, $feed, $now)->sortOrder;
        $none = $rules->state(EvaluationStatus::NotEvaluable, Color::Gray, null, $feed, $now)->sortOrder;

        $this->assertTrue($red < $green && $green < $stale && $stale < $none);
    }

    public function testTotalsCountCvesNotPackages(): void {
        $finding = $this->finding(['severity' => 3, 'kev_cisa' => true]);
        $red = new Classification(Rule::CisaKev, $finding->reasons());
        $inProgress = $red->withReasons(Reason::DECISION_IN_PROGRESS);

        // One CVE in two packages, one of them in progress
        $first = new CveRollup();
        $first->add($finding, $red);
        $first->add($finding, $inProgress);
        // A second CVE without decision
        $second = new CveRollup();
        $second->add($finding, $red);

        $totals = HostTotals::fromRollups(['CVE-1' => $first, 'CVE-2' => $second]);

        $this->assertSame(2, $totals->open);
        $this->assertSame(2, $totals->red);
        $this->assertSame(2, $totals->kev);
        $this->assertSame(2, $totals->high);
        $this->assertSame(1, $totals->inProgress);
    }

    public function testNotAffectedOnlyIfEveryPackageIsNotAffected(): void {
        $finding = $this->finding();
        $notAffected = new Classification(Rule::NotAffected, Reason::DECISION_NOT_AFFECTED);

        $all = new CveRollup();
        $all->add($finding, $notAffected);
        $partly = new CveRollup();
        $partly->add($finding, $notAffected);
        $partly->add($finding, new Classification(Rule::NoFix, 0));

        $totals = HostTotals::fromRollups(['CVE-1' => $all, 'CVE-2' => $partly]);

        $this->assertSame(1, $totals->notAffected);
        $this->assertSame(1, $totals->open);
    }
}
