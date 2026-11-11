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
 * A Linux distribution as Vuls knows it.
 *
 * The agent reports the distribution with the names of gopsutil (os_name), e.g. "almalinux" or "opensuse-leap".
 * Vuls expects its own names ("alma", "opensuse.leap") and answers every other name with HTTP 503.
 */
final class LinuxDistribution {

    /**
     * Name of the agent => Vuls family. These distributions check source packages (dpkg).
     */
    private const DEBIAN_BASED = [
        'ubuntu'   => 'ubuntu',
        'debian'   => 'debian',
        'raspbian' => 'raspbian',
    ];

    /**
     * Name of the agent => Vuls family. These distributions check binary packages (rpm).
     * gopsutil reports RHEL as "redhat", "rhel" is only listed to be on the safe side.
     */
    private const RPM_BASED = [
        'almalinux'           => 'alma',
        'redhat'              => 'redhat',
        'rhel'                => 'redhat',
        'rocky'               => 'rocky',
        'centos'              => 'centos',
        'oracle'              => 'oracle',
        'fedora'              => 'fedora',
        'amazon'              => 'amazon',
        'opensuse-leap'       => 'opensuse.leap',
        'opensuse-tumbleweed' => 'opensuse',
        'opensuse'            => 'opensuse',
        'sles'                => 'suse.linux.enterprise.server',
        'sled'                => 'suse.linux.enterprise.desktop',
    ];

    private function __construct(
        public readonly string $osName,
        public readonly string $osVersion,
        public readonly string $family,
        public readonly bool   $isDebianBased
    ) {
    }

    /**
     * @param string $osName Name as reported by the agent, e.g. almalinux
     * @param string $osVersion Version as reported by the agent, e.g. 9.8
     * @return self|null null if Vuls does not support the distribution
     */
    public static function fromInventory(string $osName, string $osVersion): ?self {
        if (trim($osVersion) === '') {
            return null;
        }

        if (isset(self::DEBIAN_BASED[$osName])) {
            return new self($osName, $osVersion, self::DEBIAN_BASED[$osName], true);
        }

        if (isset(self::RPM_BASED[$osName])) {
            return new self($osName, $osVersion, self::RPM_BASED[$osName], false);
        }

        return null;
    }

    /**
     * Release as Vuls expects it. The VERSION_ID of Tumbleweed is a date, Vuls expects the fixed name.
     *
     * @return string
     */
    public function getRelease(): string {
        if ($this->osName === 'opensuse-tumbleweed') {
            return 'tumbleweed';
        }
        return $this->osVersion;
    }

    /**
     * SUSE needs the installed release package in every request. Without it, Vuls reports 0 CVEs without
     * any error, even for very old packages.
     *
     * @return string|null Name of the release package, null if the distribution does not need one
     */
    public function getReleasePackageName(): ?string {
        return match ($this->family) {
            // Leap 15 uses openSUSE-release, Leap 16 Leap-release
            'opensuse.leap'                 => (int)$this->osVersion >= 16 ? 'Leap-release' : 'openSUSE-release',
            'opensuse'                      => 'openSUSE-release',
            'suse.linux.enterprise.server'  => 'sles-release',
            'suse.linux.enterprise.desktop' => 'sled-release',
            default                         => null,
        };
    }

    /**
     * For Leap the version of the release package has to match the release, e.g. "16.1-160100.1.1" for 16.1,
     * otherwise Vuls reports 0 CVEs as well.
     *
     * @param string $name
     * @param string $version
     * @return bool
     */
    public function isReleasePackage(string $name, string $version): bool {
        if ($name !== $this->getReleasePackageName()) {
            return false;
        }

        if ($this->family === 'opensuse.leap') {
            return str_starts_with($version, $this->osVersion . '-');
        }

        return true;
    }
}
