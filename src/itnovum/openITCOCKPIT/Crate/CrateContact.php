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


class CrateContact implements CrateValueObject {

    /**
     * @var id
     */
    private $id;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $name;

    /**
     * CrateContact constructor.
     * @param int $id
     */
    public function __construct($id) {
        $this->id = (int)$id;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid) {
        $this->uuid = $uuid;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param array $contact
     */
    public function setDataFromFindResult($contact) {
        $this->setName($contact['Contact']['name']);
        $this->setUuid($contact['Contact']['uuid']);
    }

    /**
     * @return array
     */
    public function getFindQuery() {
        return [
            'recursive'  => -1,
            'conditions' => [
                'Contact.id' => $this->id
            ],
            'fields'     => [
                'Contact.id',
                'Contact.uuid',
                'Contact.name'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getDataForSave() {
        return [
            'CrateContact' => [
                'id'   => $this->id,
                'uuid' => $this->uuid,
                'name' => $this->name
            ]
        ];
    }

}
