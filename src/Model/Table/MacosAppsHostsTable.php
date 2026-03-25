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

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * MacosAppsHosts Model
 *
 * @property \App\Model\Table\MacosAppsTable&\Cake\ORM\Association\BelongsTo $MacosApps
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\MacosAppsHost newEmptyEntity()
 * @method \App\Model\Entity\MacosAppsHost newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosAppsHost> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MacosAppsHost get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MacosAppsHost findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MacosAppsHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosAppsHost> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MacosAppsHost|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MacosAppsHost saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MacosAppsHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosAppsHost>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosAppsHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosAppsHost> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosAppsHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosAppsHost>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosAppsHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosAppsHost> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MacosAppsHostsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('macos_apps_hosts');
        $this->setDisplayField('version');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsTo('MacosApps', [
            'foreignKey' => 'macos_app_id',
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
            ->notEmptyString('macos_app_id');

        $validator
            ->integer('host_id')
            ->notEmptyString('host_id');

        $validator
            ->scalar('version')
            ->maxLength('version', 64)
            ->requirePresence('version', 'create')
            ->notEmptyString('version');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['macos_app_id'], 'MacosApps'), ['errorField' => 'macos_app_id']);
        $rules->add($rules->existsIn(['host_id'], 'Hosts'), ['errorField' => 'host_id']);

        return $rules;
    }

    /**
     * @param int $appId
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsWithApp(int $appId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = MacosAppsHosts.host_id']
            )
            ->contain([
                'Hosts' => function (Query $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                    ]);
                }
            ])
            ->where([
                'Hosts.disabled'              => 0,
                'MacosAppsHosts.macos_app_id' => $appId
            ]);


        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query
            ->orderBy(
                $GenericFilter->getOrderForPaginator('Hosts.name', 'asc')
            )
            ->groupBy(['Hosts.id']);

        $query->disableHydration();

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }

    /**
     * @param $hostId
     * @return int
     */
    public function countByHostId($hostId): int {
        return $this->find()->where(['host_id' => $hostId])->count();
    }

    /**
     * @param int $hostId
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMacosAppsByHost(int $hostId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->find()
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = MacosAppsHosts.host_id']
            )
            ->contain([
                'Hosts' => function (Query $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                    ]);
                },
                'MacosApps'
            ])
            ->where([
                'Hosts.disabled'         => 0,
                'MacosAppsHosts.host_id' => $hostId
            ]);


        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query->orderBy(
            $GenericFilter->getOrderForPaginator('MacosApps.name', 'asc')
        );

        $query->disableHydration();

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }
}
