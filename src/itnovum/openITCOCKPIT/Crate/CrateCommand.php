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

namespace itnovum\openITCOCKPIT\Crate;


class CrateCommand implements CrateValueObject {

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
    private $uuid;

    /**
     * @var int
     */
    private $commandType;

    /**
     * CrateCommand constructor.
     * @param $id
     */
    public function __construct($id) {
        $this->id = (int)$id;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid) {
        $this->uuid = $uuid;
    }

    /**
     * @param int $commandType
     */
    public function setCommandType($commandType) {
        $this->commandType = (int)$commandType;
    }

    /**
     * @param array $command
     */
    public function setDataFromFindResult($command) {
        $this->setName($command['Command']['name']);
        $this->setUuid($command['Command']['uuid']);
        $this->setCommandType($command['Command']['command_type']);
    }

    /**
     * @return array
     */
    public function getFindQuery() {
        return [
            'recursive'  => -1,
            'fields'     => [
                'Command.id',
                'Command.name',
                'Command.uuid',
                'Command.command_type'
            ],
            'conditions' => [
                'Command.id'           => $this->id,
                'Command.command_type' => NOTIFICATION_COMMAND
            ]
        ];
    }

    public function getDataForSave() {
        return [
            'CrateCommand' => [
                'id'           => $this->id,
                'uuid'         => $this->uuid,
                'name'         => $this->name,
                'command_type' => (int)$this->commandType
            ]
        ];
    }


}
