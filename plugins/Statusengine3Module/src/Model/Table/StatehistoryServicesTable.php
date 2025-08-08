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

use App\Lib\Interfaces\StatehistoryServiceTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\StatehistoryServiceConditions;
use Statusengine3Module\Model\Entity\StatehistoryService;

/**
 * StatehistoryService Model
 *
 * @method \Statusengine3Module\Model\Entity\StatehistoryService newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\StatehistoryService newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\StatehistoryService[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class StatehistoryServicesTable extends Table implements StatehistoryServiceTableInterface {

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

        $this->setTable('statusengine_service_statehistory');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'service_description', 'state_time']);
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
     * @param StatehistoryServiceConditions $StatehistoryServiceConditions
     * @param null $PaginateOMat
     * @param bool $enableHydration
     * @return array
     */
    public function getStatehistoryIndex(StatehistoryServiceConditions $StatehistoryServiceConditions, $PaginateOMat = null, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'StatehistoryServices.service_description' => $StatehistoryServiceConditions->getServiceUuid(),
                'StatehistoryServices.state_time >'        => $StatehistoryServiceConditions->getFrom(),
                'StatehistoryServices.state_time <'        => $StatehistoryServiceConditions->getTo()
            ])
            ->orderBy($StatehistoryServiceConditions->getOrder());

        if ($StatehistoryServiceConditions->hasConditions()) {
            $query->andWhere($StatehistoryServiceConditions->getConditions());
        }

        if (!empty($StatehistoryServiceConditions->getStates())) {
            $query->andWhere([
                'StatehistoryServices.state IN' => $StatehistoryServiceConditions->getStates()
            ]);
        }


        if (!empty($StatehistoryServiceConditions->getStateTypes())) {
            $query->andWhere([
                'StatehistoryServices.is_hardstate IN' => $StatehistoryServiceConditions->getStateTypes()
            ]);
        }

        if ($StatehistoryServiceConditions->hardStateTypeAndOkState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryServices.is_hardstate' => 1,
                    'StatehistoryServices.state'        => 0
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
     * @param StatehistoryServiceConditions $StatehistoryServiceConditions
     * @param bool $enableHydration
     * @return array|StatehistoryService|null
     */
    public function getLastRecord(StatehistoryServiceConditions $StatehistoryServiceConditions, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'StatehistoryServices.service_description' => $StatehistoryServiceConditions->getServiceUuid(),
                'StatehistoryServices.state_time <='       => $StatehistoryServiceConditions->getFrom()
            ])
            ->orderBy([
                'StatehistoryServices.state_time' => 'DESC'
            ])
            ->enableHydration($enableHydration)
            ->first();

        return $query;
    }

    /**
     * @param StatehistoryServiceConditions $StatehistoryServiceConditions
     * @return \itnovum\openITCOCKPIT\Core\Views\StatehistoryService[]
     */
    public function getRecordsForReporting(StatehistoryServiceConditions $StatehistoryServiceConditions) {
        $statehistoryRecords = [];
        $query = $this->find()
            ->where([
                'StatehistoryServices.service_description' => $StatehistoryServiceConditions->getServiceUuid(),
                'StatehistoryServices.state_time <='       => $StatehistoryServiceConditions->getFrom()
            ]);
        if ($StatehistoryServiceConditions->hardStateTypeAndOkState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryServices.is_hardstate' => 1,
                    'StatehistoryServices.state'        => 0
                ]
            ]);
        }
        $query->orderBy([
            'StatehistoryServices.state_time' => 'DESC'
        ])
            ->disableHydration();

        $result = $query->first();
        if (!empty($result)) {
            $statehistoryRecords[] = new \itnovum\openITCOCKPIT\Core\Views\StatehistoryService($result);
        }

        $query = $this->find()
            ->where([
                'StatehistoryServices.service_description' => $StatehistoryServiceConditions->getServiceUuid(),
                'StatehistoryServices.state_time >'        => $StatehistoryServiceConditions->getFrom()
            ]);
        if ($StatehistoryServiceConditions->hardStateTypeAndOkState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryServices.is_hardstate' => 1,
                    'StatehistoryServices.state'        => 0
                ]
            ]);
        }
        $query->orderBy([
            'StatehistoryServices.state_time' => 'ASC'
        ])
            ->disableHydration();

        $results = $query->all();

        foreach ($results as $result) {
            $statehistoryRecords[] = new \itnovum\openITCOCKPIT\Core\Views\StatehistoryService($result);
        }

        return $statehistoryRecords;
    }
}
