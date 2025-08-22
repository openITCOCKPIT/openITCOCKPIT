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

use App\Model\Table\SystemsettingsTable;

/**
 * Class Systemsetting
 * @package itnovum\openITCOCKPIT\InitialDatabase
 * @property SystemsettingsTable $Table
 */
class Systemsetting extends Importer {

    /**
     * @return bool
     */
    public function import() {
        $data = $this->getData();
        foreach ($data as $record) {
            if (!$this->Table->existsByKey($record['key'])) {
                $entity = $this->Table->newEntity($record);
                $this->Table->save($entity);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getData() {
        $data = [
            (int)0   => [
                'key'      => 'SUDO_SERVER.API_KEY',
                'value'    => 'UNSAVE_DEFAULT_CHANGE_ASAP',
                'info'     => 'API key for the sudoserver socket API',
                'section'  => 'SUDO_SERVER',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)1   => [
                'key'      => 'WEBSERVER.USER',
                'value'    => 'www-data',
                'info'     => 'Username of the webserver',
                'section'  => 'WEBSERVER',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)2   => [
                'key'      => 'WEBSERVER.GROUP',
                'value'    => 'www-data',
                'info'     => 'Usergroup of the webserver',
                'section'  => 'WEBSERVER',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)3   => [
                'key'      => 'MONITORING.USER',
                'value'    => 'nagios',
                'info'     => 'The user of your monitoring system',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)4   => [
                'key'      => 'MONITORING.GROUP',
                'value'    => 'nagios',
                'info'     => 'The group of your monitoring system',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)5   => [
                'key'      => 'MONITORING.FROM_ADDRESS',
                'value'    => 'adasda@asdad.e',
                'info'     => 'Sender mail address for notifications',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)6   => [
                'key'      => 'MONITORING.FROM_NAME',
                'value'    => 'openITCOCKPIT Notification',
                'info'     => 'The name we should display in your mail client',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)7   => [
                'key'      => 'MONITORING.MESSAGE_HEADER',
                'value'    => '**** openITCOCKPIT notification by it-novum GmbH ****',
                'info'     => 'The header in the plain text mail',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            //(int)8   => [
            //    'key'      => 'MONITORING.ACK_RECEIVER_SERVER',
            //    'value'    => 'imap.gmail.com:993/imap/ssl',
            //    'info'     => 'Email server to connect. Must be provided in following format: server.com:port/imap[/ssl]',
            //    'section'  => 'MONITORING',
            //    'created'  => '2020-01-29 09:28:17',
            //    'modified' => '2020-01-29 09:28:17'
            //],
            //(int)9   => [
            //    'key'      => 'MONITORING.ACK_RECEIVER_ADDRESS',
            //    'value'    => 'my_email@gmail.com',
            //    'info'     => 'Username for sender notification mail',
            //    'section'  => 'MONITORING',
            //    'created'  => '2020-01-29 09:28:17',
            //    'modified' => '2020-01-29 09:28:17'
            //],
            //(int)10  => [
            //    'key'      => 'MONITORING.ACK_RECEIVER_PASSWORD',
            //    'value'    => 'my_password',
            //    'info'     => 'Password for sender notification mail',
            //    'section'  => 'MONITORING',
            //    'created'  => '2020-01-29 09:28:17',
            //    'modified' => '2020-01-29 09:28:17'
            //],
            (int)11  => [
                'key'      => 'MONITORING.CMD',
                'value'    => '/opt/openitc/nagios/var/rw/nagios.cmd',
                'info'     => 'The command pipe for your monitoring system',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)12  => [
                'key'      => 'MONITORING.HOST.INITSTATE',
                'value'    => 'u',
                'info'     => 'Host initial state [o,d,u]',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)13  => [
                'key'      => 'MONITORING.SERVICE.INITSTATE',
                'value'    => 'u',
                'info'     => 'Service initial state [o,w,u,c]',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)14  => [
                'key'      => 'MONITORING.RESTART',
                'value'    => 'systemctl restart nagios',
                'info'     => 'Command to reload your monitoring software',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)15  => [
                'key'      => 'MONITORING.RELOAD',
                'value'    => 'systemctl reload nagios',
                'info'     => 'Command to reload your monitoring software',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)16  => [
                'key'      => 'MONITORING.STOP',
                'value'    => 'systemctl stop nagios',
                'info'     => 'Command to stop your monitoring software',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)17  => [
                'key'      => 'MONITORING.START',
                'value'    => 'systemctl start nagios',
                'info'     => 'Command to start your monitoring software',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)18  => [
                'key'      => 'MONITORING.STATUS',
                'value'    => 'systemctl status nagios',
                'info'     => 'Command to query the status of your monitoring software',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)19  => [
                'key'      => 'MONITORING.CORECONFIG',
                'value'    => '/opt/openitc/etc/nagios/nagios.cfg',
                'info'     => 'Path to monitoring core configuration file',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)20  => [
                'key'      => 'MONITORING.STATUS_DAT',
                'value'    => '/opt/openitc/nagios/var/status.dat',
                'info'     => 'Path to the status.dat of the monitoring system',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)21  => [
                'key'      => 'MONITORING.FRESHNESS_THRESHOLD_ADDITION',
                'value'    => '300',
                'info'     => 'Value in seconds that get added to the service check interval for passive services, before the monitoring system will fire up the freshness check',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)22  => [
                'key'      => 'MONITORING.AFTER_EXPORT',
                'value'    => '#echo 1',
                'info'     => 'A command that get executed on each export (Notice: this command runs as root, so be careful)',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)23  => [
                'key'      => 'MONITORING.SINGLE_INSTANCE_SYNC',
                'value'    => '0',
                'info'     => 'If enabled, you can select which openITCOCKPIT instance you like to push the new configuration to. If disabled all instances will be synchronized',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)24  => [
                'key'      => 'MONITORING.QUERY_HANDLER',
                'value'    => '/opt/openitc/nagios/var/rw/nagios.qh',
                'info'     => 'Path to the query handler of your monitoring engine',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)25  => [
                'key'      => 'MONITORING.HOST_CHECK_ACTIVE_DEFAULT',
                'value'    => '1',
                'info'     => 'If enabled, new host templates will have active_checks enabled by default',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)26  => [
                'key'      => 'MONITORING.SERVICE_CHECK_ACTIVE_DEFAULT',
                'value'    => '1',
                'info'     => 'If enabled, new service templates will have active_checks enabled by default',
                'section'  => 'MONITORING',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)27  => [
                'key'      => 'SYSTEM.ADDRESS',
                'value'    => '127.0.0.1',
                'info'     => 'The IP address or FQDN of the system',
                'section'  => 'SYSTEM',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)28  => [
                'key'      => 'SYSTEM.ANONYMOUS_STATISTICS',
                'value'    => '2',
                'info'     => 'Determines if you want to support the developers of openITCOCKPIT by providing anonymous statistical data or not.',
                'section'  => 'SYSTEM',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)29  => [
                'key'      => 'FRONTEND.SYSTEMNAME',
                'value'    => 'openITCOCKPIT',
                'info'     => 'The name of your system',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)30  => [
                'key'      => 'FRONTEND.SHOW_EXPORT_RUNNING',
                'value'    => 'yes',
                'info'     => 'Show if an export is running in headarea',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)31  => [
                'key'      => 'FRONTEND.MASTER_INSTANCE',
                'value'    => 'Mastersystem',
                'info'     => 'The name of your openITCOCKPIT main instance',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)32  => [
                'key'      => 'FRONTEND.AUTH_METHOD',
                'value'    => 'session',
                'info'     => 'The authentication method that should be used for login',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)33  => [
                'key'      => 'FRONTEND.LDAP.TYPE',
                'value'    => 'adldap',
                'info'     => 'LDAP server type',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)34  => [
                'key'      => 'FRONTEND.LDAP.ADDRESS',
                'value'    => '192.168.1.10',
                'info'     => 'The address or hostname of your LDAP server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)35  => [
                'key'      => 'FRONTEND.LDAP.PORT',
                'value'    => '389',
                'info'     => 'The port where your LDAP server is listen to. Notice: If you want to use TLS the default port 389 should work as well! (SSL default Port is 636)',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)36  => [
                'key'      => 'FRONTEND.LDAP.QUERY',
                'value'    => '(&(objectClass=user)(samaccounttype=805306368)(objectCategory=person)(cn=*))',
                'info'     => 'Your Filter Query',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)37  => [
                'key'      => 'FRONTEND.LDAP.BASEDN',
                'value'    => 'DC=example,DC=org',
                'info'     => 'Your BASEDN',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)38  => [
                'key'      => 'FRONTEND.LDAP.USERNAME',
                'value'    => 'administrator',
                'info'     => 'The username that the system will use to connect to your LDAP server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)39  => [
                'key'      => 'FRONTEND.LDAP.PASSWORD',
                'value'    => 'Testing123!',
                'info'     => 'The password that the system will use to connect to your LDAP server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)40  => [
                'key'      => 'FRONTEND.LDAP.SUFFIX',
                'value'    => '@example.org',
                'info'     => 'The Suffix of your domain',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)41  => [
                'key'      => 'FRONTEND.LDAP.USE_TLS',
                'value'    => '1',
                'info'     => 'If PHP should upgrade the security of a plain connection to a TLS encrypted connection',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)42  => [
                'key'      => 'FRONTEND.SSO.CLIENT_ID',
                'value'    => 'my_client_id',
                'info'     => 'Client id generated in SSO Server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)43  => [
                'key'      => 'FRONTEND.SSO.CLIENT_SECRET',
                'value'    => 'some_client_password',
                'info'     => 'Client secret generated in SSO Server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)44  => [
                'key'      => 'FRONTEND.SSO.AUTH_ENDPOINT',
                'value'    => 'https://sso.server.com/authorization.oauth2',
                'info'     => 'Authorization endpoint of SSO Server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)45  => [
                'key'      => 'FRONTEND.SSO.TOKEN_ENDPOINT',
                'value'    => 'https://sso.server.com/token.oauth2',
                'info'     => 'Token endpoint of SSO Server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)46  => [
                'key'      => 'FRONTEND.SSO.USER_ENDPOINT',
                'value'    => 'https://sso.server.com/userinfo.oauth2',
                'info'     => 'User info endpoint of SSO Server',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)47  => [
                'key'      => 'FRONTEND.SSO.NO_EMAIL_MESSAGE',
                'value'    => 'Email address not found. Please contact your <a href="mailto:admin@my.com">administrator</a>',
                'info'     => 'The error message that appears when provided E-mail address was not found in openITCOCKPIT',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)48  => [
                'key'      => 'FRONTEND.SSO.LOG_OFF_LINK',
                'value'    => 'https://sso.server.com/sso/logoff',
                'info'     => 'SSO Server log out link',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)49  => [
                'key'      => 'FRONTEND.CERT.DEFAULT_USER_EMAIL',
                'value'    => 'default.user@email.de',
                'info'     => 'Default user E-mail address to be used if no E-mail address was found during the login with certificate',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)50  => [
                'key'      => 'FRONTEND.HIDDEN_USER_IN_CHANGELOG',
                'value'    => '0',
                'info'     => 'Hide the user name in the change log due to privacy reasons',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)51  => [
                'key'      => 'FRONTEND.PRESELECTED_DOWNTIME_OPTION',
                'value'    => '0',
                'info'     => 'Set preselected Host downtime options individual',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)52  => [
                'key'      => 'FRONTEND.DISABLE_LOGIN_ANIMATION',
                'value'    => '0',
                'info'     => 'Determine if you want to disable the animation in login screen.',
                'section'  => 'FRONTEND',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)53  => [
                'key'      => 'CHECK_MK.BIN',
                'value'    => 'PYTHONPATH=/opt/openitc/check_mk/lib/python OMD_ROOT=/opt/openitc/check_mk OMD_SITE=1 /opt/openitc/check_mk/bin/check_mk',
                'info'     => 'Path to check_mk binary',
                'section'  => 'CHECK_MK',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)54  => [
                'key'      => 'CHECK_MK.MATCH',
                'value'    => '(perl|dsmc|java|ksh|VBoxHeadless|php)',
                'info'     => 'These are the services that should not be compressed by check_mk as regular expression',
                'section'  => 'CHECK_MK',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)55  => [
                'key'      => 'CHECK_MK.ETC',
                'value'    => '/opt/openitc/check_mk/etc/check_mk/',
                'info'     => 'Path to Check_MK config files',
                'section'  => 'CHECK_MK',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)56  => [
                'key'      => 'CHECK_MK.VAR',
                'value'    => '/opt/openitc/check_mk/var/check_mk/',
                'info'     => 'Path to Check_MK variable files',
                'section'  => 'CHECK_MK',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)57  => [
                'key'      => 'CHECK_MK.ACTIVE_CHECK',
                'value'    => 'CHECK_MK_ACTIVE',
                'info'     => 'The name of the check_mk active check service template',
                'section'  => 'CHECK_MK',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)58  => [
                'key'      => 'ARCHIVE.AGE.SERVICECHECKS',
                'value'    => '2',
                'info'     => 'Time in weeks how long service check results will be stored',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)59  => [
                'key'      => 'ARCHIVE.AGE.HOSTCHECKS',
                'value'    => '2',
                'info'     => 'Time in weeks how long host check results will be stored',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)60  => [
                'key'      => 'ARCHIVE.AGE.STATEHISTORIES',
                'value'    => '53',
                'info'     => 'Time in weeks how long state change events will be stored',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)61  => [
                'key'      => 'ARCHIVE.AGE.LOGENTRIES',
                'value'    => '2',
                'info'     => 'Time in weeks how long logentries will be stored',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)62  => [
                'key'      => 'ARCHIVE.AGE.NOTIFICATIONS',
                'value'    => '2',
                'info'     => 'Time in weeks how long notifications will be stored (keep eq to CONTACTNOTIFICATIONS AND CONTACTNOTIFICATIONMETHODS)',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)63  => [
                'key'      => 'ARCHIVE.AGE.CONTACTNOTIFICATIONS',
                'value'    => '2',
                'info'     => 'Time in weeks how long contactnotifications will be stored (keep eq to NOTIFICATIONS AND CONTACTNOTIFICATIONMETHODS)',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)64  => [
                'key'      => 'ARCHIVE.AGE.CONTACTNOTIFICATIONMETHODS',
                'value'    => '2',
                'info'     => 'Time in weeks how long contactnotificationmethods will be stored (keep eq to NOTIFICATIONS AND CONTACTNOTIFICATIONS)',
                'section'  => 'ARCHIVE',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)65  => [
                'key'      => 'INIT.SUDO_SERVER_STATUS',
                'value'    => 'systemctl status sudo_server',
                'info'     => 'Command to query the status of your sudo_server',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)66  => [
                'key'      => 'INIT.GEARMAN_WORKER_STATUS',
                'value'    => 'systemctl status gearman_worker',
                'info'     => 'Command to query the status of gearman_worker',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)67  => [
                'key'      => 'INIT.OITC_CMD_STATUS',
                'value'    => 'systemctl status oitc_cmd',
                'info'     => 'Command to query the status of your oitc_cmd',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)68  => [
                'key'      => 'INIT.NPCD_STATUS',
                'value'    => 'systemctl status npcd',
                'info'     => 'Command to query the status of your NPCD',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)69  => [
                'key'      => 'INIT.NDO_STATUS',
                'value'    => 'systemctl status ndoutils',
                'info'     => 'Command to query the status of your NDOUtils',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)70  => [
                'key'      => 'INIT.STATUSENIGNE_STATUS',
                'value'    => 'systemctl status statusengine',
                'info'     => 'Command to query the status of your statusengine',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)71  => [
                'key'      => 'INIT.GEARMAN_JOB_SERVER_STATUS',
                'value'    => 'systemctl status gearman-job-server',
                'info'     => 'Command to query the status of  gearman-job-server',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)72  => [
                'key'      => 'INIT.PHPNSTA_STATUS',
                'value'    => 'systemctl status phpnsta',
                'info'     => 'Command to query the status of  phpNSTA',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)73  => [
                'key'      => 'INIT.PUSH_NOTIFICATION',
                'value'    => 'systemctl status push_notification',
                'info'     => 'Command to query the status of  push_notification service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)74  => [
                'key'      => 'INIT.NODEJS_SERVER',
                'value'    => 'systemctl status nodejs_server',
                'info'     => 'Command to query the status of openITCOCKPITs NodeJS Server backend',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)75  => [
                'key'      => 'INIT.GEARMAN_WORKER_RESTART',
                'value'    => 'systemctl restart gearman_worker',
                'info'     => 'Command to restart gearman_worker service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)76  => [
                'key'      => 'INIT.OITC_GRAPHING_RESTART',
                'value'    => 'systemctl restart openitcockpit-graphing',
                'info'     => 'Command to restart openitcockpit-graphing service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)77  => [
                'key'      => 'INIT.PHPNSTA_RESTART',
                'value'    => 'systemctl restart phpnsta',
                'info'     => 'Command to restart phpNSTA service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)78  => [
                'key'      => 'INIT.STATUSENGINE_RESTART',
                'value'    => 'systemctl restart statusengine',
                'info'     => 'Command to restartstatusengine service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)79  => [
                'key'      => 'TICKET_SYSTEM.URL',
                'value'    => '',
                'info'     => 'Link to the ticket system',
                'section'  => 'TICKET_SYSTEM',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)80  => [
                'key'      => 'FRONTEND.REPLACE_USER_MACROS',
                'value'    => '1',
                'info'     => 'If enabled $USERn$ macros will get replaced in the command_line in host and service status overviews',
                'section'  => 'FRONTEND',
                'created'  => '2020-04-17 08:43:17',
                'modified' => '2020-04-17 08:43:17'
            ],
            (int)81  => [
                'key'      => 'INIT.NSTA_RESTART',
                'value'    => 'systemctl restart nsta',
                'info'     => 'Command to restart the Go version of NSTA service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)82  => [
                'key'      => 'INIT.NSTA_RELOAD',
                'value'    => 'systemctl reload nsta',
                'info'     => 'Command to reload the Go version of NSTA service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)83  => [
                'key'      => 'INIT.NSTA_STATUS',
                'value'    => 'systemctl status nsta',
                'info'     => 'Command to get the current status of the Go version of NSTA service',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)84  => [
                'key'      => 'FRONTEND.ENABLE_IFRAME_IN_DASHBOARDS',
                'value'    => '0',
                'info'     => 'If enabled the Website widget is available to the dashboards which allow users to embed 3rd-party websites.',
                'section'  => 'FRONTEND',
                'created'  => '2020-09-23 15:55:17',
                'modified' => '2020-09-23 15:55:17'
            ],
            (int)85  => [
                'key'      => 'FRONTEND.SSO.AUTH_PROVIDER',
                'value'    => 'generic',
                'info'     => 'Select which auth provider should be used by the system.',
                'section'  => 'FRONTEND',
                'created'  => '2020-10-01 08:48:17',
                'modified' => '2020-10-01 08:48:17'
            ],
            (int)86  => [
                'key'      => 'FRONTEND.SSO.FORCE_USER_TO_LOGINPAGE',
                'value'    => '0',
                'info'     => 'Auto redirect not logged in users to the login page of the oAuth server.',
                'section'  => 'FRONTEND',
                'created'  => '2020-10-01 08:48:17',
                'modified' => '2020-10-01 08:48:17'
            ],
            (int)87  => [
                'key'      => 'FRONTEND.REPLACE_PASSWORD_IN_OBJECT_MACROS',
                'value'    => '0',
                'info'     => 'If enabled $_HOST...$ and $_SERVICE...$ custom object macros with "password" option will get replaced in the command_line in host and service status overviews',
                'section'  => 'FRONTEND',
                'created'  => '2020-04-17 08:43:17',
                'modified' => '2020-04-17 08:43:17'
            ],
            (int)88  => [
                'key'      => 'FRONTEND.BLUR_COMMAND_LINE_IN_BROWSER',
                'value'    => '0',
                'info'     => 'If enabled the check command line in host and service browser will be displayed blurry. On mouseover the command line will be displayed normally.',
                'section'  => 'FRONTEND',
                'created'  => '2020-07-12 16:09:17',
                'modified' => '2020-07-12 16:09:17'
            ],
            (int)89  => [
                'key'      => 'CHECK_MK.DOCKERIZED',
                'value'    => '1',
                'info'     => 'Determine if Checkmk is running locally or inside of a Docker container',
                'section'  => 'CHECK_MK',
                'created'  => '2021-08-13 13:07:17',
                'modified' => '2021-08-13 13:07:17'
            ],
            (int)90  => [
                'key'      => 'CHECK_MK.DOCKER_API',
                'value'    => 'http://127.0.0.1:1234',
                'info'     => 'Base URL of the Checkmk API endpoint inside of Docker',
                'section'  => 'CHECK_MK',
                'created'  => '2021-08-13 13:07:17',
                'modified' => '2021-08-13 13:07:17'
            ],
            (int)91  => [
                'key'      => 'FRONTEND.LDAP.GROUP_QUERY',
                'value'    => 'ObjectClass=Group',
                'info'     => 'This query filter is used by openITCOCKPIT to import LDAP groups into the database',
                'section'  => 'FRONTEND',
                'created'  => '2022-01-06 08:52:17',
                'modified' => '2022-01-06 08:52:17'
            ],
            (int)92  => [
                'key'      => 'INIT.OPENITCOCKPIT_NODE',
                'value'    => 'systemctl status openitcockpit-node',
                'info'     => 'Command to query the status of openITCOCKPITs Node.js Server backend',
                'section'  => 'INIT',
                'created'  => '2022-05-03 13:56:17',
                'modified' => '2022-05-03 13:56:17'
            ],
            (int)93  => [
                'key'      => 'SYSTEM_HEALTH.CPU.LOAD_WARNING',
                'value'    => '',
                'info'     => 'Warning threshold for the CPU load (openITCOCKPIT evaluates the value of load15). If empty, openITCOCKPIT will calculate the threshold values automatically based on the CPU core count of the system.',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)94  => [
                'key'      => 'SYSTEM_HEALTH.CPU.LOAD_CRITICAL',
                'value'    => '',
                'info'     => 'Critical threshold for the CPU load (openITCOCKPIT evaluates the value of load15). If empty, openITCOCKPIT will calculate the threshold values automatically based on the CPU core count of the system.',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)95  => [
                'key'      => 'SYSTEM_HEALTH.RAM.WARNING_IN_PERCENT',
                'value'    => '80',
                'info'     => 'The warning threshold. Defaults to 80',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)96  => [
                'key'      => 'SYSTEM_HEALTH.RAM.CRITICAL_IN_PERCENT',
                'value'    => '90',
                'info'     => 'The critical threshold. Defaults to 90',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)97  => [
                'key'      => 'SYSTEM_HEALTH.SWAP.WARNING_IN_PERCENT',
                'value'    => '40',
                'info'     => 'The warning threshold. Defaults to 40',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)98  => [
                'key'      => 'SYSTEM_HEALTH.SWAP.CRITICAL_IN_PERCENT',
                'value'    => '50',
                'info'     => 'The critical threshold. Defaults to 50',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)99  => [
                'key'      => 'SYSTEM_HEALTH.DISK.WARNING_IN_PERCENT',
                'value'    => '80',
                'info'     => 'The warning threshold. Defaults to 80',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)100 => [
                'key'      => 'SYSTEM_HEALTH.DISK.CRITICAL_IN_PERCENT',
                'value'    => '90',
                'info'     => 'The critical threshold. Defaults to 90',
                'section'  => 'SYSTEM_HEALTH',
                'created'  => '2023-03-16 13:45:00',
                'modified' => '2023-03-16 13:45:00'
            ],
            (int)101 => [
                'key'      => 'INIT.EVENT_COLLECTD_RESTART',
                'value'    => 'systemctl restart event-collectd',
                'info'     => 'Command to restart the Event-Collectd daemon of openITCOCKPIT',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)102 => [
                'key'      => 'INIT.EVENT_COLLECTD_RELOAD',
                'value'    => 'systemctl reload event-collectd',
                'info'     => 'Command to reload the the Event-Collectd daemon of openITCOCKPIT',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)103 => [
                'key'      => 'INIT.EVENT_COLLECTD_STATUS',
                'value'    => 'systemctl status event-collectd',
                'info'     => 'Command to get the current status of the Event-Collectd daemon of openITCOCKPIT',
                'section'  => 'INIT',
                'created'  => '2020-01-29 09:28:17',
                'modified' => '2020-01-29 09:28:17'
            ],
            (int)104 => [
                'key'      => 'FRONTEND.LDAP.OPENLDAP_GROUP_SCHEMA',
                'value'    => 'memberUid',
                'info'     => 'Specifying Group Memberships by Using the memberUid or uniqueMember (RFC 4519 2.40) attribute for OpenLDAP servers',
                'section'  => 'FRONTEND',
                'created'  => '2022-01-06 08:52:17',
                'modified' => '2022-01-06 08:52:17'
            ],
            (int)105 => [
                'key'      => 'FRONTEND.DISABLE_LOGIN_SOCIAL_BUTTONS',
                'value'    => '0',
                'info'     => 'Remove the social links from the login screen.',
                'section'  => 'FRONTEND',
                'created'  => '2025-03-19 15:17:17',
                'modified' => '2025-03-19 15:17:17'
            ],
            (int)106 => [
                'key'      => 'FRONTEND.ENABLE_COLUMN_LAYOUT_FOR_THE_LOGIN_PAGE',
                'value'    => '0',
                'info'     => 'The background image on the login page should not be displayed in full width, but only on the left part of the page.',
                'section'  => 'FRONTEND',
                'created'  => '2025-03-26 15:17:17',
                'modified' => '2025-03-26 15:17:17'
            ],
        ];

        return $data;
    }
}
