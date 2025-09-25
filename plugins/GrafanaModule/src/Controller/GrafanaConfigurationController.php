<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace GrafanaModule\Controller;

use App\Model\Table\DashboardTabsTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ProxiesTable;
use App\Model\Table\WidgetsTable;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use GrafanaModule\Model\Table\GrafanaConfigurationsTable;
use GrafanaModule\Model\Table\GrafanaDashboardsTable;
use GuzzleHttp\Client;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Grafana\GrafanaApiConfiguration;

/**
 * Class GrafanaConfigurationController
 * @package GrafanaModule\Controller
 */
class GrafanaConfigurationController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
        $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');
        $grafanaConfiguration = $GrafanaConfigurationsTable->getGrafanaConfigurationForEdit();

        if ($this->request->is('get')) {
            $this->set('grafanaConfiguration', $grafanaConfiguration);
            $this->viewBuilder()->setOption('serialize', [
                'grafanaConfiguration'
            ]);
            return;
        }

        //Save POST||PUT Request
        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData();
            $_hostgroups = $this->request->getData('Hostgroup', []);
            $_hostgroups_excluded = $this->request->getData('Hostgroup_excluded', []);

            if (isset($data['Hostgroup'])) {
                unset($data['Hostgroup']);
            }
            if (isset($data['Hostgroup_excluded'])) {
                unset($data['Hostgroup_excluded']);
            }

            $data['grafana_configuration_hostgroup_membership'] = [];
            foreach ($_hostgroups as $hostgroupId) {
                $data['grafana_configuration_hostgroup_membership'][] = [
                    'configuration_id' => $GrafanaConfigurationsTable->getConfigurationId(),
                    'hostgroup_id'     => $hostgroupId,
                    'excluded'         => 0
                ];
            }
            foreach ($_hostgroups_excluded as $excludedHostgroupId) {
                $data['grafana_configuration_hostgroup_membership'][] = [
                    'configuration_id' => $GrafanaConfigurationsTable->getConfigurationId(),
                    'hostgroup_id'     => $excludedHostgroupId,
                    'excluded'         => 1
                ];
            }


            $enity = $GrafanaConfigurationsTable->getGrafanaConfigurationEntity();
            $enity = $GrafanaConfigurationsTable->patchEntity($enity, $data);

            $GrafanaConfigurationsTable->save($enity);
            if ($enity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $enity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('grafanaConfiguration', $enity);
            $this->viewBuilder()->setOption('serialize', ['grafanaConfiguration']);
        }
    }

    public function loadHostgroups() {
        if (!$this->isApiRequest()) {
            //Only ship template for AngularJs
            return;
        }

        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        $hostgroups = $HostgroupsTable->getHostgroupsAsList([], $this->MY_RIGHTS);

        $hostgroups = Api::makeItJavaScriptAble($hostgroups);

        $this->set('hostgroups', $hostgroups);
        $this->viewBuilder()->setOption('serialize', ['hostgroups']);
    }

    public function testGrafanaConnection() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $config = $this->request->getData(null, []);

            $GrafanaApiConfiguration = GrafanaApiConfiguration::fromArray($config);

            /** @var ProxiesTable $ProxiesTable */
            $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');

            /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
            $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');

            $client = $GrafanaConfigurationsTable->testConnection($GrafanaApiConfiguration, $ProxiesTable->getSettings());
            if ($client instanceof Client) {
                $status = ['status' => true];
            } else {

                $client = (json_decode($client)) ? json_decode($client) : ['message' => (string)$client];

                //Resolve: ITC-2169 RVID: 5-445b21 - Medium - Server-Side Request Forgery
                $message = __('Error while connecting to Grafana server.');
                $message = __('For detailed information, please uncomment line {0} in {1}. Detailed output is disabled due to security reasons.', (__LINE__ + 1), __FILE__);
                //$message = $client;

                if (is_object($client) && property_exists($client, 'message')) {
                    if ($client->message === 'Invalid API key') {
                        $message = 'Invalid API key';
                    }
                }

                if (is_array($client) && isset($client['message'])) {
                    if (strpos($client['message'], 'cURL error') === 0) {
                        $message = $client['message'];
                    }
                }


                $status = [
                    'status' => false,
                    'msg'    => [
                        'message' => $message
                    ]
                ];
            }


            $this->set('status', $status);
            $this->viewBuilder()->setOption('serialize', ['status']);
        }
    }

    public function grafanaWidget() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }


        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        /** @var GrafanaConfigurationsTable $GrafanaConfigurationsTable */
        $GrafanaConfigurationsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaConfigurations');
        /** @var GrafanaDashboardsTable $GrafanaDashboardsTable */
        $GrafanaDashboardsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaDashboards');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId', 0);
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new \RuntimeException('Invalid widget id');
            }

            $widget = $WidgetsTable->getWidgetByIdAsCake2($widgetId);

            //Check host permissions
            $host = null;
            if ($widget['Widget']['host_id'] !== null) {
                $host = $HostsTable->getHostByIdForPermissionCheck($widget['Widget']['host_id']);
            }

            $hostId = null;
            $iframeUrl = '';
            if ($host !== null) {
                $hostId = (int)$host->get('id');
                if ($this->hasRootPrivileges === false) {
                    if (!$this->allowedByContainerId($host->getContainerIds())) {
                        $hostId = null;
                    }
                }


                $grafanaConfiguration = $GrafanaConfigurationsTable->getGrafanaConfiguration();
                if (!empty($grafanaConfiguration) && $GrafanaDashboardsTable->existsForUuid($host->get('uuid'))) {
                    $hostDashboard = $GrafanaDashboardsTable->getDashboardByHostUuid($host->get('uuid'));

                    $GrafanaConfiguration = GrafanaApiConfiguration::fromArray($grafanaConfiguration);
                    $GrafanaConfiguration->setHostUuid($host->get('uuid'));
                    $GrafanaConfiguration->setGrafanaUid($hostDashboard->get('grafana_uid'));
                    $iframeUrl = $GrafanaConfiguration->getIframeUrl();
                } else {
                    $hostId = null;
                    $iframeUrl = '';
                }
            }


            $this->set('host_id', $hostId);
            $this->set('iframe_url', $iframeUrl);
            $this->viewBuilder()->setOption('serialize', ['host_id', 'iframe_url']);
            return;
        }


        if ($this->request->is('post')) {
            $hostId = (int)$this->request->getData('host_id', 0);
            if ($hostId === 0) {
                $hostId = null;
            }

            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new \RuntimeException('Invalid widget id');
            }

            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget->set('host_id', $hostId);

            $WidgetsTable->save($widget);
            if ($widget->hasErrors()) {
                $this->serializeCake4ErrorMessage($widget);
                return;
            } else {
                $this->set('host_id', $hostId);
                $this->viewBuilder()->setOption('serialize', ['host_id']);
                return;
            }
        }
        throw new MethodNotAllowedException();
    }

    public function getGrafanaDashboards() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $grafanaDashboards = [];
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Host.name'
            ]
        ]);

        /** @var GrafanaDashboardsTable $GrafanaDashboardsTable */
        $GrafanaDashboardsTable = TableRegistry::getTableLocator()->get('GrafanaModule.GrafanaDashboards');

        $rawGrafanaDashboards = $GrafanaDashboardsTable->getGrafanaDashboards($GenericFilter, $this->MY_RIGHTS);
        foreach ($rawGrafanaDashboards as $rawGrafanaDashboard) {
            $grafanaDashboards[] = [
                'GrafanaDashboard' => [
                    'id'        => (int)$rawGrafanaDashboard['id'],
                    'host_id'   => (int)$rawGrafanaDashboard['host_id'],
                    'host_uuid' => $rawGrafanaDashboard['host_uuid']
                ],
                'Host'             => [
                    'name' => $rawGrafanaDashboard['Host']['name']
                ]
            ];
        }

        $this->set('grafana_dashboards', $grafanaDashboards);
        $this->viewBuilder()->setOption('serialize', ['grafana_dashboards']);

    }
}
