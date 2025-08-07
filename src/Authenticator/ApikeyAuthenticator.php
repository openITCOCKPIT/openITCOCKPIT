<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace App\Authenticator;


use App\Model\Table\ApikeysTable;
use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\StatelessInterface;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\ServerRequestInterface;

class ApikeyAuthenticator extends AbstractAuthenticator implements StatelessInterface {

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request to get authentication information from.
     * @return \Authentication\Authenticator\ResultInterface Returns a result object.
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface {
        $data = ['apikey' => null];

        if (!empty($request->getHeaderLine('Authorization'))) {
            $authHeaderKey = $this->getConfig('apikeyPrefix');
            $data['apikey'] = trim(substr($request->getHeaderLine($this->getConfig('header')), strlen($authHeaderKey)));
        }

        $cookies = $request->getCookieParams();
        if (!empty($cookies['Authorization'])) {
            $authHeaderKey = $this->getConfig('apikeyPrefix');
            $data['apikey'] = trim(substr($cookies['Authorization'], strlen($authHeaderKey)));
        }

        if (isset($request->getQueryParams()[$this->getConfig('queryParam')])) {
            $data['apikey'] = trim($request->getQueryParams()[$this->getConfig('queryParam')]);
        }

        $user = $this->_identifier->identify($data);
        if (empty($user) || $user === null) {
            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());
        }
        //$request->withoutHeader('Set-Cookie');

        $result = new Result($user, Result::SUCCESS);

        if ($result->isValid() && $data['apikey'] !== null) {
            $this->saveLastUseDate($data['apikey']);
        }

        return $result;
    }

    /**
     *  Gets the record by api key and saves the last use date
     * Returns true for successful
     *
     * @param string $apiKey
     * @return bool
     */
    public function saveLastUseDate($apiKey) {

        /** @var $ApikeysTable ApikeysTable */
        $ApikeysTable = TableRegistry::getTableLocator()->get('Apikeys');
        $apiKeyId = $ApikeysTable->getIdByApiKey($apiKey);


        if (!empty($apiKeyId)) {

            $apiKeyToUpdate = $ApikeysTable->get($apiKeyId);
            $apiKeyToUpdate->set('last_use', \Cake\I18n\DateTime::now());
            if (!$ApikeysTable->save($apiKeyToUpdate)) {
                Log::error(sprintf(
                    'saveLastUseDate: Could not save api key [%s] %s',
                    $apiKeyToUpdate->id,
                    $apiKeyToUpdate->last_use
                ));
                Log::error(json_encode($apiKeyToUpdate->getErrors()));
                return false;
            }

        } else {
            Log::error(sprintf(
                'saveLastUseDate: Could not save api key because id not found.'
            ));
            return false;
        }

        return true;

    }


    /**
     * Create a challenge exception
     *
     * Create an exception with the appropriate headers and response body
     * to challenge a request that has missing or invalid credentials.
     *
     * This is primarily used by authentication methods that use the WWW-Authorization
     * header.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return void
     * @throws \Authentication\Authenticator\AuthenticationRequiredException
     */
    public function unauthorizedChallenge(ServerRequestInterface $request): void {
        //throw new AuthenticationRequiredException([], '');
    }
}
