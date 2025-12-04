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
use MapModule\Model\Table\MapsTable;
use MapModule\Model\Table\MapsummaryitemsTable;

class Mapgenerator {

    public const TYPE_GENERATE_BY_CONTAINER_STRUCTURE = 1;
    public const TYPE_GENERATE_BY_HOSTNAME_SPLITTING = 2;

    /**
     * configuration data of the mapgenerator
     *
     * @var array
     */
    private $mapgeneratorData;

    /**
     * maps and hosts data to generate the maps and items
     *
     * format of the array:
     * [
     * 'name' => 'Name of the map or container',
     * 'containerIdForNewMap' => 1, // container id to assign the new map to
     * 'parentIndex' => 0, // index of the parent container or map in this array
     * 'hosts' => [ // array of hosts to add to this map
     *   [
     *    'id' => 1, // id of the host
     *   'name' => 'Name of the host'
     *  ],
     * ]
     *
     * @var array
     */
    private $mapsAndHostsData;

    /**
     * type of the generator
     *
     * @var int
     */
    private $type;

    /**
     * all generated maps (including the maps that are already existing)
     *
     * @var array
     */
    private $allGeneratedMaps;

    /**
     * all new generated items (maps and host summary items)
     *
     * @var array
     */
    private $generatedItems;

    /**
     * the new generated maps in this run
     *
     * @var array
     */
    private $newGeneratedMaps;


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
    }

    public function generate() {

        $generatedMapsAndItems = $this->generateMapsAndItems($this->mapsAndHostsData);

        if (array_key_exists("error", $generatedMapsAndItems)) {
            return $generatedMapsAndItems;
        }

        $generatedMapsAndItems = $this->convertToArray();

        return $generatedMapsAndItems;
    }

    /**
     * generate maps and items based on the given data
     * - first generate the map
     * - than add the map to the higher map as mapsummaryitem (if there is a higher map)
     * - than add the hosts as mapsummaryitems to the generated map
     *
     * @param array $mapsAndHostsData
     * @return array|array[]|mixed[]
     */
    private function generateMapsAndItems(array $mapsAndHostsData) {

        $higherMap = []; // this is the map that is generated for the map that is higher in the hierarchy
        $generatedMapsAndItems = [
            'maps'  => [],
            'items' => []
        ];

        // create maps
        foreach ($mapsAndHostsData as $mapKey => $mapAndHosts) {

            $mapAddedToHigherMap = false;

            // check if container is already generated
            $mapName = $mapAndHosts['name'];
            if ($this->type === $this::TYPE_GENERATE_BY_CONTAINER_STRUCTURE) {
                $mapName = $this->buildMapName($mapAndHosts, $mapsAndHostsData);
            }
            $map = null;

            if (in_array($mapName, Hash::extract($this->allGeneratedMaps, '{n}.name'), true)) {
                foreach ($this->allGeneratedMaps as $generatedMap) {
                    if ($mapName === $generatedMap['name']) {
                        $map = $generatedMap;
                        break;
                    }
                }

                //check if map has a mapsummaryitem in the higher map (to add it if someone deletes the higher map)
                foreach ($this->allGeneratedMaps as $generatedMap) {
                    if (!empty($generatedMap['mapsummaryitems'])) {
                        foreach ($generatedMap['mapsummaryitems'] as $generatedItem) {
                            if ($generatedItem['type'] === 'map' && $generatedItem['object_id'] === $map['id']) {
                                // mapsummaryitem already exists
                                $mapAddedToHigherMap = true;
                                break;
                            }
                        }
                    }
                }


            } else {

                // create new map for this container
                $map = $this->createNewMap($mapName, $this->mapgeneratorData['map_refresh_interval'], $mapAndHosts['containerIdForNewMap']);

                if (array_key_exists("error", $map)) {
                    return $map;
                }

                $this->allGeneratedMaps[] = $map;
                $this->newGeneratedMaps[] = $map;
                $generatedMapsAndItems['maps'][] = $map;

            }

            // add map as mapsummaryitem to the previously generated map
            if (!$mapAddedToHigherMap && array_key_exists("parentIndex", $mapAndHosts) && !empty($mapsAndHostsData[$mapAndHosts['parentIndex']]) && $mapKey > 0) {

                $higherMapAndHosts = $mapsAndHostsData[$mapAndHosts['parentIndex']];
                $higherMapName = $higherMapAndHosts['name'];
                if ($this->type === $this::TYPE_GENERATE_BY_CONTAINER_STRUCTURE) {
                    $higherMapName = $this->buildMapName($higherMapAndHosts, $mapsAndHostsData);
                }

                if (in_array($higherMapName, Hash::extract($this->allGeneratedMaps, '{n}.name'), true)) {
                    foreach ($this->allGeneratedMaps as $generatedMap) {
                        if ($higherMapName === $generatedMap['name']) {
                            $higherMap = $generatedMap;
                            break;
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

            // create Hosts
            if (!empty($mapAndHosts['hosts'])) {
                foreach ($mapAndHosts['hosts'] as $host) {
                    $newHostItem = $this->createNewMapSummaryItem($map, $host['id'], 'host');

                    if (array_key_exists("error", $newHostItem)) {
                        return $newHostItem;
                    }

                    if (!empty($newHostItem)) {

                        // add new host item to the current map to keep the state for calculating the position of the next item
                        $map['mapsummaryitems'][] = $newHostItem;

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

        /**
         * calculate new x and y position for the new mapsummaryitem
         * by searching for the previous item and its position in the existing items
         */
        $x = 0; // x position of the new item
        $y = 0; // y position of the new item
        $LINE_HEIGHT = 140; // size of one line in the map
        $WIDTH = 350; // default width of an item
        $ITEMS_PER_LINE = $this->mapgeneratorData['items_per_line']; // number of items per line
        $mapHasItems = false; // if map has only one item, calculate position based on this item
        $itemsPerLineCounter = 0; // counter for items per line
        $DEFAULT_ICON_WIDTH = 100;
        $iconWidth = $DEFAULT_ICON_WIDTH; // default with of mapsummaryitem icon
        $iconHeight = $DEFAULT_ICON_WIDTH; // default height of mapsummaryitem icon
        $lineHeightAlreadyIncreased = false; // flag to check if line height was already increased

        // searching for the previous item and its position in the existing items
        foreach (['mapsummaryitems', 'mapgadgets', 'mapicons', 'mapitems', 'maplines', 'maptexts'] as $itemType) {
            if (isset($mapToAddItems[$itemType])) {
                $mapHasItems = true;
                foreach ($mapToAddItems[$itemType] as $item) {

                    // check if mapsummaryitem is already on the map and break if so
                    if ($itemType === "mapsummaryitems" && $item['object_id'] === $objectId && $item['type'] === $type) {
                        return [];
                    }

                    if ($itemType === 'maplines') {
                        $itemX = max($item['startX'], $item['endX']);
                        $itemY = max($item['startY'], $item['endY']);
                    } else {
                        $itemX = $item['x'];
                        $itemY = $item['y'];
                    }

                    // skip items that are above the current y position (higher row)
                    if ($itemY < $y) {
                        continue;
                    }

                    // start new line
                    if ($itemY > $y) {
                        $y = $itemY;
                        $x = 0;
                        $itemsPerLineCounter = 1;

                        $iconWidth = $DEFAULT_ICON_WIDTH;
                        $iconHeight = $DEFAULT_ICON_WIDTH;

                        if (!empty($item['size_x'])) {
                            $iconWidth = $item['size_x'];
                        }

                        if (!empty($item['size_y'])) {
                            $iconHeight = $item['size_y'];
                        }

                        // if icon is higher than line height, increase y position
                        if ($iconHeight > $LINE_HEIGHT) {
                            // cause mapsummaryitems have same height and width and height is in DB wrongly set, we use width for mapsummaryitems
                            if ($itemType === 'mapsummaryitems') {
                                $y += $iconWidth;
                            } else {
                                $y += $iconHeight;
                            }
                        }

                        if ($itemType !== 'mapsummaryitems') {
                            $iconWidth = $DEFAULT_ICON_WIDTH;
                            $itemsPerLineCounter = $ITEMS_PER_LINE + 1; // force new line for next item to continue grid
                        }

                    } else if ($itemY === $y && $itemType === 'mapsummaryitems') {
                        $itemsPerLineCounter++;
                        if ($itemX > $x || ($itemX === $x && $itemX === 0 && $itemY === 0)) {

                            $iconWidth = $DEFAULT_ICON_WIDTH; // default width of mapsummaryitem icon

                            if (!empty($item['size_x'])) {
                                $iconWidth = $item['size_x'];
                            }

                            $x = $itemX;

                        }
                    }
                }
            }
        }

        // if y does not fit the line height (140px)
        if ($y > 0 && $y % $LINE_HEIGHT !== 0) {
            $y += $LINE_HEIGHT - ($y % $LINE_HEIGHT); // calculate next line start
            $itemsPerLineCounter = $ITEMS_PER_LINE + 1; // force new line for next item to continue grid
            $lineHeightAlreadyIncreased = true;
        }

        if ($x > 0 || $mapHasItems) {
            $x += $WIDTH + ($iconWidth / 2); // add some space to the right
        }
        // if item is too far to the right, move it to the next line
        if ($itemsPerLineCounter >= $ITEMS_PER_LINE) {
            $x = 0; // reset x position to start
            if (!$lineHeightAlreadyIncreased) {
                $y += $LINE_HEIGHT; // add some space to the bottom
            }
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

        // add mapsummaryitem to the already generated maps to keep the state for calculating the position of the next item
        foreach ($this->allGeneratedMaps as $key => $generatedMap) {
            if ($generatedMap['id'] === $mapToAddItems["id"]) {
                $this->allGeneratedMaps[$key]['mapsummaryitems'][] = $mapsummaryitemEntity->toArray();
            }
        }

        return $mapsummaryitemEntity->toArray();

    }

    /**
     * convert the generated maps and items to an array to have a clean output besides error output
     *
     * @return array|array[]
     */
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
