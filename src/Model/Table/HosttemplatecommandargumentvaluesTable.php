<?php

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Hosttemplatecommandargumentvalues Model
 *
 * @property \App\Model\Table\CommandargumentsTable|\Cake\ORM\Association\BelongsTo $Commandarguments
 * @property \App\Model\Table\HosttemplatesTable|\Cake\ORM\Association\BelongsTo $Hosttemplates
 *
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Hosttemplatecommandargumentvalue findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class HosttemplatecommandargumentvaluesTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('hosttemplatecommandargumentvalues');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Commandarguments', [
            'foreignKey' => 'commandargument_id',
            'joinType'   => 'INNER'
        ]);
        $this->belongsTo('Hosttemplates', [
            'foreignKey' => 'hosttemplate_id',
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
        $rules->add($rules->existsIn(['hosttemplate_id'], 'Hosttemplates'));

        return $rules;
    }

    /**
     * @param int $hosttemplateId
     * @param int $commandId
     * @return array
     */
    public function getByHosttemplateIdAndCommandId($hosttemplateId, $commandId) {
        $query = $this->find()
            ->contain(['Commandarguments'])
            ->where([
                'Hosttemplatecommandargumentvalues.hosttemplate_id' => $hosttemplateId,
                'Commandarguments.command_id'                       => $commandId
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
