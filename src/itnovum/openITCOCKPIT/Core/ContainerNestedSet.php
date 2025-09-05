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

use App\Lib\Constants;

/**
 * Class ContainerNestedSet
 * @package itnovum\openITCOCKPIT\Core
 */
class ContainerNestedSet {

    /**
     * @var array
     */
    private $containers = [];

    /**
     * @var bool
     */
    private $hasRootPrivileges = false;

    /**
     * @var Constants
     */
    private $Constants;

    /**
     * ContainerNestedSet constructor.
     * @param array $containers
     * @param bool $hasRootPrivileges
     */
    private function __construct($containers, $hasRootPrivileges) {
        $this->containers = $containers;
        $this->hasRootPrivileges = $hasRootPrivileges;

        $this->Constants = new Constants();
    }

    /**
     * @param array $containersFindAllResult
     * @param bool $hasRootPrivileges
     * @return ContainerNestedSet
     */
    public static function fromCake2($containersFindAllResult, $hasRootPrivileges = false) {
        $containers = [];

        foreach ($containersFindAllResult as $row) {
            $containers[$row['Container']['id']] = $row['Container'];
        }

        return new self($containers, $hasRootPrivileges);
    }

    /**
     * @param array $containersFindAllResult
     * @param bool $hasRootPrivileges
     * @return ContainerNestedSet
     */
    public static function fromCake4($containersFindAllResult, $hasRootPrivileges = false) {
        $containers = [];

        foreach ($containersFindAllResult as $row) {
            $containers[$row['id']] = $row;
        }

        return new self($containers, $hasRootPrivileges);
    }


    /**
     * @param array|int $ids
     * @param int $include Object constant like OBJECT_NODE or OBJECT_HOST
     * @param array array $exclude list of object types to exclude like OBJECT_HOSTGROUP
     * @return array
     * @throws \Exception
     */
    public function easyPath($ids, $include, $exclude = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if ($this->hasRootPrivileges === false) {
            $ids = $this->removeRootContainer($ids);
        }

        if (empty($include) || empty($ids)) {
            return [];
        }

        return $this->path($ids, $this->Constants->containerProperties($include, $exclude));
    }

    /**
     * @param $ids
     * @param array $validateTypes
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function path($ids, $validateTypes = [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE], $order = 'asc') {
        $nodes = $this->filterContainersById($ids);
        $nodes = $this->filterContainersByContainerTypes($nodes, $validateTypes);

        $paths = [];
        foreach ($nodes as $container) {
            $paths[$container['id']] = $this->implodePath($this->getPathForContainer($container['id']));
        }

        if ($order === 'asc') {
            asort($paths);
        }

        if ($order === 'desc') {
            arsort($paths);
        }

        return $paths;
    }

    /**
     * @param int $containerId
     * @return array
     * @throws \Exception
     */
    public function getPathForContainer($containerId) {
        if (!isset($this->containers[$containerId])) {
            throw new \Exception('Container id not found in local array');
        }

        $getParentFor = $containerId;
        $path = [
            $this->containers[$containerId]
        ];
        do {
            $parent = $this->getParentContainer($getParentFor);
            if ($parent !== null) {
                $path[] = $parent;
                $getParentFor = $parent['id'];
            }
        } while ($parent !== null);

        return array_reverse($path);
    }

    /**
     * @param array $path from self::getPathForContainer
     * @param string $delimiter
     * @return null|string
     */
    private function implodePath($path, $delimiter = '/') {
        $names = [];
        foreach ($path as $container) {
            $names[] = $container['name'];
        }
        if (empty($names)) {
            return null;
        }

        return '/' . implode($delimiter, $names);
    }

    /**
     * @param $containerId
     * @return mixed|null
     * @throws \Exception
     */
    private function getParentContainer($containerId) {
        if (!isset($this->containers[$containerId])) {
            throw new \Exception('Container id not found in local array');
        }

        $container = $this->containers[$containerId];
        if (is_numeric($container['parent_id']) && $container['parent_id'] !== null) {
            return $this->containers[$container['parent_id']];
        }

        return null;
    }

    /**
     * @param $MY_RIGHTS
     * @return array
     */
    public function filterContainersByPermissions($MY_RIGHTS) {
        $containers = [];

        foreach ($MY_RIGHTS as $permittedContainerId) {
            $containers[] = $this->containers[$permittedContainerId];
        }
        return $containers;
    }

    /**
     * @param array $containers
     * @param array $containerTypeIds with container type constants
     * @return array
     */
    public function filterContainersByContainerTypes($containers, $containerTypeIds) {
        $filteredContainers = [];
        foreach ($containers as $container) {
            $containerTypeId = (int)$container['containertype_id'];
            if (in_array($containerTypeId, $containerTypeIds, true)) {
                $filteredContainers[$container['id']] = $container;
            }
        }

        return $filteredContainers;
    }

    /**
     * @param array|int $ids
     * @return array
     */
    public function filterContainersById($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $containers = [];
        foreach ($ids as $id) {
            if (isset($this->containers[$id])) {
                $containers[$id] = $this->containers[$id];
            }
        }
        return $containers;
    }

    /**
     * Remove the ROOT_CONTAINER from a given array with container ids as value
     *
     * @param array $containerIds
     *
     * @return array
     */
    public function removeRootContainer($containerIds) {
        $result = [];
        foreach ($containerIds as $containerId) {
            $containerId = (int)$containerId;
            if ($containerId !== ROOT_CONTAINER) {
                $result[] = $containerId;
            }
        }
        return $result;
    }

    /**
     * @param array|int $containerIds
     * @param bool $resolveRoot
     * @param array $includeContainerTypes
     * @return array
     */
    public function resolveChildrenOfContainerIds($containerIds, $resolveRoot = false, $includeContainerTypes = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $containerIds = array_unique($containerIds);
        $result = [ROOT_CONTAINER];
        foreach ($containerIds as $containerId) {
            $containerId = (int)$containerId;
            if (!isset($this->containers[$containerId])) {
                continue;
            }
            if ($containerId === ROOT_CONTAINER && $resolveRoot === false) {
                continue;
            }

            $container = $this->containers[$containerId];
            $lft = (int)$container['lft'];
            $rght = (int)$container['rght'];

            $children = [];
            foreach ($this->containers as $possibleChildrenContainer) {
                if ($possibleChildrenContainer['lft'] > $lft && $possibleChildrenContainer['rght'] < $rght) {
                    $children[] = $possibleChildrenContainer;
                }
            }

            if (!empty($includeContainerTypes)) {
                $children = \Hash::extract($children, '{n}[containertype_id=/^(' . implode('|', $includeContainerTypes) . ')$/].id');
            } else {
                $children = \Hash::extract($children, '{n}.id');
            }
            $result = array_merge($result, $children);
            $result[] = $containerId;
        }


        return array_unique($result);
    }
}
