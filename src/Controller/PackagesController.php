<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

use App\Model\Table\HostsTable;
use App\Model\Table\MacosAppsHostsTable;
use App\Model\Table\MacosAppsTable;
use App\Model\Table\MacosUpdatesHostsTable;
use App\Model\Table\MacosUpdatesTable;
use App\Model\Table\PackagesLinuxHostsTable;
use App\Model\Table\PackagesLinuxTable;
use App\Model\Table\WindowsAppsHostsTable;
use App\Model\Table\WindowsAppsTable;
use App\Model\Table\WindowsUpdatesHostsTable;
use App\Model\Table\WindowsUpdatesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * Packages Controller
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class PackagesController extends AppController {

    //Only for ACLs
    public function index() {
    }

    public function linux(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var PackagesLinuxTable $PackagesLinuxTable */
        $PackagesLinuxTable = TableRegistry::getTableLocator()->get('PackagesLinux');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'           => [
                'PackagesLinux.name',
                'PackagesLinux.description'
            ],
            'greater_equals' => [
                'available_updates',
                'available_security_updates',
            ],
            'equals'         => [
                'PackagesLinux.id'
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_packages_linux = $PackagesLinuxTable->getPackagesLinuxIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($all_packages_linux as $index => $packages_linux) {
            $cumulatedStatus = 0;
            $hostsWithUpdates = [];
            $hostsWithSecurityUpdates = [];
            $allHosts = [];
            foreach ($packages_linux['package_linux_hosts'] as $packages_host) {
                $allHosts[$packages_host['host_id']] = $packages_host['host_id'];
                if ($packages_host['needs_update']) {
                    $cumulatedStatus = 1;
                    $hostsWithUpdates[$packages_host['host_id']] = $packages_host['host_id'];
                    if ($packages_host['is_security_update']) {
                        $cumulatedStatus = 2;
                        $hostsWithSecurityUpdates[$packages_host['host_id']] = $packages_host['host_id'];
                    }
                }
            }
            unset($all_packages_linux[$index]['package_linux_hosts']);
            $all_packages_linux[$index]['cumulated_status'] = $cumulatedStatus;
            $all_packages_linux[$index]['all_hosts'] = array_values($allHosts);
            $all_packages_linux[$index]['hosts_needs_update'] = array_values($hostsWithUpdates);
            $all_packages_linux[$index]['hosts_needs_security_update'] = array_values($hostsWithSecurityUpdates);
        }

        $this->set('all_packages_linux', $all_packages_linux);
        $this->viewBuilder()->setOption('serialize', ['all_packages_linux']);
    }

    public function summary() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        /***** Linux *****/
        /** @var PackagesLinuxTable $PackagesLinuxTable */
        $PackagesLinuxTable = TableRegistry::getTableLocator()->get('PackagesLinux');
        $summary['linux'] = $PackagesLinuxTable->getPackagesLinuxForSummary($MY_RIGHTS);

        /***** Windows *****/
        /** @var WindowsAppsTable $WindowsAppsTable */
        $WindowsAppsTable = TableRegistry::getTableLocator()->get('WindowsApps');
        /** @var WindowsUpdatesTable $WindowsUpdatesTable */
        $WindowsUpdatesTable = TableRegistry::getTableLocator()->get('WindowsUpdates');

        $windowsAppsSummary = $WindowsAppsTable->getWindowsAppsForSummary($MY_RIGHTS);
        $windowsUpdatesSummary = $WindowsUpdatesTable->getWindowsUpdatesForSummary($MY_RIGHTS);

        $summary['windows'] = array_merge($windowsAppsSummary, $windowsUpdatesSummary);

        /***** macOS *****/
        /** @var MacosAppsTable $MacosAppsTable */
        $MacosAppsTable = TableRegistry::getTableLocator()->get('MacosApps');
        /** @var MacosUpdatesTable $MacosUpdatesTable */
        $MacosUpdatesTable = TableRegistry::getTableLocator()->get('MacosUpdates');

        $macosAppsSummary = $MacosAppsTable->getMacosAppsForSummary($MY_RIGHTS);
        $macosUpdatesSummary = $MacosUpdatesTable->getMacosUpdatesForSummary($MY_RIGHTS);

        $summary['macos'] = array_merge($macosAppsSummary, $macosUpdatesSummary);
        $summary['total'] = $summary['linux']['totalPackages'] + $summary['windows']['totalPackages'] + $summary['macos']['totalPackages'];
        $summary['outdated'] = $summary['linux']['updatesAvailable'] + $summary['windows']['updatesAvailable'] + $summary['macos']['updatesAvailable'];
        $summary['security'] = $summary['linux']['securityUpdates'] + $summary['windows']['securityUpdates'] + $summary['macos']['securityUpdates'];

        $hostsToUpdateLinux = sizeof($summary['linux']['hostsWithUpdates']);
        $hostsToUpdateWindows = sizeof($summary['windows']['hostsWithUpdates']);
        $hostsToUpdateMacos = sizeof($summary['macos']['hostsWithUpdates']);
        $summary['outdated_hosts'] = $hostsToUpdateLinux + $hostsToUpdateWindows + $hostsToUpdateMacos;

        $hostsWithSecurityUpdatesLinux = sizeof($summary['linux']['hostsWithSecurityUpdates']);
        $hostsWithSecurityUpdatesWindows = sizeof($summary['windows']['hostsWithSecurityUpdates']);
        $hostsWithSecurityUpdatesMacos = sizeof($summary['macos']['hostsWithSecurityUpdates']);
        $summary['security_hosts'] = $hostsWithSecurityUpdatesLinux + $hostsWithSecurityUpdatesWindows + $hostsWithSecurityUpdatesMacos;


        $this->set('summary', $summary);
        $this->viewBuilder()->setOption('serialize', ['summary']);
    }

    public function view_linux($id = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $id = (int)$id;


        /** @var PackagesLinuxTable $PackagesLinuxTable */
        $PackagesLinuxTable = TableRegistry::getTableLocator()->get('PackagesLinux');
        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');

        if (!$PackagesLinuxTable->existsById($id)) {
            throw new NotFoundException(__('Invalid package'));
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $package = $PackagesLinuxTable->getPackageBy($id);

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name',
                'PackagesLinuxHosts.current_version',
                'PackagesLinuxHosts.available_version'
            ],
            'bool' => [
                'PackagesLinuxHosts.needs_update',
                'PackagesLinuxHosts.is_security_update',
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_host_packages = $PackagesLinuxHostsTable->getHostsWithPackage($id, $GenericFilter, $PaginateOMat, $MY_RIGHTS);


        $this->set('package', $package);
        $this->set('all_host_packages', $all_host_packages);
        $this->viewBuilder()->setOption('serialize', ['package', 'all_host_packages']);
    }

    public function windows(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var WindowsAppsTable $WindowsAppsTable */
        $WindowsAppsTable = TableRegistry::getTableLocator()->get('WindowsApps');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'   => [
                'WindowsApps.name',
                'WindowsApps.publisher'
            ],
            'equals' => [
                'WindowsApps.id'
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_windows_apps = $WindowsAppsTable->getWindowsAppsIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);


        foreach ($all_windows_apps as $index => $app) {
            $allHosts = [];
            foreach ($app['windows_apps_hosts'] as $packages_host) {
                $allHosts[$packages_host['host_id']] = $packages_host['host_id'];
            }
            unset($all_windows_apps[$index]['windows_apps_hosts']);
            $all_windows_apps[$index]['all_hosts'] = array_values($allHosts);
        }

        $this->set('all_windows_apps', $all_windows_apps);
        $this->viewBuilder()->setOption('serialize', ['all_windows_apps']);
    }

    public function view_windows($id = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $id = (int)$id;


        /** @var WindowsAppsTable $WindowsAppsTable */
        $WindowsAppsTable = TableRegistry::getTableLocator()->get('WindowsApps');
        /** @var WindowsAppsHostsTable $WindowsAppsHostsTable */
        $WindowsAppsHostsTable = TableRegistry::getTableLocator()->get('WindowsAppsHosts');

        if (!$WindowsAppsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid app'));
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $app = $WindowsAppsTable->getAppById($id);

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name',
                'WindowsAppsHosts.version',
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_host_apps = $WindowsAppsHostsTable->getHostsWithApp($id, $GenericFilter, $PaginateOMat, $MY_RIGHTS);


        $this->set('app', $app);
        $this->set('all_host_apps', $all_host_apps);
        $this->viewBuilder()->setOption('serialize', ['app', 'all_host_apps']);
    }

    public function windows_updates(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var WindowsUpdatesTable $WindowsUpdatesTable */
        $WindowsUpdatesTable = TableRegistry::getTableLocator()->get('WindowsUpdates');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'           => [
                'WindowsUpdates.name',
                'WindowsUpdates.description',
                'WindowsUpdates.kbarticle_ids'
            ],
            'greater_equals' => [
                'available_updates',
                'available_security_updates',
            ],
            'equals'         => [
                'WindowsUpdates.id',
                'WindowsUpdates.update_id',
                'WindowsUpdatesHosts.is_security_update'
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }


        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_windows_updates = $WindowsUpdatesTable->getWindowsUpdatesIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);

        foreach ($all_windows_updates as $index => $windows_update) {
            $hostsWithUpdates = [];
            $hostsWithSecurityUpdates = [];
            $allHosts = [];
            foreach ($windows_update['windows_updates_hosts'] as $update_host) {
                $all_windows_updates[$index]['kbarticle_ids'] = !empty($windows_update['kbarticle_ids']) ? explode(',', $windows_update['kbarticle_ids']) : [];
                $allHosts[$update_host['host_id']] = $update_host['host_id'];
                $hostsWithUpdates[$update_host['host_id']] = $update_host['host_id'];
                if ($update_host['is_security_update']) {
                    $hostsWithSecurityUpdates[$update_host['host_id']] = $update_host['host_id'];
                }
            }

            unset($all_windows_updates[$index]['windows_updates_hosts']);
            $all_windows_updates[$index]['all_hosts'] = array_values($allHosts);
            $all_windows_updates[$index]['hosts_needs_update'] = array_values($hostsWithUpdates);
            $all_windows_updates[$index]['hosts_needs_security_update'] = array_values($hostsWithSecurityUpdates);
        }

        $this->set('all_windows_updates', $all_windows_updates);
        $this->viewBuilder()->setOption('serialize', ['all_windows_updates']);
    }

    public function view_windows_update($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $id = (int)$id;


        /** @var WindowsUpdatesTable $WindowsUpdatesTable */
        $WindowsUpdatesTable = TableRegistry::getTableLocator()->get('WindowsUpdates');
        /** @var WindowsUpdatesHostsTable $WindowsUpdatesHostsTable */
        $WindowsUpdatesHostsTable = TableRegistry::getTableLocator()->get('WindowsUpdatesHosts');

        if (!$WindowsUpdatesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid update'));
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $update = $WindowsUpdatesTable->getUpdateById($id);

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name',
            ],
            'bool' => [
                'WindowsUpdatesHosts.is_security_update',
                'WindowsUpdatesHosts.reboot_required',
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_host_updates = $WindowsUpdatesHostsTable->getUpdateWithHost($id, $GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($all_host_updates as $index => $hostUpdate) {
            $all_host_updates[$index]['kbarticle_ids'] = !empty($update['kbarticle_ids']) ? explode(',', $update['kbarticle_ids']) : [];
        }

        $update['kbarticle_ids'] = !empty($update['kbarticle_ids']) ? explode(',', $update['kbarticle_ids']) : [];


        $this->set('update', $update);
        $this->set('all_host_updates', $all_host_updates);
        $this->viewBuilder()->setOption('serialize', ['update', 'all_host_updates']);
    }

    public function macos(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var MacosAppsTable $MacosAppsTable */
        $MacosAppsTable = TableRegistry::getTableLocator()->get('MacosApps');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'   => [
                'MacosApps.name',
                'MacosApps.description'
            ],
            'equals' => [
                'MacosApps.id'
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_macos_apps = $MacosAppsTable->getMacosAppsIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);


        foreach ($all_macos_apps as $index => $app) {
            $allHosts = [];
            foreach ($app['macos_apps_hosts'] as $packages_host) {
                $allHosts[$packages_host['host_id']] = $packages_host['host_id'];
            }
            unset($all_macos_apps[$index]['macos_apps_hosts']);
            $all_macos_apps[$index]['all_hosts'] = array_values($allHosts);
        }

        $this->set('all_macos_apps', $all_macos_apps);
        $this->viewBuilder()->setOption('serialize', ['all_macos_apps']);
    }

    public function view_macos($id = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $id = (int)$id;


        /** @var MacosAppsTable $MacosAppsTable */
        $MacosAppsTable = TableRegistry::getTableLocator()->get('MacosApps');
        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');

        if (!$MacosAppsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid app'));
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $app = $MacosAppsTable->getAppById($id);

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name',
                'MacosAppsHosts.version',
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_host_apps = $MacosAppsHostsTable->getHostsWithApp($id, $GenericFilter, $PaginateOMat, $MY_RIGHTS);


        $this->set('app', $app);
        $this->set('all_host_apps', $all_host_apps);
        $this->viewBuilder()->setOption('serialize', ['app', 'all_host_apps']);
    }

    public function macos_updates(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var MacosUpdatesTable $MacosUpdatesTable */
        $MacosUpdatesTable = TableRegistry::getTableLocator()->get('MacosUpdates');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'   => [
                'MacosUpdates.name',
                'MacosUpdates.description',
            ],
            'equals' => [
                'MacosUpdates.id',
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }


        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_macos_updates = $MacosUpdatesTable->getMacosUpdatesIndex($GenericFilter, $PaginateOMat, $MY_RIGHTS);

        foreach ($all_macos_updates as $index => $macos_update) {
            $hostsWithUpdates = [];
            $allHosts = [];
            foreach ($macos_update['macos_updates_hosts'] as $update_host) {
                $allHosts[$update_host['host_id']] = $update_host['host_id'];
                $hostsWithUpdates[$update_host['host_id']] = $update_host['host_id'];
            }

            unset($all_macos_updates[$index]['macos_updates_hosts']);
            $all_macos_updates[$index]['all_hosts'] = array_values($allHosts);
            $all_macos_updates[$index]['hosts_needs_update'] = array_values($hostsWithUpdates);
        }

        $this->set('all_macos_updates', $all_macos_updates);
        $this->viewBuilder()->setOption('serialize', ['all_macos_updates']);
    }

    public function view_macos_update($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $id = (int)$id;


        /** @var MacosUpdatesTable $MacosUpdatesTable */
        $MacosUpdatesTable = TableRegistry::getTableLocator()->get('MacosUpdates');
        /** @var MacosUpdatesHostsTable $MacosUpdatesHostsTable */
        $MacosUpdatesHostsTable = TableRegistry::getTableLocator()->get('MacosUpdatesHosts');

        if (!$MacosUpdatesTable->existsById($id)) {
            throw new NotFoundException(__('Invalid update'));
        }

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $update = $MacosUpdatesTable->getUpdateById($id);

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Hosts.name',
            ],
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_host_updates = $MacosUpdatesHostsTable->getUpdateWithHost($id, $GenericFilter, $PaginateOMat, $MY_RIGHTS);

        $this->set('update', $update);
        $this->set('all_host_updates', $all_host_updates);
        $this->viewBuilder()->setOption('serialize', ['update', 'all_host_updates']);
    }

    public function host_linux_packages($hostId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'   => [
                'PackagesLinux.name',
                'PackagesLinux.description',
                'PackagesLinuxHosts.current_version',
                'PackagesLinuxHosts.available_version',
            ],
            'equals' => [
                'PackagesLinuxHosts.needs_update',
                'PackagesLinuxHosts.is_security_update'
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_packages = $PackagesLinuxHostsTable->getPackagesOfHost($hostId, $GenericFilter, $PaginateOMat, $MY_RIGHTS);

        $this->set('all_packages_linux', $all_packages);
        $this->viewBuilder()->setOption('serialize', ['all_packages_linux']);
    }

    public function host_windows_updates($hostId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        /** @var WindowsUpdatesHostsTable $WindowsUpdatesHostsTable */
        $WindowsUpdatesHostsTable = TableRegistry::getTableLocator()->get('WindowsUpdatesHosts');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like'   => [
                'WindowsUpdates.name',
                'WindowsUpdates.description',
                'WindowsUpdates.kbarticle_ids',
                'WindowsUpdates.update_id'
            ],
            'equals' => [
                'WindowsUpdatesHosts.is_security_update'
            ],
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_updates = $WindowsUpdatesHostsTable->getUpdatesOfHost($hostId, $GenericFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($all_updates as $index => $update) {
            $all_updates[$index]['windows_update']['kbarticle_ids'] = !empty($update['windows_update']['kbarticle_ids']) ? explode(',', $update['windows_update']['kbarticle_ids']) : [];
        }

        $this->set('all_windows_updates', $all_updates);
        $this->viewBuilder()->setOption('serialize', ['all_windows_updates']);
    }

    public function host_windows_apps($hostId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        /** @var WindowsAppsHostsTable $WindowsAppsHostsTable */
        $WindowsAppsHostsTable = TableRegistry::getTableLocator()->get('WindowsAppsHosts');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'WindowsApps.name',
                'WindowsApps.publisher',
                'WindowsAppsHosts.version'
            ]
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_windows_apps = $WindowsAppsHostsTable->getWindowsAppsByHost($hostId, $GenericFilter, $PaginateOMat, $MY_RIGHTS);


        $this->set('all_windows_apps', $all_windows_apps);
        $this->viewBuilder()->setOption('serialize', ['all_windows_apps']);
    }

    public function host_macos_updates($hostId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        /** @var MacosUpdatesHostsTable $MacosUpdatesHostsTable */
        $MacosUpdatesHostsTable = TableRegistry::getTableLocator()->get('MacosUpdatesHosts');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'MacosUpdates.name',
                'MacosUpdates.description',
                'MacosUpdates.version',
            ]
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_updates = $MacosUpdatesHostsTable->getUpdatesOfHost($hostId, $GenericFilter, $PaginateOMat, $MY_RIGHTS);

        $this->set('all_macos_updates', $all_updates);
        $this->viewBuilder()->setOption('serialize', ['all_macos_updates']);
    }

    public function host_macos_apps($hostId = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');
        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'MacosApps.name',
                'MacosApps.description',
                'MacosAppsHosts.version'
            ]
        ]);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());
        $all_macos_apps = $MacosAppsHostsTable->getMacosAppsByHost($hostId, $GenericFilter, $PaginateOMat, $MY_RIGHTS);


        $this->set('all_macos_apps', $all_macos_apps);
        $this->viewBuilder()->setOption('serialize', ['all_macos_apps']);
    }
}
