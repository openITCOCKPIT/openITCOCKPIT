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

namespace itnovum\openITCOCKPIT\Vuln\Linux;

/**
 * Tells whether an installed kernel package belongs to the running kernel.
 *
 * A host can have several kernels installed, but only one of them runs. The code of the others only becomes
 * active with a reboot into them, so the policy treats their CVEs differently (Rule::InactiveKernel).
 *
 * The running kernel is the kernel release of the agent (uname -r), e.g.
 * - Ubuntu:    6.8.0-147-generic       packages linux-image-6.8.0-147-generic, source linux 6.8.0-147.147
 * - Debian:    6.12.113+deb13-amd64    source linux-signed-amd64 6.12.113+1
 * - AlmaLinux: 5.14.0-687.44.1.el9_8.x86_64   package kernel 5.14.0-687.44.1.el9_8
 * - SUSE:      6.12.0-160100.8-default package kernel-default 6.12.0-160100.8.1
 */
final class RunningKernel {

    /**
     * Flavor at the end of a SUSE kernel release, e.g. "-default" in 6.12.0-160100.8-default
     */
    private const SUSE_FLAVOR = '/-(?:default|preempt|azure|rt|64kb|kvmsmall)$/';

    /**
     * Flavor at the end of a Debian or Ubuntu kernel release, e.g. "-generic" or "-amd64"
     */
    private const DEBIAN_FLAVOR = '/-[a-z][a-z0-9+]*$/';

    /**
     * @param string $kernelRelease Running kernel as reported by the agent, empty if unknown
     * @param bool $isDebianBased
     * @param string $packageName Name of the binary package, e.g. linux-image-6.8.0-147-generic
     * @param string $sourceVersion Version of the source package, e.g. 6.8.0-147.147
     * @return bool|null null if the running kernel is unknown
     */
    public static function isRunning(string $kernelRelease, bool $isDebianBased, string $packageName, string $sourceVersion): ?bool {
        if ($kernelRelease === '') {
            return null;
        }

        if ($isDebianBased) {
            return self::isRunningDebianKernel($kernelRelease, $packageName, $sourceVersion);
        }

        return self::isRunningRpmKernel($kernelRelease, $sourceVersion);
    }

    /**
     * RPM: the kernel release starts with version and release of the package.
     * SUSE replaces the last part of the version by the flavor: package 6.12.0-160100.8.1 runs as 6.12.0-160100.8-default.
     */
    private static function isRunningRpmKernel(string $kernelRelease, string $sourceVersion): bool {
        if (str_starts_with($kernelRelease, $sourceVersion)) {
            return true;
        }

        $suseRelease = (string)preg_replace(self::SUSE_FLAVOR, '', $kernelRelease);
        if ($suseRelease === $kernelRelease) {
            // No SUSE flavor, so it is another kernel
            return false;
        }

        return str_starts_with($sourceVersion, $suseRelease . '.');
    }

    /**
     * Debian and Ubuntu: binary packages of a kernel contain the release in their name
     * (linux-image-6.8.0-147-generic), meta and signed packages only in their source version.
     */
    private static function isRunningDebianKernel(string $kernelRelease, string $packageName, string $sourceVersion): bool {
        // 6.8.0-147-generic => 6.8.0-147, 6.12.113+deb13-amd64 => 6.12.113+deb13
        $releaseWithoutFlavor = (string)preg_replace(self::DEBIAN_FLAVOR, '', $kernelRelease);

        if (str_contains($packageName, $kernelRelease) || str_contains($packageName, $releaseWithoutFlavor)) {
            return true;
        }

        // 6.12.113+deb13 => 6.12.113, the Debian source version is 6.12.113-1 or 6.12.113+1
        $upstreamVersion = (string)preg_replace('/[+].*$/', '', $releaseWithoutFlavor);

        return str_starts_with($sourceVersion, $upstreamVersion . '.')
            || str_starts_with($sourceVersion, $upstreamVersion . '-')
            || str_starts_with($sourceVersion, $upstreamVersion . '+');
    }
}
