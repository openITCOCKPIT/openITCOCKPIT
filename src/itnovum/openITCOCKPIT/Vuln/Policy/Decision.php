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

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Decision of a human about one finding. It remembers the state of the finding it was made for.
 *
 * - accepted:     The risk is accepted
 * - not_affected: The host is not affected
 * - in_progress:  The finding is confirmed and somebody works on it
 *
 * How a decision affects the classification is defined in DecisionRules.
 */
readonly class Decision {

    public const ACCEPTED = 'accepted';
    public const NOT_AFFECTED = 'not_affected';
    public const IN_PROGRESS = 'in_progress';

    /**
     * @param string $state One of the constants
     * @param DateTimeImmutable $reviewAfter Review date, for in_progress the due date. The decision ends on this day
     * @param Severity|null $severity Severity of the finding when the decision was made
     * @param string $fixedIn Fixed version of the finding when the decision was made
     * @param string $fixState Vendor state of the finding when the decision was made
     * @param bool $notFixedYet "Not fixed yet" of the finding when the decision was made
     * @param string $policyVersion Version of the policy when the decision was made
     */
    public function __construct(
        public string            $state,
        public DateTimeImmutable $reviewAfter,
        public ?Severity         $severity,
        public string            $fixedIn,
        public string            $fixState,
        public bool              $notFixedYet,
        public string            $policyVersion
    ) {
    }

    /**
     * Creates the decision from a row of cve_decisions.
     *
     * @param array $row review_after may be a string (Y-m-d) or a date object
     * @return self
     */
    public static function fromRow(array $row): self {
        $reviewAfter = $row['review_after'];
        if (!is_string($reviewAfter)) {
            // Cake\I18n\Date does not implement DateTimeInterface, but every date object can format itself
            $reviewAfter = $reviewAfter->format('Y-m-d');
        }

        return new self(
            state: (string)$row['state'],
            reviewAfter: new DateTimeImmutable($reviewAfter),
            severity: $row['severity'] === null ? null : Severity::from((int)$row['severity']),
            fixedIn: trim((string)($row['fixed_in'] ?? '')),
            fixState: trim((string)($row['fix_state'] ?? '')),
            notFixedYet: (bool)$row['not_fixed_yet'],
            policyVersion: (string)$row['policy_version']
        );
    }

    public function isAccepted(): bool {
        return $this->state === self::ACCEPTED;
    }

    public function isNotAffected(): bool {
        return $this->state === self::NOT_AFFECTED;
    }

    public function isInProgress(): bool {
        return $this->state === self::IN_PROGRESS;
    }

    /**
     * The decision ends on its review date, not one day later.
     */
    public function isExpired(DateTimeInterface $now): bool {
        return $now->format('Y-m-d') >= $this->reviewAfter->format('Y-m-d');
    }

    /**
     * The reason that names the kind of the decision.
     *
     * @return int Reason::DECISION_ACCEPTED, Reason::DECISION_NOT_AFFECTED or Reason::DECISION_IN_PROGRESS
     */
    public function stateReason(): int {
        switch ($this->state) {
            case self::ACCEPTED:
                return Reason::DECISION_ACCEPTED;

            case self::NOT_AFFECTED:
                return Reason::DECISION_NOT_AFFECTED;

            case self::IN_PROGRESS:
                return Reason::DECISION_IN_PROGRESS;

            default:
                throw new \LogicException('Unhandled decision state: ' . $this->state);
        }
    }

    /**
     * Compares the finding with the state it had when the decision was made.
     *
     * @param FindingFacts $finding
     * @return int Reason::DECISION_*_CHANGED bits, 0 if nothing changed
     */
    public function changesSince(FindingFacts $finding): int {
        $changes = 0;

        if ($this->severity !== $finding->severity) {
            $changes |= Reason::DECISION_SEVERITY_CHANGED;
        }
        if ($this->fixedIn !== $finding->fixedIn) {
            $changes |= Reason::DECISION_FIX_CHANGED;
        }
        if ($this->fixState !== $finding->fixState || $this->notFixedYet !== $finding->notFixedYet) {
            $changes |= Reason::DECISION_VENDOR_STATE_CHANGED;
        }
        if ($this->policyVersion !== PolicyVersion::CURRENT) {
            $changes |= Reason::DECISION_POLICY_CHANGED;
        }

        return $changes;
    }
}
