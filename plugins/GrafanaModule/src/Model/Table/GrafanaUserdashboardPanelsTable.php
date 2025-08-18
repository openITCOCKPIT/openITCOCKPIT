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
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use GrafanaModule\Model\Entity\GrafanaUserdashboardPanel;

/**
 * GrafanaUserdashboardPanels Model
 *
 * @property GrafanaUserdashboardsTable&BelongsTo $GrafanaUserdashboardsTable
 *
 * @method GrafanaUserdashboardPanel get($primaryKey, $options = [])
 * @method GrafanaUserdashboardPanel newEntity($data = null, array $options = [])
 * @method GrafanaUserdashboardPanel[] newEntities(array $data, array $options = [])
 * @method GrafanaUserdashboardPanel|false save(EntityInterface $entity, $options = [])
 * @method GrafanaUserdashboardPanel saveOrFail(EntityInterface $entity, $options = [])
 * @method GrafanaUserdashboardPanel patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method GrafanaUserdashboardPanel[] patchEntities($entities, array $data, array $options = [])
 * @method GrafanaUserdashboardPanel findOrCreate($search, callable $callback = null, $options = [])
 */
class GrafanaUserdashboardPanelsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('grafana_userdashboard_panels');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('GrafanaUserdashboards', [
            'foreignKey' => 'userdashboard_id',
            'joinType'   => 'INNER',
            'className'  => 'GrafanaModule.GrafanaUserdashboards',
        ]);

        $this->hasMany('GrafanaUserdashboardMetrics', [
            'foreignKey'       => 'panel_id',
            'className'        => 'GrafanaModule.GrafanaUserdashboardMetrics',
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
            ->integer('row')
            ->requirePresence('row', 'create')
            ->notEmptyString('row');

        $validator
            ->integer('userdashboard_id')
            ->requirePresence('userdashboard_id', 'create')
            ->notEmptyString('userdashboard_id')
            ->greaterThan('userdashboard_id', 0);


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
        $rules->add($rules->existsIn(['userdashboard_id'], 'GrafanaUserdashboards'));

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['GrafanaUserdashboardPanels.id' => $id]);
    }


    /**
     * @param $dashboardId
     * @return int
     */
    public function getNextRow($dashboardId) {
        try {
            $result = $this->find()
                ->where([
                    'GrafanaUserdashboardPanels.userdashboard_id' => $dashboardId
                ])
                ->orderBy([
                    'GrafanaUserdashboardPanels.row' => 'DESC'
                ])
                ->firstOrFail();
        } catch (RecordNotFoundException $e) {
            return 0;
        }

        return $result->get('row') + 1;
    }

    /**
     * @param int $dashboardId
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getPanelsByUserdashboardIdForCopy(int $dashboardId) {
        $result = $this->find()
            ->where([
                'GrafanaUserdashboardPanels.userdashboard_id' => $dashboardId
            ])
            ->all();

        return $result;
    }

    /**
     * Use this method to delete rows of a user defined grafana dashboard.
     * This function has to recalculate the "row index" therefore it is important that this function will not run in parallel.
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     *  VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *   ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param int $userdashboardId
     * @param int $rowIndex
     * @return bool
     */
    public function deleteRowByUserdashboardIdAndRowIndex(int $userdashboardId, int $rowIndex): bool {
        // "Rows" do not exist in the openITCOCKPIT database, they are a virtual construct of panels.
        // When we have 4 rows [0,1,2,3] and we want to remove row "1", we have to update the row index of all panels
        // on rows > 1. (decrement by 1)
        // So row 0 stays row 0, row 2 becomes row 1, row 3 becomes row 2.

        // Find all panels we want to delete
        $panels = $this->find()
            ->where([
                'GrafanaUserdashboardPanels.userdashboard_id' => $userdashboardId,
                'GrafanaUserdashboardPanels.row'              => $rowIndex
            ])
            ->all();

        // Delete all panels
        $success = $this->deleteMany($panels);
        if (!$success) {
            return $success;
        }

        // Select all panels with a row index > $rowIndex
        // To decrement the row index by 1
        $panels = $this->find()
            ->where([
                'GrafanaUserdashboardPanels.userdashboard_id' => $userdashboardId,
                'GrafanaUserdashboardPanels.row >'            => $rowIndex
            ])
            ->orderBy([
                'GrafanaUserdashboardPanels.row' => 'ASC'
            ])
            ->all();

        /** @var GrafanaUserdashboardPanel $panel */
        foreach ($panels as $panel) {
            if ($panel->row > 0) {
                $panel->set('row', $panel->get('row') - 1);
                $this->save($panel);
            }
        }

        return true;
    }

}
