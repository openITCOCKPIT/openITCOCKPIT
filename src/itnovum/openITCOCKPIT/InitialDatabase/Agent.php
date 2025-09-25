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

namespace itnovum\openITCOCKPIT\InitialDatabase;

use App\Model\Entity\Commandargument;
use App\Model\Entity\Servicetemplatecommandargumentvalue;
use App\Model\Table\AgentchecksTable;
use App\Model\Table\CommandsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * Class Cronjob
 * @package itnovum\openITCOCKPIT\InitialDatabase
 */
class Agent extends Importer {

    /**
     * @var CommandsTable
     */
    private $CommandsTable;

    /**
     * @var HosttemplatesTable
     */
    private $HosttemplatesTable;

    /**
     * @var ServicetemplatesTable
     */
    private $ServicetemplatesTable;

    /**
     * @var AgentchecksTable
     */
    private $AgentchecksTable;

    /**
     * Agent constructor.
     * @param CommandsTable $CommandsTable
     * @param HosttemplatesTable $HosttemplatesTable
     * @param ServicetemplatesTable $ServicetemplatesTable
     * @param AgentchecksTable $AgentchecksTable
     */
    public function __construct(CommandsTable $CommandsTable, HosttemplatesTable $HosttemplatesTable, ServicetemplatesTable $ServicetemplatesTable, AgentchecksTable $AgentchecksTable) {
        $this->CommandsTable = $CommandsTable;
        $this->HosttemplatesTable = $HosttemplatesTable;
        $this->ServicetemplatesTable = $ServicetemplatesTable;
        $this->AgentchecksTable = $AgentchecksTable;
    }

    /**
     * @return bool
     */
    public function import() {
        $data = $this->getData();

        foreach ($data['Commands'] as $command) {
            if (isset($command['uuid']) && !$this->CommandsTable->existsByUuid($command['uuid'])) {
                $entity = $this->CommandsTable->newEntity($command);
                $this->CommandsTable->save($entity);
            }
        }

        foreach ($data['Hosttemplates'] as $hosttemplate) {
            if (isset($hosttemplate['uuid']) && !$this->HosttemplatesTable->existsByUuid($hosttemplate['uuid'])) {
                if (isset($hosttemplate['command_id']) && $this->CommandsTable->existsByUuid($hosttemplate['command_id'])) {
                    $command = $this->CommandsTable->getCommandByUuid($hosttemplate['command_id'], true, false)[0];
                    $hosttemplate['command_id'] = $command['id'];
                    if (!empty($hosttemplate['hosttemplatecommandargumentvalues']) && !empty($command['commandarguments'])) {
                        foreach ($hosttemplate['hosttemplatecommandargumentvalues'] as $templateArgumentKey => $templateArgumentValue) {
                            $hosttemplate['hosttemplatecommandargumentvalues'][$templateArgumentKey] = [
                                'commandargument_id' => $this->getCommandArgumentIdByName($templateArgumentValue['commandargument_id'], $command['commandarguments']),
                                'value'              => $templateArgumentValue['value'],
                            ];
                        }
                    }
                    $entity = $this->HosttemplatesTable->newEntity($hosttemplate);
                    $this->HosttemplatesTable->save($entity);
                }
            }
        }

        foreach ($data['Servicetemplates'] as $servicetemplate) {
            if (isset($servicetemplate['uuid']) && !$this->ServicetemplatesTable->existsByUuid($servicetemplate['uuid'])) {
                if (isset($servicetemplate['command_id']) && $this->CommandsTable->existsByUuid($servicetemplate['command_id'])) {
                    $command = $this->CommandsTable->getCommandByUuid($servicetemplate['command_id'], true, false)[0];
                    $servicetemplate['command_id'] = $command['id'];
                    if (!empty($servicetemplate['servicetemplatecommandargumentvalues']) && !empty($command['commandarguments'])) {
                        foreach ($servicetemplate['servicetemplatecommandargumentvalues'] as $templateArgumentKey => $templateArgumentValue) {
                            $servicetemplate['servicetemplatecommandargumentvalues'][$templateArgumentKey] = [
                                'commandargument_id' => $this->getCommandArgumentIdByName($templateArgumentValue['commandargument_id'], $command['commandarguments']),
                                'value'              => $templateArgumentValue['value'],
                            ];
                        }
                    }
                    $entity = $this->ServicetemplatesTable->newEntity($servicetemplate);
                    $this->ServicetemplatesTable->save($entity);
                }
            }
        }

        // Delete old 1.x legacy Agentchecks
        /*
        $legacyServicetemplateUuids = [
            'be4c9649-8771-4704-b409-c56b5f67abc8',
            '3e0bd59e-822d-47ed-a5b2-15f1e53fe043',
            'f73dc076-bdb0-4302-9776-88ca1ba79364',
            '21057a75-57a1-4972-8f2f-073c8a6000b0',
            'dda2d5dc-7987-49a5-b4c2-0540e8aaf45e',
            'ca73653f-2bba-4542-b11b-0bbd0ecc8b7a',
            'a9f7757e-34b0-4df9-8fca-ab8b594c2c26',
            'aef4c1a8-ed71-4799-a164-3ad469baadc5'
        ];
        foreach ($legacyServicetemplateUuids as $legacyServicetemplateUuid) {
            $servicetemplate = $this->ServicetemplatesTable->getServicetemplateByUuid($legacyServicetemplateUuid, [], false);
            if (!empty($servicetemplate)) {
                try {
                    $agentcheck = $this->AgentchecksTable->find()
                        ->where(['servicetemplate_id' => $servicetemplate['id']])
                        ->firstOrFail();
                    $this->AgentchecksTable->delete($agentcheck);
                } catch (RecordNotFoundException $e) {
                    //Nothing to delete
                }
            }
        }
        */

        // Create new Agent Checks
        foreach ($data['Agentchecks'] as $agentcheck) {
            //debug($this->ServicetemplatesTable->getServicetemplateByUuid($agentcheck['servicetemplate_id']));die();
            $servicetemplate = $this->ServicetemplatesTable->getServicetemplateByUuid($agentcheck['servicetemplate_id']);
            if (isset($servicetemplate['Servicetemplate']) && isset($servicetemplate['Servicetemplate']['id'])) {
                $agentcheck['servicetemplate_id'] = $servicetemplate['Servicetemplate']['id'];

                if (!$this->AgentchecksTable->existsByNameAndServicetemplateId($agentcheck['name'], $agentcheck['servicetemplate_id'])) {
                    $entity = $this->AgentchecksTable->newEntity($agentcheck);
                    $this->AgentchecksTable->save($entity);
                }
            }
        }

        // ITC-2939
        // Add new Command arguments to existing commands and service templates
        $this->updateRecords();

        return true;
    }

    /**
     * @param string $name
     * @param array $commandarguments
     * @return bool|mixed
     */
    public function getCommandArgumentIdByName(string $name, array $commandarguments) {
        foreach ($commandarguments as $commandargument) {
            if (isset($commandargument['name']) && $commandargument['name'] === $name) {
                return $commandargument['id'];
            }
        }
        return false;
    }

    public function getAgentchecksData() {
        $data = [
            [
                'name'               => 'agent',
                'plugin_name'        => 'Agent',
                'servicetemplate_id' => 'c475f1c8-fd28-493d-aad0-7861e418170d'
            ],
            [
                'name'               => 'agent.system_uptime',
                'plugin_name'        => 'SystemUptime',
                'servicetemplate_id' => 'ca9158a8-39a7-4ada-ba69-1fe5865e4ab9'
            ],
            // Agent 1.x legacy - delete this
            //[
            //    'name'               => 'cpu_percentage',
            //    'plugin_name'        => 'CpuTotalPercentage',
            //    'servicetemplate_id' => 'be4c9649-8771-4704-b409-c56b5f67abc8'
            //],
            // Agent 3.x (can use the sam command and service template as 1.x)
            [
                'name'               => 'cpu.cpu_percentage',
                'plugin_name'        => 'CpuTotalPercentage',
                'servicetemplate_id' => 'be4c9649-8771-4704-b409-c56b5f67abc8'
            ],
            [
                'name'               => 'system_load',
                'plugin_name'        => 'SystemLoad',
                'servicetemplate_id' => '566a710f-a554-4fa6-b2a2-e46b1d937a64'
            ],
            [
                'name'               => 'memory',
                'plugin_name'        => 'MemoryUsage',
                'servicetemplate_id' => '4052fe4f-50b1-443a-8be1-9dbca8d43ebd'
            ],
            [
                'name'               => 'swap',
                'plugin_name'        => 'SwapUsage',
                'servicetemplate_id' => 'f9d5e18a-8894-4324-b54a-497db87b6f4f'
            ],
            [
                'name'               => 'disk_io',
                'plugin_name'        => 'DiskIO',
                'servicetemplate_id' => '68fb72a3-5bf5-4d97-8690-91242628659b'
            ],
            [
                'name'               => 'disks',
                'plugin_name'        => 'DiskUsage',
                'servicetemplate_id' => '24851d0d-32fa-4048-bd67-6a30d710bba1'
            ],
            [
                'name'               => 'disks_free', // Virtual JSON field
                'plugin_name'        => 'DiskFree',
                'servicetemplate_id' => 'bdc40fb0-3efe-4372-95bf-aaca0b7761d6'
            ],
            // Agent 1.x legacy - delete this
            //[
            //    'name'               => 'sensors',
            //    'plugin_name'        => 'Fan',
            //    'servicetemplate_id' => '3e0bd59e-822d-47ed-a5b2-15f1e53fe043'
            //],
            // Agent 1.x legacy - delete this
            [
                'name'               => 'sensors',
                'plugin_name'        => 'Temperature',
                'servicetemplate_id' => 'f73dc076-bdb0-4302-9776-88ca1ba79364'
            ],
            // Agent 3.x
            [
                'name'               => 'sensors.Temperatures',
                'plugin_name'        => 'Temperature',
                'servicetemplate_id' => 'a73aa635-a0d0-463a-b065-9c8fe9a5a20b'
            ],
            // Agent 1.x legacy - delete this
            //[
            //    'name'               => 'sensors',
            //    'plugin_name'        => 'Battery',
            //    'servicetemplate_id' => '21057a75-57a1-4972-8f2f-073c8a6000b0'
            //],
            // Agent 3.x
            [
                'name'               => 'sensors.Batteries',
                'plugin_name'        => 'Battery',
                'servicetemplate_id' => '7d2961c3-b02b-4727-b870-5b391628b96f'
            ],

            [
                'name'               => 'net_io',
                'plugin_name'        => 'NetIO',
                'servicetemplate_id' => 'e5d848f5-a323-4bfe-9ec5-1c5cdf138abf'
            ],
            [
                'name'               => 'net_stats',
                'plugin_name'        => 'NetStats',
                'servicetemplate_id' => 'f6f64207-ef75-4a3d-b9f2-2eaab398a6f1'
            ],
            [
                'name'               => 'processes',
                'plugin_name'        => 'Process',
                'servicetemplate_id' => '37a78eca-4a58-46cd-9fb1-6029724cab35'
            ],
            [
                'name'               => 'windows_services',
                'plugin_name'        => 'WindowsService',
                'servicetemplate_id' => '370731ed-34d4-48f4-933e-90e488bb390f'
            ],
            [
                'name'               => 'systemd_services',
                'plugin_name'        => 'SystemdService',
                'servicetemplate_id' => 'd21e3462-7430-4d38-b92a-853bcfc12356'
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'               => 'windows_eventlog',
            //    'plugin_name'        => 'WindowsEventlog',
            //    'servicetemplate_id' => 'dda2d5dc-7987-49a5-b4c2-0540e8aaf45e'
            //],
            // Agent 3.x
            [
                'name'               => 'windows_eventlog',
                'plugin_name'        => 'WindowsEventlog',
                'servicetemplate_id' => 'f9205afd-3530-4b65-b6cc-347a652f7b66'
            ],

            // Agent 1.x legacy - delete this
            [
                'name'               => 'dockerstats',
                'plugin_name'        => 'DockerContainerRunning',
                'servicetemplate_id' => 'ca73653f-2bba-4542-b11b-0bbd0ecc8b7a'
            ],
            // Agent 3.x
            [
                'name'               => 'docker.running',
                'plugin_name'        => 'DockerContainerRunning',
                'servicetemplate_id' => 'ca73653f-2bba-4542-b11b-0bbd0ecc8b7a'
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'               => 'dockerstats',
            //    'plugin_name'        => 'DockerContainerCPU',
            //    'servicetemplate_id' => 'a9f7757e-34b0-4df9-8fca-ab8b594c2c26'
            //],
            // Agent 3.x
            [
                'name'               => 'docker.cpu',
                'plugin_name'        => 'DockerContainerCPU',
                'servicetemplate_id' => 'a9f7757e-34b0-4df9-8fca-ab8b594c2c26'
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'               => 'dockerstats',
            //    'plugin_name'        => 'DockerContainerMemory',
            //    'servicetemplate_id' => 'aef4c1a8-ed71-4799-a164-3ad469baadc5'
            //],
            // Agent 3.x
            [
                'name'               => 'docker.memory',
                'plugin_name'        => 'DockerContainerMemory',
                'servicetemplate_id' => '7f86be31-0bb9-45f1-82bd-898deeba2cbd'
            ],

            [
                'name'               => 'qemustats',
                'plugin_name'        => 'QemuVMRunning',
                'servicetemplate_id' => 'c1c8c77a-cecf-4a94-8418-a69081946ba0'
            ],
            [
                'name'               => 'customchecks',
                'plugin_name'        => 'Customcheck',
                'servicetemplate_id' => '03b32b83-df7b-4204-a8bb-138a4939c554'
            ],
            [
                'name'               => 'alfrescostats',
                'plugin_name'        => 'Alfresco',
                'servicetemplate_id' => 'e453767c-b216-4b17-a012-9bc037d7da49'
            ],
            [
                'name'               => 'launchd_services',
                'plugin_name'        => 'Launchd',
                'servicetemplate_id' => '7370c78c-fc04-459b-9fc8-39a76bbe0fba'
            ],
            [
                'name'               => 'libvirt',
                'plugin_name'        => 'Libvirt',
                'servicetemplate_id' => '4a0d6a78-8dd5-47c7-9edc-ed6a2aebce3a'
            ],
            [
                'name'               => 'ntp',
                'plugin_name'        => 'Ntp',
                'servicetemplate_id' => 'f3aadc86-fcc6-4d36-abce-50d0af593e44'
            ],
        ];
        return $data;
    }

    public function getCommandsData() {
        $data = [
            [
                'name'             => 'check_oitc_agent_active',
                'command_line'     => '/opt/openitc/receiver/bin/poller.php poller -H $HOSTNAME$ -c /opt/openitc/receiver/etc/production.json',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'be116ff1-f797-4ccb-993c-b80ccb337de8',
                'description'      => "Actively executed by the monitoring engine.\nSend HTTP-Request to the target device and query the openITCOCKPIT Agent API interface.",
                'commandarguments' => []
            ],

            [
                'name'             => 'check_oitc_agent_system_uptime',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '46b1d040-af5a-4b37-bf10-ab7e42b2882e',
                'description'      => "Checks the Uptime of the Operating System.\n" .
                    "Warning: Value in seconds. E.g.: 20:1000 Would be warning if the uptime is < 20 or > 1000 \n" .
                    "Critical: Value in seconds. E.g.: 10:2000 Would be critical if the uptime is < 10 or > 2000 \n" .
                    "By default, this plugin does not use a threshold! \n" .
                    "This plugin uses threshold ranges by default: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT \n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in seconds (range)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in seconds (range)'
                    ],
                ]
            ],

            [
                'name'             => 'check_oitc_agent_cpu_total_percentage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '27551d06-610d-4331-af2d-aab77c45bd36',
                'description'      => "Check CPU usage in percentage.\nWarning and Critical thresholds are percentage values from 0-100\n If per core is set to 1 a seperate gauge per cpu core will be generated.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Per core 1/0'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_system_load',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'd4e55865-5cbc-4f01-a4e3-896074a1d7d1',
                'description'      => "Check system load.\n" .
                    "Warning and Critical thresholds are integer values (e.g. 2 or 4).\n" .
                    "Timerange: Integer value that decides if thresholds will be used for load1, load5 or load15. (Value: 1 / 5 / 15)",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Timerange'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_memory_usage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'ab3fe6f2-b5c6-4ba0-9d72-123696d42277',
                'description'      => "Return memory usage as percentage\n" .
                    "Warning and Critical thresholds are percentage values from 0-100 or float values depending on the chosen unit." .
                    "Unit: Determines if warning and critical is defined as percentage (%) or amount of ('B', 'kB', 'KiB', 'MB', 'MiB', 'GB', 'GiB', 'TB', 'TiB', 'PB', 'PiB', 'EB', 'EiB')",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Unit'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_swap_usage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'ebff0db2-e5de-4430-8799-3c6662422646',
                'description'      => "Return swap usage as percentage\n" .
                    "Warning and Critical thresholds are percentage values from 0-100",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],


            [
                'name'             => 'check_oitc_agent_disk_io',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'ffd0a58f-01e0-4a4d-8db9-a3107a9e765f',
                'description'      => "Fetch disk io statistics.\n" .
                    "Linux only: Warning and Critical thresholds for disk load as percentage value.\n" .
                    "Device: Disk device name (e.g. sda1)",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Device'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_disk_usage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3830078a-ccc5-41fd-ae33-8369bca1a6b7',
                'description'      => "Checks used disk space of a device.\n" .
                    "Warning: percentage threshold (0-100) or GB value (20)\n" .
                    "Critical: percentage threshold (0-100) or GB value (10)\n" .
                    "Mountpoint: Mountpoint of the device. Examples: Linux: / Windows: C:\\\n" .
                    "Percentage: Determines if warning and critical is defined as percentage or amount of GB\n" .
                    "This plugin uses threshold ranges by default: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT \n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Mountpoint'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Unit GB or %'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_disk_free_space',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '131f581f-ced9-405a-ba9b-4e0e7276258c',
                'description'      => "Checks free disk space of a device.\n" .
                    "Warning: percentage threshold (0-100) or GB value (20)\n" .
                    "Critical: percentage threshold (0-100) or GB value (10)\n" .
                    "Mountpoint: Mountpoint of the device. Examples: Linux: / Windows: C:\\\n" .
                    "Percentage: Determines if warning and critical is defined as percentage or amount of GB\n" .
                    "This plugin uses threshold ranges by default: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT \n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Mountpoint'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Unit GB or %'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_fan',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '7bee1594-b029-4db1-8059-694a75b4f83e',
                'description'      => "Return fan speed of a given device.\n" .
                    "Warning: Fan RPM as integer\n" .
                    "Critical:  Fan RPM as integer\n" .
                    "Average: Use average fan speed, if a device has multiple fans.\n" .
                    "Device: Fan device name (e.g. cpu_fan) \n" .
                    "Only available on Linux and macOS.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Average'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Device'
                    ]
                ]
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'             => 'check_oitc_agent_temperature',
            //    'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
            //    'command_type'     => CHECK_COMMAND,
            //    'human_args'       => null,
            //    'uuid'             => '42e3e6c9-a989-4044-a993-193558ad7964',
            //    'description'      => "Return the temperature of a given sensor.\n" .
            //        "Warning: Temperature as integer\n" .
            //        "Critical: Temperature as integer\n" .
            //        "Average: Use average value of all temperatures, if a device has multiple temperature values (eg. for each core of one cpu device) \n" .
            //        "Device: Sensor name (e.g. coretemp / acpitz / pch_skylake) \n" .
            //        "Only available on Linux.\n",
            //    'commandarguments' => [
            //        [
            //            'name'       => '$ARG1$',
            //            'human_name' => 'Warning'
            //        ],
            //        [
            //            'name'       => '$ARG2$',
            //            'human_name' => 'Critical'
            //        ],
            //        [
            //            'name'       => '$ARG3$',
            //            'human_name' => 'Average 1/0'
            //        ],
            //        [
            //            'name'       => '$ARG4$',
            //            'human_name' => 'Device'
            //        ]
            //    ]
            //],
            // Agent 3.x
            [
                'name'             => 'check_oitc_agent3_temperature',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '348a8ece-6bd1-4908-8524-a00893aff602',
                'description'      => "Return the temperature of a given sensor.\n" .
                    "Warning: Temperature as integer\n" .
                    "Critical: Temperature as integer\n" .
                    "Sensor: Sensor name (e.g. coretemp / acpitz / pch_skylake) \n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Sensor'
                    ]
                ]
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'             => 'check_oitc_agent_battery',
            //    'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
            //    'command_type'     => CHECK_COMMAND,
            //    'human_args'       => null,
            //    'uuid'             => '02289d0e-0a3c-46e5-94f1-b18e57be4e70',
            //    'description'      => "Check battery power left in percentage. \nWarning and Critical thresholds are percentage values from 0-100",
            //    'commandarguments' => [
            //        [
            //            'name'       => '$ARG1$',
            //            'human_name' => 'Warning %'
            //        ],
            //        [
            //            'name'       => '$ARG2$',
            //            'human_name' => 'Critical %'
            //        ],
            //        [
            //            'name'       => '$ARG3$',
            //            'human_name' => 'ID'
            //        ]
            //    ]
            //],
            //Agent 3.x
            [
                'name'             => 'check_oitc_agent3_battery',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0b2ba27e-2e9c-40cc-a3a0-266709728b66',
                'description'      => "Check current battery capacity as percentage. \nWarning and Critical thresholds are percentage values from 0-100",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Id'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_net_io',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '4186bc96-0081-48fc-bee6-0ff163c9f4a2',
                'description'      => "Fetch network io statistics and returns the specific nagios status, if at least one value exceeds the given (warning or critical) edge.\n" .
                    "Total average bytes: Average bytes sent and received per second\n" .
                    "Total average bytes (warning/critical) as integer (e.g. 1073741824 = 1GB)\n" .
                    "Total average errors: Average value of packages send and received with errors\n" .
                    "Total average errors (warning/critical) as integer (e.g. 5 or 10)\n" .
                    "Total average drops: Average value of (input and output) packages which were dropped\n" .
                    "Total average drops (warning/critical) as integer (e.g. 5 or 10)\n" .
                    "Device: Network device name (e.g. eth0) \n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Total average bytes warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Total average bytes critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Total average errors warning'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Total average errors critical'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Total average drops warning'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Total average drops critical'
                    ],
                    [
                        'name'       => '$ARG7$',
                        'human_name' => 'Device'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_net_stats',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '2c96d1c9-de93-4bfa-ab69-2b45bf40d2e3',
                'description'      => "Checks if the link of the device / interface is down and returns a custom nagios state\n" .
                    "Link Down State: The service state for nagios if link is down (warning/critical/unknown)" .
                    "Device: Network device name (e.g. eth0) \n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Link Down State'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Device'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_process',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '629050dd-1359-4c8d-9f13-fc49fdab84dc',
                'description'      => "Checks if a custom process or many matching process(es) exceeds the given values for cpu, memory or amount.\n" .
                    "CPU: Warning and critical percentage values (0-100) of cpu usage for the matching process(es).\n" .
                    "Memory: Warning and critical percentage values (0-100) of memory usage for the matching process(es).\n" .
                    "Amount: Warning and critical values (e.g. 5 or 10) as amount of matching processes.\n" .
                    "Match: String that must match with the process command line (use process 'cmdline' > 'exec' > 'name'). Leave empty to select all.\n" .
                    "Strict: Decides if the match must be completely or just in a part (1/0).\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'CPU warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'CPU critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Memory warning'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Memory critical'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Amount warning'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Amount critical'
                    ],
                    [
                        'name'       => '$ARG7$',
                        'human_name' => 'Match'
                    ],
                    [
                        'name'       => '$ARG8$',
                        'human_name' => 'Strict'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_windows_service_running',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '06bf6bc1-f228-4adc-ac14-5fe902349768',
                'description'      => "Returns the amount of running windows services.\n" .
                    "Amount: Warning and critical values (e.g. 5 or 10) as amount of matching services.\n" .
                    "Match: String that must match with the service binpath (use service 'binpath' > 'display_name' > 'name'). Leave empty to select all.\n" .
                    "Strict: Decides if the match must be completely or just in a part (1/0).\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Amount warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Amount critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Match'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Strict'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_systemd_service_running',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '44cf7fa5-38cc-45fb-aa5c-d4bb082e195d',
                'description'      => "Returns the state of a systemd service.\n" .
                    "Match: String that must match with the service unit name. (e.g. cron.service)\n" .
                    "Strict: Decides if the match must be completely or just in a part (1/0).\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Match'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Strict'
                    ],
                ]
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'             => 'check_oitc_agent_windows_eventlog',
            //    'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
            //    'command_type'     => CHECK_COMMAND,
            //    'human_args'       => null,
            //    'uuid'             => 'af3f0cac-f562-4830-9935-a9b2d69d494e',
            //    'description'      => "Returns the state of a windows event log entry.\n" .
            //        "Log type: The windows event log type to search in. Need to be specified in the agent configuration. (e.g. 'System', 'Application', 'Security')\n" .
            //        "Default state: The service state if no log entry will be found. (default: 'ok', eg. 'ok', 'warning', 'critical', 'unknown')\n" .
            //        "Check past minutes: Check for the log entry within the last X minutes. (default: '60', complete log: '0')\n" .
            //        "Match: String that must match with the event log source name.\n" .
            //        "Strict: Decides if the match must be completely or just in a part (1/0).\n",
            //    'commandarguments' => [
            //        [
            //            'name'       => '$ARG1$',
            //            'human_name' => 'Log type'
            //        ],
            //        [
            //            'name'       => '$ARG2$',
            //            'human_name' => 'Default state'
            //        ],
            //        [
            //            'name'       => '$ARG3$',
            //            'human_name' => 'Check past minutes'
            //        ],
            //        [
            //            'name'       => '$ARG4$',
            //            'human_name' => 'Match'
            //        ],
            //        [
            //            'name'       => '$ARG5$',
            //            'human_name' => 'Strict'
            //        ]
            //    ]
            //],
            // Agent 3.x
            [
                'name'             => 'check_oitc_agent3_windows_eventlog',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '2342bf29-de87-40e3-ba04-c83b5a48a42f',
                'description'      => "Checks for error messages in the given Windows Event Log.\n" .
                    "Log type: The windows event log type to search in. Need to be specified in the agent configuration. (e.g. 'System', 'Application', 'Security')\n" .
                    "Default state: The service state if no log entry will be found. (default: 0, 0=ok, 1=warning, 2=critical, 3=unknown)\n" .
                    "EntryTypeIds: Comma separated list of entry types IDs (Level) to include or exclude from evaluation. (1=Error, 2=Warning, 4=Information, 8=SuccessAudit 16=FailureAudit)\n" .
                    "EntryTypeIds Mode: Determine if the defined Ids at EntryTypeIds will be included or excluded from the check evaluation ('included' or 'excluded')\n" .
                    "EventIDs: Comma separated list of event IDs to includ or exclude from evaluation. If empty all events gets evaluated. \n" .
                    "EventIDs Mode: Determine if the defined Ids at EventIDs will be included or excluded from the check evaluation ('included' or 'excluded') \n" .
                    "Match source: A regular expression that must match with the event source. (e.g. '/Windows/')\n" .
                    "Match message: A regular expression that must match with the event message. (e.g. '/svchost/')\n\n" .
                    "For data collection PowerShell gets used by the Agent: https://docs.microsoft.com/de-de/dotnet/api/system.diagnostics.eventlogentrytype?view=net-5.0\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Log type'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Default state'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'EntryTypeIds'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'EntryTypeIds Mode'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'EventIDs'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'EventIDs Mode'
                    ],
                    [
                        'name'       => '$ARG7$',
                        'human_name' => 'Match source'
                    ],
                    [
                        'name'       => '$ARG8$',
                        'human_name' => 'Match message'
                    ],
                ]
            ],

            [
                'name'             => 'check_oitc_agent_docker_running',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '577da91f-e70d-4218-bef1-c8b3b72d2ad5',
                'description'      => "Return the state of a docker container (ok/critical).\n" .
                    "Identifier Type: Values: name or id - Determines if the name of the id should be used to identify the container.\n" .
                    "Identifier:  Name or id of the container.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Identifier Type'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Identifier'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_docker_cpu',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '198c52aa-28c0-45d8-a22d-7df3dad0f716',
                'description'      => "Return the cpu usage of a docker container.\n" .
                    "Identifier Type: Values: name or id - Determines if the name of the id should be used to identify the container.\n" .
                    "Identifier:  Name or id of the container.\n" .
                    "Warning and Critical thresholds are percentage values from 0-100.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Identifier Type'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Identifier'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical %'
                    ],
                ]
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'name'             => 'check_oitc_agent_docker_memory',
            //    'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
            //    'command_type'     => CHECK_COMMAND,
            //    'human_args'       => null,
            //    'uuid'             => 'ba2b3392-a769-4b20-8ea2-10b60f2c74e8',
            //    'description'      => "Return the memory usage of a docker container.\n" .
            //        "Identifier Type: Values: name or id - Determines if the name of the id should be used to identify the container.\n" .
            //        "Identifier:  Name or id of the container.\n" .
            //        "Warning and Critical thresholds are percentage values from 0-100 or float values depending on the chosen unit.\n" .
            //        "Unit: Determines if warning and critical is defined as percentage (%) or amount of ('B', 'kB', 'KiB', 'MB', 'MiB', 'GB', 'GiB', 'TB', 'TiB', 'PB', 'PiB', 'EB', 'EiB')",
            //    'commandarguments' => [
            //        [
            //            'name'       => '$ARG1$',
            //            'human_name' => 'Identifier Type'
            //        ],
            //        [
            //            'name'       => '$ARG2$',
            //            'human_name' => 'Identifier'
            //        ],
            //        [
            //            'name'       => '$ARG3$',
            //            'human_name' => 'Warning'
            //        ],
            //        [
            //            'name'       => '$ARG4$',
            //            'human_name' => 'Critical'
            //        ],
            //        [
            //            'name'       => '$ARG5$',
            //            'human_name' => 'Unit'
            //        ],
            //    ]
            //],
            // Agent 3.x
            [
                'name'             => 'check_oitc_agent3_docker_memory',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '984cb470-80aa-4561-b138-208ee144c5ce',
                'description'      => "Return the memory usage of a docker container.\n" .
                    "Identifier Type: Values: name or id - Determines if the name of the id should be used to identify the container.\n" .
                    "Identifier:  Name or id of the container.\n" .
                    "Warning and Critical thresholds are percentage values from 0-100 or float values depending on the chosen unit.\n" .
                    "AsPercentage: If set to 1 warning and critical thresholds will be a percentage (%) value otherwise warning and critical are MiB (Megabytes)",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Identifier Type'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Identifier'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'AsPercentage'
                    ],
                ]
            ],

            [
                'name'             => 'check_oitc_agent_qemu_vm_running',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '67ca5f1d-cc94-4bf1-8397-fc6e4abdbf92',
                'description'      => "Return the state of a qemu virtual machine (ok/critical).\n" .
                    "Identifier Type: Values: name, id, uuid - Determines if the name, the id or the uuid should be used to identify the virtual machine.\n" .
                    "Identifier:  Name, id or uuid of the virtual machine.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Identifier Type'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Identifier'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_customcheck',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'fbd23c8c-453d-4107-ae27-2cfafe63fef5',
                'description'      => "Custom command that were executed by the openITCOCKPIT Agent to replace check_by_ssh or check_nrpe.\n" .
                    "Name: Unique name of the command (e.g. username)\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_alfresco',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0d2e47c7-d584-4a4a-9a6c-1a1ff0f9365c',
                'description'      => "Returns the value and state of an Alfresco check by its name.\n" .
                    "Warning and Critical thresholds are numeric values depending on the chosen name.\n" .
                    "Name:  Name of the Alfresco check.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Name'
                    ]
                ]
            ],

            [
                'name'             => 'check_oitc_agent_launchd_service_running',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'e4f4a260-9b6d-453d-9f3d-76ea5635f770',
                'description'      => "Returns the state of a launchd service.\n" .
                    "Match: String that must match with the launchctl Label. (e.g. com.apple.trustd)\n" .
                    "Strict: Decides if the match must be completely or just in a part (1/0).\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Match'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Strict'
                    ],
                ]
            ],

            [
                'name'             => 'check_oitc_agent_libvirt',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '04f9fe28-736e-4398-9f5c-5330778da9e6',
                'description'      => "Check the state of a virtual machine running via libvirt.\n" .
                    "UUID: Unique identifier of the VM\n" .
                    "Memory warning and critical thresholds are percentage values from 0-100.\n" .
                    "CPU warning and critical thresholds are percentage values from 0-100.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'UUID'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Memory used warning (%)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Memory used critical (%)'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'CPU used warning (%)'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'CPU used critical (%)'
                    ],
                ]
            ],

            // Agent 3.x
            [
                'name'             => 'check-host-alive-oitc-agent-push',
                'command_line'     => '/opt/openitc/frontend/bin/cake agent --check -H --hostuuid "$HOSTNAME$" --critical $ARG1$',
                'command_type'     => HOSTCHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '1eca25a3-7982-448f-82bf-8597facf23fc',
                'description'      => "Determines the host state of an host running in Push Mode by evaluating the timestamp of the last received check results",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Age in seconds'
                    ]
                ]
            ],

            // Agent 3.x
            [
                'name'             => 'check-host-alive-oitc-agent-pull',
                'command_line'     => '/opt/openitc/receiver/bin/poller.php poller -H "$HOSTNAME$" -c /opt/openitc/receiver/etc/production.json --host-check',
                'command_type'     => HOSTCHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'be93c366-7373-447c-b394-a95edeb7d435',
                'description'      => "Actively executed by the monitoring engine.\nSend HTTP-Request to the target device and query the openITCOCKPIT Agent API interface.",
                'commandarguments' => []
            ],

            // Agent 3.x
            [
                'name'             => 'check_oitc_agent_ntp',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'e96abe8e-af71-4565-9997-dfeb9e518cac',
                'description'      => "Monitores if the system clock is synchronized to a time source like NTP.\n" .
                    "Warning and Critical thresholds are range integer values in seconds (e.g. -60:60 or -90:90).\n" .
                    "On macOS clients the system time will be compared against the server time",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],

        ];
        return $data;
    }

    public function getHosttemplatesData() {
        $data = [
            [
                'uuid'                              => 'a038bf40-02ab-45a3-8d34-5407e45a2fda',
                'name'                              => 'openITCOCKPIT Agent - Push',
                'description'                       => 'Host monitored via openITCOCKPIT Monitoring Agent operating in Push Mode',
                'hosttemplatetype_id'               => GENERIC_HOSTTEMPLATE,
                'command_id'                        => '1eca25a3-7982-448f-82bf-8597facf23fc',
                'check_command_args'                => '',
                'eventhandler_command_id'           => '0',
                'timeperiod_id'                     => '0',
                'check_interval'                    => '60',
                'retry_interval'                    => '60',
                'max_check_attempts'                => '3',
                'first_notification_delay'          => '0',
                'notification_interval'             => '7200',
                'notify_on_down'                    => '1',
                'notify_on_unreachable'             => '1',
                'notify_on_recovery'                => '1',
                'notify_on_flapping'                => '0',
                'notify_on_downtime'                => '0',
                'flap_detection_enabled'            => '0',
                'flap_detection_on_up'              => '0',
                'flap_detection_on_down'            => '0',
                'flap_detection_on_unreachable'     => '0',
                'low_flap_threshold'                => '0',
                'high_flap_threshold'               => '0',
                'process_performance_data'          => '0',
                'freshness_checks_enabled'          => '0',
                'freshness_threshold'               => '0',
                'passive_checks_enabled'            => '0',
                'event_handler_enabled'             => '0',
                'active_checks_enabled'             => '1',
                'retain_status_information'         => '0',
                'retain_nonstatus_information'      => '0',
                'notifications_enabled'             => '1',
                'notes'                             => '',
                'priority'                          => '1',
                'check_period_id'                   => '1',
                'notify_period_id'                  => '1',
                'tags'                              => '',
                'container_id'                      => '1',
                'host_url'                          => '',
                'created'                           => '2021-03-05 11:35:07',
                'modified'                          => '2021-03-05 11:35:07',
                'hosttemplatecommandargumentvalues' => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '120',
                    ],
                ],
                'customvariables'                   => [],
                'hostgroups'                        => [],
                'contactgroups'                     => [],
                'contacts'                          => [
                    '_ids' => [
                        (int)0 => '1'
                    ]
                ]
            ],

            [
                'uuid'                              => 'd086e12a-e0c5-42b9-a2f4-1875c65ad09b',
                'name'                              => 'openITCOCKPIT Agent - Pull',
                'description'                       => 'Actively monitor a Host via openITCOCKPIT Monitoring Agent operating in Pull Mode',
                'hosttemplatetype_id'               => GENERIC_HOSTTEMPLATE,
                'command_id'                        => 'be93c366-7373-447c-b394-a95edeb7d435',
                'check_command_args'                => '',
                'eventhandler_command_id'           => '0',
                'timeperiod_id'                     => '0',
                'check_interval'                    => '60',
                'retry_interval'                    => '60',
                'max_check_attempts'                => '3',
                'first_notification_delay'          => '0',
                'notification_interval'             => '7200',
                'notify_on_down'                    => '1',
                'notify_on_unreachable'             => '1',
                'notify_on_recovery'                => '1',
                'notify_on_flapping'                => '0',
                'notify_on_downtime'                => '0',
                'flap_detection_enabled'            => '0',
                'flap_detection_on_up'              => '0',
                'flap_detection_on_down'            => '0',
                'flap_detection_on_unreachable'     => '0',
                'low_flap_threshold'                => '0',
                'high_flap_threshold'               => '0',
                'process_performance_data'          => '0',
                'freshness_checks_enabled'          => '0',
                'freshness_threshold'               => '0',
                'passive_checks_enabled'            => '0',
                'event_handler_enabled'             => '0',
                'active_checks_enabled'             => '1',
                'retain_status_information'         => '0',
                'retain_nonstatus_information'      => '0',
                'notifications_enabled'             => '1',
                'notes'                             => '',
                'priority'                          => '1',
                'check_period_id'                   => '1',
                'notify_period_id'                  => '1',
                'tags'                              => '',
                'container_id'                      => '1',
                'host_url'                          => '',
                'created'                           => '2021-03-05 11:35:07',
                'modified'                          => '2021-03-05 11:35:07',
                'hosttemplatecommandargumentvalues' => [],
                'customvariables'                   => [],
                'hostgroups'                        => [],
                'contactgroups'                     => [],
                'contacts'                          => [
                    '_ids' => [
                        (int)0 => '1'
                    ]
                ]
            ],
        ];
        return $data;
    }

    public function getServicetemplatesData() {
        $data = [
            [
                'uuid'                                      => 'c475f1c8-fd28-493d-aad0-7861e418170d',
                'template_name'                             => 'OITC_AGENT_ACTIVE',
                'name'                                      => 'OITC_AGENT_ACTIVE',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'be116ff1-f797-4ccb-993c-b80ccb337de8',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'ca9158a8-39a7-4ada-ba69-1fe5865e4ab9',
                'template_name'                             => 'OITC_AGENT_SYSTEM_UPTIME',
                'name'                                      => 'Uptime',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '46b1d040-af5a-4b37-bf10-ab7e42b2882e',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '-1:',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '-2:',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'be4c9649-8771-4704-b409-c56b5f67abc8',
                'template_name'                             => 'OITC_AGENT_CPU_TOTAL_PERCENTAGE',
                'name'                                      => 'CPU usage percentage',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '27551d06-610d-4331-af2d-aab77c45bd36',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '85',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '0',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '566a710f-a554-4fa6-b2a2-e46b1d937a64',
                'template_name'                             => 'OITC_AGENT_SYSTEM_LOAD',
                'name'                                      => 'CPU load',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'd4e55865-5cbc-4f01-a4e3-896074a1d7d1',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '24',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '32',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '5',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '4052fe4f-50b1-443a-8be1-9dbca8d43ebd',
                'template_name'                             => 'OITC_AGENT_MEMORY_USAGE',
                'name'                                      => 'Memory usage percentage',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'ab3fe6f2-b5c6-4ba0-9d72-123696d42277',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '%',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'f9d5e18a-8894-4324-b54a-497db87b6f4f',
                'template_name'                             => 'OITC_AGENT_SWAP_USAGE',
                'name'                                      => 'Swap usage percentage',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'ebff0db2-e5de-4430-8799-3c6662422646',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '40',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '80',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '68fb72a3-5bf5-4d97-8690-91242628659b',
                'template_name'                             => 'OITC_AGENT_DISK_IO',
                'name'                                      => 'Disk Load',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'ffd0a58f-01e0-4a4d-8db9-a3107a9e765f',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '60',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => 'sda1',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '24851d0d-32fa-4048-bd67-6a30d710bba1',
                'template_name'                             => 'OITC_AGENT_DISK_USAGE',
                'name'                                      => 'Disk Space Used',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '3830078a-ccc5-41fd-ae33-8369bca1a6b7',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '/',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '%',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'bdc40fb0-3efe-4372-95bf-aaca0b7761d6',
                'template_name'                             => 'OITC_AGENT_DISK_FREE',
                'name'                                      => 'Disk Free Space',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '131f581f-ced9-405a-ba9b-4e0e7276258c',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '20:',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '10:',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '/',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '%',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'uuid'                                      => '3e0bd59e-822d-47ed-a5b2-15f1e53fe043',
            //    'template_name'                             => 'OITC_AGENT_FAN_SPEED',
            //    'name'                                      => 'Fan Speed',
            //    'container_id'                              => ROOT_CONTAINER,
            //    'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
            //    'check_period_id'                           => '1',
            //    'notify_period_id'                          => '1',
            //    'description'                               => '',
            //    'command_id'                                => '7bee1594-b029-4db1-8059-694a75b4f83e',
            //    'check_command_args'                        => '',
            //    'checkcommand_info'                         => '',
            //    'eventhandler_command_id'                   => '0',
            //    'timeperiod_id'                             => '0',
            //    'check_interval'                            => '300',
            //    'retry_interval'                            => '60',
            //    'max_check_attempts'                        => '3',
            //    'first_notification_delay'                  => '0',
            //    'notification_interval'                     => '7200',
            //    'notify_on_warning'                         => '1',
            //    'notify_on_unknown'                         => '1',
            //    'notify_on_critical'                        => '1',
            //    'notify_on_recovery'                        => '1',
            //    'notify_on_flapping'                        => '0',
            //    'notify_on_downtime'                        => '0',
            //    'flap_detection_enabled'                    => '0',
            //    'flap_detection_on_ok'                      => '0',
            //    'flap_detection_on_warning'                 => '0',
            //    'flap_detection_on_unknown'                 => '0',
            //    'flap_detection_on_critical'                => '0',
            //    'low_flap_threshold'                        => '0',
            //    'high_flap_threshold'                       => '0',
            //    'process_performance_data'                  => '1',
            //    'freshness_checks_enabled'                  => '1',
            //    'freshness_threshold'                       => '300',
            //    'passive_checks_enabled'                    => '1',
            //    'event_handler_enabled'                     => '0',
            //    'active_checks_enabled'                     => '0',
            //    'retain_status_information'                 => '0',
            //    'retain_nonstatus_information'              => '0',
            //    'notifications_enabled'                     => '1',
            //    'notes'                                     => '',
            //    'priority'                                  => '1',
            //    'tags'                                      => '',
            //    'service_url'                               => '',
            //    'is_volatile'                               => '0',
            //    'check_freshness'                           => '0',
            //    'servicetemplateeventcommandargumentvalues' => [],
            //    'servicetemplatecommandargumentvalues'      => [
            //        [
            //            'commandargument_id' => '$ARG1$',
            //            'value'              => '2900',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG2$',
            //            'value'              => '3300',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG3$',
            //            'value'              => '0',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG4$',
            //            'value'              => 'thinkpad',
            //        ]
            //    ],
            //    'customvariables'                           => [],
            //    'servicegroups'                             => [],
            //    'contactgroups'                             => [],
            //    'contacts'                                  => []
            //],

            // Agent 1.x legacy - delete this
            //[
            //    'uuid'                                      => 'f73dc076-bdb0-4302-9776-88ca1ba79364',
            //    'template_name'                             => 'OITC_AGENT_DEVICE_TEMPERATURE',
            //    'name'                                      => 'Device Temperature',
            //    'container_id'                              => ROOT_CONTAINER,
            //    'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
            //    'check_period_id'                           => '1',
            //    'notify_period_id'                          => '1',
            //    'description'                               => '',
            //    'command_id'                                => '42e3e6c9-a989-4044-a993-193558ad7964',
            //    'check_command_args'                        => '',
            //    'checkcommand_info'                         => '',
            //    'eventhandler_command_id'                   => '0',
            //    'timeperiod_id'                             => '0',
            //    'check_interval'                            => '300',
            //    'retry_interval'                            => '60',
            //    'max_check_attempts'                        => '3',
            //    'first_notification_delay'                  => '0',
            //    'notification_interval'                     => '7200',
            //    'notify_on_warning'                         => '1',
            //    'notify_on_unknown'                         => '1',
            //    'notify_on_critical'                        => '1',
            //    'notify_on_recovery'                        => '1',
            //    'notify_on_flapping'                        => '0',
            //    'notify_on_downtime'                        => '0',
            //    'flap_detection_enabled'                    => '0',
            //    'flap_detection_on_ok'                      => '0',
            //    'flap_detection_on_warning'                 => '0',
            //    'flap_detection_on_unknown'                 => '0',
            //    'flap_detection_on_critical'                => '0',
            //    'low_flap_threshold'                        => '0',
            //    'high_flap_threshold'                       => '0',
            //    'process_performance_data'                  => '1',
            //    'freshness_checks_enabled'                  => '1',
            //    'freshness_threshold'                       => '300',
            //    'passive_checks_enabled'                    => '1',
            //    'event_handler_enabled'                     => '0',
            //    'active_checks_enabled'                     => '0',
            //    'retain_status_information'                 => '0',
            //    'retain_nonstatus_information'              => '0',
            //    'notifications_enabled'                     => '1',
            //    'notes'                                     => '',
            //    'priority'                                  => '1',
            //    'tags'                                      => '',
            //    'service_url'                               => '',
            //    'is_volatile'                               => '0',
            //    'check_freshness'                           => '0',
            //    'servicetemplateeventcommandargumentvalues' => [],
            //    'servicetemplatecommandargumentvalues'      => [
            //        [
            //            'commandargument_id' => '$ARG1$',
            //            'value'              => '100',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG2$',
            //            'value'              => '110',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG3$',
            //            'value'              => '1',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG4$',
            //            'value'              => 'coretemp',
            //        ]
            //    ],
            //    'customvariables'                           => [],
            //    'servicegroups'                             => [],
            //    'contactgroups'                             => [],
            //    'contacts'                                  => []
            //],
            // Agent 3.x
            [
                'uuid'                                      => 'a73aa635-a0d0-463a-b065-9c8fe9a5a20b',
                'template_name'                             => 'OITC_AGENT3_SENSOR_TEMPERATURE',
                'name'                                      => 'Temperature Sensor',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '348a8ece-6bd1-4908-8524-a00893aff602',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '100',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => 'coretemp',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'uuid'                                      => '21057a75-57a1-4972-8f2f-073c8a6000b0',
            //    'template_name'                             => 'OITC_AGENT_BATTERY_LEVEL',
            //    'name'                                      => 'Battery Level',
            //    'container_id'                              => ROOT_CONTAINER,
            //    'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
            //    'check_period_id'                           => '1',
            //    'notify_period_id'                          => '1',
            //    'description'                               => '',
            //    'command_id'                                => '02289d0e-0a3c-46e5-94f1-b18e57be4e70',
            //    'check_command_args'                        => '',
            //    'checkcommand_info'                         => '',
            //    'eventhandler_command_id'                   => '0',
            //    'timeperiod_id'                             => '0',
            //    'check_interval'                            => '300',
            //    'retry_interval'                            => '60',
            //    'max_check_attempts'                        => '3',
            //    'first_notification_delay'                  => '0',
            //    'notification_interval'                     => '7200',
            //    'notify_on_warning'                         => '1',
            //    'notify_on_unknown'                         => '1',
            //    'notify_on_critical'                        => '1',
            //    'notify_on_recovery'                        => '1',
            //    'notify_on_flapping'                        => '0',
            //    'notify_on_downtime'                        => '0',
            //    'flap_detection_enabled'                    => '0',
            //    'flap_detection_on_ok'                      => '0',
            //    'flap_detection_on_warning'                 => '0',
            //    'flap_detection_on_unknown'                 => '0',
            //    'flap_detection_on_critical'                => '0',
            //    'low_flap_threshold'                        => '0',
            //    'high_flap_threshold'                       => '0',
            //    'process_performance_data'                  => '1',
            //    'freshness_checks_enabled'                  => '1',
            //    'freshness_threshold'                       => '300',
            //    'passive_checks_enabled'                    => '1',
            //    'event_handler_enabled'                     => '0',
            //    'active_checks_enabled'                     => '0',
            //    'retain_status_information'                 => '0',
            //    'retain_nonstatus_information'              => '0',
            //    'notifications_enabled'                     => '1',
            //    'notes'                                     => '',
            //    'priority'                                  => '1',
            //    'tags'                                      => '',
            //    'service_url'                               => '',
            //    'is_volatile'                               => '0',
            //    'check_freshness'                           => '0',
            //    'servicetemplateeventcommandargumentvalues' => [],
            //    'servicetemplatecommandargumentvalues'      => [
            //        [
            //            'commandargument_id' => '$ARG1$',
            //            'value'              => '@35',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG2$',
            //            'value'              => '@20',
            //        ]
            //    ],
            //    'customvariables'                           => [],
            //    'servicegroups'                             => [],
            //    'contactgroups'                             => [],
            //    'contacts'                                  => []
            //],
            //Agent 3.x
            [
                'uuid'                                      => '7d2961c3-b02b-4727-b870-5b391628b96f',
                'template_name'                             => 'OITC_AGENT3_BATTERY_LEVEL',
                'name'                                      => 'Battery Level',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '0b2ba27e-2e9c-40cc-a3a0-266709728b66',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '@35',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '@20',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '0',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'e5d848f5-a323-4bfe-9ec5-1c5cdf138abf',
                'template_name'                             => 'OITC_AGENT_NET_IO',
                'name'                                      => 'Network Stats',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '4186bc96-0081-48fc-bee6-0ff163c9f4a2',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '5',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '10',
                    ],
                    [
                        'commandargument_id' => '$ARG5$',
                        'value'              => '5',
                    ],
                    [
                        'commandargument_id' => '$ARG6$',
                        'value'              => '10',
                    ],
                    [
                        'commandargument_id' => '$ARG7$',
                        'value'              => '',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'f6f64207-ef75-4a3d-b9f2-2eaab398a6f1',
                'template_name'                             => 'OITC_AGENT_NET_DEVICE_STATS',
                'name'                                      => 'Network Device Status',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '2c96d1c9-de93-4bfa-ab69-2b45bf40d2e3',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => 'critical',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => 'eth0',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '37a78eca-4a58-46cd-9fb1-6029724cab35',
                'template_name'                             => 'OITC_AGENT_PROCESSES',
                'name'                                      => 'Check Process',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '629050dd-1359-4c8d-9f13-fc49fdab84dc',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '50',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '60',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '50',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '60',
                    ],
                    [
                        'commandargument_id' => '$ARG5$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG6$',
                        'value'              => '1:1',
                    ],
                    [
                        'commandargument_id' => '$ARG7$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG8$',
                        'value'              => '0',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '370731ed-34d4-48f4-933e-90e488bb390f',
                'template_name'                             => 'OITC_AGENT_WINDOWS_SERVICES',
                'name'                                      => 'Check windows service running',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '06bf6bc1-f228-4adc-ac14-5fe902349768',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '1:1',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '1',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'd21e3462-7430-4d38-b92a-853bcfc12356',
                'template_name'                             => 'OITC_AGENT_SYSTEMD_SERVICES',
                'name'                                      => 'Check systemd service running',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '44cf7fa5-38cc-45fb-aa5c-d4bb082e195d',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '1',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'uuid'                                      => 'dda2d5dc-7987-49a5-b4c2-0540e8aaf45e',
            //    'template_name'                             => 'OITC_AGENT_WINDOWS_EVENTLOG',
            //    'name'                                      => 'Check windows eventlog entry',
            //    'container_id'                              => ROOT_CONTAINER,
            //    'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
            //    'check_period_id'                           => '1',
            //    'notify_period_id'                          => '1',
            //    'description'                               => '',
            //    'command_id'                                => 'af3f0cac-f562-4830-9935-a9b2d69d494e',
            //    'check_command_args'                        => '',
            //    'checkcommand_info'                         => '',
            //    'eventhandler_command_id'                   => '0',
            //    'timeperiod_id'                             => '0',
            //    'check_interval'                            => '300',
            //    'retry_interval'                            => '60',
            //    'max_check_attempts'                        => '3',
            //    'first_notification_delay'                  => '0',
            //    'notification_interval'                     => '7200',
            //    'notify_on_warning'                         => '1',
            //    'notify_on_unknown'                         => '1',
            //    'notify_on_critical'                        => '1',
            //    'notify_on_recovery'                        => '1',
            //    'notify_on_flapping'                        => '0',
            //    'notify_on_downtime'                        => '0',
            //    'flap_detection_enabled'                    => '0',
            //    'flap_detection_on_ok'                      => '0',
            //    'flap_detection_on_warning'                 => '0',
            //    'flap_detection_on_unknown'                 => '0',
            //    'flap_detection_on_critical'                => '0',
            //    'low_flap_threshold'                        => '0',
            //    'high_flap_threshold'                       => '0',
            //    'process_performance_data'                  => '1',
            //    'freshness_checks_enabled'                  => '1',
            //    'freshness_threshold'                       => '300',
            //    'passive_checks_enabled'                    => '1',
            //    'event_handler_enabled'                     => '0',
            //    'active_checks_enabled'                     => '0',
            //    'retain_status_information'                 => '0',
            //    'retain_nonstatus_information'              => '0',
            //    'notifications_enabled'                     => '1',
            //    'notes'                                     => '',
            //    'priority'                                  => '1',
            //    'tags'                                      => '',
            //    'service_url'                               => '',
            //    'is_volatile'                               => '0',
            //    'check_freshness'                           => '0',
            //    'servicetemplateeventcommandargumentvalues' => [],
            //    'servicetemplatecommandargumentvalues'      => [
            //        [
            //            'commandargument_id' => '$ARG1$',
            //            'value'              => '',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG2$',
            //            'value'              => 'ok',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG3$',
            //            'value'              => '60',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG4$',
            //            'value'              => '',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG5$',
            //            'value'              => '1',
            //        ],
            //    ],
            //    'customvariables'                           => [],
            //    'servicegroups'                             => [],
            //    'contactgroups'                             => [],
            //    'contacts'                                  => []
            //],
            // Agent 3.x
            [
                'uuid'                                      => 'f9205afd-3530-4b65-b6cc-347a652f7b66',
                'template_name'                             => 'OITC_AGENT3_WINDOWS_EVENTLOG',
                'name'                                      => 'Check windows event log entries',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '2342bf29-de87-40e3-ba04-c83b5a48a42f',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '0',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '1,2,16',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => 'included',
                    ],
                    [
                        'commandargument_id' => '$ARG5$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG6$',
                        'value'              => 'excluded',
                    ],
                    [
                        'commandargument_id' => '$ARG7$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG8$',
                        'value'              => '',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'ca73653f-2bba-4542-b11b-0bbd0ecc8b7a',
                'template_name'                             => 'OITC_AGENT_DOCKER_RUNNING',
                'name'                                      => 'Docker Container Running',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '577da91f-e70d-4218-bef1-c8b3b72d2ad5',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => 'name',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => 'container_name',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'a9f7757e-34b0-4df9-8fca-ab8b594c2c26',
                'template_name'                             => 'OITC_AGENT_DOCKER_CPU',
                'name'                                      => 'Docker Container CPU Percentage',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '198c52aa-28c0-45d8-a22d-7df3dad0f716',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => 'name',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => 'container_name',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '90',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            // Agent 1.x legacy - delete this
            //[
            //    'uuid'                                      => 'aef4c1a8-ed71-4799-a164-3ad469baadc5',
            //    'template_name'                             => 'OITC_AGENT_DOCKER_MEMORY',
            //    'name'                                      => 'Docker Container Memory Usage',
            //    'container_id'                              => ROOT_CONTAINER,
            //    'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
            //    'check_period_id'                           => '1',
            //    'notify_period_id'                          => '1',
            //    'description'                               => '',
            //    'command_id'                                => 'ba2b3392-a769-4b20-8ea2-10b60f2c74e8',
            //    'check_command_args'                        => '',
            //    'checkcommand_info'                         => '',
            //    'eventhandler_command_id'                   => '0',
            //    'timeperiod_id'                             => '0',
            //    'check_interval'                            => '300',
            //    'retry_interval'                            => '60',
            //    'max_check_attempts'                        => '3',
            //    'first_notification_delay'                  => '0',
            //    'notification_interval'                     => '7200',
            //    'notify_on_warning'                         => '1',
            //    'notify_on_unknown'                         => '1',
            //    'notify_on_critical'                        => '1',
            //    'notify_on_recovery'                        => '1',
            //    'notify_on_flapping'                        => '0',
            //    'notify_on_downtime'                        => '0',
            //    'flap_detection_enabled'                    => '0',
            //    'flap_detection_on_ok'                      => '0',
            //    'flap_detection_on_warning'                 => '0',
            //    'flap_detection_on_unknown'                 => '0',
            //    'flap_detection_on_critical'                => '0',
            //    'low_flap_threshold'                        => '0',
            //    'high_flap_threshold'                       => '0',
            //    'process_performance_data'                  => '1',
            //    'freshness_checks_enabled'                  => '1',
            //    'freshness_threshold'                       => '300',
            //    'passive_checks_enabled'                    => '1',
            //    'event_handler_enabled'                     => '0',
            //    'active_checks_enabled'                     => '0',
            //    'retain_status_information'                 => '0',
            //    'retain_nonstatus_information'              => '0',
            //    'notifications_enabled'                     => '1',
            //    'notes'                                     => '',
            //    'priority'                                  => '1',
            //    'tags'                                      => '',
            //    'service_url'                               => '',
            //    'is_volatile'                               => '0',
            //    'check_freshness'                           => '0',
            //    'servicetemplateeventcommandargumentvalues' => [],
            //    'servicetemplatecommandargumentvalues'      => [
            //        [
            //            'commandargument_id' => '$ARG1$',
            //            'value'              => 'name',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG2$',
            //            'value'              => 'container_name',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG3$',
            //            'value'              => '50.0',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG4$',
            //            'value'              => '80.5',
            //        ],
            //        [
            //            'commandargument_id' => '$ARG5$',
            //            'value'              => 'MiB',
            //        ]
            //    ],
            //    'customvariables'                           => [],
            //    'servicegroups'                             => [],
            //    'contactgroups'                             => [],
            //    'contacts'                                  => []
            //],
            // Agent 3.x
            [
                'uuid'                                      => '7f86be31-0bb9-45f1-82bd-898deeba2cbd',
                'template_name'                             => 'OITC_AGENT3_DOCKER_MEMORY',
                'name'                                      => 'Docker Container Memory Usage',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '984cb470-80aa-4561-b138-208ee144c5ce',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => 'name',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => 'container_name',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '50.0',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '80.5',
                    ],
                    [
                        'commandargument_id' => '$ARG5$',
                        'value'              => '0',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'c1c8c77a-cecf-4a94-8418-a69081946ba0',
                'template_name'                             => 'OITC_AGENT_QEMU_VM_RUNNING',
                'name'                                      => 'QEMU VM Running',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '67ca5f1d-cc94-4bf1-8397-fc6e4abdbf92',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => 'id',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '100',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '03b32b83-df7b-4204-a8bb-138a4939c554',
                'template_name'                             => 'OITC_AGENT_CUSTOMCHECK',
                'name'                                      => 'Custom check',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'fbd23c8c-453d-4107-ae27-2cfafe63fef5',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => 'checkname',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],


            /* Alfresco servicetemplates */

            [
                'uuid'                                      => 'e453767c-b216-4b17-a012-9bc037d7da49',
                'template_name'                             => 'OITC_AGENT_ALFRESCO',
                'name'                                      => 'Alfresco check',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '0d2e47c7-d584-4a4a-9a6c-1a1ff0f9365c',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '0',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '0',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => 'Alfresco check name',
                    ],
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '7370c78c-fc04-459b-9fc8-39a76bbe0fba',
                'template_name'                             => 'OITC_AGENT_LAUNCHD_SERVICES',
                'name'                                      => 'Check launchd service running',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'e4f4a260-9b6d-453d-9f3d-76ea5635f770',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '1',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => '4a0d6a78-8dd5-47c7-9edc-ed6a2aebce3a',
                'template_name'                             => 'OITC_AGENT_LIBVIRT_VM',
                'name'                                      => 'Check VM state via libvirt',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '04f9fe28-736e-4398-9f5c-5330778da9e6',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG3$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG4$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG5$',
                        'value'              => '90',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],

            [
                'uuid'                                      => 'f3aadc86-fcc6-4d36-abce-50d0af593e44',
                'template_name'                             => 'OITC_AGENT_NTP',
                'name'                                      => 'System Time',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'e96abe8e-af71-4565-9997-dfeb9e518cac',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '300',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '1',
                'freshness_threshold'                       => '300',
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '0',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '-60:60',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '-90:90',
                    ]
                ],
                'customvariables'                           => [],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
        ];
        return $data;
    }

    /**
     * @return array
     */
    public function getData() {
        return [
            'Commands'         => $this->getCommandsData(),
            'Hosttemplates'    => $this->getHosttemplatesData(),
            'Servicetemplates' => $this->getServicetemplatesData(),
            'Agentchecks'      => $this->getAgentchecksData()
        ];
    }

    /**
     * This function is used to update existing command or service templates
     * For example to add a new commandargument value
     * @return void
     */
    public function updateRecords() {
        // ITC-2939
        // Add identifier field to check_oitc_agent_process (629050dd-1359-4c8d-9f13-fc49fdab84dc) command
        if ($this->CommandsTable->existsByUuid('629050dd-1359-4c8d-9f13-fc49fdab84dc')) {
            $command = $this->CommandsTable->find()
                ->where([
                    'uuid' => '629050dd-1359-4c8d-9f13-fc49fdab84dc'
                ])
                ->contain([
                    'Commandarguments'
                ])
                ->firstOrFail();

            $hasIdentifierArgument = false;
            if (sizeof($command->commandarguments) === 8) {
                foreach ($command->commandarguments as $commandargument) {
                    /** @var Commandargument $commandargument */
                    if ($commandargument->name === '$ARG9$' && $commandargument->human_name === 'identifier') {
                        $hasIdentifierArgument = true;
                    }
                }

                if ($hasIdentifierArgument === false) {
                    $commandArray = $command->toArray();

                    $commandArray['description'] = "Checks if a custom process or many matching process(es) exceeds the given values for cpu, memory or amount.\n" .
                        "CPU: Warning and critical percentage values (0-100) of cpu usage for the matching process(es).\n" .
                        "Memory: Warning and critical percentage values (0-100) of memory usage for the matching process(es).\n" .
                        "Amount: Warning and critical values (e.g. 5 or 10) as amount of matching processes.\n" .
                        "Match: String that must match with the process command line (e.g. /sbin/init)\n" .
                        "Strict: Decides if the match must be completely or just in a part (1/0).\n" .
                        "Identifier: Field used for the match condition ('auto', 'cmdline', 'exec' or 'name').";

                    $commandArray['commandarguments'][] = [
                        'name'       => '$ARG9$',
                        'human_name' => 'identifier'
                    ];

                    $entity = $this->CommandsTable->patchEntity($command, $commandArray);
                    $this->CommandsTable->save($entity);
                }
            }
        }

        // Add identifier field to OITC_AGENT_PROCESSES (37a78eca-4a58-46cd-9fb1-6029724cab35) service template
        if ($this->ServicetemplatesTable->existsByUuid('37a78eca-4a58-46cd-9fb1-6029724cab35')) {
            $st = $this->ServicetemplatesTable->find()
                ->select([
                    'id'
                ])
                ->where([
                    'uuid' => '37a78eca-4a58-46cd-9fb1-6029724cab35'
                ])
                ->firstOrFail();

            $servicetemplate = $this->ServicetemplatesTable->getServicetemplateForEdit($st->id);

            $updateTemplate = false;
            if (sizeof($servicetemplate['Servicetemplate']['servicetemplatecommandargumentvalues']) === 9) {
                foreach ($servicetemplate['Servicetemplate']['servicetemplatecommandargumentvalues'] as $index => $stcarg) {
                    if ($stcarg['commandargument']['name'] === '$ARG9$' && $stcarg['commandargument']['human_name'] === 'identifier' && empty($stcarg['value'])) {
                        $updateTemplate = true;
                        $servicetemplate['Servicetemplate']['servicetemplatecommandargumentvalues'][$index]['value'] = 'auto';
                    }
                }
            }

            $updateServices = false;
            if ($updateTemplate === true) {
                $servicetemplateEntity = $this->ServicetemplatesTable->get($servicetemplate['Servicetemplate']['id']);
                $servicetemplateEntity = $this->ServicetemplatesTable->patchEntity($servicetemplateEntity, $servicetemplate['Servicetemplate']);
                $this->ServicetemplatesTable->save($servicetemplateEntity);
                if (!$servicetemplateEntity->hasErrors()) {
                    $updateServices = true;
                }
            }

            // Add new Command Argument to all services

        }
    }
}
