<?php

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Customvariables Model
 *
 * @property \App\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \App\Model\Entity\Customvariable get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Customvariable newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Customvariable[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Customvariable|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customvariable|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customvariable patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Customvariable[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Customvariable findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CustomvariablesTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('customvariables');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->allowEmptyString('name', __('Macro name cannot be left blank.'), false)
            ->regex('name', '/[\d\w\_]/', __('Macro name contains illegal characters'));

        $validator
            ->scalar('value')
            ->maxLength('value', 255)
            ->requirePresence('value', 'create')
            ->allowEmptyString('value', __('Macro value cannot be left blank.'), false);

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

        return $rules;
    }
}
