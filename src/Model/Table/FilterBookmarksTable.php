<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

use App\Model\Entity\FilterBookmark;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;


class FilterBookmarksTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('filter_bookmarks');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'   => 'INNER',
        ]);

        $this->hasMany('FilterBookmarkAllocations', [
            'foreignKey'       => 'filter_bookmark_id',
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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('plugin')
            ->maxLength('plugin', 255)
            // ->requirePresence('plugin', 'create')
            ->allowEmptyString('plugin', null, true);

        $validator
            ->scalar('controller')
            ->maxLength('controller', 255)
            ->requirePresence('controller', 'create')
            ->allowEmptyString('controller', null, false);

        $validator
            ->scalar('action')
            ->maxLength('action', 255)
            ->requirePresence('action', 'create')
            ->allowEmptyString('action', null, false);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->allowEmptyString('name', null, false);

        $validator
            ->requirePresence('user_id', 'create')
            ->integer('user_id')
            ->allowEmptyString('user_id', null, false);

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

        return $rules;
    }


    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['FilterBookmarks.id' => $id]);
    }


    /**
     * @param int $userId
     * @param string $plugin
     * @param string $controller
     * @param string $action
     * @return array
     */
    public function getFilterByUser(int $userId, string $plugin, string $controller, string $action): array {
        $query = $this->find()
            ->where([
                'FilterBookmarks.plugin'     => $plugin,
                'FilterBookmarks.controller' => $controller,
                'FilterBookmarks.action'     => $action,
                'FilterBookmarks.user_id'    => $userId
            ])
            ->orderBy([
                'FilterBookmarks.favorite' => 'asc',
                'FilterBookmarks.name'     => 'asc',
            ]);
        $result = $query->all();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param string $uuid
     * @return array|EntityInterface|null
     */
    public function getFilterByUuid(string $uuid) {
        $query = $this->find()
            ->where([
                'FilterBookmarks.uuid' => $uuid,
            ])
            ->first();
        return $query;
    }

    /**
     * @param int $id
     * @return FilterBookmark
     * @throws RecordNotFoundException
     */
    public function getByIdAndUserId($id, int $userId) {
        return $this->find()
            ->where([
                'FilterBookmarks.id'      => $id,
                'FilterBookmarks.user_id' => $userId
            ])
            ->firstOrFail();
    }

    /**
     * @param User $User
     * @param string $plugin
     * @param string $controller
     * @param string $action
     * @return array
     */

    public function getAllBookmarksByUser(User $User, string $plugin, string $controller, string $action): array {
        // Check for allocated Dashboards
        $allocations = [];

        /** @var FilterBookmarkAllocationsTable $FilterBookmarkAllocationsTable */
        $FilterBookmarkAllocationsTable = TableRegistry::getTableLocator()->get('FilterBookmarkAllocations');

        //$allocations = $FilterBookmarksAllocationsTable->getAllBookmarksAllocationsByUser($User);
        //$allocationBookmarkIds = Hash::combine($allocations, '{n}.filter_bookmark_id', '{n}');

        $allBookmarkAllocations = $FilterBookmarkAllocationsTable->getAllBookmarkAllocations($User, $plugin, $controller, $action);
        $userBookmarkAllocations = $FilterBookmarkAllocationsTable->getAllBookmarkAllocationsByUser($User, $plugin, $controller, $action);
        $userBookmarkAllocationsIds = Hash::combine($userBookmarkAllocations, '{n}.filter_bookmark_id', '{n}');
        $allBookmarkAllocationsIds = Hash::combine($allBookmarkAllocations, '{n}.filter_bookmark_id', '{n}');


        // Get all Filter Bookmarks from the user
        $where = [
            'FilterBookmarks.user_id'    => $User->getId(),
            'FilterBookmarks.plugin'     => $plugin,
            'FilterBookmarks.controller' => $controller,
            'FilterBookmarks.action'     => $action,
        ];

        // Also select allocated Tabs (if any exit)
        if (!empty($allBookmarkAllocationsIds) && !empty($userBookmarkAllocationsIds)) {
            $where = [
                'OR' => [
                    [
                        'FilterBookmarks.user_id'    => $User->getId(),
                        'FilterBookmarks.plugin'     => $plugin,
                        'FilterBookmarks.controller' => $controller,
                        'FilterBookmarks.action'     => $action,
                    ],
                    [
                        'FilterBookmarks.id IN' => array_keys($userBookmarkAllocationsIds),
                    ],
                ]
            ];
        }

        $result = $this->find()
            ->where($where)
            ->orderBy([
                'FilterBookmarks.id' => 'ASC',
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
                    'id'                         => (int)$row['id'],
                    'uuid'                       => $row['uuid'],
                    'plugin'                     => $row['plugin'],
                    'controller'                 => $row['controller'],
                    'action'                     => $row['action'],
                    'name'                       => $row['name'],
                    'filter'                     => $row['filter'],
                    'ownership'                  => $isOwner,
                    'filter_bookmark_allocation' => $allBookmarkAllocationsIds[$row['id']] ?? null,
                    'fav_group'                  => $row['favorite'] ? __('Favorites') : __('Filters'),
                ];
            } else {
                // This dashboard tab got allocated to the user
                // We remove any potential sensitive data
                if (!isset($allBookmarkAllocationsIds[$row['id']])) {
                    // this should be impossible !
                    continue;
                }

                $allocation = $allBookmarkAllocationsIds[$row['id']];

                $forJs[] = [
                    'id'         => (int)$row['id'],
                    'uuid'       => $row['uuid'],
                    'plugin'     => $row['plugin'],
                    'controller' => $row['controller'],
                    'action'     => $row['action'],
                    'name'       => $row['name'],
                    'filter'     => $row['filter'],
                    'ownership'  => $isOwner,
                    'fav_group'  => $row['favorite'] ? __('Favorites') : __('Filters'),
                    //'source'            => 'ALLOCATED'
                ];
            }
        }


        return Hash::sort($forJs, '{n}.id', 'asc');
    }

}
