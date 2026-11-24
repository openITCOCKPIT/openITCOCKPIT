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

namespace MattermostModule\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MattermostSettings Model
 *
 * @method \MattermostModule\Model\Entity\MattermostSetting get($primaryKey, $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting newEntity($data = null, array $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting[] newEntities(array $data, array $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting[] patchEntities($entities, array $data, array $options = [])
 * @method \MattermostModule\Model\Entity\MattermostSetting findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MattermostSettingsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('mattermost_settings');
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
            ->scalar('webhook_url')
            ->maxLength('webhook_url', 255)
            ->notEmptyString('webhook_url');

        $validator
            ->boolean('two_way')
            ->notEmptyString('two_way');


        $validator
            ->maxLength('apikey', 255)
            ->notEmptyString('apikey', __('For Two-way integration an API key is required.'), function ($context) {
                return (isset($context['data']['two_way']) && $context['data']['two_way'] === true);
            });

        $validator
            ->boolean('use_proxy')
            ->notEmptyString('use_proxy');

        return $validator;
    }

    /**
     * @return array
     */
    public function getMattermostSettings() {
        $default = [
            'webhook_url' => '',
            'two_way'     => true,
            'apikey'      => '',
            'use_proxy'   => false
        ];

        $result = $this->unhydratedFind()
            ->where([
                'id' => 1
            ])
            ->first();

        if (empty($result)) {
            return $default;
        }

        return $result;
    }

    /**
     * @return \Cake\Datasource\EntityInterface
     */
    public function getMattermostSettingsEntity() {
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
