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
 * Classifies a finding. The checks follow the rule table of POLICY.md:
 * the first matching rule wins, so the order of the checks is the order of the ranks.
 *
 * Every classification carries all reasons of the finding, also those that did not decide the rule.
 * Decisions of humans are applied afterwards by DecisionRules.
 */
class FindingClassifier {

    public function classify(FindingFacts $finding): Classification {
        $reasons = $finding->reasons();

        // Rule 1: CISA confirms attacks in the wild. Only an inactive kernel can lower this, because the
        // vulnerable code does not run until the next reboot into that kernel.
        if ($finding->isCisaKev && $finding->isActiveCode()) {
            return new Classification(Rule::CisaKev, $reasons);
        }

        // Rule 2: A ready to use attack tool for a high priority CVE. Proof of concepts do not count here.
        if ($finding->hasWeaponizedExploit() && $finding->isHighPriority() && $finding->isActiveCode()) {
            return new Classification(Rule::WeaponizedExploit, $reasons);
        }

        // Rule 3: High priority on active code. This applies even without a fix, the risk is the same.
        if ($finding->isHighPriority() && $finding->isActiveCode()) {
            return new Classification(Rule::HighPriority, $reasons);
        }

        // Rule 4: Only VulnCheck reports exploitation. A CVE in both catalogs already matched rule 1.
        if ($finding->isVulnCheckKev && $finding->isActiveCode()) {
            return new Classification(Rule::VulnCheckKev, $reasons);
        }

        // Rule 5: A dangerous CVE in a kernel that is installed but not running. It becomes active with
        // the next reboot into that kernel, so the package should be removed or updated.
        if ($finding->isInactiveKernel() && ($finding->isHighPriority() || $finding->isKev())) {
            return new Classification(Rule::InactiveKernel, $reasons);
        }

        // Rule 6: Nobody rates the CVE. Unknown is never treated as harmless, a human has to look at it.
        if (!$finding->hasKnownPriority()) {
            return new Classification(Rule::PriorityUnknown, $reasons);
        }

        // Rule 7: Lower priority, but there is something to do: a fixed version or an update.
        // An update does not prove that it fixes this CVE, see FindingFacts::hasUsableUpdate().
        if ($finding->hasRemedy()) {
            return new Classification(Rule::RemedyAvailable, $reasons);
        }

        // Rule 8: Lower priority and no fix. The finding stays open, this is no proof that the host is not affected.
        // Reason::PRODUCT_MATCH tells that the fix is unknown rather than missing.
        return new Classification(Rule::NoFix, $reasons);
    }
}
