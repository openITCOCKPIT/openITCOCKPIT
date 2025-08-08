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

namespace App\Command;

use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsersTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DistributeModule\Model\Entity\Satellite;
use EventcorrelationModule\Model\Table\EventcorrelationSettingsTable;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\SetupShell\MailConfigurator;
use itnovum\openITCOCKPIT\SetupShell\MailConfigValue;
use itnovum\openITCOCKPIT\SetupShell\MailConfigValueInt;
use NickBeen\ProgressBar\ProgressBar;

/**
 * Update3To4 command.
 */
class Update3To4Command extends Command {

    /**
     * @var int
     */
    private $limit = 1000;

    /**
     * @var ConsoleIo
     */
    private $io;

    /**
     * Statusengine Node Name
     * @var string
     */
    private $nodeName = 'openITCOCKPIT';

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
        $parser->addOption('email-config', [
            'help'    => __('Migrate openITCOCKPIT V3 E-Mail configuration'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('activate-users', [
            'help'    => __('Enable users from the file /root/.openitcockpit_active_user_migration.json'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('reset-all-passwords', [
            'help'    => __('Send a new random password to all local users per mail.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-notifications', [
            'help'    => __('Migrate notification history records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-statehistory', [
            'help'    => __('Migrate statehistory records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-acknowledgements', [
            'help'    => __('Migrate acknowledgements history records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-downtimes', [
            'help'    => __('Migrate downtimes history records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-hostchecks', [
            'help'    => __('Migrate hostchecks records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-servicechecks', [
            'help'    => __('Migrate servicechecks records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-logentries', [
            'help'    => __('Migrate logentries records from nagios_ to statusengine_ tables.'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('evc-use-statusengine', [
            'help'    => __('Set the Statusengine Broker as default submit method for EVC results'),
            'boolean' => true,
            'default' => false
        ]);

        $parser->addOption('migrate-satellites', [
            'help'    => __('Migrate the SSH configuration of V3 satellite systems'),
            'boolean' => true,
            'default' => false
        ]);

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
        $this->io = $io;

        if ($args->getOption('email-config') === true) {
            $this->migrateEmailConfiguration();
        }

        if ($args->getOption('activate-users') === true) {
            $io->out('Active users...    ', 0);
            $this->setActiveUsersFromJson();
            $io->success('done');
        }

        if ($args->getOption('reset-all-passwords') === true) {
            $io->out('Reset users passwords...    ');
            $this->resetAllUserPasswords();
            $io->success('done');
        }

        //Set timezone to UTC to migrate between mysql DATETIME and php timestamp strtotime and so on...
        if ($args->getOption('migrate-notifications') === true) {
            date_default_timezone_set('UTC');
            $this->migrateHostNotifications();
            $this->migrateServiceNotifications();
        }

        if ($args->getOption('migrate-statehistory') === true) {
            date_default_timezone_set('UTC');
            $this->migrateHostStatehistory();
            $this->migrateServiceStatehistory();
        }

        if ($args->getOption('migrate-acknowledgements') === true) {
            date_default_timezone_set('UTC');
            $this->migrateHostAcknowledgements();
            $this->migrateServiceAcknowledgements();
        }

        if ($args->getOption('migrate-downtimes') === true) {
            date_default_timezone_set('UTC');
            $this->migrateHostDowntimes();
            $this->migrateServiceDowntimes();
        }

        if ($args->getOption('migrate-hostchecks') === true) {
            date_default_timezone_set('UTC');
            $this->migrateHostChecks();
        }

        if ($args->getOption('migrate-servicechecks') === true) {
            date_default_timezone_set('UTC');
            $this->migrateServiceChecks();
        }

        if ($args->getOption('migrate-logentries') === true) {
            date_default_timezone_set('UTC');
            $this->migrateLogentries();
        }

        if ($args->getOption('evc-use-statusengine') === true) {
            $this->changeEvcSubmitMethod();
        }

        if ($args->getOption('migrate-satellites') === true) {
            $this->migrateSatelliteSystems();
        }
    }

    public function migrateEmailConfiguration() {
        if (!file_exists('/etc/openitcockpit/app/Config/email.php')) {
            $this->io->error('File /etc/openitcockpit/app/Config/email.php not found.');
            return;
        }

        require_once '/etc/openitcockpit/app/Config/email.php';
        $OldEmailConfig = new \EmailConfig();


        $mailHost = new MailConfigValue($OldEmailConfig->default['host']);
        $mailPort = new MailConfigValueInt((int)$OldEmailConfig->default['port']);
        $mailUsername = new MailConfigValue($OldEmailConfig->default['username'] ?? null);
        $mailPassword = new MailConfigValue($OldEmailConfig->default['password'] ?? null);
        $mailConfigurator = new MailConfigurator(
            $mailHost, $mailPort, $mailUsername, $mailPassword
        );

        $file = fopen(CONFIG . 'email.php', 'w+');
        fwrite($file, $mailConfigurator->getConfig());
        fclose($file);
        $this->io->success(__('Mail configuration "{0}" saved successfully.', CONFIG . 'email.php'));
    }

    public function setActiveUsersFromJson() {
        $filename = '/root/.openitcockpit_active_user_migration.json';
        if (!file_exists($filename)) {
            return false;
        }

        $users = json_decode(file_get_contents($filename), true);

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if (!is_array($users)) {
            $this->io->warning('No active openITCOCKPIT Version 3 users found!');
            return false;
        }

        foreach ($users as $user) {
            if ($UsersTable->existsById($user['id'])) {
                $userEntity = $UsersTable->get($user['id']);
                $userEntity->set('is_active', 1);
                $UsersTable->save($userEntity);
            }
        }

        //Remove json file
        unlink($filename);
    }

    public function resetAllUserPasswords() {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $systemsettings = $SystemsettingsTable->findAsArray();

        $users = $UsersTable->find()
            ->where(['password !=' => ''])
            ->disableHydration()
            ->all();

        $users = $users->toArray();

        foreach ($users as $userArray) {
            $user = $UsersTable->get($userArray['id']);
            $newPassword = $UsersTable->generatePassword();

            $user->set('password', $newPassword);

            $Logo = new Logo();

            $Mailer = new Mailer();
            $Mailer->setFrom($systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'], $systemsettings['MONITORING']['MONITORING.FROM_NAME']);
            $Mailer->addTo($user->get('email'));
            $Mailer->setSubject(__('Your ') . $systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'] . __(' got reset!'));
            $Mailer->setEmailFormat('text');
            $Mailer->setAttachments([
                'logo.png' => [
                    'file'      => $Logo->getSmallLogoPdfPath(),
                    'mimetype'  => 'image/png',
                    'contentId' => '100'
                ]
            ]);
            $Mailer->viewBuilder()
                ->setTemplate('reset_password')
                ->setVar('systemname', $systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'])
                ->setVar('newPassword', $newPassword);

            $user->set('password', $newPassword);

            $UsersTable->save($user);
            if ($user->hasErrors()) {
                $this->io->error('Could not reset password for user: ' . $user->email);
                continue;
            }

            $Mailer->deliver();
            $this->io->success(__('New password was send to {0}', $user->email));
        }
    }


    public function migrateHostNotifications() {
        $this->migrateParitions('nagios_notifications', 'statusengine_host_notifications');

        $hostNotificationsCount = $this->getHostNotificationsMigrationQuery(0, true);
        $numberOfSelects = ceil($hostNotificationsCount / $this->limit);

        if ($hostNotificationsCount == 0) {
            return;
        }

        $this->io->out('Migrating host notifications');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
      INSERT IGNORE INTO statusengine_host_notifications
      (hostname, contact_name, command_name, state, start_time, start_time_usec, end_time, reason_type, output )
      VALUES%s";

        $baseValues = '(?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getHostNotificationsMigrationQuery($offset) as $record) {
                $values[] = $baseValues;
                $params[] = $record['Hosts']['uuid'];
                $params[] = $record['Contacts']['uuid'];
                $params[] = $record['Commands']['uuid'];
                $params[] = $record['state'];
                $params[] = strtotime($record['start_time']);
                $params[] = $this->getMicrotime();
                $params[] = strtotime($record['end_time']);
                $params[] = $record['notification_reason'];
                $params[] = $record['output'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }

        $ProgressBar->finish();

        $this->io->out('');
    }

    public function migrateServiceNotifications() {
        $this->migrateParitions('nagios_notifications', 'statusengine_service_notifications');


        $serviceNotificationsCount = $this->getServiceNotificationsMigrationQuery(0, true);
        $numberOfSelects = ceil($serviceNotificationsCount / $this->limit);

        if ($serviceNotificationsCount == 0) {
            return;
        }

        $this->io->out('Migrating service notifications');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
      INSERT IGNORE INTO statusengine_service_notifications
      (hostname, service_description, contact_name, command_name, state, start_time, start_time_usec, end_time, reason_type, output )
      VALUES%s";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getServiceNotificationsMigrationQuery($offset) as $record) {
                $values[] = $baseValues;
                $params[] = $record['Hosts']['uuid'];
                $params[] = $record['Services']['uuid'];
                $params[] = $record['Contacts']['uuid'];
                $params[] = $record['Commands']['uuid'];
                $params[] = $record['state'];
                $params[] = strtotime($record['start_time']);
                $params[] = $this->getMicrotime();
                $params[] = strtotime($record['end_time']);
                $params[] = $record['notification_reason'];
                $params[] = $record['output'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateHostStatehistory() {
        $this->migrateParitions('nagios_statehistory', 'statusengine_host_statehistory');


        $sql = "INSERT IGNORE INTO statusengine_host_statehistory
                (hostname, state_time, state_time_usec, state, state_change, is_hardstate, current_check_attempt, max_check_attempts, last_state, last_hard_state, output, long_output)
                    SELECT nagios_objects.name1,
                        UNIX_TIMESTAMP(nagios_statehistory.state_time),
                        LPAD(FLOOR(RAND() * 999999.99), 6, 0),
                        state,
                        state_change,
                        state_type,
                        current_check_attempt,
                        max_check_attempts,
                        last_state,
                        last_hard_state,
                        output,
                        long_output
                    FROM nagios_statehistory
                    INNER JOIN nagios_objects ON nagios_objects.object_id = nagios_statehistory.object_id
                    WHERE nagios_objects.objecttype_id = 1;";

        $this->io->out('Migrating host statehistory');
        $this->io->info('This could take up to several hours depending on your systems performance.');
        $this->io->out('');
        $this->io->info('No progress bar will be shown.');

        $start = time();
        $connection = ConnectionManager::get('default');
        $statement = $connection->execute($sql);
        $end = time();

        $UserTime = new UserTime(date_default_timezone_get(), 'Y-m-d H:i:s');

        $this->io->out(sprintf(
            'Migration done. This operation took: %s',
            $UserTime->secondsInHumanShort(($end - $start))
        ));
        $this->io->out('');
    }

    public function migrateServiceStatehistory() {
        $this->migrateParitions('nagios_statehistory', 'statusengine_service_statehistory');


        $sql = "INSERT IGNORE INTO statusengine_service_statehistory
                (hostname, service_description, state_time, state_time_usec, state, state_change, is_hardstate, current_check_attempt, max_check_attempts, last_state, last_hard_state, output, long_output)
                    SELECT nagios_objects.name1,
                        nagios_objects.name2,
                        UNIX_TIMESTAMP(nagios_statehistory.state_time),
                        LPAD(FLOOR(RAND() * 999999.99), 6, 0),
                        state,
                        state_change,
                        state_type,
                        current_check_attempt,
                        max_check_attempts,
                        last_state,
                        last_hard_state,
                        output,
                        long_output
                    FROM nagios_statehistory
                    INNER JOIN nagios_objects ON nagios_objects.object_id = nagios_statehistory.object_id
                    WHERE nagios_objects.objecttype_id = 2
                    ;";

        $this->io->out('Migrating service statehistory');
        $this->io->info('This could take up to several hours depending on your systems performance.');
        $this->io->out('');
        $this->io->info('No progress bar will be shown.');

        $start = time();
        $connection = ConnectionManager::get('default');
        $statement = $connection->execute($sql);
        $end = time();

        $UserTime = new UserTime(date_default_timezone_get(), 'Y-m-d H:i:s');

        $this->io->out(sprintf(
            'Migration done. This operation took: %s',
            $UserTime->secondsInHumanShort(($end - $start))
        ));
        $this->io->out('');
    }

    public function migrateHostAcknowledgements() {
        $hostAcknowledgementsCount = $this->getHostAcknowledgementsMigrationQuery(0, true);
        $numberOfSelects = ceil($hostAcknowledgementsCount / $this->limit);

        if ($hostAcknowledgementsCount == 0) {
            return;
        }

        $this->io->out('Migrating host acknowledgements');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_host_acknowledgements
        (hostname, state, author_name, comment_data, entry_time, entry_time_usec, acknowledgement_type, is_sticky, persistent_comment, notify_contacts)
        VALUES%s;";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getHostAcknowledgementsMigrationQuery($offset) as $record) {
                $values[] = $baseValues;

                $params[] = $record['Objects']['name1'];
                $params[] = $record['state'];
                $params[] = $record['author_name'];
                $params[] = $record['comment_data'];
                $params[] = strtotime($record['entry_time']);
                $params[] = $this->getMicrotime();
                $params[] = $record['acknowledgement_type'];
                $params[] = $record['is_sticky'];
                $params[] = $record['persistent_comment'];
                $params[] = $record['notify_contacts'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateServiceAcknowledgements() {
        $serviceAcknowledgementsCount = $this->getServiceAcknowledgementsMigrationQuery(0, true);
        $numberOfSelects = ceil($serviceAcknowledgementsCount / $this->limit);

        if ($serviceAcknowledgementsCount == 0) {
            return;
        }

        $this->io->out('Migrating service acknowledgements');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_service_acknowledgements
        (hostname, service_description, state, author_name, comment_data, entry_time, entry_time_usec, acknowledgement_type, is_sticky, persistent_comment, notify_contacts)
        VALUES%s;";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getServiceAcknowledgementsMigrationQuery($offset) as $record) {
                $values[] = $baseValues;

                $params[] = $record['Objects']['name1'];
                $params[] = $record['Objects']['name2'];
                $params[] = $record['state'];
                $params[] = $record['author_name'];
                $params[] = $record['comment_data'];
                $params[] = strtotime($record['entry_time']);
                $params[] = $this->getMicrotime();
                $params[] = $record['acknowledgement_type'];
                $params[] = $record['is_sticky'];
                $params[] = $record['persistent_comment'];
                $params[] = $record['notify_contacts'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateHostDowntimes() {
        $this->migrateParitions('nagios_downtimehistory', 'statusengine_host_downtimehistory');

        $hostDowntimesCount = $this->getHostDowntimesMigrationQuery(0, true);
        $numberOfSelects = ceil($hostDowntimesCount / $this->limit);

        if ($hostDowntimesCount == 0) {
            return;
        }

        $this->io->out('Migrating host downtimes');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_host_downtimehistory
        (hostname, internal_downtime_id, scheduled_start_time, node_name, entry_time, entry_time_usec, author_name, comment_data, triggered_by_id, is_fixed, duration, scheduled_end_time, was_started, actual_start_time, actual_end_time, was_cancelled)
        VALUES%s";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getHostDowntimesMigrationQuery($offset) as $record) {
                $values[] = $baseValues;
                $params[] = $record['Objects']['name1'];
                $params[] = $record['internal_downtime_id'];
                $params[] = strtotime($record['scheduled_start_time']);
                $params[] = $this->nodeName;
                $params[] = strtotime($record['entry_time']);
                $params[] = $this->getMicrotime();
                $params[] = $record['author_name'];
                $params[] = $record['comment_data'];
                $params[] = $record['triggered_by_id'];
                $params[] = $record['is_fixed'];
                $params[] = $record['duration'];
                $params[] = strtotime($record['scheduled_end_time']);
                $params[] = $record['was_started'];
                $params[] = strtotime($record['actual_start_time']);
                $params[] = strtotime($record['actual_end_time']);
                $params[] = $record['was_cancelled'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateServiceDowntimes() {
        $this->migrateParitions('nagios_downtimehistory', 'statusengine_service_downtimehistory');

        $serviceDowntimesCount = $this->getServiceDowntimesMigrationQuery(0, true);
        $numberOfSelects = ceil($serviceDowntimesCount / $this->limit);

        if ($serviceDowntimesCount == 0) {
            return;
        }

        $this->io->out('Migrating service downtimes');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_service_downtimehistory
        (hostname, service_description, internal_downtime_id, scheduled_start_time, node_name, entry_time, entry_time_usec, author_name, comment_data, triggered_by_id, is_fixed, duration, scheduled_end_time, was_started, actual_start_time, actual_end_time, was_cancelled)
        VALUES%s";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getServiceDowntimesMigrationQuery($offset) as $record) {
                $values[] = $baseValues;
                $params[] = $record['Objects']['name1'];
                $params[] = $record['Objects']['name2'];
                $params[] = $record['internal_downtime_id'];
                $params[] = strtotime($record['scheduled_start_time']);
                $params[] = $this->nodeName;
                $params[] = strtotime($record['entry_time']);
                $params[] = $this->getMicrotime();
                $params[] = $record['author_name'];
                $params[] = $record['comment_data'];
                $params[] = $record['triggered_by_id'];
                $params[] = $record['is_fixed'];
                $params[] = $record['duration'];
                $params[] = strtotime($record['scheduled_end_time']);
                $params[] = $record['was_started'];
                $params[] = strtotime($record['actual_start_time']);
                $params[] = strtotime($record['actual_end_time']);
                $params[] = $record['was_cancelled'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateHostChecks() {
        $this->migrateParitions('nagios_hostchecks', 'statusengine_hostchecks');

        $hostChecksCount = $this->getHostChecksMigrationQuery(0, true);
        $numberOfSelects = ceil($hostChecksCount / $this->limit);

        if ($hostChecksCount == 0) {
            return;
        }

        $this->io->out('Migrating host checks');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_hostchecks
        (hostname, start_time, start_time_usec, state, is_hardstate, end_time, output, timeout, early_timeout, latency, execution_time, perfdata, command, current_check_attempt, max_check_attempts, long_output)
        VALUES%s";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getHostChecksMigrationQuery($offset) as $record) {
                $values[] = $baseValues;

                $params[] = $record['Objects']['name1'];
                $params[] = strtotime($record['start_time']);
                $params[] = $this->getMicrotime();
                $params[] = $record['state'];
                $params[] = $record['state_type'];
                $params[] = strtotime($record['end_time']);
                $params[] = $record['output'];
                $params[] = $record['timeout'];
                $params[] = $record['early_timeout'];
                $params[] = $record['latency'];
                $params[] = $record['execution_time'];
                $params[] = $record['perfdata'];
                $params[] = $record['CommandObject']['name1'];
                $params[] = $record['current_check_attempt'];
                $params[] = $record['max_check_attempts'];
                $params[] = $record['long_output'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateServiceChecks() {
        $this->migrateParitions('nagios_servicechecks', 'statusengine_servicechecks');

        $serviceChecksCount = $this->getServiceChecksMigrationQuery(0, true);
        $numberOfSelects = ceil($serviceChecksCount / $this->limit);

        if ($serviceChecksCount == 0) {
            return;
        }

        $this->io->out('Migrating service checks');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_servicechecks
        (hostname, service_description, start_time, start_time_usec, state, is_hardstate, end_time, output, timeout, early_timeout, latency, execution_time, perfdata, command, current_check_attempt, max_check_attempts, long_output)
        VALUES%s";

        $baseValues = '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getServiceChecksMigrationQuery($offset) as $record) {
                $values[] = $baseValues;

                $params[] = $record['Objects']['name1'];
                $params[] = $record['Objects']['name2'];
                $params[] = strtotime($record['start_time']);
                $params[] = $this->getMicrotime();
                $params[] = $record['state'];
                $params[] = $record['state_type'];
                $params[] = strtotime($record['end_time']);
                $params[] = $record['output'];
                $params[] = $record['timeout'];
                $params[] = $record['early_timeout'];
                $params[] = $record['latency'];
                $params[] = $record['execution_time'];
                $params[] = $record['perfdata'];
                $params[] = $record['CommandObject']['name1'];
                $params[] = $record['current_check_attempt'];
                $params[] = $record['max_check_attempts'];
                $params[] = $record['long_output'];
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    public function migrateLogentries() {
        $this->migrateParitions('nagios_logentries', 'statusengine_logentries');

        $logentriesCount = $this->getLogentriesMigrationQuery(0, true);
        $numberOfSelects = ceil($logentriesCount / $this->limit);

        if ($logentriesCount == 0) {
            return;
        }

        $this->io->out('Migrating logentries');
        $ProgressBar = new ProgressBar(0, $numberOfSelects);
        $ProgressBar->start();

        $query = "
        INSERT IGNORE INTO statusengine_logentries
        (entry_time, logentry_type, logentry_data, node_name)
        VALUES%s";

        $baseValues = '(?,?,?,?)';
        for ($i = 0; $i < $numberOfSelects; $i++) {
            $offset = $this->limit * $i;

            $values = [];
            $params = [];
            foreach ($this->getLogentriesMigrationQuery($offset) as $record) {
                $values[] = $baseValues;

                $params[] = strtotime($record['logentry_time']);
                $params[] = $record['logentry_type'];
                $params[] = $record['logentry_data'];
                $params[] = $this->nodeName;
            }

            $sql = sprintf($query, implode(',', $values));
            $connection = ConnectionManager::get('default');

            $statement = $connection->execute(
                $sql,
                $params
            );

            $ProgressBar->tick();
        }
        $ProgressBar->finish();
        $this->io->out('');
    }

    /**
     * @param int $offset
     * @return mixed
     */
    private function getHostNotificationsMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\NotificationHostsTable $NotificationHostsTable */
        $NotificationHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.NotificationHosts');

        $query = $NotificationHostsTable->find()
            ->select([
                'NotificationHosts.object_id',
                'NotificationHosts.notification_type',
                'NotificationHosts.start_time',
                'NotificationHosts.end_time',
                'NotificationHosts.state',
                'NotificationHosts.output',
                'NotificationHosts.notification_reason',


                'Hosts.id',
                'Hosts.uuid',
                'Hosts.name',

                'Contactnotifications.notification_id',
                'Contactnotifications.contact_object_id',
                'Contactnotifications.start_time',

                'Contacts.id',
                'Contacts.uuid',
                'Contacts.name',

                'Commands.id',
                'Commands.uuid',
                'Commands.name',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = NotificationHosts.object_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->innerJoin(
                ['Contactnotifications' => 'nagios_contactnotifications'],
                ['NotificationHosts.notification_id = Contactnotifications.notification_id']
            )
            ->innerJoin(
                ['ContactObjects' => 'nagios_objects'],
                ['Contactnotifications.contact_object_id = ContactObjects.object_id']
            )
            ->innerJoin(
                ['Contacts' => 'contacts'],
                ['ContactObjects.name1 = Contacts.uuid']
            )
            ->innerJoin(
                ['Contactnotificationmethods' => 'nagios_contactnotificationmethods'],
                ['Contactnotificationmethods.contactnotification_id = Contactnotifications.contactnotification_id']
            )
            ->innerJoin(
                ['CommandObjects' => 'nagios_objects'],
                ['Contactnotificationmethods.command_object_id = CommandObjects.object_id']
            )
            ->innerJoin(
                ['Commands' => 'commands'],
                ['CommandObjects.name1 = Commands.uuid']
            )
            ->where([
                'NotificationHosts.notification_type'   => 0,
                'NotificationHosts.contacts_notified >' => 0
            ])
            ->groupBy(['Contactnotifications.contactnotification_id']);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getServiceNotificationsMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\NotificationServicesTable $NotificationServicesTable */
        $NotificationServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.NotificationServices');

        $query = $NotificationServicesTable->find();
        $query->select([
            'NotificationServices.object_id',
            'NotificationServices.notification_type',
            'NotificationServices.start_time',
            'NotificationServices.end_time',
            'NotificationServices.state',
            'NotificationServices.output',
            'NotificationServices.notification_reason',

            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',

            'Services.id',
            'Services.uuid',
            'Services.name',

            'Servicetemplates.id',
            'Servicetemplates.uuid',
            'Servicetemplates.name',

            'Contactnotifications.notification_id',
            'Contactnotifications.contact_object_id',
            'Contactnotifications.start_time',

            'Contacts.id',
            'Contacts.uuid',
            'Contacts.name',

            'Commands.id',
            'Commands.uuid',
            'Commands.name',
        ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = NotificationServices.object_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->innerJoin(
                ['Services' => 'services'],
                ['Objects.name2 = Services.uuid']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Services.servicetemplate_id = Servicetemplates.id']
            )
            ->innerJoin(
                ['Contactnotifications' => 'nagios_contactnotifications'],
                ['NotificationServices.notification_id = Contactnotifications.notification_id']
            )
            ->innerJoin(
                ['ContactObjects' => 'nagios_objects'],
                ['Contactnotifications.contact_object_id = ContactObjects.object_id']
            )
            ->innerJoin(
                ['Contacts' => 'contacts'],
                ['ContactObjects.name1 = Contacts.uuid']
            )
            ->innerJoin(
                ['Contactnotificationmethods' => 'nagios_contactnotificationmethods'],
                ['Contactnotificationmethods.contactnotification_id = Contactnotifications.contactnotification_id']
            )
            ->innerJoin(
                ['CommandObjects' => 'nagios_objects'],
                ['Contactnotificationmethods.command_object_id = CommandObjects.object_id']
            )
            ->innerJoin(
                ['Commands' => 'commands'],
                ['CommandObjects.name1 = Commands.uuid']
            )
            ->where([
                'NotificationServices.notification_type'   => 1,
                'NotificationServices.contacts_notified >' => 0
            ])
            ->groupBy(['Contactnotifications.contactnotification_id']);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getHostStatehistoryMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\StatehistoryHostsTable $StatehistoryHostsTable */
        $StatehistoryHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.StatehistoryHosts');

        $query = $StatehistoryHostsTable->find();
        $query
            ->select([
                'StatehistoryHosts.state_time',
                'StatehistoryHosts.state',
                'StatehistoryHosts.state_change',
                'StatehistoryHosts.state_type',
                'StatehistoryHosts.current_check_attempt',
                'StatehistoryHosts.max_check_attempts',
                'StatehistoryHosts.last_state',
                'StatehistoryHosts.last_hard_state',
                'StatehistoryHosts.output',
                'StatehistoryHosts.long_output',

                'Objects.name1'
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = StatehistoryHosts.object_id']
            )
            ->where([
                'Objects.objecttype_id' => 1
            ]);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getServiceStatehistoryMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\StatehistoryServicesTable $StatehistoryServicesTable */
        $StatehistoryServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.StatehistoryServices');

        $query = $StatehistoryServicesTable->find();
        $query
            ->select([
                'StatehistoryServices.state_time',
                'StatehistoryServices.state',
                'StatehistoryServices.state_change',
                'StatehistoryServices.state_type',
                'StatehistoryServices.current_check_attempt',
                'StatehistoryServices.max_check_attempts',
                'StatehistoryServices.last_state',
                'StatehistoryServices.last_hard_state',
                'StatehistoryServices.output',
                'StatehistoryServices.long_output',

                'Objects.name1',
                'Objects.name2'
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = StatehistoryServices.object_id']
            )
            ->where([
                'Objects.objecttype_id' => 2
            ]);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getHostAcknowledgementsMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\AcknowledgementHostsTable $AcknowledgementHostsTable */
        $AcknowledgementHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.AcknowledgementHosts');


        $query = $AcknowledgementHostsTable->find();

        $query
            ->select([
                'AcknowledgementHosts.state',
                'AcknowledgementHosts.author_name',
                'AcknowledgementHosts.comment_data',
                'AcknowledgementHosts.entry_time',
                'AcknowledgementHosts.acknowledgement_type',
                'AcknowledgementHosts.is_sticky',
                'AcknowledgementHosts.persistent_comment',
                'AcknowledgementHosts.notify_contacts',

                'Objects.name1',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = AcknowledgementHosts.object_id']
            )
            ->where([
                'Objects.objecttype_id' => 1
            ]);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getServiceAcknowledgementsMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\AcknowledgementServicesTable $AcknowledgementServicesTable */
        $AcknowledgementServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.AcknowledgementServices');


        $query = $AcknowledgementServicesTable->find();

        $query
            ->select([
                'AcknowledgementServices.state',
                'AcknowledgementServices.author_name',
                'AcknowledgementServices.comment_data',
                'AcknowledgementServices.entry_time',
                'AcknowledgementServices.acknowledgement_type',
                'AcknowledgementServices.is_sticky',
                'AcknowledgementServices.persistent_comment',
                'AcknowledgementServices.notify_contacts',

                'Objects.name1',
                'Objects.name2',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = AcknowledgementServices.object_id']
            )
            ->where([
                'Objects.objecttype_id' => 2
            ]);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getHostDowntimesMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\DowntimeHostsTable $DowntimeHostsTable */
        $DowntimeHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.DowntimeHosts');

        $query = $DowntimeHostsTable->find();

        $query
            ->select([
                'DowntimeHosts.internal_downtime_id',
                'DowntimeHosts.scheduled_start_time',
                'DowntimeHosts.entry_time',
                'DowntimeHosts.author_name',
                'DowntimeHosts.comment_data',
                'DowntimeHosts.triggered_by_id',
                'DowntimeHosts.is_fixed',
                'DowntimeHosts.duration',
                'DowntimeHosts.scheduled_end_time',
                'DowntimeHosts.was_started',
                'DowntimeHosts.actual_start_time',
                'DowntimeHosts.actual_end_time',
                'DowntimeHosts.was_cancelled',

                'Objects.name1',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeHosts.object_id', 'DowntimeHosts.downtime_type = 2'] //Downtime.downtime_type = 2 Host downtime
            )
            ->where([
                'Objects.objecttype_id' => 1
            ]);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getServiceDowntimesMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\DowntimeServicesTable $DowntimeServicesTable */
        $DowntimeServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.DowntimeServices');

        $query = $DowntimeServicesTable->find();

        $query
            ->select([
                'DowntimeServices.internal_downtime_id',
                'DowntimeServices.scheduled_start_time',
                'DowntimeServices.entry_time',
                'DowntimeServices.author_name',
                'DowntimeServices.comment_data',
                'DowntimeServices.triggered_by_id',
                'DowntimeServices.is_fixed',
                'DowntimeServices.duration',
                'DowntimeServices.scheduled_end_time',
                'DowntimeServices.was_started',
                'DowntimeServices.actual_start_time',
                'DowntimeServices.actual_end_time',
                'DowntimeServices.was_cancelled',

                'Objects.name1',
                'Objects.name2',
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = DowntimeServices.object_id', 'DowntimeServices.downtime_type = 1'] //Downtime.downtime_type = 1 Service downtime
            )
            ->where([
                'Objects.objecttype_id' => 2
            ]);

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getHostChecksMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\HostchecksTable $HostchecksTable */
        $HostchecksTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Hostchecks');

        $query = $HostchecksTable->find();
        $query
            ->select([
                'Hostchecks.start_time',
                'Hostchecks.state',
                'Hostchecks.state_type',
                'Hostchecks.end_time',
                'Hostchecks.output',
                'Hostchecks.timeout',
                'Hostchecks.early_timeout',
                'Hostchecks.latency',
                'Hostchecks.execution_time',
                'Hostchecks.perfdata',
                'Hostchecks.current_check_attempt',
                'Hostchecks.max_check_attempts',
                'Hostchecks.long_output',

                'Objects.name1',
                'CommandObject.name1'
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = Hostchecks.host_object_id', 'Objects.objecttype_id = 1']
            )
            ->innerJoin(
                ['CommandObject' => 'nagios_objects'],
                ['CommandObject.object_id = Hostchecks.command_object_id', 'CommandObject.objecttype_id = 12']
            );

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getServiceChecksMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\ServicechecksTable $ServicechecksTable */
        $ServicechecksTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Servicechecks');

        $query = $ServicechecksTable->find();
        $query
            ->select([
                'Servicechecks.start_time',
                'Servicechecks.state',
                'Servicechecks.state_type',
                'Servicechecks.end_time',
                'Servicechecks.output',
                'Servicechecks.timeout',
                'Servicechecks.early_timeout',
                'Servicechecks.latency',
                'Servicechecks.execution_time',
                'Servicechecks.perfdata',
                'Servicechecks.current_check_attempt',
                'Servicechecks.max_check_attempts',
                'Servicechecks.long_output',

                'Objects.name1',
                'Objects.name2',
                'CommandObject.name1'
            ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = Servicechecks.service_object_id', 'Objects.objecttype_id = 2']
            )
            ->innerJoin(
                ['CommandObject' => 'nagios_objects'],
                ['CommandObject.object_id = Servicechecks.command_object_id', 'CommandObject.objecttype_id = 12']
            );

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function getLogentriesMigrationQuery(int $offset = 0, bool $asCount = false) {
        /** @var \Statusengine2Module\Model\Table\LogentriesTable $LogentriesTable */
        $LogentriesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Logentries');

        $query = $LogentriesTable->find();

        if ($asCount === true) {
            return $query->count();
        }

        $query->offset($offset);
        $query->limit($this->limit);
        $query->disableHydration();
        $query->disableResultsCasting();
        return $query->toArray();
    }

    private function migrateParitions(string $sourceTable, string $targetTable) {
        $Connection = ConnectionManager::get('default');

        //Get existing partitions for this table out of MySQL's information_schema
        $query = $Connection->execute("
                SELECT
                    PARTITION_NAME AS 'PARTITION_NAME',
                    PARTITION_DESCRIPTION AS 'PARTITION_DESCRIPTION'
                FROM information_schema.partitions
                WHERE TABLE_SCHEMA = :databaseName
                AND TABLE_NAME = :tableName
                ORDER BY partitions.PARTITION_DESCRIPTION ASC", [
            'databaseName' => $Connection->config()['database'],
            'tableName'    => $sourceTable
        ]);
        $partitionsInNdoSchema = $query->fetchAll('assoc');

        $query = $Connection->execute("
                SELECT *
                FROM information_schema.partitions
                WHERE TABLE_SCHEMA = :databaseName
                AND TABLE_NAME = :tableName
                ORDER BY partitions.PARTITION_DESCRIPTION ASC", [
            'databaseName' => $Connection->config()['database'],
            'tableName'    => $targetTable
        ]);
        $partitionsInStatusengineSchema = $query->fetchAll('assoc');

        //MySQL 5.x
        $partitionsInStatusengineSchemaNames = Hash::extract($partitionsInStatusengineSchema, '{n}.partition_name');

        //MySQL 8.x
        if (isset($partitionsInStatusengineSchema['0']['PARTITION_NAME'])) {
            $partitionsInStatusengineSchemaNames = Hash::extract($partitionsInStatusengineSchema, '{n}.PARTITION_NAME');
        }

        foreach ($partitionsInNdoSchema as $ndoPartition) {
            if ($ndoPartition['PARTITION_NAME'] === 'p_max' || is_numeric($ndoPartition['PARTITION_DESCRIPTION']) === false) {
                continue;
            }

            if (!in_array($ndoPartition['PARTITION_NAME'], $partitionsInStatusengineSchemaNames, true)) {

                //Get less than value
                $query = $Connection->execute("SELECT FROM_DAYS(" . $ndoPartition['PARTITION_DESCRIPTION'] . ") as less_than");
                $lessThan = $query->fetchAll('assoc');

                $lessThan = strtotime($lessThan[0]['less_than'] . ' 00:00:00');
                //debug($ndoPartition['PARTITION_NAME'] . ' => ' . date('d.m.o H:i:s', $lessThan) . ' => ' . intdiv($lessThan, 86400));
                $currentMysqlPartitionStartDate = intdiv($lessThan, 86400);

                $this->io->info(__('Create partition {0} in table {1}', $ndoPartition['PARTITION_NAME'], $targetTable), 0);
                $Connection->execute("ALTER TABLE " . $Connection->config()['database'] . "." . $targetTable . " REORGANIZE PARTITION p_max INTO (PARTITION " . $ndoPartition['PARTITION_NAME'] . " VALUES LESS THAN (" . $currentMysqlPartitionStartDate . "), PARTITION p_max values LESS THAN (MAXVALUE));");
                $this->io->success('   Ok');
            }
        }
    }

    /**
     * @return float
     */
    private function getMicrotime() {
        $microtime = explode(' ', microtime())[0];
        return (int)ltrim($microtime, '0.');
    }

    private function changeEvcSubmitMethod() {
        if (Plugin::isLoaded('EventcorrelationModule')) {
            /** @var EventcorrelationSettingsTable $EventcorrelationSettingsTable */
            $EventcorrelationSettingsTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.EventcorrelationSettings');

            $settings = $EventcorrelationSettingsTable->find()->first();
            if ($settings !== null) {
                $settings->set('monitoring_system', 'statusengine');
                $EventcorrelationSettingsTable->save($settings);
            }
        }
    }

    private function migrateSatelliteSystems() {
        if (Plugin::isLoaded('DistributeModule')) {
            /** @var \DistributeModule\Model\Table\SatellitesTable $SatellitesTable */
            $SatellitesTable = TableRegistry::getTableLocator()->get('DistributeModule.Satellites');

            $sshConfig = [
                'sync_method'      => 'ssh',
                'login'            => 'nagios',
                'port'             => 22,
                'private_key_path' => '/var/lib/nagios/.ssh/id_rsa',
                'remote_port'      => 4730,
                'use_timesync'     => 1
            ];

            if (file_exists('/etc/phpnsta/config.php')) {
                require_once '/etc/phpnsta/config.php';

                if (isset($config) && is_array($config)) {
                    $sshConfig = [
                        'sync_method'      => 'ssh',
                        'login'            => $config['SSH']['username'],
                        'port'             => (int)$config['SSH']['port'],
                        'private_key_path' => $config['SSH']['private_path'],
                        'remote_port'      => 4730,
                        'use_timesync'     => (int)$config['TSYNC']['synchronize_time']
                    ];
                }
            }

            foreach ($SatellitesTable->getSatellitesBySyncMethod('ssh') as $satellite) {
                /** @var Satellite $satellite */

                $satellite = $SatellitesTable->patchEntity($satellite, $sshConfig);
                $SatellitesTable->save($satellite);

            }


        }
    }
}
