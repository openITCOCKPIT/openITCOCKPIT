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

use App\Lib\Exceptions\MissingDbBackendException;
use App\Model\Entity\Host;
use App\Model\Entity\Service;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AcknowledgedHostConditions;
use itnovum\openITCOCKPIT\Core\AcknowledgedServiceConditions;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\AcknowledgementsControllerRequest;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\AcknowledgementService;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use Statusengine2Module\Model\Entity\AcknowledgementHost;

/**
 * Class AcknowledgementsController
 * @package App\Controller
 */
class AcknowledgementsController extends AppController {

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function host($id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
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

        $AngularAcknowledgementsControllerRequest = new AcknowledgementsControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularAcknowledgementsControllerRequest->getPage());

        //Process conditions
        $Conditions = new AcknowledgedHostConditions();
        $Conditions->setFrom($AngularAcknowledgementsControllerRequest->getFrom());
        $Conditions->setTo($AngularAcknowledgementsControllerRequest->getTo());
        $Conditions->setStates($AngularAcknowledgementsControllerRequest->getHostStates());
        $Conditions->setOrder($AngularAcknowledgementsControllerRequest->getOrderForPaginator('AcknowledgementHosts.entry_time', 'desc'));
        $Conditions->setConditions($AngularAcknowledgementsControllerRequest->getHostFilters());
        $Conditions->setHostUuid($host->get('uuid'));


        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $AcknowledgementHostsTable = $this->DbBackend->getAcknowledgementHostsTable();

        //Query acknowledgements records
        $BBCodeParser = new BBCodeParser();
        $all_acknowledgements = [];
        foreach ($AcknowledgementHostsTable->getAcknowledgements($Conditions, $PaginateOMat) as $AcknowledgementHost) {
            /** @var AcknowledgementHost $acknowledgement */
            $Acknowledgement = new \itnovum\openITCOCKPIT\Core\Views\AcknowledgementHost($AcknowledgementHost->toArray(), $UserTime);

            $acknowledgementArray = $Acknowledgement->toArray();
            $acknowledgementArray['comment_data'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($acknowledgementArray['comment_data'], true));

            $all_acknowledgements[] = [
                'AcknowledgedHost' => $acknowledgementArray
            ];
        }

        $this->set('all_acknowledgements', $all_acknowledgements);
        $toJson = ['all_acknowledgements', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_acknowledgements', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function service($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
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

        $AngularAcknowledgementsControllerRequest = new AcknowledgementsControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularAcknowledgementsControllerRequest->getPage());

        //Process conditions
        $Conditions = new AcknowledgedServiceConditions();
        $Conditions->setFrom($AngularAcknowledgementsControllerRequest->getFrom());
        $Conditions->setTo($AngularAcknowledgementsControllerRequest->getTo());
        $Conditions->setStates($AngularAcknowledgementsControllerRequest->getServiceStates());
        $Conditions->setOrder($AngularAcknowledgementsControllerRequest->getOrderForPaginator('AcknowledgementServices.entry_time', 'desc'));
        $Conditions->setConditions($AngularAcknowledgementsControllerRequest->getServiceFilters());
        $Conditions->setServiceUuid($service->get('uuid'));

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $AcknowledgementServicesTable = $this->DbBackend->getAcknowledgementServicesTable();

        //Query acknowledgements records
        $BBCodeParser = new BBCodeParser();
        $all_acknowledgements = [];
        foreach ($AcknowledgementServicesTable->getAcknowledgements($Conditions, $PaginateOMat) as $acknowledgement) {
            $AcknowledgedService = new AcknowledgementService($acknowledgement, $UserTime);
            $acknowledgementArray = $AcknowledgedService->toArray();
            $acknowledgementArray['comment_data'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($acknowledgementArray['comment_data'], true));
            $all_acknowledgements[] = [
                'AcknowledgedService' => $acknowledgementArray
            ];
        }

        $this->set('all_acknowledgements', $all_acknowledgements);
        $toJson = ['all_acknowledgements', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_acknowledgements', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function delete() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $hostId = $this->request->getData('hostId', null);
        $serviceId = $this->request->getData('serviceId', null);

        if ($hostId === null) {
            throw new \InvalidArgumentException('$hostId needs to be set!');
        }

        $GearmanClient = new Gearman();

        if ($serviceId != null) {

            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            if (!$ServicesTable->existsById($serviceId)) {
                throw new NotFoundException(__('Invalid service'));
            }

            /** @var Service $service */
            $service = $ServicesTable->getServiceByIdForPermissionsCheck($serviceId);
            if (!$this->allowedByContainerId($service->getContainerIds(), true)) {
                $this->render403();
                return;
            }

            //Delete service acknowledgement
            $GearmanClient->sendBackground('deleteServiceAcknowledgement', [
                'hostUuid'     => $service->host->uuid,
                'serviceUuid'  => $service->uuid,
                'satellite_id' => $service->host->satellite_id
            ]);

            $this->set('success', true);
            $this->set('message', __('Successfully'));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        //Delete host acknowledgement

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Invalid host'));
        }

        /** @var Host $host */
        $host = $HostsTable->getHostByIdForPermissionCheck($hostId);
        if (!$this->allowedByContainerId($host->getContainerIds(), true)) {
            $this->render403();
            return;
        }

        $GearmanClient->sendBackground('deleteHostAcknowledgement', [
            'hostUuid'     => $host->uuid,
            'satellite_id' => $host->satellite_id
        ]);

        $this->set('success', true);
        $this->set('message', __('Successfully'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
        return;
    }
}
