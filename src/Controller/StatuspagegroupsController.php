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

use App\Model\Entity\Statuspagegroup;
use App\Model\Table\ContainersTable;
use App\Model\Table\StatuspagegroupsTable;
use App\Model\Table\StatuspagesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Filter\StatuspagesFilter;

/**
 * Statuspagegroups Controller
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class StatuspagegroupsController extends AppController {
    /**
     * Initialize controller
     *
     * @return void
     */


    /**
     * @return void
     */
    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Statuspagegroups.name',
                'Statuspagegroups.description'
            ]
        ]);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $statuspagegroups = $StatuspagegroupsTable->getStatuspagegroupsIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($statuspagegroups as $index => $statuspagegroup) {
            $statuspagegroups[$index]['container'] = '/' . $ContainersTable->treePath($statuspagegroup['container_id']);
            if ($this->hasRootPrivileges === true) {
                $statuspagegroups[$index]['allowEdit'] = true;
                $statuspagegroups[$index]['allowView'] = true;
            } else {
                $statuspagegroups[$index]['allowEdit'] = $this->isWritableContainer($statuspagegroup['container_id']);
                $statuspagegroups[$index]['allowView'] = in_array($statuspagegroup['container_id'], $MY_RIGHTS, true);
            }
        }
        $this->set('all_statuspagegroups', $statuspagegroups);
        $this->viewBuilder()->setOption('serialize', ['all_statuspagegroups']);

    }

    /**
     * View method
     *
     * @param string|null $id Statuspagegroup id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $id = (int)$id;
        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');
        $statuspagegroup = $StatuspagegroupsTable->getStatuspagegroupForById($id);
        if (!$this->allowedByContainerId($statuspagegroup['container_id'])) {
            $this->render403();
            return;
        }
        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');

        // Get cumulated status per status page AND total cumulated status for the group
        $statuspagesFormated = [];
        if (!empty($statuspagegroup)) {
            $statuspageIds = Hash::combine($statuspagegroup['statuspages_memberships'], '{n}.statuspage_id', '{n}.statuspage_id');
            $statuspages = $StatuspagesTable->getStatuspageWithAllObjects($statuspageIds, $MY_RIGHTS);
            $allHostUuids = [];
            $allServiceUuids = [];


            foreach ($statuspages as $statuspage) {
                $hostsWithServices = [
                    'hosts' => []
                ];

                //Hosts
                foreach ($statuspage['hosts'] as $host) {
                    if (!isset($hostsWithServices['hosts'][$host['uuid']])) {
                        $hostsWithServices['hosts'][$host['uuid']] = [
                            'services' => []
                        ];
                        $allHostUuids[$host['id']] = $host['uuid'];

                        foreach ($host['services'] as $service) {
                            $hostsWithServices['hosts'][$host['uuid']]['services'][$service['uuid']] = $service['id'];
                            $allServiceUuids[$service['id']] = $service['uuid'];
                        }
                    }
                }
                //Services
                foreach ($statuspage['services'] as $service) {
                    if (!isset($hostsWithServices['hosts'][$service['host']['uuid']])) {
                        $hostsWithServices['hosts'][$service['host']['uuid']] = [
                            'services' => []
                        ];
                    }
                    $hostsWithServices['hosts'][$service['host']['uuid']]['services'][$service['uuid']] = $service['id'];
                    $allHostUuids[$service['host']['id']] = $service['host']['uuid'];
                    $allServiceUuids[$service['id']] = $service['uuid'];
                }
                //Host groups
                foreach ($statuspage['hostgroups'] as $key => $hostgroup) {
                    foreach ($hostgroup['hosts'] as $host) {
                        if (!isset($hostsWithServices['hosts'][$host['uuid']])) {
                            $hostsWithServices['hosts'][$host['uuid']] = [
                                'services' => []
                            ];
                        }
                        $allHostUuids[$host['id']] = $host['uuid'];
                        foreach ($host['services'] as $service) {
                            $hostsWithServices['hosts'][$host['uuid']]['services'][$service['uuid']] = $service['id'];
                            $allServiceUuids[$service['id']] = $service['uuid'];
                        }
                    }

                    foreach ($hostgroup['hosttemplates'] as $hosttemplate) {
                        foreach ($hosttemplate['hosts'] as $host) {
                            if (!isset($hostsWithServices['hosts'][$host['uuid']])) {
                                $hostsWithServices['hosts'][$host['uuid']] = [
                                    'services' => []
                                ];
                            }
                            $allHostUuids[$host['id']] = $host['uuid'];
                            foreach ($host['services'] as $service) {
                                $hostsWithServices['hosts'][$host['uuid']]['services'][$service['uuid']] = $service['id'];
                                $allServiceUuids[$service['id']] = $service['uuid'];
                            }
                        }
                    }
                }
                //Service groups
                foreach ($statuspage['servicegroups'] as $key => $servicegroup) {
                    foreach ($servicegroup['services'] as $service) {
                        if (!isset($hostsWithServices['hosts'][$service['host']['uuid']])) {
                            $hostsWithServices['hosts'][$service['host']['uuid']] = [
                                'services' => []
                            ];
                        }
                        $hostsWithServices['hosts'][$service['host']['uuid']]['services'][$service['uuid']] = $service['id'];

                        $allServiceUuids[$service['id']] = $service['uuid'];
                        $allHostUuids[$service['host']['id']] = $service['host']['uuid'];
                    }

                    foreach ($servicegroup['servicetemplates'] as $servicetemplate) {
                        foreach ($servicetemplate['services'] as $service) {
                            if (!isset($hostsWithServices['hosts'][$service['host']['uuid']])) {
                                $hostsWithServices['hosts'][$service['host']['uuid']] = [
                                    'services' => []
                                ];
                            }
                            $hostsWithServices['hosts'][$service['host']['uuid']['uuid']]['services'][$service['uuid']] = $service['id'];
                            $allServiceUuids[$service['id']] = $service['uuid'];
                            $allHostUuids[$service['host']['id']] = $service['host']['uuid'];
                        }
                    }
                }

                $statuspagesFormated[$statuspage['id']] = [
                    'statuspage'               => [
                        'id'                => $statuspage['id'],
                        'uuid'              => $statuspage['uuid'],
                        'container_id'      => $statuspage['container_id'],
                        'name'              => $statuspage['name'],
                        'description'       => $statuspage['description'],
                        'public_title'      => $statuspage['public_title'],
                        'public_identifier' => $statuspage['public_identifier'],
                        'public'            => $statuspage['public'],
                    ],
                    'hostsWithServices'        => $hostsWithServices,
                    'cumulatedState'           => Statuspagegroup::CUMULATED_STATE_NOT_IN_MONITORING,
                    'host_acknowledgements'    => 0, // Total amount of host acknowledgements
                    'host_downtimes'           => 0, // Total amount of host downtimes
                    'host_total'               => sizeof($hostsWithServices['hosts']), // Total amount of hosts
                    'host_problems'            => 0, // Hosts in none up state
                    'service_acknowledgements' => 0, // Total amount of service acknowledgements
                    'service_downtimes'        => 0, // Total amount of service downtimes
                    'service_total'            => Hash::apply($hostsWithServices['hosts'], '{s}.services.{s}', 'sizeof'), // Total amount of services
                    'service_problems'         => 0, // Services in none up state
                ];
            }

            // Query host and service status for all objects in two queries
            $DbBackend = new DbBackend();
            $HoststatusTable = $DbBackend->getHoststatusTable();
            $ServicestatusTable = $DbBackend->getServicestatusTable();

            $HoststatusFields = new HoststatusFields($DbBackend);
            $HoststatusFields
                ->currentState()
                ->isHardstate()
                ->problemHasBeenAcknowledged()
                ->scheduledDowntimeDepth();

            $ServicestatusFields = new ServicestatusFields($DbBackend);
            $ServicestatusFields
                ->currentState()
                ->isHardstate()
                ->problemHasBeenAcknowledged()
                ->scheduledDowntimeDepth();

            $AllHoststatus = $HoststatusTable->byUuid($allHostUuids, $HoststatusFields);
            $AllServicestatus = $ServicestatusTable->byUuids($allServiceUuids, $ServicestatusFields);

            foreach ($statuspagesFormated as $statuspageId => $statuspage) {
                $cumulatedState = Statuspagegroup::CUMULATED_STATE_NOT_IN_MONITORING;
                foreach ($statuspage['hostsWithServices']['hosts'] as $hostUuid => $services) {
                    if (!isset($AllHoststatus[$hostUuid]['Hoststatus'])) {
                        continue;
                    }
                    $Hoststatus = new Hoststatus($AllHoststatus[$hostUuid]['Hoststatus']);
                    if ($Hoststatus->currentState() > 0) {
                        $statuspagesFormated[$statuspageId]['host_problems']++;
                    }
                    if ($Hoststatus->isAcknowledged() && $Hoststatus->currentState() > 0) {
                        $statuspagesFormated[$statuspageId]['host_acknowledgements']++;
                    }
                    if ($Hoststatus->isInDowntime()) {
                        $statuspagesFormated[$statuspageId]['host_downtimes']++;
                    }

                    if ($Hoststatus->currentState() > 0) {
                        // Host is down or unreachable - use the host status only
                        // +1 shifts a host state into a service state so we can use a single array
                        if ($Hoststatus->currentState() + 1 > $cumulatedState) {
                            $cumulatedState = $Hoststatus->currentState() + 1;
                        }

                        // Loop through services to count problems, acks and downtimes (just for the statistics)
                        foreach ($services['services'] as $serviceUuid => $serviceId) {
                            if (!isset($AllServicestatus[$serviceUuid]['Servicestatus'])) {
                                continue;
                            }
                            $Servicestatus = new Servicestatus($AllServicestatus[$serviceUuid]['Servicestatus']);

                            if ($Servicestatus->currentState() > 0) {
                                $statuspagesFormated[$statuspageId]['service_problems']++;
                            }
                            if ($Servicestatus->isAcknowledged() && $Servicestatus->currentState() > 0) {
                                $statuspagesFormated[$statuspageId]['service_acknowledgements']++;
                            }
                            if ($Servicestatus->isInDowntime()) {
                                $statuspagesFormated[$statuspageId]['service_downtimes']++;
                            }

                            if ($Servicestatus->currentState() > $cumulatedState) {
                                $cumulatedState = $Servicestatus->currentState();
                            }
                        }

                        continue;
                    }

                    // If the host is up -> use worst service state
                    // IF host is down (or unreachable) use the host state (service state not needed in this case)
                    // This is the same behavior as we use on Maps and Statuspages

                    // Host is UP - use cumulated service status (just like on maps)
                    // Merge host state and service state into one single cumulated state
                    foreach ($services['services'] as $serviceUuid => $serviceId) {
                        if (!isset($AllServicestatus[$serviceUuid]['Servicestatus'])) {
                            continue;
                        }
                        $Servicestatus = new Servicestatus($AllServicestatus[$serviceUuid]['Servicestatus']);

                        if ($Servicestatus->currentState() > 0) {
                            $statuspagesFormated[$statuspageId]['service_problems']++;
                        }
                        if ($Servicestatus->isAcknowledged() && $Servicestatus->currentState() > 0) {
                            $statuspagesFormated[$statuspageId]['service_acknowledgements']++;
                        }
                        if ($Servicestatus->isInDowntime()) {
                            $statuspagesFormated[$statuspageId]['service_downtimes']++;
                        }

                        if ($Servicestatus->currentState() > $cumulatedState) {
                            $cumulatedState = $Servicestatus->currentState();
                        }
                    }
                }
                $statuspagesFormated[$statuspageId]['cumulatedState'] = $cumulatedState;

                // Remove hosts and services array to save memory (and to not have it in the response JSON)
                unset($statuspagesFormated[$statuspageId]['hostsWithServices']);
            }

        }

        // Clear some memory
        unset($statuspages, $AllHoststatus, $AllServicestatus);
        $cumulatedStategroupState = Statuspagegroup::CUMULATED_STATE_NOT_IN_MONITORING;
        $allCumulatedStatuspageStates = Hash::extract($statuspagesFormated, '{n}.cumulatedState');
        if (!empty($allCumulatedStatuspageStates)) {
            // We have to check for empty array - max() throws an error then
            $cumulatedStategroupState = max($allCumulatedStatuspageStates);
        }

        $this->set('statuspages', array_values($statuspagesFormated));
        $this->set('cumulatedStategroupState', $cumulatedStategroupState);

        $this->viewBuilder()->setOption('serialize', ['statuspages', 'cumulatedStategroupState']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');

        if ($this->request->is('post')) {
            $statuspagegroup = $StatuspagegroupsTable->newEmptyEntity();
            $statuspagegroup->setAccess('id', false);
            $statuspagegroup->setAccess('statuspages_membership', false);

            $statuspagegroup = $StatuspagegroupsTable->patchEntity($statuspagegroup, $this->request->getData(null, []));

            $StatuspagegroupsTable->save($statuspagegroup);
            if ($statuspagegroup->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $statuspagegroup->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($statuspagegroup); // REST API ID serialization
                    return;
                }
            }
            $this->set('statuspagegroup', $statuspagegroup);
            $this->viewBuilder()->setOption('serialize', ['statuspagegroup']);
        }
    }

    /**
     * Edit method
     *
     * @param string|null $id Statuspagegroup id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $id = (int)$id;
        if (!$StatuspagegroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid status page group'));
        }

        $statuspagegroup = $StatuspagegroupsTable->getStatuspagegroupForEdit($id);
        if (!$this->allowedByContainerId($statuspagegroup->container_id)) {
            $this->render403();
            return;
        }

        $oldContainerId = $statuspagegroup->container_id; // Save old container_id to check if container was changed -> necessary for status page cleanup
        if ($this->request->is('post')) {
            $statuspagegroup->setAccess('id', false);
            $statuspagegroup->setAccess('statuspages_membership', false);
            $statuspagegroup = $StatuspagegroupsTable->patchEntity($statuspagegroup, $this->request->getData(null, []));

            $newContainerId = $statuspagegroup->container_id;

            if ($oldContainerId != $newContainerId) {
                $oldContainerIds = $ContainersTable->resolveChildrenOfContainerIds(
                    $oldContainerId,
                    true,
                    [
                        CT_GLOBAL,
                        CT_TENANT,
                        CT_LOCATION,
                        CT_NODE
                    ]
                );
                $newContainerIds = $ContainersTable->resolveChildrenOfContainerIds(
                    $newContainerId,
                    true,
                    [
                        CT_GLOBAL,
                        CT_TENANT,
                        CT_LOCATION,
                        CT_NODE
                    ]
                );

                $containersToRemove = array_diff($oldContainerIds, $newContainerIds);
                if (!empty($containersToRemove)) {
                    $StatuspagegroupsTable->_cleanupStatuspagesMembershipsByRemovedContainerIds(
                        $statuspagegroup->id,
                        $containersToRemove
                    );
                }
            }

            $StatuspagegroupsTable->save($statuspagegroup);
            if ($statuspagegroup->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $statuspagegroup->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($statuspagegroup); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('statuspagegroup', $statuspagegroup);
        $this->viewBuilder()->setOption('serialize', ['statuspagegroup']);

    }

    /**
     * editStepTwo method
     * In this step, the user can only assign status pages to the group
     *
     * @param string|null $id Statuspagegroup id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function editStepTwo($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');

        $id = (int)$id;
        if (!$StatuspagegroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid status page group'));
        }

        if ($this->request->is('get')) {
            $statuspagegroup = $StatuspagegroupsTable->getStatuspagegroupForEdit($id);
            if (!$this->allowedByContainerId($statuspagegroup->container_id)) {
                $this->render403();
                return;
            }

            /** @var StatuspagesTable $StatuspagesTable */
            $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');
            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            if ($statuspagegroup->container_id == ROOT_CONTAINER) {
                //Don't panic! Only root users can edit /root objects ;)s
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true, [
                    CT_GLOBAL,
                    CT_TENANT,
                    CT_LOCATION,
                    CT_NODE
                ]);
            } else {
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds($statuspagegroup->container_id, false, [
                    CT_GLOBAL,
                    CT_TENANT,
                    CT_LOCATION,
                    CT_NODE
                ]);
            }

            $statuspages = Api::makeItJavaScriptAble(
                $StatuspagesTable->getStatuspagesList($containerIds)
            );

            $this->set('statuspagegroup', $statuspagegroup);
            $this->set('statuspages', $statuspages);
            $this->viewBuilder()->setOption('serialize', ['statuspagegroup', 'statuspages']);
            return;
        }

        if ($this->request->is('post')) {
            $statuspagegroup = $StatuspagegroupsTable->get($id, contain: [
                'StatuspagesMemberships'
            ]);
            if (!$this->allowedByContainerId($statuspagegroup->container_id)) {
                $this->render403();
                return;
            }

            $statuspagegroup->setAccess('statuspages_membership', true);
            $statuspagegroup->setAccess('statuspagegroup_categories', false);
            $statuspagegroup->setAccess('statuspagegroup_collections', false);
            $statuspagegroup = $StatuspagegroupsTable->patchEntity($statuspagegroup, $this->request->getData(null, []));

            $StatuspagegroupsTable->save($statuspagegroup);
            if ($statuspagegroup->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $statuspagegroup->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($statuspagegroup); // REST API ID serialization
                    return;
                }
            }

            $this->set('statuspagegroup', $statuspagegroup);
            $this->viewBuilder()->setOption('serialize', ['statuspagegroup']);
        }


    }

    /**
     * @param int|null $id
     */
    public function delete($id = null): void {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');

        $id = (int)$id;
        if (!$StatuspagegroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid status page group'));
        }

        $statuspagegroup = $StatuspagegroupsTable->get($id, contain: [
            'StatuspagegroupCategories',
            'StatuspagegroupCollections',
            'StatuspagesMemberships'
        ]);
        if (!$this->allowedByContainerId($statuspagegroup['container_id'])) {
            $this->render403();
            return;
        }
        if ($StatuspagegroupsTable->delete($statuspagegroup)) {
            $this->set('success', true);
            $this->set('message', __('Status page group deleted successfully'));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('message', __('Issue while deleting Status page group'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }


    /****************************
     *       AJAX METHODS       *
     ****************************/

    /**
     * @return void
     * @throws \Exception
     */
    public function loadContainers() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOST, [], false, [CT_HOSTGROUP]);
        }
        $containers = Api::makeItJavaScriptAble($containers);
        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    public function loadStatuspagesByString() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $statuspagesFilter = new StatuspagesFilter($this->request);


        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');
        $selected = $this->request->getQuery('selected');

        $MY_RIGHTS = [];
        if (!$this->hasRootPrivileges) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $statuspages = Api::makeItJavaScriptAble(
            $StatuspagesTable->getStatuspagesForAngular($selected, $statuspagesFilter, $MY_RIGHTS)
        );

        $this->set('statuspages', $statuspages);
        $this->viewBuilder()->setOption('serialize', ['statuspages']);
    }

}
