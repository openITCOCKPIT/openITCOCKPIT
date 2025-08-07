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

use App\Lib\Interfaces\AcknowledgementServicesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Http\Exception\NotImplementedException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\AcknowledgedServiceConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use Statusengine2Module\Model\Entity\AcknowledgementService;

/**
 * AcknowledgementServicesTable Model
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementService findOrCreate($search, callable $callback = null, $options = [])
 */
class AcknowledgementServicesTable extends Table implements AcknowledgementServicesTableInterface {

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

        $this->setTable('nagios_acknowledgements');
        $this->setDisplayField('acknowledgement_id');
        $this->setPrimaryKey('acknowledgement_id');

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
     * @param string $uuid
     * @return AcknowledgementService|null
     */
    public function byUuid($uuid) {
        return $this->byServiceUuid($uuid);
    }

    /**
     * @param string $uuid
     * @return AcknowledgementService|null
     */
    public function byServiceUuid($uuid = null) {
        $query = $this->find()
            ->where([
                'Objects.name2'         => $uuid,
                'Objects.objecttype_id' => 2,
            ])
            ->contain([
                'Objects'
            ])
            ->orderBy([
                'entry_time' => 'DESC',
            ])
            ->first();

        return $query;
    }

    /**
     * @param AcknowledgedServiceConditions $AcknowledgedServiceConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getAcknowledgements(AcknowledgedServiceConditions $AcknowledgedServiceConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name2'         => $AcknowledgedServiceConditions->getServiceUuid(),
                'Objects.objecttype_id' => 2,
                'entry_time >'          => date('Y-m-d H:i:s', $AcknowledgedServiceConditions->getFrom()),
                'entry_time <'          => date('Y-m-d H:i:s', $AcknowledgedServiceConditions->getTo())
            ])
            ->orderBy($AcknowledgedServiceConditions->getOrder());

        if ($AcknowledgedServiceConditions->hasConditions()) {
            $query->andWhere($AcknowledgedServiceConditions->getConditions());
        }

        if (!empty($AcknowledgedServiceConditions->getStates())) {
            $query->andWhere([
                'state IN' => $AcknowledgedServiceConditions->getStates()
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

    /**
     * @param $uuids
     * @return mixed
     */
    public function byUuids($uuids) {
        throw new NotImplementedException();
    }
}
