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

namespace itnovum\openITCOCKPIT\ApiShell\Commands;

use itnovum\openITCOCKPIT\ApiShell\CoreApi;
use itnovum\openITCOCKPIT\ApiShell\Exceptions\RecordExistsExceptions;
use itnovum\openITCOCKPIT\ApiShell\Interfaces\ApiInterface;
use itnovum\openITCOCKPIT\ApiShell\OptionParser;
use itnovum\openITCOCKPIT\Core\UUID;

class Api extends CoreApi implements ApiInterface {

    /**
     * @var OptionParser
     */
    private $optionParser;

    /**
     * @var array
     */
    private $data;

    public function __construct($cake, $modelName) {
        parent::__construct($cake, $modelName);
    }

    public function setOptionsFromOptionParser(OptionParser $optionParser) {
        $this->optionParser = $optionParser;
        $this->data = $optionParser->getData();
    }

    public function dispatchRequest() {
        switch ($this->optionParser->getAction()) {
            case 'add':
                $this->add();
                break;
            case 'update':
                $this->update();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'addbyuuid':
                $this->addByUuid();
        }
    }

    /**
     * @throws RecordExistsExceptions
     * @throws \Exception
     */
    public function add() {
        if (!$this->exists()) {
            $data = [
                'name'         => $this->getNameOfData(),
                'command_line' => $this->data[1],
                'command_type' => $this->data[2],
                'uuid'         => UUID::v4(),
                'description'  => $this->data[3],
            ];
            if ($this->Database->save($data)) {
                return true;
            }
            throw new \Exception('Could not save data');
        }
        throw new RecordExistsExceptions('Record already exists');
    }

    /**
     * @throws \Exception
     */
    public function update() {
        if (!$this->exists()) {
            $this->add();
        }
        $record = $this->getRecordByName();
        $record['Commands']['name'] = $this->getNameOfData();
        $record['Commands']['command_line'] = $this->data[1];
        $record['Commands']['command_type'] = $this->data[2];
        $record['Commands']['uuid'] = $this->data[3];
        $record['Commands']['description'] = $this->data[4];

        if ($this->Database->save($record)) {
            return true;
        }
        throw new \Exception('Could not save data');
    }

    /**
     * @throws RecordExistsExceptions
     * @throws \Exception
     */
    public function delete() {
        if (!$this->exists()) {
            throw new RecordExistsExceptions('Record does not exists!');
        }
        $record = $this->getRecordByName();
        if ($this->Database->delete($record['Commands']['id'])) {
            return true;
        }
        throw new \Exception('Could not delete data');
    }

    /**
     * @throws RecordExistsExceptions
     * @throws \Exception
     */
    public function addByUuid() {
        if (!$this->existsByUuid()) {
            $data = [
                'name'         => $this->getNameOfData(),
                'command_line' => $this->data[1],
                'command_type' => $this->data[2],
                'uuid'         => $this->data[3],
                'description'  => $this->data[4],
            ];
            if ($this->Database->save($data)) {
                return true;
            }
            throw new \Exception('Could not save data');
        }
        throw new RecordExistsExceptions('Record already exists');
    }

    /**
     * Checks if a record for given key exists
     * @return bool
     */
    public function exists() {
        $result = $this->getRecordByName();

        return !empty($result);
    }


    /**
     * Checks if a record for given uuid exists
     * @return bool
     */
    public function existsByUuid() {
        $result = $this->getRecordByUuid();

        return !empty($result);
    }

    /**
     * @return string
     */
    public function getNameOfData() {
        $data = $this->data;
        $name = array_shift($data);

        return $name;
    }

    public function getRecordByName() {
        $name = $this->getNameOfData();

        return $this->Database->find('first', [
            'conditions' => [
                'name' => $name,
            ],
        ]);
    }

    public function getRecordByUuid() {
        $uuid = $this->data[3];

        return $this->Database->find('first', [
            'conditions' => [
                'uuid' => $uuid,
            ],
        ]);
    }

}
