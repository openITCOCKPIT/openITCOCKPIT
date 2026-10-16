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

namespace itnovum\openITCOCKPIT\InitialDatabase;

use App\Model\Entity\Commandargument;
use App\Model\Table\AgentchecksTable;
use App\Model\Table\CommandsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicetemplatesTable;

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
            [
                'name'               => 'packagemanager',
                'plugin_name'        => 'Packagemanager',
                'servicetemplate_id' => 'eef93b44-1ffa-4b8b-a8c3-e1a1258bf68c'
            ],

            // Agent 3.7: new monitoring features (logfiles, system health, security, ZFS, Ceph, SMART, Proxmox VE, Redfish, ...)
            [
                'name'               => 'system_health.raid',
                'plugin_name'        => 'Mdraid',
                'servicetemplate_id' => '4d293bff-06fc-48c1-acd6-564fed2e7359'
            ],
            [
                'name'               => 'system_health.oom',
                'plugin_name'        => 'OomKill',
                'servicetemplate_id' => '85c25766-2afc-4c6b-89b3-565cb8d66b96'
            ],
            [
                'name'               => 'system_health.file_descriptors',
                'plugin_name'        => 'FileDescriptors',
                'servicetemplate_id' => '8089e21b-d5be-4187-b9d6-0409c1a59b9a'
            ],
            [
                'name'               => 'system_health.conntrack',
                'plugin_name'        => 'Conntrack',
                'servicetemplate_id' => 'ce769054-6fbb-44d6-a14d-47db38313a91'
            ],
            [
                'name'               => 'system_health.pressure',
                'plugin_name'        => 'Pressure',
                'servicetemplate_id' => '3589b65e-8403-464b-befd-c1c6689ded21'
            ],
            [
                'name'               => 'disks_inodes',
                'plugin_name'        => 'DiskInodes',
                'servicetemplate_id' => '80ae83c6-41eb-443b-9e18-c32341b0ed95'
            ],
            [
                'name'               => 'disks_readonly',
                'plugin_name'        => 'MountReadonly',
                'servicetemplate_id' => '7b779163-4bfa-462c-bac7-b84515cc6347'
            ],
            [
                'name'               => 'systemd_services.failed',
                'plugin_name'        => 'SystemdFailedUnits',
                'servicetemplate_id' => '08514e2c-c23c-4431-98f5-a213df7098df'
            ],
            [
                'name'               => 'processes.zombies',
                'plugin_name'        => 'ZombieProcesses',
                'servicetemplate_id' => '21945974-bb8a-4dd1-a0e1-765cdb5663c0'
            ],
            [
                'name'               => 'windows_services.automatic',
                'plugin_name'        => 'WindowsAutomaticServices',
                'servicetemplate_id' => 'c3c79ca5-a29e-467e-a8e5-2800406c3c70'
            ],
            [
                'name'               => 'logfiles',
                'plugin_name'        => 'Logfile',
                'servicetemplate_id' => 'fc6ecf52-e354-4e7f-b88b-cf5133a3b6aa'
            ],
            [
                'name'               => 'certificates',
                'plugin_name'        => 'Certificate',
                'servicetemplate_id' => '052eca9c-4161-413a-998e-bf1298bd5b64'
            ],
            [
                'name'               => 'files.file',
                'plugin_name'        => 'File',
                'servicetemplate_id' => '8c6112c9-5bec-43bd-bfb9-6924274f54b2'
            ],
            [
                'name'               => 'files.directory',
                'plugin_name'        => 'Directory',
                'servicetemplate_id' => 'd82d997c-bb5e-48dc-b7a1-8e1c57078bd0'
            ],
            [
                'name'               => 'http',
                'plugin_name'        => 'Http',
                'servicetemplate_id' => 'e82dfaa1-0af9-4c7e-a12e-d4c7c7aacc3b'
            ],
            [
                'name'               => 'windows_perfcounters',
                'plugin_name'        => 'WindowsPerfCounter',
                'servicetemplate_id' => 'bec5d45c-d9b0-4d1d-8611-fef685a3510e'
            ],
            [
                'name'               => 'windows_scheduled_tasks',
                'plugin_name'        => 'WindowsScheduledTask',
                'servicetemplate_id' => '153fadc5-9b88-4bbc-b981-97081b817840'
            ],
            [
                'name'               => 'security.firewall',
                'plugin_name'        => 'Firewall',
                'servicetemplate_id' => 'f5c7d77c-5b69-4498-bab5-6f6b5db59ba1'
            ],
            [
                'name'               => 'security.selinux',
                'plugin_name'        => 'Selinux',
                'servicetemplate_id' => 'b45f7351-2591-449b-a212-34ad1b0497fa'
            ],
            [
                'name'               => 'security.apparmor',
                'plugin_name'        => 'Apparmor',
                'servicetemplate_id' => 'f4b6c286-00fd-47f8-8a28-a34def9100e3'
            ],
            [
                'name'               => 'security.filevault',
                'plugin_name'        => 'FileVault',
                'servicetemplate_id' => 'e1fa1856-cd40-4e9a-ad89-75c4bcd3ecc5'
            ],
            [
                'name'               => 'security.sip',
                'plugin_name'        => 'SystemIntegrityProtection',
                'servicetemplate_id' => 'd906c938-ec3d-4c83-ac96-d9e8b670b9b8'
            ],
            [
                'name'               => 'security.defender',
                'plugin_name'        => 'WindowsDefender',
                'servicetemplate_id' => 'f28f4628-e03a-4813-936e-0ecbea92986f'
            ],
            [
                'name'               => 'zfs.pools',
                'plugin_name'        => 'ZfsPool',
                'servicetemplate_id' => '6af127b0-56b2-41e0-8ad6-ffe1c633f54d'
            ],
            [
                'name'               => 'zfs.arc',
                'plugin_name'        => 'ZfsArc',
                'servicetemplate_id' => 'f20b6c89-8914-46b6-b7a9-ef0115e76895'
            ],
            [
                'name'               => 'ceph.health',
                'plugin_name'        => 'CephHealth',
                'servicetemplate_id' => '043c16e6-e033-446c-b399-3be4afd6a561'
            ],
            [
                'name'               => 'ceph.osds',
                'plugin_name'        => 'CephOsd',
                'servicetemplate_id' => 'c7c80d63-6b7c-46dc-97ec-799efdef7149'
            ],
            [
                'name'               => 'ceph.usage',
                'plugin_name'        => 'CephUsage',
                'servicetemplate_id' => '6383a920-f22a-47a8-b1ca-b0de18184f6c'
            ],
            [
                'name'               => 'smart',
                'plugin_name'        => 'Smart',
                'servicetemplate_id' => '82188d07-d8a1-4cc1-84c7-3782f80c730f'
            ],
            [
                'name'               => 'redfish.health',
                'plugin_name'        => 'RedfishHealth',
                'servicetemplate_id' => '83d6640a-adbe-443e-b823-470869629712'
            ],
            [
                'name'               => 'redfish.sensors',
                'plugin_name'        => 'RedfishSensors',
                'servicetemplate_id' => '9fb0d09b-e467-4282-ae86-b7b538e888d7'
            ],
            [
                'name'               => 'redfish.power',
                'plugin_name'        => 'RedfishPower',
                'servicetemplate_id' => '50ff1ade-8596-46e8-9fc2-23607bea6aad'
            ],
            [
                'name'               => 'redfish.storage',
                'plugin_name'        => 'RedfishStorage',
                'servicetemplate_id' => '459f2366-c067-45a9-86d1-3e2d1183271e'
            ],
            [
                'name'               => 'redfish.eventlog',
                'plugin_name'        => 'RedfishEventLog',
                'servicetemplate_id' => 'dea2ecf4-001b-44aa-b345-a0198ac2f510'
            ],
            [
                'name'               => 'docker.health',
                'plugin_name'        => 'DockerContainerHealth',
                'servicetemplate_id' => 'f108b1f7-3f5e-4217-bc3d-df6f56cef2e3'
            ],
            [
                'name'               => 'postfix',
                'plugin_name'        => 'PostfixQueue',
                'servicetemplate_id' => 'e9db80e0-7cea-48c8-8229-3cf74d71f0b1'
            ],
            [
                'name'               => 'proxmox.cluster',
                'plugin_name'        => 'ProxmoxCluster',
                'servicetemplate_id' => '161f2e1c-f173-40f3-ae12-b22ff1ef679c'
            ],
            [
                'name'               => 'proxmox.storages',
                'plugin_name'        => 'ProxmoxStorage',
                'servicetemplate_id' => 'db5a02aa-b017-47bd-b7eb-870b470bfa9f'
            ],
            [
                'name'               => 'proxmox.guests',
                'plugin_name'        => 'ProxmoxGuest',
                'servicetemplate_id' => 'f5b2deea-0198-4b46-92f6-456ca4b75035'
            ],
            [
                'name'               => 'proxmox.backups',
                'plugin_name'        => 'ProxmoxBackup',
                'servicetemplate_id' => 'f1372ba7-e1df-4d9e-9ccc-cd9e238df708'
            ],
            [
                'name'               => 'proxmox.replication',
                'plugin_name'        => 'ProxmoxReplication',
                'servicetemplate_id' => '42ee9475-f1d4-43a7-9b40-bba50896d340'
            ],

            // Agent 3.7: bonding, filesystem errors, EDAC, LVM thin pools, missing mounts, Exim and BitLocker
            [
                'name'               => 'system_health.bonding',
                'plugin_name'        => 'Bonding',
                'servicetemplate_id' => '92889b93-26d0-4624-8c89-5786de537853'
            ],
            [
                'name'               => 'system_health.filesystem_errors',
                'plugin_name'        => 'FilesystemErrors',
                'servicetemplate_id' => '00c5473d-9060-46b4-bae8-14293f169361'
            ],
            [
                'name'               => 'system_health.edac',
                'plugin_name'        => 'Edac',
                'servicetemplate_id' => '68addeef-25bd-477a-a67a-37d2032f0e33'
            ],
            [
                'name'               => 'lvm.thin_pools',
                'plugin_name'        => 'LvmThin',
                'servicetemplate_id' => '19acdc71-ba20-44a8-a3e6-402b8cd7cd60'
            ],
            [
                'name'               => 'mounts',
                'plugin_name'        => 'MissingMounts',
                'servicetemplate_id' => '8eb07426-462c-4e09-bdc3-3f0091e9d8a9'
            ],
            [
                'name'               => 'exim',
                'plugin_name'        => 'EximQueue',
                'servicetemplate_id' => '0f0690ec-4bb4-4d0e-9af7-79d14cf12a12'
            ],
            [
                'name'               => 'security.bitlocker',
                'plugin_name'        => 'BitLocker',
                'servicetemplate_id' => '10fc3768-884c-4286-9490-89d9d4b4a7b1'
            ],

            // Agent 3.7: network ports
            [
                'name'               => 'network_ports.listener',
                'plugin_name'        => 'NetworkPort',
                'servicetemplate_id' => 'b4736b11-23aa-4f64-b717-64e7e155e675'
            ],
            [
                'name'               => 'network_ports.tcp_states',
                'plugin_name'        => 'TcpStates',
                'servicetemplate_id' => '38ebb55e-9c88-455f-a536-d0f930a465d1'
            ],
            [
                'name'               => 'network_ports.ephemeral_ports',
                'plugin_name'        => 'EphemeralPorts',
                'servicetemplate_id' => '27a4b783-621f-42bc-b104-d9cb06d288a2'
            ],
            [
                'name'               => 'network_ports.tcp_errors',
                'plugin_name'        => 'TcpErrors',
                'servicetemplate_id' => '2d3faac5-82f6-4f08-83d5-3891788a350d'
            ],
            [
                'name'               => 'network_ports.unexpected',
                'plugin_name'        => 'UnexpectedPorts',
                'servicetemplate_id' => '33d9e80f-228e-4507-ab85-b173c2f98f05'
            ],

            // Agent 3.7: IPMI
            [
                'name'               => 'ipmi.health',
                'plugin_name'        => 'IpmiHealth',
                'servicetemplate_id' => '96d90f51-95ab-4ff7-82af-62681fdcdec8'
            ],
            [
                'name'               => 'ipmi.sensors',
                'plugin_name'        => 'IpmiSensors',
                'servicetemplate_id' => '2281db92-0525-4dd4-ac53-d04ed66d9278'
            ],
            [
                'name'               => 'ipmi.sel',
                'plugin_name'        => 'IpmiSel',
                'servicetemplate_id' => '8e883faf-31e1-4e6a-9c86-65846ae4dca4'
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
                    "On macOS and FreeBSD (if ntpd is not running) the agent measures the offset to the time server with SNTP.\n" .
                    "If no time server is reachable, the system time will be compared against the server time",
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

            // Agent 3.x
            [
                'name'             => 'check_oitc_check_updates',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'fc502c30-8629-453a-a481-382d66cefa8c',
                'description'      => "Checks if any operating system updates are available.\n" .
                    "If supported, the agent will list optional and security related updates separately.\n" .
                    "Number of available updates: Minimum number ofavailable updates to return a WARNING state.\n" .
                    "Only Security Updates: Only consider security updates and never return a WARNING state (1/0).\n" .
                    "Information only: Only track update information but never switch into a WARNING or CRITICAL state.\n",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Number of available updates'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Only Security Updates (1/0)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Information only (1/0)'
                    ]
                ]
            ],

            // Agent 3.7: new monitoring features (logfiles, system health, security, ZFS, Ceph, SMART, Proxmox VE, Redfish, ...)
            [
                'name'             => 'check_oitc_agent_mdraid',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3c1e4e69-d27b-495c-bd91-e5b22a1afb31',
                'description'      => "Checks Linux software RAID arrays (/proc/mdstat).\n" .
                    "CRITICAL: failed or missing disks or an inactive array. WARNING: the array is rebuilding or resyncing.\n" .
                    "md device: Name of the array, e.g. md0. Empty = all arrays.\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'md device (empty = all)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_oom_kill',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '06d903d9-02b0-419a-9c1b-2fce8c1b1d26',
                'description'      => "Counts the processes killed by the Linux out of memory killer within the given window.\n" .
                    "Warning and Critical: Number of OOM kills (e.g. 0 = warning on the first OOM kill).\n" .
                    "Window: Time window in seconds (maximum 86400).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux only.",
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
                        'human_name' => 'Window in seconds'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_file_descriptors',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '02e4c02d-5116-4ca1-989d-ea0e72c941a4',
                'description'      => "Checks the system wide usage of file descriptors (/proc/sys/fs/file-nr).\n" .
                    "Warning and Critical: Usage in percent (0-100).\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_conntrack',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '41c7a349-df22-4b57-9cf1-0e5c87f1c992',
                'description'      => "Checks the usage of the netfilter connection tracking table. A full table drops new connections.\n" .
                    "Warning and Critical: Usage in percent (0-100).\n" .
                    "Linux only, if the conntrack module is loaded.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_pressure',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '5c5fa13c-c28f-4d6e-a880-9700c47e9e80',
                'description'      => "Checks the pressure stall information (PSI): percentage of time tasks were stalled waiting for a resource.\n" .
                    "Resource: cpu, memory or io.\n" .
                    "Warning and Critical: Percent of time (0-100). Empty = only performance data.\n" .
                    "Type: some (at least one task was stalled) or full (all non-idle tasks were stalled).\n" .
                    "Average: 10, 60 or 300 seconds.\n" .
                    "Linux 4.20 or newer with PSI enabled.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Resource (cpu, memory, io)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical %'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Type (some, full)'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Average (10, 60, 300)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_disk_inodes',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0ddcc07b-6c84-4b3c-8888-619466b2ee90',
                'description'      => "Checks the inode usage of a filesystem. A filesystem without free inodes can not store new files, even if space is available.\n" .
                    "Warning and Critical: Usage in percent (0-100).\n" .
                    "Mountpoint: Mountpoint of the filesystem, e.g. / or /var.\n" .
                    "Linux, macOS and FreeBSD. Filesystems without inodes (e.g. btrfs) are reported as OK.",
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
                        'human_name' => 'Mountpoint'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_mount_readonly',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3104c1eb-75c7-44c9-9f3b-fa81bd55b58c',
                'description'      => "Checks if filesystems are mounted read-only, e.g. after filesystem errors.\n" .
                    "Mountpoint: Check only this mountpoint. Empty = all mountpoints (pseudo filesystems and the sealed system volume of macOS are ignored).\n" .
                    "State if read-only: warning or critical.\n" .
                    "Exclude: Regular expression for mountpoints which are read-only by intention (only if mountpoint is empty), e.g. ^/mnt/backup\n" .
                    "Linux, macOS and FreeBSD.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Mountpoint (empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if read-only'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Exclude mountpoints (regex)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_systemd_failed_units',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'c48cb0c8-eebc-4690-8067-61ef41c6f6b7',
                'description'      => "Counts all systemd units in the state failed, like \"systemctl --failed\".\n" .
                    "Warning and Critical: Number of failed units (default: critical on the first failed unit).\n" .
                    "Exclude: Regular expression for units which should be ignored, e.g. ^(apt-daily|fwupd)\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux only.",
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
                        'human_name' => 'Exclude units (regex)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_zombie_processes',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0cf03564-c4e8-4b7b-b336-7c968ec28c54',
                'description'      => "Counts zombie (defunct) processes. A zombie is only removed if its parent collects the exit status,\n" .
                    "so the long output lists the zombies grouped by their parent process.\n" .
                    "Warning and Critical: Number of zombie processes.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux, macOS and FreeBSD.",
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
                'name'             => 'check_oitc_agent_windows_automatic_services',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '4ee8f012-f14d-4bd2-b568-f0e5e76da27a',
                'description'      => "Checks all Windows services with the start type Automatic or Automatic (Delayed Start) which are not running.\n" .
                    "Warning and Critical: Number of services which are not running (default: critical on the first service).\n" .
                    "Exclude: Regular expression for the name or display name of services which should be ignored (case insensitive).\n" .
                    "Grace period: No alert within this time after the boot, as delayed services start about 2 minutes after the boot.\n" .
                    "Include trigger start services: Services with a trigger start are stopped by Windows if they are idle, so they are\n" .
                    "ignored by default unless they stopped with an error (true/false).\n" .
                    "Windows only.",
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
                        'human_name' => 'Exclude services (regex)'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Grace period after boot'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Include trigger start services (true/false)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_logfile',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '71fef69e-a452-41c6-940a-88c14773a7f1',
                'description'      => "Counts the lines of a log file, the journal (Linux) or the unified log (macOS) which match the regular expression\n" .
                    "of the definition within its time window. The definition is configured in the logfiles.ini of the agent.\n" .
                    "Name: Name of the section in the logfiles.ini.\n" .
                    "Warning and Critical: Number of matching lines (default: warning on the first matching line).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_certificate',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0240157d-9ab9-43c1-b03e-8ca8734428c5',
                'description'      => "Checks a certificate file, a TLS endpoint or the Windows certificate store defined in the certificates.ini of the agent.\n" .
                    "Expired certificates are always critical.\n" .
                    "Name: Name of the section in the certificates.ini.\n" .
                    "Warning and Critical: Days until the certificate expires.\n" .
                    "State if not trusted: State if the certificate is not trusted or the hostname does not match.\n" .
                    "State on weak cryptography: e.g. SHA-1 signatures, short keys or TLS older than 1.2.\n" .
                    "Check chain: Also check the intermediate certificates (1/0).\n" .
                    "States: ok, warning, critical or unknown.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning days'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical days'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'State if not trusted'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'State on weak cryptography'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Check chain (1/0)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_file',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'a6d7bf0d-529f-4107-9bc7-5eb78dd09bb5',
                'description'      => "Checks the age and the size of the newest file which matches a file definition of the files.ini of the agent,\n" .
                    "e.g. to monitor that a backup was created.\n" .
                    "Name: Name of the section in the files.ini (type = file).\n" .
                    "Warning and Critical age: Maximum age, e.g. 25h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "Warning and Critical size: Size range, e.g. 1MB: (alert if smaller than 1 MB). Units: B, kB, MB, GB, TB, KiB, MiB, GiB, TiB.\n" .
                    "State if missing: States: ok, warning, critical or unknown.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning age'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical age'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning size'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Critical size'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'State if missing'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_directory',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '59f4b534-936f-4ece-9c6c-c6cd5518451a',
                'description'      => "Checks the number of files, the age of the newest and oldest file and the total size of a directory definition\n" .
                    "of the files.ini of the agent, e.g. to monitor a spool directory.\n" .
                    "Name: Name of the section in the files.ini (type = directory).\n" .
                    "Files: Range for the number of files, e.g. ~:50.\n" .
                    "Newest and oldest age: Maximum age, e.g. 1h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "Total size: Size range. Units: B, kB, MB, GB, TB, KiB, MiB, GiB, TiB.\n" .
                    "State if missing: States: ok, warning, critical or unknown.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning files'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical files'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning newest age'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Critical newest age'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'State if missing'
                    ],
                    [
                        'name'       => '$ARG7$',
                        'human_name' => 'Warning oldest age'
                    ],
                    [
                        'name'       => '$ARG8$',
                        'human_name' => 'Critical oldest age'
                    ],
                    [
                        'name'       => '$ARG9$',
                        'human_name' => 'Warning total size'
                    ],
                    [
                        'name'       => '$ARG10$',
                        'human_name' => 'Critical total size'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_http',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'cecb5bd7-4356-41cf-aeb2-3b46abb6dea7',
                'description'      => "Checks an HTTP(S) endpoint defined in the http.ini of the agent. The agent can reach internal services,\n" .
                    "which are not reachable by the openITCOCKPIT server.\n" .
                    "Connection errors, unexpected status codes and content mismatches are critical.\n" .
                    "Name: Name of the section in the http.ini.\n" .
                    "Warning and Critical: Response time in seconds.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning response time'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical response time'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_windows_perfcounter',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0dbd706e-8098-433e-a5af-93d3d8070020',
                'description'      => "Checks a Windows performance counter defined in the perfcounters.ini of the agent.\n" .
                    "Name: Name of the section in the perfcounters.ini.\n" .
                    "Warning and Critical: Thresholds for the value, e.g. ~:500.\n" .
                    "Instance: Check only this instance, empty = all instances.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Windows only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Instance (empty = all)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_windows_scheduled_task',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '9888c028-2f2d-4b97-9ef7-b4ecda238a4b',
                'description'      => "Checks the last run of Windows scheduled tasks. Disabled tasks raise a warning.\n" .
                    "Task path: Regular expression for the path of the tasks, e.g. ^\\\\Backup\\\\\n" .
                    "Warning and Critical age: Maximum age of the last run, e.g. 25h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "State if the last run failed and state if no task matches: States: ok, warning, critical or unknown.\n" .
                    "Windows only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Task path (regex)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning age of the last run'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical age of the last run'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'State if the last run failed'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'State if no task matches'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_firewall',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'f8cfa21c-8ba2-420c-8963-893d38951818',
                'description'      => "Checks if the firewall is enabled.\n" .
                    "State if disabled: States: ok, warning, critical or unknown.\n" .
                    "Profiles (Windows only): active = only the active network profiles, all = domain, private and public.\n" .
                    "Linux: the firewall is active, if nftables or iptables contain rules for incoming connections (e.g. firewalld, ufw or the Proxmox VE firewall).\n" .
                    "Windows, macOS and Linux.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State if disabled'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Profiles (active, all)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_selinux',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3a57f78a-4521-4bfd-9829-88346d57e07b',
                'description'      => "Checks the mode of SELinux.\n" .
                    "Expected mode: enforcing, permissive or disabled.\n" .
                    "State if the mode is weaker than expected: States: ok, warning, critical or unknown.\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Expected mode'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if weaker'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_apparmor',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'a34116a8-1e13-45be-8048-3e647eba5345',
                'description'      => "Checks if AppArmor is enabled and if profiles are in complain mode.\n" .
                    "State if disabled and state if profiles are in complain mode: States: ok, warning, critical or unknown.\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State if disabled'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if profiles in complain mode'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_filevault',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '484df7ea-50a5-4333-a2fc-ab028bf375f3',
                'description'      => "Checks if the disk encryption FileVault is enabled.\n" .
                    "State if disabled: States: ok, warning, critical or unknown.\n" .
                    "macOS only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State if disabled'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_system_integrity_protection',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '4b46b59d-dc23-49e1-a93a-2e9fc7c41a7c',
                'description'      => "Checks if the System Integrity Protection (SIP) is enabled.\n" .
                    "State if disabled and state if a custom configuration is used: States: ok, warning, critical or unknown.\n" .
                    "macOS only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State if disabled'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if custom configuration'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_windows_defender',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '8a30dedd-0d94-48de-970c-c147b2ac9124',
                'description'      => "Checks Microsoft Defender: disabled antivirus or real-time protection is critical.\n" .
                    "The passive mode is OK if another antivirus is active.\n" .
                    "Warning and Critical: Age of the signatures in days.\n" .
                    "Windows only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning days'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical days'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_zfs_pool',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'c8903188-fcb0-409e-8610-40395878a3e7',
                'description'      => "Checks the health and the capacity of ZFS pools.\n" .
                    "DEGRADED pools and device errors are warnings, FAULTED or UNAVAIL pools and data errors are critical.\n" .
                    "Pool: Name of the pool, empty = all pools.\n" .
                    "Warning and Critical: Capacity in percent (0-100).\n" .
                    "Maximum age of the last scrub (optional), e.g. 35d. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "Linux and FreeBSD.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Pool (empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical %'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Maximum age of the last scrub'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_zfs_arc',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'e8fbd219-3a4e-46a4-864b-a9c97d6ec1f5',
                'description'      => "Reports the size and the hit ratio of the ZFS adaptive replacement cache (ARC).\n" .
                    "Warning and Critical: Hit ratio in percent, e.g. 80: and 60:. Empty = only performance data.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux and FreeBSD.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning hit ratio'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical hit ratio'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ceph_health',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'd85e4b92-ff08-402d-acf6-077c06e0762c',
                'description'      => "Checks the health of a Ceph cluster. HEALTH_ERR is critical, muted health checks are ignored.\n" .
                    "State for HEALTH_WARN: States: ok, warning, critical or unknown.\n" .
                    "Ignore: Regular expression for health checks which should be ignored, e.g. ^(RECENT_CRASH|POOL_NO_REDUNDANCY)\$\n" .
                    "Linux only (e.g. Proxmox VE nodes with Ceph).",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State for HEALTH_WARN'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Ignore health checks (regex)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ceph_osd',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'fcfbcc00-d909-45cb-9b52-024f171cedb5',
                'description'      => "Checks the OSDs and placement groups of a Ceph cluster.\n" .
                    "Warning and Critical: Number of OSDs which are down or out (default: warning on the first OSD).\n" .
                    "PG Warning and Critical: Number of placement groups which are not active+clean.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux only.",
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
                        'human_name' => 'PG Warning'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'PG Critical'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ceph_usage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'e8a7c9bd-529e-49b8-ad72-876dc893f36c',
                'description'      => "Checks the raw usage of a Ceph cluster or the usage of a pool.\n" .
                    "Ceph warns at 85% (nearfull) and stops writing at 95% (full) of the fullest OSD.\n" .
                    "Pool: Name of the pool, empty = raw usage of the cluster.\n" .
                    "Warning and Critical: Usage in percent (0-100).\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Pool (empty = cluster)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_smart',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '5e7baf52-2f9e-461b-9e4f-e167f67a225a',
                'description'      => "Checks the SMART health of disks (requires smartmontools 7.0 or newer on the monitored system).\n" .
                    "A failed self-assessment and failing attributes are critical, reallocated, pending or uncorrectable sectors raise a warning.\n" .
                    "Device: Device (e.g. /dev/sda) or serial number, empty = all disks with SMART.\n" .
                    "Warning and Critical temperature in degrees Celsius.\n" .
                    "Warning and Critical wear of SSDs in percent.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Device or serial (empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning temperature'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical temperature'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning SSD wear %'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Critical SSD wear %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_redfish_health',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '9f4594a3-24b3-4c7d-8cf8-09515e0f3cd7',
                'description'      => "Checks the health of a server reported by its BMC (Redfish): systems including CPUs and memory, chassis and the BMC itself.\n" .
                    "The BMC is defined in the redfish.ini of the agent. Warning is a warning, Critical is critical.\n" .
                    "BMC: Name of the section in the redfish.ini.\n" .
                    "Expected power state: e.g. On. Empty = any power state.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'BMC'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Expected power state'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_redfish_sensors',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '6b66ec2c-0301-434f-b7e9-8881b4b84def',
                'description'      => "Checks the temperatures and fans of a server reported by its BMC (Redfish).\n" .
                    "BMC: Name of the section in the redfish.ini of the agent.\n" .
                    "Type: temperature, fan or empty for both.\n" .
                    "Sensor names: Regular expression for the sensor names (case insensitive), empty = all sensors.\n" .
                    "Warning and Critical: Own thresholds (requires a type, degrees Celsius for temperatures).\n" .
                    "Empty = the thresholds and the health reported by the BMC are used.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'BMC'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Type (temperature, fan, empty = both)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Sensor names (regex)'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_redfish_power',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '56f8c7f5-96a1-4107-bd98-64241fcea30c',
                'description'      => "Checks the power supplies, the power redundancy and the power consumption of a server reported by its BMC (Redfish).\n" .
                    "BMC: Name of the section in the redfish.ini of the agent.\n" .
                    "Warning and Critical: Power consumption in watts (optional, per chassis).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'BMC'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning watts'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical watts'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_redfish_storage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3a006d24-7fa8-4dc8-92a5-b2ad9170a0eb',
                'description'      => "Checks the storage controllers, drives (including predicted failures) and volumes of a server reported by its BMC (Redfish).\n" .
                    "BMC: Name of the section in the redfish.ini of the agent.\n" .
                    "Storage names: Regular expression for the storage subsystems (case insensitive), empty = all.\n" .
                    "Warning and Critical: Minimum life left of SSDs in percent.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'BMC'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Storage names (regex)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning life left %'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical life left %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_redfish_eventlog',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '25e72e58-9d81-47e0-90e9-d77b9db6c238',
                'description'      => "Checks the warning and critical entries of the event logs (e.g. SEL or IML) of a BMC (Redfish)\n" .
                    "within the time window (eventlog_window) of the redfish.ini of the agent.\n" .
                    "BMC: Name of the section in the redfish.ini.\n" .
                    "Exclude: Regular expression for the message or message id of entries which should be ignored.\n" .
                    "State for warning entries and state for critical entries: States: ok, warning, critical or unknown.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'BMC'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Exclude entries (regex)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'State for warning entries'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'State for critical entries'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_docker_container_health',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'b8e97f2f-043e-4184-b0ab-874e40bada26',
                'description'      => "Checks the healthcheck state and restart loops of a Docker container.\n" .
                    "Unhealthy containers and containers which are restarting are critical, OOM kills raise a warning.\n" .
                    "Identifier: Field used to find the container (name or id).\n" .
                    "Container: Name or id of the container.\n" .
                    "Warning and Critical: Number of restarts within the last hour (default: warning on the first restart).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Identifier (name, id)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Container'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning restarts'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical restarts'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_postfix_queue',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '826d26ff-7447-40ca-a706-10522e972d1a',
                'description'      => "Checks the number of messages in the Postfix mail queues (Postfix 3.1 or newer). A stopped Postfix is critical.\n" .
                    "Queue: maildrop, incoming, active, deferred or hold. Empty = all queues.\n" .
                    "Warning and Critical: Number of messages.\n" .
                    "Warning and Critical age: Age of the oldest message (optional), e.g. 1h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "Linux and FreeBSD.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Queue (empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning messages'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical messages'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning age'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Critical age'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_proxmox_cluster',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '5c0b2cd7-39ce-4758-8a9d-108ccf3d043d',
                'description'      => "Checks the quorum, the nodes and the HA resources of a Proxmox VE cluster.\n" .
                    "A lost quorum and HA resources in the state error or fence are critical.\n" .
                    "State if a node is offline: States: ok, warning, critical or unknown.\n" .
                    "Proxmox VE only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State if a node is offline'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_proxmox_storage',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'c1a9003a-0ccb-4288-8140-ac7a798ada72',
                'description'      => "Checks the storages of a Proxmox VE node. Enabled storages which are not active are critical.\n" .
                    "Storage: Name of the storage, empty = all enabled storages.\n" .
                    "Warning and Critical: Usage in percent (0-100).\n" .
                    "Proxmox VE only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Storage (empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_proxmox_guest',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '01c87717-091d-4b04-9259-2f6380f3da65',
                'description'      => "Checks the state of a virtual machine or container of a Proxmox VE cluster. The guest is found on every node of the cluster.\n" .
                    "Guest: VMID or name of the guest.\n" .
                    "Expected state: e.g. running or stopped.\n" .
                    "Proxmox VE only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'VMID or name'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Expected state'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_proxmox_backup',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '99a1ab5e-12bc-4db8-a372-5042ba0a3ec8',
                'description'      => "Checks the backups (vzdump) of the guests of a Proxmox VE node. A failed last backup is critical.\n" .
                    "Maximum age of the last successful backup, e.g. 25h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "State for guests without backup job: States: ok, warning, critical or unknown.\n" .
                    "Proxmox VE only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Maximum age of the last backup'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State for guests without backup job'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_proxmox_replication',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'f25bfe01-0257-4029-9864-ca3d6973baf4',
                'description'      => "Checks the storage replication jobs of a Proxmox VE node. Failed replication jobs are critical.\n" .
                    "Maximum age of the last sync (optional), e.g. 1h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "Proxmox VE only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Maximum age of the last sync'
                    ]
                ]
            ],

            // Agent 3.7: bonding, filesystem errors, EDAC, LVM thin pools, missing mounts, Exim and BitLocker
            [
                'name'             => 'check_oitc_agent_bonding',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3f1fb4cc-8417-4650-9abf-77364a356e56',
                'description'      => "Checks Linux network bonds (/proc/net/bonding). A bond keeps working if a link fails, so a failed link is easily overlooked.\n" .
                    "A bond which is down or has no working slave is critical.\n" .
                    "Bond: Name of the bond, e.g. bond0. Empty = all bonds.\n" .
                    "State if a slave is down: States: ok, warning, critical or unknown.\n" .
                    "State if a slave is not part of the active aggregator (802.3ad/LACP, e.g. wrong configuration of the switch): States: ok, warning, critical or unknown.\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Bond (empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if a slave is down'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'State if a slave is not in the active aggregator'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_filesystem_errors',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'd8e66844-71a3-4c49-86d9-b6089554fa0b',
                'description'      => "Checks the error counters of ext4 and btrfs filesystems. Filesystem errors are often the first sign of a failing disk.\n" .
                    "ext4 keeps the error counter until the filesystem was checked with fsck, btrfs counters are reset with \"btrfs device stats -z\".\n" .
                    "Filesystems: Regular expression for the mountpoint or device, empty = all filesystems.\n" .
                    "Warning and Critical: Number of errors (default: critical on the first error).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Filesystems (regex)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_edac',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '0e5fcb46-93ef-4909-b4b3-f21875f57c13',
                'description'      => "Checks the memory errors of ECC memory reported by the Linux EDAC driver since the boot.\n" .
                    "Uncorrected errors are always critical. A growing number of corrected errors is a sign of a failing DIMM.\n" .
                    "Warning and Critical: Number of corrected errors (default: warning on the first error).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux only, physical servers with ECC memory.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning corrected errors'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical corrected errors'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_lvm_thin',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '18ec98c6-ec5c-46d6-93b8-be4b23a2ec72',
                'description'      => "Checks the usage of LVM thin pools, e.g. local-lvm of Proxmox VE. A full thin pool corrupts the filesystems of its thin volumes.\n" .
                    "Unhealthy pools (e.g. out of data space or read only metadata) are critical.\n" .
                    "Thin pool: vg/pool, e.g. pve/data. Empty = all thin pools.\n" .
                    "Warning and Critical: Usage of the data and the metadata in percent (0-100).\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Thin pool (vg/pool, empty = all)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Warning data %'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Critical data %'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning metadata %'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Critical metadata %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_missing_mounts',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'e04b9f41-55b8-4665-9543-b07b5c9892cb',
                'description'      => "Checks if all filesystems of the /etc/fstab are mounted, e.g. a NFS share after a reboot. Swap and entries with noauto are ignored.\n" .
                    "State if a filesystem is missing and state if a filesystem with the option nofail (FreeBSD: failok) is missing: States: ok, warning, critical or unknown.\n" .
                    "Exclude: Regular expression for mountpoints which should be ignored, e.g. ^/mnt/usb\n" .
                    "Linux and FreeBSD.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'State if missing'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if missing with nofail'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Exclude mountpoints (regex)'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_exim_queue',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '953b13cf-6b78-4366-b229-44dd3b71e409',
                'description'      => "Checks the mail queue of Exim. Frozen messages are not delivered by Exim anymore.\n" .
                    "Warning and Critical: Number of messages.\n" .
                    "Warning and Critical frozen: Number of frozen messages (default: warning on the first frozen message).\n" .
                    "Warning and Critical age: Age of the oldest message (optional), e.g. 1h. Durations can be written with units: s, m, h, d, w (e.g. 25h or 7d).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux and FreeBSD.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning messages'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical messages'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning frozen'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical frozen'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Warning age'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Critical age'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_bitlocker',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '40e92165-c7f8-4009-8431-5a6a334d12ba',
                'description'      => "Checks the BitLocker drive encryption. An encryption or decryption in progress is a warning.\n" .
                    "Drive: e.g. C:, empty = the operating system volume and all fixed data volumes.\n" .
                    "State if the protection is off or suspended: States: ok, warning, critical or unknown.\n" .
                    "Windows only, the agent has to run as service (SYSTEM).",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Drive (empty = OS and fixed volumes)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if off'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_network_port',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '4651f946-f9c3-442c-a899-404ab84007eb',
                'description'      => "Checks that a service listens on a port of the host, like check_tcp but without network access from the openITCOCKPIT server. A port which is not listening is critical.\n" .
                    "Port: e.g. 443. Empty = any port of the process (the process is required in this case).\n" .
                    "Protocol: tcp or udp.\n" .
                    "Process: regular expression for the process which has to listen on the port (optional), e.g. ^nginx$. The process of other users requires the agent to run as root (Linux, FreeBSD) or as service (Windows).\n" .
                    "Address: empty = any address, public = at least one address which is not 127.0.0.1 or ::1, or an IP address (also fulfilled by 0.0.0.0 and ::).\n" .
                    "Warning and Critical: Number of established TCP connections (optional).\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Port'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Protocol (tcp, udp)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Process (regex)'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Address (empty, public or IP)'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Warning connections'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Critical connections'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_tcp_states',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'b209a015-9814-4fc3-919f-77ec9aaa75a0',
                'description'      => "Checks the number of TCP connections per state.\n" .
                    "CLOSE_WAIT: the remote side closed the connection, but the local application did not. Many connections in this state are almost always a bug of the application.\n" .
                    "SYN_RECV: half open incoming connections, e.g. SYN flood or an overloaded application.\n" .
                    "TIME_WAIT and ESTABLISHED: optional thresholds.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning CLOSE_WAIT'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical CLOSE_WAIT'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning SYN_RECV'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical SYN_RECV'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Warning TIME_WAIT'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Critical TIME_WAIT'
                    ],
                    [
                        'name'       => '$ARG7$',
                        'human_name' => 'Warning ESTABLISHED'
                    ],
                    [
                        'name'       => '$ARG8$',
                        'human_name' => 'Critical ESTABLISHED'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ephemeral_ports',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '48583e60-042f-4b67-8756-fb82b2c8f382',
                'description'      => "Checks the usage of the local port range for outgoing TCP connections (ephemeral ports). If the range is exhausted, new connections fail with \"Cannot assign requested address\", e.g. on proxies or applications without connection pooling.\n" .
                    "Warning and Critical: Usage in percent.\n" .
                    "The output contains the destination with the most connections.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical %'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_tcp_errors',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'f0e8e9f8-14cd-47a5-861a-e179245209eb',
                'description'      => "Checks TCP and UDP errors per second, averaged over the check interval of the agent.\n" .
                    "Dropped connection attempts: e.g. if the accept queue is full because the application does not accept the connections fast enough. The clients see timeouts (default: warning on the first drop).\n" .
                    "Retransmits: retransmitted TCP segments in percent of the sent segments, e.g. packet loss. Only evaluated if at least 1000 segments were sent.\n" .
                    "UDP buffer errors: dropped UDP packets per second because the buffer of a socket was full.\n" .
                    "Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Linux only.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning drops/s'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical drops/s'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning retransmits %'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical retransmits %'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Warning UDP buffer errors/s'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'Critical UDP buffer errors/s'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_unexpected_ports',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'f27e454c-5576-4ae2-8420-69095f155aba',
                'description'      => "Alerts on listening ports which are not allowed, e.g. a new service or a forgotten debug port.\n" .
                    "Allowed ports: comma separated list, e.g. 22,80,443,udp/53,tcp/8000-8100. Ports without protocol apply to TCP and UDP.\n" .
                    "Protocols: tcp, udp or all.\n" .
                    "Include loopback: yes or no. Ports on 127.0.0.1 and ::1 are ignored by default.\n" .
                    "Ignore ephemeral ports: yes or no, e.g. for the dynamic RPC ports of Windows (49152-65535).\n" .
                    "Ignore processes: regular expression (optional).\n" .
                    "State for unexpected ports: ok, warning, critical or unknown.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Allowed ports'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Protocols (tcp, udp, all)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Include loopback (yes, no)'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Ignore ephemeral ports (yes, no)'
                    ],
                    [
                        'name'       => '$ARG5$',
                        'human_name' => 'Ignore processes (regex)'
                    ],
                    [
                        'name'       => '$ARG6$',
                        'human_name' => 'State if unexpected'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ipmi_health',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '7b777447-441e-4889-a6b1-bc44840564bd',
                'description'      => "Checks the overall health of the server reported by the local BMC (IPMI): all sensors and the chassis status.\n" .
                    "Analog sensors (temperatures, fans, voltages, ...) are evaluated with the thresholds of the BMC, discrete sensors (power supplies, redundancy, drives, memory, ...) with the severities of the IPMI specification. Sensors without reading (e.g. empty DIMM slots) are ignored.\n" .
                    "Exclude sensors: regular expression for sensor names (case insensitive, optional).\n" .
                    "State if the chassis was opened: ok, warning, critical or unknown.\n" .
                    "Requires a BMC: Linux /dev/ipmi0 (kernel modules ipmi_si and ipmi_devintf, agent as root), Windows the IPMI driver (agent as service). No ipmitool required.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Exclude sensors (regex)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State if chassis opened'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ipmi_sensors',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '212418a9-a5ab-43af-8a99-1784541d8595',
                'description'      => "Checks the sensors with readings of the local BMC (IPMI) and creates performance data.\n" .
                    "Type: temperature, fan, voltage, power, current or empty for all sensors with readings.\n" .
                    "Sensor names: regular expression (case insensitive, optional).\n" .
                    "Warning and Critical: own thresholds (optional), they replace the thresholds of the BMC and require a type. Thresholds use the range format of the monitoring plugins: https://www.monitoring-plugins.org/doc/guidelines.html#THRESHOLDFORMAT\n" .
                    "Requires a BMC: Linux /dev/ipmi0 (kernel modules ipmi_si and ipmi_devintf, agent as root), Windows the IPMI driver (agent as service). No ipmitool required.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Type'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Sensor names (regex)'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            [
                'name'             => 'check_oitc_agent_ipmi_sel',
                'command_line'     => '$USER1$/check_dummy 3 "No data received from agent"',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'c4f7b1bc-0a88-4491-99c7-917b150a402f',
                'description'      => "Checks the system event log (SEL) of the local BMC (IPMI): warnings and critical entries within the time window of the agent (ipmi-sel-window, default 24 hours). The end of a condition (e.g. a power supply which works again) is not reported.\n" .
                    "Exclude: regular expression for the sensor and the description (case insensitive, optional).\n" .
                    "State for warning entries and for critical entries: ok, warning, critical or unknown.\n" .
                    "SEL usage: warning if the SEL is full or the usage exceeds the given percent (0 = off). Many BMCs stop logging if the SEL is full.\n" .
                    "Requires a BMC: Linux /dev/ipmi0 (kernel modules ipmi_si and ipmi_devintf, agent as root), Windows the IPMI driver (agent as service). No ipmitool required.",
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Exclude (regex)'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'State for warning entries'
                    ],
                    [
                        'name'       => '$ARG3$',
                        'human_name' => 'State for critical entries'
                    ],
                    [
                        'name'       => '$ARG4$',
                        'human_name' => 'Warning SEL usage %'
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
                'name'                                      => 'Network Statistics',
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

            [
                'uuid'                                      => 'eef93b44-1ffa-4b8b-a8c3-e1a1258bf68c',
                'template_name'                             => 'OITC_AGENT_SYSTEM_UPDATES',
                'name'                                      => 'System Updates',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'fc502c30-8629-453a-a481-382d66cefa8c',
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
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '0',
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

            // Agent 3.7: new monitoring features (logfiles, system health, security, ZFS, Ceph, SMART, Proxmox VE, Redfish, ...)
            $this->getAgentServicetemplate('4d293bff-06fc-48c1-acd6-564fed2e7359', 'OITC_AGENT_MDRAID', 'Software RAID', '3c1e4e69-d27b-495c-bd91-e5b22a1afb31', ['']),
            $this->getAgentServicetemplate('85c25766-2afc-4c6b-89b3-565cb8d66b96', 'OITC_AGENT_OOM_KILL', 'OOM kills', '06d903d9-02b0-419a-9c1b-2fce8c1b1d26', ['0', '', '3600']),
            $this->getAgentServicetemplate('8089e21b-d5be-4187-b9d6-0409c1a59b9a', 'OITC_AGENT_FILE_DESCRIPTORS', 'File descriptors', '02e4c02d-5116-4ca1-989d-ea0e72c941a4', ['80', '90']),
            $this->getAgentServicetemplate('ce769054-6fbb-44d6-a14d-47db38313a91', 'OITC_AGENT_CONNTRACK', 'Conntrack table', '41c7a349-df22-4b57-9cf1-0e5c87f1c992', ['80', '90']),
            $this->getAgentServicetemplate('3589b65e-8403-464b-befd-c1c6689ded21', 'OITC_AGENT_PRESSURE', 'Pressure stall information', '5c5fa13c-c28f-4d6e-a880-9700c47e9e80', ['cpu', '40', '80', 'some', '60']),
            $this->getAgentServicetemplate('80ae83c6-41eb-443b-9e18-c32341b0ed95', 'OITC_AGENT_DISK_INODES', 'Inode usage', '0ddcc07b-6c84-4b3c-8888-619466b2ee90', ['80', '90', '/']),
            $this->getAgentServicetemplate('7b779163-4bfa-462c-bac7-b84515cc6347', 'OITC_AGENT_MOUNT_READONLY', 'Read-only filesystems', '3104c1eb-75c7-44c9-9f3b-fa81bd55b58c', ['', 'critical', '']),
            $this->getAgentServicetemplate('08514e2c-c23c-4431-98f5-a213df7098df', 'OITC_AGENT_SYSTEMD_FAILED_UNITS', 'Failed systemd units', 'c48cb0c8-eebc-4690-8067-61ef41c6f6b7', ['', '0', '']),
            $this->getAgentServicetemplate('21945974-bb8a-4dd1-a0e1-765cdb5663c0', 'OITC_AGENT_ZOMBIE_PROCESSES', 'Zombie processes', '0cf03564-c4e8-4b7b-b336-7c968ec28c54', ['5', '10']),
            $this->getAgentServicetemplate('c3c79ca5-a29e-467e-a8e5-2800406c3c70', 'OITC_AGENT_WINDOWS_AUTOMATIC_SERVICES', 'Automatic services', '4ee8f012-f14d-4bd2-b568-f0e5e76da27a', ['', '0', '^(MapsBroker|TrustedInstaller|gupdate|GoogleUpdaterService.*|edgeupdate|sppsvc)$', '10m', 'false']),
            $this->getAgentServicetemplate('fc6ecf52-e354-4e7f-b88b-cf5133a3b6aa', 'OITC_AGENT_LOGFILE', 'Logfile', '71fef69e-a452-41c6-940a-88c14773a7f1', ['', '0', '']),
            $this->getAgentServicetemplate('052eca9c-4161-413a-998e-bf1298bd5b64', 'OITC_AGENT_CERTIFICATE', 'Certificate', '0240157d-9ab9-43c1-b03e-8ca8734428c5', ['', '30', '14', 'warning', 'warning', '1']),
            $this->getAgentServicetemplate('8c6112c9-5bec-43bd-bfb9-6924274f54b2', 'OITC_AGENT_FILE', 'File', 'a6d7bf0d-529f-4107-9bc7-5eb78dd09bb5', ['', '', '', '', '', 'critical']),
            $this->getAgentServicetemplate('d82d997c-bb5e-48dc-b7a1-8e1c57078bd0', 'OITC_AGENT_DIRECTORY', 'Directory', '59f4b534-936f-4ece-9c6c-c6cd5518451a', ['', '', '', '', '', 'critical', '', '', '', '']),
            $this->getAgentServicetemplate('e82dfaa1-0af9-4c7e-a12e-d4c7c7aacc3b', 'OITC_AGENT_HTTP', 'HTTP', 'cecb5bd7-4356-41cf-aeb2-3b46abb6dea7', ['', '1', '5']),
            $this->getAgentServicetemplate('bec5d45c-d9b0-4d1d-8611-fef685a3510e', 'OITC_AGENT_WINDOWS_PERFCOUNTER', 'Performance counter', '0dbd706e-8098-433e-a5af-93d3d8070020', ['', '', '', '']),
            $this->getAgentServicetemplate('153fadc5-9b88-4bbc-b981-97081b817840', 'OITC_AGENT_WINDOWS_SCHEDULED_TASK', 'Scheduled task', '9888c028-2f2d-4b97-9ef7-b4ecda238a4b', ['', '', '', 'critical', 'critical']),
            $this->getAgentServicetemplate('f5c7d77c-5b69-4498-bab5-6f6b5db59ba1', 'OITC_AGENT_FIREWALL', 'Firewall', 'f8cfa21c-8ba2-420c-8963-893d38951818', ['critical', 'active']),
            $this->getAgentServicetemplate('b45f7351-2591-449b-a212-34ad1b0497fa', 'OITC_AGENT_SELINUX', 'SELinux', '3a57f78a-4521-4bfd-9829-88346d57e07b', ['enforcing', 'critical']),
            $this->getAgentServicetemplate('f4b6c286-00fd-47f8-8a28-a34def9100e3', 'OITC_AGENT_APPARMOR', 'AppArmor', 'a34116a8-1e13-45be-8048-3e647eba5345', ['critical', 'ok']),
            $this->getAgentServicetemplate('e1fa1856-cd40-4e9a-ad89-75c4bcd3ecc5', 'OITC_AGENT_FILEVAULT', 'FileVault', '484df7ea-50a5-4333-a2fc-ab028bf375f3', ['critical']),
            $this->getAgentServicetemplate('d906c938-ec3d-4c83-ac96-d9e8b670b9b8', 'OITC_AGENT_SYSTEM_INTEGRITY_PROTECTION', 'System Integrity Protection', '4b46b59d-dc23-49e1-a93a-2e9fc7c41a7c', ['critical', 'warning']),
            $this->getAgentServicetemplate('f28f4628-e03a-4813-936e-0ecbea92986f', 'OITC_AGENT_WINDOWS_DEFENDER', 'Microsoft Defender', '8a30dedd-0d94-48de-970c-c147b2ac9124', ['3', '7']),
            $this->getAgentServicetemplate('6af127b0-56b2-41e0-8ad6-ffe1c633f54d', 'OITC_AGENT_ZFS_POOL', 'ZFS pools', 'c8903188-fcb0-409e-8610-40395878a3e7', ['', '80', '90', '']),
            $this->getAgentServicetemplate('f20b6c89-8914-46b6-b7a9-ef0115e76895', 'OITC_AGENT_ZFS_ARC', 'ZFS ARC', 'e8fbd219-3a4e-46a4-864b-a9c97d6ec1f5', ['', '']),
            $this->getAgentServicetemplate('043c16e6-e033-446c-b399-3be4afd6a561', 'OITC_AGENT_CEPH_HEALTH', 'Ceph health', 'd85e4b92-ff08-402d-acf6-077c06e0762c', ['warning', '']),
            $this->getAgentServicetemplate('c7c80d63-6b7c-46dc-97ec-799efdef7149', 'OITC_AGENT_CEPH_OSD', 'Ceph OSDs', 'fcfbcc00-d909-45cb-9b52-024f171cedb5', ['0', '', '', '']),
            $this->getAgentServicetemplate('6383a920-f22a-47a8-b1ca-b0de18184f6c', 'OITC_AGENT_CEPH_USAGE', 'Ceph usage', 'e8a7c9bd-529e-49b8-ad72-876dc893f36c', ['', '75', '85']),
            $this->getAgentServicetemplate('82188d07-d8a1-4cc1-84c7-3782f80c730f', 'OITC_AGENT_SMART', 'SMART', '5e7baf52-2f9e-461b-9e4f-e167f67a225a', ['', '60', '70', '80', '90']),
            $this->getAgentServicetemplate('83d6640a-adbe-443e-b823-470869629712', 'OITC_AGENT_REDFISH_HEALTH', 'Redfish health', '9f4594a3-24b3-4c7d-8cf8-09515e0f3cd7', ['', '']),
            $this->getAgentServicetemplate('9fb0d09b-e467-4282-ae86-b7b538e888d7', 'OITC_AGENT_REDFISH_SENSORS', 'Redfish sensors', '6b66ec2c-0301-434f-b7e9-8881b4b84def', ['', '', '', '', '']),
            $this->getAgentServicetemplate('50ff1ade-8596-46e8-9fc2-23607bea6aad', 'OITC_AGENT_REDFISH_POWER', 'Redfish power', '56f8c7f5-96a1-4107-bd98-64241fcea30c', ['', '', '']),
            $this->getAgentServicetemplate('459f2366-c067-45a9-86d1-3e2d1183271e', 'OITC_AGENT_REDFISH_STORAGE', 'Redfish storage', '3a006d24-7fa8-4dc8-92a5-b2ad9170a0eb', ['', '', '20', '10']),
            $this->getAgentServicetemplate('dea2ecf4-001b-44aa-b345-a0198ac2f510', 'OITC_AGENT_REDFISH_EVENTLOG', 'Redfish event log', '25e72e58-9d81-47e0-90e9-d77b9db6c238', ['', '', 'warning', 'critical']),
            $this->getAgentServicetemplate('f108b1f7-3f5e-4217-bc3d-df6f56cef2e3', 'OITC_AGENT_DOCKER_CONTAINER_HEALTH', 'Docker container health', 'b8e97f2f-043e-4184-b0ab-874e40bada26', ['name', '', '0', '']),
            $this->getAgentServicetemplate('e9db80e0-7cea-48c8-8229-3cf74d71f0b1', 'OITC_AGENT_POSTFIX_QUEUE', 'Postfix queue', '826d26ff-7447-40ca-a706-10522e972d1a', ['', '50', '100', '', '']),
            $this->getAgentServicetemplate('161f2e1c-f173-40f3-ae12-b22ff1ef679c', 'OITC_AGENT_PROXMOX_CLUSTER', 'Proxmox cluster', '5c0b2cd7-39ce-4758-8a9d-108ccf3d043d', ['warning']),
            $this->getAgentServicetemplate('db5a02aa-b017-47bd-b7eb-870b470bfa9f', 'OITC_AGENT_PROXMOX_STORAGE', 'Proxmox storages', 'c1a9003a-0ccb-4288-8140-ac7a798ada72', ['', '80', '90']),
            $this->getAgentServicetemplate('f5b2deea-0198-4b46-92f6-456ca4b75035', 'OITC_AGENT_PROXMOX_GUEST', 'Proxmox guest', '01c87717-091d-4b04-9259-2f6380f3da65', ['', 'running']),
            $this->getAgentServicetemplate('f1372ba7-e1df-4d9e-9ccc-cd9e238df708', 'OITC_AGENT_PROXMOX_BACKUP', 'Proxmox backups', '99a1ab5e-12bc-4db8-a372-5042ba0a3ec8', ['25h', 'warning']),
            $this->getAgentServicetemplate('42ee9475-f1d4-43a7-9b40-bba50896d340', 'OITC_AGENT_PROXMOX_REPLICATION', 'Proxmox replication', 'f25bfe01-0257-4029-9864-ca3d6973baf4', ['']),

            // Agent 3.7: bonding, filesystem errors, EDAC, LVM thin pools, missing mounts, Exim and BitLocker
            $this->getAgentServicetemplate('92889b93-26d0-4624-8c89-5786de537853', 'OITC_AGENT_BONDING', 'Network bond', '3f1fb4cc-8417-4650-9abf-77364a356e56', ['', 'warning', 'warning']),
            $this->getAgentServicetemplate('00c5473d-9060-46b4-bae8-14293f169361', 'OITC_AGENT_FILESYSTEM_ERRORS', 'Filesystem errors', 'd8e66844-71a3-4c49-86d9-b6089554fa0b', ['', '', '0']),
            $this->getAgentServicetemplate('68addeef-25bd-477a-a67a-37d2032f0e33', 'OITC_AGENT_EDAC', 'Memory errors (ECC)', '0e5fcb46-93ef-4909-b4b3-f21875f57c13', ['0', '']),
            $this->getAgentServicetemplate('19acdc71-ba20-44a8-a3e6-402b8cd7cd60', 'OITC_AGENT_LVM_THIN', 'LVM thin pool', '18ec98c6-ec5c-46d6-93b8-be4b23a2ec72', ['', '80', '90', '80', '90']),
            $this->getAgentServicetemplate('8eb07426-462c-4e09-bdc3-3f0091e9d8a9', 'OITC_AGENT_MISSING_MOUNTS', 'Missing mounts', 'e04b9f41-55b8-4665-9543-b07b5c9892cb', ['critical', 'warning', '']),
            $this->getAgentServicetemplate('0f0690ec-4bb4-4d0e-9af7-79d14cf12a12', 'OITC_AGENT_EXIM_QUEUE', 'Exim queue', '953b13cf-6b78-4366-b229-44dd3b71e409', ['50', '100', '0', '', '', '']),
            $this->getAgentServicetemplate('10fc3768-884c-4286-9490-89d9d4b4a7b1', 'OITC_AGENT_BITLOCKER', 'BitLocker', '40e92165-c7f8-4009-8431-5a6a334d12ba', ['', 'critical']),

            // Agent 3.7: network ports
            $this->getAgentServicetemplate('b4736b11-23aa-4f64-b717-64e7e155e675', 'OITC_AGENT_NETWORK_PORT', 'Network port', '4651f946-f9c3-442c-a899-404ab84007eb', ['', 'tcp', '', '', '', '']),
            $this->getAgentServicetemplate('38ebb55e-9c88-455f-a536-d0f930a465d1', 'OITC_AGENT_TCP_STATES', 'TCP connection states', 'b209a015-9814-4fc3-919f-77ec9aaa75a0', ['100', '500', '100', '500', '', '', '', '']),
            $this->getAgentServicetemplate('27a4b783-621f-42bc-b104-d9cb06d288a2', 'OITC_AGENT_EPHEMERAL_PORTS', 'Ephemeral ports', '48583e60-042f-4b67-8756-fb82b2c8f382', ['80', '90']),
            $this->getAgentServicetemplate('2d3faac5-82f6-4f08-83d5-3891788a350d', 'OITC_AGENT_TCP_ERRORS', 'TCP errors', 'f0e8e9f8-14cd-47a5-861a-e179245209eb', ['0', '1', '5', '10', '1', '10']),
            $this->getAgentServicetemplate('33d9e80f-228e-4507-ab85-b173c2f98f05', 'OITC_AGENT_UNEXPECTED_PORTS', 'Unexpected ports', 'f27e454c-5576-4ae2-8420-69095f155aba', ['', 'tcp', 'no', 'no', '', 'warning']),

            // Agent 3.7: IPMI
            $this->getAgentServicetemplate('96d90f51-95ab-4ff7-82af-62681fdcdec8', 'OITC_AGENT_IPMI_HEALTH', 'IPMI health', '7b777447-441e-4889-a6b1-bc44840564bd', ['', 'warning']),
            $this->getAgentServicetemplate('2281db92-0525-4dd4-ac53-d04ed66d9278', 'OITC_AGENT_IPMI_SENSORS', 'IPMI sensors', '212418a9-a5ab-43af-8a99-1784541d8595', ['', '', '', '']),
            $this->getAgentServicetemplate('8e883faf-31e1-4e6a-9c86-65846ae4dca4', 'OITC_AGENT_IPMI_SEL', 'IPMI event log', 'c4f7b1bc-0a88-4491-99c7-917b150a402f', ['', 'warning', 'critical', '90']),
        ];
        return $data;
    }

    /**
     * Returns a service template for a passive check of the openITCOCKPIT Monitoring Agent
     *
     * @param string $uuid
     * @param string $templateName
     * @param string $name
     * @param string $commandUuid
     * @param array $commandargumentValues Values of $ARG1$, $ARG2$, ...
     * @return array
     */
    private function getAgentServicetemplate(string $uuid, string $templateName, string $name, string $commandUuid, array $commandargumentValues): array {
        $servicetemplatecommandargumentvalues = [];
        foreach (array_values($commandargumentValues) as $index => $value) {
            $servicetemplatecommandargumentvalues[] = [
                'commandargument_id' => sprintf('$ARG%d$', $index + 1),
                'value'              => $value,
            ];
        }

        return [
            'uuid'                                      => $uuid,
            'template_name'                             => $templateName,
            'name'                                      => $name,
            'container_id'                              => ROOT_CONTAINER,
            'servicetemplatetype_id'                    => OITC_AGENT_SERVICE,
            'check_period_id'                           => '1',
            'notify_period_id'                          => '1',
            'description'                               => '',
            'command_id'                                => $commandUuid,
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
            'servicetemplatecommandargumentvalues'      => $servicetemplatecommandargumentvalues,
            'customvariables'                           => [],
            'servicegroups'                             => [],
            'contactgroups'                             => [],
            'contacts'                                  => []
        ];
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
