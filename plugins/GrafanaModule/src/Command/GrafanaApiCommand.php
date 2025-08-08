<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use App\Model\Table\ProxiesTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use GrafanaModule\Model\Table\GrafanaConfigurationsTable;
use GrafanaModule\Model\Table\GrafanaDashboardsTable;
use GrafanaModule\Model\Table\GrafanaUserdashboardsTable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use itnovum\openITCOCKPIT\Grafana\GrafanaApiConfiguration;

/**
 * GrafanaDashboard command.
 */
class GrafanaApiCommand extends Command {

    /**
     * @var GrafanaApiConfiguration
     */
    private $GrafanaApiConfiguration;


    /**
     * @var Client
     */
    private $client;


    /**
     * @var ConsoleIo
     */
    private $io;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {

        $parser->addOption(
            'cleanup-dashboards',
            [
                'help'    => __d('oitc_console', 'Delete all dashboards from Grafana that exist in Grafana but not in openITCOCKPIT'),
                'boolean' => true,
                'default' => false,
            ]
        );

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     * @throws GuzzleException
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->io = $io;

        /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
        $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');

        $grafanaConfiguration = $GrafanaConfigurationsTable->getGrafanaConfiguration();
        $this->GrafanaApiConfiguration = GrafanaApiConfiguration::fromArray($grafanaConfiguration);
        $hasGrafanaConfig = $grafanaConfiguration['api_url'] !== '';

        if (!$hasGrafanaConfig) {
            throw new \RuntimeException('No Grafana configuration found');
        }

        if ($args->getOption('cleanup-dashboards')) {
            $this->cleanupDashboards();
            return;
        }


        $this->displayHelp($this->getOptionParser(), $args, $io);


    }

    private function cleanupDashboards() {
        /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
        $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');


        /** @var ProxiesTable $ProxiesTable */
        $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
        /** @var GrafanaDashboardsTable $GrafanaDashboardsTable */
        $GrafanaDashboardsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaDashboards');

        /** @var GrafanaUserdashboardsTable $GrafanaUserdashboardsTable */
        $GrafanaUserdashboardsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaUserdashboards');

        $this->io->out('Check Connection to Grafana');
        $this->client = $GrafanaConfigurationsTable->testConnection($this->GrafanaApiConfiguration, $ProxiesTable->getSettings());

        if ($this->client instanceof Client) {
            $this->io->success('Connection check successful');
            $allGrafanaDashboards = $this->getAllGrafanaDashboards();

            $this->io->info('Found ' . sizeof($allGrafanaDashboards) . ' dashboards in Grafana');

            $existingDashboardUuids = Hash::extract($GrafanaDashboardsTable->getDashboardUuids(), '{n}.grafana_uid');
            $existingUserDashboardUuids = Hash::extract($GrafanaUserdashboardsTable->getDashboardUuids(), '{n}.grafana_uid');

            $existingDashboardUuids = array_merge($existingDashboardUuids, $existingUserDashboardUuids);

            if (!empty($allGrafanaDashboards)) {
                $this->io->out('Start cleanup for Grafana Dashboards:');
                $grafanaDashboardsUuids = Hash::extract($allGrafanaDashboards, '{n}.uid');
                $dashboardsToDelete = array_diff($grafanaDashboardsUuids, $existingDashboardUuids);
                if (!empty($dashboardsToDelete)) {
                    $this->deleteOrphanedDashboards($dashboardsToDelete);
                }
            }
        } else {
            Log::error('GrafanaDashboardCommand: ' . $this->client);
            Log::error('GrafanaDashboardCommand: Connection check failed');
        }
        $this->io->out('Done');
    }

    private function getAllGrafanaDashboards(): array {
        $allDashboards = [];
        $page = 1;
        $loadMore = true;

        do {
            try {

                $request = new Request('GET', $this->GrafanaApiConfiguration->getApiUrl() . '/search');
                $response = $this->client->send($request, [
                    'query' => [
                        'limit' => 500,
                        'page'  => $page,
                        'type'  => 'dash-db',
                    ],
                ]);
            } catch (BadResponseException $e) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                $this->io->error($responseBody);
            }

            if ($response->getStatusCode() == 200) {
                $body = $response->getBody();
                $dashboards = json_decode($body->getContents());

                if (empty($dashboards)) {
                    // No more dashboards
                    $loadMore = false;
                    break;
                }

                foreach ($dashboards as $dashboard) {
                    $allDashboards[] = $dashboard;
                }

                $page++;
            }
        } while ($loadMore);

        return $allDashboards;
    }

    /**
     * @param array $orphanedDashboardUuids
     * @return void
     * @throws GuzzleException
     */
    private function deleteOrphanedDashboards(array $orphanedDashboardUuids = []): void {
        try {
            if (empty($orphanedDashboardUuids)) {
                throw new \Exception('No orphaned dashboards given');
            }

            foreach ($orphanedDashboardUuids as $grafanaUid) {
                //New grafana >= 8.0
                $start = microtime(true);
                $request = new Request('DELETE', sprintf('%s/dashboards/uid/%s',
                        $this->GrafanaApiConfiguration->getApiUrl(),
                        $grafanaUid
                    )
                );
                try {
                    $response = $this->client->send($request);
                    $end = microtime(true);

                    if ($response->getStatusCode() == 200) {
                        $this->io->success('Dashboard with uid' . $grafanaUid . ' deleted! [Took: ' . ($end - $start) . ' seconds]');
                    }
                } catch (BadResponseException $e) {
                    $response = $e->getResponse();
                    $responseBody = $response->getBody()->getContents();
                    $this->io->error($responseBody);
                }
            }
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }
}
