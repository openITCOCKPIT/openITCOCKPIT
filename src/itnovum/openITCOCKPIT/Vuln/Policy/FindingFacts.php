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
 * All facts about one finding that the rules are based on.
 */
readonly class FindingFacts {

    /**
     * @param Severity|null $severity null if neither the vendor nor a CVSS source rates the CVE
     * @param string $componentType package, os or app
     * @param bool $isKernel The finding affects a kernel package
     * @param bool|null $isRunning Kernel packages only: false if the kernel is installed but not running, null if unknown
     * @param string $fixedIn Version that fixes the CVE, empty if unknown
     * @param string $fixState State reported by the vendor, e.g. "Will not fix"
     * @param bool $notFixedYet The vendor has not released a fix yet
     * @param bool $updateAvailable The package manager of the host offers an update
     * @param string $updateVersion Version of the available update, empty if unknown
     * @param bool $isCisaKev
     * @param bool $isVulnCheckKev
     * @param int $metasploitCount
     * @param int $verifiedExploitCount Exploits from Nuclei or Exploit-DB
     * @param int $proofOfConceptCount Proof of concepts from GitHub or Trickest
     */
    public function __construct(
        public ?Severity $severity,
        public string    $componentType,
        public bool      $isKernel,
        public ?bool     $isRunning,
        public string    $fixedIn,
        public string    $fixState,
        public bool      $notFixedYet,
        public bool      $updateAvailable,
        public string    $updateVersion,
        public bool      $isCisaKev,
        public bool      $isVulnCheckKev,
        public int       $metasploitCount,
        public int       $verifiedExploitCount,
        public int       $proofOfConceptCount
    ) {
    }

    /**
     * Creates the facts from a row of cve_host_findings joined with cves.
     *
     * @param array $row
     * @return self
     */
    public static function fromRow(array $row): self {
        return new self(
            severity: $row['severity'] === null ? null : Severity::from((int)$row['severity']),
            componentType: (string)$row['component_type'],
            isKernel: (bool)$row['is_kernel'],
            isRunning: $row['is_running'] === null ? null : (bool)$row['is_running'],
            fixedIn: trim((string)($row['fixed_in'] ?? '')),
            fixState: trim((string)($row['fix_state'] ?? '')),
            notFixedYet: (bool)$row['not_fixed_yet'],
            updateAvailable: (bool)$row['update_available'],
            updateVersion: trim((string)($row['update_version'] ?? '')),
            isCisaKev: (bool)($row['kev_cisa'] ?? false),
            isVulnCheckKev: (bool)($row['kev_vulncheck'] ?? false),
            metasploitCount: (int)($row['metasploit_count'] ?? 0),
            verifiedExploitCount: (int)($row['verified_exploit_count'] ?? 0),
            proofOfConceptCount: (int)($row['poc_exploit_count'] ?? 0)
        );
    }

    public function hasKnownPriority(): bool {
        return $this->severity !== null;
    }

    public function isHighPriority(): bool {
        return $this->severity !== null && $this->severity->isHighOrAbove();
    }

    /**
     * Only a kernel that is proven to be not running counts as inactive.
     * If the running kernel is unknown, the code counts as active, to be on the safe side.
     */
    public function isInactiveKernel(): bool {
        return $this->isKernel && $this->isRunning === false;
    }

    public function isActiveCode(): bool {
        return !$this->isInactiveKernel();
    }

    public function isKev(): bool {
        return $this->isCisaKev || $this->isVulnCheckKev;
    }

    /**
     * Ready to use attack tools. Proof of concepts from GitHub or Trickest do not count on purpose:
     * they exist for too many CVEs to tell anything about the risk.
     */
    public function hasWeaponizedExploit(): bool {
        return $this->metasploitCount > 0 || $this->verifiedExploitCount > 0;
    }

    /**
     * A fixed version only counts if the vendor does not say "not fixed yet" at the same time.
     */
    public function hasKnownFix(): bool {
        return $this->fixedIn !== '' && !$this->notFixedYet;
    }

    /**
     * If the vendor says "not fixed yet", an available update does not prove that it fixes this CVE.
     */
    public function hasUsableUpdate(): bool {
        return $this->updateAvailable && !$this->notFixedYet;
    }

    public function hasRemedy(): bool {
        return $this->hasKnownFix() || $this->hasUsableUpdate();
    }

    /**
     * Applications match by product name and version (CPE), the feed contains no fix for them.
     */
    public function isProductMatch(): bool {
        return $this->componentType === 'app';
    }

    /**
     * All reasons that apply to this finding, see Reason. Decisions add their own reasons later.
     *
     * @return int Combined Reason::* bits
     */
    public function reasons(): int {
        $reasons = 0;

        if ($this->isCisaKev) {
            $reasons |= Reason::CISA_KEV;
        }
        if ($this->isVulnCheckKev) {
            $reasons |= Reason::VULNCHECK_KEV;
        }
        if ($this->hasWeaponizedExploit()) {
            $reasons |= Reason::WEAPONIZED_EXPLOIT;
        }
        if ($this->proofOfConceptCount > 0) {
            $reasons |= Reason::PROOF_OF_CONCEPT;
        }

        if ($this->isHighPriority()) {
            $reasons |= Reason::HIGH_PRIORITY;
        }
        if (!$this->hasKnownPriority()) {
            $reasons |= Reason::PRIORITY_UNKNOWN;
        }

        if ($this->isInactiveKernel()) {
            $reasons |= Reason::KERNEL_NOT_RUNNING;
        }
        if ($this->isKernel && $this->isRunning === null) {
            $reasons |= Reason::RUNNING_KERNEL_UNKNOWN;
        }

        if ($this->hasKnownFix()) {
            $reasons |= Reason::FIX_KNOWN;
        }
        if ($this->updateAvailable) {
            $reasons |= Reason::UPDATE_AVAILABLE;
        }
        if ($this->notFixedYet) {
            $reasons |= Reason::VENDOR_NOT_FIXED;
        }
        if ($this->isProductMatch()) {
            $reasons |= Reason::PRODUCT_MATCH;
        }

        return $reasons;
    }
}
