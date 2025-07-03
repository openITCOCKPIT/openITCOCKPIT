<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsersToOrganizationalChartNodes Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\OrganizationalChartNodesTable&\Cake\ORM\Association\BelongsTo $OrganizationalChartNodes
 *
 * @method \App\Model\Entity\UsersToOrganizationalChartNode newEmptyEntity()
 * @method \App\Model\Entity\UsersToOrganizationalChartNode newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode get($primaryKey, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\UsersToOrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsersToOrganizationalChartNodesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users_to_organizational_chart_nodes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('OrganizationalChartNodes', [
            'foreignKey' => 'organizational_chart_node_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->integer('organizational_chart_node_id')
            ->notEmptyString('organizational_chart_node_id');

        $validator
            ->integer('is_manager')
            ->notEmptyString('is_manager');

        $validator
            ->integer('user_role')
            ->notEmptyString('user_role');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn('organizational_chart_node_id', 'OrganizationalChartNodes'), ['errorField' => 'organizational_chart_node_id']);

        return $rules;
    }
}
