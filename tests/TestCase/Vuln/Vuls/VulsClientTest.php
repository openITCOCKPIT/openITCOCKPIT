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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use itnovum\openITCOCKPIT\Vuln\Vuls\ReleaseNotInFeedException;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsClient;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class VulsClientTest extends TestCase {

    private const REQUEST = [
        'serverName'    => 'test-1',
        'family'        => 'debian',
        'release'       => '13.7',
        'runningKernel' => ['release' => '', 'version' => ''],
        'packages'      => ['curl' => ['name' => 'curl', 'version' => '8.14.1-2+deb13u7', 'arch' => 'amd64']],
        'srcPackages'   => ['curl' => ['name' => 'curl', 'version' => '8.14.1-2+deb13u7', 'arch' => 'amd64', 'binaryNames' => ['curl']]],
    ];

    /** @var array Requests the client sent, filled by the history middleware */
    private array $history = [];

    /**
     * @param array $responses Responses or exceptions the mocked Vuls returns, in this order
     * @return VulsClient
     */
    private function client(array $responses): VulsClient {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new VulsClient(new Client([
            'handler'                   => $stack,
            'base_uri'                  => 'http://127.0.0.1:5515/',
            RequestOptions::HTTP_ERRORS => false,
        ]));
    }

    private function report(array $overrides = []): string {
        return json_encode($overrides + [
                'serverName'  => 'test-1',
                'family'      => 'debian',
                'release'     => '13.7',
                'scannedCves' => ['CVE-2026-1234' => ['cveID' => 'CVE-2026-1234']],
            ]);
    }

    public function testHealthy(): void {
        $this->assertTrue($this->client([new Response(200)])->isHealthy());
        $this->assertFalse($this->client([new Response(500)])->isHealthy());
        $this->assertFalse($this->client([new ConnectException('Connection refused', new Request('GET', '/health'))])->isHealthy());
    }

    public function testScanSendsTheRequestAsJson(): void {
        $this->client([new Response(200, [], $this->report())])->scan(self::REQUEST);

        /** @var Request $request */
        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/vuls', $request->getUri()->getPath());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(self::REQUEST, json_decode((string)$request->getBody(), true));
    }

    public function testScanReturnsTheReport(): void {
        $report = $this->client([new Response(200, [], $this->report())])->scan(self::REQUEST);

        $this->assertArrayHasKey('CVE-2026-1234', $report['scannedCves']);
    }

    public function testReportInAListIsUnwrapped(): void {
        $report = $this->client([new Response(200, [], '[' . $this->report() . ']')])->scan(self::REQUEST);

        $this->assertSame('test-1', $report['serverName']);
    }

    public function testUnknownReleaseIsNoOutage(): void {
        try {
            $this->client([new Response(503, [], 'Failed to detect: ecosystem not found')])->scan(['family' => 'opensuse.leap', 'release' => '16.1'] + self::REQUEST);
            $this->fail('ReleaseNotInFeedException expected');
        } catch (ReleaseNotInFeedException $e) {
            $this->assertSame('opensuse.leap', $e->family);
            $this->assertSame('16.1', $e->release);
        }
    }

    public function testErrorOfVuls(): void {
        $this->expectException(VulsException::class);
        $this->expectExceptionMessage('HTTP 500');

        $this->client([new Response(500, [], 'internal error')])->scan(self::REQUEST);
    }

    public function testVulsNotReachable(): void {
        $this->expectException(VulsException::class);
        $this->expectExceptionMessage('not reachable');

        $this->client([new ConnectException('Connection refused', new Request('POST', '/vuls'))])->scan(self::REQUEST);
    }

    public function testInvalidJson(): void {
        $this->expectException(VulsException::class);
        $this->expectExceptionMessage('no valid JSON');

        $this->client([new Response(200, [], '{"serverName": ')])->scan(self::REQUEST);
    }

    public function testReportOfAnotherSystemIsRejected(): void {
        $this->expectException(VulsException::class);
        $this->expectExceptionMessage('serverName');

        $this->client([new Response(200, [], $this->report(['serverName' => 'other']))])->scan(self::REQUEST);
    }

    public function testErrorsInsideTheReportAreRejected(): void {
        $this->expectException(VulsException::class);
        $this->expectExceptionMessage('Vuls reports errors');

        $this->client([new Response(200, [], $this->report(['errors' => ['failed to detect']]))])->scan(self::REQUEST);
    }

    public function testDefaultClientIgnoresTheProxyOfTheEnvironment(): void {
        putenv('HTTP_PROXY=http://proxy.example.org:3128');
        putenv('HTTPS_PROXY=http://proxy.example.org:3128');
        try {
            $vulsClient = new VulsClient();
            $client = (new ReflectionProperty(VulsClient::class, 'Client'))->getValue($vulsClient);

            $this->assertSame(['http' => '', 'https' => ''], $client->getConfig(RequestOptions::PROXY));
            $this->assertSame($vulsClient->getAddress(), (string)$client->getConfig('base_uri'));
        } finally {
            putenv('HTTP_PROXY');
            putenv('HTTPS_PROXY');
        }
    }
}
