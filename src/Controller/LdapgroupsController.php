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

use App\Model\Table\LdapgroupsTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\LdapgroupFilter;


/**
 * Class LdapgroupsController
 * @package App\Controller
 */
class LdapgroupsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $LdapgroupFilter = new LdapgroupFilter($this->request);

        /** @var LdapgroupsTable $LdapgroupsTable */
        $LdapgroupsTable = TableRegistry::getTableLocator()->get('Ldapgroups');

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $LdapgroupFilter->getPage());
        $all_ldapgroups = $LdapgroupsTable->getLdapgroupsIndex($LdapgroupFilter, $PaginateOMat);

        $this->set('all_ldapgroups', $all_ldapgroups);
        $this->viewBuilder()->setOption('serialize', ['all_ldapgroups']);
    }
}
