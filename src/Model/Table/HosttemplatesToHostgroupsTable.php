<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * HosttemplatesToHostgroups Model
 *
 * @property \App\Model\Table\HosttemplatesTable&\Cake\ORM\Association\BelongsTo $Hosttemplates
 * @property \App\Model\Table\HostgroupsTable&\Cake\ORM\Association\BelongsTo $Hostgroups
 *
 * @method \App\Model\Entity\HosttemplatesToHostgroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\HosttemplatesToHostgroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\HosttemplatesToHostgroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\HosttemplatesToHostgroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\HosttemplatesToHostgroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\HosttemplatesToHostgroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\HosttemplatesToHostgroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\HosttemplatesToHostgroup findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class HosttemplatesToHostgroupsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('hosttemplates_to_hostgroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Hosttemplates', [
            'foreignKey' => 'hosttemplate_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Hostgroups', [
            'foreignKey' => 'hostgroup_id',
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
        $rules->add($rules->existsIn(['hosttemplate_id'], 'Hosttemplates'));
        $rules->add($rules->existsIn(['hostgroup_id'], 'Hostgroups'));

        return $rules;
    }
}
