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

namespace App\itnovum\openITCOCKPIT\Maps;

use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Maps\MapForAngular;
use MapModule\Model\Table\MapsTable;
use MapModule\Model\Table\MapsummaryitemsTable;

class Mapgenerator {

    public const TYPE_GENERATE_BY_CONTAINER_STRUCTURE = 1;
    public const TYPE_GENERATE_BY_HOSTNAME_SPLITTING = 2;

    /**
     * @var array
     */
    private $mapgeneratorData;

    /**
     * @var array
     */
    private $mapsAndHostsData;

    /**
     * @var int
     */
    private $type;

    /**
     * @var array
     */
    private $allGeneratedMaps;

    /**
     * @var array
     */
    private $generatedItems;

    /**
     * @var array
     */
    private $newGeneratedMaps;

    /**
     * @var array
     */
    private $cachedMapsWithItems = [];


    /**
     * Mapgenerator constructor.
     * @param array $mapsAndHostsData
     * @param array $generatedMaps
     * @param int $type
     */
    public function __construct(array $mapgeneratorData, array $mapsAndHostsData, array $generatedMaps = [], int $type = 1) {
        $this->mapgeneratorData = $mapgeneratorData;
        $this->mapsAndHostsData = $mapsAndHostsData;
        $this->type = $type;
        $this->allGeneratedMaps = $generatedMaps;
        $this->generatedItems = [];
        $this->newGeneratedMaps = [];
        $this->cachedMapsWithItems = [];
    }

    public function generate() {

        if ($this->type === self::TYPE_GENERATE_BY_CONTAINER_STRUCTURE) {

            $generatedMapsAndItems = $this->generateByContainerHierarchy($this->mapsAndHostsData);

        } else if ($this->type === self::TYPE_GENERATE_BY_HOSTNAME_SPLITTING) {

            $generatedMapsAndItems = $this->generateByHostname($this->mapsAndHostsData);

        }

        if (array_key_exists("error", $generatedMapsAndItems)) {
            return $generatedMapsAndItems;
        }

        $generatedMapsAndItems = $this->convertToArray();

        return $generatedMapsAndItems;
    }

    private function generateByContainerHierarchy(array $mapsAndHostsData) {

        $higherMap = []; // this is the map that is generated for the container that is higher in the hierarchy
        $generatedMapsAndItems = [
            'maps'  => [],
            'items' => []
        ];

        // create maps
        foreach ($mapsAndHostsData as $containerKey => $container) {

            // check if container is already generated
            $containerName = $this->buildMapName($container, $mapsAndHostsData);
            $map = null;

            if (in_array($containerName, Hash::extract($this->allGeneratedMaps, '{n}.name'), true)) {
                foreach ($this->allGeneratedMaps as $generatedMap) {
                    if ($containerName === $generatedMap['name']) {
                        $map = $generatedMap;
                    }
                }

            } else {

                // create new map for this container
                $map = $this->createNewMap($containerName, $this->mapgeneratorData['map_refresh_interval'], $container['containerIdForNewMap']);

                if (array_key_exists("error", $map)) {
                    return $map;
                }

                $this->allGeneratedMaps[] = $map;
                $this->newGeneratedMaps[] = $map;
                $generatedMapsAndItems['maps'][] = $map;

                // add map as mapsummaryitem to the previously generated map
                if (array_key_exists("parentIndex", $container) && !empty($mapsAndHostsData[$container['parentIndex']]) && $containerKey > 0) {

                    $higherMapAndHostsData = $mapsAndHostsData[$container['parentIndex']];
                    $higherMapContainerName = $this->buildMapName($higherMapAndHostsData, $mapsAndHostsData);

                    if (in_array($higherMapContainerName, Hash::extract($this->allGeneratedMaps, '{n}.name'), true)) {
                        foreach ($this->allGeneratedMaps as $generatedMap) {
                            if ($higherMapContainerName === $generatedMap['name']) {
                                $higherMap = $generatedMap;
                            }
                        }
                    }

                    if (!empty($higherMap)) {

                        $mapsummaryitem = $this->createNewMapSummaryItem($higherMap, $map["id"], 'map');

                        if (array_key_exists("error", $mapsummaryitem)) {
                            return $mapsummaryitem;
                        }

                        $this->generatedItems[] = $mapsummaryitem;
                        $generatedMapsAndItems['items'][] = $mapsummaryitem;

                    }
                }

            }

            // create Hosts
            if (!empty($container['hosts'])) {
                foreach ($container['hosts'] as $host) {
                    $newHostItem = $this->createNewMapSummaryItem($map, $host['id'], 'host');

                    if (array_key_exists("error", $newHostItem)) {
                        return $newHostItem;
                    }

                    if (!empty($newHostItem)) {
                        $this->generatedItems[] = $newHostItem;
                        $generatedMapsAndItems['items'][] = $newHostItem;
                    }
                }
            }

        }

        return $generatedMapsAndItems;
    }

    /**
     *
     * to build unique names, which can be assigned to a map hierarchy
     *
     * @param $map
     * @param $mapsAndHostsData
     * @return string
     */
    private function buildMapName($map, $mapsAndHostsData) {
        $mapNameParts = [];

        while ($map !== null) {
            $mapNameParts[] = $map['name'];
            $parentIndex = null;
            if (array_key_exists("parentIndex", $map)) {
                $parentIndex = $map['parentIndex'];
            }
            $map = $parentIndex !== null ? $mapsAndHostsData[$parentIndex] : null;
        }

        return implode('/', array_reverse($mapNameParts));
    }

    private function generateByHostname(array $mapsAndHostsData) {

        $generatedMapsAndItems = [
            'maps'  => [],
            'items' => []
        ];

        // create maps
        foreach ($mapsAndHostsData as $mapKey => $mapAndHosts) {

            // check if map is already generated
            $map = null;

            if (in_array($mapAndHosts['name'], Hash::extract($this->allGeneratedMaps, '{n}.name'), true)) {
                foreach ($this->allGeneratedMaps as $generatedMap) {
                    if ($mapAndHosts['name'] === $generatedMap['name']) {
                        $map = $generatedMap;
                    }
                }
            } else {

                // create new map for this mapName
                $map = $this->createNewMap($mapAndHosts['name'], $this->mapgeneratorData['map_refresh_interval'], $mapAndHosts['containerIdForNewMap']);

                if (array_key_exists("error", $map)) {
                    return $map;
                }

                $this->allGeneratedMaps[] = $map;
                $this->newGeneratedMaps[] = $map;
                $generatedMapsAndItems['maps'][] = $map;


                // add map as mapsummaryitem to the previously generated map
                if (array_key_exists("parentIndex", $mapAndHosts) && !empty($mapsAndHostsData[$mapAndHosts['parentIndex']]) && $mapKey > 0) {

                    $higherMapAndHostsData = $mapsAndHostsData[$mapAndHosts['parentIndex']];

                    if (in_array($higherMapAndHostsData['name'], Hash::extract($this->allGeneratedMaps, '{n}.name'), true)) {
                        foreach ($this->allGeneratedMaps as $generatedMap) {
                            if ($higherMapAndHostsData['name'] === $generatedMap['name']) {
                                $higherMap = $generatedMap;
                            }
                        }
                    }

                    if (!empty($higherMap)) {

                        $mapsummaryitem = $this->createNewMapSummaryItem($higherMap, $map["id"], 'map');


                        if (array_key_exists("error", $mapsummaryitem)) {
                            return $mapsummaryitem;
                        }

                        $this->generatedItems[] = $mapsummaryitem;
                        $generatedMapsAndItems['items'][] = $mapsummaryitem;

                    }
                }

            }


            // create Hosts
            if (!empty($mapAndHosts['hosts'])) {
                foreach ($mapAndHosts['hosts'] as $host) {
                    $newHostItem = $this->createNewMapSummaryItem($map, $host['id'], 'host');

                    if (array_key_exists("error", $newHostItem)) {
                        return $newHostItem;
                    }

                    if (!empty($newHostItem)) {
                        $this->generatedItems[] = $newHostItem;
                        $generatedMapsAndItems['items'][] = $newHostItem;
                    }
                }
            }

        }

        return $generatedMapsAndItems;

    }

    private function createNewMap(string $name, int $refreshInterval, int $containerId) {

        $map = [];

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $mapData = [
            'containers'       => [
                '_ids' => [$containerId]
            ],
            'name'             => $name,
            'title'            => $name,
            'refresh_interval' => $refreshInterval,
            'auto_generated'   => 1
        ];

        $map = $MapsTable->newEmptyEntity();
        $map = $MapsTable->patchEntity($map, $mapData);

        $MapsTable->save($map);
        if ($map->hasErrors()) {
            return [
                'error' => $map->getErrors()
            ];
        }

        return $map->toArray();

    }

    private function createNewMapSummaryItem(array $mapToAddItems, int $objectId, string $type) {

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        // load map with items from cache or database
        if (!isset($this->cachedMapsWithItems[$mapToAddItems["id"]])) {
            //get all items of the map to add the new item
            $this->cachedMapsWithItems[$mapToAddItems["id"]] = $MapsTable->get($mapToAddItems["id"], contain: [
                'Containers',
                'Mapgadgets',
                'Mapicons',
                'Mapitems',
                'Maplines',
                'Maptexts',
                'Mapsummaryitems'
            ])->toArray();
        }
        $mapToAddItemsWithItems = $this->cachedMapsWithItems[$mapToAddItems["id"]];

        $MapForAngular = new MapForAngular($mapToAddItemsWithItems);
        $mapToAddItemsWithItems = $MapForAngular->toArray();

        /**
         * calculate new x and y position for the new mapsummaryitem
         * by searching for the previous item and its position in the existing items
         */
        $x = 0; // x position of the new item
        $y = 0; // y position of the new item
        $LINE_HEIGHT = 140; // size of one line in the map
        $ITEM_MIN_WIDTH = 200; // minimum width of an item
        $ITEMS_PER_LINE = $this->mapgeneratorData['items_per_line']; // number of items per line
        $mapHasItems = false; // if map has only one item, calculate position based on this item
        $previousItem = [
            'type' => "",
            'id'   => 0,
        ];
        $itemsPerLineCounter = 0; // counter for items per line

        // searching for the previous item and its position in the existing items
        foreach (['Mapgadgets', 'Mapicons', 'Mapitems', 'Maplines', 'Maptexts', 'Mapsummaryitems'] as $itemType) {
            if (isset($mapToAddItemsWithItems[$itemType])) {
                $mapHasItems = true;
                foreach ($mapToAddItemsWithItems[$itemType] as $item) {

                    // check if mapsummaryitem is already on the map and break if so
                    if ($itemType === "Mapsummaryitems" && $item['object_id'] === $objectId && $item['type'] === $type) {
                        return [];
                    }

                    $itemX = ($itemType === 'Maplines') ? $item['endX'] : $item['x'];
                    $itemY = ($itemType === 'Maplines') ? $item['endY'] : $item['y'];

                    // start new line
                    if ($itemY > $y) {
                        $y = $itemY;
                        $x = 0;
                        $itemsPerLineCounter = 1;
                        $previousItem = [
                            'type' => $item['type'],
                            'id'   => $item['object_id']
                        ];
                    } else if ($itemY === $y) {
                        $itemsPerLineCounter++;
                        if ($itemX > $x || ($itemX === $x && $itemX === 0 && $itemY === 0)) {
                            $x = $itemX;
                            $previousItem = [
                                'type' => $item['type'],
                                'id'   => $item['object_id']
                            ];
                        }
                    }
                }
            }
        }

        // get name of the previous item and calculate the width
        $width = $this->calculateItemWidth($previousItem);

        // if y does not fit the line height (130px), add some space to the top
        if ($y > 0 && $y % $LINE_HEIGHT !== 0) {
            $y += ($y % $LINE_HEIGHT); // add some space to the top
        }

        if ($x > 0 || $mapHasItems) {
            if ($width < $ITEM_MIN_WIDTH) {
                $width = $ITEM_MIN_WIDTH;
            }
            $x += $width; // add some space to the right
        }
        // if item is too far to the right, move it to the next line
        if ($itemsPerLineCounter >= $ITEMS_PER_LINE) {
            $x = 0; // reset x position to start
            $y += $LINE_HEIGHT; // add some space to the bottom
        }

        /** @var MapsummaryitemsTable $MapsummaryitemsTable */
        $MapsummaryitemsTable = TableRegistry::getTableLocator()->get('MapModule.Mapsummaryitems');

        $mapsummaryitemEntity = $MapsummaryitemsTable->newEmptyEntity();

        // add item to the map
        $mapsummaryitem['Mapsummaryitem'] = [
            "z_index"         => "0",
            "x"               => $x,
            "y"               => $y,
            "size_x"          => 0,
            "size_y"          => 0,
            "show_label"      => 1,
            "label_possition" => 2,
            "type"            => $type,
            "object_id"       => $objectId,
            "map_id"          => $mapToAddItems["id"]
        ];
        $mapsummaryitemEntity = $MapsummaryitemsTable->patchEntity($mapsummaryitemEntity, $mapsummaryitem['Mapsummaryitem']);
        $MapsummaryitemsTable->save($mapsummaryitemEntity);

        if ($mapsummaryitemEntity->hasErrors()) {
            return [
                'error' => $mapsummaryitemEntity->getErrors()
            ];
        }

        // add mapsummaryitem to the cached maps with items
        $this->cachedMapsWithItems[$mapToAddItems["id"]]['mapsummaryitems'][] = $mapsummaryitemEntity->toArray();

        return $mapsummaryitemEntity->toArray();

    }

    private function calculateItemWidth(array $previousItem) {
        $width = 0;
        $namesById = [];
        if ($previousItem['type'] && $previousItem['id']) {
            if ($previousItem['type'] === 'map') {
                $namesById = Hash::combine($this->allGeneratedMaps, '{n}.id', '{n}.name');
            } else if ($previousItem['type'] === 'host') {
                $namesById = Hash::combine($this->mapsAndHostsData, '{n}.hosts.{n}.id', '{n}.hosts.{n}.name');
            }

            if ($namesById && isset($namesById[$previousItem['id']])) {
                $width = strlen($namesById[$previousItem['id']]) * 7; // calculate width based on name length
            }
        }
        return $width;
    }

    private function convertToArray() {

        $generatedMapsAndItems = [
            'maps'  => [],
            'items' => []
        ];

        $mapsById = Hash::combine($this->allGeneratedMaps, '{n}.id', '{n}.name');

        foreach ($this->newGeneratedMaps as $map) {
            $generatedMapsAndItems['maps'][] = $map;
        }

        foreach ($this->generatedItems as $item) {

            $item["map"] = [
                'id'   => $item['map_id'],
                'name' => $mapsById[$item['map_id']]
            ];

            $generatedMapsAndItems['items'][] = $item;
        }

        return $generatedMapsAndItems;

    }

    public function getAllGeneratedMaps(): array {
        return $this->allGeneratedMaps;
    }

    public function getGeneratedItems(): array {
        return $this->generatedItems;
    }

    public function getNewGeneratedMaps(): array {
        return $this->newGeneratedMaps;
    }


}
