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
 * PackagesHostDetails Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\PackagesHostDetail newEmptyEntity()
 * @method \App\Model\Entity\PackagesHostDetail newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\PackagesHostDetail> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PackagesHostDetail findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\PackagesHostDetail> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PackagesHostDetailsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('packages_host_details');
        $this->setDisplayField('os_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
        ]);

        $this->hasMany('PackagesLinuxHosts', [
            'foreignKey' => 'host_id',
            'bindingKey' => 'host_id',
            'conditions' => [
                'PackagesLinuxHosts.needs_update' => 1
            ],
            'className'  => PackagesLinuxHostsTable::class
        ]);

        $this->hasMany('WindowsUpdatesHosts', [
            'foreignKey' => 'host_id',
            'bindingKey' => 'host_id',
            'className'  => WindowsUpdatesHostsTable::class
        ]);

        $this->hasMany('MacosUpdatesHosts', [
            'foreignKey' => 'host_id',
            'bindingKey' => 'host_id',
            'className'  => MacosUpdatesHostsTable::class
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
            ->integer('host_id')
            ->notEmptyString('host_id');

        $validator
            ->scalar('os_type')
            ->maxLength('os_type', 255)
            ->requirePresence('os_type', 'create')
            ->notEmptyString('os_type');

        $validator
            ->scalar('os_name')
            ->maxLength('os_name', 255)
            ->requirePresence('os_name', 'create')
            ->notEmptyString('os_name');

        $validator
            ->scalar('os_version')
            ->maxLength('os_version', 255)
            ->requirePresence('os_version', 'create')
            ->allowEmptyString('os_version');

        $validator
            ->scalar('os_family')
            ->maxLength('os_family', 255)
            ->allowEmptyString('os_family');

        $validator
            ->scalar('agent_version')
            ->maxLength('agent_version', 15)
            ->requirePresence('agent_version', 'create')
            ->notEmptyString('agent_version');

        $validator
            ->boolean('reboot_required')
            ->notEmptyString('reboot_required');

        $validator
            ->requirePresence('system_uptime', 'create')
            ->allowEmptyString('system_uptime');

        $validator
            ->dateTime('last_update')
            ->requirePresence('last_update', 'create')
            ->notEmptyDateTime('last_update');

        $validator
            ->scalar('available_updates')
            ->integer('available_updates')
            ->allowEmptyString('available_updates');

        $validator
            ->scalar('available_security_updates')
            ->integer('available_security_updates')
            ->allowEmptyString('available_security_updates');

        $validator
            ->scalar('last_error')
            ->maxLength('last_error', 1000)
            ->allowEmptyString('last_error');

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
        $rules->add($rules->existsIn(['host_id'], 'Hosts'), ['errorField' => 'host_id']);

        return $rules;
    }

    /**
     * @param int $hostId
     * @param array $details
     * @return \App\Model\Entity\PackagesHostDetail|\Cake\Datasource\EntityInterface|false
     */
    public function updateHostDetails(int $hostId, array $details) {
        $entity = $this->find()
            ->where(['host_id' => $hostId])
            ->first();

        if (!$entity) {
            $entity = $this->newEmptyEntity();
            $entity->host_id = $hostId;
        }

        $entity->setAccess('host_id', false);
        $entity = $this->patchEntity($entity, $details);
        return $this->save($entity);
    }

    public function getSummary(GenericFilter $GenericFilter, array $MY_RIGHTS = []): array {

        $summary = [
            'totalHosts'   => 0,
            'linuxHosts'   => 0,
            'windowsHosts' => 0,
            'macosHosts'   => 0,

            'totalRebootRequired'   => 0,
            'linuxRebootRequired'   => 0,
            'windowsRebootRequired' => 0,
            'macosRebootRequired'   => 0,

            'totalOutdatedPackages'   => 0,
            'linuxOutdatedPackages'   => 0,
            'windowsOutdatedPackages' => 0,
            'macosOutdatedPackages'   => 0,
        ];


        $query = $this->find()
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = PackagesHostDetails.host_id']
            )
            ->where([
                'Hosts.disabled' => 0
            ]);

        $where = $GenericFilter->genericFilters();
        if (!empty($where)) {
            $query->where($where);
        }

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $result = $query->toArray();
        if (empty($result)) {
            return $summary;
        }

        foreach ($result as $entity) {
            $summary['totalHosts']++;
            if ($entity->reboot_required) {
                $summary['totalRebootRequired']++;
            }

            $summary['totalOutdatedPackages'] += (int)$entity->available_updates;

            switch (strtolower($entity->os_type)) {
                case 'linux':
                    $summary['linuxHosts']++;
                    if ($entity->reboot_required) {
                        $summary['linuxRebootRequired']++;
                    }
                    $summary['linuxOutdatedPackages'] += (int)$entity->available_updates;
                    break;
                case 'windows':
                    $summary['windowsHosts']++;
                    if ($entity->reboot_required) {
                        $summary['windowsRebootRequired']++;
                    }
                    $summary['windowsOutdatedPackages'] += (int)$entity->available_updates;
                    break;
                case 'macos':
                    $summary['macosHosts']++;
                    if ($entity->reboot_required) {
                        $summary['macosRebootRequired']++;
                    }
                    $summary['macosOutdatedPackages'] += (int)$entity->available_updates;
                    break;
            }
        }

        return $summary;
    }


    /**
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getPatchstatusIndex(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->find()
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = PackagesHostDetails.host_id']
            )
            ->contain([
                'Hosts'               => function (Query $query) {
                    return $query->select([
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.uuid',
                        'Hosts.container_id',
                    ]);
                },
                'PackagesLinuxHosts'  => function (Query $query) {
                    return $query->select([
                        'PackagesLinuxHosts.package_linux_id',
                        'PackagesLinuxHosts.host_id',
                        'PackagesLinuxHosts.is_security_update'
                    ]);
                },
                'WindowsUpdatesHosts' => function (Query $query) {
                    return $query->select([
                        'WindowsUpdatesHosts.windows_update_id',
                        'WindowsUpdatesHosts.host_id',
                        'WindowsUpdatesHosts.is_security_update'
                    ]);
                },
                'MacosUpdatesHosts'   => function (Query $query) {
                    return $query->select([
                        'MacosUpdatesHosts.macos_update_id',
                        'MacosUpdatesHosts.host_id',
                    ]);
                },
            ])
            ->where([
                'Hosts.disabled' => 0
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
            $GenericFilter->getOrderForPaginator('Hosts.name', 'asc')
        )->groupBy(['Hosts.id']);

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
     * @return mixed
     */
    public function getPackagesHostDetailsByHostId($hostId) {
        $query = $this->find()
            ->where([
                'host_id' => $hostId
            ])->first();

        if (is_null($query)) {
            return [];
        }
        return $query->toArray();

    }
}
