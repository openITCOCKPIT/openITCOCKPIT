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
use App\Model\Entity\WindowsUpdatesHost;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * WindowsUpdatesHosts Model
 *
 * @property \App\Model\Table\WindowsUpdatesTable&\Cake\ORM\Association\BelongsTo $WindowsUpdates
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\WindowsUpdatesHost newEmptyEntity()
 * @method \App\Model\Entity\WindowsUpdatesHost newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsUpdatesHost> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WindowsUpdatesHost get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\WindowsUpdatesHost findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\WindowsUpdatesHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsUpdatesHost> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WindowsUpdatesHost|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WindowsUpdatesHost saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdatesHost>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdatesHost> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdatesHost>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdatesHost>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdatesHost> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WindowsUpdatesHostsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('windows_updates_hosts');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('WindowsUpdates', [
            'foreignKey' => 'windows_update_id',
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
            ->notEmptyString('windows_update_id');

        $validator
            ->integer('host_id')
            ->notEmptyString('host_id');

        $validator
            ->boolean('reboot_required')
            ->notEmptyString('reboot_required');

        $validator
            ->boolean('is_security_update')
            ->notEmptyString('is_security_update');

        $validator
            ->boolean('is_optional')
            ->notEmptyString('is_optional');

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
        $rules->add($rules->existsIn(['windows_update_id'], 'WindowsUpdates'), ['errorField' => 'windows_update_id']);
        $rules->add($rules->existsIn(['host_id'], 'Hosts'), ['errorField' => 'host_id']);

        return $rules;
    }

    /**
     * @param int $hostId
     * @return WindowsUpdatesHost[]
     */
    public function getAllUpdatesOfHost(int $hostId): array {
        $query = $this->find()
            ->where([
                'WindowsUpdatesHosts.host_id' => $hostId
            ])
            ->contain([
                'WindowsUpdates' => function (Query $query) {
                    return $query->select([
                        'id',
                        'update_id',
                        'name',
                    ]);
                }
            ]);

        $result = [];
        foreach ($query->toArray() as $item) {
            $result[$item->windows_update_id] = $item;
        }


        return $result;
    }


    public function getUpdateWithHost(int $updateId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->find()
            ->select([
                'WindowsUpdatesHosts.id',
                'WindowsUpdatesHosts.windows_update_id',
                'WindowsUpdatesHosts.host_id',
                'WindowsUpdatesHosts.reboot_required',
                'WindowsUpdatesHosts.is_security_update',
                'WindowsUpdatesHosts.created',
                'WindowsUpdatesHosts.modified',
                'Hosts.id',
                'Hosts.name',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = WindowsUpdatesHosts.host_id']
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
            'WindowsUpdates' => function (Query $query) {
                $query->select([
                    'WindowsUpdates.name',
                    'WindowsUpdates.description',
                    'WindowsUpdates.kbarticle_ids'
                ])->disableAutoFields();

                return $query;
            }
        ]);

        $where = $GenericFilter->genericFilters();

        if (!empty($where)) {
            $query->where($where);
        }

        $query->where([
            'WindowsUpdatesHosts.windows_update_id' => $updateId
        ]);


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('Hosts.name', 'asc'),
                ['WindowsUpdatesHosts.id' => 'asc']
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
                'WindowsUpdatesHosts.id',
                'WindowsUpdatesHosts.windows_update_id',
                'WindowsUpdatesHosts.host_id',
                'WindowsUpdatesHosts.reboot_required',
                'WindowsUpdatesHosts.is_security_update',
                'WindowsUpdatesHosts.created',
                'WindowsUpdatesHosts.modified',
                'Hosts.id',
                'Hosts.name',
            ])
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = WindowsUpdatesHosts.host_id']
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
            'WindowsUpdates' => function (Query $query) {
                $query->select([
                    'WindowsUpdates.name',
                    'WindowsUpdates.description',
                    'WindowsUpdates.kbarticle_ids',
                    'WindowsUpdates.update_id'
                ])->disableAutoFields();

                return $query;
            }
        ]);

        $where = $GenericFilter->genericFilters();

        if (!empty($where)) {
            $query->where($where);
        }

        $query->where([
            'WindowsUpdatesHosts.host_id' => $hostId
        ]);


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('WindowsUpdates.name', 'asc'),
                ['WindowsUpdatesHosts.id' => 'asc']
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
}
