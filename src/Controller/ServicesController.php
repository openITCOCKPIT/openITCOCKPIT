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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Interfaces\AcknowledgementHostsTableInterface;
use App\Lib\Interfaces\AcknowledgementServicesTableInterface;
use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Lib\Interfaces\DowntimehistoryServicesTableInterface;
use App\Lib\Interfaces\HoststatusTableInterface;
use App\Lib\Interfaces\ServicestatusTableInterface;
use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\CommandargumentsTable;
use App\Model\Table\CommandsTable;
use App\Model\Table\ContactgroupsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\DeletedServicesTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\InstantreportsTable;
use App\Model\Table\MacrosTable;
use App\Model\Table\ServicecommandargumentvaluesTable;
use App\Model\Table\ServiceeventcommandargumentvaluesTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\TimeperiodsTable;
use AutoreportModule\Model\Table\AutoreportsTable;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use CustomalertModule\Model\Table\CustomalertsTable;
use DistributeModule\Model\Table\SatellitesTable;
use EventcorrelationModule\Model\Table\EventcorrelationsTable;
use GuzzleHttp\Exception\GuzzleException;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\AcknowledgedServiceConditions;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\CommandArgReplacer;
use itnovum\openITCOCKPIT\Core\Comparison\ServiceComparisonForSave;
use itnovum\openITCOCKPIT\Core\CustomMacroReplacer;
use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\HostMacroReplacer;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\Merger\HostMergerForBrowser;
use itnovum\openITCOCKPIT\Core\Merger\ServiceMergerForBrowser;
use itnovum\openITCOCKPIT\Core\Merger\ServiceMergerForView;
use itnovum\openITCOCKPIT\Core\Reports\DaterangesCreator;
use itnovum\openITCOCKPIT\Core\ServiceConditions;
use itnovum\openITCOCKPIT\Core\ServiceControllerRequest;
use itnovum\openITCOCKPIT\Core\ServiceMacroReplacer;
use itnovum\openITCOCKPIT\Core\ServiceNotificationConditions;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\StatehistoryHostConditions;
use itnovum\openITCOCKPIT\Core\StatehistoryServiceConditions;
use itnovum\openITCOCKPIT\Core\Timeline\AcknowledgementSerializer;
use itnovum\openITCOCKPIT\Core\Timeline\DowntimeSerializer;
use itnovum\openITCOCKPIT\Core\Timeline\Groups;
use itnovum\openITCOCKPIT\Core\Timeline\NotificationSerializer;
use itnovum\openITCOCKPIT\Core\Timeline\StatehistorySerializer;
use itnovum\openITCOCKPIT\Core\Timeline\TimeRangeSerializer;
use itnovum\openITCOCKPIT\Core\UserDefinedMacroReplacer;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\AcknowledgementHost;
use itnovum\openITCOCKPIT\Core\Views\AcknowledgementService;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Core\Views\Command;
use itnovum\openITCOCKPIT\Core\Views\Contact;
use itnovum\openITCOCKPIT\Core\Views\ContainerPermissions;
use itnovum\openITCOCKPIT\Core\Views\DeletedService;
use itnovum\openITCOCKPIT\Core\Views\Downtime;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\NotificationService;
use itnovum\openITCOCKPIT\Core\Views\PerfdataChecker;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryHost;
use itnovum\openITCOCKPIT\Core\Views\StatehistoryService;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServiceFilter;
use itnovum\openITCOCKPIT\Graphite\GraphiteConfig;
use itnovum\openITCOCKPIT\Graphite\GraphiteLoader;
use MapModule\Model\Table\MapsTable;
use SLAModule\Model\Table\SlasTable;
use Statusengine\PerfdataParser;

/**
 * Class ServicesController
 * @package App\Controller
 */
class ServicesController extends AppController {

    use PluginManagerTableTrait;

    /**
     * @throws MissingDbBackendException
     */
    public function index() {
        $User = new User($this->getUser());
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $masterInstanceName = $SystemsettingsTable->getMasterInstanceName();

        $satellites = [];

        if (Plugin::isLoaded('DistributeModule')) {
            /** @var SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

            $satellites = $SatellitesTable->getSatellitesAsListWithDescription($this->MY_RIGHTS);
            $satellites[0] = $masterInstanceName;
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$this->isApiRequest()) {
            $this->set('username', $User->getFullName());
            $this->set('satellites', $satellites);
            $this->set('types', $ServicesTable->getServiceTypes());

            //Only ship HTML template
            return;
        }

        $ServiceFilter = new ServiceFilter($this->request);

        $ServiceControllerRequest = new ServiceControllerRequest($this->request, $ServiceFilter);
        $ServiceConditions = new ServiceConditions(
            $ServiceFilter->indexFilter()
        );
        $ServiceConditions->setContainerIds($this->MY_RIGHTS);

        if ($ServiceControllerRequest->isRequestFromBrowser() === false) {
            $ServiceConditions->setIncludeDisabled(false);
            $ServiceConditions->setContainerIds($this->MY_RIGHTS);
        }

        if ($ServiceControllerRequest->isRequestFromBrowser() === true) {
            $browserContainerIds = $ServiceControllerRequest->getBrowserContainerIdsByRequest();
            foreach ($browserContainerIds as $containerIdToCheck) {
                if (!in_array($containerIdToCheck, $this->MY_RIGHTS)) {
                    $this->render403();
                    return;
                }
            }

            $ServiceConditions->setIncludeDisabled(false);
            $ServiceConditions->setContainerIds($browserContainerIds);

            if ($User->isRecursiveBrowserEnabled()) {
                //get recursive container ids
                $containerIdToResolve = $browserContainerIds;
                $children = $ContainersTable->getChildren($containerIdToResolve[0]);
                $containerIds = Hash::extract($children, '{n}.id');
                $recursiveContainerIds = [];
                foreach ($containerIds as $containerId) {
                    if (in_array($containerId, $this->MY_RIGHTS)) {
                        $recursiveContainerIds[] = $containerId;
                    }
                }
                $ServiceConditions->setContainerIds(array_merge($ServiceConditions->getContainerIds(), $recursiveContainerIds));
            }
        }

        $ServiceConditions->setOrder($ServiceControllerRequest->getOrder([
            'Hosts.name'  => 'asc',
            'servicename' => 'asc'
        ]));


        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceFilter->getPage());

        $AcknowledgementServicesTable = $this->DbBackend->getAcknowledgementServicesTable();
        $DowntimehistoryServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();

        if ($this->DbBackend->isNdoUtils()) {
            $services = $ServicesTable->getServiceIndex($ServiceConditions, $PaginateOMat);
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->DbBackend->isStatusengine3()) {
            $services = $ServicesTable->getServiceIndexStatusengine3($ServiceConditions, $PaginateOMat);
        }

        $hostContainers = [];
        $typesForView = $ServicesTable->getServiceTypesWithStyles();
        foreach ($services as $index => $service) {
            $services[$index]['allow_edit'] = $this->hasRootPrivileges;

        }
        if ($this->hasRootPrivileges === false) {
            foreach ($services as $index => $service) {
                $hostId = $service['_matchingData']['Hosts']['id'];
                if (!isset($hostContainers[$hostId])) {
                    $hostContainers[$hostId] = $HostsTable->getHostContainerIdsByHostId($hostId);
                }

                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $hostContainers[$hostId]);
                $services[$index]['allow_edit'] = $ContainerPermissions->hasPermission();
            }
        }

        $HoststatusTable = $this->DbBackend->getHoststatusTable();

        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->isFlapping()
            ->lastStateChange()
            ->lastHardStateChange();
        $hoststatusCache = $HoststatusTable->byUuid(
            array_unique(Hash::extract($services, '{n}._matchingData.Hosts.uuid')),
            $HoststatusFields
        );

        $all_services = [];
        $UserTime = $User->getUserTime();
        $serviceTypes = $ServicesTable->getServiceTypesWithStyles();
        foreach ($services as $service) {
            $allowEdit = $service['allow_edit'];
            $Host = new Host($service['_matchingData']['Hosts'], $allowEdit);
            if (isset($hoststatusCache[$Host->getUuid()]['Hoststatus'])) {
                $Hoststatus = new Hoststatus($hoststatusCache[$Host->getUuid()]['Hoststatus'], $UserTime);
            } else {
                $Hoststatus = new Hoststatus([], $UserTime);
            }
            $Service = new Service($service, null, $allowEdit);
            $Servicestatus = new Servicestatus($service['Servicestatus'], $UserTime);
            $PerfdataChecker = new PerfdataChecker($Host, $Service, $this->PerfdataBackend, $Servicestatus, $this->DbBackend, $service['service_type']);

            $downtime = [];
            if ($ServiceControllerRequest->includeDowntimeInformation() && $Servicestatus->isInDowntime()) {
                $downtime = $DowntimehistoryServicesTable->byServiceUuid($Service->getUuid());
                if (!empty($downtime)) {
                    $Downtime = new Downtime($downtime, $allowEdit, $UserTime);
                    $downtime = $Downtime->toArray();
                }
            }

            $acknowledgement = [];
            if ($ServiceControllerRequest->includeAcknowledgementInformation() && $Servicestatus->isAcknowledged()) {
                $acknowledgement = $AcknowledgementServicesTable->byServiceUuid($Service->getUuid());
                if (!empty($acknowledgement)) {
                    $Acknowledgement = new AcknowledgementService($acknowledgement, $UserTime, $allowEdit);
                    $acknowledgement = $Acknowledgement->toArray();
                }
            }

            $tmpRecord = [
                'Service'         => $Service->toArray(),
                'Host'            => $Host->toArray(),
                'Hoststatus'      => $Hoststatus->toArray(),
                'Servicestatus'   => $Servicestatus->toArray(),
                'ServiceType'     => $serviceTypes[$service['service_type']],
                'Downtime'        => $downtime,
                'Acknowledgement' => $acknowledgement
            ];

            $satelliteName = $masterInstanceName;
            $satellite_id = 0;

            if ($Host->isSatelliteHost() && isset($satellites[$Host->getSatelliteId()])) {
                $satelliteName = $satellites[$Host->getSatelliteId()];
                $satellite_id = $Host->getSatelliteId();
            }
            $tmpRecord['Host']['satelliteName'] = $satelliteName;
            $tmpRecord['Host']['satelliteId'] = $satellite_id;
            $tmpRecord['Service']['has_graph'] = $PerfdataChecker->hasPerfdata();
            $all_services[] = $tmpRecord;
        }

        $satellites = Api::makeItJavaScriptAble($satellites);
        $this->set('all_services', $all_services);
        $this->set('username', $User->getFullName());
        $this->set('satellites', $satellites);
        $this->viewBuilder()->setOption('serialize', ['all_services', 'username', 'satellites']);
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->exists($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        /** @var \App\Model\Entity\Service $service */
        $service = $ServicesTable->getServiceById($id);

        if (!$this->allowedByContainerId($service->get('host')->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields->wildcard();

        $ServicestatusTable = $this->DbBackend->getServicestatusTable();
        $servicestatus = $ServicestatusTable->byUuid($service->get('uuid'), $ServicestatusFields);

        if (empty($servicestatus)) {
            $servicestatus = [
                'Servicestatus' => []
            ];
        }
        $Servicestatus = new Hoststatus($servicestatus['Servicestatus']);

        $this->set('service', $service);
        $this->set('servicestatus', $Servicestatus->toArray());
        $this->viewBuilder()->setOption('serialize', ['service', 'servicestatus']);
    }

    public function byUuid($uuid) {
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        try {
            $service = $ServicesTable->getServiceByUuid($uuid);

            if (!$this->allowedByContainerId($service->get('host')->getContainerIds(), false)) {
                $this->render403();
                return;
            }
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException('Service not found');
        }

        $this->set('service', $service);
        $this->viewBuilder()->setOption('serialize', ['service']);
    }

    public function notMonitored() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        $ServiceFilter = new ServiceFilter($this->request);

        $ServiceControllerRequest = new ServiceControllerRequest($this->request, $ServiceFilter);
        $ServiceConditions = new ServiceConditions(
            $ServiceFilter->disabledFilter()
        );
        $ServiceConditions->setContainerIds($this->MY_RIGHTS);
        $ServiceConditions->setOrder($ServiceControllerRequest->getOrder('Hosts.name', 'asc'));

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceFilter->getPage());

        if ($this->DbBackend->isNdoUtils()) {
            $services = $ServicesTable->getServiceNotMonitored($ServiceConditions, $PaginateOMat);
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->DbBackend->isStatusengine3()) {
            $services = $ServicesTable->getServiceNotMonitoredStatusengine3($ServiceConditions, $PaginateOMat);
        }

        $hostContainers = [];
        foreach ($services as $index => $service) {
            $services[$index]['allow_edit'] = $this->hasRootPrivileges;

        }
        if ($this->hasRootPrivileges === false) {
            foreach ($services as $index => $service) {
                $hostId = $service['_matchingData']['Hosts']['id'];
                if (!isset($hostContainers[$hostId])) {
                    $hostContainers[$hostId] = $HostsTable->getHostContainerIdsByHostId($hostId);
                }

                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $hostContainers[$hostId]);
                $services[$index]['allow_edit'] = $ContainerPermissions->hasPermission();
            }
        }

        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields->currentState()->isHardstate();
        $hoststatusCache = $HoststatusTable->byUuid(
            array_unique(Hash::extract($services, '{n}._matchingData.Hosts.uuid')),
            $HoststatusFields
        );


        $all_services = [];
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();
        $serviceTypes = $ServicesTable->getServiceTypesWithStyles();
        foreach ($services as $service) {
            $allowEdit = $service['allow_edit'];
            $Host = new Host($service['_matchingData']['Hosts'], $allowEdit);
            if (isset($hoststatusCache[$Host->getUuid()]['Hoststatus'])) {
                $Hoststatus = new Hoststatus($hoststatusCache[$Host->getUuid()]['Hoststatus'], $UserTime);
            } else {
                $Hoststatus = new Hoststatus([], $UserTime);
            }
            $Service = new Service($service, null, $allowEdit);

            $tmpRecord = [
                'Service'     => $Service->toArray(),
                'ServiceType' => $serviceTypes[$service['service_type']],
                'Host'        => $Host->toArray(),
                'Hoststatus'  => $Hoststatus->toArray()
            ];
            $all_services[] = $tmpRecord;
        }

        $this->set('all_services', $all_services);
        $toJson = ['all_services', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_services', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function disabled() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }


        $ServiceFilter = new ServiceFilter($this->request);

        $ServiceControllerRequest = new ServiceControllerRequest($this->request, $ServiceFilter);
        $ServiceConditions = new ServiceConditions(
            $ServiceFilter->notMonitoredFilter()
        );
        $ServiceConditions->setContainerIds($this->MY_RIGHTS);
        $ServiceConditions->setOrder($ServiceControllerRequest->getOrder('Hosts.name', 'asc'));

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceFilter->getPage());

        $services = $ServicesTable->getServicesForDisabled($ServiceConditions, $PaginateOMat);

        $hostContainers = [];

        foreach ($services as $index => $service) {
            $services[$index]['allow_edit'] = $this->hasRootPrivileges;

        }
        if ($this->hasRootPrivileges === false) {
            foreach ($services as $index => $service) {
                $hostId = $service['_matchingData']['Hosts']['id'];
                if (!isset($hostContainers[$hostId])) {
                    $hostContainers[$hostId] = $HostsTable->getHostContainerIdsByHostId($hostId);
                }

                $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $hostContainers[$hostId]);
                $services[$index]['allow_edit'] = $ContainerPermissions->hasPermission();
            }
        }

        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields->currentState()->isHardstate();
        $hoststatusCache = $HoststatusTable->byUuid(
            array_unique(Hash::extract($services, '{n}._matchingData.Hosts.uuid')),
            $HoststatusFields
        );


        $all_services = [];
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();
        foreach ($services as $service) {
            $allowEdit = $service['allow_edit'];
            $Host = new Host($service['_matchingData']['Hosts'], $allowEdit);
            if (isset($hoststatusCache[$Host->getUuid()]['Hoststatus'])) {
                $Hoststatus = new Hoststatus($hoststatusCache[$Host->getUuid()]['Hoststatus'], $UserTime);
            } else {
                $Hoststatus = new Hoststatus([], $UserTime);
            }
            $Service = new Service($service, null, $allowEdit);

            $tmpRecord = [
                'Service'    => $Service->toArray(),
                'Host'       => $Host->toArray(),
                'Hoststatus' => $Hoststatus->toArray()
            ];
            $all_services[] = $tmpRecord;
        }

        $this->set('all_services', $all_services);
        $toJson = ['all_services', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_services', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('post')) {
            $servicetemplateId = $this->request->getData('Service.servicetemplate_id');

            if (!$this->request->getData('Service.host_id') || !$this->request->getData('Service.servicetemplate_id')) {
                $errors = [];
                $this->response = $this->response->withStatus(400);
                if (!$this->request->getData('Service.host_id')) {

                    $errors['host_id'] = [
                        'empty' => __('This field cannot be left empty')
                    ];
                }
                if (!$this->request->getData('Service.servicetemplate_id')) {
                    $errors['servicetemplate_id'] = [
                        'empty' => __('This field cannot be left empty')
                    ];
                }
                $this->set('error', $errors);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if ($servicetemplateId === null) {
                throw new BadRequestException('Service.servicetemplate_id needs to set.');
            }

            $hostId = $this->request->getData('Service.host_id');
            if ($hostId === null) {
                throw new BadRequestException('Service.host_id needs to set.');
            }

            /** @var $HosttemplatesTable HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            /** @var $ServicetemplatesTable ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            if (!$ServicetemplatesTable->existsById($servicetemplateId)) {
                throw new NotFoundException(__('Invalid service template'));
            }

            $host = $HostsTable->get($hostId);
            $request = $this->request->getData();
            $request['Host'] = [
                'id'   => $host->get('id'),
                'name' => $host->get('name')
            ];

            $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($servicetemplateId);


            $servicename = $this->request->getData('Service.name');
            if ($servicename === null || $servicename === '') {
                $servicename = $servicetemplate['Servicetemplate']['name'];
            }

            $ServiceComparisonForSave = new ServiceComparisonForSave(
                $request,
                $servicetemplate,
                $HostsTable->getContactsAndContactgroupsById($host->get('id')),
                $HosttemplatesTable->getContactsAndContactgroupsById($host->get('hosttemplate_id'))
            );
            $serviceData = $ServiceComparisonForSave->getDataForSaveForAllFields();
            $serviceData['uuid'] = UUID::v4();

            //Add required fields for validation
            $serviceData['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
            $serviceData['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
            $serviceData['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
            $serviceData['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
            $serviceData['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

            $service = $ServicesTable->newEntity($serviceData);

            $ServicesTable->save($service);
            if ($service->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $service->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                $User = new User($this->getUser());
                $extDataForChangelog = $ServicesTable->resolveDataForChangelog($request);
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'add',
                    'services',
                    $service->get('id'),
                    OBJECT_SERVICE,
                    $host->get('container_id'),
                    $User->getId(),
                    $host->get('name') . '/' . $servicename,
                    array_merge($request, $extDataForChangelog)
                );

                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }


                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($service); // REST API ID serialization
                    return;
                }
            }
            $this->set('service', $service);
            $this->viewBuilder()->setOption('serialize', ['service']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Service not found'));
        }

        $service = $ServicesTable->getServiceForEdit($id);
        $host = $HostsTable->getHostForServiceEdit($service['Service']['host_id']);
        $isSlaHost = $HostsTable->isHostInSla($service['Service']['host_id']);


        $hostContactsAndContactgroups = $HostsTable->getContactsAndContactgroupsById($host['Host']['id']);
        $hosttemplateContactsAndContactgroups = $HosttemplatesTable->getContactsAndContactgroupsById($host['Host']['hosttemplate_id']);


        $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($service['Service']['servicetemplate_id']);
        $ServiceMergerForView = new ServiceMergerForView(
            $service,
            $servicetemplate,
            $hostContactsAndContactgroups,
            $hosttemplateContactsAndContactgroups
        );
        $mergedService = $ServiceMergerForView->getDataForView();
        $serviceForChangelog = $mergedService;


        if (!$this->allowedByContainerId($host['Host']['hosts_to_containers_sharing']['_ids'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return service information
            $typesForView = $ServicesTable->getServiceTypesWithStyles();
            $serviceType = $typesForView[$mergedService['Service']['service_type']];

            $this->set('service', $mergedService);
            $this->set('host', $host);
            $this->set('servicetemplate', $servicetemplate);
            $this->set('hostContactsAndContactgroups', $hostContactsAndContactgroups);
            $this->set('hosttemplateContactsAndContactgroups', $hosttemplateContactsAndContactgroups);
            $this->set('areContactsInheritedFromHosttemplate', $ServiceMergerForView->areContactsInheritedFromHosttemplate());
            $this->set('areContactsInheritedFromHost', $ServiceMergerForView->areContactsInheritedFromHost());
            $this->set('areContactsInheritedFromServicetemplate', $ServiceMergerForView->areContactsInheritedFromServicetemplate());
            $this->set('serviceType', $serviceType);
            $this->set('isSlaHost', $isSlaHost);


            $this->viewBuilder()->setOption('serialize', [
                'service',
                'host',
                'servicetemplate',
                'hostContactsAndContactgroups',
                'hosttemplateContactsAndContactgroups',
                'areContactsInheritedFromHosttemplate',
                'areContactsInheritedFromHost',
                'areContactsInheritedFromServicetemplate',
                'serviceType',
                'isSlaHost'
            ]);
            return;
        }


        if ($this->request->is('post')) {
            $servicetemplateId = $this->request->getData('Service.servicetemplate_id');
            if ($servicetemplateId === null) {
                throw new \Exception('Service.servicetemplate_id needs to set.');
            }

            if (!$ServicetemplatesTable->existsById($servicetemplateId)) {
                throw new NotFoundException(__('Invalid service template'));
            }
            $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($servicetemplateId);

            $servicename = $this->request->getData('Service.name');
            if ($servicename === null || $servicename === '') {
                $servicename = $servicetemplate['Servicetemplate']['name'];
            }

            $ServiceComparisonForSave = new ServiceComparisonForSave(
                $this->request->getData(),
                $servicetemplate,
                $HostsTable->getContactsAndContactgroupsById($host['Host']['id']),
                $HosttemplatesTable->getContactsAndContactgroupsById($host['Host']['hosttemplate_id'])
            );
            $dataForSave = $ServiceComparisonForSave->getDataForSaveForAllFields();

            //Add required fields for validation
            $dataForSave['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
            $dataForSave['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
            $dataForSave['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
            $dataForSave['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
            $dataForSave['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

            //Update contact data
            $serviceEntity = $ServicesTable->get($id);
            $serviceEntity->setAccess('uuid', false);
            $serviceEntity->setAccess('id', false);
            $serviceEntity->setAccess('host_id', false);

            $serviceEntity = $ServicesTable->patchEntity($serviceEntity, $dataForSave);
            $ServicesTable->save($serviceEntity);

            $request = $this->request->getData();
            $request['Host'] = [
                [
                    'id'   => $host['Host']['id'],
                    'name' => $host['Host']['name'],
                ]
            ];

            if ($serviceEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $serviceEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                $User = new User($this->getUser());

                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'services',
                    $serviceEntity->get('id'),
                    OBJECT_SERVICE,
                    $host['Host']['container_id'],
                    $User->getId(),
                    $host['Host']['name'] . '/' . $servicename,
                    array_merge($ServicesTable->resolveDataForChangelog($request), $request),
                    array_merge($ServicesTable->resolveDataForChangelog($serviceForChangelog), $serviceForChangelog)
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($serviceEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('service', $serviceEntity);
            $this->viewBuilder()->setOption('serialize', ['service']);
        }
    }

    public function deleted() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        /** @var $DeletedServicesTable DeletedServicesTable */
        $DeletedServicesTable = TableRegistry::getTableLocator()->get('DeletedServices');
        $ServiceFilter = new ServiceFilter($this->request);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ServiceFilter->getPage());
        $result = $DeletedServicesTable->getDeletedServicesIndex($ServiceFilter, $PaginateOMat);

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();
        $all_services = [];
        foreach ($result as $deletedService) {
            $DeletedService = new DeletedService($deletedService, $UserTime);
            $all_services[] = [
                'DeletedService' => $DeletedService->toArray()
            ];
        }

        $this->set('all_services', $all_services);
        $toJson = ['all_services', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['all_services', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        /** @var \App\Model\Entity\Service $service */
        $service = $ServicesTable->find()
            ->contain([
                'ServiceescalationsServiceMemberships',
                'ServicedependenciesServiceMemberships'
            ])
            ->where([
                'Services.id' => $id
            ])
            ->firstOrFail();

        $host = $HostsTable->getHostByIdForPermissionCheck($service->get('host_id'));
        if (!$this->allowedByContainerId($host->getContainerIds(), true)) {
            $this->render403();
            return;
        }

        $usedBy = $service->isUsedByModules();
        if (!empty($usedBy)) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Issue while deleting service'));
            $this->set('usedBy', $usedBy);
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
            return;
        }

        //Service is not in use by any Module an can be deleted.
        $User = new User($this->getUser());
        if ($ServicesTable->__delete($service, $User)) {
            $this->set('success', true);
            $this->set('message', __('Service successfully deleted'));
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->set('success', false);
        $this->set('message', __('Error while deleting service'));
        $this->viewBuilder()->setOption('serialize', ['success']);
    }


    public function copy() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->request->is('get')) {
            $hostId = $this->request->getQuery('hostId');
            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException('Invalid host');
            }

            $containerId = $HostsTable->getHostPrimaryContainerIdByHostId($hostId);
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

            $services = $ServicesTable->getServicesForCopy(func_get_args(), $containerIds);

            /** @var $CommandsTable CommandsTable */
            $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
            $commands = $CommandsTable->getCommandByTypeAsList(CHECK_COMMAND);
            $eventhandlerCommands = $CommandsTable->getCommandByTypeAsList(EVENTHANDLER_COMMAND);

            $this->set('services', $services);
            $this->set('commands', Api::makeItJavaScriptAble($commands));
            $this->set('eventhandlerCommands', Api::makeItJavaScriptAble($eventhandlerCommands));
            $this->viewBuilder()->setOption('serialize', ['services', 'commands', 'eventhandlerCommands']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $Cache = new KeyValueStore();
            $ServicetemplateCache = new KeyValueStore();
            $ServicetemplateEditCache = new KeyValueStore();
            $ObjectsCacheChangelog = new ObjectsCache();

            $postData = $this->request->getData('data');
            $hostId = $this->request->getData('hostId');

            if (!$HostsTable->existsById($hostId)) {
                throw new NotFoundException('Invalid host');
            }

            $host = $HostsTable->getHostForServiceEdit($hostId);

            if (!$this->allowedByContainerId($host['Host']['hosts_to_containers_sharing']['_ids'])) {
                $this->render403();
                return;
            }

            $hostContactsAndContactgroups = $HostsTable->getContactsAndContactgroupsById($host['Host']['id']);
            $hosttemplateContactsAndContactgroups = $HosttemplatesTable->getContactsAndContactgroupsById($host['Host']['hosttemplate_id']);

            $User = new User($this->getUser());

            foreach ($postData as $index => $serviceData) {
                if (!isset($serviceData['Service']['id'])) {
                    //Create/clone service
                    $sourceServiceId = $serviceData['Source']['id'];
                    if (!$Cache->has($sourceServiceId)) {
                        $sourceService = $ServicesTable->getServiceForEdit($sourceServiceId);

                        if (!$ServicetemplateCache->has($sourceService['Service']['servicetemplate_id'])) {
                            $sourceServiceServicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($sourceService['Service']['servicetemplate_id']);
                            $ServicetemplateCache->set($sourceService['Service']['servicetemplate_id'], $sourceServiceServicetemplate);
                        }

                        $sourceServiceServicetemplate = $ServicetemplateCache->get($sourceService['Service']['servicetemplate_id']);

                        $ServiceMergerForView = new ServiceMergerForView(
                            $sourceService,
                            $sourceServiceServicetemplate
                        );

                        $sourceService = $ServiceMergerForView->getDataForView();

                        //This service is not used - because it does not exists yet
                        $sourceService['Service']['usage_flag'] = 0;

                        unset($sourceService['Service']['id'], $sourceService['Service']['uuid'], $sourceService['Service']['host_id']);

                        foreach ($sourceService['Service']['servicecommandargumentvalues'] as $i => $servicecommandargumentvalues) {
                            unset($sourceService['Service']['servicecommandargumentvalues'][$i]['id']);
                            if (isset($sourceService['Service']['servicecommandargumentvalues'][$i]['service_id'])) {
                                unset($sourceService['Service']['servicecommandargumentvalues'][$i]['service_id']);
                            }

                            if (isset($sourceService['Service']['servicecommandargumentvalues'][$i]['servicetemplate_id'])) {
                                unset($sourceService['Service']['servicecommandargumentvalues'][$i]['servicetemplate_id']);
                            }
                        }
                        if (!empty($serviceData['Service']['servicecommandargumentvalues'])) {
                            $newServiceData['Service']['servicecommandargumentvalues'] = $serviceData['Service']['servicecommandargumentvalues'];
                        }


                        foreach ($sourceService['Service']['serviceeventcommandargumentvalues'] as $i => $serviceeventcommandargumentvalues) {
                            unset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['id']);
                            if (isset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['service_id'])) {
                                unset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['service_id']);
                            }

                            if (isset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['servicetemplate_id'])) {
                                unset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['servicetemplate_id']);
                            }
                        }

                        if (!empty($serviceData['Service']['serviceeventcommandargumentvalues'])) {
                            $newServiceData['Service']['serviceeventcommandargumentvalues'] = $serviceData['Service']['serviceeventcommandargumentvalues'];
                        }

                        foreach ($sourceService['Service']['serviceeventcommandargumentvalues'] as $i => $serviceeventcommandargumentvalues) {
                            unset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['id']);
                            if (isset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['service_id'])) {
                                unset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['service_id']);
                            }

                            if (isset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['servicetemplate_id'])) {
                                unset($sourceService['Service']['serviceeventcommandargumentvalues'][$i]['servicetemplate_id']);
                            }
                        }

                        foreach ($sourceService['Service']['customvariables'] as $i => $customvariables) {
                            unset($sourceService['Service']['customvariables'][$i]['id']);
                            if (isset($sourceService['Service']['customvariables'][$i]['object_id'])) {
                                unset($sourceService['Service']['customvariables'][$i]['object_id']);
                            }
                        }

                        $Cache->set($sourceServiceId, $sourceService);
                    }

                    $sourceService = $Cache->get($sourceServiceId);
                    if (isset($newServiceData['Service']['servicecommandargumentvalues'])) {
                        $sourceService['Service']['servicecommandargumentvalues'] = $newServiceData['Service']['servicecommandargumentvalues'];
                    }
                    $newServiceData = $sourceService;
                    $newServiceData['Service']['host_id'] = $hostId;
                    $newServiceData['Service']['name'] = $serviceData['Service']['name'];
                    $newServiceData['Service']['description'] = $serviceData['Service']['description'];
                    $newServiceData['Service']['command_id'] = $serviceData['Service']['command_id'];
                }
                $action = 'copy';
                if (isset($serviceData['Service']['id'])) {
                    //Update existing service
                    //This happens, if a user copy multiple services, and one run into an validation error
                    //All services without validation errors got already saved to the database
                    $newServiceData = $ServicesTable->getServiceForEdit($serviceData['Service']['id']);
                    $serviceForChangelog = $newServiceData;

                    $newServiceData['Service']['name'] = $serviceData['Service']['name'];
                    $newServiceData['Service']['description'] = $serviceData['Service']['description'];
                    $newServiceData['Service']['command_id'] = $serviceData['Service']['command_id'];
                    if (!empty($serviceData['Service']['servicecommandargumentvalues'])) {
                        $newServiceData['Service']['servicecommandargumentvalues'] = $serviceData['Service']['servicecommandargumentvalues'];
                    }

                    $action = 'edit';
                }

                $servicename = $newServiceData['Service']['name'];
                // Replace service template values with zero
                if (!$ServicetemplateEditCache->has($newServiceData['Service']['servicetemplate_id'])) {
                    $servicetemplate = $ServicetemplatesTable->getServicetemplateForDiff($newServiceData['Service']['servicetemplate_id']);
                    $ServicetemplateEditCache->set($newServiceData['Service']['servicetemplate_id'], $servicetemplate);
                }
                $servicetemplate = $ServicetemplateEditCache->get($newServiceData['Service']['servicetemplate_id']);

                $ServiceComparisonForSave = new ServiceComparisonForSave(
                    $newServiceData,
                    $servicetemplate,
                    $hostContactsAndContactgroups,
                    $hosttemplateContactsAndContactgroups
                );
                $serviceData = $ServiceComparisonForSave->getDataForSaveForAllFields();
                //Add required fields for validation
                $serviceData['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
                $serviceData['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
                $serviceData['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
                $serviceData['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
                $serviceData['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

                //Container permissions check for contacts, contact groups and time periods
                if (!empty($serviceData['contacts']['_ids'])) {
                    // Host can use this contacts
                    // Contacts are different than in the service template
                    // Check if the contacts can be used by the host
                    $serviceData['contacts']['_ids'] = $ContactsTable->removeContactsWhichAreNotInContainer(
                        $serviceData['contacts']['_ids'],
                        $host['Host']['container_id']
                    );
                }

                if (!empty($serviceData['contactgroups']['_ids'])) {
                    // Host can use this contactgroups
                    // Contactgroups are different than in the service template
                    // Check if the contactgroups can be used by the host
                    $serviceData['contactgroups']['_ids'] = $ContactgroupsTable->removeContactgroupsWhichAreNotInContainer(
                        $serviceData['contactgroups']['_ids'],
                        $host['Host']['container_id']
                    );
                }

                if ($serviceData['check_period_id'] !== null) {
                    // Host can use this template
                    // Timeperiod is different than in the service template
                    // Check if this time period can be used by the host
                    $serviceData['check_period_id'] = $TimeperiodsTable->checkTimeperiodIdForContainerPermissions(
                        $serviceData['check_period_id'],
                        $host['Host']['container_id'],
                        $host['Host']['check_period_id']
                    );
                }

                if ($serviceData['notify_period_id'] !== null) {
                    // Host can use this template
                    // Timeperiod is different than in the service template
                    // Check if this time period can be used by the host
                    $serviceData['notify_period_id'] = $TimeperiodsTable->checkTimeperiodIdForContainerPermissions(
                        $serviceData['notify_period_id'],
                        $host['Host']['container_id'],
                        $host['Host']['notify_period_id']
                    );
                }

                if ($action === 'copy') {
                    $serviceData['uuid'] = UUID::v4();
                    $newServiceEntity = $ServicesTable->newEntity($serviceData);
                } else {
                    $newServiceEntity = $ServicesTable->get($newServiceData['Service']['id']);
                    $newServiceEntity->setAccess('uuid', false);
                    $newServiceEntity->setAccess('id', false);
                    $newServiceEntity->setAccess('host_id', false);
                    $newServiceEntity = $ServicesTable->patchEntity($newServiceEntity, $serviceData);
                }

                $ServicesTable->save($newServiceEntity);

                $postData[$index]['Error'] = [];
                if ($newServiceEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newServiceEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Service']['id'] = $newServiceEntity->get('id');

                    if ($action === 'copy') {
                        /** @var  ChangelogsTable $ChangelogsTable */
                        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                        $changelog_data = $ChangelogsTable->parseDataForChangelog(
                            $action,
                            'services',
                            $postData[$index]['Service']['id'],
                            OBJECT_SERVICE,
                            $host['Host']['container_id'],
                            $User->getId(),
                            $host['Host']['name'] . '/' . $servicename,
                            $serviceData
                        );
                    } else {
                        /** @var  ChangelogsTable $ChangelogsTable */
                        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                        $changelog_data = $ChangelogsTable->parseDataForChangelog(
                            $action,
                            'services',
                            $postData[$index]['Service']['id'],
                            OBJECT_SERVICE,
                            $host['Host']['container_id'],
                            $User->getId(),
                            $host['Host']['name'] . '/' . $servicename,
                            array_merge($ServicesTable->resolveDataForChangelog($serviceData, $ObjectsCacheChangelog), $serviceData),
                            array_merge($ServicesTable->resolveDataForChangelog($serviceForChangelog, $ObjectsCacheChangelog), $serviceForChangelog)
                        );
                    }

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
    public function deactivate($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        $service = $ServicesTable->get($id, [
            'contain' => [
                'Servicetemplates'
            ]
        ]);
        $host = $HostsTable->getHostForServiceEdit($service->get('host_id'));
        if (!$this->allowedByContainerId($host['Host']['hosts_to_containers_sharing']['_ids'])) {
            $this->render403();
            return;
        }

        $service->set('disabled', 1);
        $ServicesTable->save($service);

        if ($service->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('message', __('Issue while disabling service'));
            $this->set('error', $service->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error', 'success', 'message']);
            return;
        }

        $User = new User($this->getUser());
        /** @var  ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');
        $serviceName = !empty($service->get('name')) ? $service->get('name') : $service->get('servicetemplate')->get('name');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'deactivate',
            'services',
            $id,
            OBJECT_SERVICE,
            $host['Host']['container_id'],
            $User->getId(),
            $host['Host']['name'] . '/' . $serviceName,
            []
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        $this->set('success', true);
        $this->set('id', $id);
        $this->set('message', __('Service successfully disabled'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message', 'id']);
    }

    /**
     * @param int|null $id
     */
    public function enable($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        $service = $ServicesTable->get($id, [
            'contain' => [
                'Servicetemplates'
            ]
        ]);
        $host = $HostsTable->getHostForServiceEdit($service->get('host_id'));
        if (!$this->allowedByContainerId($host['Host']['hosts_to_containers_sharing']['_ids'])) {
            $this->render403();
            return;
        }

        if ($host['Host']['disabled'] === 1) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Could not enable service, because associated host is also disabled.'));
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message']);
            return;
        }

        $service->set('disabled', 0);
        $ServicesTable->save($service);

        if ($service->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('message', __('Issue while enabling service'));
            $this->set('error', $service->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error', 'success', 'message']);
            return;
        }

        $User = new User($this->getUser());
        /** @var  ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');
        $serviceName = !empty($service->get('name')) ? $service->get('name') : $service->get('servicetemplate')->get('name');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'activate',
            'services',
            $id,
            OBJECT_SERVICE,
            $host['Host']['container_id'],
            $User->getId(),
            $host['Host']['name'] . '/' . $serviceName,
            []
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        $this->set('success', true);
        $this->set('id', $id);
        $this->set('message', __('Service successfully enabled'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message', 'id']);
    }

    /**
     * @param int|string|null $idOrUuid
     * @throws MissingDbBackendException
     * @throws GuzzleException
     * @throws \Exception
     */
    public function browser($idOrUuid = null) {
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $canUserSeeCheckCommand = isset($this->PERMISSIONS['services']['checkcommand']);

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $blurryCommandLine = $SystemsettingsTable->blurCheckCommand();
        $masterInstanceName = $SystemsettingsTable->getMasterInstanceName();

        if ($this->isHtmlRequest()) {
            //Only ship template
            $this->set('username', $User->getFullName());
            $this->set('blurryCommandLine', $blurryCommandLine);
            $this->set('masterInstanceName', $masterInstanceName);
            return;
        }


        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $id = $idOrUuid;
        if (!is_numeric($idOrUuid)) {
            if (preg_match(UUID::regex(), $idOrUuid)) {
                try {
                    $lookupService = $ServicesTable->getServiceByUuid($idOrUuid);
                    $id = $lookupService->get('id');
                } catch (RecordNotFoundException $e) {
                    throw new NotFoundException(__('Service not found'));
                }
            }
        }
        unset($idOrUuid);

        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $HoststatusTable HoststatusTableInterface */
        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        /** @var $ServicestatusTable ServicestatusTableInterface */
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();
        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var $AcknowledgementHostsTable AcknowledgementHostsTableInterface */
        $AcknowledgementHostsTable = $this->DbBackend->getAcknowledgementHostsTable();
        /** @var $AcknowledgementServicesTable AcknowledgementServicesTableInterface */
        $AcknowledgementServicesTable = $this->DbBackend->getAcknowledgementServicesTable();
        /** @var $DowntimehistoryHostsTable DowntimehistoryHostsTableInterface */
        $DowntimehistoryHostsTable = $this->DbBackend->getDowntimehistoryHostsTable();
        /** @var $DowntimehistoryServicesTable DowntimehistoryServicesTableInterface */
        $DowntimehistoryServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var $SystemsettingsTable SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Service not found'));
        }

        $service = $ServicesTable->getServiceForBrowser($id);

        //Check permissions
        $host = $HostsTable->getHostForServiceEdit($service['host_id']);
        if (!$this->allowedByContainerId($host['Host']['hosts_to_containers_sharing']['_ids'], false)) {
            $this->render403();
            return;
        }

        $allowEdit = $this->hasRootPrivileges;
        if ($this->hasRootPrivileges === false) {
            $ContainerPermissions = new ContainerPermissions($this->MY_RIGHTS_LEVEL, $host['Host']['hosts_to_containers_sharing']['_ids']);
            $allowEdit = $ContainerPermissions->hasPermission();
        }

        $serviceObj = new Service($service, $allowEdit);

        //Load required data to merge and display inheritance data
        $hostContactsAndContactgroups = $HostsTable->getContactsAndContactgroupsByIdForServiceBrowser($host['Host']['id']);
        $hosttemplateContactsAndContactgroups = $HosttemplatesTable->getContactsAndContactgroupsByIdForServiceBrowser($host['Host']['hosttemplate_id']);
        $servicetemplate = $ServicetemplatesTable->getServicetemplateForServiceBrowser($service['servicetemplate_id']);

        //Merge service and inheritance data
        $ServiceMergerForView = new ServiceMergerForBrowser(
            $service,
            $servicetemplate,
            $hostContactsAndContactgroups,
            $hosttemplateContactsAndContactgroups
        );
        $mergedService = $ServiceMergerForView->getDataForView();

        //Load host
        $hostEntity = $HostsTable->getHostById($host['Host']['id']);
        $hostObj = new Host($hostEntity, $allowEdit);
        $host = $hostObj->toArray();
        $host['is_satellite_host'] = $hostObj->isSatelliteHost();

        //Load required data to merge and display inheritance data
        $hosttemplate = $HosttemplatesTable->getHosttemplateForHostBrowser($host['hosttemplate_id']);

        //Merge host and inheritance data
        $HostMergerForBrowser = new HostMergerForBrowser(
            $HostsTable->getHostForBrowser($host['id']),
            $hosttemplate
        );
        $mergedHost = $HostMergerForBrowser->getDataForView();

        $replacePasswordInObjectMacros = false;
        try {
            $systemsettingsReplacePasswordsEntity = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.REPLACE_PASSWORD_IN_OBJECT_MACROS');
            if ($systemsettingsReplacePasswordsEntity->get('value') === '1') {
                $replacePasswordInObjectMacros = true;
            }
        } catch (RecordNotFoundException $e) {
            // Rocket not found in system settings - do replace passwords in $_HOSTFOOBAR$ custom variables
        }

        //Replace macros in service url
        $HostMacroReplacer = new HostMacroReplacer($host);
        $ServiceMacroReplacer = new ServiceMacroReplacer($mergedService);
        $ServiceCustomMacroReplacer = new CustomMacroReplacer($mergedService['customvariables'], OBJECT_SERVICE, $replacePasswordInObjectMacros);

        $HostCustomMacroReplacer = new CustomMacroReplacer($mergedHost['customvariables'], OBJECT_HOST, $replacePasswordInObjectMacros);
        $mergedService['service_url_replaced'] =
            $ServiceMacroReplacer->replaceBasicMacros(                  // Replace $SERVICEDESCRIPTION$
                $HostMacroReplacer->replaceBasicMacros(                 // Replace $HOSTNAME$
                    $HostCustomMacroReplacer->replaceAllMacros(         // Replace $_HOSTFOOBAR$
                        $ServiceCustomMacroReplacer->replaceAllMacros(  // Replace $_SERVICEFOOBAR$
                            $mergedService['service_url']
                        )
                    )
                )
            );

        $checkCommand = $CommandsTable->getCommandById($mergedService['command_id']);
        $checkPeriod = $TimeperiodsTable->getTimeperiodByIdCake4($mergedService['check_period_id']);
        $notifyPeriod = $TimeperiodsTable->getTimeperiodByIdCake4($mergedService['notify_period_id']);

        // Replace $ARGn$
        $ArgnReplacer = new CommandArgReplacer($mergedService['servicecommandargumentvalues']);
        $serviceCommandLine = $ArgnReplacer->replace($checkCommand['Command']['command_line']);

        // Replace $_SERVICEFOOBAR$
        $serviceCommandLine = $ServiceCustomMacroReplacer->replaceAllMacros($serviceCommandLine);

        // Replace $_HOSTFOOBAR$
        $serviceCommandLine = $HostCustomMacroReplacer->replaceAllMacros($serviceCommandLine);

        // Replace $HOSTNAME$
        $serviceCommandLine = $HostMacroReplacer->replaceBasicMacros($serviceCommandLine);

        // Replace $SERVICEDESCRIPTION$
        $serviceCommandLine = $ServiceMacroReplacer->replaceBasicMacros($serviceCommandLine);

        // Replace $USERn$ Macros (if enabled)
        try {
            $systemsettingsEntity = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.REPLACE_USER_MACROS');
            if ($systemsettingsEntity->get('value') === '1') {
                /** @var MacrosTable $MacrosTable */
                $MacrosTable = TableRegistry::getTableLocator()->get('Macros');
                $macros = $MacrosTable->getAllMacros();

                $UserMacroReplacer = new UserDefinedMacroReplacer($macros);
                $serviceCommandLine = $UserMacroReplacer->replaceMacros($serviceCommandLine);
            }
        } catch (RecordNotFoundException $e) {
            // Rocket not found in systemsettings - do not replace $USERn$ macros
        }

        $mergedService['serviceCommandLine'] = $serviceCommandLine;

        // Convert interval values for humans
        $mergedService['checkIntervalHuman'] = $UserTime->secondsInHumanShort($mergedService['check_interval']);
        $mergedService['retryIntervalHuman'] = $UserTime->secondsInHumanShort($mergedService['retry_interval']);
        $mergedService['notificationIntervalHuman'] = $UserTime->secondsInHumanShort($mergedService['notification_interval']);

        //Check permissions for Contacts
        $contactsWithContainers = [];
        $writeContainers = $this->getWriteContainers();

        foreach ($mergedService['contacts'] as $key => $contact) {
            $contactsWithContainers[$contact['id']] = [];
            foreach ($contact['containers'] as $container) {
                $contactsWithContainers[$contact['id']][] = $container['id'];
            }

            $mergedService['contacts'][$key]['allowEdit'] = $this->hasRootPrivileges;
            if ($this->hasRootPrivileges === false) {
                if (!empty(array_intersect($contactsWithContainers[$contact['id']], $writeContainers))) {
                    $mergedService['contacts'][$key]['allowEdit'] = true;
                }
            }
        }

        //Load containers information
        $mainContainer = $ContainersTable->getTreePathForBrowser($hostEntity->get('container_id'), $this->MY_RIGHTS_LEVEL);
        //Add shared containers
        $sharedContainers = [];
        foreach ($hostEntity->getContainerIds() as $sharedContainerId) {
            if ($sharedContainerId != $hostEntity->get('container_id')) {
                $sharedContainers[$container['id']] = $ContainersTable->getTreePathForBrowser($sharedContainerId, $this->MY_RIGHTS_LEVEL);
            }
        }

        //Check permissions for Contact groups
        foreach ($mergedService['contactgroups'] as $key => $contactgroup) {
            $mergedService['contactgroups'][$key]['allowEdit'] = $this->isWritableContainer($contactgroup['container']['parent_id']);
        }

        //Load host and service status
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->problemHasBeenAcknowledged()
            ->scheduledDowntimeDepth()
            ->lastStateChange();


        $hoststatus = $HoststatusTable->byUuid($hostObj->getUuid(), $HoststatusFields);
        if (empty($hoststatus)) {
            //Empty host state for Hoststatus object
            $hoststatus = [
                'Hoststatus' => []
            ];
        }
        $Hoststatus = new Hoststatus($hoststatus['Hoststatus'], $UserTime);
        $hoststatus = $Hoststatus->toArrayForBrowser();


        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields->wildcard();

        $servicestatus = $ServicestatusTable->byUuid($service['uuid'], $ServicestatusFields);
        if (empty($servicestatus)) {
            //Empty host state for Servicestatus object
            $servicestatus = [
                'Servicestatus' => []
            ];
        }
        $Servicestatus = new Servicestatus($servicestatus['Servicestatus'], $UserTime);
        $servicestatus = $Servicestatus->toArrayForBrowser();

        //Parse BBCode in long output
        $BBCodeParser = new BBCodeParser();
        $servicestatus['outputHtml'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($Servicestatus->getOutput(), true));
        $servicestatus['longOutputHtml'] = $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($Servicestatus->getLongOutput(), true));

        $mergedService['allowEdit'] = $allowEdit;

        //Add parsed perfdata information
        if (Plugin::isLoaded('PrometheusModule') && $serviceObj->getServiceType() === PROMETHEUS_SERVICE) {
            // Query Prometheus to get all metrics
            $PrometheusPerfdataLoader = new \PrometheusModule\Lib\PrometheusPerfdataLoader();
            $mergedService['Perfdata'] = $PrometheusPerfdataLoader->getAvailableMetricsByService($serviceObj, false, true);
            $mergedService['has_graph'] = !empty($mergedService['Perfdata']); // Usually a prometheus service has always a graph
        } else {
            // "Normal" Naemon service (not a PROMETHEUS_SERVICE!)
            $PerfdataChecker = new PerfdataChecker(
                $hostObj,
                $serviceObj,
                $this->PerfdataBackend,
                $Servicestatus,
                $this->DbBackend,
                $mergedService['service_type']
            );
            $mergedService['has_graph'] = $PerfdataChecker->hasPerfdata();

            if (empty($Servicestatus->getPerfdata()) && $mergedService['has_graph'] === true && $this->PerfdataBackend->isWhisper()) {
                //Query graphite backend to get available metrics - used if perfdata string is empty for example on unknown state

                $mergedService['Perfdata'] = [];

                $GraphiteConfig = new GraphiteConfig();
                $GraphiteLoader = new GraphiteLoader($GraphiteConfig);
                $metrics = $GraphiteLoader->findMetricsByUuid($hostObj->getUuid(), $serviceObj->getUuid());

                foreach ($metrics as $metric) {
                    $mergedService['Perfdata'][$metric] = [
                        'current'  => null,
                        'unit'     => null,
                        'warning'  => null,
                        'critical' => null,
                        'min'      => null,
                        'max'      => null,
                        'metric'   => $metric // ITC-2824 make classical metrics look the same
                    ];
                }

            } else {
                //Parse perfdata string from database to get metrics - this is the default
                $PerfdataParser = new PerfdataParser($Servicestatus->getPerfdata());
                $mergedService['Perfdata'] = $PerfdataParser->parse();
            }
        }


        $systemsettingsEntity = $SystemsettingsTable->getSystemsettingByKey('TICKET_SYSTEM.URL');
        $ticketSystem = $systemsettingsEntity->get('value');

        //Check for service acknowledgements and downtimes
        $acknowledgement = [];
        if ($Servicestatus->isAcknowledged()) {
            $acknowledgement = $AcknowledgementServicesTable->byServiceUuid($serviceObj->getUuid());
            if (!empty($acknowledgement)) {
                $Acknowledgement = new AcknowledgementService($acknowledgement, $UserTime, $allowEdit);
                $acknowledgement = $Acknowledgement->toArray();

                $ticketDetails = [];
                if (!empty($ticketSystem) && preg_match('/^(Ticket)_?(\d+);?(\d+)/', $Acknowledgement->getCommentData(), $ticketDetails)) {
                    $commentDataHtml = $Acknowledgement->getCommentData();
                    if (isset($ticketDetails[1], $ticketDetails[3], $ticketDetails[2])) {
                        $commentDataHtml = sprintf(
                            '<a href="%s%s" target="_blank">%s %s</a>',
                            $ticketSystem,
                            $ticketDetails[3],
                            $ticketDetails[1],
                            $ticketDetails[2]
                        );
                    }
                } else {
                    $commentDataHtml = $BBCodeParser->asHtml($Acknowledgement->getCommentData(), true);
                }

                $acknowledgement['commentDataHtml'] = $commentDataHtml;
            }
        }

        $downtime = [];
        if ($Servicestatus->isInDowntime()) {
            $downtime = $DowntimehistoryServicesTable->byServiceUuid($serviceObj->getUuid(), true);
            if (!empty($downtime)) {
                $Downtime = new Downtime($downtime, $allowEdit, $UserTime);
                $downtime = $Downtime->toArray();
            }
        }

        //Check for host acknowledgements and downtimes
        $hostAcknowledgement = [];
        if ($Hoststatus->isAcknowledged()) {
            $hostAcknowledgement = $AcknowledgementHostsTable->byHostUuid($hostObj->getUuid());
            if (!empty($hostAcknowledgement)) {
                $AcknowledgementHost = new AcknowledgementHost($hostAcknowledgement, $UserTime, $allowEdit);
                $hostAcknowledgement = $AcknowledgementHost->toArray();

                $ticketDetails = [];
                if (!empty($ticketSystem) && preg_match('/^(Ticket)_?(\d+);?(\d+)/', $AcknowledgementHost->getCommentData(), $ticketDetails)) {
                    $commentDataHtml = $AcknowledgementHost->getCommentData();
                    if (isset($ticketDetails[1], $ticketDetails[3], $ticketDetails[2])) {
                        $commentDataHtml = sprintf(
                            '<a href="%s%s" target="_blank">%s %s</a>',
                            $ticketSystem,
                            $ticketDetails[3],
                            $ticketDetails[1],
                            $ticketDetails[2]
                        );
                    }
                } else {
                    $commentDataHtml = $BBCodeParser->asHtml($AcknowledgementHost->getCommentData(), true);
                }

                $hostAcknowledgement['commentDataHtml'] = $commentDataHtml;
            }
        }

        $hostDowntime = [];
        if ($Hoststatus->isInDowntime()) {
            $hostDowntime = $DowntimehistoryHostsTable->byHostUuid($hostObj->getUuid(), true);
            if (!empty($hostDowntime)) {
                $DowntimeHost = new Downtime($hostDowntime, $allowEdit, $UserTime);
                $hostDowntime = $DowntimeHost->toArray();
            }
        }

        $canSubmitExternalCommands = $this->hasPermission('externalcommands', 'services') && $this->hasPermission('submit', 'cmd', 'nagiosmodule');

        $typesForView = $ServicesTable->getServiceTypesWithStyles();
        $serviceType = $typesForView[$mergedService['service_type']];

        if ($canUserSeeCheckCommand === false) {
            $mergedService['serviceCommandLine'] = 'Removed due to insufficient permissions';
            $mergedService['servicecommandargumentvalues'] = 'Removed due to insufficient permissions';
            $checkCommand = 'Removed due to insufficient permissions';
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        //Check if the service is used by Instantreports
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        $objects['Instantreports'] = $InstantreportsTable->getInstantReportsByServiceId((int)$id, $MY_RIGHTS);

        //Check if the service is used by Autoreports
        if (Plugin::isLoaded('AutoreportModule')) {
            /** @var $AutoreportsTable AutoreportsTable */
            $AutoreportsTable = TableRegistry::getTableLocator()->get('AutoreportModule.Autoreports');
            $objects['Autoreports'] = $AutoreportsTable->getAutoReportsByServiceId((int)$id, $MY_RIGHTS);
        }

        //Check if the host is used by Eventcorrelations
        if (Plugin::isLoaded('EventcorrelationModule')) {
            /** @var EventcorrelationsTable $EventcorrelationsTable */
            $EventcorrelationsTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.Eventcorrelations');
            $objects['Eventcorrelations'] = $EventcorrelationsTable->getEventCorrelationsByServiceId((int)$id, $MY_RIGHTS);
        }

        //Check if the service is used by Maps
        if (Plugin::isLoaded('MapModule')) {
            /** @var $MapsTable MapsTable */
            $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');
            $objects['Maps'] = $MapsTable->getMapsByServiceId((int)$id, $MY_RIGHTS);
        }

        //Check if the host is used by Hostgroups
        /** @var $ServicegroupsTable ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');
        $objects['Servicegroups'] = $ServicegroupsTable->getServiceGroupsByServiceId((int)$id, $MY_RIGHTS);


        // Set data to fronend
        $this->set('mergedService', $mergedService);
        $this->set('serviceType', $serviceType);
        $this->set('host', ['Host' => $host]);
        $this->set('areContactsFromService', $ServiceMergerForView->areContactsFromService());
        $this->set('areContactsInheritedFromHosttemplate', $ServiceMergerForView->areContactsInheritedFromHosttemplate());
        $this->set('areContactsInheritedFromHost', $ServiceMergerForView->areContactsInheritedFromHost());
        $this->set('areContactsInheritedFromServicetemplate', $ServiceMergerForView->areContactsInheritedFromServicetemplate());
        $this->set('hoststatus', $hoststatus);
        $this->set('servicestatus', $servicestatus);
        $this->set('acknowledgement', $acknowledgement);
        $this->set('downtime', $downtime);
        $this->set('hostDowntime', $hostDowntime);
        $this->set('hostAcknowledgement', $hostAcknowledgement);
        $this->set('checkCommand', $checkCommand);
        $this->set('checkPeriod', $checkPeriod);
        $this->set('notifyPeriod', $notifyPeriod);
        $this->set('canSubmitExternalCommands', $canSubmitExternalCommands);
        $this->set('mainContainer', $mainContainer);
        $this->set('sharedContainers', $sharedContainers);
        $this->set('objects', $objects);
        $this->set('usageFlag', $serviceObj->getUsageFlag());
        $this->set('username', $User->getFullName());
        $this->set('blurryCommandLine', $blurryCommandLine);
        $this->set('masterInstanceName', $masterInstanceName);

        $this->viewBuilder()->setOption('serialize', [
            'mergedService',
            'serviceType',
            'host',
            'areContactsFromService',
            'areContactsInheritedFromHosttemplate',
            'areContactsInheritedFromHost',
            'areContactsInheritedFromServicetemplate',
            'hoststatus',
            'servicestatus',
            'acknowledgement',
            'downtime',
            'hostDowntime',
            'hostAcknowledgement',
            'checkCommand',
            'checkPeriod',
            'notifyPeriod',
            'canSubmitExternalCommands',
            'mainContainer',
            'sharedContainers',
            'objects',
            'usageFlag',
            'mapModule',
            'username',
            'blurryCommandLine',
            'masterInstanceName'
        ]);
    }

    /**
     * @param int|null $host_id
     */
    public function serviceList($host_id = null) {
        $User = new User($this->getUser());

        //Only ship HTML template
        $this->set('username', $User->getFullName());
        return;
    }

    public function listToPdf() {
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $ServiceFilter = new ServiceFilter($this->request);
        $User = new User($this->getUser());

        $ServiceControllerRequest = new ServiceControllerRequest($this->request, $ServiceFilter);
        $ServiceConditions = new ServiceConditions(
            $ServiceFilter->indexFilter()
        );
        $ServiceConditions->setContainerIds($this->MY_RIGHTS);

        if ($ServiceControllerRequest->isRequestFromBrowser() === false) {
            $ServiceConditions->setIncludeDisabled(false);
            $ServiceConditions->setContainerIds($this->MY_RIGHTS);
        }

        if ($ServiceControllerRequest->isRequestFromBrowser() === true) {
            $browserContainerIds = $ServiceControllerRequest->getBrowserContainerIdsByRequest();
            foreach ($browserContainerIds as $containerIdToCheck) {
                if (!in_array($containerIdToCheck, $this->MY_RIGHTS)) {
                    $this->render403();
                    return;
                }
            }

            $ServiceConditions->setIncludeDisabled(false);
            $ServiceConditions->setContainerIds($browserContainerIds);

            if ($User->isRecursiveBrowserEnabled()) {
                //get recursive container ids
                $containerIdToResolve = $browserContainerIds;
                $children = $ContainersTable->getChildren($containerIdToResolve[0]);
                $containerIds = Hash::extract($children, '{n}.id');
                $recursiveContainerIds = [];
                foreach ($containerIds as $containerId) {
                    if (in_array($containerId, $this->MY_RIGHTS)) {
                        $recursiveContainerIds[] = $containerId;
                    }
                }
                $ServiceConditions->setContainerIds(array_merge($ServiceConditions->getContainerIds(), $recursiveContainerIds));
            }
        }

        $ServiceConditions->setOrder($ServiceControllerRequest->getOrder([
            'Hosts.name'  => 'asc',
            'servicename' => 'asc'
        ]));


        if ($this->DbBackend->isNdoUtils()) {
            $services = $ServicesTable->getServiceIndex($ServiceConditions);
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->DbBackend->isStatusengine3()) {
            $services = $ServicesTable->getServiceIndexStatusengine3($ServiceConditions);
        }

        $HoststatusTable = $this->DbBackend->getHoststatusTable();

        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isFlapping()
            ->lastHardStateChange();
        $hoststatusCache = $HoststatusTable->byUuid(
            array_unique(Hash::extract($services, '{n}._matchingData.Hosts.uuid')),
            $HoststatusFields
        );

        $all_services = [];
        $UserTime = $User->getUserTime();
        foreach ($services as $service) {
            $Host = new Host($service['_matchingData']['Hosts']);
            if (isset($hoststatusCache[$Host->getUuid()]['Hoststatus'])) {
                $Hoststatus = new Hoststatus($hoststatusCache[$Host->getUuid()]['Hoststatus'], $UserTime);
            } else {
                $Hoststatus = new Hoststatus([], $UserTime);
            }
            $Service = new Service($service);
            $Servicestatus = new Servicestatus($service['Servicestatus'], $UserTime);

            $tmpRecord = [
                'Service'       => $Service->toArray(),
                'Host'          => $Host->toArray(),
                'Hoststatus'    => $Hoststatus,
                'Servicestatus' => $Servicestatus
            ];
            $all_services[] = $tmpRecord;
        }

        $this->set('User', $User);
        $this->set('all_services', $all_services);

        $this->viewBuilder()->setOption(
            'pdfConfig',
            [
                'download' => true,
                'filename' => __('Services_') . date('dmY_his') . '.pdf',
            ]
        );
    }

    public function listToCsv() {
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $ServiceFilter = new ServiceFilter($this->request);
        $User = new User($this->getUser());

        $ServiceControllerRequest = new ServiceControllerRequest($this->request, $ServiceFilter);
        $ServiceConditions = new ServiceConditions(
            $ServiceFilter->indexFilter()
        );
        $ServiceConditions->setContainerIds($this->MY_RIGHTS);

        if ($ServiceControllerRequest->isRequestFromBrowser() === false) {
            $ServiceConditions->setIncludeDisabled(false);
            $ServiceConditions->setContainerIds($this->MY_RIGHTS);
        }

        if ($ServiceControllerRequest->isRequestFromBrowser() === true) {
            $browserContainerIds = $ServiceControllerRequest->getBrowserContainerIdsByRequest();
            foreach ($browserContainerIds as $containerIdToCheck) {
                if (!in_array($containerIdToCheck, $this->MY_RIGHTS)) {
                    $this->render403();
                    return;
                }
            }

            $ServiceConditions->setIncludeDisabled(false);
            $ServiceConditions->setContainerIds($browserContainerIds);

            if ($User->isRecursiveBrowserEnabled()) {
                //get recursive container ids
                $containerIdToResolve = $browserContainerIds;
                $children = $ContainersTable->getChildren($containerIdToResolve[0]);
                $containerIds = Hash::extract($children, '{n}.id');
                $recursiveContainerIds = [];
                foreach ($containerIds as $containerId) {
                    if (in_array($containerId, $this->MY_RIGHTS)) {
                        $recursiveContainerIds[] = $containerId;
                    }
                }
                $ServiceConditions->setContainerIds(array_merge($ServiceConditions->getContainerIds(), $recursiveContainerIds));
            }
        }

        $ServiceConditions->setOrder($ServiceControllerRequest->getOrder([
            'Hosts.name'  => 'asc',
            'servicename' => 'asc'
        ]));


        if ($this->DbBackend->isNdoUtils()) {
            $services = $ServicesTable->getServiceIndex($ServiceConditions);
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->DbBackend->isStatusengine3()) {
            $services = $ServicesTable->getServiceIndexStatusengine3($ServiceConditions);
        }

        $all_services = [];
        $UserTime = $User->getUserTime();
        foreach ($services as $service) {
            $Host = new Host($service['_matchingData']['Hosts']);
            $Service = new Service($service);
            $Servicestatus = new Servicestatus($service['Servicestatus'], $UserTime);

            $tags = $Service->getTags();
            if (empty($tags)) {
                $tags = $service['_matchingData']['Servicetemplates']['tags'];
            }

            $all_services[] = [
                $Service->getServicename(),
                $Service->getId(),
                $Service->getUuid(),
                $Service->getDescription(),
                $tags,
                $service['_matchingData']['Servicetemplates']['id'],
                $service['_matchingData']['Servicetemplates']['name'],

                $Host->getHostname(),
                $Host->getId(),
                $Host->getUuid(),
                $Host->getAddress(),
                $Host->getSatelliteId(),

                $Servicestatus->currentState(),
                $Servicestatus->isAcknowledged() ? 1 : 0,
                $Servicestatus->isInDowntime() ? 1 : 0,
                $Servicestatus->getLastCheck(),
                $UserTime->format($Servicestatus->getLastCheck()),
                $Servicestatus->getNextCheck(),
                $UserTime->format($Servicestatus->getNextCheck()),
                $Servicestatus->isActiveChecksEnabled() ? 1 : 0,
                $Servicestatus->getOutput()
            ];
        }

        $header = [
            'service_name',
            'service_id',
            'service_uuid',
            'service_description',
            'service_tags',
            'servicetemplate_id',
            'servicetemplate_name',

            'host_name',
            'host_id',
            'host_uuid',
            'host_address',
            'satellite_id',

            'current_state',
            'problem_has_been_acknowledged',
            'in_downtime',
            'last_check',
            'last_check_formatted',
            'next_check',
            'next_check_formatted',
            'active_checks_enabled',
            'output'
        ];

        $this->set('data', $all_services);

        $filename = __('Services_') . date('dmY_his') . '.csv';
        $this->setResponse($this->getResponse()->withDownload($filename));
        $this->viewBuilder()
            ->setClassName('CsvView.Csv')
            ->setOptions([
                'delimiter' => ';', // Excel prefers ; over ,
                'bom'       => true, // Fix UTF-8 umlauts in Excel
                'serialize' => 'data',
                'header'    => $header,
            ]);
    }

    /**
     * For ACL only
     */
    public function checkcommand() {
        return;
    }

    /**
     * For ACL only
     */
    public function externalcommands() {
        return;
    }

    public function icon() {
        //Only ship HTML Template
        return;
    }

    public function servicecumulatedstatusicon() {
        //Only ship HTML Template
        return;
    }

    /**
     * Angular directive serviceStatusDetails
     */
    public function details() {
        //Only ship template for auto maps modal
        $User = new User($this->getUser());
        $this->set('username', $User->getFullName());
        return;
    }


    public function loadServicesByContainerId() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $containerId = $this->request->getQuery('containerId', 0);
        $selected = $this->request->getQuery('selected');
        $ServiceFilter = new ServiceFilter($this->request);
        $recursive = $this->request->getQuery('recursive', false) === 'true';
        $containerIds = [ROOT_CONTAINER, $containerId];

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($recursive === false) {
            if ($containerId == ROOT_CONTAINER) {
                //Don't panic! Only root users can edit /root objects ;)
                //So no loss of selected hosts/host templates
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
            }
        } else {
            //Also include child containers
            if ($containerId != ROOT_CONTAINER) {
                $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, false);
            }
        }

        $ServiceCondition = new ServiceConditions($ServiceFilter->indexFilter());
        $ServiceCondition->setContainerIds($containerIds);
        $ServiceCondition->setIncludeDisabled(false);

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $services = Api::makeItJavaScriptAble(
            $ServicesTable->getServicesForAngular($ServiceCondition, $selected)
        );

        $this->set('services', $services);
        $this->viewBuilder()->setOption('serialize', ['services']);
    }


    public function loadServicesByString() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $selected = $this->request->getQuery('selected');
        $includeDisabled = $this->request->getQuery('includeDisabled') === 'true';

        $ServiceFilter = new ServiceFilter($this->request);

        $ServiceCondition = new ServiceConditions($ServiceFilter->indexFilter());
        $ServiceCondition->setIncludeDisabled($includeDisabled);
        $ServiceCondition->setContainerIds($this->MY_RIGHTS);
        $ServiceCondition->includeDisabled();

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $services = Api::makeItJavaScriptAble(
            $ServicesTable->getServicesForAngular($ServiceCondition, $selected)
        );


        $this->set('services', $services);
        $this->viewBuilder()->setOption('serialize', ['services']);
    }

    /**
     * @param int|null $id
     * @throws MissingDbBackendException
     */
    public function timeline($id = null) {
        $session = $this->request->getSession();
        $session->close();

        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Service not found'));
        }

        $service = $ServicesTable->getServiceByIdForTimeline($id);

        if (!$this->allowedByContainerId($service->getContainerIds(), false)) {
            $this->render403();
            return;
        }

        $timeperiodId = $service->get('check_period_id');
        if ($timeperiodId === null || $timeperiodId === '') {
            $timeperiodId = $service->get('servicetemplate')->get('check_period_id');
        }

        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        $checkTimePeriod = $TimeperiodsTable->getTimeperiodWithTimerangesById($timeperiodId);

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();
        $offset = $UserTime->getUserTimeToServerOffset();

        $Groups = new Groups();
        $this->set('groups', $Groups->serialize(false));

        $start = $this->request->getQuery('start', -1);
        $end = $this->request->getQuery('end', -1);

        if ($start > 0) {
            $start -= $offset;
        }
        if ($end > 0) {
            $end -= $offset;
        }

        if (!is_numeric($start) || $start < 0) {
            $start = time() - 2 * 24 * 3600;
        }


        if (!is_numeric($end) || $end < 0) {
            $end = time();
        }

        /*************  TIME RANGES *************/
        $timeRanges = DaterangesCreator::createDateRanges(
            $start,
            $end,
            $checkTimePeriod['Timeperiod']['timeperiod_timeranges']
        );

        $TimeRangeSerializer = new TimeRangeSerializer($timeRanges, $UserTime);
        $this->set('timeranges', $TimeRangeSerializer->serialize());
        unset($TimeRangeSerializer, $timeRanges);

        $hostUuid = $service->get('host')->get('uuid');
        $serviceUuid = $service->get('uuid');

        /*************  HOST STATEHISTORY *************/
        $StatehistoryHostsTable = $this->DbBackend->getStatehistoryHostsTable();
        $HoststatusTable = $this->DbBackend->getHoststatusTable();

        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->lastStateChange()
            ->lastHardStateChange();

        $hoststatus = $HoststatusTable->byUuid($hostUuid, $HoststatusFields);

        //Process conditions
        $Conditions = new StatehistoryHostConditions();
        $Conditions->setOrder(['StatehistoryHosts.state_time' => 'asc']);

        $Conditions->setFrom($start);
        $Conditions->setTo($end);
        $Conditions->setHostUuid($hostUuid);
        $Conditions->setUseLimit(false);

        //Query state history records for hosts
        /** @var \Statusengine2Module\Model\Entity\StatehistoryHost[] $statehistoriesHost */
        $statehistories = $StatehistoryHostsTable->getStatehistoryIndex($Conditions);

        $statehistoryRecords = [];

        //Get last available state history record for this host
        $record = $StatehistoryHostsTable->getLastRecord($Conditions);
        if (!empty($record)) {
            if (!empty($hoststatus)) {
                $Hoststatus = new Hoststatus($hoststatus['Hoststatus']);
                $hostStatusLastStateChange = $Hoststatus->getLastStateChange();
                if ($record->get('state_time') < $hostStatusLastStateChange && $hostStatusLastStateChange <= $start) {
                    $stateHistoryHostTmp = [
                        'StatehistoryHost' => [
                            'state_time'      => $start,
                            'state'           => $Hoststatus->currentState(),
                            'last_state'      => $Hoststatus->currentState(),
                            'last_hard_state' => $Hoststatus->getLastHardState(),
                            'state_type'      => (int)$Hoststatus->isHardState()
                        ]
                    ];

                    $StatehistoryHost = new StatehistoryHost($stateHistoryHostTmp['StatehistoryHost']);
                    $statehistoryRecords[] = $StatehistoryHost;
                } else {
                    $record->set('state_time', $start);
                    $StatehistoryHost = new StatehistoryHost($record->toArray());
                    $statehistoryRecords[] = $StatehistoryHost;
                }
            }
        }

        //Host has no state history record for selected time range
        if (empty($statehistoryRecords)) {
            if (!empty($hoststatus)) {
                $isHardstate = false;
                if (isset($hoststatus['Hoststatus']['state_type'])) {
                    $isHardstate = ($hoststatus['Hoststatus']['state_type']) ? true : false;
                }

                if (isset($hoststatus['Hoststatus']['is_hardstate'])) {
                    $isHardstate = ($hoststatus['Hoststatus']['is_hardstate']) ? true : false;
                }

                $record = [
                    'state_time' => $hoststatus['Hoststatus']['last_state_change'],
                    'state'      => $hoststatus['Hoststatus']['current_state'],
                    'state_type' => $isHardstate,
                ];
                $StatehistoryHost = new StatehistoryHost($record);
                $statehistoryRecords[] = $StatehistoryHost;
            }
        }

        foreach ($statehistories as $statehistory) {
            $StatehistoryHost = new StatehistoryHost($statehistory);
            $statehistoryRecords[] = $StatehistoryHost;
        }

        $StatehistorySerializer = new StatehistorySerializer($statehistoryRecords, $UserTime, $end, 'host');
        $this->set('statehistory', $StatehistorySerializer->serialize());
        unset($StatehistorySerializer, $statehistoryRecords);

        /*************  SERVICE STATEHISTORY *************/
        $StatehistoryServicesTable = $this->DbBackend->getStatehistoryServicesTable();
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();

        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields
            ->currentState()
            ->isHardstate()
            ->lastStateChange()
            ->lastHardStateChange();

        $servicestatus = $ServicestatusTable->byUuid($service->get('uuid'), $ServicestatusFields);


        //Process conditions
        $StatehistoryServiceConditions = new StatehistoryServiceConditions();
        $StatehistoryServiceConditions->setOrder(['StatehistoryServices.state_time' => 'asc']);
        $StatehistoryServiceConditions->setFrom($start);
        $StatehistoryServiceConditions->setTo($end);
        $StatehistoryServiceConditions->setServiceUuid($serviceUuid);
        $StatehistoryServiceConditions->setUseLimit(false);
        //Query state history records for service
        $statehistoriesService = $StatehistoryServicesTable->getStatehistoryIndex($StatehistoryServiceConditions);
        $statehistoryServiceRecords = [];

        //Get last available state history record for this service
        $record = $StatehistoryServicesTable->getLastRecord($StatehistoryServiceConditions);
        if (!empty($record)) {
            if (!empty($servicestatus)) {
                $Servicestatus = new Servicestatus($servicestatus['Servicestatus']);
                $serviceStatusLastStateChange = $Servicestatus->getLastStateChange();
                if ($record->get('state_time') < $serviceStatusLastStateChange && $serviceStatusLastStateChange <= $start) {
                    $stateHistoryServiceTmp = [
                        'StatehistoryService' => [
                            'state_time'      => $start,
                            'state'           => $Servicestatus->currentState(),
                            'last_state'      => $Servicestatus->currentState(),
                            'last_hard_state' => $Servicestatus->getLastHardState(),
                            'state_type'      => (int)$Servicestatus->isHardState()
                        ]
                    ];

                    $StatehistoryService = new StatehistoryService($stateHistoryServiceTmp['StatehistoryService']);
                    $statehistoryServiceRecords[] = $StatehistoryService;
                } else {
                    $record->set('state_time', $start);
                    $StatehistoryService = new StatehistoryService($record->toArray());
                    $statehistoryServiceRecords[] = $StatehistoryService;
                }
            }
        }
        //Service has no state history record for selected time range
        if (empty($statehistoryServiceRecords)) {
            if (!empty($servicestatus)) {
                $isHardstate = false;
                if (isset($servicestatus['Servicestatus']['state_type'])) {
                    $isHardstate = ($servicestatus['Servicestatus']['state_type']) ? true : false;
                }

                if (isset($servicestatus['Servicestatus']['is_hardstate'])) {
                    $isHardstate = ($servicestatus['Servicestatus']['is_hardstate']) ? true : false;
                }

                $record = [
                    'state_time' => $servicestatus['Servicestatus']['last_state_change'],
                    'state'      => $servicestatus['Servicestatus']['current_state'],
                    'state_type' => $isHardstate
                ];

                $StatehistoryService = new StatehistoryService($record);
                $statehistoryServiceRecords[] = $StatehistoryService;
            }
        }

        foreach ($statehistoriesService as $statehistoryService) {
            $StatehistoryService = new StatehistoryService($statehistoryService);
            $statehistoryServiceRecords[] = $StatehistoryService;
        }


        $StatehistorySerializer = new StatehistorySerializer($statehistoryServiceRecords, $UserTime, $end, 'service');
        $this->set('servicestatehistory', $StatehistorySerializer->serialize());
        unset($StatehistorySerializer, $statehistoryServiceRecords);

        /*************  SERVICE DOWNTIMES *************/
        $DowntimehistoryServicesTable = $this->DbBackend->getDowntimehistoryServicesTable();

        //Query downtime records for hosts
        $DowntimeServiceConditions = new DowntimeServiceConditions();
        $DowntimeServiceConditions->setOrder(['DowntimeServices.scheduled_start_time' => 'asc']);
        $DowntimeServiceConditions->setFrom($start);
        $DowntimeServiceConditions->setTo($end);
        $DowntimeServiceConditions->setServiceUuid($serviceUuid);
        $DowntimeServiceConditions->setIncludeCancelledDowntimes(true);

        $downtimes = $DowntimehistoryServicesTable->getDowntimesForReporting($DowntimeServiceConditions);
        $downtimeRecords = [];
        foreach ($downtimes as $downtime) {
            $downtimeRecords[] = new Downtime($downtime);
        }

        $DowntimeSerializer = new DowntimeSerializer($downtimeRecords, $UserTime);
        $this->set('downtimes', $DowntimeSerializer->serialize());
        unset($DowntimeSerializer, $downtimeRecords);

        /*************  SERVICE NOTIFICATIONS *************/
        $NotificationServicesTable = $this->DbBackend->getNotificationServicesTable();

        $Conditions = new ServiceNotificationConditions();
        $Conditions->setUseLimit(false);
        $Conditions->setFrom($start);
        $Conditions->setTo($end);
        $Conditions->setServiceUuid($serviceUuid);

        $notifications = $NotificationServicesTable->getNotifications($Conditions);
        $notificationRecords = [];
        foreach ($notifications as $notification) {
            $notification = $notification->toArray();

            $notificationRecords[] = [
                'NotificationService' => new NotificationService($notification),
                'Command'             => new Command($notification['Commands']),
                'Contact'             => new Contact($notification['Contacts'])
            ];
        }

        $NotificationSerializer = new NotificationSerializer($notificationRecords, $UserTime, 'service');
        $this->set('notifications', $NotificationSerializer->serialize());
        unset($NotificationSerializer, $notificationRecords);

        /*************  SERVICE ACKNOWLEDGEMENTS *************/
        $AcknowledgementServicesTable = $this->DbBackend->getAcknowledgementServicesTable();

        //Process conditions
        $Conditions = new AcknowledgedServiceConditions();
        $Conditions->setUseLimit(false);
        $Conditions->setFrom($start);
        $Conditions->setTo($end);
        $Conditions->setServiceUuid($serviceUuid);

        $acknowledgementRecords = [];
        $acknowledgements = $AcknowledgementServicesTable->getAcknowledgements($Conditions);

        foreach ($acknowledgements as $acknowledgement) {
            $acknowledgementRecords[] = new AcknowledgementService($acknowledgement->toArray());
        }

        $AcknowledgementSerializer = new AcknowledgementSerializer($acknowledgementRecords, $UserTime);
        $this->set('acknowledgements', $AcknowledgementSerializer->serialize());

        $start += $offset;
        $end += $offset;

        $this->set('start', $start);
        $this->set('end', $end);
        $this->viewBuilder()->setOption('serialize', [
            'start',
            'end',
            'groups',
            'statehistory',
            'servicestatehistory',
            'downtimes',
            'notifications',
            'acknowledgements',
            'timeranges'
        ]);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/


    /**
     * @param int $hostId
     * @param int $serviceId
     * @throws \Exception
     */
    public function loadElementsByHostId($hostId, $serviceId = 0) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $hostId = (int)$hostId;
        $serviceId = (int)$serviceId;

        $servicetemplateType = GENERIC_SERVICE;

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $ServicegroupsTable ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Invalid host'));
        }

        $containerId = $HostsTable->getHostPrimaryContainerIdByHostId($hostId);
        $isSlaHost = $HostsTable->isHostInSla($hostId);

        if ($serviceId != 0) {
            try {
                $service = $ServicesTable->get($serviceId);
                $servicetemplateType = $service->get('service_type');
            } catch (RecordNotFoundException $e) {
                //Ignore error
            }
        }

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);


        $servicetemplates = $ServicetemplatesTable->getServicetemplatesByContainerId($containerIds, 'list', $servicetemplateType);
        $servicetemplates = Api::makeItJavaScriptAble($servicetemplates);

        $servicegroups = $ServicegroupsTable->getServicegroupsByContainerId($containerIds, 'list', 'id');
        $servicegroups = Api::makeItJavaScriptAble($servicegroups);

        $timeperiods = $TimeperiodsTable->timeperiodsByContainerId($containerIds, 'list');
        $timeperiods = Api::makeItJavaScriptAble($timeperiods);
        $checkperiods = $timeperiods;

        $contacts = $ContactsTable->contactsByContainerId($containerIds, 'list');
        $contacts = Api::makeItJavaScriptAble($contacts);

        $contactgroups = $ContactgroupsTable->getContactgroupsByContainerId($containerIds, 'list', 'id');
        $contactgroups = Api::makeItJavaScriptAble($contactgroups);

        $existingServices = $ServicesTable->getListOfServiceNamesForUniqueCheck($hostId);

        $this->set('servicetemplates', $servicetemplates);
        $this->set('servicegroups', $servicegroups);
        $this->set('timeperiods', $timeperiods);
        $this->set('checkperiods', $checkperiods);
        $this->set('contacts', $contacts);
        $this->set('contactgroups', $contactgroups);
        $this->set('existingServices', $existingServices);
        $this->set('isSlaHost', $isSlaHost);

        $this->viewBuilder()->setOption('serialize', [
            'servicetemplates',
            'servicegroups',
            'timeperiods',
            'checkperiods',
            'contacts',
            'contactgroups',
            'existingServices',
            'isSlaHost'
        ]);
    }

    /**
     * @param int $servicetemplateId
     * @param int|null $hostId
     */
    public function loadServicetemplate($servicetemplateId, $hostId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        if (!$ServicetemplatesTable->existsById($servicetemplateId)) {
            throw new NotFoundException(__('Invalid service template'));
        }

        $servicetemplate = $ServicetemplatesTable->getServicetemplateForEdit($servicetemplateId);
        $toJson = ['servicetemplate'];


        if ($hostId !== null) {
            //We are in /services/add

            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            /** @var $HosttemplatesTable HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
            if ($HostsTable->existsById($hostId)) {
                $host = $HostsTable->get($hostId);

                //We are in services/add
                //There is no service jet, so no contacts
                $serviceContacts = [
                    'contacts'      => ['_ids' => []],
                    'contactgroups' => ['_ids' => []]
                ];

                $servicetemplateContactsAndContactgroups = $ServicetemplatesTable->getContactsAndContactgroupsById($servicetemplateId);
                $hostContactsAndContactgroups = $HostsTable->getContactsAndContactgroupsById($hostId);
                $hosttemplateContactsAndContactgroups = $HosttemplatesTable->getContactsAndContactgroupsById($host->get('hosttemplate_id'));

                $ServiceMergerForView = new ServiceMergerForView(
                    ['Service' => $serviceContacts],
                    ['Servicetemplate' => $servicetemplateContactsAndContactgroups],
                    $hostContactsAndContactgroups,
                    $hosttemplateContactsAndContactgroups
                );

                $contactsAndContactgroups = $ServiceMergerForView->getDataForContactsAndContactgroups();

                $this->set('contactsAndContactgroups', $contactsAndContactgroups);
                $this->set('hostContactsAndContactgroups', $hostContactsAndContactgroups);
                $this->set('hosttemplateContactsAndContactgroups', $hosttemplateContactsAndContactgroups);
                $this->set('servicetemplateContactsAndContactgroups', $servicetemplateContactsAndContactgroups);
                $this->set('areContactsInheritedFromHosttemplate', $ServiceMergerForView->areContactsInheritedFromHosttemplate());
                $this->set('areContactsInheritedFromHost', $ServiceMergerForView->areContactsInheritedFromHost());
                $this->set('areContactsInheritedFromServicetemplate', $ServiceMergerForView->areContactsInheritedFromServicetemplate());
                $toJson[] = 'contactsAndContactgroups';
                $toJson[] = 'hostContactsAndContactgroups';
                $toJson[] = 'hosttemplateContactsAndContactgroups';
                $toJson[] = 'servicetemplateContactsAndContactgroups';
                $toJson[] = 'areContactsInheritedFromHosttemplate';
                $toJson[] = 'areContactsInheritedFromHost';
                $toJson[] = 'areContactsInheritedFromServicetemplate';
            }
        }


        $this->set('servicetemplate', $servicetemplate);
        $this->viewBuilder()->setOption('serialize', $toJson);
    }

    public function loadCommands() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        $commands = $CommandsTable->getCommandByTypeAsList(CHECK_COMMAND);

        $eventhandlerCommands = [
            0 => __('None')
        ];

        //Use foreach because of array_merge remove the keys and adding None after getCommandByTypeAsList()
        //will display "None" as the last element in the select box
        foreach ($CommandsTable->getCommandByTypeAsList(EVENTHANDLER_COMMAND) as $eventhandlerCommndId => $eventhandlerCommandName) {
            $eventhandlerCommands[$eventhandlerCommndId] = $eventhandlerCommandName;
        }

        $this->set('commands', Api::makeItJavaScriptAble($commands));
        $this->set('eventhandlerCommands', Api::makeItJavaScriptAble($eventhandlerCommands));
        $this->viewBuilder()->setOption('serialize', ['commands', 'eventhandlerCommands']);
    }

    /**
     * @param int|null $commandId
     * @param int|null $serviceId
     */
    public function loadCommandArguments($commandId = null, $serviceId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $CommandargumentsTable CommandargumentsTable */
        $CommandargumentsTable = TableRegistry::getTableLocator()->get('Commandarguments');

        if (!$CommandsTable->existsById($commandId)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $servicecommandargumentvalues = [];

        if ($serviceId != null) {
            //User passed an serviceId, so we are in a non add mode!
            //Check if the service has defined command arguments

            /** @var $ServicecommandargumentvaluesTable ServicecommandargumentvaluesTable */
            $ServicecommandargumentvaluesTable = TableRegistry::getTableLocator()->get('Servicecommandargumentvalues');

            $serviceCommandArgumentValues = $ServicecommandargumentvaluesTable->getByServiceIdAndCommandId($serviceId, $commandId);

            foreach ($serviceCommandArgumentValues as $serviceCommandArgumentValue) {
                $servicecommandargumentvalues[] = [
                    'commandargument_id' => $serviceCommandArgumentValue['commandargument_id'],
                    'service_id'         => $serviceCommandArgumentValue['service_id'],
                    'value'              => $serviceCommandArgumentValue['value'],
                    'commandargument'    => [
                        'name'       => $serviceCommandArgumentValue['commandargument']['name'],
                        'human_name' => $serviceCommandArgumentValue['commandargument']['human_name'],
                        'command_id' => $serviceCommandArgumentValue['commandargument']['command_id'],
                    ]
                ];
            }
        }

        //Get command arguments
        $commandarguments = $CommandargumentsTable->getByCommandId($commandId);
        if (empty($servicecommandargumentvalues)) {
            //Service has no command arguments defined
            //Or we are in services/add ?

            //Load command arguments of the check command
            foreach ($commandarguments as $commandargument) {
                $servicecommandargumentvalues[] = [
                    'commandargument_id' => $commandargument['Commandargument']['id'],
                    'value'              => '',
                    'commandargument'    => [
                        'name'       => $commandargument['Commandargument']['name'],
                        'human_name' => $commandargument['Commandargument']['human_name'],
                        'command_id' => $commandargument['Commandargument']['command_id'],
                    ]
                ];
            }
        }

        // Merge new command arguments that are missing in the service to service command arguments
        // and remove old command arguments that don't exists in the command anymore.
        $filteredCommandArgumentsValules = [];
        foreach ($commandarguments as $commandargument) {
            $valueExists = false;
            foreach ($servicecommandargumentvalues as $servicecommandargumentvalue) {
                if ($commandargument['Commandargument']['id'] === $servicecommandargumentvalue['commandargument_id']) {
                    $filteredCommandArgumentsValules[] = $servicecommandargumentvalue;
                    $valueExists = true;
                }
            }
            if (!$valueExists) {
                $filteredCommandArgumentsValules[] = [
                    'commandargument_id' => $commandargument['Commandargument']['id'],
                    'value'              => '',
                    'commandargument'    => [
                        'name'       => $commandargument['Commandargument']['name'],
                        'human_name' => $commandargument['Commandargument']['human_name'],
                        'command_id' => $commandargument['Commandargument']['command_id'],
                    ]
                ];
            }
        }
        $servicecommandargumentvalues = $filteredCommandArgumentsValules;
        $servicecommandargumentvalues = Hash::sort(
            $servicecommandargumentvalues,
            '{n}.commandargument.name',
            'asc',
            'natural'
        );

        $this->set('servicecommandargumentvalues', $servicecommandargumentvalues);
        $this->viewBuilder()->setOption('serialize', ['servicecommandargumentvalues']);
    }

    /**
     * @param int|null $commandId
     * @param int|null $serviceId
     */
    public function loadEventhandlerCommandArguments($commandId = null, $serviceId = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $CommandargumentsTable CommandargumentsTable */
        $CommandargumentsTable = TableRegistry::getTableLocator()->get('Commandarguments');


        if (!$CommandsTable->existsById($commandId)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $serviceeventhandlercommandargumentvalues = [];

        if ($serviceId != null) {
            //User passed an serviceId, so we are in a non add mode!
            //Check if the service has defined command arguments for the event handler

            /** @var $ServiceeventcommandargumentvaluesTable ServiceeventcommandargumentvaluesTable */
            $ServiceeventcommandargumentvaluesTable = TableRegistry::getTableLocator()->get('Serviceeventcommandargumentvalues');

            $serviceEventhandlerCommandArgumentValues = $ServiceeventcommandargumentvaluesTable->getByServiceIdAndCommandId($serviceId, $commandId);

            foreach ($serviceEventhandlerCommandArgumentValues as $serviceEventhandlerCommandArgumentValue) {
                $serviceeventhandlercommandargumentvalues[] = [
                    'commandargument_id' => $serviceEventhandlerCommandArgumentValue['commandargument_id'],
                    'service_id'         => $serviceEventhandlerCommandArgumentValue['service_id'],
                    'value'              => $serviceEventhandlerCommandArgumentValue['value'],
                    'commandargument'    => [
                        'name'       => $serviceEventhandlerCommandArgumentValue['commandargument']['name'],
                        'human_name' => $serviceEventhandlerCommandArgumentValue['commandargument']['human_name'],
                        'command_id' => $serviceEventhandlerCommandArgumentValue['commandargument']['command_id'],
                    ]
                ];
            }
        }

        //Get command arguments
        if (empty($serviceeventhandlercommandargumentvalues)) {
            //Service has no command arguments defined
            //Or we are in services/add ?

            //Load event handler command arguments of the check command
            foreach ($CommandargumentsTable->getByCommandId($commandId) as $commandargument) {
                $serviceeventhandlercommandargumentvalues[] = [
                    'commandargument_id' => $commandargument['Commandargument']['id'],
                    'value'              => '',
                    'commandargument'    => [
                        'name'       => $commandargument['Commandargument']['name'],
                        'human_name' => $commandargument['Commandargument']['human_name'],
                        'command_id' => $commandargument['Commandargument']['command_id'],
                    ]
                ];
            }
        }

        $this->set('serviceeventhandlercommandargumentvalues', $serviceeventhandlercommandargumentvalues);
        $this->viewBuilder()->setOption('serialize', ['serviceeventhandlercommandargumentvalues']);
    }

    public function loadServicesByStringCake4($onlyHostsWithWritePermission = false) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $selected = $this->request->getQuery('selected');
        $containerId = $this->request->getQuery('containerId');
        $resolveContainerIds = $this->request->getQuery('resolveContainerIds', false);

        if (empty($containerId)) {
            $containerId = $this->MY_RIGHTS;
        }

        if (!$this->allowedByContainerId($containerId, false)) {
            $this->render403();
            return;
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        if ($containerId !== ROOT_CONTAINER && $resolveContainerIds) {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, true);
            $containerIds = array_merge($containerIds, [ROOT_CONTAINER, $containerId]);
        }

        $ServicesFilter = new ServiceFilter($this->request);

        $ServiceConditions = new ServiceConditions($ServicesFilter->indexFilter());

        if ($onlyHostsWithWritePermission) {
            $writeContainers = [];
            foreach ($containerIds as $index => $containerId) {
                if (isset($this->MY_RIGHTS_LEVEL[$containerId])) {
                    if ($this->MY_RIGHTS_LEVEL[$containerId] === WRITE_RIGHT) {
                        $writeContainers[] = $containerId;
                    }
                }
            }
            $containerIds = $writeContainers;
        }

        $ServiceConditions->setContainerIds($containerIds);

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $services = Api::makeItJavaScriptAble(
            $ServicesTable->getServicesForAngularCake4($ServiceConditions, $selected, true)
        );

        $this->set('services', $services);
        $this->viewBuilder()->setOption('serialize', ['services']);
    }

    public function loadServicesByStringForOptionGroup($onlyHostsWithWritePermission = false) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $selected = $this->request->getQuery('selected');
        $containerId = $this->request->getQuery('containerId');
        $resolveContainerIds = $this->request->getQuery('resolveContainerIds', false);

        if (empty($containerId)) {
            $containerId = $this->MY_RIGHTS;
        }

        if (!$this->allowedByContainerId($containerId, false)) {
            $this->render403();
            return;
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        if ($containerId !== ROOT_CONTAINER && $resolveContainerIds) {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId, true);
            $containerIds = array_merge($containerIds, [ROOT_CONTAINER, $containerId]);
        }

        $ServicesFilter = new ServiceFilter($this->request);

        $ServiceConditions = new ServiceConditions($ServicesFilter->indexFilter());

        if ($onlyHostsWithWritePermission) {
            $writeContainers = [];
            foreach ($containerIds as $index => $containerId) {
                if (isset($this->MY_RIGHTS_LEVEL[$containerId])) {
                    if ($this->MY_RIGHTS_LEVEL[$containerId] === WRITE_RIGHT) {
                        $writeContainers[] = $containerId;
                    }
                }
            }
            $containerIds = $writeContainers;
        }

        $ServiceConditions->setContainerIds($containerIds);

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $services = $ServicesTable->getServicesForAngularCake4($ServiceConditions, $selected, true);
        $reorderServicesArray = [];
        foreach ($services as $service) {
            if (!isset($reorderServicesArray[$service['_matchingData']['Hosts']['name']])) {
                $reorderServicesArray[$service['_matchingData']['Hosts']['name']] = [
                    'label' => $service['_matchingData']['Hosts']['name'],
                    'value' => $service['_matchingData']['Hosts']['id'],
                    'items' => []
                ];
            }
            $serviceName = $service['servicename'];
            if ($service['disabled'] == 1) {
                $serviceName .= '🔌';
            }
            $reorderServicesArray[$service['_matchingData']['Hosts']['name']]['items'][] = [
                'label'    => $serviceName,
                'value'    => $service['id'],
                'disabled' => ($service['disabled'] == 1)  // if needed can be configured by parameter
            ];
        }


        $reorderServicesArray = Hash::sort($reorderServicesArray, '{s}', 'asc', 'natural');
        $services = array_values($reorderServicesArray);

        $this->set('services', $services);
        $this->viewBuilder()->setOption('serialize', ['services']);
    }

    /**
     * @return void
     * @deprecated
     */
    public function loadServicesByContainerIdCake4() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            // ITC-2124
            $selected = $this->request->getData('selected');
            $containerId = $this->request->getData('containerId');
        } else {
            // Keep the API stable for GET
            $selected = $this->request->getQuery('selected');
            $containerId = $this->request->getQuery('containerId');
        }

        $ServiceFilter = new ServiceFilter($this->request);

        $containerIds = [ROOT_CONTAINER, $containerId];

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        if ($containerId == ROOT_CONTAINER) {
            //Don't panic! Only root users can edit /root objects ;)
            //So no loss of selected hosts/host templates
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(ROOT_CONTAINER, true);
        }

        $ServiceCondition = new ServiceConditions($ServiceFilter->indexFilter());
        $ServiceCondition->setContainerIds($containerIds);
        $ServiceCondition->setIncludeDisabled(false);


        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $services = Api::makeItJavaScriptAble(
            $ServicesTable->getServicesForAngularCake4($ServiceCondition, $selected)
        );

        $this->set('services', $services);
        $this->viewBuilder()->setOption('serialize', ['services']);
    }

    public function loadCustomalerts() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $id = $this->request->getQuery('id');

        $customalertsExists = false;

        if (Plugin::isLoaded('CustomalertModule')) {
            /** @var CustomalertsTable $CustomalertsTable */
            $CustomalertsTable = TableRegistry::getTableLocator()->get('CustomalertModule.Customalerts');
            $customalertsExists = $CustomalertsTable->existsCustomalertsByServiceId($id);
        }

        $this->set('CustomalertsExists', $customalertsExists);
        $this->viewBuilder()->setOption('serialize', ['CustomalertsExists']);
    }

    public function loadSlaInformation() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $id = $this->request->getQuery('id');

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        $service = $ServicesTable->get($id, [
            'contain' => [
                'Hosts',
                'Servicetemplates'
            ]
        ]);
        if (!$HostsTable->existsById($service->get('host_id'))) {
            throw new NotFoundException(__('Invalid host'));
        }

        $host = $HostsTable->getHostForSlaBrowserTabWithHosttemplateById($service->get('host_id'));
        if (!$this->allowedByContainerId($host['hosts_to_containers_sharing']['_ids'], false)) {
            $this->render403();
            return;
        }
        $slaOverview = false;

        if (Plugin::isLoaded('SLAModule')) {
            /** @var SlasTable $SlasTable */
            $SlasTable = TableRegistry::getTableLocator()->get('SLAModule.Slas');
            $hostSlaId = $host['sla_id'] ?? $host['hosttemplate']['sla_id'];
            if (!empty($hostSlaId)) {
                if (!$SlasTable->existsById($hostSlaId)) {
                    throw new NotFoundException(__('Invalid sla'));
                }

                $isSlaRelevant = $service->sla_relevant;
                if (is_null($isSlaRelevant)) {
                    $isSlaRelevant = $service->servicetemplate->sla_relevant;
                }
                if ($isSlaRelevant == 0) {

                    $slaOverview = [
                        'state'          => 'not_sla_relevant',
                        'evaluation_end' => time()
                    ];
                    $this->set('slaOverview', $slaOverview);
                    $this->viewBuilder()->setOption('serialize', ['slaOverview']);
                    return;
                }

                $SlaInformation = $SlasTable->getSlaStatusInformationByServiceIdAndSlaId($id, $hostSlaId);
                $slaOverview = [
                    'state'          => 'not_available',
                    'evaluation_end' => time()
                ];

                $currentlyAvailabilityService = null;
                $serviceSlaStatusData = null;
                if (!empty($SlaInformation['sla_availability_status_services'][0])) {
                    $serviceSlaStatusData = $SlaInformation['sla_availability_status_services'][0];
                    $currentlyAvailabilityService = $serviceSlaStatusData['determined_availability_percent'];
                }

                if ($currentlyAvailabilityService !== null) {
                    $slaOverview = [
                        'evaluation_end'                  => $serviceSlaStatusData['evaluation_end'],
                        'determined_availability_percent' => $currentlyAvailabilityService,
                        'warning_threshold'               => $SlaInformation['warning_threshold'],
                        'minimal_availability'            => $SlaInformation['minimal_availability']
                    ];
                    if ($currentlyAvailabilityService < $SlaInformation['minimal_availability']) {
                        $state = 'danger';
                    } else if (!empty($SlaInformation['warning_threshold']) && $SlaInformation['warning_threshold'] > $currentlyAvailabilityService) {
                        $state = 'warning';
                    } else {
                        $state = 'success';
                    }
                    $slaOverview['state'] = $state;
                }

            }

        }
        $this->set('slaOverview', $slaOverview);
        $this->viewBuilder()->setOption('serialize', ['slaOverview']);
    }

    /**
     * @param $id
     * @return void
     */
    public function usedBy($id = null): void {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        if (!$ServicesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid service'));
        }

        $service = $ServicesTable->getServiceById($id);
        $Service = new Service($service->toArray());

        $service['name'] = $Service->getServicename();

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        /** @var InstantreportsTable $InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        $objects['Instantreports'] = $InstantreportsTable->getInstantReportsByServiceId((int)$id, $MY_RIGHTS);

        //Check if the service is used by Autoreports
        if (Plugin::isLoaded('AutoreportModule')) {
            /** @var $AutoreportsTable AutoreportsTable */
            $AutoreportsTable = TableRegistry::getTableLocator()->get('AutoreportModule.Autoreports');
            $objects['Autoreports'] = $AutoreportsTable->getAutoReportsByServiceId((int)$id, $MY_RIGHTS);
        }

        //Check if the host is used by Eventcorrelations
        if (Plugin::isLoaded('EventcorrelationModule')) {
            /** @var EventcorrelationsTable $EventcorrelationsTable */
            $EventcorrelationsTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.Eventcorrelations');
            $objects['Eventcorrelations'] = $EventcorrelationsTable->getEventCorrelationsByServiceId((int)$id, $MY_RIGHTS);
        }

        //Check if the service is used by Maps
        if (Plugin::isLoaded('MapModule')) {
            /** @var $MapsTable MapsTable */
            $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');
            $objects['Maps'] = $MapsTable->getMapsByServiceId((int)$id, $MY_RIGHTS);
        }

        //Check if the host is used by Hostgroups
        /** @var $ServicegroupsTable ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');
        $objects['Servicegroups'] = $ServicegroupsTable->getServiceGroupsByServiceId((int)$id, $MY_RIGHTS);

        $total = 0;
        $total += sizeof($objects['Instantreports']);
        if (isset($objects['Autoreports'])) {
            $total += sizeof($objects['Autoreports']);
        }
        if (isset($objects['Eventcorrelations'])) {
            $total += sizeof($objects['Eventcorrelations']);
        }
        if (isset($objects['Maps'])) {
            $total += sizeof($objects['Maps']);
        }
        $total += sizeof($objects['Servicegroups']);

        $this->set('service', $service->toArray());
        $this->set('objects', $objects);
        $this->set('total', $total);
        $this->viewBuilder()->setOption('serialize', ['service', 'objects', 'total']);
    }
}
