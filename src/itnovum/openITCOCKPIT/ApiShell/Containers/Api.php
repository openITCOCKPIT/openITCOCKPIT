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

namespace itnovum\openITCOCKPIT\ApiShell\Containers;

use itnovum\openITCOCKPIT\ApiShell\CoreApi;
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
            case 'update_container_type':
                $this->update_container_type();
                break;
        }
    }

    /**
     * @throws \Exception
     */
    public function update_container_type() {
        $records = $this->getRecordsByTypeId();
        foreach ($records as $record) {
            $record['Containers']['containertype_id'] = CT_NODE;
            if (!$this->Database->save($record)) {
                throw new \Exception('Could not save data');
            }
        }
    }


    public function getRecordsByTypeId() {
        return $this->Database->find('all', [
            'conditions' => [
                'containertype_id' => CT_DEVICEGROUP,
            ],
        ]);
    }

}
