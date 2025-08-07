<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace itnovum\openITCOCKPIT\Core\Views;

use Cake\I18n\FrozenTime;

/**
 * Class Apikey
 * @package itnovum\openITCOCKPIT\Core\Views
 */
class Apikey {

    private $id = null;

    private $userId = null;

    private $apikey = null;

    private $description = null;
    private $last_use = null;

    /**
     * Apikey constructor.
     * @param array $apikey
     */
    public function __construct($apikey) {
        if (isset($apikey['id'])) {
            $this->id = (int)$apikey['id'];
        }

        if (isset($apikey['user_id'])) {
            $this->userId = (int)$apikey['user_id'];
        }

        if (isset($apikey['apikey'])) {
            $this->apikey = $apikey['apikey'];
        }

        if (isset($apikey['description'])) {
            $this->description = $apikey['description'];
        }

        if (isset($apikey['last_use'])) {
            $this->last_use = $apikey['last_use'];
        }

    }

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return null
     */
    public function getApikey() {
        return $this->apikey;
    }

    /**
     * @return null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return string|null|\Cake\I18n\DateTime
     */
    public function getLastUse() {
        return $this->last_use;
    }

    /**
     * @param string|\Cake\I18n\DateTime $lastUse
     */
    public function setLastUse($lastUse) {
        $this->last_use = $lastUse;
    }


    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
