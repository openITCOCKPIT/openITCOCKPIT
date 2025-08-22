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


use Cake\Utility\Hash;

class Http {

    private $curlOptions = [];
    private $lastError;
    private $proxy;
    private $url;
    private $postBody;
    private $ch;

    public $error;
    public $data;

    public function __construct($url = '', $settings = [], $proxy = []) {
        $_curlDefaults = [
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS'      => 2,
            'CURLOPT_HEADER'         => 0,
            'CURLOPT_TIMEOUT'        => 10,
            'CURLOPT_RETURNTRANSFER' => 1,
        ];
        $this->curlOptions = Hash::merge($_curlDefaults, $settings);

        $_proxyDefaults = [
            'ipaddress' => '',
            'port'      => 0,
            'enabled'   => false,
        ];
        $this->proxy = Hash::merge($_proxyDefaults, $proxy);

        $this->lastError = false;

        $this->url = $url;
        $this->postBody = null;;
        $this->ch = null;
        $this->error = false;
        $this->data = null;
        if (function_exists("curl_init")) {
            //PHP Erweiterung curl ist geladen
            $this->error = false;
            $this->data = __('PHP Extension php5-curl loaded successfully');
        } else {
            $this->error = true;
            $this->data = __('PHP Extension php5-curl not loaded or installed!');
        }
    }

    public function sendRequest() {
        $this->init();
        $this->error = false;
        $this->lastError = false;
        $this->data = curl_exec($this->ch);
        $this->lastError = $this->setLastError();
        if ($this->lastError) {
            $this->error = true;
        }
        $this->close();
    }

    public function init() {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->url);

        foreach ($this->curlOptions as $key => $value) {
            curl_setopt($this->ch, constant($key), $value);
        }

        if (!empty($this->postBody)) {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postBody);
        }
        if ($this->proxy['enabled'] == true) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy['ipaddress']);
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $this->proxy['port']);
        }
    }

    public function close() {
        curl_close($this->ch);
    }

    public function setLastError() {
        if (!is_resource($this->ch)) {
            return false;
        }

        $error = curl_error($this->ch);
        $errno = curl_errno($this->ch);

        if (strlen($error) > 0 && $errno > 0) {
            return [
                'error' => $error,
                'errno' => $errno,
            ];
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function getLastError() {
        return $this->lastError;
    }
}
