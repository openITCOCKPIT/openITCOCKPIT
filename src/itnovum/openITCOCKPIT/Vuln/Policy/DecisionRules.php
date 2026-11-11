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
 * Applies the decision of a human to a classification.
 *
 * - accepted and not_affected change the rule to Accepted or NotAffected (gray). They end on the review date,
 *   or as soon as severity, fix, vendor state or policy version differ from the state they were made for.
 * - in_progress only marks the finding. Rule, color and view stay, a red finding stays red and keeps
 *   the host red. It ends only on its due date, a changed finding does not stop the work on it.
 *
 * A decision that ended never hides a finding: it gets its normal classification back, at least the
 * yellow task ReviewRequired. Missing fixes or missing findings never create a decision on their own.
 */
class DecisionRules {

    public function apply(Classification $classification, FindingFacts $finding, ?Decision $decision, DateTimeInterface $now): Classification {
        if ($decision === null) {
            return $classification;
        }

        // Record the kind of the decision in any case, also if it ended
        $classification = $classification->withReasons($decision->stateReason());

        if ($decision->isInProgress()) {
            return $this->applyInProgress($classification, $decision, $now);
        }

        $endReasons = $decision->changesSince($finding);
        if ($decision->isExpired($now)) {
            $endReasons |= Reason::DECISION_EXPIRED;
        }

        if ($endReasons !== 0) {
            return $this->requireReview($classification, $endReasons);
        }

        if ($decision->isAccepted()) {
            return $classification->withRule(Rule::Accepted);
        }

        return $classification->withRule(Rule::NotAffected);
    }

    /**
     * In progress keeps the classification. Only the overdue date requires a new decision.
     */
    private function applyInProgress(Classification $classification, Decision $decision, DateTimeInterface $now): Classification {
        if ($decision->isExpired($now)) {
            return $this->requireReview($classification, Reason::DECISION_EXPIRED);
        }

        return $classification;
    }

    /**
     * A finding that needs action keeps its rule, so a red finding stays red.
     * All other findings (no fix) become the yellow task ReviewRequired.
     *
     * @param Classification $classification
     * @param int $endReasons Reason::REVIEW_CAUSES bits that ended the decision
     * @return Classification
     */
    private function requireReview(Classification $classification, int $endReasons): Classification {
        $classification = $classification->withReasons($endReasons);

        if ($classification->view() !== FindingView::Action) {
            return $classification->withRule(Rule::ReviewRequired);
        }

        return $classification;
    }
}
