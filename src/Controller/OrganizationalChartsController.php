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

namespace App\Controller;

use App\Model\Table\ContainersTable;
use App\Model\Table\OrganizationalChartConnectionsTable;
use App\Model\Table\OrganizationalChartsTable;
use App\Model\Table\OrganizationalChartStructuresTable;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
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
            //Only ship HTML Template
            return;
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
                $organizationalCharts[$index]['allowEdit'] = empty(array_intersect($containersToCheck, $this->getWriteContainers()));
                $organizationalCharts[$index]['allowView'] = empty(array_intersect($containersToCheck, $MY_RIGHTS));
            }
        }
        $this->set('all_organizationalcharts', $organizationalCharts);
        $this->viewBuilder()->setOption('serialize', ['all_organizationalcharts']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('post')) {

            $data = $this->request->getData(null, []);
            $connections = $data['organizational_chart_connections'] ?? [];

            unset($data['organizational_chart_connections']);

            if (!isset($data['organizational_chart_nodes'])) {
                $data['organizational_chart_nodes'] = [];
            }


            /** @var OrganizationalChartsTable $OrganizationalChartsTable */
            $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');
            /** @var OrganizationalChartConnectionsTable $OrganizationalChartConnectionsTable */
            $OrganizationalChartConnectionsTable = TableRegistry::getTableLocator()->get('OrganizationalChartConnections');

            $entity = $OrganizationalChartsTable->newEmptyEntity();
            $entity = $OrganizationalChartsTable->patchEntity($entity, $data);

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
                        'uuid'                                => $connection['id'],
                        'organizational_chart_id'             => $entity->id,
                        'organizational_chart_input_node_id'  => $nodeUuidToId[$connection['organizational_chart_input_node_id']] ?? 0,
                        'organizational_chart_output_node_id' => $nodeUuidToId[$connection['organizational_chart_output_node_id']] ?? 0,
                    ]);

                    $OrganizationalChartConnectionsTable->save($connectionEntity);
                    if ($connectionEntity->hasErrors()) {
                        Log::error('Error while saving organizational chart connection: ' . json_encode($connectionEntity->getErrors()));
                        dd($connectionEntity);
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
            //Only ship HTML template for angular
            return;
        }

        /** @var OrganizationalChartStructuresTable $OrganizationalChartStructuresTable */
        $OrganizationalChartStructuresTable = TableRegistry::getTableLocator()->get('OrganizationalChartStructures');

        debug($OrganizationalChartStructuresTable->getChartTreeForEdit(1));

    }

    /**
     * @param null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var OrganizationalChartsTable $OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');

        if (!$OrganizationalChartsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid organizational chart'));
        }
        $organizationalChart = $OrganizationalChartsTable->get($id);
        if (!$this->allowedByContainerId($organizationalChart->get('id'))) {
            throw new ForbiddenException('403 Forbidden');
        }

        $this->set('organizationalchart', $organizationalChart);
        $this->viewBuilder()->setOption('serialize', ['organizationalchart']);
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

        $organizationalChart = $OrganizationalChartsTable->get($id, [
            'contain' => [
                'OrganizationalChartNodes' => 'UsersToOrganizationalChartNodes',
                'OrganizationalChartConnections'
            ]
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

        if (!$OrganizationalChartsTable->existsById($organizationalChartId)) {
            throw new NotFoundException(__('Invalid organizational chart'));
        }

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $organizationalChartId = (int)$organizationalChartId;
        $organizationalChart = $OrganizationalChartsTable->getOrganizationalChartById($organizationalChartId, $MY_RIGHTS);

        $containers = Api::makeItJavaScriptAble(
            Hash::combine(
                $organizationalChart,
                'organizational_chart_nodes.{n}.container.id',
                'organizational_chart_nodes.{n}.container'
            )
        );

        $this->set('organizationalChart', $organizationalChart);
        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['organizationalChart', 'containers']);
    }
}
