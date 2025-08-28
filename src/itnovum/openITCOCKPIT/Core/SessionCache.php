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


use SessionComponent;

class SessionCache {

    /**
     * @var SessionComponent
     */
    private $Session;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $expire = 0;

    private $expireKeyName = 'SessionCacheExpire';

    /**
     * SessionCache constructor.
     * @param $name
     * @param SessionComponent $Session
     * @param int $expire Value in seconds how long cached data should be stored in $_SESSION (freshness)
     */
    public function __construct($name, SessionComponent $Session, $expire = 0) {
        $this->name = $name;
        $this->Session = $Session;
        $this->expire = (int)$expire;

        if ($this->isExpired()) {
            $this->flushCache();
        }
        if ($this->expire > 0 && !$this->has($this->expireKeyName)) {
            $this->set($this->expireKeyName, time() + $this->expire);
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function getKey($key) {
        return sprintf('%s.%s', $this->name, $key);
    }

    public function isEmpty() {
        $data = $this->Session->read($this->name);

        if ($data === null) {
            return true;
        }

        if (sizeof($data) === 1 && isset($data[$this->expireKeyName])) {
            return true;
        }
        return false;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key = '') {
        if ($this->isEmpty() === true) {
            return false;
        }
        return $this->Session->check($this->getKey($key));
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->Session->read($this->getKey($key));
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value) {
        $this->Session->write($this->getKey($key), $value);
    }

    /**
     * @return bool
     */
    public function isExpired() {
        if ($this->expire > 0) {
            if ($this->has($this->expireKeyName)) {
                if (time() > $this->get($this->expireKeyName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function flushCache() {
        $this->Session->delete($this->name);
    }

    public function flushIfExpired() {
        if ($this->isExpired()) {
            $this->flushCache();
        }
    }

}
