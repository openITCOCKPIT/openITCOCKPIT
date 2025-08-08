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

use App\Lib\Interfaces\HostchecksTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\HostcheckConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * HostchecksTable Model
 *
 * @link http://nagios.sourceforge.net/docs/ndoutils/NDOUtils_DB_Model.pdf
 *
 * @property \Statusengine2Module\Model\Table\HostchecksTable|\Cake\ORM\Association\BelongsTo $Hostchecks
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $HostObjects
 *
 * @method \Statusengine2Module\Model\Entity\Hostcheck get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Hostcheck findOrCreate($search, callable $callback = null, $options = [])
 */
class HostchecksTable extends Table implements HostchecksTableInterface {

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

        $this->setTable('nagios_hostchecks');
        $this->setDisplayField('hostcheck_id');
        $this->setPrimaryKey(['hostcheck_id', 'start_time']);

        $this->belongsTo('HostObjects', [
            'foreignKey' => 'host_object_id',
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
     * @param HostcheckConditions $HostcheckConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getHostchecks(HostcheckConditions $HostcheckConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->contain([
                'HostObjects'
            ])
            ->where([
                'HostObjects.name1' => $HostcheckConditions->getHostUuid(),
                'Hostchecks.start_time >'  => date('Y-m-d H:i:s', $HostcheckConditions->getFrom()),
                'Hostchecks.start_time <'  => date('Y-m-d H:i:s', $HostcheckConditions->getTo())
            ])
            ->orderBy($HostcheckConditions->getOrder());

        if($HostcheckConditions->hasConditions()){
            $query->andWhere($HostcheckConditions->getConditions());
        }

        if (!empty($HostcheckConditions->getStates())) {
            $query->andWhere([
                'Hostchecks.state IN' => $HostcheckConditions->getStates()
            ]);
        }

        if (!empty($HostcheckConditions->getStateTypes())) {
            $query->andWhere([
                'Hostchecks.state_type IN' => $HostcheckConditions->getStateTypes()
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
