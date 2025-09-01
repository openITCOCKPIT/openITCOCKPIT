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


class ContainerRepository {

    /**
     * @var array
     */
    private $containerIds = [];

    /**
     * ContainerRepository constructor.
     *
     * @param int|array $containerIds
     */
    public function __construct($containerIds = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $this->containerIds = $containerIds;
        $this->toInt();
    }

    public function getContainer() {
        return $this->containerIds;
    }

    /**
     * @param int|array $containerIds
     */
    public function addContainer($containerIds) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        foreach ($containerIds as $containerId) {
            $containerId = (int)$containerId;
            if ($this->exists($containerId) === false) {
                $this->containerIds[] = $containerId;
            }
        }
    }

    /**
     * @param int $containerId
     *
     * @return bool
     */
    public function exists($containerId) {
        $containerId = (int)$containerId;

        return in_array($containerId, $this->containerIds);
    }

    /**
     * Remove a given container id from repository
     *
     * @param int|array $containerId
     */
    public function removeContainerId($containerIdsToRemove) {
        if (!is_array($containerIdsToRemove)) {
            $containerIdsToRemove = [$containerIdsToRemove];
        }
        $_containerIdsToKeep = [];
        foreach ($this->containerIds as $currentContainerId) {
            if (!in_array($currentContainerId, $containerIdsToRemove)) {
                $_containerIdsToKeep[] = $currentContainerId;
            }
        }
        $this->containerIds = $_containerIdsToKeep;
    }

    private function toInt() {
        $containerIds = [];
        foreach ($this->containerIds as $containerId) {
            $containerIds[] = (int)$containerId;
        }
        $this->containerIds = $containerIds;
    }

}
