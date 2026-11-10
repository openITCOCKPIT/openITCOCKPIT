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
 * Where the priority of a CVE comes from. Stored in the severity_source columns and always shown,
 * because a vendor priority means something else than a CVSS score.
 */
enum PrioritySource: string {

    /*
     * Vendor ratings per CVE
     */
    case Ubuntu = 'ubuntu';
    case Debian = 'debian';
    case RedHat = 'redhat';
    case Microsoft = 'microsoft';
    case Suse = 'suse';

    /** Highest severity of the errata (e.g. AlmaLinux, Rocky). An erratum rates a whole update, not one CVE */
    case Erratum = 'erratum';

    /** A vendor rating without a known vendor */
    case Vendor = 'vendor';

    /*
     * CVSS scores, used if the vendor does not rate the CVE
     */
    case Nvd = 'nvd';
    case VulnCheck = 'vulncheck';
    case Mitre = 'mitre';
    case Euvd = 'euvd';

    /** CVSS of Red Hat, used for other distributions than Red Hat */
    case RedHatCvss = 'redhat_cvss';

    /** Nobody rates the CVE */
    case Unknown = 'unknown';

    public function isVendor(): bool {
        return match ($this) {
            self::Ubuntu, self::Debian, self::RedHat, self::Microsoft, self::Suse, self::Erratum, self::Vendor => true,
            default => false,
        };
    }
}
