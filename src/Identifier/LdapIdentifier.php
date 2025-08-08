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


namespace App\Identifier;

use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsersTable;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\IdentifierInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use FreeDSx\Ldap\Exception\BindException;
use FreeDSx\Ldap\LdapClient;
use FreeDSx\Ldap\Operation\ResultCode;
use FreeDSx\Ldap\Operations;
use FreeDSx\Ldap\Search\Filters;

class LdapIdentifier extends AbstractIdentifier implements IdentifierInterface {

    /**
     * Identifies an user or service by the passed credentials
     *
     * @param array $credentials Authentication credentials
     * @return \ArrayAccess|array|null
     */
    public function identify(array $credentials): \ArrayAccess|array|null {
        if (!isset($credentials['username'])) {
            //or username === null
            return null;
        }

        $identity = $this->_findIdentity($credentials['username']);
        if (array_key_exists('password', $credentials) && $identity !== null) {
            $password = $credentials['password'];
            if (!$this->_checkPassword($identity, $credentials['username'], $password)) {
                return null;
            }
        }

        return $identity;
    }

    /**
     * Find a user record using the username/identifier provided.
     *
     * @param string $identifier The username/identifier.
     * @return \ArrayAccess|array|null
     */
    protected function _findIdentity(string $identifier) {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->getUserBySamAccountName($identifier);
        if ($user) {
            // Make all fields available as we need the user's password hash
            $user->setHidden([], false);
            return $user->toArray();
        }

        return null;
    }

    /**
     * Connect to LDAP Server and check provided credentials
     *
     * @param array|\ArrayAccess|null $identity The identity or null.
     * @param string|null $username The LDAP username.
     * @param string|null $password The password.
     * @return bool
     */
    protected function _checkPassword($identity, ?string $username, ?string $password): bool {
        if (empty($username) || empty($password)) {
            return false;
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArraySection('FRONTEND');

        //Connect to LDAP Server
        //
        //TLS Levels
        // 0 = plain
        // 1 = StartTLS
        // 2 = TLS (ldaps)
        $tlsLevel = (int)$systemsettings['FRONTEND']['FRONTEND.LDAP.USE_TLS'];

        try {
            $ldap = new LdapClient([
                'servers'               => [$systemsettings['FRONTEND']['FRONTEND.LDAP.ADDRESS']],
                'port'                  => (int)$systemsettings['FRONTEND']['FRONTEND.LDAP.PORT'],
                'ssl_allow_self_signed' => true,
                'ssl_validate_cert'     => false,
                'use_ssl'               => ($tlsLevel === 2), // If true, this will start a TLS connection (ldaps)
                'base_dn'               => $systemsettings['FRONTEND']['FRONTEND.LDAP.BASEDN'],
            ]);

            if ($tlsLevel === 1) {
                // Upgrade plain text communication to TLS through StartTLS
                $ldap->startTls();
            }


            $ldap->bind(
                sprintf(
                    '%s%s',
                    $systemsettings['FRONTEND']['FRONTEND.LDAP.USERNAME'],
                    $systemsettings['FRONTEND']['FRONTEND.LDAP.SUFFIX']
                ),
                $systemsettings['FRONTEND']['FRONTEND.LDAP.PASSWORD']
            );
        } catch (\Exception $e) {
            Log::error($e);
            $this->_errors[] = $e->getMessage();
            return false;
        }

        //Set Filters
        $filter = Filters::and(
            Filters::raw($systemsettings['FRONTEND']['FRONTEND.LDAP.QUERY']),
            Filters::equal('sAMAccountName', $identity->get('samaccountname'))
        );

        if ($systemsettings['FRONTEND']['FRONTEND.LDAP.TYPE'] === 'openldap') {
            $filter = Filters::and(
                Filters::raw($systemsettings['FRONTEND']['FRONTEND.LDAP.QUERY']),
                Filters::or(
                    Filters::equal('cn', $username), // Fix login for openLDAP https://github.com/openITCOCKPIT/openITCOCKPIT/commit/d14c28603dda76558ace345d8ad12dc69caaae08
                    Filters::equal('uid', $username)
                )
            );
        }

        //Print the filters as string for ldapsearch
        //FileDebugger::dump((string)$filter);
        // Microsoft Active Directory
        $search = Operations::search($filter, 'cn', 'memberof', 'dn');

        /** @var \FreeDSx\Ldap\Entry\Entries $entries */
        $entries = $ldap->search($search);

        $userDn = null;
        foreach ($entries as $entry) {
            /** @var \FreeDSx\Ldap\Entry\Entry $entry */

            $userDn = (string)$entry->getDn();
            $ldap->unbind(); //Remove ldap search account

            //TLS Levels
            // 0 = plain
            // 1 = StartTLS
            // 2 = TLS (ldaps)
            $tlsLevel = (int)$systemsettings['FRONTEND']['FRONTEND.LDAP.USE_TLS'];

            $ldap = new LdapClient([
                # Servers are tried in order until one connects
                'servers'               => [$systemsettings['FRONTEND']['FRONTEND.LDAP.ADDRESS']],
                'port'                  => (int)$systemsettings['FRONTEND']['FRONTEND.LDAP.PORT'],
                'ssl_allow_self_signed' => true,
                'ssl_validate_cert'     => false,
                'use_ssl'               => ($tlsLevel === 2), // If true, this will start a TLS connection (ldaps)
                'base_dn'               => $systemsettings['FRONTEND']['FRONTEND.LDAP.BASEDN'],
            ]);

            if ($tlsLevel === 1) {
                // Upgrade plain text communication to TLS through StartTLS
                $ldap->startTls();
            }

            if (!empty($userDn)) {
                try {
                    $ldap->bind($userDn, $password);
                    return true; // valid credentials :)
                } catch (BindException $e) {
                    Log::error($e);
                    if ($e->getCode() === ResultCode::INVALID_CREDENTIALS) {
                        //$this->_errors[] = __('Invalid username or password');
                        return false;
                    }

                    $this->_errors[] = $e->getMessage();
                    return false;
                } catch (\Exception $e) {
                    Log::error($e);
                    $this->_errors[] = $e->getMessage();
                    return false;
                }
            }
        }

        return false;
    }
}
