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

namespace App\itnovum\openITCOCKPIT\Database;


use App\Model\Table\ExportsTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Class Backup
 * @package App\itnovum\openITCOCKPIT\Database
 */
class Backup {


    /**
     * @var string
     */
    private $myCnf = '/opt/openitc/etc/mysql/dump.cnf';

    /**
     * @var string
     */
    private $oitcMyCnf = '/opt/openitc/etc/mysql/mysql.cnf';

    /**
     * Tables that be ignored during mysql dump
     * @var array
     */
    private $ignore = [
        'nagios_acknowledgements',
        'nagios_commands',
        'nagios_commenthistory',
        'nagios_comments',
        'nagios_configfiles',
        'nagios_configfilevariables',
        'nagios_conninfo',
        'nagios_contact_addresses',
        'nagios_contact_notificationcommands',
        'nagios_contactgroup_members',
        'nagios_contactgroups',
        'nagios_contactnotificationmethods',
        'nagios_contactnotifications',
        'nagios_contacts',
        'nagios_contactstatus',
        'nagios_customvariables',
        'nagios_customvariablestatus',
        'nagios_dbversion',
        'nagios_downtimehistory',
        'nagios_eventhandlers',
        'nagios_externalcommands',
        'nagios_flappinghistory',
        'nagios_host_contactgroups',
        'nagios_host_contacts',
        'nagios_host_parenthosts',
        'nagios_hostchecks',
        'nagios_hostdependencies',
        'nagios_hostescalation_contactgroups',
        'nagios_hostescalation_contacts',
        'nagios_hostescalations',
        'nagios_hostgroup_members',
        'nagios_hostgroups',
        'nagios_hosts',
        'nagios_hoststatus',
        'nagios_instances',
        'nagios_logentries',
        'nagios_notifications',
        'nagios_processevents',
        'nagios_programstatus',
        'nagios_runtimevariables',
        'nagios_scheduleddowntime',
        'nagios_service_contactgroups',
        'nagios_service_contacts',
        'nagios_service_parentservices',
        'nagios_servicechecks',
        'nagios_servicedependencies',
        'nagios_serviceescalation_contactgroups',
        'nagios_serviceescalation_contacts',
        'nagios_serviceescalations',
        'nagios_servicegroup_members',
        'nagios_servicegroups',
        'nagios_services',
        'nagios_servicestatus',
        'nagios_statehistory',
        'nagios_systemcommands',
        'nagios_timedeventqueue',
        'nagios_timedevents',
        'nagios_timeperiod_timeranges',
        'nagios_timeperiods',

        'statusengine_dbversion',
        'statusengine_host_acknowledgements',
        'statusengine_host_downtimehistory',
        'statusengine_host_notifications',
        'statusengine_host_notifications_log',
        'statusengine_host_scheduleddowntimes',
        'statusengine_host_statehistory',
        'statusengine_hostchecks',
        'statusengine_hoststatus',
        'statusengine_logentries',
        'statusengine_nodes',
        'statusengine_perfdata',
        'statusengine_service_acknowledgements',
        'statusengine_service_downtimehistory',
        'statusengine_service_notifications',
        'statusengine_service_notifications_log',
        'statusengine_service_scheduleddowntimes',
        'statusengine_service_statehistory',
        'statusengine_servicechecks',
        'statusengine_servicestatus',
        'statusengine_tasks',
        'statusengine_users',

        'customalerts',
        'customalert_statehistory',

        'sla_availability_status_hosts_log',
        'sla_availability_status_services_log',
        'sla_host_outages',
        'sla_service_outages'
    ];

    /**
     * Backup constructor.
     * @throws \Exception
     */
    public function __construct() {
        if (!file_exists($this->myCnf)) {
            Log::error(sprintf('MySQL Config file "%s" not found!', $this->myCnf));
            throw new \Exception(sprintf('MySQL Config file "%s" not found!', $this->myCnf));
        }

        if (!file_exists($this->oitcMyCnf)) {
            Log::error(sprintf('MySQL Config file "%s" not found!', $this->oitcMyCnf));
            throw new \Exception(sprintf('MySQL Config file "%s" not found!', $this->oitcMyCnf));
        }
    }

    /**
     * @param string $backupFile
     * @return array
     */
    public function createMysqlDump($backupFile) {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $db = $SystemsettingsTable->getConnection()->config()['database'];

        // ITC-2921
        // MySQL Bug: https://bugs.mysql.com/bug.php?id=109685
        // As with mysqldump 8.0.32 --single-transaction requires RELOAD or FLUSH_TABLES privilege(s) which the openitcockpit user does not have
        // So for now the workaround is to remove "--single-transaction"
        $baseCmd = 'mysqldump --defaults-extra-file=%s --databases %s --flush-privileges --triggers --routines --no-tablespaces --events --hex-blob \\%s %s > %s';
        $ignore = [];
        foreach ($this->ignore as $table) {
            $ignore[] = sprintf('--ignore-table=%s.%s \\%s', $db, $table, PHP_EOL);
        }

        $cmd = sprintf(
            $baseCmd,
            $this->myCnf,
            $db,
            PHP_EOL,
            implode(' ', $ignore),
            $backupFile
        );

        $output = [];
        exec($cmd, $output, $returncode);
        $return = [
            'output'     => $output,
            'returncode' => $returncode,
        ];

        return $return;
    }

    /**
     * @param string $dumpFile
     * @return array
     * @throws \Exception
     */
    public function restoreMysqlDump($dumpFile) {
        if (!file_exists($dumpFile)) {
            throw new \Exception(sprintf('File "%s" does not exists', $dumpFile));
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $db = $SystemsettingsTable->getConnection()->config()['database'];

        $baseCmd = 'mysql --defaults-extra-file=%s %s < %s';

        $cmd = sprintf(
            $baseCmd,
            $this->oitcMyCnf,
            $db,
            $dumpFile
        );

        $output = [];
        exec($cmd, $output, $returncode);

        /** @var ExportsTable $ExportsTable */
        $ExportsTable = TableRegistry::getTableLocator()->get('Exports');
        $ExportsTable->deleteAll([]);

        $return = [
            'output'     => $output,
            'returncode' => $returncode,
        ];

        return $return;
    }

}
