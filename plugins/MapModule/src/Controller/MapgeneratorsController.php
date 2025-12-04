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

use App\itnovum\openITCOCKPIT\Core\Permissions\MapgeneratorContainersPermissions;
use App\itnovum\openITCOCKPIT\Filter\MapgeneratorFilter;
use App\itnovum\openITCOCKPIT\Maps\Mapgenerator;
use App\Model\Table\ContainersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Exception;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use MapModule\Model\Table\MapgeneratorsTable;
use MapModule\Model\Table\MapsTable;

class MapgeneratorsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            //Only ship template for AngularJs
            return;
        }

        /** @var MapgeneratorsTable $MapgeneratorsTable */
        $MapgeneratorsTable = TableRegistry::getTableLocator()->get('MapModule.Mapgenerators');

        $MapgeneratorFilter = new MapgeneratorFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $MapgeneratorFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $all_mapgenerators = $MapgeneratorsTable->getMapgeneratorsIndex($MapgeneratorFilter, $PaginateOMat, $MY_RIGHTS);


        foreach ($all_mapgenerators as $key => $mapgenerator) {
            $mapgenerator['maps'] = Hash::extract($mapgenerator, 'maps.{n}.id');
            $containerIds = Hash::extract($mapgenerator, 'containers.{n}.id');
            if ($mapgenerator['type'] == Mapgenerator::TYPE_GENERATE_BY_HOSTNAME_SPLITTING) {
                $all_mapgenerators[$key]['allowEdit'] = true;
            }
            if ($mapgenerator['type'] == Mapgenerator::TYPE_GENERATE_BY_CONTAINER_STRUCTURE) {
                $all_mapgenerators[$key]['allowEdit'] = true;
                if ($this->hasRootPrivileges === false && !empty($containerIds)) {
                    $all_mapgenerators[$key]['allowEdit'] = false;
                    if (!empty(array_intersect($containerIds, $this->getWriteContainers()))) {
                        $all_mapgenerators[$key]['allowEdit'] = true;
                    }
                }
            }
        }


        $this->set('all_mapgenerators', $all_mapgenerators);
        $this->viewBuilder()->setOption('serialize', ['all_mapgenerators', 'paging']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship template for AngularJs
            return;
        }

        if (($this->request->is('post') || $this->request->is('put')) && $this->isAngularJsRequest()) {
            $data = $this->request->getData(null, []);

            if (empty($data['Mapgenerator']['map_refresh_interval'])) {
                $data['Mapgenerator']['map_refresh_interval'] = 90000;
            } else {
                if ($data['Mapgenerator']['map_refresh_interval'] < 5) {
                    $data['Mapgenerator']['map_refresh_interval'] = 5;
                }

                $data['Mapgenerator']['map_refresh_interval'] = ((int)$data['Mapgenerator']['map_refresh_interval'] * 1000);
            }

            if ($data['Mapgenerator']['type'] === Mapgenerator::TYPE_GENERATE_BY_CONTAINER_STRUCTURE) {
                unset($data['Mapgenerator']['mapgenerator_levels']);
            }

            /** @var MapgeneratorsTable $MapgeneratorsTable */
            $MapgeneratorsTable = TableRegistry::getTableLocator()->get('MapModule.Mapgenerators');

            $mapgeneratorsEntity = $MapgeneratorsTable->newEmptyEntity();
            $mapgeneratorsEntity = $MapgeneratorsTable->patchEntity($mapgeneratorsEntity, $data['Mapgenerator']);
            $MapgeneratorsTable->checkRules($mapgeneratorsEntity);

            $MapgeneratorsTable->save($mapgeneratorsEntity);
            if (!$mapgeneratorsEntity->hasErrors()) {
                $this->serializeCake4Id($mapgeneratorsEntity);
            } else {
                $this->serializeCake4ErrorMessage($mapgeneratorsEntity);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, CT_TENANT, [], $this->hasRootPrivileges, [CT_GLOBAL, CT_RESOURCEGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), CT_TENANT, [], $this->hasRootPrivileges, [CT_GLOBAL, CT_RESOURCEGROUP]);
        }

        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    public function edit($id = null): void {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var MapgeneratorsTable $MapgeneratorsTable */
        $MapgeneratorsTable = TableRegistry::getTableLocator()->get('MapModule.Mapgenerators');

        if (!$MapgeneratorsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Map generator'));
        }

        $mapgenerator = $MapgeneratorsTable->get($id, contain: [
            'Containers',
            'MapgeneratorLevels'
        ]);

        $containerIds = Hash::extract($mapgenerator, 'containers.{n}.id');

        if (!empty($containerIds) && !$this->allowedByContainerId($containerIds)) {
            $this->render403();
            return;
        }

        if ($this->hasRootPrivileges === false) {
            if (!empty($containerIds) && empty(array_intersect($containerIds, $this->getWriteContainers()))) {
                $this->render403();
            }
        }

        $MapgeneratorContainersPermissions = new MapgeneratorContainersPermissions(
            $containerIds,
            $this->getWriteContainers(),
            $this->hasRootPrivileges
        );

        $mapgenerator['containers'] = [
            '_ids' => $containerIds
        ];

        $this->set('areContainersChangeable', $MapgeneratorContainersPermissions->areContainersChangeable());
        $this->set('mapgenerator', $mapgenerator);
        $this->viewBuilder()->setOption('serialize', ['mapgenerator', 'areContainersChangeable']);

        if (($this->request->is('post') || $this->request->is('put')) && $this->isAngularJsRequest()) {
            $data = $this->request->getData(null, []);

            if (empty($data['Mapgenerator']['map_refresh_interval'])) {
                $data['Mapgenerator']['map_refresh_interval'] = 90000;
            } else {
                if ($data['Mapgenerator']['map_refresh_interval'] < 5) {
                    $data['Mapgenerator']['map_refresh_interval'] = 5;
                }

                $data['Mapgenerator']['map_refresh_interval'] = ((int)$data['Mapgenerator']['map_refresh_interval'] * 1000);
            }

            if (!empty(['Mapgenerator']['mapgenerator_levels'])) {
                foreach ($data['Mapgenerator']['mapgenerator_levels'] as $levelKey => $level) {
                    // remove id from level when its null (new level added), because somehow this causes an error
                    if (empty($level['id'])) {
                        unset($data['Mapgenerator']['mapgenerator_levels'][$levelKey]['id']);
                    }
                }
            }

            $mapgeneratorEntity = $mapgenerator;
            $mapgeneratorEntity = $MapgeneratorsTable->patchEntity($mapgeneratorEntity, $data['Mapgenerator']);
            $MapgeneratorsTable->checkRules($mapgeneratorEntity);

            $MapgeneratorsTable->save($mapgeneratorEntity);
            if (!$mapgeneratorEntity->hasErrors()) {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($mapgeneratorEntity);
                }
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4ErrorMessage($mapgeneratorEntity);
                }
            }
        }
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var MapgeneratorsTable $MapgeneratorsTable */
        $MapgeneratorsTable = TableRegistry::getTableLocator()->get('MapModule.Mapgenerators');

        if (!$MapgeneratorsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Map generator'));
        }

        $mapgenerator = $MapgeneratorsTable->get($id, contain: [
            'Maps',
            'Containers'
        ]);
        $containerIdsToCheck = Hash::extract($mapgenerator, 'containers.{n}.id');
        if (!empty($containerIdsToCheck) && !$this->allowedByContainerId($containerIdsToCheck)) {
            $this->render403();
            return;
        }

        if ($MapgeneratorsTable->delete($mapgenerator)) {
            $this->set('message', __('Map generator deleted successfully'));
            $this->viewBuilder()->setOption('serialize', ['message']);
            return;
        }

        $this->response->withStatus(400);
        $this->set('message', __('Could not delete map generator'));
        $this->viewBuilder()->setOption('serialize', ['message']);
    }

    public function generate($id = null) {

        if (!$this->isApiRequest()) {
            //Only ship html template
            return;
        }

        /** @var MapgeneratorsTable $MapgeneratorsTable */
        $MapgeneratorsTable = TableRegistry::getTableLocator()->get('MapModule.Mapgenerators');

        if (!$MapgeneratorsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid Map generator'));
        }

        $mapgenerator = $MapgeneratorsTable->get($id, contain: [
            'Containers',
            'Maps',
            'MapgeneratorLevels'
        ]);

        if ($this->request->is('get')) {
            $this->viewBuilder()->setOption('serialize', ['mapgenerator']);
            $this->set('mapgenerator', $mapgenerator);
            return;
        }

        if (($this->request->is('post') || $this->request->is('put')) && $this->isAngularJsRequest()) {

            $containerIds = Hash::extract($mapgenerator, 'containers.{n}.id');

            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                $MY_RIGHTS = $this->getWriteContainers();
                $containerIds = array_intersect($containerIds, $MY_RIGHTS);
            }

            $type = $mapgenerator['type'];

            switch ($type) {
                //generate by host name splitting
                case Mapgenerator::TYPE_GENERATE_BY_HOSTNAME_SPLITTING:
                    $mapgeneratorLevels = $mapgenerator['mapgenerator_levels'];

                    if (empty($mapgeneratorLevels) || count($mapgeneratorLevels) < 2) {
                        $errors = [
                            'mapgenerator_levels' => __('You need at least two mapgenerator levels')
                        ];
                        $this->set('error', $errors);
                        $this->viewBuilder()->setOption('serialize', ['error']);
                    }

                    $mapsAndHostsData = $MapgeneratorsTable->getMapsAndHostsByHostsNameSplitting($mapgeneratorLevels, $MY_RIGHTS, $this->hasRootPrivileges);
                    break;
                //generate by container structure
                default:
                    $mapsAndHostsData = $MapgeneratorsTable->getMapsAndHostsDataByContainerStructure($containerIds, $MY_RIGHTS, $this->hasRootPrivileges);
                    break;
            }

            $generatedMaps = [];
            $mapIds = Hash::extract($mapgenerator['maps'], '{n}.id');

            // get already generated maps
            if (!empty($mapIds)) {

                /** @var MapsTable $MapsTable */
                $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

                // order by y coordinate ascending to avoid problems with items placement
                $contain = [
                    'Containers',
                    'Mapsummaryitems' => [
                        'sort' => ['Mapsummaryitems.y' => 'ASC']
                    ],
                    'Mapgadgets'      => [
                        'sort' => ['Mapgadgets.y' => 'ASC']
                    ],
                    'Mapicons'        => [
                        'sort' => ['Mapicons.y' => 'ASC']
                    ],
                    'Mapitems'        => [
                        'sort' => ['Mapitems.y' => 'ASC']
                    ],
                    'Maplines'        => [
                        'sort' => ['Maplines.endY' => 'ASC']
                    ],
                    'Maptexts'        => [
                        'sort' => ['Maptexts.y' => 'ASC']
                    ],
                ];

                $generatedMaps = $MapsTable->getMapsAndItemsByIds($mapIds, $contain);

            }

            // generate maps
            $Mapgenerator = new Mapgenerator($mapgenerator->toArray(), $mapsAndHostsData, $generatedMaps, $type);
            $generatedMapsAndItems = $Mapgenerator->generate();

            $allGeneratedMaps = $Mapgenerator->getAllGeneratedMaps();
            $allGeneratedMapIds = Hash::extract($allGeneratedMaps, '{n}.id');
            $amountOfNewGeneratedMaps = count($Mapgenerator->getNewGeneratedMaps());

            // save new generated maps
            if ($amountOfNewGeneratedMaps) {

                $data = [
                    'maps' => [
                        '_ids' => $allGeneratedMapIds
                    ]
                ];

                $mapgeneratorEntity = $MapgeneratorsTable->patchEntity($mapgenerator, $data);
                $MapgeneratorsTable->save($mapgeneratorEntity);
                if (!$mapgeneratorEntity->hasErrors()) {
                    if ($this->isJsonRequest()) {
                        $this->serializeCake4Id($mapgeneratorEntity);
                    }
                } else {
                    if ($this->isJsonRequest()) {
                        $this->serializeCake4ErrorMessage($mapgeneratorEntity);
                    }
                }
            }

            // return errors from generate
            if (array_key_exists("error", $generatedMapsAndItems)) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $generatedMapsAndItems['error']);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }


            // return most important information
            $generatedMapsAndItems = [
                'amountOfTotalMaps'         => count($allGeneratedMaps),
                'amountOfNewGeneratedMaps'  => $amountOfNewGeneratedMaps,
                'amountOfNewGeneratedItems' => count($Mapgenerator->getGeneratedItems()),
                'maps'                      => $allGeneratedMapIds,
                'newMaps'                   => Hash::extract($Mapgenerator->getNewGeneratedMaps(), '{n}.id'),
            ];


            $this->viewBuilder()->setOption('serialize', ['generatedMapsAndItems']);
            $this->set('generatedMapsAndItems', $generatedMapsAndItems);


        }


    }

}
