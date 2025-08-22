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

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TimeperiodTimeranges Model
 *
 * @property \App\Model\Table\TimeperiodsTable|\Cake\ORM\Association\BelongsTo $Timeperiods
 *
 * @method \App\Model\Entity\TimeperiodTimerange get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TimeperiodTimerange newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\TimeperiodTimerange[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TimeperiodTimerange|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TimeperiodTimerange|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TimeperiodTimerange patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TimeperiodTimerange[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\TimeperiodTimerange findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class TimeperiodTimerangesTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('timeperiod_timeranges');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Timeperiods', [
            'foreignKey' => 'timeperiod_id',
            'joinType'   => 'INNER'
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
            ->integer('day')
            ->inList('day', [1, 2, 3, 4, 5, 6, 7])
            ->allowEmptyString('day', null, false);

        $validator
            ->scalar('start')
            ->maxLength('start', 5)
            ->allowEmptyString('start', null, true)
            ->regex('start', '/(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]|(24:00)$/', 'Please use HH:mm format')
            ->add('start', 'custom', [
                'rule'    => function ($value, $context) {
                    if (!empty($context['data']['start']) && !empty($context['data']['end'])) {
                        return $context['data']['start'] < $context['data']['end'];
                    }
                    return true;
                },
                'message' => __('The start time must be before the end time.')
            ]);

        $validator
            ->scalar('end')
            ->maxLength('end', 5)
            ->allowEmptyString('end', null, true)
            ->regex('end', '/(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]|(24:00)$/', 'Please use HH:mm format')
            ->add('end', 'custom', [
                'rule'    => function ($value, $context) {
                    if (!empty($context['data']['start']) && !empty($context['data']['end'])) {
                        return $context['data']['start'] < $context['data']['end'];
                    }
                    return true;
                },
                'message' => __('The end time must be after the start time.')
            ]);

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
        $rules->add($rules->existsIn(['timeperiod_id'], 'Timeperiods'));
        return $rules;
    }
}
