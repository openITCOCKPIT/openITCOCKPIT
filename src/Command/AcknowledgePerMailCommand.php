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

use App\itnovum\openITCOCKPIT\Monitoring\Naemon\ExternalCommands;
use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Ddeboer\Imap\Server;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;

/**
 * AcknowledgePerMail command.
 */
class AcknowledgePerMailCommand extends Command implements CronjobInterface {

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
        $io->setStyle('red', ['text' => 'red', 'blink' => false]);

        $io->out('Checking inbox mails for acknowledgment...');

        $ackHostsAndServices = $this->ackHostsAndServices($io);
        if ($ackHostsAndServices['success'] === true) {
            $io->success('    Ok');
        } else {
            $io->out('<red>    Error</red>');
        }

        foreach ($ackHostsAndServices['messages'] as $message) {
            $io->out($message);
        }

        $io->hr();
    }


    /**
     * @param ConsoleIo $io
     * @return array
     */
    private function ackHostsAndServices(ConsoleIo $io) {
        $ExternalCommands = new ExternalCommands();

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArraySection('MONITORING');
        if (empty($systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_SERVER']) ||
            empty($systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_ADDRESS']) ||
            empty($systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_PASSWORD'])) {
            return [
                'success'  => false,
                'messages' => [
                    'Some of ACK_ values were not provided in system settings'
                ]
            ];
        }
        $serverParts = explode('/', $systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_SERVER']);
        if (count($serverParts) < 2) {
            return [
                'success'  => false,
                'messages' => [
                    'ACK_RECEIVER_SERVER has wrong format'
                ]
            ];
        }
        $serverAndPort = explode(':', $serverParts[0]);
        if (count($serverAndPort) < 2) {
            return [
                'success'  => false,
                'messages' => [
                    'ACK_RECEIVER_SERVER has wrong format. Either connection URL is wrong or port was not provided.'
                ]
            ];
        }

        $serverURL = trim($serverAndPort[0]);
        $serverPort = trim($serverAndPort[1]);
        $serverProtocol = trim($serverParts[1]);
        $serverSSL = in_array('ssl', $serverParts);
        $serverNoValidateCert = in_array('novalidate-cert', $serverParts);
        $serverOptions = '/' . $serverProtocol . ($serverSSL ? '/ssl' : '') . ($serverNoValidateCert ? '/novalidate-cert' : '');

        if ($serverProtocol != 'imap') {
            return [
                'success'  => false,
                'messages' => [
                    'Only IMAP protocol is supported.'
                ]
            ];
        }

        $server = new Server($serverURL, $serverPort, $serverOptions);
        $connection = $server->authenticate($systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_ADDRESS'], $systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_PASSWORD']);
        $mailbox = $connection->getMailbox('INBOX');
        $acks = [];
        $acknowledged = 0;

        foreach ($mailbox->getMessages() as $message) {
            $io->out('Parsing email from ' . $message->getFrom()->getAddress());

            $body = $message->getBodyText();
            //Check if this is base64_encoded, but the header is not set to base64
            if ($this->isBase64($body)) {
                $body = base64_decode(str_replace(["\n", "\r\n", "\r"], '', $body), true);
            }
//debug($body);
            $parsedValues = $this->parseAckInformation($body);
            if (empty($parsedValues)) {
                $message->delete();
                continue;
            }
            $author = empty($message->getFrom()->getName()) ? $message->getFrom()->getAddress() : $message->getFrom()->getName();
            $acknowledged++;
            if (empty($parsedValues['ACK_SERVICEUUID']) && !empty($parsedValues['ACK_HOSTUUID'])) {
                $hostAckArray = [
                    'hostUuid' => $parsedValues['ACK_HOSTUUID'],
                    'author'   => $author,
                    'comment'  => __('Acknowledged per mail'),
                    'sticky'   => 2, // 2 = sticky with Nagios Core, 1 = Sticky with Naemon Core, 2 works for both :)
                    'type'     => 'hostOnly'
                ];

                Log::info('AcknowledgePerMailCommand: Host ack info: ' . json_encode($hostAckArray));

                $ExternalCommands->setHostAck($hostAckArray);
                $io->out('Host ' . $parsedValues['ACK_HOSTUUID'], 0);
                $io->success(' acknowledged');

            } else if (!empty($parsedValues['ACK_SERVICEUUID']) && !empty($parsedValues['ACK_HOSTUUID'])) {
                $serviceAckArray = [
                    'hostUuid'    => $parsedValues['ACK_HOSTUUID'],
                    'serviceUuid' => $parsedValues['ACK_SERVICEUUID'],
                    'author'      => $author,
                    'comment'     => __('Acknowledged per mail'),
                    'sticky'      => 2 // 2 = sticky with Nagios Core, 1 = Sticky with Naemon Core, 2 works for both :)
                ];

                Log::info('AcknowledgePerMailCommand: Service ack info: ' . json_encode($serviceAckArray));
                $ExternalCommands->setServiceAck($serviceAckArray);
                $io->out('Service ' . $parsedValues['ACK_SERVICEUUID'], 0);
                $io->success(' acknowledged');
            }
            $message->delete();
        }
        $connection->expunge();

        if ($acknowledged === 0) {
            $acks = ['No hosts and services were acknowledged'];
        }
        $connection->close();

        return [
            'success'  => true,
            'messages' => $acks
        ];
    }

    private function getStringBetween($string, $start, $end) {
        $str = str_replace(["\r", "\n", '>'], '', $string);
        $ini = mb_strpos($str, $start);
        if ($ini === false) return '';
        $ini += strlen($start);
        $len = mb_strpos($str, $end);
        if ($end === false) return '';
        return mb_substr($str, $ini, $len - $ini);
    }

    private function parseAckInformation($ackString) {
        $myString = $this->getStringBetween($ackString, '--- BEGIN ACK INFORMATION ---', '--- END ACK INFORMATION ---');
        if (empty($myString)) {
            $myString = $this->getStringBetween($ackString, '--- BEGIN ACK2 INFORMATION ---', '--- END ACK2 INFORMATION ---');
        }
        if (empty($myString)) return [];

        $firstExplode = explode(':', $myString);
        $currentIndex = trim($firstExplode[0]);
        $returnArr = [];
        for ($i = 1; $i < count($firstExplode); $i++) {
            $nextExplose = explode('ACK_', $firstExplode[$i]);
            $returnArr[$currentIndex] = trim($nextExplose[0]);
            $currentIndex = isset($nextExplose[1]) ? ('ACK_' . trim($nextExplose[1])) : null;
        }
        if (!isset($returnArr['ACK_HOSTUUID']) || empty($returnArr['ACK_HOSTUUID']) || !isset($returnArr['ACK_SERVICEUUID'])) {
            return [];
        }

        return $returnArr;
    }

    /**
     * @param $str
     * @return bool
     */
    private function isBase64($str) {
        $str = str_replace(["\n", "\r\n", "\r"], '', $str);
        if (base64_encode(base64_decode($str, true)) === $str) {
            return true;
        }
        return false;
    }
}
