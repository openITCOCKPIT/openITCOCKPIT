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

class SslIdentifier extends AbstractIdentifier implements IdentifierInterface {

    /**
     * Identifies an user or service by the passed credentials
     *
     * @param array $credentials Authentication credentials
     * @return \ArrayAccess|array|null
     */
    public function identify(array $credentials): \ArrayAccess|array|null {
        // Has the client send a valid SSL certificate?
        if (isset($_SERVER['SSL_VERIFIED']) && $_SERVER['SSL_VERIFIED'] === 'SUCCESS' && isset($_SERVER['SSL_CERT'])) {
            $certificate = openssl_x509_parse(urldecode($_SERVER['SSL_CERT']));
            return $this->_findIdentity($certificate);
        }

        if (isset($_SERVER['SSL_VERIFIED']) && $_SERVER['SSL_VERIFIED'] !== 'NONE') {
            if ($_SERVER['SSL_VERIFIED'] !== 'SUCCESS') {
                Log::error('Invalid client certificate!');
                $this->_errors[] = __('Invalid client certificate');
            }
        }

        return null;
    }

    /**
     * Find a user record using the username/identifier provided.
     *
     * @param array $certificate The parsed SSL Client certificate.
     * @return \ArrayAccess|array|null
     */
    protected function _findIdentity(array $certificate): \ArrayAccess|array|null {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        // Use email field from certificate
        if (isset($certificate['subject']['emailAddress'])) {
            // Data example
            // 'subject' => [
            //     'CN' => 'Max Mustermann',
            //     'OU' => 'it-novum',
            //     'emailAddress' => 'Max.Mustermann@it-novum.com'
            // ],
            $user = $UsersTable->getUserByEmailForLogin($certificate['subject']['emailAddress']);
            if ($user) {
                // Make all fields available as we need the user's password hash
                $user->setHidden([], false);
                return $user->toArray();
            }
            return null;
        }

        // Try to use CN (commonName) as email
        if (isset($certificate['subject']['CN'])) {
            // Date example
            // 'name' => '/Max.Mustermann@it-novum.com/OU=it-novum/O=it-novum/C=DE',
            // 'subject' => [
            //     'CN' => 'Max.Mustermann@it-novum.com',
            //     'OU' => 'it-novum',
            //     'O' => 'it-novum',
            //     'C' => 'DE'
            // ],

            $user = $UsersTable->getUserByEmailForLogin($certificate['subject']['CN']);
            if ($user !== null) {
                // Make all fields available as we need the user's password hash
                $user->setHidden([], false);
                return $user->toArray();
            }

            // Is this an "FHG" certificate?
            // Date example
            // 'name' => '/CN=Max Mustermann/OU=People/OU=it-novum/O=it-novum/C=DE',
            // 'subject' => [
            //     'CN' => 'Max Mustermann',
            //     'OU' => [
            //         (int) 0 => 'People',
            //         (int) 1 => 'it-novum'
            //     ],
            //     'O' => 'it-novum',
            //     'C' => 'DE'
            // ],


            $names = explode(' ', $certificate['subject']['CN']);
            if (sizeof($names) >= 2) {
                $firstname = $names[0];
                $lastname = $names[1];
                $ou = null;

                if (isset($certificate['subject']['OU'])) {
                    if (!is_array($certificate['subject']['OU'])) {
                        $certificate['subject']['OU'] = [
                            $certificate['subject']['OU']
                        ];
                    }

                    foreach ($certificate['subject']['OU'] as $index => $item) {
                        if ($item !== 'People') {
                            $ou = $item;
                        }
                    }

                    if (!empty($ou)) {
                        $emailLike = sprintf('@%s', $ou);
                        $user = $UsersTable->getUserForFhgLogin(
                            $firstname, // = Max AND
                            $lastname,  // = Mustermann AND
                            $emailLike  // LIKE %@it-novum%
                        );

                        if ($user !== null) {
                            //We found a user by first and last name and email like
                            // Make all fields available as we need the user's password hash
                            $user->setHidden([], false);
                            return $user->toArray();
                        }
                    }
                }

                if ($ou === null) {
                    // Try to find the user with first and last name only
                    $user = $UsersTable->getUserForFhgLoginInsecure(
                        $firstname, // = Max AND
                        $lastname  // = Mustermann
                    );

                    if ($user !== null) {
                        //We found a user by first and last name
                        // Make all fields available as we need the user's password hash
                        $user->setHidden([], false);
                        return $user->toArray();
                    }
                }

                if (empty($user)) {
                    //No user found, but user has a valid SSL cert - return a default user if defined.
                    try {
                        /** @var SystemsettingsTable $SystemsettingsTable */
                        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

                        $entity = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.CERT.DEFAULT_USER_EMAIL');
                        if ($entity->get('value') !== '') {
                            $user = $UsersTable->getUserByEmailForLogin($entity->get('value'));
                            if ($user) {
                                // Make all fields available as we need the user's password hash
                                $user->setHidden([], false);
                                return $user->toArray();
                            }
                            return null;
                        }
                    } catch (\Exception $e) {
                        return null;
                    }
                }
            }
        }

        return null;
    }
}
