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

use App\Lib\Interfaces\ServicechecksTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServicechecksConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * ServicechecksTable Model
 *
 * @property \Statusengine2Module\Model\Table\ServicechecksTable|\Cake\ORM\Association\BelongsTo $Servicechecks
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $ServiceObjects
 *
 * @method \Statusengine2Module\Model\Entity\Servicecheck get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicecheck findOrCreate($search, callable $callback = null, $options = [])
 */
class ServicechecksTable extends Table implements ServicechecksTableInterface {

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
    public function initialize(array $config) :void {
        parent::initialize($config);

        $this->setTable('nagios_servicechecks');
        $this->setDisplayField('servicecheck_id');
        $this->setPrimaryKey(['servicecheck_id', 'start_time']);

        $this->belongsTo('Objects', [
            'foreignKey' => 'service_object_id',
            'joinType'   => 'INNER',
            'className'  => 'Statusengine2Module.Objects'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) :Validator {
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
    public function buildRules(RulesChecker $rules) :RulesChecker {
        //Readonly table
        return $rules;
    }

    /**
     * @param ServicechecksConditions $ServicechecksConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getServicechecks(ServicechecksConditions $ServicechecksConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name2'              => $ServicechecksConditions->getServiceUuid(),
                'Servicechecks.start_time >' => date('Y-m-d H:i:s', $ServicechecksConditions->getFrom()),
                'Servicechecks.start_time <' => date('Y-m-d H:i:s', $ServicechecksConditions->getTo())
            ])
            ->orderBy($ServicechecksConditions->getOrder());

        if ($ServicechecksConditions->hasConditions()) {
            $query->andWhere($ServicechecksConditions->getConditions());
        }

        if (!empty($ServicechecksConditions->getStates())) {
            $query->andWhere([
                'Servicechecks.state IN' => $ServicechecksConditions->getStates()
            ]);
        }

        if (!empty($ServicechecksConditions->getStateTypes())) {
            $query->andWhere([
                'Servicechecks.state_type IN' => $ServicechecksConditions->getStateTypes()
            ]);
        }

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
}
