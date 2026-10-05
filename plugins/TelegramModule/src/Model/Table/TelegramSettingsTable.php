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
 * @method \TelegramModule\Model\Entity\TelegramSetting get($primaryKey, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting newEntity($data = null, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting[] newEntities(array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting[] patchEntities($entities, array $data, array $options = [])
 * @method \TelegramModule\Model\Entity\TelegramSetting findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TelegramSettingsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('telegram_settings');
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
            ->scalar('token')
            ->maxLength('token', 255)
            ->notEmptyString('token');

        $validator
            ->integer('last_update_id')
            ->allowEmptyString('last_update_id');

        $validator
            ->boolean('two_way')
            ->notEmptyString('two_way');

        $validator
            ->scalar('external_webhook_domain')
            ->maxLength('external_webhook_domain', 255);

        $validator
            ->scalar('webhook_api_key')
            ->maxLength('webhook_api_key', 255);

        $validator
            ->boolean('use_proxy')
            ->notEmptyString('use_proxy');

        return $validator;
    }

    /**
     * @return \TelegramModule\Model\Entity\TelegramSetting
     */
    public function getTelegramSettings() {
        $default = [
            'token'                   => '',
            'last_update_id'          => 0,
            'two_way'                 => false,
            'external_webhook_domain' => '',
            'webhook_api_key'         => '',
            'use_proxy'               => false
        ];

        if ($this->exists(['id' => 1])) {
            return $this->get(1);
        }

        return $this->newEntity($default);
    }

    /**
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getTelegramSettingsEntity() {
        $result = $this->find()
            ->where([
                'id' => 1
            ])
            ->first();

        if (empty($result)) {
            $entity = $this->newEmptyEntity();
            $entity->set('id', 1);
            $entity->setAccess('id', false);
            return $entity;
        }

        $result->setAccess('id', false);
        return $result;
    }
}
