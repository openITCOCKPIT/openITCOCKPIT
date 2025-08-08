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
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\System\Health\SystemId;
use itnovum\openITCOCKPIT\SetupShell\MailConfigurator;
use itnovum\openITCOCKPIT\SetupShell\MailConfigValue;
use itnovum\openITCOCKPIT\SetupShell\MailConfigValueInt;
use Symfony\Component\Yaml\Yaml;

/**
 * Setup command.
 */
class SetupCommand extends Command {

    /**
     * @var ConsoleIo
     */
    private $io;

    /**
     * @var string
     */
    private $defaultsFile = '/opt/openitc/ansible/ansible_settings.yml';

    /**
     * @var array
     */
    private $defaultConfig = [];

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

        $parser->addOptions([
            'fast' => ['help' => 'Skip 10 seconds waiting if ansible_settings.yml is present and used', 'boolean' => true, 'default' => false]
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

        $this->io->setStyle('blue', ['text' => 'blue']);

        $this->io->hr();
        $this->io->out("<blue>                                        ///</blue>");
        $this->io->out("<blue>                              ///////////////////////</blue>");
        $this->io->out("<blue>                          ///////////////////////////////</blue>");
        $this->io->out("<blue>                        ///////////////////////////////////</blue>");
        $this->io->out("<blue>                     /////////////////////////////////////////</blue>");
        $this->io->out("<blue>                    ///////////////////////////////////////////</blue>");
        $this->io->out("<blue>                   ////////////////   .////////////////////    ,</blue>");
        $this->io->out("<blue>                  //////////                  //////             .</blue>");
        $this->io->out("<blue>                 ////////         //////        /         ///////.</blue>");
        $this->io->out("<blue>                 ///////       ///////////.   /////// ////////////</blue>");
        $this->io->out("<blue>                ///////       /////////////      /   /////////////</blue>");
        $this->io->out("<blue>                ///////       /////////////*  /* /   /////////////</blue>");
        $this->io->out("<blue>                ///////       /////////////   /*     /////////////</blue>");
        $this->io->out("<blue>                ////////        //////////    /*       //////////</blue>");
        $this->io->out("<blue>                //////////                     //</blue>");
        $this->io->out("<blue>                /////////////               ////////</blue>");
        $this->io->out("<blue>                /////////////////////////////////////////////</blue>");
        $this->io->out("<blue>                ///////////////////////////////////////////</blue>");
        $this->io->out("<blue>                ////////////////////////////////////////</blue>");
        $this->io->out("<blue>                ////////////////////////////////////</blue>");
        $this->io->hr();


        $this->defaultConfig = $this->checkAndLoadDefaults($io);
        $fast = $args->getOption('fast') === true;
        if (!empty($this->defaultConfig)) {
            $io->info('Using defaults defined in ' . $this->defaultsFile);
            $io->info('The setup will continue without asking any questions!');

            if (!$fast) {
                $i = 10;
                do {
                    $io->info(__('Setup will continue in {0} seconds. Press CTRL + C to abort.', $i));
                    sleep(1);
                    $i--;
                } while ($i > 0);
            }

            $this->setupFromDefaults();
        } else {
            /** @var UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('Users');
            $result = $UsersTable->find()->count();

            if ($result > 0) {
                $io->out(__('This shell helps you to setup your fresh installation of openITCOCKPIT.'));
                $io->warning(__('Warning:'));
                $io->warning(__('Only run this command, if you installed openITCOCKPIT right now and never logged in to the interface!'));
                $io->warning(__('If you continue, you will lose all your archive data!'));
                $io->hr();

                $io->out(__d('oitc_console', '[Y]es I want to continue the setup of openITCOCKPIT'));
                $io->out(__d('oitc_console', '[N]o and exit shell'));

                $result = strtoupper($io->askChoice(__d('oitc_console', 'Are you sure you want to continue?'), ['Y', 'N'], 'N'));
                switch ($result) {
                    case 'Y':
                        $this->setup();
                        break;
                    case 'N':
                        $io->info(__d('oitc_console', 'Setup aborted!'));
                        exit(0);
                    default:
                        $io->error(__d('oitc_console', 'You have made an invalid selection. Please choose by entering Y or N.'));
                        exit(1);
                }
            } else {
                $io->out(__('Setup openITCOCKPIT and create your first user.'));
                $io->hr();
                $this->setup();
            }
        }


        // Generate new SystemId
        $SystemId = new SystemId();
        $this->io->out('');
        $this->io->hr();
        $this->io->success(__('You can now open the web frontend in your browser and login. Have a nice day!'));
        $this->io->hr();
        exit(0);
    }

    private function setupFromDefaults() {
        $this->createAdminUser([
            'firstname'              => $this->defaultConfig['firstname'],
            'lastname'               => $this->defaultConfig['lastname'],
            'email'                  => $this->defaultConfig['email'],
            'password'               => $this->defaultConfig['password'],
            'confirm_password'       => $this->defaultConfig['password'],
            'timezone'               => $this->defaultConfig['timezone'],
            'is_active'              => 1,
            'usergroup_id'           => 1,
            'dateformat'             => 'H:i:s - d.m.Y',
            'showstatsinmenu'        => 0,
            'dashboard_tab_rotation' => 0,
            'paginatorlength'        => 25,
            'recursive_browser'      => 0,
            'containers'             => [
                [
                    'id'        => ROOT_CONTAINER,
                    '_joinData' => [
                        'permission_level' => WRITE_RIGHT
                    ]
                ]
            ]
        ]);
        $this->setHostname($this->defaultConfig['hostname']);
        $this->setSender($this->defaultConfig['mail']['sender']);
        $this->setMailconfig([
            'host'     => $this->defaultConfig['mail']['host'],
            'port'     => $this->defaultConfig['mail']['port'],
            'username' => $this->defaultConfig['mail']['username'],
            'password' => $this->defaultConfig['mail']['password']
        ]);

        // ITC-3460
        $enabled = false;
        if ($this->defaultConfig['anonymous_statistics'] === true) {
            $enabled = true;
        }
        $this->setAnonymousStatistics($enabled);

        $this->createMysqlPartitions();

    }

    public function setup() {

        $user = $this->fetchUserdata();
        $this->createAdminUser($user);

        $hostname = $this->askHostname();
        $this->setHostname($hostname);

        $mailConfig = $this->fetchMailconfig();

        $this->setSender($mailConfig['sender']);
        $this->setMailconfig([
            'host'     => $mailConfig['host'],
            'port'     => $mailConfig['port'],
            'username' => $mailConfig['username'],
            'password' => $mailConfig['password']
        ]);

        $enabled = $this->askAnonymousStatistics();
        $this->setAnonymousStatistics($enabled);

        $this->createMysqlPartitions();

    }

    public function fetchUserdata() {

        $firstname = $this->askFirstname();
        $lastname = $this->askLastname();
        $email = $this->askEmail();
        $password = $this->askPassword();
        $timezone = 'Europe/Berlin';

        return [
            'firstname'              => $firstname,
            'lastname'               => $lastname,
            'email'                  => $email,
            'password'               => $password,
            'confirm_password'       => $password,
            'timezone'               => $timezone,
            'is_active'              => 1,
            'usergroup_id'           => 1,
            'showstatsinmenu'        => 0,
            'dashboard_tab_rotation' => 0,
            'paginatorlength'        => 25,
            'recursive_browser'      => 0,
            'containers'             => [
                [
                    'id'        => ROOT_CONTAINER,
                    '_joinData' => [
                        'permission_level' => WRITE_RIGHT
                    ]
                ]
            ]
        ];


    }

    /**
     * @return string
     */
    public function askFirstname() {
        $input = $this->io->ask(__d('oitc_console', 'Please enter your first name'));
        $input = trim($input);
        if (strlen($input) > 0) {
            return $input;
        }
        $this->askFirstname();
    }

    /**
     * @return string
     */
    public function askLastname() {
        $input = $this->io->ask(__d('oitc_console', 'Please enter your last name'));
        $input = trim($input);
        if (strlen($input) > 0) {
            return $input;
        }
        $this->askLastname();
    }

    /**
     * @return string
     */
    public function askEmail() {
        $input = $this->io->ask(__d('oitc_console', 'Please enter your email address. This will be the username for the login.'));
        $input = trim($input);

        if (!Validation::email($input)) {
            $this->askEmail();
        }

        return $input;
    }

    /**
     * @return string
     */
    public function askPassword() {
        $this->io->info('The password must consist of 6 alphanumeric characters and must contain at least one digit');
        $pw1 = $this->io->ask(__d('oitc_console', 'Please enter a password for the login.'));
        $pw1 = trim($pw1);
        if (strlen($pw1) >= 6) {
            $pw2 = $this->io->ask(__d('oitc_console', 'Please confirm your password'));
            $pw2 = trim($pw2);
            if ($pw1 == $pw2) {
                return $pw1;
            }
        }
        $this->io->error('Password mismatch!');
        $this->askPassword();
    }

    /**
     * @return string
     */
    public function askHostname() {
        //Try to auto-detect with fqdn
        $hostname = gethostbyaddr(gethostbyname(gethostname()));
        if ($hostname === false) {
            $hostname = 'localhost';
        } else if (strlen($hostname) === 0) {
            $hostname = php_uname('n');
        }

        $this->io->out(__('Please enter the FQDN or IP address of your openITCOCKPIT Server. If you do not know your IP address enter a random one and change it via the interface later.'));

        $input = $this->io->ask(__d('oitc_console', 'System Address or FQDN?'), $hostname);
        $input = trim($input);
        if (strlen($input) > 0) {
            return $input;
        }
        $this->askHostname();
    }

    /**
     * @return array
     */
    public function fetchMailconfig() {
        $this->io->info('The installer will now ask you for your mail configuration');
        $this->io->info('This configuration is used by the interface and the monitoring engine to send emails.');
        $this->io->info('It is recommended to install a local mail server!');
        $this->io->info('The settings get saved to the file ', 0);
        $this->io->warning(CONFIG . 'email.php', 0);
        $this->io->info(' and could be always changed later on...');

        $config = [
            'host'     => $this->askMailhost(),
            'port'     => $this->askMailPort(),
            'sender'   => $this->askFromAddress(),
            'username' => $this->askMailUser(),
            'password' => $this->askMailPassword()
        ];
        return $config;
    }

    /**
     * @return string
     */
    public function askFromAddress() {
        $this->io->info('openITCOCKPIT requires a valid mail address to send emails. (e.g. openitcockpit@example.org)');
        $input = $this->io->ask(__d('oitc_console', 'Please enter a sender email address'), 'openitcockpit@example.org');
        $input = trim($input);
        if (!Validation::email($input)) {
            $this->askFromAddress();
        }

        return $input;
    }

    /**
     * @return string
     */
    public function askMailhost() {
        $this->io->info('If you want to use a local installed mail server (e.g.postfix) enter 127.0.0.1 as address and left username and password blank');
        $input = $this->io->ask(__d('oitc_console', 'Please enter the address of your mail server (e.g. mail.example.org)'), '127.0.0.1');
        $input = trim($input);
        if (strlen($input) > 0) {
            return $input;
        }
        $this->askMailhost();
    }

    /**
     * @return int
     */
    public function askMailPort() {
        $input = $this->io->ask(__d('oitc_console', 'Please enter the port of your mail server'), '25');
        $input = trim($input);
        if (strlen($input) > 0 && is_numeric($input)) {
            return $input;
        }
        $this->askMailPort();
    }

    /**
     * @return string
     */
    public function askMailUser() {
        $this->io->info('Your username may looks like:');
        $this->io->info('"domain\jdoe"</> or "john.doe@example.org"');
        $input = $this->io->ask(__d('oitc_console', 'If required, set a username, or leave it blank if you don\'t need a user'));
        $input = trim($input);

        return $input;
    }

    /**
     * @return string
     */
    public function askMailPassword() {
        $input = $this->io->ask(__d('oitc_console', 'Please enter your password, or leave it blank if you don\'t need a password'));
        $input = trim($input);

        return $input;
    }

    /**
     * @return bool
     */
    public function createMysqlPartitions() {
        $this->io->out('Create MySQL partitions', 0);


        $DbBackend = new DbBackend();
        if ($DbBackend->isNdoUtils()) {
            $sqlFile = ROOT . DS . 'partitions.sql';
        }

        if ($DbBackend->isStatusengine3()) {
            $sqlFile = ROOT . DS . 'partitions_statusengine3.sql';
        }

        if (!isset($sqlFile)) {
            throw new \RuntimeException('Could not detect DbBackend!');
        }

        if (!file_exists($sqlFile)) {
            throw new \RuntimeException('File ' . $sqlFile . ' does not exists!');
        }

        $mysqlCnf = '/opt/openitc/etc/mysql/mysql.cnf';
        if (file_exists($mysqlCnf)) {
            exec('mysql --defaults-extra-file=' . $mysqlCnf . ' < ' . $sqlFile, $out, $ret);
            if ($ret == 0) {
                $this->io->success(' ...OK');
                return true;
            }
            $this->io->error(' ...ERROR');
            return false;
        }
        $this->io->error('MySQL configuration file ' . $mysqlCnf . ' does not exist');
        return false;
    }


    /**
     * @param ConsoleIo $io
     * @return array|mixed
     */
    public function checkAndLoadDefaults(ConsoleIo $io) {
        if (!file_exists($this->defaultsFile)) {
            return [];
        }

        $default = Yaml::parseFile($this->defaultsFile);
        if (!is_array($default)) {
            return [];
        }

        if (!isset($default['setup'])) {
            return [];
        }


        //Check for all required keys
        $keys = [
            'firstname',
            'lastname',
            'email',
            'password',
            'timezone',
            'hostname',
            'anonymous_statistics',
            'mail' => [
                'host',
                'port',
                'sender',
                'username',
                'password',
            ]
        ];

        foreach ($keys as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (!isset($default['setup'][$key])) {
                        $io->error('Missing key "setup/' . $key . '" in defaults file!');
                        exit(1);
                    }
                    if (!array_key_exists($subValue, $default['setup'][$key])) { //isset can not handle null values
                        $io->error('Missing key "setup/' . $key . '/' . $subValue . '" in defaults file!');
                        exit(1);
                    }
                }
            } else {
                if (!array_key_exists($value, $default['setup'])) { //isset can not handle null values
                    $io->error('Missing key "setup/' . $value . '" in defaults file!');
                    exit(1);
                }
            }
        }

        return $default['setup'];
    }

    /**
     * @param array $data
     * @return \App\Model\Entity\User
     */
    private function createAdminUser(array $data) {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        $entity = $UsersTable->newEntity($data);
        if ($entity->hasErrors()) {
            throw new \RuntimeException(json_encode($entity->getErrors(), JSON_PRETTY_PRINT));
        }

        $UsersTable->save($entity);

        $this->io->success(__('User "{0}" created successfully', $data['email']));

        return $entity;
    }

    /**
     * @param null $hostname
     * @return \Cake\Datasource\EntityInterface
     */
    private function setHostname($hostname = null) {
        if ($hostname === null) {
            //Try to auto-detect with fqdn
            $hostname = gethostbyaddr(gethostbyname(gethostname()));
            if (strlen($hostname) === 0) {
                $hostname = php_uname('n');
            }
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $entity = $SystemsettingsTable->getSystemsettingByKey('SYSTEM.ADDRESS');
        $entity->set('value', $hostname);

        $SystemsettingsTable->save($entity);
        $this->io->success(__('Hostname was set to "{0}"', $hostname));
        return $entity;
    }

    /**
     * @param null $hostname
     * @return \Cake\Datasource\EntityInterface
     */
    private function setSender(string $sender) {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $entity = $SystemsettingsTable->getSystemsettingByKey('MONITORING.FROM_ADDRESS');
        $entity->set('value', $sender);

        $SystemsettingsTable->save($entity);
        $this->io->success(__('E-Mail sender was set to "{0}"', $sender));
        return $entity;
    }

    private function setMailconfig(array $config) {
        $file = fopen(CONFIG . 'email.php', 'w+');

        $mailHost = new MailConfigValue($config['host']);
        $mailPort = new MailConfigValueInt((int)$config['port']);
        $mailUsername = new MailConfigValue($config['username']);
        $mailPassword = new MailConfigValue($config['password']);
        $mailConfigurator = new MailConfigurator(
            $mailHost, $mailPort, $mailUsername, $mailPassword
        );
        fwrite($file, $mailConfigurator->getConfig());
        fclose($file);
        $this->io->success(__('Mail configuration "{0}" saved successfully.', CONFIG . 'email.php'));
    }

    /**
     * @param bool $enabled
     * @return \Cake\Datasource\EntityInterface
     */
    private function setAnonymousStatistics($enabled = false): void {

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        try {
            $record = $SystemsettingsTable->getSystemsettingByKey('SYSTEM.ANONYMOUS_STATISTICS');
        } catch (RecordNotFoundException $e) {
            // System Settings Record is missing for some reason
            // Ignore for now
            return;
        }

        $value = 0; // Off by default
        if ($enabled === true) {
            $value = 1;
        }

        $record->set('value', $value);
        $SystemsettingsTable->save($record);

        $this->io->success(__('Sending of anonymous statistics is currently {0}. ', ($enabled ? 'enabled' : 'disabled')));

    }

    /**
     * @return bool
     */
    public function askAnonymousStatistics() {
        $this->io->info(__d('oitc_console', 'We ask for your help to improve openITCOCKPIT'));
        $this->io->info(__d('oitc_console', 'Learn more at: https://openitcockpit.io/we-ask-for-your-help-to-improve-openitcockpit/'));
        $result = strtoupper($this->io->askChoice(__d('oitc_console', 'Do you want to enable sending of anonymous statistics?'), ['Y', 'N'], 'N'));

        $enabled = false;
        if ($result === 'Y') {
            $enabled = true;
        }

        return $enabled;
    }

}
