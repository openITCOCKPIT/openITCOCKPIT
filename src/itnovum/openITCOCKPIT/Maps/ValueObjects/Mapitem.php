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


class Mapitem {

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
    private $limit;

    /**
     * @var string
     */
    private $iconset;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $object_id;

    /**
     * @var string
     */
    private $z_index;

    /**
     * @var bool
     */
    private $show_label;

    /**
     * @var int
     */
    private $label_possition;

    /**
     * @var bool
     * Required for frontend to show and hide layers
     * Backend will not use this variable but it is an easy way to get it into the json
     */
    private $display = true;

    /**
     * Mapitem constructor.
     * @param array $mapitem
     */
    public function __construct($mapitem) {

        if (isset($mapitem['id'])) {
            $this->id = (int)$mapitem['id'];
        }

        if (isset($mapitem['map_id'])) {
            $this->map_id = (int)$mapitem['map_id'];
        }

        if (isset($mapitem['x'])) {
            $this->x = (int)$mapitem['x'];
        }

        if (isset($mapitem['y'])) {
            $this->y = (int)$mapitem['y'];
        }

        if (isset($mapitem['limit'])) {
            $this->limit = (int)$mapitem['limit'];
        }

        if (isset($mapitem['iconset'])) {
            $this->iconset = $mapitem['iconset'];
        }

        if (isset($mapitem['type'])) {
            $this->type = $mapitem['type'];
        }

        if (isset($mapitem['object_id'])) {
            $this->object_id = (int)$mapitem['object_id'];
        }

        if (isset($mapitem['z_index'])) {
            //z_index needs to be a string :(
            $this->z_index = (string)$mapitem['z_index'];
        }

        if (isset($mapitem['show_label'])) {
            $this->show_label = (bool)$mapitem['show_label'];
        }

        if (isset($mapitem['label_possition'])) {
            $this->label_possition = (int)$mapitem['label_possition'];
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
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function getIconset() {
        return $this->iconset;
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
     * @return string
     */
    public function getZIndex() {
        return $this->z_index;
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
    public function getLabelPossition() {
        return $this->label_possition;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
