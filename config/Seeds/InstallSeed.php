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

declare(strict_types=1);

use Cake\ORM\TableRegistry;

/**
 * Class InstallSeed
 *
 * Created:
 * manually
 *
 * Apply:
 * oitc migrations seed
 */
class InstallSeed extends \Migrations\BaseSeed {
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     *
     * @return void
     * @throws Exception
     */
    public function run(): void {
        //       !!! IMPORTANT !!!
        // Make sure to keep the right order
        // Same as in DumpCommand

        $MacrosImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Macro(
            TableRegistry::getTableLocator()->get('Macros')
        );
        $MacrosImporter->import();

        $CronjobsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Cronjob(
            TableRegistry::getTableLocator()->get('Cronjobs')
        );
        $CronjobsImporter->import();

        $SystemsettingsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Systemsetting(
            TableRegistry::getTableLocator()->get('Systemsettings')
        );
        $SystemsettingsImporter->import();

        $CommandsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Commands(
            TableRegistry::getTableLocator()->get('Commands')
        );
        $CommandsImporter->import();

        $ContainersImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Container(
            TableRegistry::getTableLocator()->get('Containers')
        );
        $ContainersImporter->import();

        $TimeperiodsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Timeperiod(
            TableRegistry::getTableLocator()->get('Timeperiods')
        );
        $TimeperiodsImporter->import();

        $ContactsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Contact(
            TableRegistry::getTableLocator()->get('Contacts')
        );
        $ContactsImporter->import();

        $UsergroupsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Usergroup(
            TableRegistry::getTableLocator()->get('Usergroups')
        );
        $UsergroupsImporter->import();

        $HosttemplatesImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Hosttemplate(
            TableRegistry::getTableLocator()->get('Hosttemplates')
        );
        $HosttemplatesImporter->import();

        $HostsImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Host(
            TableRegistry::getTableLocator()->get('Hosts')
        );
        $HostsImporter->import();

        $ServicetemplatesImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Servicetemplate(
            TableRegistry::getTableLocator()->get('Servicetemplates')
        );
        $ServicetemplatesImporter->import();

        $ServicesImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Service(
            TableRegistry::getTableLocator()->get('Services')
        );
        $ServicesImporter->import();

        //Check for openITCOCKPIT Agent commands and service templates
        $AgentImporter = new \itnovum\openITCOCKPIT\InitialDatabase\Agent(
            TableRegistry::getTableLocator()->get('Commands'),
            TableRegistry::getTableLocator()->get('Hosttemplates'),
            TableRegistry::getTableLocator()->get('Servicetemplates'),
            TableRegistry::getTableLocator()->get('Agentchecks')
        );
        $AgentImporter->import();

        /******************* Configuration Wizard staff *******************/

        /** @var  $MysqlWizardImporter */
        //Check for openITCOCKPIT MySql Wizard commands and service templates
        $MysqlWizardImporter = new \itnovum\openITCOCKPIT\InitialDatabase\MysqlWizard(
            TableRegistry::getTableLocator()->get('Commands'),
            TableRegistry::getTableLocator()->get('Servicetemplates'),
            TableRegistry::getTableLocator()->get('WizardAssignments')
        );
        $MysqlWizardImporter->import();

        // Add new cronjobs to database
        //Cronjobs
        $table = $this->table('cronjobs');

        // Add all (new) cronjobs to this array
        $data = [
            [
                'task'     => 'LdapGroupImport',
                'plugin'   => 'Core',
                'interval' => '1440',
                'enabled'  => '1',
            ],
        ];

        //Check if records exists
        foreach ($data as $index => $record) {
            $QueryBuilder = $this->getAdapter()->getSelectBuilder();

            $stm = $QueryBuilder->select('*')
                ->from($table->getName())
                ->where([
                    'plugin' => $record['plugin'],
                    'task'   => $record['task']
                ])
                ->execute();
            $result = $stm->fetchAll('assoc');

            if (empty($result)) {
                $table->insert($record)->save();
            }
        }

        // ITC-3535 Add UUID to existing Statuspages
        /** @var \App\Model\Table\StatuspagesTable $StatuspagesTable */
        $StatuspagesTable = TableRegistry::getTableLocator()->get('Statuspages');
        $StatuspagesTable->addMissingUuidToStatuspages();
    }
}
