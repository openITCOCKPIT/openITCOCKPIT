<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * HostOutages Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\HostOutage newEmptyEntity()
 * @method \App\Model\Entity\HostOutage newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\HostOutage[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\HostOutage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\HostOutage findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\HostOutage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\HostOutage[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\HostOutage|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\HostOutage saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\HostOutage[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\HostOutage[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\HostOutage[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\HostOutage[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class HostOutagesTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('host_outages');
        $this->setDisplayField(['host_id', 'start_time', 'state_time_usec']);
        $this->setPrimaryKey(['host_id', 'start_time', 'state_time_usec']);

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
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

        return $rules;
    }
}
