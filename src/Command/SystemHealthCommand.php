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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Command;

use App\itnovum\openITCOCKPIT\Core\Permissions\MyRightsFactory;
use App\itnovum\openITCOCKPIT\Core\SatelliteHealthNotification;
use App\itnovum\openITCOCKPIT\Core\SystemHealthNotification;
use App\itnovum\openITCOCKPIT\Supervisor\Supervisorctl;
use App\Model\Table\SystemHealthUsersTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\System\Health\CpuLoad;
use itnovum\openITCOCKPIT\Core\System\Health\Disks;
use itnovum\openITCOCKPIT\Core\System\Health\MemoryUsage;

/**
 * SystemHealth command.
 */
class SystemHealthCommand extends Command implements CronjobInterface {

    private $state = 'unknown';
    private $satellites_state = 'unknown';

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $io->out('Fetch system health information...', 0);

        // Fetch latest SystemHealth Information
        $data = $this->fetchInformation();
        $this->sendHealthNotifications($data);

        // Save current states as previous states for the next run.
        // Must be called after sendHealthNotifications(), which determines the current states.
        $this->saveToCache($data);

        // Message OK to console.
        $io->success('   Ok');
        $io->hr();
    }

    /**
     * @return array
     */
    public function fetchInformation() {
        $data = [
            'isNagiosRunning'                 => false,
            'isNdoRunning'                    => false,
            'isStatusengineRunning'           => false,
            'isNpcdRunning'                   => false,
            'isOitcCmdRunning'                => false,
            'isSudoServerRunning'             => false, // Replaced by openitcockpit-websocket.service
            'isNstaRunning'                   => false,
            'isGearmanWorkerRunning'          => false,
            'isNdoInstalled'                  => false,
            'isStatusengineInstalled'         => true, //NDOUtils are not supported anymore
            'isStatusenginePerfdataProcessor' => true, //NPCD is not supported anymore
            'isDistributeModuleInstalled'     => false,
            'isPushNotificationRunning'       => false, // Replaced by openitcockpit-websocket.service
            'isNodeJsServerRunning'           => false,
            'isWebsocketServerRunning'        => false
        ];

        if (IS_CONTAINER) {
            // openITCOCKPIT is running inside a container like docker
            $Supervisorctl = new Supervisorctl();
            $data = [
                'isNagiosRunning'                 => $Supervisorctl->isRunning('naemon'),
                'isNdoRunning'                    => false,
                'isStatusengineRunning'           => $Supervisorctl->isRunning('statusengine'),
                'isNpcdRunning'                   => false,
                'isOitcCmdRunning'                => $Supervisorctl->isRunning('oitc_cmd'),
                'isSudoServerRunning'             => false, // ITC-3487 sudo_server got removed
                'isNstaRunning'                   => $Supervisorctl->isRunning('nsta'),
                'isGearmanWorkerRunning'          => $Supervisorctl->isRunning('gearman_worker'),
                'isNdoInstalled'                  => false,
                'isStatusengineInstalled'         => true, //NDOUtils are not supported anymore
                'isStatusenginePerfdataProcessor' => true, //NPCD is not supported anymore
                'isDistributeModuleInstalled'     => false,
                'isPushNotificationRunning'       => false, // ITC-3487 push_notification got removed
                'isNodeJsServerRunning'           => $Supervisorctl->isRunning('openitcockpit-node'),
                'isWebsocketServerRunning'        => $Supervisorctl->isRunning('openitcockpit-websocket')
            ];
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsetting = $SystemsettingsTable->findAsArray();

        /****** Cpu Load ******/
        $CpuLoad = new CpuLoad();

        $cpuLoadWarning = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.CPU.LOAD_WARNING']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.CPU.LOAD_WARNING'] : null;
        $cpuLoadCritical = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.CPU.LOAD_CRITICAL']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.CPU.LOAD_CRITICAL'] : null;

        $data['load'] = $CpuLoad->getLoadForSystemHealth($cpuLoadWarning, $cpuLoadCritical);

        /****** Disks ******/
        $Disks = new Disks();
        $diskWarningInPercent = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.DISK.WARNING_IN_PERCENT']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.DISK.WARNING_IN_PERCENT'] : null;
        $diskCriticalInPercent = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.DISK.CRITICAL_IN_PERCENT']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.DISK.CRITICAL_IN_PERCENT'] : null;
        $diskExcludeRegex = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.DISK.EXCLUDE_REGEX']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.DISK.EXCLUDE_REGEX'] : null;
        $data['disk_usage'] = $Disks->getDiskUsage($diskWarningInPercent, $diskCriticalInPercent, $diskExcludeRegex);

        /****** Memory ******/
        $MemoryUsage = new MemoryUsage();

        $memoryWarningInPercent = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.RAM.WARNING_IN_PERCENT']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.RAM.WARNING_IN_PERCENT'] : null;
        $memoryCriticalInPercent = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.RAM.CRITICAL_IN_PERCENT']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.RAM.CRITICAL_IN_PERCENT'] : null;
        $swapWarningInPercent = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.SWAP.WARNING_IN_PERCENT']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.SWAP.WARNING_IN_PERCENT'] : null;
        $swapCriticalInPercent = !empty($systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.SWAP.CRITICAL_IN_PERCENT']) ? $systemsetting['SYSTEM_HEALTH']['SYSTEM_HEALTH.SWAP.CRITICAL_IN_PERCENT'] : null;


        $data['memory_usage'] = $MemoryUsage->getMemoryUsage(
            $memoryWarningInPercent,
            $memoryCriticalInPercent,
            $swapWarningInPercent,
            $swapCriticalInPercent
        );


        $errorRedirect = ' 2> /dev/null';

        if (IS_CONTAINER === false) {
            // Normal installation of openITCOCKPIT via apt, dnf or git
            exec($systemsetting['MONITORING']['MONITORING.STATUS'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isNagiosRunning'] = true;
            }

            exec($systemsetting['INIT']['INIT.NDO_STATUS'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isNdoRunning'] = true;
            }

            exec($systemsetting['INIT']['INIT.STATUSENIGNE_STATUS'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isStatusengineRunning'] = true;
            }

            exec($systemsetting['INIT']['INIT.NPCD_STATUS'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isNpcdRunning'] = true;
            }

            exec($systemsetting['INIT']['INIT.OITC_CMD_STATUS'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isOitcCmdRunning'] = true;
            }

            $data['isSudoServerRunning'] = false; // ITC-3487 sudo_server got removed

            exec($systemsetting['INIT']['INIT.NSTA_STATUS'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isNstaRunning'] = true;
            }

            $data['isPushNotificationRunning'] = false; // ITC-3487 push_notification got removed

            exec('systemctl status openitcockpit-websocket.service' . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isWebsocketServerRunning'] = true;
            }

            exec($systemsetting['INIT']['INIT.OPENITCOCKPIT_NODE'] . $errorRedirect, $output, $returncode);
            if ($returncode == 0) {
                $data['isNodeJsServerRunning'] = true;
            }

            if (file_exists('/opt/openitc/nagios/bin/ndo2db')) {
                $data['isNdoInstalled'] = true;
            }

            //if (file_exists('/opt/openitc/statusengine2/cakephp/app/Console/Command/StatusengineLegacyShell.php')) {
            //    $data['isStatusengineInstalled'] = true;
            //}

            //$statusengineConfig = '/opt/openitc/statusengine2/cakephp/app/Config/Statusengine.php';
            //if (file_exists($statusengineConfig)) {
            //    require_once $statusengineConfig;
            //    if (isset($config['process_perfdata'])) {
            //        if ($config['process_perfdata'] === true) {
            //            $data['isStatusenginePerfdataProcessor'] = true;
            //        }
            //    }
            //}
        }

        if (Plugin::isLoaded('DistributeModule')) {
            $data['isDistributeModuleInstalled'] = true;
            // @var SatellitesTable $SatellitesTable
            $data['satellites'] = $this->getSatellitesStatusWithHealth($systemsetting['SYSTEM_HEALTH'] ?? []);
        }

        return $data;
    }

    public function getSatellitesStatusWithHealth(array $systemHealthSettings): array {

        $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
        $query = $SatellitesTable->unhydratedFind('all');
        $satellitesArray = $query->select([
            'Satellites.id',
            'Satellites.name',
            'Satellites.description',
            'Satellites.address',
            'Satellites.container_id',
            'Satellites.timezone',
            'Satellites.sync_method',
            'SatelliteStatus.status',
            'SatelliteStatus.last_error',
            'SatelliteStatus.last_export',
            'SatelliteStatus.last_seen',
            'SatelliteStatus.satellite_id',
            'status' => $query->expr('IF(SatelliteStatus.status IS NULL, 0, SatelliteStatus.status)')
        ])
            ->contain(['SatelliteStatus'])
            ->orderBy(['Satellites.id'])
            ->toArray();

        if (empty($satellitesArray)) {
            return [];
        }
        $satelliteIds = array_column($satellitesArray, 'id');
        $SatelliteInformationTable = TableRegistry::getTableLocator()->get('DistributeModule.SatelliteInformation');
        $updatedSatellites = [];

        $systemHealthBySatelliteId = $SatelliteInformationTable->unhydratedFind()
            ->select(['satellite_id', 'system_health'])
            ->where(['satellite_id IN' => $satelliteIds])
            ->all()
            ->combine('satellite_id', function ($row) {
                if (is_string($row['system_health'])) {
                    $decoded = json_decode($row['system_health'], true);
                    if ($decoded !== null) {
                        return $decoded;
                    }
                }
                return $row['system_health'];
            })
            ->toArray();

        $thresholds = $this->getSatelliteThresholds($systemHealthSettings);

        foreach ($satellitesArray as $satellite) {
            if (!isset($satellite['satellite_status'])) {
                continue;
            }

            $parsedHealth = $systemHealthBySatelliteId[$satellite['id']] ?? null;

            if (!empty($parsedHealth) && is_array($parsedHealth)) {
                // CPU
                $cpu_cores = (int)$parsedHealth['cpu_cores'];
                /*
                 * ITC-3886
                 * The old "cores minus 2" rule was too aggressive on small systems and
                 * scaled poorly on larger ones. Using proportional thresholds keeps the
                 * warning/critical levels consistent across different host sizes.
                 */
                if ($cpu_cores > 0) {
                    $loadPercentage = ((float)$parsedHealth['cpu_load15'] / $cpu_cores) * 100;
                    $parsedHealth['cpu_state'] = $this->getStateByPercentage($loadPercentage, $thresholds['cpu']['warning'], $thresholds['cpu']['critical']);
                }

                // RAM
                if (isset($parsedHealth['memory']['memory']['percentage'])) {
                    $parsedHealth['memory']['memory']['state'] = $this->getStateByPercentage($parsedHealth['memory']['memory']['percentage'], $thresholds['ram']['warning'], $thresholds['ram']['critical']);
                }

                // SWAP
                if (isset($parsedHealth['memory']['swap']['percentage'])) {
                    $parsedHealth['memory']['swap']['state'] = $this->getStateByPercentage($parsedHealth['memory']['swap']['percentage'], $thresholds['swap']['warning'], $thresholds['swap']['critical']);
                }

                // DISKS
                if (!empty($parsedHealth['disks']) && is_array($parsedHealth['disks'])) {
                    $disksWithState = [];
                    foreach ($parsedHealth['disks'] as $disk) {
                        if ($thresholds['exclude_regex'] !== null && (preg_match($thresholds['exclude_regex'], $disk['disk'] ?? '') === 1 || preg_match($thresholds['exclude_regex'], $disk['mountpoint'] ?? '') === 1)) {
                            continue;
                        }
                        $disk['state'] = $this->getStateByPercentage($disk['use_percentage'], $thresholds['disk']['warning'], $thresholds['disk']['critical']);
                        $disksWithState[] = $disk;
                    }
                    $parsedHealth['disks'] = $disksWithState;
                }

                $satellite['satellite_information'] = [
                    'satellite_id'  => $satellite['id'],
                    'system_health' => $parsedHealth
                ];
            }
            $updatedSatellites[] = $satellite;
        }

        return $updatedSatellites;

    }

    /**
     * I will determine the system and satellites state and send the SystemHealthNotification
     * and SatelliteHealthNotifications if the respective state has changed.
     *
     * @param array $data Result of fetchInformation()
     * @return void
     */
    public function sendHealthNotifications(array $data): void {
        // Determine system and satellites state and enrich the data for the email
        $dataForEmail = $this->determineState($data);

        // Compare with the states of the previous run BEFORE the cache gets overwritten
        $sendSystemHealthMail = $this->checkSendingSystemHealthMail();
        $sendSatelliteHealthMail = $this->checkSendingSatelliteHealthMail();

        // Maybe send SystemHealth
        if ($sendSystemHealthMail) {
            $this->sendSystemHealthNotification($dataForEmail);
        } else {
            $this->io->verbose('Not sending SystemHealthNotification: system state has not changed');
        }

        // Maybe send SatelliteHealth
        if (Plugin::isLoaded('DistributeModule')) {
            if ($sendSatelliteHealthMail) {
                $this->sendSatelliteHealthNotifications($dataForEmail['satellites'] ?? []);
            } else {
                $this->io->verbose('Not sending SatelliteHealthNotification: satellites state has not changed');
            }
        }
    }

    /**
     * I will send the SystemHealthNotification to all users subscribed to the current system state.
     *
     * @param array $data Result of determineState()
     * @return void
     */
    private function sendSystemHealthNotification(array $data): void {
        $flags = $this->getNotificationFlagsByState($this->state);
        if ($flags === null) {
            $this->io->verbose(sprintf('Not sending SystemHealthNotification: no notifications for state "%s"', $this->state));
            return;
        }

        /** @var SystemHealthUsersTable $SystemHealthUsersTable */
        $SystemHealthUsersTable = TableRegistry::getTableLocator()->get('SystemHealthUsers');
        $users = $SystemHealthUsersTable->getUsersForNotifications(...$flags);

        $systemHealthNotification = new SystemHealthNotification($users, $this->state);
        $systemHealthNotification->setData($data);
        $systemHealthNotification->sendNotification();
    }

    /**
     * I will send one SatelliteHealthNotification per user, containing only the satellites
     * the user has container permissions for.
     *
     * @param array $satellites Result of getSatellitesStatusWithHealth()
     * @return void
     */
    private function sendSatelliteHealthNotifications(array $satellites): void {
        $flags = $this->getNotificationFlagsByState($this->satellites_state);
        if ($flags === null) {
            $this->io->verbose(sprintf('Not sending SatelliteHealthNotification: no notifications for state "%s"', $this->satellites_state));
            return;
        }

        // On recovery, all satellites of the user get reported (they are all OK now).
        // Otherwise only satellites with a problem get reported.
        $isRecovery = strtolower($this->satellites_state) === 'ok';

        foreach ($satellites as $index => $satellite) {
            $satellites[$index]['state'] = $this->getStateOfSatellite($satellite);
        }

        /** @var SystemHealthUsersTable $SystemHealthUsersTable */
        $SystemHealthUsersTable = TableRegistry::getTableLocator()->get('SystemHealthUsers');
        $users = $SystemHealthUsersTable->getUsersWithIdsForNotifications(...$flags);

        foreach ($users as $user) {
            $this->io->verbose('Composing SatelliteHealthNotification to user: ' . $user['email']);

            $userPermissions = MyRightsFactory::getUserPermissions($user['id'], $user['usergroup_id']);
            $SatelliteHealthNotification = new SatelliteHealthNotification($user['email'], $user['full_name'], $this->io);

            $stateForUser = 'unknown';
            foreach ($satellites as $satellite) {
                if (!$this->isSatelliteAllowedForUser($satellite, $userPermissions)) {
                    $this->io->verbose("User {$user['email']} does not have permission to see satellite {$satellite['name']} (ID: {$satellite['id']}), skipping.");
                    continue;
                }

                if (!$isRecovery && !in_array($satellite['state'], ['warning', 'critical'], true)) {
                    $this->io->verbose("Satellite {$satellite['name']} (ID: {$satellite['id']}) is in state {$satellite['state']}, skipping for user {$user['email']}.");
                    continue;
                }

                $SatelliteHealthNotification->addSatelliteState($satellite);
                $stateForUser = $this->mergeState($stateForUser, $satellite['state']);
            }

            $SatelliteHealthNotification->setState($stateForUser);
            $SatelliteHealthNotification->sendNotification();
        }
    }

    /**
     * I will check whether the user may see the given satellite (container check).
     * Same logic as SatellitesController::index() (read permission on the satellite container is enough).
     *
     * @param array $satellite
     * @param array $userPermissions Result of MyRightsFactory::getUserPermissions()
     * @return bool
     */
    private function isSatelliteAllowedForUser(array $satellite, array $userPermissions): bool {
        if ($userPermissions['hasRootPrivileges'] === true) {
            return true;
        }

        if (!isset($satellite['container_id'])) {
            return false;
        }

        return in_array((int)$satellite['container_id'], array_map('intval', $userPermissions['MY_RIGHTS']), true);
    }

    /**
     * I will return the arguments for SystemHealthUsersTable::getUsersForNotifications()
     * [notify_on_warning, notify_on_critical, notify_on_recovery] or null if no notification should be sent.
     *
     * @param string $state
     * @return int[]|null
     */
    private function getNotificationFlagsByState(string $state): ?array {
        return match (strtoupper($state)) {
            'OK' => [0, 0, 1],
            'WARNING' => [1, 0, 0],
            'CRITICAL' => [0, 1, 0],
            default => null,
        };
    }

    /**
     * I will compare the current system state with the state of the previous run (cache).
     *
     * @return bool true if the system state has changed
     */
    private function checkSendingSystemHealthMail(): bool {
        $cache = Cache::read('system_health', 'permissions');

        return !empty($cache) && !empty($cache['previousState']) && $cache['previousState'] !== $this->state;
    }

    /**
     * I will compare the current satellites state with the state of the previous run (cache).
     *
     * @return bool true if the satellites state has changed
     */
    private function checkSendingSatelliteHealthMail(): bool {
        $cache = Cache::read('system_health', 'permissions');

        return !empty($cache) && !empty($cache['previousSatellitesState']) && $cache['previousSatellitesState'] !== $this->satellites_state;
    }

    /**
     * I will calculate the system state and the satellites state and enrich the data with both states.
     *
     * @param array $data Result of fetchInformation()
     * @return array
     */
    public function determineState($data) {

        $dataForEmail = $data;

        $dataForEmail['cache_readable'] = true;
        $dataForEmail['gearman_reachable'] = false;
        $dataForEmail['gearman_worker_running'] = false;

        $GearmanClient = new Gearman();
        $GearmanClient->setTimeout(5000);
        $dataForEmail['gearman_reachable'] = $GearmanClient->ping();

        // replacement of ps -eaf because it takes ps too long to display the username in an LDAP based setup
        // https://www.ibm.com/support/pages/apar/IJ08995
        // we have no need for the username, so we can use the faster ps -eo command
        exec('ps -eo command |grep gearman_worker |grep -v \'mod_gearman_worker\' |grep -v \'grep\'', $output);
        $dataForEmail['gearman_worker_running'] = sizeof($output) > 0;
        if (!$dataForEmail['gearman_worker_running']) {
            $this->setHealthState('critical');
        }

        if (!$dataForEmail['isNagiosRunning']) {
            $this->setHealthState('critical');
        }

        if (!$dataForEmail['isOitcCmdRunning']) {
            $this->setHealthState('warning');
        }

        if (!$dataForEmail['isWebsocketServerRunning']) {
            $this->setHealthState('warning');
        }

        if (!$dataForEmail['gearman_reachable'] || !$dataForEmail['gearman_worker_running']) {
            $this->setHealthState('critical');
        }

        if ($dataForEmail['isStatusengineInstalled'] && !$dataForEmail['isStatusengineRunning']) {
            $this->setHealthState('critical');
        }

        if ($dataForEmail['isStatusenginePerfdataProcessor'] && !$dataForEmail['isStatusengineRunning']) {
            $this->setHealthState('critical');
        }

        if (!$dataForEmail['isStatusenginePerfdataProcessor'] && !$dataForEmail['isNpcdRunning']) {
            $this->setHealthState('critical');
        }

        if ($dataForEmail['isDistributeModuleInstalled'] && !$dataForEmail['isNstaRunning']) {
            $this->setHealthState('warning');
        }

        foreach ($dataForEmail['satellites'] ?? [] as $satellite) {
            foreach ($this->getStatesOfSatellite($satellite) as $satelliteState) {
                $this->setSatellitesHealthState($satelliteState);
            }
        }

        $this->setHealthState($dataForEmail['memory_usage']['memory']['state']);
        $this->setHealthState($dataForEmail['memory_usage']['swap']['state']);
        $this->setHealthState($dataForEmail['load']['state']);
        foreach ($dataForEmail['disk_usage'] as $disk) {
            $this->setHealthState($disk['state']);
        }

        $dataForEmail['state'] = $this->state;
        $dataForEmail['satellites_state'] = $this->satellites_state;

        return $dataForEmail;

    }

    /**
     * I will return all single states (sync status, RAM, Swap, Disks, CPU) of one satellite.
     *
     * @param array $satellite
     * @return string[]
     */
    private function getStatesOfSatellite(array $satellite): array {
        $states = [];

        if (isset($satellite['status'])) {
            $satellite_status = "";
            if (is_numeric($satellite['status'])) {
                $satellite_status = $this->getSatellitesState((int)$satellite['status']);
            } else if (is_string($satellite['status'])) {
                $satellite_status = $satellite['status'];
            }
            $states[] = $satellite_status;
        }

        $systemHealth = $satellite['satellite_information']['system_health'] ?? [];
        if (empty($systemHealth) || !is_array($systemHealth)) {
            return $states;
        }

        // RAM
        if (isset($systemHealth['memory']['memory']['state'])) {
            $states[] = $systemHealth['memory']['memory']['state'];
        }
        if (isset($systemHealth['memory']['swap']['state'])) {
            $states[] = $systemHealth['memory']['swap']['state'];
        }
        // Disks
        if (!empty($systemHealth['disks']) && is_array($systemHealth['disks'])) {
            foreach ($systemHealth['disks'] as $disk) {
                if (isset($disk['state'])) {
                    $states[] = $disk['state'];
                }
            }
        }

        //CPU
        if (isset($systemHealth['cpu_cores'], $systemHealth['cpu_load15'], $systemHealth['cpu_state'])) {
            $states[] = $systemHealth['cpu_state'];
        }

        return $states;
    }

    /**
     * I will return the combined state of one satellite (ok, warning, critical or unknown).
     *
     * @param array $satellite
     * @return string
     */
    private function getStateOfSatellite(array $satellite): string {
        $state = 'unknown';
        foreach ($this->getStatesOfSatellite($satellite) as $singleState) {
            $state = $this->mergeState($state, $singleState);
        }
        return $state;
    }

    /**
     * I will merge two states. critical can not be overwritten, warning can only be overwritten by critical.
     *
     * @param string $currentState
     * @param string $newState
     * @return string
     */
    private function mergeState(string $currentState, string $newState): string {
        //Do not overwrite critical with ok or warning
        if ($currentState === 'critical') {
            return $currentState;
        }

        //Do not overwrite warning with ok
        if ($currentState === 'warning' && $newState !== 'critical') {
            return $currentState;
        }

        return $newState;
    }

    /**
     * I will merge the given state into the system state.
     *
     * @param string $state
     * @return void
     */
    private function setHealthState($state) {
        $this->state = $this->mergeState((string)$this->state, (string)$state);
    }

    /**
     * I will merge the given state into the satellites state.
     *
     * @param string $satellites_state
     * @return void
     */
    private function setSatellitesHealthState($satellites_state) {
        $this->satellites_state = $this->mergeState((string)$this->satellites_state, (string)$satellites_state);
    }

    public function saveToCache($data) {
        $data['previousState'] = $this->state;
        $data['previousSatellitesState'] = $this->satellites_state;

        $data['update'] = time();

        $redisHost = env('OITC_REDIS_HOST', '127.0.0.1');
        $redisPort = filter_var(env('OITC_REDIS_PORT', 6379), FILTER_VALIDATE_INT);

        $Redis = new \Redis();
        $Redis->connect($redisHost, $redisPort);
        $Redis->setex('permissions_system_health', 60 * 3, serialize($data));
    }

    private function getSatellitesState($satellites_state): string {
        if (!isset($satellites_state)) {
            return 'unknown';
        }
        return match ($satellites_state) {
            1 => 'ok',
            2, 3 => 'critical',//2 => 'warning'
            default => 'unknown',
        };
    }

    private function getSatelliteThresholds(array $settings): array {
        return [
            'cpu'           => [
                'warning'  => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_CPU_LOAD_WARNING_IN_PERCENT'] ?? null, 75),
                'critical' => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_CPU_LOAD_CRITICAL_IN_PERCENT'] ?? null, 90),
            ],
            'ram'           => [
                'warning'  => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_RAM_WARNING_IN_PERCENT'] ?? null, 75),
                'critical' => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_RAM_CRITICAL_IN_PERCENT'] ?? null, 90),
            ],
            'swap'          => [
                'warning'  => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_SWAP_WARNING_IN_PERCENT'] ?? null, 75),
                'critical' => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_SWAP_CRITICAL_IN_PERCENT'] ?? null, 90),
            ],
            'disk'          => [
                'warning'  => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_DISK_WARNING_IN_PERCENT'] ?? null, 75),
                'critical' => $this->getPercentSetting($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_DISK_CRITICAL_IN_PERCENT'] ?? null, 90),
            ],
            'exclude_regex' => $this->sanitizeRegex($settings['SYSTEM_HEALTH.SATELLITES_THRESHOLD_DISK_EXCLUDE_REGEX'] ?? null)
        ];
    }

    private function getPercentSetting($value, $default): float {
        return (is_numeric($value) && $value > 0) ? (float)$value : (float)$default;
    }

    private function getStateByPercentage($percentage, $warningInPercent, $criticalInPercent): string {
        $state = 'ok';
        if ($percentage >= $warningInPercent) {
            $state = 'warning';
        }
        if ($percentage >= $criticalInPercent) {
            $state = 'critical';
        }
        return $state;
    }

    /**
     * @param string|null $diskExcludeRegex
     * @return string|null
     */
    private function sanitizeRegex(?string $diskExcludeRegex): ?string {
        if (!$diskExcludeRegex || trim($diskExcludeRegex) === '') {
            return null;
        }

        $pattern = '`' . trim($diskExcludeRegex) . '`';
        if (@preg_match($pattern, '') === false) {
            return null;
        }
        return $pattern;
    }

}
