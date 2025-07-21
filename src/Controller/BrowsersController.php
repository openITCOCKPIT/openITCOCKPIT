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

use App\Model\Table\ContainersTable;
use App\Model\Table\HostsTable;
use App\Model\Table\OrganizationalChartsTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\TenantsTable;
use Cake\Core\Plugin;
use Cake\Http\Exception\BadRequestException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;

/**
 * Class BrowsersController
 * @package App\Controller
 */
class BrowsersController extends AppController {

    /**
     * @param int|null $containerId
     */
    function index($containerId = null) {
        $User = new User($this->getUser());

        if (!$this->isApiRequest()) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $masterInstanceName = $SystemsettingsTable->getMasterInstanceName();
            $satellites = [];

            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

            if (Plugin::isLoaded('DistributeModule')) {
                /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
                $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

                $satellites = $SatellitesTable->getSatellitesAsListWithDescription($this->MY_RIGHTS);
                $satellites[0] = $masterInstanceName;
            }

            $this->set('username', $User->getFullName());
            $this->set('satellites', $satellites);
            $this->set('types', $HostsTable->getHostTypes());
            //Only ship HTML template
            return;
        }

        if ($containerId === null) {
            throw new BadRequestException("containerId is missing");
        }
        $containerId = (int)$containerId;

        /** @var TenantsTable $TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        /** @var OrganizationalChartsTable $OrganizationalChartsTable */
        $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');

        $tenants = $TenantsTable->getTenantsForBrowsersIndex($this->MY_RIGHTS, $User->getId());
        $tenants = Hash::sort($tenants, '{n}.container.name', 'asc', ['type' => 'regular', 'ignoreCase' => true]);
        $tenantsFiltered = [];
        $tenantsOfContainerNotVisibleForUser = false;
        foreach ($tenants as $tenant) {
            if (in_array($tenant['container']['id'], $this->MY_RIGHTS, true)) {
                $organizanionalCharts = $OrganizationalChartsTable->getOrganizationalChartsByContainerId(
                    $tenant['container']['id'],
                    $MY_RIGHTS,
                    'list'
                );
                $tenantsFiltered[$tenant['container']['id']] = [
                    'id'                        => $tenant['container']['id'],
                    'name'                      => $tenant['container']['name'],
                    'containertype_id'          => $tenant['container']['containertype_id'],
                    'has_organizational_charts' => !empty($organizanionalCharts)
                ];
            } else {
                $tenantsOfContainerNotVisibleForUser = true;
            }
        }
        $tenants = $tenantsFiltered;
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($containerId === ROOT_CONTAINER && !empty($tenants) && $tenantsOfContainerNotVisibleForUser === false) {
            //First request if tenants are not empty or ROOT_CONTAINER

            $this->set('containers', Api::makeItJavaScriptAble($tenants));
            $this->set('breadcrumbs', Api::makeItJavaScriptAble([ROOT_CONTAINER => __('root')]));
        } else {
            //Child container (or so)
            $containerNest = $ContainersTable->getChildren($containerId, true);
            $browser = $ContainersTable->getFirstContainers($containerNest, $this->MY_RIGHTS, [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE]);

            $browser = Hash::sort($browser, '{n}.name', 'asc', ['type' => 'regular', 'ignoreCase' => true]);

            if ($this->hasRootPrivileges === false) {
                foreach ($browser as $key => $containerRecord) {
                    if (!in_array($containerRecord['id'], $this->MY_RIGHTS, true)) {
                        unset($browser[$key]);
                    }
                }
            }

            $containers = [];
            foreach ($browser as $node) {
                $organizanionalCharts = $OrganizationalChartsTable->getOrganizationalChartsByContainerId(
                    $containerId,
                    $MY_RIGHTS,
                    'list'
                );
                $containers[$node['id']] = [
                    'id'                        => $node['id'],
                    'name'                      => $node['name'],
                    'containertype_id'          => $node['containertype_id'],
                    'has_organizational_charts' => !empty($organizanionalCharts)
                ];
            }


            $currentContainer = $ContainersTable->get($containerId)->toArray();

            $breadcrumbs = [];
            if ($currentContainer['parent_id'] === null) {
                $parents = $ContainersTable->getPathByIdAndCacheResult(ROOT_CONTAINER, 'BrowsersIndex');
            } else {
                $parents = $ContainersTable->getPathByIdAndCacheResult($currentContainer['parent_id'], 'BrowsersIndex');
            }


            foreach ($parents as $parentContainer) {
                if (in_array((int)$parentContainer['id'], $this->MY_RIGHTS, true)) {
                    $breadcrumbs[$parentContainer['id']] = $parentContainer['name'];
                }
            }
            $breadcrumbs[$currentContainer['id']] = $currentContainer['name'];
            $this->set('containers', Api::makeItJavaScriptAble($containers));
            $this->set('breadcrumbs', Api::makeItJavaScriptAble($breadcrumbs));

        }

        $organizationalCharts = [];

        if ($containerId !== ROOT_CONTAINER && $this->allowedByContainerId($containerId, false)) {
            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            /** @var $OrganizationalChartsTable OrganizationalChartsTable */
            $OrganizationalChartsTable = TableRegistry::getTableLocator()->get('OrganizationalCharts');
            $organizationalCharts = $OrganizationalChartsTable->getOrganizationalChartsByContainerId($containerId, $MY_RIGHTS, 'list');

        }

        $this->set('organizationalCharts', Api::makeItJavaScriptAble($organizationalCharts));
        $this->set('recursiveBrowser', $User->isRecursiveBrowserEnabled());
        $this->viewBuilder()->setOption('serialize', ['containers', 'recursiveBrowser', 'breadcrumbs', 'organizationalCharts']);
    }
}
