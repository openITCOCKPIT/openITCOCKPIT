<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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


declare(strict_types=1);

namespace App\Command;

use App\itnovum\openITCOCKPIT\WebSockets\SudoMessasgeInterface;
use App\Model\Table\SystemsettingsTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Ratchet\Overwrites\HttpServerSize;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;


/**
 * SudoServer command.
 */
class SudoServerCommand extends Command {

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
        $io->out('<info>Starting SudoServer in forground mode. Exit with [STRG] + [C]</info>');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        if (!isset($systemsettings['SUDO_SERVER']['SUDO_SERVER.API_KEY']) || strlen($systemsettings['SUDO_SERVER']['SUDO_SERVER.API_KEY']) < 10) {
            Log::error('No SUDO_SERVER.API_KEY defined or less than 10 characters');
            exit(1);
        }

        $checkForExport = false;
        if (isset($systemsettings['FRONTEND']['FRONTEND.SHOW_EXPORT_RUNNING']) && $systemsettings['FRONTEND']['FRONTEND.SHOW_EXPORT_RUNNING'] === 'yes') {
            $checkForExport = true;
        }

        $SudoMessasgeInterface = new SudoMessasgeInterface(
            $systemsettings['SUDO_SERVER']['SUDO_SERVER.API_KEY'],
            $checkForExport
        );

        $loop = \React\EventLoop\Loop::get();
        $loop->addPeriodicTimer(1, [$SudoMessasgeInterface, 'eventLoop']);

        $Server = new IoServer(
            new HttpServerSize(
                new WsServer($SudoMessasgeInterface)
            ),
            new SocketServer(sprintf('%s:%s', '0.0.0.0', 8081), [], $loop),
            $loop
        );

        try {
            $Server->run();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            exit(1);
        }
    }
}
