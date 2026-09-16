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

namespace itnovum\openITCOCKPIT\ApiTokens;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use itnovum\openITCOCKPIT\Core\System\Health\SystemId;

/**
 * Issues short-lived tokens that let a component act as one user.
 *
 * For work that happens outside the user's own request - a queue worker, a
 * sidecar - and must still see only what that user may see. The token carries
 * the user id and nothing else about the user: rights are looked up when the
 * token is used, so a changed role or a deactivated account takes effect on
 * the next request.
 *
 * A token names the one service that may accept it. The openITCOCKPIT API only
 * accepts AUDIENCE_API, so a token issued for another service does not open it.
 *
 * Class ApiTokenIssuer
 * @package itnovum\openITCOCKPIT\ApiTokens
 */
class ApiTokenIssuer {

    /**
     * The openITCOCKPIT API itself.
     */
    public const AUDIENCE_API = 'openitcockpit-api';

    public const DEFAULT_LIFETIME = 300;

    /**
     * Upper bound for any lifetime a caller asks for. A token cannot be
     * revoked individually, so how long it lives is how long a leaked one
     * stays useful.
     */
    public const MAX_LIFETIME = 900;

    /**
     * @var ApiTokenKeys
     */
    private $Keys;

    /**
     * @var string
     */
    private $issuer;

    public function __construct(?ApiTokenKeys $Keys = null, ?string $issuer = null) {
        $this->Keys = $Keys ?? new ApiTokenKeys();
        $this->issuer = $issuer ?? self::issuerOfThisSystem();
    }

    /**
     * @param int $userId
     * @param string $audience
     * @param int $lifetime seconds, capped at MAX_LIFETIME
     * @return string
     */
    public function issue(int $userId, string $audience = self::AUDIENCE_API, int $lifetime = self::DEFAULT_LIFETIME): string {
        if ($userId <= 0) {
            throw new InvalidArgumentException('An API token needs a user id.');
        }
        if ($audience === '') {
            throw new InvalidArgumentException('An API token needs an audience.');
        }

        $now = time();
        $lifetime = max(1, min($lifetime, self::MAX_LIFETIME));

        $claims = [
            'iss' => $this->issuer,
            'sub' => (string)$userId,
            'aud' => $audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $lifetime,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($claims, $this->Keys->getPrivateKey(), ApiTokenKeys::ALGORITHM, $this->Keys->getKeyId());
    }

    /**
     * The value every token of this installation carries as "iss", and that
     * the authenticator requires.
     *
     * @return string
     */
    public static function issuerOfThisSystem(): string {
        return 'openitcockpit:' . (new SystemId())->getSystemId();
    }
}
