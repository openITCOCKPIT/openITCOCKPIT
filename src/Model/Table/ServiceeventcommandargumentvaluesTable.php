<?php

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Serviceeventcommandargumentvalues Model
 *
 * @property \App\Model\Table\CommandargumentsTable|\Cake\ORM\Association\BelongsTo $Commandarguments
 * @property \App\Model\Table\ServicesTable|\Cake\ORM\Association\BelongsTo $Services
 *
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Serviceeventcommandargumentvalue findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServiceeventcommandargumentvaluesTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('serviceeventcommandargumentvalues');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Commandarguments', [
            'foreignKey' => 'commandargument_id',
            'joinType'   => 'INNER'
        ]);
        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
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
            ->scalar('value')
            ->maxLength('value', 1000)
            ->allowEmptyString('value', null, true);

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
        $rules->add($rules->existsIn(['commandargument_id'], 'Commandarguments'));
        $rules->add($rules->existsIn(['service_id'], 'Services'));

        return $rules;
    }

    /**
     * @param int $serviceId
     * @param int $commandId
     * @return array
     */
    public function getByServiceIdAndCommandId($serviceId, $commandId) {
        $query = $this->find()
            ->contain(['Commandarguments'])
            ->where([
                'Serviceeventcommandargumentvalues.service_id' => $serviceId,
                'Commandarguments.command_id'                  => $commandId
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }
        return $result;
    }
}
