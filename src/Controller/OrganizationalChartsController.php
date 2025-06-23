<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use App\Model\Entity\UsersToOrganizationalChartStructure;
use App\Model\Table\ContainersTable;
use App\Model\Table\OrganizationalChartsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;


/**
 * Class OrganizationalChartsController
 * @package App\Controller
 */
class OrganizationalChartsController extends AppController {
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        /** @var $OrganizationalChartsTable OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');


        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'OrganizationalCharts.name',
                'OrganizationalCharts.description'
            ],
            /*
            'equals' => [
                'Containers.id', /// ???????
            ],
            */
        ]);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());


        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }


        $organizationalCharts = $OrganizationalChartsTable->getOrganizationalChartsIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($organizationalCharts as $index => $organizationalChart) {
            $organizationalChart[$index]['allow_edit'] = $this->isWritableContainer($organizationalChart['container']['parent_id']);
            $users = [];
            $managers = [];
            $regionManagers = [];
            foreach ($organizationalChart['users'] as $user) {
                if ($user['_joinData']['is_manager'] == 1) {
                    if ($user['_joinData']['user_role'] == UsersToOrganizationalChartStructure::REGION_MANAGER) {
                        $regionManagers[] = [
                            'id'       => $user['id'],
                            'username' => sprintf('%s %s', $user['firstname'], $user['lastname'])
                        ];
                    } else {
                        $managers[] = [
                            'id'       => $user['id'],
                            'username' => sprintf('%s %s', $user['firstname'], $user['lastname'])
                        ];
                    }

                } else {
                    $users[] = [
                        'id'       => $user['id'],
                        'username' => sprintf('%s %s', $user['firstname'], $user['lastname'])
                    ];
                }
            }
            $organizationalCharts[$index]['managers'] = $managers;
            $organizationalCharts[$index]['region_managers'] = $regionManagers;
            $organizationalCharts[$index]['users'] = $users;
            $organizationalCharts[$index]['statesummary'] = '???';
        }

        $this->set('all_organizationalcharts', $organizationalCharts);
        $this->viewBuilder()->setOption('serialize', ['all_organizationalcharts']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }


    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }


    }


    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

    }


    /****************************
     *       AJAX METHODS       *
     ****************************/
    public function loadContainers() {
        if (!$this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containers = $ContainersTable->getContainersByIdsGroupByType($MY_RIGHTS, [], [CT_TENANT, CT_LOCATION, CT_NODE]);

        $this->set('tenants', $containers['tenants']);
        $this->set('locations', $containers['locations']);
        $this->set('nodes', $containers['nodes']);
        $this->viewBuilder()->setOption('serialize', ['tenants', 'locations', 'nodes']);
    }
}
