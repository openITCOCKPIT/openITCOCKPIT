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

use App\Model\Table\StatuspagegroupsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

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
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var StatuspagegroupsTable $StatuspagegroupsTable */
        $StatuspagegroupsTable = TableRegistry::getTableLocator()->get(' Statuspagegroups');


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
            if ($this->hasRootPrivileges === true) {
                $statuspagegroups[$index]['allowEdit'] = true;
                $statuspagegroups[$index]['allowView'] = true;
            } else {
                $statuspagegroups[$index]['allowEdit'] = $this->isWritableContainer($statuspagegroup['container_id']);
                $statuspagegroups[$index]['allowView'] = in_array($statuspagegroup['container_id'], $MY_RIGHTS, true);
            }
        }
        $this->set('all_statuspagegroups', $statuspagegroups);
        $this->viewBuilder()->setOption('serialize', ['$statuspagegroups']);

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

    }

    /**
     * If you remove this function, please also clean up ACLDependencies
     * @return void
     */
    public function addCollection() {

    }

    /**
     * If you remove this function, please also clean up ACLDependencies
     * @return void
     */
    public function addCategory() {

    }


    /**
     * Edit method
     *
     * @param string|null $id Statuspagegroup id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null) {

    }

    /**
     * If you remove this function, please also clean up ACLDependencies
     * @return void
     */
    public function editCollection() {

    }

    /**
     * If you remove this function, please also clean up ACLDependencies
     * @return void
     */
    public function editCategory() {

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
}
