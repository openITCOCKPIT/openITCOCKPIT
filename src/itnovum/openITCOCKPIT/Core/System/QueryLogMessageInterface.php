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

namespace itnovum\openITCOCKPIT\Core\System;


use App\Command\QueryLogCommand;
use Cake\Command\Command;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SqlFormatter;
use Symfony\Component\Process\Process;

class QueryLogMessageInterface implements MessageComponentInterface {

    /**
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * @var string
     */
    private $logfile;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var Command
     */
    private $CakeCommand;

    /**
     * @var bool
     */
    private $prettyPrint;

    /**
     * @var bool
     */
    private $hidePermissionQueries;

    /**
     * QueryLogMessageInterface constructor.
     * @param QueryLogCommand $CakeCommand
     * @param string $logfile
     * @param bool $prettyPrint
     */
    public function __construct(QueryLogCommand $CakeCommand, $logfile = '', $prettyPrint = true, $hidePermissionQueries = false) {
        $this->CakeCommand = $CakeCommand;
        $this->logfile = $logfile;
        $this->prettyPrint = $prettyPrint;
        $this->hidePermissionQueries = $hidePermissionQueries;
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->CakeCommand->io->out('<info>New client connected</info>');
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
        $this->clients->detach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $this->send('This server does not process user input.');
    }

    public function send($message) {
        foreach ($this->clients as $n => $client) {
            $client->send($message);
        }
    }

    public function eventLoop() {
        $output = $this->process->getIncrementalOutput();
        if ($output !== '') {
            $sourceQueries = $this->CakeCommand->parseLogOutputToSourceQueries($output);

            SqlFormatter::$cli = false;
            if ($sourceQueries !== null) {
                $queries = [];
                $time = 0;
                foreach ($sourceQueries as $query) {
                    if ($this->hidePermissionQueries) {
                        $acoQuery = 'SELECT `Acos`.`id`, `Acos`.`parent_id`';
                        $aroQuery = 'SELECT `Aros`.`id`, `Aros`.`parent_id`';
                        $permissionQuery = 'SELECT `Permissions`.`id`, `Permissions`.`aro_id`';

                        if (strstr($query['query'], $acoQuery) || strstr($query['query'], $aroQuery) || strstr($query['query'], $permissionQuery)) {
                            continue;
                        }
                    }

                    if ($this->prettyPrint) {
                        $query['query'] = SqlFormatter::format($query['query'], true);
                    } else {
                        $query['query'] = SqlFormatter::highlight($query['query']);
                    }
                    $queries[] = $query;
                    $time += $query['duration'];
                }
                $processedQueries = [
                    'queries'    => $queries,
                    'datasource' => 'openitcockpit',
                    'count'      => sizeof($queries),
                    'time'       => $time
                ];

                $this->send(json_encode($processedQueries));
            }
        }
    }

    public function startTailf() {
        $this->process = new Process(['/usr/bin/tail', '-f', '-n', '10', $this->logfile]);
        $this->process->start();
    }

    public function stopTailf() {
        $this->process->stop();
    }
}
