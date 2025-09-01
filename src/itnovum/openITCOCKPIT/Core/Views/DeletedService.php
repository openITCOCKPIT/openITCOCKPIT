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

namespace itnovum\openITCOCKPIT\Core\Views;

class DeletedService {

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $uuid;

    /**
     * @var int
     */
    private $hostUuid;

    /**
     * @var int
     */
    private $servicetemplateId;

    /**
     * @var int
     */
    private $hostId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $perfdataDeleted;

    /**
     * @var int
     */
    private $created;

    /**
     * @var int
     */
    private $modified;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    public function __construct($data, $UserTime = null) {
        if (isset($data['DeletedService']['id'])) {
            $this->id = $data['DeletedService']['id'];
        }

        if (isset($data['DeletedService']['uuid'])) {
            $this->uuid = $data['DeletedService']['uuid'];
        }

        if (isset($data['DeletedService']['host_uuid'])) {
            $this->hostUuid = $data['DeletedService']['uuid'];
        }

        if (isset($data['DeletedService']['servicetemplate_id'])) {
            $this->servicetemplateId = (int)$data['DeletedService']['servicetemplate_id'];
        }

        if (isset($data['DeletedService']['host_id'])) {
            $this->hostId = (int)$data['DeletedService']['host_id'];
        }

        if (isset($data['DeletedService']['name'])) {
            $this->name = $data['DeletedService']['name'];
        }

        if (isset($data['DeletedService']['description'])) {
            $this->description = $data['DeletedService']['description'];
        }

        if (isset($data['DeletedService']['deleted_perfdata'])) {
            $this->perfdataDeleted = (bool)$data['DeletedService']['deleted_perfdata'];
        }

        if (isset($data['DeletedService']['created'])) {
            $this->created = strtotime($data['DeletedService']['created']);
        }

        if (isset($data['DeletedService']['modified'])) {
            $this->modified = strtotime($data['DeletedService']['modified']);
        }

        $this->UserTime = $UserTime;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        if (isset($arr['UserTime'])) {
            unset($arr['UserTime']);
        }

        if ($this->UserTime !== null) {
            $arr['created'] = $this->UserTime->format($this->created);
            $arr['modified'] = $this->UserTime->format($this->modified);
        }
        return $arr;
    }

}
