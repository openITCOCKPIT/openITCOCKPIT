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

declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\CronjobsTable;
use App\Model\Table\RegistersTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Core\Configure;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\DnfRepositoryChecker;
use itnovum\openITCOCKPIT\Core\RepositoryChecker;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\System\Health\CpuLoad;
use itnovum\openITCOCKPIT\Core\System\Health\Disks;
use itnovum\openITCOCKPIT\Core\System\Health\LsbRelease;
use itnovum\openITCOCKPIT\Core\System\Health\MemoryUsage;
use itnovum\openITCOCKPIT\Core\System\Health\MonitoringEngine;
use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * Class AdministratorsController
 * @package App\Controller
 */
class AdministratorsController extends AppController {

    function debug() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template

            $RepositoryChecker = new RepositoryChecker();
            $DnfRepositoryChecker = new DnfRepositoryChecker();
            $LsbRelease = new LsbRelease();
            $this->set('RepositoryChecker', $RepositoryChecker);
            $this->set('DnfRepositoryChecker', $DnfRepositoryChecker);
            $this->set('LsbRelease', $LsbRelease);
            return;
        }

        $isoTimestamp = (bool)$this->request->getQuery('isoTimestamp', 0);

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsetting = $SystemsettingsTable->findAsArray();

        /** @var $RegistersTable RegistersTable */
        $RegistersTable = TableRegistry::getTableLocator()->get('Registers');
        $License = $RegistersTable->getLicense();

        Configure::load('nagios');
        Configure::load('gearman');


        //Collect interface information
        $edition = __('Community - unregistered');
        if (isset($License['license'])) {
            $edition = __('Enterprise');
            if ($License['license'] === $RegistersTable->getCommunityLicenseKey()) {
                $edition = __('Community - registered');
            }
        }

        $MonitoringEngine = new MonitoringEngine();

        $interfaceInformation = [
            'systemname'             => $systemsetting['FRONTEND']['FRONTEND.SYSTEMNAME'],
            'version'                => OPENITCOCKPIT_VERSION,
            'oitc_is_debugging_mode' => Configure::read('debug'),
            'edition'                => $edition,
            'path_for_config'        => Configure::read('nagios.export.backupSource'),
            'path_for_backups'       => Configure::read('nagios.export.backupTarget'),
            'command_interface'      => $systemsetting['MONITORING']['MONITORING.CMD'],
            'monitoring_engine'      => $MonitoringEngine->getMonitoringEngine()

        ];

        //Collect process information
        $GearmanClient = new Gearman();
        $gearmanReachable = $GearmanClient->ping();

        $isGearmanWorkerRunning = false;
        // replacement of ps -eaf because it takes ps too long to display the username in an LDAP based setup
        // https://www.ibm.com/support/pages/apar/IJ08995
        // we have no need for the username, so we can use the faster ps -eo command
        exec('ps -eo command |grep gearman_worker |grep -v \'mod_gearman_worker\' |grep -v \'grep\'', $output);
        if (sizeof($output) > 0) {
            $isGearmanWorkerRunning = true;
        }

        $backgroundProcessStatus = [];
        if ($gearmanReachable && $isGearmanWorkerRunning) {
            //Check if background proesses are running
            $backgroundProcessStatus = $GearmanClient->send('check_background_processes');
        }

        $isNdoInstalled = false;
        if (file_exists('/opt/openitc/nagios/bin/ndo2db')) {
            $isNdoInstalled = true;
        }

        $isStatusengineInstalled = false;
        if (file_exists('/opt/openitc/statusengine2/cakephp/app/Console/Command/StatusengineLegacyShell.php')) {
            $isStatusengineInstalled = true;
        }

        //NPCD is not supported anymore!
        $isStatusenginePerfdataProcessor = true;

        if (IS_CONTAINER) {
            // We are running inside a container and are therefore a recent version of openITCOCKPIT.
            // NDO and NPCD support was dropped with openITCOCKPIT 3.x (at least 2 years before container support)
            // so there is absolutely no way we have one of those two installed.
            // Honestly speaking the checking for NDO and NPCD should be removed anyway!
            $isStatusengineInstalled = true;
            $isStatusenginePerfdataProcessor = true;
        }

        $processInformation = [
            'gearmanReachable'                => $gearmanReachable,
            'isGearmanWorkerRunning'          => $isGearmanWorkerRunning,
            'isNdoInstalled'                  => $isNdoInstalled,
            'isStatusengineInstalled'         => $isStatusengineInstalled,
            'isStatusenginePerfdataProcessor' => $isStatusenginePerfdataProcessor,
            'backgroundProcesses'             => $backgroundProcessStatus
        ];


        //Collect server information
        $LsbRelease = new LsbRelease();
        if ($LsbRelease->isDebianBased()) {
            $osVersion = sprintf('%s %s (%s)', $LsbRelease->getVendor(), $LsbRelease->getVersion(), $LsbRelease->getCodename());
        } else {
            $osVersion = $LsbRelease->getCodename();
        }

        $CpuLoad = new CpuLoad();

        $serverInformation = [
            'address'                => $_SERVER['SERVER_ADDR'],
            'webserver'              => $_SERVER['SERVER_SOFTWARE'],
            'tls'                    => $_SERVER['HTTPS'],
            'os_version'             => $osVersion,
            'kernel'                 => php_uname('r'),
            'architecture'           => php_uname('m'),
            'cpu_processor'          => $CpuLoad->getModel(),
            'cpu_cores'              => $CpuLoad->getNumberOfCores(),
            'php_version'            => PHP_VERSION,
            'php_memory_limit'       => str_replace('M', '', get_cfg_var('memory_limit')) . 'MB',
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_extensions'         => get_loaded_extensions(),
            'containerized'          => (IS_CONTAINER) ? __('Yes') : __('No'),
            'isContainer'            => IS_CONTAINER,
            'LsbRelease'             => $LsbRelease->getCodename(),
            'isDebianBased'          => $LsbRelease->isDebianBased(),
            'isRhelBased'            => $LsbRelease->isRhelBased(),

        ];

        //Collect CPU load history
        $renderGraph = false;
        $currentCpuLoad = [
            1  => $CpuLoad->getLoad1(),
            5  => $CpuLoad->getLoad5(),
            15 => $CpuLoad->getLoad15()
        ];
        $cpuLoadHistoryInformation = [
            1  => [],
            5  => [],
            15 => [],
        ];
        if (file_exists(TMP . 'loadavg')) {
            $load = file(TMP . 'loadavg');
            if (sizeof($load) >= 3) {
                $renderGraph = true;
                foreach ($load as $line) {
                    $line = explode(' ', $line);

                    if ($isoTimestamp) {
                        // V5 iso timestamps for eCharts + float values
                        $timestamp = intval($line[0]);
                        $cpuLoadHistoryInformation[1][date('c', $timestamp)] = floatval($line[1]);
                        $cpuLoadHistoryInformation[5][date('c', $timestamp)] = floatval($line[2]);
                        $cpuLoadHistoryInformation[15][date('c', $timestamp)] = floatval($line[3]);
                    } else {
                        // V4 legacy jsTimestamps and string values
                        $cpuLoadHistoryInformation[1][($line[0] * 1000)] = $line[1];
                        $cpuLoadHistoryInformation[5][($line[0] * 1000)] = $line[2];
                        $cpuLoadHistoryInformation[15][($line[0] * 1000)] = $line[3];
                    }
                }
            }
        }

        //Collect memory usage
        $MemoryUsage = new MemoryUsage();
        $memory = $MemoryUsage->getMemoryUsage();

        //Collect disk usage
        $Disks = new Disks();
        $diskUsage = $Disks->getDiskUsage();


        //Collect gearman queue information
        $gearmanStatus = [];
        $output = null;
        if ($gearmanReachable) {
            exec('gearadmin --status -h ' . escapeshellarg(env('OITC_GEARMAN_ADDRESS', 'localhost')), $output);
            //Parse output
            $trash = array_pop($output);
            foreach ($output as $line) {
                $queueDetails = explode("\t", $line);
                $gearmanStatus[$queueDetails[0]] = [
                    'jobs'    => $queueDetails[1],
                    'running' => $queueDetails[2],
                    'worker'  => $queueDetails[3],
                ];
            }
        }

        //Collect email configuration
        Configure::load('email', 'default', false);
        $mailConfig = Configure::read('EmailTransport');

        $User = $this->getUser();
        //$Email = new Mailer('default');
        //debug($Email::getConfig('default'));

        $emailInformation = [
            'transport'         => $mailConfig['default']['className'],
            'host'              => $mailConfig['default']['host'],
            'port'              => $mailConfig['default']['port'],
            'username'          => $mailConfig['default']['username'],
            'test_mail_address' => $User->get('email')
        ];

        //Collect remote user information
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $os = "unknown";
        if (strstr($agent, "Windows 98")) $os = "Windows 98";
        else if (strstr($agent, "NT 4.0")) $os = "Windows NT ";
        else if (strstr($agent, "NT 5.1")) $os = "Windows XP";
        else if (strstr($agent, "NT 6.0")) $os = "Windows Vista";
        else if (strstr($agent, "NT 6.1")) $os = "Windows 7";
        else if (strstr($agent, "NT 6.2")) $os = "Windows 8";
        else if (strstr($agent, "NT 6.3")) $os = "Windows 8.1";
        else if (strstr($agent, "NT 6.4")) $os = "Windows 10";
        else if (strstr($agent, "NT 10.0")) $os = "Windows 11";
        else if (strstr($agent, "Win")) $os = "Windows";
        //Firefox
        else if (strstr($agent, "Mac OS X 10.5")) $os = "Mac OS X - Leopard";
        else if (strstr($agent, "Mac OS X 10.6")) $os = "Mac OS X - Snow Leopard";
        else if (strstr($agent, "Mac OS X 10.7")) $os = "Mac OS X - Lion";
        else if (strstr($agent, "Mac OS X 10.8")) $os = "Mac OS X - Mountain Lion";
        else if (strstr($agent, "Mac OS X 10.9")) $os = "Mac OS X - Mavericks";
        else if (strstr($agent, "Mac OS X 10.10")) $os = "Mac OS X - Yosemite";
        else if (strstr($agent, "Mac OS X 10.11")) $os = "Mac OS X - El Capitan";

        else if (strstr($agent, "Mac OS X 10.12")) $os = "macOS Sierra";
        else if (strstr($agent, "Mac OS X 10.13")) $os = "macOS High Sierra";
        else if (strstr($agent, "Mac OS X 10.14")) $os = "macOS Mojave";
        else if (strstr($agent, "Mac OS X 10.15")) $os = "macOS Catalina";
        else if (strstr($agent, "Mac OS X 11.6")) $os = "macOS Big Sur";

        //Chrome
        else if (strstr($agent, "Mac OS X 10_5")) $os = "Mac OS X - Leopard";
        else if (strstr($agent, "Mac OS X 10_6")) $os = "Mac OS X - Snow Leopard";
        else if (strstr($agent, "Mac OS X 10_7")) $os = "Mac OS X - Lion";
        else if (strstr($agent, "Mac OS X 10_8")) $os = "Mac OS X - Mountain Lion";
        else if (strstr($agent, "Mac OS X 10_9")) $os = "Mac OS X - Mavericks";
        else if (strstr($agent, "Mac OS X 10_10")) $os = "Mac OS X - Yosemite";
        else if (strstr($agent, "Mac OS X 10_11")) $os = "Mac OS X - El Capitan";

        else if (strstr($agent, "Mac OS X 10_12")) $os = "macOS Sierra";
        else if (strstr($agent, "Mac OS X 10_13")) $os = "macOS High Sierra";
        else if (strstr($agent, "Mac OS X 10_14")) $os = "macOS Mojave";
        else if (strstr($agent, "Mac OS X 10_15")) $os = "macOS Catalina";
        else if (strstr($agent, "Mac OS X 11_6")) $os = "macOS Big Sur";

        else if (strstr($agent, "Mac OS")) $os = "Mac OS X";
        else if (strstr($agent, "Linux")) $os = "Linux";
        else if (strstr($agent, "Unix")) $os = "Unix";
        else if (strstr($agent, "Ubuntu")) $os = "Ubuntu";

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $userInformation = [
            'user_agent'          => $agent,
            'user_os'             => $os,
            'user_remote_address' => $ip
        ];


        //Check if load cronjob exists
        /** @var $CronjobsTable CronjobsTable */
        $CronjobsTable = TableRegistry::getTableLocator()->get('Cronjobs');

        if (!$CronjobsTable->checkForCronjob('CpuLoad', 'Core')) {
            //Cron does not exists, so we create it
            $newCron = $CronjobsTable->newEntity([
                'task'     => 'CpuLoad',
                'plugin'   => 'Core',
                'interval' => 15,
                'enabled'  => 1
            ]);

            $CronjobsTable->save($newCron);
        }


        $Logo = new Logo();
        $this->set('logoHtmlPath', $Logo->getLogoForHtml());

        $this->set('interfaceInformation', $interfaceInformation);
        $this->set('processInformation', $processInformation);
        $this->set('renderGraph', $renderGraph);
        $this->set('cpuLoadHistoryInformation', $cpuLoadHistoryInformation);
        $this->set('currentCpuLoad', $currentCpuLoad);
        $this->set('serverInformation', $serverInformation);
        $this->set('memory', $memory);
        $this->set('diskUsage', $diskUsage);
        $this->set('gearmanStatus', $gearmanStatus);
        $this->set('emailInformation', $emailInformation);
        $this->set('userInformation', $userInformation);

        $this->viewBuilder()->setOption('serialize', [
            'interfaceInformation',
            'processInformation',
            'renderGraph',
            'cpuLoadHistoryInformation',
            'currentCpuLoad',
            'serverInformation',
            'memory',
            'diskUsage',
            'gearmanStatus',
            'emailInformation',
            'userInformation',
            'logoHtmlPath'
        ]);
    }

    public function testMail() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        try {
            $User = $this->getUser();
            $recipientAddress = $User->get('email');

            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $_systemsettings = $SystemsettingsTable->findAsArray();

            $Logo = new Logo();

            $Mailer = new Mailer();
            $Mailer->setFrom($_systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'], $_systemsettings['MONITORING']['MONITORING.FROM_NAME']);
            $Mailer->addTo($recipientAddress);
            $Mailer->setSubject(__('System test mail from ') . $_systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME']);
            $Mailer->setEmailFormat('both');
            $Mailer->setAttachments([
                'logo.png' => [
                    'file'      => $Logo->getSmallLogoPdfPath(),
                    'mimetype'  => 'image/png',
                    'contentId' => '100'
                ]
            ]);
            $Mailer->viewBuilder()
                ->setTemplate('test_mail')
                ->setVar('systemname', $_systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME']);

            $Mailer->deliver();

            $this->set('success', true);
            $this->set('message', __('Test mail send to: ') . $recipientAddress);
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        } catch (\Exception $ex) {
            $this->set('success', false);
            $this->set('message', __('An error occured while sending test mail: ') . $ex->getMessage());
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
        }
    }

    public function querylog() {
        //Only ship HTML template
    }

    /**
     * USED BY THE NEW ANGULAR FRONTEND !!
     * @return void
     */
    public function php_info() {
        $this->viewBuilder()->setLayout('default');
        //Only ship HTML template
    }
}
