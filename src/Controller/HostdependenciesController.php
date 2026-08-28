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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Lib\Exceptions\MissingDbBackendException;
use App\Model\Entity\Host;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostdependenciesTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostdependenciesFilter;

/**
 * Class HostdependenciesController
 * @package App\Controller
 */
class HostdependenciesController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');

        $HostdependenciesFilter = new HostdependenciesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostdependenciesFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $hostdependencies = $HostdependenciesTable->getHostdependenciesIndex($HostdependenciesFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($hostdependencies as $index => $hostdependency) {
            $hostdependencies[$index]['allowEdit'] = $this->isWritableContainer($hostdependency['container_id']);
        }


        $this->set('all_hostdependencies', $hostdependencies);
        $toJson = ['all_hostdependencies', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_hostdependencies', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');

        if (!$HostdependenciesTable->exists($id)) {
            throw new NotFoundException(__('Host dependency not found'));
        }

        $hostdependency = $HostdependenciesTable->getHostdependencyById($id);
        if (!$this->allowedByContainerId(Hash::extract($hostdependency, 'Hostdependency.container_id'))) {
            $this->render403();
            return;
        }

        $this->set('hostdependency', $hostdependency);
        $this->viewBuilder()->setOption('serialize', ['hostdependency']);

    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            /** @var HostdependenciesTable $HostdependenciesTable */
            $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
            $data = [];
            $data['hosts'] = $HostdependenciesTable->parseHostMembershipData(
                $this->request->getData('Hostdependency.hosts._ids'),
                $this->request->getData('Hostdependency.hosts_dependent._ids')
            );
            $data['hostgroups'] = $HostdependenciesTable->parseHostgroupMembershipData(
                $this->request->getData('Hostdependency.hostgroups._ids'),
                $this->request->getData('Hostdependency.hostgroups_dependent._ids')
            );

            $validateRequestEntity = $HostdependenciesTable->newEntity($this->request->getData('Hostdependency'));
            $validateRequestEntity->set('uuid', UUID::v4());

            if ($validateRequestEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $validateRequestEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data = array_merge($this->request->getData('Hostdependency'), $data);
            $hostdependency = $HostdependenciesTable->newEntity($data);
            $hostdependency->set('uuid', UUID::v4());
            $HostdependenciesTable->save($hostdependency);

            if ($hostdependency->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostdependency->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostdependency); // REST API ID serialization
                    return;
                }
            }
            $this->set('hostdependency', $hostdependency);
            $this->viewBuilder()->setOption('serialize', ['hostdependency']);
        }
    }

    /**
     * @param null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
        if (!$HostdependenciesTable->existsById($id)) {
            throw new NotFoundException('Host dependency not found');
        }
        $hostdependency = $HostdependenciesTable->get($id, contain: [
            'Hosts'      => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id', 'name']);
            },
            'Hostgroups' => function (Query $q) {
                return $q->enableAutoFields(false)
                    ->select(['id']);
            },
        ]);

        if (!$this->allowedByContainerId($hostdependency->get('container_id'))) {
            $this->render403();
            return;
        }
        if ($this->request->is('post')) {
            /** @var HostdependenciesTable $HostdependenciesTable */
            $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
            $data['hosts'] = $HostdependenciesTable->parseHostMembershipData(
                $this->request->getData('Hostdependency.hosts._ids'),
                $this->request->getData('Hostdependency.hosts_dependent._ids')
            );
            $data['hostgroups'] = $HostdependenciesTable->parseHostgroupMembershipData(
                $this->request->getData('Hostdependency.hostgroups._ids'),
                $this->request->getData('Hostdependency.hostgroups_dependent._ids')
            );

            $validateRequestEntity = $HostdependenciesTable->patchEntity(
                $hostdependency,
                $this->request->getData('Hostdependency')
            );
            if ($validateRequestEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $validateRequestEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data = array_merge($this->request->getData('Hostdependency'), $data);
            $hostdependency = $HostdependenciesTable->patchEntity($hostdependency, $data);
            $HostdependenciesTable->save($hostdependency);

            if ($hostdependency->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $hostdependency->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($hostdependency); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('hostdependency', $hostdependency);
        $this->viewBuilder()->setOption('serialize', ['hostdependency']);
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');

        if (!$HostdependenciesTable->existsById($id)) {
            throw new NotFoundException(__('Host dependency not found'));
        }

        $hostdependency = $HostdependenciesTable->getHostdependencyById($id);
        if (!$this->allowedByContainerId(Hash::extract($hostdependency, 'Hostdependency.container_id'))) {
            $this->render403();
            return;
        }
        $hostdependencyEntity = $HostdependenciesTable->get($id);
        if ($HostdependenciesTable->delete($hostdependencyEntity)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    public function loadElementsByContainerId($containerId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException(__('This is only allowed via API.'));
            return;
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $hostgroups = $HostgroupsTable->getHostgroupsByContainerId($containerIds, 'list', 'id');
        $hostgroups = Api::makeItJavaScriptAble($hostgroups);
        $hostgroupsDependent = $hostgroups;

        $hosts = $HostsTable->getHostsByContainerId($containerIds, 'list');
        $hosts = Api::makeItJavaScriptAble($hosts);
        $hostsDependent = $hosts;

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);

        $this->set('hosts', $hosts);
        $this->set('hostsDependent', $hostsDependent);
        $this->set('hostgroups', $hostgroups);
        $this->set('hostgroupsDependent', $hostgroupsDependent);
        $this->set('timeperiods', $timeperiods);
        $this->viewBuilder()->setOption('serialize', [
            'hosts',
            'hostsDependent',
            'hostgroups',
            'hostgroupsDependent',
            'timeperiods'
        ]);
    }

    /**
     * @throws \Exception
     */
    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP, CT_CONTACTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP, CT_CONTACTGROUP]);
        }

        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    /**
     * @param $hostId
     * @return void
     * @throws MissingDbBackendException
     */
    public function dependencyTree($hostId): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        $hostId = (int)$hostId;

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        /** @var Host $host */
        $host = $HostsTable->getHostWithHostgroupsById($hostId);

        if (!$this->allowedByContainerId($host->getContainerIds())) {
            $this->render403();
            return;
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $hostGroupIds = Hash::extract($host['hostgroups'], '{n}.id');
        if (empty($hostGroupIds)) {
            $hostGroupIds = Hash::extract($host['hosttemplate']['hostgroups'], '{n}.id');
        }
        $hostDependencies = $HostdependenciesTable->getHostDependenciesHosts($hostId, $hostGroupIds, $MY_RIGHTS);
        $hostDependencyNodes = [];
        $hostDependencyConnections = [];
        $dependenciesTree = [
            'nodes'       => [],
            'connections' => []
        ];
        foreach ($hostDependencies as $hostdependency) {
            $hostDependencyUuid = $hostdependency['uuid'];
            $hostDependencyNodes[$hostDependencyUuid] = [
                'id'                               => $hostDependencyUuid, //just for foblex
                'hostdependency_id'                => $hostdependency['id'],
                'uuid'                             => $hostDependencyUuid,
                'type'                             => 'dependency',
                'inherits_parent'                  => $hostdependency['inherits_parent'],
                'timeperiod'                       => [
                    'id'   => $hostdependency['timeperiod_id'],
                    'name' => $hostdependency['timeperiod']['name'] ?? null,
                ],
                'execution_fail_on_up'             => $hostdependency['execution_fail_on_up'],
                'execution_fail_on_down'           => $hostdependency['execution_fail_on_down'],
                'execution_fail_on_unreachable'    => $hostdependency['execution_fail_on_unreachable'],
                'execution_fail_on_pending'        => $hostdependency['execution_fail_on_pending'],
                'execution_none'                   => $hostdependency['execution_none'],
                'notification_fail_on_up'          => $hostdependency['notification_fail_on_up'],
                'notification_fail_on_down'        => $hostdependency['notification_fail_on_down'],
                'notification_fail_on_unreachable' => $hostdependency['notification_fail_on_unreachable'],
                'notification_fail_on_pending'     => $hostdependency['notification_fail_on_pending'],
                'notification_none'                => $hostdependency['notification_none'],
            ];

            $dependencyHostgroupsIds = [
                'hostgroups'           => [],
                'dependent_hostgroups' => [],
            ];
            foreach ($hostdependency->get('hostgroups') as $hostgroup) {
                if ($hostgroup['_joinData']['dependent'] === 0) {
                    $dependencyHostgroupsIds['hostgroups'][] = $hostgroup['id'];
                } else {
                    $dependencyHostgroupsIds['dependent_hostgroups'][] = $hostgroup['id'];
                }
            }

            $hostsByHostgroupIds = $HostgroupsTable->getHostsByHostgroupIds(
                $dependencyHostgroupsIds['hostgroups'],
                $MY_RIGHTS
            );
            $dependentHostsByHostgroupIds = $HostgroupsTable->getHostsByHostgroupIds(
                $dependencyHostgroupsIds['dependent_hostgroups'],
                $MY_RIGHTS
            );

            $dependencyHosts = [
                'hosts'           => [],
                'dependent_hosts' => []
            ];
            foreach ($hostdependency->get('hosts') as $host) {
                if ($host['_joinData']['dependent'] === 0) {
                    $dependencyHosts['hosts'][$host['id']] = [
                        'host_id' => $host['id'],
                        'address' => $host['address'],
                        'name'    => $host['name'],
                        'uuid'    => $host['uuid'],
                    ];

                } else {
                    $dependencyHosts['dependent_hosts'][$host['id']] = [
                        'host_id' => $host['id'],
                        'address' => $host['address'],
                        'name'    => $host['name'],
                        'uuid'    => $host['uuid'],
                    ];
                }
            }

            foreach ($hostsByHostgroupIds as $host) {
                $dependencyHosts['hosts'][$host['id']] = [
                    'host_id' => $host['id'],
                    'address' => $host['address'],
                    'name'    => $host['name'],
                    'uuid'    => $host['uuid'],
                ];
            }

            foreach ($dependentHostsByHostgroupIds as $dependentHost) {
                $dependencyHosts['dependent_hosts'][$dependentHost['id']] = [
                    'host_id' => $dependentHost['id'],
                    'address' => $dependentHost['address'],
                    'name'    => $dependentHost['name'],
                    'uuid'    => $dependentHost['uuid'],
                ];
            }

            if (!empty($dependencyHosts['hosts']) || !empty($dependentHosts['dependent_hosts'])) {
                // if size of unique values is 1, then all values are the same, and we can determine if host is dependent or not based on the value
                foreach ($dependencyHosts['hosts'] as $host) {
                    if (!isset($hostDependencyNodes[$host['uuid']])) {
                        $hostDependencyNodes[$host['uuid']] = [
                            'id'      => $host['uuid'],
                            'host_id' => $host['host_id'],
                            'address' => $host['address'],
                            'uuid'    => $host['uuid'],
                            'type'    => 'host',
                            'name'    => $host['name']
                        ];
                    }
                    $hostDependencyConnections[$hostDependencyUuid . $host['uuid']] = [
                        'source' => $host['uuid'],
                        'target' => $hostDependencyUuid
                    ];
                }
            }

            foreach ($dependencyHosts['dependent_hosts'] as $dependentHost) {
                if (!isset($hostDependencyNodes[$dependentHost['uuid']])) {
                    $hostDependencyNodes[$dependentHost['uuid']] = [
                        'id'      => $dependentHost['uuid'],
                        'host_id' => $dependentHost['host_id'],
                        'address' => $dependentHost['address'],
                        'uuid'    => $dependentHost['uuid'],
                        'type'    => 'host',
                        'name'    => $dependentHost['name']
                    ];
                }
                $hostDependencyConnections[$hostDependencyUuid . $dependentHost['uuid']] = [
                    'source' => $hostDependencyUuid,
                    'target' => $dependentHost['uuid']
                ];
            }
        }

        if ($hostDependencyNodes && $hostDependencyConnections) {
            $hostsUuids = Hash::extract($hostDependencyNodes, '{s}[type=host].uuid');
            $hoststatusByUuids = $HoststatusTable->byUuids(
                $hostsUuids,
                (new HoststatusFields($this->DbBackend))
                    ->currentState()
                    ->isHardstate()
                    ->isFlapping()
                    ->problemHasBeenAcknowledged()
                    ->scheduledDowntimeDepth()
                    ->lastCheck()
                    ->currentCheckAttempt()
                    ->maxCheckAttempts()
            );
            //debug($hoststatusByUuids);
            foreach ($hostDependencyNodes as $hostDependencyUuid => $hostDependencyNode) {
                if ($hostDependencyNode['type'] === 'dependency') {
                    continue;
                }
                $Hoststatus = new Hoststatus([]);
                if (isset($hoststatusByUuids[$hostDependencyUuid])) {
                    $Hoststatus = new Hoststatus(
                        $hoststatusByUuids[$hostDependencyUuid]['Hoststatus'],
                        $UserTime
                    );
                }
                $hostDependencyNodes[$hostDependencyUuid]['Hoststatus'] = $Hoststatus->toArray();
            }
            $hostDependencyConnections = array_values($hostDependencyConnections);
            $connectionIndex = 1;
            //add unique index id for Foblex Flow <f-connection></f-connection>
            foreach ($hostDependencyConnections as $index => $connection) {
                $hostDependencyConnections[$index]['id'] = 'conn-' . $connectionIndex++;
            }
            //reindex value to have normal index keys for frontend
            $dependenciesTree = [
                'nodes'       => array_values($hostDependencyNodes),
                'connections' => $hostDependencyConnections
            ];
        }
        $this->set('hostdependenciesTree', $dependenciesTree);
        $this->viewBuilder()->setOption('serialize', ['hostdependenciesTree']);
    }
}
