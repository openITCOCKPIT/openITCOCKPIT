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

use App\Lib\Interfaces\LogentriesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Database\Expression\Comparison;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\LogentryConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\LogentryFilter;

/**
 * Logentries Model
 *
 * @method \Statusengine3Module\Model\Entity\Logentry newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\Logentry newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Logentry[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
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

        $this->setTable('statusengine_logentries');
        $this->setDisplayField('id');
        $this->setPrimaryKey(['id', 'entry_time']);
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
     * @param LogentryFilter $LogentryFilter
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getLogentries(LogentryFilter $LogentryFilter, $PaginateOMat = null, LogentryConditions $LogentryConditions = null) {
        //Get all user ids where container assigned are made directly at the user
        $query = $this->find()
            ->where($LogentryFilter->indexFilter())
            ->orderBy($LogentryFilter->getOrderForPaginator('Logentries.entry_time', 'desc'));

        if (!empty($LogentryFilter->getMatchingUuids())) {
            //Refactoring: to avoid "Timeout exceeded in regular expression match." error
            /*
            $query->andWhere(new Comparison(
                'Logentries.logentry_data',
                sprintf('.*(%s).*', implode('|', $LogentryFilter->getMatchingUuids())),
                'string',
                'RLIKE'
            ));
            */
            $comparisons = [];
            foreach ($LogentryFilter->getMatchingUuids() as $uuid) {
                $comparisons[] = new Comparison(
                    'Logentries.logentry_data',
                    sprintf('%%%s%%', $uuid),
                    'string',
                    'LIKE'
                );
            }
            $query->where([
                'OR' =>
                    $comparisons
            ]);
        }

        $query->where([
            'Logentries.entry_time >' => $LogentryConditions->getFrom(),
            'Logentries.entry_time <' => $LogentryConditions->getTo()
        ]);

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
