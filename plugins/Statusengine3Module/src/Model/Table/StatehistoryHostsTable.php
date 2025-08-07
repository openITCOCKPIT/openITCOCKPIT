<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace Statusengine3Module\Model\Table;

use App\Lib\Interfaces\StatehistoryHostTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\StatehistoryHostConditions;
use Statusengine3Module\Model\Entity\StatehistoryHost;

/**
 * StatehistoryHost Model
 *
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryHost[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class StatehistoryHostsTable extends Table implements StatehistoryHostTableInterface {

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statusengine_host_statehistory');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'state_time']);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        //Readonly table
        return $validator;
    }

    /**
     * @param StatehistoryHostConditions $StatehistoryHostConditions
     * @param null $PaginateOMat
     * @param bool $enableHydration
     * @return array
     */
    public function getStatehistoryIndex(StatehistoryHostConditions $StatehistoryHostConditions, $PaginateOMat = null, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'StatehistoryHosts.hostname'     => $StatehistoryHostConditions->getHostUuid(),
                'StatehistoryHosts.state_time >' => $StatehistoryHostConditions->getFrom(),
                'StatehistoryHosts.state_time <' => $StatehistoryHostConditions->getTo()
            ])
            ->orderBy($StatehistoryHostConditions->getOrder());

        if ($StatehistoryHostConditions->hasConditions()) {
            $query->andWhere($StatehistoryHostConditions->getConditions());
        }

        if (!empty($StatehistoryHostConditions->getStates())) {
            $query->andWhere([
                'StatehistoryHosts.state IN' => $StatehistoryHostConditions->getStates()
            ]);
        }


        if (!empty($StatehistoryHostConditions->getStateTypes())) {
            $query->andWhere([
                'StatehistoryHosts.is_hardstate IN' => $StatehistoryHostConditions->getStateTypes()
            ]);
        }

        if ($StatehistoryHostConditions->hardStateTypeAndUpState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryHosts.is_hardstate' => 1,
                    'StatehistoryHosts.state'        => 0
                ]
            ]);
        }
        $query->enableHydration($enableHydration);

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->emptyArrayIfNull($query->toArray());
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
     * @param StatehistoryHostConditions $StatehistoryHostConditions
     * @param bool $enableHydrationgetPercentageValues
     * @return array|StatehistoryHost|null
     */
    public function getLastRecord(StatehistoryHostConditions $StatehistoryHostConditions, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'StatehistoryHosts.hostname'      => $StatehistoryHostConditions->getHostUuid(),
                'StatehistoryHosts.state_time <=' => $StatehistoryHostConditions->getFrom()
            ])
            ->orderBy([
                'StatehistoryHosts.state_time' => 'DESC'
            ])
            ->enableHydration($enableHydration)
            ->first();

        return $query;
    }

    /**
     * @param StatehistoryHostConditions $StatehistoryHostConditions
     * @return \itnovum\openITCOCKPIT\Core\Views\StatehistoryHost[]
     */
    public function getRecordsForReporting(StatehistoryHostConditions $StatehistoryHostConditions) {
        $statehistoryRecords = [];
        $query = $this->find()
            ->where([
                'StatehistoryHosts.hostname'      => $StatehistoryHostConditions->getHostUuid(),
                'StatehistoryHosts.state_time <=' => $StatehistoryHostConditions->getFrom()
            ]);
        if ($StatehistoryHostConditions->hardStateTypeAndUpState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryHosts.is_hardstate' => 1,
                    'StatehistoryHosts.state'      => 0
                ]
            ]);
        }
        $query->orderBy([
            'StatehistoryHosts.state_time' => 'DESC'
        ])
            ->disableHydration();

        $result = $query->first();
        if (!empty($result)) {
            $statehistoryRecords[] = new \itnovum\openITCOCKPIT\Core\Views\StatehistoryHost($result);
        }

        $query = $this->find()
            ->where([
                'StatehistoryHosts.hostname'     => $StatehistoryHostConditions->getHostUuid(),
                'StatehistoryHosts.state_time >' => $StatehistoryHostConditions->getFrom()
            ]);
        if ($StatehistoryHostConditions->hardStateTypeAndUpState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryHosts.is_hardstate' => 1,
                    'StatehistoryHosts.state'      => 0
                ]
            ]);
        }
        $query->orderBy([
            'StatehistoryHosts.state_time' => 'ASC'
        ])
            ->disableHydration();

        $results = $query->all();

        foreach($results as $result){
            $statehistoryRecords[] = new \itnovum\openITCOCKPIT\Core\Views\StatehistoryHost($result);
        }

        return $statehistoryRecords;
    }
}
