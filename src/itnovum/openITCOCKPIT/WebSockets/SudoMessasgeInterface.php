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

namespace App\itnovum\openITCOCKPIT\WebSockets;

use App\itnovum\openITCOCKPIT\Monitoring\Naemon\ExternalCommands;
use App\Model\Table\ExportsTable;
use Cake\Command\Command;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\UUID;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Class SudoMessasgeInterface
 * @package App\itnovum\openITCOCKPIT\WebSockets
 */
class SudoMessasgeInterface implements MessageComponentInterface {

    /**
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * @var int
     */
    private $lastExportCheck = 0;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var ExternalCommands
     */
    private $ExternalCommands;

    /**
     * @var bool
     */
    private $checkForExport;

    /**
     * @var null|string
     */
    private $requestor;

    /**
     * @var ExportsTable
     */
    private $ExportsTable;

    /**
     * SudoMessasgeInterface constructor.
     * @param string $apiKey
     * @param bool $checkForExport
     */
    public function __construct(string $apiKey, bool $checkForExport = false) {
        $this->apiKey = $apiKey;
        $this->clients = new \SplObjectStorage;
        $this->checkForExport = $checkForExport;
        $this->lastExportCheck = time();

        /** @var ExportsTable $ExportsTable */
        $this->ExportsTable = TableRegistry::getTableLocator()->get('Exports');

        $this->ExternalCommands = new ExternalCommands();

        $this->requestor = null;
    }

    /**
     * Gets called every 0.01 sec
     */
    public function eventLoop() {
        $this->isExportRunning();
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn) {
        $uniqid = UUID::v4();
        $this->clients->attach($conn);

        $conn->send(json_encode($this->merge([
            'payload' => 'Connection established',
            'uniqid'  => $uniqid,
            'type'    => 'connection',
        ])));
    }

    /**
     * @param array $msg
     * @return array
     */
    public function merge($msg = []) {
        $default = [
            'payload' => '',
            'type'    => '',
            'task'    => ''
        ];

        return Hash::merge($default, $msg);
    }

    /**
     * @param string $payload
     * @param string $type
     * @param string $task
     * @param string $category
     */
    public function send($payload, $type = 'response', $task = '', $category = 'notification') {
        if ($this->requestor !== null) {
            if ($payload == false) {
                $payload = '';
            }
            foreach ($this->clients as $n => $client) {
                $client->send(json_encode($this->merge([
                    'payload'  => $payload,
                    'uniqid'   => $this->requestor,
                    'type'     => $type,
                    'task'     => $task,
                    'category' => $category,
                ])));
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msg = json_decode($msg);
        if ($msg->key !== $this->apiKey) {
            Log::error('SudoServer received message with wrong API key!');
            Log::error(json_encode($msg));
            return;
        }

        // Avoid "MySQL server has gone away"
        //$connection = $SystemsettingsTable->getConnection();
        //$connection->disconnect();
        //$connection->connect();

        $this->requestor = $msg->uniqid;

        switch ($msg->task) {
            case 'keepAlive':
                $this->send('Pong', 'keepAlive', 'keepAlive');
                break;

            case 'rescheduleHost':
                $this->ExternalCommands->rescheduleHost(['uuid' => $msg->data[0], 'type' => $msg->data[1], 'satellite_id' => $msg->data[2]]);
                break;

            case 'commitPassiveResult':
                $this->ExternalCommands->passiveTransferHostCheckresult(['uuid' => $msg->data[0], 'comment' => $msg->data[1], 'state' => $msg->data[2], 'forceHardstate' => $msg->data[3], 'repetitions' => $msg->data[4]]);
                break;

            case 'commitPassiveServiceResult':
                $this->ExternalCommands->passiveTransferServiceCheckresult(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1], 'comment' => $msg->data[2], 'state' => $msg->data[3], 'forceHardstate' => $msg->data[4], 'repetitions' => $msg->data[5]]);
                break;

            case 'enableOrDisableHostFlapdetection':
                $this->ExternalCommands->enableOrDisableHostFlapdetection(['uuid' => $msg->data[0], 'condition' => $msg->data[1]]);
                break;

            case 'enableOrDisableServiceFlapdetection':
                $this->ExternalCommands->enableOrDisableServiceFlapdetection(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1], 'condition' => $msg->data[2]]);
                break;

            case 'rescheduleService':
                $this->ExternalCommands->rescheduleService(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1], 'satellite_id' => $msg->data[2]]);
                break;

            case 'sendCustomHostNotification':
                $this->ExternalCommands->sendCustomHostNotification(['hostUuid' => $msg->data[0], 'type' => $msg->data[1], 'author' => $msg->data[2], 'comment' => $msg->data[3]]);
                break;

            case 'sendCustomServiceNotification':
                $this->ExternalCommands->sendCustomServiceNotification(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1], 'type' => $msg->data[2], 'author' => $msg->data[3], 'comment' => $msg->data[4]]);
                break;

            case 'submitServicestateAck':
                $this->ExternalCommands->setServiceAck(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1], 'comment' => $msg->data[2], 'author' => $msg->data[3], 'sticky' => $msg->data[4], 'notify' => $msg->data[5]]);
                break;

            case 'submitHoststateAck':
                $this->ExternalCommands->setHostAck(['hostUuid' => $msg->data[0], 'comment' => $msg->data[1], 'author' => $msg->data[2], 'sticky' => $msg->data[3], 'type' => $msg->data[4], 'notify' => $msg->data[5]]);
                break;

            case 'submitServiceDowntime':
                $this->ExternalCommands->setServiceDowntime(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1], 'start' => strtotime($msg->data[2]), 'end' => strtotime($msg->data[3]), 'comment' => $msg->data[4], 'author' => $msg->data[5]]);
                break;

            case 'submitHostDowntime':
                $this->ExternalCommands->setHostDowntime(['hostUuid' => $msg->data[0], 'start' => strtotime($msg->data[1]), 'end' => strtotime($msg->data[2]), 'comment' => $msg->data[3], 'author' => $msg->data[4], 'downtimetype' => $msg->data[5]]);
                break;

            case 'submitDisableHostNotifications':
                $this->ExternalCommands->disableHostNotifications(['uuid' => $msg->data[0], 'type' => $msg->data[1]]);
                break;

            case 'submitEnableHostNotifications':
                $this->ExternalCommands->enableHostNotifications(['uuid' => $msg->data[0], 'type' => $msg->data[1]]);
                break;

            case 'submitDisableServiceNotifications':
                $this->ExternalCommands->disableServiceNotifications(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1]]);
                break;

            case 'submitEnableServiceNotifications':
                $this->ExternalCommands->enableServiceNotifications(['hostUuid' => $msg->data[0], 'serviceUuid' => $msg->data[1]]);
                break;
        }
    }

    public function onClose(ConnectionInterface $connection) {
        $this->clients->detach($connection);
    }

    public function onError(ConnectionInterface $connection, \Exception $e) {
        Log::error(sprintf('SudoServer: An client connection error has occurred: %s', $e->getMessage()));
        $connection->close();
        $this->clients->detach($connection);
    }


    private function isExportRunning() {
        if ((time() - $this->lastExportCheck) > 3) {
            $result = $this->ExportsTable->find()
                ->where([
                    'task' => 'export_started'
                ])
                ->first();

            $exportRunning = true;
            if ($result === null) {
                $exportRunning = false;
            } else {
                if ($result->get('finished') === 1) {
                    $exportRunning = false;
                }
            }

            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type'    => 'dispatcher',
                    'running' => $exportRunning,
                ]));
            }
            $this->lastExportCheck = time();
        }
    }
}
