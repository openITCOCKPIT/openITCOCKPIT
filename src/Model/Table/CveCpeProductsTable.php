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

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CveCpeProducts Model
 *
 * CPE products of Windows and macOS applications. Each product has rules that map installed applications
 * to it (CveCpeMappings) and the names it has in CVE descriptions (CveCpeProductNames).
 * The default products get created by the InstallSeed, users can add their own.
 *
 * Default products should be disabled instead of deleted: the seed runs with every update
 * and would create them again.
 *
 * @property \App\Model\Table\CveCpeMappingsTable&\Cake\ORM\Association\HasMany $CveCpeMappings
 * @property \App\Model\Table\CveCpeProductNamesTable&\Cake\ORM\Association\HasMany $CveCpeProductNames
 *
 * @method \App\Model\Entity\CveCpeProduct newEmptyEntity()
 * @method \App\Model\Entity\CveCpeProduct newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CveCpeProduct get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CveCpeProduct patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveCpeProduct|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CveCpeProduct saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveCpeProductsTable extends Table {

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_cpe_products');
        $this->setDisplayField('product');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('CveCpeMappings', [
            'foreignKey' => 'cve_cpe_product_id',
            'dependent'  => true,
        ]);

        $this->hasMany('CveCpeProductNames', [
            'foreignKey' => 'cve_cpe_product_id',
            'dependent'  => true,
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
            ->scalar('vendor')
            ->maxLength('vendor', 100)
            ->requirePresence('vendor', 'create')
            ->notEmptyString('vendor', __('Please enter the vendor of the CPE, e.g. google.'));

        $validator
            ->scalar('product')
            ->maxLength('product', 100)
            ->requirePresence('product', 'create')
            ->notEmptyString('product', __('Please enter the product of the CPE, e.g. chrome.'));

        $validator
            ->boolean('enabled')
            ->notEmptyString('enabled');

        $validator
            ->boolean('is_default')
            ->notEmptyString('is_default');

        $validator
            ->boolean('verified')
            ->notEmptyString('verified');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->isUnique(['vendor', 'product']), [
            'errorField' => 'product',
            'message'    => __('This CPE product already exists.'),
        ]);

        return $rules;
    }

    /**
     * Marks products whose CPE found at least one CVE in the Vuls database.
     * A product that never finds anything may be misspelled.
     *
     * @param string[] $cpeKeys e.g. ["google:chrome", "mozilla:firefox"]
     * @return int Number of products that got verified now
     */
    public function markVerified(array $cpeKeys): int {
        $verified = 0;

        foreach ($cpeKeys as $cpeKey) {
            [$vendor, $product] = explode(':', $cpeKey, 2);
            $verified += $this->updateAll(['verified' => true], [
                'vendor'   => $vendor,
                'product'  => $product,
                'verified' => false,
            ]);
        }

        return $verified;
    }
}
