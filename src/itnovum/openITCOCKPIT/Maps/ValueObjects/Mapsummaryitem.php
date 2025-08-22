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


class Mapsummaryitem {

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
     * @param array $mapsummaryitem
     */
    public function __construct($mapsummaryitem) {

        if (isset($mapsummaryitem['id'])) {
            $this->id = (int)$mapsummaryitem['id'];
        }

        if (isset($mapsummaryitem['map_id'])) {
            $this->map_id = (int)$mapsummaryitem['map_id'];
        }

        if (isset($mapsummaryitem['x'])) {
            $this->x = (int)$mapsummaryitem['x'];
        }

        if (isset($mapsummaryitem['y'])) {
            $this->y = (int)$mapsummaryitem['y'];
        }

        if (isset($mapsummaryitem['size_x'])) {
            $this->size_x = (int)$mapsummaryitem['size_x'];
        }

        if (isset($mapsummaryitem['size_y'])) {
            $this->size_y = (int)$mapsummaryitem['size_y'];
        }

        if (isset($mapsummaryitem['limit'])) {
            $this->limit = (int)$mapsummaryitem['limit'];
        }

        if (isset($mapsummaryitem['type'])) {
            $this->type = $mapsummaryitem['type'];
        }

        if (isset($mapsummaryitem['object_id'])) {
            $this->object_id = (int)$mapsummaryitem['object_id'];
        }

        if (isset($mapsummaryitem['z_index'])) {
            //z_index needs to be a string :(
            $this->z_index = (string)$mapsummaryitem['z_index'];
        }

        if (isset($mapsummaryitem['show_label'])) {
            $this->show_label = (bool)$mapsummaryitem['show_label'];
        }

        if (isset($mapsummaryitem['label_possition'])) {
            $this->label_possition = (int)$mapsummaryitem['label_possition'];
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
