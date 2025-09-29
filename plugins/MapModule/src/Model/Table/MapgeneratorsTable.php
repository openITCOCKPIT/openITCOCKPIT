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
     * gets the hosts and name splitting by mapgenerator levels
     * - the last part of the hostname is always the hostname itself and not a map level
     * - the part that is marked as container must be the same as the tenant container of the host
     *
     * @param array $hosts
     * @param array $mapGeneratorLevels
     * @param bool $hasRootPrivileges
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsByNameSplitting($hosts, $mapGeneratorLevels, $hasRootPrivileges, $MY_RIGHTS = []) {

        $hostsAndMaps = [];

        // gets the container structure for the hosts to find the container for the new map
        // run this for all hosts to avoid multiple database calls
        $hostsWithContainerStructure = $this->getContainersForMapgeneratorByContainerStructure($hosts, $hasRootPrivileges, $MY_RIGHTS, []);
        $containerStructureByHostId = [];
        foreach ($hostsWithContainerStructure as $entry) {
            $containerStructureByHostId[$entry['hostId']] = $entry;
        }

        foreach ($hosts as $host) {

            $hostNameParts = [];
            $restofHostName = $host['name'];
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

                    if (empty($containerStructureByHostId[$host['id']])) {
                        // No container found for this level, skip this host
                        continue;
                    }

                    // container has to be the same as the tenant container of the host
                    $tenantContainer = $containerStructureByHostId[$host['id']]['containerHierarchy'][0];

                    if ($tenantContainer['name'] === $part && ((!empty($MY_RIGHTS) && in_array($tenantContainer['id'], $MY_RIGHTS)) || (empty($MY_RIGHTS) && $hasRootPrivileges))) {
                        // Found the container for the new map
                        $containerIdForNewMap = $tenantContainer['id'];
                    }

                }

            }

            if ($containerIdForNewMap === 0 || count($hostNameParts) !== count($mapGeneratorLevels)) {
                // Not enough parts for the defined levels, skip this host
                continue;
            }

            // remove hostname from the parts
            array_pop($hostNameParts);

            $hostsAndMaps[] = [
                'hostId'               => $host['id'],
                'hostName'             => $host['name'],
                'containerIdForNewMap' => $containerIdForNewMap,
                'mapNames'             => $hostNameParts
            ];

        }

        return $hostsAndMaps;

    }

    /**
     *
     * gets the container structure/ hierarchy for the given hosts up to the tenant container
     * - the container parameter can be used to filter the hosts and container structure by the selected containers
     *
     * @param array $hosts
     * @param bool $hasRootPrivileges
     * @param array $MY_RIGHTS
     * @param array $containers filter by these containers
     * @return array
     */
    public function getContainersForMapgeneratorByContainerStructure($hosts, $hasRootPrivileges, $MY_RIGHTS, $containers) {

        $containersAndHosts = [];
        $containerCache = []; // cache for containers to avoid multiple database calls

        foreach ($hosts as $host) {

            // skip hosts without container_id or id or name
            if (!isset($host['container_id'], $host['id'], $host['name'])) {
                continue;
            }

            $containerHierarchyForHost = [];
            $startContainerId = 0; // this is the first container in the hierarchy and gets checked for rights
            $currentContainerId = $host['container_id'];
            $skipHost = false;

            // skip host if container is root
            if ($currentContainerId === ROOT_CONTAINER) {
                continue;
            }

            while ($startContainerId === 0) {

                // load container by id from cache or database
                if (!isset($containerCache[$currentContainerId])) {

                    /** @var $ContainersTable ContainersTable */
                    $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

                    $containerCache[$currentContainerId] = $ContainersTable->getContainerById($currentContainerId);
                }
                $currentContainer = $containerCache[$currentContainerId];

                if (empty($currentContainer)) {
                    // Container not found, skip this host
                    $skipHost = true;
                    break;
                }

                // check if container is mandant
                if ($currentContainer['containertype_id'] === 2) {
                    // check for rights
                    if (!empty($MY_RIGHTS) && !in_array($currentContainer['id'], $MY_RIGHTS, true)) {
                        $skipHost = true;
                        break;
                    }
                    $startContainerId = $currentContainer['id'];
                }

                $containerForHost = [
                    'id'   => $currentContainer['id'],
                    'name' => $currentContainer['name']
                ];

                $containerHierarchyForHost[] = $containerForHost;
                $currentContainerId = $currentContainer['parent_id'] ?? null;

                // if next element is root break the loop
                if ($currentContainerId === null) {
                    break;
                }

            }

            // reverse array to have higher containers first
            $containerHierarchyForHost = array_reverse($containerHierarchyForHost);

            // filter hosts and container structure by selected containers
            if (!empty($containers)) {

                $startContainerFound = false;
                foreach ($containers as $containerId) {
                    foreach ($containerHierarchyForHost as $containerKey => $containerHierarchyContainer) {
                        // also check for rights
                        if ($containerId === $containerHierarchyContainer['id']
                            && ((!empty($MY_RIGHTS) && in_array($containerHierarchyContainer['id'], $MY_RIGHTS, true)) || (empty($MY_RIGHTS) && $hasRootPrivileges))) {
                            $startContainerFound = true;
                            break;
                        }
                    }
                }

                // if start container is not found, skip this host
                if (!$startContainerFound) {
                    $skipHost = true;
                }

            }

            if (!$skipHost) {

                $hostObject = [
                    'hostId'             => $host['id'],
                    'hostName'           => $host['name'],
                    'containerHierarchy' => $containerHierarchyForHost,
                ];

                $containersAndHosts[] = $hostObject;
            }

        }

        return $containersAndHosts;

    }

}
