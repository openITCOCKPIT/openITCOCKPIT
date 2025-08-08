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
use App\Model\Entity\Changelog;
use App\Model\Entity\Timeperiod;
use Cake\Core\Plugin;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\TimeperiodsFilter;

/**
 * Timeperiods Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\TimeperiodTimerangesTable|\Cake\ORM\Association\HasMany $TimeperiodTimeranges
 *
 * @method \App\Model\Entity\Timeperiod get($primaryKey, $options = [])
 * @method \App\Model\Entity\Timeperiod newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Timeperiod[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Timeperiod|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Timeperiod|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Timeperiod patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Timeperiod[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Timeperiod findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TimeperiodsTable extends Table {

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

        $this->setTable('timeperiods');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->hasMany('TimeperiodTimeranges', [
            'foreignKey'   => 'timeperiod_id',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsTo('Calendars', [
            'joinType' => 'LEFT'
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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('container_id')
            ->greaterThan('container_id', 0)
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name')
            ->allowEmptyString('name', null, false);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
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
        $rules->add($rules->isUnique(['uuid']));
        $rules->add($rules->existsIn(['container_id'], 'Containers'));
        /** @var $entity Entity */
        $rules->add(function ($entity, $options) {
            if (empty($entity->timeperiod_timeranges)) {
                return true;
            }
            if (!empty($entity->timeperiod_timeranges)) {
                $data = [];
                foreach ($entity->timeperiod_timeranges as $timeperiodTimerangEentity) {
                    $data[] = [
                        'day'   => $timeperiodTimerangEentity->day,
                        'start' => $timeperiodTimerangEentity->start,
                        'end'   => $timeperiodTimerangEentity->end
                    ];
                }
                $errors = $this->checkTimerangeOvelapping($data);
                if (!empty($errors)) {
                    $entity->setInvalidField('validate_timeranges', false);
                    $entity->setErrors([
                        'validate_timeranges' => $errors
                    ]);
                    return false;
                }
                return true;
            }
        }, 'validate_timeranges');

        return $rules;
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getAllTimeperiodsAsCake2($MY_RIGHTS) {
        $query = $this->find()
            ->where([
                'Timeperiods.container_id IN' => $MY_RIGHTS
            ])
            ->orderBy(['Timeperiods.name' => 'asc'])
            ->disableHydration()
            ->all();
        return $this->formatResultAsCake2($query->toArray(), false);
    }

    /**
     * @return array
     */
    public function getAllTimeperiodsUuidsAsList() {
        $query = $this->find('list',
        keyField: 'id',
        valueField: 'uuid')
            ->disableHydration();
        return $query->toArray();
    }

    /**
     * @param TimeperiodsFilter $TimeperiodsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getTimeperiodsIndex(TimeperiodsFilter $TimeperiodsFilter, $PaginateOMat = null) {
        $query = $this->find('all')->disableHydration();
        $query->where($TimeperiodsFilter->indexFilter());
        $query->orderBy($TimeperiodsFilter->getOrderForPaginator('Timeperiods.name', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @return array
     */
    public function getTimeperiodById($id) {
        $query = $this->find()
            ->where([
                'Timeperiods.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $id
     * @param bool $enableHydration
     * @return array
     */
    public function getTimeperiodByIdCake4($id, $enableHydration = true) {
        $query = $this->find()
            ->where([
                'Timeperiods.id' => $id
            ])
            ->enableHydration($enableHydration)
            ->first();
        return $query->toArray();
    }

    /**
     * @return array
     */
    public function getTimeperiodWithTimeranges() {
        $query = $this->find()
            ->contain('TimeperiodTimeranges')
            ->disableHydration();
        return $this->formatResultAsCake2($query->toArray(), false);
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getTimeperiodWithTimerangesById($id) {
        $query = $this->find()
            ->contain('TimeperiodTimeranges')
            ->where([
                'Timeperiods.id' => $id
            ])
            ->first();
        if (is_null($query)) {
            return null;
        }

        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param $uuid
     * @return array
     */
    public function getTimeperiodWithTimerangesByUuid($uuid) {
        $query = $this->find()
            ->contain('TimeperiodTimeranges')
            ->where([
                'Timeperiods.uuid' => $uuid
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int|array $containerIds
     * @return array
     */
    public function getTimeperiodByContainerIdsAsList($containerIds = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $timeperiods = $this->find()
            ->select([
                'Timeperiods.id',
                'Timeperiods.name'
            ])
            ->where(['Timeperiods.container_id IN' => $containerIds])
            ->all();

        return $this->formatListAsCake2($timeperiods->toArray());
    }

    /**
     * @param array $calendarIds
     * @return array
     */
    public function getTimeperiodByCalendarIdsAsList($calendarIds = []) {
        if (!is_array($calendarIds)) {
            $calendarIds = [$calendarIds];
        }

        $timeperiods = $this->find()
            ->select([
                'Timeperiods.id',
                'Timeperiods.name'
            ])
            ->where(['Timeperiods.calendar_id IN' => $calendarIds])
            ->all();

        return $this->formatListAsCake2($timeperiods->toArray());
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getTimeperiodsAsList($ids = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find()
            ->select([
                'Timeperiods.id',
                'Timeperiods.name'
            ])
            ->disableHydration();
        if (!empty($ids)) {
            $query->where([
                'Timeperiods.id IN' => $ids
            ]);
        }

        return $this->formatListAsCake2($query->toArray());
    }

    /**
     * @param array $ids
     * @paran array $MY_RIGHTS
     * @return array
     */
    public function getTimeperiodsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Timeperiods.id',
                'Timeperiods.name',
                'Timeperiods.description'
            ])
            ->where([
                'Timeperiods.id IN' => $ids
            ])
            ->orderBy(['Timeperiods.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Timeperiods.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }

    public function checkTimerangeOvelapping($timeranges) {
        $error_arr = [];
        foreach ($timeranges as $key => $row) {
            $day[$key] = $row['day'];
            $start[$key] = $row['start'];
        }
        array_multisort($day, SORT_ASC, $start, SORT_ASC, $timeranges);
        $check_timerange_array = [];
        foreach ($timeranges as $key => $timerange) {
            $check_timerange_array[$timerange['day']][] = ['start' => $timerange['start'], 'end' => $timerange['end']];
        }
        foreach ($check_timerange_array as $day => $timerange_data) {
            if (sizeof($timerange_data) > 1) {
                $intern_counter = 0;
                $tmp_start = $check_timerange_array[$day][$intern_counter]['start'];
                $tmp_end = $check_timerange_array[$day][$intern_counter]['end'];
                for ($input_key = 0; $input_key < sizeof($timerange_data); $input_key++) {
                    $intern_counter++;
                    if (isset($timerange_data[$intern_counter])) {
                        if ($tmp_start <= $timerange_data[$intern_counter]['start'] &&
                            $tmp_end > $timerange_data[$intern_counter]['start']
                        ) {
                            if ($tmp_end <= $timerange_data[$intern_counter]['end']) {
                                $tmp_end = $timerange_data[$intern_counter]['end'];
                            } else {
                                $input_key--;
                            }
                            $error_arr[$day]['state-error'][] = [
                                'start' => $timerange_data[$intern_counter]['start'],
                                'end'   => $timerange_data[$intern_counter]['end']
                            ];
                            $timeranges[$intern_counter] = 'error';
                        } else {
                            $tmp_start = $timerange_data[$intern_counter]['start'];
                            $tmp_end = $timerange_data[$intern_counter]['end'];
                            $input_key++;
                        }
                    }
                }
            }
        }
        return $error_arr;
    }

    /**
     * @param $id
     * @return string|null
     */
    public function getTimeperiodUuidById($id) {
        $timeperiod = $this->find('all')
            ->select(['Timeperiods.uuid'])
            ->where(['Timeperiods.id' => $id])
            ->first();

        if (is_null($timeperiod)) {
            return null;
        }

        return $timeperiod->uuid;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id): bool {
        return $this->exists(['Timeperiods.id' => $id]);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public function existsByUuid(string $uuid): bool {
        return $this->exists(['Timeperiods.uuid' => $uuid]);
    }

    public function timeperiodsByContainerId($container_ids = [], $type = 'all') {
        if (!is_array($container_ids)) {
            $container_ids = [$container_ids];
        }

        $container_ids = array_unique($container_ids);

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $tenantContainerIds = [];
        foreach ($container_ids as $container_id) {
            if ($container_id != ROOT_CONTAINER) {
                $path = $ContainersTable->getPathByIdAndCacheResult($container_id, 'TimeperiodTimeperiodsByContainerId');
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

        $containerIds = array_unique(array_merge($tenantContainerIds, $container_ids));
        if (empty($containerIds)) {
            return [];
        }

        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->innerJoinWith('Containers', function (Query $q) use ($containerIds) {
            return $q->where(['Containers.id IN' => $containerIds]);
        });

        $query->distinct('Timeperiods.id');
        $query->disableHydration();

        if ($type === 'all') {
            return $this->formatResultAsCake2($query->toArray());
        }

        return $this->formatListAsCake2($query->toArray());
    }

    /**
     * @param int $timeperiodId
     * @param int $containerId
     * @param int $fallbackTimeperiodId
     * @return int
     */
    public function checkTimeperiodIdForContainerPermissions($timeperiodId, $containerId, $fallbackTimeperiodId) {
        $tenantContainerIds = [];

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        foreach ($containerIds as $containerId) {
            if ($containerId != ROOT_CONTAINER) {
                $path = $ContainersTable->getPathByIdAndCacheResult($containerId, 'TimeperiodCheckTimeperiodIdForContainerPermissions');
                // Get container id of the tenant container
                // Tenant timeperiods are available for all users of a tenant (oITC V2 legacy)
                if (isset($path[1])) {
                    $tenantContainerIds[] = $path[1]['id'];
                }
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }

        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));

        $timeperiod = $this->find()
            ->select([
                'Timeperiods.id',
                'Timeperiods.container_id'
            ])
            ->where([
                'Timeperiods.id ' => $timeperiodId
            ])
            ->disableHydration()
            ->first();

        if ($timeperiod === null) {
            return $fallbackTimeperiodId;
        }

        if (in_array($timeperiod['container_id'], $containerIds, true)) {
            return $timeperiod['id'];
        }

        return $fallbackTimeperiodId;
    }

    /**
     * @param $timeperiod
     * @return bool
     */
    public function allowDelete($timeperiod) {
        if (is_numeric($timeperiod)) {
            $timeperiodId = $timeperiod;
        } else {
            $timeperiodId = $timeperiod['Timeperiod']['id'];
        }

        //Check contacts
        /** @var ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        if ($ContactsTable->isTimeperiodUsedByContacts($timeperiodId)) {
            return false;
        }

        //Check host templates
        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        if ($HosttemplatesTable->isTimeperiodUsedByHosttemplate($timeperiodId)) {
            return false;
        }

        //Check hosts
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        if ($HostsTable->isTimeperiodUsedByHost($timeperiodId)) {
            return false;
        }


        //Check service templates
        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        if ($ServicetemplatesTable->isTimeperiodUsedByServicetemplate($timeperiodId)) {
            return false;
        }

        //Check services
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        if ($ServicesTable->isTimeperiodUsedByService($timeperiodId)) {
            return false;
        }

        //Check host and service dependencies
        /** @var HostdependenciesTable $HostdependenciesTable */
        $HostdependenciesTable = TableRegistry::getTableLocator()->get('Hostdependencies');
        if ($HostdependenciesTable->isTimeperiodUsedByHostdependencies($timeperiodId)) {
            return false;
        }

        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
        if ($ServicedependenciesTable->isTimeperiodUsedByServicedependencies($timeperiodId)) {
            return false;
        }

        //Check host and service escalations
        /** @var HostescalationsTable $HostescalationsTable */
        $HostescalationsTable = TableRegistry::getTableLocator()->get('Hostescalations');
        if ($HostescalationsTable->isTimeperiodUsedByHostescalations($timeperiodId)) {
            return false;
        }

        /** @var ServicedependenciesTable $ServicedependenciesTable */
        $ServicedependenciesTable = TableRegistry::getTableLocator()->get('Servicedependencies');
        if ($ServicedependenciesTable->isTimeperiodUsedByServicedependencies($timeperiodId)) {
            return false;
        }

        //Check autoreports
        if (Plugin::isLoaded('AutoreportModule')) {
            /** @var \AutoreportModule\Model\Table\AutoreportsTable $AutoreportsTable */
            $AutoreportsTable = TableRegistry::getTableLocator()->get('AutoreportModule.Autoreports');
            if ($AutoreportsTable->isTimeperiodUsedByAutoreports($timeperiodId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param array $MY_RIGHTS
     * @param array $where
     * @return array
     */
    public function getTimeperiodsByContainerIdExact($containerId, $type = 'all', $index = 'id', $MY_RIGHTS = [], $where = []) {
        $_where = [
            'Timeperiods.container_id' => $containerId
        ];

        $where = Hash::merge($_where, $where);

        $query = $this->find();
        $query->select([
            'Timeperiods.' . $index,
            'Timeperiods.name'
        ]);
        $query->where($where);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Timeperiods.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $query->orderBy([
            'Timeperiods.name' => 'asc'
        ]);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        if ($type === 'all') {
            return $result;
        }

        $list = [];
        foreach ($result as $row) {
            $list[$row[$index]] = $row['name'];
        }

        return $list;
    }


    public function parseTimerangesForCalendar($timeRanges) {
        $possibleWeekDays = [0, 1, 2, 3, 4, 5, 6];
        $eventsFormated = [];
        $calendarEvents = Hash::combine(
            $timeRanges,
            '{n}.id',
            '{n}',
            '{n}.day'
        );
        if (isset($calendarEvents[7])) { //replace key from sunday from 7 to 0
            $calendarEvents[0] = $calendarEvents[7];
            unset($calendarEvents[7]);
        }
        ksort($calendarEvents);

        foreach ($calendarEvents as $day => $events) {
            foreach ($events as $event) {
                $eventsFormated[] = [
                    'daysOfWeek' => [$day],
                    'startTime'  => $event['start'],
                    'endTime'    => $event['end'],
                    'title'      => sprintf('%s - %s', $event['start'], $event['end'])
                ];
            }
        }
        $emptyDays = array_diff($possibleWeekDays, array_keys($calendarEvents));
        if (!empty($emptyDays)) {
            $eventsFormated[] = [
                'daysOfWeek' => array_keys($emptyDays),
                'display'    => 'background',
                'className'  => 'no-events'
            ];
        }
        /** highlight non business days: saturday and sunday  */
        $eventsFormated[] = [
            'daysOfWeek' => [0, 6],
            'display'    => 'background',
            'className'  => 'fc-nonbusiness',
            'allDay'     => true,
            'overLap'    => false
        ];

        return $eventsFormated;
    }

    /**
     * @param $ids
     * @return array
     */
    public function getTimeperiodsByIdsForExport($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->contain(['TimeperiodTimeranges'])
            ->where([
                'Timeperiods.id IN'        => $ids,
                'Timeperiods.container_id' => ROOT_CONTAINER
            ])
            ->disableHydration();
        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getTimeperiodForEdit($id) {
        $where = [
            'Timeperiods.id' => $id
        ];
        return $this->getTimeperiodForEditByWhere($where);
    }

    /**
     * @param string $uuid
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getTimeperiodForEditByUuid(string $uuid) {
        $where = [
            'Timeperiods.uuid' => $uuid
        ];
        return $this->getTimeperiodForEditByWhere($where);
    }

    /**
     * @param array $where
     * @return array|\Cake\Datasource\EntityInterface
     */
    private function getTimeperiodForEditByWhere(array $where) {
        return $this->find()
            ->where($where)
            ->contain(['TimeperiodTimeranges'])
            ->firstOrFail();
    }

    /**
     * This method provides a unified way to create new timeperiod. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     * @param Timeperiod $entity The entity that will be saved by the Table
     * @param array $timeperiod The timeperiod as array ( [ Timeperiod => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Timeperiod
     */
    public function createTimeperiod(Timeperiod $entity, array $timeperiod, int $userId): Timeperiod {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'add',
            'Timeperiods',
            $entity->get('id'),
            OBJECT_TIMEPERIOD,
            [ROOT_CONTAINER],
            $userId,
            $entity->get('name'),
            $timeperiod
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }


        return $entity;
    }

    /**
     * This method provides a unified way to update an existing timeperiod. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     * @param Timeperiod $entity The entity that will be updated by the Table
     * @param array $newTimeperiod The new timeperiod as array ( [ Timeperiod => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param array $oldTimeperiod The old timeperiod as array ( [ Timeperiod => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Timeperiod
     */
    public function updateTimeperiod(Timeperiod $entity, array $newTimeperiod, array $oldTimeperiod, int $userId): Timeperiod {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'edit',
            'Timeperiods',
            $entity->get('id'),
            OBJECT_TIMEPERIOD,
            [$entity->get('container_id')],
            $userId,
            $entity->get('name'),
            $newTimeperiod,
            $oldTimeperiod
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        return $entity;
    }

    /**
     * @param $uuid
     * @return array
     */
    public function getTimeperiodByUuidForImportDiff($uuid) {
        $query = $this->find('all')
            ->contain('TimeperiodTimeranges')
            ->where(['Timeperiods.uuid' => $uuid])
            ->disableHydration()
            ->firstOrFail();

        return $this->emptyArrayIfNull($query);
    }
}
