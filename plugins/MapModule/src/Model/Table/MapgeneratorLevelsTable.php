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

namespace MapModule\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use MapModule\Model\Entity\MapgeneratorLevel;

/**
 * MapgeneratorLevels Model
 *
 * @property MapgeneratorsTable|\Cake\ORM\Association\BelongsTo $Mapgenerators
 *
 * @method MapgeneratorLevel get($primaryKey, $options = [])
 * @method MapgeneratorLevel newEntity($data = null, array $options = [])
 * @method MapgeneratorLevel[] newEntities(array $data, array $options = [])
 * @method MapgeneratorLevel|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method MapgeneratorLevel|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method MapgeneratorLevel patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method MapgeneratorLevel[] patchEntities($entities, array $data, array $options = [])
 * @method MapgeneratorLevel findOrCreate($search, callable $callback = null, $options = [])
 */
class MapgeneratorLevelsTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('mapgenerator_levels');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Mapgenerators', [
            'foreignKey' => 'mapgenerator_id',
            'joinType'   => 'INNER',
            'className'  => 'MapModule.Mapgenerators',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
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
        $rules->add($rules->existsIn(['mapgenerator_id'], 'Mapgenerators'));
        return $rules;
    }
}
