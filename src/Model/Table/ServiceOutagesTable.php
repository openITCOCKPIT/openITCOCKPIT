<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ServiceOutages Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\BelongsTo $Services
 *
 * @method \App\Model\Entity\ServiceOutage newEmptyEntity()
 * @method \App\Model\Entity\ServiceOutage newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceOutage[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceOutage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ServiceOutage findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ServiceOutage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceOutage[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceOutage|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServiceOutage saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServiceOutage[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ServiceOutage[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ServiceOutage[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ServiceOutage[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ServiceOutagesTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('service_outages');
        $this->setDisplayField(['service_id', 'start_time', 'state_time_usec']);
        $this->setPrimaryKey(['service_id', 'start_time', 'state_time_usec']);

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
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
            ->integer('host_id')
            ->notEmptyString('host_id');

        $validator
            ->requirePresence('end_time', 'create')
            ->notEmptyString('end_time');

        $validator
            ->scalar('output')
            ->maxLength('output', 1024)
            ->allowEmptyString('output');

        $validator
            ->boolean('is_hardstate')
            ->notEmptyString('is_hardstate');

        $validator
            ->boolean('in_downtime')
            ->notEmptyString('in_downtime');

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
        $rules->add($rules->existsIn('host_id', 'Hosts'), ['errorField' => 'host_id']);
        $rules->add($rules->existsIn('service_id', 'Services'), ['errorField' => 'service_id']);

        return $rules;
    }
}
