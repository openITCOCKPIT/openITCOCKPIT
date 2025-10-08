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

declare(strict_types=1);

namespace MapModule\Model\Table;

use App\itnovum\openITCOCKPIT\Filter\MapgeneratorFilter;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Table\ContainersTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use MapModule\Model\Entity\Mapgenerator;

/**
 * Mapgenerators Model
 *
 * @property MapsTable&HasMany $MapgeneratorsToMaps
 * @property ContainersTable&HasMany $MapgeneratorsToContainers
 * @property MapgeneratorLevelsTable&HasMany $MapgeneratorLevels
 *
 *
 * @method Mapgenerator get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method Mapgenerator newEntity($data = null, array $options = [])
 * @method Mapgenerator[] newEntities(array $data, array $options = [])
 * @method Mapgenerator|false save(EntityInterface $entity, $options = [])
 * @method Mapgenerator saveOrFail(EntityInterface $entity, $options = [])
 * @method Mapgenerator patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Mapgenerator[] patchEntities($entities, array $data, array $options = [])
 * @method Mapgenerator findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class MapgeneratorsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('mapgenerators');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Containers', [
            'className'        => 'Containers',
            'foreignKey'       => 'mapgenerator_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'mapgenerators_to_containers',
            'joinType'         => 'INNER',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Maps', [
            'className'        => 'MapModule.Maps',
            'foreignKey'       => 'mapgenerator_id',
            'targetForeignKey' => 'map_id',
            'joinTable'        => 'mapgenerators_to_maps',
            'saveStrategy'     => 'replace',
        ]);

        $this->hasMany('MapgeneratorLevels', [
            'foreignKey'   => 'mapgenerator_id',
            'saveStrategy' => 'replace',
            'className'    => 'MapModule.MapgeneratorLevels'
        ])->setDependent(true);

    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {

        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', false)
            ->allowEmptyString('description', null, true);

        $validator
            ->integer('map_refresh_interval')
            ->notEmptyString('map_refresh_interval');

        $validator
            ->allowEmptyString('containers', null, false)
            ->add('containers', 'minContainerRule', [
                'rule'    => function ($value, $context) {
                    return count($value['_ids']) > 0;
                },
                'message' => __('You have to choose at least one option.'),
                'on'      => function ($context) {
                    return $context['data']['type'] === \App\itnovum\openITCOCKPIT\Maps\Mapgenerator::TYPE_GENERATE_BY_CONTAINER_STRUCTURE;
                }
            ]);

        $validator
            ->integer('type')
            ->notEmptyString('type');

        $validator
            ->integer('items_per_line')
            ->notEmptyString('items_per_line')
            ->greaterThan('items_per_line', 0, __('This value need to be at least 1'));

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {

        /**
         *
         * this rule validates the mapgenerator levels by the following criteria:
         * - at least one level must be set as container
         * - the name of each level must be unique in context of the mapgenerator
         * - the divider must be set for each level except the last one
         *
         */

        $rules->add(function ($entity, $options) {
            $levels = $entity->mapgenerator_levels;
            $levelPartErrors = [];
            $isContainerErrors = [];

            if (($entity->type === \App\itnovum\openITCOCKPIT\Maps\Mapgenerator::TYPE_GENERATE_BY_CONTAINER_STRUCTURE && empty($levels)) || $levels === null) {
                return true;
            }

            $names = [];
            $isContainerCount = 0;

            foreach ($levels as $index => $level) {

                // check if the name is unique
                if (in_array($level['name'], $names)) {
                    $levelPartErrors[$index]['name']['unique'] = __('The name must be unique.');
                }
                $names[] = $level['name'];

                // checks if the divider is set (except last level)
                if ($index < count($levels) - 1 && empty($level['divider'])) {
                    $levelPartErrors[$index]['divider']['empty'] = __('The divider cannot be empty for non-final levels.');
                }

                // checks if is_container is only one time set to true
                if (!empty($level['is_container']) && boolval($level['is_container']) === true) {
                    $isContainerCount++;
                }
            }

            if ($isContainerCount === 0) {
                $isContainerErrors['empty'] = __('At least one level must be set as container.');
            }

            if (!empty($levelPartErrors)) {
                $entity->setInvalidField('validate_levels', false);
                $entity->setErrors([
                    'validate_levels' => $levelPartErrors
                ]);
            }

            if (!empty($isContainerErrors)) {
                $entity->setInvalidField('validate_levels_is_container', false);
                $entity->setErrors([
                    'validate_levels_is_container' => $isContainerErrors
                ]);
            }

            if (!empty($levelPartErrors) || !empty($isContainerErrors)) {
                return false;
            }

            return true;
        }, 'validate_mapgenerator_levels');

        return $rules;
    }

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Mapgenerators.id' => $id]);
    }


    /**
     * @param MapgeneratorFilter $MapgeneratorFilter
     * @param $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapgeneratorsIndex(MapgeneratorFilter $MapgeneratorFilter, $PaginateOMat = null, array $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->select([
                'Mapgenerators.id',
                'Mapgenerators.name',
                'Mapgenerators.description',
                'Mapgenerators.type',
                'Mapgenerators.map_refresh_interval',
                'Mapgenerators.items_per_line'
            ])->contain([
                'Maps',
                'Containers' => function ($q) {
                    return $q->select([
                        'Containers.id',
                        'Containers.name'
                    ]);
                }
            ]);
        $where = $MapgeneratorFilter->indexFilter();

        if (isset($where['has_generated_maps'])) {
            if ($where['has_generated_maps'] === 1) {
                $query->leftJoin(
                    ['MapgeneratorsToMaps' => 'mapgenerators_to_maps'],
                    ['MapgeneratorsToMaps.mapgenerator_id = Mapgenerators.id']
                )->where(['MapgeneratorsToMaps.map_id IS NOT NULL']);
            } else if ($where['has_generated_maps'] === 0) {
                $query->leftJoin(
                    ['MapgeneratorsToMaps' => 'mapgenerators_to_maps'],
                    ['MapgeneratorsToMaps.mapgenerator_id = Mapgenerators.id']
                )->where(['MapgeneratorsToMaps.map_id IS NULL']);
            }
            unset($where['has_generated_maps']);
        }

        $query->where($where)->groupBy(['Mapgenerators.id']);

        if (!empty($MY_RIGHTS)) {
            $query->select([
                'permission_status' => $query->newExpr(
                    'IF(
                        COUNT(`Containers`.`id`) > 0,
                        IF(
                          NOT EXISTS (
                            SELECT mcs.mapgenerator_id
                            FROM `mapgenerators_to_containers` mcs
                            WHERE mcs.mapgenerator_id = `Mapgenerators`.`id`
                              AND mcs.container_id IN (' . implode(',', $MY_RIGHTS) . ')
                          ),
                          "not_permitted",
                          "permitted"
                        ),
                        "permitted"
                      )'
                ),
            ])->join([
                [
                    'table'      => 'mapgenerators_to_containers',
                    'alias'      => 'Containers',
                    'type'       => 'LEFT',
                    'conditions' => [
                        'Mapgenerators.id = Containers.mapgenerator_id',
                    ],
                ]
            ])->having([
                'permission_status' => 'permitted'
            ])->groupBy(['Mapgenerators.id']);
        }
        $query->orderBy($MapgeneratorFilter->getOrderForPaginator('Mapgenerators.name', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->emptyArrayIfNull($query->toArray());
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }

    /**
     *
     *  gets the maps annd hosts by name splitting of the host names
     *  - the last part of the hostname is always the hostname itself and not a map level
     *  - the part that is marked as container must be the same as the tenant container of the host
     *
     * @param $mapGeneratorLevels
     * @param $MY_RIGHTS
     * @param $hasRootPrivileges
     * @return array
     */
    public function getMapsAndHostsByHostsNameSplitting($mapGeneratorLevels, $MY_RIGHTS, $hasRootPrivileges) {

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $containersWithChildsAndHostsForEachGivenContainerId = [];
        $mapsAndHosts = [];
        $containerParentIdToContainerArray = []; // array to find parent containers by id

        $containers = $MY_RIGHTS;
        if (empty($containers) && $hasRootPrivileges) {
            $containers = [ROOT_CONTAINER];
        }

        foreach ($containers as $id) {

            // get container with all children
            $subContainers = $ContainersTable->getContainerWithAllChildrenAndHosts($id, $MY_RIGHTS);
            $containersWithChildsAndHostsForEachGivenContainerId[] = Hash::filter($subContainers);
        }

        foreach ($containersWithChildsAndHostsForEachGivenContainerId as $containersWithChildsAndHosts) {
            foreach ($containersWithChildsAndHosts as $containerWithChildsAndHosts) {
                if (!empty($containerWithChildsAndHosts['parent_id'])) {
                    $containerParentIdToContainerArray[$containerWithChildsAndHosts['parent_id']] = $containerWithChildsAndHosts;
                }

                // run through all hosts and split the names by the defined levels and build the maps and hosts array
                if (!empty($containerWithChildsAndHosts['childsElements']['hosts'])) {
                    foreach ($containerWithChildsAndHosts['childsElements']['hosts'] as $hostId => $hostName) {

                        $hostNameParts = [];
                        $restofHostName = $hostName;
                        $containerIdForNewMap = 0;
                        $previousPartsAsString = ''; // to build unique names, which can be assigned to a map hierarchy

                        //split by the defined levels
                        foreach ($mapGeneratorLevels as $mapGeneratorLevel) {
                            if (!empty($mapGeneratorLevel['divider'])) {
                                $divider = $mapGeneratorLevel['divider'];
                                $pos = strpos($restofHostName, $divider);
                                if ($pos !== false) {
                                    $part = substr($restofHostName, 0, $pos);
                                    if ($previousPartsAsString !== '') {
                                        $nameToSave = $previousPartsAsString . '/' . $part;
                                    } else {
                                        $nameToSave = $part;
                                    }
                                    $hostNameParts[] = $nameToSave;
                                    $previousPartsAsString = $nameToSave;
                                    $restofHostName = substr($restofHostName, $pos + strlen($divider));
                                } else {
                                    // No more dividers found, take the rest of the hostname
                                    $part = $restofHostName;
                                    $hostNameParts[] = $part;
                                    break;
                                }
                            } else {
                                // No divider defined, take the whole rest of the hostname
                                $part = $restofHostName;
                                $hostNameParts[] = $part;
                                break;
                            }

                            // find the container for the new map
                            if ($mapGeneratorLevel['is_container']) {

                                // container has to be the same as the tenant container of the host
                                $tentantContainer = $this->findParentContainerByNameAndType($containerWithChildsAndHosts['parent_id'], $part, $containerParentIdToContainerArray);

                                if (!empty($tentantContainer)) {
                                    // Found the container for the new map
                                    $containerIdForNewMap = $tentantContainer['id'];
                                }

                            }

                        }

                        if ($containerIdForNewMap === 0 || count($hostNameParts) !== count($mapGeneratorLevels)) {
                            // Not enough parts for the defined levels, skip this host
                            continue;
                        }

                        // remove hostname from the parts
                        array_pop($hostNameParts);

                        // build the maps and hosts array
                        foreach ($hostNameParts as $index => $hostNamePart) {
                            $mapsAndHosts[] = [
                                'name'                 => $hostNamePart,
                                'containerIdForNewMap' => $containerIdForNewMap,
                                'hosts'                => []
                            ];

                            $lastElementIndex = array_key_last($mapsAndHosts);

                            // parent Index is used to find the higher level map during generation
                            if ($index > 0) {
                                $mapsAndHosts[$lastElementIndex]['parentIndex'] = $lastElementIndex - 1;
                            }

                            // add host to the last level
                            if ($index === count($hostNameParts) - 1) {
                                $mapsAndHosts[$lastElementIndex]['hosts'] = [
                                    [
                                        'id'   => $hostId,
                                        'name' => $hostName
                                    ]
                                ];
                            }
                        }
                    }
                }


            }
        }

        return $mapsAndHosts;

    }

    /**
     *  finds the parent container by name and type
     *
     * @param $containerId
     * @param $part
     * @param $containerParentIdToContainerArray
     * @return null
     */
    private function findParentContainerByNameAndType($containerId, $part, $containerParentIdToContainerArray) {
        while (isset($containerParentIdToContainerArray[$containerId])) {
            $container = $containerParentIdToContainerArray[$containerId];

            if ($container['name'] === $part && $container['containertype_id'] === 2) {
                return $container;
            }

            $containerId = $container['parent_id'];
        }

        return null;
    }

    /**
     *
     * gets the maps and host data by container structure/ hierarchy for the given containers
     * - only containers with hosts are returned
     *
     * @param array $containerIds
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapsAndHostsDataByContainerStructure($containerIds, $MY_RIGHTS) {

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $containersWithChildsAndHostsForEachGivenContainerId = [];
        $mapsAndHosts = [];
        $containerIdToIndexArray = []; // array to find parent containers index by id

        foreach ($containerIds as $id) {

            // get container with all children
            $subContainers = $ContainersTable->getContainerWithAllChildrenAndHosts($id, $MY_RIGHTS);
            $containersWithChildsAndHostsForEachGivenContainerId[] = Hash::filter($subContainers);
        }

        foreach ($containersWithChildsAndHostsForEachGivenContainerId as $containersWithChildsAndHosts) {
            // build the maps and hosts array
            foreach ($containersWithChildsAndHosts as $containerWithChildsAndHosts) {
                $hosts = [];
                if (!empty($containerWithChildsAndHosts['childsElements']['hosts'])) {
                    foreach ($containerWithChildsAndHosts['childsElements']['hosts'] as $hostId => $hostName) {
                        $hosts[] = [
                            'id'   => $hostId,
                            'name' => $hostName
                        ];
                    }
                }
                $mapsAndHosts[] = [
                    'name'                 => $containerWithChildsAndHosts['name'],
                    'containerIdForNewMap' => $containerWithChildsAndHosts['id'],
                    'hosts'                => $hosts
                ];
                $lastElementIndex = array_key_last($mapsAndHosts);
                $containerIdToIndexArray[$containerWithChildsAndHosts['id']] = $lastElementIndex;

                // parent Index is used to find the higher level map during generation
                // do not use empty to allow 0 as index
                if (array_key_exists($containerWithChildsAndHosts['parent_id'], $containerIdToIndexArray)) {
                    $mapsAndHosts[$lastElementIndex]['parentIndex'] = $containerIdToIndexArray[$containerWithChildsAndHosts['parent_id']];
                }

            }
        }

        return $mapsAndHosts;

    }

}
