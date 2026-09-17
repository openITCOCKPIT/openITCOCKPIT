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

namespace GrafanaModule\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use GrafanaModule\Model\Table\GrafanaConfigurationsTable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * ServiceAccount command.
 *
 * Generated via:
 * oitc bake command -p GrafanaModule ServiceAccount
 *
 * Execute via:
 * oitc GrafanaModule.service_account
 */
class ServiceAccountCommand extends Command {

    /**
     * @var string
     */
    private $grafanaAdminPasswordFile = '/opt/openitc/etc/grafana/admin_password';

    /**
     * Older versions of Grafana used API Keys, today this feature is deprecated
     * and got replaced by Service Accounts and Service Account Tokens.
     *
     * https://grafana.com/docs/grafana/latest/administration/service-accounts/migrate-api-keys/
     * https://grafana.com/docs/grafana/latest/administration/service-accounts/
     *
     * For legacy and backwards compatibility reasons, we call the file "api_key", even if it holds a service account token.
     *
     * @var string
     */
    private $grafanaApiKeyFile = '/opt/openitc/etc/grafana/api_key';

    /**
     * @var string
     */
    private $grafanaServiceAccountFile = '/opt/openitc/etc/grafana/service_account';

    /**
     * @var string
     */
    private $grafanaAdminUser = 'admin';

    /**
     * @var string
     */
    private $grafanaAdminPassword = '';

    /*
     * Name for the openITCOCKPIT Service Account in Grafana
     * https://grafana.com/docs/grafana/latest/developers/http_api/serviceaccount/
     * https://grafana.com/docs/grafana/latest/administration/service-accounts/
     * @var string
     */
    private $serviceAccountName = 'openITCOCKPIT';

    /**
     * @var int
     */
    private $serviceAccountId = 0;

    /**
     * @var string
     */
    private $grafanaUrl = 'http://127.0.0.1:3033';

    /**
     * @var string
     */
    private $grafanHostname = 'grafana.docker';

    /**
     * @var ConsoleIo
     */
    private $io;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('grafana-hostname', [
            'help'    => 'Hostname of the Grafana instance, required to avoid any proxy issues',
            'default' => 'grafana.docker', // $OITC_GRAFANA_HOSTNAME in Docker
        ]);

        $parser->addOption('grafana-url', [
            'help'    => 'Base URL to the Grafana API',
            'default' => 'http://127.0.0.1:3033', // $OITC_GRAFANA_URL in Docker
        ]);

        // Graphite
        $parser->addOption('graphite-web-host', [
            'help'    => 'IP-Address or hostname of the Graphite Web instance',
            'default' => 'graphite-web', // $OITC_GRAPHITE_WEB_ADDRESS in Docker
        ]);
        $parser->addOption('graphite-web-port', [
            'help'    => 'Port or hostname of the Graphite Web instance',
            'default' => '8080', // $OITC_GRAPHITE_WEB_PORT in Docker
        ]);

        // VictoriaMetrics
        // Notice! Its "victoriametrics" for traditional setups and "victoria-metrics" in docker
        $parser->addOption('victoria-metrics-host', [
            'help'    => 'IP-Address or hostname of the VictoriaMetrics instance',
            'default' => 'victoriametrics', // $VICTORIA_METRICS_HOST in Docker
        ]);
        $parser->addOption('victoria-metrics-port', [
            'help'    => 'Port or hostname of the VictoriaMetrics instance',
            'default' => '8428', // $VICTORIA_METRICS_PORT in Docker
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->io = $io;

        if (!file_exists($this->grafanaAdminPasswordFile)) {
            $io->error(sprintf(
                'Grafana admin password file "%s" not found',
                $this->grafanaAdminPasswordFile
            ));
            return 1;
        }

        $this->grafanaUrl = $args->getOption('grafana-url');

        $this->grafanaAdminPassword = trim(file_get_contents($this->grafanaAdminPasswordFile));

        $isGrafanaOnline = $this->waitForGrafanaToBeAvailable();
        if (!$isGrafanaOnline) {
            return 1;
        }

        $this->io->info('Checking if openITCOCKPIT Service Account exists in Grafana...');
        $serviceAccountExists = $this->checkIfServiceAccountExists();
        if ($serviceAccountExists) {
            $this->io->success('Service Account already exists.');
        } else {
            $this->io->info('Service Account does not exist. Creating it...');
            $this->createServiceAccount();
        }

        if ($this->serviceAccountId === 0) {
            $this->io->error('Service Account not found.');
            return 1;
        }

        $tokenExists = $this->checkIfTokenExists();

        if (!$tokenExists) {
            if ($this->createNewToken($args->getOption('grafana-hostname'))) {
                $this->io->success('Service Account Token created successfully.');
            } else {
                $this->io->error('Error creating Service Account Token.');
                return 1;
            }
        }

        $this->ensureGraphiteDatasourceExists(
            $args->getOption('graphite-web-host'),
            $args->getOption('graphite-web-port')
        );

        $this->ensurePrometheusDatasourceExists(
            $args->getOption('victoria-metrics-host'),
            $args->getOption('victoria-metrics-port')
        );

    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function waitForGrafanaToBeAvailable(): bool {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        $counter = 1;

        do {
            $this->io->info("Try to connect to Grafana API... (" . $counter . " / 45)");

            try {
                $response = $Client->get($this->grafanaUrl . '/api/health');
                if ($response->getStatusCode() === 200) {
                    $this->io->success('Grafana online.');
                    return true;
                }

            } catch (\Exception $e) {
                $this->io->info("Grafana API connection error: " . $e->getMessage());
            }

            $counter++;
            sleep(1);
        } while ($counter < 45);

        $this->io->error('Grafana not reachable');
        return false;
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function checkIfServiceAccountExists(): bool {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        $response = $Client->get($this->grafanaUrl . '/api/serviceaccounts/search', [
            'query' => [
                'perpage' => 10000,
                'page'    => 1,
                'query'   => $this->serviceAccountName
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $response = json_decode($response->getBody()->getContents(), true);
            if (isset($response['totalCount']) && $response['totalCount'] == 0) {
                // No account found, create it
                return false;
            }

            if (isset($response['serviceAccounts']) && is_array($response['serviceAccounts'])) {
                foreach ($response['serviceAccounts'] as $serviceAccount) {
                    if ($serviceAccount['name'] === $this->serviceAccountName) {
                        $this->serviceAccountId = $serviceAccount['id'];
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function createServiceAccount(): bool {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        try {
            $response = $Client->post($this->grafanaUrl . '/api/serviceaccounts', [
                'json' => [
                    'name'       => $this->serviceAccountName,
                    'role'       => 'Editor',
                    'isDisabled' => false
                ]
            ]);


            if ($response->getStatusCode() === 201) {
                // New service account created
                $this->io->success('Service Account created successfully.');

                $response = $response->getBody()->getContents();

                file_put_contents($this->grafanaServiceAccountFile, $response);

                $response = json_decode($response, true);
                $this->serviceAccountId = $response['id'];

                return true;
            }

        } catch (\Exception $e) {
            $this->io->error('Error creating service account: ' . $e->getMessage());
        }

        return false;
    }

    private function checkIfTokenExists(): bool {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        try {

            $response = $Client->get($this->grafanaUrl . '/api/serviceaccounts/' . $this->serviceAccountId . '/tokens');

            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody()->getContents(), true);
                if (empty($response)) {
                    // Grafana returns an empty array if no token exists
                    return false;
                }

                return true;
            }

        } catch (\Exception $e) {
            $this->io->error('Error creating service account: ' . $e->getMessage());
        }

        return false;
    }

    private function createNewToken(string $grafanaHostname): bool {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        try {
            $response = $Client->post($this->grafanaUrl . '/api/serviceaccounts/' . $this->serviceAccountId . '/tokens', [
                'json' => [
                    'name'          => 'openITCOCKPIT-Token',
                    'secondsToLive' => 0, // Never expire
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                // New service account created

                $response = json_decode($response->getBody()->getContents(), true);
                file_put_contents($this->grafanaApiKeyFile, $response['key']);

                // Store the new token into the openITCOCKPIT database
                /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
                $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');
                $GrafanaConfigurationsTable->saveGrafanaConfigurationForSetupAndUpdate($grafanaHostname, $response['key']);

                return true;
            }

        } catch (\Exception $e) {
            $this->io->error('Error creating service account token: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param string $graphiteWebHost
     * @param string $graphiteWebPort
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function ensureGraphiteDatasourceExists(string $graphiteWebHost, string $graphiteWebPort) {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        try {
            $this->io->info("Check if Graphite Datasource exists in Grafana");

            $response = $Client->get($this->grafanaUrl . '/api/datasources/name/Graphite');

        } catch (ClientException $e) {
            if ($e->getCode() == '404') {
                $this->io->info("Create Graphite as default Datasource for Grafana");

                $response = $Client->post($this->grafanaUrl . '/api/datasources', [
                    'json' => [
                        "name"      => "Graphite",
                        "type"      => "graphite",
                        "url"       => sprintf("http://%s:%s", $graphiteWebHost, $graphiteWebPort),
                        "access"    => "proxy",
                        "basicAuth" => false,
                        "isDefault" => true,
                        "jsonData"  => [
                            "graphiteVersion" => 1.1
                        ]
                    ]
                ]);
            }
        } catch (\Exception $e) {
            $this->io->error('Error checking Graphite datasource: ' . $e->getMessage());
            return false;
        }

        $this->io->success('Ok: Graphite datasource exists.');
        return true;
    }

    /**
     * @param string $victoriaMetricsHost
     * @param string $victoriaMetricsPort
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function ensurePrometheusDatasourceExists(string $victoriaMetricsHost, string $victoriaMetricsPort): bool {
        $options = $this->getClientOptions(true);
        $Client = new Client($options);

        try {
            $this->io->info("Check if Prometheus/VictoriaMetrics Datasource exists in Grafana");

            $response = $Client->get($this->grafanaUrl . '/api/datasources/name/Prometheus');


        } catch (ClientException $e) {
            if ($e->getCode() == '404') {
                $this->io->info("Create Prometheus/VictoriaMetrics Datasource for Grafana");

                $response = $Client->post($this->grafanaUrl . '/api/datasources', [
                    'json' => [
                        "name"      => "Prometheus",
                        "type"      => "prometheus",
                        "url"       => sprintf("http://%s:%s", $victoriaMetricsHost, $victoriaMetricsPort),
                        "access"    => "proxy",
                        "basicAuth" => false,
                        "isDefault" => false,
                        "jsonData"  => new \stdClass() // Empty object "{}"
                    ]
                ]);
            }

        } catch (\Exception $e) {
            $this->io->error('Error checking Prometheus/VictoriaMetrics datasource: ' . $e->getMessage());
            return false;
        }

        $this->io->success('Ok: Prometheus/VictoriaMetrics datasource exists.');
        return true;
    }

    private function getClientOptions(bool $useBasicAuth = true): array {
        $options = [
            'headers'         => [
                'Content-Type' => 'application/json'
            ],

            // Disable SSL verification - but Grafana should be accessed via HTTP anyway
            'verify'          => false,
            'connect_timeout' => 2,

            // Disable HTTP proxy
            'proxy'           => [
                'http'  => '',
                'https' => ''
            ]
        ];

        if ($useBasicAuth) {
            $options['auth'] = [
                $this->grafanaAdminUser,
                $this->grafanaAdminPassword
            ];
        }

        return $options;
    }
}
