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

namespace itnovum\openITCOCKPIT\ApiShell\Systemsettings;

use itnovum\openITCOCKPIT\ApiShell\CoreApi;
use itnovum\openITCOCKPIT\ApiShell\Exceptions\RecordExistsExceptions;
use itnovum\openITCOCKPIT\ApiShell\Interfaces\ApiInterface;
use itnovum\openITCOCKPIT\ApiShell\OptionParser;

class Api extends CoreApi implements ApiInterface {

    /**
     * @var OptionParser
     */
    private $optionParser;

    /**
     * @var array
     */
    private $data;

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
        }
    }

    /**
     * @throws RecordExistsExceptions
     * @throws \Exception
     */
    public function add() {
        if (!$this->exists()) {
            $data = [
                'key'     => $this->getKeyOfData(),
                'value'   => $this->data[1],
                'info'    => $this->data[2],
                'section' => $this->data[3],
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
        $record = $this->getRecordByKey();
        $record['Systemsettings']['key'] = $this->getKeyOfData();
        $record['Systemsettings']['value'] = $this->data[1];
        $record['Systemsettings']['info'] = $this->data[2];
        $record['Systemsettings']['section'] = $this->data[3];

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
        $record = $this->getRecordByKey();
        if ($this->Database->delete($record['Systemsettings']['id'])) {
            return true;
        }
        throw new \Exception('Could not delete data');
    }

    /**
     * Checks if a record for given key exists
     * @return bool
     */
    public function exists() {
        $result = $this->getRecordByKey();

        return !empty($result);
    }

    /**
     * @return string
     */
    public function getKeyOfData() {
        $data = $this->data;
        $key = array_shift($data);

        return $key;
    }

    public function getRecordByKey() {
        $key = $this->getKeyOfData();

        return $this->Database->find('first', [
            'conditions' => [
                'key' => $key,
            ],
        ]);
    }

}
