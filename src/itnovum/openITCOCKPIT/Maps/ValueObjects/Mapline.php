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


class Mapline {

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
    private $startX;

    /**
     * @var int
     */
    private $startY;

    /**
     * @var int
     */
    private $endX;

    /**
     * @var int
     */
    private $endY;

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
     * @var bool
     * Required for frontend to show and hide layers
     * Backend will not use this variable but it is an easy way to get it into the json
     */
    private $display = true;


    /**
     * Mapitem constructor.
     * @param array $mapline
     */
    public function __construct($mapline) {

        if (isset($mapline['id'])) {
            $this->id = (int)$mapline['id'];
        }

        if (isset($mapline['map_id'])) {
            $this->map_id = (int)$mapline['map_id'];
        }

        if (isset($mapline['startX'])) {
            $this->startX = (int)$mapline['startX'];
        }

        if (isset($mapline['startY'])) {
            $this->startY = (int)$mapline['startY'];
        }

        if (isset($mapline['endX'])) {
            $this->endX = (int)$mapline['endX'];
        }

        if (isset($mapline['endY'])) {
            $this->endY = (int)$mapline['endY'];
        }

        if (isset($mapline['limit'])) {
            $this->limit = (int)$mapline['limit'];
        }

        if (isset($mapline['iconset'])) {
            $this->iconset = $mapline['iconset'];
        }

        if (isset($mapline['type'])) {
            $this->type = $mapline['type'];
        }

        if (isset($mapline['object_id'])) {
            $this->object_id = (int)$mapline['object_id'];
        }

        if (isset($mapline['z_index'])) {
            //z_index needs to be a string :(
            $this->z_index = (string)$mapline['z_index'];
        }

        if (isset($mapline['show_label'])) {
            $this->show_label = (bool)$mapline['show_label'];
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
    public function getStartX() {
        return $this->startX;
    }

    /**
     * @return int
     */
    public function getStartY() {
        return $this->startY;
    }

    /**
     * @return int
     */
    public function getEndX() {
        return $this->endX;
    }

    /**
     * @return int
     */
    public function getEndY() {
        return $this->endY;
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
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
