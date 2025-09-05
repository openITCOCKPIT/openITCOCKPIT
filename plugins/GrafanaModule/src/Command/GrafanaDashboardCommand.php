<?php
declare(strict_types=1);

namespace GrafanaModule\Command;

use App\itnovum\openITCOCKPIT\Grafana\GrafanaColorOverrides;
use App\itnovum\openITCOCKPIT\Grafana\GrafanaTooltip;
use App\Lib\Interfaces\ServicestatusTableInterface;
use App\Model\Table\HostsTable;
use App\Model\Table\ProxiesTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use GrafanaModule\Model\Table\GrafanaConfigurationsTable;
use GrafanaModule\Model\Table\GrafanaDashboardsTable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;
use itnovum\openITCOCKPIT\Core\ServicestatusConditions;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\ValueObjects\Perfdata;
use itnovum\openITCOCKPIT\Grafana\GrafanaApiConfiguration;
use itnovum\openITCOCKPIT\Grafana\GrafanaDashboard;
use itnovum\openITCOCKPIT\Grafana\GrafanaOverrides;
use itnovum\openITCOCKPIT\Grafana\GrafanaPanel;
use itnovum\openITCOCKPIT\Grafana\GrafanaRow;
use itnovum\openITCOCKPIT\Grafana\GrafanaTag;
use itnovum\openITCOCKPIT\Grafana\GrafanaTargetCollection;
use itnovum\openITCOCKPIT\Grafana\GrafanaTargetPrometheus;
use itnovum\openITCOCKPIT\Grafana\GrafanaTargetUnit;
use itnovum\openITCOCKPIT\Grafana\GrafanaTargetWhisper;
use itnovum\openITCOCKPIT\Grafana\GrafanaThresholdCollection;
use itnovum\openITCOCKPIT\Grafana\GrafanaThresholds;
use Statusengine\PerfdataParser;

/**
 * GrafanaDashboard command.
 */
class GrafanaDashboardCommand extends Command implements CronjobInterface {

    /**
     * @var GrafanaApiConfiguration
     */
    private $GrafanaApiConfiguration;


    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $tag;

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
        $parser = parent::buildOptionParser($parser);

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

        /** @var ProxiesTable $ProxiesTable */
        $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');


        $io->out('Check Connection to Grafana');
        $this->client = $GrafanaConfigurationsTable->testConnection($this->GrafanaApiConfiguration, $ProxiesTable->getSettings());

        if ($this->client instanceof Client) {
            $io->success('Connection check successful');
            //get Tag name
            $tag = new GrafanaTag();
            $this->tag = $tag->getTag();
            //delete previous dashboards
            $this->deleteDashboards($this->tag);
            $this->createDashboard();
        } else {
            Log::error('GrafanaDashboardCommand: ' . $this->client);
            Log::error('GrafanaDashboardCommand: Connection check failed');
        }
        $io->out('Done');
    }

    public function createDashboard() {
        /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
        $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');

        /** @var GrafanaDashboardsTable $GrafanaDashboardsTable */
        $GrafanaDashboardsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaDashboards');

        $DbBackend = new DbBackend();
        $ServicestatusTable = $DbBackend->getServicestatusTable();
        $ServicestatusFields = new ServicestatusFields($DbBackend);
        $ServicestatusFields->perfdata();
        $ServicestatusConditions = new ServicestatusConditions($DbBackend);
        $ServicestatusConditions->perfdataIsNotNull();
        $ServicestatusConditions->perfdataIsNotEmpty();

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $hosts = $GrafanaDashboardsTable->getHostsForDashboardCreationCronjob();
        $filteredHosts = $GrafanaDashboardsTable->filterResults(
            $hosts,
            $this->GrafanaApiConfiguration->getIncludedHostgroups(),
            $this->GrafanaApiConfiguration->getExcludedHostgroups()
        );

        foreach ($filteredHosts as $id => $hostUuid) {
            $json = $this->getJsonForImport($id, $HostsTable, $ServicestatusTable, $ServicestatusFields, $ServicestatusConditions);

            $start = microtime(true);
            if ($json) {
                $request = new Request('POST', $this->GrafanaApiConfiguration->getApiUrl() . '/dashboards/db', ['content-type' => 'application/json'], $json);
                try {
                    $response = $this->client->send($request);
                } catch (BadResponseException $e) {
                    $response = $e->getResponse();
                    $responseBody = $response->getBody()->getContents();
                    $this->io->error($responseBody);
                }

                if ($response->getStatusCode() == 200) {
                    $end = microtime(true);

                    $responseBody = $response->getBody()->getContents();
                    $responseBody = json_decode($responseBody, true);

                    try {
                        $record = $GrafanaDashboardsTable->find()
                            ->where([
                                'configuration_id' => $GrafanaConfigurationsTable->getConfigurationId(),
                                'host_id'          => $id,
                                'host_uuid'        => $hostUuid,
                            ])
                            ->firstOrFail();

                        $entity = $GrafanaDashboardsTable->patchEntity($record, [
                            'configuration_id' => $GrafanaConfigurationsTable->getConfigurationId(),
                            'host_id'          => $id,
                            'host_uuid'        => $hostUuid,
                            'grafana_uid'      => $responseBody['uid']
                        ]);
                        $this->io->success('Dashboard for host with id ' . $id . ' updated [Took: ' . ($end - $start) . ' seconds]');

                    } catch (RecordNotFoundException $e) {
                        $entity = $GrafanaDashboardsTable->newEntity([
                            'configuration_id' => $GrafanaConfigurationsTable->getConfigurationId(),
                            'host_id'          => $id,
                            'host_uuid'        => $hostUuid,
                            'grafana_uid'      => $responseBody['uid']
                        ]);
                        $this->io->success('Dashboard for host with id ' . $id . ' created [Took: ' . ($end - $start) . ' seconds]');

                    }

                    $GrafanaDashboardsTable->save($entity);
                }
            }
        }
    }

    /**
     * @param $hostId
     * @param HostsTable $HostsTable
     * @param ServicestatusTableInterface $ServicestatusTable
     * @param ServicestatusFields $ServicestatusFields
     * @param ServicestatusConditions $ServicestatusConditions
     * @return bool|string
     */
    public function getJsonForImport($hostId, HostsTable $HostsTable, ServicestatusTableInterface $ServicestatusTable, ServicestatusFields $ServicestatusFields, ServicestatusConditions $ServicestatusConditions) {
        $host = $HostsTable->find()
            ->select([
                'id',
                'uuid',
                'name',
                'GrafanaDashboards.grafana_uid'
            ])
            ->leftJoin(
                ['GrafanaDashboards' => 'grafana_dashboards'],
                ['GrafanaDashboards.host_id = Hosts.id']
            )
            ->contain([
                'Services' => function (Query $query) {
                    $query
                        ->disableAutoFields()
                        ->select([
                            'id',
                            'uuid',
                            'name',
                            'host_id',
                            'servicetemplate_id',
                            'service_type'
                        ])
                        ->contain([
                            'Servicetemplates' => function (Query $qery) {
                                return $qery
                                    ->disableAutoFields()
                                    ->select([
                                        'id',
                                        'name'
                                    ]);
                            }
                        ]);

                    if (Plugin::isLoaded('PrometheusModule')) {
                        $query->contain('PrometheusAlertRules', function (Query $query) {
                            return $query
                                ->disableAutoFields()
                                ->select([
                                    'id',
                                    'host_id',
                                    'service_id',
                                    'promql',
                                    'unit',
                                    'threshold_type',
                                    'warning_min',
                                    'warning_max',
                                    'critical_min',
                                    'critical_max',
                                    'warning_longer_as',
                                    'critical_longer_as',
                                    'warning_operator',
                                    'critical_operator'
                                ]);
                        });
                    }


                    return $query;
                }
            ])
            ->where([
                'Hosts.id' => $hostId
            ])
            ->disableHydration()
            ->first();

        if ($host === null) {
            //Host not found
            return false;
        }
        if (empty($host['services'])) {
            //Host has no services
            return false;
        }

        $servicestatus = $ServicestatusTable->byUuid(
            Hash::extract($host, 'services.{n}.uuid'),
            $ServicestatusFields,
            $ServicestatusConditions
        );

        if (empty($servicestatus)) {
            //No servicestatus for all services
            return false;
        }
        $grafanaDashboard = new GrafanaDashboard();
        $grafanaDashboard->setTitle(sprintf('%s | %s', $host['uuid'], '🖥️ ' . $host['name']));
        $grafanaDashboard->setEditable(false);
        //$grafanaDashboard->setTags($this->tag);
        //$grafanaDashboard->setTags('🖥️ ' . $host['name']);
        $grafanaDashboard->setTooltip(GrafanaTooltip::DEFAULT);
        if (!empty($host['GrafanaDashboards']['grafana_uid'])) {
            $grafanaDashboard->setUid($host['GrafanaDashboards']['grafana_uid']);
        }
        $panelId = 1;
        $grafanaRow = new GrafanaRow();


        foreach ($host['services'] as $service) {
            $isRowFull = false;
            $serviceName = $service['name'];
            if ($serviceName === null || $serviceName === '') {
                $serviceName = $service['servicetemplate']['name'];
            }
            if (!isset($servicestatus[$service['uuid']]['Servicestatus']['perfdata'])) {
                //Skip services that have no performance data - Prometheus services have always performance data
                if ($service['service_type'] !== PROMETHEUS_SERVICE) {
                    continue;
                }
            }

            $grafanaTargetCollection = new GrafanaTargetCollection();
            if ($service['service_type'] !== PROMETHEUS_SERVICE) {
                $PerfdataParser = new PerfdataParser($servicestatus[$service['uuid']]['Servicestatus']['perfdata']);
                $perfdata = $PerfdataParser->parse();
                $grafanaPanel = new GrafanaPanel($panelId, 6);
                $grafanaPanel->setTitle(sprintf('%s - %s', $host['name'], $serviceName));
                foreach ($perfdata as $label => $gauge) {
                    $Perfdata = Perfdata::fromArray($label, $gauge);
                    $grafanaTargetCollection->addTarget(
                        new GrafanaTargetWhisper(
                            sprintf(
                                '%s.%s.%s.%s',
                                $this->GrafanaApiConfiguration->getGraphitePrefix(),
                                $host['uuid'],
                                $service['uuid'],
                                $Perfdata->getReplacedLabel()
                            ),
                            new GrafanaTargetUnit($Perfdata->getUnit()),
                            new GrafanaThresholds($Perfdata->getWarning(), $Perfdata->getCritical()),
                            $Perfdata->getLabel()
                        )
                    );
                }
            } else {
                //Prometheus service
                $metricCount = 1;
                if (Plugin::isLoaded('PrometheusModule')) {
                    // Query Prometheus to get all metrics
                    $PrometheusPerfdataLoader = new \PrometheusModule\Lib\PrometheusPerfdataLoader();
                    $metricCount = $PrometheusPerfdataLoader->getMetricsCountByPromQl($service['prometheus_alert_rule']['promql']);
                }

                $alias = $serviceName;
                if ($metricCount > 1) {
                    // The given PromQL returns more than 1 metric
                    // So we let Grafana generate a legend
                    $alias = null;
                }

                $grafanaPanel = new GrafanaPanel($panelId, 6);
                $grafanaPanel->setTitle(sprintf('%s - %s', $host['name'], $serviceName));
                $grafanaPanel->setMetricCount($metricCount);
                $grafanaTargetCollection->addTarget(
                    new GrafanaTargetPrometheus(
                        $service['prometheus_alert_rule']['promql'],
                        new GrafanaTargetUnit($service['prometheus_alert_rule']['unit']),
                        new GrafanaThresholds($service['prometheus_alert_rule']['warning_min'], $service['prometheus_alert_rule']['critical_min']),
                        $alias
                    )
                );
            }


            $grafanaPanel->addTargets(
                $grafanaTargetCollection,
                new GrafanaOverrides($grafanaTargetCollection),
                new GrafanaColorOverrides($grafanaTargetCollection),
                new GrafanaThresholdCollection($grafanaTargetCollection)
            );

            if ($panelId % 2 === 0) {
                //Row is full, create a new one
                $grafanaRow->addPanel($grafanaPanel);
                $grafanaDashboard->addRow($grafanaRow);
                $grafanaRow = new GrafanaRow();
                $isRowFull = true;
            } else {
                $grafanaRow->addPanel($grafanaPanel);
            }


            $panelId++;
        }
        if ($grafanaRow->getNumberOfPanels() > 0 && $isRowFull === false) {
            $grafanaDashboard->addRow($grafanaRow);
        }
        return $grafanaDashboard->getGrafanaDashboardJson();
    }


    private function getGrafanaDashboardsByTag($tag) {
        try {
            $request = new Request('GET', $this->GrafanaApiConfiguration->getApiUrl() . '/search?tag=' . $tag);
            $response = $this->client->send($request);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            $this->io->error($responseBody);
        }
        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
            return json_decode($body->getContents());
        }
    }

    /**
     * @param $tag
     * @return void
     * @throws GuzzleException
     */
    private function deleteDashboards($tag): void {
        try {
            if (empty($tag)) {
                throw new \Exception('No Grafana Tag given');
            }

            /** @var GrafanaDashboardsTable $GrafanaDashboardsTable */
            $GrafanaDashboardsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaDashboards');

            //Only delete auto generated dashboards
            $dashboardUuids = $GrafanaDashboardsTable->getAllDashboardsForDeleteCronjob();
            foreach ($dashboardUuids as $dashboard) {
                $hostUuid = $dashboard['host_uuid'];
                $grafanaUid = $dashboard['grafana_uid'];
                //New grafana >= 8.0
                $start = microtime(true);
                if ($grafanaUid !== null) {
                    $request = new Request('DELETE', sprintf('%s/dashboards/uid/%s',
                            $this->GrafanaApiConfiguration->getApiUrl(),
                            $grafanaUid
                        )
                    );
                } else {
                    $request = new Request('DELETE', $this->GrafanaApiConfiguration->getApiUrl() . '/dashboards/db/' . $hostUuid);
                }
                $end = microtime(true);
                // Delete dashboard from openITCOCKPIT if host or dashboard does not exist
                $entityToDelete = $GrafanaDashboardsTable->get($dashboard['id']);
                try {
                    $response = $this->client->send($request);

                    if ($response->getStatusCode() == 200) {
                        $body = $response->getBody();
                        $response = json_decode($body->getContents());
                        $GrafanaDashboardsTable->delete($entityToDelete);
                        $this->io->success('Dashboard ' . $hostUuid . ' deleted! [Took: ' . ($end - $start) . ' seconds]');
                    }
                } catch (BadResponseException $e) {
                    $response = $e->getResponse();
                    $GrafanaDashboardsTable->delete($entityToDelete);
                    $responseBody = $response->getBody()->getContents();
                    $this->io->error($responseBody);
                }
            }
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }
}
