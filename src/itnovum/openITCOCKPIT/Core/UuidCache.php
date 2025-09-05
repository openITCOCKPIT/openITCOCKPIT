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

namespace App\itnovum\openITCOCKPIT\Core;


use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\UUID;

class UuidCache {

    /**
     * @var array
     */
    private $cache = [];

    public function __construct() {
    }

    public function buildCache() {
        $tablesToCache = [
            'Hosts',
            'Hosttemplates',
            'Timeperiods',
            'Commands',
            'Contacts',
            'Contactgroups',
            'Hostgroups',
            'Servicegroups',
            'Services',
            'Servicetemplates',
            //'Hostescalations', //Do not have a name
            //'Serviceescalations', //Do not have a name
            //'Hostdependencies', //Do not have a name
            //'Servicedependencies' //Do not have a name
        ];

        foreach ($tablesToCache as $TableName) {
            /** @var Table $Table */
            $Table = TableRegistry::getTableLocator()->get($TableName);

            switch ($TableName) {
                case 'Services':
                    $query = $Table->find();
                    $result = $query->select([
                        'Services.id',
                        'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
                        'Services.uuid'
                    ])->where()
                        ->innerJoinWith('Servicetemplates')
                        ->disableHydration()
                        ->all();

                    foreach ($result->toArray() as $service) {
                        $service['name'] = $service['servicename'];
                        unset($service['servicename']);
                        $this->cache[$TableName][$service['uuid']] = $service;
                    }
                    unset($result);
                    break;

                case 'Contactgroups':
                case 'Hostgroups':
                case 'Servicegroups':
                    $query = $Table->find();
                    $result = $query->select([
                        'id',
                        'uuid'
                    ])->where()
                        ->contain([
                            'Containers' => function (Query $query) {
                                return $query
                                    ->disableAutoFields()
                                    ->select([
                                        'name'
                                    ]);
                            }
                        ])
                        ->disableHydration()
                        ->all();

                    foreach ($result->toArray() as $containerObj) {
                        $this->cache[$TableName][$containerObj['uuid']] = [
                            'id'   => $containerObj['id'],
                            'name' => $containerObj['container']['name'],
                            'uuid' => $containerObj['uuid']
                        ];
                    }
                    unset($result);

                    break;

                default:
                    $result = $Table->find()
                        ->select([
                            'id',
                            'name',
                            'uuid'
                        ])
                        ->disableHydration()
                        ->all();

                    foreach ($result->toArray() as $obj) {
                        $this->cache[$TableName][$obj['uuid']] = $obj;
                    }
                    unset($result);
                    break;
            }
        }
    }

    /**
     * @param string $str
     * @return string|string[]|null
     */
    public function replaceUuidWithAngularJsLink($str) {
        $outputStr = preg_replace_callback(UUID::regex(), function ($matches) {
            foreach ($matches as $uuid) {
                $object = null;
                foreach (array_keys($this->cache) as $ObjectName) {
                    if (isset($this->cache[$ObjectName][$uuid])) {
                        $object = $this->cache[$ObjectName][$uuid];
                        break;
                    }
                }

                if ($object) {
                    return sprintf(
                        '<a class="bold" ui-sref="%sIndex({id:%s})">%s</a>',
                        $ObjectName,
                        $object['id'],
                        h($object['name'])
                    );
                } else {
                    return '<i>[' . $uuid . ']</i>';
                }
            }
            return '';
        }, $str);
        return $outputStr;
    }

    /**
     * @param $str
     * @param $permissions
     * @return array|string|string[]|null
     */
    public function replaceUuidWithAngularLink($str, $permissions) {

        $outputStr = preg_replace_callback(UUID::regex(), function ($matches) use ($permissions) {
            foreach ($matches as $uuid) {
                $object = null;
                foreach (array_keys($this->cache) as $ObjectName) {
                    if (isset($this->cache[$ObjectName][$uuid])) {
                        $object = $this->cache[$ObjectName][$uuid];
                        break;
                    }
                }

                if ($object) {
                    $objectType = strtolower($ObjectName);
                    if (isset($permissions[$objectType]['index'])) {
                        return sprintf('<a rel="ng" href="/%s/index?id=%s"><b>%s</b></a>',
                            $objectType,
                            $object['id'],
                            h($object['name']));

                    } else {
                        return sprintf('<b>%s</b>', h($object['name']));

                    }
                } else {
                    return '<i>[' . $uuid . ']</i>';
                }
            }
            return '';
        }, $str);
        return $outputStr;
    }
}
