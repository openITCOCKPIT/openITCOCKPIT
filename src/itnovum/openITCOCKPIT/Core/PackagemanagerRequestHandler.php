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

class PackagemanagerRequestHandler {

    /**
     * Address of the licsense server
     * @var string
     */
    private string $address = 'https://packagemanager.openitcockpit.io';

    /**
     * Base URL to query all available modules.
     * Replace %s with a license key or leave it empty
     * @var string
     */
    private $baseUrl = '%s/modules/fetch/%s/4.json';

    /**
     * Checks if a given license key is valid or not
     * @var string
     */
    private $checkLicenseUrl = '%s/licenses/check/%s.json';

    private $license = null;

    private array $proxySettings = [];

    public function __construct(?string $license = null) {
        $this->license = $license;

        /** @var ProxiesTable $ProxiesTable */
        $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
        $this->proxySettings = $ProxiesTable->getSettings();
    }

    /**
     * Query the openITCOCKPIT License Server for all available modules
     * and load the changelog of all versions
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadModulesWithChangelog(): array {
        $url = sprintf($this->baseUrl, $this->address, $this->getLicense());
        $Client = new Client($this->getClientOptions());

        $result = [
            'error'     => true,
            'error_msg' => 'Unknown error',
            'data'      => []
        ];
        try {
            $response = $Client->request('GET', $url);
            if ($response->getStatusCode() !== 200) {
                $result['error'] = true;
                $result['error_msg'] = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
                $result['data'] = [];

                return $result;
            }

            // 200 Ok
            $result['error'] = false;
            $result['error_msg'] = '';

            $data = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // $JSON parse error
                $result['data'] = [];
            } else {
                $result['data'] = $data;
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['error_msg'] = $e->getMessage();
            $result['data'] = [];

            return $result;
        }
    }

    /**
     * Asks the openITCOCKPIT license server if the given license is valid
     * Will return information about the license and available modules
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validateLicense(): array {
        $url = sprintf($this->checkLicenseUrl, $this->address, $this->getLicense());
        $Client = new Client($this->getClientOptions());

        $result = [
            'error'     => true,
            'error_msg' => 'Unknown error',
            'license'   => []
        ];
        try {
            $response = $Client->request('GET', $url);
            if ($response->getStatusCode() !== 200) {
                $result['error'] = true;
                $result['error_msg'] = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
                $result['license'] = [];

                return $result;
            }

            // 200 Ok
            $result['error'] = false;
            $result['error_msg'] = '';

            $data = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // $JSON parse error
                $result['license'] = [];
            } else {
                $result['license'] = $data;
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['error_msg'] = $e->getMessage();
            $result['license'] = [];

            return $result;
        }

    }

    private function getLicense() {
        if (empty($this->license)) {
            // Strange legacy behavior the old request builder did
            return '0';
        }

        return $this->license;
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
