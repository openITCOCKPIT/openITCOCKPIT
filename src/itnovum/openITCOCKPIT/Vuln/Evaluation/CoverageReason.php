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

namespace itnovum\openITCOCKPIT\Vuln\Evaluation;

/**
 * Why a host could not be evaluated completely. Stored in cve_host_coverage (type "reason"),
 * the frontend translates the code into a text. The values never change.
 *
 * A host is
 * - not evaluable if nothing could be checked (e.g. UNSUPPORTED_DISTRIBUTION, RELEASE_NOT_IN_FEED)
 * - partial if some installations could not be checked or a fact is unknown (e.g. RUNNING_KERNEL_UNKNOWN)
 * Some reasons only inform and keep the evaluation complete, see isInformational().
 */
final class CoverageReason {

    /** Vuls does not support the distribution of the host */
    public const UNSUPPORTED_DISTRIBUTION = 'unsupported_distribution';

    /** The agent reported no installed packages */
    public const EMPTY_INVENTORY = 'empty_inventory';

    /**
     * Packages without the information Vuls needs, e.g. the source package on Debian and Ubuntu.
     * Without it Vuls answers with 0 CVEs and no error, so these packages are not checked at all.
     * Older agents do not report source packages.
     */
    public const MISSING_SOURCE_METADATA = 'missing_source_metadata';

    /** RPM version without release ("1.2" instead of "1.2-3.el9"), Vuls can not compare it */
    public const VERSION_WITHOUT_RELEASE = 'version_without_release';

    /**
     * SUSE: the release package (e.g. Leap-release) is missing in the inventory.
     * Vuls finds no CVE without it and does not say why.
     */
    public const SUSE_RELEASE_PACKAGE_MISSING = 'suse_release_package_missing';

    /** The Vuls database does not know the release yet, e.g. a brand new release */
    public const RELEASE_NOT_IN_FEED = 'release_not_in_feed';

    /** Vuls answered with an error, the last successful result of these packages is used */
    public const CHECK_FAILED = 'check_failed';

    /** New packages that have not been checked yet, the next run checks them */
    public const CHECK_PENDING = 'check_pending';

    /**
     * There is a new Vuls database, but some packages have only been checked with the old one.
     * Their old result is used until the next run.
     */
    public const FEED_CHECK_PENDING = 'feed_check_pending';

    /**
     * The agent does not report the running kernel. All kernel packages count as running, to be on the safe side,
     * and Vuls can not tell which kernel is affected.
     */
    public const RUNNING_KERNEL_UNKNOWN = 'running_kernel_unknown';

    /**
     * Debian and Ubuntu: Vuls only checks the packages of the running kernel. Installed kernels that are not running
     * are not checked, they are listed in cve_host_coverage (type "inactive_kernel").
     */
    public const INACTIVE_KERNELS_NOT_EVALUATED = 'inactive_kernels_not_evaluated';

    /**
     * Packages of RPM modules (e.g. nodejs:20). Vuls checks them, but the results have not been verified
     * against the advisories of the vendor yet.
     */
    public const MODULE_PACKAGES_UNVERIFIED = 'module_packages_unverified';

    /**
     * The host also has applications of another operating system in its inventory, e.g. Windows apps on a
     * Linux host. Either the inventory is outdated or the host has been reinstalled.
     */
    public const PLATFORM_CONFLICT = 'platform_conflict';

    /**
     * Reasons that only inform. The installed packages have all been checked.
     */
    private const INFORMATIONAL = [
        self::INACTIVE_KERNELS_NOT_EVALUATED,
        self::MODULE_PACKAGES_UNVERIFIED,
        self::PLATFORM_CONFLICT,
    ];

    /**
     * Reasons that are stored as error_message of a package state (see CvePackageStatesTable::saveCheckError()).
     * Every other error message is an error of Vuls.
     */
    private const STATE_ERRORS = [
        self::VERSION_WITHOUT_RELEASE,
        self::SUSE_RELEASE_PACKAGE_MISSING,
        self::RELEASE_NOT_IN_FEED,
        self::UNSUPPORTED_DISTRIBUTION,
    ];

    /**
     * @param string $reason
     * @return bool True if the reason does not make the evaluation partial
     */
    public static function isInformational(string $reason): bool {
        return in_array($reason, self::INFORMATIONAL, true);
    }

    /**
     * Maps the error message of a package state to a reason.
     *
     * @param string $errorMessage error_message of cve_package_states
     * @return string One of the constants
     */
    public static function fromStateError(string $errorMessage): string {
        if (in_array($errorMessage, self::STATE_ERRORS, true)) {
            return $errorMessage;
        }

        return self::CHECK_FAILED;
    }
}
