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

use App\Lib\PluginManager;
use App\Lib\Traits\Cake2ResultTableTrait;
use App\Model\Entity\Cronjob;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Cronjobs Model
 *
 * @property \App\Model\Table\CronschedulesTable|\Cake\ORM\Association\HasMany $Cronschedules
 *
 * @method \App\Model\Entity\Cronjob get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Cronjob newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Cronjob[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Cronjob|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Cronjob|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Cronjob patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Cronjob[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Cronjob findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CronjobsTable extends Table {


    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->addBehavior('Timestamp');

        $this->setTable('cronjobs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->hasOne('Cronschedules', [
            'foreignKey' => 'cronjob_id'
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('task')
            ->maxLength('task', 255)
            ->requirePresence('task', 'create')
            ->allowEmptyString('task', null, false);

        $validator
            ->scalar('plugin')
            ->maxLength('plugin', 255)
            ->requirePresence('plugin', 'create')
            ->allowEmptyString('plugin', null, false);

        $validator
            ->integer('interval')
            ->allowEmptyString('interval');

        $validator
            ->boolean('enabled')
            ->requirePresence('enabled', 'create')
            ->allowEmptyString('enabled', null, false);

        $validator
            ->scalar('priority')
            ->requirePresence('priority', 'create')
            ->allowEmptyString('priority', null, false)
            ->inList('priority', ['low', 'high'], __('The priority must be one of the following: low, high.'));

        return $validator;
    }

    /**
     * @return array
     */
    public function getCronjobs() {
        $query = $this->find()->contain([
            'Cronschedules'
        ]);
        if (!is_null($query)) {
            return $this->formatResultAsCake2($query->disableHydration()->toArray());
        }
        return [];
    }

    /**
     * @param null $id Cronjob ID
     * @return array
     */
    public function getCronjob($id = null) {
        $query = $this->get($id, contain: [
            'Cronschedules'
        ]);
        if (!is_null($query)) {
            return $this->formatFirstResultAsCake2($query->toArray());
        }
        return [];
    }

    /**
     * @return array
     */
    public function getEnabledCronjobs($priority = Cronjob::PRIORITY_LOW) {
        $query = $this->find()
            ->where([
                'enabled'  => 1,
                'priority' => $priority
            ])
            ->contain('Cronschedules')
            ->disableHydration();

        return $this->formatResultAsCake2($query->toArray());
    }

    public function getEnabledCronjobsLowPriority() {
        return $this->getEnabledCronjobs(Cronjob::PRIORITY_LOW);
    }


    public function getEnabledCronjobsHighPriority() {
        return $this->getEnabledCronjobs(Cronjob::PRIORITY_HIGH);
    }

    /**
     * @return array
     */
    public function fetchPlugins() {
        $plugins = [];
        $plugins['Core'] = 'Core';

        foreach (PluginManager::getAvailablePlugins() as $pluginName) {
            $plugins[$pluginName] = $pluginName;
        }

        return $plugins;
    }

    /**
     * @param $pluginName
     * @return array
     */
    public function fetchTasks($pluginName) {
        $return = [];
        if ($pluginName == 'Core') {
            if (is_dir(APP . 'Command')) {
                $result = scandir(APP . 'Command/');
                if (!empty($result) && is_array($result)) {
                    foreach ($result as $file) {
                        if ($file != '.' && $file != '..' && $file != 'empty' && $file != '.empty') {
                            $taskName = str_replace('Command.php', '', $file);
                            $return[$taskName] = $taskName;
                        }
                    }
                }
            }
        } else {
            if (is_dir(APP . '../plugins/' . $pluginName . '/src/Command/')) {
                $result = scandir(APP . '../plugins/' . $pluginName . '/src/Command/');
                if (!empty($result) && is_array($result)) {
                    foreach ($result as $file) {
                        if ($file != '.' && $file != '..' && $file != 'empty' && $file != '.empty') {
                            $taskName = str_replace('Command.php', '', $file);
                            $return[$taskName] = $taskName;
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @param $task
     * @param string $plugin
     * @return bool
     */
    public function checkForCronjob($task, $plugin = 'Core') {
        $query = $this->find()
            ->where([
                'task'   => $task,
                'plugin' => $plugin
            ])
            ->first();
        if (!is_null($query)) {
            return true;
        }
        return false;
    }
}
