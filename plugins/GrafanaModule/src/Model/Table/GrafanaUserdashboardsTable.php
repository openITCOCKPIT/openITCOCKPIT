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

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Core\Plugin;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use GrafanaModule\Model\Entity\GrafanaUserdashboard;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GrafanaUserDashboardFilter;

/**
 * GrafanaUserdashboards Model
 *
 * @property ContainersTable&BelongsTo $Containers
 * @property ConfigurationsTable&BelongsTo $Configurations
 *
 * @method GrafanaUserdashboard get($primaryKey, $options = [])
 * @method GrafanaUserdashboard newEntity($data = null, array $options = [])
 * @method GrafanaUserdashboard[] newEntities(array $data, array $options = [])
 * @method GrafanaUserdashboard|false save(EntityInterface $entity, $options = [])
 * @method GrafanaUserdashboard saveOrFail(EntityInterface $entity, $options = [])
 * @method GrafanaUserdashboard patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method GrafanaUserdashboard[] patchEntities($entities, array $data, array $options = [])
 * @method GrafanaUserdashboard findOrCreate($search, callable $callback = null, $options = [])
 */
class GrafanaUserdashboardsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('grafana_userdashboards');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER',
            'className'  => 'Containers',
        ]);

        $this->belongsTo('GrafanaConfigurations', [
            'foreignKey' => 'configuration_id',
            'joinType'   => 'INNER',
            'className'  => 'GrafanaModule.GrafanaConfigurations',
        ]);

        $this->hasMany('GrafanaUserdashboardPanels', [
            'className'        => 'GrafanaModule.GrafanaUserdashboardPanels',
            'foreignKey'       => 'userdashboard_id',
            'dependent'        => true,
            'cascadeCallbacks' => true
        ]);
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->notEmptyString('name');

        $validator
            ->integer('container_id')
            ->greaterThan('container_id', 0)
            ->notEmptyString('container_id');

        $validator
            ->integer('configuration_id')
            ->greaterThan('configuration_id', 0)
            ->notEmptyString('configuration_id');

        $validator
            ->scalar('refresh')
            ->maxLength('refresh', 255)
            ->notEmptyString('refresh');

        $validator
            ->scalar('range')
            ->maxLength('range', 255)
            ->notEmptyString('range');

        $validator
            ->integer('configuration_id')
            ->greaterThan('configuration_id', -1)
            ->lessThan('configuration_id', 3)
            ->notEmptyString('configuration_id');

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
        $rules->add($rules->existsIn(['container_id'], 'Containers'));
        $rules->add($rules->isUnique(['name']));

        return $rules;
    }

    /**
     * @param GrafanaUserDashboardFilter $GrafanaUserDashboardFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @param bool $skipUnsyncDashboards
     * @return array
     */
    public function getGrafanaUserdashboardsIndex(GrafanaUserDashboardFilter $GrafanaUserDashboardFilter, $PaginateOMat = null, $MY_RIGHTS = [], $skipUnsyncDashboards = false) {
        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where($GrafanaUserDashboardFilter->indexFilter());#
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'GrafanaUserdashboards.container_id IN' => $MY_RIGHTS
            ]);
        }

        if ($skipUnsyncDashboards) {
            $query->andWhere([
                'grafana_url IS NOT NULL',
                'grafana_url !=' => ''

            ]);
        }

        $query->orderBy($GrafanaUserDashboardFilter->getOrderForPaginator('GrafanaUserdashboards.name', 'asc'));
        $query->disableHydration();


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
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['GrafanaUserdashboards.id' => $id]);
    }

    /**
     * @param $id
     * @return array
     */
    public function getGrafanaUserDashboardEdit($id) {
        $query = $this->find()
            ->contain([
                'GrafanaUserdashboardPanels' => function (Query $query) {
                    $query->contain([
                        'GrafanaUserdashboardMetrics' => function (Query $query) {
                            $query->contain([
                                'Hosts'    => function (Query $query) {
                                    $query->disableAutoFields();
                                    $query->select([
                                        'id',
                                        'name',
                                        'uuid'
                                    ]);
                                    return $query;
                                },
                                'Services' => function (Query $query) {
                                    $query->disableAutoFields();
                                    $query->select([
                                        'id',
                                        'name',
                                        'uuid',
                                        'service_type'
                                    ]);

                                    if (Plugin::isLoaded('PrometheusModule')) {
                                        $query->contain('PrometheusAlertRules', function (Query $query) {
                                            return $query
                                                ->disableAutoFields()
                                                ->select([
                                                    'id',
                                                    'host_id',
                                                    'service_id',
                                                    'promql',
                                                    'unit',
                                                    'threshold_type',
                                                    'warning_min',
                                                    'warning_max',
                                                    'critical_min',
                                                    'critical_max',
                                                    'warning_longer_as',
                                                    'critical_longer_as',
                                                    'warning_operator',
                                                    'critical_operator'
                                                ]);
                                        });
                                    }

                                    $query->contain([
                                        'Servicetemplates' => function (Query $query) {
                                            $query->disableAutoFields();
                                            $query->select([
                                                'id',
                                                'name'
                                            ]);
                                            return $query;
                                        }
                                    ]);
                                    return $query;
                                }
                            ]);
                            return $query;
                        }
                    ])
                        ->orderBy([
                            'GrafanaUserdashboardPanels.row' => 'ASC',
                            'GrafanaUserdashboardPanels.id'  => 'ASC'
                        ]);
                    return $query;
                },
            ])
            ->where(['GrafanaUserdashboards.id' => $id])
            ->disableHydration();


        $result = $query->first();
        return $result;
    }

    /**
     * @param array $findResult
     * @return array
     */
    public function extractRowsWithPanelsAndMetricsFromFindResult($findResult) {
        $rowsWithPanelsAndMetrics = [];
        foreach ($findResult['grafana_userdashboard_panels'] as $k => $panel) {
            $rowsWithPanelsAndMetrics[$panel['row']][$k] = [
                'id'                 => $panel['id'],
                'userdashboard_id'   => $panel['userdashboard_id'],
                'row'                => $panel['row'],
                'unit'               => $panel['unit'],
                'title'              => $panel['title'],
                'visualization_type' => $panel['visualization_type'],
                'stacking_mode'      => $panel['stacking_mode'],
                'metrics'            => []
            ];
            foreach ($panel['grafana_userdashboard_metrics'] as $metric) {
                $metric['servicetemplate'] = [];
                if (isset($metric['service']['servicetemplate'])) {
                    $metric['Servicetemplate'] = $metric['service']['servicetemplate'];
                }
                $host = new \itnovum\openITCOCKPIT\Core\Views\Host($metric['host']);
                $service = new \itnovum\openITCOCKPIT\Core\Views\Service($metric['service']);
                $metric['Host'] = $host->toArray();
                $metric['Service'] = $service->toArray();
                $rowsWithPanelsAndMetrics[$panel['row']][$k]['metrics'][] = $metric;
            };
        }
        return $rowsWithPanelsAndMetrics;
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param array $MY_RIGHTS
     * @param array $where
     * @return array
     */
    public function getGrafanaUserDashboardsByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'GrafanaUserdashboards.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'GrafanaUserdashboards.' . $index,
            'GrafanaUserdashboards.name'
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'GrafanaUserdashboards.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        if ($type === 'all') {
            return $result;
        }

        $list = [];
        foreach ($result as $row) {
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }

    public function getGrafanaUserdashboardsWithPanelsAndMetricsById($id) {
        $query = $this->find();
        $query->innerJoin(['GrafanaUserdashboardPanels' => 'grafana_userdashboard_panels'], [
            'GrafanaUserdashboardPanels.userdashboard_id = GrafanaUserdashboards.id'
        ])
            ->innerJoin(['GrafanaUserdashboardMetrics' => 'grafana_userdashboard_metrics'], [
                'GrafanaUserdashboardMetrics.panel_id = GrafanaUserdashboardPanels.id',
            ])
            ->innerJoin(['Services' => 'services'], [
                'Services.id = GrafanaUserdashboardMetrics.service_id',
            ])
            ->where([
                    'GrafanaUserdashboards.id' => $id,
                    'Services.disabled'        => 0
                ]
            );

        return $query->first();
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getGrafanaUserdashboardsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'GrafanaUserdashboards.id',
                'GrafanaUserdashboards.name'
            ])
            ->where(['GrafanaUserdashboards.id IN' => $ids])
            ->orderBy(['GrafanaUserdashboards.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'GrafanaUserdashboards.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param $sourceAutomapId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getSourceGrafanaUserdashboardForCopy($sourceAutomapId, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['GrafanaUserdashboards.id' => $sourceAutomapId]);
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'GrafanaUserdashboards.container_id IN' => $MY_RIGHTS
            ]);
        }

        $result = $query->firstOrFail();
        $dbResult = $this->emptyArrayIfNull($result->toArray());
        if (empty($dbResult)) {
            return [];
        }

        // Remove IDs of source elements
        $dashboard = [
            'container_id'     => $dbResult['container_id'],
            'configuration_id' => $dbResult['configuration_id']
        ];

        return $dashboard;
    }

    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedGrafanaUserdashboardsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }

    /**
     * @return array
     */
    public function getDashboardUuids(): array {
        $query = $this->find()
            ->select([
                'grafana_uid'
            ])
            ->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }
}
