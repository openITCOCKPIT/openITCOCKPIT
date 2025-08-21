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
use App\Model\Entity\Service;
use App\Model\Table\ServicesTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\ServicechecksControllerRequest;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\ServicechecksConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Core\Views\Servicecheck;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Class ServicechecksController
 * @package App\Controller
 */
class ServicechecksController extends AppController {

    /**
     * @param null $id
     * @throws MissingDbBackendException
     */
    public function index($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship html template
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        /** @var Service $service */
        $service = $ServicesTable->getServiceByIdForPermissionsCheck($id);
        if (!$this->allowedByContainerId($service->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $ServicechecksControllerRequest = new ServicechecksControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServicechecksControllerRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new ServicechecksConditions();
        $Conditions->setHostUuid($service->host->uuid);
        $Conditions->setFrom($UserTime->toServerTime($ServicechecksControllerRequest->getFrom()));
        $Conditions->setTo($UserTime->toServerTime($ServicechecksControllerRequest->getTo()));
        $Conditions->setOrder($ServicechecksControllerRequest->getOrderForPaginator('Servicecheck.start_time', 'desc'));
        $Conditions->setStates($ServicechecksControllerRequest->getServiceStates());
        $Conditions->setStateTypes($ServicechecksControllerRequest->getServiceStateTypes());
        $Conditions->setServiceUuid($service->get('uuid'));
        $Conditions->setConditions($ServicechecksControllerRequest->getIndexFilters());

        $ServicechecksTable = $this->DbBackend->getServicechecksTable();

        $all_servicechecks = [];
        foreach ($ServicechecksTable->getServicechecks($Conditions, $PaginateOMat) as $servicecheck) {
            /** @var \Statusengine2Module\Model\Entity\Servicecheck $servicecheck */
            $Servicecheck = new Servicecheck($servicecheck->toArray(), $UserTime);
            $servicecheckArray = $Servicecheck->toArray();

            //Parse BBCode in output
            $BBCodeParser = new BBCodeParser();
            $servicecheckArray['outputHtml'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($Servicecheck->getOutput(), true));

            $all_servicechecks[] = [
                'Servicecheck' => $servicecheckArray
            ];
        }

        $this->set('all_servicechecks', $all_servicechecks);
        $toJson = ['all_servicechecks', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_servicechecks', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }
}
