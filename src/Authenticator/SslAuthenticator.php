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

namespace App\Authenticator;


use App\Model\Table\EventlogsTable;
use App\Model\Table\UsersTable;
use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Psr\Http\Message\ServerRequestInterface;

class SslAuthenticator extends AbstractAuthenticator {

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request to get authentication information from.
     * @return \Authentication\Authenticator\ResultInterface Returns a result object.
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface {

        $data = [];
        $user = $this->_identifier->identify($data);

        if (empty($user)) {
            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());

            /**
             * this if statement prevents that the save date will be triggered on every single request.
             * possible solution is to save the date only once a day, but this needs an extra query
             */
        } else if ($_SERVER['REQUEST_URI'] == '/') {
            /** @var UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('Users');
            /** @var EventlogsTable $EventlogsTable */
            $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

            $UsersTable->saveLastLoginDate($user['email']);
            $userFromDb = $UsersTable->getUserByEmailForLoginLog($user['email']);
            if (!empty($userFromDb)) {

                $containerIds = Hash::extract($userFromDb, 'containers.{n}.id');

                $containerRoleContainerIds = $UsersTable->getContainerIdsOfUserContainerRoles(['User' => $userFromDb]);
                $containerIds = array_merge($containerIds, $containerRoleContainerIds);

                $loginData = $EventlogsTable->createDataJsonForUser($userFromDb->get('email'));
                $fullName = $userFromDb->get('firstname') . ' ' . $userFromDb->get('lastname');
                $EventlogsTable->saveNewEntity('login', 'User', $userFromDb->id, $fullName, $loginData, $containerIds);
            }

        }

        return new Result($user, Result::SUCCESS);
    }

}
