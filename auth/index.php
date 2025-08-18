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


/***************************************************************
 * Auth Proxy for Grafana
 * All requests to /grafana will get redirected to http://127.0.0.1:8085 (this script)
 * by Nginx first.
 * This script will check if a valid openITCOCKPIT Auth cookie exists.
 * If yes the script will return an HTTP 200 Ok.
 *
 * If no valid user is found or no cookie is passed, the script returns a 401 Unauthorized
 * and Nginx will block the access to Grafana.
 */

use App\Identifier\ApikeyIdentifier;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\IdentifierCollection;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Log\Log;

// Use tail -F /opt/openitc/frontend/auth/debug.log to trace the debug file...
$debug = false;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__ . DS . '..' . DS . 'vendor' . DS . 'autoload.php';
require_once __DIR__ . DS . '..' . DS . 'config' . DS . 'bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($debug === true) {
    $file = fopen('debug.log', 'w+');

    fwrite($file, var_export('$_SERVER:' . PHP_EOL, true));
    fwrite($file, var_export($_SERVER, true));

    fwrite($file, var_export('$_COOKIE:' . PHP_EOL, true));
    fwrite($file, var_export($_COOKIE, true));

    fwrite($file, var_export('$_REQUEST:' . PHP_EOL, true));
    fwrite($file, var_export($_REQUEST, true));

    fwrite($file, var_export('$_SESSION:' . PHP_EOL, true));
    fwrite($file, var_export($_SESSION, true));

    //fwrite($file, var_export('HTTP Headers:' . PHP_EOL, true));
    //fwrite($file, var_export(getallheaders(), true));
}

try {
    if (isset($_SESSION['Auth'])) {
        if (!empty($_SESSION['Auth']) && is_array($_SESSION['Auth'])) {
            // CakePHP 5.x
            //Login success
            //The current session contains a valid openITCOCKPIT user...
            header("HTTP/1.0 200 Ok");
            return;
        }

        if (!is_array($_SESSION['Auth']) && get_class($_SESSION['Auth']) === 'App\Model\Entity\User') {
            // CakePHP 4.x
            //Login success
            //The current session contains a valid openITCOCKPIT user...
            header("HTTP/1.0 200 Ok");
            return;
        }
    }

    if (isset($_COOKIE['CookieAuth'])) {
        //No user object in the session... Lets check the cookie.
        //Validate if this is a valid openITCOCKPIT login cookie...
        $token = json_decode(rawurldecode($_COOKIE['CookieAuth']), true);

        if ($token === null || count($token) !== 2) {
            Log::error('GrafanaModule Auth: Cookie token is invalid.');
            throw new UnauthorizedException();
        }

        [$username, $tokenHash] = $token;

        $IdentifierCollection = new IdentifierCollection([
            'Authentication.Password' => [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password'
                ]
            ]
        ]);

        $identity = $IdentifierCollection->identify(['username' => $username]);

        if (empty($identity)) {
            throw new UnauthorizedException();
        }

        $plainToken = $identity['email'] . $identity['password'];

        $DefaultPasswordHasher = new DefaultPasswordHasher();
        if ($DefaultPasswordHasher->check($plainToken, $tokenHash)) {
            //Login success
            if ($debug) {
                fwrite($file, 'Login Ok !!');
            }
            header("HTTP/1.0 200 Ok");
            return;
        }

        throw new UnauthorizedException();
    }

    if (isset($_COOKIE['Authorization'])) {
        // This is used if openITCOCKPIT is embedded into an iframe via /iframe-oitc/
        $apiKeyArray = explode('X-OITC-API ', $_COOKIE['Authorization']);

        if (sizeof($apiKeyArray) === 2) {
            $apikey = $apiKeyArray[1];
            $IdentifierCollection = new IdentifierCollection([
                'Authentication.Apikey' => [
                    'className' => ApikeyIdentifier::class
                ]
            ]);

            $identity = $IdentifierCollection->identify(['apikey' => $apikey]);

            if (!empty($identity)) {
                //Login success
                if ($debug) {
                    fwrite($file, 'Login Ok !!');
                }
                header("HTTP/1.0 200 Ok");
                return;
            }

            throw new UnauthorizedException();
        }
    }
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        // This is used if Grafana is embedded into an iframe via /iframe-oitc/
        $apiKeyArray = explode('X-OITC-API ', $_SERVER['HTTP_AUTHORIZATION']);

        if (sizeof($apiKeyArray) === 2) {
            $apikey = $apiKeyArray[1];
            $IdentifierCollection = new IdentifierCollection([
                'Authentication.Apikey' => [
                    'className' => ApikeyIdentifier::class
                ]
            ]);

            $identity = $IdentifierCollection->identify(['apikey' => $apikey]);

            if (!empty($identity)) {
                //Login success
                if ($debug) {
                    fwrite($file, 'Login Ok !!');
                }
                header("HTTP/1.0 200 Ok");
                return;
            }
            throw new UnauthorizedException();
        }
    }
} catch (\Exception $e) {

}

if ($debug) {
    fwrite($file, 'Unauthorized !!');
}
header("HTTP/1.0 401 Unauthorized");

if ($debug) {
    fclose($file);
}
