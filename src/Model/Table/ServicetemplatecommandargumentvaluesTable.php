<?php

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Servicetemplatecommandargumentvalues Model
 *
 * @property \App\Model\Table\CommandargumentsTable|\Cake\ORM\Association\BelongsTo $Commandarguments
 * @property \App\Model\Table\ServicetemplatesTable|\Cake\ORM\Association\BelongsTo $Servicetemplates
 *
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Servicetemplatecommandargumentvalue findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServicetemplatecommandargumentvaluesTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('servicetemplatecommandargumentvalues');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Commandarguments', [
            'foreignKey' => 'commandargument_id',
            'joinType'   => 'INNER'
        ]);
        $this->belongsTo('Servicetemplates', [
            'foreignKey' => 'servicetemplate_id',
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
        $rules->add($rules->existsIn(['servicetemplate_id'], 'Servicetemplates'));

        return $rules;
    }

    /**
     * @param int $servicetemplateId
     * @param int $commandId
     * @return array
     */
    public function getByServicetemplateIdAndCommandId($servicetemplateId, $commandId) {
        $query = $this->find()
            ->contain(['Commandarguments'])
            ->where([
                'Servicetemplatecommandargumentvalues.servicetemplate_id' => $servicetemplateId,
                'Commandarguments.command_id'                             => $commandId
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
