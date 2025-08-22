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

declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;

/**
 * CpuLoad command.
 */
class CpuLoadCommand extends Command implements CronjobInterface {

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        if (file_exists('/proc/loadavg')) {
            $io->out('Fetch current CPU load...', 0);
            $load = file('/proc/loadavg');
            $records = [];
            //do not use TMP constant because CLI uses tmp_cli not tmp folder!
            if (file_exists(ROOT . DS . 'tmp' . DS . 'loadavg')) {
                $records = file(ROOT . DS . 'tmp' . DS . 'loadavg');
            }
            $newLoad = [];
            if (sizeof($records) > 15) {
                //Truncate file if more that 15 entries
                $records = array_reverse($records);
                for ($i = 0; $i < 15; $i++) {
                    $newLoad[] = $records[$i];
                }
                $newLoad = array_reverse($newLoad);
            } else {
                $newLoad = $records;
            }
            $newLoad[] = time() . ' ' . $load[0];
            unset($records);
            $file = fopen(ROOT . DS . 'tmp' . DS . 'loadavg', 'w+');
            foreach ($newLoad as $line) {
                fwrite($file, $line);
            }
            fclose($file);
            $io->success('   Ok');
            $io->hr();
        }
    }
}
