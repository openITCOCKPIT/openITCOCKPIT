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
 * Combines all findings of one CVE on one host. A CVE can affect several packages,
 * but counts only once for the host.
 */
class CveRollup {

    /** Highest severity of all findings, null if none of them is rated */
    public ?Severity $highestSeverity = null;

    public bool $isKev = false;
    public bool $hasExploit = false;
    public bool $isFixable = false;

    /** At least one finding needs action */
    public bool $needsAction = false;

    /** At least one finding needs action and is red */
    public bool $isRed = false;

    /** At least one finding is in progress */
    public bool $isInProgress = false;

    private bool $allAccepted = true;
    private bool $allNotAffected = true;

    public function add(FindingFacts $finding, Classification $classification): void {
        if ($finding->severity !== null) {
            if ($this->highestSeverity === null || $finding->severity->isHigherThan($this->highestSeverity)) {
                $this->highestSeverity = $finding->severity;
            }
        }

        if ($finding->isKev()) {
            $this->isKev = true;
        }
        if ($finding->hasWeaponizedExploit()) {
            $this->hasExploit = true;
        }
        if ($finding->hasRemedy()) {
            $this->isFixable = true;
        }

        if ($classification->view() === FindingView::Action) {
            $this->needsAction = true;
            if ($classification->color() === Color::Red) {
                $this->isRed = true;
            }
        }

        if ($classification->isInProgress()) {
            $this->isInProgress = true;
        }

        if ($classification->view() !== FindingView::Accepted) {
            $this->allAccepted = false;
        }
        if ($classification->view() !== FindingView::NotAffected) {
            $this->allNotAffected = false;
        }
    }

    /**
     * The CVE counts as accepted only if the risk of every affected package is accepted
     */
    public function isAccepted(): bool {
        return $this->allAccepted;
    }

    /**
     * The host counts as not affected only if every affected package has been checked
     */
    public function isNotAffected(): bool {
        return $this->allNotAffected;
    }
}
