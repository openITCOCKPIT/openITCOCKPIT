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

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use MapModule\Model\Entity\Mapitem;

/**
 * Mapitems Model
 *
 * @property MapsTable&BelongsTo $Maps
 *
 * @method Mapitem get($primaryKey, $options = [])
 * @method Mapitem newEntity($data = null, array $options = [])
 * @method Mapitem[] newEntities(array $data, array $options = [])
 * @method Mapitem|false save(EntityInterface $entity, $options = [])
 * @method Mapitem saveOrFail(EntityInterface $entity, $options = [])
 * @method Mapitem patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Mapitem[] patchEntities($entities, array $data, array $options = [])
 * @method Mapitem findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class MapitemsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('mapitems');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Maps', [
            'foreignKey' => 'map_id',
            'joinType'   => 'INNER',
            'className'  => 'MapModule.Maps',
        ]);

        $this->belongsTo('Hosts', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'conditions' => [
                'type' => 'host'
            ]
        ]);

        $this->belongsTo('Services', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'conditions' => [
                'type' => 'service'
            ]
        ]);

        $this->belongsTo('Hostgroups', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'conditions' => [
                'type' => 'hostgroup'
            ]
        ]);

        $this->belongsTo('Servicegroups', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'conditions' => [
                'type' => 'servicegroup'
            ]
        ]);
    }

    public function bindCoreAssociations(Table $coreTable) {
        switch ($coreTable->getAlias()) {
            case 'Hosts':
                if (!$coreTable->hasAssociation('Mapitems')) {
                    $coreTable->hasMany('Mapitems', [
                        'className'  => 'MapModule.Mapitems',
                        'dependent'  => true,
                        'foreignKey' => 'object_id',
                        'joinType'   => 'INNER',
                        'conditions' => [
                            'type' => 'host'
                        ]
                    ]);
                }
                break;
            case 'Hostgroups':
                if (!$coreTable->hasAssociation('Mapitems')) {
                    $coreTable->hasMany('Mapitems', [
                        'className'  => 'MapModule.Mapitems',
                        'dependent'  => true,
                        'foreignKey' => 'object_id',
                        'joinType'   => 'INNER',
                        'conditions' => [
                            'type' => 'hostgroup'
                        ]
                    ]);
                }
                break;
            case 'Services':
                if (!$coreTable->hasAssociation('Mapitems')) {
                    $coreTable->hasMany('Mapitems', [
                        'className'  => 'MapModule.Mapitems',
                        'dependent'  => true,
                        'foreignKey' => 'object_id',
                        'joinType'   => 'INNER',
                        'conditions' => [
                            'type' => 'service'
                        ]
                    ]);
                }
                break;
            case 'Servicegroups':
                if (!$coreTable->hasAssociation('Mapitems')) {
                    $coreTable->hasMany('Mapitems', [
                        'className'  => 'MapModule.Mapitems',
                        'dependent'  => true,
                        'foreignKey' => 'object_id',
                        'joinType'   => 'INNER',
                        'conditions' => [
                            'type' => 'servicegroup'
                        ]
                    ]);
                }
                break;
        }
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
            ->integer('x')
            ->notEmptyString('x');

        $validator
            ->integer('y')
            ->notEmptyString('y');

        $validator
            ->integer('limit')
            ->allowEmptyString('limit');

        $validator
            ->scalar('iconset')
            ->maxLength('iconset', 128)
            ->requirePresence('iconset', 'create')
            ->notEmptyString('iconset');

        $validator
            ->scalar('type')
            ->maxLength('type', 20)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('object_id')
            ->requirePresence('object_id', 'create')
            ->notEmptyString('object_id');

        $validator
            ->integer('z_index')
            ->notEmptyString('z_index');

        $validator
            ->integer('show_label')
            ->notEmptyString('show_label');

        $validator
            ->integer('label_possition')
            ->notEmptyString('label_possition');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['map_id'], 'Maps'));

        return $rules;
    }

    /**
     * @param $objectId
     * @param $mapId
     * @return array
     */
    //$map['id'], $mapId
    public function getMapitemsForMaps($objectId, $mapId) {
        $query = $this->find()
            ->select(['Mapitems.object_id'])
            ->where([
                'Mapitems.map_id'    => $mapId,
                'Mapitems.type'      => 'map',
                'Mapitems.object_id' => $objectId
            ]);
        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param $objectId
     * @param $mapId
     * @return array
     */
    public function getMapMapitemsByMap($objectId, $mapId) {
        $query = $this->find()
            ->select(['Mapitems.object_id'])
            ->where([
                'Mapitems.map_id'    => $mapId,
                'Mapitems.type'      => 'map',
                'Mapitems.object_id' => $objectId
            ]);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }


    /**
     * @param int $mapId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function allVisibleMapItems($mapId, $MY_RIGHTS = [], $enableHydration = true) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->join([
                [
                    'table'      => 'maps',
                    'type'       => 'INNER',
                    'alias'      => 'Maps',
                    'conditions' => 'Maps.id = Mapitems.map_id',
                ],
                [
                    'table'      => 'maps_to_containers',
                    'type'       => 'INNER',
                    'alias'      => 'MapsToContainers',
                    'conditions' => 'MapsToContainers.map_id = Maps.id',
                ],
            ])
            ->select([
                'Mapitems.map_id',
                'Mapitems.object_id',
            ])
            ->where([
                'Mapitems.type'       => 'map',
                'Mapitems.map_id != ' => $mapId
            ])
            ->enableHydration($enableHydration);
        if (!empty($MY_RIGHTS)) {
            $query->where(['MapsToContainers.container_id IN' => $MY_RIGHTS]);
        }

        $result = $query->all();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Mapitems.id' => $id]);
    }
}
