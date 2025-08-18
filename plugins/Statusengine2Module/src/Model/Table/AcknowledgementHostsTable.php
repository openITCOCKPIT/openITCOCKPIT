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

use App\Lib\Interfaces\AcknowledgementHostsTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Http\Exception\NotImplementedException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\AcknowledgedHostConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use Statusengine2Module\Model\Entity\AcknowledgementHost;

/**
 * AcknowledgementHosts Model
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\AcknowledgementHost findOrCreate($search, callable $callback = null, $options = [])
 */
class AcknowledgementHostsTable extends Table implements AcknowledgementHostsTableInterface {

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
                'Objects.objecttype_id' => 1
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
     * @return AcknowledgementHost|null
     */
    public function byUuid($uuid) {
        return $this->byHostUuid($uuid);
    }

    /**
     * @param string $uuid
     * @return AcknowledgementHost|null
     */
    public function byHostUuid($uuid = null) {
        $query = $this->find()
            ->where([
                'Objects.name1'         => $uuid,
                'Objects.objecttype_id' => 1,
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
     * @param AcknowledgedHostConditions $AcknowledgedHostConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getAcknowledgements(AcknowledgedHostConditions $AcknowledgedHostConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->contain([
                'Objects'
            ])
            ->where([
                'Objects.name1'         => $AcknowledgedHostConditions->getHostUuid(),
                'Objects.objecttype_id' => 1,
                'entry_time >'          => date('Y-m-d H:i:s', $AcknowledgedHostConditions->getFrom()),
                'entry_time <'          => date('Y-m-d H:i:s', $AcknowledgedHostConditions->getTo())
            ])
            ->orderBy($AcknowledgedHostConditions->getOrder());

        if ($AcknowledgedHostConditions->hasConditions()) {
            $query->andWhere($AcknowledgedHostConditions->getConditions());
        }

        if (!empty($AcknowledgedHostConditions->getStates())) {
            $query->andWhere([
                'state IN' => $AcknowledgedHostConditions->getStates()
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
     * @param array $uuids
     * @return mixed
     */
    public function byUuids($uuids) {
        throw new NotImplementedException();
    }

}
