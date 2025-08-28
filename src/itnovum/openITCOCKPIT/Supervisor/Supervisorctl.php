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

namespace App\itnovum\openITCOCKPIT\Supervisor;

use Cake\Log\Log;

/**
 * Wrapper class that has the goal to provide simmilar features such as `systemctl status nginx`
 * but via supervisor
 */
class Supervisorctl {

    /**
     * @param string $serviceName
     * @return bool|string
     * @throws ApiException
     */
    public function start(string $serviceName) {
        $SupervisorApi = $this->getSupervisorApiEndpointByServiceName($serviceName);
        return $SupervisorApi->startProcess($serviceName);
    }

    /**
     * @param string $serviceName
     * @return bool|string
     * @throws ApiException
     */
    public function stop(string $serviceName) {
        $SupervisorApi = $this->getSupervisorApiEndpointByServiceName($serviceName);
        return $SupervisorApi->stopProcess($serviceName);
    }

    /**
     * @param string $serviceName
     * @return bool|string
     * @throws ApiException
     */
    public function restart(string $serviceName) {
        $SupervisorApi = $this->getSupervisorApiEndpointByServiceName($serviceName);
        $SupervisorApi->stopProcess($serviceName);
        return $SupervisorApi->startProcess($serviceName);
    }

    /**
     * @param string $serviceName
     * @return array|string
     * @throws ApiException
     */
    public function status(string $serviceName) {
        $SupervisorApi = $this->getSupervisorApiEndpointByServiceName($serviceName);
        return $SupervisorApi->getProcessInfo($serviceName);
    }

    /**
     * @param string $serviceName
     * @return XMLRPCApi
     * @throws \RuntimeException
     */
    public function getSupervisorApiEndpointByServiceName(string $serviceName): XMLRPCApi {
        // If you edit this like, also make sure to edit
        // SupervisorCommand::buildOptionParser()

        $username = env('SUPERVISOR_USER', 'supervisord');
        $password = env('SUPERVISOR_PASSWORD', 'password');

        switch ($serviceName) {
            case 'naemon':
                // Tell the Naemon Container to start|stop|restart Naemon
                $url = sprintf(
                    'http://%s:%s/RPC2',
                    env('OITC_NAEMON_HOSTNAME', 'naemon'),
                    env('SUPERVISOR_PORT', 9001)
                );
                $SupervisorApi = new XMLRPCApi($username, $password, $url);
                break;

            case 'statusengine':
                // Tell the Naemon Container to start|stop|restart Naemon
                $url = sprintf(
                    'http://%s:%s/RPC2',
                    env('OITC_STATUSENGINE_WORKER_HOSTNAME', 'statusengine-worker'),
                    env('SUPERVISOR_PORT', 9001)
                );
                $SupervisorApi = new XMLRPCApi($username, $password, $url);
                break;

            default:
                // Service is running in the same container as openITCOCKPIT itslef
                $SupervisorApi = new XMLRPCApi($username, $password, 'http://127.0.0.1:9001/RPC2');
                break;
        }

        if (!isset($SupervisorApi) || !($SupervisorApi instanceof XMLRPCApi)) {
            throw new \RuntimeException('No SupervisorAPI endpoint found for given service_name');
        }

        return $SupervisorApi;
    }

    public function isRunning(string $serviceName): bool {
        try {
            $SupervisorApi = $this->getSupervisorApiEndpointByServiceName($serviceName);
            $result = $SupervisorApi->getProcessInfo($serviceName);
            if (isset($result['statename'])) {
                if($result['statename'] === 'STARTING' || $result['statename'] === 'RUNNING') {
                    // We consider STARTING as running is this case as the process itself is started
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return false;
    }

}

