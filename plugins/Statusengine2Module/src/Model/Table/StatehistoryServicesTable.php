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

namespace Statusengine2Module\Model\Table;

use App\Lib\Interfaces\StatehistoryServiceTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Http\Exception\NotImplementedException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\StatehistoryServiceConditions;
use Statusengine2Module\Model\Entity\StatehistoryService;

/**
 * StatehistoryService Model
 *
 * @property \Statusengine2Module\Model\Table\StatehistoryServicesTable|\Cake\ORM\Association\BelongsTo $Statehistories
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\StatehistoryService get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\StatehistoryService findOrCreate($search, callable $callback = null, $options = [])
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

        $this->setTable('nagios_statehistory');
        $this->setDisplayField('statehistory_id');
        $this->setPrimaryKey(['statehistory_id', 'state_time']);

        $this->belongsTo('Objects', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Statusengine2Module.Objects',
            'conditions' => [
                'Objects.objecttype_id' => 2
            ]
        ]);
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
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        //Readonly table
        return $rules;
    }

    /**
     * @param StatehistoryServiceConditions $StatehistoryServiceConditions
     * @param null $PaginateOMat
     * @param bool $enableHydration
     * @return array
     */
    public function getStatehistoryIndex(StatehistoryServiceConditions $StatehistoryServiceConditions, $PaginateOMat = null, $enableHydration = true) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name2'                     => $StatehistoryServiceConditions->getServiceUuid(),
                'StatehistoryServices.state_time >' => date('Y-m-d H:i:s', $StatehistoryServiceConditions->getFrom()),
                'StatehistoryServices.state_time <' => date('Y-m-d H:i:s', $StatehistoryServiceConditions->getTo())
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
                'StatehistoryServices.state_type IN' => $StatehistoryServiceConditions->getStateTypes()
            ]);
        }

        if ($StatehistoryServiceConditions->hardStateTypeAndOkState()) {
            $query->andWhere([
                'OR' => [
                    'StatehistoryServices.state_type' => 1,
                    'StatehistoryServices.state'      => 0
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
     * @return array
     */


    /**
     * @param StatehistoryServiceConditions $StatehistoryServiceConditions
     * @param bool $enableHydration
     * @return array|StatehistoryService|null
     */
    public function getLastRecord(StatehistoryServiceConditions $StatehistoryServiceConditions, $enableHydration = true) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name2'                      => $StatehistoryServiceConditions->getServiceUuid(),
                'StatehistoryServices.state_time <=' => date('Y-m-d H:i:s', $StatehistoryServiceConditions->getFrom())
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
        throw new NotImplementedException();
    }
}
