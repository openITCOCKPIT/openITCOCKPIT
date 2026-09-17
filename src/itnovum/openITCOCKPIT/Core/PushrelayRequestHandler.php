<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace App\itnovum\openITCOCKPIT\Core;

use App\Model\Table\ProxiesTable;
use Cake\ORM\TableRegistry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use itnovum\openITCOCKPIT\Core\System\Health\SystemId;

class PushrelayRequestHandler {

    /**
     * Address of the Push Relay / Push Gateway server
     * @var string
     */
    private string $address = '';

    private int $port = 443;

    private array $proxySettings = [];

    public function __construct() {
        /** @var ProxiesTable $ProxiesTable */
        $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
        $this->proxySettings = $ProxiesTable->getSettings();
    }

    /**
     * This method use the given address and port and try to register this openITCOCKPIT system
     * to the push gateway
     *
     * @param string $address Address of the Pushrelay you want to register to
     * @param int $port Port of the Pushrelay you want to register to
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function registerAndTestAtRelay(string $address, int $port): array {
        $url = sprintf('%s:%s/systems/register.json', $address, $port);
        $Client = new Client($this->getClientOptions());

        $SystemId = new SystemId();

        $result = [
            'error'         => true,
            'status'        => 400,
            'reason_phrase' => 'Bad Request',
            'response_msg'  => '',
            'system'        => null,
        ];

        try {
            $response = $Client->post($url, [
                'json' => [
                    'system_id' => $SystemId->getSystemId(),
                ]
            ]);
            if ($response->getStatusCode() !== 200) {
                // We got an error from the Push Relay Server
                $result['error'] = true;
                $result['status'] = $response->getStatusCode();
                $result['reason_phrase'] = $response->getReasonPhrase();
                $result['response_msg'] = $response->getBody()->getContents();
                $result['system'] = null;

                return $result;
            }

            // 200 Ok from the Push Relay Server
            $data = json_decode($response->getBody()->getContents(), true);

            $result['error'] = false;
            $result['status'] = $response->getStatusCode();
            $result['reason_phrase'] = $response->getReasonPhrase();
            $result['response_msg'] = $response->getBody()->getContents();
            $result['system'] = $data['system'] ?? null;

            return $result;
        } catch (ServerException $e) {
            // Some error happened on the Push Relay Server
            $result['error'] = true;
            $result['status'] = $e->getCode();
            $result['reason_phrase'] = ''; // Guzzle can't give us the reason ?
            $result['response_msg'] = $e->getMessage();

            return $result;
        } catch (\Exception $e) {
            // Connection error
            $result['error'] = true;
            $result['status'] = $e->getCode();
            $result['reason_phrase'] = 'HTTP Error';
            $result['response_msg'] = $e->getMessage();

            return $result;
        }

        return $result;

    }

    /**
     * @return array
     */
    private function getClientOptions(): array {
        $options = [
            'headers'         => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify'          => true,
            'connect_timeout' => 25
        ];
        if (!empty($this->proxySettings['enabled']) && !empty($this->proxySettings['ipaddress']) && !empty($this->proxySettings['port'])) {
            $options['proxy'] = [
                'http'  => sprintf('%s:%s', $this->proxySettings['ipaddress'], $this->proxySettings['port']),
                'https' => sprintf('%s:%s', $this->proxySettings['ipaddress'], $this->proxySettings['port'])
            ];
        } else {
            $options['proxy'] = [
                'http'  => '',
                'https' => ''
            ];
        }
        return $options;
    }

}
