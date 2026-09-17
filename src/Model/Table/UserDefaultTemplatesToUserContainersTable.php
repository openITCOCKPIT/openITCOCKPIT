<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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
 * UserDefaultTemplatesToUserContainers Model
 *
 * @property \App\Model\Table\UserDefaultTemplatesTable&\Cake\ORM\Association\BelongsTo $UserDefaultTemplates
 * @property \App\Model\Table\ContainersTable&\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer newEmptyEntity()
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserDefaultTemplateToUserContainer> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserDefaultTemplateToUserContainer> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplateToUserContainer saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplateToUserContainer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplateToUserContainer>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplateToUserContainer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplateToUserContainer> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplateToUserContainer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplateToUserContainer>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplateToUserContainer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplateToUserContainer> deleteManyOrFail(iterable $entities, array $options = [])
 */
class UserDefaultTemplatesToUserContainersTable extends Table {
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('user_default_templates_to_user_containers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('UserDefaultTemplates', [
            'foreignKey' => 'user_default_template_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
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
            ->integer('user_default_template_id')
            ->notEmptyString('user_default_template_id');

        $validator
            ->integer('container_id')
            ->notEmptyString('container_id');

        $validator
            ->integer('permission_level')
            ->notEmptyString('permission_level');

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
        $rules->add($rules->existsIn(['user_default_template_id'], 'UserDefaultTemplates'), ['errorField' => 'user_default_template_id']);
        $rules->add($rules->existsIn(['container_id'], 'Containers'), ['errorField' => 'container_id']);

        return $rules;
    }
}
