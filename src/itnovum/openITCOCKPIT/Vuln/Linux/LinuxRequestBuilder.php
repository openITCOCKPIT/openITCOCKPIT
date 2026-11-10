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

use App\Model\Entity\CvePackageState;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsRequest;

/**
 * Builds the requests for Vuls from the package states of one group (see CvePackageStatesTable::getPendingGroups()).
 *
 * All package states of a group share operating system and running kernel. Vuls expects one request per system,
 * and every package name only once per request. The package states of many hosts can contain the same package
 * in several versions, so they get split into batches: each batch is one request.
 *
 * - Debian based: Vuls checks the source packages. A source package exists once per request and needs one version,
 *   so a binary package whose source package is already in the batch with another version goes to the next batch.
 * - RPM based: Vuls checks the binary packages. The version gets split at the last "-" into version and release.
 * - SUSE: the release package is part of every batch, see LinuxDistribution::getReleasePackageName().
 * - Kernel packages: the request names the running kernel. Vuls only evaluates the packages of this kernel
 *   on Debian based systems, so kernel packages are grouped per running kernel.
 */
final class LinuxRequestBuilder {

    /**
     * @param LinuxDistribution $distribution
     * @param string|null $kernelRelease Running kernel of a group of kernel packages, null for all other packages
     * @param CvePackageState[] $states Package states of the group
     * @param CvePackageState[] $releasePackages Package states of the release package of this operating system, SUSE only.
     *                                           Can be checked already, it is needed in every request anyway
     * @return LinuxRequestPlan
     */
    public function build(LinuxDistribution $distribution, ?string $kernelRelease, array $states, array $releasePackages = []): LinuxRequestPlan {
        $plan = new LinuxRequestPlan();

        $releasePackage = null;
        if ($distribution->getReleasePackageName() !== null) {
            foreach ($releasePackages as $state) {
                if ($distribution->isReleasePackage($state->name, $state->version)) {
                    $releasePackage = $state;
                    break;
                }
            }

            if ($releasePackage === null) {
                foreach ($states as $state) {
                    $plan->skip($state->id, 'suse_release_package_missing');
                }
                return $plan;
            }
        }

        $batches = [];
        foreach ($states as $state) {
            // The release package is added to every batch below
            if ($releasePackage !== null && $state->id === $releasePackage->id) {
                continue;
            }

            $entry = $this->packageEntry($distribution, $state);
            if ($entry === null) {
                $plan->skip($state->id, 'version_without_release');
                continue;
            }

            $index = $this->findBatch($batches, $distribution, $state);
            $batches[$index] ??= ['packages' => [], 'srcPackages' => [], 'stateIds' => []];
            $this->addToBatch($batches[$index], $distribution, $state, $entry);
        }

        // Only the release package itself is pending, it still needs a request
        $stateIds = array_map(fn(CvePackageState $state) => $state->id, $states);
        if (empty($batches) && $releasePackage !== null && in_array($releasePackage->id, $stateIds, true)) {
            $batches[0] = ['packages' => [], 'srcPackages' => [], 'stateIds' => []];
        }

        foreach ($batches as $index => $batch) {
            if ($releasePackage !== null) {
                $this->addToBatch($batch, $distribution, $releasePackage, $this->packageEntry($distribution, $releasePackage));
            }

            $plan->addRequest(new VulsRequest(
                serverName: $this->serverName($distribution, $kernelRelease, $index + 1),
                family: $distribution->family,
                release: $distribution->getRelease(),
                kernelRelease: $kernelRelease ?? '',
                packages: $batch['packages'],
                srcPackages: $batch['srcPackages'],
                stateIdsByPackage: $batch['stateIds']
            ));
        }

        return $plan;
    }

    /**
     * The package as Vuls expects it
     *
     * @param LinuxDistribution $distribution
     * @param CvePackageState $state
     * @return array|null null if the RPM version can not be split into version and release
     */
    private function packageEntry(LinuxDistribution $distribution, CvePackageState $state): ?array {
        if ($distribution->isDebianBased) {
            return [
                'name'    => $state->name,
                'version' => $state->version,
                'arch'    => (string)$state->architecture,
            ];
        }

        // e.g. "5.14.0-687.44.1.el9_8" => version "5.14.0", release "687.44.1.el9_8". An epoch stays in the version
        $separator = strrpos($state->version, '-');
        if ($separator === false || $separator === 0 || $separator === strlen($state->version) - 1) {
            return null;
        }

        return [
            'name'            => $state->name,
            'version'         => substr($state->version, 0, $separator),
            'release'         => substr($state->version, $separator + 1),
            'arch'            => (string)$state->architecture,
            'modularitylabel' => (string)$state->modularity_label,
        ];
    }

    /**
     * The first batch that does not contain the package name yet and whose source package fits
     *
     * @param array $batches
     * @param LinuxDistribution $distribution
     * @param CvePackageState $state
     * @return int
     */
    private function findBatch(array $batches, LinuxDistribution $distribution, CvePackageState $state): int {
        $index = 0;
        while (isset($batches[$index])) {
            $batch = $batches[$index];

            $nameIsFree = !isset($batch['packages'][$state->name]);
            $sourceFits = !$distribution->isDebianBased || $this->sourcePackageFits($batch, $state);
            if ($nameIsFree && $sourceFits) {
                return $index;
            }

            $index++;
        }

        return $index;
    }

    /**
     * A source package exists once per request: same version, and the same architecture unless one of them is "all"
     *
     * @param array $batch
     * @param CvePackageState $state
     * @return bool
     */
    private function sourcePackageFits(array $batch, CvePackageState $state): bool {
        $existing = $batch['srcPackages'][$state->source_package] ?? null;
        if ($existing === null) {
            return true;
        }

        if ($existing['version'] !== $state->source_version) {
            return false;
        }

        $architecture = (string)$state->architecture;
        return $existing['arch'] === $architecture || $existing['arch'] === 'all' || $architecture === 'all';
    }

    /**
     * @param array $batch
     * @param LinuxDistribution $distribution
     * @param CvePackageState $state
     * @param array $entry
     * @return void
     */
    private function addToBatch(array &$batch, LinuxDistribution $distribution, CvePackageState $state, array $entry): void {
        $batch['packages'][$state->name] = $entry;
        $batch['stateIds'][$state->name] = $state->id;

        if (!$distribution->isDebianBased) {
            return;
        }

        $source = $state->source_package;
        $batch['srcPackages'][$source] ??= [
            'name'        => $source,
            'version'     => $state->source_version,
            'arch'        => (string)$state->architecture,
            'binaryNames' => [],
        ];
        $batch['srcPackages'][$source]['binaryNames'][] = $state->name;

        // A source package of architecture "all" takes the first real architecture of its binaries
        if ($batch['srcPackages'][$source]['arch'] === 'all') {
            $batch['srcPackages'][$source]['arch'] = (string)$state->architecture;
        }
    }

    /**
     * Unique name of a request, Vuls returns it in the report
     *
     * @param LinuxDistribution $distribution
     * @param string|null $kernelRelease
     * @param int $batch
     * @return string
     */
    private function serverName(LinuxDistribution $distribution, ?string $kernelRelease, int $batch): string {
        $name = 'oitc-' . $distribution->osName . '-' . $distribution->osVersion;
        if ($kernelRelease !== null) {
            $name .= '-kernel-' . $kernelRelease;
        }
        return $name . '-batch-' . $batch;
    }
}
