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

namespace itnovum\openITCOCKPIT\Agent;


use App\Model\Table\AgentchecksTable;
use App\Model\Table\ServicecommandargumentvaluesTable;
use App\Model\Table\ServicesTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class AgentResponseToServices {

    /**
     * Processes with more listening ports get one service for the process instead of one service per port
     */
    const MAX_PORTS_PER_PROCESS = 3;

    /**
     * Processes which listen for different services (e.g. RDP and RPC of svchost.exe), they always get one service per port
     */
    const SHARED_PORT_PROCESSES = ['svchost.exe', 'System', 'systemd', 'launchd', 'inetd', 'xinetd'];

    /**
     * @var int
     */
    private $hostId;

    /**
     * @var array
     */
    private $agentResponse;

    /**
     * @var AgentchecksTable $AgentchecksTable
     */
    private $AgentchecksTable;

    /**
     * @var int
     */
    private $maxLengthCommandArg;

    /**
     * @var int
     */
    private $maxLengthServiceName;

    /**
     * Return all services or filter result
     * @var bool
     */
    private $onlyMissingServices = false;

    /**
     * @var array
     */
    private $existingServicesCache = [];

    /**
     * AgentResponseToServices constructor.
     * @param int|null $hostId
     * @param array $agentResponse
     */
    public function __construct($hostId = null, $agentResponse = [], $onlyMissingServices = false) {
        $this->hostId = $hostId;
        $this->agentResponse = $agentResponse;
        $this->AgentchecksTable = TableRegistry::getTableLocator()->get('Agentchecks');

        /** @var ServicecommandargumentvaluesTable $ServicecommandargumentvaluesTable */
        $ServicecommandargumentvaluesTable = TableRegistry::getTableLocator()->get('Servicecommandargumentvalues');
        $this->maxLengthCommandArg = $ServicecommandargumentvaluesTable->getSchema()->getColumn('value')['length'];

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $this->maxLengthServiceName = $ServicesTable->getSchema()->getColumn('name')['length'];

        $this->onlyMissingServices = $onlyMissingServices;
        if ($onlyMissingServices && is_numeric($hostId)) {
            $this->existingServicesCache = $ServicesTable->getAgentServicesByHostId($hostId);
        }
    }

    /**
     * @param bool $onlyMissingServices
     * @return array
     * @todo implement: alfresco
     */
    public function getAllServices() {
        $services = [];
        if (!is_array($this->agentResponse)) {
            return [];
        }
        foreach ($this->agentResponse as $mainKey => $items) {
            switch ($mainKey) {
                case 'agent':
                    $systemUptime = $this->getServiceStructByName('agent.system_uptime', 'Uptime');
                    if ($systemUptime) {
                        $services['system_uptime'] = $systemUptime;
                    }
                    break;
                case 'memory':
                    $memoryService = $this->getServiceStructByName('memory', 'Memory usage percentage');
                    if ($memoryService) {
                        $services['memory'] = $memoryService;
                    }
                    break;
                case 'swap':
                    $swapService = $this->getServiceStructByName('swap', 'Swap usage percentage');
                    if ($swapService) {
                        $services['swap'] = $swapService;
                    }
                    break;
                case 'cpu':
                    $cpuService = $this->getServiceStructByName('cpu.cpu_percentage', 'CPU usage percentage');
                    if ($cpuService) {
                        $services['cpu_percentage'] = $cpuService;
                    }
                    break;
                case 'system_load':
                    $systemLoad = $this->getServiceStructForSystemLoad('CPU load');
                    if ($systemLoad) {
                        $services['system_load'] = $systemLoad;
                    }
                    break;
                case 'sensors':
                    $sensorServices = $this->getServiceStructForSensors([
                            'Temperatures',
                            'Batteries'
                        ]
                    );
                    if ($sensorServices) {
                        $services['sensors'] = $sensorServices;
                    }
                    break;
                case 'disk_io':
                    $diskIoServices = $this->getServiceStructForDiskIo();
                    if ($diskIoServices) {
                        $services['disk_io'] = $diskIoServices;
                    }
                    break;
                case 'disks':
                    $disksServices = $this->getServiceStructForDisks(); // Space used
                    if ($disksServices) {
                        $services['disks'] = $disksServices;
                    }

                    // "disks_free" is only a Virtual JSON key bc it does not exist in the Agent output
                    $disksFreeSpaceServices = $this->getServiceStructForDisks(true); // Space free
                    if ($disksFreeSpaceServices) {
                        $services['disks_free'] = $disksFreeSpaceServices;
                    }

                    $diskInodesServices = $this->getServiceStructForDiskInodes();
                    if ($diskInodesServices) {
                        $services['disks_inodes'] = $diskInodesServices;
                    }

                    if (!$this->isWindows()) {
                        $readonlyService = $this->getServiceStructByName('disks_readonly', 'Read-only filesystems');
                        if ($readonlyService) {
                            $services['disks_readonly'] = $readonlyService;
                        }
                    }
                    break;
                case 'net_io':
                    $netIoServices = $this->getServiceStructForNetIo();
                    if ($netIoServices) {
                        $services['net_io'] = $netIoServices;
                    }
                    break;
                case 'net_stats':
                    $netStatsServices = $this->getServiceStructForNetStats();
                    if ($netStatsServices) {
                        $services['net_stats'] = $netStatsServices;
                    }
                    break;
                case 'processes':
                    $processServices = $this->getServiceStructForProcesses();
                    if ($processServices) {
                        $services['processes'] = $processServices;
                    }

                    if (!$this->isWindows() && !isset($this->agentResponse['processes']['error'])) {
                        $zombieService = $this->getServiceStructByName('processes.zombies', 'Zombie processes');
                        if ($zombieService) {
                            $services['processes_zombies'] = $zombieService;
                        }
                    }
                    break;
                case 'systemd_services':
                    $systemdServices = $this->getServiceStructForSystemdServices();
                    if ($systemdServices) {
                        $services['systemd_services'] = $systemdServices;
                    }

                    if (!isset($this->agentResponse['systemd_services']['error'])) {
                        $failedUnitsService = $this->getServiceStructByName('systemd_services.failed', 'Failed systemd units');
                        if ($failedUnitsService) {
                            $services['systemd_services_failed'] = $failedUnitsService;
                        }
                    }
                    break;
                case 'launchd_services':
                    $launchdServices = $this->getServiceStructForLaunchdServices();
                    if ($launchdServices) {
                        $services['launchd_services'] = $launchdServices;
                    }
                    break;
                case 'windows_services':
                    $windowsServices = $this->getServiceStructForwindowsServices();
                    if ($windowsServices) {
                        $services['windows_services'] = $windowsServices;
                    }

                    if (!isset($this->agentResponse['windows_services']['error'])) {
                        $automaticServices = $this->getServiceStructByName('windows_services.automatic', 'Automatic services');
                        if ($automaticServices) {
                            $services['windows_services_automatic'] = $automaticServices;
                        }
                    }
                    break;
                case 'windows_eventlog':
                    $windowsEventlogs = $this->getServiceStructForWindowsEventlogs();
                    if ($windowsEventlogs) {
                        $services['windows_eventlog'] = $windowsEventlogs;
                    }
                    break;
                case 'customchecks':
                    $customchecks = $this->getServiceStructForCustomchecks();
                    if ($customchecks) {
                        $services['customchecks'] = $customchecks;
                    }
                    break;
                case 'docker':
                    $dockerCheckTypes = [
                        'docker.running',
                        'docker.cpu',
                        'docker.memory',
                        'docker.health'
                    ];
                    foreach ($dockerCheckTypes as $type) {
                        $dockerServices = $this->getServiceStructForDocker($type);
                        if ($dockerServices) {
                            $services[str_replace('.', '_', $type)] = $dockerServices;
                        }
                    }
                    break;
                case 'libvirt':
                    $libvirtServices = $this->getServiceStructForLibvirt();
                    if ($libvirtServices) {
                        $services['libvirt'] = $libvirtServices;
                    }
                    break;
                case 'ntp':
                    $ntp = $this->getServiceStructByName('ntp', 'System Time');
                    if ($ntp) {
                        $services['ntp'] = $ntp;
                    }
                    break;

                case 'system_health':
                    $services = array_merge($services, $this->getServiceStructsForSystemHealth());
                    break;

                case 'security':
                    $services = array_merge($services, $this->getServiceStructsForSecurity());
                    break;

                case 'logfiles':
                case 'certificates':
                case 'http':
                case 'windows_perfcounters':
                    // One service per definition of the logfiles.ini, certificates.ini, http.ini or perfcounters.ini of the agent
                    $formats = [
                        'logfiles'             => 'Logfile: %s',
                        'certificates'         => 'Certificate: %s',
                        'http'                 => 'HTTP: %s',
                        'windows_perfcounters' => 'Performance counter: %s'
                    ];
                    if (is_array($items) && !isset($items['error'])) {
                        $names = array_keys($items);
                        $definitionServices = $this->getServiceStructsForValues($mainKey, 0, array_combine($names, $names), $formats[$mainKey]);
                        if ($definitionServices) {
                            $services[$mainKey] = $definitionServices;
                        }
                    }
                    break;

                case 'files':
                    // files.ini of the agent: the definitions have the type file or directory
                    if (is_array($items) && !isset($items['error'])) {
                        foreach (['file' => 'File: %s', 'directory' => 'Directory: %s'] as $type => $format) {
                            $names = array_keys(array_filter($items, function ($item) use ($type) {
                                return is_array($item) && ($item['type'] ?? '') === $type;
                            }));
                            $fileServices = $this->getServiceStructsForValues('files.' . $type, 0, array_combine($names, $names), $format);
                            if ($fileServices) {
                                $services['files_' . $type] = $fileServices;
                            }
                        }
                    }
                    break;

                case 'windows_scheduled_tasks':
                    if (is_array($items) && !isset($items['error'])) {
                        $tasks = [];
                        foreach ($items as $task) {
                            if (isset($task['path'])) {
                                // Regular expression which matches exactly this task, e.g. ^\\Backup\\Daily$
                                $tasks['^' . addcslashes($task['path'], '\\.+*?[^]$(){}|') . '$'] = $task['path'];
                            }
                        }
                        $taskServices = $this->getServiceStructsForValues('windows_scheduled_tasks', 0, $tasks, 'Scheduled task: %s');
                        if ($taskServices) {
                            $services['windows_scheduled_tasks'] = $taskServices;
                        }
                    }
                    break;

                case 'zfs':
                    // One service per pool, so every pool can get its own thresholds (e.g. a backup pool which is always quite full)
                    if (is_array($items['pools'] ?? null) && !isset($items['pools']['error'])) {
                        $pools = [];
                        foreach ($items['pools'] as $pool) {
                            if (isset($pool['name'])) {
                                $pools[$pool['name']] = $pool['name'];
                            }
                        }
                        $zfsPools = $this->getServiceStructsForValues('zfs.pools', 0, $pools, 'ZFS pool: %s');
                        if ($zfsPools) {
                            $services['zfs_pools'] = $zfsPools;
                        }
                    }
                    if (isset($items['arc']) && !isset($items['arc']['error'])) {
                        $zfsArc = $this->getServiceStructByName('zfs.arc', 'ZFS ARC');
                        if ($zfsArc) {
                            $services['zfs_arc'] = $zfsArc;
                        }
                    }
                    break;

                case 'ceph':
                    if (isset($items['health']) && !isset($items['error'])) {
                        // Health and OSDs are a state of the whole cluster
                        foreach (['ceph.health' => 'Ceph health', 'ceph.osds' => 'Ceph OSDs'] as $name => $servicename) {
                            $cephService = $this->getServiceStructByName($name, $servicename);
                            if ($cephService) {
                                $services[str_replace('.', '_', $name)] = $cephService;
                            }
                        }

                        // Raw usage of the cluster (empty pool) and one service per pool, so every pool can get its own thresholds.
                        // Internal pools like .mgr or .rgw.root are skipped.
                        $cephUsage = $this->getServiceStructsForValues('ceph.usage', 0, ['' => ''], 'Ceph usage%s') ?: [];
                        $pools = [];
                        foreach ((is_array($items['pools'] ?? null) && !isset($items['pools']['error'])) ? $items['pools'] : [] as $pool) {
                            if (isset($pool['name']) && !str_starts_with($pool['name'], '.')) {
                                $pools[$pool['name']] = $pool['name'];
                            }
                        }
                        $cephUsage = array_merge($cephUsage, $this->getServiceStructsForValues('ceph.usage', 0, $pools, 'Ceph pool: %s') ?: []);
                        if (!empty($cephUsage)) {
                            $services['ceph_usage'] = $cephUsage;
                        }
                    }
                    break;

                case 'smart':
                    // A single service for all disks which support SMART
                    if (is_array($items) && !isset($items['error']) && !empty(array_filter($items, function ($device) {
                            return !empty($device['smart_available']);
                        }))) {
                        $smartService = $this->getServiceStructByName('smart', 'SMART');
                        if ($smartService) {
                            $services['smart'] = $smartService;
                        }
                    }
                    break;

                case 'proxmox':
                    $services = array_merge($services, $this->getServiceStructsForProxmox());
                    break;

                case 'postfix':
                    if (!isset($items['error'])) {
                        $postfixService = $this->getServiceStructByName('postfix', 'Postfix queue');
                        if ($postfixService) {
                            $services['postfix'] = $postfixService;
                        }
                    }
                    break;

                case 'redfish':
                    $services = array_merge($services, $this->getServiceStructsForRedfish());
                    break;

                case 'lvm':
                    // One service per thin pool, so every pool can get its own thresholds
                    if (is_array($items['thin_pools'] ?? null) && !isset($items['thin_pools']['error'])) {
                        $pools = [];
                        foreach ($items['thin_pools'] as $pool) {
                            if (!empty($pool['active']) && isset($pool['vg'], $pool['name'])) {
                                $pools[$pool['vg'] . '/' . $pool['name']] = $pool['vg'] . '/' . $pool['name'];
                            }
                        }
                        $lvmServices = $this->getServiceStructsForValues('lvm.thin_pools', 0, $pools, 'LVM thin pool: %s');
                        if ($lvmServices) {
                            $services['lvm_thin_pools'] = $lvmServices;
                        }
                    }
                    break;

                case 'mounts':
                    if (!isset($items['error']) && !empty($items['expected'])) {
                        $mountsService = $this->getServiceStructByName('mounts', 'Missing mounts');
                        if ($mountsService) {
                            $services['mounts'] = $mountsService;
                        }
                    }
                    break;

                case 'exim':
                    if (!isset($items['error'])) {
                        $eximService = $this->getServiceStructByName('exim', 'Exim queue');
                        if ($eximService) {
                            $services['exim'] = $eximService;
                        }
                    }
                    break;

                case 'network_ports':
                    $services = array_merge($services, $this->getServiceStructsForNetworkPorts());
                    break;

                case 'ipmi':
                    $services = array_merge($services, $this->getServiceStructsForIpmi());
                    break;

                case 'packagemanager':
                    if (!empty($this->agentResponse['packagemanager']['Enabled'])) {
                        $packagemanager = $this->getServiceStructByName('packagemanager', 'System Updates');
                        if ($packagemanager) {
                            $services['packagemanager'] = $packagemanager;
                        }
                    }
                    break;
            }
        }
        return $services;
    }

    /**
     * @param int $servicetemplateId
     * @param string $servicename
     * @param array $commandargumentvalues
     * @return array
     */
    private function getServiceStruct($servicetemplateId, $servicename, $commandargumentvalues = []) {
        $struct = [
            'servicetemplate_id'           => $servicetemplateId,
            'name'                         => $this->shortServiceName($servicename),
            'servicecommandargumentvalues' => $commandargumentvalues
        ];
        if (is_numeric($this->hostId)) {
            $struct['host_id'] = $this->hostId;
        }
        return $struct;
    }

    /**
     * @param string $name
     * @param string $servicename
     * @return array|bool
     */
    private function getServiceStructByName(string $name, string $servicename) {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName($name);
        if (empty($agentcheck)) {
            return false;
        }

        if ($this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'])) {
            return false;
        }

        return $this->getServiceStruct(
            $agentcheck['servicetemplate_id'],
            $servicename,
            $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues']
        );
    }

    /**
     * @param string $servicename
     * @return array|bool
     */
    private function getServiceStructForSystemLoad(string $servicename) {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('system_load');
        if (empty($agentcheck)) {
            return false;
        }

        if ($this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'])) {
            return false;
        }

        $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
        $warning = 24;
        $critical = 32;
        if (isset($this->agentResponse['cpu']['cpu_percentage'])) {
            $cpuCount = sizeof($this->agentResponse['cpu']['cpu_percentage']);
            if ($cpuCount < 1) {
                $cpuCount = 1;
            }
            $warning = $cpuCount;
            $critical = $cpuCount + 2;
        }

        $servicetemplatecommandargumentvalues[0]['value'] = $warning;
        $servicetemplatecommandargumentvalues[1]['value'] = $critical;

        return $this->getServiceStruct(
            $agentcheck['servicetemplate_id'],
            $servicename,
            $servicetemplatecommandargumentvalues
        );

    }

    /**
     * @param array $categories
     * @return array|bool
     */
    private function getServiceStructForSensors(array $categories) {
        $services = [];
        foreach ($categories as $category) {
            $agentcheck = $this->AgentchecksTable->getAgentcheckByName(sprintf('%s.%s', 'sensors', $category));
            if (empty($agentcheck)) {
                continue;
            }
            if (isset($this->agentResponse['sensors'][$category])) {
                foreach ($this->agentResponse['sensors'][$category] as $itemKey => $items) {
                    $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
                    switch ($category) {
                        case 'Temperatures':
                            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [2 => $this->shortCommandargumentValue($items['label'])])) {
                                $servicetemplatecommandargumentvalues[2]['value'] = $this->shortCommandargumentValue($items['label']);
                                $services[] = $this->getServiceStruct(
                                    $agentcheck['servicetemplate_id'],
                                    sprintf('Sensor: %s', $items['label']),
                                    $servicetemplatecommandargumentvalues
                                );
                            }
                            break;
                        case 'Batteries':
                            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [2 => $items['id']])) {
                                $servicetemplatecommandargumentvalues[2]['value'] = $items['id'];
                                $services[] = $this->getServiceStruct(
                                    $agentcheck['servicetemplate_id'],
                                    sprintf('Battery: %s', $items['id']),
                                    $servicetemplatecommandargumentvalues
                                );
                            }
                            break;
                    }
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForDiskIo() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('disk_io');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['disk_io'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['disk_io'] as $deviceName => $device) {
                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [2 => $this->shortCommandargumentValue($deviceName)])) {
                    $servicetemplatecommandargumentvalues[2]['value'] = $this->shortCommandargumentValue($deviceName); //sda
                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Disk statistics of: %s', $deviceName),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @param bool $monitorDiskFreeSpace
     * @return array|false
     */
    private function getServiceStructForDisks(bool $monitorDiskFreeSpace = false) {
        $virtualJsonKey = 'disks'; // To monitor used disk space
        if ($monitorDiskFreeSpace === true) {
            $virtualJsonKey = 'disks_free'; // To monitor free disk space
        }

        $agentcheck = $this->AgentchecksTable->getAgentcheckByName($virtualJsonKey);
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['disks'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['disks'] as $device) {
                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [2 => $this->shortCommandargumentValue($device['disk']['mountpoint'])])) {
                    $servicetemplatecommandargumentvalues[2]['value'] = $this->shortCommandargumentValue($device['disk']['mountpoint']); // / or C:

                    $serviceDesc = 'Disk usage of';
                    if ($monitorDiskFreeSpace === true) {
                        $serviceDesc = 'Disk free space of';
                    }

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('%s: %s (%s)', $serviceDesc, $device['disk']['mountpoint'], $device['disk']['device']),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForNetIo() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('net_io');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['net_io'])) {
            $speed = 1000; // default bandwidth speed
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];

            foreach ($this->agentResponse['net_io'] as $nicName => $nic) {
                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [6 => $this->shortCommandargumentValue($nicName)])) {
                    if (isset($this->agentResponse['net_stats'][$nicName]['speed'])) {
                        if ($this->agentResponse['net_stats'][$nicName]['speed'] > 0) {
                            $speed = $this->agentResponse['net_stats'][$nicName]['speed'];
                        }
                    }
                    $maxMegabytePerSecond = $speed / 8;
                    $maxBytePerSecond = $maxMegabytePerSecond * 1024 * 1024;

                    $warning = $maxBytePerSecond / 100 * 85; // 85% of total bandwidth
                    $critical = $maxBytePerSecond / 100 * 90; // 90% of total bandwidth

                    $servicetemplatecommandargumentvalues[0]['value'] = $warning; // Total average bytes warning per second
                    $servicetemplatecommandargumentvalues[1]['value'] = $critical; // Total average bytes critical per second
                    $servicetemplatecommandargumentvalues[2]['value'] = 5; // Total average errors warning
                    $servicetemplatecommandargumentvalues[3]['value'] = 10; // Total average errors critical
                    $servicetemplatecommandargumentvalues[4]['value'] = 5; // Total average drops warning
                    $servicetemplatecommandargumentvalues[5]['value'] = 10; // Total average drops critical
                    $servicetemplatecommandargumentvalues[6]['value'] = $this->shortCommandargumentValue($nicName); // Device

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Network statistics of: %s', $nicName),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForNetStats() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('net_stats');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['net_stats'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['net_stats'] as $nicName => $nic) {
                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [1 => $this->shortCommandargumentValue($nicName)])) {
                    $servicetemplatecommandargumentvalues[0]['value'] = 'critical'; // Nagios state if interface is down
                    $servicetemplatecommandargumentvalues[1]['value'] = $this->shortCommandargumentValue($nicName); // Device

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Network state of: %s', $nicName),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForProcesses() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('processes');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['processes'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            // ITC-2939 add support for process identifier via service template
            $identifier = $servicetemplatecommandargumentvalues[8]['value'] ?? 'auto';
            if (!in_array($identifier, ['cmdline', 'exec', 'name', 'auto'], true)) {
                $identifier = 'auto';
            }

            foreach ($this->agentResponse['processes'] as $item) {
                if ($identifier !== 'auto' && isset($item[$identifier])) {
                    $processName = $item[$identifier];
                } else {
                    $processName = $item['name'];
                    if (!empty($item['exec'])) {
                        $processName = $item['exec'];
                    }
                    if (!empty($item['cmdline']) && is_array($item['cmdline'])) {
                        // Agent 1.x
                        $processName = implode(' ', $item['cmdline']);
                    }
                    if (!empty($item['cmdline']) && is_string($item['cmdline'])) {
                        // Agent 3.x
                        $processName = $item['cmdline'];
                    }
                }

                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [6 => $this->shortCommandargumentValue($processName)])) {
                    $servicetemplatecommandargumentvalues[6]['value'] = $this->shortCommandargumentValue($processName); // match

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Process: %s', $processName),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForSystemdServices() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('systemd_services');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['systemd_services'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['systemd_services'] as $itemKey => $item) {
                if (!isset($item['ActiveState'])) {
                    // Random systemd error like
                    // 'error' => 'dbus: connection closed by user'
                    continue;
                }

                // Only show active systemd services
                if ($item['ActiveState'] !== 'active') {
                    continue;
                }

                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $this->shortCommandargumentValue($item['Name'])])) {
                    $servicetemplatecommandargumentvalues[0]['value'] = $this->shortCommandargumentValue($item['Name']); // apache2.service

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        $item['Name'],
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForLaunchdServices() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('launchd_services');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['launchd_services'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['launchd_services'] as $itemKey => $item) {
                if ($item['IsRunning'] !== true) {
                    continue;
                }

                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $this->shortCommandargumentValue($item['Label'])])) {
                    $servicetemplatecommandargumentvalues[0]['value'] = $this->shortCommandargumentValue($item['Label']); // com.apple.trustd

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        $item['Label'],
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForwindowsServices() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('windows_services');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['windows_services'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['windows_services'] as $itemKey => $item) {
                if ($item['Status'] !== 'Running') {
                    continue;
                }

                $match = $item['Name']; // Name is unique and never empty
                $serviceName = $item['Name']; // wisvc
                if (!empty($item['DisplayName'])) { // Windows Insider Service
                    $serviceName = $item['DisplayName'];
                }

                // Agent 1.x was using BinPath which is not unique and makes no sense ?!
                // $item['BinPath']; // C:\Windows\system32\svchost.exe -k netsvc

                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [2 => $this->shortCommandargumentValue($match)])) {
                    $servicetemplatecommandargumentvalues[2]['value'] = $this->shortCommandargumentValue($match); // wisvc
                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Service: %s', $serviceName), // Windows Insider Service
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    public function getServiceStructForWindowsEventlogs() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('windows_eventlog');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['windows_eventlog'])) {
            $eventlogs = array_keys($this->agentResponse['windows_eventlog']);
            if (empty($eventlogs)) {
                //No Windows Event Logs (Application, Security, System, etc...) in agent output
                return false;
            }

            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($eventlogs as $item) {
                $logType = $item; // Application

                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $this->shortCommandargumentValue($logType)])) {
                    $servicetemplatecommandargumentvalues[0]['value'] = $this->shortCommandargumentValue($logType); // Application
                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Event log: %s', $item),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    private function getServiceStructForCustomchecks() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('customchecks');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['customchecks'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['customchecks'] as $checkName => $item) {
                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $this->shortCommandargumentValue($checkName)])) {
                    $servicetemplatecommandargumentvalues[0]['value'] = $this->shortCommandargumentValue($checkName); // check_whoami

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('Custom check: %s', $checkName),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|bool
     */
    public function getServiceStructForDocker(string $type) {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName($type);
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['docker'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['docker'] as $itemKey => $item) {
                switch ($type) {
                    case 'docker.running':
                        if (isset($item['name'])) {
                            $args = [
                                0 => 'name',
                                1 => $this->shortCommandargumentValue($item['name'])
                            ];

                            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], $args)) {
                                $servicetemplatecommandargumentvalues[0]['value'] = 'name'; // identifier
                                $servicetemplatecommandargumentvalues[1]['value'] = $this->shortCommandargumentValue($item['name']); // grafana
                                $services[] = $this->getServiceStruct(
                                    $agentcheck['servicetemplate_id'],
                                    sprintf('Container %s is running', $item['name']),
                                    $servicetemplatecommandargumentvalues
                                );
                            }
                        }
                        break;

                    case 'docker.cpu':
                        if (isset($item['name']) && isset($item['cpu_percentage'])) {
                            $args = [
                                0 => 'name',
                                1 => $this->shortCommandargumentValue($item['name'])
                            ];

                            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], $args)) {
                                $servicetemplatecommandargumentvalues[0]['value'] = 'name'; // identifier
                                $servicetemplatecommandargumentvalues[1]['value'] = $this->shortCommandargumentValue($item['name']); // grafana
                                $services[] = $this->getServiceStruct(
                                    $agentcheck['servicetemplate_id'],
                                    sprintf('Container %s CPU percentage', $item['name']),
                                    $servicetemplatecommandargumentvalues
                                );
                            }
                        }
                        break;

                    case 'docker.memory':
                        if (isset($item['name']) && isset($item['memory_used']) && isset($item['memory_used'])) {
                            $args = [
                                0 => 'name',
                                1 => $this->shortCommandargumentValue($item['name'])
                            ];

                            $currentUsedMb = $item['memory_used'] / 1024 / 1024;
                            $warning = $currentUsedMb + 265;
                            $critical = $currentUsedMb + 512;

                            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], $args)) {
                                $servicetemplatecommandargumentvalues[0]['value'] = 'name'; // identifier
                                $servicetemplatecommandargumentvalues[1]['value'] = $this->shortCommandargumentValue($item['name']); // grafana
                                $servicetemplatecommandargumentvalues[2]['value'] = $warning; // warning
                                $servicetemplatecommandargumentvalues[3]['value'] = $critical; // critical
                                $servicetemplatecommandargumentvalues[4]['value'] = '0'; // 1 == % 0 == MiB
                                $services[] = $this->getServiceStruct(
                                    $agentcheck['servicetemplate_id'],
                                    sprintf('Container %s Memory used', $item['name']),
                                    $servicetemplatecommandargumentvalues
                                );
                            }
                        }

                        break;

                    case 'docker.health':
                        // Agents >= 3.7 report the state of the healthcheck and restart loops
                        if (isset($item['name']) && array_key_exists('health', $item)) {
                            $args = [
                                0 => 'name',
                                1 => $this->shortCommandargumentValue($item['name'])
                            ];

                            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], $args)) {
                                $servicetemplatecommandargumentvalues[0]['value'] = 'name'; // identifier
                                $servicetemplatecommandargumentvalues[1]['value'] = $this->shortCommandargumentValue($item['name']); // grafana
                                $services[] = $this->getServiceStruct(
                                    $agentcheck['servicetemplate_id'],
                                    sprintf('Container %s health', $item['name']),
                                    $servicetemplatecommandargumentvalues
                                );
                            }
                        }
                        break;
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    private function shortCommandargumentValue(string $value) {
        return substr($value, 0, $this->maxLengthCommandArg);
    }

    private function shortServiceName(string $name) {
        return substr($name, 0, $this->maxLengthServiceName);
    }

    /**
     * @param int $servicetemplateId
     * @param null|array $commandarguments
     * @param string $commandargumentValue
     * @return bool
     */
    private function doesServiceAlreadyExists(int $servicetemplateId, $commandarguments = null) {
        if ($this->onlyMissingServices === false) {
            // Show all services that are possible to create
            return false;
        }

        if (empty($this->existingServicesCache)) {
            //No agent services at all on this host
            return false;
        }

        $servicesToCheck = Hash::extract($this->existingServicesCache, '{n}[servicetemplate_id=' . $servicetemplateId . ']');
        if (!is_array($commandarguments)) {
            // cpu load / memory usage etc...

            // No service with the given servicetemplateId found.
            return !empty($servicesToCheck);
        }

        $servicesAlreadyExists = false;
        foreach ($servicesToCheck as $service) {
            $commandargumentsToCompare = [];
            foreach ($commandarguments as $argPos => $argValue) {
                if (isset($service['servicecommandargumentvalues'][$argPos])) {
                    $commandargumentsToCompare[$argPos] = $service['servicecommandargumentvalues'][$argPos]['value'];
                }
            }

            if (sizeof($commandargumentsToCompare) != sizeof($commandarguments)) {
                return false;
            }

            if ($servicesAlreadyExists === false) {
                $servicesAlreadyExists = empty(Hash::diff($commandargumentsToCompare, $commandarguments));
            } else {
                // Service found !
                return true;
            }
        }

        return $servicesAlreadyExists;
    }

    private function getServiceStructForLibvirt() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('libvirt');
        if (empty($agentcheck)) {
            return false;
        }
        $services = [];
        if (isset($this->agentResponse['libvirt']) && !isset($this->agentResponse['libvirt']['error'])) {
            $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
            foreach ($this->agentResponse['libvirt'] as $checkName => $item) {
                if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $item['Uuid']])) {
                    $servicetemplatecommandargumentvalues[0]['value'] = $item['Uuid']; // 1e6a8d99-471e-493a-8490-bf9eb5487951

                    $services[] = $this->getServiceStruct(
                        $agentcheck['servicetemplate_id'],
                        sprintf('VM: %s', $item['Name']),
                        $servicetemplatecommandargumentvalues
                    );
                }
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return bool
     */
    private function isWindows() {
        return ($this->agentResponse['agent']['goos'] ?? '') === 'windows';
    }

    /**
     * Creates a service for every value, e.g. for every definition of the logfiles.ini of the agent
     *
     * @param string $agentcheckName
     * @param int $argIndex Index of the command argument which gets the value
     * @param array $values value => name of the service, e.g. 'nginx_errors' => 'nginx_errors'
     * @param string $nameFormat e.g. 'Logfile: %s'
     * @return array|false
     */
    private function getServiceStructsForValues(string $agentcheckName, int $argIndex, array $values, string $nameFormat) {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName($agentcheckName);
        if (empty($agentcheck) || empty($values)) {
            return false;
        }

        $services = [];
        $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
        foreach ($values as $value => $name) {
            $value = $this->shortCommandargumentValue((string)$value);
            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [$argIndex => $value])) {
                $servicetemplatecommandargumentvalues[$argIndex]['value'] = $value;
                $services[] = $this->getServiceStruct(
                    $agentcheck['servicetemplate_id'],
                    sprintf($nameFormat, $name),
                    $servicetemplatecommandargumentvalues
                );
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * @return array|false
     */
    private function getServiceStructForDiskInodes() {
        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('disks_inodes');
        if (empty($agentcheck) || !is_array($this->agentResponse['disks'] ?? null) || isset($this->agentResponse['disks']['error'])) {
            return false;
        }

        $services = [];
        $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
        foreach ($this->agentResponse['disks'] as $device) {
            // Filesystems without inodes (e.g. btrfs, vfat or Windows) report 0 inodes.
            // ZFS has no fixed number of inodes and FUSE filesystems (e.g. /etc/pve) report virtual values.
            $fstype = strtolower($device['disk']['fstype'] ?? '');
            if (empty($device['usage']['inodes_total']) || !isset($device['disk']['mountpoint']) || $fstype === 'zfs' || str_starts_with($fstype, 'fuse')) {
                continue;
            }
            $mountpoint = $this->shortCommandargumentValue($device['disk']['mountpoint']);
            if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [2 => $mountpoint])) {
                $servicetemplatecommandargumentvalues[2]['value'] = $mountpoint;
                $services[] = $this->getServiceStruct(
                    $agentcheck['servicetemplate_id'],
                    sprintf('Inode usage of: %s (%s)', $device['disk']['mountpoint'], $device['disk']['device'] ?? ''),
                    $servicetemplatecommandargumentvalues
                );
            }
        }
        if (empty($services)) {
            return false;
        }
        return $services;
    }

    /**
     * Linux system health: software RAID, OOM kills, file descriptors, conntrack and pressure stall information
     *
     * @return array
     */
    private function getServiceStructsForSystemHealth() {
        $services = [];
        $health = $this->agentResponse['system_health'];
        if (!is_array($health) || isset($health['error'])) {
            return $services;
        }

        $parts = [
            'raid'             => ['system_health.raid', 'Software RAID'],
            'oom_kills'        => ['system_health.oom', 'OOM kills'],
            'file_descriptors' => ['system_health.file_descriptors', 'File descriptors'],
            'conntrack'        => ['system_health.conntrack', 'Conntrack table'],
            'filesystem_errors' => ['system_health.filesystem_errors', 'Filesystem errors'],
            'edac'             => ['system_health.edac', 'Memory errors (ECC)'],
        ];
        foreach ($parts as $part => [$name, $servicename]) {
            if (!isset($health[$part]) || !is_array($health[$part]) || isset($health[$part]['error'])) {
                continue;
            }
            if ($part === 'raid' && empty($health[$part])) {
                // No software RAID arrays
                continue;
            }
            if ($part === 'conntrack' && empty($health[$part]['available'])) {
                // The conntrack module is not loaded
                continue;
            }
            if ($part === 'filesystem_errors' && empty($health[$part])) {
                // No ext4 or btrfs filesystems
                continue;
            }
            if ($part === 'edac' && empty($health[$part]['available'])) {
                // No ECC memory or a virtual machine
                continue;
            }
            $service = $this->getServiceStructByName($name, $servicename);
            if ($service) {
                $services['system_health_' . $part] = $service;
            }
        }

        // One service per network bond
        if (is_array($health['bonding'] ?? null) && !isset($health['bonding']['error'])) {
            $bonds = [];
            foreach ($health['bonding'] as $bond) {
                if (isset($bond['name'])) {
                    $bonds[$bond['name']] = $bond['name'];
                }
            }
            $bondServices = $this->getServiceStructsForValues('system_health.bonding', 0, $bonds, 'Network bond: %s');
            if ($bondServices) {
                $services['system_health_bonding'] = $bondServices;
            }
        }

        // One service per resource (cpu, memory, io) with suitable thresholds
        if (!empty($health['pressure']['available']) && is_array($health['pressure']['resources'] ?? null)) {
            $agentcheck = $this->AgentchecksTable->getAgentcheckByName('system_health.pressure');
            if (!empty($agentcheck)) {
                $thresholds = [
                    'cpu'    => ['40', '80'],
                    'memory' => ['10', '25'],
                    'io'     => ['30', '60']
                ];
                $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
                foreach ($thresholds as $resource => [$warning, $critical]) {
                    if (!isset($health['pressure']['resources'][$resource])) {
                        continue;
                    }
                    if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $resource])) {
                        $servicetemplatecommandargumentvalues[0]['value'] = $resource;
                        $servicetemplatecommandargumentvalues[1]['value'] = $warning;
                        $servicetemplatecommandargumentvalues[2]['value'] = $critical;
                        $services['system_health_pressure'][] = $this->getServiceStruct(
                            $agentcheck['servicetemplate_id'],
                            sprintf('Pressure stall information: %s', $resource),
                            $servicetemplatecommandargumentvalues
                        );
                    }
                }
            }
        }
        return $services;
    }

    /**
     * Firewall (Windows, macOS), SELinux, AppArmor (Linux), FileVault, SIP (macOS) and Microsoft Defender (Windows)
     *
     * @return array
     */
    private function getServiceStructsForSecurity() {
        $services = [];
        $security = $this->agentResponse['security'];
        if (!is_array($security) || isset($security['error'])) {
            return $services;
        }

        $parts = [
            'firewall'  => ['security.firewall', 'Firewall'],
            'apparmor'  => ['security.apparmor', 'AppArmor'],
            'filevault' => ['security.filevault', 'FileVault'],
            'sip'       => ['security.sip', 'System Integrity Protection'],
            'defender'  => ['security.defender', 'Microsoft Defender'],
            'bitlocker' => ['security.bitlocker', 'BitLocker'],
        ];
        foreach ($parts as $part => [$name, $servicename]) {
            if (!isset($security[$part]) || !is_array($security[$part]) || isset($security[$part]['error'])) {
                continue;
            }
            if ($part === 'apparmor' && empty($security[$part]['enabled'])) {
                continue;
            }
            if ($part === 'defender' && empty($security[$part]['available'])) {
                // Microsoft Defender is not installed or replaced by another antivirus
                continue;
            }
            if ($part === 'firewall' && array_key_exists('active', $security[$part]) && empty($security[$part]['active'])) {
                // Linux without firewall rules for incoming connections, a service would be critical right away
                continue;
            }
            if ($part === 'bitlocker' && empty($security[$part]['available'])) {
                // BitLocker is not installed (e.g. Windows Home)
                continue;
            }
            $service = $this->getServiceStructByName($name, $servicename);
            if ($service) {
                $services['security_' . $part] = $service;
            }
        }

        // SELinux: the current mode is expected, so a system in permissive mode does not raise an alert right away
        $mode = $security['selinux']['mode'] ?? '';
        if (in_array($mode, ['enforcing', 'permissive'], true)) {
            $agentcheck = $this->AgentchecksTable->getAgentcheckByName('security.selinux');
            if (!empty($agentcheck) && !$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'])) {
                $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
                $servicetemplatecommandargumentvalues[0]['value'] = $mode;
                $services['security_selinux'] = $this->getServiceStruct($agentcheck['servicetemplate_id'], 'SELinux', $servicetemplatecommandargumentvalues);
            }
        }
        return $services;
    }

    /**
     * Proxmox VE: cluster, storages, backups, replication and the guests of the local node
     *
     * @return array
     */
    private function getServiceStructsForProxmox() {
        $services = [];
        $proxmox = $this->agentResponse['proxmox'];
        if (!is_array($proxmox) || isset($proxmox['error'])) {
            return $services;
        }

        $parts = [
            'cluster'     => ['proxmox.cluster', 'Proxmox cluster'],
            'backups'     => ['proxmox.backups', 'Proxmox backups'],
            'replication' => ['proxmox.replication', 'Proxmox replication'],
        ];
        foreach ($parts as $part => [$name, $servicename]) {
            if (!isset($proxmox[$part]) || !is_array($proxmox[$part]) || isset($proxmox[$part]['error'])) {
                continue;
            }
            if ($part === 'replication' && empty($proxmox[$part])) {
                continue;
            }
            $service = $this->getServiceStructByName($name, $servicename);
            if ($service) {
                $services['proxmox_' . $part] = $service;
            }
        }

        // One service per enabled storage, so every storage can get its own thresholds (e.g. a backup storage which is always quite full)
        if (is_array($proxmox['storages'] ?? null) && !isset($proxmox['storages']['error'])) {
            $storages = [];
            foreach ($proxmox['storages'] as $storage) {
                if (isset($storage['storage']) && !empty($storage['enabled'])) {
                    $storages[$storage['storage']] = $storage['storage'];
                }
            }
            $storageServices = $this->getServiceStructsForValues('proxmox.storages', 0, $storages, 'Proxmox storage: %s');
            if ($storageServices) {
                $services['proxmox_storages'] = $storageServices;
            }
        }

        // One service per guest of the local node, the current state is the expected state
        if (is_array($proxmox['guests'] ?? null) && !isset($proxmox['guests']['error'])) {
            $agentcheck = $this->AgentchecksTable->getAgentcheckByName('proxmox.guests');
            if (!empty($agentcheck)) {
                $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
                foreach ($proxmox['guests'] as $guest) {
                    if (!empty($guest['template']) || ($guest['node'] ?? '') !== ($proxmox['node'] ?? '') || !isset($guest['vmid'])) {
                        continue;
                    }
                    $vmid = (string)$guest['vmid'];
                    if (!$this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => $vmid])) {
                        $servicetemplatecommandargumentvalues[0]['value'] = $vmid;
                        $servicetemplatecommandargumentvalues[1]['value'] = ($guest['status'] ?? '') === 'running' ? 'running' : 'stopped';
                        $services['proxmox_guests'][] = $this->getServiceStruct(
                            $agentcheck['servicetemplate_id'],
                            sprintf('Proxmox %s %s: %s', ($guest['type'] ?? '') === 'lxc' ? 'CT' : 'VM', $vmid, $guest['name'] ?? ''),
                            $servicetemplatecommandargumentvalues
                        );
                    }
                }
            }
        }
        return $services;
    }

    /**
     * Network ports: one service per listening TCP port, TCP states, ephemeral ports and TCP errors (Linux).
     * Ports of the ephemeral range above 32767 are skipped, as they change with every start of the process
     * (e.g. the dynamic RPC ports of Windows). Processes with more than MAX_PORTS_PER_PROCESS ports (e.g. Ceph OSDs)
     * get one service for the process instead of one service per port, except SHARED_PORT_PROCESSES.
     * Unexpected ports are not suggested, as the allowed ports are unknown.
     *
     * @return array
     */
    private function getServiceStructsForNetworkPorts() {
        $services = [];
        $networkPorts = $this->agentResponse['network_ports'];
        if (!is_array($networkPorts) || isset($networkPorts['error'])) {
            return $services;
        }

        $parts = [
            'tcp_states'      => ['network_ports.tcp_states', 'TCP connection states'],
            'ephemeral_ports' => ['network_ports.ephemeral_ports', 'Ephemeral ports'],
            'tcp_errors'      => ['network_ports.tcp_errors', 'TCP errors'],
        ];
        foreach ($parts as $part => [$name, $servicename]) {
            if (!is_array($networkPorts[$part] ?? null)) {
                continue;
            }
            $service = $this->getServiceStructByName($name, $servicename);
            if ($service) {
                $services['network_ports_' . $part] = $service;
            }
        }

        $agentcheck = $this->AgentchecksTable->getAgentcheckByName('network_ports.listener');
        if (empty($agentcheck) || !is_array($networkPorts['listeners'] ?? null)) {
            return $services;
        }

        $ephemeralFirst = max(32768, (int)($networkPorts['ephemeral_ports']['first'] ?? 32768));
        $ports = [];
        foreach ($networkPorts['listeners'] as $listener) {
            if (($listener['protocol'] ?? '') !== 'tcp' || empty($listener['port']) || $listener['port'] >= $ephemeralFirst) {
                continue;
            }
            $port = (int)$listener['port'];
            if (!isset($ports[$port]) || $ports[$port] === '') {
                $ports[$port] = (string)($listener['process'] ?? '');
            }
        }
        ksort($ports);

        $portsPerProcess = [];
        foreach ($ports as $port => $process) {
            if ($process !== '' && !in_array($process, self::SHARED_PORT_PROCESSES, true)) {
                $portsPerProcess[$process][] = $port;
            }
        }

        $servicetemplatecommandargumentvalues = $agentcheck['servicetemplate']['servicetemplatecommandargumentvalues'];
        $processServices = [];
        foreach ($ports as $port => $process) {
            // A hyphen outside of a character class does not need to be escaped (e.g. ^ceph-osd$)
            $processRegex = $process !== '' ? $this->shortCommandargumentValue('^' . str_replace('\\-', '-', preg_quote($process, '#')) . '$') : '';
            $args = $servicetemplatecommandargumentvalues;
            $args[1]['value'] = 'tcp';
            $args[2]['value'] = $processRegex;

            if (sizeof($portsPerProcess[$process] ?? []) > self::MAX_PORTS_PER_PROCESS) {
                if (isset($processServices[$process]) || $this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => '', 2 => $processRegex])) {
                    continue;
                }
                $processServices[$process] = true;
                $args[0]['value'] = '';
                $services['network_ports'][] = $this->getServiceStruct(
                    $agentcheck['servicetemplate_id'],
                    sprintf('Ports of %s', $process),
                    $args
                );
                continue;
            }

            if ($this->doesServiceAlreadyExists($agentcheck['servicetemplate_id'], [0 => (string)$port])) {
                continue;
            }
            $args[0]['value'] = (string)$port;
            $services['network_ports'][] = $this->getServiceStruct(
                $agentcheck['servicetemplate_id'],
                $process !== '' ? sprintf('Port tcp/%s (%s)', $port, $process) : sprintf('Port tcp/%s', $port),
                $args
            );
        }
        return $services;
    }

    /**
     * IPMI (local BMC): health, one service per sensor type with readings and the SEL
     *
     * @return array
     */
    private function getServiceStructsForIpmi() {
        $services = [];
        $ipmi = $this->agentResponse['ipmi'];
        if (!is_array($ipmi) || isset($ipmi['error']) || !is_array($ipmi['sensors'] ?? null)) {
            return $services;
        }

        $health = $this->getServiceStructByName('ipmi.health', 'IPMI health');
        if ($health) {
            $services['ipmi_health'] = $health;
        }

        // Sensor types with readings, the order is the order of the services
        $types = ['temperature' => 'IPMI temperatures', 'fan' => 'IPMI fans', 'voltage' => 'IPMI voltages', 'power' => 'IPMI power consumption'];
        $available = [];
        foreach ($ipmi['sensors'] as $sensor) {
            if (!isset($sensor['reading'])) {
                continue;
            }
            if (($sensor['type'] ?? '') === 'Temperature') {
                $available['temperature'] = $types['temperature'];
            } else if (($sensor['type'] ?? '') === 'Fan') {
                $available['fan'] = $types['fan'];
            } else if (($sensor['type'] ?? '') === 'Voltage') {
                $available['voltage'] = $types['voltage'];
            } else if (($sensor['unit'] ?? '') === 'Watts') {
                $available['power'] = $types['power'];
            }
        }
        $available = array_intersect_key($types, $available);
        $sensorServices = $this->getServiceStructsForValues('ipmi.sensors', 0, $available, '%s');
        if ($sensorServices) {
            $services['ipmi_sensors'] = $sensorServices;
        }

        if (is_array($ipmi['sel'] ?? null)) {
            $sel = $this->getServiceStructByName('ipmi.sel', 'IPMI event log');
            if ($sel) {
                $services['ipmi_sel'] = $sel;
            }
        }
        return $services;
    }

    /**
     * Redfish: health, sensors, power, storage and event log of every BMC of the redfish.ini of the agent
     *
     * @return array
     */
    private function getServiceStructsForRedfish() {
        $services = [];
        $redfish = $this->agentResponse['redfish'];
        if (!is_array($redfish) || (isset($redfish['error']) && !is_array($redfish['error']))) {
            return $services;
        }

        $checks = [
            'redfish.health'   => 'health',
            'redfish.sensors'  => 'sensors',
            'redfish.power'    => 'power',
            'redfish.storage'  => 'storage',
            'redfish.eventlog' => 'event log'
        ];
        foreach ($checks as $name => $description) {
            $bmcs = [];
            foreach ($redfish as $bmcName => $bmc) {
                $collected = isset($bmc['collected_at']) && !isset($bmc['error']);
                // The event log is optional (eventlog = false in the redfish.ini)
                if ($name === 'redfish.eventlog' && $collected && !isset($bmc['eventlog'])) {
                    continue;
                }
                // Not all BMCs report the storage of the server
                if ($name === 'redfish.storage' && $collected && empty($bmc['storage'])) {
                    continue;
                }
                $bmcs[$bmcName] = $bmcName;
            }
            $bmcServices = $this->getServiceStructsForValues($name, 0, $bmcs, 'Redfish %s: ' . $description);
            if ($bmcServices) {
                $services[str_replace('.', '_', $name)] = $bmcServices;
            }
        }
        return $services;
    }
}
