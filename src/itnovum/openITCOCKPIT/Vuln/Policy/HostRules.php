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

namespace itnovum\openITCOCKPIT\Vuln\Policy;

use DateTimeInterface;

/**
 * Rules for the traffic light of a host. It shows the need for action, not security.
 */
class HostRules {

    private const SECONDS_PER_HOUR = 3600;
    private const SECONDS_PER_DAY = 86400;

    /**
     * @param int $inventoryMaxAgeHours An older inventory makes the host stale
     * @param int $feedMaxAgeDays An older Vuls database makes all hosts stale
     */
    public function __construct(
        private int $inventoryMaxAgeHours = 48,
        private int $feedMaxAgeDays = 7
    ) {
    }

    /**
     * Color of a host after an evaluation.
     */
    public function color(EvaluationStatus $status, HostTotals $totals): Color {
        // A failed evaluation must not hide red findings of the last successful one
        if ($status === EvaluationStatus::Failed && $totals->red > 0) {
            return Color::Red;
        }

        if ($status === EvaluationStatus::Failed || $status === EvaluationStatus::NotEvaluable) {
            return Color::Gray;
        }

        if ($totals->red > 0) {
            return Color::Red;
        }
        if ($totals->action > 0) {
            return Color::Yellow;
        }

        // Green means nothing needs action, it is no proof that the host is free of CVEs
        return Color::Green;
    }

    /**
     * Adds the freshness of the data to the color. Stale findings stay visible, but the color turns gray:
     * green with old data would only mean that an old database knows no CVEs.
     *
     * @param EvaluationStatus $status
     * @param Color $color Result of color()
     * @param DateTimeInterface|null $inventoryLastUpdate null if unknown, which counts as stale
     * @param DateTimeInterface|null $feedDate Date of the Vuls database, null if unknown, which counts as stale
     * @param DateTimeInterface $now
     * @return HostState
     */
    public function state(EvaluationStatus $status, Color $color, ?DateTimeInterface $inventoryLastUpdate, ?DateTimeInterface $feedDate, DateTimeInterface $now): HostState {
        $inventoryAgeSeconds = null;
        if ($inventoryLastUpdate !== null) {
            $inventoryAgeSeconds = $now->getTimestamp() - $inventoryLastUpdate->getTimestamp();
        }

        $isStale = $this->isInventoryStale($inventoryAgeSeconds) || $this->isFeedStale($feedDate, $now);
        $assessmentStatus = $this->assessmentStatus($status, $isStale);

        if ($assessmentStatus === AssessmentStatus::Stale || $assessmentStatus === AssessmentStatus::NotEvaluated) {
            $color = Color::Gray;
        }

        $inventoryAgeDays = null;
        if ($inventoryAgeSeconds !== null) {
            $inventoryAgeDays = (int)floor($inventoryAgeSeconds / self::SECONDS_PER_DAY);
        }

        return new HostState(
            status: $assessmentStatus,
            color: $color,
            sortOrder: $this->sortOrder($assessmentStatus, $color),
            inventoryAgeDays: $inventoryAgeDays
        );
    }

    private function isInventoryStale(?int $ageSeconds): bool {
        if ($ageSeconds === null) {
            return true;
        }
        return $ageSeconds > $this->inventoryMaxAgeHours * self::SECONDS_PER_HOUR;
    }

    private function isFeedStale(?DateTimeInterface $feedDate, DateTimeInterface $now): bool {
        if ($feedDate === null) {
            return true;
        }
        $ageSeconds = $now->getTimestamp() - $feedDate->getTimestamp();
        return $ageSeconds > $this->feedMaxAgeDays * self::SECONDS_PER_DAY;
    }

    /**
     * Not evaluable and failed are shown as they are, even with stale data: there is nothing newer to show.
     */
    private function assessmentStatus(EvaluationStatus $status, bool $isStale): AssessmentStatus {
        if ($status === EvaluationStatus::NotEvaluable) {
            return AssessmentStatus::NotEvaluated;
        }
        if ($status === EvaluationStatus::Failed) {
            return AssessmentStatus::Failed;
        }
        if ($isStale) {
            return AssessmentStatus::Stale;
        }
        if ($status === EvaluationStatus::Partial) {
            return AssessmentStatus::Partial;
        }
        return AssessmentStatus::Evaluated;
    }

    /**
     * Urgent hosts first, then stale hosts, hosts without evaluation last. Within a group by color.
     */
    private function sortOrder(AssessmentStatus $status, Color $color): int {
        $groupOffset = 0;
        switch ($status) {
            case AssessmentStatus::NotEvaluated:
                $groupOffset = 20;
                break;
            case AssessmentStatus::Stale:
                $groupOffset = 10;
                break;
        }

        $colorOrder = 0;
        switch ($color) {
            case Color::Red:
                $colorOrder = 0;
                break;
            case Color::Yellow:
                $colorOrder = 1;
                break;
            case Color::Green:
                $colorOrder = 2;
                break;
            case Color::Gray:
                $colorOrder = 3;
                break;
        }

        return $groupOffset + $colorOrder;
    }
}
