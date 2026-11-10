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
 * The rules of the policy, see POLICY.md. The FindingClassifier checks them in the order of their rank,
 * the first matching rule wins. Decisions of humans lead to the rules Accepted, NotAffected or ReviewRequired.
 *
 * The rank orders findings by urgency (1 is the most urgent) and is stored in cve_host_findings.rule_rank.
 * Two rules share rank 6: both are yellow tasks a human has to look at.
 */
enum Rule: string {

    /** Rank 1, red: CISA KEV on active code */
    case CisaKev = 'cisa_kev';

    /** Rank 2, red: high priority on active code and a weaponized exploit */
    case WeaponizedExploit = 'weaponized_exploit';

    /** Rank 3, red: high priority on active code */
    case HighPriority = 'high_priority';

    /** Rank 4, yellow: VulnCheck reports exploitation, CISA does not */
    case VulnCheckKev = 'vulncheck_kev';

    /** Rank 5, yellow: high priority or KEV on an installed kernel that is not running */
    case InactiveKernel = 'inactive_kernel';

    /** Rank 6, yellow: no priority known */
    case PriorityUnknown = 'priority_unknown';

    /** Rank 6, yellow: a decision ended (expired or the finding changed), a human has to decide again */
    case ReviewRequired = 'review_required';

    /** Rank 7, yellow: lower priority, but a fix or an update is known */
    case RemedyAvailable = 'remedy_available';

    /** Rank 8, gray: lower priority and no fix known */
    case NoFix = 'no_fix';

    /** Rank 9, gray: the risk is accepted */
    case Accepted = 'accepted';

    /** Rank 10, gray: the host is not affected */
    case NotAffected = 'not_affected';

    /**
     * @return int 1 is the most urgent
     */
    public function rank(): int {
        switch ($this) {
            case self::CisaKev:
                return 1;
            case self::WeaponizedExploit:
                return 2;
            case self::HighPriority:
                return 3;
            case self::VulnCheckKev:
                return 4;
            case self::InactiveKernel:
                return 5;
            case self::PriorityUnknown:
            case self::ReviewRequired:
                return 6;
            case self::RemedyAvailable:
                return 7;
            case self::NoFix:
                return 8;
            case self::Accepted:
                return 9;
            case self::NotAffected:
                return 10;

            default:
                throw new \LogicException('Unhandled Rule enum case: ' . $this->value);
        }
    }

    public function color(): Color {
        switch ($this) {
            case self::CisaKev:
            case self::WeaponizedExploit:
            case self::HighPriority:
                return Color::Red;

            case self::VulnCheckKev:
            case self::InactiveKernel:
            case self::PriorityUnknown:
            case self::ReviewRequired:
            case self::RemedyAvailable:
                return Color::Yellow;

            case self::NoFix:
            case self::Accepted:
            case self::NotAffected:
                return Color::Gray;

            default:
                throw new \LogicException('Unhandled Rule enum case: ' . $this->value);
        }
    }

    public function view(): FindingView {
        switch ($this) {
            case self::NoFix:
                return FindingView::Unfixed;

            case self::Accepted:
                return FindingView::Accepted;

            case self::NotAffected:
                return FindingView::NotAffected;

            default:
                return FindingView::Action;
        }
    }
}
