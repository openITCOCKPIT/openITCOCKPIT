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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace itnovum\openITCOCKPIT\Core\MonitoringEngine;

use App\itnovum\openITCOCKPIT\Supervisor\Supervisorctl;
use App\Lib\ExportTasks;
use App\Lib\PluginExportTasks;
use App\Model\Entity\CalendarHoliday;
use App\Model\Entity\Host;
use App\Model\Entity\Hostdependency;
use App\Model\Entity\Hostgroup;
use App\Model\Entity\Service;
use App\Model\Entity\Servicegroup;
use App\Model\Table\AgentconfigsTable;
use App\Model\Table\CalendarsTable;
use App\Model\Table\CommandsTable;
use App\Model\Table\ConfigurationFilesTable;
use App\Model\Table\ContactgroupsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\DeletedHostsTable;
use App\Model\Table\DeletedServicesTable;
use App\Model\Table\HostdependenciesTable;
use App\Model\Table\HostescalationsTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\MacrosTable;
use App\Model\Table\ProxiesTable;
use App\Model\Table\ServicedependenciesTable;
use App\Model\Table\ServiceescalationsTable;
use App\Model\Table\ServicegroupsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\TimeperiodsTable;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Agent\AgentConfiguration;
use itnovum\openITCOCKPIT\ConfigGenerator\GraphingDocker;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\UUID;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class NagiosConfigGenerator {

    private $conf = [];

    private $_systemsettings = [];

    private $FRESHNESS_THRESHOLD_ADDITION = 0;

    /**
     * Is DistributeModule loaded
     * @var bool
     */
    private $dm = false;

    /**
     * @var \DistributeModule\Model\Entity\Satellite[]
     */
    private $Satellites;

    /**
     * @var KeyValueStore
     */
    private $TimeperiodUuidsCache;

    /**
     * @var KeyValueStore
     */
    private $CommandUuidsCache;

    /**
     * @var KeyValueStore
     */
    private $HosttemplateHostgroupsCache;

    /**
     * NagiosExportTask constructor.
     */
    public function __construct() {
        Configure::load('nagios');
        $this->conf = Configure::read('nagios.export');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $this->_systemsettings = $SystemsettingsTable->findAsArray();
        $this->FRESHNESS_THRESHOLD_ADDITION = (int)$this->_systemsettings['MONITORING']['MONITORING.FRESHNESS_THRESHOLD_ADDITION'];

        //Loading distributed Monitoring support, if plugin is loaded
        $this->dm = Plugin::isLoaded('DistributeModule');

        /** @var TimeperiodsTable $TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');

        $this->TimeperiodUuidsCache = new KeyValueStore();
        $this->TimeperiodUuidsCache->setArray($TimeperiodsTable->getAllTimeperiodsUuidsAsList());

        $this->CommandUuidsCache = new KeyValueStore();
        $this->CommandUuidsCache->setArray($CommandsTable->getAllCommandsUuidsAsList());

        $this->HosttemplateHostgroupsCache = new KeyValueStore();

        if ($this->dm === true) {

            //Loading external Model
            /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');
            $query = $SatellitesTable->find()
                ->all();

            $satellites = $query->toArray();

            $this->Satellites = $satellites;

            //Create default config folder for sat systems
            if (!is_dir($this->conf['satellite_path'])) {
                mkdir($this->conf['satellite_path']);
            }

            //Create rollout folder
            if (!is_dir($this->conf['rollout'])) {
                mkdir($this->conf['rollout']);
            }

            foreach ($this->Satellites as $satellite) {
                if (!is_dir($this->conf['satellite_path'] . $satellite->get('id'))) {
                    mkdir($this->conf['satellite_path'] . $satellite->get('id'));
                }

                if (!is_dir($this->conf['satellite_path'] . $satellite->get('id') . DS . $this->conf['config'])) {
                    mkdir($this->conf['satellite_path'] . $satellite->get('id') . DS . $this->conf['config']);
                }
            }

        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportCommands($uuid = null) {
        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');

        if ($uuid !== null) {
            $commands = [];
            $commands[] = $CommandsTable->getCommandByUuid($uuid);
        } else {
            $commands = $CommandsTable->getAllCommands();
        }

        if (!is_dir($this->conf['path'] . $this->conf['commands'])) {
            mkdir($this->conf['path'] . $this->conf['commands']);
        }

        if ($this->conf['minified'] == true) {
            $file = new File($this->conf['path'] . $this->conf['commands'] . 'commands_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        foreach ($commands as $command) {
            if (!empty($command['Command']['command_line'])) {

                if ($this->conf['minified'] == false) {
                    $file = new File($this->conf['path'] . $this->conf['commands'] . $command['Command']['uuid'] . $this->conf['suffix']);
                    $content = $this->fileHeader();
                    if (!$file->exists()) {
                        $file->create();
                    }
                }


                $content .= $this->addContent('define command{', 0);
                $content .= $this->addContent('command_name', 1, $command['Command']['uuid']);
                $content .= $this->addContent('command_line', 1, $this->removeNewlines($this->escapeLastBackslash($command['Command']['command_line'])));
                $content .= $this->addContent('}', 0);

                if ($this->conf['minified'] == false) {
                    $file->write($content);
                    $file->close();
                }
            }
        }

        if ($this->conf['minified'] == true) {
            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportContacts($uuid = null) {
        /** @var ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        $contacts = $ContactsTable->getContactsForExport();


        if (!is_dir($this->conf['path'] . $this->conf['contacts'])) {
            mkdir($this->conf['path'] . $this->conf['contacts']);
        }


        if ($this->conf['minified']) {
            $file = new File($this->conf['path'] . $this->conf['contacts'] . 'contacts_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        foreach ($contacts as $contact) {
            /** @var \App\Model\Entity\Contact $contact */
            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['contacts'] . $contact['Contact']['uuid'] . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $content .= $this->addContent('define contact{', 0);
            $content .= $this->addContent('contact_name', 1, $contact->get('uuid'));
            $content .= $this->addContent('alias', 1, $this->removeNewlines($this->escapeLastBackslash($contact->get('description'))));
            $content .= $this->addContent('host_notifications_enabled', 1, $contact->get('host_notifications_enabled'));
            $content .= $this->addContent('service_notifications_enabled', 1, $contact->get('service_notifications_enabled'));
            $content .= $this->addContent('host_notification_period', 1, $contact->get('host_timeperiod')->get('uuid'));
            $content .= $this->addContent('service_notification_period', 1, $contact->get('service_timeperiod')->get('uuid'));
            $content .= $this->addContent('host_notification_commands', 1, $contact->getHostCommandsForCfg());
            $content .= $this->addContent('service_notification_commands', 1, $contact->getServiceCommandsForCfg());
            $content .= $this->addContent('host_notification_options', 1, $contact->getHostNotificationOptionsForCfg());
            $content .= $this->addContent('service_notification_options', 1, $contact->getServiceNotificationOptionsForCfg());
            if (!empty($contact->get('email'))) {
                $content .= $this->addContent('email', 1, $this->removeNewlines($this->escapeLastBackslash($contact->get('email'))));
            }
            if (!empty($contact->get('phone'))) {
                $content .= $this->addContent('pager', 1, $this->removeNewlines($this->escapeLastBackslash($contact->get('phone'))));
            }

            if ($contact->hasCustomvariables()) {
                $content .= PHP_EOL;
                $content .= $this->addContent(';Custom  variables:', 1);
                foreach ($contact->getCustomvariablesForCfg() as $varName => $varValue) {
                    $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
                }
            }

            if ($contact->get('user_id') > 0) {
                $content .= $this->addContent(';OITC user association:', 1);
                $content .= $this->addContent('_OITCUSERID', 1, $contact->get('user_id'));
            }

            $content .= $this->addContent('}', 0);
            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }
        }

        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportContactgroups($uuid = null) {
        /** @var ContactgroupsTable $ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        $contactgroups = $ContactgroupsTable->getContactgroupsForExport($uuid);


        if (!is_dir($this->conf['path'] . $this->conf['contactgroups'])) {
            mkdir($this->conf['path'] . $this->conf['contactgroups']);
        }

        if ($this->conf['minified']) {
            $file = new File($this->conf['path'] . $this->conf['contactgroups'] . 'contactgroups_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }


        foreach ($contactgroups as $contactgroup) {
            /** @var \App\Model\Entity\Contactgroup $contactgroup */

            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['contactgroups'] . $contactgroup->uuid . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $content .= $this->addContent('define contactgroup{', 0);
            $content .= $this->addContent('contactgroup_name', 1, $contactgroup->uuid);
            $content .= $this->addContent('alias', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    $contactgroup->getDescriptionForCfg()
                )));
            if ($contactgroup->hasContacts()) {
                $content .= $this->addContent('members', 1, $contactgroup->getContactsForCfg());
            }
            $content .= $this->addContent('}', 0);
            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }

        }
        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportHosttemplates($uuid = null) {
        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        $hosttemplates = $HosttemplatesTable->getHosttemplatesForExport($uuid);

        if (!is_dir($this->conf['path'] . $this->conf['hosttemplates'])) {
            mkdir($this->conf['path'] . $this->conf['hosttemplates']);
        }

        if ($this->conf['minified']) {
            $file = new File($this->conf['path'] . $this->conf['hosttemplates'] . 'hosttemplates_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        foreach ($hosttemplates as $hosttemplate) {
            /** @var \App\Model\Entity\Hosttemplate $hosttemplate */
            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['hosttemplates'] . $hosttemplate->get('uuid') . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $content .= $this->addContent('define host{', 0);
            $content .= $this->addContent('register', 1, 0);
            $content .= $this->addContent('use', 1, '8147201e91c4dcf7c016ba2ddeac3fd7e72edacc');
            $content .= $this->addContent('host_name', 1, $hosttemplate->get('uuid'));
            $content .= $this->addContent('name', 1, $hosttemplate->get('uuid'));
            $content .= $this->addContent('display_name', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    $hosttemplate->get('name')
                )));
            $content .= $this->addContent('alias', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    $hosttemplate->get('description')
                )));

            $content .= PHP_EOL;
            $content .= $this->addContent(';Check settings:', 1);

            if ($hosttemplate->hasHosttemplatecommandargumentvalues()) {
                $content .= $this->addContent('check_command', 1, $hosttemplate->get('check_command')->get('uuid') . '!' . $hosttemplate->getHosttemplatecommandargumentvaluesForCfg());
            } else {
                $content .= $this->addContent('check_command', 1, $hosttemplate->get('check_command')->get('uuid'));
            }


            $content .= $this->addContent('initial_state', 1, $this->_systemsettings['MONITORING']['MONITORING.HOST.INITSTATE']);
            $content .= $this->addContent('check_period', 1, $hosttemplate->get('check_period')->get('uuid'));
            $content .= $this->addContent('check_interval', 1, $hosttemplate->get('check_interval'));
            $content .= $this->addContent('retry_interval', 1, $hosttemplate->get('retry_interval'));
            $content .= $this->addContent('max_check_attempts', 1, $hosttemplate->get('max_check_attempts'));
            $content .= $this->addContent('active_checks_enabled', 1, $hosttemplate->get('active_checks_enabled'));
            $content .= $this->addContent('passive_checks_enabled', 1, 1);

            if ($hosttemplate->get('freshness_checks_enabled') !== null) {
                $content .= $this->addContent('check_freshness', 1, $hosttemplate->get('freshness_checks_enabled'));

                if ((int)$hosttemplate->get('freshness_threshold') > 0) {
                    $content .= $this->addContent('freshness_threshold', 1, (int)$hosttemplate->get('freshness_threshold') + $this->FRESHNESS_THRESHOLD_ADDITION);
                }
            }

            $content .= PHP_EOL;
            $content .= $this->addContent(';Notification settings:', 1);
            $content .= $this->addContent('notifications_enabled', 1, $hosttemplate->get('notifications_enabled'));

            if ($hosttemplate->hasContacts()) {
                $content .= $this->addContent('contacts', 1, $hosttemplate->getContactsForCfg());
            }


            if ($hosttemplate->hasContactgroups()) {
                $content .= $this->addContent('contact_groups', 1, $hosttemplate->getContactgroupsForCfg());
            }

            $content .= $this->addContent('notification_interval', 1, $hosttemplate->get('notification_interval'));
            $content .= $this->addContent('notification_period', 1, $hosttemplate->get('notify_period')->get('uuid'));

            $content .= $this->addContent('notification_options', 1, $hosttemplate->getHostNotificationOptionsForCfg());

            $content .= PHP_EOL;
            $content .= $this->addContent(';Flap detection settings:', 1);
            $content .= $this->addContent('flap_detection_enabled', 1, $hosttemplate->get('flap_detection_enabled'));
            if ($hosttemplate->get('flap_detection_enabled') === 1) {
                if ($hosttemplate->getHostFlapDetectionOptionsForCfg()) {
                    $content .= $this->addContent('flap_detection_options', 1, $hosttemplate->getHostFlapDetectionOptionsForCfg());
                }
            }

            $content .= PHP_EOL;
            $content .= $this->addContent(';Everything else:', 1);
            $content .= $this->addContent('process_perf_data', 1, $hosttemplate->get('process_performance_data'));

            if (!empty($hosttemplate->get('notes')))
                $content .= $this->addContent('notes', 1, $this->removeNewlines($this->escapeLastBackslash($hosttemplate->get('notes'))));


            if ($hosttemplate->hasCustomvariables()) {
                $content .= PHP_EOL;
                $content .= $this->addContent(';Custom  variables:', 1);
                foreach ($hosttemplate->getCustomvariablesForCfg() as $varName => $varValue) {
                    $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
                }
            }
            $content .= $this->addContent('}', 0);

            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }

        }

        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportHosts($uuid = null) {
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        if ($uuid !== null) {
            $hosts = $HostsTable->getHostsForExport(null, null, $uuid);
        } else {
            //Multiple queries are faster than one big query
            $hostCount = $HostsTable->getHostsCountForExport();
            $chunk = 200;
            $queryCount = ceil($hostCount / $chunk);
            $hosts = [];
            for ($i = 0; $i < $queryCount; $i++) {
                $_hosts = $HostsTable->getHostsForExport($chunk, ($chunk * $i));
                foreach ($_hosts as $_host) {
                    $hosts[] = $_host;
                }
                unset($_hosts);
            }
        }

        if (!is_dir($this->conf['path'] . $this->conf['hosts'])) {
            mkdir($this->conf['path'] . $this->conf['hosts']);
        }

        if ($this->conf['minified']) {
            $fileName = $this->conf['path'] . $this->conf['hosts'] . 'hosts_minified' . $this->conf['suffix'];
            $file = new File($fileName);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        $HosttemplatesCache = new KeyValueStore();

        foreach ($hosts as $host) {
            /** @var $host \App\Model\Entity\Host */

            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['hosts'] . $host->get('uuid') . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            if (!$HosttemplatesCache->has($host->get('hosttemplate_id'))) {
                $hosttemplate = $HosttemplatesTable->get($host->get('hosttemplate_id'), contain: [
                    'Hosttemplatecommandargumentvalues' => [
                        'Commandarguments'
                    ]
                ]);
                $HosttemplatesCache->set($host->get('hosttemplate_id'), $hosttemplate);
            }

            /** @var \App\Model\Entity\Hosttemplate $hosttemplate */
            $hosttemplate = $HosttemplatesCache->get($host->get('hosttemplate_id'));


            $content .= $this->addContent('define host{', 0);
            $content .= $this->addContent('use', 1, $hosttemplate->get('uuid'));
            $content .= $this->addContent('host_name', 1, $host->get('uuid'));
            $content .= $this->addContent('display_name', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('name'))));
            $content .= $this->addContent('address', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('address'))));


            if (!empty($host->get('description')))
                $content .= $this->addContent('alias', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('description'))));

            if ($host->hasParentHostsForExport()) {
                $content .= $this->addContent('parents', 1, $host->getParentHostsForCfg());
            }

            $content .= PHP_EOL;
            $content .= $this->addContent(';Check settings:', 1);
            $commandarguments = null;
            if ($host->isSatelliteHost() === false) {
                if (!empty($host->get('hostcommandargumentvalues'))) {
                    if ($host->get('command_id') === null) {
                        //Host has own command arguments but uses the same command as the host template
                        $commandId = $hosttemplate->get('command_id');
                    } else {
                        //Host has own command arguments AND own check command
                        $commandId = $host->get('command_id');
                    }

                    $commandUuid = $this->CommandUuidsCache->get($commandId);

                    $commandarguments = $host->getCommandargumentValuesForCfg($hosttemplate);
                    $content .= $this->addContent('check_command', 1, $this->removeNewlines(
                        $this->escapeLastBackslash(
                            sprintf(
                                '%s!%s; %s',
                                $commandUuid,
                                implode('!', Hash::extract($commandarguments, '{n}.value')),
                                implode('!', Hash::extract($commandarguments, '{n}.human_name'))
                            )
                        )));
                } else {
                    //May be check command without arguments
                    if ($host->get('command_id') !== null) {
                        //Host has own check command but this command has no arguments at all
                        $commandId = $host->get('command_id');
                        $commandUuid = $this->CommandUuidsCache->get($commandId);
                        $content .= $this->addContent('check_command', 1, $commandUuid);
                    }
                }
            }

            if ($host->isSatelliteHost() === true) {
                //Host check fresh command
                $content .= $this->addContent('check_command', 1, '2106cf0bf26a82af262c4078e6d9f94eded84d2a');
            }

            if ($host->get('check_period_id')) {
                $timeperiodUuid = $this->TimeperiodUuidsCache->get($host->get('check_period_id'));
                $content .= $this->addContent('check_period', 1, $timeperiodUuid);
            }

            if ($host->get('check_interval') !== null && $host->get('check_interval') !== '') {
                $content .= $this->addContent('check_interval', 1, $host->get('check_interval'));
            }

            if ($host->get('retry_interval') !== null && $host->get('retry_interval') !== '') {
                $content .= $this->addContent('retry_interval', 1, $host->get('retry_interval'));
            }

            if ($host->get('max_check_attempts') !== null && $host->get('max_check_attempts') !== '') {
                $content .= $this->addContent('max_check_attempts', 1, $host->get('max_check_attempts'));
            }

            if ($host->isSatelliteHost() === true) {
                //Host is on a Satellite - so it's passive on the master instance
                $content .= $this->addContent('active_checks_enabled', 1, 0);
            } else {
                if ($host->get('active_checks_enabled') !== null && $host->get('active_checks_enabled') !== '') {
                    $content .= $this->addContent('active_checks_enabled', 1, $host->get('active_checks_enabled'));
                }
            }


            $checkInterval = $host->get('check_interval');
            if ($checkInterval === null || $checkInterval === '') {
                $checkInterval = $hosttemplate->get('check_interval');
            }

            if ($checkInterval === 0 || $checkInterval === null) {
                //Default check interval - just in case...
                $checkInterval = 300;
            }

            /* Freshness checks starts */
            $activeChecksEnabled = $host->get('active_checks_enabled');
            if ($activeChecksEnabled === null) {
                $activeChecksEnabled = $hosttemplate->get('active_checks_enabled');
            }

            $freshnessChecksEnabled = $host->get('freshness_checks_enabled');
            if ($freshnessChecksEnabled === null) {
                $freshnessChecksEnabled = $hosttemplate->get('freshness_checks_enabled');
            }
            $freshnessThreshold = $host->get('freshness_threshold');
            if ($freshnessThreshold === null) {
                $freshnessThreshold = $hosttemplate->get('freshness_threshold');
            }

            if ($host->isSatelliteHost() === true) {
                // Host is checked by a satellite system
                // Do we need to add the freshness check on the master instance?
                if ($activeChecksEnabled) {
                    $content .= $this->addContent('check_freshness', 1, 1);
                    if ($freshnessChecksEnabled > 0 && $freshnessThreshold > 0) {
                        // Active check with configured freshness + freshness_threshold_addition on the master for "service is no longer current"
                        $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold + $checkInterval + $this->FRESHNESS_THRESHOLD_ADDITION);
                    } else {
                        // Only active check, add freshness check on the master for "service is no longer current"
                        $content .= $this->addContent('freshness_threshold', 1, $checkInterval + $this->FRESHNESS_THRESHOLD_ADDITION);
                    }
                } else {
                    if ($freshnessChecksEnabled > 0 && $freshnessThreshold > 0) {
                        // Passive host with enabled freshness checking
                        $content .= $this->addContent('check_freshness', 1, 1);
                        $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold + $checkInterval + $this->FRESHNESS_THRESHOLD_ADDITION);
                    }
                }
            } else {
                // Host is in the master instance
                // Passive host on the master system
                if ($host->get('freshness_checks_enabled')) {
                    $content .= $this->addContent('check_freshness', 1, $host->get('freshness_checks_enabled'));
                }
                if ($freshnessChecksEnabled > 0 && $freshnessThreshold > 0) {
                    $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold);
                }
            }
            /* Freshness checks ends */

            if ($host->get('passive_checks_enabled') !== null && $host->get('passive_checks_enabled') !== '')
                $content .= $this->addContent('passive_checks_enabled', 1, $host->get('passive_checks_enabled'));


            $content .= PHP_EOL;
            $content .= $this->addContent(';Notification settings:', 1);

            if ($host->get('notifications_enabled') !== null && $host->get('notifications_enabled') !== '')
                $content .= $this->addContent('notifications_enabled', 1, $host->get('notifications_enabled'));

            if (!empty($host->get('contacts'))) {
                //ITC-2710 Inheritance of contacts and contact groups
                $content .= $this->addContent('contacts', 1, $host->getContactsforCfg());
                if (empty($host->get('contactgroups'))) {
                    $content .= $this->addContent('contact_groups', 1, 'null');
                }
            }

            if (!empty($host->get('contactgroups'))) {
                //ITC-2710 Inheritance of contacts and contact groups
                if (empty($host->get('contacts'))) {
                    $content .= $this->addContent('contacts', 1, 'null');
                }
                $content .= $this->addContent('contact_groups', 1, $host->getContactgroupsforCfg());
            }

            if ($host->get('notification_interval') !== null && $host->get('notification_interval') !== '')
                $content .= $this->addContent('notification_interval', 1, $host->get('notification_interval'));

            if ($host->get('notify_period_id')) {
                $timeperiodUuid = $this->TimeperiodUuidsCache->get($host->get('notify_period_id'));
                $content .= $this->addContent('notification_period', 1, $timeperiodUuid);
            }

            if (strlen($host->getNotificationOptionsForCfg($hosttemplate)) > 0) {
                $content .= $this->addContent('notification_options', 1, $host->getNotificationOptionsForCfg($hosttemplate));
            }

            $content .= PHP_EOL;
            $content .= $this->addContent(';Flap detection settings:', 1);

            if ($host->get('flap_detection_enabled') === 1 || $host->get('flap_detection_enabled') === 0)
                $content .= $this->addContent('flap_detection_enabled', 1, $host->get('flap_detection_enabled'));

            if ($host->get('flap_detection_enabled') === 1 && strlen($host->getFlapdetectionOptionsForCfg($hosttemplate)) > 0) {
                $content .= $this->addContent('flap_detection_options', 1, $host->getFlapdetectionOptionsForCfg($hosttemplate));
            }

            $content .= PHP_EOL;
            $content .= $this->addContent(';Everything else:', 1);

            if ($host->get('process_performance_data') === 1 || $host->get('process_performance_data') === 0)
                $content .= $this->addContent('process_perf_data', 1, $host->get('process_performance_data'));

            if ($host->get('notes') && strlen($host->get('notes')) > 0) {
                $content .= $this->addContent('notes', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('notes'))));
            }

            if (!$this->HosttemplateHostgroupsCache->has($host->get('hosttemplate_id'))) {
                //Load hostgroups of the host template
                $hostgroups = [];
                $result = $HosttemplatesTable->getHostgroupsByHosttemplateId($host->get('hosttemplate_id'));
                if (!empty($result['hostgroups'])) {
                    $hostgroups = \Cake\Utility\Hash::extract($result['hostgroups'], '{n}.uuid');
                }

                $this->HosttemplateHostgroupsCache->set($host->get('hosttemplate_id'), implode(',', $hostgroups));
            }
            $hosttemplateHostgroupsForCfg = $this->HosttemplateHostgroupsCache->get($host->get('hosttemplate_id'));

            if (!empty($host->get('hostgroups'))) {
                //Use host groups of the host
                $content .= PHP_EOL;
                $content .= $this->addContent(';Hostgroup memberships:', 1);
                $content .= $this->addContent('hostgroups', 1, $host->getHostgroupsForCfg());
            } else if (empty($host->get('hostgroups')) && strlen($hosttemplateHostgroupsForCfg) > 0) {
                //Use host groups of host template configuration
                $content .= PHP_EOL;
                $content .= $this->addContent(';Hostgroup memberships:', 1);
                $content .= $this->addContent('hostgroups', 1, $hosttemplateHostgroupsForCfg);
            }

            if ($host->hasCustomvariables()) {
                $content .= PHP_EOL;
                $content .= $this->addContent(';Custom  variables:', 1);
                foreach ($host->getCustomvariablesForCfg() as $varName => $varValue) {
                    $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
                }
            }

            if (IS_CONTAINER === false && ($host->host_type == EVK_HOST || $hosttemplate->get('uuid') === 'd086e12a-e0c5-42b9-a2f4-1875c65ad09b')) {
                // ITC-3177 EVC Hosts (and all their services) needs to be checked by the local running Naemon
                // due to the EVC Checkplugin requires Database access.
                // This is only relevant for classical installations
                // In a Docker / Container based installation **all** checks are getting executed by Mod_Gearman
                // and a complex Docker-Compose file makes sure that the database access does work.
                //
                // We ensure that EVC hosts will bypass Mod-Gearman by setting the _WORKER variable to local
                //
                // Hosttemplate: The openITCOCKPIT Agent is Pulled through the host template.
                $content .= $this->addContent('_WORKER', 1, 'local');
            }

            $content .= $this->addContent('}', 0);

            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }


            if ($this->dm === true && $host->isSatelliteHost()) {
                //Generate config file for sat nagios
                $this->exportSatHost($host, $HosttemplatesTable, $HosttemplatesCache);
            }
        }
        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }

        if ($this->dm === true) {
            $this->exportSatHostgroups();
        }

        $this->deleteHostPerfdata();
    }

    /**
     * @param \App\Model\Entity\Host $host
     * @param HosttemplatesTable $HosttemplatesTable
     * @param KeyValueStore $HosttemplatesCache
     */
    private function exportSatHost(\App\Model\Entity\Host $host, HosttemplatesTable $HosttemplatesTable, KeyValueStore $HosttemplatesCache) {
        $satelliteId = $host->get('satellite_id');

        /** @var \App\Model\Entity\Hosttemplate $hosttemplate */
        $hosttemplate = $HosttemplatesCache->get($host->get('hosttemplate_id'));

        if (!is_dir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hosts'])) {
            mkdir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hosts']);
        }

        if (!$this->conf['minified']) {
            $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hosts'] . $host->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();
        } else {
            $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hosts'] . 'hosts_minified' . $this->conf['suffix']);
            $content = '';
        }

        if (!$file->exists()) {
            $file->create();
        }

        $content .= $this->addContent('define host{', 0);
        $content .= $this->addContent('use', 1, $hosttemplate->get('uuid'));
        $content .= $this->addContent('host_name', 1, $host->get('uuid'));
        $content .= $this->addContent('display_name', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('name'))));
        $content .= $this->addContent('address', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('address'))));


        if ($host->get('description'))
            $content .= $this->addContent('alias', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('description'))));

        $parenthosts = $host->getParentHostsForSatCfgAsArray();
        if (!empty($parenthosts)) {
            $content .= $this->addContent('parents', 1, implode(',', $parenthosts));
        }

        $content .= PHP_EOL;
        $content .= $this->addContent(';Check settings:', 1);

        if (!empty($host->get('hostcommandargumentvalues'))) {
            if ($host->get('command_id') === null) {
                //Host has own command arguments but uses the same command as the host template
                $commandId = $hosttemplate->get('command_id');
            } else {
                //Host has own command arguments AND own check command
                $commandId = $host->get('command_id');
            }

            $commandUuid = $this->CommandUuidsCache->get($commandId);
            $commandarguments = $host->getCommandargumentValuesForCfg($hosttemplate);
            $content .= $this->addContent('check_command', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    sprintf(
                        '%s!%s; %s',
                        $commandUuid,
                        implode('!', Hash::extract($commandarguments, '{n}.value')),
                        implode('!', Hash::extract($commandarguments, '{n}.human_name'))
                    )
                )));
        } else {
            //May be check command without arguments
            if ($host->get('command_id') !== null) {
                //Host has own check command but this command has no arguments at all
                $commandId = $host->get('command_id');
                $commandUuid = $this->CommandUuidsCache->get($commandId);
                $content .= $this->addContent('check_command', 1, $commandUuid);
            }
        }

        if ($host->get('check_period_id')) {
            $timeperiodUuid = $this->TimeperiodUuidsCache->get($host->get('check_period_id'));
            $content .= $this->addContent('check_period', 1, $timeperiodUuid);
        }

        if ($host->get('check_interval') !== null && $host->get('check_interval') !== '') {
            $content .= $this->addContent('check_interval', 1, $host->get('check_interval'));
        }

        if ($host->get('retry_interval') !== null && $host->get('retry_interval') !== '') {
            $content .= $this->addContent('retry_interval', 1, $host->get('retry_interval'));
        }

        if ($host->get('max_check_attempts') !== null && $host->get('max_check_attempts') !== '') {
            $content .= $this->addContent('max_check_attempts', 1, $host->get('max_check_attempts'));
        }

        if ($host->get('active_checks_enabled') !== null && $host->get('active_checks_enabled') !== '') {
            $content .= $this->addContent('active_checks_enabled', 1, $host->get('active_checks_enabled'));
        }

        /* Freshness checks starts */
        $freshnessChecksEnabled = $host->get('freshness_checks_enabled');
        if ($freshnessChecksEnabled === null) {
            $freshnessChecksEnabled = $hosttemplate->get('freshness_checks_enabled');
        }
        $freshnessThreshold = $host->get('freshness_threshold');
        if ($freshnessThreshold === null) {
            $freshnessThreshold = $hosttemplate->get('freshness_threshold');
        }

        if ($freshnessChecksEnabled > 0) {
            $content .= $this->addContent('check_freshness', 1, 1);
            if ($freshnessThreshold > 0) {
                $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold + $this->FRESHNESS_THRESHOLD_ADDITION);
            }
        }
        /* Freshness checks ends */

        if ($host->get('passive_checks_enabled') !== null && $host->get('passive_checks_enabled') !== '')
            $content .= $this->addContent('passive_checks_enabled', 1, $host->get('passive_checks_enabled'));

        $content .= PHP_EOL;
        $content .= $this->addContent(';Notification settings:', 1);

        if ($host->get('notifications_enabled') !== null && $host->get('notifications_enabled') !== '')
            $content .= $this->addContent('notifications_enabled', 1, $host->get('notifications_enabled'));

        if (!empty($host->get('contacts'))) {
            //ITC-2710 Inheritance of contacts and contact groups
            $content .= $this->addContent('contacts', 1, $host->getContactsforCfg());
            if (empty($host->get('contactgroups'))) {
                $content .= $this->addContent('contact_groups', 1, 'null');
            }
        }

        if (!empty($host->get('contactgroups'))) {
            //ITC-2710 Inheritance of contacts and contact groups
            if (empty($host->get('contacts'))) {
                $content .= $this->addContent('contacts', 1, 'null');
            }
            $content .= $this->addContent('contact_groups', 1, $host->getContactgroupsforCfg());
        }

        if ($host->get('notification_interval') !== null && $host->get('notification_interval') !== '')
            $content .= $this->addContent('notification_interval', 1, $host->get('notification_interval'));

        if ($host->get('notify_period_id')) {
            $timeperiodUuid = $this->TimeperiodUuidsCache->get($host->get('notify_period_id'));
            $content .= $this->addContent('notification_period', 1, $timeperiodUuid);
        }

        if (strlen($host->getNotificationOptionsForCfg($hosttemplate)) > 0) {
            $content .= $this->addContent('notification_options', 1, $host->getNotificationOptionsForCfg($hosttemplate));
        }

        $content .= PHP_EOL;
        $content .= $this->addContent(';Flap detection settings:', 1);

        if ($host->get('flap_detection_enabled') === 1 || $host->get('flap_detection_enabled') === 0)
            $content .= $this->addContent('flap_detection_enabled', 1, $host->get('flap_detection_enabled'));

        if ($host->get('flap_detection_enabled') === 1 && strlen($host->getFlapdetectionOptionsForCfg($hosttemplate)) > 0) {
            $content .= $this->addContent('flap_detection_options', 1, $host->getFlapdetectionOptionsForCfg($hosttemplate));
        }

        $content .= PHP_EOL;
        $content .= $this->addContent(';Everything else:', 1);

        if ($host->get('process_performance_data') === 1 || $host->get('process_performance_data') === 0)
            $content .= $this->addContent('process_perf_data', 1, $host->get('process_performance_data'));

        if ($host->get('notes') && strlen($host->get('notes')) > 0) {
            $content .= $this->addContent('notes', 1, $this->removeNewlines($this->escapeLastBackslash($host->get('notes'))));
        }

        if (!$this->HosttemplateHostgroupsCache->has($host->get('hosttemplate_id'))) {
            //Load hostgroups of the host template
            $hostgroups = [];
            $result = $HosttemplatesTable->getHostgroupsByHosttemplateId($host->get('hosttemplate_id'));
            if (!empty($result['hostgroups'])) {
                $hostgroups = Hash::extract($result['hostgroups'], '{n}.uuid');
            }

            $this->HosttemplateHostgroupsCache->set($host->get('hosttemplate_id'), implode(',', $hostgroups));
        }
        $hosttemplateHostgroupsForCfg = $this->HosttemplateHostgroupsCache->get($host->get('hosttemplate_id'));

        if (!empty($host->get('hostgroups'))) {
            //Use host groups of the host
            $content .= PHP_EOL;
            $content .= $this->addContent(';Hostgroup memberships:', 1);
            $content .= $this->addContent('hostgroups', 1, $host->getHostgroupsForCfg());
        } else if (empty($host->get('hostgroups')) && strlen($hosttemplateHostgroupsForCfg) > 0) {
            //Use host groups of host template configuration
            $content .= PHP_EOL;
            $content .= $this->addContent(';Hostgroup memberships:', 1);
            $content .= $this->addContent('hostgroups', 1, $hosttemplateHostgroupsForCfg);
        }

        if ($host->hasCustomvariables()) {
            $content .= PHP_EOL;
            $content .= $this->addContent(';Custom  variables:', 1);
            foreach ($host->getCustomvariablesForCfg() as $varName => $varValue) {
                $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
            }
        }
        if (IS_CONTAINER === false && $hosttemplate->get('uuid') === 'd086e12a-e0c5-42b9-a2f4-1875c65ad09b') {
            // ITC-3177 On teh Satellite we do not need to care about EVC
            // Hosttemplate: The openITCOCKPIT Agent is Pulled through the host template.
            $content .= $this->addContent('_WORKER', 1, 'local');
        }

        $content .= $this->addContent('}', 0);

        if (!$this->conf['minified']) {
            $file->write($content);
        } else {
            $file->append($content);
        }
        $file->close();
    }

    /**
     * @param null|string $uuid
     */
    public function exportServicetemplates($uuid = null) {
        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        $servicetemplates = $ServicetemplatesTable->getServicetemplatesForExport($uuid);

        if (!is_dir($this->conf['path'] . $this->conf['servicetemplates'])) {
            mkdir($this->conf['path'] . $this->conf['servicetemplates']);
        }

        if ($this->conf['minified']) {
            $file = new File($this->conf['path'] . $this->conf['servicetemplates'] . 'servicetemplates_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        foreach ($servicetemplates as $servicetemplate) {
            /** @var \App\Model\Entity\Servicetemplate $servicetemplate */

            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['servicetemplates'] . $servicetemplate->get('uuid') . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $content .= $this->addContent('define service{', 0);
            $content .= $this->addContent('register', 1, 0);
            $content .= $this->addContent('use', 1, '689bfdd01af8a21c4a4706c5117849c2fc2c3f38');
            $content .= $this->addContent('name', 1, $servicetemplate->get('uuid'));
            $content .= $this->addContent('display_name', 1, $this->removeNewlines($this->escapeLastBackslash(
                $servicetemplate->get('name')
            )));
            $content .= $this->addContent('service_description', 1, $servicetemplate->get('uuid'));

            $content .= PHP_EOL;
            $content .= $this->addContent(';Check settings:', 1);

            if ($servicetemplate->hasServicetemplatecommandargumentvalues()) {
                $content .= $this->addContent('check_command', 1, $this->removeNewlines($this->escapeLastBackslash(
                    $servicetemplate->get('check_command')->get('uuid') . '!' . $servicetemplate->getServicetemplatecommandargumentvaluesForCfg()
                )));
            } else {
                $content .= $this->addContent('check_command', 1, $servicetemplate->get('check_command')->get('uuid'));
            }


            $content .= $this->addContent('initial_state', 1, $this->_systemsettings['MONITORING']['MONITORING.SERVICE.INITSTATE']);
            $content .= $this->addContent('check_period', 1, $servicetemplate->get('check_period')->get('uuid'));
            $content .= $this->addContent('check_interval', 1, $servicetemplate->get('check_interval'));
            $content .= $this->addContent('retry_interval', 1, $servicetemplate->get('retry_interval'));
            $content .= $this->addContent('max_check_attempts', 1, $servicetemplate->get('max_check_attempts'));
            $content .= $this->addContent('active_checks_enabled', 1, $servicetemplate->get('active_checks_enabled'));
            $content .= $this->addContent('passive_checks_enabled', 1, 1);

            if ($servicetemplate->get('freshness_checks_enabled') > 0) {
                $content .= $this->addContent('check_freshness', 1, 1);

                if ((int)$servicetemplate->get('freshness_threshold') > 0) {
                    $content .= $this->addContent('freshness_threshold', 1, (int)$servicetemplate->get('freshness_threshold') + $this->FRESHNESS_THRESHOLD_ADDITION);
                }
            }


            $content .= PHP_EOL;
            $content .= $this->addContent(';Notification settings:', 1);
            $content .= $this->addContent('notifications_enabled', 1, $servicetemplate->get('notifications_enabled'));

            if ($servicetemplate->hasContacts()) {
                $content .= $this->addContent('contacts', 1, $servicetemplate->getContactsForCfg());
            }

            if ($servicetemplate->hasContactgroups()) {
                $content .= $this->addContent('contact_groups', 1, $servicetemplate->getContactgroupsForCfg());
            }

            $content .= $this->addContent('notification_interval', 1, $servicetemplate->get('notification_interval'));
            $content .= $this->addContent('notification_period', 1, $servicetemplate->get('notify_period')->get('uuid'));

            $content .= $this->addContent('notification_options', 1, $servicetemplate->getServiceNotificationOptionsForCfg());

            $content .= PHP_EOL;
            $content .= $this->addContent(';Flap detection settings:', 1);
            $content .= $this->addContent('flap_detection_enabled', 1, $servicetemplate->get('flap_detection_enabled'));
            if ($servicetemplate->get('flap_detection_enabled') === 1) {
                if ($servicetemplate->getServiceFlapDetectionOptionsForCfg()) {
                    $content .= $this->addContent('flap_detection_options', 1, $servicetemplate->getServiceFlapDetectionOptionsForCfg());
                }
            }

            $content .= PHP_EOL;
            $content .= $this->addContent(';Everything else:', 1);
            $content .= $this->addContent('process_perf_data', 1, $servicetemplate->get('process_performance_data'));

            $content .= $this->addContent('is_volatile', 1, (int)$servicetemplate->get('is_volatile'));

            if (!empty($servicetemplate->get('notes')))
                $content .= $this->addContent('notes', 1, $this->removeNewlines($this->escapeLastBackslash($servicetemplate->get('notes'))));


            if ($servicetemplate->hasEventhandler()) {
                $content .= PHP_EOL;
                $content .= $this->addContent(';Event handler:', 1);

                $commandUuid = $this->CommandUuidsCache->get($servicetemplate->get('eventhandler_command_id'));

                if ($servicetemplate->hasServicetemplateeventcommandargumentvalues()) {
                    $content .= $this->addContent('event_handler', 1, $this->removeNewlines($this->escapeLastBackslash(
                        $commandUuid . '!' . $servicetemplate->getServicetemplateeventcommandargumentvaluesForCfg()
                    )));
                } else {
                    $content .= $this->addContent('event_handler', 1, $commandUuid);
                }
            }


            if ($servicetemplate->hasCustomvariables()) {
                $content .= PHP_EOL;
                $content .= $this->addContent(';Custom  variables:', 1);
                foreach ($servicetemplate->getCustomvariablesForCfg() as $varName => $varValue) {
                    $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
                }
            }
            $content .= $this->addContent('}', 0);

            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }
        }

        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }

    }


    public function exportServices() {
        if (!is_dir($this->conf['path'] . $this->conf['services'])) {
            mkdir($this->conf['path'] . $this->conf['services']);
        }

        if ($this->conf['minified']) {
            $fileName = $this->conf['path'] . $this->conf['services'] . 'services_minified' . $this->conf['suffix'];
            $file = new File($fileName);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        $ServicetemplatesCache = new KeyValueStore();

        $hosts = $HostsTable->getHostsForServiceExport();
        foreach ($hosts as $host) {
            /** @var Host $host */

            $services = $ServicesTable->getServicesForExportByHostId($host->get('id'));
            foreach ($services as $service) {
                /** @var Service $service */

                if (!$this->conf['minified']) {
                    $file = new File($this->conf['path'] . $this->conf['services'] . $service->get('uuid') . $this->conf['suffix']);
                    $content = $this->fileHeader();
                    if (!$file->exists()) {
                        $file->create();
                    }
                }

                if (!$ServicetemplatesCache->has($service->get('servicetemplate_id'))) {
                    $servicetemplate = $ServicetemplatesTable->getServicetemplateForServiceExport($service->get('servicetemplate_id'));
                    $ServicetemplatesCache->set($service->get('servicetemplate_id'), $servicetemplate);
                }
                /** @var \App\Model\Entity\Servicetemplate $servicetemplate */
                $servicetemplate = $ServicetemplatesCache->get($service->get('servicetemplate_id'));


                $content .= $this->addContent('define service{', 0);
                $content .= $this->addContent('use', 1, $servicetemplate->get('uuid'));
                $content .= $this->addContent('host_name', 1, $host->get('uuid'));

                $content .= $this->addContent('name', 1, $service->get('uuid'));

                if ($service->get('name') !== null && $service->get('name') !== '') {
                    $content .= $this->addContent('display_name', 1, $this->removeNewlines(
                        $this->escapeLastBackslash(
                            $service->get('name')
                        )));
                } else {
                    $content .= $this->addContent('display_name', 1, $this->removeNewlines(
                        $this->escapeLastBackslash(
                            $servicetemplate->get('name')
                        )));
                }

                $content .= $this->addContent('service_description', 1, $service->get('uuid'));

                $content .= PHP_EOL;
                $content .= $this->addContent(';Check settings:', 1);

                if ($host->isSatelliteHost() === true) {
                    // On the master system we set the freshness check command
                    // UNKNOWN: Service is no longer current
                    $content .= $this->addContent('check_command', 1, '2106cf0bf26a82af262c4078e6d9f94eded84d2a');
                }

                if ($host->isSatelliteHost() === false) {
                    if (!empty($service->get('servicecommandargumentvalues'))) {
                        if ($service->get('command_id') === null) {
                            //Service has own command arguments but uses the same command as the service template
                            $commandId = $servicetemplate->get('command_id');
                        } else {
                            //Service has own command arguments AND own check command
                            $commandId = $service->get('command_id');
                        }
                        $commandUuid = $this->CommandUuidsCache->get($commandId);
                        $commandarguments = $service->getCommandargumentValuesForCfg($servicetemplate);
                        $content .= $this->addContent('check_command', 1, $this->removeNewlines(
                            $this->escapeLastBackslash(
                                sprintf(
                                    '%s!%s; %s',
                                    $commandUuid,
                                    implode('!', Hash::extract($commandarguments, '{n}.value')),
                                    implode('!', Hash::extract($commandarguments, '{n}.human_name'))
                                )
                            )));
                    } else {
                        //May be check command without arguments
                        if ($service->get('command_id') !== null) {
                            //Service has own check command but this command has no arguments at all
                            $commandId = $service->get('command_id');
                            $commandUuid = $this->CommandUuidsCache->get($commandId);
                            $content .= $this->addContent('check_command', 1, $commandUuid);
                        }
                    }

                    if ($servicetemplate->hasEventhandler() || $service->hasEventhandler()) {
                        $content .= PHP_EOL;
                        $content .= $this->addContent(';Event handler:', 1);

                        if (!empty($service->get('serviceeventcommandargumentvalues'))) {
                            if ($service->get('eventhandler_command_id') === null) {
                                //Service has own command arguments but uses the same event handler command as the service template
                                $commandId = $servicetemplate->get('eventhandler_command_id');
                            } else {
                                //Service has own event handler command arguments AND own event handler command
                                $commandId = $service->get('eventhandler_command_id');
                            }
                            $commandUuid = $this->CommandUuidsCache->get($commandId);
                            $eventcommandarguments = $service->getEventhandlerCommandargumentValuesForCfg();
                            $content .= $this->addContent('event_handler', 1, $this->removeNewlines(
                                $this->escapeLastBackslash(
                                    sprintf(
                                        '%s!%s; %s',
                                        $commandUuid,
                                        implode('!', Hash::extract($eventcommandarguments, '{n}.value')),
                                        implode('!', Hash::extract($eventcommandarguments, '{n}.human_name'))
                                    )
                                )));
                        } else {
                            //May be event handler command without arguments
                            if ($service->get('eventhandler_command_id') !== null) {
                                //Service has own evnet handler command but this command has no arguments at all
                                $commandId = $service->get('eventhandler_command_id');
                                $commandUuid = $this->CommandUuidsCache->get($commandId);
                                $content .= $this->addContent('event_handler', 1, $commandUuid);
                            }
                        }
                    }
                }

                if ($service->get('check_period_id')) {
                    $timeperiodUuid = $this->TimeperiodUuidsCache->get($service->get('check_period_id'));
                    $content .= $this->addContent('check_period', 1, $timeperiodUuid);
                }

                if ($service->get('check_interval') !== null && $service->get('check_interval') !== '')
                    $content .= $this->addContent('check_interval', 1, $service->get('check_interval'));

                if ($service->get('retry_interval') !== null && $service->get('retry_interval') !== '')
                    $content .= $this->addContent('retry_interval', 1, $service->get('retry_interval'));

                if ($service->get('max_check_attempts') !== null && $service->get('max_check_attempts') !== '')
                    $content .= $this->addContent('max_check_attempts', 1, $service->get('max_check_attempts'));


                if ($host->isSatelliteHost() === true) {
                    $content .= $this->addContent('active_checks_enabled', 1, 0);
                    $content .= $this->addContent('passive_checks_enabled', 1, 1);
                }

                if ($host->isSatelliteHost() === false) {
                    if ($service->get('active_checks_enabled') !== null && $service->get('active_checks_enabled') !== '') {
                        $content .= $this->addContent('active_checks_enabled', 1, $service->get('active_checks_enabled'));
                    }

                    if ($service->get('passive_checks_enabled') !== null && $service->get('passive_checks_enabled') !== '') {
                        $content .= $this->addContent('passive_checks_enabled', 1, $service->get('passive_checks_enabled'));
                    }
                }

                /* Freshness checks starts */
                $activeChecksEnabled = $service->get('active_checks_enabled');
                if ($activeChecksEnabled === null) {
                    $activeChecksEnabled = $servicetemplate->get('active_checks_enabled');
                }

                $freshnessChecksEnabled = $service->get('freshness_checks_enabled');
                if ($freshnessChecksEnabled === null) {
                    $freshnessChecksEnabled = $servicetemplate->get('freshness_checks_enabled');
                }
                $freshnessThreshold = $service->get('freshness_threshold');
                if ($freshnessThreshold === null) {
                    $freshnessThreshold = $servicetemplate->get('freshness_threshold');
                }

                $checkInterval = $service->get('check_interval');
                if ($checkInterval === null || $checkInterval === '' || $checkInterval === 0) {
                    $checkInterval = $servicetemplate->get('check_interval');
                }

                $checkInterval = (int)$checkInterval;

                if ($host->isSatelliteHost() === true) {
                    // Service is checked by a satellite system
                    // Do we need to add the freshness check on the master instance?
                    if ($activeChecksEnabled) {
                        $content .= $this->addContent('check_freshness', 1, 1);
                        if ($freshnessChecksEnabled > 0 && $freshnessThreshold > 0) {
                            // Active check with configured freshness + freshness_threshold_addition on the master for "service is no longer current"
                            $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold + $checkInterval + $this->FRESHNESS_THRESHOLD_ADDITION);
                        } else {
                            // Only active check, add freshness check on the master for "service is no longer current"
                            $content .= $this->addContent('freshness_threshold', 1, $checkInterval + $this->FRESHNESS_THRESHOLD_ADDITION);
                        }
                    } else {
                        if ($freshnessChecksEnabled > 0 && $freshnessThreshold > 0) {
                            // Passive service with enabled freshness checking
                            $content .= $this->addContent('check_freshness', 1, 1);
                            $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold + $checkInterval + $this->FRESHNESS_THRESHOLD_ADDITION);
                        }
                    }
                } else {
                    // Service is in the master instance
                    // Passive service on the master system
                    if ($service->get('freshness_checks_enabled') !== null) {
                        $content .= $this->addContent('check_freshness', 1, $service->get('freshness_checks_enabled'));
                    }
                    if ($freshnessChecksEnabled > 0 && $freshnessThreshold > 0) {
                        $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold);
                    }
                }
                /* Freshness checks ends */

                $content .= PHP_EOL;
                $content .= $this->addContent(';Notification settings:', 1);

                if ($service->get('notifications_enabled') !== null && $service->get('notifications_enabled') !== '') {
                    $content .= $this->addContent('notifications_enabled', 1, $service->get('notifications_enabled'));
                }

                if (!empty($service->get('contacts'))) {
                    $content .= $this->addContent('contacts', 1, $service->getContactsforCfg());
                    // ITC-2710 Inheritance of contacts and contact groups
                    if (empty($service->get('contactgroups'))) {
                        $content .= $this->addContent('contact_groups', 1, 'null');
                    }
                }

                if (!empty($service->get('contactgroups'))) {
                    // ITC-2710 Inheritance of contacts and contact groups
                    if (empty($service->get('contacts'))) {
                        $content .= $this->addContent('contacts', 1, 'null');
                    }
                    $content .= $this->addContent('contact_groups', 1, $service->getContactgroupsforCfg());
                }


                if ($service->get('notification_interval') !== null && $service->get('notification_interval') !== '') {
                    $content .= $this->addContent('notification_interval', 1, $service->get('notification_interval'));
                }

                if ($service->get('notify_period_id')) {
                    $timeperiodUuid = $this->TimeperiodUuidsCache->get($service->get('notify_period_id'));
                    $content .= $this->addContent('notification_period', 1, $timeperiodUuid);
                }

                if (strlen($service->getNotificationOptionsForCfg($servicetemplate)) > 0) {
                    $content .= $this->addContent('notification_options', 1, $service->getNotificationOptionsForCfg($servicetemplate));
                }

                $content .= PHP_EOL;
                $content .= $this->addContent(';Flap detection settings:', 1);
                if ($service->get('flap_detection_enabled') === 1 || $service->get('flap_detection_enabled') === 0) {
                    $content .= $this->addContent('flap_detection_enabled', 1, $service->get('flap_detection_enabled'));
                }

                if ($service->get('flap_detection_enabled') === 1 && strlen($service->getFlapdetectionOptionsForCfg($servicetemplate)) > 0) {
                    $content .= $this->addContent('flap_detection_options', 1, $service->getFlapdetectionOptionsForCfg($servicetemplate));
                }

                $content .= PHP_EOL;
                $content .= $this->addContent(';Everything else:', 1);
                if ($service->get('process_performance_data') === 1 || $service->get('process_performance_data') === 0)
                    $content .= $this->addContent('process_perf_data', 1, $service->get('process_performance_data'));

                if ($service->get('notes') && strlen($service->get('notes')) > 0) {
                    $content .= $this->addContent('notes', 1, $this->removeNewlines($this->escapeLastBackslash($service->get('notes'))));
                }

                if ($service->get('is_volatile') === 1 || $service->get('is_volatile') === 0)
                    $content .= $this->addContent('is_volatile', 1, $service->get('is_volatile'));

                if (!empty($service->get('servicegroups'))) {
                    //Use service groups of the service
                    $content .= PHP_EOL;
                    $content .= $this->addContent(';Servicegroup memberships:', 1);
                    $content .= $this->addContent('servicegroups', 1, $service->getServicegroupsForCfg());
                } else if (empty($service->get('servicegroups')) && !empty($servicetemplate->get('servicegroups'))) {
                    //Use service groups of service template configuration
                    $content .= PHP_EOL;
                    $content .= $this->addContent(';Servicegroup memberships:', 1);
                    $content .= $this->addContent('servicegroups', 1, $servicetemplate->getServicegroupsForCfg());
                }

                if ($service->hasCustomvariables()) {
                    $content .= PHP_EOL;
                    $content .= $this->addContent(';Custom  variables:', 1);
                    foreach ($service->getCustomvariablesForCfg() as $varName => $varValue) {
                        $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
                    }
                }

                if (IS_CONTAINER === false && $service->service_type != GENERIC_SERVICE) {
                    // ITC-3177 The active Checkmk Service is making an HTTP request to the local running Docker container.
                    // This HTTP request can only work on the openITCOCKPIT Server itself.
                    // Due to Checkmk has only one active Service "CHECK_MK_ACTIVE" and all other Checkmk services
                    // are passive checks anyway simply force all Checkmk Services to bypass Mod-Gearman.
                    //
                    // The openITCOCKPIT Agent Active check needs a .json file with the configuration
                    // Prometheus Services are passive and dont matter
                    // External Monitoring Services are also handled by an external daemon
                    //
                    // In a Docker / Container based installation **all** checks are getting executed by Mod_Gearman
                    // and a complex Docker-Compose file makes sure that everything works
                    //
                    // We ensure that Checkmk services will bypass Mod-Gearman by setting the _WORKER variable to local
                    $content .= $this->addContent('_WORKER', 1, 'local');
                }

                $content .= $this->addContent('}', 0);

                if (!$this->conf['minified']) {
                    $file->write($content);
                    $file->close();
                }


                if ($this->dm === true && $host->isSatelliteHost() === true) {
                    $this->exportSatService($service, $host, $servicetemplate);
                }
            }
        }

        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }

        if ($this->dm === true) {
            $this->exportSatServicegroups();
        }

        $this->deleteServicePerfdata();
    }

    /**
     * @param \App\Model\Entity\Service $service
     * @param \App\Model\Entity\Host $host
     * @param \App\Model\Entity\Servicetemplate $servicetemplate
     */
    public function exportSatService(\App\Model\Entity\Service $service, \App\Model\Entity\Host $host, \App\Model\Entity\Servicetemplate $servicetemplate) {
        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');


        $satelliteId = $host->get('satellite_id');
        if (!is_dir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['services'])) {
            mkdir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['services']);
        }

        if (!$this->conf['minified']) {
            $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['services'] . $service->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();

        } else {
            $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['services'] . 'services_minified' . $this->conf['suffix']);
            $content = '';
        }

        if (!$file->exists()) {
            $file->create();
        }

        $content .= $this->addContent('define service{', 0);
        $content .= $this->addContent('use', 1, $servicetemplate->get('uuid'));
        $content .= $this->addContent('host_name', 1, $host->get('uuid'));

        $content .= $this->addContent('name', 1, $service->get('uuid'));
        if ($service->get('name') !== null && $service->get('name') !== '') {
            $content .= $this->addContent('display_name', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    $service->get('name')
                )));
        } else {
            $content .= $this->addContent('display_name', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    $servicetemplate->get('name')
                )));
        }

        $content .= $this->addContent('service_description', 1, $service->get('uuid'));

        $content .= PHP_EOL;
        $content .= $this->addContent(';Check settings:', 1);

        if (!empty($service->get('servicecommandargumentvalues'))) {
            if ($service->get('command_id') === null) {
                //Service has own command arguments but uses the same command as the service template
                $commandId = $servicetemplate->get('command_id');
            } else {
                //Service has own command arguments AND own check command
                $commandId = $service->get('command_id');
            }
            $commandUuid = $this->CommandUuidsCache->get($commandId);
            $commandarguments = $service->getCommandargumentValuesForCfg($servicetemplate);
            $content .= $this->addContent('check_command', 1, $this->removeNewlines(
                $this->escapeLastBackslash(
                    sprintf(
                        '%s!%s; %s',
                        $commandUuid,
                        implode('!', Hash::extract($commandarguments, '{n}.value')),
                        implode('!', Hash::extract($commandarguments, '{n}.human_name'))
                    )
                )));
        } else {
            //May be check command without arguments
            if ($service->get('command_id') !== null) {
                //Service has own check command but this command has no arguments at all
                $commandId = $service->get('command_id');
                $commandUuid = $this->CommandUuidsCache->get($commandId);
                $content .= $this->addContent('check_command', 1, $commandUuid);
            }
        }

        if ($servicetemplate->hasEventhandler() || $service->hasEventhandler()) {
            $content .= PHP_EOL;
            $content .= $this->addContent(';Event handler:', 1);

            if (!empty($service->get('serviceeventcommandargumentvalues'))) {
                if ($service->get('eventhandler_command_id') === null) {
                    //Service has own command arguments but uses the same event handler command as the service template
                    $commandId = $servicetemplate->get('eventhandler_command_id');
                } else {
                    //Service has own event handler command arguments AND own event handler command
                    $commandId = $service->get('eventhandler_command_id');
                }
                $commandUuid = $this->CommandUuidsCache->get($commandId);
                $eventcommandarguments = $service->getEventhandlerCommandargumentValuesForCfg();
                $content .= $this->addContent('event_handler', 1, $this->removeNewlines(
                    $this->escapeLastBackslash(
                        sprintf(
                            '%s!%s; %s',
                            $commandUuid,
                            implode('!', Hash::extract($eventcommandarguments, '{n}.value')),
                            implode('!', Hash::extract($eventcommandarguments, '{n}.human_name'))
                        )
                    )));
            } else {
                //May be event handler command without arguments
                if ($service->get('eventhandler_command_id') !== null) {
                    //Service has own evnet handler command but this command has no arguments at all
                    $commandId = $service->get('eventhandler_command_id');
                    $commandUuid = $this->CommandUuidsCache->get($commandId);
                    $content .= $this->addContent('event_handler', 1, $commandUuid);
                }
            }
        }

        if ($service->get('check_period_id')) {
            $timeperiodUuid = $this->TimeperiodUuidsCache->get($service->get('check_period_id'));
            $content .= $this->addContent('check_period', 1, $timeperiodUuid);
        }

        if ($service->get('check_interval') !== null && $service->get('check_interval') !== '')
            $content .= $this->addContent('check_interval', 1, $service->get('check_interval'));

        if ($service->get('retry_interval') !== null && $service->get('retry_interval') !== '')
            $content .= $this->addContent('retry_interval', 1, $service->get('retry_interval'));

        if ($service->get('max_check_attempts') !== null && $service->get('max_check_attempts') !== '')
            $content .= $this->addContent('max_check_attempts', 1, $service->get('max_check_attempts'));


        if ($service->get('active_checks_enabled') !== null && $service->get('active_checks_enabled') !== '') {
            $content .= $this->addContent('active_checks_enabled', 1, $service->get('active_checks_enabled'));
        }

        $content .= $this->addContent('passive_checks_enabled', 1, 1);

        /* Freshness checks starts */
        $freshnessChecksEnabled = $service->get('freshness_checks_enabled');
        if ($freshnessChecksEnabled === null) {
            $freshnessChecksEnabled = $servicetemplate->get('freshness_checks_enabled');
        }
        $freshnessThreshold = $service->get('freshness_threshold');
        if ($freshnessThreshold === null) {
            $freshnessThreshold = $servicetemplate->get('freshness_threshold');
        }
        if ($freshnessChecksEnabled > 0) {
            $content .= $this->addContent('check_freshness', 1, 1);
            if ($freshnessThreshold > 0) {
                $content .= $this->addContent('freshness_threshold', 1, $freshnessThreshold + $this->FRESHNESS_THRESHOLD_ADDITION);
            }
        }

        /* Freshness checks ends */

        $content .= PHP_EOL;
        $content .= $this->addContent(';Notification settings:', 1);
        if ($service->get('notifications_enabled') !== null && $service->get('notifications_enabled') !== '') {
            $content .= $this->addContent('notifications_enabled', 1, $service->get('notifications_enabled'));
        }

        if (!empty($service->get('contacts'))) {
            $content .= $this->addContent('contacts', 1, $service->getContactsforCfg());
            // ITC-2710 Inheritance of contacts and contact groups
            if (empty($service->get('contactgroups'))) {
                $content .= $this->addContent('contact_groups', 1, 'null');
            }
        }

        if (!empty($service->get('contactgroups'))) {
            // ITC-2710 Inheritance of contacts and contact groups
            if (empty($service->get('contacts'))) {
                $content .= $this->addContent('contacts', 1, 'null');
            }
            $content .= $this->addContent('contact_groups', 1, $service->getContactgroupsforCfg());
        }


        if ($service->get('notification_interval') !== null && $service->get('notification_interval') !== '') {
            $content .= $this->addContent('notification_interval', 1, $service->get('notification_interval'));
        }

        if ($service->get('notify_period_id')) {
            $timeperiodUuid = $this->TimeperiodUuidsCache->get($service->get('notify_period_id'));
            $content .= $this->addContent('notification_period', 1, $timeperiodUuid);
        }

        if (strlen($service->getNotificationOptionsForCfg($servicetemplate)) > 0) {
            $content .= $this->addContent('notification_options', 1, $service->getNotificationOptionsForCfg($servicetemplate));
        }

        $content .= PHP_EOL;
        $content .= $this->addContent(';Flap detection settings:', 1);
        if ($service->get('flap_detection_enabled') === 1 || $service->get('flap_detection_enabled') === 0) {
            $content .= $this->addContent('flap_detection_enabled', 1, $service->get('flap_detection_enabled'));
        }

        if ($service->get('flap_detection_enabled') === 1 && strlen($service->getFlapdetectionOptionsForCfg($servicetemplate)) > 0) {
            $content .= $this->addContent('flap_detection_options', 1, $service->getFlapdetectionOptionsForCfg($servicetemplate));
        }

        $content .= PHP_EOL;
        $content .= $this->addContent(';Everything else:', 1);
        if ($service->get('process_performance_data') === 1 || $service->get('process_performance_data') === 0)
            $content .= $this->addContent('process_perf_data', 1, $service->get('process_performance_data'));

        if ($service->get('notes') && strlen($service->get('notes')) > 0) {
            $content .= $this->addContent('notes', 1, $this->removeNewlines($this->escapeLastBackslash($service->get('notes'))));
        }

        if ($service->get('is_volatile') === 1 || $service->get('is_volatile') === 0)
            $content .= $this->addContent('is_volatile', 1, $service->get('is_volatile'));

        if (!empty($service->get('servicegroups'))) {
            //Use service groups of the service
            $content .= PHP_EOL;
            $content .= $this->addContent(';Servicegroup memberships:', 1);
            $content .= $this->addContent('servicegroups', 1, $service->getServicegroupsForCfg());
        } else if (empty($service->get('servicegroups')) && !empty($servicetemplate->get('servicegroups'))) {
            //Use service groups of service template configuration
            $content .= PHP_EOL;
            $content .= $this->addContent(';Servicegroup memberships:', 1);
            $content .= $this->addContent('servicegroups', 1, $servicetemplate->getServicegroupsForCfg());
        }

        if ($service->hasCustomvariables()) {
            $content .= PHP_EOL;
            $content .= $this->addContent(';Custom  variables:', 1);
            foreach ($service->getCustomvariablesForCfg() as $varName => $varValue) {
                $content .= $this->addContent($varName, 1, $this->removeNewlines($this->escapeLastBackslash($varValue)));
            }
        }

        if (IS_CONTAINER === false && $service->service_type != GENERIC_SERVICE) {
            // ITC-3177 The active Checkmk Service is making an HTTP request to the local running Docker container.
            // This HTTP request can only work on the openITCOCKPIT Server itself.
            // Due to Checkmk has only one active Service "CHECK_MK_ACTIVE" and all other Checkmk services
            // are passive checks anyway simply force all Checkmk Services to bypass Mod-Gearman.
            //
            // The openITCOCKPIT Agent Active check needs a .json file with the configuration
            // Prometheus Services are passive and dont matter
            // External Monitoring Services are also handled by an external daemon
            //
            // In a Docker / Container based installation **all** checks are getting executed by Mod_Gearman
            // and a complex Docker-Compose file makes sure that everything works
            //
            // We ensure that Checkmk services will bypass Mod-Gearman by setting the _WORKER variable to local
            $content .= $this->addContent('_WORKER', 1, 'local');
        }

        $content .= $this->addContent('}', 0);


        if (!$this->conf['minified']) {
            $file->write($content);
        } else {
            $file->append($content);
        }
        $file->close();

    }


    /**
     * @param null|string $uid
     */
    public function exportHostgroups() {
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        $hostgroups = $HostgroupsTable->getHostgroupsForExport();

        if (!is_dir($this->conf['path'] . $this->conf['hostgroups'])) {
            mkdir($this->conf['path'] . $this->conf['hostgroups']);
        }

        if ($this->conf['minified']) {
            $file = new File($this->conf['path'] . $this->conf['hostgroups'] . 'hostgroups_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        foreach ($hostgroups as $hostgroup) {
            /** @var \App\Model\Entity\Hostgroup $hostgroup */
            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['hostgroups'] . $hostgroup->get('uuid') . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $alias = $this->removeNewlines($this->escapeLastBackslash($hostgroup->get('description')));
            if (empty($alias)) {
                $alias = $hostgroup->get('uuid');
            }
            $content .= $this->addContent('define hostgroup{', 0);
            $content .= $this->addContent('hostgroup_name', 1, $hostgroup->get('uuid'));
            $content .= $this->addContent('alias', 1, $alias);
            $content .= $this->addContent('}', 0);

            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }
        }
        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }
    }

    public function exportServicegroups() {
        /** @var ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');
        $servicegroups = $ServicegroupsTable->getServicegroupsForExport();

        if (!is_dir($this->conf['path'] . $this->conf['servicegroups'])) {
            mkdir($this->conf['path'] . $this->conf['servicegroups']);
        }

        foreach ($servicegroups as $servicegroup) {
            /** @var \App\Model\Entity\Servicegroup $servicegroup */
            $file = new File($this->conf['path'] . $this->conf['servicegroups'] . $servicegroup->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();
            if (!$file->exists()) {
                $file->create();
            }

            $alias = $this->removeNewlines($this->escapeLastBackslash($servicegroup->get('description')));
            if (empty($alias)) {
                $alias = $servicegroup->get('uuid');
            }

            $content .= $this->addContent('define servicegroup{', 0);
            $content .= $this->addContent('servicegroup_name', 1, $servicegroup->get('uuid'));
            $content .= $this->addContent('alias', 1, $alias);

            $content .= $this->addContent('}', 0);
            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportHostescalations($uuid = null) {
        /* @var HostescalationsTable $HostescalationsTable */
        /* @var \App\Model\Entity\Hostescalation $hostescalation */
        /* @var \App\Model\Entity\Contact $contact */
        /* @var \App\Model\Entity\Hostgroup $hostgroup */
        /* @var \App\Model\Entity\Host $host */
        /* @var \App\Model\Entity\Contactgroup $contactgroup */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');
        $hostescalations = $HostescalationsTable->getHostescalationsForExport($uuid);

        if (!is_dir($this->conf['path'] . $this->conf['hostescalations'])) {
            mkdir($this->conf['path'] . $this->conf['hostescalations']);
        }
        foreach ($hostescalations as $hostescalation) {

            $hosts = $hostescalation->get('hosts');
            $hostgroups = $hostescalation->get('hostgroups');

            $escalationHosts = [
                'included' => [],
                'excluded' => []
            ];

            $escalationHostgroups = [
                'included' => [],
                'excluded' => []
            ];

            $includedHostIds = [];
            $excludedHostIds = [];

            $includedHostgroupHostIds = [];
            $excludedHostgroupHostIds = [];

            if (!is_null($hosts)) {
                foreach ($hosts as $host) {
                    if ($host->get('_joinData')->get('excluded') === 0) {
                        $escalationHosts['included'][] = $host->get('uuid');
                        $includedHostIds[] = $host->get('id');
                    } else {
                        $escalationHosts['excluded'][] = $host->get('uuid');
                        $excludedHostIds = $host->get('id');
                    }
                }
            }

            if (!is_null($hostgroups)) {
                foreach ($hostgroups as $hostgroup) {
                    //consider hosts from host template too
                    $hostIdsOverHosttemplate = Hash::extract($hostgroup, 'hosttemplates.{n}.hosts.{n}.id');
                    //ignore empty hostgroups
                    if (empty($hostgroup->get('hosts')) && empty($hostIdsOverHosttemplate)) {
                        continue;
                    }
                    if ($hostgroup->get('_joinData')->get('excluded') === 0) {
                        $escalationHostgroups['included'][] = $hostgroup->get('uuid');
                        foreach ($hostgroup->get('hosts') as $hostgroupHost) {
                            $includedHostgroupHostIds[] = $hostgroupHost->get('id');
                        }
                        $includedHostgroupHostIds = array_merge($includedHostgroupHostIds, $hostIdsOverHosttemplate);
                    } else {
                        $escalationHostgroups['excluded'][] = $hostgroup->get('uuid');
                        foreach ($hostgroup->get('hosts') as $hostgroupHost) {
                            $excludedHostgroupHostIds[] = $hostgroupHost->get('id');
                        }
                        $excludedHostgroupHostIds = array_merge($excludedHostgroupHostIds, $hostIdsOverHosttemplate);
                    }
                }
            }

            if (empty($escalationHosts['included']) && empty($escalationHostgroups['included'])) {
                //host escalation is broken - delete !!!
                $HostescalationsTable->delete($hostescalation);
                continue;
            }

            //all included hosts are excluded by "excluded host groups" configuration
            $excludedHostgroupHostIds = array_unique($excludedHostgroupHostIds);
            if (!empty($escalationHosts['included']) && empty(array_diff($includedHostIds, $excludedHostgroupHostIds))) {
                //host escalation is broken - delete !!!
                $HostescalationsTable->delete($hostescalation);
                continue;
            }

            //all included hosts through host group definition are excluded by "excluded hosts" configuration
            $includedHostgroupHostIds = array_unique($includedHostgroupHostIds);
            if (!empty($escalationHostgroups['included']) && empty(array_diff($includedHostgroupHostIds, $excludedHostIds))) {
                //host escalation is broken - delete !!!
                $HostescalationsTable->delete($hostescalation);
                continue;
            }

            $contactUuids = [];
            foreach ($hostescalation->get('contacts') as $contact) {
                $contactUuids[] = $contact->get('uuid');
            }
            $contactgroupUuids = [];
            foreach ($hostescalation->get('contactgroups') as $contactgroup) {
                $contactgroupUuids[] = $contactgroup->get('uuid');
            }

            $file = new File($this->conf['path'] . $this->conf['hostescalations'] . $hostescalation->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();
            if (!$file->exists()) {
                $file->create();
            }


            //export hosts with excluded host groups
            if (!empty($escalationHosts['included'])) {
                $content .= $this->addContent('define hostescalation{', 0);

                if (!empty($contactUuids)) {
                    $content .= $this->addContent('contacts', 1, implode(',', $contactUuids));
                }
                if (!empty($contactgroupUuids)) {
                    $content .= $this->addContent('contact_groups', 1, implode(',', $contactgroupUuids));
                }
                $content .= $this->addContent('first_notification', 1, $hostescalation->get('first_notification'));
                $content .= $this->addContent('last_notification', 1, $hostescalation->get('last_notification'));
                $content .= $this->addContent('notification_interval', 1, (int)$hostescalation->get('notification_interval'));

                $escalationTimeperiod = $hostescalation->get('timeperiod');
                if (!is_null($escalationTimeperiod)) {
                    $content .= $this->addContent('escalation_period', 1, $escalationTimeperiod->get('uuid'));
                }

                $hostEscalationString = $hostescalation->getHostEscalationStringForCfg();
                if (!empty($hostEscalationString)) {
                    $content .= $this->addContent('escalation_options', 1, $hostEscalationString);
                }

                $content .= $this->addContent('host_name', 1, implode(',', $escalationHosts['included']));
                if (!empty($escalationHostgroups['excluded'])) {
                    $content .= $this->addContent('hostgroup_name', 1, implode(',', preg_filter('/^/', '!', $escalationHostgroups['excluded'])));
                }
                $content .= $this->addContent('}', 0);

            }

            //export hosts groups with excluded hosts
            if (!empty($escalationHostgroups['included'])) {
                $content .= $this->addContent('define hostescalation{', 0);
                if (!empty($contactUuids)) {
                    $content .= $this->addContent('contacts', 1, implode(',', $contactUuids));
                }
                if (!empty($contactgroupUuids)) {
                    $content .= $this->addContent('contact_groups', 1, implode(',', $contactgroupUuids));
                }
                $content .= $this->addContent('first_notification', 1, $hostescalation->get('first_notification'));
                $content .= $this->addContent('last_notification', 1, $hostescalation->get('last_notification'));
                $content .= $this->addContent('notification_interval', 1, (int)$hostescalation->get('notification_interval'));

                $escalationTimeperiod = $hostescalation->get('timeperiod');
                if (!is_null($escalationTimeperiod)) {
                    $content .= $this->addContent('escalation_period', 1, $escalationTimeperiod->get('uuid'));
                }

                $hostEscalationString = $hostescalation->getHostEscalationStringForCfg();
                if (!empty($hostEscalationString)) {
                    $content .= $this->addContent('escalation_options', 1, $hostEscalationString);
                }
                $content .= $this->addContent('hostgroup_name', 1, implode(',', $escalationHostgroups['included']));
                if (!empty($escalationHosts['excluded'])) {
                    $content .= $this->addContent('host_name', 1, implode(',', preg_filter('/^/', '!', $escalationHosts['excluded'])));

                }
                $content .= $this->addContent('}', 0);
            }


            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportServiceescalations($uuid = null) {
        /* @var ServiceescalationsTable $ServiceescalationsTable */
        /* @var \App\Model\Entity\Serviceescalation $serviceescalation */
        /* @var \App\Model\Entity\Contact $contact */
        /* @var \App\Model\Entity\Servicegroup $servicegroup */
        /* @var \App\Model\Entity\Service $service */
        /* @var \App\Model\Entity\Contactgroup $contactgroup */
        $ServiceescalationsTable = TableRegistry::getTableLocator()->get('Serviceescalations');
        $serviceescalations = $ServiceescalationsTable->getServiceescalationsForExport($uuid);

        if (!is_dir($this->conf['path'] . $this->conf['serviceescalations'])) {
            mkdir($this->conf['path'] . $this->conf['serviceescalations']);
        }


        foreach ($serviceescalations as $serviceescalation) {

            $services = $serviceescalation->get('services');
            $servicegroups = $serviceescalation->get('servicegroups');

            $escalationServices = [
                'included' => [],
                'excluded' => []
            ];

            $escalationServicegroups = [
                'included' => [],
                'excluded' => []
            ];

            $includedServiceIds = [];
            $excludedServiceIds = [];

            $includedServicegroupServiceIds = [];
            $excludedServicegroupServiceIds = [];

            if (!is_null($services)) {
                foreach ($services as $service) {
                    if ($service->get('_joinData')->get('excluded') === 0) {
                        $escalationServices['included'][$service->get('host')->get('uuid')][] = $service->get('uuid');
                        $includedServiceIds[] = $service->get('id');
                    } else {
                        $escalationServices['excluded'][$service->get('host')->get('uuid')][] = $service->get('uuid');
                        $excludedServiceIds[] = $service->get('id');
                    }
                }
            }

            if (!is_null($servicegroups)) {
                foreach ($servicegroups as $servicegroup) {
                    //consider services from service template too
                    $serviceIdsOverServicetemplate = Hash::extract($servicegroup, 'servicetemplates.{n}.services.{n}.id');
                    //ignore empty servicegroups
                    if (empty($servicegroup->get('services')) && empty($serviceIdsOverServicetemplate)) {
                        continue;
                    }
                    if ($servicegroup->get('_joinData')->get('excluded') === 0) {
                        $escalationServicegroups['included'][] = $servicegroup->get('uuid');
                        foreach ($servicegroup->get('services') as $servicegroupService) {
                            $includedServicegroupServiceIds[] = $servicegroupService->get('id');
                        }
                        $includedServicegroupServiceIds = array_merge($includedServicegroupServiceIds, $serviceIdsOverServicetemplate);
                    } else {
                        $escalationServicegroups['excluded'][] = $servicegroup->get('uuid');
                        foreach ($servicegroup->get('services') as $servicegroupService) {
                            $excludedServicegroupServiceIds[] = $servicegroupService->get('id');
                        }
                        $excludedServicegroupServiceIds = array_merge($excludedServicegroupServiceIds, $serviceIdsOverServicetemplate);
                    }
                }
            }

            if (empty($escalationServices['included']) && empty($escalationServicegroups['included'])) {
                //service escalation is broken - delete !!!
                $ServiceescalationsTable->delete($serviceescalation);
                continue;
            }

            //all included services are excluded by "excluded service groups" configuration
            $excludedServicegroupServiceIds = array_unique($excludedServicegroupServiceIds);
            if (!empty($escalationServices['included']) && empty(array_diff($includedServiceIds, $excludedServicegroupServiceIds))) {
                //service escalation is broken - delete !!!
                $ServiceescalationsTable->delete($serviceescalation);
                continue;
            }

            //all included services through service group definition are excluded by "excluded service" configuration
            $includedServicegroupServiceIds = array_unique($includedServicegroupServiceIds);
            if (!empty($escalationServicegroups['included']) && empty(array_diff($includedServicegroupServiceIds, $excludedServiceIds))) {
                //service escalation is broken - delete !!!
                $ServiceescalationsTable->delete($serviceescalation);
                continue;
            }

            $file = new File($this->conf['path'] . $this->conf['serviceescalations'] . $serviceescalation->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();
            if (!$file->exists()) {
                $file->create();
            }

            $contactUuids = [];
            foreach ($serviceescalation->get('contacts') as $contact) {
                $contactUuids[] = $contact->get('uuid');
            }
            $contactgroupUuids = [];
            foreach ($serviceescalation->get('contactgroups') as $contactgroup) {
                $contactgroupUuids[] = $contactgroup->get('uuid');
            }

            foreach ($escalationServices['included'] as $hostUuid => $includedServiceUuids) {
                $content .= $this->addContent('define serviceescalation{', 0);
                $content .= $this->addContent('host_name', 1, $hostUuid);
                $content .= $this->addContent('service_description', 1, implode(',', $includedServiceUuids));

                if (!empty($escalationServicegroups['excluded'])) {
                    $content .= $this->addContent('servicegroup_name', 1, implode(',', preg_filter('/^/', '!', $escalationServicegroups['excluded'])));
                }

                if (!empty($contactUuids)) {
                    $content .= $this->addContent('contacts', 1, implode(',', $contactUuids));
                }
                if (!empty($contactgroupUuids)) {
                    $content .= $this->addContent('contact_groups', 1, implode(',', $contactgroupUuids));
                }
                $content .= $this->addContent('first_notification', 1, $serviceescalation->get('first_notification'));
                $content .= $this->addContent('last_notification', 1, $serviceescalation->get('last_notification'));
                $content .= $this->addContent('notification_interval', 1, (int)$serviceescalation->get('notification_interval'));

                $escalationTimeperiod = $serviceescalation->get('timeperiod');
                if (!is_null($escalationTimeperiod)) {
                    $content .= $this->addContent('escalation_period', 1, $escalationTimeperiod->get('uuid'));
                }
                $serviceEscalationString = $serviceescalation->getServiceEscalationStringForCfg();
                if (!empty($serviceEscalationString)) {
                    $content .= $this->addContent('escalation_options', 1, $serviceEscalationString);
                }

                $content .= $this->addContent('}', 0);
            }

            if (!empty($escalationServicegroups['included']) && empty($escalationServices['excluded'])) {
                $content .= $this->addContent('define serviceescalation{', 0);

                $content .= $this->addContent('servicegroup_name', 1, implode(',', $escalationServicegroups['included']));

                if (!empty($contactUuids)) {
                    $content .= $this->addContent('contacts', 1, implode(',', $contactUuids));
                }
                if (!empty($contactgroupUuids)) {
                    $content .= $this->addContent('contact_groups', 1, implode(',', $contactgroupUuids));
                }
                $content .= $this->addContent('first_notification', 1, $serviceescalation->get('first_notification'));
                $content .= $this->addContent('last_notification', 1, $serviceescalation->get('last_notification'));
                $content .= $this->addContent('notification_interval', 1, (int)$serviceescalation->get('notification_interval'));

                $escalationTimeperiod = $serviceescalation->get('timeperiod');
                if (!is_null($escalationTimeperiod)) {
                    $content .= $this->addContent('escalation_period', 1, $escalationTimeperiod->get('uuid'));
                }
                $serviceEscalationString = $serviceescalation->getServiceEscalationStringForCfg();
                if (!empty($serviceEscalationString)) {
                    $content .= $this->addContent('escalation_options', 1, $serviceEscalationString);
                }
                $content .= $this->addContent('}', 0);
            }

            if (!empty($escalationServicegroups['included']) && !empty($escalationServices['excluded'])) {
                foreach ($escalationServices['excluded'] as $hostUuid => $excludedServiceUuids) {
                    $content .= $this->addContent('define serviceescalation{', 0);
                    $content .= $this->addContent('host_name', 1, $hostUuid);
                    $content .= $this->addContent('service_description', 1, implode(',', preg_filter('/^/', '!', $excludedServiceUuids)));

                    $content .= $this->addContent('servicegroup_name', 1, implode(',', $escalationServicegroups['included']));

                    if (!empty($contactUuids)) {
                        $content .= $this->addContent('contacts', 1, implode(',', $contactUuids));
                    }
                    if (!empty($contactgroupUuids)) {
                        $content .= $this->addContent('contact_groups', 1, implode(',', $contactgroupUuids));
                    }
                    $content .= $this->addContent('first_notification', 1, $serviceescalation->get('first_notification'));
                    $content .= $this->addContent('last_notification', 1, $serviceescalation->get('last_notification'));
                    $content .= $this->addContent('notification_interval', 1, (int)$serviceescalation->get('notification_interval'));

                    $escalationTimeperiod = $serviceescalation->get('timeperiod');
                    if (!is_null($escalationTimeperiod)) {
                        $content .= $this->addContent('escalation_period', 1, $escalationTimeperiod->get('uuid'));
                    }
                    $serviceEscalationString = $serviceescalation->getServiceEscalationStringForCfg();
                    if (!empty($serviceEscalationString)) {
                        $content .= $this->addContent('escalation_options', 1, $serviceEscalationString);
                    }
                    $content .= $this->addContent('}', 0);
                }
            }

            $file->write($content);
            $file->close();
        }
    }


    /**
     * @param null $uuid
     * @throws \Exception
     */
    public function exportTimeperiods($uuid = null) {
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');
        /** @var CalendarsTable $CalendarsTable */
        $CalendarsTable = TableRegistry::getTableLocator()->get('Calendars');
        if ($uuid !== null) {
            $timeperiods[] = $TimeperiodsTable->getTimeperiodWithTimerangesByUuid($uuid);
        } else {
            $timeperiods = $TimeperiodsTable->getTimeperiodWithTimeranges();
        }

        if (!is_dir($this->conf['path'] . $this->conf['timeperiods'])) {
            mkdir($this->conf['path'] . $this->conf['timeperiods']);
        }

        if ($this->conf['minified']) {
            $file = new File($this->conf['path'] . $this->conf['timeperiods'] . 'timeperiods_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        $date = new \DateTime();
        $weekdays = [];
        for ($i = 1; $i <= 7; $i++) {
            $weekdays[$date->format('N')] = strtolower($date->format('l'));
            $date->modify('+1 day');
        }

        foreach ($timeperiods as $timeperiod) {
            $timeranges = [];
            if (!$this->conf['minified']) {
                $file = new File($this->conf['path'] . $this->conf['timeperiods'] . $timeperiod['Timeperiod']['uuid'] . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $content .= $this->addContent('define timeperiod{', 0);
            $content .= $this->addContent('timeperiod_name', 1, $timeperiod['Timeperiod']['uuid']);
            if (!empty($timeperiod['Timeperiod']['description']) && strlen($timeperiod['Timeperiod']['description']) > 0) {
                $content .= $this->addContent('alias', 1, $this->removeNewlines($this->escapeLastBackslash($timeperiod['Timeperiod']['description'])));
            } else {
                //Naemon 1.0.0 fix
                $content .= $this->addContent('alias', 1, $timeperiod['Timeperiod']['uuid']);
            }

            foreach ($timeperiod['Timeperiod']['timeperiod_timeranges'] as $timerange) {
                $timeranges[$timerange['day']][] = $timerange['start'] . '-' . $timerange['end'];
            }

            foreach ($timeranges as $weekday => $timesArray) {
                asort($timesArray);
                $content .= $this->addContent($weekdays[$weekday], 1, implode(',', $timesArray));
            }

            //Merge Calendar records to timeperiod
            if ($timeperiod['Timeperiod']['calendar_id'] > 0) {
                try {
                    $calendar = $CalendarsTable->getCalendarById($timeperiod['Timeperiod']['calendar_id']);
                    foreach ($calendar->get('calendar_holidays') as $holiday) {
                        /** @var CalendarHoliday $holiday */
                        $timestamp = strtotime(sprintf('%s 00:00', $holiday->get('date')));

                        $calendarDay = sprintf('%s 00:00-24:00; %s',
                            strtolower(date('Y-m-d', $timestamp)),
                            $holiday->get('name')
                        );
                        $content .= $this->addContent($calendarDay, 1);
                    }
                } catch (RecordNotFoundException $e) {
                    Log::error(sprintf(
                        'NagiosConfigGenerator: Calendar with id "%s" not found',
                        $timeperiod['Timeperiod']['calendar_id']
                    ));
                    Log::error($e->getMessage());
                }
            }

            $content .= $this->addContent('}', 0);

            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }
        }

        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }

        if ($this->dm === true) {
            foreach ($this->Satellites as $satelite) {
                $this->exportSatTimeperiods($timeperiods, $satelite);
            }
        }
    }

    /**
     * @param $timeperiods
     * @param EntityInterface $satelite
     * @throws \Exception
     */
    public function exportSatTimeperiods($timeperiods, EntityInterface $satelite) {
        if (!is_dir($this->conf['satellite_path'] . $satelite->get('id') . DS . $this->conf['timeperiods'])) {
            mkdir($this->conf['satellite_path'] . $satelite->get('id') . DS . $this->conf['timeperiods']);
        }

        /** @var CalendarsTable $CalendarsTable */
        $CalendarsTable = TableRegistry::getTableLocator()->get('Calendars');

        if ($this->conf['minified']) {
            $file = new File($this->conf['satellite_path'] . $satelite->get('id') . DS . $this->conf['timeperiods'] . 'timeperiods_minified' . $this->conf['suffix']);
            if (!$file->exists()) {
                $file->create();
            }
            $content = $this->fileHeader();
        }

        $date = new \DateTime();
        $weekdays = [];
        for ($i = 1; $i <= 7; $i++) {
            $weekdays[$date->format('N')] = strtolower($date->format('l'));
            $date->modify('+1 day');
        }

        foreach ($timeperiods as $timeperiod) {
            if (!$this->conf['minified']) {
                $file = new File($this->conf['satellite_path'] . $satelite->get('id') . DS . $this->conf['timeperiods'] . $timeperiod['Timeperiod']['uuid'] . $this->conf['suffix']);
                $content = $this->fileHeader();
                if (!$file->exists()) {
                    $file->create();
                }
            }

            $content .= $this->addContent('define timeperiod{', 0);
            $content .= $this->addContent('timeperiod_name', 1, $timeperiod['Timeperiod']['uuid']);
            if (!empty($timeperiod['Timeperiod']['description']) && strlen($timeperiod['Timeperiod']['description']) > 0) {
                $content .= $this->addContent('alias', 1, $this->removeNewlines($this->escapeLastBackslash($timeperiod['Timeperiod']['description'])));
            } else {
                //Naemon 1.0.0 fix
                $content .= $this->addContent('alias', 1, $timeperiod['Timeperiod']['uuid']);
            }
            $timeRanges = [];
            foreach ($timeperiod['Timeperiod']['timeperiod_timeranges'] as $timeRange) {
                if (empty($satelite->get('timezone')) || ($timeRange['start'] == '00:00' && $timeRange['end'] == '24:00')) {
                    $timeRanges[$weekdays[$timeRange['day']]][] = $timeRange['start'] . '-' . $timeRange['end'];
                } else {
                    $remoteTimeZone = new \DateTimeZone($satelite->get('timezone'));
                    $start = new \DateTime($weekdays[$timeRange['day']] . ' ' . $timeRange['start']);
                    $start = $start->setTimezone($remoteTimeZone);
                    $end = new \DateTime($weekdays[$timeRange['day']] . ' ' . (($timeRange['end'] == '24:00') ? '23:59' : $timeRange['end']));
                    $end = $end->setTimezone($remoteTimeZone);
                    if ($timeRange['end'] == '24:00') {
                        $end = $end->add(new \DateInterval('PT1M'));
                    }
                    if ($start->format('l') == $end->format('l')) {
                        $timeRanges[strtolower($start->format('l'))][] = $start->format('H:i') . '-' . $end->format('H:i');
                    } else {
                        $timeRanges[strtolower($start->format('l'))][] = $start->format('H:i') . '-24:00';
                        if ($end->format('H:i') != '00:00') {
                            $timeRanges[strtolower($end->format('l'))][] = '00:00-' . $end->format('H:i');
                        }
                    }
                }
            }
            foreach ($timeRanges as $day => $timeRange) {
                asort($timeRange);
                $content .= $this->addContent($day, 1, implode(',', $timeRange));
            }

            //Merge Calendar records to timeperiod
            if ($timeperiod['Timeperiod']['calendar_id'] > 0) {
                try {
                    $calendar = $CalendarsTable->getCalendarById($timeperiod['Timeperiod']['calendar_id']);
                    foreach ($calendar->get('calendar_holidays') as $holiday) {
                        /** @var CalendarHoliday $holiday */
                        $timestamp = strtotime(sprintf('%s 00:00', $holiday->get('date')));

                        $calendarDay = sprintf('%s 00:00-24:00; %s',
                            strtolower(date('Y-m-d', $timestamp)),
                            $holiday->get('name')
                        );
                        $content .= $this->addContent($calendarDay, 1);
                    }
                } catch (RecordNotFoundException $e) {
                    Log::error(sprintf(
                        'NagiosConfigGenerator: Calendar with id "%s" not found',
                        $timeperiod['Timeperiod']['calendar_id']
                    ));
                    Log::error($e->getMessage());
                }
            }

            $content .= $this->addContent('}', 0);

            if (!$this->conf['minified']) {
                $file->write($content);
                $file->close();
            }
        }

        if ($this->conf['minified']) {
            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportHostdependencies($uuid = null) {
        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $hostdependencies = $HostdependenciesTable->getHostdependenciesForExport($uuid);

        if (!is_dir($this->conf['path'] . $this->conf['hostdependencies'])) {
            mkdir($this->conf['path'] . $this->conf['hostdependencies']);
        }

        foreach ($hostdependencies as $hostdependency) {
            /** @var Hostdependency $hostdependency */
            $file = new File($this->conf['path'] . $this->conf['hostdependencies'] . $hostdependency->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();
            if (!$file->exists()) {
                $file->create();
            }

            $hostsForCfg = [];
            $dependentHostsForCfg = [];
            $hosts = $hostdependency->get('hosts');
            //Check if the host dependency is valid
            if (is_null($hosts)) {
                //This host dependency is broken, ther are no hosts in it!
                $HostdependenciesTable->delete($hostdependency);
                $file->close();
                if ($file->exists()) {
                    $file->delete();
                }
                continue;
            }

            $hostgroupsForCfg = [];
            $dependentHostgroupsForCfg = [];

            $hostgroupIds = [];
            $dependentHostgroupIds = [];

            if (!is_null($hostdependency->get('hostgroups'))) {
                $hostgroups = $hostdependency->get('hostgroups');
                foreach ($hostgroups as $hostgroup) {
                    /** @var Hostgroup $hostgroup */
                    if ($hostgroup->get('_joinData')->get('dependent') === 0) {
                        $hostgroupsForCfg[] = $hostgroup->get('uuid');
                        $hostgroupIds[] = $hostgroup->get('id');

                    } else {
                        $dependentHostgroupsForCfg[] = $hostgroup->get('uuid');
                        $dependentHostgroupIds[] = $hostgroup->get('id');
                    }
                }
            }

            foreach ($hosts as $host) {
                /** @var Host $host */
                if ($host->get('_joinData')->get('dependent') === 0) {
                    if (empty($dependentHostgroupIds)) {
                        $hostsForCfg[] = $host->get('uuid');
                    } else if (!empty($dependentHostgroupIds) && !$HostgroupsTable->isHostInHostgroup($host->get('id'), $dependentHostgroupIds)) {
                        $hostsForCfg[] = $host->get('uuid');
                    }

                } else {
                    if (empty($hostgroupIds)) {
                        $dependentHostsForCfg[] = $host->get('uuid');
                    } else if (!empty($hostgroupIds) && !$HostgroupsTable->isHostInHostgroup($host->get('id'), $hostgroupIds)) {
                        $dependentHostsForCfg[] = $host->get('uuid');
                    }
                }
            }

            if (empty($hostsForCfg) || empty($dependentHostsForCfg)) {
                //This host dependency is broken, there are no hosts in it!
                $HostdependenciesTable->delete($hostdependency);
                $file->close();
                if ($file->exists()) {
                    $file->delete();
                }
                continue;
            }

            if (!empty($hostsForCfg) && !empty($dependentHostsForCfg)) {
                $content .= $this->addContent('define hostdependency{', 0);

                $content .= $this->addContent('host_name', 1, implode(',', $hostsForCfg));
                $content .= $this->addContent('dependent_host_name', 1, implode(',', $dependentHostsForCfg));
            }

            if (!empty($hostgroupsForCfg)) {
                $content .= $this->addContent('hostgroup_name', 1, implode(',', $hostgroupsForCfg));
            }
            if (!empty($dependentHostgroupsForCfg)) {
                $content .= $this->addContent('dependent_hostgroup_name', 1, implode(',', $dependentHostgroupsForCfg));
            }

            $content .= $this->addContent('inherits_parent', 1, $hostdependency->get('inherits_parent'));

            $executionFailureCriteriaForCfgString = $hostdependency->getExecutionFailureCriteriaForCfg();
            if (!empty($executionFailureCriteriaForCfgString)) {
                $content .= $this->addContent('execution_failure_criteria', 1, $executionFailureCriteriaForCfgString);
            }
            $notificationFailureCriteriaForCfgString = $hostdependency->getNotificationFailureCriteriaForCfg();
            if (!empty($notificationFailureCriteriaForCfgString)) {
                $content .= $this->addContent('notification_failure_criteria', 1, $notificationFailureCriteriaForCfgString);
            }
            $dependencyTimeperiod = $hostdependency->get('timeperiod');
            if (!is_null($dependencyTimeperiod)) {
                $content .= $this->addContent('dependency_period', 1, $dependencyTimeperiod->get('uuid'));
            }
            $content .= $this->addContent('}', 0);

            $file->write($content);
            $file->close();
        }
    }

    /**
     * @param null|string $uuid
     */
    public function exportServicedependencies($uuid = null) {
        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
        /** @var \App\Model\Entity\Servicedependency $servicedependency */
        $servicedependencies = $ServicedependenciesTable->getServicedependenciesForExport($uuid);
        /** @var  ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');

        if (!is_dir($this->conf['path'] . $this->conf['servicedependencies'])) {
            mkdir($this->conf['path'] . $this->conf['servicedependencies']);
        }

        foreach ($servicedependencies as $servicedependency) {
            $file = new File($this->conf['path'] . $this->conf['servicedependencies'] . $servicedependency->get('uuid') . $this->conf['suffix']);
            $content = $this->fileHeader();
            if (!$file->exists()) {
                $file->create();
            }
            $masterServicesForCfg = [];
            $dependentServicesForCfg = [];

            $servicegroupIds = [];
            $dependentServicegroupIds = [];

            $services = $servicedependency->get('services', [
                'contain' => 'Hosts'
            ]);

            $servicegroupsForCfg = [];
            $dependentServicegroupsForCfg = [];
            if (!is_null($servicedependency->get('servicegroups'))) {
                $servicegroups = $servicedependency->get('servicegroups');
                foreach ($servicegroups as $servicegroup) {
                    /** @var Servicegroup $servicegroup */
                    if ($servicegroup->get('_joinData')->get('dependent') === 0) {
                        $servicegroupsForCfg[] = $servicegroup->get('uuid');
                        $servicegroupIds[] = $servicegroup->get('id');
                    } else {
                        $dependentServicegroupsForCfg[] = $servicegroup->get('uuid');
                        $dependentServicegroupIds[] = $servicegroup->get('id');
                    }
                }
            }

            foreach ($services as $service) {
                /** @var Service $service */
                if ($service->get('_joinData')->get('dependent') === 0) {
                    if (empty($dependentServicegroupIds)) {
                        $masterServicesForCfg[] = [
                            'hostUuid'    => $service->get('host')->get('uuid'),
                            'serviceUuid' => $service->get('uuid')
                        ];
                    } else if (!empty($dependentServicegroupIds) && !$ServicegroupsTable->isServiceInServicegroup($service->get('id'), $dependentServicegroupIds)) {
                        $masterServicesForCfg[] = [
                            'hostUuid'    => $service->get('host')->get('uuid'),
                            'serviceUuid' => $service->get('uuid')
                        ];
                    }
                } else {
                    if (empty($servicegroupIds)) {
                        $dependentServicesForCfg[] = [
                            'hostUuid'    => $service->get('host')->get('uuid'),
                            'serviceUuid' => $service->get('uuid')
                        ];
                    } else if (!empty($servicegroupIds) && !$ServicegroupsTable->isServiceInServicegroup($service->get('id'), $servicegroupIds)) {
                        $dependentServicesForCfg[] = [
                            'hostUuid'    => $service->get('host')->get('uuid'),
                            'serviceUuid' => $service->get('uuid')
                        ];
                    }
                }
            }

            //Check if the service dependency is valid
            if (empty($masterServicesForCfg) || empty($dependentServicesForCfg)) {
                //This service dependency is broken, there are no services or no dependent services in it!
                $ServicedependenciesTable->delete($servicedependency);
                $file->close();
                if ($file->exists()) {
                    $file->delete();
                }
                continue;
            }

            $serviceDependencyTimeperiod = null;
            $dependencyTimeperiod = $servicedependency->get('timeperiod');
            if (!is_null($dependencyTimeperiod)) {
                $serviceDependencyTimeperiod = $dependencyTimeperiod->get('uuid');
            }
            $executionFailureCriteriaForCfgString = $servicedependency->getExecutionFailureCriteriaForCfg();
            $notificationFailureCriteriaForCfgString = $servicedependency->getNotificationFailureCriteriaForCfg();


            foreach ($masterServicesForCfg as $masterServiceForCfg) {
                foreach ($dependentServicesForCfg as $dependentServiceForCfg) {
                    $content .= $this->addContent('define servicedependency{', 0);
                    $content .= $this->addContent('host_name', 1, $masterServiceForCfg['hostUuid']);
                    $content .= $this->addContent('service_description', 1, $masterServiceForCfg['serviceUuid']);
                    $content .= $this->addContent('dependent_host_name', 1, $dependentServiceForCfg['hostUuid']);
                    $content .= $this->addContent('dependent_service_description', 1, $dependentServiceForCfg['serviceUuid']);


                    if (!empty($servicegroupsForCfg)) {
                        $content .= $this->addContent('servicegroup_name', 1, implode(', ', $servicegroupsForCfg));
                    }

                    if (!empty($dependentServicegroupsForCfg)) {
                        $content .= $this->addContent('dependent_servicegroup_name', 1, implode(', ', $dependentServicegroupsForCfg));
                    }

                    $content .= $this->addContent('inherits_parent', 1, $servicedependency->get('inherits_parent'));

                    if (!empty($executionFailureCriteriaForCfgString)) {
                        // If all states are selected you get an warning like this:
                        //Warning: Ignoring lame service dependency (config file 'foo.cfg', line 83)
                        $content .= $this->addContent('execution_failure_criteria', 1, $executionFailureCriteriaForCfgString);
                    }
                    if (!empty($notificationFailureCriteriaForCfgString)) {
                        // If all states are selected you get an warning like this:
                        //Warning: Ignoring lame service dependency (config file 'foo.cfg', line 83)
                        $content .= $this->addContent('notification_failure_criteria', 1, $notificationFailureCriteriaForCfgString);
                    }

                    if (!is_null($serviceDependencyTimeperiod)) {
                        $content .= $this->addContent('dependency_period', 1, $serviceDependencyTimeperiod);
                    }

                    $content .= $this->addContent('}', 0);
                    $content .= PHP_EOL;
                }
            }
            $file->write($content);
            $file->close();
        }
    }


    public function exportMacros() {
        $file = new File($this->conf['path'] . $this->conf['macros'] . $this->conf['suffix']);
        $content = $this->hashFileHeader();
        if (!$file->exists()) {
            $file->create();
        }

        /** @var $Macro MacrosTable */
        $Macro = TableRegistry::getTableLocator()->get('Macros');
        $macros = $Macro->getAllMacros();

        foreach ($macros as $macro) {
            $content .= $this->addContent($macro['name'] . '=' . $this->removeNewlines($this->escapeLastBackslash($macro['value'])), 0);
        }

        $file->write($content);
        $file->close();
    }

    /**
     * This method runs after the old Nagios/Naemon config files where deleted and the backup was created
     * and BEFORE any new configuration file was written
     */
    public function beforeExportExternalTasks() {
        $this->createMissingOitcAgentActiveChecks();

        $ExportTasks = new ExportTasks();
        foreach ($ExportTasks->getTasks() as $task) {
            /** @var PluginExportTasks $task */
            $task->beforeExport();
        }
    }

    /**
     * This methods run after all Nagios/Naemon configuration files where generated
     * but BEFORE the sync on satellite systems
     *
     */
    public function afterExportExternalTasks() {
        // Create Agent config for the Master System
        $this->createOitcAgentJsonConfig(0);
        // Create Agent config for all Satellite Systems
        if ($this->dm === true) {
            foreach ($this->Satellites as $satellite) {
                $satelliteId = $satellite->get('id');

                $this->createOitcAgentJsonConfig($satelliteId);
            }
        }

        //Restart oitc CMD to wipe old cached information
        if (IS_CONTAINER) {
            try {
                $Supervisorctl = new Supervisorctl();
                $Supervisorctl->restart('oitc_cmd');
            } catch (\Exception $e) {
                Log::error('NagiosConfigGenerator: ' . $e->getMessage());
            }
        } else {
            exec('systemctl restart oitc_cmd');
        }


        $ExportTasks = new ExportTasks();
        foreach ($ExportTasks->getTasks() as $task) {
            /** @var PluginExportTasks $task */
            $task->afterExport();
        }
    }

    public function exportSatHostgroups() {
        /** @var HostgroupsTable $HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        $hostgroups = $HostgroupsTable->getHostgroupsForExport();

        foreach ($this->Satellites as $satellite) {
            $satelliteId = $satellite->get('id');

            if (!is_dir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hostgroups'])) {
                mkdir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hostgroups']);
            }

            if ($this->conf['minified']) {
                $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hostgroups'] . 'hostgroups_minified' . $this->conf['suffix']);
                if (!$file->exists()) {
                    $file->create();
                }
                $content = $this->fileHeader();
            }

            foreach ($hostgroups as $hostgroup) {
                /** @var \App\Model\Entity\Hostgroup $hostgroup */
                if (!$this->conf['minified']) {
                    $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['hostgroups'] . $hostgroup->get('uuid') . $this->conf['suffix']);

                    $content = $this->fileHeader();
                    if (!$file->exists()) {
                        $file->create();
                    }
                }

                $alias = $this->removeNewlines($this->escapeLastBackslash($hostgroup->get('description')));
                if (empty($alias)) {
                    $alias = $hostgroup->get('uuid');
                }
                $content .= $this->addContent('define hostgroup{', 0);
                $content .= $this->addContent('hostgroup_name', 1, $hostgroup->get('uuid'));
                $content .= $this->addContent('alias', 1, $alias);
                $content .= $this->addContent('}', 0);

                if (!$this->conf['minified']) {
                    $file->write($content);
                    $file->close();
                }
            }
            if ($this->conf['minified']) {
                $file->write($content);
                $file->close();
            }
        }
    }

    public function exportSatServicegroups() {
        /** @var ServicegroupsTable $ServicegroupsTable */
        $ServicegroupsTable = TableRegistry::getTableLocator()->get('Servicegroups');
        $servicegroups = $ServicegroupsTable->getServicegroupsForExport();

        foreach ($this->Satellites as $satellite) {
            $satelliteId = $satellite->get('id');

            if (!is_dir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['servicegroups'])) {
                mkdir($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['servicegroups']);
            }

            if ($this->conf['minified']) {
                $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['servicegroups'] . 'servicegroups_minified' . $this->conf['suffix']);
                if (!$file->exists()) {
                    $file->create();
                }
                $content = $this->fileHeader();
            }

            foreach ($servicegroups as $servicegroup) {
                /** @var \App\Model\Entity\Servicegroup $servicegroup */
                if (!$this->conf['minified']) {
                    $file = new File($this->conf['satellite_path'] . $satelliteId . DS . $this->conf['servicegroups'] . $servicegroup->get('uuid') . $this->conf['suffix']);

                    $content = $this->fileHeader();
                    if (!$file->exists()) {
                        $file->create();
                    }
                }

                $alias = $this->removeNewlines($this->escapeLastBackslash($servicegroup->get('description')));
                if (empty($alias)) {
                    $alias = $servicegroup->get('uuid');
                }

                $content .= $this->addContent('define servicegroup{', 0);
                $content .= $this->addContent('servicegroup_name', 1, $servicegroup->get('uuid'));
                $content .= $this->addContent('alias', 1, $alias);
                $content .= $this->addContent('}', 0);
                $file->write($content);
                $file->close();
            }
        }
    }

    /**
     * @return string
     */
    public function returnReloadCommand() {
        return $this->_systemsettings['MONITORING']['MONITORING.RELOAD'];
    }

    /**
     * @return string
     */
    public function returnAfterExportCommand() {
        return $this->_systemsettings['MONITORING']['MONITORING.AFTER_EXPORT'];
    }

    public function deleteHostPerfdata() {
        return true; // @todo fix me
        $basePath = Configure::read('rrd.path');

        /** @var $DeletedHostsTable DeletedHostsTable */
        $DeletedHostsTable = TableRegistry::getTableLocator()->get('DeletedHosts');

        /** @var ConfigurationFilesTable $ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');
        $GraphingDocker = new GraphingDocker();
        $config = $GraphingDocker->mergeDbResultWithDefaultConfiguration($ConfigurationFilesTable->getConfigValuesByConfigFile($GraphingDocker->getDbKey()));

        $wspPath = $config['string']['carbon_path'] . DS . 'openitcockpit' . DS; // Default: /var/lib/graphite/whisper/openitcockpit/

        foreach ($DeletedHostsTable->getDeletedHostsWherePerfdataWasNotDeletedYet() as $deletedHost) {
            /** @var \App\Model\Entity\DeletedHost $deletedHost */

            //Delete .rrd files (Rrdtool)
            if (is_dir($basePath . $deletedHost->get('uuid'))) {
                $folder = new Folder($basePath . $deletedHost->get('uuid'));
                $folder->delete();
                unset($folder);
            }

            //Delete .wsp files (Graphite)
            if (is_dir($wspPath . $deletedHost->get('uuid'))) {
                $folder = new Folder($wspPath . $deletedHost->get('uuid'));
                $folder->delete();
                unset($folder);
            }


            $deletedHost->set('deleted_perfdata', 1);
            $DeletedHostsTable->save($deletedHost);
        }
    }

    public function deleteServicePerfdata() {
        return true; // @todo fix me
        $basePath = Configure::read('rrd.path');

        /** @var $DeletedServicesTable DeletedServicesTable */
        $DeletedServicesTable = TableRegistry::getTableLocator()->get('DeletedServices');

        /** @var ConfigurationFilesTable $ConfigurationFilesTable */
        $ConfigurationFilesTable = TableRegistry::getTableLocator()->get('ConfigurationFiles');
        $GraphingDocker = new GraphingDocker();
        $config = $GraphingDocker->mergeDbResultWithDefaultConfiguration($ConfigurationFilesTable->getConfigValuesByConfigFile($GraphingDocker->getDbKey()));

        $wspPath = $config['string']['carbon_path'] . DS . 'openitcockpit' . DS; // Default: /var/lib/graphite/whisper/openitcockpit/

        foreach ($DeletedServicesTable->getDeletedServicesWherePerfdataWasNotDeletedYet() as $deletedService) {
            /** @var \App\Model\Entity\DeletedService $deletedService */
            foreach (['rrd', 'xml'] as $extension) {
                //Check if perfdata .rrd and .xml files still exists and if we need to delete them (Rrdtool)
                $file = $basePath . $deletedService['DeletedService']['host_uuid'] . '/' . $deletedService['DeletedService']['uuid'] . '.' . $extension;
                if (file_exists($file)) {
                    unlink($file);
                }

                //Checking for .wsp file (Graphite)
                $wspFilesDir = $wspPath . $deletedService['DeletedService']['host_uuid'] . '/' . $deletedService['DeletedService']['uuid'];
                if (is_dir($wspFilesDir)) {
                    $folder = new Folder($wspFilesDir);
                    $folder->delete();
                    unset($folder);
                }
            }
            $deletedService->set('deleted_perfdata', 1);
            $DeletedServicesTable->save($deletedService);
        }
    }

    public function deleteAllConfigfiles() {
        if (!is_dir($this->conf['path'] . 'config')) {
            mkdir($this->conf['path'] . 'config');
        }

        $result = scandir($this->conf['path'] . 'config');
        foreach ($result as $filename) {
            if (!in_array($filename, ['.', '..'], true)) {
                if (is_dir($this->conf['path'] . 'config' . DS . $filename)) {
                    $folder = new Folder($this->conf['path'] . 'config' . DS . $filename);
                    $folder->delete();
                    unset($folder);
                }
            }
        }

        //Delete all SAT Configs
        if (is_array($this->Satellites) && !empty($this->Satellites)) {
            foreach ($this->Satellites as $satellite) {
                foreach (['config', 'check_mk'] as $baseDir) {
                    if (is_dir($this->conf['satellite_path'] . $satellite->get('id') . DS . $baseDir)) {
                        $result = scandir($this->conf['satellite_path'] . $satellite->get('id') . DS . $baseDir);
                        foreach ($result as $dirname) {
                            if (!in_array($dirname, ['.', '..'], true)) {
                                if (is_dir($this->conf['satellite_path'] . $satellite->get('id') . DS . $baseDir . DS . $dirname)) {
                                    $folder = new Folder($this->conf['satellite_path'] . $satellite->get('id') . DS . $baseDir . DS . $dirname);
                                    $folder->delete();
                                    unset($folder);
                                } else if (is_file($this->conf['satellite_path'] . $satellite->get('id') . DS . $baseDir . DS . $dirname)) {
                                    unlink($this->conf['satellite_path'] . $satellite->get('id') . DS . $baseDir . DS . $dirname);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param null|resource $file
     *
     * @return string
     */
    public function fileHeader($file = null) {
        $header = ";#########################################################################
;#    DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN  #
;#                                                                       #
;#                   File generated by openITCOCKPIT                     #
;#                                                                       #
;#                        Created: " . date('d.m.Y H:i') . "                      #
;#########################################################################

;Weblinks:
; https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/4/en/objectdefinitions.html
; https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/4/en/objecttricks.html
\n";
        if (is_resource($file)) {
            fwrite($file, $header);
        } else {
            return $header;
        }
    }

    /**
     * @param null|resource $file
     *
     * @return string
     */
    public function hashFileHeader($file = null) {
        $header = "#########################################################################
#    DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN  #
#                                                                       #
#                   File generated by openITCOCKPIT                     #
#                                                                       #
#                        Created: " . date('d.m.Y H:i') . "                      #
#########################################################################
#
#Weblinks:
# https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/4/en/configmain.html#resource_file
# https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/4/en/macrolist.html#user
";
        if (is_resource($file)) {
            fwrite($file, $header);
        } else {
            return $header;
        }
    }

    /**
     * @param string $str
     * @param int $deep
     * @param null|string $value
     * @param bool $newline
     *
     * @return string
     */
    public function addContent($str, $deep = 1, $value = null, $newline = true) {
        $c = "";
        $i = 0;
        while ($i < $deep) {
            $c .= '    ';
            $i++;
        }
        $c .= $str;

        if ($value !== null) {
            while ((strlen($c) < 40)) {
                $c .= ' ';
            }
        }

        if ($value !== null) {
            $c .= $value;
        }

        if ($newline === true) {
            $c .= PHP_EOL;
        }

        return $c;
    }

    public function escapeLastBackslash($str = '') {
        if (mb_substr($str, -1) === '\\') {
            $str = sprintf('%s\\', $str); //Add a \ to the end of the string - because last char is a \
        }
        return $str;
    }

    /**
     * @param string $str
     * @return string
     */
    public function removeNewlines(string $str = '') {
        return str_replace(["\r\n", "\n", "\r"], ' ', $str);
    }

    private function createMissingOitcAgentActiveChecks() {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        try {
            $servicetemplate = $ServicetemplatesTable->getServicetemplateByName('OITC_AGENT_ACTIVE', OITC_AGENT_SERVICE);
            $servicetemplateId = $servicetemplate->get('id');
            $hosts = $HostsTable->getHostsThatUseOitcAgentInPullModeForExport();

            foreach ($hosts as $host) {
                $usedServicetemplateIds = Hash::combine($host['services'], '{n}.servicetemplate_id', '{n}.servicetemplate_id');

                //Make sure the host has agent services and has not the ACTIVE check already
                if (!empty($host['services']) && !isset($usedServicetemplateIds[$servicetemplateId])) {
                    // Create CHECK_AGENT_ACTIVE

                    $serviceData = [
                        'uuid'               => UUID::v4(),
                        'host_id'            => $host['id'],
                        'servicetemplate_id' => $servicetemplateId,
                        'service_type'       => OITC_AGENT_SERVICE,

                        'flap_detection_enabled' => 0
                    ];

                    $service = $ServicesTable->newEntity($serviceData);
                    $ServicesTable->save($service);
                }
            }

        } catch (\Exception $e) {
            debug($e->getMessage());
            //Ignore error while export
        }
    }

    /**
     * @param int $satelliteId
     */
    private function createOitcAgentJsonConfig(int $satelliteId) {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var ProxiesTable $ProxiesTable */
        $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        $proxySettings = $ProxiesTable->getSettings();
        $proxyAddress = $proxySettings['ipaddress'] . ':' . $proxySettings['port'];

        $isSystemsettingsProxyEnabled = false;
        if ($proxySettings['enabled']) {
            $isSystemsettingsProxyEnabled = true;
        }

        $hosts = $HostsTable->getHostsThatUseOitcAgentForExport($satelliteId);
        if (empty($hosts)) {
            return;
        }

        $servicetemplate = $ServicetemplatesTable->getServicetemplateByName('OITC_AGENT_ACTIVE', OITC_AGENT_SERVICE);
        $servicetemplateId = $servicetemplate->get('id');

        if ($satelliteId === 0) {
            // Generate config file for the Master System
            $configDir = '/opt/openitc/receiver/etc/';
            $configFile = sprintf('/opt/openitc/receiver/etc/production_%s.json', date('Y-m-d-H-i-s'));
            $outfile = '/opt/openitc/receiver/etc/production.json';
        } else {
            //Generate config file for a satellite
            $configDir = '/opt/openitc/nagios/satellites/' . $satelliteId;
            $configFile = '/opt/openitc/nagios/satellites/' . $satelliteId . '/agent_production.json';
            $outfile = $configFile;
        }
        $jsonConfig = [];
        foreach ($hosts as $host) {
            $hostUuid = $host['uuid'];
            $agentConfigAsJsonFromDatabase = $host['_matchingData']['Agentconfigs']['config'];
            $isOldAgent1Config = false;

            if ($agentConfigAsJsonFromDatabase === '') {
                // DB record exists but no json config
                // Old 1.x agent config

                $record = $AgentconfigsTable->getConfigByHostId($host['id']);
                $agentConfigAsJsonFromDatabase = $record->config;
                $isOldAgent1Config = true;
            }

            $AgentConfiguration = new AgentConfiguration();
            $config = $AgentConfiguration->unmarshal($agentConfigAsJsonFromDatabase);
            if ($isOldAgent1Config === true && isset($record)) {
                // Migrate old config from agent 1.x to 3.x
                $config['int']['bind_port'] = (int)$record->port;
                $config['bool']['use_http_basic_auth'] = $record->basic_auth;
                $config['string']['username'] = $record->username;
                $config['string']['password'] = $record->password;
                $config['int']['bind_port'] = (int)$record->port;
                $config['bool']['use_proxy'] = $record->proxy;
                $config['bool']['enable_push_mode'] = false;
                if ($record->push_noticed) {
                    $config['bool']['enable_push_mode'] = true;
                }
            }
            unset($record);

            $services = $ServicesTable->getAllOitcAgentServicesByHostIdForExport($host['id']);
            if (!empty($services)) {
                //Host is Agent host and has agent services - write to config

                if ($config['bool']['enable_push_mode'] === true) {
                    // Agent is running in Push Mode
                    $jsonConfig[$hostUuid] = [
                        'name'    => $host['name'],
                        'address' => $host['address'],
                        'uuid'    => $hostUuid,
                        'mode'    => 'push',
                        'checks'  => []
                    ];
                } else {
                    // Host is running in Pull Mode
                    $proxy = false;
                    if ($config['bool']['use_proxy'] && $isSystemsettingsProxyEnabled) {
                        $proxy = $proxyAddress;
                    }

                    $jsonConfig[$hostUuid] = [
                        'name'        => $host['name'],
                        'address'     => $host['address'],
                        'uuid'        => $hostUuid,
                        'port'        => $config['int']['bind_port'],
                        'proxy'       => $proxy,
                        'use_autossl' => $config['bool']['use_autossl'],
                        'use_https'   => $config['bool']['use_https'],
                        'insecure'    => $config['bool']['use_https_verify'] === false,
                        'basic_auth'  => $config['bool']['use_http_basic_auth'],
                        'username'    => $config['string']['username'],
                        'password'    => $config['string']['password'],
                        'mode'        => 'pull',
                        'checks'      => []
                    ];
                }

                foreach ($services as $service) {
                    // Ignore OITC_AGENT_ACTIVE service
                    if ($service['servicetemplate_id'] == $servicetemplateId) {
                        continue;
                    }

                    $servicename = $service['name'];
                    if ($servicename === null || $servicename === '') {
                        $servicename = $service['servicetemplate']['name'];
                    }

                    $jsonConfig[$hostUuid]['checks'][] = [
                        'plugin'      => $service['servicetemplate']['agentcheck']['plugin_name'],
                        'servicename' => $servicename,
                        'uuid'        => $service['uuid'],
                        'args'        => $service['args_for_config']
                    ];
                }
            }
        }

        // Store new openitcockpit-receiver json configuration
        file_put_contents($configFile, json_encode($jsonConfig, JSON_PRETTY_PRINT));

        if ($configFile !== $outfile) {
            if (file_exists($outfile)) {
                unlink($outfile);
            }

            symlink($configFile, $outfile);
        }

        // Delete old files (Only on the Master System)
        if ($satelliteId > 0) {
            $Finder = new Finder();
            $Finder
                ->name('*.json')
                ->date('until 10 days ago');
            foreach ($Finder->in($configDir) as $file) {
                /** @var SplFileInfo $file */
                unlink($file->getRealPath());
            }
        }

    }
}
