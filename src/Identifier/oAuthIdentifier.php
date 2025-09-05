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


namespace App\Identifier;

use App\Model\Table\UsersTable;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\IdentifierInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\oAuth\oAuthClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class oAuthIdentifier extends AbstractIdentifier implements IdentifierInterface {

    /**
     * Identifies an user or service by the passed credentials
     *
     * @param array $credentials Authentication credentials
     * @return \ArrayAccess|array|null
     */
    public function identify(array $credentials): \ArrayAccess|array|null {
        if (!isset($credentials['code'])) {
            //or code === null
            return null;
        }

        // Query the oAuth Server to get more information about the user
        $oAuthClient = new oAuthClient();

        // Try to get an access token using the authorization code grant.
        try {
            $accessToken = $oAuthClient->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            // Using the access token, we may look up details about the resource owner.
            $emailAddress = $oAuthClient->getEmailAddressOfResourceOwner($accessToken);

            $identity = $this->_findIdentity($emailAddress);
            if ($identity === null) {
                //$this->_errors[] = __('No user found in local database with email address: ' . $emailAddress);
                Log::error('No oAuth user found for email: ' . $emailAddress);
            }
            return $identity;


        } catch (IdentityProviderException $e) {
            Log::error('Could net request oAuth access token.');
            Log::error($e->getMessage());
        }

        return null;
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
        $user = $UsersTable->getOAuthUserByEmailForLogin($identifier);
        if ($user) {
            // Make all fields available as we need the user's password hash
            $user->setHidden([], false);
            return $user->toArray();
        }

        return null;
    }

}
