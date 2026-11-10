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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Talks to the Vuls server (vuls server -listen). Vuls checks the packages of one request and returns
 * a report with the CVEs it found, see https://vuls.io/docs/en/usage-server.html
 *
 * The client only sends and receives. Building the requests and reading the reports is done by others.
 */
class VulsClient {

    /**
     * Checking a large host (e.g. all packages of Ubuntu) can take more than a minute
     */
    private const SCAN_TIMEOUT_SECONDS = 300;

    private const CONNECT_TIMEOUT_SECONDS = 10;

    private const HEALTH_TIMEOUT_SECONDS = 10;

    private Client $Client;

    /**
     * Address of the Vuls server. Hardcoded for now, it can come from env() later, see the constructor.
     */
    private string $address = 'http://127.0.0.1:5515/';

    /**
     * @param Client|null $Client Only for tests, e.g. with a MockHandler. Without a client the default one is used
     */
    public function __construct(?Client $Client = null) {
        // This is the place to read the address from the environment later, e.g. env('OITC_VULS_ADDRESS')
        if ($Client !== null) {
            $this->Client = $Client;
        } else {
            $this->Client = new Client([
                'base_uri'                      => $this->address,
                RequestOptions::CONNECT_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
                // Without this Guzzle silently uses the proxy of the environment variables HTTP_PROXY / HTTPS_PROXY.
                // Vuls runs on the same machine, a proxy would only fail or see the inventory of the hosts.
                RequestOptions::PROXY           => [
                    'http'  => '',
                    'https' => '',
                ],
                // Status codes are checked by this class, Vuls reports errors as plain text
                RequestOptions::HTTP_ERRORS     => false,
            ]);
        }
    }

    /**
     * @return string e.g. http://127.0.0.1:5515/
     */
    public function getAddress(): string {
        return $this->address;
    }

    /**
     * Returns true if the Vuls server answers.
     *
     * @return bool
     */
    public function isHealthy(): bool {
        try {
            $response = $this->Client->get('/health', [
                RequestOptions::TIMEOUT => self::HEALTH_TIMEOUT_SECONDS,
            ]);
        } catch (GuzzleException) {
            return false;
        }

        return $response->getStatusCode() === 200;
    }

    /**
     * Sends one request to Vuls and returns its report.
     *
     * The request describes one system: Vuls family and release, the running kernel, packages and source packages.
     * Vuls answers with one report for this system, the CVEs are in "scannedCves".
     *
     * @param array $request e.g. ['serverName' => 'state-group-1', 'family' => 'debian', 'release' => '13.7',
     *                       'runningKernel' => ['release' => '', 'version' => ''], 'packages' => [...], 'srcPackages' => [...]]
     * @return array The report of Vuls
     * @throws ReleaseNotInFeedException If the Vuls database does not know the release
     * @throws VulsException If Vuls is not reachable, answers with an error or the report does not belong to the request
     */
    public function scan(array $request): array {
        try {
            $body = json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new VulsException('The request for Vuls can not be encoded: ' . $e->getMessage(), 0, $e);
        }

        try {
            $response = $this->Client->post('/vuls', [
                RequestOptions::BODY    => $body,
                RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
                RequestOptions::TIMEOUT => self::SCAN_TIMEOUT_SECONDS,
            ]);
        } catch (GuzzleException $e) {
            throw new VulsException(sprintf('Vuls is not reachable at %s: %s', $this->address, $e->getMessage()), 0, $e);
        }

        $this->assertSuccessful($response, $request);

        $report = $this->decode($response);
        $this->assertReportBelongsToRequest($report, $request);

        return $report;
    }

    /**
     * @param ResponseInterface $response
     * @param array $request
     * @return void
     */
    private function assertSuccessful(ResponseInterface $response, array $request): void {
        $status = $response->getStatusCode();
        if ($status === 200) {
            return;
        }

        $message = substr((string)$response->getBody(), 0, 500);

        // Vuls answers with 503 if the database has no data for this family and release
        if ($status === 503 && str_contains($message, 'ecosystem not found')) {
            throw new ReleaseNotInFeedException((string)($request['family'] ?? ''), (string)($request['release'] ?? ''));
        }

        throw new VulsException(sprintf('Vuls answered with HTTP %d: %s', $status, $message));
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    private function decode(ResponseInterface $response): array {
        try {
            $report = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new VulsException('The report of Vuls is no valid JSON: ' . $e->getMessage(), 0, $e);
        }

        // Some versions of Vuls return a list with one report
        if (is_array($report) && array_is_list($report) && count($report) === 1) {
            $report = $report[0];
        }

        if (!is_array($report)) {
            throw new VulsException('The report of Vuls is empty.');
        }

        return $report;
    }

    /**
     * A report for another system would store the CVEs at the wrong packages.
     * Vuls also reports some problems inside the report instead of the status code.
     *
     * @param array $report
     * @param array $request
     * @return void
     */
    private function assertReportBelongsToRequest(array $report, array $request): void {
        foreach (['serverName', 'family', 'release'] as $field) {
            if (($report[$field] ?? null) !== ($request[$field] ?? null)) {
                throw new VulsException(sprintf(
                    'The report of Vuls does not belong to the request: %s is "%s" instead of "%s".',
                    $field,
                    (string)($report[$field] ?? ''),
                    (string)($request[$field] ?? '')
                ));
            }
        }

        if (!empty($report['errors'])) {
            throw new VulsException('Vuls reports errors: ' . json_encode($report['errors']));
        }
    }
}
