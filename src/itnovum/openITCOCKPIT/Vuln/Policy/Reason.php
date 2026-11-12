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
 * Reasons of a classification as bit flags.
 *
 * A classification carries every fact that applies to a finding, not only the one that decided the rule.
 * Together with the rule this explains why a CVE got classified the way it did, e.g.
 * Rule::HighPriority with HIGH_PRIORITY | FIX_KNOWN | PROOF_OF_CONCEPT means: high priority on active code,
 * a fix is known, a proof of concept exists but does not escalate on its own.
 *
 * Reasons are combined with "|" and checked with Reason::has(). Do not combine them with "^" (XOR):
 * it toggles a bit, so adding the same reason twice would remove it.
 *
 * The values get stored in the database. Never change or reuse the value of an existing reason, only add new ones.
 * The frontend translates every bit into a text.
 */
final class Reason {

    /*
     * Signs of exploitation
     */

    /** CISA lists the CVE in its catalog of known exploited vulnerabilities (KEV) */
    public const CISA_KEV = 1 << 0;

    /** VulnCheck reports exploitation, see https://vulncheck.com/kev */
    public const VULNCHECK_KEV = 1 << 1;

    /** A ready to use attack tool exists: Metasploit module, Nuclei template or Exploit-DB entry */
    public const WEAPONIZED_EXPLOIT = 1 << 2;

    /**
     * Only a proof of concept exists, e.g. on GitHub. It is shown, but does not escalate on its own:
     * it exists for too many CVEs to tell anything about the risk.
     */
    public const PROOF_OF_CONCEPT = 1 << 3;

    /*
     * Priority
     */

    /** Priority High or Critical, from the vendor or from the CVSS score */
    public const HIGH_PRIORITY = 1 << 4;

    /** Neither the vendor nor any CVSS source rates the CVE. Unknown is never treated as harmless */
    public const PRIORITY_UNKNOWN = 1 << 5;

    /*
     * Kernel
     */

    /** The finding affects an installed kernel that is not running */
    public const KERNEL_NOT_RUNNING = 1 << 6;

    /** The finding affects a kernel package, but the running kernel is unknown. The kernel counts as running */
    public const RUNNING_KERNEL_UNKNOWN = 1 << 7;

    /*
     * Fix
     */

    /** The vendor names a version that fixes the CVE */
    public const FIX_KNOWN = 1 << 8;

    /** The package manager of the host offers an update for the component */
    public const UPDATE_AVAILABLE = 1 << 9;

    /** The vendor says "not fixed yet". Then neither a fixed version nor an available update counts as fix */
    public const VENDOR_NOT_FIXED = 1 << 10;

    /** The finding matched by product name and version (CPE). The feed contains no fix for such matches */
    public const PRODUCT_MATCH = 1 << 11;

    /*
     * Decisions of humans
     */

    /** A human accepted the risk */
    public const DECISION_ACCEPTED = 1 << 12;

    /** A human checked that the host is not affected */
    public const DECISION_NOT_AFFECTED = 1 << 13;

    /** A human confirmed the finding and works on it */
    public const DECISION_IN_PROGRESS = 1 << 14;

    /** The review date of the decision (the due date for in progress) has been reached */
    public const DECISION_EXPIRED = 1 << 15;

    /** The severity changed since the decision was made */
    public const DECISION_SEVERITY_CHANGED = 1 << 16;

    /** The fixed version changed since the decision was made */
    public const DECISION_FIX_CHANGED = 1 << 17;

    /** The vendor state ("not fixed yet", "will not fix", ...) changed since the decision was made */
    public const DECISION_VENDOR_STATE_CHANGED = 1 << 18;

    /** The decision was made with another version of the policy */
    public const DECISION_POLICY_CHANGED = 1 << 19;

    /** Each of these reasons ends a decision, so a human has to review the finding again */
    public const REVIEW_CAUSES = self::DECISION_EXPIRED
    | self::DECISION_SEVERITY_CHANGED
    | self::DECISION_FIX_CHANGED
    | self::DECISION_VENDOR_STATE_CHANGED
    | self::DECISION_POLICY_CHANGED;

    /**
     * Names of all reasons, for logs, the command line and the API (lower case, see CveFormatter).
     */
    private const NAMES = [
        self::CISA_KEV                      => 'CISA_KEV',
        self::VULNCHECK_KEV                 => 'VULNCHECK_KEV',
        self::WEAPONIZED_EXPLOIT            => 'WEAPONIZED_EXPLOIT',
        self::PROOF_OF_CONCEPT              => 'PROOF_OF_CONCEPT',
        self::HIGH_PRIORITY                 => 'HIGH_PRIORITY',
        self::PRIORITY_UNKNOWN              => 'PRIORITY_UNKNOWN',
        self::KERNEL_NOT_RUNNING            => 'KERNEL_NOT_RUNNING',
        self::RUNNING_KERNEL_UNKNOWN        => 'RUNNING_KERNEL_UNKNOWN',
        self::FIX_KNOWN                     => 'FIX_KNOWN',
        self::UPDATE_AVAILABLE              => 'UPDATE_AVAILABLE',
        self::VENDOR_NOT_FIXED              => 'VENDOR_NOT_FIXED',
        self::PRODUCT_MATCH                 => 'PRODUCT_MATCH',
        self::DECISION_ACCEPTED             => 'DECISION_ACCEPTED',
        self::DECISION_NOT_AFFECTED         => 'DECISION_NOT_AFFECTED',
        self::DECISION_IN_PROGRESS          => 'DECISION_IN_PROGRESS',
        self::DECISION_EXPIRED              => 'DECISION_EXPIRED',
        self::DECISION_SEVERITY_CHANGED     => 'DECISION_SEVERITY_CHANGED',
        self::DECISION_FIX_CHANGED          => 'DECISION_FIX_CHANGED',
        self::DECISION_VENDOR_STATE_CHANGED => 'DECISION_VENDOR_STATE_CHANGED',
        self::DECISION_POLICY_CHANGED       => 'DECISION_POLICY_CHANGED',
    ];

    /**
     * Returns true if all bits of $reason are set in $reasons
     *
     * @param int $reasons Combined reasons of a classification
     * @param int $reason One or more reasons to check, e.g. Reason::CISA_KEV
     * @return bool
     */
    public static function has(int $reasons, int $reason): bool {
        return ($reasons & $reason) === $reason;
    }

    /**
     * Returns the names of all set reasons, e.g. ['HIGH_PRIORITY', 'FIX_KNOWN']
     *
     * @param int $reasons
     * @return string[]
     */
    public static function toNames(int $reasons): array {
        $names = [];
        foreach (self::NAMES as $reason => $name) {
            if (self::has($reasons, $reason)) {
                $names[] = $name;
            }
        }
        return $names;
    }
}
