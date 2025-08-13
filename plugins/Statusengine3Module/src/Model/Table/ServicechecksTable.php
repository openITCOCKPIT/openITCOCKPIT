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

use App\Lib\Interfaces\ServicechecksTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServicechecksConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Servicechecks Model
 *
 * @method \Statusengine3Module\Model\Entity\Servicecheck newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\Servicecheck newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Servicecheck[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
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
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statusengine_servicechecks');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey(['hostname', 'service_description', 'start_time']);
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
     * @param ServicechecksConditions $ServicechecksConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getServicechecks(ServicechecksConditions $ServicechecksConditions, $PaginateOMat = null) {
        $query = $this->find()
            ->where([
                'Servicechecks.service_description' => $ServicechecksConditions->getServiceUuid(),
                'Servicechecks.start_time >'        => $ServicechecksConditions->getFrom(),
                'Servicechecks.start_time <'        => $ServicechecksConditions->getTo()
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
                'Servicechecks.is_hardstate IN' => $ServicechecksConditions->getStateTypes()
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
