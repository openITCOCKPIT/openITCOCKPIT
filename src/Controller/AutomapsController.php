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

declare(strict_types=1);

namespace App\Controller;

use App\Lib\Exceptions\MissingDbBackendException;
use App\Model\Entity\Automap;
use App\Model\Table\AutomapsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\DashboardTabsTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\WidgetsTable;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Exception;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\Dashboards\AutomapJson;
use itnovum\openITCOCKPIT\Core\HostConditions;
use itnovum\openITCOCKPIT\Core\ServiceConditions;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\AutomapsFilter;
use itnovum\openITCOCKPIT\Filter\HostFilter;
use itnovum\openITCOCKPIT\Filter\ServiceFilter;

/**
 * Class AutomapsController
 * @package App\Controller
 */
class AutomapsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var AutomapsTable $AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        $AutomapsFilter = new AutomapsFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AutomapsFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $automaps = $AutomapsTable->getAutomapsIndex($AutomapsFilter, $PaginateOMat, $MY_RIGHTS);
        $all_automaps = [];

        foreach ($automaps as $automap) {
            /** @var Automap $automap */
            $automap = $automap->toArray();
            $automap['allow_edit'] = $this->hasRootPrivileges;

            if ($this->hasRootPrivileges === true) {
                $automap['allow_edit'] = $this->isWritableContainer($automap['container']['id']);
            }

            $all_automaps[] = $automap;
        }

        $this->set('all_automaps', $all_automaps);
        $toJson = ['all_automaps', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_automaps', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $AutomapsTable AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        if ($this->request->is('post') || $this->request->is('put')) {
            $automap = $AutomapsTable->newEmptyEntity();
            $automap = $AutomapsTable->patchEntity($automap, $this->request->getData('Automap', []));
            $AutomapsTable->save($automap);
            if ($automap->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $automap->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('automap', $automap);
            $this->viewBuilder()->setOption('serialize', ['automap']);
        }
    }

    /**
     * @param int|null $id
     * @throws Exception
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var AutomapsTable $AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        if (!$AutomapsTable->existsById($id)) {
            throw new NotFoundException(__('Automap not found'));
        }

        $automap = $AutomapsTable->get($id);

        if (!$this->allowedByContainerId($automap->get('container_id'), true)) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return contact information
            $automap = $automap->toArray();
            $toIntFields = [
                'recursive',
                'show_ok',
                'show_warning',
                'show_critical',
                'show_unknown',
                'show_acknowledged',
                'show_downtime',
                'show_label',
                'group_by_host',
                'use_paginator'
            ];
            foreach ($toIntFields as $intField) {
                $automap[$intField] = (int)$automap[$intField];
            }

            $this->set('automap', $automap);
            $this->viewBuilder()->setOption('serialize', ['automap']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Update automap data
            $automap = $AutomapsTable->patchEntity($automap, $this->request->getData('Automap', []));
            $automap->id = $id;
            $AutomapsTable->save($automap);
            if ($automap->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $automap->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }
            $this->set('automap', $automap);
            $this->viewBuilder()->setOption('serialize', ['automap']);
        }
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $fontSizes = [
            1 => 'xx-small',
            2 => 'x-small',
            3 => 'small',
            4 => 'medium',
            5 => 'large',
            6 => 'x-large',
            7 => 'xx-large',
        ];
        /** @var $AutomapsTable AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        if (!$AutomapsTable->existsById($id)) {
            throw new NotFoundException(__('Automap not found'));
        }
        $automap = $AutomapsTable->get($id);

        if (!$this->allowedByContainerId($automap->get('container_id'), false)) {
            $this->render403();
            return;
        }
        $automap = $automap->toArray();
        $automap['font_size_html'] = $fontSizes[$automap['font_size']];

        $automap['allow_edit'] = $this->hasRootPrivileges;
        if ($this->hasRootPrivileges === true) {
            $automap['allow_edit'] = $this->isWritableContainer($automap['container_id']);
        }

        // Query host and services
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $containerIds = [
            $automap['container_id']
        ];
        if ($automap['recursive']) {
            if ($automap['container_id'] == ROOT_CONTAINER) {
                $containerIds = $this->MY_RIGHTS;
            } else {
                $tmpContainerIds = $ContainersTable->resolveChildrenOfContainerIds($automap['container_id'], false);
                $containerIds = $ContainersTable->removeRootContainer($tmpContainerIds);
            }
        }

        //Add conditions which services should be shown on the automap
        $conditions = [];

        $current_stateConditions = [];
        $state_types = [
            'show_unknown'  => 3,
            'show_critical' => 2,
            'show_warning'  => 1,
            'show_ok'       => 0,
        ];
        foreach ($state_types as $stateName => $stateNumber) {
            if ($automap[$stateName]) {
                $current_stateConditions[] = $stateNumber;
            }
        }
        if (sizeof($current_stateConditions) < 4) {
            $conditions['Servicestatus.current_state IN'] = $current_stateConditions;
        }

        if ($automap['show_acknowledged'] === false) {
            $conditions['Servicestatus.problem_has_been_acknowledged'] = 0;
        }

        if ($automap['show_downtime'] === false) {
            $conditions['Servicestatus.scheduled_downtime_depth'] = 0;
        }
        $ServicesConditions = new ServiceConditions($conditions);
        $ServicesConditions->setContainerIds($containerIds);
        $ServicesConditions->setHostnameRegex($automap['host_regex']);
        $ServicesConditions->setServicenameRegex($automap['service_regex']);

        $ServiceFilter = new ServiceFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceFilter->getPage());

        if ($automap['use_paginator'] === false) {
            $PaginateOMat = null;
        }
        if (!empty($automap['hostgroup_regex'])) {
            try {
                $allHostIdsArray = $HostgroupsTable->getHostIdsByHostgroupNameRegex($automap['hostgroup_regex'], $containerIds);
                $ServicesConditions->setHostIds($allHostIdsArray);
            } catch (Exception $e) {
                $ServicesConditions->setHostIds([]);
            }
            if (empty($allHostIdsArray)) {
                $this->set('automap', $automap);
                $this->set('servicesByHost', []);
                $this->viewBuilder()->setOption('serialize', ['automap', 'servicesByHost']);
                return;
            }
        }
        try {
            if ($this->DbBackend->isNdoUtils()) {
                $services = $ServicesTable->getServiceWithStatusByRegularExpressionStatusengine2($ServicesConditions, $PaginateOMat, 'all');
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isStatusengine3()) {
                $services = $ServicesTable->getServiceWithStatusByRegularExpressionStatusengine3($ServicesConditions, $PaginateOMat, 'all');
            }
        } catch (Exception $e) {
            $services = null;
        }
        if (!is_array($services)) {
            $services = [];
        }
        $servicesByHost = [];

        foreach ($services as $service) {
            /** @var \App\Model\Entity\Service $Service */
            $service = $service->toArray();

            $Service = new Service($service);
            $Host = new Host($service['_matchingData']['Hosts']);
            $Servicestatus = new Servicestatus($service['Servicestatus']);

            if (!isset($servicesByHost[$Host->getId()])) {
                $servicesByHost[$Host->getId()] = [
                    'host'     => $Host->toArray(),
                    'services' => []
                ];
            }

            $servicesByHost[$Host->getId()]['services'][] = [
                'service'       => $Service->toArray(),
                'servicestatus' => $Servicestatus->toArray()
            ];
        }

        //Drop keys to make in a array [] for javascript - not a hash {} !
        $servicesByHost = array_values($servicesByHost);


        $this->set('automap', $automap);
        $this->set('servicesByHost', $servicesByHost);
        $toJson = ['automap', 'servicesByHost'];
        if ($this->isScrollRequest()) {
            $toJson[] = 'scroll';
        } else {
            $toJson[] = 'paging';
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $AutomapsTable AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        if (!$AutomapsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Automap'));
        }

        $automap = $AutomapsTable->get($id);

        if (!$this->allowedByContainerId($automap->get('container_id'), true)) {
            $this->render403();
            return;
        }

        if (!$AutomapsTable->delete($automap)) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->viewBuilder()->setOption('serialize', ['success', 'id']);
            return;
        }

        $this->set('success', true);
        $this->set('id', $id);
        $this->viewBuilder()->setOption('serialize', ['success', 'id']);
    }

    public function icon() {
        //Only ship HTML Template
        return;
    }

    /**
     * @param int|null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var AutomapsTable $AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $automaps = $AutomapsTable->getAutomapsForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('automaps', $automaps);
            $this->viewBuilder()->setOption('serialize', ['automaps']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $postData = $this->request->getData('data');

            foreach ($postData as $index => $automapData) {
                if (!isset($automapData['Automap']['id'])) {
                    //Create/clone automap
                    $sourceAutomapId = $automapData['Source']['id'];
                    $sourceAutomap = $AutomapsTable->getSourceAutomapForCopy($sourceAutomapId, $MY_RIGHTS);

                    $newAutomapData = [
                        'name'              => $automapData['Automap']['name'],
                        'description'       => $automapData['Automap']['description'],
                        'container_id'      => $sourceAutomap['container_id'],
                        'recursive'         => $sourceAutomap['recursive'],
                        'host_regex'        => $automapData['Automap']['host_regex'],
                        'hostgroup_regex'   => $automapData['Automap']['hostgroup_regex'],
                        'service_regex'     => $automapData['Automap']['service_regex'],
                        'show_ok'           => $sourceAutomap['show_ok'],
                        'show_warning'      => $sourceAutomap['show_warning'],
                        'show_critical'     => $sourceAutomap['show_critical'],
                        'show_unknown'      => $sourceAutomap['show_unknown'],
                        'show_downtime'     => $sourceAutomap['show_downtime'],
                        'show_acknowledged' => $sourceAutomap['show_acknowledged'],
                        'show_label'        => $sourceAutomap['show_label'],
                        'group_by_host'     => $sourceAutomap['group_by_host'],
                        'use_paginator'     => $sourceAutomap['use_paginator'],
                        'font_size'         => $sourceAutomap['font_size'],
                    ];

                    $newAutomapEntity = $AutomapsTable->newEntity($newAutomapData);
                }

                if (isset($automapData['Automap']['id'])) {
                    //Update existing automap
                    //This happens, if a user copy multiple automaps, and one run into an validation error
                    //All automaps without validation errors got already saved to the database
                    $newAutomapEntity = $AutomapsTable->get($automapData['Automap']['id']);
                    $newAutomapEntity->setAccess('*', false);
                    $newAutomapEntity->setAccess(['name', 'description', 'host_regex', 'hostgroup_regex', 'service_regex'], true);

                    $newAutomapEntity = $AutomapsTable->patchEntity($newAutomapEntity, $automapData['Automap']);
                }
                $AutomapsTable->save($newAutomapEntity);

                $postData[$index]['Error'] = [];
                if ($newAutomapEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newAutomapEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Automap']['id'] = $newAutomapEntity->get('id');
                }
            }
        }

        if ($hasErrors) {
            $this->response = $this->response->withStatus(400);
        }
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function loadContainers() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        $containers = Api::makeItJavaScriptAble($containers);

        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    public function getMatchingHostAndServices() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $defaults = [
            'container_id'    => 0,
            'recursive'       => 0,
            'host_regex'      => '',
            'hostgroup_regex' => '',
            'service_regex'   => ''
        ];
        $hostCount = 0;
        $serviceCount = 0;
        $hostgroupCount = 0;

        $post = $this->request->getData('Automap', []);
        $post = Hash::merge($defaults, $post);

        if ($post['container_id'] > 0) {
            /** @var $ContainersTable ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');
            /** @var $HostgroupTable ServicesTable */
            $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

            $containerIds = [
                $post['container_id']
            ];
            if ($post['recursive']) {
                if ($post['container_id'] == ROOT_CONTAINER) {
                    $containerIds = $this->MY_RIGHTS;
                } else {
                    $tmpContainerIds = $ContainersTable->resolveChildrenOfContainerIds($post['container_id']);
                    $containerIds = $ContainersTable->removeRootContainer($tmpContainerIds);
                }
            }

            $HostConditions = new HostConditions();
            $HostConditions->setContainerIds($containerIds);
            $HostConditions->setHostnameRegex($post['host_regex']);

            $HostFilter = new HostFilter($this->request); //Only used for order right now

            $allHostIdsArray = [];
            if ($post['hostgroup_regex'] != '') {
                try {
                    $allHostIdsArray = $HostgroupsTable->getHostIdsByHostgroupNameRegex($post['hostgroup_regex'], $containerIds);
                    $hostgroupCount = $HostgroupsTable->getHostgroupIdsByNameRegex($post['hostgroup_regex'], $containerIds, 'count');
                } catch (Exception $e) {
                    $hostgroupCount = 0;
                }

                $HostConditions->setHostIds($allHostIdsArray);
                if (empty($allHostIdsArray)) {
                    $this->set('hostCount', $hostCount);
                    $this->set('serviceCount', $serviceCount);
                    $this->set('hostgroupCount', $hostgroupCount);
                    $this->viewBuilder()->setOption('serialize', ['hostCount', 'serviceCount', 'hostgroupCount']);
                    return;
                }
            }


            if ($post['host_regex'] != '') {
                try {
                    $hostCount = $HostsTable->getHostsByRegularExpression($HostFilter, $HostConditions, null, 'count');
                } catch (Exception $e) {
                    $hostCount = 0;
                }
            }

            $ServicesConditions = new ServiceConditions();
            $ServicesConditions->setContainerIds($containerIds);
            $ServicesConditions->setHostnameRegex($post['host_regex']);
            $ServicesConditions->setServicenameRegex($post['service_regex']);
            if (!empty($post['hostgroup_regex'])) {
                $ServicesConditions->setHostIds($allHostIdsArray);
            }
            if ($post['service_regex'] != '') {
                try {
                    if ($this->DbBackend->isNdoUtils()) {
                        $serviceCount = $ServicesTable->getServiceWithStatusByRegularExpressionStatusengine2($ServicesConditions, null, 'count');
                    }

                    if ($this->DbBackend->isCrateDb()) {
                        throw new MissingDbBackendException('MissingDbBackendException');
                    }

                    if ($this->DbBackend->isStatusengine3()) {
                        $serviceCount = $ServicesTable->getServiceWithStatusByRegularExpressionStatusengine3($ServicesConditions, null, 'count');
                    }
                } catch (Exception $e) {
                    $serviceCount = 0;
                }
            }
        }


        $this->set('hostCount', $hostCount);
        $this->set('serviceCount', $serviceCount);
        $this->set('hostgroupCount', $hostgroupCount);
        $this->viewBuilder()->setOption('serialize', ['hostCount', 'serviceCount', 'hostgroupCount']);
    }

    public function automap() {
        //Ship template of automap directive.
        //It is a directive be able to also use the automap as a widget
        return;
    }

    public function automapWidget() {
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

        $AutomapJson = new AutomapJson();
        /** @var AutomapsTable $AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new NotFoundException('Invalid widget id');
            }
            $widgetEntity = $WidgetsTable->get($widgetId);
            $widget = $widgetEntity->toArray();
            $config = [
                'automap_id' => null
            ];
            if ($widget['json_data'] !== null && $widget['json_data'] !== '') {
                $config = json_decode($widget['json_data'], true);
                if (!isset($config['automap_id'])) {
                    $config['automap_id'] = null;
                }
            }
            //Check automap permissions
            if ($config['automap_id'] !== null) {
                $id = $config['automap_id'];
                if (!$AutomapsTable->existsById($id)) {
                    throw new NotFoundException(__('Auto map not found'));
                }
                $automap = $AutomapsTable->get($id);
                $automap = $automap->toArray();
                //Check automap permissions
                if (!empty($automap) && isset($automap[0])) {
                    if (!$this->allowedByContainerId($automap['container_id'], false)) {
                        $config['automap_id'] = null;
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

            $config = $AutomapJson->standardizedData($this->request->getData());
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

    public function loadAutomapsByString() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $AutomapsFilter = new AutomapsFilter($this->request);


        /** @var AutomapsTable $AutomapsTable */
        $AutomapsTable = TableRegistry::getTableLocator()->get('Automaps');
        $selected = $this->request->getQuery('selected');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $automaps = Api::makeItJavaScriptAble(
            $AutomapsTable->getAutomapsForAngular($selected, $AutomapsFilter, $MY_RIGHTS)
        );

        $this->set('automaps', $automaps);
        $this->viewBuilder()->setOption('serialize', ['automaps']);
    }

}
