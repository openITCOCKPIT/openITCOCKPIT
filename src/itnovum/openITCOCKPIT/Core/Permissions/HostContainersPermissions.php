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

namespace itnovum\openITCOCKPIT\Core\Permissions;


use itnovum\openITCOCKPIT\Core\HostSharingPermissions;

class HostContainersPermissions extends ContainersPermissions {

    /**
     * @var int
     */
    private $primaryContainerId;

    /**
     * @var array
     */
    private $sharedContainerIds = [];

    /**
     * @var array
     */
    private $MY_WRITABLE_CONTAINERS = [];

    /**
     * @var bool
     */
    private $hasRootPrivileges = false;

    /**
     * HostContainers constructor.
     * @param int $primaryContainerId
     * @param array|int $sharedContainerIds
     * @param array|int $MY_WRITABLE_CONTAINERS
     * @param bool $hasRootPrivileges
     */
    public function __construct($primaryContainerId, $sharedContainerIds, $MY_WRITABLE_CONTAINERS = [], $hasRootPrivileges = false) {
        $this->primaryContainerId = (int)$primaryContainerId;
        $this->sharedContainerIds = $this->castToIntArray($sharedContainerIds);
        $this->MY_WRITABLE_CONTAINERS = $this->castToIntArray($MY_WRITABLE_CONTAINERS);
        $this->hasRootPrivileges = $hasRootPrivileges;
    }

    /**
     * @return bool
     */
    public function isPrimaryContainerChangeable() {
        if ($this->primaryContainerId === ROOT_CONTAINER) {
            return false;
        }

        if ($this->hasRootPrivileges === true && $this->primaryContainerId !== ROOT_CONTAINER) {
            return true;
        }

        if ($this->hasRootPrivileges === false && isset($this->MY_WRITABLE_CONTAINERS[$this->primaryContainerId])) {
            return true;
        }

        return false;
    }

    /**
     * @param array|int $MY_RIGHTS
     * @param int $hostTypeId
     * @return bool
     */
    public function allowSharing($MY_RIGHTS, $hostTypeId) {
        $MY_RIGHTS = $this->castToIntArray($MY_RIGHTS);
        $hostTypeId = (int)$hostTypeId;

        if ($hostTypeId !== GENERIC_HOST) {
            return false;
        }

        $HostSharingPermissions = new HostSharingPermissions(
            $this->primaryContainerId,
            $this->hasRootPrivileges,
            $this->sharedContainerIds,
            $MY_RIGHTS
        );

        return $HostSharingPermissions->allowSharing();
    }

    /**
     * @return boolean|null
     */
    public function isHostOnlyEditableDueToHostSharing() {
        if (isset($this->MY_WRITABLE_CONTAINERS[$this->primaryContainerId])) {
            //User has permissions to this host via primary container
            return false;
        }

        foreach ($this->sharedContainerIds as $sharedContainerId) {
            if (isset($this->MY_WRITABLE_CONTAINERS[$sharedContainerId])) {
                return true;
            }
        }

        //The user has no permissions to see this host at all
        return null;
    }

}
