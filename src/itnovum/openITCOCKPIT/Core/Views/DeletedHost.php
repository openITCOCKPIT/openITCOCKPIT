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


class DeletedHost {

    private $id = null;

    private $uuid = null;

    private $hosttemplateId = null;

    private $hostId = null;

    private $name = null;

    private $description = null;

    private $perfdataDeleted = null;

    private $created = null;

    private $modified = null;

    /**
     * @var UserTime
     */
    private $UserTime;

    /**
     * DeletedHost constructor.
     * @param array $deletedHost
     * @param UserTime $UserTime
     */
    public function __construct($deletedHost, $UserTime = null) {
        if (isset($deletedHost['DeletedHost']['id'])) {
            $this->id = (int)$deletedHost['DeletedHost']['id'];
        }

        if (isset($deletedHost['DeletedHost']['uuid'])) {
            $this->uuid = $deletedHost['DeletedHost']['uuid'];
        }

        if (isset($deletedHost['DeletedHost']['name'])) {
            $this->name = $deletedHost['DeletedHost']['name'];
        }

        if (isset($deletedHost['DeletedHost']['hosttemplate_id'])) {
            $this->hosttemplateId = (int)$deletedHost['DeletedHost']['hosttemplate_id'];
        }

        if (isset($deletedHost['DeletedHost']['host_id'])) {
            $this->hostId = (int)$deletedHost['DeletedHost']['host_id'];
        }

        if (isset($deletedHost['DeletedHost']['description'])) {
            $this->description = $deletedHost['DeletedHost']['description'];
        }

        if (isset($deletedHost['DeletedHost']['deleted_perfdata'])) {
            $this->perfdataDeleted = (bool)$deletedHost['DeletedHost']['deleted_perfdata'];
        }

        if (isset($deletedHost['DeletedHost']['created'])) {
            $this->created = $deletedHost['DeletedHost']['created'];
        }

        if (isset($deletedHost['DeletedHost']['modified'])) {
            $this->modified = $deletedHost['DeletedHost']['modified'];
        }

        $this->UserTime = $UserTime;

    }

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return null
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * @return int|null
     */
    public function getHosttemplateId() {
        return $this->hosttemplateId;
    }

    /**
     * @return int|null
     */
    public function getHostId() {
        return $this->hostId;
    }

    /**
     * @return null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return bool|null
     */
    public function hasPerfdataBeenDeleted() {
        return $this->perfdataDeleted;
    }

    /**
     * @return null
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * @return null
     */
    public function getModified() {
        return $this->modified;
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
            $arr['created'] = $this->UserTime->format($this->getCreated());
            $arr['modified'] = $this->UserTime->format($this->getModified());
        }
        return $arr;
    }

}
