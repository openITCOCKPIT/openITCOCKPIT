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

// 2.
//    If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//    under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//    License agreement and license key will be shipped with the order
//    confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Form\AgentConfigurationForm;
use App\itnovum\openITCOCKPIT\Agent\AgentSatelliteTasks;
use App\Model\Entity\Changelog;
use App\Model\Entity\Host;
use App\Model\Table\AgentconfigsTable;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\PushAgentsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DistributeModule\Model\Table\SatellitesTable;
use DistributeModule\Model\Table\SatelliteTasksTable;
use itnovum\openITCOCKPIT\Agent\AgentConfiguration;
use itnovum\openITCOCKPIT\Agent\AgentHttpClient;
use itnovum\openITCOCKPIT\Agent\AgentResponseToServices;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\Comparison\ServiceComparisonForSave;
use itnovum\openITCOCKPIT\Core\HostConditions;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\ContainerPermissions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Filter\HostFilter;

class AgentconnectorController extends AppController {

    //public $autoRender = false;

    /* TODO:
     *
     * Need a monthly cronjob to check if the CA will expire in 2 Month
     * It creates a new CA certificate (or maybe extend the existing)?
     * User can issue a new CA using the frontend.
     *
     * Things to do on creating a new CA certificate:
     *  - Create a second CA certificate
     *  - Use new CA for incoming certificate requests
     *  - Update certificate of all agents in pull mode with the old CA using updateCrt post request (in connectToAgent function)
     *  - Delete old CA
     *
     *
     * Push worker:
     *  - accept all requests with matching hostuuid and certificate checksum
     *  - return hint, that a new CA is available ('new_ca'), if certificate creation date (in database) is older than the creation date of the current CA
     *  - add 'ca_checksum' of this agent entity (AgentconnectorTable) to the hint to confirm it comes from the right CA-Server
     *
     */

    /*********************************
     *    AGENTS OVERVIEW METHODS    *
     *********************************/

    //Only for ACLs
    public function overview() {

    }

    public function pull() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');


        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name'
            ]
        ]);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $agents = $AgentconfigsTable->getPullAgents($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($agents as $index => $agent) {
            $agents[$index]['allow_edit'] = $this->allowedByContainerId(
                Hash::extract(
                    $agent['host']['hosts_to_containers_sharing'],
                    '{n}.id'
                )
            );
        }

        $this->set('agents', $agents);
        $this->viewBuilder()->setOption('serialize', ['agents']);
    }

    public function push() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var PushAgentsTable $PushAgentsTable */
        $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');


        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name'
            ],
            'bool' => [
                'hasHostAssignment'
            ]
        ]);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $agents = $PushAgentsTable->getPushAgents($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($agents as $index => $agent) {
            $agents[$index]['last_update'] = $UserTime->format($agent['last_update']);
            $agents[$index]['allow_edit'] = true;
            if (!empty($agent['host']) && $this->hasRootPrivileges === false) {

                $containerIds = explode(',', $agent['container_ids']);
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $containerIds);
                $allowEdit = $ContainerPermissions->hasPermission();

                $agents[$index]['allow_edit'] = $allowEdit;
            }
        }

        $this->set('agents', $agents);
        $this->viewBuilder()->setOption('serialize', ['agents']);
    }

    public function push_satellite() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        if (!Plugin::isLoaded('DistributeModule') || !Plugin::isLoaded('ImportModule')) {
            throw new MissingPluginException('DistributeModule or ImportModule is not loaded');
        }

        /** @var \ImportModule\Model\Table\SatellitePushAgentsTable $SatellitePushAgentsTable */
        $SatellitePushAgentsTable = TableRegistry::getTableLocator()->get('ImportModule.SatellitePushAgents');


        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name'
            ],
            'bool' => [
                'hasHostAssignment'
            ]
        ]);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $MY_RIGHTS = $this->MY_RIGHTS;
        $agents = $SatellitePushAgentsTable->getSatellitePushAgents($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($agents as $index => $agent) {
            $agents[$index]['last_update'] = $UserTime->format($agent['last_update']);
            $agents[$index]['allow_edit'] = true;
            if (!empty($agent['host']) && $this->hasRootPrivileges === false) {

                $containerIds = explode(',', $agent['container_ids']);
                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $containerIds);
                $allowEdit = $ContainerPermissions->hasPermission();

                $agents[$index]['allow_edit'] = $allowEdit;
            }
        }

        $this->set('agents', $agents);
        $this->viewBuilder()->setOption('serialize', ['agents']);
    }

    /**
     * @param null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        if (!$AgentconfigsTable->existsById($id)) {
            throw new NotFoundException(__('Agent config not found'));
        }

        $agentConfig = $AgentconfigsTable->get($id, contain: [
            'Hosts'
        ]);
        if (!$this->allowedByContainerId($agentConfig->get('host')->get('container_id'))) {
            $this->render403();
            return;
        }

        if ($AgentconfigsTable->delete($agentConfig)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);

            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /**
     * @param null $id
     */
    public function delete_push_agent($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var PushAgentsTable $PushAgentsTable */
        $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        if (!$PushAgentsTable->existsById($id)) {
            throw new NotFoundException(__('Push Agent config not found'));
        }

        $pushAgent = $PushAgentsTable->get($id, contain: [
            'Agentconfigs'
        ]);

        if (!empty($pushAgent->get('agentconfig'))) {
            // PushAgent has an host assignment via the Agentconfig.
            // Check permissions if the user is allowed to delete it.

            $hostId = $pushAgent->get('agentconfig')->get('host_id');
            $host = $host = $HostsTable->getHostByIdForPermissionCheck($hostId);

            if (!$this->allowedByContainerId($host->getContainerIds(), true)) {
                $this->render403();
                return;
            }
        }

        // If the PushAgent has no host assignment anyone can delete it
        $agentConfig = $pushAgent->get('agentconfig');

        if ($PushAgentsTable->delete($pushAgent)) {
            if (!empty($agentConfig)) {
                $AgentconfigsTable->delete($agentConfig);
            }
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);

            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /**
     * @param null $id
     */
    public function delete_satellite_push_agent($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        if (!Plugin::isLoaded('DistributeModule') || !Plugin::isLoaded('ImportModule')) {
            throw new MissingPluginException('DistributeModule or ImportModule is not loaded');
        }

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        /** @var \ImportModule\Model\Table\SatellitePushAgentsTable $SatellitePushAgentsTable */
        $SatellitePushAgentsTable = TableRegistry::getTableLocator()->get('ImportModule.SatellitePushAgents');


        if (!$SatellitePushAgentsTable->existsById($id)) {
            throw new NotFoundException(__('Satellite Push Agent config not found'));
        }

        $satellitePushAgent = $SatellitePushAgentsTable->get($id, contain: [
            'Agentconfigs'
        ]);

        if (!empty($satellitePushAgent->get('agentconfig'))) {
            // SatellitePushAgent has a host assignment via the Agentconfig.
            // Check permissions if the user is allowed to delete it.

            $hostId = $satellitePushAgent->get('agentconfig')->get('host_id');
            $host = $HostsTable->getHostByIdForPermissionCheck($hostId);

            if (!$this->allowedByContainerId($host->getContainerIds(), true)) {
                $this->render403();
                return;
            }
        }

        // If the SatellitePushAgent has no host assignment anyone can delete it
        $agentConfig = $satellitePushAgent->get('agentconfig');

        if ($SatellitePushAgentsTable->delete($satellitePushAgent)) {
            if (!empty($agentConfig)) {
                $AgentconfigsTable->delete($agentConfig);
            }
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);

            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    public function showOutput() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        $mode = $this->request->getQuery('mode', 'pull');
        $id = $this->request->getQuery('id', 0);

        $host = [];
        $outputAsJson = [];
        $config = [];
        $pushAgentUuid = null;

        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if ($mode === 'pull') {
            // The Agent is running in Pull mode so we have an host association and an agent config.

            $hostId = $id;

            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException();
            }

            /** @var Host $host */
            $host = $HostsTable->getHostByIdForPermissionCheck($hostId);
            if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
                $this->render403();
                return;
            }

            if (!$AgentconfigsTable->existsByHostId($host->id)) {
                throw new NotFoundException();
            }

            $record = $AgentconfigsTable->getConfigByHostId($hostId);
            $AgentConfiguration = new AgentConfiguration();
            $config = $AgentConfiguration->unmarshal($record->config);

            // Send HTTP/s request to Agent to query/pull the data
            $AgentHttpClient = new AgentHttpClient($record, $host->get('address'));
            $outputAsArray = $AgentHttpClient->getResults();
            $outputAsJson = json_encode($outputAsArray, JSON_PRETTY_PRINT);
        } else if ($mode === 'push_satellite') {
            // The Agent is running in Push mode from a satellite via the Import Module
            // Agents from a Satellite (without the Import Module) are not handled by this
            if (!Plugin::isLoaded('DistributeModule') || !Plugin::isLoaded('ImportModule')) {
                throw new MissingPluginException('DistributeModule or ImportModule is not loaded');
            }

            /** @var \ImportModule\Model\Table\SatellitePushAgentsTable $SatellitePushAgentsTable */
            $SatellitePushAgentsTable = TableRegistry::getTableLocator()->get('ImportModule.SatellitePushAgents');

            $pushAgentId = $id;
            if (!$SatellitePushAgentsTable->existsById($pushAgentId)) {
                throw new NotFoundException();
            }

            $record = $SatellitePushAgentsTable->get($pushAgentId);

            //Has this agent already a configuration?
            if ($record->agentconfig_id !== null && $AgentconfigsTable->existsById($record->agentconfig_id)) {
                $configEntity = $AgentconfigsTable->get($record->agentconfig_id);

                /** @var Host $host */
                $host = $HostsTable->getHostByIdForPermissionCheck($configEntity->host_id);
                if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
                    $this->render403();
                    return;
                }

                $AgentConfiguration = new AgentConfiguration();
                $config = $AgentConfiguration->unmarshal($configEntity->config);
            }

            $checkresults = $record->checkresults;
            if (empty($checkresults)) {
                $checkresults = '[]';
            }

            $outputAsJson = json_encode(json_decode($checkresults, true), JSON_PRETTY_PRINT);

        } else {
            // Agent is running in Push mode. Maybe has no config and host yet
            /** @var PushAgentsTable $PushAgentsTable */
            $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');

            $pushAgentId = $id;
            if (!$PushAgentsTable->existsById($pushAgentId)) {
                throw new NotFoundException();
            }

            $record = $PushAgentsTable->get($pushAgentId);

            //Has this agent already a configuration?
            if ($record->agentconfig_id !== null && $AgentconfigsTable->existsById($record->agentconfig_id)) {
                $configEntity = $AgentconfigsTable->get($record->agentconfig_id);

                /** @var Host $host */
                $host = $HostsTable->getHostByIdForPermissionCheck($configEntity->host_id);
                if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
                    $this->render403();
                    return;
                }

                $AgentConfiguration = new AgentConfiguration();
                $config = $AgentConfiguration->unmarshal($configEntity->config);
            }

            $checkresults = $record->checkresults;
            if (empty($checkresults)) {
                $checkresults = '[]';
            }

            $outputAsJson = json_encode(json_decode($checkresults, true), JSON_PRETTY_PRINT);
        }

        $this->set('host', $host);
        $this->set('config', $config);
        $this->set('outputAsJson', $outputAsJson);
        $this->set('pushAgentUuid', $record->uuid);
        $this->viewBuilder()->setOption('serialize', ['host', 'config', 'outputAsJson', 'pushAgentUuid']);
    }

    /****************************
     *      Wizard METHODS      *
     ****************************/

    // Step 1
    public function wizard() {
        if (!$this->isApiRequest()) {
            //Only ship HTML Template
            return;
        }

        $hostId = $this->request->getQuery('hostId', 0);

        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException();
        }

        $isConfigured = $AgentconfigsTable->existsByHostId($hostId);

        $agentConfig = null;
        $this->set('isConfigured', $isConfigured);
        $this->viewBuilder()->setOption('serialize', ['isConfigured']);

    }

    // Step 2
    public function config() {
        if (!$this->isApiRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        if ($this->request->is('get')) {
            $hostId = $this->request->getQuery('hostId', 0);

            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException();
            }

            $host = $HostsTable->get($hostId);

            $satellite = null;
            if ($host->satellite_id > 0 && Plugin::isLoaded('DistributeModule')) {
                /** @var SatellitesTable $SatellitesTable */
                $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
                $satellite = $SatellitesTable->getSatelliteByIdForAgent($host->satellite_id);
            }


            $agentConfigAsJsonFromDatabase = '';
            $isOldAgent1Config = false;
            $isNewConfig = true;
            if ($AgentconfigsTable->existsByHostId($host->id)) {
                $isNewConfig = false;
                $record = $AgentconfigsTable->getConfigByHostId($host->id);
                $agentConfigAsJsonFromDatabase = $record->config;

                if ($agentConfigAsJsonFromDatabase === '') {
                    // DB record exists but no json config
                    // Old 1.x agent config
                    $isOldAgent1Config = true;
                }
            }

            $AgentConfiguration = new AgentConfiguration();
            $config = $AgentConfiguration->unmarshal($agentConfigAsJsonFromDatabase);
            if ($isOldAgent1Config === true && isset($record)) {
                // Migrate old config from agent 1.x to 3.x
                $config['int']['bind_port'] = (int)$record->port;
                $config['bool']['use_http_basic_auth'] = $record->basic_auth;
                $config['string']['username'] = $record->username;
                $config['string']['password'] = $record->password;
                $config['bool']['use_proxy'] = $record->proxy;
                $config['bool']['enable_push_mode'] = false;
                if ($record->push_noticed) {
                    $config['bool']['enable_push_mode'] = true;
                }
            }

            $this->set('config', $config);
            $this->set('isNewConfig', $isNewConfig);
            $this->set('host', $host);
            $this->set('satellite', $satellite);
            $this->viewBuilder()->setOption('serialize', ['config', 'host', 'isNewConfig', 'satellite']);
        }

        if ($this->request->is('post')) {
            // Validate and save agent configuration
            $AgentConfigurationForm = new AgentConfigurationForm();
            $dataWithDatatypes = $this->request->getData('config', []);

            $hostId = $this->request->getData('hostId', 0);
            $pushAgentId = $this->request->getData('pushAgentId', 0);

            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException();
            }

            $host = $HostsTable->get($hostId);

            //Remote data type keys for validation (string, int, bool etc)
            $data = [];
            foreach ($dataWithDatatypes as $datatype => $fields) {
                foreach ($fields as $fieldName => $fieldValue) {
                    $data[$fieldName] = $fieldValue;
                }
            }
            $AgentConfigurationForm->execute($data);

            if (!empty($AgentConfigurationForm->getErrors())) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $AgentConfigurationForm->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            // json config is valid
            $entity = $AgentconfigsTable->newEmptyEntity();
            // Get old configuration from database to run an update - if exists
            if ($AgentconfigsTable->existsByHostId($host->id)) {
                $entity = $AgentconfigsTable->getConfigByHostId($host->id);
            }

            $AgentConfiguration = new AgentConfiguration();
            $AgentConfiguration->setConfigForJson($dataWithDatatypes);

            // Legacy configuration for Agent version < 3.x
            $data = [
                'host_id'       => $hostId,
                'port'          => $dataWithDatatypes['int']['bind_port'],
                'basic_auth'    => $dataWithDatatypes['bool']['use_http_basic_auth'],
                'username'      => $dataWithDatatypes['bool']['use_http_basic_auth'] ? $dataWithDatatypes['string']['username'] : '',
                'password'      => $dataWithDatatypes['bool']['use_http_basic_auth'] ? $dataWithDatatypes['string']['password'] : '',
                'proxy'         => $dataWithDatatypes['bool']['use_proxy'],
                'insecure'      => !$dataWithDatatypes['bool']['use_https_verify'], // Validate TLS certificate in PULL mode
                'use_https'     => $dataWithDatatypes['bool']['use_https'], // Use own TLS certificate for the agent like Let's Encrypt
                'use_autossl'   => $dataWithDatatypes['bool']['use_autossl'], // New field with agent 3.x
                'use_push_mode' => $dataWithDatatypes['bool']['enable_push_mode'], // New field with agent 3.x
                'config'        => $AgentConfiguration->marshal(), // New field with agent 3.x
            ];
            $entity = $AgentconfigsTable->patchEntity($entity, $data);
            $AgentconfigsTable->save($entity);
            if ($entity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $host->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if ($pushAgentId > 0) {
                /** @var PushAgentsTable $PushAgentsTable */
                $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');
                if ($PushAgentsTable->existsById($pushAgentId)) {

                    // Was this Host already assigned to an Agent?
                    $oldPushAgent = $PushAgentsTable->getByAgentconfigId($entity->id);

                    if (!is_null($oldPushAgent)) {
                        if ($pushAgentId !== $oldPushAgent->id) {
                            // User assigned a new Agent to this host
                            $oldPushAgent->set('agentconfig_id', null);
                            $PushAgentsTable->save($oldPushAgent);
                        }
                    }

                    $pushAgent = $PushAgentsTable->get($pushAgentId);
                    $pushAgent->set('agentconfig_id', $entity->id);
                    $PushAgentsTable->save($pushAgent);
                }

            }

            $this->set('id', $entity->id);
            $this->viewBuilder()->setOption('serialize', ['id']);
        }
    }

    // Step 3
    public function install() {
        if (!$this->isAngularJsRequest()) {
            return;
        }

        $hostId = $this->request->getQuery('hostId', 0);
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException();
        }

        $host = $HostsTable->get($hostId);

        if (!$AgentconfigsTable->existsByHostId($host->id)) {
            throw new NotFoundException();
        }

        $record = $AgentconfigsTable->getConfigByHostId($host->id);

        $AgentConfiguration = new AgentConfiguration();
        $config = $AgentConfiguration->unmarshal($record->config);

        $this->set('config', $config);
        $this->set('host', $host);
        $this->set('config_as_ini', $AgentConfiguration->getAsIni());


        $this->viewBuilder()->setOption('serialize', ['config', 'host', 'config_as_ini']);
    }

    // Step 4 (In Pull mode)
    public function autotls() {
        if (!$this->isAngularJsRequest()) {
            return;
        }

        $hostId = $this->request->getQuery('hostId', 0);
        $reExchangeAutoTLS = $this->request->getQuery('reExchangeAutoTLS', 'false') === 'true';
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException();
        }

        $host = $HostsTable->get($hostId);

        if (!$AgentconfigsTable->existsByHostId($host->id)) {
            throw new NotFoundException();
        }

        $record = $AgentconfigsTable->getConfigByHostId($host->id);

        if ($reExchangeAutoTLS === true) {
            if ($record->use_autossl && $record->autossl_successful) {
                // This agent used AutoTLS but someone delete the cert on the agent.
                $record->set('autossl_successful', false);
                $AgentconfigsTable->save($record);
            }
        }

        $AgentConfiguration = new AgentConfiguration();
        $config = $AgentConfiguration->unmarshal($record->config);

        if ($config['bool']['enable_push_mode'] === true) {
            throw new BadRequestException('AutoTLS is only available in Pull mode');
        }

        $connection_test = null;
        $satellite_task_id = null;
        if ($host->satellite_id > 0 && Plugin::isLoaded('DistributeModule')) {
            // Run Auto TLS on the Satellite System

            $AgentSatelliteTasks = new AgentSatelliteTasks();

            //Send a background job to the NSTA to run query the oITC Agent from the Satellite
            //The NSTA will put the result into the oitc_gearman queue, handled by the gearman_worker
            //The frontend will ask frequently if the result has arrived
            $NSTAOptions = [
                'SatelliteID' => $host->satellite_id,
                'Command'     => 'agent',
                'Data'        => [
                    //'TaskID'           => $task->id, //This will be added by the AgentSatelliteTasks class
                    'AgentConfigId'      => $record->id,
                    'Task'               => 'query',
                    'Address'            => $host->address,
                    'Port'               => $config['int']['bind_port'],
                    'UseAutossl'         => $config['bool']['use_autossl'],
                    'TestConnectionOnly' => true, //Only test the connection do not query the output from the Agent
                    'AutosslSuccessful'  => $record->autossl_successful,
                    'UseHttps'           => $config['bool']['use_https'],
                    'Insecure'           => !$config['bool']['use_https_verify'],
                    'BasicAuth'          => $config['bool']['use_http_basic_auth'],
                    'Username'           => $config['string']['username'],
                    'Password'           => $config['string']['password'],
                ]
            ];
            $satellite_task_id = $AgentSatelliteTasks->sendRequestToSatelliteNonBlocking(
                'oitc_agent_autotls',
                $host->satellite_id,
                $NSTAOptions
            );
        } else {
            // Master System
            $AgentHttpClient = new AgentHttpClient($record, $host->get('address'));
            $connection_test = $AgentHttpClient->testConnectionAndExchangeAutotls();
        }

        $this->set('config', $config);
        $this->set('host', $host);
        $this->set('connection_test', $connection_test);
        $this->set('satellite_task_id', $satellite_task_id);


        $this->viewBuilder()->setOption('serialize', ['config', 'host', 'connection_test', 'satellite_task_id']);
    }

    // Step 4 (In Push mode)
    public function select_agent() {
        if (!$this->isJsonRequest()) {
            return;
        }

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
        /** @var PushAgentsTable $PushAgentsTable */
        $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');

        if ($this->request->is('get')) {
            $hostId = $this->request->getQuery('hostId', 0);
        } else {
            $hostId = (int)$this->request->getData('pushagent.host_id', 0);
        }

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException();
        }

        $host = $HostsTable->get($hostId);

        if (!$AgentconfigsTable->existsByHostId($host->id)) {
            throw new NotFoundException();
        }

        $config = $AgentconfigsTable->getConfigByHostId($host->id);

        if ($this->request->is('get')) {
            $selectedPushAgentId = 0;

            if ($host->satellite_id > 0 && Plugin::isLoaded('DistributeModule')) {
                // Query the available push agents from the Satellite System
                // Run request on Satellite System and wait for response

                // Long-running request so we release the session
                $session = $this->request->getSession();
                $session->close();

                $AgentSatelliteTasks = new AgentSatelliteTasks();

                $NSTAOptions = [
                    'SatelliteID' => $host->satellite_id,
                    'Command'     => 'agent',
                    'Data'        => [
                        //'TaskID'        => $task->id, //This will be added by the AgentSatelliteTasks class
                        'AgentConfigId' => $config->id,
                        'Task'          => 'get_push_agents',
                        'HostUuid'      => $host->uuid
                    ]
                ];
                $pushAgents = $AgentSatelliteTasks->sendRequestToSatelliteBlocking(
                    'get_available_push_agents',
                    $host->satellite_id,
                    $NSTAOptions
                );
            } else {
                // Host/Agent on the Master System
                $pushAgents = $PushAgentsTable->getPushAgentsForAssignments($config->id);
            }

            // Argument must be of type array|object, null given in
            if (empty($pushAgents)) {
                $pushAgents = [];
            }

            $hostnameMatchingPercentages = [];
            foreach ($pushAgents as $index => $pushAgent) {
                if (isset($pushAgent['agentconfig_id']) && $pushAgent['agentconfig_id'] == $config->id) {
                    // The user already assigned this Agent to an Agent Config (Master System only)
                    $selectedPushAgentId = $pushAgent['id'];
                    break;
                }

                // Use the best matching host name to guess the right agent
                $ip = $pushAgent['ipaddress']; // did the agent send us an IP?
                if (empty($ip)) {
                    $ip = $pushAgent['http_x_forwarded_for']; // Agent used a proxy server?
                    if (empty($ip)) {
                        $ip = $pushAgent['remote_address']; // This is the IP we received data from the agent
                    }
                }


                if ($ip === $host->address) {
                    $selectedPushAgentId = $pushAgent['id'];
                    break;
                }

                if (!empty($pushAgent['hostname'])) {
                    $sim = similar_text($host->name, $pushAgent['hostname'], $percent);
                    $hostnameMatchingPercentages[$index] = $percent;
                }
            }

            // Argument must be of type array|object, null given in
            if (empty($selectedPushAgentId)) {
                $selectedPushAgentId = [];
            }

            if ($selectedPushAgentId === 0 && !empty($hostnameMatchingPercentages)) {
                $max = 0;
                $indexToUse = 0;
                foreach ($hostnameMatchingPercentages as $index => $percentage) {
                    if ($percentage > $max) {
                        $max = $percentage;
                        $indexToUse = $index;
                    }
                }

                if (isset($pushAgents[$indexToUse])) {
                    $selectedPushAgentId = $pushAgents[$indexToUse]['id'];
                }
            }

            $pushAgentsForSelectbox = [];
            foreach ($pushAgents as $pushAgent) {
                $ip = $pushAgent['ipaddress']; // did the agent send us an IP?
                if (empty($ip)) {
                    $ip = $pushAgent['http_x_forwarded_for']; // Agent used a proxy server?
                    if (empty($ip)) {
                        $ip = $pushAgent['remote_address']; // This is the IP we received data from the agent
                    }
                }

                $name = $ip;

                if (!empty($pushAgent['hostname'])) {
                    $name = sprintf('%s (%s)', $pushAgent['hostname'], $ip);
                }


                $pushAgentsForSelectbox[] = [
                    'id'         => $pushAgent['id'],
                    'agent_uuid' => $pushAgent['uuid'],
                    'name'       => $name
                ];
            }

            $this->set('config', $config);
            $this->set('host', $host);
            $this->set('pushAgents', $pushAgentsForSelectbox);
            $this->set('selectedPushAgentId', $selectedPushAgentId);

            $this->viewBuilder()->setOption('serialize', ['config', 'host', 'pushAgents', 'selectedPushAgentId']);
            return;
        }

        if ($this->request->is('post')) {
            $pushAgentId = (int)$this->request->getData('pushagent.id', 0);
            $agentUuid = $this->request->getData('pushagent.agent_uuid', 'Unknown');
            if ($host->satellite_id > 0 && Plugin::isLoaded('DistributeModule')) {
                // Send corresponding HostUuid to the Satellite so the satellite
                // knows which Agent monitors which host.

                // Long-running request so we release the session
                $session = $this->request->getSession();
                $session->close();

                $AgentSatelliteTasks = new AgentSatelliteTasks();

                $NSTAOptions = [
                    'SatelliteID' => $host->satellite_id,
                    'Command'     => 'agent',
                    'Data'        => [
                        //'TaskID'      => $task->id, //This will be added by the AgentSatelliteTasks class
                        'AgentConfigId' => $config->id,
                        'Task'          => 'assign_host',
                        'PushAgentId'   => $pushAgentId,
                        'HostUuid'      => $host->uuid
                    ]
                ];
                $AgentSatelliteTasks->sendRequestToSatelliteBlocking(
                    'oitc_agent_assign_host',
                    $host->satellite_id,
                    $NSTAOptions);

                if (Plugin::isLoaded('ImportModule') && UUID::is_valid($agentUuid)) {
                    // ITC-2932
                    // Save manual assigment in SatellitePushAgentsTable so that the Import Module knows, this Agent got assigned by a user
                    /** @var \ImportModule\Model\Table\SatellitePushAgentsTable $SatellitePushAgentsTable */
                    $SatellitePushAgentsTable = TableRegistry::getTableLocator()->get('ImportModule.SatellitePushAgents');
                    $SatellitePushAgentsTable->manuallyAssignSatellitePushAgentIfExistsByUuid($agentUuid, $config->id);
                }


                $this->set('success', true);
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            } else {
                // Host/Agent monitored by Master System
                if (!$PushAgentsTable->existsById($pushAgentId)) {
                    throw new NotFoundException();
                }

                // Was this Host already assigned to an Agent?
                $oldPushAgent = $PushAgentsTable->getByAgentconfigId($config->id);

                if (!is_null($oldPushAgent)) {
                    if ($pushAgentId !== $oldPushAgent->id) {
                        // User assigned a new Agent to this host
                        $oldPushAgent->set('agentconfig_id', null);
                        $PushAgentsTable->save($oldPushAgent);
                    }
                }

                $pushAgent = $PushAgentsTable->get($pushAgentId);
                $pushAgent->set('agentconfig_id', $config->id);
                $PushAgentsTable->save($pushAgent);

                if ($pushAgent->hasErrors()) {
                    $this->response = $this->response->withStatus(400);
                    $this->set('success', false);
                    $this->set('error', $pushAgent->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error', 'success']);
                    return;
                }

                $this->set('success', true);
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            }
        }

        throw new MethodNotAllowedException();
    }

    // Step 5
    public function create_services() {
        if (!$this->isAngularJsRequest()) {
            return;
        }

        $hostId = $this->request->getQuery('hostId', 0);
        $testConnection = $this->request->getQuery('testConnection', 'false') === 'true';

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException();
        }

        $host = $HostsTable->get($hostId);

        if (!$AgentconfigsTable->existsByHostId($host->id)) {
            throw new NotFoundException();
        }

        if ($this->request->is('post')) {
            // Save new services
            $User = new User($this->getUser());
            /** @var HosttemplatesTable $HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            /** @var ServicetemplatesTable $ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            $servicesPost = $this->request->getData('services', []);

            $hostContactsAndContactgroupsById = $HostsTable->getContactsAndContactgroupsById(
                $host->get('id')
            );
            $hosttemplateContactsAndContactgroupsById = $HosttemplatesTable->getContactsAndContactgroupsById(
                $host->get('hosttemplate_id')
            );

            $errors = [];
            $newServiceIds = [];

            $ObjectsCacheChangelog = new ObjectsCache();

            foreach ($servicesPost as $servicePost) {
                $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($servicePost['servicetemplate_id']);
                $servicename = $servicePost['name'];

                $serviceData = ServiceComparisonForSave::getServiceSkeleton($servicePost['host_id'], $servicePost['servicetemplate_id'], OITC_AGENT_SERVICE);
                $serviceData['name'] = $servicename;
                $serviceData['command_id'] = $servicetemplate['Servicetemplate']['command_id'];

                $serviceData['servicecommandargumentvalues'] = $servicePost['servicecommandargumentvalues'];
                $ServiceComparisonForSave = new ServiceComparisonForSave(
                    ['Service' => $serviceData],
                    $servicetemplate,
                    $hostContactsAndContactgroupsById,
                    $hosttemplateContactsAndContactgroupsById
                );
                $serviceData = $ServiceComparisonForSave->getDataForSaveForAllFields();
                $serviceData['uuid'] = UUID::v4();
                //Add required fields for validation
                $serviceData['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
                $serviceData['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
                $serviceData['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
                $serviceData['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
                $serviceData['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];
                $service = $ServicesTable->newEntity($serviceData);

                $ServicesTable->save($service);
                if ($service->hasErrors()) {
                    $errors[] = $service->getErrors();
                } else {
                    //No errors

                    $extDataForChangelog = $ServicesTable->resolveDataForChangelog(['Service' => $serviceData], $ObjectsCacheChangelog);
                    /** @var  ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');
                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        'add',
                        'services',
                        $service->get('id'),
                        OBJECT_SERVICE,
                        $host->get('container_id'),
                        $User->getId(),
                        $host->get('name') . '/' . $servicename,
                        array_merge(['Service' => $serviceData], $extDataForChangelog)
                    );

                    if ($changelog_data) {
                        /** @var Changelog $changelogEntry */
                        $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                        $ChangelogsTable->save($changelogEntry);
                    }
                    $newServiceIds[] = $service->get('id');
                }
            }

            if (!empty($errors)) {
                $this->response = $this->response->withStatus(400);
                $this->set('success', false);
                $this->set('error', $errors);
                $this->viewBuilder()->setOption('serialize', ['error', 'success']);
                return;
            }

            $this->set('success', true);
            $this->set('services', ['_ids' => $newServiceIds]);
            $this->viewBuilder()->setOption('serialize', ['services', 'success']);
            return;
        }


        // GET request
        $record = $AgentconfigsTable->getConfigByHostId($host->id);
        $AgentConfiguration = new AgentConfiguration();
        $config = $AgentConfiguration->unmarshal($record->config);

        $agentresponse = []; // Empty agent response
        if ($config['bool']['enable_push_mode'] === true) {
            // Push Mode

            if ($host->satellite_id > 0 && Plugin::isLoaded('DistributeModule')) {
                // Satellite (Push Mode)
                // Long-running request so we release the session
                $session = $this->request->getSession();
                $session->close();

                $AgentSatelliteTasks = new AgentSatelliteTasks();

                $NSTAOptions = [
                    'SatelliteID' => $host->satellite_id,
                    'Command'     => 'agent',
                    'Data'        => [
                        //'TaskID'      => $task->id, //This will be added by the AgentSatelliteTasks class
                        'AgentConfigId' => $record->id,
                        'Task'          => 'query_push',
                        'HostUuid'      => $host->uuid
                    ]
                ];

                $agentresponse = $AgentSatelliteTasks->sendRequestToSatelliteBlocking(
                    'oitc_agent_query',
                    $host->satellite_id,
                    $NSTAOptions
                );
            } else {
                // Master Server (Push Mode)
                /** @var PushAgentsTable $PushAgentsTable */
                $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');
                $agentresponse = $PushAgentsTable->getAgentOutputByAgentconfigId($record->id);
            }
        } else {
            // Pull Mode
            if ($host->satellite_id > 0 && Plugin::isLoaded('DistributeModule')) {
                // Run request on Satellite System and wait for response (Pull Mode)

                // Long-running request so we release the session
                $session = $this->request->getSession();
                $session->close();

                $AgentSatelliteTasks = new AgentSatelliteTasks();

                $NSTAOptions = [
                    'SatelliteID' => $host->satellite_id,
                    'Command'     => 'agent',
                    'Data'        => [
                        //'TaskID'           => $task->id, //This will be added by the AgentSatelliteTasks class
                        'AgentConfigId'      => $record->id,
                        'Task'               => 'query',
                        'Address'            => $host->address,
                        'Port'               => $config['int']['bind_port'],
                        'UseAutossl'         => $config['bool']['use_autossl'],
                        'TestConnectionOnly' => false, //Get the JSON output from the Agent
                        'AutosslSuccessful'  => $record->autossl_successful,
                        'UseHttps'           => $config['bool']['use_https'],
                        'Insecure'           => !$config['bool']['use_https_verify'],
                        'BasicAuth'          => $config['bool']['use_http_basic_auth'],
                        'Username'           => $config['string']['username'],
                        'Password'           => $config['string']['password'],
                    ]
                ];

                $agentresponse = $AgentSatelliteTasks->sendRequestToSatelliteBlocking(
                    'oitc_agent_query',
                    $host->satellite_id,
                    $NSTAOptions
                );

            } else {
                // Run on Master System (Pull Mode)
                $AgentHttpClient = new AgentHttpClient($record, $host->get('address'));
                $agentresponse = $AgentHttpClient->getResults();
            }
        }


        // Test responses
        // macOS test output (custom checks + docker)
        //$agentresponse = json_decode(file_get_contents(TESTS . 'agent' . DS . 'output_darwin.json'), true);
        // Linux test output (custom checks + docker + libvirt)
        //$agentresponse = json_decode(file_get_contents(TESTS . 'agent' . DS . 'output_linux.json'), true);
        // Windows test output (custom checks + docker)
        //$agentresponse = json_decode(file_get_contents(TESTS . 'agent' . DS . 'output_windows.json'), true);

        $AgentResponseToServices = new AgentResponseToServices($host->id, $agentresponse, true);
        $services = $AgentResponseToServices->getAllServices();

        $connection_test = null;
        if ($config['bool']['enable_push_mode'] === false && $testConnection && $host->satellite_id == 0) {
            // Agent is running in PULL Mode and the user clicked on the First Wizard Page on "Create new services"
            $AgentHttpClient = new AgentHttpClient($record, $host->get('address'));
            $connection_test = $AgentHttpClient->testConnectionAndExchangeAutotls();
        }


        $this->set('host', $host);
        $this->set('services', $services);
        $this->set('connection_test', $connection_test);
        $this->set('config', $config);
        $this->viewBuilder()->setOption('serialize', ['host', 'services', 'connection_test', 'config']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    /**
     * @param bool $onlyHostsWithWritePermission
     */
    public function loadHostsByString($onlyHostsWithWritePermission = false) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $selected = $this->request->getQuery('selected');
        $pushAgentId = (int)$this->request->getQuery('pushAgentId', 0);

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $HostFilter = new HostFilter($this->request);

        $where = $HostFilter->ajaxFilter();
        $where['Hosts.host_type'] = GENERIC_HOST;


        $HostCondition = new HostConditions($where);
        $HostCondition->setIncludeDisabled(false);
        $HostCondition->setContainerIds($this->MY_RIGHTS);

        if ($pushAgentId > 0) {
            /** @var AgentconfigsTable $AgentconfigsTable */
            $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
            $hostIdsToExclude = $AgentconfigsTable->getHostIdsByMode('push');

            if (!empty($hostIdsToExclude)) {
                $HostCondition->setNotConditions([
                    'Hosts.id IN' => $hostIdsToExclude
                ]);
            }
        }


        if ($onlyHostsWithWritePermission) {
            $writeContainers = [];
            foreach ($this->MY_RIGHTS_LEVEL as $containerId => $rightLevel) {
                $rightLevel = (int)$rightLevel;
                if ($rightLevel === WRITE_RIGHT) {
                    $writeContainers[$containerId] = $rightLevel;
                }
            }
            $HostCondition->setContainerIds(array_keys($writeContainers));
        }

        $hosts = Api::makeItJavaScriptAble(
            $HostsTable->getHostsForAngular($HostCondition, $selected)
        );

        $this->set('hosts', $hosts);
        $this->viewBuilder()->setOption('serialize', ['hosts']);
    }

    public function satellite_response() {
        $taskId = $this->request->getQuery('task_id', 0);

        /** @var SatelliteTasksTable $SatelliteTasksTable */
        $SatelliteTasksTable = TableRegistry::getTableLocator()->get('DistributeModule.SatelliteTasks');

        try {
            $task = $SatelliteTasksTable->find()
                ->where([
                    'id' => $taskId
                ])
                ->firstOrFail();
        } catch (RecordNotFoundException $e) {
            $this->response = $this->response->withStatus(404);
            return;
        }

        $this->set('task', $task);
        $this->viewBuilder()->setOption('serialize', ['task']);
    }


    /************************************
     *    AGENT API METHODS FOR PUSH    *
     ************************************/

    /**
     * Register new PUSH Agents
     *
     * How it works:
     * Register new Agents:
     * The Agent send it's UUID and an empty Password to the openITCOCKPIT Server. If no password was generated for the given UUID
     * openITCOCKPIT will generate a new Password and respond this password to the Agent. Respond with 201
     *
     * If an Agent sends a password which is not found in the database, openITCOCKPIT will respond with a 403 Forbidden
     *
     * IF you change this method PLEASE MAKE SURE TO CHANGE IT ON THE SATELLITE IN THE SAME WAY!
     */
    public function register_agent() {
        $agentUuid = $this->request->getData('agentuuid', null);
        $agentPassword = $this->request->getData('password', null);

        if (!$this->request->is('post') || !$this->isJsonRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($agentUuid === null || $agentPassword === null) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', 'Field uuid or password is missing in POST data');
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        /** @var PushAgentsTable $PushAgentsTable */
        $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');

        if ($agentPassword === '' && $PushAgentsTable->existsByUuid($agentUuid)) {
            // It this UUID already registered?
            $this->response = $this->response->withStatus(403);
            $this->set('error', 'The given UUID is already registed with a password!');
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        if ($agentPassword === '' && !$PushAgentsTable->existsByUuid($agentUuid)) {
            // New or unknown agent - Create a new password for this Agent and add it to our database
            $bytes = openssl_random_pseudo_bytes(64, $cstrong);
            $password = bin2hex($bytes);

            $remoteAddress = null;
            if (!empty($_SERVER['REMOTE_ADDR'])) {
                $remoteAddress = $_SERVER['REMOTE_ADDR'];
            }
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $remoteAddress = $_SERVER['HTTP_CLIENT_IP'];
            }
            $HTTP_X_FORWARDED_FOR = null;
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $HTTP_X_FORWARDED_FOR = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }


            $entity = $PushAgentsTable->newEntity([
                'uuid'                 => $agentUuid,
                'agentconfig_id'       => null,
                'password'             => $password,
                'hostname'             => $this->request->getData('hostname', null),
                'ipaddress'            => $this->request->getData('ipaddress', null),
                'remote_address'       => $remoteAddress,
                'http_x_forwarded_for' => $HTTP_X_FORWARDED_FOR,
                'last_update'          => new \Cake\I18n\DateTime(),
                'checkresults'         => null
            ]);

            $PushAgentsTable->save($entity);
            if ($entity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            //Send new Password to Agent
            $this->response = $this->response->withStatus(201);
            $this->set('agentuuid', $agentUuid);
            $this->set('password', $password);
            $this->viewBuilder()->setOption('serialize', ['agentuuid', 'password']);
            return;
        }

        // Password and Agent UUID given - check if this exists in the database
        if ($PushAgentsTable->existsByUuidAndPassword($agentUuid, $agentPassword)) {
            $this->response = $this->response->withStatus(200);
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(404);
        $this->set('error', 'No Agent found for given UUID and password');
        $this->viewBuilder()->setOption('serialize', ['error']);
        return;
    }

    /**
     * Receiver function called by Agents running in PUSH mode
     */
    public function submit_checkdata() {
        if (!$this->isJsonRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $agentUuid = $this->request->getData('agentuuid', '');
        $agentPassword = $this->request->getData('password', '');
        $checkdata = $this->request->getData('checkdata', []);

        /** @var PushAgentsTable $PushAgentsTable */
        $PushAgentsTable = TableRegistry::getTableLocator()->get('PushAgents');

        $receivedChecks = 0;
        try {
            $pushAgent = $PushAgentsTable->getConfigWithHostForSubmitCheckdata(
                $agentUuid,
                $agentPassword
            );


            $hostUuid = $pushAgent->get('agentconfig')->get('host')->get('uuid');
            $GearmanClient = new Gearman();

            // Agent is known and authorized
            require_once "/opt/openitc/receiver/vendor/autoload.php";
            $CheckConfig = new \itnovum\openITCOCKPIT\Checks\Receiver\CheckConfig('/opt/openitc/receiver/etc/production.json');
            try {
                $config = $CheckConfig->getConfigByHostName($hostUuid);

                $results = [
                    'Messages' => []
                ];

                foreach ($config['checks'] as $pluginConfig) {
                    $pluginName = $pluginConfig['plugin'];

                    $pluginClassName = sprintf('itnovum\openITCOCKPIT\Checks\Receiver\Plugins\%s', $pluginName);
                    if (!class_exists($pluginClassName)) {
                        //Unknown Plugin
                        continue;
                    }

                    /** @var  $Plugin \itnovum\openITCOCKPIT\Checks\Receiver\Plugins\PluginInterface */
                    $Plugin = new $pluginClassName($pluginConfig, $checkdata);

                    $pluginOutput = $Plugin->getOutput();
                    if (strlen($Plugin->getPerfdataSerialized()) > 0) {
                        $pluginOutput .= '|' . $Plugin->getPerfdataSerialized();
                    }

                    // Create bulk message for Statusengine Broker
                    $results['messages'][] = [
                        'Command' => 'check_result',
                        'Data'    => [
                            'host_name'           => $hostUuid,
                            'service_description' => $pluginConfig['uuid'],
                            'output'              => $pluginOutput,
                            'long_output'         => ($Plugin->getLongOutput() === '' ? null : $Plugin->getLongOutput()),
                            'check_type'          => 1, //https://github.com/naemon/naemon-core/blob/cec6e10cbee9478de04b4cf5af29e83d47b5cfd9/src/naemon/common.h#L330-L334
                            'return_code'         => $Plugin->getStatuscode(),
                            'start_time'          => time(),
                            'end_time'            => time(),
                            'early_timeout'       => 0,
                            'latency'             => 0,
                            'exited_ok'           => 1
                        ]
                    ];

                    $receivedChecks++;
                }

                if (!empty($results['messages'])) {
                    $GearmanClient->toStatusnginCmdBackground($results);
                }

            } catch (\RuntimeException $e) {
                // Host was not exported yet to Monitoring Engine and check receiver - just save the check result to the database
                // and ignore the error
            }

            $pushAgent->set('last_update', new \Cake\I18n\DateTime());
            $pushAgent->set('checkresults', json_encode($checkdata));
            $PushAgentsTable->save($pushAgent);

            $this->set('success', true);
            $this->set('received_checks', $receivedChecks);
            $this->viewBuilder()->setOption('serialize', ['success', 'received_checks']);
            return;
        } catch (RecordNotFoundException $e) {
            //No host for given agent config found - only save agent result into database
            if ($PushAgentsTable->existsByUuidAndPassword($agentUuid, $agentPassword)) {
                $pushAgent = $PushAgentsTable->getPushAgentByUuidAndPassword(
                    $agentUuid,
                    $agentPassword
                );
                $pushAgent->set('last_update', new \Cake\I18n\DateTime());
                $pushAgent->set('checkresults', json_encode($checkdata));
                $PushAgentsTable->save($pushAgent);
            }
        }

        $this->response = $this->response->withStatus(400);
        $this->set('error', 'Invalid credentials or host not found');
        $this->viewBuilder()->setOption('serialize', ['error']);
    }
}
