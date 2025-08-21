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

use App\Lib\Exceptions\MissingDbBackendException;
use App\Model\Entity\Host;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\StatehistoryControllerRequest;
use itnovum\openITCOCKPIT\Core\StatehistoryHostConditions;
use itnovum\openITCOCKPIT\Core\StatehistoryServiceConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryHost;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryService;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Class StatehistoriesController
 * @package App\Controller
 */
class StatehistoriesController extends AppController {

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function host($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid host'));
        }

        /** @var Host $host */
        $host = $HostsTable->getHostByIdForPermissionCheck($id);
        if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $AngularStatehistoryControllerRequest = new StatehistoryControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularStatehistoryControllerRequest->getPage());


        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new StatehistoryHostConditions();
        $Conditions->setOrder($AngularStatehistoryControllerRequest->getOrderForPaginator('StatehistoryHosts.state_time', 'desc'));
        $Conditions->setStates($AngularStatehistoryControllerRequest->getHostStates());
        $Conditions->setStateTypes($AngularStatehistoryControllerRequest->getHostStateTypes());
        $Conditions->setFrom($UserTime->toServerTime($AngularStatehistoryControllerRequest->getFrom()));
        $Conditions->setTo($UserTime->toServerTime($AngularStatehistoryControllerRequest->getTo()));
        $Conditions->setHostUuid($host->get('uuid'));
        $Conditions->setConditions($AngularStatehistoryControllerRequest->getHostFilters());


        $StatehistoryHostsTable = $this->DbBackend->getStatehistoryHostsTable();

        $all_statehistories = [];
        foreach ($StatehistoryHostsTable->getStatehistoryIndex($Conditions, $PaginateOMat) as $statehistory) {
            $StatehistoryHost = new StatehistoryHost($statehistory, $UserTime);
            $StatehistoryHostArray = $StatehistoryHost->toArray();

            //Parse BBCode in output
            $BBCodeParser = new BBCodeParser();
            $StatehistoryHostArray['outputHtml'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($StatehistoryHost->getOutput(), true));

            $all_statehistories[] = [
                'StatehistoryHost' => $StatehistoryHostArray
            ];
        }

        $this->set('all_statehistories', $all_statehistories);

        $toJson = ['all_statehistories', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_statehistories', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function service($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }


        $service = $ServicesTable->getServiceByIdForPermissionsCheck($id);
        if (!$this->allowedByContainerId($service->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $AngularStatehistoryControllerRequest = new StatehistoryControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularStatehistoryControllerRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new StatehistoryServiceConditions();
        $Conditions->setOrder($AngularStatehistoryControllerRequest->getOrderForPaginator('StatehistoryServices.state_time', 'desc'));
        $Conditions->setStates($AngularStatehistoryControllerRequest->getServiceStates());
        $Conditions->setStateTypes($AngularStatehistoryControllerRequest->getServiceStateTypes());
        $Conditions->setFrom($UserTime->toServerTime($AngularStatehistoryControllerRequest->getFrom()));
        $Conditions->setTo($UserTime->toServerTime($AngularStatehistoryControllerRequest->getTo()));
        $Conditions->setConditions($AngularStatehistoryControllerRequest->getServiceFilters());
        $Conditions->setServiceUuid($service->get('uuid'));

        //Query state history records
        $StatehistoryServicesTable = $this->DbBackend->getStatehistoryServicesTable();

        $all_statehistories = [];
        foreach ($StatehistoryServicesTable->getStatehistoryIndex($Conditions, $PaginateOMat) as $statehistory) {
            $StatehistoryService = new StatehistoryService($statehistory, $UserTime);
            $StatehistoryServiceArray = $StatehistoryService->toArray();

            //Parse BBCode in output
            $BBCodeParser = new BBCodeParser();
            $StatehistoryServiceArray['outputHtml'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($StatehistoryService->getOutput(), true));

            $all_statehistories[] = [
                'StatehistoryService' => $StatehistoryServiceArray
            ];
        }

        $this->set('all_statehistories', $all_statehistories);

        $toJson = ['all_statehistories', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_statehistories', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }
}
