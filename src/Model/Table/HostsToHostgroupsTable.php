<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * HostsToHostgroups Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 * @property \App\Model\Table\HostgroupsTable&\Cake\ORM\Association\BelongsTo $Hostgroups
 *
 * @method \App\Model\Entity\HostsToHostgroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\HostsToHostgroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\HostsToHostgroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\HostsToHostgroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\HostsToHostgroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\HostsToHostgroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\HostsToHostgroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\HostsToHostgroup findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class HostsToHostgroupsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('hosts_to_hostgroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
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
        $rules->add($rules->existsIn(['host_id'], 'Hosts'));
        $rules->add($rules->existsIn(['hostgroup_id'], 'Hostgroups'));

        return $rules;
    }
}
