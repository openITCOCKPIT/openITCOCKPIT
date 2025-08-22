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

namespace itnovum\openITCOCKPIT\Core\Views;


class Command {


    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $command_line;

    /**
     * @var int
     */
    private $command_type;

    /**
     * @var string
     */
    private $description;


    /**
     * Command constructor.
     * @param array $data
     */
    public function __construct($data) {
        if (isset($data['id'])) {
            $this->id = (int)$data['id'];
        }

        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['command_line'])) {
            $this->command_line = $data['command_line'];
        }

        if (isset($data['command_type'])) {
            $this->command_type = (int)$data['command_type'];
        }

        if (isset($data['description'])) {
            $this->description = $data['description'];
        }
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCommandLine() {
        return $this->command_line;
    }

    /**
     * @return int
     */
    public function getCommandType() {
        return $this->command_type;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        return $arr;
    }

}
