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

use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\openITCOCKPIT_AvailableVersion;
use App\Model\Table\ContactsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\DocumentationsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\MessagesOtdTable;
use App\Model\Table\RegistersTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DateTime;
use DateTimeZone;
use Exception;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\CustomMacroReplacer;
use itnovum\openITCOCKPIT\Core\HostMacroReplacer;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Menu\Menu;
use itnovum\openITCOCKPIT\Core\Merger\HostMergerForBrowser;
use itnovum\openITCOCKPIT\Core\Merger\ServiceMergerForBrowser;
use itnovum\openITCOCKPIT\Core\ServiceMacroReplacer;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\HostAndServiceSummaryIcon;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\PieChart;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Monitoring\QueryHandler;
use Laminas\Diactoros\CallbackStream;
use RuntimeException;

/**
 * Class AngularController
 * @package App\Controller
 */
class AngularController extends AppController {

    private $state = 'unknown';

    private $errorCount = 0;

    /**
     * @throws Exception
     */
    public function user_timezone() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $session = $this->request->getSession();
        $session->close();

        $user = $this->getUser();
        $userTimezone = $user->get('timezone');
        if (strlen($userTimezone) < 2) {
            $userTimezone = 'Europe/Berlin';
        }

        $browserTimezone = (string)$this->request->getQuery('browserTimezone', '');
        if (strlen($browserTimezone) < 2) {
            $browserTimezone = 'Europe/Berlin';
        }

        $UserTime = new DateTime($userTimezone);
        $ServerTime = new DateTime();
        $ServerTimeZone = new DateTimeZone($ServerTime->getTimezone()->getName());
        $timezone = [
            'user_timezone'               => $userTimezone,
            'user_time_to_server_offset'  => $this->getTimeZoneOffset($ServerTimeZone->getName(), $userTimezone),
            'user_time_to_browser_offset' => $this->getTimeZoneOffset($browserTimezone, $userTimezone),
            'browser_timezone'            => $browserTimezone,
            'user_offset'                 => $UserTime->getOffset(),
            'server_time_utc'             => time(),
            'server_time'                 => date('F d, Y H:i:s'),
            'server_timezone_offset'      => $ServerTime->getOffset(),
            //ISO 8601
            'server_time_iso'             => date('c'),
            'server_timezone'             => $ServerTimeZone->getName()
        ];
        $this->set('timezone', $timezone);
        $this->viewBuilder()->setOption('serialize', ['timezone']);
    }

    /**
     * For the given $remote and $origin (server timezone), I will return the offset in seconds.
     * @param string $remote
     * @param string $origin
     * @return bool|int
     * @throws Exception
     */
    private function getTimeZoneOffset(string $remote, string $origin = '') {
        if (($origin === '') && !is_string($origin = date_default_timezone_get())) {
            return false; // A UTC timestamp was returned -- bail out!
        }
        $originTimeZone = new DateTimeZone($origin);
        $remoteTimeZone = new DateTimeZone($remote);
        $originDateTime = new DateTime("now", $originTimeZone);
        $remoteDateTime = new DateTime("now", $remoteTimeZone);

        return $originTimeZone->getOffset($originDateTime) - $remoteTimeZone->getOffset($remoteDateTime);
    }

    public function version_check() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $path = APP . 'Lib' . DS . 'openITCOCKPIT_AvailableVersion.php';
        $availableVersion = '???';
        if (file_exists($path)) {
            $availableVersion = openITCOCKPIT_AvailableVersion::get();
        }
        $newVersionAvailable = false;
        if (version_compare($availableVersion, OPENITCOCKPIT_VERSION) > 0 && $this->hasRootPrivileges) {
            $newVersionAvailable = true;
        }
        $this->set('newVersionAvailable', $newVersionAvailable);
        $this->viewBuilder()->setOption('serialize', ['newVersionAvailable']);
    }

    public function message_of_the_day() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }
        $messageOtdAvailable = false;
        $User = new User($this->getUser());

        /** @var MessagesOtdTable $MessagesOtdTable */
        $MessagesOtdTable = TableRegistry::getTableLocator()->get('MessagesOtd');
        $messageOtd = $MessagesOtdTable->getMessageOtdForToday(
            $User->getTimezone(),
            $this->getUser()->get('usergroup_id')
        );
        $showMessageAfterLogin = false;

        if (!empty($messageOtd)) {
            $messageOtdAvailable = true;
            $session = $this->request->getSession();
            if (!$session->check('MessageOtd.showMessage')) {
                $session->write('MessageOtd.showMessage', true);
                $showMessageAfterLogin = true;
            }
        }

        $this->set('messageOtdAvailable', $messageOtdAvailable);
        $this->set('messageOtd', $messageOtd);
        $this->set('showMessageAfterLogin', $showMessageAfterLogin);
        $this->viewBuilder()->setOption('serialize', [
            'messageOtdAvailable',
            'messageOtd',
            'showMessageAfterLogin'
        ]);
    }

    public function menustats() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }


        $user = $this->getUser();
        $showstatsinmenu = (bool)$user->get('showstatsinmenu');

        $hoststatusCount = [
            '1' => 0,
            '2' => 0,
        ];
        $servicestatusCount = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
        ];

        if ($showstatsinmenu) {

            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            if ($this->DbBackend->isNdoUtils()) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                $hoststatusCount = $HostsTable->getHoststatusCount($MY_RIGHTS, false);
                $servicestatusCount = $HostsTable->getServicestatusCount($MY_RIGHTS, false);
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');

            }

            if ($this->DbBackend->isStatusengine3()) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                $hoststatusCount = $HostsTable->getHoststatusCountStatusengine3($MY_RIGHTS, false);
                $servicestatusCount = $HostsTable->getServicestatusCountStatusengine3($MY_RIGHTS, false);
            }

        }
        $this->set(compact(['showstatsinmenu', 'hoststatusCount', 'servicestatusCount']));
        $this->viewBuilder()->setOption('serialize', ['showstatsinmenu', 'hoststatusCount', 'servicestatusCount']);
    }

    public function statuscount() {
        if (!$this->isApiRequest()) {
            throw new RuntimeException('Only for API requests');
        }
        $session = $this->request->getSession();
        $session->close();

        $recursive = false;
        // ITC-3258 No recursive parameter, use the default from the user settings
        if ($this->request->getQuery('recursive', null) === null) {
            $User = new User($this->getUser());
            $recursive = $User->isRecursiveBrowserEnabled();
        } else {
            // Parameter is set, use it
            $recursive = false;
            if ($this->request->getQuery('recursive', null) === 'true') {
                $recursive = true;
            }
        }
        $extended = $this->request->getQuery('extended', null) === 'true';

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $containerIds = $this->request->getQuery('containerIds', $MY_RIGHTS);
        if (!is_numeric($containerIds) && !is_array($containerIds)) {
            $containerIds = ROOT_CONTAINER;
        }

        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($recursive) {
            //get recursive container ids
            if (empty($containerIds)) {
                $containerIds[] = ROOT_CONTAINER;
            }
            $containerIdToResolve = $containerIds;

            $children = $ContainersTable->getChildren($containerIdToResolve[0]);
            $containerIdsResolved = Hash::extract($children, '{n}.id');
            $recursiveContainerIds = [];
            foreach ($containerIdsResolved as $containerId) {
                if (in_array($containerId, $this->MY_RIGHTS)) {
                    $recursiveContainerIds[] = $containerId;
                }
            }
            $containerIds = array_merge($containerIds, $recursiveContainerIds);
        }

        //We need integers for cratedb
        $containerIdsForQuery = [];
        foreach ($containerIds as $containerId) {
            $containerIdsForQuery[] = (int)$containerId;
        }
        $hoststatus = [];
        $servicestatus = [];

        if ($this->DbBackend->isNdoUtils()) {
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $hoststatus = $HostsTable->getHostsWithStatusByConditions($containerIdsForQuery, []);
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');
            $servicestatus = $ServicesTable->getServicesWithStatusByConditions($containerIdsForQuery, []);
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->DbBackend->isStatusengine3()) {
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $hoststatus = $HostsTable->getHostsWithStatusByConditionsStatusengine3($containerIdsForQuery, []);
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');
            $servicestatus = $ServicesTable->getServicesWithStatusByConditionsStatusengine3($containerIdsForQuery, []);
        }

        $hoststatusSummary = $HostsTable->getHostStateSummary($hoststatus, $extended);
        $servicestatusSummary = $ServicesTable->getServiceStateSummary($servicestatus, $extended);
        $hoststatusSum = $hoststatusSummary['total'];
        $servicestatusSum = $servicestatusSummary['total'];

        $hoststatusCount = $hoststatusSummary['state'];
        $servicestatusCount = $servicestatusSummary['state'];

        $hoststatusCountPercentage = [];
        $servicestatusCountPercentage = [];
        foreach ($hoststatusCount as $stateId => $count) {
            if (is_numeric($stateId)) {
                if ($hoststatusSum > 0) {
                    $hoststatusCountPercentage[$stateId] = round($count / $hoststatusSum * 100, 2);
                } else {
                    $hoststatusCountPercentage[$stateId] = 0;
                }
            }

        }

        foreach ($servicestatusCount as $stateId => $count) {
            if (is_numeric($stateId)) {
                if ($servicestatusSum > 0) {
                    $servicestatusCountPercentage[$stateId] = round($count / $servicestatusSum * 100, 2);
                } else {
                    $servicestatusCountPercentage[$stateId] = 0;
                }
            }
        }

        $unhandledHosts = $hoststatusSummary['not_handled'];
        $unhandledHostsSum = 0;
        foreach ($hoststatusSummary['not_handled'] as $stateId => $count) {
            if (is_numeric($stateId)) {
                $unhandledHostsSum += $count;
            }
        }

        $unhandledServices = $servicestatusSummary['not_handled'];
        $unhandledServicesSum = 0;
        foreach ($servicestatusSummary['not_handled'] as $stateId => $count) {
            if (is_numeric($stateId)) {
                $unhandledServicesSum += $count;
            }
        }

        $this->set('hoststatusCount', $hoststatusCount);
        $this->set('servicestatusCount', $servicestatusCount);
        $this->set('hoststatusSum', $hoststatusSum);
        $this->set('servicestatusSum', $servicestatusSum);
        $this->set('hoststatusCountPercentage', $hoststatusCountPercentage);
        $this->set('cumulativeHoststatus', $hoststatusSummary['cumulative_state']);
        $this->set('servicestatusCountPercentage', $servicestatusCountPercentage);
        $this->set('cumulativeServicestatus', $servicestatusSummary['cumulative_state']);
        $this->set('unhandledHosts', $unhandledHosts);
        $this->set('unhandledHostsSum', $unhandledHostsSum);
        $this->set('unhandledServices', $unhandledServices);
        $this->set('unhandledServicesSum', $unhandledServicesSum);


        $this->viewBuilder()->setOption('serialize', [
            'hoststatusCount',
            'servicestatusCount',
            'hoststatusSum',
            'servicestatusSum',
            'hoststatusCountPercentage',
            'servicestatusCountPercentage',
            'unhandledHosts',
            'unhandledHostsSum',
            'unhandledServices',
            'unhandledServicesSum',
            'cumulativeHoststatus',
            'cumulativeServicestatus'
        ]);
    }


    public function menu() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $user = $this->getUser();
        $cacheKey = sprintf('Menu_%s', $user->get('id'));

        if (!Cache::read($cacheKey, 'permissions')) {
            $Menu = new Menu($this->PERMISSIONS);
            $jsonMenu = [];
            foreach ($Menu->getMenuItems() as $headline) {
                $jsonMenu[] = $headline->toArray();
            }

            Cache::write($cacheKey, $jsonMenu, 'permissions');
        }
        $session = $this->request->getSession();
        $session->close();

        $menu = Cache::read($cacheKey, 'permissions');

        $Logo = new Logo();

        $this->set('menu', $menu);
        $this->set('headerLogoForHtml', $Logo->getHeaderLogoForHtml());
        $this->viewBuilder()->setOption('serialize', ['menu', 'headerLogoForHtml']);
    }

    public function topSearch() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if ($this->request->is('post')) {
            //Search request
            $type = $this->request->getData('type');
            $searchStr = $this->request->getData('searchStr');

            if ($type !== 'uuid') {
                throw new BadRequestException('Unknown type');
            }

            $tablesToSeach = [
                'Hosts',
                'Hosttemplates',
                'Timeperiods',
                'Commands',
                'Contacts',
                'Contactgroups',
                'Hostgroups',
                'Servicegroups',
                'Services',
                'Servicetemplates',
                'Hostescalations',
                'Serviceescalations',
                'Hostdependencies',
                'Servicedependencies'
            ];

            foreach ($tablesToSeach as $TableName) {
                /** @var Table $Table */
                $Table = TableRegistry::getTableLocator()->get($TableName);

                $result = $Table->find()
                    ->select([
                        'id',
                        'uuid'
                    ])
                    ->where([
                        'uuid' => $searchStr
                    ])
                    ->first();

                if ($result !== null) {
                    $hasPermission = $this->hasPermission('index', strtolower($TableName), '');
                    $this->set('hasPermission', $hasPermission);

                    if (!$hasPermission) {
                        $this->set('message', __('You are not permitted to access this object.'));
                        $this->viewBuilder()->setOption('serialize', [
                            'hasPermission',
                            'message'
                        ]);
                        $this->response = $this->response->withStatus(403);
                        return;
                    }

                    $this->set('state', $TableName . 'Index'); // AngularJS
                    $this->set('url', ['/', strtolower($TableName), 'index']); // Angular
                    $this->set('id', $result->get('id'));
                    $this->viewBuilder()->setOption('serialize', [
                        'state',
                        'url',
                        'id',
                        'hasPermission'
                    ]);
                    return;
                }
            }
        }

        $this->set('message', __('Object could not be found.'));
        $this->viewBuilder()->setOption('serialize', [
            'message'
        ]);
        $this->response = $this->response->withStatus(404);
    }

    public function websocket_configuration() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if (!Cache::read('systemsettings', 'permissions')) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $systemsettingsArray = $SystemsettingsTable->findAsArray();
            Cache::write('systemsettings', $systemsettingsArray, 'permissions');
        }
        $session = $this->request->getSession();
        $session->close();

        $systemsettings = Cache::read('systemsettings', 'permissions');
        $websocketConfig = $systemsettings['SUDO_SERVER'];
        $websocketConfig['SUDO_SERVER.URL'] = 'wss://' . env('HTTP_HOST') . '/sudo_server';
        $websocketConfig['QUERY_LOG.URL'] = 'wss://' . env('HTTP_HOST') . '/query_log';
        $websocketConfig['PUSH_NOTIFICATIONS.URL'] = 'wss://' . env('HTTP_HOST') . '/push_notifications';

        $this->set('websocket', $websocketConfig);
        $this->viewBuilder()->setOption('serialize', ['websocket']);
    }

    public function push_configuration() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if (!Cache::read('systemsettings', 'permissions')) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $systemsettingsArray = $SystemsettingsTable->findAsArray();
            Cache::write('systemsettings', $systemsettingsArray, 'permissions');
        }
        $session = $this->request->getSession();
        $session->close();

        $systemsettings = Cache::read('systemsettings', 'permissions');
        $websocketConfig = $systemsettings['SUDO_SERVER'];
        $websocketConfig['PUSH_NOTIFICATIONS.URL'] = 'wss://' . env('HTTP_HOST') . '/push_notifications';


        $user = $this->getUser();

        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');

        $this->set('user', [
            'id'             => $user->get('id'),
            'hasPushContact' => $ContactsTable->hasUserAPushContact($user->get('id'))
        ]);

        $this->set('websocket', $websocketConfig);
        $this->viewBuilder()->setOption('serialize', ['websocket', 'user']);
    }

    public function executing() {
        //Only ship HTML template
        $id = $this->request->getQuery('id', 'angularExecutingModal');

        $this->set('id', $id);
    }

    public function getDowntimeData() {
        if (!Cache::read('FRONTEND.PRESELECTED_DOWNTIME_OPTION', 'permissions')) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $record = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.PRESELECTED_DOWNTIME_OPTION');
            Cache::write('FRONTEND.PRESELECTED_DOWNTIME_OPTION', $record->get('value'), 'permissions');
        }
        $downtimetypeId = Cache::read('FRONTEND.PRESELECTED_DOWNTIME_OPTION', 'permissions');

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $defaultValues = [
            'js_from'         => $UserTime->customFormat('Y, m, d, H, i', time()),
            'js_to'           => $UserTime->customFormat('Y, m, d, H, i', time() + 60 * 15),
            'from_date'       => $UserTime->customFormat('d.m.Y', time()),
            'from_time'       => $UserTime->customFormat('H:i', time()),
            'to_date'         => $UserTime->customFormat('d.m.Y', time()),
            'to_time'         => $UserTime->customFormat('H:i', time() + 60 * 15),
            'duration'        => 15,
            'comment'         => __('In maintenance'),
            'downtimetype_id' => $downtimetypeId
        ];

        $userData = [
            'id'       => $User->getId(),
            'fullname' => $User->getFullName()

        ];

        $this->set('defaultValues', $defaultValues);
        $this->set('author', $userData);
        $this->viewBuilder()->setOption('serialize', ['defaultValues', 'author']);
    }

    public function system_health() {
        $session = $this->request->getSession();
        $session->close();
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $cache = Cache::read('system_health', 'permissions');
        if ($cache === null) {
            $status = [
                'cache_readable' => false,
                'state'          => 'unknown'
            ];
            $this->set('status', $status);
            $this->viewBuilder()->setOption('serialize', ['status']);
            return;
        }


        $cache['cache_readable'] = true;
        $cache['gearman_reachable'] = false;
        $cache['gearman_worker_running'] = false;

        $GearmanClient = new Gearman();
        $GearmanClient->setTimeout(5000);
        $cache['gearman_reachable'] = $GearmanClient->ping();

        // replacement of ps -eaf because it takes ps too long to display the username in an LDAP based setup
        // https://www.ibm.com/support/pages/apar/IJ08995
        // we have no need for the username, so we can use the faster ps -eo command
        exec('ps -eo command |grep gearman_worker |grep -v \'mod_gearman_worker\' |grep -v \'grep\'', $output);
        $cache['gearman_worker_running'] = sizeof($output) > 0;
        if (!$cache['gearman_worker_running']) {
            $this->setHealthState('critical');
        }

        if (!$cache['isNagiosRunning']) {
            $this->setHealthState('critical');
        }

        if (!$cache['isOitcCmdRunning']) {
            $this->setHealthState('warning');
        }

        if (!$cache['isSudoServerRunning']) {
            $this->setHealthState('warning');
        }

        if (!$cache['gearman_reachable'] || !$cache['gearman_worker_running']) {
            $this->setHealthState('critical');
        }

        if ($cache['isStatusengineInstalled'] && !$cache['isStatusengineRunning']) {
            $this->setHealthState('critical');
        }

        if ($cache['isStatusenginePerfdataProcessor'] && !$cache['isStatusengineRunning']) {
            $this->setHealthState('critical');
        }

        if (!$cache['isStatusenginePerfdataProcessor'] && !$cache['isNpcdRunning']) {
            $this->setHealthState('critical');
        }

        if ($cache['isDistributeModuleInstalled'] && !$cache['isNstaRunning']) {
            $this->setHealthState('warning');
        }

        $this->setHealthState($cache['memory_usage']['memory']['state']);
        $this->setHealthState($cache['memory_usage']['swap']['state']);
        $this->setHealthState($cache['load']['state']);
        foreach ($cache['disk_usage'] as $disk) {
            $this->setHealthState($disk['state']);
        }

        if (Plugin::isLoaded('DistributeModule')) {
            $User = new User($this->getUser());
            $UserTime = $User->getUserTime();
            foreach (($cache['satellites'] ?? []) as $index => $satellite) {
                // Put date to users time-zone
                if (!empty($cache['satellites'][$index]['satellite_status']['last_seen'])) {
                    $date = $UserTime->format($cache['satellites'][$index]['satellite_status']['last_seen']);
                    $cache['satellites'][$index]['satellite_status']['last_seen'] = $date;
                }
                // Check if user may edit satellite
                if ($this->hasRootPrivileges) {
                    $cache['satellites'][$index]['allow_edit'] = true;
                } else {
                    $cache['satellites'][$index]['allow_edit'] = $this->isWritableContainer($satellite['container_id']);
                }
            }
        }

        $user = $this->getUser();
        $UserTime = new UserTime($user->get('timezone'), $user->get('dateformat'));
        $cache['update'] = $UserTime->format($cache['update']);
        $cache['state'] = $this->state;
        $cache['errorCount'] = $this->errorCount;
        $this->set('status', $cache);
        $this->viewBuilder()->setOption('serialize', ['status']);
    }

    private function setHealthState($state) {
        if ($state !== 'ok') {
            $this->errorCount++;
        }

        //Do not overwrite critical with ok or warning
        if ($this->state === 'critical') {
            return;
        }

        //Do not overwrite warning with ok
        if ($this->state === 'warning' && $state !== 'critical') {
            return;
        }

        $this->state = $state;
    }

    /**
     * @param int $up up|ok
     * @param int $down down|warning
     * @param int $unreachable unreachable|critical
     * @param int $unknown unknown
     * @throws Exception
     */
    public function getPieChart($up = 0, $down = 0, $unreachable = 1, $unknown = null) {
        $session = $this->request->getSession();
        $session->close();
        $PieChart = new PieChart();

        $chartData = [$up, $down, $unreachable];
        if ($unknown !== null) {
            $chartData = [$up, $down, $unreachable, $unknown];
        }

        $PieChart->createPieChart($chartData);

        $image = $PieChart->getImage();


        $this->disableAutoRender();
        $this->response = $this->response->withHeader('Content-Type', 'image/png');
        $stream = new CallbackStream(function () use ($image) {
            imagepng($image, null, 0);
            imagedestroy($image);
        });
        $this->response = $this->response->withBody($stream);
    }

    /**
     * @param int $up up|ok
     * @param int $down down|warning
     * @param int $unreachable unreachable|critical
     * @param int $unknown unknown
     * @throws Exception
     */
    public function getHalfPieChart($up = 0, $down = 0, $unreachable = 1, $unknown = null) {
        $session = $this->request->getSession();
        $session->close();
        $PieChart = new PieChart();

        $chartData = [$up, $down, $unreachable];
        if ($unknown !== null) {
            $chartData = [$up, $down, $unreachable, $unknown];
        }

        $PieChart->createHalfPieChart($chartData);

        $image = $PieChart->getImage();

        $this->disableAutoRender();
        $this->response = $this->response->withHeader('Content-Type', 'image/png');
        $stream = new CallbackStream(function () use ($image) {
            imagepng($image, null, 0);
            imagedestroy($image);
        });
        $this->response = $this->response->withBody($stream);
    }

    /**
     * @param int $size
     * @param int $bitMaskHostState
     * @param int $bitMaskServiceState
     * @throws Exception
     */

    public function getHostAndServiceStateSummaryIcon($size = 100, $bitMaskHostState = 0, $bitMaskServiceState = 0) {
        $session = $this->request->getSession();
        $session->close();
        $HostAndServiceSummaryIcon = new HostAndServiceSummaryIcon($size);
        $HostAndServiceSummaryIcon->createSummaryIcon($bitMaskHostState, $bitMaskServiceState);
        $image = $HostAndServiceSummaryIcon->getImage();

        $this->disableAutoRender();
        $this->response = $this->response->withHeader('Content-Type', 'image/png');
        $stream = new \Laminas\Diactoros\CallbackStream(function () use ($image) {
            imagepng($image, null, 0);
            imagedestroy($image);
        });
        $this->response = $this->response->withBody($stream);
    }

    public function ldap_configuration() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        if (!Cache::read('systemsettings', 'permissions')) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $systemsettingsArray = $SystemsettingsTable->findAsArray();
            Cache::write('systemsettings', $systemsettingsArray, 'permissions');
        }
        $session = $this->request->getSession();
        $session->close();

        $systemsettings = Cache::read('systemsettings', 'permissions');
        $ldapConfig = [
            'host'    => $systemsettings['FRONTEND']['FRONTEND.LDAP.ADDRESS'],
            'query'   => $systemsettings['FRONTEND']['FRONTEND.LDAP.QUERY'],
            'base_dn' => $systemsettings['FRONTEND']['FRONTEND.LDAP.BASEDN']
        ];

        $this->set('ldapConfig', $ldapConfig);
        $this->viewBuilder()->setOption('serialize', ['ldapConfig']);
    }

    public function queryhandler() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $QueryHandler = new QueryHandler($SystemsettingsTable->getQueryHandlerPath());

        $this->set('QueryHandler', [
            'exists'      => $QueryHandler->exists(),
            'path'        => $QueryHandler->getPath(),
            'isContainer' => $QueryHandler->isContainer()
        ]);
        $this->viewBuilder()->setOption('serialize', ['QueryHandler']);

    }

    public function hostBrowserMenu() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $hostId = $this->request->getQuery('hostId');
        $includeHoststatus = $this->request->getQuery('includeHoststatus') === 'true';

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException('Invalid host');
        }
        $host = $HostsTable->getHostByIdWithHosttemplate($hostId);

        //Can user see this object?
        if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
            return $this->render403();
        }

        //Can user edit this object?
        $allowEdit = $this->hasRootPrivileges;
        if ($allowEdit === false) {
            //Strict checking for non root users
            $allowEdit = $this->allowedByContainerId($host->getContainerIds());
        }

        /** @var DocumentationsTable $DocumentationsTable */
        $DocumentationsTable = TableRegistry::getTableLocator()->get('Documentations');


        $hostUrl = $host->get('host_url');
        if ($hostUrl === null || $hostUrl === '') {
            $hostUrl = $host->get('hosttemplate')->get('host_url');
        }

        if ($hostUrl) {
            /** @var HosttemplatesTable $HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

            $hostForMerge = $HostsTable->getHostForBrowser($hostId);
            $hosttemplateForMerge = $HosttemplatesTable->getHosttemplateForHostBrowser($hostForMerge['hosttemplate_id']);

            //Merge host and inheritance data
            $HostMergerForBrowser = new HostMergerForBrowser(
                $hostForMerge,
                $hosttemplateForMerge
            );
            $mergedHost = $HostMergerForBrowser->getDataForView();

            $HostMacroReplacer = new HostMacroReplacer($mergedHost);
            $HostCustomMacroReplacer = new CustomMacroReplacer($mergedHost['customvariables'], OBJECT_HOST);

            $hostUrl =
                $HostMacroReplacer->replaceBasicMacros(          // Replace $HOSTNAME$
                    $HostCustomMacroReplacer->replaceAllMacros(  // Replace $_HOSTFOOBAR$
                        $hostUrl
                    )
                );
        }

        if ($includeHoststatus) {
            //Get meta data and push to front end
            $HoststatusFields = new HoststatusFields($this->DbBackend);
            $HoststatusFields->currentState()->isFlapping()->isHardstate();
            $HosttatusTable = $this->DbBackend->getHoststatusTable();
            $hoststatus = $HosttatusTable->byUuid($host->get('uuid'), $HoststatusFields);
            if (!isset($hoststatus['Hoststatus'])) {
                $hoststatus['Hoststatus'] = [];
            }
            $Hoststatus = new Hoststatus($hoststatus['Hoststatus']);
        } else {
            $Hoststatus = new Hoststatus([
                'Hoststatus' => []
            ]);

        }

        $config = [
            'hostId'            => $host->get('id'),
            'hostUuid'          => $host->get('uuid'),
            'hostName'          => $host->get('name'),
            'hostAddress'       => $host->get('address'),
            'docuExists'        => $DocumentationsTable->existsByUuid($host->get('uuid')),
            'hostUrl'           => $hostUrl,
            'allowEdit'         => $allowEdit,
            'includeHoststatus' => $includeHoststatus,
            'Hoststatus'        => $Hoststatus->toArray()
        ];

        $this->set('config', $config);
        $this->viewBuilder()->setOption('serialize', ['config']);
    }

    public function serviceBrowserMenu() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $serviceId = $this->request->getQuery('serviceId');
        $includeHoststatus = $this->request->getQuery('includeHoststatus') === 'true';
        $includeServicestatus = $this->request->getQuery('includeServicestatus') === 'true';

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        if (!$ServicesTable->existsById($serviceId)) {
            throw new NotFoundException('Invalid service');
        }
        $service = $ServicesTable->getServiceByIdWithHostAndServicetemplate($serviceId);

        //Can user see this object?
        if (!$this->allowedByContainerId($service->getContainerIds(), false)) {
            return $this->render403();
        }

        //Can user edit this object?
        $allowEdit = $this->hasRootPrivileges;
        if ($allowEdit === false) {
            //Strict checking for non root users
            $allowEdit = $this->allowedByContainerId($service->getContainerIds());
        }

        /** @var DocumentationsTable $DocumentationsTable */
        $DocumentationsTable = TableRegistry::getTableLocator()->get('Documentations');

        $serviceName = $service->get('name');
        if ($serviceName === null || $serviceName === '') {
            $serviceName = $service->get('servicetemplate')->get('name');
        }


        $serviceUrl = $service->get('service_url');
        if ($serviceUrl === null || $serviceUrl === '') {
            $serviceUrl = $service->get('servicetemplate')->get('service_url');
        }

        if ($serviceUrl) {
            $serviceForMerge = $ServicesTable->getServiceForBrowser($serviceId);
            /** @var ServicetemplatesTable $ServicetemplatesTable */
            $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
            $servicetemplateForMerge = $ServicetemplatesTable->getServicetemplateForServiceBrowser($service['servicetemplate_id']);

            //Merge service and inheritance data
            $ServiceMergerForView = new ServiceMergerForBrowser(
                $serviceForMerge,
                $servicetemplateForMerge,
                [],
                []
            );
            $mergedService = $ServiceMergerForView->getDataForView();

            /** @var HosttemplatesTable $HosttemplatesTable */
            $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

            $hostForMerge = $HostsTable->getHostForBrowser($serviceForMerge['host_id']);
            $hosttemplateForMerge = $HosttemplatesTable->getHosttemplateForHostBrowser($hostForMerge['hosttemplate_id']);

            //Merge host and inheritance data
            $HostMergerForBrowser = new HostMergerForBrowser(
                $hostForMerge,
                $hosttemplateForMerge
            );
            $mergedHost = $HostMergerForBrowser->getDataForView();


            //Replace macros in service url
            $HostMacroReplacer = new HostMacroReplacer($mergedHost);
            $ServiceMacroReplacer = new ServiceMacroReplacer($mergedService);
            $ServiceCustomMacroReplacer = new CustomMacroReplacer($mergedService['customvariables'], OBJECT_SERVICE);
            $HostCustomMacroReplacer = new CustomMacroReplacer($mergedHost['customvariables'], OBJECT_HOST);
            $serviceUrl =
                $ServiceMacroReplacer->replaceBasicMacros(                  // Replace $SERVICEDESCRIPTION$
                    $HostMacroReplacer->replaceBasicMacros(                 // Replace $HOSTNAME$
                        $HostCustomMacroReplacer->replaceAllMacros(         // Replace $_HOSTFOOBAR$
                            $ServiceCustomMacroReplacer->replaceAllMacros(  // Replace $_SERVICEFOOBAR$
                                $serviceUrl
                            )
                        )
                    )
                );

        }

        if ($includeServicestatus) {
            //Get meta data and push to front end
            $ServicestatusFields = new ServicestatusFields($this->DbBackend);
            $ServicestatusFields->currentState()->isFlapping()->isHardstate();
            $ServicestatusTable = $this->DbBackend->getServicestatusTable();
            $servicestatus = $ServicestatusTable->byUuid($service->get('uuid'), $ServicestatusFields);
            if (!isset($servicestatus['Servicestatus'])) {
                $servicestatus['Servicestatus'] = [];
            }
            $Servicestatus = new Servicestatus($servicestatus['Servicestatus']);
        } else {
            $Servicestatus = new Servicestatus([
                'Servicestatus' => []
            ]);
        }

        if ($includeHoststatus) {
            //Get meta data and push to front end
            $HoststatusFields = new HoststatusFields($this->DbBackend);
            $HoststatusFields->currentState()->isFlapping()->isHardstate();
            $HoststatusTable = $this->DbBackend->getHoststatusTable();
            $hoststatus = $HoststatusTable->byUuid($service->get('host')->get('uuid'), $HoststatusFields);
            if (!isset($hoststatus['Hoststatus'])) {
                $hoststatus['Hoststatus'] = [];
            }
            $Hoststatus = new Hoststatus($hoststatus['Hoststatus']);
        } else {
            $Hoststatus = new Hoststatus([
                'Hoststatus' => []
            ]);
        }

        $config = [
            'hostId'               => $service->get('host')->get('id'),
            'serviceId'            => $service->get('id'),
            'serviceUuid'          => $service->get('uuid'),
            'hostName'             => $service->get('host')->get('name'),
            'serviceName'          => $serviceName,
            'hostAddress'          => $service->get('host')->get('address'),
            'docuExists'           => $DocumentationsTable->existsByUuid($service->get('uuid')),
            'serviceUrl'           => $serviceUrl,
            'allowEdit'            => $allowEdit,
            'includeServicestatus' => $includeServicestatus,
            'Servicestatus'        => $Servicestatus->toArray(),
            'includeHoststatus'    => $includeHoststatus,
            'Hoststatus'           => $Hoststatus->toArray()
        ];
        $this->set('config', $config);
        $this->viewBuilder()->setOption('serialize', ['config']);
    }

    public function getSatellites() {
        $satellites = [];

        if (Plugin::isLoaded('DistributeModule')) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $masterInstanceName = $SystemsettingsTable->getMasterInstanceName();

            /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

            $satellites = $SatellitesTable->getSatellitesAsListWithDescription($this->MY_RIGHTS);
            $satellites[0] = $masterInstanceName;
        }

        $satellites = Api::makeItJavaScriptAble($satellites);

        $this->set('satellites', $satellites);
        $this->viewBuilder()->setOption('serialize', ['satellites']);
    }

    public function getSystemname() {
        $systenmane = parent::getSystemname();
        $this->set('systenmane', $systenmane);
        $this->viewBuilder()->setOption('serialize', ['systenmane']);
    }

    public function getAppHeaderInfo() {
        if ($this->isApiRequest()) {

            $path = APP . 'Lib' . DS . 'openITCOCKPIT_AvailableVersion.php';
            $availableVersion = '???';
            if (file_exists($path)) {
                $availableVersion = openITCOCKPIT_AvailableVersion::get();
            }
            $newVersionAvailable = false;
            if (version_compare($availableVersion, OPENITCOCKPIT_VERSION) > 0 && $this->hasRootPrivileges) {
                $newVersionAvailable = true;
            }

            /** @var RegistersTable $RegistersTable */
            $RegistersTable = TableRegistry::getTableLocator()->get('Registers');


            $license = $RegistersTable->getLicense();
            $isCommunityEdition = false;
            $hasSubscription = $license !== null;
            if (isset($license['license']) && $license['license'] === $RegistersTable->getCommunityLicenseKey()) {
                $isCommunityEdition = true;
            }

            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $systemsettingsArray = $SystemsettingsTable->findAsArray();

            $exportRunningHeaderInfo = false;
            if (isset($systemsettingsArray['FRONTEND']['FRONTEND.SHOW_EXPORT_RUNNING'])) {
                if ($systemsettingsArray['FRONTEND']['FRONTEND.SHOW_EXPORT_RUNNING'] === 'yes') {
                    $exportRunningHeaderInfo = true;
                }
            }

            $this->set('isCommunityEdition', $isCommunityEdition);
            $this->set('hasSubscription', $hasSubscription);
            $this->set('exportRunningHeaderInfo', $exportRunningHeaderInfo);
            $this->set('newVersionAvailable', $newVersionAvailable);
            $this->viewBuilder()->setOption('serialize', ['isCommunityEdition', 'hasSubscription', 'exportRunningHeaderInfo', 'newVersionAvailable']);
        }

    }
}
