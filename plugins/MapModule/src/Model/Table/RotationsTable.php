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

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Table\ContainersTable;
use Cake\Core\Plugin;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use MapModule\Model\Entity\Rotation;

/**
 * Rotations Model
 *
 * @property RotationsTable&HasMany $MapsToRotations
 * @property ContainersTable&HasMany $RotationsToContainers
 *
 * @method Rotation get($primaryKey, $options = [])
 * @method Rotation newEntity($data = null, array $options = [])
 * @method Rotation[] newEntities(array $data, array $options = [])
 * @method Rotation|false save(EntityInterface $entity, $options = [])
 * @method Rotation saveOrFail(EntityInterface $entity, $options = [])
 * @method Rotation patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Rotation[] patchEntities($entities, array $data, array $options = [])
 * @method Rotation findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class RotationsTable extends Table {

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('rotations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Containers', [
            'className'        => 'Containers',
            'foreignKey'       => 'rotation_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'rotations_to_containers',
            'joinType'         => 'INNER',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Maps', [
            'className'        => 'MapModule.Maps',
            'foreignKey'       => 'rotation_id',
            'targetForeignKey' => 'map_id',
            'joinTable'        => 'maps_to_rotations',
            'saveStrategy'     => 'replace',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {

        $intervalStep = 5;

        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('interval')
            ->greaterThanOrEqual('interval', 10, __('This value need to be at least 10'))
            ->notEmptyString('interval')
            ->add('interval', 'step', [
                'rule'    => function ($value, $context) use ($intervalStep) {
                    $step = $intervalStep;
                    return $value % $step === 0;
                },
                'message' => __('The value must be a multiple of {0}.', $intervalStep)
            ]);

        $validator
            ->requirePresence('containers', 'create', __('You have to choose at least one option.'))
            ->allowEmptyString('containers', null, false)
            ->multipleOptions('containers', [
                'min' => 1
            ], __('You have to choose at least one option.'));

        return $validator;
    }

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Rotations.id' => $id]);
    }

    /**
     * @param array $indexFilter
     * @param array $orderForPaginator
     * @param int|null $limit
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getAll(array $indexFilter, array $orderForPaginator, ?int $limit = null, ?PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->contain([
                'Maps',
                'Containers'
            ])
            ->innerJoinWith('Containers', function (Query $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            });
        if (!empty($indexFilter)) {
            $query->where($indexFilter);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $queryResult = $query->orderBy($orderForPaginator)
            ->groupBy(['Rotations.id'])
            ->enableAutoFields(true)
            ->all();

        if (empty($queryResult)) {
            $result = [];
        } else {
            if ($PaginateOMat === null) {
                $result = $query->toArray();
            } else {
                if ($PaginateOMat->useScroll()) {
                    $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
                } else {
                    $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
                }
            }
        }

        return $result;
    }

    /**
     * @param int|array $satelliteId
     * @param bool $enableHydration
     * @return array
     */
    public function getRotationsWithMapsBySatelliteId($satelliteIds) {
        if (!Plugin::isLoaded('DistributeModule')) {
            return [];
        }

        if (!is_array($satelliteIds)) {
            $satelliteIds = [$satelliteIds];
        }


        $query = $this->find()
            ->select([
                'Rotations.id',
                'Rotations.name',
                'Rotations.interval',
            ])
            ->innerJoinWith('Maps', function (Query $q) use ($satelliteIds) {
                $q->innerJoinWith('Satellites', function (Query $q) use ($satelliteIds) {
                    $q->where([
                        'Satellites.id IN' => $satelliteIds
                    ]);
                    return $q;
                });
                return $q;
            })
            ->groupBy([
                'Rotations.id'
            ])
            ->enableHydration(false)
            ->all();

        $rotations = $query->toArray() ?? [];

        $result = [];
        foreach ($rotations as $rotation) {
            $record = [
                'id'       => $rotation['id'],
                'name'     => $rotation['name'],
                'interval' => $rotation['interval'],
                'maps'     => [
                    '_ids' => []
                ]
            ];

            //Load maps of the rotation that are available on the satellite
            $query = $this->find()
                ->select([
                    'Maps.id'
                ])
                ->innerJoinWith('Maps', function (Query $q) use ($satelliteIds) {
                    $q->innerJoinWith('Satellites', function (Query $q) use ($satelliteIds) {
                        $q->where([
                            'Satellites.id IN' => $satelliteIds
                        ]);
                        return $q;
                    });
                    return $q;
                })
                ->where([
                    'Rotations.id' => $rotation['id']
                ])
                ->enableHydration(false)
                ->all();

            $maps = $query->toArray() ?? [];
            $record['maps']['_ids'] = Hash::extract($maps, '{n}._matchingData.Maps.id');

            //Only add rotations with maps
            if (!empty($record['maps']['_ids'])) {
                $result[] = $record;
            }
        }

        return $result;
    }

    /**
     * @param $rotationId
     * @param array $containerIds
     * @return array
     */
    public function getRequiredContainerIdsForRotation($rotationId, array $containerIds) {
        $requiredIds = [];
        if (empty($containerIds)) {
            return $requiredIds;
        }
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $ids = array_unique(
            array_merge_recursive(
                $this->getMapContainerIdsForRotation($rotationId, $containerIds)
            )
        );

        foreach ($ids as $requiredId) {
            $requiredIds[] = (int)$requiredId;
        }
        return $requiredIds;
    }

    /**
     * MapsToRotations
     * @param int $rotationId
     * @param array $containerIds
     * @return array
     */
    public function getMapContainerIdsForRotation(int $rotationId, array $containerIds) {
        if (empty($containerIds)) {
            return [];
        }

        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $query = $this->find()
            ->select([
                'Containers.id'
            ])
            ->innerJoin(
                ['MapsToRotations' => 'maps_to_rotations'],
                ['MapsToRotations.rotation_id = Rotations.id']
            )
            ->innerJoin(
                ['Maps' => 'maps'],
                ['Maps.id = MapsToRotations.map_id']
            )
            ->innerJoin(
                ['MapsToContainers' => 'maps_to_containers'],
                ['MapsToContainers.map_id = Maps.id']
            )
            ->innerJoin(
                ['Containers' => 'containers'],
                ['Containers.id = MapsToContainers.container_id']
            )
            ->where([
                'Rotations.id'     => $rotationId,
                'Containers.id IN' => $containerIds
            ])
            ->distinct()
            ->disableAutoFields()
            ->disableHydration()
            ->toArray();
        return Hash::extract($query, '{n}.Containers.id');
    }

}
