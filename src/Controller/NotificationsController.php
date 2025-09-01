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
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\NotificationsLogRequest;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\NotificationsOverviewControllerRequest;
use itnovum\openITCOCKPIT\Core\HostNotificationConditions;
use itnovum\openITCOCKPIT\Core\ServiceNotificationConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Command;
use itnovum\openITCOCKPIT\Core\Views\Contact;
use itnovum\openITCOCKPIT\Core\Views\NotificationHost;
use itnovum\openITCOCKPIT\Core\Views\NotificationService;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Class NotificationsController
 * @package App\Controller
 */
class NotificationsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        $AngularNotificationsOverviewControllerRequest = new NotificationsOverviewControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularNotificationsOverviewControllerRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new HostNotificationConditions();
        $Conditions->setContainerIds($this->MY_RIGHTS);
        $Conditions->setFrom($UserTime->toServerTime($AngularNotificationsOverviewControllerRequest->getFrom()));
        $Conditions->setTo($UserTime->toServerTime($AngularNotificationsOverviewControllerRequest->getTo()));
        $Conditions->setOrder($AngularNotificationsOverviewControllerRequest->getOrderForPaginator('NotificationHosts.start_time', 'desc'));
        $Conditions->setStates($AngularNotificationsOverviewControllerRequest->getHostStates());
        $Conditions->setConditions($AngularNotificationsOverviewControllerRequest->getHostFilters());

        //Query notification records
        $NotificationHostsTable = $this->DbBackend->getNotificationHostsTable();

        $all_notifications = [];
        foreach ($NotificationHostsTable->getNotifications($Conditions, $PaginateOMat) as $notification) {
            $NotificationHost = new NotificationHost($notification, $UserTime);
            $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($notification['Hosts']);
            $Command = new Command($notification['Commands']);
            $Contact = new Contact($notification['Contacts']);
            $all_notifications[] = [
                'NotificationHost' => $NotificationHost->toArray(),
                'Host'             => $Host->toArray(),
                'Command'          => $Command->toArray(),
                'Contact'          => $Contact->toArray()
            ];
        }

        $this->set('all_notifications', $all_notifications);

        $toJson = ['all_notifications', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_notifications', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function services() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        $AngularNotificationsOverviewControllerRequest = new NotificationsOverviewControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularNotificationsOverviewControllerRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new ServiceNotificationConditions();
        $Conditions->setContainerIds($this->MY_RIGHTS);
        $Conditions->setFrom($UserTime->toServerTime($AngularNotificationsOverviewControllerRequest->getFrom()));
        $Conditions->setTo($UserTime->toServerTime($AngularNotificationsOverviewControllerRequest->getTo()));
        $Conditions->setOrder($AngularNotificationsOverviewControllerRequest->getOrderForPaginator('NotificationServices.start_time', 'desc'));
        $Conditions->setStates($AngularNotificationsOverviewControllerRequest->getServiceStates());
        $Conditions->setConditions($AngularNotificationsOverviewControllerRequest->getServiceFilters());

        //Query notification records
        $NotificationServicesTable = $this->DbBackend->getNotificationServicesTable();

        $all_notifications = [];
        foreach ($NotificationServicesTable->getNotifications($Conditions, $PaginateOMat) as $notification) {
            $NotificationService = new NotificationService($notification, $UserTime);
            $Service = new Service($notification['Services'], $notification['servicename']);
            $Host = new Host($notification['Hosts']);
            $Command = new Command($notification['Commands']);
            $Contact = new Contact($notification['Contacts']);
            $all_notifications[] = [
                'NotificationService' => $NotificationService->toArray(),
                'Service'             => $Service->toArray(),
                'Host'                => $Host->toArray(),
                'Command'             => $Command->toArray(),
                'Contact'             => $Contact->toArray()
            ];
        }

        $this->set('all_notifications', $all_notifications);

        $toJson = ['all_notifications', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_notifications', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function hostTopNotifications() {
        if (!$this->isApiRequest()) {
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        $AngularNotificationsLogRequest = new NotificationsLogRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularNotificationsLogRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new HostNotificationConditions();
        $Conditions->setContainerIds($this->MY_RIGHTS);
        if ($this->request->getQuery('limit') !== null) {
            $Conditions->setLimit($this->request->getQuery('limit'));
        };
        $Conditions->setFrom($AngularNotificationsLogRequest->getFromInterval());
        // $Conditions->setTo($AngularNotificationsLogRequest->getTo());
        $Conditions->setOrder($AngularNotificationsLogRequest->getOrderForPaginator('NotificationHostsLog.start_time', 'desc'));
        $Conditions->setStates($AngularNotificationsLogRequest->getHostStates());
        // $Conditions->setConditions($AngularNotificationsOverviewControllerRequest->getHostFilters());

        //Query notification records
        $NotificationHostsTable = $this->DbBackend->getNotificationHostsLogTable();

        $all_notifications = [];
        foreach ($NotificationHostsTable->getNotifications($Conditions, $PaginateOMat) as $notification) {
            $NotificationHost = new NotificationHost($notification, $UserTime);
            $Host = new \itnovum\openITCOCKPIT\Core\Views\Host($notification['Hosts']);
            $all_notifications[] = [
                'NotificationHost' => $NotificationHost->toArray(),
                'Host'             => $Host->toArray(),
                'count'            => $NotificationHost->getCount(),
            ];
        }

        $this->set('all_notifications', $all_notifications);

        $toJson = ['all_notifications', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_notifications', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function serviceTopNotifications() {
        if (!$this->isApiRequest()) {
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        $AngularNotificationsLogRequest = new NotificationsLogRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularNotificationsLogRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new ServiceNotificationConditions();
        $Conditions->setContainerIds($this->MY_RIGHTS);
        if ($this->request->getQuery('limit') !== null) {
            $Conditions->setLimit($this->request->getQuery('limit'));
        };
        $Conditions->setFrom($AngularNotificationsLogRequest->getFromInterval());
        // $Conditions->setTo($AngularNotificationsOverviewControllerRequest->getTo());
        $Conditions->setOrder($AngularNotificationsLogRequest->getOrderForPaginator('NotificationServicesLog.start_time', 'desc'));
        $Conditions->setStates($AngularNotificationsLogRequest->getServiceStates());
        //$Conditions->setConditions($AngularNotificationsLogRequest->getServiceFilters());

        //Query notification records
        $NotificationServicesTable = $this->DbBackend->getNotificationServicesLogTable();

        $all_notifications = [];
        foreach ($NotificationServicesTable->getNotifications($Conditions, $PaginateOMat) as $notification) {
            $NotificationService = new NotificationService($notification, $UserTime);
            $Service = new Service($notification['Services'], $notification['servicename']);
            $Host = new Host($notification['Hosts']);
            $all_notifications[] = [
                'NotificationService' => $NotificationService->toArray(),
                'Service'             => $Service->toArray(),
                'Host'                => $Host->toArray(),
                'count'               => $NotificationService->getCount(),
            ];
        }

        $this->set('all_notifications', $all_notifications);

        $toJson = ['all_notifications', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_notifications', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function hostNotification($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var HostsTable $HostsTable */
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

        $session = $this->request->getSession();
        $session->close();

        $AngularNotificationsOverviewControllerRequest = new NotificationsOverviewControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularNotificationsOverviewControllerRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new HostNotificationConditions();
        $Conditions->setContainerIds($this->MY_RIGHTS);
        $Conditions->setFrom($AngularNotificationsOverviewControllerRequest->getFrom());
        $Conditions->setTo($AngularNotificationsOverviewControllerRequest->getTo());
        $Conditions->setOrder($AngularNotificationsOverviewControllerRequest->getOrderForPaginator('NotificationHosts.start_time', 'desc'));
        $Conditions->setStates($AngularNotificationsOverviewControllerRequest->getHostStates());
        $Conditions->setConditions($AngularNotificationsOverviewControllerRequest->getHostFilters());
        $Conditions->setHostUuid($host->get('uuid'));

        //Query notification records
        $NotificationHostsTable = $this->DbBackend->getNotificationHostsTable();

        $all_notifications = [];
        foreach ($NotificationHostsTable->getNotifications($Conditions, $PaginateOMat) as $notification) {
            $NotificationHost = new NotificationHost($notification, $UserTime);
            $Host = new Host($notification['Hosts']);
            $Command = new Command($notification['Commands']);
            $Contact = new Contact($notification['Contacts']);
            $all_notifications[] = [
                'NotificationHost' => $NotificationHost->toArray(),
                'Host'             => $Host->toArray(),
                'Command'          => $Command->toArray(),
                'Contact'          => $Contact->toArray()
            ];
        }

        $this->set('all_notifications', $all_notifications);

        $toJson = ['all_notifications', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_notifications', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function serviceNotification($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }


        $service = $ServicesTable->getServiceByIdForPermissionsCheck($id);
        if (!$this->allowedByContainerId($service->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $AngularNotificationsOverviewControllerRequest = new NotificationsOverviewControllerRequest($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $AngularNotificationsOverviewControllerRequest->getPage());

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        //Process conditions
        $Conditions = new ServiceNotificationConditions();
        $Conditions->setContainerIds($this->MY_RIGHTS);
        $Conditions->setFrom($AngularNotificationsOverviewControllerRequest->getFrom());
        $Conditions->setTo($AngularNotificationsOverviewControllerRequest->getTo());
        $Conditions->setOrder($AngularNotificationsOverviewControllerRequest->getOrderForPaginator('NotificationServices.start_time', 'desc'));
        $Conditions->setStates($AngularNotificationsOverviewControllerRequest->getServiceStates());
        $Conditions->setConditions($AngularNotificationsOverviewControllerRequest->getServiceFilters());
        $Conditions->setServiceUuid($service->get('uuid'));

        //Query notification records
        $NotificationServicesTable = $this->DbBackend->getNotificationServicesTable();

        $all_notifications = [];
        foreach ($NotificationServicesTable->getNotifications($Conditions, $PaginateOMat) as $notification) {
            $NotificationService = new NotificationService($notification, $UserTime);
            $Service = new Service($notification['Services'], $notification['servicename']);
            $Host = new Host($notification['Hosts']);
            $Command = new Command($notification['Commands']);
            $Contact = new Contact($notification['Contacts']);
            $all_notifications[] = [
                'NotificationService' => $NotificationService->toArray(),
                'Service'             => $Service->toArray(),
                'Host'                => $Host->toArray(),
                'Command'             => $Command->toArray(),
                'Contact'             => $Contact->toArray()
            ];
        }

        $this->set('all_notifications', $all_notifications);

        $toJson = ['all_notifications', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_notifications', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

}
