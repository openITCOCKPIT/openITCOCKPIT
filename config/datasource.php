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

$mcp = new \App\itnovum\openITCOCKPIT\Database\MysqlConfigFileParser();
$ini_file = $mcp->parse_mysql_cnf('/opt/openitc/etc/mysql/mysql.cnf');

// Synchronize the timezone setting between PHP and MySQL
$timezone = date_default_timezone_get();
if (empty($timezone)) {
    $timezone = 'UTC';
}

// Calculate the offset between UTC and the current timezone
// https://www.sitepoint.com/synchronize-php-mysql-timezone-configuration/
// MySQL requires the offset in the format +/-HH:MM
$now = new \DateTime();
$mins = $now->getOffset() / 60;
$sgn = ($mins < 0 ? -1 : 1); // Sign of offset (positive/negative)
$mins = abs($mins); // Converts negative offsets to positive
$hrs = floor($mins / 60);
$mins = $mins - ($hrs * 60);
$offset = sprintf('%+d:%02d', $hrs * $sgn, $mins);

return [
    'Datasources' => [
        'default' => [
            'className'        => 'Cake\Database\Connection',
            'driver'           => 'Cake\Database\Driver\Mysql',
            'persistent'       => true,
            'host'             => $ini_file['host'],
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',
            'username'         => $ini_file['user'],
            'password'         => $ini_file['password'],
            'database'         => $ini_file['database'],
            /*
             * You do not need to set this flag to use full utf-8 encoding (internal default since CakePHP 3.6).
             */
            'encoding'         => 'utf8mb4',
            'timezone'         => $offset,
            'flags'            => [],
            'cacheMetadata'    => true,
            'log'              => false,

            /**
             * Set identifier quoting to true if you are using reserved words or
             * special characters in your table or column names. Enabling this
             * setting will result in queries built using the Query Builder having
             * identifiers quoted when creating SQL. It should be noted that this
             * decreases performance because each query needs to be traversed and
             * manipulated before being executed.
             */
            'quoteIdentifiers' => true,
        ],

        /**
         * The test connection is used during the test suite.
         */
        'test'    => [
            'className'        => 'Cake\Database\Connection',
            'driver'           => 'Cake\Database\Driver\Mysql',
            'persistent'       => false,
            'host'             => $ini_file['host'],
            //'port' => 'non_standard_port_number',
            'username'         => $ini_file['user'],
            'password'         => $ini_file['password'],
            'database'         => $ini_file['database'],
            'encoding'         => 'utf8mb4',
            'timezone'         => $offset,
            'cacheMetadata'    => true,
            'quoteIdentifiers' => false,
            'log'              => false,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
            'url'              => env('DATABASE_TEST_URL', null),
        ],
    ]
];
