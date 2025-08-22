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

namespace itnovum\openITCOCKPIT\Maps\ValueObjects;


class Mapgadget {

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $map_id;

    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;

    /**
     * @var int
     */
    private $size_x;

    /**
     * @var int
     */
    private $size_y;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var string
     */
    private $gadget;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $object_id;

    /**
     * @var bool
     */
    private $transparent_background;

    /**
     * @var bool
     */
    private $show_label;

    /**
     * @var int
     */
    private $font_size;

    /**
     * @var string
     */
    private $z_index;

    /**
     * @var null|string
     */
    private $metric;

    /**
     * @var null|string
     */
    private $output_type;

    /**
     * @var bool
     * Required for frontend to show and hide layers
     * Backend will not use this variable but it is an easy way to get it into the json
     */
    private $display = true;


    /**
     * @var int
     */
    private $label_possition;

    /**
     * Mapitem constructor.
     * @param array $mapgadget
     */
    public function __construct($mapgadget) {

        if (isset($mapgadget['id'])) {
            $this->id = (int)$mapgadget['id'];
        }

        if (isset($mapgadget['map_id'])) {
            $this->map_id = (int)$mapgadget['map_id'];
        }

        if (isset($mapgadget['x'])) {
            $this->x = (int)$mapgadget['x'];
        }

        if (isset($mapgadget['y'])) {
            $this->y = (int)$mapgadget['y'];
        }

        if (isset($mapgadget['size_x'])) {
            $this->size_x = (int)$mapgadget['size_x'];
        }

        if (isset($mapgadget['size_y'])) {
            $this->size_y = (int)$mapgadget['size_y'];
        }

        if (isset($mapgadget['limit'])) {
            $this->limit = (int)$mapgadget['limit'];
        }

        if (isset($mapgadget['gadget'])) {
            $this->gadget = $mapgadget['gadget'];
        }

        if (isset($mapgadget['type'])) {
            $this->type = $mapgadget['type'];
        }

        if (isset($mapgadget['object_id'])) {
            $this->object_id = (int)$mapgadget['object_id'];
        }

        if (isset($mapgadget['transparent_background'])) {
            $this->transparent_background = (bool)$mapgadget['transparent_background'];
        }

        if (isset($mapgadget['show_label'])) {
            $this->show_label = (bool)$mapgadget['show_label'];
        }

        if (isset($mapgadget['font_size'])) {
            $this->font_size = (int)$mapgadget['font_size'];
            if ($this->font_size === 0) {
                $this->font_size = 13;
            }
        }

        if (isset($mapgadget['z_index'])) {
            //z_index needs to be a string :(
            $this->z_index = (string)$mapgadget['z_index'];
        }

        if (isset($mapgadget['metric'])) {
            $this->metric = $mapgadget['metric'];
        }

        if (isset($mapgadget['output_type'])) {
            $this->output_type = $mapgadget['output_type'];
        }


    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMapId() {
        return $this->map_id;
    }

    /**
     * @return int
     */
    public function getX() {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY() {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getSizeX() {
        return $this->size_x;
    }

    /**
     * @return int
     */
    public function getSizeY() {
        return $this->size_y;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function getGadget() {
        return $this->gadget;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getObjectId() {
        return $this->object_id;
    }

    /**
     * @return bool
     */
    public function isTransparentBackground() {
        return $this->transparent_background;
    }

    /**
     * @return bool
     */
    public function isShowLabel() {
        return $this->show_label;
    }

    /**
     * @return int
     */
    public function getFontSize() {
        return $this->font_size;
    }

    /**
     * @return string
     */
    public function getZIndex() {
        return $this->z_index;
    }

    /**
     * @return int
     */
    public function getLabelPossition() {
        return $this->label_possition;
    }

    /**
     * @return null|string
     */
    public function getMetric() {
        return $this->metric;
    }

    /**
     * @return string|null
     */
    public function getOutputType() {
        return $this->output_type;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
