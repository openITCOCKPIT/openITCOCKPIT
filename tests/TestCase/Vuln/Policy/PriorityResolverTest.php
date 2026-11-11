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

namespace App\Test\TestCase\Vuln\Policy;

use itnovum\openITCOCKPIT\Vuln\Policy\PriorityResolver;
use itnovum\openITCOCKPIT\Vuln\Policy\PrioritySource;
use itnovum\openITCOCKPIT\Vuln\Policy\Severity;
use PHPUnit\Framework\TestCase;

class PriorityResolverTest extends TestCase {

    public function testVendorPriorityWinsOverCvss(): void {
        $cve = ['cveContents' => ['ubuntu_api' => [['severity' => 'High']], 'nvd' => [['cvss3Score' => 9.9]]]];

        $priority = (new PriorityResolver())->resolve($cve, 'ubuntu');

        $this->assertSame(Severity::High, $priority->severity);
        $this->assertSame(PrioritySource::Ubuntu, $priority->source);
        $this->assertSame('High', $priority->vendorLabel);
    }

    public function testFallbackThroughAllCvssSourcesUpToRedHat(): void {
        $cve = ['cveContents' => [
            'debian_security_tracker' => [['cvss3Severity' => '']],
            'redhat_api'              => [['cvss3Score' => 0, 'cvss3Severity' => 'Important']],
        ]];

        $priority = (new PriorityResolver())->resolve($cve, 'debian');

        $this->assertSame(PrioritySource::RedHatCvss, $priority->source);
        $this->assertSame(Severity::High, $priority->severity);
        $this->assertNull($priority->cvssScore);
    }

    public function testDebianUnimportantWinsOverNvd(): void {
        $cve = ['cveContents' => ['debian_security_tracker' => [['cvss3Severity' => 'unimportant']], 'nvd' => [['cvss3Score' => 9.8]]]];

        $this->assertSame(Severity::None, (new PriorityResolver())->resolve($cve, 'debian')->severity);
    }

    public function testHighestErratumForAlma(): void {
        $cve = ['distroAdvisories' => [['severity' => 'Moderate'], ['severity' => 'Important']]];

        $priority = (new PriorityResolver())->resolve($cve, 'alma');

        $this->assertSame(Severity::High, $priority->severity);
        $this->assertSame(PrioritySource::Erratum, $priority->source);
    }

    public function testMicrosoft(): void {
        $cve = ['cveContents' => ['microsoft' => [['cvss3Severity' => 'CRITICAL']]]];

        $priority = (new PriorityResolver())->resolve($cve, 'windows');

        $this->assertSame(Severity::Critical, $priority->severity);
        $this->assertSame(PrioritySource::Microsoft, $priority->source);
    }

    public function testWithoutDataThePriorityIsUnknownNotZero(): void {
        $priority = (new PriorityResolver())->resolve([], 'macos');

        $this->assertNull($priority->level());
        $this->assertSame(PrioritySource::Unknown, $priority->source);
    }

    public function testCvssBands(): void {
        $priority = (new PriorityResolver())->resolve(['cveContents' => ['nvd' => [['cvss3Score' => 7.0]]]], 'cpe');

        $this->assertSame(Severity::High, $priority->severity);
        $this->assertSame(PrioritySource::Nvd, $priority->source);
        $this->assertSame(7.0, $priority->cvssScore);
        $this->assertFalse($priority->source->isVendor());
    }

    public function testCvss4IsPreferred(): void {
        $cve = ['cveContents' => ['nvd' => [['cvss40Score' => 9.3, 'cvss3Score' => 6.5]]]];

        $this->assertSame(Severity::Critical, (new PriorityResolver())->resolve($cve, 'cpe')->severity);
    }

    public function testRedHatAndCentOs(): void {
        $cve = ['cveContents' => ['redhat_api' => [['cvss3Severity' => 'Important']], 'nvd' => [['cvss3Score' => 9.8]]], 'distroAdvisories' => [['severity' => '']]];

        $priority = (new PriorityResolver())->resolve($cve, 'redhat');
        $this->assertSame(PrioritySource::RedHat, $priority->source);
        $this->assertSame(Severity::High, $priority->severity);
        $this->assertTrue($priority->source->isVendor());

        $this->assertSame(Severity::High, (new PriorityResolver())->resolve($cve, 'centos')->severity);
    }

    public function testRaspbianUsesDebian(): void {
        $cve = ['cveContents' => ['debian_security_tracker' => [['cvss3Severity' => 'low']]]];

        $priority = (new PriorityResolver())->resolve($cve, 'raspbian');

        $this->assertSame(Severity::Low, $priority->severity);
        $this->assertSame(PrioritySource::Debian, $priority->source);
    }

    public function testSuseWinsOverNvd(): void {
        $cve = ['cveContents' => ['suse' => [['cvss3Severity' => 'medium', 'cvss3Score' => 4.4]], 'nvd' => [['cvss3Score' => 9.8]]]];

        $priority = (new PriorityResolver())->resolve($cve, 'opensuse.leap');

        $this->assertSame(PrioritySource::Suse, $priority->source);
        $this->assertSame(Severity::Medium, $priority->severity);
    }
}
