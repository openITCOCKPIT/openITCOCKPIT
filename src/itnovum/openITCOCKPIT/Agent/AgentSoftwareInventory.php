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

namespace App\itnovum\openITCOCKPIT\Agent;

use App\Model\Table\MacosAppsTable;
use App\Model\Table\MacosUpdatesTable;
use App\Model\Table\PackagesHostDetailsTable;
use App\Model\Table\PackagesLinuxTable;
use App\Model\Table\WindowsAppsTable;
use App\Model\Table\WindowsUpdatesTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AgentSoftwareInventory {

    private PackagesHostDetailsTable|Table $PackagesHostDetailsTable;
    private PackagesLinuxTable|Table $PackagesLinuxTable;
    private WindowsAppsTable|Table $WindowsAppsTable;
    private WindowsUpdatesTable|Table $WindowsUpdatesTable;
    private MacosAppsTable|Table $MacosAppsTable;

    private MacosUpdatesTable|Table $MacosUpdatesTable;

    public function __construct() {

        /** @var PackagesHostDetailsTable $PackagesHostDetailsTable */
        $this->PackagesHostDetailsTable = TableRegistry::getTableLocator()->get('PackagesHostDetails');
        /** @var PackagesLinuxTable $PackagesLinuxTable */
        $this->PackagesLinuxTable = TableRegistry::getTableLocator()->get('PackagesLinux');
        /** @var WindowsAppsTable $WindowsAppsTable */
        $this->WindowsAppsTable = TableRegistry::getTableLocator()->get('WindowsApps');
        /** @var WindowsUpdatesTable $WindowsUpdatesTable */
        $this->WindowsUpdatesTable = TableRegistry::getTableLocator()->get('WindowsUpdates');
        /** @var MacosAppsTable $MacosAppsTable */
        $this->MacosAppsTable = TableRegistry::getTableLocator()->get('MacosApps');
        /** @var MacosUpdatesTable $MacosUpdatesTable */
        $this->MacosUpdatesTable = TableRegistry::getTableLocator()->get('MacosUpdates');
    }


    /**
     * @param int $hostId
     * @param array $result
     * @return void
     * @throws \Exception
     */
    public function processAgentInventoryResponse(int $hostId, array $result): void {
        if (!isset($result['Pending']) || $result['Pending']) {
            throw new \Exception('Agent reports pending state, skipping package update');
        }

        $requireUpdate = true;
        if (!empty($result['Stats']) && !empty($result['Stats']['OperatingSystem'])) {
            $newPackagesHash = $this->getPackagesHash($result);

            $availableUpdatesCounts = $this->getUpdateCounts($result);

            $details = [
                'os_type'                    => $result['Stats']['OperatingSystem'],   // "linux", "macos", "windows"
                'os_name'                    => $result['Stats']['OsName'],            // "ubuntu", "opensuse-tumbleweed", "almalinux", "macos",  "Microsoft Windows 11 Enterprise"
                'os_version'                 => $result['Stats']['OsVersion'] ?? '',   // "20.04",  "20260113",            "9.7",       "26.2",   "24H2 (10.0.26100.7462 Build 26100.7462)"
                'os_family'                  => $result['Stats']['OsFamily'] ?? '',    // "debian", "suse",                "rhel",      "darwin", "windows"
                'kernel_release'             => $result['Stats']['KernelRelease'] ?? '', // Currently running linux kernel release, e.g. "5.15.0-76-generic"
                'packages_hash'              => $newPackagesHash,
                'agent_version'              => $result['Stats']['AgentVersion'] ?? '',
                'reboot_required'            => !empty($result['Stats']['RebootRequired']) ? 1 : 0,
                'system_uptime'              => $result['Stats']['Uptime'] ?? 0,
                'last_update'                => date('Y-m-d H:i:s', $result['LastUpdate']),
                'available_updates'          => $availableUpdatesCounts['available_updates'],
                'available_security_updates' => $availableUpdatesCounts['available_security_updates'],
                'last_error'                 => !empty($result['Stats']['LastError']) ? $result['Stats']['LastError'] : null
            ];
            $oldPackagesHash = $this->PackagesHostDetailsTable->updateHostDetails($hostId, $details);
            $requireUpdate = $oldPackagesHash !== $newPackagesHash;

            if (!$requireUpdate) {
                // No changes in packages, skip further processing
                return;
            }
        }

        switch ($result['Stats']['OperatingSystem']) {
            case 'linux':
                $installedPackages = $result['LinuxPackages'] ?? [];
                $availableUpdates = $result['LinuxUpdates'] ?? [];

                $this->PackagesLinuxTable->savePackagesForHost($hostId, $installedPackages, $availableUpdates);
                break;

            case 'windows':
                $apps = $result['WindowsApps'] ?? [];
                $availableUpdates = $result['WindowsUpdates'] ?? [];

                $this->WindowsAppsTable->saveAppsForHost($hostId, $apps);
                $this->WindowsUpdatesTable->saveUpdatesForHost($hostId, $availableUpdates);
                break;

            case 'macos':
                $apps = $result['MacosApps'] ?? [];
                $availableUpdates = $result['MacosUpdates'] ?? [];

                $this->MacosAppsTable->saveAppsForHost($hostId, $apps);
                $this->MacosUpdatesTable->saveUpdatesForHost($hostId, $availableUpdates);
                break;

            default:
                throw new \Exception('Package processing for OS ' . $result['Stats']['OperatingSystem'] . ' not implemented yet');
                break;
        }
    }

    /**
     * Cleans up unused packages and apps from the database.
     * @return void
     */
    public function cleanupUnusedPackages(): void {
        $this->PackagesLinuxTable->deleteUnusedPackages();
        $this->WindowsUpdatesTable->deleteUnusedUpdates();
        $this->MacosUpdatesTable->deleteUnusedUpdates();
    }

    /**
     * Calculates the number of available updates and security updates from the agent result.
     * @param array $result
     * @return int[]
     */
    private function getUpdateCounts(array $result): array {
        $counts = [
            'available_updates'          => 0,
            'available_security_updates' => 0
        ];

        switch ($result['Stats']['OperatingSystem']) {
            case 'linux':
                $availableUpdates = $result['LinuxUpdates'] ?? [];

                $counts['available_updates'] = count($availableUpdates);
                foreach ($availableUpdates as $update) {
                    if (!empty($update['IsSecurityUpdate']) || !empty($update['IsPatch'])) {
                        $counts['available_security_updates']++;
                    }
                }

                break;

            case 'windows':
                $availableUpdates = $result['WindowsUpdates'] ?? [];

                $counts['available_updates'] = count($availableUpdates);
                foreach ($availableUpdates as $update) {
                    if (!empty($update['IsSecurityUpdate'])) {
                        $counts['available_security_updates']++;
                    }
                }
                break;

            case 'macos':
                $availableUpdates = $result['MacosUpdates'] ?? [];

                // macOS only has OS updates, and I think all of them are security related
                $counts['available_updates'] = count($availableUpdates);
                $counts['available_security_updates'] = count($availableUpdates);
                break;

            default:
                break;
        }

        return $counts;
    }

    /**
     * Creats a hash sum of the installed packages and available updates to detect changes in the package inventory.
     *
     * @param array $result
     * @return string
     * @throws \Exception
     */
    public function getPackagesHash(array $result): string {
        switch ($result['Stats']['OperatingSystem']) {
            case 'linux':
                $installedPackages = $result['LinuxPackages'] ?? [];
                $availableUpdates = $result['LinuxUpdates'] ?? [];
                return hash('sha256', json_encode(['installed' => $installedPackages, 'updates' => $availableUpdates]));

            case 'windows':
                $apps = $result['WindowsApps'] ?? [];
                $availableUpdates = $result['WindowsUpdates'] ?? [];
                return hash('sha256', json_encode(['apps' => $apps, 'updates' => $availableUpdates]));
                break;

            case 'macos':
                $apps = $result['MacosApps'] ?? [];
                $availableUpdates = $result['MacosUpdates'] ?? [];
                return hash('sha256', json_encode(['apps' => $apps, 'updates' => $availableUpdates]));
                break;

            default:
                throw new \Exception('Package processing for OS ' . $result['Stats']['OperatingSystem'] . ' not implemented yet');
                break;
        }
    }

}
