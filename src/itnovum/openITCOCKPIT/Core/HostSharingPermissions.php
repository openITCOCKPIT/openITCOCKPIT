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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\Core;


class HostSharingPermissions {

    /**
     * @var int
     */
    private $hostContainerId;

    /**
     * @var boolean
     */
    private $hasRootPrivileges;

    /**
     * @var array
     */
    private $hostSharingContainerIds;

    /**
     * @var array
     */
    private $userContainerIds;

    /**
     * HostSharingPermissions constructor.
     *
     * @param int $hostContainerId
     * @param boolean $hasRootPrivileges
     *
     * @paran array $hostSharingContainerIds
     * @paran array $userContainerIds
     */
    public function __construct($hostContainerId, $hasRootPrivileges, $hostSharingContainerIds, $userContainerIds) {
        $this->hostContainerId = $hostContainerId;
        $this->hasRootPrivileges = $hasRootPrivileges;
        $this->hostSharingContainerIds = $this->cleanHostContainerArray($hostContainerId, $hostSharingContainerIds);
        $this->userContainerIds = $userContainerIds;
    }

    /**
     * @return bool
     */
    public function allowSharing() {
        if ($this->isRootHostAndRestrictedUser()) {
            return false;
        }
        if ($this->isRootHostAndRestrictedUser() === false) {
            if ($this->isSharedToNotPermittedContainers()) {
                return false;
            }
            if ($this->hostPrimaryContaineIsNotPermitted() === true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isRootHostAndRestrictedUser() {
        if ($this->hostContainerId == ROOT_CONTAINER && $this->hasRootPrivileges === false) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isSharedToNotPermittedContainers() {
        foreach ($this->hostSharingContainerIds as $hostSharedContainerId) {
            if (in_array($hostSharedContainerId, $this->userContainerIds) === false) {
                return true;
            }
        }

        return false;
    }

    public function hostPrimaryContaineIsNotPermitted() {
        if (!in_array($this->hostContainerId, $this->userContainerIds)) {
            return true;
        }

        return false;
    }

    public function cleanHostContainerArray($hostContainerId, $hostSharingContainerIds) {
        $sharedContainerIds = [];
        foreach ($hostSharingContainerIds as $key => $hostSharingContainerId) {
            if ($hostSharingContainerId != $hostContainerId) {
                $sharedContainerIds[] = $hostSharingContainerId;
            }
        }

        return $sharedContainerIds;
    }
}
