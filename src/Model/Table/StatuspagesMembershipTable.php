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

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * StatuspagesMemberships Model
 *
 * @property \App\Model\Table\StatuspagegroupsTable&\Cake\ORM\Association\BelongsTo $Statuspagegroups
 * @property \App\Model\Table\StatuspagesTable&\Cake\ORM\Association\BelongsTo $Statuspages
 *
 * @method \App\Model\Entity\StatuspagesMembership newEmptyEntity()
 * @method \App\Model\Entity\StatuspagesMembership newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\StatuspagesMembership> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StatuspagesMembership get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\StatuspagesMembership findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\StatuspagesMembership patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\StatuspagesMembership> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\StatuspagesMembership|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\StatuspagesMembership saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\StatuspagesMembership>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatuspagesMembership>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\StatuspagesMembership>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatuspagesMembership> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\StatuspagesMembership>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatuspagesMembership>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\StatuspagesMembership>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\StatuspagesMembership> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StatuspagesMembershipTable extends Table {
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statuspages_to_statuspagegroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Statuspagegroups', [
            'foreignKey' => 'statuspagegroup_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Statuspages', [
            'foreignKey' => 'statuspage_id',
            'joinType'   => 'INNER',
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
            ->integer('statuspagegroup_id')
            ->notEmptyString('statuspagegroup_id');

        $validator
            ->integer('collection_id')
            ->requirePresence('collection_id', 'create')
            ->notEmptyString('collection_id');

        $validator
            ->integer('category_id')
            ->requirePresence('category_id', 'create')
            ->notEmptyString('category_id');

        $validator
            ->integer('statuspage_id')
            ->notEmptyString('statuspage_id');

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
        $rules->add($rules->existsIn(['statuspagegroup_id'], 'Statuspagegroups'), ['errorField' => 'statuspagegroup_id']);
        $rules->add($rules->existsIn(['statuspage_id'], 'Statuspages'), ['errorField' => 'statuspage_id']);

        return $rules;
    }
}
