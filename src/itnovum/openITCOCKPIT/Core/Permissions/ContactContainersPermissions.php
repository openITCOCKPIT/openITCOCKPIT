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


class ContactContainersPermissions extends ContainersPermissions {

    /**
     * @var array
     */
    private $containerIds = [];

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
     * @param array|int $containerIds
     * @param array|int $MY_WRITABLE_CONTAINERS
     * @param bool $hasRootPrivileges
     */
    public function __construct($containerIds, $MY_WRITABLE_CONTAINERS = [], $hasRootPrivileges = false) {
        $this->containerIds = $this->castToIntArray($containerIds);
        $this->MY_WRITABLE_CONTAINERS = $this->castToIntArray($MY_WRITABLE_CONTAINERS);
        $this->hasRootPrivileges = $hasRootPrivileges;
    }

    /**
     * @return bool
     */
    public function areContainersChangeable() {
        if ($this->hasRootPrivileges) {
            //Root users can edit all objects
            return true;
        }

        if (isset($this->containerIds[ROOT_CONTAINER])) {
            //Only root users can edit objects, which use /root as container
            return false;
        }

        $hasPermissionsToAllContainers = false;
        foreach ($this->containerIds as $containerId) {
            //Has the user write permissions to all containers of the contact?
            $hasPermissionsToAllContainers = isset($this->MY_WRITABLE_CONTAINERS[$containerId]);
            if ($hasPermissionsToAllContainers === false) {
                return false;
            }
        }
        if ($hasPermissionsToAllContainers === true) {
            return true;
        }

        return false;
    }
}
