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
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys get($primaryKey, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys newEntity($data = null, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys[] newEntities(array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys[] patchEntities($entities, array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramContactsAccessKeys findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TelegramContactsAccessKeysTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('telegram_contacts_access_keys');
        $this->setDisplayField('contact_uuid');
        $this->setPrimaryKey('contact_uuid');

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
            ->scalar('contact_uuid')
            ->maxLength('contact_uuid', 37)
            ->requirePresence('contact_uuid', 'create')
            ->notEmptyString('contact_uuid')
            ->add('contact_uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('access_key')
            ->maxLength('access_key', 255)
            ->notEmptyString('access_key');

        return $validator;
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateRandomString($length = 40) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-.+:!=';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param string $contact_uuid
     * @return \TelegramModule\Model\Entity\TelegramContactsAccessKeys
     */
    public function getNewOrExistingAccessKeyByContactUuid(string $contact_uuid) {
        $default = [
            'contact_uuid' => $contact_uuid,
            'access_key'   => $this->generateRandomString()
        ];

        if ($this->exists(['contact_uuid' => $contact_uuid])) {
            return $this->get($contact_uuid);
        }

        return $this->newEntity($default);
    }

    /**
     * @param string $access_key
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getContactByAccessKey(string $access_key) {
        if ($this->exists(['access_key' => $access_key])) {
            return $this->find()
                ->where([
                    'access_key' => $access_key
                ])
                ->first();
        }

        return null;
    }

    /**
     * @return array
     */
    public function getAllAsArray() {
        $contactsAccessKeys = [];
        $contactsAccessKeysQuery = $this->find()
            ->disableHydration()
            ->all();

        if ($contactsAccessKeysQuery !== null) {
            $contactsAccessKeys = $contactsAccessKeysQuery->toArray();
        }

        return $contactsAccessKeys;
    }
}
