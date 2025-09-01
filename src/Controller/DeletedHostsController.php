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

use App\Model\Table\DeletedHostsTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\DeletedHost;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostFilter;

/**
 * Class DeletedHostsController
 * @property AppPaginatorComponent $Paginator
 */
class DeletedHostsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $DeletedHostsTable DeletedHostsTable */
        $DeletedHostsTable = TableRegistry::getTableLocator()->get('DeletedHosts');
        $HostFilter = new HostFilter($this->request);
        $User = new User($this->getUser());

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $HostFilter->getPage());
        $result = $DeletedHostsTable->getDeletedHostsIndex($HostFilter, $PaginateOMat);

        $UserTime = new UserTime($User->getTimezone(), $User->getDateformat());
        $deletedHosts = [];
        foreach ($result as $host) {
            $DeletedHost = new DeletedHost($host, $UserTime);
            $deletedHosts[] = [
                'DeletedHost' => $DeletedHost->toArray()
            ];
        }

        $this->set('deletedHosts', $deletedHosts);
        $toJson = ['deletedHosts', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['deletedHosts', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }
}
