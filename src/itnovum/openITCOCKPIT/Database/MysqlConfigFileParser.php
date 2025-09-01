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

namespace App\itnovum\openITCOCKPIT\Database;


class MysqlConfigFileParser {
    function parse_mysql_cnf($path) {
        $contents = file_get_contents($path);
        $lines = preg_split("/\r\n|\n|\r/", $contents);
        $config = [];

        $inSection = FALSE;
        foreach($lines as $line) {
            if (preg_match('/\s*\[(.*)\]\s*/', $line, $matchSection)) {
                switch (strtolower(trim($matchSection[1]))) {
                    case 'client':
                    case 'mysql':
                        $inSection = TRUE;
                        break;
                    default:
                        $inSection = FALSE;
                        break;
                }
            }

            if ($inSection) {
                $parts = explode('=', $line, 2);
                $left = trim($parts[0]);
                switch($left)  {
                    case 'user':
                    case 'password':
                    case 'database':
                    case 'port':
                    case 'host':
                        $right = trim($parts[1]);
                        if(preg_match('/^[\'"](.*)[\'"]$/', $right, $match)) {
                            $right = $match[1];
                        }
                        $config[$left] = $right;
                        break;
                    default:
                }
            }
        }
        $shell = '';
        foreach ($config as $key => $val) {
            $var = 'MYSQL_' . strtoupper($key);
            $shell .= $var . '=' . escapeshellarg($val) . "\n";
        }
        $config['shell'] = $shell;
        return $config;
    }
}
