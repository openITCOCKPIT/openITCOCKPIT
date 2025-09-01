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

namespace itnovum\openITCOCKPIT\Core;


use Cake\Error\Debugger;
use Cake\ORM\Query;

class FileDebugger {

    /**
     * @param mixed $data
     * @param string $filename
     */
    public static function dump($data, $filename = '/tmp/debug.log', $wipe = false) {

        $trace = Debugger::trace();
        $file = fopen($filename, 'a+');
        fwrite($file, '************* ' . date('H:i:s - d.m.Y') . ' ************* ' . PHP_EOL);
        fwrite($file, var_export($data, true));
        fwrite($file, PHP_EOL . 'Stack trace' . PHP_EOL);
        fwrite($file, $trace);
        fwrite($file, PHP_EOL . PHP_EOL . PHP_EOL);
        fclose($file);
    }

    /**
     * @param mixed $jsonString
     * @param string $filename
     */
    public static function dumpJson($jsonString, $filename = '/tmp/debug.log', $wipe = false) {

        $trace = Debugger::trace();
        $file = fopen($filename, 'a+');
        fwrite($file, '************* ' . date('H:i:s - d.m.Y') . ' ************* ' . PHP_EOL);
        fwrite($file, json_encode(json_decode($jsonString), JSON_PRETTY_PRINT));
        fwrite($file, PHP_EOL . 'Stack trace' . PHP_EOL);
        fwrite($file, $trace);
        fwrite($file, PHP_EOL . PHP_EOL . PHP_EOL);
        fclose($file);
    }

    /**
     * @param mixed $data
     * @param string $filename
     */
    public static function varExport($data, $filename = '/tmp/debug.log', $wipe = false) {

        $file = fopen($filename, 'a+');
        fwrite($file, '************* ' . date('H:i:s - d.m.Y') . ' ************* ' . PHP_EOL);

        if ($data === null) {
            $data = 'NULL';
        }

        if ($data === true) {
            $data = 'TRUE';
        }

        if ($data === false) {
            $data = 'FALSE';
        }

        $file = fopen($filename, 'a+');
        fwrite($file, '************* ' . date('H:i:s - d.m.Y') . '************* ' . PHP_EOL);
        fwrite($file, var_export($data, true));
        fwrite($file, PHP_EOL);
        fclose($file);
    }

    public static function wipe($filename = '/tmp/debug.log') {
        fclose(fopen($filename, 'w+'));
    }

    public static function query(Query $query, $die = false, $filename = '/tmp/debug.log') {

        $sql = (string)$query;

        $result = \SqlFormatter::format($sql, false);

        $file = fopen($filename, 'a+');
        fwrite($file, '************* SQL query' . date('H:i:s - d.m.Y') . ' ************* ' . PHP_EOL);
        fwrite($file, $result);
        fwrite($file, PHP_EOL . PHP_EOL . PHP_EOL);
        fclose($file);

        if ($die) {
            die('die() in ' . __CLASS__ . ' on line: ' . __LINE__);
        }
    }

    public static function dieQuery(Query $query) {
        $sql = (string)$query;

        $result = \SqlFormatter::format($sql, true);
        echo $result;
        die();
    }
}
