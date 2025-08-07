<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\CalendarFilter;

/**
 * Calendars Model
 *
 * @property |\Cake\ORM\Association\BelongsTo $Containers
 * @property |\Cake\ORM\Association\HasMany $Autoreports
 * @property \App\Model\Table\CalendarHolidaysTable|\Cake\ORM\Association\HasMany $CalendarHolidays
 * @property |\Cake\ORM\Association\HasMany $Timeperiods
 *
 * @method \App\Model\Entity\Calendar get($primaryKey, $options = [])
 * @method \App\Model\Entity\Calendar newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Calendar[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Calendar|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Calendar saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Calendar patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Calendar[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Calendar findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CalendarsTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('calendars');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->hasMany('CalendarHolidays', [
            'foreignKey'   => 'calendar_id',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsTo('Timeperiods', [
            'foreignKey' => 'calendar_id',
            'joinType'   => 'LEFT'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->allowEmptyString('name', null, false)
            ->add('name', 'unique', [
                'rule'     => 'validateUnique',
                'provider' => 'table',
                'message'  => __('This calendar name has already been taken.')
            ]);

        $validator
            ->scalar('container_id')
            ->allowEmptyString('container_id', null, false)
            ->greaterThan('container_id', 0);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

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
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param CalendarFilter $CalendarFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getCalendarsIndex(CalendarFilter $CalendarFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find();

        $where = $CalendarFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['Calendars.container_id IN'] = $MY_RIGHTS;
        }
        $query->where($where);

        $query->orderBy($CalendarFilter->getOrderForPaginator('Calendars.name', 'asc'));

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
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Calendars.id' => $id]);
    }


    /**
     * @param int $id
     * @return array|\Cake\Datasource\EntityInterface
     * @throws RecordNotFoundException
     */
    public function getCalendarById($id) {
        return $this->find()
            ->contain([
                'Containers',
                'CalendarHolidays'
            ])
            ->where([
                'Calendars.id' => $id
            ])
            ->firstOrFail();
    }

    /**
     * @param int|array $containerId
     * @param string $type
     * @return array|\Cake\Datasource\EntityInterface
     * @throws RecordNotFoundException
     */
    public function getCalendarsByContainerIds($containerIds, $type = 'all') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $containerIds = array_unique($containerIds);

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $tenantContainerIds = [];
        foreach ($containerIds as $containerId) {
            if ($containerId != ROOT_CONTAINER) {
                $path = $ContainersTable->getPathByIdAndCacheResult($containerId, 'CalendarCalendarsByContainerIds');
                // Get container id of the tenant container
                // Tenant timeperiods are available for all users of a tenant (oITC V2 legacy)
                if (isset($path[1])) {
                    $tenantContainerIds[] = $path[1]['id'];
                }
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);

        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));
        if (empty($containerIds)) {
            return [];
        }

        $query = $this->find('all')
            ->contain([
                'Containers'
            ])
            ->where([
                'Calendars.container_id IN' => $containerIds
            ])
            ->disableHydration();

        if ($type === 'all') {
            return $this->formatResultAsCake2($query->toArray());
        }

        return $this->formatListAsCake2($query->toArray());
    }

    public function getCalendarByIdForEdit($id) {
        $result = $this->find()
            ->contain([
                'CalendarHolidays'
            ])
            ->where([
                'Calendars.id' => $id
            ])
            ->disableHydration()
            ->firstOrFail();

        return $result;
    }
}
