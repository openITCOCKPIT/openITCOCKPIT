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

use App\itnovum\openITCOCKPIT\Core\PackagemanagerRequestHandler;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Registers Model
 *
 * @method \App\Model\Entity\Register get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Register newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Register[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Register|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Register|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Register patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Register[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Register findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RegistersTable extends Table {

    use LocatorAwareTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('registers');
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
            ->scalar('license')
            ->maxLength('license', 37)
            ->requirePresence('license', 'create')
            ->notEmptyString('license');

        return $validator;
    }

    /**
     * @return mixed License Array or null
     */
    public function getLicense() {
        $query = $this->getLicenseEntity();
        if (!empty($query)) {
            return $query->toArray();
        }
        return $query;
    }

    /**
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getLicenseEntity() {
        return $this->find()->first();
    }

    /**
     * @param string $license
     * @return array|bool|null
     */
    public function checkLicenseKey($license) {
        if (empty($license)) {
            return [
                'success' => false,
                'error'   => __('Please enter a license key'),
                'license' => null
            ];
        }

        $PackagemanagerRequestHandler = new PackagemanagerRequestHandler($license);
        $licenseResponse = $PackagemanagerRequestHandler->validateLicense();
        if ($licenseResponse['error'] === false && isset($licenseResponse['license']['license']['License'])) {
            return [
                'success' => true,
                'error'   => null,
                'license' => $licenseResponse['license']['license']['License']
            ];
        }

        if ($licenseResponse['error'] === false) {
            // No HTTP error but we also did no license information back - so invalid
            return [
                'success' => false,
                'error'   => __('Invalid license key'),
                'license' => null
            ];
        }

        // Some HTTP error
        return [
            'success' => false,
            'error'   => $licenseResponse['error_msg'],
            'license' => null
        ];
    }

    public function getCommunityLicenseKey() {
        return 'e5aef99e-817b-0ff5-3f0e-140c1f342792';
    }
}
