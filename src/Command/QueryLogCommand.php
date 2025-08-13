<?php
// Copyright (C) <2020>  <it-novum GmbH>
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

use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use itnovum\openITCOCKPIT\Core\System\QueryLogMessageInterface;
use itnovum\openITCOCKPIT\Ratchet\Overwrites\HttpServerSize;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;
use Symfony\Component\Process\Process;

/**
 * Dump command.
 */
class QueryLogCommand extends Command {

    /**
     * @var ConsoleIo
     */
    public $io;

    /**
     * @var string
     */
    private $logfile;

    /**
     * @var bool
     */
    private $prettyPrint = false;

    /**
     * @var bool
     */
    private $hidePermissionQueries = false;


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

        $parser->addOptions([
            'pretty'           => ['boolean' => true, 'help' => __d('oitc_console', 'Pretty print sql queries')],
            'hide-acl'         => ['boolean' => true, 'help' => __d('oitc_console', 'Hide (ARO/ACO) permission queries')],
            'websocket-server' => ['boolean' => true, 'help' => __d('oitc_console', 'Start a WebSocket server to make the query log accessible from openITCOCKPIT web interface')],
        ]);

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
        $this->io = $io;
        $this->logfile = LOGS . 'queries.log';

        $dataSource = ConnectionManager::getConfig('default');
        if ($dataSource['log'] != 1) {
            $io->setStyle('red_bold', ['text' => 'red', 'bold' => true]);
            $io->out('<red_bold>Setting "\'log\' => true" in "' . ROOT . '/config/datasource.php' . '" required!</red_bold>');
        }

        if ($args->getOption('pretty')) {
            $this->prettyPrint = true;
        }
        if ($args->getOption('hide-acl')) {
            $this->hidePermissionQueries = true;
        }
        if ($args->getOption('websocket-server')) {
            $this->fireUpWebSocketServer();
        }
        $this->tailf();
    }

    private function tailf() {
        $options = [
            'cwd' => '/tmp',
            'env' => [
                'LANG'     => 'C',
                'LANGUAGE' => 'en_US.UTF-8',
                'LC_ALL'   => 'C',
                'PATH'     => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
            ],
        ];

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "r"],
        ];

        $process = new Process(['/usr/bin/tail', '-f', '-n', '10', $this->logfile]);
        $process->start();
        pcntl_signal(SIGTERM, function ($signal) use ($process) {
            $process->stop();
        });
        while ($process->isRunning()) {
            $output = $process->getIncrementalOutput();
            if ($output === '') {
                sleep(1);
                continue;
            }

            $queries = $this->parseLogOutputToSourceQueries($output);

            if ($queries === null) {
                continue;
            }

            $time = 0;
            foreach ($queries as $i => $query) {
                if ($this->hidePermissionQueries) {
                    $acoQuery = 'SELECT `Acos`.`id`, `Acos`.`parent_id`';
                    $aroQuery = 'SELECT `Aros`.`id`, `Aros`.`parent_id`';
                    $permissionQuery = 'SELECT `Permissions`.`id`, `Permissions`.`aro_id`';

                    if (strstr($query['query'], $acoQuery) || strstr($query['query'], $aroQuery) || strstr($query['query'], $permissionQuery)) {
                        continue;
                    }
                }
                $time += $query['duration'];
            }

            $this->io->out(sprintf(
                '<info>Database "%s" %s queries took %s ms</info>',
                'openitcockpit',
                sizeof($queries),
                $time
            ));

            foreach ($queries as $i => $query) {

                if ($this->hidePermissionQueries) {
                    $acoQuery = 'SELECT `Acos`.`id`, `Acos`.`parent_id`';
                    $aroQuery = 'SELECT `Aros`.`id`, `Aros`.`parent_id`';
                    $permissionQuery = 'SELECT `Permissions`.`id`, `Permissions`.`aro_id`';

                    if (strstr($query['query'], $acoQuery) || strstr($query['query'], $aroQuery) || strstr($query['query'], $permissionQuery)) {
                        continue;
                    }
                }

                $this->io->out(sprintf(
                    '-- [%s], num. Rows %s, %s ms',
                    $i,
                    $query['numRows'],
                    $query['duration']
                ));
                if ($this->prettyPrint) {
                    $this->io->out(\SqlFormatter::format($query['query']), 0);
                } else {
                    $this->io->out(\SqlFormatter::highlight($query['query']), 0);
                }
                $this->io->out();
            }
            $this->io->out('------------');
            $this->io->out();
        }
    }

    private function fireUpWebSocketServer() {
        $MessageInterface = new QueryLogMessageInterface($this, $this->logfile, $this->prettyPrint, $this->hidePermissionQueries);
        $MessageInterface->startTailf();
        pcntl_signal(SIGTERM, function ($signal) use ($MessageInterface) {
            $MessageInterface->stopTailf();
        });

        $loop = \React\EventLoop\Loop::get();
        $loop->addPeriodicTimer(0.01, [$MessageInterface, 'eventLoop']);

        $Server = new IoServer(
            new HttpServerSize(
                new WsServer($MessageInterface)
            ),
            new SocketServer(sprintf('%s:%s', '0.0.0.0', 8082), [], $loop),
            $loop
        );

        try {
            $Server->run();
        } catch (\Exception $e) {
            debug($e);
        }

    }

    /**
     * @param string $output
     * @return array
     */
    public function parseLogOutputToSourceQueries(string $output) {
        $sourceQueries = [];

        foreach (explode(PHP_EOL, $output) as $line) {
            if (trim($line) === '') {
                continue;
            }

            /*
            Example of how $query will look
            array(
                0 => ''2023-09-06',
                1 => '11:15:19',
                2 => 'debug:',
                3 => 'connection=default',
                4 => 'duration=0',
                5 => 'rows=1',
                6 => 'SELECT `Permissions` . `id` as `Permissions__id`, `Permissions` . `aro_id` as `Permissions__aro_id`, `Permissions` . `aco_id` as `Permissions__aco_id`, `Permissions` . `_create` as `Permissions___create`, `Permissions` . `_read` as `Permissions___read`, `Permissions` . `_update` as `Permissions___update`, `Permissions` . `_delete` as `Permissions___delete`, `Acos` . `id` as `Acos__id`, `Acos` . `parent_id` as `Acos__parent_id`, `Acos` . `model` as `Acos__model`, `Acos` . `foreign_key` as `Acos__foreign_key`, `Acos` . `alias` as `Acos__alias`, `Acos` . `lft` as `Acos__lft`, `Acos` . `rght` as `Acos__rght` FROM `aros_acos` `Permissions` LEFT JOIN `acos` `Acos` ON `Acos` . `id` = `Permissions` . `aco_id` WHERE(`Permissions` . `aro_id` = 1 and `Permissions` . `aco_id` in(551, 550, 1)) ORDER BY `Acos` . `lft` desc'',
            )*/
            $query = explode(' ', $line, 7);

            $date = $query[0];
            $time = $query[1];
            $connection = explode('=', $query[3], 2)[1];
            $duration = explode('=', $query[4], 2)[1];

            $rows = explode('=', $query[5], 2);
            // Todo implement ,ulti-line record from logfile
            if(!isset($rows[1])){
                debug($line);
                debug($query);
            }

            $rows = explode('=', $query[5], 2)[1] ?? 0;
            $SQL = $query[6];


            $sourceQueries[] = [
                'datetime' => $date . $time,
                'query'    => $SQL,
                'numRows'  => intval($rows),
                'duration' => intval($duration)
            ];

        }

        return $sourceQueries;
    }

}
