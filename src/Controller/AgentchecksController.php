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

use App\Model\Entity\Agentcheck;
use App\Model\Table\AgentchecksTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\AgentchecksFilter;

/**
 * Class AgentchecksController
 * @package App\Controller
 */
class AgentchecksController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var AgentchecksTable $AgentchecksTable */
        $AgentchecksTable = TableRegistry::getTableLocator()->get('Agentchecks');

        $AgentchecksFilter = new AgentchecksFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AgentchecksFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }
        $agentchecks = $AgentchecksTable->getAgentchecksIndex($AgentchecksFilter, $PaginateOMat, $MY_RIGHTS);


        $all_agentchecks = [];
        foreach ($agentchecks as $index => $agentcheck) {
            /** @var Agentcheck $agentcheck */
            $all_agentchecks[$index] = $agentcheck->toArray();
            $all_agentchecks[$index]['allow_edit'] = true;
            if ($this->hasRootPrivileges === false) {
                $all_agentchecks[$index]['allow_edit'] = $this->isWritableContainer($agentcheck->get('servicetemplate')->get('container_id'));
            }
        }


        $this->set('all_agentchecks', $all_agentchecks);
        $toJson = ['all_agentchecks', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_agentchecks', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            /** @var $AgentchecksTable AgentchecksTable */
            $AgentchecksTable = TableRegistry::getTableLocator()->get('Agentchecks');
            $agentcheck = $AgentchecksTable->newEmptyEntity();
            $agentcheck = $AgentchecksTable->patchEntity($agentcheck, $this->request->getData('Agentcheck'));

            $AgentchecksTable->save($agentcheck);
            if ($agentcheck->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $agentcheck->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($agentcheck); // REST API ID serialization
                    return;
                }
            }
            $this->set('agentcheck', $agentcheck);
            $this->viewBuilder()->setOption('serialize', ['agentcheck']);
        }
    }

    /**
     * @param int|null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var AgentchecksTable $AgentchecksTable */
        $AgentchecksTable = TableRegistry::getTableLocator()->get('Agentchecks');

        if (!$AgentchecksTable->existsById($id)) {
            throw new NotFoundException(__('Agentcheck not found'));
        }

        $agentcheck = $AgentchecksTable->getAgentcheckById($id);

        $allowEdit = $this->isWritableContainer($agentcheck->get('servicetemplate')->get('container_id'));
        if (!$allowEdit) {
            $this->render403();
            return;
        }

        if ($this->request->is('post')) {
            $agentcheck = $AgentchecksTable->patchEntity($agentcheck, $this->request->getData('Agentcheck'));

            $AgentchecksTable->save($agentcheck);
            if ($agentcheck->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $agentcheck->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($agentcheck); // REST API ID serialization
                    return;
                }
            }
        }
        $this->set('agentcheck', $agentcheck);
        $this->viewBuilder()->setOption('serialize', ['agentcheck']);
    }

    /**
     * @param int $id
     */
    public function delete($id) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var AgentchecksTable $AgentchecksTable */
        $AgentchecksTable = TableRegistry::getTableLocator()->get('Agentchecks');

        if (!$AgentchecksTable->existsById($id)) {
            throw new NotFoundException(__('Agentcheck not found'));
        }

        $agentcheck = $AgentchecksTable->getAgentcheckById($id);

        $allowEdit = $this->isWritableContainer($agentcheck->get('servicetemplate')->get('container_id'));
        if (!$allowEdit) {
            $this->render403();
            return;
        }

        if ($AgentchecksTable->delete($agentcheck)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function loadServicetemplates() {
        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $servicetemplates = $ServicetemplatesTable->getServicetemplatesByContainerId($this->MY_RIGHTS, 'list', OITC_AGENT_SERVICE);
        $servicetemplates = Api::makeItJavaScriptAble($servicetemplates);

        $this->set('servicetemplates', $servicetemplates);
        $this->viewBuilder()->setOption('serialize', ['servicetemplates']);
    }

}
