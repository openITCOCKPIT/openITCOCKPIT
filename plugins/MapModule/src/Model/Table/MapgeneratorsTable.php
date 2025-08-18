<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use MapModule\Model\Entity\Mapgenerator;

/**
 * Mapgenerators Model
 *
 * @property MapgeneratorsTable&HasMany $MapgeneratorsToMaps
 * @property ContainersTable&HasMany $MapgeneratorsToContainers
 * @property ContainersTable&HasMany $MapgeneratorLevels
 *
 * @method Mapgenerator get($primaryKey, $options = [])
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
    use Cake2ResultTableTrait;

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
            'saveStrategy' => 'replace'
        ])->setDependent(true);

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
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', false)
            ->allowEmptyString('description', null, true);

        $validator
            ->integer('map_refresh_interval')
            ->notEmptyString('map_refresh_interval');

        $validator
            ->requirePresence('containers', 'create', __('You have to choose at least one option.'))
            ->allowEmptyString('containers', null, false)
            ->multipleOptions('containers', [
                'min' => 1
            ], __('You have to choose at least one option.'));

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
        $rules->add(function ($entity, $options) {
            $levels = $entity->mapgenerator_levels;
            $levelPartErrors = [];
            $isContainerErrors = [];
            $levelErrors = [];

            if ($entity->type === 1 && empty($levels)) {
                return true;
            }

            // check if there are at least 2 levels
            if (count($levels) < 2) {
                $levelErrors['min'] = __('There must be at least 2 levels.');
            }

            $names = [];
            $isContainerCount = 0;

            foreach ($levels as $index => $level) {
                // checks if the level has a name
                if (empty($level['name'])) {
                    $levelPartErrors[$index]['name']['empty'] = __('The name cannot be empty.');
                }

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

            if ($isContainerCount > 1) {
                $isContainerErrors['unique'] = __('Only one level can be the container level.');
                return false;
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

            if (!empty($levelErrors)) {
                $entity->setInvalidField('mapgenerator_levels', false);
                $entity->setErrors([
                    'mapgenerator_levels' => $levelErrors
                ]);
            }

            if (!empty($levelPartErrors) || !empty($isContainerErrors) || !empty($levelErrors)) {
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
     * @param array $indexFilter
     * @param array $orderForPaginator
     * @param int|null $limit
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getAll(array $indexFilter, array $orderForPaginator, int $limit = null, PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $where = $indexFilter;

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

        $query->where($where);

        if ($limit !== null) {
            $query->limit($limit);
        }

        $queryResult = $query->orderBy($orderForPaginator)
            ->groupBy(['Mapgenerators.id'])
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

}
