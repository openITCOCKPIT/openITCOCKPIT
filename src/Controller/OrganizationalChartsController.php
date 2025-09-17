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

declare(strict_types=1);

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Core\Dashboards\OrganizationalchartJson;
use App\Model\Table\ContainersTable;
use App\Model\Table\DashboardTabsTable;
use App\Model\Table\OrganizationalChartConnectionsTable;
use App\Model\Table\OrganizationalChartsTable;
use App\Model\Table\WidgetsTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\NotImplementedException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;


/**
 * Class OrganizationalChartsController
 * @package App\Controller
 */
class OrganizationalChartsController extends AppController {
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        /** @var $OrganizationalChartsTable OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');


        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'OrganizationalCharts.name',
                'OrganizationalCharts.description'
            ]
        ]);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $organizationalCharts = $OrganizationalChartsTable->getOrganizationalChartsIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($organizationalCharts as $index => $organizationalChart) {
            if ($this->hasRootPrivileges === true) {
                $organizationalCharts[$index]['allowEdit'] = true;
                $organizationalCharts[$index]['allowView'] = true;
            } else {
                $containersToCheck = Hash::extract($organizationalChart, 'organizational_chart_nodes.{n}.container.id');
                $organizationalCharts[$index]['allowEdit'] = empty(array_diff($containersToCheck, $this->getWriteContainers()));
                $organizationalCharts[$index]['allowView'] = empty(array_diff($containersToCheck, $MY_RIGHTS));
            }
        }
        $this->set('all_organizationalcharts', $organizationalCharts);
        $this->viewBuilder()->setOption('serialize', ['all_organizationalcharts']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {

            $data = $this->request->getData(null, []);
            $connections = $data['organizational_chart_connections'] ?? [];

            unset($data['organizational_chart_connections']);

            if (!isset($data['organizational_chart_nodes'])) {
                $data['organizational_chart_nodes'] = [];
            }

            // Remove any UUIDs for new nodes
            foreach ($data['organizational_chart_nodes'] as $index => $node) {
                if (isset($node['id']) && UUID::is_valid($node['id'])) {
                    unset($data['organizational_chart_nodes'][$index]['id']);
                }
            }

            /** @var OrganizationalChartsTable $OrganizationalChartsTable */
            $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');
            /** @var OrganizationalChartConnectionsTable $OrganizationalChartConnectionsTable */
            $OrganizationalChartConnectionsTable = TableRegistry::getTableLocator()->get('OrganizationalChartConnections');

            $entity = $OrganizationalChartsTable->newEmptyEntity();
            $entity = $OrganizationalChartsTable->patchEntity($entity, $data, [
                'associated' => [
                    'OrganizationalChartNodes.Users'
                ]
            ]);

            // In my tests, it was enough to only set the deep association in the patchEntity method.
            // According to the CakePHP Slack channel, "The save usually doesn't need it afaik if the patching marked all nested entities as "dirty", as it just follows that along, probably."
            $OrganizationalChartsTable->save($entity);
            if ($entity->hasErrors()) {
                $this->set('error', $entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                $this->response = $this->response->withStatus(400);
                return;
            } else {
                // No errors
                // save connections
                $nodeUuidToId = [];
                foreach ($entity->organizational_chart_nodes as $node) {
                    $nodeUuidToId[$node->uuid] = $node->id;
                }

                foreach ($connections as $connection) {
                    $connectionEntity = $OrganizationalChartConnectionsTable->newEntity([
                        'uuid'                                => $connection['uuid'],
                        'organizational_chart_id'             => $entity->id,
                        'organizational_chart_input_node_id'  => $nodeUuidToId[$connection['organizational_chart_input_node_id']] ?? 0,
                        'organizational_chart_output_node_id' => $nodeUuidToId[$connection['organizational_chart_output_node_id']] ?? 0,
                    ]);

                    $OrganizationalChartConnectionsTable->save($connectionEntity);
                    if ($connectionEntity->hasErrors()) {
                        Log::error('Error while saving organizational chart connection: ' . json_encode($connectionEntity->getErrors()));
                    }
                }

            }

            $this->set('oc', $entity);
            $this->viewBuilder()->setOption('serialize', ['oc']);
            return;
        }

        throw new MethodNotAllowedException();


    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $id = intval($id);

        /** @var OrganizationalChartsTable $OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');

        if (!$OrganizationalChartsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid organizational chart'));
        }

        $organizationalChart = $OrganizationalChartsTable->getOrnanizationalChartForEdit($id);

        // Check permissions first
        $containersToCheck = Hash::extract($organizationalChart, 'organizational_chart_nodes.{n}.container.id');
        if (!empty(array_diff($containersToCheck, $this->getWriteContainers()))) {
            $this->render403();
            return;
        }

        if ($this->request->is('get')) {
            // Return the organizational chart for editing
            $this->set('organizational_chart', $organizationalChart);
            $this->viewBuilder()->setOption('serialize', ['organizational_chart']);
            return;
        }

        if ($this->request->is('post')) {
            // Update the organizational chart
            /** @var OrganizationalChartConnectionsTable $OrganizationalChartConnectionsTable */
            $OrganizationalChartConnectionsTable = TableRegistry::getTableLocator()->get('OrganizationalChartConnections');

            $data = $this->request->getData(null, []);
            $connections = $data['organizational_chart_connections'] ?? [];

            unset($data['organizational_chart_connections']);

            if (!isset($data['organizational_chart_nodes'])) {
                $data['organizational_chart_nodes'] = [];
            }

            // Remove any UUIDs for new nodes
            foreach ($data['organizational_chart_nodes'] as $index => $node) {
                if (isset($node['id']) && UUID::is_valid($node['id'])) {
                    unset($data['organizational_chart_nodes'][$index]['id']);
                }
            }

            $entity = $OrganizationalChartsTable->get($id, contain: [
                'OrganizationalChartNodes.Users'
            ]);
            $entity = $OrganizationalChartsTable->patchEntity($entity, $data, [
                'associated' => [
                    'OrganizationalChartNodes.Users',
                ]
            ]);

            // In my tests, it was enough to only set the deep association in the patchEntity method.
            // According to the CakePHP Slack channel, "The save usually doesn't need it afaik if the patching marked all nested entities as "dirty", as it just follows that along, probably."
            $OrganizationalChartsTable->save($entity);
            if ($entity->hasErrors()) {
                $this->set('error', $entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                $this->response = $this->response->withStatus(400);
                return;
            } else {
                // No errors
                // save connections
                $nodeUuidToId = [];
                foreach ($entity->organizational_chart_nodes as $node) {
                    $nodeUuidToId[$node->uuid] = $node->id;
                }


                // First delete all existing connections for this organizational chart
                $OrganizationalChartConnectionsTable->deleteAll([
                    'organizational_chart_id' => $entity->id
                ]);

                foreach ($connections as $connection) {
                    $inputId = $connection['organizational_chart_input_node_id'];
                    if (UUID::is_valid($inputId)) {
                        // New connection
                        $inputId = $nodeUuidToId[$connection['organizational_chart_input_node_id']] ?? 0;
                    }

                    $outputId = $connection['organizational_chart_output_node_id'];
                    if (UUID::is_valid($outputId)) {
                        // New connection
                        $outputId = $nodeUuidToId[$connection['organizational_chart_output_node_id']] ?? 0;
                    }

                    $connectionEntity = $OrganizationalChartConnectionsTable->newEntity([
                        'uuid'                                => $connection['uuid'],
                        'organizational_chart_id'             => $entity->id,
                        'organizational_chart_input_node_id'  => $inputId,
                        'organizational_chart_output_node_id' => $outputId
                    ]);

                    $OrganizationalChartConnectionsTable->save($connectionEntity);
                    if ($connectionEntity->hasErrors()) {
                        Log::error('Error while saving organizational chart connection: ' . json_encode($connectionEntity->getErrors()));
                    }
                }

            }

            $this->set('oc', $entity);
            $this->viewBuilder()->setOption('serialize', ['oc']);
            return;
        }

    }

    /**
     * For ACL only
     * @param null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        throw new NotImplementedException();
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $OrganizationalChartsTable OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');

        if (!$OrganizationalChartsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid organizational chart'));
        }

        $organizationalChart = $OrganizationalChartsTable->get($id, contain: [
            'OrganizationalChartNodes' => 'Users',
            'OrganizationalChartConnections'
        ]);
        $containerIds = Hash::extract($organizationalChart, 'organizational_chart_nodes.{n}.container_id');
        if (!empty($containerIds)) {
            if (!$this->allowedByContainerId($containerIds)) {
                $this->render403();
                return;
            }
        }
        if ($OrganizationalChartsTable->delete($organizationalChart)) {
            $this->set('success', true);
            $this->set('message', __('Organizational chart deleted successfully'));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('message', __('Issue while deleting Organizational chart'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }

    public function organizationalchartWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        $widgetId = (int)$this->request->getQuery('widgetId');
        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $OrganizationalchartJson = new OrganizationalchartJson();
        /** @var OrganizationalChartsTable $OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new NotFoundException('Invalid widget id');
            }
            $widgetEntity = $WidgetsTable->get($widgetId);
            $widget = $widgetEntity->toArray();
            $config = [
                'organizationalchart_id' => null
            ];
            if ($widget['json_data'] !== null && $widget['json_data'] !== '') {
                $config = json_decode($widget['json_data'], true);
                if (!isset($config['organizationalchart_id'])) {
                    $config['organizationalchart_id'] = null;
                }
            }
            //Check organizational chart permissions
            if ($config['organizationalchart_id'] !== null) {
                $id = (int)$config['organizationalchart_id'];
                if (!$OrganizationalChartsTable->existsById($id)) {
                    throw new NotFoundException(__('Organizationalchart not found'));
                }
                $organizationalchart = $OrganizationalChartsTable->get($id);
                $organizationalchart = $organizationalchart->toArray();
                //Check organizational chart permissions
                if (!empty($organizationalchart) && isset($organizationalchart[0])) {
                    if (!$this->allowedByContainerId($organizationalchart['container_id'], false)) {
                        $config['organizationalchart_id'] = null;
                    }
                }
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {
            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $config = $OrganizationalchartJson->standardizedData($this->request->getData());
            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);
            $WidgetsTable->save($widget);

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }
        throw new MethodNotAllowedException();
    }


    /****************************
     *       AJAX METHODS       *
     ****************************/
    public function loadContainers() {
        if (!$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containers = $ContainersTable->getContainersByIdsGroupByType($MY_RIGHTS, [], [CT_TENANT, CT_LOCATION, CT_NODE]);

        $this->set('tenants', $containers['tenants']);
        $this->set('locations', $containers['locations']);
        $this->set('nodes', $containers['nodes']);
        $this->viewBuilder()->setOption('serialize', ['tenants', 'locations', 'nodes']);
    }

    public function loadOrganizationalChartsByContainerId($containerId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        if (empty($containerId)) {
            throw new BadRequestException("containerId is missing");
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $containerId = (int)$containerId;
        $organizationalCharts = [];

        if ($this->allowedByContainerId($containerId, false)) {
            /** @var OrganizationalChartsTable $OrganizationalChartsTable */
            $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');
            $organizationalCharts = Api::makeItJavaScriptAble(
                $OrganizationalChartsTable->getOrganizationalChartsByContainerId($containerId, $MY_RIGHTS, 'list')
            );
        }


        $this->set('organizationalCharts', $organizationalCharts);
        $this->viewBuilder()->setOption('serialize', ['organizationalCharts']);

    }


    public function loadOrganizationalChartById($organizationalChartId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        if (empty($organizationalChartId)) {
            throw new BadRequestException("organizationalChartId is missing");
        }

        /** @var OrganizationalChartsTable $OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$OrganizationalChartsTable->existsById($organizationalChartId)) {
            throw new NotFoundException(__('Invalid organizational chart'));
        }

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $organizationalChartId = (int)$organizationalChartId;
        $organizationalChart = $OrganizationalChartsTable->getOrganizationalChartById($organizationalChartId, $MY_RIGHTS);

        foreach ($organizationalChart['organizational_chart_nodes'] as $index => $node) {
            $organizationalChart['organizational_chart_nodes'][$index]['container']['path'] = $ContainersTable->getPathByIdAsString($node['container_id']);
        }

        $containersToCheck = Hash::extract($organizationalChart, 'organizational_chart_nodes.{n}.container.id');
        $allowEdit = empty(array_diff($containersToCheck, $this->getWriteContainers()));

        $containers = Api::makeItJavaScriptAble(
            Hash::combine(
                $organizationalChart,
                'organizational_chart_nodes.{n}.container.id',
                'organizational_chart_nodes.{n}.container'
            )
        );

        $this->set('organizationalChart', $organizationalChart);
        $this->set('containers', $containers);
        $this->set('allowEdit', $allowEdit);
        $this->viewBuilder()->setOption('serialize', ['organizationalChart', 'containers', 'allowEdit']);
    }
}
