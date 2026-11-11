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

namespace App\Test\TestCase\Vuln\Linux;

use App\Model\Entity\CveHostCoverage;
use itnovum\openITCOCKPIT\Vuln\Evaluation\CoverageReason;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxDistribution;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxFindingBuilder;
use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;
use PHPUnit\Framework\TestCase;

class LinuxFindingBuilderTest extends TestCase {

    private const FEED = 'vulsdb:current';

    private int $nextId = 1;

    /** @var array<int, array> Package states of the test, indexed by id */
    private array $states = [];

    /** @var array<int, array> CVEs per package state */
    private array $matches = [];

    /**
     * An installed package with its package state. Without $checked the package state is checked with the current feed.
     */
    private function installation(string $name, string $version, ?string $source = null, ?string $sourceVersion = null, array $options = []): array {
        $id = $this->nextId++;

        $stateId = null;
        if (($options['linked'] ?? true) === true) {
            $stateId = 1000 + $id;
            $this->states[$stateId] = [
                'id'            => $stateId,
                'name'          => $name,
                'version'       => $options['state_version'] ?? $version,
                'is_kernel'     => $options['is_kernel'] ?? false,
                'feed_digest'   => array_key_exists('feed_digest', $options) ? $options['feed_digest'] : self::FEED,
                'checked'       => array_key_exists('checked', $options) ? $options['checked'] : '2026-11-11 08:00:00',
                'error_message' => $options['error_message'] ?? null,
            ];
        }

        return [
            'id'                   => $id,
            'cve_package_state_id' => $stateId,
            'name'                 => $name,
            'version'              => $version,
            'architecture'         => 'amd64',
            'source_package'       => $source,
            'source_version'       => $sourceVersion,
            'modularity_label'     => $options['modularity_label'] ?? null,
            'needs_update'         => $options['needs_update'] ?? 0,
            'available_version'    => $options['available_version'] ?? $version,
        ];
    }

    private function match(array $installation, int $cveId, array $values = []): void {
        $this->matches[$installation['cve_package_state_id']][] = $values + [
                'cve_id'          => $cveId,
                'fixed_in'        => null,
                'fix_state'       => null,
                'not_fixed_yet'   => false,
                'severity'        => 2,
                'severity_source' => 'ubuntu',
            ];
    }

    private function build(string $osName, string $osVersion, string $kernelRelease, array $installations, int $foreignApps = 0) {
        return (new LinuxFindingBuilder())->build(
            LinuxDistribution::fromInventory($osName, $osVersion),
            $kernelRelease,
            self::FEED,
            $installations,
            $this->states,
            $this->matches,
            $foreignApps
        );
    }

    public function testBinaryPackagesShareTheFindingOfTheirSourcePackage(): void {
        $openssl = $this->installation('openssl', '3.0.13-0ubuntu3.5', 'openssl', '3.0.13-0ubuntu3.5');
        $libssl = $this->installation('libssl3t64', '3.0.13-0ubuntu3.5', 'openssl', '3.0.13-0ubuntu3.5', [
            'needs_update'      => 1,
            'available_version' => '3.0.13-0ubuntu3.6',
        ]);
        $this->match($openssl, 7, ['not_fixed_yet' => true, 'severity' => 2]);
        $this->match($libssl, 7, ['fixed_in' => '3.0.13-0ubuntu3.6', 'severity' => 3, 'severity_source' => 'ubuntu']);

        $evaluation = $this->build('ubuntu', '24.04', '6.8.0-147-generic', [$openssl, $libssl]);

        $this->assertSame(EvaluationStatus::Complete, $evaluation->status);
        $this->assertCount(1, $evaluation->findings);
        $finding = $evaluation->findings[0];
        $this->assertSame('openssl', $finding['component_name']);
        $this->assertSame('3.0.13-0ubuntu3.5', $finding['component_version']);
        $this->assertSame('3.0.13-0ubuntu3.6', $finding['fixed_in']);
        $this->assertFalse($finding['not_fixed_yet'], 'Only "not fixed yet" if all binary packages say so');
        $this->assertTrue($finding['update_available']);
        $this->assertSame('3.0.13-0ubuntu3.6', $finding['update_version']);
        $this->assertSame(3, $finding['severity'], 'The highest severity counts');
        $this->assertFalse($finding['is_kernel']);
        $this->assertNull($finding['is_running']);
        $this->assertSame(2, $evaluation->evaluatedInstallations);
        $this->assertTrue($evaluation->canResolve);
    }

    public function testEveryInstalledKernelIsAFindingOfItsOwn(): void {
        $running = $this->installation('kernel-core', '5.14.0-687.44.1.el9_8', 'kernel', '5.14.0-687.44.1.el9_8', ['is_kernel' => true]);
        $old = $this->installation('kernel-core', '5.14.0-611.49.1.el9_7', 'kernel', '5.14.0-611.49.1.el9_7', ['is_kernel' => true]);
        $this->match($running, 1);
        $this->match($old, 1);

        $evaluation = $this->build('almalinux', '9.8', '5.14.0-687.44.1.el9_8.x86_64', [$running, $old]);

        $this->assertCount(2, $evaluation->findings);
        $byVersion = array_column($evaluation->findings, 'is_running', 'component_version');
        $this->assertTrue($byVersion['5.14.0-687.44.1.el9_8']);
        $this->assertFalse($byVersion['5.14.0-611.49.1.el9_7']);
    }

    public function testUnknownRunningKernelMakesTheEvaluationPartial(): void {
        $kernel = $this->installation('kernel-core', '5.14.0-687.44.1.el9_8', 'kernel', '5.14.0-687.44.1.el9_8', ['is_kernel' => true]);
        $this->match($kernel, 1);

        $evaluation = $this->build('almalinux', '9.8', '', [$kernel]);

        $this->assertSame(EvaluationStatus::Partial, $evaluation->status);
        $this->assertContains(CoverageReason::RUNNING_KERNEL_UNKNOWN, $evaluation->reasons);
        $this->assertNull($evaluation->findings[0]['is_running'], 'Unknown, so the policy treats it as running');
    }

    public function testPackagesWithoutSourceAreListedAndMakeTheEvaluationPartial(): void {
        $curl = $this->installation('curl', '8.14.1-2+deb13u7', 'curl', '8.14.1-2+deb13u7');
        $legacy = $this->installation('legacy-tool', '1.0', null, null, ['linked' => false]);

        $evaluation = $this->build('debian', '13.7', '6.12.113+deb13-amd64', [$curl, $legacy]);

        $this->assertSame(EvaluationStatus::Partial, $evaluation->status);
        $this->assertSame([CoverageReason::MISSING_SOURCE_METADATA], $evaluation->reasons);
        $this->assertSame(['legacy-tool'], $evaluation->coverage[CveHostCoverage::TYPE_MISSING_METADATA]);
        $this->assertSame(2, $evaluation->installations);
        $this->assertSame(1, $evaluation->evaluatedInstallations);
        $this->assertTrue($evaluation->canResolve, 'Packages that can never be checked do not block resolving');
    }

    public function testReleaseUnknownToVulsIsNotEvaluable(): void {
        $package = $this->installation('Leap-release', '16.1-160100.1.1', 'Leap-release', '16.1-160100.1.1', [
            'checked'       => null,
            'feed_digest'   => null,
            'error_message' => CoverageReason::RELEASE_NOT_IN_FEED,
        ]);

        $evaluation = $this->build('opensuse-leap', '16.1', '6.12.0-160100.8-default', [$package]);

        $this->assertSame(EvaluationStatus::NotEvaluable, $evaluation->status);
        $this->assertContains(CoverageReason::RELEASE_NOT_IN_FEED, $evaluation->reasons);
        $this->assertFalse($evaluation->canResolve);
    }

    public function testOnlyFailedChecksKeepTheLastResult(): void {
        $package = $this->installation('curl', '8.14.1-2+deb13u7', 'curl', '8.14.1-2+deb13u7', [
            'checked'       => null,
            'feed_digest'   => null,
            'error_message' => 'Vuls answered with HTTP 500',
        ]);

        $evaluation = $this->build('debian', '13.7', '6.12.113+deb13-amd64', [$package]);

        $this->assertSame(EvaluationStatus::Failed, $evaluation->status);
        $this->assertSame([CoverageReason::CHECK_FAILED], $evaluation->reasons);
    }

    public function testResultOfAnOlderFeedIsUsedButResolvesNothing(): void {
        $curl = $this->installation('curl', '8.14.1-2+deb13u7', 'curl', '8.14.1-2+deb13u7', ['feed_digest' => 'vulsdb:old']);
        $this->match($curl, 3);

        $evaluation = $this->build('debian', '13.7', '6.12.113+deb13-amd64', [$curl]);

        $this->assertSame(EvaluationStatus::Partial, $evaluation->status);
        $this->assertSame([CoverageReason::FEED_CHECK_PENDING], $evaluation->reasons);
        $this->assertCount(1, $evaluation->findings);
        $this->assertFalse($evaluation->canResolve);
    }

    public function testInventoryChangedAfterTheSync(): void {
        $curl = $this->installation('curl', '8.14.1-2+deb13u8', 'curl', '8.14.1-2+deb13u8', ['state_version' => '8.14.1-2+deb13u7']);
        $this->match($curl, 3);

        $evaluation = $this->build('debian', '13.7', '6.12.113+deb13-amd64', [$curl]);

        $this->assertSame([], $evaluation->findings, 'The result belongs to the old version');
        $this->assertSame([CoverageReason::CHECK_PENDING], $evaluation->reasons);
        $this->assertFalse($evaluation->canResolve);
    }

    public function testEmptyInventory(): void {
        $evaluation = $this->build('debian', '13.7', '6.12.113+deb13-amd64', []);

        $this->assertSame(EvaluationStatus::NotEvaluable, $evaluation->status);
        $this->assertSame([CoverageReason::EMPTY_INVENTORY], $evaluation->reasons);
    }

    public function testInformationalReasonsKeepTheEvaluationComplete(): void {
        $running = $this->installation('linux-image-6.12.113+deb13-amd64', '6.12.113-1', 'linux-signed-amd64', '6.12.113+1', ['is_kernel' => true]);
        $old = $this->installation('linux-image-6.12.74+deb13-amd64', '6.12.74-1', 'linux-signed-amd64', '6.12.74+1', ['is_kernel' => true]);

        $evaluation = $this->build('debian', '13.7', '6.12.113+deb13-amd64', [$running, $old], foreignApps: 3);

        $this->assertSame(EvaluationStatus::Complete, $evaluation->status);
        $this->assertSame([CoverageReason::INACTIVE_KERNELS_NOT_EVALUATED, CoverageReason::PLATFORM_CONFLICT], $evaluation->reasons);
        $this->assertSame(['linux-image-6.12.74+deb13-amd64'], $evaluation->coverage[CveHostCoverage::TYPE_INACTIVE_KERNEL]);
    }

    public function testDistributionsWithOnlyFixedCvesInTheFeedSaySo(): void {
        $alma = $this->installation('openssl-libs', '1:3.5.8-2.el9_8', 'openssl', '3.5.8-2.el9_8');
        $evaluation = $this->build('almalinux', '9.8', '5.14.0-687.44.1.el9_8.x86_64', [$alma]);
        $this->assertSame(EvaluationStatus::Complete, $evaluation->status, 'Only informs');
        $this->assertSame([CoverageReason::UNFIXED_CVES_NOT_IN_FEED], $evaluation->reasons);

        $rhel = $this->installation('openssl-libs', '1:3.5.8-2.el9_8', 'openssl', '3.5.8-2.el9_8');
        $this->assertSame([], $this->build('redhat', '9.8', '5.14.0-687.44.1.el9_8.x86_64', [$rhel])->reasons);

        $debian = $this->installation('openssl', '3.5.7-1~deb13u3', 'openssl', '3.5.7-1~deb13u3');
        $this->assertSame([], $this->build('debian', '13.7', '6.12.113+deb13-amd64', [$debian])->reasons);
    }

    public function testFingerprintChangesWithEverythingTheEvaluationDependsOn(): void {
        $curl = $this->installation('curl', '8.14.1-2+deb13u7', 'curl', '8.14.1-2+deb13u7');
        $builder = new LinuxFindingBuilder();
        $debian = LinuxDistribution::fromInventory('debian', '13.7');
        $fingerprint = fn(array $installation, string $kernel = '6.12.113+deb13-amd64', string $feed = self::FEED) => $builder->getFingerprint($debian, $kernel, $feed, [$installation], $this->states, 0);

        $this->assertSame($fingerprint($curl), $fingerprint($curl), 'Stable');
        $this->assertNotSame($fingerprint($curl), $fingerprint(['needs_update' => 1] + $curl), 'An update became available');
        $this->assertNotSame($fingerprint($curl), $fingerprint($curl, '6.12.120+deb13-amd64'), 'Reboot into another kernel');
        $this->assertNotSame($fingerprint($curl), $fingerprint($curl, feed: 'vulsdb:new'), 'New Vuls database');

        $before = $fingerprint($curl);
        $this->states[$curl['cve_package_state_id']]['checked'] = '2026-11-12 08:00:00';
        $this->assertNotSame($before, $fingerprint($curl), 'Checked again');
    }
}
