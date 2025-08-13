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

/*
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
 * environment information.
 *
 * @param string $key Environment variable name.
 * @param string|bool|null $default Specify a default value in case the environment variable is not defined.
 * @return string|float|int|bool|null Environment variable setting.
 * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#env
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * We use this function as the CakePHP env() function is not available in this part of the bootstrap.
 */
function envBootstrap(string $key, string|float|int|bool|null $default = null): string|float|int|bool|null {
    if ($key === 'HTTPS') {
        if (isset($_SERVER['HTTPS'])) {
            return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }

        return str_starts_with((string)envBootstrap('SCRIPT_URI'), 'https://');
    }

    if ($key === 'SCRIPT_NAME' && envBootstrap('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
        $key = 'SCRIPT_URL';
    }

    $val = $_SERVER[$key] ?? $_ENV[$key] ?? null;
    assert($val === null || is_scalar($val));
    if ($val == null && getenv($key) !== false) {
        $val = (string)getenv($key);
    }

    if ($key === 'REMOTE_ADDR' && $val === envBootstrap('SERVER_ADDR')) {
        $addr = envBootstrap('HTTP_PC_REMOTE_ADDR');
        if ($addr !== null) {
            $val = $addr;
        }
    }

    if ($val !== null) {
        return $val;
    }

    switch ($key) {
        case 'DOCUMENT_ROOT':
            $name = (string)envBootstrap('SCRIPT_NAME');
            $filename = (string)envBootstrap('SCRIPT_FILENAME');
            $offset = 0;
            if (!str_ends_with($name, '.php')) {
                $offset = 4;
            }

            return substr($filename, 0, -(strlen($name) + $offset));
        case 'PHP_SELF':
            return str_replace((string)envBootstrap('DOCUMENT_ROOT'), '', (string)envBootstrap('SCRIPT_FILENAME'));
        case 'CGI_MODE':
            return PHP_SAPI === 'cgi';
    }

    return $default;
}

/*
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */

/*
 * The full path to the directory which holds "src", WITHOUT a trailing DS.
 */
define('ROOT', dirname(__DIR__));

/*
 * The actual directory name for the application directory. Normally
 * named 'src'.
 */
define('APP_DIR', 'src');

/*
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);

/*
 * Path to the config directory.
 */
define('CONFIG', ROOT . DS . 'config' . DS);

/*
 * File path to the webroot directory.
 *
 * To derive your webroot from your webserver change this to:
 *
 * `define('WWW_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DS) . DS);`
 */
if (!defined('WWW_ROOT')) {
    //@todo remove the if and always define the WWW_ROOT.
    //This is to load cake2 and cake4 at the same time.
    define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
}

$isCli = PHP_SAPI === 'cli';

// This var determins if openITCOCKPIT is running inside a workhorse container like mod_gearman
// It yes, we use different path for logfiles and caching to avoid permission issues
$isWorkhorseContainer = filter_var(envBootstrap('IS_WORKHORSE_CONTAINER', false), FILTER_VALIDATE_BOOLEAN);

/*
 * Path to the tests directory.
 */
define('TESTS', ROOT . DS . 'tests' . DS);

/*
 * Path to the temporary files directory.
 */

if (!isset($_SERVER['USER'])) {
    // Must be inside a container or some other scatchy setup
    $_SERVER['USER'] = 'root';
    if (getmyuid() > 0) {
        // Probably we are nagios?
        $_SERVER['USER'] = 'nagios';
    }
}

if ($isWorkhorseContainer === false) {
    // Default installation of openITCOCKPIT via apt, dnf or git
    // Also used if openITCOCKPIT is running inside a container like docker
    if ($isCli === false) {
        // www-data
        define('TMP', ROOT . DS . 'tmp' . DS);
    } else {
        // root or nagios
        if ($_SERVER['USER'] !== 'root') {
            //nagios user or so
            define('TMP', ROOT . DS . 'tmp' . DS . 'nagios' . DS);
        } else {
            //root user
            define('TMP', ROOT . DS . 'tmp' . DS . 'cli' . DS);
        }
    }
} else {
    // This openITCOCKPIT is running inside a workhorse container like mod_gearman
    // The primary job of this container is only to provide the openITCOCKPIT CLI backend to be able
    // to execute notification commands or the evc check plugin
    if ($isCli === false) {
        // www-data
        define('TMP', DS . 'tmp' . DS . 'openitcockpit' . DS . 'tmp'); // /tmp/openitcockpit/tmp/
    } else {
        // root or nagios
        if ($_SERVER['USER'] !== 'root') {
            //nagios user or so
            define('TMP', DS . 'tmp' . DS . 'openitcockpit' . DS . 'tmp' . DS . 'nagios' . DS); // /tmp/openitcockpit/tmp/nagios
        } else {
            //root user
            define('TMP', DS . 'tmp' . DS . 'openitcockpit' . DS . 'tmp' . DS . 'cli' . DS); // /tmp/openitcockpit/tmp/cli
        }
    }
}


/*
 * Path to the logs directory.
 */
if ($isWorkhorseContainer === false) {
    // Default installation of openITCOCKPIT via apt, dnf or git
    // Also used if openITCOCKPIT is running inside a container like docker
    if ($isCli === false) {
        //www-data
        define('LOGS', ROOT . DS . 'logs' . DS);
    } else {
        if ($_SERVER['USER'] !== 'root') {
            define('LOGS', ROOT . DS . 'logs' . DS . 'nagios' . DS);
        } else {
            define('LOGS', ROOT . DS . 'logs' . DS);
        }
    }
} else {
    // This openITCOCKPIT is running inside a workhorse container like mod_gearman
    // The primary job of this container is only to provide the openITCOCKPIT CLI backend to be able
    // to execute notification commands or the evc check plugin
    if ($isCli === false) {
        //www-data
        define('LOGS', DS . 'tmp' . DS . 'openitcockpit' . DS . 'logs' . DS); // /tmp/openitcockpit/logs/
    } else {
        if ($_SERVER['USER'] !== 'root') {
            define('LOGS', DS . 'tmp' . DS . 'openitcockpit' . DS . 'logs' . DS . 'nagios' . DS); // /tmp/openitcockpit/logs/nagios
        } else {
            define('LOGS', DS . 'tmp' . DS . 'openitcockpit' . DS . 'logs' . DS); // /tmp/openitcockpit/logs/
        }
    }
}

/*
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
if ($isWorkhorseContainer === false) {
    // Default installation of openITCOCKPIT via apt, dnf or git
    // Also used if openITCOCKPIT is running inside a container like docker
    if ($isCli === false) {
        //www-data
        define('CACHE', TMP . 'cache' . DS);
    } else {
        if ($_SERVER['USER'] !== 'root') {
            //nagios user or so
            define('CACHE', TMP . 'cache' . DS . 'nagios' . DS);
        } else {
            //root user
            define('CACHE', TMP . 'cache' . DS . 'cli' . DS);
        }
    }
} else {
    // This openITCOCKPIT is running inside a workhorse container like mod_gearman
    // The primary job of this container is only to provide the openITCOCKPIT CLI backend to be able
    // to execute notification commands or the evc check plugin
    if ($isCli === false) {
        //www-data
        define('CACHE', DS . 'tmp' . DS . 'openitcockpit' . DS . 'cache' . DS); // /tmp/openitcockpit/cache/
    } else {
        if ($_SERVER['USER'] !== 'root') {
            //nagios user or so
            define('CACHE', DS . 'tmp' . DS . 'openitcockpit' . DS . 'cache' . DS . 'nagios' . DS); // /tmp/openitcockpit/cache/nagios/
        } else {
            //root user
            define('CACHE', DS . 'tmp' . DS . 'openitcockpit' . DS . 'cache' . DS . 'cli' . DS); // /tmp/openitcockpit/cache/cli/
        }
    }
}

/**
 * Path to the resources directory.
 */
define('RESOURCES', ROOT . DS . 'resources' . DS);

/**
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * CakePHP should always be installed with composer, so look there.
 */
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');

/*
 * Path to the cake directory.
 */
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
