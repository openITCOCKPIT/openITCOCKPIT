<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace TelegramModule\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TelegramModule Model
 *
 * @method \TelegramModule\Model\Entity\TelegramChats get($primaryKey, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats newEntity($data = null, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats[] newEntities(array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats[] patchEntities($entities, array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramChats findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TelegramChatsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('telegram_chats');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->integer('chat_id')
            ->notEmptyString('chat_id');

        $validator
            ->boolean('enabled')
            ->notEmptyString('enabled');

        $validator
            ->scalar('started_from_username')
            ->maxLength('started_from_username', 255)
            ->notEmptyString('started_from_username');

        $validator
            ->scalar('contact_uuid')
            ->maxLength('contact_uuid', 37)
            ->requirePresence('contact_uuid', 'create')
            ->notEmptyString('contact_uuid')
            ->add('contact_uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        return $validator;
    }

    /**
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getTelegramChats() {
        $result = $this->find()
            ->disableHydration()
            ->all();

        return $result;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsByChatId($chat_id) {
        return $this->exists(['chat_id' => $chat_id]);
    }

    /**
     * @param $chat_id
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getByChatId($chat_id) {
        return $this->find()->where(['chat_id' => $chat_id])->first();
    }
}
