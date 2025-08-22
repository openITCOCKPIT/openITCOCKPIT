<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;

/**
 * Proxies Model
 *
 * @method \App\Model\Entity\Proxy get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Proxy newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Proxy[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Proxy|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Proxy|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Proxy patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Proxy[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Proxy findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class ProxiesTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('proxies');
        $this->setDisplayField('id');
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
            ->scalar('ipaddress')
            ->maxLength('ipaddress', 255)
            ->requirePresence('ipaddress', 'create')
            ->notEmptyString('ipaddress');

        $validator
            ->integer('port', __('This field needs to be numeric.'))
            ->requirePresence('port', 'create')
            ->greaterThanOrEqual('port', 0, __('Port needs to be between 0 and 65535'))
            ->lessThanOrEqual('port', 65535, __('Port needs to be between 0 and 65535'))
            ->notEmptyString('port');

        $validator
            ->boolean('enabled')
            ->requirePresence('enabled', 'create')
            ->notEmptyString('enabled');

        return $validator;
    }

    /**
     * Get Proxy Settings
     * @return array
     */
    public function getSettings() {
        $result = $this->find()->first();
        $settings = ['ipaddress' => '', 'port' => 0, 'enabled' => false];
        if (!is_null($result)) {
            $proxy = $result->toArray();
            $settings = Hash::merge($settings, $proxy);
        }
        return $settings;
    }
}
