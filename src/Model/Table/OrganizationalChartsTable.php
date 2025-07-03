<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;


/**
 * OrganizationalCharts Model
 *
 * @property \App\Model\Table\OrganizationalChartNodesTable&\Cake\ORM\Association\HasMany $OrganizationalChartNodes
 *
 * @method \App\Model\Entity\OrganizationalChart newEmptyEntity()
 * @method \App\Model\Entity\OrganizationalChart newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart get($primaryKey, $options = [])
 * @method \App\Model\Entity\OrganizationalChart findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\OrganizationalChart patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChart saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrganizationalChartsTable extends Table {
    use PaginationAndScrollIndexTrait;
    use CustomValidationTrait;


    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('organizational_charts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('OrganizationalChartNodes', [
            'foreignKey'       => 'organizational_chart_id',
            'cascadeCallbacks' => true,
        ])->setDependent(true);

        $this->hasMany('OrganizationalChartConnections', [
            'foreignKey' => 'organizational_chart_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        return $validator;
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getOrganizationalChartsIndex(GenericFilter $GenericFilter, PaginateOMat $PaginateOMat, array $MY_RIGHTS) {

        $query = $this->find('all')
            ->select([
                'OrganizationalCharts.id',
                'OrganizationalCharts.name',
                'OrganizationalCharts.description',
                'OrganizationalCharts.created',
                'OrganizationalCharts.modified',
            ])
            ->contain(['OrganizationalChartNodes' => 'Containers']);
        if (!empty($MY_RIGHTS)) {
            $query->select([
                'permission_status' => $query->newExpr(
                    'IF(
                        COUNT(`OrganizationalChartNodes`.`id`) > 0,
                        IF(
                          NOT EXISTS (
                            SELECT ocs.organizational_chart_id
                            FROM `organizational_chart_nodes` ocs
                            WHERE ocs.organizational_chart_id = `OrganizationalCharts`.`id`
                              AND ocs.container_id IN (' . implode(',', $MY_RIGHTS) . ')
                          ),
                          "not_permitted",
                          "permitted"
                        ),
                        "permitted"
                      )'
                )
            ])->join([
                [
                    'table'      => 'organizational_chart_nodes',
                    'alias'      => 'OrganizationalChartNodes',
                    'type'       => 'LEFT',
                    'conditions' => [
                        'OrganizationalCharts.id = OrganizationalChartNodes.organizational_chart_id',
                    ],
                ]
            ]);
            $query->having([
                'permission_status' => 'permitted'
            ])->group(['OrganizationalCharts.id']);
        }

        $query->disableHydration();
        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }
        $query->disableHydration();
        $query->order($GenericFilter->getOrderForPaginator('OrganizationalCharts.name', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }
        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['OrganizationalCharts.id' => $id]);
    }
}
