<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ServicesToServicegroups Model
 *
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\BelongsTo $Services
 * @property \App\Model\Table\ServicegroupsTable&\Cake\ORM\Association\BelongsTo $Servicegroups
 *
 * @method \App\Model\Entity\ServicesToServicegroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ServicesToServicegroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ServicesToServicegroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ServicesToServicegroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServicesToServicegroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServicesToServicegroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ServicesToServicegroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ServicesToServicegroup findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class ServicesToServicegroupsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('services_to_servicegroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
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
        $rules->add($rules->existsIn(['service_id'], 'Services'));
        $rules->add($rules->existsIn(['servicegroup_id'], 'Servicegroups'));

        return $rules;
    }
}
