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

use App\Model\Table\ProxiesTable;
use App\Model\Table\RegistersTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\DnfRepositoryChecker;
use itnovum\openITCOCKPIT\Core\Http;
use itnovum\openITCOCKPIT\Core\PackagemanagerRequestBuilder;
use itnovum\openITCOCKPIT\Core\RepositoryChecker;
use itnovum\openITCOCKPIT\Core\System\Health\LsbRelease;
use itnovum\openITCOCKPIT\Core\ValueObjects\License;
use itnovum\openITCOCKPIT\Core\Views\Logo;

class PacketmanagerController extends AppController {

    public function index() {
        if ($this->isHtmlRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }


        if ($this->request->is('get')) {
            /** @var ProxiesTable $ProxiesTable */
            $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
            /** @var RegistersTable $RegistersTable */
            $RegistersTable = TableRegistry::getTableLocator()->get('Registers');

            $License = $RegistersTable->getLicense();
            $License = new License($License);

            $packagemanagerRequestBuilder = new PackagemanagerRequestBuilder(ENVIRONMENT, $License->getLicense());
            $http = new Http(
                $packagemanagerRequestBuilder->getUrl(),
                $packagemanagerRequestBuilder->getOptions(),
                $ProxiesTable->getSettings()
            );

            $http->sendRequest();

            $result = [
                'error'     => false,
                'error_msg' => '',
                'data'      => []
            ];

            if (!$http->error) {
                if (strlen($http->data) > 0) {
                    $result['data'] = json_decode($http->data, true);
                    if (!empty($result['data']['changelog'])) {
                        foreach ($result['data']['changelog'] as $index => $changelog) {
                            $result['data']['changelog'][$index]['Changelog']['changes'] = nl2br($result['data']['changelog'][$index]['Changelog']['changes']);
                        }
                    }
                }
            } else {
                $result['error'] = true;
                $result['error_msg'] = $http->getLastError()['error'];
            }

            $installedModules = [];
            $output = [];
            if (IS_CONTAINER === false) {
                $LsbRelease = new LsbRelease();
                if ($LsbRelease->isDebianBased()) {
                    exec('dpkg -l |grep openitcockpit-module', $output, $rc);
                    //$output = $this->getTestDpkgOutput();
                }

                if ($LsbRelease->isRhelBased()) {
                    exec('dnf list installed | grep openitcockpit-module', $output, $rc);
                    //$output = $this->getTestDnfOutput();
                }

                foreach ($output as $line) {
                    preg_match_all('/(openitcockpit\-module\-[^\s|^\.]+)/', $line, $matches);
                    if (isset($matches[0][0])) {
                        $module = $matches[0][0];
                        $installedModules[$module] = true;
                    }

                    if (isset($matches)) {
                        unset($matches);
                    }
                }
            } else {
                // Container based version of openITCOCKPIT have always all modules enabled
                if (isset($result['data']['modules'])) {
                    foreach ($result['data']['modules'] as $module) {
                        $installedModules[$module['Module']['apt_name']] = true;
                    }
                }
            }

            $LsbRelease = new LsbRelease();
            $Logo = new Logo();
            $result['data']['systemname'] = $this->getSystemname();
            $result['data']['RepositoryChecker'] = new RepositoryChecker();
            $result['data']['DnfRepositoryChecker'] = new DnfRepositoryChecker();
            $result['data']['LsbRelease'] = $LsbRelease->getCodename();
            $result['data']['isDebianBased'] = $LsbRelease->isDebianBased();
            $result['data']['isRhelBased'] = $LsbRelease->isRhelBased();
            $result['data']['logoUrl'] = $Logo->getLogoForHtml();
            $this->set('result', $result);
            $this->set('installedModules', $installedModules);
            $this->set('OPENITCOCKPIT_VERSION', OPENITCOCKPIT_VERSION);
            $this->viewBuilder()->setOption('serialize', ['result', 'installedModules', 'OPENITCOCKPIT_VERSION', 'LsbRelease', 'isDebianBased', 'isRhelBased', 'systemname', 'logoUrl']);
        }
    }

    public function repositoryChecker(): void {
        if (!$this->isApiRequest()) {
            return;
        }
        $RepositoryChecker = new RepositoryChecker();
        $DnfRepositoryChecker = new DnfRepositoryChecker();
        $LsbRelease = new LsbRelease();

        $result = [
            'data' => [
                // MAIN
                'hasError'                               => false,

                // DEBIAN
                'isDebianBased'                          => $LsbRelease->isDebianBased(),
                'repositoryCheckerExists'                => false,
                'RepositoryCheckerExistsError'           => '',
                'repositoryCheckerIsReadable'            => false,
                'repositoryCheckerIsReadableError'       => '',
                'RepositoryCheckerIsReadableSourcesList' => '',
                'isOldRepositoryInUse'                   => false,
                'isOldRepositoryInUseError'              => '',

                // RHEL
                'isRhelBased'                            => $LsbRelease->isRhelBased(),
                'dnfRepositoryCheckerExists'             => false,
                'dnfRepositoryCheckerExistsError'        => '',
                'dnfRepositoryIsReadable'                => false,
                'dnfRepositoryIsReadableError'           => '',
                'dnfRepositoryRepoConfig'                => ''
            ]
        ];


        if ($result['data']['isDebianBased']) {
            $this->checkDebianRepository($RepositoryChecker, $result);
        }

        if ($result['data']['isRhelBased']) {
            $this->checkRhelRepository($DnfRepositoryChecker, $result);
        }

        $this->set('result', $result);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    private function checkDebianRepository($RepositoryChecker, &$result): void {
        try {
            $result['data']['repositoryCheckerExists'] = $RepositoryChecker->exists();
        } catch (\Exception $e) {
            $result['data']['hasError'] = true;
            $result['data']['RepositoryCheckerExistsError'] = $e->getMessage();
        }

        try {
            $result['data']['repositoryCheckerIsReadable'] = $RepositoryChecker->isReadable();
        } catch (\Exception $e) {
            $result['data']['hasError'] = true;
            $result['data']['repositoryCheckerIsReadableError'] = $e->getMessage();
            $result['data']['RepositoryCheckerIsReadableSourcesList'] = $RepositoryChecker->getSourcesList();
        }

        try {
            $result['data']['isOldRepositoryInUse'] = $RepositoryChecker->isOldRepositoryInUse();
        } catch (\Exception $e) {
            $result['data']['hasError'] = true;
            $result['data']['isOldRepositoryInUseError'] = $e->getMessage();
        }
    }

    private function checkRhelRepository($DnfRepositoryChecker, &$result): void {
        try {
            $result['data']['dnfRepositoryCheckerExists'] = $DnfRepositoryChecker->exists();
        } catch (\Exception $e) {
            $result['data']['hasError'] = true;
            $result['data']['dnfRepositoryCheckerExistsError'] = $e->getMessage();
        }

        try {
            $result['data']['dnfRepositoryIsReadable'] = $DnfRepositoryChecker->isReadable();
        } catch (\Exception $e) {
            $result['data']['hasError'] = true;
            $result['data']['dnfRepositoryIsReadableError'] = $e->getMessage();
            $result['data']['dnfRepositoryRepoConfig'] = $DnfRepositoryChecker->getRepoConfig();
        }
    }

    /**
     * @return array
     */
    private function getTestDpkgOutput() {
        $output = [
            'ii  openitcockpit-module-autoreport       3.7.3-4ubuntu16.04~201912170227                 amd64        Auto Reporting module for openITCOCKPIT',
            'ii  openitcockpit-module-design           3.7.3-4ubuntu16.04~201912170227                 amd64        Change design of openITCOCKPIT',
            'ii  openitcockpit-module-discovery        3.7.3-4ubuntu16.04~201912170227                 amd64        Discovery module for openITCOCKPIT',
            'ii  openitcockpit-module-distribute       3.7.3-4ubuntu16.04~201912170227                 amd64        Distributed Monitoring module for openITCOCKPIT',
            'ii  openitcockpit-module-evc              3.7.3-4ubuntu16.04~201912170227                 amd64        Event correlation module for openITCOCKPIT',
            'ii  openitcockpit-module-grafana          3.7.3-4ubuntu16.04~201912170227                 amd64        Grafana module for openITCOCKPIT',
            'ii  openitcockpit-module-map              3.7.3-4ubuntu16.04~201912170227                 amd64        Map module for openITCOCKPIT',
            'ii  openitcockpit-module-mk               3.7.3-4ubuntu16.04~201912170227                 amd64        CheckMK module for openITCOCKPIT',
            'ii  openitcockpit-module-openstreetmap    3.7.3-4ubuntu16.04~201912170227                 amd64        OpenStreetMap Module for openITCOCKPIT',
            'ii  openitcockpit-module-slack            3.7.3-4ubuntu16.04~201912170227                 amd64        Slack module for openITCOCKPIT',
            'ii  openitcockpit-module-wmi              3.7.3-4ubuntu16.04~201912170227                 amd64        WMI module for openITCOCKPIT',
            'ii  openitcockpit-module-wmi-plugins      1.3.15-0ubuntu16.04~201912170224                amd64        plugins for wmi module.',
            'ii  openitcockpit-module-windows-basic-monitoring-nscp 4.1.0-20200923181004focal          all          openITCOCKPIT Frontend module windows-basic-monitoring-nscp package'
        ];
        return $output;
    }

    /**
     * @return array
     */
    private function getTestDnfOutput() {
        $output = [
            'openitcockpit-module-distribute.x86_64             4.6.3_20230330042510RHEL8-1.RHEL8            @openitcockpit',
            'openitcockpit-module-grafana.x86_64                4.6.3_20230330042510RHEL8-1.RHEL8            @openitcockpit',
            'openitcockpit-module-prometheus.x86_64             4.6.3_20230330042510RHEL8-1.RHEL8            @openitcockpit'
        ];
        return $output;
    }

}
