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

use App\Form\InstantreportForm;
use App\itnovum\openITCOCKPIT\Core\Reports\InstantreportCreator;
use App\Lib\Exceptions\MissingDbBackendException;
use App\Model\Table\ContainersTable;
use App\Model\Table\InstantreportsTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryHost;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryService;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\InstantreportFilter;
use Statusengine2Module\Model\Entity\DowntimeHost;


/**
 * @property Instantreport $Instantreport
 * @property Host $Host
 * @property Service $Service
 * @property Timeperiod $Timeperiod
 * @property StatehistoryHost $StatehistoryHost
 * @property DowntimeHost $DowntimeHost
 * @property StatehistoryService $StatehistoryService
 * @property DbBackend $DbBackend
 * @property AppPaginatorComponent $Paginator
 */
class InstantreportsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }
        $InstantreportFilter = new InstantreportFilter($this->request);
        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $InstantreportFilter->getPage());
        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $instantreports = $InstantreportsTable->getInstantreportsIndex($InstantreportFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($instantreports as $index => $instantreport) {
            $instantreports[$index]['allowEdit'] = $this->isWritableContainer($instantreport['Instantreport']['container_id']);
        }
        $this->set('instantreports', $instantreports);
        $toJson = ['instantreports', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['instantreports', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }
        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        if ($this->request->is('post') && $this->isAngularJsRequest()) {

            /** @var $TimeperiodsTable TimeperiodsTable */
            $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
            $timeperiod = $TimeperiodsTable->getTimeperiodWithTimerangesById($this->request->getData('Instantreport.timeperiod_id', 0));

            if (empty($timeperiod['Timeperiod']['timeperiod_timeranges'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'timeperiod_id' => [
                        'empty' => 'There are no time frames defined. Time evaluation report data is not available for the selected period.'
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $instantreport = $InstantreportsTable->newEmptyEntity();
            $instantreport = $InstantreportsTable->patchEntity($instantreport, $this->request->getData('Instantreport'));
            $InstantreportsTable->save($instantreport);
            if ($instantreport->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->serializeCake4ErrorMessage($instantreport);
                return;
            } else {
                //No errors
                $this->serializeCake4Id($instantreport);
            }
            $this->set('instantreport', $instantreport);
            $this->viewBuilder()->setOption('serialize', ['instantreport']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');

        if (!$InstantreportsTable->existsById($id)) {
            throw new NotFoundException(__('Instant report not found'));
        }
        $instantreport = $InstantreportsTable->getInstantreportForEdit($id);
        if (!$this->allowedByContainerId($instantreport['Instantreport']['container_id'])) {
            $this->render403();
            return;
        }
        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return instant report information
            $this->set('instantreport', $instantreport);
            $this->viewBuilder()->setOption('serialize', ['instantreport']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {

            /** @var $TimeperiodsTable TimeperiodsTable */
            $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
            $timeperiod = $TimeperiodsTable->getTimeperiodWithTimerangesById($this->request->getData('Instantreport.timeperiod_id'));

            if (empty($timeperiod['Timeperiod']['timeperiod_timeranges'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'timeperiod_id' => [
                        'empty' => 'There are no time frames defined. Time evaluation report data is not available for the selected period.'
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data = $this->request->getData('Instantreport');
            $instantreport = $InstantreportsTable->get($id);
            $instantreport = $InstantreportsTable->patchEntity($instantreport, $data);
            $InstantreportsTable->save($instantreport);
            if ($instantreport->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $instantreport->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('instantreport', $instantreport);
            $this->viewBuilder()->setOption('serialize', ['instantreport']);
        }
    }

    public function generate($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $requestData = $this->request->getData();
        if (isset($requestData['from_date'])) {
            $requestData['from_date'] = Api::replaceAngularDateYmd($requestData['from_date']);
        }
        if (isset($requestData['to_date'])) {
            $requestData['to_date'] = Api::replaceAngularDateYmd($requestData['to_date']);
        }

        $instantreportForm = new InstantreportForm();
        $instantreportForm->execute($requestData);

        if (!empty($instantreportForm->getErrors())) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $instantreportForm->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $instantreportId = $this->request->getData('instantreport_id');
        $fromDate = strtotime($requestData['from_date'] . ' 00:00:00');
        $toDate = strtotime($requestData['to_date'] . ' 23:59:59');

        $User = new User($this->getUser());
        if ($toDate > time()) {
            $toDate = time();
        }
        $UserTime = $User->getUserTime();
        $InstantreportCreator = new InstantreportCreator(
            $UserTime,
            $this->MY_RIGHTS,
            $this->hasRootPrivileges
        );


        $instantReport = $InstantreportCreator->createReport(
            $instantreportId,
            $fromDate,
            $toDate
        );

        if (isset($instantReport['error'])) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $instantReport['error']);
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }


        if ($instantReport === null) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', [
                'no_data' => [
                    'empty' => __('No report data specified time found ({0} - {1}) !',
                        date('d.m.Y', $fromDate),
                        date('d.m.Y', $toDate)
                    )
                ]
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $this->set('instantReport', $instantReport);
        $this->viewBuilder()->setOption('serialize', ['instantReport']);

    }


    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');

        if (!$InstantreportsTable->exists($id)) {
            throw new NotFoundException(__('Instant report not found'));
        }

        $instantreport = $InstantreportsTable->getInstantreportById($id);
        if (!$this->allowedByContainerId(Hash::extract($instantreport, 'Instantreport.container_id'))) {
            $this->render403();
            return;
        }
        $instantreportEntity = $InstantreportsTable->get($id);
        if ($InstantreportsTable->delete($instantreportEntity)) {
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
     * @throws MissingDbBackendException
     * @throws \Exception
     */
    public function createPdfReport() {
        $User = new User($this->getUser());
        $UserTime = UserTime::fromUser($User);


        $requestData = $this->request->getQuery('data', []);
        if (isset($requestData['from_date'])) {
            $requestData['from_date'] = Api::replaceAngularDateYmd($requestData['from_date']);
        }
        if (isset($requestData['to_date'])) {
            $requestData['to_date'] = Api::replaceAngularDateYmd($requestData['to_date']);
        }

        $instantreportForm = new InstantreportForm();

        $instantreportForm->execute($requestData);

        if (!empty($instantreportForm->getErrors())) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $instantreportForm->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $instantreportId = $this->request->getQuery('data.instantreport_id', 0);


        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        if (!$InstantreportsTable->existsById($instantreportId)) {
            throw new NotFoundException('Instant report not found!');
        }

        $instantreportEntity = $InstantreportsTable->get($instantreportId);

        if ($this->isJsonRequest()) {
            //Only validate parameters
            $filename = sprintf('InstantReport_%s', $instantreportEntity->name) . date('dmY_his') . '.pdf';

            $this->set('success', true);
            $this->set('filename', $filename);
            $this->viewBuilder()->setOption('serialize', ['success', 'filename']);
            return;
        }

        $fromDate = strtotime($this->request->getQuery('data.from_date', date('d.m.Y')) . ' 00:00:00');
        $toDate = strtotime($this->request->getQuery('data.to_date', date('d.m.Y')) . ' 23:59:59');
        if ($toDate > time()) {
            $toDate = time();
        }

        $InstantreportCreator = new InstantreportCreator(
            $UserTime,
            $this->MY_RIGHTS,
            $this->hasRootPrivileges
        );

        $instantReport = $InstantreportCreator->createReport(
            $instantreportId,
            $fromDate,
            $toDate
        );

        if (isset($instantReport['error'])) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $instantReport['error']);
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }


        if ($instantReport === null) {
            $this->set('error', [
                'no_data' => [
                    'empty' => __('! No data within specified time found ({0} - {1}) !',
                        date('d.m.Y', $fromDate),
                        date('d.m.Y', $toDate)
                    )
                ]
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);
        }

        //User server timezone but with user date format
        $ServerTime = new UserTime(date_default_timezone_get(), $UserTime->getFormatString());

        $this->set('fromDate', $ServerTime->format($fromDate));
        $this->set('toDate', $ServerTime->format($toDate));


        $this->set('instantReport', $instantReport);
        $this->viewBuilder()->setOption('pdfConfig', [
                'download' => false,
                'filename' => sprintf('InstantReport_%s', $instantReport['reportDetails']['name']) . date('dmY_his') . '.pdf'
            ]
        );
    }

    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOSTGROUP, [], $this->hasRootPrivileges);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOSTGROUP, [], $this->hasRootPrivileges);
        }
        $containers = Api::makeItJavaScriptAble($containers);

        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    public function loadInstantreports() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $InstantreportsTable InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $instantreports = Api::makeItJavaScriptAble(
            $InstantreportsTable->getInstantreportsForAngular($MY_RIGHTS)
        );

        $this->set('instantreports', $instantreports);
        $this->viewBuilder()->setOption('serialize', ['instantreports']);
    }

}
