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
use App\Model\Entity\MacosUpdatesHost;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * MacosUpdatesHosts Model
 *
 * @property \App\Model\Table\MacosUpdatesTable&\Cake\ORM\Association\BelongsTo $MacosUpdates
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\MacosUpdatesHost newEmptyEntity()
 * @method \App\Model\Entity\MacosUpdatesHost newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosUpdatesHost> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MacosUpdatesHost get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MacosUpdatesHost findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MacosUpdatesHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosUpdatesHost> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MacosUpdatesHost|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MacosUpdatesHost saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdatesHost>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdatesHost> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdatesHost>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdatesHost> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MacosUpdatesHostsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('macos_updates_hosts');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('MacosUpdates', [
            'foreignKey' => 'macos_update_id',
            'joinType'   => 'INNER',
        ]);
        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
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
            ->notEmptyString('macos_update_id');

        $validator
            ->integer('host_id')
            ->notEmptyString('host_id');

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
        $rules->add($rules->existsIn(['macos_update_id'], 'MacosUpdates'), ['errorField' => 'macos_update_id']);
        $rules->add($rules->existsIn(['host_id'], 'Hosts'), ['errorField' => 'host_id']);

        return $rules;
    }

    /**
     * @param int $hostId
     * @return MacosUpdatesHost[]
     */
    public function getAllUpdatesOfHost(int $hostId): array {
        $query = $this->find()
            ->where([
                'MacosUpdatesHosts.host_id' => $hostId
            ])
            ->contain([
                'MacosUpdates' => function (Query $query) {
                    return $query->select([
                        'id',
                        'name',
                    ]);
                }
            ]);

        $result = [];
        foreach ($query->toArray() as $item) {
            $result[$item->macos_update_id] = $item;
        }


        return $result;
    }

    public function getUpdateWithHost(int $updateId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->find()
            ->select([
                'MacosUpdatesHosts.id',
                'MacosUpdatesHosts.macos_update_id',
                'MacosUpdatesHosts.host_id',
                'MacosUpdatesHosts.created',
                'MacosUpdatesHosts.modified',
                'Hosts.id',
                'Hosts.name',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = MacosUpdatesHosts.host_id']
            );

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->where([
            'Hosts.disabled' => 0
        ])->contain([
            'MacosUpdates' => function (Query $query) {
                $query->select([
                    'MacosUpdates.name',
                    'MacosUpdates.description',
                    'MacosUpdates.version'
                ])->disableAutoFields();

                return $query;
            }
        ]);

        $where = $GenericFilter->genericFilters();

        if (!empty($where)) {
            $query->where($where);
        }

        $query->where([
            'MacosUpdatesHosts.macos_update_id' => $updateId
        ]);


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('Hosts.name', 'asc'),
                ['MacosUpdatesHosts.id' => 'asc']
            )
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
     * @param int $hostId
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getUpdatesOfHost(int $hostId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->find()
            ->select([
                'MacosUpdatesHosts.id',
                'MacosUpdatesHosts.macos_update_id',
                'MacosUpdatesHosts.host_id',
                'MacosUpdatesHosts.created',
                'MacosUpdatesHosts.modified',
                'Hosts.id',
                'Hosts.name',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = MacosUpdatesHosts.host_id']
            );

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->where([
            'Hosts.disabled' => 0
        ])->contain([
            'MacosUpdates' => function (Query $query) {
                $query->select([
                    'MacosUpdates.name',
                    'MacosUpdates.description',
                    'MacosUpdates.version'
                ])->disableAutoFields();

                return $query;
            }
        ]);

        $where = $GenericFilter->genericFilters();

        if (!empty($where)) {
            $query->where($where);
        }

        $query->where([
            'MacosUpdatesHosts.host_id' => $hostId
        ]);


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('MacosUpdates.name', 'asc'),
                ['MacosUpdatesHosts.id' => 'asc']
            )
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

    /**
     * @param $hostId
     * @return array
     */
    public function getUpdatesByHostId($hostId): array {
        return $this->find()
            ->where([
                'host_id' => $hostId
            ])
            ->disableHydration()
            ->toArray();
    }
}
