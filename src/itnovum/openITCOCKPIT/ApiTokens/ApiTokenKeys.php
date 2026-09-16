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

use RuntimeException;

/**
 * The ECDSA P-256 key pair that signs and verifies API tokens.
 *
 * ES256 through OpenSSL rather than EdDSA through sodium: sodium is not
 * available for the PHP 8.2 and 8.3 module streams on EL9, while ext-openssl
 * is a requirement of openITCOCKPIT on every platform.
 *
 * Whoever holds the private key can issue a token for any user, so the key file
 * is readable by root and the web server group only. The public half is also
 * written as a JSON Web Key Set, for services that verify tokens without being
 * able to issue them.
 *
 * The files live beside the system id: /opt/openitc/etc on a package
 * installation, /opt/openitc/var in a container, where that directory is a
 * volume. OITC_API_TOKEN_KEY_DIR overrides both.
 *
 * Replacing the key invalidates every token issued before. Tokens live for
 * minutes, so that is a way to revoke them all, not a loss.
 *
 * Class ApiTokenKeys
 * @package itnovum\openITCOCKPIT\ApiTokens
 */
class ApiTokenKeys {

    public const ALGORITHM = 'ES256';

    /**
     * @var string
     */
    private $directory;

    public function __construct(?string $directory = null) {
        if ($directory === null) {
            $directory = env('OITC_API_TOKEN_KEY_DIR', null);
        }

        if (empty($directory)) {
            $directory = (defined('IS_CONTAINER') && IS_CONTAINER)
                ? '/opt/openitc/var/api_tokens'
                : '/opt/openitc/etc/api_tokens';
        }

        $this->directory = rtrim($directory, DS);
    }

    public function getSigningKeyFile(): string {
        return $this->directory . DS . 'signing_key.json';
    }

    public function getJwksFile(): string {
        return $this->directory . DS . 'jwks.json';
    }

    public function exists(): bool {
        return is_file($this->getSigningKeyFile());
    }

    /**
     * Creates the key pair unless one exists.
     *
     * @return bool true if a new key pair was written
     */
    public function generate(): bool {
        if ($this->exists()) {
            return false;
        }

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0750, true);
        }

        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ]);
        if ($privateKey === false) {
            throw new RuntimeException('Could not create an API token signing key: ' . openssl_error_string());
        }

        openssl_pkey_export($privateKey, $privatePem);
        $details = openssl_pkey_get_details($privateKey);

        $signingKey = [
            'kid'     => bin2hex(random_bytes(8)),
            'alg'     => self::ALGORITHM,
            'private' => $privatePem,
            'public'  => $details['key'],
        ];

        // Written under a restrictive umask and renamed into place, so the
        // private key is never readable by others, not even for a moment.
        $previousUmask = umask(0027);
        try {
            $temporary = $this->getSigningKeyFile() . '.tmp';
            file_put_contents($temporary, json_encode($signingKey, JSON_PRETTY_PRINT));
            chmod($temporary, 0640);
            rename($temporary, $this->getSigningKeyFile());
        } finally {
            umask($previousUmask);
        }

        file_put_contents($this->getJwksFile(), json_encode($this->toJwks($signingKey['kid'], $details), JSON_PRETTY_PRINT));
        chmod($this->getJwksFile(), 0644);

        return true;
    }

    /**
     * @return string PEM encoded private key
     */
    public function getPrivateKey(): string {
        return $this->read()['private'];
    }

    /**
     * @return string PEM encoded public key
     */
    public function getPublicKey(): string {
        return $this->read()['public'];
    }

    public function getKeyId(): string {
        return $this->read()['kid'];
    }

    /**
     * @return array{kid: string, alg: string, private: string, public: string}
     */
    private function read(): array {
        if (!$this->exists()) {
            throw new RuntimeException(sprintf(
                'No API token signing key at %s. Run: oitc api_tokens --generate-key',
                $this->getSigningKeyFile()
            ));
        }

        $signingKey = json_decode((string)file_get_contents($this->getSigningKeyFile()), true);
        if (!is_array($signingKey) || empty($signingKey['private']) || empty($signingKey['public']) || empty($signingKey['kid'])) {
            throw new RuntimeException(sprintf('The API token signing key at %s is unreadable.', $this->getSigningKeyFile()));
        }

        return $signingKey;
    }

    /**
     * The public key as a JSON Web Key Set (RFC 7517, EC keys per RFC 7518).
     *
     * @param string $keyId
     * @param array $details from openssl_pkey_get_details()
     * @return array
     */
    private function toJwks(string $keyId, array $details): array {
        // OpenSSL returns the coordinates without leading zero bytes, RFC 7518
        // requires the full 32 bytes of a P-256 coordinate.
        $coordinate = function (string $bytes): string {
            $bytes = str_pad($bytes, 32, "\0", STR_PAD_LEFT);

            return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        };

        return [
            'keys' => [
                [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'use' => 'sig',
                    'alg' => self::ALGORITHM,
                    'kid' => $keyId,
                    'x'   => $coordinate($details['ec']['x']),
                    'y'   => $coordinate($details['ec']['y']),
                ],
            ],
        ];
    }
}
