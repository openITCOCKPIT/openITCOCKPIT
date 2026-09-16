<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace App\Identifier;

use App\Model\Table\UsersTable;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\IdentifierInterface;
use Cake\ORM\TableRegistry;

/**
 * Resolves the user named in a verified API token.
 *
 * The token has already been checked by ApiTokenAuthenticator. What is left
 * is whether the account still exists and is active: a token issued a minute
 * before a user was deactivated must not outlive the deactivation.
 *
 * Class ApiTokenIdentifier
 * @package App\Identifier
 */
class ApiTokenIdentifier extends AbstractIdentifier implements IdentifierInterface {

    public const CREDENTIAL_USER_ID = 'api_token_user_id';

    /**
     * @param array $credentials
     * @return \ArrayAccess|array|null
     */
    public function identify(array $credentials): \ArrayAccess|array|null {
        $userId = (int)($credentials[self::CREDENTIAL_USER_ID] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->getActiveUserByIdForApiToken($userId);
        if ($user === null) {
            return null;
        }

        // The same shape ApikeyIdentifier returns, so the rest of the
        // application cannot tell how the user authenticated.
        $user->setHidden([], false);

        return $user->toArray();
    }
}
