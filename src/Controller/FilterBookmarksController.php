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

use App\Model\Entity\FilterBookmark;
use App\Model\Table\FilterBookmarksTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;

/**
 * Class FilterBookmarksController
 * @package App\Controller
 */
class FilterBookmarksController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $plugin = $this->request->getQuery('plugin', null);
        $controller = $this->request->getQuery('controller', null);
        $action = $this->request->getQuery('action', null);
        if ($controller === null || $action === null) {
            throw new BadRequestException('Missing parameter');
        }

        $queryFilter = $this->request->getQuery('queryFilter', null);

        /** @var User $User */
        $User = new User($this->getUser());

        /** @var FilterBookmarksTable $FilterBookmarksTable */
        $FilterBookmarksTable = TableRegistry::getTableLocator()->get('FilterBookmarks');

        if ($queryFilter) {
            // User want's to load a specific filter by UUID - filter via URL param
            $bookmark = $FilterBookmarksTable->getFilterByUuid($queryFilter);

            if (!empty($bookmark)) {
                $bookmark = $bookmark->toArray();
                $bookmark['ownership'] = $bookmark['user_id'] === $User->getId();
                if ($bookmark['user_id'] !== $User->getId()) {
                    // This filter belongs to another user
                    unset($bookmark['id']);
                    unset($bookmark['user_id']);
                    unset($bookmark['uuid']);
                }
            }
        }
        $this->set('bookmark', $bookmark ?? null);
        $filterBookmarks = $FilterBookmarksTable->getFilterByUser($User->getId(), $plugin, $controller, $action);
        $this->set('bookmarks', $filterBookmarks);
        $this->viewBuilder()->setOption('serialize', ['bookmarks', 'bookmark']);
    }

    public function add() {
        if (!$this->isApiRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var FilterBookmarksTable $FilterBookmarksTable */
        $FilterBookmarksTable = TableRegistry::getTableLocator()->get('FilterBookmarks');

        $User = new User($this->getUser());

        $data = $this->request->getData(null, []);
        $data['filter'] = json_encode($this->request->getData('filter', '{}'));
        $data['uuid'] = UUID::v4();
        $data['user_id'] = $User->getId();

        $bookmark = $FilterBookmarksTable->newEmptyEntity();
        $bookmark = $FilterBookmarksTable->patchEntity($bookmark, $data);

        $FilterBookmarksTable->save($bookmark);
        if ($bookmark->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $bookmark->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $this->set('bookmark', $bookmark);
        $this->viewBuilder()->setOption('serialize', ['bookmark']);
    }

    public function edit($id) {
        if (!$this->isApiRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var FilterBookmarksTable $FilterBookmarksTable */
        $FilterBookmarksTable = TableRegistry::getTableLocator()->get('FilterBookmarks');

        $User = new User($this->getUser());

        try {
            $bookmark = $FilterBookmarksTable->getByIdAndUserId($id, $User->getId());
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException();
        }

        $data = $this->request->getData(null, []);
        $data['filter'] = json_encode($this->request->getData('filter', '{}'));
        $bookmark->setAccess('id', false);
        $bookmark->setAccess('uuid', false);
        $bookmark->setAccess('user_id', false);

        $bookmark = $FilterBookmarksTable->patchEntity($bookmark, $data);

        $FilterBookmarksTable->save($bookmark);
        if ($bookmark->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $bookmark->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $this->set('bookmark', $bookmark);
        $this->viewBuilder()->setOption('serialize', ['bookmark']);
    }

    public function delete($id) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var User $User */
        $User = new User($this->getUser());

        /** @var FilterBookmarksTable $FilterBookmarksTable */
        $FilterBookmarksTable = TableRegistry::getTableLocator()->get('FilterBookmarks');

        try {
            $bookmark = $FilterBookmarksTable->getByIdAndUserId($id, $User->getId());

            if ($FilterBookmarksTable->delete($bookmark)) {
                $this->set('success', true);
                $this->set('message', __('Filter successfully deleted'));
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('message', __('Error while deleting filter bookmark'));
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function directive() {
        // Only ship HTML template
    }

}
