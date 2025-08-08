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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace GrafanaModule\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use GrafanaModule\Model\Entity\GrafanaUserdashboardMetric;

/**
 * GrafanaUserdashboardMetrics Model
 *
 * @property PanelsTable&BelongsTo $Panels
 * @property HostsTable&BelongsTo $Hosts
 * @property ServicesTable&BelongsTo $Services
 *
 * @method GrafanaUserdashboardMetric get($primaryKey, $options = [])
 * @method GrafanaUserdashboardMetric newEntity($data = null, array $options = [])
 * @method GrafanaUserdashboardMetric[] newEntities(array $data, array $options = [])
 * @method GrafanaUserdashboardMetric|false save(EntityInterface $entity, $options = [])
 * @method GrafanaUserdashboardMetric saveOrFail(EntityInterface $entity, $options = [])
 * @method GrafanaUserdashboardMetric patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method GrafanaUserdashboardMetric[] patchEntities($entities, array $data, array $options = [])
 * @method GrafanaUserdashboardMetric findOrCreate($search, callable $callback = null, $options = [])
 */
class GrafanaUserdashboardMetricsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('grafana_userdashboard_metrics');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('GrafanaUserdashboardPanels', [
            'foreignKey' => 'panel_id',
            'joinType'   => 'INNER',
            'className'  => 'GrafanaModule.GrafanaUserdashboardPanels',
        ]);
        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
            'className'  => 'Hosts',
        ]);
        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
            'joinType'   => 'INNER',
            'className'  => 'Services',
        ]);
    }

    /**
     * Bind Core2Plugin associations
     *
     * @param RepositoryInterface $coreTable
     */
    public function bindCoreAssociations(Table $coreTable) {
        switch ($coreTable->getAlias()) {
            case 'Hosts':
                if (!$coreTable->hasAssociation('GrafanaUserdashboardMetrics')) {
                    $coreTable->hasOne('GrafanaUserdashboardMetrics', [
                        'className'  => 'GrafanaModule.GrafanaUserdashboardMetrics',
                        'foreignKey' => 'host_id',
                        'dependent'  => true
                    ]);
                }
                break;

            case 'Services':
                if (!$coreTable->hasAssociation('GrafanaUserdashboardMetrics')) {
                    $coreTable->hasOne('GrafanaUserdashboardMetrics', [
                        'className'  => 'GrafanaModule.GrafanaUserdashboardMetrics',
                        'foreignKey' => 'service_id',
                        'dependent'  => true
                    ]);
                }
                break;
        }
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('metric')
            ->maxLength('metric', 255)
            ->requirePresence('metric', 'create')
            ->notEmptyString('metric');

        $validator
            ->integer('panel_id')
            ->greaterThan('panel_id', 0)
            ->notEmptyString('panel_id');

        $validator
            ->integer('host_id')
            ->greaterThan('hopst_id', 0)
            ->notEmptyString('host_id');

        $validator
            ->integer('service_id')
            ->greaterThan('service_id', 0)
            ->notEmptyString('service_id');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['panel_id'], 'GrafanaUserdashboardPanels'));
        $rules->add($rules->existsIn(['host_id'], 'Hosts'));
        $rules->add($rules->existsIn(['service_id'], 'Services'));

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['GrafanaUserdashboardMetrics.id' => $id]);
    }

    /**
     * @param int $panelId
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getMetricsByPanelIdForCopy(int $panelId) {
        $result = $this->find()
            ->where([
                'GrafanaUserdashboardMetrics.panel_id' => $panelId
            ])
            ->all();
        return $result;
    }

}
