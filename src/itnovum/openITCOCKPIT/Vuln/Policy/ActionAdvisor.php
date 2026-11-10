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
 * Tells what should be done. The first matching case wins.
 */
class ActionAdvisor {

    public function forFinding(FindingFacts $finding, Classification $classification): Recommendation {
        // A human already decided, the task is to check the decision on time
        if ($classification->view() === FindingView::Accepted || $classification->view() === FindingView::NotAffected) {
            return new Recommendation(Action::ReviewDecision);
        }

        if ($finding->isInactiveKernel()) {
            return new Recommendation(Action::RemoveOrUpdateKernel);
        }

        // Operating systems with a fixed version, e.g. the Windows build that fixes the CVE
        if ($finding->componentType === 'os' && $finding->hasKnownFix()) {
            return new Recommendation(Action::InstallOsUpdate, $finding->fixedIn);
        }

        if ($finding->hasUsableUpdate()) {
            return new Recommendation(Action::InstallUpdate, $finding->updateVersion === '' ? null : $finding->updateVersion);
        }

        if ($finding->hasKnownFix()) {
            return new Recommendation(Action::CheckFixedVersion, $finding->fixedIn);
        }

        // Product matches (applications, macOS) contain no fix data
        if ($finding->componentType === 'app' || $finding->componentType === 'os') {
            return new Recommendation(Action::UpdateToVendorVersion);
        }

        return new Recommendation(Action::WatchVendor);
    }

    /**
     * Recommendation for a component across several hosts, e.g. "update package openssl on 12 hosts".
     *
     * @param string $componentType package, os or app
     * @param bool $isKernel
     * @param bool $updateAvailable At least one host can install an update
     * @return Recommendation
     */
    public function forComponent(string $componentType, bool $isKernel, bool $updateAvailable): Recommendation {
        if ($componentType === 'os') {
            return new Recommendation(Action::UpdateOperatingSystem);
        }
        if ($isKernel) {
            return new Recommendation(Action::UpdateKernelAndReboot);
        }
        if ($componentType === 'app') {
            return new Recommendation(Action::UpdateApplication);
        }
        if ($updateAvailable) {
            return new Recommendation(Action::UpdatePackage);
        }
        return new Recommendation(Action::CheckMitigation);
    }
}
