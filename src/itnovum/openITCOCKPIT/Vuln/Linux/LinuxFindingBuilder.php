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

use App\Model\Entity\CveHostCoverage;
use itnovum\openITCOCKPIT\Vuln\Evaluation\CoverageReason;
use itnovum\openITCOCKPIT\Vuln\Evaluation\HostEvaluation;
use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;
use itnovum\openITCOCKPIT\Vuln\Policy\PolicyVersion;

/**
 * Builds the findings of one Linux host from its installed packages and the results of Vuls.
 *
 * Vuls checks package states, which are shared by all hosts. This class adds what only the host knows:
 * which packages are installed, which kernel is running and which updates the package manager offers.
 *
 * One finding is one CVE in one source package in one version, e.g. CVE-2026-1234 in openssl 3.0.13-0ubuntu3.
 * The binary packages of a source package (libssl3, openssl) share the finding: an update of the source package
 * fixes all of them. A second installed version (e.g. a second kernel) is a finding of its own.
 */
final class LinuxFindingBuilder {

    /**
     * Debian and Ubuntu name the image of every kernel linux-image-<release>
     */
    private const DEBIAN_KERNEL_IMAGE = '/^linux-image-\d/';

    /**
     * @param LinuxDistribution $distribution
     * @param string $kernelRelease Running kernel, empty if unknown
     * @param string $feedDigest Digest of the current Vuls database
     * @param array<int, array> $installations Rows of PackagesLinuxHostsTable::getPackagesForCveCheck()
     * @param array<int, array> $statesById Linked package states with id, name, version, is_kernel, feed_digest, checked, error_message
     * @param array<int, array> $matchesByStateId CVEs per package state: cve_id (cves.id), fixed_in, fix_state, not_fixed_yet,
     *                                            severity, severity_source
     * @param int $foreignApps Windows or macOS applications in the inventory of this Linux host
     * @return HostEvaluation
     */
    public function build(
        LinuxDistribution $distribution,
        string            $kernelRelease,
        string            $feedDigest,
        array             $installations,
        array             $statesById,
        array             $matchesByStateId,
        int               $foreignApps = 0
    ): HostEvaluation {
        $fingerprint = $this->getFingerprint($distribution, $kernelRelease, $feedDigest, $installations, $statesById, $foreignApps);

        if (empty($installations)) {
            return HostEvaluation::notEvaluable(CoverageReason::EMPTY_INVENTORY, $fingerprint);
        }

        $reasons = [];
        $missingMetadata = [];
        $inactiveKernels = [];
        $evaluated = 0;
        $canResolve = true;
        $findings = [];

        foreach ($this->sortInstallations($installations) as $installation) {
            $name = (string)$installation['name'];
            $stateId = $installation['cve_package_state_id'];
            $state = $stateId === null ? null : ($statesById[(int)$stateId] ?? null);

            // Not linked: the package lacks the data Vuls needs (see CvePackageStatesTable::buildPackageState())
            if ($state === null) {
                $missingMetadata[] = $name;
                continue;
            }

            // The inventory changed after the last sync, the link belongs to the old version
            if ($state['name'] !== $name || $state['version'] !== (string)$installation['version']) {
                $reasons[CoverageReason::CHECK_PENDING] = true;
                $canResolve = false;
                continue;
            }

            $errorMessage = trim((string)($state['error_message'] ?? ''));

            // Never checked successfully: either it failed or the next run checks it
            if ($state['checked'] === null) {
                $reasons[$errorMessage === '' ? CoverageReason::CHECK_PENDING : CoverageReason::fromStateError($errorMessage)] = true;
                $canResolve = false;
                continue;
            }

            // Checked, but not with the current Vuls database: the old result is still the best we know
            if ($state['feed_digest'] !== $feedDigest) {
                $reasons[$errorMessage === '' ? CoverageReason::FEED_CHECK_PENDING : CoverageReason::fromStateError($errorMessage)] = true;
                $canResolve = false;
            }

            $evaluated++;

            if (!empty($installation['modularity_label'])) {
                $reasons[CoverageReason::MODULE_PACKAGES_UNVERIFIED] = true;
            }

            if ($distribution->isDebianBased && $this->isInactiveDebianKernelImage($name, $kernelRelease)) {
                $inactiveKernels[] = $name;
            }

            foreach ($matchesByStateId[(int)$state['id']] ?? [] as $match) {
                $this->addMatch($findings, $distribution, $kernelRelease, $installation, (bool)$state['is_kernel'], $match);
            }
        }

        if (!empty($missingMetadata)) {
            $reasons[CoverageReason::MISSING_SOURCE_METADATA] = true;
        }
        if ($kernelRelease === '') {
            $reasons[CoverageReason::RUNNING_KERNEL_UNKNOWN] = true;
        }
        if (!empty($inactiveKernels)) {
            $reasons[CoverageReason::INACTIVE_KERNELS_NOT_EVALUATED] = true;
        }
        if ($foreignApps > 0) {
            $reasons[CoverageReason::PLATFORM_CONFLICT] = true;
        }

        $reasons = array_keys($reasons);
        sort($reasons);

        return new HostEvaluation(
            status: $this->getStatus($evaluated, $reasons),
            findings: array_values($findings),
            reasons: $reasons,
            coverage: [
                CveHostCoverage::TYPE_MISSING_METADATA => $missingMetadata,
                CveHostCoverage::TYPE_INACTIVE_KERNEL  => $inactiveKernels,
            ],
            installations: count($installations),
            evaluatedInstallations: $evaluated,
            canResolve: $canResolve,
            fingerprint: $fingerprint
        );
    }

    /**
     * Adds one CVE of one installed binary package to the finding of its source package.
     *
     * If the binary packages of a source package report different values, the finding takes:
     * - the first known fixed version
     * - "not fixed yet" only if all binary packages say so
     * - an available update if at least one binary package has one
     * - the highest severity
     *
     * @param array<string, array> $findings
     * @param LinuxDistribution $distribution
     * @param string $kernelRelease
     * @param array $installation
     * @param bool $isKernel
     * @param array $match
     * @return void
     */
    private function addMatch(array &$findings, LinuxDistribution $distribution, string $kernelRelease, array $installation, bool $isKernel, array $match): void {
        [$componentName, $componentVersion] = $this->getComponent($installation);
        $key = $match['cve_id'] . '|' . $componentName . '|' . $componentVersion;

        if (!isset($findings[$key])) {
            $findings[$key] = [
                'cve_id'            => (int)$match['cve_id'],
                'component_type'    => 'package',
                'component_name'    => $componentName,
                'component_version' => $componentVersion,
                'is_kernel'         => $isKernel,
                'is_running'        => null,
                'fixed_in'          => null,
                'fix_state'         => null,
                'not_fixed_yet'     => (bool)$match['not_fixed_yet'],
                'update_available'  => false,
                'update_version'    => null,
                'severity'          => null,
                'severity_source'   => null,
            ];
        }
        $finding = &$findings[$key];

        if ($isKernel) {
            $isRunning = RunningKernel::isRunning($kernelRelease, $distribution->isDebianBased, (string)$installation['name'], $componentVersion);
            // One binary package of the running kernel makes the source package running
            if ($isRunning !== null) {
                $finding['is_running'] = ($finding['is_running'] ?? false) || $isRunning;
            }
        }

        $fixedIn = trim((string)($match['fixed_in'] ?? ''));
        $notFixedYet = (bool)$match['not_fixed_yet'];
        if ($finding['fixed_in'] === null && $fixedIn !== '' && !$notFixedYet) {
            $finding['fixed_in'] = mb_substr($fixedIn, 0, 255);
        }
        $finding['not_fixed_yet'] = $finding['not_fixed_yet'] && $notFixedYet;

        $fixState = trim((string)($match['fix_state'] ?? ''));
        if ($finding['fix_state'] === null && $fixState !== '') {
            $finding['fix_state'] = mb_substr($fixState, 0, 255);
        }

        if ((bool)$installation['needs_update']) {
            $finding['update_available'] = true;
            $availableVersion = trim((string)($installation['available_version'] ?? ''));
            if ($finding['update_version'] === null && $availableVersion !== '') {
                $finding['update_version'] = $availableVersion;
            }
        }

        if ($match['severity'] !== null && ($finding['severity'] === null || (int)$match['severity'] > $finding['severity'])) {
            $finding['severity'] = (int)$match['severity'];
            $finding['severity_source'] = $match['severity_source'];
        }
    }

    /**
     * The source package is the component, the binary package only if the inventory has no source package.
     *
     * @param array $installation
     * @return array{0: string, 1: string} Name and version
     */
    private function getComponent(array $installation): array {
        $name = trim((string)($installation['source_package'] ?? ''));
        $version = trim((string)($installation['source_version'] ?? ''));

        if ($name === '' || $version === '') {
            $name = (string)$installation['name'];
            $version = (string)$installation['version'];
        }

        return [mb_substr($name, 0, 255), mb_substr($version, 0, 255)];
    }

    /**
     * Debian and Ubuntu: an installed kernel image that is not the running kernel. Vuls does not check it.
     */
    private function isInactiveDebianKernelImage(string $name, string $kernelRelease): bool {
        if ($kernelRelease === '' || preg_match(self::DEBIAN_KERNEL_IMAGE, $name) !== 1) {
            return false;
        }

        return $name !== 'linux-image-' . $kernelRelease;
    }

    /**
     * @param int $evaluated Installations with a result of Vuls
     * @param string[] $reasons
     * @return EvaluationStatus
     */
    private function getStatus(int $evaluated, array $reasons): EvaluationStatus {
        if ($evaluated === 0) {
            // Nothing has a result. If only Vuls failed, the last successful result of the host stays visible.
            $onlyFailed = in_array(CoverageReason::CHECK_FAILED, $reasons, true)
                && !in_array(CoverageReason::RELEASE_NOT_IN_FEED, $reasons, true);
            return $onlyFailed ? EvaluationStatus::Failed : EvaluationStatus::NotEvaluable;
        }

        foreach ($reasons as $reason) {
            if (!CoverageReason::isInformational($reason)) {
                return EvaluationStatus::Partial;
            }
        }

        return EvaluationStatus::Complete;
    }

    /**
     * Same order on every run, so "the first fixed version" of addMatch() is always the same one
     *
     * @param array<int, array> $installations
     * @return array<int, array>
     */
    private function sortInstallations(array $installations): array {
        usort($installations, function (array $a, array $b): int {
            return [$a['name'], $a['version'], (string)$a['architecture'], (int)$a['id']]
                <=> [$b['name'], $b['version'], (string)$b['architecture'], (int)$b['id']];
        });

        return $installations;
    }

    /**
     * Everything the evaluation depends on. An unchanged fingerprint means the evaluation would give the same
     * findings, so the host can be skipped. Decisions and the date are not part of it, see HostEvaluator::reclassifyHost().
     *
     * @param LinuxDistribution $distribution
     * @param string $kernelRelease
     * @param string $feedDigest
     * @param array<int, array> $installations
     * @param array<int, array> $statesById
     * @param int $foreignApps
     * @return string
     */
    public function getFingerprint(LinuxDistribution $distribution, string $kernelRelease, string $feedDigest, array $installations, array $statesById, int $foreignApps): string {
        $rows = [];
        foreach ($installations as $installation) {
            $stateId = $installation['cve_package_state_id'] === null ? null : (int)$installation['cve_package_state_id'];
            $state = $statesById[$stateId] ?? null;

            $rows[] = implode('|', [
                $installation['id'],
                $installation['name'],
                $installation['version'],
                $installation['source_package'] ?? '',
                $installation['source_version'] ?? '',
                (int)(bool)$installation['needs_update'],
                $installation['available_version'] ?? '',
                $stateId ?? '',
                $state['feed_digest'] ?? '',
                $state === null || $state['checked'] === null ? '' : (string)$state['checked'],
                $state['error_message'] ?? '',
            ]);
        }
        sort($rows);

        return hash('sha256', implode("\n", [
            PolicyVersion::CURRENT,
            $feedDigest,
            $distribution->osName,
            $distribution->osVersion,
            $kernelRelease,
            $foreignApps,
            ...$rows,
        ]));
    }
}
