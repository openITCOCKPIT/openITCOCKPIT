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
 * Counters of a host, stored in cve_host_summaries. All counters count distinct CVEs, not packages.
 */
class HostTotals {

    public int $critical = 0;
    public int $high = 0;
    public int $medium = 0;
    public int $low = 0;

    /** CVEs without any severity */
    public int $unknown = 0;

    /** Open CVEs. Accepted CVEs are still open, not affected CVEs are not */
    public int $open = 0;

    public int $kev = 0;
    public int $exploit = 0;
    public int $fixable = 0;

    /** CVEs with at least one finding that needs action */
    public int $action = 0;

    /** CVEs with at least one red finding that needs action */
    public int $red = 0;

    public int $accepted = 0;
    public int $notAffected = 0;
    public int $inProgress = 0;

    /**
     * @param CveRollup[] $rollups One per CVE
     * @return self
     */
    public static function fromRollups(array $rollups): self {
        $totals = new self();
        foreach ($rollups as $rollup) {
            $totals->add($rollup);
        }
        return $totals;
    }

    private function add(CveRollup $rollup): void {
        // A CVE that does not affect the host is not open and is not counted anywhere else
        if ($rollup->isNotAffected()) {
            $this->notAffected++;
            return;
        }

        $this->open++;

        if ($rollup->isAccepted()) {
            $this->accepted++;
        }
        if ($rollup->isKev) {
            $this->kev++;
        }
        if ($rollup->hasExploit) {
            $this->exploit++;
        }
        if ($rollup->isFixable) {
            $this->fixable++;
        }
        if ($rollup->needsAction) {
            $this->action++;
        }
        if ($rollup->isRed) {
            $this->red++;
        }
        if ($rollup->isInProgress) {
            $this->inProgress++;
        }

        $this->countSeverity($rollup->highestSeverity);
    }

    /**
     * Severity "none" (e.g. Debian "unimportant") counts as low, unknown counts on its own
     */
    private function countSeverity(?Severity $severity): void {
        switch ($severity) {
            case null:
                $this->unknown++;
                break;

            case Severity::Critical:
                $this->critical++;
                break;

            case Severity::High:
                $this->high++;
                break;

            case Severity::Medium:
                $this->medium++;
                break;

            case Severity::Low:
            case Severity::None:
                $this->low++;
                break;
        }
    }
}
