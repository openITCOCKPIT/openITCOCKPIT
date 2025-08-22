<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OrganizationalChartNodes Model
 *
 * @property \App\Model\Table\OrganizationalChartNodesTable&\Cake\ORM\Association\BelongsTo $ParentOrganizationalChartNodes
 * @property \App\Model\Table\OrganizationalChartsTable&\Cake\ORM\Association\BelongsTo $OrganizationalCharts
 * @property \App\Model\Table\ContainersTable&\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\OrganizationalChartNodesTable&\Cake\ORM\Association\HasMany $ChildOrganizationalChartNodes
 * @property \App\Model\Table\OrganizationalChartNodesUsersMembershipsTable&\Cake\ORM\Association\HasMany $Users
 *
 * @method \App\Model\Entity\OrganizationalChartNode newEmptyEntity()
 * @method \App\Model\Entity\OrganizationalChartNode newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\OrganizationalChartNode findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 */
class OrganizationalChartNodesTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('organizational_chart_nodes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('OrganizationalCharts', [
            'foreignKey' => 'organizational_chart_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Users', [
            'through'      => 'OrganizationalChartNodesUsersMemberships',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('OrganizationalChartInputConnections', [
            'className'  => 'OrganizationalChartConnections',
            'foreignKey' => 'organizational_chart_input_node_id'
        ])->setDependent(true);

        $this->hasMany('OrganizationalChartOutputConnections', [
            'className'  => 'OrganizationalChartConnections',
            'foreignKey' => 'organizational_chart_output_node_id'
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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('organizational_chart_id')
            ->allowEmptyString('organizational_chart_id');

        $validator
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->integer('containertype_id')
            ->requirePresence('containertype_id', 'create')
            ->allowEmptyString('containertype_id', null, false)
            ->greaterThanOrEqual('containertype_id', 1)
            ->inList('containertype_id', [CT_TENANT, CT_LOCATION, CT_NODE]);

        $validator
            ->allowEmptyString('recursive')
            ->boolean('recursive');

        $validator
            ->integer('x_position');
        //->greaterThanOrEqual('x_position', 0, __('X position must be greater than or equal to 0'));

        $validator
            ->integer('y_position');
        //->greaterThanOrEqual('y_position', 0, __('Y position must be greater than or equal to 0'));


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
        $rules->add($rules->existsIn('container_id', 'Containers'), ['errorField' => 'container_id']);
        $rules->add($rules->isUnique(['uuid']));

        return $rules;
    }

}
