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


use App\Model\Table\ContainersTable;
use App\Model\Table\StatuspagesTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\StatuspagesFilter;


class StatuspagesController extends AppController {

    //https://discourse.cakephp.org/t/bypass-authentication/9197/3
    //a condition in src/Policy/RequestPolicy->canAccess is alsonecessary here
    public function beforeFilter(EventInterface $event) {
        parent::beforeFilter($event);
        //$this->Authentication->addUnauthenticatedActions(['publicView']);
        $this->Authentication->allowUnauthenticated(['publicView']);
    }


    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index($withState = false) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

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

        $statuspagesFilter = new StatuspagesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $statuspagesFilter->getPage());
        $all_statuspages = $StatuspagesTable->getStatuspagesIndex($statuspagesFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($all_statuspages as $key => $statuspage) {

            if ($withState) {
                $statuspageViewData = $StatuspagesTable->getStatuspageForView((int)$statuspage['id'], $MY_RIGHTS, $UserTime);
                $all_statuspages[$key]['cumulatedState'] = $statuspageViewData['statuspage']['cumulatedColorId'];
                $all_statuspages[$key]['color'] = 'bg-' . $statuspageViewData['statuspage']['cumulatedColor'];
                $all_statuspages[$key]['css_color'] = $statuspageViewData['statuspage']['cumulatedColor'];
            }

            $all_statuspages[$key]['allow_edit'] = true;
            if ($this->hasRootPrivileges === false) {
                $all_statuspages[$key]['allow_edit'] = $this->isWritableContainer($statuspage['container_id']);
            }
        }

        $this->set('all_statuspages', $all_statuspages);
        $this->viewBuilder()->setOption('serialize', ['all_statuspages']);
    }


    /**
     * View method
     *
     * @param string|null $id Statuspage id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Http\Exception\NotFoundException; When record not found.
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $id = (int)$id;

        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');
        if (!$StatuspagesTable->existsById($id)) {
            throw new NotFoundException('Statuspage not found');
        }
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $statuspageViewData = $StatuspagesTable->getStatuspageForView($id, $MY_RIGHTS, $UserTime);
        $this->set('Statuspage', $statuspageViewData);
        $this->viewBuilder()->setOption('serialize', ['Statuspage']);
    }


    /**
     * Public View method
     * USED BY THE NEW ANGULAR FRONTEND !!
     *
     * @param string|null $id Statuspage id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Http\Exception\NotFoundException
     * @throws \Cake\Http\Exception\MethodNotAllowedException
     */
    public function publicView(null|string|int $idOrUuidOrPublicIdentifier = null) {
        if (empty($idOrUuidOrPublicIdentifier)) {
            throw new NotFoundException('Statuspage not found');
        }

        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');

        // Did the user pass a numeric ID?
        if (is_numeric($idOrUuidOrPublicIdentifier)) {
            $id = (int)$idOrUuidOrPublicIdentifier;
        } else {
            // Did the user pass a UUID or a public identifier?
            $id = 0;
            $isUuid = UUID::is_valid($idOrUuidOrPublicIdentifier);
            if ($isUuid) {
                $entity = $StatuspagesTable->getStatuspageByUuid($idOrUuidOrPublicIdentifier);
                $id = $entity->id;
            } else {
                // User passed a public identifier like "/datacenter" or "/my-status-page"
                $entity = $StatuspagesTable->getStatuspageByPublicIdentifier($idOrUuidOrPublicIdentifier);
                $id = $entity->id;
            }

        }

        if (!$StatuspagesTable->existsById($id)) {
            throw new NotFoundException(__('Statuspage not found'));
        }
        if (!$StatuspagesTable->isPublic($id)) {
            // We don't want to be too honest at this point so that it is not possible to bruteforce existing Statuspages
            // GitHub does this the same way, if you are not logged in you get a Not found error on private repisitories.
            throw new NotFoundException(__('Statuspage not found'));
        }
        $this->viewBuilder()->setLayout('statuspage_public');
        $UserTime = new UserTime(date_default_timezone_get(), 'd.m.Y H:i:s');
        $statuspageViewData = $StatuspagesTable->getStatuspageForView((int)$id, [], $UserTime);
        $this->set('statuspage', $statuspageViewData);
        $this->set('systemname', $this->getSystemname());
        $this->set('id', $id);
        $this->viewBuilder()->setOption('serialize', ['Statuspage']);
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

        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');

        if ($this->request->is('post') || $this->request->is('put')) {
            $statuspage = $StatuspagesTable->newEmptyEntity();

            $data = $this->request->getData('Statuspage', []);
            // empty(false) is also true, which is useful here for checking $data['Statuspage']['public']
            if (empty($data['Statuspage']['public']) || empty($data['Statuspage']['public_identifier'])) {
                $data['Statuspage']['public_identifier'] = null;
            }

            $statuspage = $StatuspagesTable->patchEntity($statuspage, $data);
            $statuspage->set('uuid', UUID::v4());
            $StatuspagesTable->save($statuspage);
            if ($statuspage->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $statuspage->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if ($this->isJsonRequest()) {
                $this->serializeCake4Id($statuspage); // REST API ID serialization
                return;
            }

            $this->set('statuspage', $statuspage);
            $this->viewBuilder()->setOption('serialize', ['statuspage']);
        }
    }

    /**
     *
     * edit
     *
     * @param string|null $id Statuspage id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');
        if (!$StatuspagesTable->existsById($id)) {
            throw new NotFoundException('Status page not found');
        }
        $statuspage = $StatuspagesTable->getStatuspageForEdit($id);
        if (!$this->allowedByContainerId($statuspage['Statuspage']['container_id'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $data = $this->request->getData('Statuspage', []);

            // empty(false) is also true, which is useful here for checking $data['Statuspage']['public']
            if (empty($data['Statuspage']['public']) || empty($data['Statuspage']['public_identifier'])) {
                $data['Statuspage']['public_identifier'] = null;
            }

            $statuspage = $StatuspagesTable->get($id);
            $statuspage->setAccess('uuid', false);
            $statuspage = $StatuspagesTable->patchEntity($statuspage, $data);
            $StatuspagesTable->save($statuspage);
            if ($statuspage->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $statuspage->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('statuspage', $statuspage);
            $this->viewBuilder()->setOption('serialize', ['statuspage']);
        }

        $this->set('statuspage', $statuspage);
        $this->viewBuilder()->setOption('serialize', ['statuspage']);

    }

    /**
     * Delete method
     *
     * @param string|null $id Statuspage id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException
     * @throws \Cake\Http\Exception\MethodNotAllowedException
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');
        if (!$StatuspagesTable->existsById($id)) {
            throw new NotFoundException('Statuspage not found');
        }

        $statuspage = $StatuspagesTable->get($id);


        if (!$this->isWritableContainer($statuspage['container_id'])) {
            $this->render403();
            return;
        }

        if ($StatuspagesTable->delete($statuspage)) {
            $this->set('success', true);
            $this->set('id', $id);
            $this->viewBuilder()->setOption('serialize', ['success', 'id']);

        } else {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->viewBuilder()->setOption('serialize', ['success', 'id']);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
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
