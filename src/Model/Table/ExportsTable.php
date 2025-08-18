<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Exports Model
 *
 * @method \App\Model\Entity\Export get($primaryKey, $options = [])
 * @method \App\Model\Entity\Export newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Export[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Export|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Export saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Export patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Export[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Export findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ExportsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('exports');
        $this->setDisplayField('id');
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
            ->scalar('task')
            ->maxLength('task', 255)
            ->requirePresence('task', 'create')
            ->notEmptyString('task');

        $validator
            ->scalar('text')
            ->maxLength('text', 255)
            ->requirePresence('text', 'create')
            ->notEmptyString('text');

        $validator
            ->integer('finished')
            ->notEmptyString('finished');

        $validator
            ->integer('successfully')
            ->notEmptyString('successfully');

        return $validator;
    }

    /**
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getCurrentExportState() {
        return $this->find()
            ->orderBy(['id' => 'asc'])
            ->all();
    }
}
