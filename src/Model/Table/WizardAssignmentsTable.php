<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

namespace App\Model\Table;

use App\Lib\PluginManager;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\Wizards\ModuleWizardsInterface;


/**
 * WizardAssignments Model
 *
 * @property \App\Model\Table\ServicetemplatesToWizardAssignmentsTable&\Cake\ORM\Association\HasMany $ServicetemplatesToWizardAssignments
 *
 * @method \App\Model\Entity\WizardAssignment newEmptyEntity()
 * @method \App\Model\Entity\WizardAssignment newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\WizardAssignment[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WizardAssignment get($primaryKey, $options = [])
 * @method \App\Model\Entity\WizardAssignment findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\WizardAssignment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\WizardAssignment[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WizardAssignment|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WizardAssignment saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WizardAssignment[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\WizardAssignment[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\WizardAssignment[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\WizardAssignment[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class WizardAssignmentsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('wizard_assignments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsToMany('Servicetemplates', [
            'className'        => 'Servicetemplates',
            'foreignKey'       => 'wizard_assignment_id',
            'targetForeignKey' => 'servicetemplate_id',
            'joinTable'        => 'servicetemplates_to_wizard_assignments',
            'saveStrategy'     => 'replace'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->notEmptyString('uuid')
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->isUnique(['uuid']), ['errorField' => 'uuid']);
        return $rules;
    }

    /**
     * @param array $ACL_PERMISSIONS
     * @return array
     */
    public function getAvailableWizards($ACL_PERMISSIONS = []) {
        // Core Wizards
        if (!(isset($ACL_PERMISSIONS['hosts']['add']) && isset($ACL_PERMISSIONS['services']['add']))) {
            return [];
        }
        $wizards = [
            'linux-server-agent'   => [
                'type_id'                 => 'linux-server-agent',
                'uuid'                    => '4bdca652-cf5c-4b85-ab4f-d75695ec725c',
                'title'                   => __('Linux Server'),
                'description'             => __('Monitoring for your Linux Server with openITCOCKPIT Agent'),
                'image'                   => 'linux.svg',
                'directive'               => 'linux-server', //AngularJS directive
                'category'                => ['linux'],
                'state'                   => 'AgentconnectorsConfig',
                'second_url'              => '/agentconnector/config/:hostId',
                'selected_os'             => 'linux',
                'necessity_of_assignment' => false

            ],
            /*
            'linux-server-ssh'        => [
                'type_id'                 => 'linux-server-ssh',
                'uuid'                    => 'b5b31647-6095-444b-ad05-22aac099ee45',
                'title'                   => __('Linux (SSH)'),
                'description'             => __('Monitoring via Secure Shell (SSH) enables you to gather performance and system data from many Linux and Unix distributions'),
                'image'                   => 'linux.svg',
                'directive'               => 'linux-ssh', //AngularJS directive
                'category'                => ['linux'],
                'state'                   => 'WizardsLinuxServerSsh',
                'selected_os'             => 'linux',
                'necessity_of_assignment' => true
            ],
            'linux-server-snmp'       => [
                'type_id'                 => 'linux-server-snmp',
                'uuid'                    => '2f172d07-ba32-4b9b-b67c-4c296bc5d14b',
                'title'                   => __('Linux (SNMP)'),
                'description'             => __('Monitoring Linux devices via SNMP'),
                'image'                   => 'linux.svg',
                'directive'               => 'linux-snmp', //AngularJS directive
                'category'                => ['linux'],
                'state'                   => 'WizardsLinuxServerSnmp',
                'selected_os'             => 'linux',
                'necessity_of_assignment' => true
            ],
            */
            'windows-server-agent' => [
                'type_id'                 => 'windows-server-agent',
                'uuid'                    => 'e6b36290-9543-4aae-a074-09ff455bc8c9',
                'title'                   => __('Windows Server'),
                'description'             => __('Monitoring for your Windows Server with openITCOCKPIT Agent'),
                'image'                   => 'Windows.svg',
                'directive'               => 'windows', //AngularJS directive
                'category'                => ['windows'],
                'state'                   => 'AgentconnectorsConfig',
                'second_url'              => '/agentconnector/config/:hostId',
                'selected_os'             => 'windows',
                'necessity_of_assignment' => false
            ],
            /*
            'windows-server-snmp'     => [
                'type_id'                 => 'windows-server-snmp',
                'uuid'                    => '6b076d6d-296b-4959-a4e8-9ab242ae513f',
                'title'                   => __('Windows (SNMP)'),
                'description'             => __('Monitoring Windows server with SNMP'),
                'image'                   => 'Windows.svg',
                'directive'               => 'windows-snmp', //AngularJS directive
                'category'                => ['windows'],
                'state'                   => 'WizardsWindowsServerSnmp',
                'selected_os'             => 'windows',
                'necessity_of_assignment' => true
            ],
            'windows-server-nsclient' => [
                'type_id'                 => 'windows-server-nsclient',
                'uuid'                    => '9fc2cd53-9f44-4f78-b0a1-2b40b1161009',
                'title'                   => __('Windows (NSClient++)'),
                'description'             => __('NSClient++ (nscp) aims to be a simple yet powerful and flexible monitoring daemon'),
                'image'                   => 'nsclient-logo-300x75.png',
                'directive'               => 'windows-nsclient', //AngularJS directive
                'category'                => ['windows'],
                'state'                   => 'WizardsWindowsServerNSClient',
                'selected_os'             => 'windows',
                'necessity_of_assignment' => true
            ],
            */
            'macos-server'         => [
                'type_id'                 => 'macos-server',
                'uuid'                    => '784c4ee7-9d79-4cf3-b491-fffba497d175',
                'title'                   => __('macOS Server'),
                'description'             => __('Monitoring for your macOS Server with openITCOCKPIT Agent'),
                'image'                   => 'MacOS-Logo.svg',
                'directive'               => 'macos', //AngularJS directive
                'category'                => ['macos'],
                'state'                   => 'AgentconnectorsConfig',
                'second_url'              => '/agentconnector/config/:hostId',
                'selected_os'             => 'macos',
                'necessity_of_assignment' => false
            ],
            'mysql-server'         => [
                'type_id'                 => 'mysql-server',
                'uuid'                    => '7fb02fac-1ac5-43cf-baf2-b5893f9f9aa8',
                'title'                   => __('Mysql'),
                'description'             => __('Track MySQL Query Throughput, Execution Performance, Connections, And Buffer Pool Usage'),
                'image'                   => 'MySQL_logo.svg',
                'directive'               => 'mysql', //AngularJS directive
                'state'                   => 'WizardsMysqlServer',
                'second_url'              => '/wizards/mysqlserver/:hostId',
                'category'                => ['linux', 'mysql', 'database'],
                'necessity_of_assignment' => true
            ]
            /*
            'docker'                  => [
                'type_id'                 => 'docker',
                'uuid'                    => '18bf7126-2015-483f-882a-ca4bd55888da',
                'title'                   => __('Docker'),
                'description'             => __('Instantly monitor & troubleshoot issues within containers'),
                'image'                   => 'docker.png',
                'directive'               => 'docker', //AngularJS directive
                'category'                => ['linux', 'docker'],
                'necessity_of_assignment' => true
            ],
            */
        ];
        $modules = PluginManager::getAvailablePlugins();
        foreach ($modules as $module) {
            $className = sprintf('\\%s\\Lib\\Wizards', $module);
            if (class_exists($className)) {

                /** @var ModuleWizardsInterface $PluginWizards */
                $PluginWizards = new $className($ACL_PERMISSIONS);

                foreach ($PluginWizards->getAvailableWizards() as $typeId => $pluginWizard) {
                    $wizards[$typeId] = $pluginWizard;
                }
            }
        }

        return $wizards;
    }

    /**
     * @param array $availableWizards
     * @return array
     */
    public function getPossibleWizardsOfModules($availableWizards = []) {
        // placeholder wizards if a module is not installed
        $possibleWizards = [
            'checkmk'                => [
                'type_id'     => 'checkmk',
                'title'       => __('Checkmk'),
                'description' => __('Monitoring for your server with checkmk'),
                'image'       => 'checkmk_logo_main_vertical.svg',
                'category'    => ['checkmk', 'linux', 'windows', 'macos']
            ],
            'prometheus'             => [
                'type_id'     => 'prometheus',
                'title'       => __('Prometheus'),
                'description' => __('Metrics based monitoring using Prometheus'),
                'image'       => 'prometheus.svg',
                'category'    => ['network', 'linux', 'windows', 'macos']
            ],
            'network'                => [
                'type_id'     => 'network',
                'title'       => __('Network'),
                'description' => __('Used to check any kind of network devices and interfaces like: Switch, Router, Firewall, Access Point, ...'),
                'image'       => 'network.png',
                'category'    => ['linux', 'network']
            ],
            'sap'                    => [
                'type_id'     => 'sap',
                'title'       => __('SAP'),
                'description' => __('Monitoring SAP Systems'),
                'image'       => 'SAP-Logo.svg',
                'category'    => ['sap', 'linux', 'database']
            ],
            'sap-hana-system'        => [
                'type_id'     => 'sap-hana-system',
                'title'       => __('SAP HANA (System)'),
                'description' => __('Monitoring for your SAP Hana System Database'),
                'image'       => 'SAP-Logo.svg',
                'category'    => ['sap', 'linux', 'database']
            ],
            'sap-hana-tenant'        => [
                'type_id'     => 'sap-hana-tenant',
                'title'       => __('SAP HANA (Tenant)'),
                'description' => __('Monitoring for your SAP Hana Tenant Database'),
                'image'       => 'SAP-Logo.svg',
                'category'    => ['sap', 'linux', 'database']
            ],
            'vmware-through-vcenter' => [
                'type_id'     => 'vmware-through-vcenter',
                'title'       => __('VMware (through vCenter)'),
                'description' => __('Check everything regarding VMware. vCenter, virtual machines...'),
                'image'       => 'VMware_logo.svg',
                'category'    => ['virtualization', 'cloud'],
            ],
            'vmware-esx-directly'    => [
                'type_id'     => 'vmware-esx-directly',
                'title'       => __('VMware (esx directly)'),
                'description' => __('Check everything regarding VMware. ESX Hosts, virtual machines...'),
                'image'       => 'VMware_logo.svg',
                'category'    => ['virtualization', 'cloud'],
            ],
            'vmware-horizon'         => [
                'type_id'     => 'vmware-horizon',
                'title'       => __('VMware Horizon'),
                'description' => __('Monitor your VMware Horizon Virtual Desktop Infrastructure'),
                'image'       => 'vmware-horizon.png',
                'category'    => ['virtualization', 'cloud'],
            ],
            'vmware-snapshot'        => [
                'type_id'     => 'vmware-snapshot',
                'title'       => __('VMware Snapshot'),
                'description' => __('Monitor your VMware snapshots to avoid performance issues'),
                'image'       => 'VMware_logo.svg',
                'category'    => ['virtualization', 'cloud'],
            ],
            'db2'                    => [
                'type_id'     => 'db2',
                'title'       => __('DB2 Monitoring'),
                'description' => __('Monitor your DB2 server'),
                'image'       => 'db2.png',
                'category'    => ['database'],
            ],
            'oracle'                 => [
                'type_id'     => 'oracle',
                'title'       => __('Oracle'),
                'description' => __('Track connection time, connected users, session and process usage, ....'),
                'image'       => 'Oracle_logo.svg',
                'category'    => ['linux', 'database'],
            ],
            'ups'                    => [
                'type_id'     => 'ups',
                'title'       => __('Uninterruptible Power Supply'),
                'description' => __('Add your UPS for monitoring by specifying the device details'),
                'image'       => 'ups.jpg',
                'category'    => ['hardware'],
            ],
            'printer'                => [
                'type_id'     => 'printer',
                'title'       => __('Printer'),
                'description' => __('Add your printers for monitoring by specifying the device details'),
                'image'       => 'printer.jpg',
                'category'    => ['hardware'],
            ],
            'mssql'                  => [
                'type_id'     => 'mssql',
                'title'       => __('MSSQL Monitoring'),
                'description' => __('Monitor your Microsoft SQL Server'),
                'image'       => 'mssql-server.png',
                'category'    => ['database'],
            ],
            'hp-server-hardware'     => [
                'type_id'     => 'hp-server-hardware',
                'title'       => __('HP Server Hardware'),
                'description' => __('These checks will allow you to monitor your HP server'),
                'image'       => 'hp.svg',
                'category'    => ['hardware'],
            ],
            'vmware-esx'             => [
                'type_id'     => 'vmware-esx',
                'title'       => __('VMWare ESX'),
                'description' => __('Check everything regarding VMware. ESX Hosts, vCenter, virtual machines...'),
                'image'       => 'VMware_logo.svg',
                'category'    => ['virtualization', 'cloud'],
            ]
        ];

        $possibleWizardsResult = [];
        foreach ($possibleWizards as $typeId => $possibleWizard) {
            //Wizard module is not installed - show disabled placeholder wizard
            if (!isset($availableWizards[$typeId])) {
                $possibleWizardsResult[$typeId] = $possibleWizard;
            }
        }

        return $possibleWizardsResult;
    }

    /**
     * @param $uuid
     * @param $typeId
     * @return bool
     */
    public function existsByUuidAndTypeId($uuid, $typeId) {
        return $this->exists(['WizardAssignments.uuid' => $uuid, 'WizardAssignments.type_id' => $typeId]);
    }

    /**
     * @param $uuid
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getWizardByUuidForEdit($uuid) {
        $query = $this->find()
            ->where(['WizardAssignments.uuid' => $uuid])
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    $query->enableAutoFields(false)
                        ->select([
                            'Servicetemplates.id'
                        ]);
                    return $query;
                }
            ])
            ->disableHydration()
            ->first();


        $wizardAssignment = $query;
        $wizardAssignment['servicetemplates'] = [
            '_ids' => Hash::extract($query, 'servicetemplates.{n}.id')
        ];
        return $wizardAssignment;
    }

    /**
     * @param $typeId
     * @param $wizards
     * @return array|\ArrayAccess
     */
    public function getWizardByTypeId($typeId, $wizards) {
        return Hash::extract($wizards, '{n}[type_id=' . $typeId . ']');
    }
}
