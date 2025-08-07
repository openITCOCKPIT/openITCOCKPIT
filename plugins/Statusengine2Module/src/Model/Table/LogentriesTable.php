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

namespace Statusengine2Module\Model\Table;

use App\Lib\Interfaces\LogentriesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Database\Expression\Comparison;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\AcknowledgedHostConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\LogentryFilter;

/**
 * NagiosLogentries Model
 *
 * @property \Statusengine2Module\Model\Table\LogentriesTable&\Cake\ORM\Association\BelongsTo $Logentries
 *
 * @method \Statusengine2Module\Model\Entity\Logentry get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Logentry findOrCreate($search, callable $callback = null, $options = [])
 */
class LogentriesTable extends Table implements LogentriesTableInterface {

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

        $this->setTable('nagios_logentries');
        $this->setDisplayField('logentry_id');
        $this->setPrimaryKey(['logentry_id']);
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
        return $rules;
    }

    /**
     * @param LogentryFilter $LogentryFilter
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getLogentries(LogentryFilter $LogentryFilter, $PaginateOMat = null) {
        //Get all user ids where container assigned are made directly at the user
        $query = $this->find()
            ->where($LogentryFilter->indexFilter())
            ->orderBy($LogentryFilter->getOrderForPaginator('Logentries.entry_time', 'desc'));

        if(!empty($LogentryFilter->getMatchingUuids())){
            $query->andWhere(new Comparison(
                'Logentries.logentry_data',
                sprintf('.*(%s).*', implode('|', $LogentryFilter->getMatchingUuids())),
                'string',
                'RLIKE'
            ));
        }

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
}
