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
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;

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
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {

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
            $statuspagegroup->setAccess('statuspagegroup_categories', false);
            $statuspagegroup->setAccess('statuspagegroup_collections', false);
            $statuspagegroup->setAccess('statuspages_memberships', false);

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
            throw new NotFoundException(__('Host not found'));
        }

        $statuspagegroup = $StatuspagegroupsTable->getStatuspagegroupForEdit($id);
        if (!$this->allowedByContainerId($statuspagegroup->container_id)) {
            $this->render403();
            return;
        }

        if ($this->request->is('post')) {
            $statuspagegroup->setAccess('id', false);
            $statuspagegroup->setAccess('statuspages_memberships', false);
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
     * Delete method
     *
     * @param string|null $id Statuspagegroup id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null) {


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
}
