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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace MapModule\Controller;

use App\itnovum\openITCOCKPIT\Core\Permissions\MapContainersPermissions;
use App\Model\Table\ContainersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\MapFilter;
use MapModule\Model\Entity\Map;
use MapModule\Model\Table\MapsTable;

/**
 * Class MapsController
 * @package MapModule\Controller
 */
class MapsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $MapFilter = new MapFilter($this->request);

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $MapFilter->getPage());
        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $all_maps = $MapsTable->getMapsIndex($MapFilter, $PaginateOMat, $MY_RIGHTS);

        $maps = [];
        foreach ($all_maps as $key => $all_map) {
            /** @var Map $all_map */
            $map = $all_map->toArray();
            $map['allowEdit'] = false;
            $map['allowCopy'] = false;
            if ($this->hasRootPrivileges === true) {
                $map['allowEdit'] = true;
                $map['allowCopy'] = true;
                $maps[] = $map;
                continue;
            }

            foreach ($all_map->getContainerIds() as $containerId) {
                if (isset($this->MY_RIGHTS_LEVEL[$containerId]) && $this->MY_RIGHTS_LEVEL[$containerId] == WRITE_RIGHT) {
                    $map['allowEdit'] = true;
                    $map['allowCopy'] = true;
                    continue;
                }
            }

            $maps[] = $map;
        }
        $this->set('all_maps', $maps);
        $this->viewBuilder()->setOption('serialize', ['all_maps']);
    }


    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $data = $this->request->getData();
        if (($this->request->is('post') || $this->request->is('put')) && isset($data['Map'])) {
            //$data['Container'] = $data['Map']['container_id'];

            if (empty($data['Map']['refresh_interval'])) {
                $data['Map']['refresh_interval'] = 90000;
            } else {
                if ($data['Map']['refresh_interval'] < 5) {
                    $data['Map']['refresh_interval'] = 5;
                }

                $data['Map']['refresh_interval'] = ((int)$data['Map']['refresh_interval'] * 1000);
            }

            /** @var MapsTable $MapsTable */
            $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

            $map = $MapsTable->newEmptyEntity();
            $map = $MapsTable->patchEntity($map, $data['Map']);

            $MapsTable->save($map);
            if ($map->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $map->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($map); // REST API ID serialization
                    return;
                }
            }
            $this->set('map', $map);
            $this->viewBuilder()->setOption('serialize', ['map']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest() && $id === null) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        if (!$MapsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid map'));
        }

        $map = $MapsTable->getMapForEdit($id);
        if (!$this->allowedByContainerId($map['Map']['containers']['_ids'])) {
            $this->render403();
            return;
        }

        if ($this->hasRootPrivileges === false) {
            if (empty(array_intersect($map['Map']['containers']['_ids'], $this->getWriteContainers()))) {
                $this->render403();
            }
        }

        $requiredContainers = $MapsTable->getRequiredContainerIdsForMap(
            intval($id),
            $map['Map']['containers']['_ids']
        );


        $MapContainersPermissions = new MapContainersPermissions(
            $map['Map']['containers']['_ids'],
            $this->getWriteContainers(),
            $this->hasRootPrivileges
        );

        if ($this->request->is('get')) {
            $this->set('areContainersChangeable', $MapContainersPermissions->areContainersChangeable());
            $this->set('requiredContainers', $requiredContainers);
            $this->viewBuilder()->setOption('serialize', ['map', 'areContainersChangeable', 'requiredContainers']);
            $this->set('map', $map);
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('Map', []);
            if (empty($data['refresh_interval'])) {
                $data['refresh_interval'] = 90000;
            } else {
                if ($data['refresh_interval'] < 5) {
                    $data['refresh_interval'] = 5;
                }

                $data['refresh_interval'] = ((int)$data['refresh_interval'] * 1000);
            }

            if ($MapContainersPermissions->areContainersChangeable() === false) {
                //Overwrite post data. User is not permitted to change container ids!
                $data['Map']['containers']['_ids'] = $map['Map']['containers']['_ids'];
            }
            if (!empty($requiredContainers)) {
                //autofill required containers
                foreach ($requiredContainers as $requiredContainerId) {
                    $data['Map']['containers']['_ids'][] = $requiredContainerId;
                }
            }


            $map = $MapsTable->get($id);
            $map->setAccess('id', false);
            $map = $MapsTable->patchEntity($map, $data);

            $MapsTable->save($map);
            if ($map->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $map->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($map); // REST API ID serialization
                    return;
                }
            }
            $this->set('map', $map);
            $this->viewBuilder()->setOption('serialize', ['map']);
        }
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        if (!$MapsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Map'));
        }

        /** @var Map $map */
        $map = $MapsTable->find()
            ->contain(['Containers'])
            ->where([
                'Maps.id' => $id
            ])
            ->first();

        if (!$this->allowedByContainerId($map->getContainerIds())) {
            $this->render403();
            return;
        }

        if ($MapsTable->delete($map)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /**
     * @param null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        if ($this->request->is('get')) {
            $MY_RIGHTS = [];
            if (!$this->hasRootPrivileges) {
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $maps = $MapsTable->getMapsForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('maps', $maps);
            $this->viewBuilder()->setOption('serialize', ['maps']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $Cache = new KeyValueStore();

            $postData = $this->request->getData('data');

            foreach ($postData as $index => $mapData) {
                if (!isset($mapData['Map']['id'])) {
                    //Create/clone map
                    $sourceMapId = $mapData['Source']['id'];
                    if (!$Cache->has($sourceMapId)) {
                        $sourceMapEntity = $MapsTable->get($sourceMapId, contain: [
                            'Containers',
                            'Mapgadgets',
                            'Mapicons',
                            'Mapitems',
                            'Maplines',
                            'Maptexts',
                            'Mapsummaryitems'
                        ]);

                        $sourceMap = $sourceMapEntity->toArray();
                        $sourceMap['containers'] = [
                            '_ids' => $sourceMapEntity->getContainerIds()
                        ];
                        unset($sourceMapEntity);

                        $Cache->set($sourceMap['id'], $sourceMap);
                    }

                    $sourceMap = $Cache->get($sourceMapId);

                    $newMapData = [
                        'name'             => $mapData['Map']['name'],
                        'title'            => $mapData['Map']['title'],
                        'refresh_interval' => $mapData['Map']['refresh_interval'] * 1000,
                        'background'       => $sourceMap['background'],
                        'containers'       => $sourceMap['containers'],
                        'mapgadgets'       => $sourceMap['mapgadgets'],
                        'mapicons'         => $sourceMap['mapicons'],
                        'mapitems'         => $sourceMap['mapitems'],
                        'maplines'         => $sourceMap['maplines'],
                        'maptexts'         => $sourceMap['maptexts'],
                        'mapsummaryitems'  => $sourceMap['mapsummaryitems']
                    ];

                    //$newMapEntity = $MapsTable->newEntity($newMapData);
                }

                $action = 'copy';
                if (isset($mapData['Map']['id'])) {
                    //Update existing map
                    //This happens, if a user copy multiple maps, and one run into an validation error
                    //All maps without validation errors got already saved to the database
                    $newMapEntity = $MapsTable->get($mapData['Map']['id'], contain: [
                        'Containers'
                    ]);
                    $mapData['Map']['containers']['_ids'] = $newMapEntity->getContainerIds();
                    if (isset($mapData['Map']['refresh_interval'])) {
                        $mapData['Map']['refresh_interval'] = $mapData['Map']['refresh_interval'] * 1000;
                    }

                    $newMapEntity->setAccess('*', false);
                    $newMapEntity->setAccess(['name', 'title', 'refresh_interval'], true);
                    $newMapEntity = $MapsTable->patchEntity($newMapEntity, $mapData['Map']);
                    $action = 'edit';
                } else {
                    $models = ['mapgadgets', 'mapicons', 'mapitems', 'maplines', 'maptexts', 'mapsummaryitems'];
                    $keysToDelete = ['id', 'map_id', '_joinData'];
                    foreach ($models as $modelName) {
                        foreach ($newMapData[$modelName] as $newMapDataIndex => $record) {
                            foreach ($record as $recordIndex => $value) {
                                if (in_array($recordIndex, $keysToDelete, true)) {
                                    unset($newMapData[$modelName][$newMapDataIndex][$recordIndex]);
                                }
                            }
                        }
                    }

                    $newMapEntity = $MapsTable->newEntity($newMapData);
                }
                //debug($newMapEntity);
                //die();
                $MapsTable->save($newMapEntity);

                $postData[$index]['Error'] = [];
                if ($newMapEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newMapEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Map']['id'] = $newMapEntity->get('id');
                }
            }
        }

        if ($hasErrors) {
            $this->response = $this->response->withStatus(400);
        }
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, CT_TENANT, [], $this->hasRootPrivileges);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), CT_TENANT, [], true);
        }
        $containers = Api::makeItJavaScriptAble($containers);


        $this->set('containers', $containers);
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }
}
