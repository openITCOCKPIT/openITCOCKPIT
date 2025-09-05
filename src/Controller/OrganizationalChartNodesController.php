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
use App\Model\Table\UsersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;

/**
 * OrganizationalChartNodes Controller
 *
 * @property \App\Model\Table\OrganizationalChartNodesTable $OrganizationalChartNodes
 * @method \App\Model\Entity\OrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class OrganizationalChartNodesController extends AppController {

    /****************************
     *       AJAX METHODS       *
     ****************************/
    public function loadUsers($containerIds = null) {
        if (!$this->isJsonRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerIds);
        $users = $UsersTable->usersByContainerId($containerIds, 'list');
        $users = Api::makeItJavaScriptAble($users);

        $data = [
            'users' => $users,
        ];

        $this->set($data);
        $this->viewBuilder()->setOption('serialize', array_keys($data));
    }
}
