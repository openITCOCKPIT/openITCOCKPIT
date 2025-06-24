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
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use MapModule\Model\Entity\Mapgenerator;

/**
 * Mapgenerators Model
 *
 * @property MapgeneratorsTable&HasMany $MapgeneratorsToMaps
 * @property ContainersTable&HasMany $MapgeneratorsToContainers
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

        $this->belongsToMany('StartContainers', [
            'className'        => 'Containers',
            'foreignKey'       => 'mapgenerator_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'mapgenerators_to_start_containers',
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

        $validator
            ->integer('type')
            ->notEmptyString('type');

        return $validator;
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

        $query = $this->find();

        $query = $query->select([
            'id',
            'name',
            'interval',
            'type',
            'maps_generated' => $query->newExpr('IF(COUNT(Maps.id) > 0, 1, 0)'),
            'created',
            'modified',
        ])
            ->contain([
                'Containers'
            ])
            ->innerJoinWith('Containers', function (Query $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->leftJoinWith('Maps');

        $where = $indexFilter;
        $having = [];

        if (isset($where['maps_generated'])) {
            $having['maps_generated'] = $where['maps_generated'];
            unset($where['maps_generated']);
        }

        if (!empty($where)) {
            $query->where($where);
        }

        if (!empty($having)) {
            $query->having($having);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $queryResult = $query->order($orderForPaginator)
            ->group(['Mapgenerators.id'])
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
