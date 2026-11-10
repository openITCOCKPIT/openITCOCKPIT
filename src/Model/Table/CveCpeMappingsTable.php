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

use App\Model\Entity\CveCpeMapping;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeCondition;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeRule;

/**
 * CveCpeMappings Model
 *
 * Rules that map installed applications to a CPE product. A rule has one condition and an optional second one,
 * both have to match. A condition compares one field of the application:
 * - name:      Name as shown by the operating system, Windows and macOS
 * - publisher: Windows only, macOS reports no publisher
 * - bundle_id: macOS only, e.g. com.google.Chrome. Does not depend on the language, so it is the best choice
 *
 * @property \App\Model\Table\CveCpeProductsTable&\Cake\ORM\Association\BelongsTo $CveCpeProducts
 *
 * @method \App\Model\Entity\CveCpeMapping newEmptyEntity()
 * @method \App\Model\Entity\CveCpeMapping newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CveCpeMapping patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveCpeMapping|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveCpeMappingsTable extends Table {

    private const PLATFORMS = ['windows', 'macos'];

    private const FIELDS = [CpeCondition::FIELD_NAME, CpeCondition::FIELD_PUBLISHER, CpeCondition::FIELD_BUNDLE_ID];

    private const MATCHES = [CpeCondition::MATCH_EQUALS, CpeCondition::MATCH_STARTS_WITH, CpeCondition::MATCH_CONTAINS, CpeCondition::MATCH_ENDS_WITH];

    /** Fields the agent reports per platform */
    private const FIELDS_OF_PLATFORM = [
        'windows' => [CpeCondition::FIELD_NAME, CpeCondition::FIELD_PUBLISHER],
        'macos'   => [CpeCondition::FIELD_NAME, CpeCondition::FIELD_BUNDLE_ID],
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_cpe_mappings');
        $this->setDisplayField('first_value');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('CveCpeProducts', [
            'foreignKey' => 'cve_cpe_product_id',
            'joinType'   => 'INNER',
        ]);
    }

    /**
     * An empty second condition from a form means "no second condition"
     *
     * @param EventInterface $event
     * @param ArrayObject $data
     * @param ArrayObject $options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
        foreach (['second_field', 'second_match', 'second_value'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
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
            ->inList('platform', self::PLATFORMS, __('Please select a platform.'))
            ->requirePresence('platform', 'create')
            ->notEmptyString('platform');

        $validator
            ->inList('first_field', self::FIELDS, __('Please select a field.'))
            ->requirePresence('first_field', 'create')
            ->notEmptyString('first_field');

        $validator
            ->inList('first_match', self::MATCHES, __('Please select a comparison.'))
            ->requirePresence('first_match', 'create')
            ->notEmptyString('first_match');

        $validator
            ->scalar('first_value')
            ->maxLength('first_value', 255)
            ->requirePresence('first_value', 'create')
            ->notEmptyString('first_value', __('Please enter a value.'));

        $validator
            ->inList('second_field', self::FIELDS, __('Please select a field.'))
            ->allowEmptyString('second_field');

        $validator
            ->inList('second_match', self::MATCHES, __('Please select a comparison.'))
            ->allowEmptyString('second_match');

        $validator
            ->scalar('second_value')
            ->maxLength('second_value', 255)
            ->allowEmptyString('second_value');

        return $validator;
    }

    /**
     * Rules that need more than one field or the database.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['cve_cpe_product_id'], 'CveCpeProducts'), [
            'errorField' => 'cve_cpe_product_id',
        ]);

        // The second condition is optional, but if it is used, it needs a field, a comparison and a value
        $rules->add(function (CveCpeMapping $mapping) {
            $isSet = [
                $mapping->second_field !== null,
                $mapping->second_match !== null,
                $mapping->second_value !== null && $mapping->second_value !== '',
            ];
            return !in_array(true, $isSet, true) || !in_array(false, $isSet, true);
        }, 'secondConditionComplete', [
            'errorField' => 'second_value',
            'message'    => __('The second condition needs a field, a comparison and a value.'),
        ]);

        // A condition on a field the agent does not report for this platform would never match
        $rules->add(function (CveCpeMapping $mapping) {
            $fields = self::FIELDS_OF_PLATFORM[$mapping->platform] ?? [];
            if (!in_array($mapping->first_field, $fields, true)) {
                return false;
            }
            return $mapping->second_field === null || in_array($mapping->second_field, $fields, true);
        }, 'fieldOfPlatform', [
            'errorField' => 'first_field',
            'message'    => __('Publisher is only available on Windows, bundle identifier only on macOS.'),
        ]);

        return $rules;
    }

    /**
     * Returns the rules of all enabled products of one platform, for the CpeMatcher.
     *
     * @param string $platform windows or macos
     * @return CpeRule[]
     */
    public function getRulesOfPlatform(string $platform): array {
        $mappings = $this->find()
            ->contain(['CveCpeProducts'])
            ->where([
                'CveCpeMappings.platform' => $platform,
                'CveCpeProducts.enabled'  => true,
            ])
            ->orderBy(['CveCpeMappings.id' => 'asc'])
            ->all();

        $rules = [];
        foreach ($mappings as $mapping) {
            $rules[] = $this->toCpeRule($mapping);
        }

        return $rules;
    }

    /**
     * @param CveCpeMapping $mapping With the contained CveCpeProduct
     * @return CpeRule
     */
    public function toCpeRule(CveCpeMapping $mapping): CpeRule {
        $secondCondition = null;
        if ($mapping->second_field !== null) {
            $secondCondition = new CpeCondition($mapping->second_field, (string)$mapping->second_match, (string)$mapping->second_value);
        }

        return new CpeRule(
            vendor: $mapping->cve_cpe_product->vendor,
            product: $mapping->cve_cpe_product->product,
            platform: $mapping->platform,
            firstCondition: new CpeCondition($mapping->first_field, $mapping->first_match, $mapping->first_value),
            secondCondition: $secondCondition
        );
    }
}
