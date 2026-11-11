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

use App\Model\Entity\CvePackageState;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxDistribution;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxRequestBuilder;
use PHPUnit\Framework\TestCase;

class LinuxRequestBuilderTest extends TestCase {

    private int $nextId = 1;

    private function state(string $name, string $version, string $arch = 'amd64', ?string $source = null, ?string $sourceVersion = null, ?string $module = null): CvePackageState {
        return new CvePackageState([
            'id'               => $this->nextId++,
            'name'             => $name,
            'version'          => $version,
            'architecture'     => $arch,
            'source_package'   => $source ?? $name,
            'source_version'   => $sourceVersion ?? $version,
            'modularity_label' => $module,
        ], ['guard' => false]);
    }

    public function testDistributionNames(): void {
        $this->assertSame('alma', LinuxDistribution::fromInventory('almalinux', '9.8')->family);
        $this->assertSame('redhat', LinuxDistribution::fromInventory('redhat', '9.6')->family);
        $this->assertSame('opensuse.leap', LinuxDistribution::fromInventory('opensuse-leap', '16.1')->family);
        $this->assertSame('tumbleweed', LinuxDistribution::fromInventory('opensuse-tumbleweed', '20261101')->getRelease());
        $this->assertTrue(LinuxDistribution::fromInventory('ubuntu', '24.04')->isDebianBased);
        $this->assertNull(LinuxDistribution::fromInventory('arch', 'rolling'));
        $this->assertNull(LinuxDistribution::fromInventory('debian', ''));
    }

    public function testReleasePackageOfSuse(): void {
        $leap16 = LinuxDistribution::fromInventory('opensuse-leap', '16.1');
        $this->assertSame('Leap-release', $leap16->getReleasePackageName());
        $this->assertTrue($leap16->isReleasePackage('Leap-release', '16.1-160100.1.1'));
        $this->assertFalse($leap16->isReleasePackage('Leap-release', '16.0-160000.1.1'), 'The version has to match the release');

        $this->assertSame('openSUSE-release', LinuxDistribution::fromInventory('opensuse-leap', '15.6')->getReleasePackageName());
        $this->assertSame('sles-release', LinuxDistribution::fromInventory('sles', '15.7')->getReleasePackageName());
        $this->assertNull(LinuxDistribution::fromInventory('debian', '13.7')->getReleasePackageName());
    }

    public function testDebianUsesSourcePackages(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('debian', '13.7'), null, [
            $this->state('curl', '8.14.1-2+deb13u7'),
            $this->state('libcurl4t64', '8.14.1-2+deb13u7', 'amd64', 'curl', '8.14.1-2+deb13u7'),
        ]);

        $this->assertCount(1, $plan->getRequests());
        $request = $plan->getRequests()[0]->toArray();
        $this->assertSame('debian', $request['family']);
        $this->assertSame(['curl', 'libcurl4t64'], array_keys((array)$request['packages']));
        $this->assertSame(['curl', 'libcurl4t64'], ((array)$request['srcPackages'])['curl']['binaryNames']);
        $this->assertSame(['release' => '', 'version' => ''], $request['runningKernel']);
    }

    public function testSamePackageInSeveralVersionsGoesToSeveralBatches(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('debian', '13.7'), null, [
            $this->state('curl', '8.14.1-2+deb13u7'),
            $this->state('curl', '8.14.1-2+deb13u2'),
            $this->state('bash', '5.2.37-2'),
        ]);

        $this->assertCount(2, $plan->getRequests());
        $this->assertSame(['curl', 'bash'], array_keys($plan->getRequests()[0]->packages));
        $this->assertSame(['curl'], array_keys($plan->getRequests()[1]->packages));
        $this->assertNotSame($plan->getRequests()[0]->serverName, $plan->getRequests()[1]->serverName);
    }

    public function testSourcePackageWithAnotherVersionGoesToTheNextBatch(): void {
        // Two binaries of the same source package, built from different source versions
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('ubuntu', '24.04'), null, [
            $this->state('libssl3t64', '3.0.13-0ubuntu3.6', 'amd64', 'openssl', '3.0.13-0ubuntu3.6'),
            $this->state('openssl', '3.0.13-0ubuntu3.5', 'amd64', 'openssl', '3.0.13-0ubuntu3.5'),
        ]);

        $this->assertCount(2, $plan->getRequests());
    }

    public function testArchitectureAllFitsEveryArchitecture(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('debian', '13.7'), null, [
            $this->state('python3-foo', '1.0-1', 'all', 'foo', '1.0-1'),
            $this->state('libfoo1', '1.0-1', 'amd64', 'foo', '1.0-1'),
        ]);

        $this->assertCount(1, $plan->getRequests());
        $this->assertSame('amd64', $plan->getRequests()[0]->srcPackages['foo']['arch']);
    }

    public function testRpmSplitsVersionAndRelease(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('almalinux', '9.8'), null, [
            $this->state('bash', '5.1.8-9.el9', 'x86_64'),
            $this->state('nodejs', '1:20.19.5-1.module_el9.6.0+1234', 'x86_64', null, null, 'nodejs:20:9060020250101:abcdef'),
            $this->state('broken', '1.0', 'x86_64'),
        ]);

        $packages = $plan->getRequests()[0]->packages;
        $this->assertSame(['name' => 'bash', 'version' => '5.1.8', 'release' => '9.el9', 'arch' => 'x86_64', 'modularitylabel' => ''], $packages['bash']);
        $this->assertSame('1:20.19.5', $packages['nodejs']['version'], 'The epoch stays in the version');
        $this->assertSame('nodejs:20:9060020250101:abcdef', $packages['nodejs']['modularitylabel']);
        $this->assertSame([], $plan->getRequests()[0]->srcPackages);
        $this->assertContains('version_without_release', $plan->getSkipped());
    }

    public function testKernelGroupNamesTheRunningKernel(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('ubuntu', '24.04'), '6.8.0-147-generic', [
            $this->state('linux-image-6.8.0-147-generic', '6.8.0-147.147', 'arm64', 'linux-signed', '6.8.0-147.147'),
        ]);

        $this->assertSame(['release' => '6.8.0-147-generic', 'version' => ''], $plan->getRequests()[0]->toArray()['runningKernel']);
        $this->assertStringContainsString('kernel-6.8.0-147-generic', $plan->getRequests()[0]->serverName);
    }

    public function testSuseNeedsTheReleasePackageInEveryBatch(): void {
        $release = $this->state('Leap-release', '16.1-160100.1.1', 'x86_64');
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('opensuse-leap', '16.1'), null, [
            $this->state('bash', '5.2.37-160100.2.1', 'x86_64'),
            $this->state('bash', '5.2.37-160100.1.1', 'x86_64'),
        ], [$release]);

        $this->assertCount(2, $plan->getRequests());
        foreach ($plan->getRequests() as $request) {
            $this->assertArrayHasKey('Leap-release', $request->packages);
            $this->assertSame($release->id, $request->stateIdsByPackage['Leap-release']);
        }
    }

    public function testSuseWithoutReleasePackageIsNotChecked(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('opensuse-leap', '16.1'), null, [
            $this->state('bash', '5.2.37-160100.2.1', 'x86_64'),
        ], [$this->state('Leap-release', '16.0-160000.1.1', 'x86_64')]);

        $this->assertSame([], $plan->getRequests());
        $this->assertSame(['suse_release_package_missing'], array_values($plan->getSkipped()));
    }

    public function testOnlyTheReleasePackageIsPending(): void {
        $release = $this->state('Leap-release', '16.1-160100.1.1', 'x86_64');
        $pending = new CvePackageState(['id' => $release->id, 'name' => $release->name, 'version' => $release->version, 'architecture' => 'x86_64'], ['guard' => false]);

        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('opensuse-leap', '16.1'), null, [$pending], [$release]);

        $this->assertCount(1, $plan->getRequests());
        $this->assertSame([$release->id], $plan->getRequests()[0]->getStateIds());
    }

    public function testEmptyListsAreEncodedAsObjects(): void {
        $plan = (new LinuxRequestBuilder())->build(LinuxDistribution::fromInventory('almalinux', '9.8'), null, [$this->state('bash', '5.1.8-9.el9', 'x86_64')]);

        $this->assertStringContainsString('"srcPackages":{}', json_encode($plan->getRequests()[0]->toArray()));
    }
}
