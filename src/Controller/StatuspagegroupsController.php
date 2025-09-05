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

use App\Model\Table\ContainersTable;
use App\Model\Table\StatuspagegroupsTable;
use App\Model\Table\StatuspagesMembershipTable;
use App\Model\Table\StatuspagesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
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

    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');

        if ($this->request->is('post')) {
            $statuspagegroup = $StatuspagegroupsTable->newEmptyEntity();
            $statuspagegroup->setAccess('id', false);
            $statuspagegroup->setAccess('statuspages', false);

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
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get('Statuspagegroups');

        $id = (int)$id;
        if (!$StatuspagegroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid status page group'));
        }

        $statuspagegroup = $StatuspagegroupsTable->getStatuspagegroupForEdit($id);
        if (!$this->allowedByContainerId($statuspagegroup->container_id)) {
            $this->render403();
            return;
        }

        if ($this->request->is('post')) {
            $statuspagegroup->setAccess('id', false);
            $statuspagegroup->setAccess('statuspages', false);
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
            throw new \Cake\Http\Exception\MethodNotAllowedException();
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
            $selected = $this->request->getQuery('selected');

            $MY_RIGHTS = [];
            if (!$this->hasRootPrivileges) {
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $statuspages = Api::makeItJavaScriptAble(
                $StatuspagesTable->getStatuspagesList($MY_RIGHTS)
            );

            $this->set('statuspagegroup', $statuspagegroup);
            $this->set('statuspages', $statuspages);

            $this->set('statuspagegroup', $statuspagegroup);
            $this->set('statuspages', $statuspages);
            $this->viewBuilder()->setOption('serialize', ['statuspagegroup', 'statuspages']);
            return;
        }

        if ($this->request->is('post')) {
            $statuspagegroup = $StatuspagegroupsTable->get($id);
            if (!$this->allowedByContainerId($statuspagegroup->container_id)) {
                $this->render403();
                return;
            }

            /** @var StatuspagesMembershipTable $StatuspagesMembershipTable */
            $StatuspagesMembershipTable = TableRegistry::getTableLocator()->get('StatuspagesMembership');

            // This is a workaround for https://github.com/cakephp/cakephp/issues/18885
            $StatuspagesMembershipTable->deleteAllRecordsByStatuspagegroupId($statuspagegroup->id);
            $data = $this->request->getData(null, []);
            if (empty($data['statuspages'])) {
                $data['statuspages'] = [];
            }

            $joinTableRecords = [];
            foreach ($data['statuspages'] as $statuspage) {
                $joinTableRecords[] = Hash::remove($statuspage['_joinData'], 'id');
            }

            $joinTableEntities = $StatuspagesMembershipTable->newEntities($joinTableRecords);
            $StatuspagesMembershipTable->saveMany($joinTableEntities);
            foreach ($joinTableEntities as $entity) {
                if ($entity->hasErrors()) {
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $entity->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }
            }

            if ($this->isJsonRequest()) {
                $this->serializeCake4Id($statuspagegroup); // REST API ID serialization
                return;
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
            'Statuspages'
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
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, CT_TENANT, [], $this->hasRootPrivileges);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), CT_TENANT, [], true);
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
