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

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;

/**
 * DashboardTabs Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\WidgetsTable&\Cake\ORM\Association\HasMany $Widgets
 *
 * @method \App\Model\Entity\DashboardTab get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\DashboardTab newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\DashboardTab[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTab|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DashboardTab saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DashboardTab patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTab[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\DashboardTab findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DashboardTabsTable extends Table {
    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('dashboard_tabs');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');


        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'   => 'INNER',
        ]);

        $this->hasMany('Widgets', [
            'foreignKey' => 'dashboard_tab_id',
            'dependent'  => true
        ]);

        $this->hasMany('DashboardTabAllocations', [
            'foreignKey'       => 'dashboard_tab_id',
            'dependent'        => true,
            'cascadeCallbacks' => true // https://book.cakephp.org/4/en/orm/deleting-data.html#cascading-deletes
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
            ->integer('position')
            ->requirePresence('position', 'create')
            ->notEmptyString('position');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->boolean('shared')
            ->notEmptyString('shared');

        $validator
            ->integer('check_for_updates')
            ->allowEmptyString('check_for_updates');

        $validator
            ->integer('last_update')
            ->allowEmptyString('last_update');

        $validator
            ->boolean('locked')
            ->notEmptyString('locked');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['DashboardTabs.id' => $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isOwnedByUser($tabId, $userId) {
        return $this->exists([
            'DashboardTabs.user_id' => $userId,
            'DashboardTabs.id'      => $tabId
        ]);
    }

    /**
     * @param int $userId
     * @param array $options
     * @return \Cake\Datasource\EntityInterface
     */
    public function createNewTab($userId, $options = []) {
        $_options = [
            'name'              => __('Default'),
            'shared'            => 0,
            'source_tab_id'     => null,
            'check_for_updates' => 0,
            'position'          => $this->getNextPosition($userId),
        ];
        $options = Hash::merge($_options, $options);

        $entity = $this->newEmptyEntity();
        $entity->set('user_id', $userId);
        foreach ($options as $key => $value) {
            $entity->set($key, $value);
        }

        $this->save($entity);
        return $entity;
    }

    /**
     * @param $userId
     * @return int
     */
    public function getNextPosition($userId): int {
        try {
            $result = $this->find()
                ->where([
                    'DashboardTabs.user_id' => $userId
                ])
                ->orderBy([
                    'DashboardTabs.position' => 'DESC'
                ])
                ->firstOrFail();

            return ($result->get('position') + 1);
        } catch (RecordNotFoundException $e) {
            return 1;
        }
        //Should be never reached
        return 1;
    }

    /**
     * @param User $User
     * @return bool
     */
    public function hasUserATab(User $User) {
        try {
            $result = $this->find()
                ->where([
                    'DashboardTabs.user_id' => $User->getId(),
                ])
                ->first();
            if (!empty($result)) {
                return true;
            }

            // Check for allocated Dashboards!
            /** @var DashboardTabAllocationsTable $DashboardTabAllocationsTable */
            $DashboardTabAllocationsTable = TableRegistry::getTableLocator()->get('DashboardTabAllocations');

            $allocations = $DashboardTabAllocationsTable->getAllDashboardAllocationsByUser($User);

            if (!empty ($allocations)) {
                return true;
            }
        } catch (RecordNotFoundException $e) {

        }
        return false;
    }

    /**
     * @param User $User
     * @return array|null
     */
    public function getAllTabsByUser(User $User) {
        // Check for allocated Dashboards
        $allocations = [];

        /** @var DashboardTabAllocationsTable $DashboardTabAllocationsTable */
        $DashboardTabAllocationsTable = TableRegistry::getTableLocator()->get('DashboardTabAllocations');

        $allocations = $DashboardTabAllocationsTable->getAllDashboardAllocationsByUser($User);
        $allocationDashboardTabIds = Hash::combine($allocations, '{n}.dashboard_tab_id', '{n}');

        $allDashboardsAllocations = $DashboardTabAllocationsTable->getAllDashboardAllocations();
        $allDashboardsAllocationsTabIds = Hash::combine($allDashboardsAllocations, '{n}.dashboard_tab_id', '{n}');


        // Get all Dashboard Tabs from the user
        $where = [
            'DashboardTabs.user_id' => $User->getId(),
        ];

        // Also select allocated Tabs (if any exit)
        if (!empty($allocationDashboardTabIds)) {
            $where = [
                'OR' => [
                    'DashboardTabs.user_id' => $User->getId(),
                    'DashboardTabs.id IN'   => array_keys($allocationDashboardTabIds)
                ]
            ];
        }

        $result = $this->find()
            ->where($where)
            ->orderBy([
                'DashboardTabs.position' => 'ASC',
            ]);

        $result
            ->disableHydration()
            ->all();


        $forJs = [];
        foreach ($result as $row) {
            $isOwner = (int)$row['user_id'] === $User->getId();
            if ($isOwner) {
                // This dashboard tab is from the user itself
                $forJs[] = [
                    'id'                       => (int)$row['id'],
                    'position'                 => (int)$row['position'],
                    'name'                     => $row['name'],
                    'shared'                   => (bool)$row['shared'],
                    'source_tab_id'            => (int)$row['source_tab_id'],
                    'check_for_updates'        => (bool)$row['check_for_updates'],
                    'last_update'              => (int)$row['last_update'],
                    'locked'                   => (bool)$row['locked'],
                    'pinned'                   => false,
                    'isOwner'                  => $isOwner,
                    'dashboard_tab_allocation' => $allDashboardsAllocationsTabIds[$row['id']] ?? null
                ];
            } else {
                // This dashboard tab got allocated to the user
                // We remove any potential sensitive data
                if (!isset($allocationDashboardTabIds[$row['id']])) {
                    // this should be impossible !
                    continue;
                }

                $allocation = $allocationDashboardTabIds[$row['id']];
                $position = (int)$row['position'];
                if ($allocation['pinned']) {
                    $position = -1;
                }

                $forJs[] = [
                    'id'                => (int)$row['id'],
                    'position'          => $position,
                    'name'              => $row['name'],
                    'shared'            => false,
                    'source_tab_id'     => 0,
                    'check_for_updates' => false,
                    'last_update'       => 0,
                    'locked'            => true,
                    'pinned'            => $allocation['pinned'],
                    'isOwner'           => $isOwner,
                    //'source'            => 'ALLOCATED'
                ];
            }
        }


        return Hash::sort($forJs, '{n}.position', 'asc');
    }

    /**
     * @return array|null
     */
    public function getSharedTabs() {
        $query = $this->find()
            ->select([
                'DashboardTabs.id',
                'DashboardTabs.position',
                'DashboardTabs.name',
                'DashboardTabs.shared',
                'DashboardTabs.source_tab_id',
                'DashboardTabs.check_for_updates',
                'DashboardTabs.last_update',
                'DashboardTabs.locked',
                'Users.firstname',
                'Users.lastname',
            ])
            ->join([
                [
                    'table'      => 'users',
                    'alias'      => 'Users',
                    'type'       => 'INNER',
                    'conditions' => [
                        'Users.id = DashboardTabs.user_id',
                    ],
                ]
            ])
            ->where([
                'DashboardTabs.shared' => 1
            ])
            ->disableHydration()
            ->all();

        if ($query->isEmpty()) {
            return [];
        }

        $forJs = [];
        foreach ($query->toArray() as $row) {
            $forJs[] = [
                'id'                => (int)$row['id'],
                'position'          => (int)$row['position'],
                'name'              => sprintf(
                    '%s, %s/%s',
                    $row['Users']['firstname'],
                    $row['Users']['lastname'],
                    $row['name']
                ),
                'shared'            => (bool)$row['shared'],
                'source_tab_id'     => (int)$row['source_tab_id'],
                'check_for_updates' => (bool)$row['check_for_updates'],
                'last_update'       => (int)$row['last_update'],
                'locked'            => (bool)$row['locked']
            ];
        }

        return $forJs;
    }

    /**
     * @param $userId
     * @param $tabId
     * @return array|null
     */
    public function getWidgetsForTabByUserIdAndTabId($userId, $tabId, int $loggedInUserId) {
        $query = $this->find()
            ->contain('Widgets', function (Query $query) {
                $query->orderBy([
                    'Widgets.col' => 'ASC'
                ]);
                return $query;
            })
            ->where([
                'DashboardTabs.id'      => $tabId,
                'DashboardTabs.user_id' => $userId
            ])
            ->disableHydration()
            ->first();

        if ($query === null) {
            return [];
        }

        $result = $this->formatFirstResultAsCake2($query);
        // Add isOwner
        $result['DashboardTab']['isOwner'] = $loggedInUserId == $result['DashboardTab']['user_id'];
        return $result;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getTabByIdAsCake2($id) {
        $result = $this->find()
            ->where([
                'DashboardTabs.id' => $id
            ])
            ->disableHydration()
            ->first();
        return $this->formatFirstResultAsCake2($result);
    }


    /**
     * @param int $id
     * @param int $userId
     * @return \App\Model\Entity\DashboardTab
     * @throws RecordNotFoundException
     */
    public function copySharedTab($id, $userId) {
        $sourceTab = $this->find()
            ->where([
                'DashboardTabs.id'     => $id,
                'DashboardTabs.shared' => 1
            ])
            ->contain([
                'Widgets'
            ])
            ->firstOrFail();

        $widgets = [];
        foreach ($sourceTab->get('widgets') as $widget) {
            $widgets[] = [
                'type_id'    => $widget->get('type_id'),
                'host_id'    => $widget->get('host_id'),
                'service_id' => $widget->get('service_id'),
                'row'        => $widget->get('row'),
                'col'        => $widget->get('col'),
                'width'      => $widget->get('width'),
                'height'     => $widget->get('height'),
                'title'      => $widget->get('title'),
                'color'      => $widget->get('color'),
                'directive'  => $widget->get('directive'),
                'icon'       => $widget->get('icon'),
                'json_data'  => $widget->get('json_data')
            ];
        }

        $newTab = $this->newEntity([
            'name'              => $sourceTab->get('name'),
            'locked'            => $sourceTab->get('locked'),
            'user_id'           => $userId,
            'position'          => $this->getNextPosition($userId),
            'shared'            => 0,
            'source_tab_id'     => $id,
            'check_for_updates' => 1,
            'last_update'       => time(),
            'widgets'           => $widgets
        ]);

        $this->save($newTab);
        return $newTab;
    }
}
