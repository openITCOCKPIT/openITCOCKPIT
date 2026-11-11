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

use itnovum\openITCOCKPIT\Vuln\Linux\RunningKernel;
use PHPUnit\Framework\TestCase;

class RunningKernelTest extends TestCase {

    public function testUnknownKernel(): void {
        $this->assertNull(RunningKernel::isRunning('', true, 'linux-image-6.8.0-147-generic', '6.8.0-147.147'));
        $this->assertNull(RunningKernel::isRunning('', false, 'kernel', '5.14.0-687.44.1.el9_8'));
    }

    public function testUbuntu(): void {
        $release = '6.8.0-147-generic';
        $this->assertTrue(RunningKernel::isRunning($release, true, 'linux-image-6.8.0-147-generic', '6.8.0-147.147'));
        $this->assertTrue(RunningKernel::isRunning($release, true, 'linux-headers-6.8.0-147', '6.8.0-147.147'), 'Same release without flavor');
        $this->assertTrue(RunningKernel::isRunning($release, true, 'linux-libc-dev', '6.8.0-147.147'), 'Source version of the running kernel');
        $this->assertFalse(RunningKernel::isRunning($release, true, 'linux-image-6.8.0-100-generic', '6.8.0-100.100'));
    }

    public function testDebian(): void {
        $release = '6.12.113+deb13-amd64';
        $this->assertTrue(RunningKernel::isRunning($release, true, 'linux-image-6.12.113+deb13-amd64', '6.12.113+1'));
        $this->assertTrue(RunningKernel::isRunning($release, true, 'linux-image-amd64', '6.12.113+1'), 'Meta package of the signed kernel');
        $this->assertTrue(RunningKernel::isRunning($release, true, 'linux-libc-dev', '6.12.113-1'));
        $this->assertFalse(RunningKernel::isRunning($release, true, 'linux-image-6.12.74+deb13-amd64', '6.12.74+1'));
    }

    public function testAlmaLinuxWithThreeKernels(): void {
        $release = '5.14.0-687.44.1.el9_8.x86_64';
        $this->assertTrue(RunningKernel::isRunning($release, false, 'kernel-core', '5.14.0-687.44.1.el9_8'));
        $this->assertFalse(RunningKernel::isRunning($release, false, 'kernel-core', '5.14.0-611.49.1.el9_7'));
        $this->assertFalse(RunningKernel::isRunning($release, false, 'kernel-core', '5.14.0-611.34.1.el9_7'));
    }

    public function testSuse(): void {
        $release = '6.12.0-160100.8-default';
        $this->assertTrue(RunningKernel::isRunning($release, false, 'kernel-default', '6.12.0-160100.8.1'));
        $this->assertFalse(RunningKernel::isRunning($release, false, 'kernel-default', '6.12.0-160100.7.1'));
        $this->assertFalse(RunningKernel::isRunning($release, false, 'kernel-default', '6.12.0-160100.80.1'), 'Not just a prefix of the version');
    }
}
