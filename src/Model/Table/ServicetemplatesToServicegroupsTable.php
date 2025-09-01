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

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ServicetemplatesToServicegroups Model
 *
 * @property \App\Model\Table\ServicetemplatesTable&\Cake\ORM\Association\BelongsTo $Servicetemplates
 * @property \App\Model\Table\ServicegroupsTable&\Cake\ORM\Association\BelongsTo $Servicegroups
 *
 * @method \App\Model\Entity\ServicetemplatesToServicegroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ServicetemplatesToServicegroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ServicetemplatesToServicegroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ServicetemplatesToServicegroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServicetemplatesToServicegroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServicetemplatesToServicegroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ServicetemplatesToServicegroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ServicetemplatesToServicegroup findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class ServicetemplatesToServicegroupsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('servicetemplates_to_servicegroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Servicetemplates', [
            'foreignKey' => 'servicetemplate_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Servicegroups', [
            'foreignKey' => 'servicegroup_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

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
        $rules->add($rules->existsIn(['servicetemplate_id'], 'Servicetemplates'));
        $rules->add($rules->existsIn(['servicegroup_id'], 'Servicegroups'));

        return $rules;
    }
}
