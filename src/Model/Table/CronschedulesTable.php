<?php

namespace App\Model\Table;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Cronschedules Model
 *
 * @property \App\Model\Table\CronjobsTable|\Cake\ORM\Association\BelongsTo $Cronjobs
 *
 * @method \App\Model\Entity\Cronschedule get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Cronschedule newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Cronschedule[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Cronschedule|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Cronschedule|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Cronschedule patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Cronschedule[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Cronschedule findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class CronschedulesTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cronschedules');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Cronjobs', [
            'foreignKey' => 'cronjob_id'
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
            ->integer('is_running')
            ->allowEmptyString('is_running');

        $validator
            ->dateTime('start_time')
            ->requirePresence('start_time', 'create')
            ->allowEmptyDateTime('start_time', null, false);

        $validator
            ->dateTime('end_time')
            ->requirePresence('end_time', 'create')
            ->allowEmptyDateTime('end_time', null, false);

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
        $rules->add($rules->existsIn(['cronjob_id'], 'Cronjobs'));

        return $rules;
    }

    /**
     * @param int $cronjobId
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getSchedulByCronjobId(int $cronjobId) {
        try {
            $query = $this->find()
                ->where([
                    'cronjob_id' => $cronjobId
                ])
                ->firstOrFail();

            return $query;
        } catch (RecordNotFoundException $e) {
            // Database truncated or maybe this cronjob was never executed before?
            return $this->newEntity([
                'cronjob_id' => $cronjobId,
                'start_time' => '1970-01-01 01:00:00',
                'end_time'   => '1970-01-01 01:00:00'
            ]);
        }
    }
}
