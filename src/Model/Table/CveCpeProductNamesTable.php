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
 * CveCpeProductNames Model
 *
 * How a CPE product is written in CVE descriptions, e.g. "Google Chrome" or "Microsoft Edge (Chromium-based)".
 * The PlatformFilter reads the descriptions of the CVEs of a product with these names, to drop CVEs that
 * only affect another operating system ("Google Chrome on Android"). Products without names are never filtered.
 *
 * Usage:
 * $names = $CveCpeProductNamesTable->getNamesByCpe();
 * new \itnovum\openITCOCKPIT\Vuln\Policy\PlatformFilter($names['google:chrome'] ?? [])
 *
 * @property \App\Model\Table\CveCpeProductsTable&\Cake\ORM\Association\BelongsTo $CveCpeProducts
 *
 * @method \App\Model\Entity\CveCpeProductName newEmptyEntity()
 * @method \App\Model\Entity\CveCpeProductName newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CveCpeProductName patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveCpeProductName|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveCpeProductNamesTable extends Table {

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_cpe_product_names');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('CveCpeProducts', [
            'foreignKey' => 'cve_cpe_product_id',
            'joinType'   => 'INNER',
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
            ->integer('cve_cpe_product_id')
            ->requirePresence('cve_cpe_product_id', 'create')
            ->notEmptyString('cve_cpe_product_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            // A very short name like "Go" would match too many descriptions
            ->minLength('name', 3, __('The name needs at least 3 characters.'))
            ->requirePresence('name', 'create')
            ->notEmptyString('name', __('Please enter the name of the product as written in CVE descriptions.'));

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['cve_cpe_product_id'], 'CveCpeProducts'), [
            'errorField' => 'cve_cpe_product_id',
        ]);

        $rules->add($rules->isUnique(['cve_cpe_product_id', 'name']), [
            'errorField' => 'name',
            'message'    => __('This product already has this name.'),
        ]);

        return $rules;
    }

    /**
     * Names of all enabled products, for the PlatformFilter.
     *
     * @return array<string, string[]> Names indexed by "vendor:product", e.g. ['google:chrome' => ['Google Chrome']]
     */
    public function getNamesByCpe(): array {
        $rows = $this->find()
            ->contain(['CveCpeProducts'])
            ->where(['CveCpeProducts.enabled' => true])
            ->orderBy(['CveCpeProductNames.name' => 'asc'])
            ->all();

        $names = [];
        foreach ($rows as $row) {
            $names[$row->cve_cpe_product->vendor . ':' . $row->cve_cpe_product->product][] = $row->name;
        }

        return $names;
    }
}
