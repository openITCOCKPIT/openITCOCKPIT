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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\SetupShell;


class MailConfigurator {
    /**
     * @var MailConfigValue
     */
    private $host;

    /**
     * @var MailConfigValueInt
     */
    private $port;

    /**
     * @var MailConfigValue
     */
    private $username;

    /**
     * @var MailConfigValue
     */
    private $password;

    /**
     * MailConfigurator constructor.
     *
     * @param MailConfigValue $host
     * @param MailConfigValueInt $port
     * @param MailConfigValue $username
     * @param MailConfigValue $password
     */
    public function __construct(MailConfigValue $host, MailConfigValueInt $port, MailConfigValue $username, MailConfigValue $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getConfig() {
        return sprintf(
            $this->getTemplate(),
            $this->host->getValueForConfig(),
            $this->port->getValueForConfig(),
            $this->username->getValueForConfig(),
            $this->password->getValueForConfig()
        );
    }

    /**
     * @return string
     */
    public function getTemplate() {
        $template = '<?php
return [
    /**
     * Email delivery profiles
     *
     * Delivery profiles allow you to predefine various properties about email
     * messages from your application and give the settings a name. This saves
     * duplication across your application and makes maintenance and development
     * easier. Each profile accepts a number of keys. See `Cake\Mailer\Email`
     * for more information.
     */
    \'Email\' => [
        \'default\' => [
            \'transport\' => \'default\',
            \'from\'      => \'you@localhost\',
            //\'charset\' => \'utf-8\',
            //\'headerCharset\' => \'utf-8\',
        ],
    ],

    /**
     * Email configuration.
     *
     * By defining transports separately from delivery profiles you can easily
     * re-use transport configuration across multiple profiles.
     *
     * You can specify multiple configurations for production, development and
     * testing.
     *
     * Each transport needs a `className`. Valid options are as follows:
     *
     *  Mail   - Send using PHP mail function
     *  Smtp   - Send using SMTP
     *  Debug  - Do not send the email, just return the result
     *
     * You can add custom transports (or override existing transports) by adding the
     * appropriate file to src/Mailer/Transport. Transports should be named
     * \'YourTransport.php\', where \'Your\' is the name of the transport.
     */

    \'EmailTransport\' => [
        \'default\' => [
            \'className\' => \Cake\Mailer\Transport\MailTransport::class,
            /*
             * The following keys are used in SMTP transports:
             */
            \'host\'      => %s,
            \'port\'      => %s,
            \'timeout\'   => 30,
            \'username\'  => %s,
            \'password\'  => %s,
            \'client\'    => null,
            \'tls\'       => null,
            \'url\'       => env(\'EMAIL_TRANSPORT_DEFAULT_URL\', null),
        ],
    ]
];
';

        return $template;
    }
}
