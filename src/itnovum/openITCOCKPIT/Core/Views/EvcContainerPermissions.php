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


class EvcContainerPermissions {

    /**
     * @var array
     */
    private $MY_RIGHTS_LEVEL = [];


    /**
     * @var array
     */
    private $MY_VIEW_RIGHTS_LEVEL = [];

    /**
     * @var array
     */
    private $usedEvcContainerIdsGroupByHost = [];

    /**
     * @var int
     */
    private $evcPrimaryContainerId;

    /**
     * ContainerPermissions constructor.
     * @param array $MY_RIGHTS_LEVEL
     * @param array $ContainersToCheck
     */
    public function __construct(array $MY_RIGHTS_LEVEL, array $usedEvcContainerIdsGroupByHost, $evcPrimaryContainerId) {
        $this->MY_VIEW_RIGHTS_LEVEL = $MY_RIGHTS_LEVEL;
        $this->evcPrimaryContainerId = (int)$evcPrimaryContainerId;
        foreach ($MY_RIGHTS_LEVEL as $containerId => $rightLevel) {
            $rightLevel = (int)$rightLevel;
            if ($rightLevel === WRITE_RIGHT) {
                $this->MY_RIGHTS_LEVEL[$containerId] = $rightLevel;
            }
        }
        $this->usedEvcContainerIdsGroupByHost = $usedEvcContainerIdsGroupByHost;
    }

    /**
     * @return bool
     */
    public function hasEditPermission() {
        $canEdit = false;
        foreach ($this->usedEvcContainerIdsGroupByHost as $hostId => $containers) {
            $usedEvcContainerIdsGroupByHost = $this->usedEvcContainerIdsGroupByHost;


            if (in_array(ROOT_CONTAINER, $usedEvcContainerIdsGroupByHost[$hostId], true)) {
                // Remove root container from list
                foreach($usedEvcContainerIdsGroupByHost[$hostId] as $i => $v){
                    if($v === ROOT_CONTAINER){
                        unset($usedEvcContainerIdsGroupByHost[$hostId][$i]);
                    }
                }

                if (empty($usedEvcContainerIdsGroupByHost[$hostId])) {
                    //This host had only the ROOT_CONTAINER (allowed for everyone)
                    //Fallback to EVCs primary container id
                    if (isset($this->MY_RIGHTS_LEVEL[$this->evcPrimaryContainerId])) {
                        $canEdit = $this->MY_RIGHTS_LEVEL[$this->evcPrimaryContainerId] === WRITE_RIGHT;
                        if ($canEdit === false) {
                            return false;
                        }
                        continue;
                    } else {
                        return false;
                    }
                }

            }
            $containersToCheck = $usedEvcContainerIdsGroupByHost[$hostId];

            $canEdit = !empty(array_intersect($containersToCheck, array_keys($this->MY_RIGHTS_LEVEL)));
            if ($canEdit === false) {
                //User is not allowd to edit this host.
                //So whole EVC is not editable for this user.
                return false;
            }
        }
        return $canEdit;
    }

    /**
     * @return bool
     */
    public function hasViewPermission() {
        $canView = false;
        foreach ($this->usedEvcContainerIdsGroupByHost as $hostId => $containers) {


            $canView = !empty(array_intersect($containers, array_keys($this->MY_VIEW_RIGHTS_LEVEL)));
            if ($canView === false) {
                //User is not allowd to edit this host.
                //So whole EVC is not editable for this user.
                return false;
            }
        }
        return $canView;
    }
}
