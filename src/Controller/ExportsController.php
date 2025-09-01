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

use App\Model\Entity\Changelog;
use App\Model\Entity\Export;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ExportsTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Core\Plugin;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use DistributeModule\Model\Entity\Satellite;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;

class ExportsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var ExportsTable $ExportsTable */
        $ExportsTable = TableRegistry::getTableLocator()->get('Exports');
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $useSingleInstanceSync = false;
        $satellites = [];
        if (Plugin::isLoaded('DistributeModule')) {
            $result = $SystemsettingsTable->getSystemsettingByKey('MONITORING.SINGLE_INSTANCE_SYNC');
            $useSingleInstanceSync = $result->get('value') === '1';

            /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
            $satellitesEntities = $SatellitesTable->getSatellitesForExportIndex($this->MY_RIGHTS);

            foreach ($satellitesEntities as $satellitesEntity) {
                /** @var Satellite $satellitesEntity */
                $satellites[] = $satellitesEntity->toAngularArray($UserTime);
            }

        }

        $GearmanClient = new Gearman();
        $gearmanReachable = $GearmanClient->ping();
        $exportRunning = false;

        $task = $ExportsTable->find()
            ->where([
                'task' => 'export_started'
            ])
            ->first();

        if (!empty($task)) {
            $exportRunning = $task->get('finished') == 0;
        }

        $tasks = [];
        if ($exportRunning) {
            foreach ($ExportsTable->getCurrentExportState() as $taskEntity) {
                /** @var Export $taskEntity */
                $task = $taskEntity->toArray();
                $task['created'] = $UserTime->customFormat('H:i:s', $taskEntity->get('created')->getTimestamp());
                $task['modified'] = $UserTime->customFormat('H:i:s', $taskEntity->get('modified')->getTimestamp());
                $tasks[] = $task;
            }
        }

        $isGearmanWorkerRunning = false;
        // replacement of ps -eaf because it takes ps too long to display the username in an LDAP based setup
        // https://www.ibm.com/support/pages/apar/IJ08995
        // we have no need for the username, so we can use the faster ps -eo command
        exec('ps -eo command |grep gearman_worker |grep -v \'mod_gearman_worker\' |grep -v \'grep\'', $output);
        if (sizeof($output) > 0) {
            $isGearmanWorkerRunning = true;
        }

        $this->set('gearmanReachable', $gearmanReachable);
        $this->set('isGearmanWorkerRunning', $isGearmanWorkerRunning);
        $this->set('exportRunning', $exportRunning);
        $this->set('tasks', $tasks);
        $this->set('useSingleInstanceSync', $useSingleInstanceSync);
        $this->set('satellites', $satellites);
        $this->viewBuilder()->setOption('serialize', [
            'gearmanReachable',
            'isGearmanWorkerRunning',
            'exportRunning',
            'tasks',
            'useSingleInstanceSync',
            'satellites'
        ]);
    }

    public function broadcast() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ExportsTable $ExportsTable */
        $ExportsTable = TableRegistry::getTableLocator()->get('Exports');

        $tasks = [];
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $exportFinished = false;
        $exportSuccessfully = false;
        foreach ($ExportsTable->getCurrentExportState() as $taskEntity) {
            /** @var Export $taskEntity */
            $task = $taskEntity->toArray();
            $task['created'] = $UserTime->customFormat('H:i:s', $taskEntity->get('created')->getTimestamp());
            $task['modified'] = $UserTime->customFormat('H:i:s', $taskEntity->get('modified')->getTimestamp());
            $tasks[] = $task;

            if ($task['task'] == 'export_finished' && $task['finished'] === 1) {
                $exportFinished = true;
                $exportSuccessfully = (bool)$task['successfully'];
            }
        }

        $this->set('successMessage', __('Refresh of monitoring configuration successfully!'));
        $this->set('tasks', $tasks);
        $this->set('exportFinished', $exportFinished);
        $this->set('exportSuccessfully', $exportSuccessfully);
        $this->viewBuilder()->setOption('serialize', [
            'tasks',
            'exportFinished',
            'exportSuccessfully',
            'successMessage'
        ]);
    }

    public function launchExport() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var ExportsTable $ExportsTable */
        $ExportsTable = TableRegistry::getTableLocator()->get('Exports');

        if (Plugin::isLoaded('DistributeModule')) {
            /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

            foreach ($this->request->getData('satellites', []) as $satellite) {
                try {
                    $entity = $SatellitesTable->get($satellite['id']);
                    $entity->set('sync_instance', (int)$satellite['sync_instance']);
                    $SatellitesTable->save($entity);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }


        $exportRunning = false;
        $task = $ExportsTable->find()
            ->where([
                'task' => 'export_started'
            ])
            ->first();

        if (!empty($task)) {
            $exportRunning = $task->get('finished') == 0;
        }

        if ($exportRunning === true) {
            throw new \RuntimeException('Export already running!');
        }

        //Remove old records from DB
        $ExportsTable->deleteAll([]);

        $GearmanClient = new Gearman();

        if (!$GearmanClient->ping()) {
            throw new \Exception('Could not connect to Gearman Job Server');
        }

        $createBackup = (int)$this->request->getData('create_backup', 1);
        $GearmanClient->sendBackground('export_start_export', ['backup' => (int)$createBackup]);

        $User = new User($this->getUser());
        /** @var  ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'export',
            'exports',
            1,
            OBJECT_EXPORT,
            ROOT_CONTAINER,
            $User->getId(),
            __('Refresh of monitoring configuration started'),
            []
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function saveInstanceConfigSyncSelection() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        if (!Plugin::isLoaded('DistributeModule')) {
            $this->set('success', false);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
        $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

        foreach ($this->request->getData('satellites', []) as $satellite) {
            try {
                $entity = $SatellitesTable->get($satellite['id']);
                $entity->set('sync_instance', (int)$satellite['sync_instance']);
                $SatellitesTable->save($entity);
            } catch (\Exception $e) {
                continue;
            }
        }

        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    /**
     * @throws \Exception
     */
    public function verifyConfig() {
        if (!$this->request->is('post')) {
            //throw new MethodNotAllowedException();
        }

        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $GearmanClient = new Gearman();

        if (!$GearmanClient->ping()) {
            throw new \Exception('Could not connect to Gearman Job Server');
        }

        $nagiosResult = $GearmanClient->send('export_verify_config');
        $output = [
            'nagios'     => [
                'hasError' => false,
                'output'   => []
            ],
            'prometheus' => [
                'hasError' => false,
                'output'   => []
            ]
        ];
        if (isset($nagiosResult['output']) && isset($nagiosResult['returncode'])) {
            $output['nagios']['hasError'] = $nagiosResult['returncode'] > 0;
            $output['nagios']['output'] = $nagiosResult['output'];
        }

        if (Plugin::isLoaded('PrometheusModule')) {
            $promResult = $GearmanClient->send('export_verify_prometheus_config');
            if (isset($promResult['output']) && isset($promResult['returncode'])) {
                $output['prometheus']['hasError'] = $promResult['returncode'] > 0;
                $output['prometheus']['output'] = $promResult['output'];
            }
        }

        $this->set('result', $output);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }
}
