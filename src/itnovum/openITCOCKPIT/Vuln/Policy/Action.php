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
 * What should be done about a finding or a component. The frontend translates the value into a text.
 */
enum Action: string {

    /*
     * For one finding, see ActionAdvisor::forFinding()
     */

    /** The finding has a decision, check it in time */
    case ReviewDecision = 'review_decision';

    /** Remove or update the installed kernel that is not running */
    case RemoveOrUpdateKernel = 'remove_or_update_kernel';

    /** Install the operating system update that fixes the CVE, e.g. a Windows update */
    case InstallOsUpdate = 'install_os_update';

    /** Install the update the package manager offers */
    case InstallUpdate = 'install_update';

    /** A fixed version is known, but the package manager offers no update yet */
    case CheckFixedVersion = 'check_fixed_version';

    /** Product match without fix data: update to the current version of the vendor */
    case UpdateToVendorVersion = 'update_to_vendor_version';

    /** No fix: watch the vendor and check mitigations */
    case WatchVendor = 'watch_vendor';

    /*
     * For a component on several hosts, see ActionAdvisor::forComponent()
     */

    case UpdateKernelAndReboot = 'update_kernel_and_reboot';
    case UpdateOperatingSystem = 'update_operating_system';
    case UpdateApplication = 'update_application';
    case UpdatePackage = 'update_package';
    case CheckMitigation = 'check_mitigation';
}
