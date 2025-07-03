<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OrganizationalChartConnections Model
 *
 * @property \App\Model\Table\OrganizationalChartsTable&\Cake\ORM\Association\BelongsTo $OrganizationalCharts
 *
 * @method \App\Model\Entity\OrganizationalChartConnection newEmptyEntity()
 * @method \App\Model\Entity\OrganizationalChartConnection newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection get($primaryKey, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChartConnection[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrganizationalChartConnectionsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('organizational_chart_connections');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('OrganizationalCharts', [
            'foreignKey' => 'organizational_chart_id',
        ]);
        $this->hasMany('UsersToOrganizationalChartNodes', [
            'foreignKey' => 'organizational_chart_id'
        ])->setDependent(true);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('organizational_chart_id')
            ->allowEmptyString('organizational_chart_id');

        $validator
            ->integer('organizational_chart_input_node_id')
            ->allowEmptyString('organizational_chart_input_node_id');

        $validator
            ->integer('organizational_chart_output_node_id')
            ->allowEmptyString('organizational_chart_output_node_id');

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
        $rules->add($rules->existsIn('organizational_chart_id', 'OrganizationalCharts'), ['errorField' => 'organizational_chart_id']);

        return $rules;
    }
}
