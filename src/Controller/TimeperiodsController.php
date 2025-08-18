<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\HostdependenciesTable;
use App\Model\Table\HostescalationsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\InstantreportsTable;
use App\Model\Table\ServicedependenciesTable;
use App\Model\Table\ServiceescalationsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\TimeperiodsTable;
use AutoreportModule\Model\Table\AutoreportsTable;
use Cake\Core\Plugin;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\TimeperiodsFilter;

/**
 * Class TimeperiodsController
 * @package App\Controller
 */
class TimeperiodsController extends AppController {

    function index() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if ($this->isApiRequest() && !$this->isAngularJsRequest()) {
            //Legacy API Request
            $this->set('all_timeperiods', $TimeperiodsTable->getAllTimeperiodsAsCake2($this->MY_RIGHTS));
            $this->viewBuilder()->setOption('serialize', ['all_timeperiods']);
            return;
        }

        if ($this->isAngularJsRequest()) {
            //AngularJS API Request
            $TimeperiodsFilter = new TimeperiodsFilter($this->request);
            $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $TimeperiodsFilter->getPage());
            $all_timeperiods = $TimeperiodsTable->getTimeperiodsIndex($TimeperiodsFilter, $PaginateOMat);

            foreach ($all_timeperiods as $index => $timeperiod) {
                $allowEdit = $this->hasRootPrivileges;
                if ($this->hasRootPrivileges === false) {
                    $allowEdit = $this->isWritableContainer($timeperiod['Timeperiod']['container_id']);
                }
                $all_timeperiods[$index]['Timeperiod']['allow_edit'] = $allowEdit;
            }


            $this->set('all_timeperiods', $all_timeperiods);
            $toJson = ['all_timeperiods', 'paging'];
            if ($this->isScrollRequest()) {
                $toJson = ['all_timeperiods', 'scroll'];
            }
            $this->viewBuilder()->setOption('serialize', $toJson);
            return;
        }
    }

    /**
     * @param $id
     */
    public function view($id) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if (!$TimeperiodsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid timeperiod'));
        }
        $timeperiod = $TimeperiodsTable->get($id);
        $timeperiod = $timeperiod->toArray();

        if (!$this->allowedByContainerId(Hash::extract($timeperiod, 'container_id'))) {
            $this->render403();
            return;
        }

        $this->set('timeperiod', $timeperiod);
        $this->viewBuilder()->setOption('serialize', ['timeperiod']);
    }

    /**
     * @param null $id
     * @throws \Exception
     */
    public function viewDetails($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if (!$TimeperiodsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid timeperiod'));
        }
        $timeperiod = $TimeperiodsTable->get($id, contain: 'TimeperiodTimeranges');
        $timeperiod = $timeperiod->toArray();


        if (!$this->allowedByContainerId(Hash::extract($timeperiod, 'container_id'))) {
            $this->render403();
            return;
        }
        $timeperiod['events'] = $TimeperiodsTable->parseTimerangesForCalendar($timeperiod['timeperiod_timeranges']);

        $this->set('timeperiod', $timeperiod);
        $this->viewBuilder()->setOption('serialize', ['timeperiod']);
    }

    /**
     * @throws \Exception
     */
    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $timeperiod = $TimeperiodsTable->newEmptyEntity();
            $timeperiod = $TimeperiodsTable->patchEntity($timeperiod, $this->request->getData('Timeperiod'));
            $timeperiod->set('uuid', UUID::v4());
            $TimeperiodsTable->checkRules($timeperiod);

            $User = new User($this->getUser());
            $requestData = $this->request->getData();

            $timeperiod = $TimeperiodsTable->createTimeperiod(
                $timeperiod,
                $requestData,
                $User->getId()
            );

            if ($timeperiod->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->serializeCake4ErrorMessage($timeperiod);
                return;
            } else {
                //No errors
                $this->serializeCake4Id($timeperiod);
            }
            $this->set('timeperiod', $timeperiod);
            $this->viewBuilder()->setOption('serialize', ['timeperiod']);
        }
    }

    /**
     * @param null $id
     * @throws \Exception
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        if (!$TimeperiodsTable->existsById($id)) {
            throw new NotFoundException('Time period not found');
        }
        $timeperiod = $TimeperiodsTable->getTimeperiodForEdit($id);
        $timeperiodForChangeLog['Timeperiod'] = $timeperiod->toArray();

        if (!$this->allowedByContainerId($timeperiod->get('container_id'))) {
            $this->render403();
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $timeperiod = $TimeperiodsTable->patchEntity($timeperiod, $this->request->getData('Timeperiod'));
            $TimeperiodsTable->checkRules($timeperiod);

            $requestData = $this->request->getData();
            $User = new User($this->getUser());

            $timeperiod = $TimeperiodsTable->updateTimeperiod(
                $timeperiod,
                $requestData,
                $timeperiodForChangeLog,
                $User->getId()
            );

            if ($timeperiod->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $timeperiod->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($timeperiod); // REST API ID serialization
                    return;
                }
            }
        }

        $this->set('timeperiod', $timeperiod);
        $this->viewBuilder()->setOption('serialize', ['timeperiod']);
    }

    /**
     * @param null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if (!$TimeperiodsTable->existsById($id)) {
            throw new NotFoundException(__('Timeperiod not found'));
        }

        $timeperiod = $TimeperiodsTable->getTimeperiodById($id);

        if (!$this->allowedByContainerId(Hash::extract($timeperiod, 'Timeperiod.container_id'))) {
            $this->render403();
            return;
        }

        if (!$TimeperiodsTable->allowDelete($timeperiod)) {
            $usedBy = [
                [
                    'baseUrl' => '#',
                    'state'   => 'TimeperiodsUsedBy',
                    'message' => __('Used by other objects'),
                    'module'  => 'Core'
                ]
            ];

            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Issue while deleting timeperiod'));
            $this->set('usedBy', $usedBy);
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
            return;
        }


        $timeperiodEntity = $TimeperiodsTable->get($id);
        if ($TimeperiodsTable->delete($timeperiodEntity)) {
            $User = new User($this->getUser());
            /** @var ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'delete',
                'timeperiods',
                $id,
                OBJECT_TIMEPERIOD,
                [$timeperiod['Timeperiod']['container_id']],
                $User->getId(),
                $timeperiod['Timeperiod']['name'],
                $timeperiod
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);
            }

            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /**
     * @param int|null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if ($this->request->is('get')) {
            $timeperiods = $TimeperiodsTable->getTimeperiodsForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('timeperiods', $timeperiods);
            $this->viewBuilder()->setOption('serialize', ['timeperiods']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $Cache = new KeyValueStore();

            $postData = $this->request->getData('data');
            $User = new User($this->getUser());

            foreach ($postData as $index => $timeperiodData) {
                if (!isset($timeperiodData['Timeperiod']['id'])) {
                    //Create/clone timeperiod
                    $sourceTimeperiodId = $timeperiodData['Source']['id'];
                    if (!$Cache->has($sourceTimeperiodId)) {
                        $sourceTimeperiod = $TimeperiodsTable->get($sourceTimeperiodId, contain: [
                            'TimeperiodTimeranges'
                        ])->toArray();
                        foreach ($sourceTimeperiod['timeperiod_timeranges'] as $i => $timerange) {
                            unset($sourceTimeperiod['timeperiod_timeranges'][$i]['id']);
                            unset($sourceTimeperiod['timeperiod_timeranges'][$i]['timeperiod_id']);
                        }
                        $Cache->set($sourceTimeperiod['id'], $sourceTimeperiod);
                    }

                    $sourceTimeperiod = $Cache->get($sourceTimeperiodId);


                    $newTimeperiodData = [
                        'name'                  => $timeperiodData['Timeperiod']['name'],
                        'description'           => $timeperiodData['Timeperiod']['description'],
                        'container_id'          => $sourceTimeperiod['container_id'],
                        'calendar_id'           => $sourceTimeperiod['calendar_id'],
                        'uuid'                  => UUID::v4(),
                        'timeperiod_timeranges' => $sourceTimeperiod['timeperiod_timeranges']
                    ];

                    $newTimeperiodEntity = $TimeperiodsTable->newEntity($newTimeperiodData);
                }

                $action = 'copy';
                if (isset($timeperiodData['Timeperiod']['id'])) {
                    //Update existing timeperiod
                    //This happens, if a user copy multiple timeperiods, and one run into an validation error
                    //All timeperiods without validation errors got already saved to the database
                    $newTimeperiodEntity = $TimeperiodsTable->get($timeperiodData['Timeperiod']['id']);
                    $newTimeperiodEntity->setAccess('*', false);
                    $newTimeperiodEntity->setAccess('name', true);
                    $newTimeperiodEntity->setAccess('description', true);
                    $newTimeperiodEntity = $TimeperiodsTable->patchEntity($newTimeperiodEntity, $timeperiodData['Timeperiod']);
                    $newTimeperiodData = $newTimeperiodEntity->toArray();
                    $action = 'edit';
                }
                $TimeperiodsTable->save($newTimeperiodEntity);

                $postData[$index]['Error'] = [];
                if ($newTimeperiodEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newTimeperiodEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Timeperiod']['id'] = $newTimeperiodEntity->get('id');

                    /** @var ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        'timeperiods',
                        $postData[$index]['Timeperiod']['id'],
                        OBJECT_TIMEPERIOD,
                        [ROOT_CONTAINER],
                        $User->getId(),
                        $newTimeperiodEntity->get('name'),
                        ['Timeperiod' => $newTimeperiodData]
                    );
                    if ($changelog_data) {
                        /** @var Changelog $changelogEntry */
                        $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                        $ChangelogsTable->save($changelogEntry);
                    }
                }
            }
        }

        if ($hasErrors) {
            $this->response = $this->response->withStatus(400);
        }
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    /**
     * @param int|null $id
     */
    public function usedBy($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        if (!$TimeperiodsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid timeperiod'));
        }

        $timeperiod = $TimeperiodsTable->get($id);


        $objects = [
            'Contacts'            => [],
            'Hostdependencies'    => [],
            'Hostescalations'     => [],
            'Hosts'               => [],
            'Hosttemplates'       => [],
            'Instantreports'      => [],
            'Servicedependencies' => [],
            'Serviceescalations'  => [],
            'Services'            => [],
            'Servicetemplates'    => []
        ];

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        //Check if the time period is used by contacts
        /** @var ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        $objects['Contacts'] = $ContactsTable->getContactsByTimeperiodId($id, $MY_RIGHTS, false);


        //Checking host dependencies
        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
        $objects['Hostdependencies'] = $HostdependenciesTable->getHostdependenciesByTimeperiodId($id, $MY_RIGHTS, false);

        //Checking host escalations
        /** @var $HostescalationsTable HostescalationsTable */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');
        $objects['Hostescalations'] = $HostescalationsTable->getHostescalationsByTimeperiodId($id, $MY_RIGHTS, false);

        //Checking host
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $objects['Hosts'] = $HostsTable->getHostsByTimeperiodId($id, $MY_RIGHTS, false);

        //Check if the time period is used by host templates
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        $objects['Hosttemplates'] = $HosttemplatesTable->getHosttemplatesByTimeperiodId($id, $MY_RIGHTS, false);

        //Check if the time period is used by instant reports
        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        $objects['Instantreports'] = $InstantreportsTable->getInstantreportsByTimeperiodId($id, $MY_RIGHTS, false);

        //Checking service dependencies
        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
        $objects['Servicedependencies'] = $ServicedependenciesTable->getServicedependenciesByTimeperiodId($id, $MY_RIGHTS, false);

        //Checking service escalations
        /** @var $ServiceescalationsTable ServiceescalationsTable */
        $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');
        $objects['Serviceescalations'] = $ServiceescalationsTable->getServiceescalationsByTimeperiodId($id, $MY_RIGHTS, false);

        //Checking service
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $objects['Services'] = $ServicesTable->getServicesByTimeperiodId($id, $MY_RIGHTS, false);

        //Check if the time period is used by service templates
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        $objects['Servicetemplates'] = $ServicetemplatesTable->getServicetemplatesByTimeperiodId($id, $MY_RIGHTS, false);

        //check if the time period is used in auto reports
        if (Plugin::isLoaded('AutoreportModule')) {
            $objects['Autoreports'] = [];
            /** @var $AutoreportsTable AutoreportsTable */
            $AutoreportsTable = TableRegistry::getTableLocator()->get('AutoreportModule.Autoreports');
            $objects['Autoreports'] = $AutoreportsTable->getAutoreportsByTimeperiodId($id, $MY_RIGHTS, false);
        }


        $total = 0;
        $total += sizeof($objects['Contacts']);
        $total += sizeof($objects['Hostdependencies']);
        $total += sizeof($objects['Hostescalations']);
        $total += sizeof($objects['Hosts']);
        $total += sizeof($objects['Hosttemplates']);
        $total += sizeof($objects['Instantreports']);
        $total += sizeof($objects['Servicedependencies']);
        $total += sizeof($objects['Serviceescalations']);
        $total += sizeof($objects['Services']);
        $total += sizeof($objects['Servicetemplates']);


        $this->set('timeperiod', $timeperiod->toArray());
        $this->set('objects', $objects);
        $this->set('total', $total);
        $this->viewBuilder()->setOption('serialize', ['timeperiod', 'objects', 'total']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function loadTimeperiodsByContainerId() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $containerId = $this->request->getQuery('containerId');

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        $timeperiods = $TimeperiodsTable->getTimeperiodByContainerIdsAsList([
            ROOT_CONTAINER, $containerId
        ]);

        $timeperiods = Api::makeItJavaScriptAble(
            $timeperiods
        );

        $this->set('timeperiods', $timeperiods);
        $this->viewBuilder()->setOption('serialize', ['timeperiods']);
    }
}
