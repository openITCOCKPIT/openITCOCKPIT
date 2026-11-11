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

namespace itnovum\openITCOCKPIT\Vuln\Vuls;

/**
 * One request for Vuls, together with the package state each package belongs to.
 * Vuls reports the affected packages by name, the name leads back to the package state.
 */
final class VulsRequest {

    /**
     * @param string $serverName Unique name, Vuls returns it in the report
     * @param string $family Vuls family, e.g. debian or alma
     * @param string $release e.g. 13.7
     * @param string $kernelRelease Running kernel (uname -r), empty for requests without kernel packages
     * @param array<string, array> $packages Binary packages indexed by name
     * @param array<string, array> $srcPackages Source packages indexed by name, Debian based only
     * @param array<string, int> $stateIdsByPackage Package state id indexed by binary package name
     */
    public function __construct(
        public readonly string $serverName,
        public readonly string $family,
        public readonly string $release,
        public readonly string $kernelRelease,
        public readonly array  $packages,
        public readonly array  $srcPackages,
        public readonly array  $stateIdsByPackage
    ) {
    }

    /**
     * The request as Vuls expects it for POST /vuls
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'serverName'    => $this->serverName,
            'family'        => $this->family,
            'release'       => $this->release,
            // Measured with Ubuntu, Debian and AlmaLinux: Vuls needs the release (uname -r), the version changes nothing
            'runningKernel' => ['release' => $this->kernelRelease, 'version' => ''],
            // Objects, so empty lists are encoded as {} and not as []
            'packages'      => (object)$this->packages,
            'srcPackages'   => (object)$this->srcPackages,
        ];
    }

    /**
     * @return int[]
     */
    public function getStateIds(): array {
        return array_values(array_unique($this->stateIdsByPackage));
    }
}
