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

namespace itnovum\openITCOCKPIT\Maps;

use itnovum\openITCOCKPIT\Maps\ValueObjects\Map;
use itnovum\openITCOCKPIT\Maps\ValueObjects\Mapgadget;
use itnovum\openITCOCKPIT\Maps\ValueObjects\Mapicon;
use itnovum\openITCOCKPIT\Maps\ValueObjects\Mapitem;
use itnovum\openITCOCKPIT\Maps\ValueObjects\Mapline;
use itnovum\openITCOCKPIT\Maps\ValueObjects\Mapsummaryitem;
use itnovum\openITCOCKPIT\Maps\ValueObjects\Maptext;

class MapForAngular {

    /**
     * @var array
     */
    private $map;

    /**
     * @var array
     */
    private $layers = [];

    /**
     * @var int|null
     */
    private $max_z_index = null;

    /**
     * @var array
     */
    private $mapAsArray = [];


    /**
     * MapForAngular constructor.
     * @param array $map
     */
    public function __construct($map) {
        $this->map = $map;
        $this->mapAsArray = $this->convertToArray();
    }

    /**
     * @return array
     */
    private function convertToArray() {
        $tmpMap = new Map($this->map);

        $map = [
            'Map' => $tmpMap->toArray()
        ];
        unset($tmpMap);

        if (isset($this->map['mapitems'])) {
            foreach ($this->map['mapitems'] as $mapitem) {
                $item = new Mapitem($mapitem);
                $this->layers[$item->getZIndex()] = sprintf('Layer %s', $item->getZIndex());
                $map['Mapitems'][] = $item->toArray();
            }
        }

        if (isset($this->map['maplines'])) {
            foreach ($this->map['maplines'] as $mapline) {
                $line = new Mapline($mapline);
                $this->layers[$line->getZIndex()] = sprintf('Layer %s', $line->getZIndex());
                $map['Maplines'][] = $line->toArray();
            }
        }

        if (isset($this->map['mapgadgets'])) {
            foreach ($this->map['mapgadgets'] as $mapgadget) {
                $gadget = new Mapgadget($mapgadget);
                $this->layers[$gadget->getZIndex()] = sprintf('Layer %s', $gadget->getZIndex());
                $map['Mapgadgets'][] = $gadget->toArray();
            }
        }

        if (isset($this->map['mapicons'])) {
            foreach ($this->map['mapicons'] as $mapicon) {
                $icon = new Mapicon($mapicon);
                $this->layers[$icon->getZIndex()] = sprintf('Layer %s', $icon->getZIndex());
                $map['Mapicons'][] = $icon->toArray();
            }
        }

        if (isset($this->map['maptexts'])) {
            foreach ($this->map['maptexts'] as $maptext) {
                $text = new Maptext($maptext);
                $this->layers[$text->getZIndex()] = sprintf('Layer %s', $text->getZIndex());
                $map['Maptexts'][] = $text->toArray();
            }
        }

        if (isset($this->map['mapsummaryitems'])) {
            foreach ($this->map['mapsummaryitems'] as $mapsummaryitem) {
                $summaryitem = new Mapsummaryitem($mapsummaryitem);
                $this->layers[$summaryitem->getZIndex()] = sprintf('Layer %s', $summaryitem->getZIndex());
                $map['Mapsummaryitems'][] = $summaryitem->toArray();
            }
        }

        if (isset($this->map['Containers'])) {
            $map['Containers'] = $this->map['Containers'];
        }

        if (empty($this->layers)) {
            $this->layers[0] = sprintf('Layer %s', 0);
        }

        $this->max_z_index = (int)max(array_keys($this->layers));

        return $map;
    }

    /**
     * @return array
     */
    public function toArray() {
        if (empty($this->mapAsArray)) {
            $this->mapAsArray = $this->convertToArray();
        }

        return $this->mapAsArray;
    }

    /**
     * @return array
     */
    public function getLayers() {
        if (empty($this->layers)) {
            $this->convertToArray();
        }
        return $this->layers;
    }

    /**
     * @return int
     */
    public function getMaxZIndex() {
        if ($this->max_z_index === null) {
            $this->convertToArray();
        }
        return $this->max_z_index;
    }

}
