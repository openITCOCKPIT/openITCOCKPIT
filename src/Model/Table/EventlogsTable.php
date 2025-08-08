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

declare(strict_types=1);

namespace App\Model\Table;

use App\itnovum\openITCOCKPIT\Filter\EventlogsFilter;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * Eventlogs Model
 *
 * @property \App\Model\Table\EventlogsToContainersTable&\Cake\ORM\Association\HasMany $EventlogsToContainers
 *
 * @method \App\Model\Entity\Eventlog newEmptyEntity()
 * @method \App\Model\Entity\Eventlog newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Eventlog[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Eventlog get($primaryKey, $options = [])
 * @method \App\Model\Entity\Eventlog findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Eventlog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Eventlog[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Eventlog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Eventlog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Eventlog[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Eventlog[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Eventlog[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Eventlog[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class EventlogsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('eventlogs');
        $this->setDisplayField('type');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Containers', [
            'joinTable' => 'eventlogs_to_containers'
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'object_id'
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
            ->scalar('type')
            ->maxLength('type', 255)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('model')
            ->maxLength('model', 255)
            ->requirePresence('model', 'create')
            ->notEmptyString('model');

        $validator
            ->integer('object_id')
            ->requirePresence('object_id', 'create')
            ->notEmptyString('object_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('data')
            ->requirePresence('data', 'create')
            ->notEmptyString('data');

        return $validator;
    }

    /**
     * @param EventlogsFilter $EventlogsFilter
     * @param array $logTypes
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getEventlogIndex(EventlogsFilter $EventlogsFilter, $logTypes, $PaginateOMat = null, $MY_RIGHTS = [], $enableHydration = true) {

        if (!is_array($logTypes)) {
            $logTypes = [$logTypes];
        }

        $query = $this->find();

        $query = $query->select([
            'id',
            'model',
            'type',
            'object_id',
            'name'       => $query->newExpr('IF(Users.email IS NULL, Eventlogs.name, CONCAT(Users.firstname, " ", Users.lastname))'),
            'user_email' => $query->newExpr('IF(Users.email IS NOT NULL, Users.email, NULL)'),
            'data',
            'created'
        ])->contain(['Containers', 'Users']);

        if (!empty($logTypes)) {
            $query->where(['Eventlogs.type IN' => $logTypes]);
        }

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Containers.id IN' => $MY_RIGHTS]);
            }
            return $q;
        })
            ->enableHydration($enableHydration);

        $where = $EventlogsFilter->indexFilter();
        $having = [];
        if (isset($where['name LIKE'])) {
            $having['name LIKE'] = $where['name LIKE'];
            unset($where['name LIKE']);
        }
        if (isset($where['user_email LIKE'])) {
            $having['user_email LIKE'] = $where['user_email LIKE'];
            unset($where['user_email LIKE']);
        }

        $where['Eventlogs.created >='] = date('Y-m-d H:i:s', $EventlogsFilter->getFrom());
        $where['Eventlogs.created <='] = date('Y-m-d H:i:s', $EventlogsFilter->getTo());

        $query->groupBy(['Eventlogs.id']);

        $query->where($where);
        if (!empty($having)) {
            $query->having($having);
        }
        $query->orderBy(
            array_merge(
                $EventlogsFilter->getOrderForPaginator('Eventlogs.id', 'desc'),
                ['Eventlogs.id' => 'desc']
            )
        );

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
     * @param string $modelName
     * @param int $objectId
     * @return bool
     */
    public function recordExists(string $modelName, $objectId) {
        $tableName = Inflector::pluralize($modelName);

        /** @var Table $Table */
        $Table = TableRegistry::getTableLocator()->get($tableName);

        try {
            return $Table->exists(['id' => $objectId]);
        } catch (\Exception $e) {
            Log::error(sprintf('Eventlog: Table %s not found! in %s on line %s', $tableName, __FILE__, __LINE__));
            Log::error($e->getMessage());
        }
        return false;
    }

    /**
     * Saves record in eventlogs table
     *  Returns true for successful
     *
     * @param string $type
     * @param string $model
     * @param int $objectId
     * @param string $name
     * @param string $data
     * @param array $container_ids
     * @param bool $dataIsJSon
     * @return bool
     */
    public function saveNewEntity($type, $model, $objectId, $name, $data, $container_ids) {

        if (!is_array($container_ids)) {
            $container_ids = [$container_ids];
        }

        if (!empty($type) && !empty($model) && !empty($objectId) && !empty($name) && !empty($data) && !empty($container_ids)) {

            $eventlog = $this->newEmptyEntity();
            $eventlog = $this->patchEntity($eventlog, [
                'type'       => $type,
                'model'      => $model,
                'object_id'  => $objectId,
                'name'       => $name,
                'containers' => [
                    '_ids' => $container_ids
                ],
                'data'       => $data,
            ]);
            $this->save($eventlog);
            if ($eventlog->hasErrors()) {
                Log::error(sprintf(
                    'EventlogsTable: Could not save %s [%s]',
                    $type,
                    $eventlog->id
                ));
                Log::error(json_encode($eventlog->getErrors()));
                return false;
            }

        } else {
            Log::error(sprintf(
                'EventlogsTable: Could not save %s',
                $type
            ));
            return false;
        }

        return true;
    }

    /**
     * cerates a json for the data column of the eventlogs table for the login type
     *  Returns a json
     *
     * @param string $email
     * @return string
     */
    public function createDataJsonForUser(string $email): string {
        $data = [
            'user_email' => $email
        ];
        return json_encode($data);
    }
}
