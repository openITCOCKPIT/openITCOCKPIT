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
use MapModule\Model\Entity\Mapline;

/**
 * Maplines Model
 *
 * @property MapsTable&BelongsTo $Maps
 *
 * @method Mapline get($primaryKey, $options = [])
 * @method Mapline newEntity($data = null, array $options = [])
 * @method Mapline[] newEntities(array $data, array $options = [])
 * @method Mapline|false save(EntityInterface $entity, $options = [])
 * @method Mapline saveOrFail(EntityInterface $entity, $options = [])
 * @method Mapline patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Mapline[] patchEntities($entities, array $data, array $options = [])
 * @method Mapline findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class MaplinesTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('maplines');
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
                if (!$coreTable->hasAssociation('Maplines')) {
                    $coreTable->hasMany('Maplines', [
                        'className'  => 'MapModule.Maplines',
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
                if (!$coreTable->hasAssociation('Maplines')) {
                    $coreTable->hasMany('Maplines', [
                        'className'  => 'MapModule.Maplines',
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
                if (!$coreTable->hasAssociation('Maplines')) {
                    $coreTable->hasMany('Maplines', [
                        'className'  => 'MapModule.Maplines',
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
                if (!$coreTable->hasAssociation('Maplines')) {
                    $coreTable->hasMany('Maplines', [
                        'className'  => 'MapModule.Maplines',
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
            ->integer('startX')
            ->notEmptyString('startX');

        $validator
            ->integer('startY')
            ->notEmptyString('startY');

        $validator
            ->integer('endX')
            ->notEmptyString('endX');

        $validator
            ->integer('endY')
            ->notEmptyString('endY');

        $validator
            ->integer('limit')
            ->allowEmptyString('limit');

        $validator
            ->scalar('iconset')
            ->maxLength('iconset', 128)
            ->allowEmptyString('iconset');

        $validator
            ->scalar('type')
            ->maxLength('type', 20)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('object_id')
            ->requirePresence('object_id', 'create')
            ->notEmptyString('object_id', null, function ($context) {
                return !(isset($context['data']['type']) && $context['data']['type'] === 'stateless');
            });

        $validator
            ->integer('z_index')
            ->notEmptyString('z_index');

        $validator
            ->integer('show_label')
            ->notEmptyString('show_label');

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
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Maplines.id' => $id]);
    }
}
