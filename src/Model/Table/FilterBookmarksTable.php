<?php

namespace App\Model\Table;

use App\Model\Entity\FilterBookmark;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FilterBookmarksTable extends Table {
    //use PaginationAndScrollIndexTrait;

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

}
