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

namespace App\itnovum\openITCOCKPIT\Agent;

use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use DistributeModule\Model\Entity\SatelliteTask;
use DistributeModule\Model\Table\SatelliteTasksTable;
use itnovum\openITCOCKPIT\Core\System\Gearman;

class AgentSatelliteTasks {

    public function __construct() {
        if (!Plugin::isLoaded('DistributeModule')) {
            throw new MissingPluginException('DistributeModule is required');
        }
    }

    /**
     * This method will send a task to a Satellite System and blocks until it gets a result.
     * As soon as the result is available, the method will return the received data.
     *
     * @param string $task
     * @param int $satelliteId
     * @param array $NSTAOptions
     * @return mixed
     */
    public function sendRequestToSatelliteBlocking(string $task, int $satelliteId, array $NSTAOptions) {
        // Create a new record to SatelliteTasksTable
        /** @var SatelliteTasksTable $SatelliteTasksTable */
        $SatelliteTasksTable = TableRegistry::getTableLocator()->get('DistributeModule.SatelliteTasks');

        $task = $SatelliteTasksTable->newEntity([
            'satellite_id' => $satelliteId,
            'task'         => $task,
            'status'       => SatelliteTask::SatelliteTaskQueued
        ]);
        $SatelliteTasksTable->save($task);

        /** @var Gearman $GearmanClient */
        $GearmanClient = new Gearman();

        //Send a background job to the NSTA
        //The NSTA will put the result into the oitc_gearman queue, handled by the gearman_worker
        $NSTAOptions['Data']['TaskID'] = $task->id;
        $GearmanClient->doBackground('oitc_agent_sattx', json_encode($NSTAOptions));

        // Wait until the response is in the database (the data will be written to the database by the gearman_worker background process)
        // @todo Can we move this into the gearman_worker some how due to php's max_execution_time ?
        while (true) {
            $responseTask = $SatelliteTasksTable->find()
                ->where([
                    'id' => $task->id
                ])
                ->firstOrFail();

            // Update record in SatelliteTasksTable
            if ($responseTask->status == SatelliteTask::SatelliteTaskFinishedSuccessfully || $responseTask->status == SatelliteTask::SatelliteTaskFinishedError) {
                // Return the result
                return json_decode($responseTask->result, true);
            }

            // No result in database yet - recheck in 5 seconds
            sleep(5);
        }
    }

    /**
     * This method will send a task to a Satellite System but this function will not block and will not wait for the result.
     * It will return a task ID from the SatelliteTasksTable. You have to check manually if the result has arrived and process the data.
     *
     *
     * @param string $task
     * @param int $satelliteId
     * @param array $NSTAOptions
     * @return int
     */
    public function sendRequestToSatelliteNonBlocking(string $task, int $satelliteId, array $NSTAOptions) {
        /** @var SatelliteTasksTable $SatelliteTasksTable */
        $SatelliteTasksTable = TableRegistry::getTableLocator()->get('DistributeModule.SatelliteTasks');

        $task = $SatelliteTasksTable->newEntity([
            'satellite_id' => $satelliteId,
            'task'         => $task,
            'status'       => SatelliteTask::SatelliteTaskQueued
        ]);
        $SatelliteTasksTable->save($task);

        /** @var Gearman $GearmanClient */
        $GearmanClient = new Gearman();

        $NSTAOptions['Data']['TaskID'] = $task->id;
        $GearmanClient->doBackground('oitc_agent_sattx', json_encode($NSTAOptions));
        return $task->id;
    }

}

