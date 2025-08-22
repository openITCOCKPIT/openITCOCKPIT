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

namespace itnovum\openITCOCKPIT\Satellite;


class MappingRepository {

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @param $uuid
     * @param $name
     * @param $objectType
     */
    public function addItem($uuid, $name, $objectType) {
        $this->mapping[$objectType][$uuid] = $name;
    }

    /**
     * @param $id
     * @param string $uuid
     * @param string $name
     * @param string|null $description
     * @param array $members
     */
    public function addHostgroup($id, string $uuid, string $name, ?string $description, array $members) {
        if (!isset($this->mapping['hostgroups'])) {
            $this->mapping['hostgroups'] = [];
        }

        if (empty($description)) {
            $description = '';
        }

        $this->mapping['hostgroups'][] = [
            'id'          => (int)$id,
            'uuid'        => $uuid,
            'name'        => $name,
            'description' => $description,
            'members'     => $members
        ];
    }

    /**
     * @param $id
     * @param string $uuid
     * @param string $name
     * @param string|null $description
     * @param array $members
     */
    public function addServicegroup($id, string $uuid, string $name, ?string $description, array $members) {
        if (!isset($this->mapping['servicegroups'])) {
            $this->mapping['servicegroups'] = [];
        }

        if (empty($description)) {
            $description = '';
        }

        $this->mapping['servicegroups'][] = [
            'id'          => (int)$id,
            'uuid'        => $uuid,
            'name'        => $name,
            'description' => $description,
            'members'     => $members
        ];
    }

    /**
     * @param string $path
     */
    public function toFile($path) {
        $file = fopen($path, 'w+');
        fwrite($file, json_encode($this->mapping));
        fclose($file);
    }

}
