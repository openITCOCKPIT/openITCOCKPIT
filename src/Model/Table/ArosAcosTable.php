<?php
declare(strict_types=1);

namespace App\Model\Table;

use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ArosAcos Model
 *
 * @property ArosTable&\Cake\ORM\Association\BelongsTo $Aros
 * @property AcosTable&\Cake\ORM\Association\BelongsTo $Acos
 *
 * @method \App\Model\Entity\ArosAco get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ArosAco newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ArosAco[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ArosAco|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ArosAco saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ArosAco patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ArosAco[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ArosAco findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class ArosAcosTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('aros_acos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Aros', [
            'className'  => 'Acl.Aros',
            'foreignKey' => 'aro_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Acos', [
            'className'  => 'Acl.Acos',
            'foreignKey' => 'aco_id',
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
