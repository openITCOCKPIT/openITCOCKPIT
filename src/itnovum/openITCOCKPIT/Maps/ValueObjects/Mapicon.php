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


class Mapicon {

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
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $z_index;

    /**
     * @var bool
     * Required for frontend to show and hide layers
     * Backend will not use this variable but it is an easy way to get it into the json
     */
    private $display = true;

    /**
     * Mapicon constructor.
     * @param array $mapicon
     */
    public function __construct($mapicon) {

        if (isset($mapicon['id'])) {
            $this->id = (int)$mapicon['id'];
        }

        if (isset($mapicon['map_id'])) {
            $this->map_id = (int)$mapicon['map_id'];
        }

        if (isset($mapicon['x'])) {
            $this->x = (int)$mapicon['x'];
        }

        if (isset($mapicon['y'])) {
            $this->y = (int)$mapicon['y'];
        }

        if (isset($mapicon['icon'])) {
            $this->icon = $mapicon['icon'];
        }

        if (isset($mapicon['z_index'])) {
            //z_index needs to be a string :(
            $this->z_index = (string)$mapicon['z_index'];
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
     * @return string
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getZIndex() {
        return $this->z_index;
    }


    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
