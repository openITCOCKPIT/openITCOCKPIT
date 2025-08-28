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

namespace itnovum\openITCOCKPIT\Monitoring;

use itnovum\openITCOCKPIT\Exceptions\TimeoutException;

/*
 * Usage:
 * $queryHandler = new QueryHandler('/var/naemon.qh');
 * if($queryHandler->exists()){
 * 		if($queryHandler->connect()){
 * 			if($queryHandler->ping()){
 * 				//Monitoring Backend is alive
 * 			}
 * 		}else{
 * 			$queryHandler->getLastError();
 * 		}
 * 	}
 *
 */

class QueryHandler {

    /**
     * @var string
     */
    private $queryHandler;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var int
     */
    private $lastErrorNo;

    /**
     * @var string
     */
    private $lastError;

    /**
     * @var string
     */
    private $query;

    /**
     * QueryHandler constructor.
     *
     * @param string $queryHandler Path to QueryHandler.qh file
     */
    public function __construct($queryHandler) {
        $this->queryHandler = $queryHandler;
    }

    public function connect() {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        $connectionResult = socket_connect($this->socket, $this->queryHandler);

        if ($connectionResult === false) {
            $this->lastErrorNo = socket_last_error($this->socket);
            $this->lastError = socket_strerror($this->lastErrorNo);

            return false;
        }
        socket_set_nonblock($this->socket);

        return true;
    }

    /**
     * @return bool
     */
    public function exists() {
        return file_exists($this->queryHandler);
    }

    /**
     * @return string
     */
    public function getPath() {
        return $this->queryHandler;
    }

    /**
     * When openITCOCKPIT is running in a Docker container, it HAS NO ACCESS to the query handler socket
     * @return bool
     */
    public function isContainer(){
        return IS_CONTAINER;
    }

    /**
     * @return string
     */
    public function getLastError() {
        $lastError = $this->lastError;
        $this->lastError = '';

        return $lastError;

    }

    /**
     * @return int
     */
    public function getLastErrorNo() {
        $lastErrorNo = $this->lastErrorNo;
        $this->lastErrorNo = null;

        return $lastErrorNo;
    }

    /**
     * @return bool
     */
    public function ping() {
        if ($this->exists()) {
            $query = '#echo Hi, are you still alive?';
            $assert = 'Hi, are you still alive?';
            $result = trim($this->send($query));
            if ($result == $assert) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $query to send to Query Handler
     * @param bool $readResult read the result of the query handler
     * @param int $timeout in seconds
     *
     * @return null|string
     * @throws TimeoutException
     */
    public function send($query, $readResult = true, $timeout = 1) {
        $this->query = $query;
        $this->terminate();

        socket_write($this->socket, $this->query);
        if ($readResult === false) {
            return null;
        }

        $this->query = '';

        $startTime = time();
        while (true) {
            $read = [$this->socket];
            $write = null;
            $except = null;
            if (socket_select($read, $write, $except, 0) < 1) {

                //Check timeout
                if ((time() - $startTime) > $timeout) {
                    throw new TimeoutException(sprintf('Operation timed out after %s seconds', $timeout));
                }

                continue;
            }

            if (in_array($this->socket, $read)) {
                break;
            }
        }
        $response = trim(socket_read($this->socket, 1024, PHP_NORMAL_READ));

        return $response;
    }

    private function terminate() {
        $this->query .= "\0";
    }

}
