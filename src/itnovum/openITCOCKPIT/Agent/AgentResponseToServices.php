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

namespace itnovum\openITCOCKPIT\Agent;


use App\Model\Table\AgentchecksTable;
use App\Model\Table\ServicecommandargumentvaluesTable;
use App\Model\Table\ServicesTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class AgentResponseToServices {

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
                    break;
                case 'systemd_services':
                    $systemdServices = $this->getServiceStructForSystemdServices();
                    if ($systemdServices) {
                        $services['systemd_services'] = $systemdServices;
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
                        'docker.memory'
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
                        sprintf('Disk stats of: %s', $deviceName),
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
                        sprintf('Network stats of: %s', $nicName),
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
}
