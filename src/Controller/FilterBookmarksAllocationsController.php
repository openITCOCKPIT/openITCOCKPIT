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

use App\Model\Table\ContainersTable;
use App\Model\Table\FilterBookmarkAllocationsTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\BookmarkAllocationsFilter;


/**
 * Class DashboardAllocationsController
 * @package App\Controller
 */
class FilterBookmarksAllocationsController extends AppController {

    use LocatorAwareTrait;

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var FilterBookmarkAllocationsTable $FilterBookmarkAllocationsTable */
        $FilterBookmarkAllocationsTable = TableRegistry::getTableLocator()->get('FilterBookmarkAllocations');
        $BookmarkAllocationsFilter = new BookmarkAllocationsFilter($this->request);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $User = new User($this->getUser());

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $BookmarkAllocationsFilter->getPage());
        $all_filterbookmark_allocations = $FilterBookmarkAllocationsTable->getBookmarkAllocationsIndex(
            $BookmarkAllocationsFilter,
            $PaginateOMat,
            $User,
            $MY_RIGHTS,
        );
        foreach ($all_filterbookmark_allocations as $key => $all_filterbookmark_allocation) {
            $all_filterbookmark_allocations[$key]['allowEdit'] = $this->isWritableContainer($all_filterbookmark_allocation['container_id']);
        }

        $this->set('all_filterbookmark_allocations', $all_filterbookmark_allocations);
        $this->viewBuilder()->setOption('serialize', ['all_filterbookmark_allocations']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            /** @var FilterBookmarkAllocationsTable $FilterBookmarkAllocationsTable */
            $FilterBookmarkAllocationsTable = TableRegistry::getTableLocator()->get('FilterBookmarkAllocations');

            $allocation = $FilterBookmarkAllocationsTable->newEmptyEntity();
            $allocation = $FilterBookmarkAllocationsTable->patchEntity($allocation, $this->request->getData('FilterBookmarkAllocation', []));

            // Set the author of the allocation
            $User = new User($this->getUser());
            $allocation->set('user_id', $User->getId());

            $FilterBookmarkAllocationsTable->save($allocation);

            if ($allocation->hasErrors()) {
                $this->set('error', $allocation->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                $this->response = $this->response->withStatus(400);
                return;
            }

            //No errors
            $this->set('allocation', $allocation);
            $this->viewBuilder()->setOption('serialize', ['allocation']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var FilterBookmarkAllocationsTable $FilterBookmarkAllocationsTable */
        $FilterBookmarkAllocationsTable = TableRegistry::getTableLocator()->get('FilterBookmarkAllocations');

        if (!$FilterBookmarkAllocationsTable->existsById($id)) {
            throw new NotFoundException('Bookmark Allocation not found');
        }

        $allocation = $FilterBookmarkAllocationsTable->getFilterBookmarkAllocationForEdit($id);
        if (!$this->allowedByContainerId($allocation['FilterBookmarkAllocation']['container_id'], true)) {
            $this->render403();
            return;
        }

        if ($this->request->is('get')) {
            $this->set('allocation', $allocation);
            $this->viewBuilder()->setOption('serialize', ['allocation']);
            return;
        }


        if ($this->request->is('post')) {
            $allocation = $FilterBookmarkAllocationsTable->get($id);

            //$allocation->setAccess('author', false); //Otherwise CakePHP will try to create a new user
            $allocation = $FilterBookmarkAllocationsTable->patchEntity($allocation, $this->request->getData('BookmarkAllocation', []));
            $FilterBookmarkAllocationsTable->save($allocation);

            if ($allocation->hasErrors()) {
                $this->set('error', $allocation->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                $this->response = $this->response->withStatus(400);
                return;
            }

            //No errors
            $this->set('allocation', $allocation);
            $this->viewBuilder()->setOption('serialize', ['allocation']);
            return;
        }

    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var FilterBookmarkAllocationsTable $FilterBookmarkAllocationsTable */
        $FilterBookmarkAllocationsTable = TableRegistry::getTableLocator()->get('FilterBookmarkAllocations');

        if (!$FilterBookmarkAllocationsTable->existsById($id)) {
            throw new NotFoundException(__('Dashboard allocation not found'));
        }

        $allocation = $FilterBookmarkAllocationsTable->get($id);

        if (!$this->allowedByContainerId($allocation->container_id)) {
            $this->render403();
            return;
        }

        if ($FilterBookmarkAllocationsTable->delete($allocation)) {

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

    public function loadElementsByContainerId($containerId) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        /** @var FilterBookmarkAllocationsTable $FilterBookmarkAllocationsTable */
        $FilterBookmarkAllocationsTable = TableRegistry::getTableLocator()->get('FilterBookmarkAllocations');

        if (!$ContainersTable->existsById($containerId)) {
            throw new NotFoundException(__('Invalid container'));
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges === true) {
            $MY_RIGHTS = [];
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $users = $UsersTable->usersByContainerId($containerIds, 'list');
        $users = Api::makeItJavaScriptAble($users);

        $usergroups = $UsergroupsTable->getUsergroupsList();
        $usergroups = Api::makeItJavaScriptAble($usergroups);

        $filterBookmarks = $UsersTable->getFilterBookmarksByContainerIdsAsList($containerIds, $MY_RIGHTS);
        $filterBookmarks = Api::makeItJavaScriptAble($filterBookmarks);

        $allAllocatedFilterBookmarkIds = $FilterBookmarkAllocationsTable->getAllocatedBookmarkIdsByContainerIdsAsList($containerIds, $MY_RIGHTS);

        $this->set('users', $users);
        $this->set('usergroups', $usergroups);
        $this->set('filter_bookmarks', $filterBookmarks);
        $this->set('allocated_filter_bookmarks', $allAllocatedFilterBookmarkIds);

        $this->viewBuilder()->setOption('serialize', [
            'users',
            'usergroups',
            'filter_bookmarks',
            'allocated_filter_bookmarks'
        ]);
    }




}
