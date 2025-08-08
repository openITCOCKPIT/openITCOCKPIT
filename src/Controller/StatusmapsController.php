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

use App\Lib\Interfaces\HoststatusTableInterface;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DistributeModule\Model\Table\SatellitesTable;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\ServiceStateSummary;
use itnovum\openITCOCKPIT\Filter\StatusmapFilter;
use Statusengine2Module\Model\Table\ServicestatusTable;


/**
 * Class StatusmapsController
 * @package App\Controller
 */
class StatusmapsController extends AppController {

    /**
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function index() {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var HoststatusTableInterface $HoststatusTable */
        $HoststatusTable = $this->DbBackend->getHoststatusTable();
        $this->loadComponent('StatusMap');

        /** @var SystemsettingsTable $Systemsettings */
        $Systemsettings = TableRegistry::getTableLocator()->get('Systemsettings');
        $masterInstanceName = $Systemsettings->getMasterInstanceName();
        $satellites = [];
        if (Plugin::isLoaded('DistributeModule')) {
            /** @var SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

            $MY_RIGHTS = $this->MY_RIGHTS;
            if (!is_array($MY_RIGHTS)) {
                $MY_RIGHTS = [$MY_RIGHTS];
            }
            $satellites = $SatellitesTable->find('list')
                ->where([
                    'Satellites.container_id IN' => $MY_RIGHTS,
                ])
                ->select([
                    'Satellites.id',
                    'Satellites.name'
                ])
                ->orderBy([
                    'Satellites.name' => 'asc'
                ])
                ->toArray();
        }
        $satellites[0] = $masterInstanceName;


        if (!$this->isAngularJsRequest()) {
            $this->set('satellites', $satellites);
            return;
        }

        $session = $this->request->getSession();
        $session->close();

        $allHostIds = [];
        $hasBrowserRight = $this->hasPermission('browser', 'hosts');
        if ($this->request->getQuery('showAll') === 'false') {

            $parentHostWithChildIds = $HostsTable->parentHostsWithChildIds();

            foreach ($parentHostWithChildIds as $parentHostWithChildId) {
                if (!in_array($parentHostWithChildId['HostToParenthostParent']['id'], $allHostIds, true)) {
                    $allHostIds[] = $parentHostWithChildId['HostToParenthostParent']['id'];
                }
                if (!in_array($parentHostWithChildId['HostToParenthostChild']['id'], $allHostIds, true)) {
                    $allHostIds[] = $parentHostWithChildId['HostToParenthostChild']['id'];
                }
            }
        }
        $containerIds = [];

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === false) {
            $containerIds = $ContainersTable->resolveChildrenOfContainerIds(
                $this->MY_RIGHTS,
                false, [
                CT_GLOBAL,
                CT_TENANT,
                CT_LOCATION,
                CT_NODE
            ]);
        }
        $StatusmapFilter = new StatusmapFilter($this->request);
        $nodes = [];
        $edges = [];

        $count = $HostsTable->getHostsForStatusmaps($StatusmapFilter->indexFilter(), $containerIds, $allHostIds, true);

        $limit = 100;
        $numberOfSelects = ceil($count / $limit);
        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields
            ->currentState()
            ->isHardstate()
            ->scheduledDowntimeDepth()
            ->problemHasBeenAcknowledged();

        for ($i = 0; $i < $numberOfSelects; $i++) {
            $tmpHostsResult = $HostsTable->getHostsForStatusmaps($StatusmapFilter->indexFilter(), $containerIds, $allHostIds, false, $limit, $limit * $i);

            $hostUuids = Hash::extract($tmpHostsResult, '{n}.uuid');
            $hoststatus = $HoststatusTable->byUuid($hostUuids, $HoststatusFields);
            foreach ($tmpHostsResult as $hostChunk) {
                if (!isset($hoststatus[$hostChunk['uuid']]['Hoststatus'])) {
                    $hoststatus[$hostChunk['uuid']] = [
                        'Hoststatus' => []
                    ];
                }
                $Hoststatus = new Hoststatus(
                    $hoststatus[$hostChunk['uuid']]['Hoststatus']
                );


                $nodes[] = [
                    'id'     => 'Host_' . $hostChunk['id'],
                    'hostId' => $hostChunk['id'],
                    'label'  => $hostChunk['name'],
                    'title'  => $hostChunk['name'] . ' (' . $hostChunk['address'] . ')',
                    'uuid'   => $hostChunk['uuid'],
                    'group'  => $this->StatusMap->getNodeGroupName($hostChunk['disabled'], $Hoststatus)
                ];

                foreach ($hostChunk['parenthosts'] as $parentHost) {
                    $edges[] = [
                        'from'   => 'Host_' . $hostChunk['id'],
                        'to'     => 'Host_' . $parentHost['id'],
                        'color'  => [
                            'inherit' => 'to',
                        ],
                        'arrows' => 'to'
                    ];
                }
            }
        }

        $statusMap = [
            'nodes' => $nodes,
            'edges' => $edges
        ];

        $this->set('satellites', Api::makeItJavaScriptAble($satellites));

        $this->set('statusMap', $statusMap);
        $this->set('hasBrowserRight', $hasBrowserRight);
        $this->viewBuilder()->setOption('serialize', ['statusMap', 'hasBrowserRight', 'satellites']);
    }

    /**
     * @param int|null $hostId
     * @throws \App\Lib\Exceptions\InvalidArgumentException
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    public function hostAndServicesSummaryStatus($hostId = null) {
        if (!$this->isAngularJsRequest()) {
            return;
        }
        if (!$hostId) {
            throw new NotFoundException(__('Invalid request parameters'));
        }
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var ServicestatusTable $ServicestatusTable */
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();

        $serviceUuids = $ServicesTable->getServiceUuidsOfHostByHostId($hostId);
        $ServicestatusFields = new ServicestatusFields($this->DbBackend);
        $ServicestatusFields->currentState()
            ->problemHasBeenAcknowledged()
            ->activeChecksEnabled()
            ->scheduledDowntimeDepth();
        $servicestatus = $ServicestatusTable->byUuids($serviceUuids, $ServicestatusFields);

        $ServicestatusObjects = Servicestatus::fromServicestatusByUuid($servicestatus);
        $serviceStateSummary = ServiceStateSummary::getServiceStateSummary($ServicestatusObjects, true);

        $this->set('serviceStateSummary', $serviceStateSummary);
        $this->set('hostId', $hostId);
        $this->viewBuilder()->setOption('serialize', ['serviceStateSummary']);
    }
}
