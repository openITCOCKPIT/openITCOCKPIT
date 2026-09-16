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

namespace App\Authenticator;

use App\Identifier\ApiTokenIdentifier;
use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\StatelessInterface;
use Cake\Log\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use itnovum\openITCOCKPIT\ApiTokens\ApiTokenIssuer;
use itnovum\openITCOCKPIT\ApiTokens\ApiTokenKeys;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authenticates a request carrying "Authorization: Bearer <token>" issued by
 * ApiTokenIssuer.
 *
 * The request then acts as the user named in the token, with that user's
 * containers and permissions, exactly as if they had logged in.
 *
 * Deliberately narrower than ApikeyAuthenticator: only the Authorization
 * header is read. A token in a query string ends up in the web server's access
 * log, and one in a cookie can be set by a response.
 *
 * The algorithm is fixed to ES256. JWT libraries decide how to verify from the
 * token's own header unless told otherwise, and a token claiming HS256 would
 * then be checked against whatever key material is at hand.
 *
 * Class ApiTokenAuthenticator
 * @package App\Authenticator
 */
class ApiTokenAuthenticator extends AbstractAuthenticator implements StatelessInterface {

    /**
     * @var array
     */
    protected array $_defaultConfig = [
        'header'   => 'Authorization',
        'prefix'   => 'Bearer',
        'audience' => ApiTokenIssuer::AUDIENCE_API,
        // Tolerated clock difference between issuer and verifier, in seconds.
        'leeway'   => 30,
    ];

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface {
        $token = $this->getToken($request);
        if ($token === null) {
            // Not a token request. The next authenticator decides.
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
        }

        $Keys = new ApiTokenKeys();
        if (!$Keys->exists()) {
            return new Result(null, Result::FAILURE_OTHER, ['API tokens are not set up on this system.']);
        }

        try {
            JWT::$leeway = (int)$this->getConfig('leeway');
            $claims = (array)JWT::decode($token, new Key($Keys->getPublicKey(), ApiTokenKeys::ALGORITHM));
        } catch (\Throwable $e) {
            // The reason goes to the log, not to the client.
            Log::warning(sprintf('ApiTokenAuthenticator: rejected token: %s', $e->getMessage()));

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID, ['Invalid API token.']);
        }

        if (!$this->hasAudience($claims, (string)$this->getConfig('audience'))) {
            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID, ['API token not issued for this service.']);
        }

        if (($claims['iss'] ?? null) !== ApiTokenIssuer::issuerOfThisSystem()) {
            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID, ['API token issued by another system.']);
        }

        $user = $this->_identifier->identify([
            ApiTokenIdentifier::CREDENTIAL_USER_ID => (int)($claims['sub'] ?? 0),
        ]);

        if (empty($user)) {
            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());
        }

        return new Result($user, Result::SUCCESS);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string|null
     */
    private function getToken(ServerRequestInterface $request): ?string {
        $header = trim($request->getHeaderLine((string)$this->getConfig('header')));
        $prefix = (string)$this->getConfig('prefix') . ' ';

        // Compared case-insensitively, as RFC 6750 allows. Anything else in
        // this header - an openITCOCKPIT API key, Basic auth - is not ours.
        if (strncasecmp($header, $prefix, strlen($prefix)) !== 0) {
            return null;
        }

        $token = trim(substr($header, strlen($prefix)));

        return $token === '' ? null : $token;
    }

    /**
     * "aud" may be a single value or a list (RFC 7519, 4.1.3).
     *
     * @param array $claims
     * @param string $audience
     * @return bool
     */
    private function hasAudience(array $claims, string $audience): bool {
        $claimed = $claims['aud'] ?? null;

        if (is_array($claimed)) {
            return in_array($audience, $claimed, true);
        }

        return $claimed === $audience;
    }

    /**
     * No challenge: a request without a token falls through to the other
     * authenticators, and one with an invalid token gets the same 403 as any
     * other unauthenticated API request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return void
     */
    public function unauthorizedChallenge(ServerRequestInterface $request): void {
    }
}
