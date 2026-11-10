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

/**
 * Result of the classification of one finding: the rule that matched and all reasons that apply.
 *
 * View, color and rank follow from the rule, see Rule. The reasons explain the rule, see Reason.
 */
readonly class Classification {

    /**
     * @param Rule $rule
     * @param int $reasons Combined Reason::* bits
     */
    public function __construct(
        public Rule $rule,
        public int  $reasons
    ) {
    }

    public function view(): FindingView {
        return $this->rule->view();
    }

    public function color(): Color {
        return $this->rule->color();
    }

    public function rank(): int {
        return $this->rule->rank();
    }

    /**
     * @param int $reason One or more Reason::* bits
     * @return bool
     */
    public function has(int $reason): bool {
        return Reason::has($this->reasons, $reason);
    }

    /**
     * A decision ended (expired or the finding changed), so a human has to decide again.
     */
    public function requiresReview(): bool {
        return ($this->reasons & Reason::REVIEW_CAUSES) !== 0;
    }

    /**
     * Somebody works on the finding and the due date has not been reached yet.
     */
    public function isInProgress(): bool {
        return $this->has(Reason::DECISION_IN_PROGRESS) && !$this->has(Reason::DECISION_EXPIRED);
    }

    public function withRule(Rule $rule): self {
        return new self($rule, $this->reasons);
    }

    /**
     * @param int $reasons Reason::* bits to add
     * @return self
     */
    public function withReasons(int $reasons): self {
        return new self($this->rule, $this->reasons | $reasons);
    }
}
