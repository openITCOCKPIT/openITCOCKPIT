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

use App\Lib\Traits\Cake2ResultTableTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ConfigurationFiles Model
 *
 * @method \App\Model\Entity\ConfigurationFile get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ConfigurationFile newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ConfigurationFile[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ConfigurationFile|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ConfigurationFile saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ConfigurationFile patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ConfigurationFile[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ConfigurationFile findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ConfigurationFilesTable extends Table {

    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('configuration_files');
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
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('config_file')
            ->maxLength('config_file', 255)
            ->requirePresence('config_file', 'create')
            ->notEmptyFile('config_file');

        $validator
            ->scalar('key')
            ->maxLength('key', 2000)
            ->requirePresence('key', 'create')
            ->notEmptyString('key');

        $validator
            ->scalar('value')
            ->maxLength('value', 2000)
            ->requirePresence('value', 'create')
            ->notEmptyString('value');

        return $validator;
    }

    /**
     * @param string $configFile
     * @return array|null
     */
    public function getConfigValuesByConfigFile($configFile) {
        $query = $this->find()
            ->where([
                'ConfigurationFiles.config_file' => $configFile
            ])
            ->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }


    /**
     * @param $configFile
     * @param array $records
     * @return bool|\Cake\Datasource\EntityInterface[]|\Cake\Datasource\ResultSetInterface
     * @throws \Exception
     */
    public function saveConfigurationValuesForConfigFile($configFile, $records) {
        $this->deleteAll([
            'ConfigurationFiles.config_file' => $configFile
        ]);

        //Convert Cake2 schema to cake4
        $entities = [];
        foreach ($records as $record) {
            $entities[] = $this->newEntity($record['ConfigurationFile']);
        }

        return $this->saveMany($entities);
    }

    /**
     * @param array $currentConfiguration from Cake's findAll
     * @param array $newConfiguration for Cake's saveAll
     * @return bool
     */
    public function hasChanged($currentConfiguration, $newConfiguration) {
        $currentConfigKeyValue = [];
        foreach ($currentConfiguration as $record) {
            $key = $record['ConfigurationFile']['key'];
            $value = $record['ConfigurationFile']['value'];
            $currentConfigKeyValue[$key] = $value;
        }

        foreach ($newConfiguration as $record) {
            $key = $record['ConfigurationFile']['key'];
            $value = $record['ConfigurationFile']['value'];

            if (!isset($currentConfigKeyValue[$key])) {
                //Key not found in old configuration
                //mark configuration file to rewrite
                return true;
            }

            if ($currentConfigKeyValue[$key] != $value) {
                //Value has changed
                //mark configuration file to rewrite
                return true;
            }
        }
        return false;
    }
}
