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

namespace App\Test\TestCase\Vuln\Vuls;

use itnovum\openITCOCKPIT\Vuln\Vuls\VulsReportReader;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsRequest;
use PHPUnit\Framework\TestCase;

class VulsReportReaderTest extends TestCase {

    private function request(): VulsRequest {
        return new VulsRequest('test-1', 'ubuntu', '24.04', '', [
            'curl'        => ['name' => 'curl', 'version' => '8.5.0-2ubuntu10.6', 'arch' => 'amd64'],
            'libcurl4t64' => ['name' => 'libcurl4t64', 'version' => '8.5.0-2ubuntu10.6', 'arch' => 'amd64'],
            'bash'        => ['name' => 'bash', 'version' => '5.2.21-2ubuntu4', 'arch' => 'amd64'],
        ], [], ['curl' => 11, 'libcurl4t64' => 12, 'bash' => 13]);
    }

    private function report(): array {
        return [
            'serverName'  => 'test-1',
            'scannedCves' => [
                'CVE-2026-1001' => [
                    'cveContents'      => [
                        'ubuntu_api' => [['summary' => 'Heap overflow in curl', 'severity' => 'high', 'published' => '2026-03-01T10:00:00Z', 'sourceLink' => 'https://ubuntu.com/security/CVE-2026-1001']],
                        'nvd'        => [['summary' => 'NVD text', 'cvss3Score' => 9.8, 'cvss3Vector' => 'CVSS:3.1/AV:N/AC:L', 'published' => '0001-01-01T00:00:00Z']],
                    ],
                    'affectedPackages' => [
                        ['name' => 'curl', 'fixedIn' => '8.5.0-2ubuntu10.7', 'fixState' => '', 'notFixedYet' => false],
                        ['name' => 'libcurl4t64', 'fixedIn' => '8.5.0-2ubuntu10.7'],
                    ],
                    'kevs'             => [['type' => 'cisa', 'date_added' => '2026-04-01T00:00:00Z'], ['type' => 'vulncheck']],
                    'exploits'         => [['exploitType' => 'OffensiveSecurity'], ['exploitType' => 'GitHub'], ['exploitType' => 'Trickest']],
                    'metasploits'      => [['name' => 'exploit/linux/curl']],
                ],
                'CVE-2026-1002' => [
                    'cveContents'      => ['nvd' => [['cvss40Score' => 5.3, 'cvss40Vector' => 'CVSS:4.0/AV:L']]],
                    'affectedPackages' => [['name' => 'unknown-package']],
                ],
            ],
        ];
    }

    public function testMasterData(): void {
        $cves = (new VulsReportReader())->read($this->report(), $this->request())['cves'];

        $this->assertSame([
            'summary'                => 'Heap overflow in curl',
            'published'              => '2026-03-01 10:00:00',
            'cvss_score'             => 9.8,
            'cvss_vector'            => 'CVSS:3.1/AV:N/AC:L',
            'cvss_source'            => 'nvd',
            'kev_cisa'               => true,
            'kev_cisa_added'         => '2026-04-01',
            'kev_vulncheck'          => true,
            'metasploit_count'       => 1,
            'verified_exploit_count' => 1,
            'poc_exploit_count'      => 2,
            'source_link'            => 'https://ubuntu.com/security/CVE-2026-1001',
        ], $cves['CVE-2026-1001']);
        $this->assertSame(5.3, $cves['CVE-2026-1002']['cvss_score'], 'CVSS v4 if there is no v3');
    }

    public function testMatchesPerPackageState(): void {
        $matches = (new VulsReportReader())->read($this->report(), $this->request())['matches'];

        $this->assertSame([
            'cve_id'          => 'CVE-2026-1001',
            'fixed_in'        => '8.5.0-2ubuntu10.7',
            'fix_state'       => null,
            'not_fixed_yet'   => false,
            'severity'        => 3,
            'severity_source' => 'ubuntu',
        ], $matches[11][0]);
        $this->assertCount(1, $matches[12]);
        $this->assertSame([], $matches[13], 'Package states without CVE get an empty list, so they count as checked');
    }

    public function testCveOfAPackageOutsideTheRequestIsUnassigned(): void {
        $result = (new VulsReportReader())->read($this->report(), $this->request());

        $this->assertSame(['CVE-2026-1002'], $result['unassigned']);
    }
}
