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
use Cake\Database\Expression\QueryExpression;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * WindowsUpdates Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsToMany $Hosts
 *
 * @method \App\Model\Entity\WindowsUpdate newEmptyEntity()
 * @method \App\Model\Entity\WindowsUpdate newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsUpdate> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\WindowsUpdate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsUpdate> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WindowsUpdatesTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('windows_updates');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('WindowsUpdatesHosts', [
            'foreignKey' => 'windows_update_id',
            'className'  => WindowsUpdatesHostsTable::class,
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 512)
            ->allowEmptyString('description');

        $validator
            ->scalar('kbarticle_ids')
            ->maxLength('kbarticle_ids', 512)
            ->allowEmptyString('kbarticle_ids');

        $validator
            ->scalar('update_id')
            ->maxLength('update_id', 255)
            ->allowEmptyString('update_id');

        return $validator;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['WindowsUpdates.id' => $id]);
    }

    public function getUpdateById($id) {
        $query = $this->unhydratedFind()
            ->where([
                'WindowsUpdates.id' => $id
            ])
            ->firstOrFail();

        return $query;
    }

    /**
     * @return int
     */
    public function getUpdatesCount(): int {
        $query = $this->find()
            ->count();

        return $query;
    }

    public function deleteUnusedUpdates() {
        $query = $this->deleteQuery();
        $query->delete('windows_updates')
            ->where(function (QueryExpression $exp, Query\DeleteQuery $query) {
                return $exp->notExists(
                    $this->find()
                        ->select(1)
                        ->from(['windows_updates_hosts'])
                        ->where(['windows_updates_hosts.windows_update_id = windows_updates.id'])
                );
            });

        return $query->execute();
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     */
    public function getWindowsUpdatesWithLimit(?int $limit = null, ?int $offset = null): array {
        $query = $this->unhydratedFind()
            ->select([
                'WindowsUpdates.id',
                'WindowsUpdates.update_id',
            ]);

        if ($limit !== null) {
            $query->limit($limit);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }

        $query->all();
        return $query->toArray();
    }

    public function getAllWindowsUpdatesAsMap() {
        //Multiple queries are faster than one big query
        $updateCount = $this->getUpdatesCount();
        $chunk = 200;
        $queryCount = ceil($updateCount / $chunk);
        $updates = [];
        for ($i = 0; $i < $queryCount; $i++) {
            $_updates = $this->getWindowsUpdatesWithLimit($chunk, ($chunk * $i));
            foreach ($_updates as $_update) {
                $updates[$_update['update_id']] = $_update['id'];
            }
            unset($_updates);
        }

        return $updates;
    }


    /**
     * @param int $hostId
     * @param array $availableUpdates
     * @return true|void
     * @throws \Exception
     */
    public function saveUpdatesForHost(int $hostId, array $availableUpdates) {
        /** @var WindowsUpdatesHostsTable $WindowsUpdatesHostsTable */
        $WindowsUpdatesHostsTable = TableRegistry::getTableLocator()->get('WindowsUpdatesHosts');

        // key = windows_update_id, value = WindowsUpdatesHost entity
        $existingUpdatesOfHost = $WindowsUpdatesHostsTable->getAllUpdatesOfHost($hostId);

        if (empty($availableUpdates)) {
            if (!empty($existingUpdatesOfHost)) {
                $WindowsUpdatesHostsTable->deleteAll(conditions: [
                    'host_id' => $hostId
                ]);
            }

            return true;
        }


        // key = update_id(uuid), value = id
        $existingUpdates = $this->getAllWindowsUpdatesAsMap();

        $newUpdates = [];
        $updatesForDiff = [];

        // Fake update for testing
        /*$availableUpdates[] = [
            'Title'            => 'Fake Windows Update (1.2.3)',
            'Description'      => 'A Fake Windows Update released in January 2026',
            'KBArticleIDs'     => [],
            'IsInstalled'      => false,
            'IsSecurityUpdate' => true,
            'IsOptional'       => false,
            'UpdateID'         => 'b0f9d02b-5d50-4293-aa18-b5922bc915b8',
            'RevisionNumber'   => 1,
            'RebootRequired'   => true
        ];*/
        foreach ($availableUpdates as $update) {
            // [
            //     'Title'            => 'Lenovo Driver Update (1.69.132.0)',
            //     'Description'      => 'Lenovo System  driver update released in  October 2025',
            //     'KBArticleIDs'     => [],
            //     'IsInstalled'      => false,
            //     'IsSecurityUpdate' => false,
            //     'IsOptional'       => false,
            //     'UpdateID'         => 'fb02eeba-e36e-4740-8695-dbe706fee161',
            //     'RevisionNumber'   => 1,
            //     'RebootRequired'   => false
            // ];

            if (empty($update['Title']) || empty($update['UpdateID'])) {
                continue;
            }

            if (!isset($existingUpdates[$update['UpdateID']])) {
                // New update - add to windows_updates
                $desc = null;
                if (isset($update['Description'])) {
                    $desc = substr($update['Description'], 0, 512);
                }

                $newUpdates[] = $this->newEntity([
                    'name'          => $update['Title'],
                    'description'   => $desc,
                    'kbarticle_ids' => implode(',', $update['KBArticleIDs']),
                    'update_id'     => $update['UpdateID'],
                ]);
            }
        }

        if (!empty($newUpdates)) {
            $this->saveMany($newUpdates);

            // Add new update to $existingUpdates map
            foreach ($newUpdates as $newUpdate) {
                $existingUpdates[$newUpdate->update_id] = $newUpdate->id;
            }
        }

        foreach ($availableUpdates as $update) {
            if (empty($update['UpdateID'])) {
                continue;
            }

            if (!isset($existingUpdates[$update['UpdateID']])) {
                Log::error(sprintf('Update %s not found in existing updates during update processing.', $update['UpdateID']));
                continue;
            }

            $updateId = $existingUpdates[$update['UpdateID']];
            $updatesForDiff[$updateId] = $update['UpdateID'];

            if (isset($existingUpdatesOfHost[$updateId])) {
                // Update exists - update it
                $windowsUpdateHost = $existingUpdatesOfHost[$updateId];
                if (
                    $windowsUpdateHost->is_security_update != $update['IsSecurityUpdate'] ||
                    $windowsUpdateHost->reboot_required != $update['RebootRequired'] ||
                    $windowsUpdateHost->is_optional != $update['IsOptional']
                ) {
                    $windowsUpdateHost->is_security_update = !empty($update['IsSecurityUpdate']) ? 1 : 0;
                    $windowsUpdateHost->reboot_required = !empty($update['RebootRequired']) ? 1 : 0;
                    $windowsUpdateHost->is_optional = !empty($update['IsOptional']) ? 1 : 0;
                    if ($WindowsUpdatesHostsTable->save($windowsUpdateHost)) {
                        $existingUpdatesOfHost[$windowsUpdateHost->windows_update_id] = $windowsUpdateHost;
                    }
                }
            } else {
                // Create new WindowsUpdatesHost entry
                $entity = $WindowsUpdatesHostsTable->newEntity([
                    'windows_update_id'  => $updateId,
                    'host_id'            => $hostId,
                    'reboot_required'    => !empty($update['RebootRequired']) ? 1 : 0,
                    'is_security_update' => !empty($update['IsSecurityUpdate']) ? 1 : 0,
                    'is_optional'        => !empty($update['IsOptional']) ? 1 : 0
                ]);
                if ($WindowsUpdatesHostsTable->save($entity)) {
                    $existingUpdatesOfHost[$entity->windows_update_id] = $entity;
                }
            }
        }

        // If the update got installed (is no longer present in $availableUpdates), remove the WindowsUpdatesHost entry
        $databaseUpdates = [];
        foreach ($existingUpdatesOfHost as $update) {
            $databaseUpdates[$update->windows_update_id] = $update->id;
        }

        $updatesThatGotInstalledOnHost = (array_diff_key($databaseUpdates, $updatesForDiff));
        if (!empty($updatesThatGotInstalledOnHost)) {
            $WindowsUpdatesHostsTable->deleteAll(conditions: [
                'id IN'   => array_values($updatesThatGotInstalledOnHost),
                'host_id' => $hostId
            ]);
        }
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getWindowsUpdatesForSummary(array $MY_RIGHTS = []): array {
        $all_windows_updates = [
            'upToDate'                 => 0,
            'updatesAvailable'         => 0,
            'securityUpdates'          => 0,
            'totalInstallations'       => 0,
            'hostsUpToDate'            => [],
            'hostsWithUpdates'         => [],
            'hostsWithSecurityUpdates' => [],
        ];

        $query = $this->unhydratedFind('all')
            ->select([
                'WindowsUpdates.id',
                'WindowsUpdates.name',
                'WindowsUpdates.description',
                'WindowsUpdates.kbarticle_ids',
                'WindowsUpdates.update_id'
            ])->contain([
                'WindowsUpdatesHosts' => function (SelectQuery $q) use ($MY_RIGHTS) {
                    $query = $q->select([
                        'windows_update_id',
                        'host_id',
                        'is_security_update',
                    ])->innerJoin(
                        ['Hosts' => 'hosts'],
                        ['Hosts.id = WindowsUpdatesHosts.host_id']
                    )
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

                    return $query;
                }
            ]);

        $query->disableAutoFields();
        $result = $query->toArray();
        if (empty($result)) {
            return $all_windows_updates;
        }
        foreach ($result as $windows_update) {
            $all_windows_updates['updatesAvailable']++;
            foreach ($windows_update['windows_updates_hosts'] as $windows_update_host) {
                if ($windows_update_host['is_security_update'] === false) {
                    $all_windows_updates['hostsWithUpdates'][$windows_update_host['host_id']] = $windows_update_host['host_id'];
                }
                if ($windows_update_host['is_security_update'] === true) {
                    $all_windows_updates['securityUpdates']++;
                    $all_windows_updates['hostsWithSecurityUpdates'][$windows_update_host['host_id']] = $windows_update_host['host_id'];
                }
            }
        }
        $all_windows_updates['hostsWithUpdates'] = array_values($all_windows_updates['hostsWithUpdates']);
        $all_windows_updates['hostsWithSecurityUpdates'] = array_values($all_windows_updates['hostsWithSecurityUpdates']);

        return $all_windows_updates;
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getWindowsUpdatesIndex(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        /** @var WindowsUpdatesHostsTable $WindowsUpdatesHostsTable */
        $WindowsUpdatesHostsTable = TableRegistry::getTableLocator()->get('WindowsUpdatesHosts');
        $subQueryForUpdates = $WindowsUpdatesHostsTable->find();
        $subQueryForUpdates->select(
            [$subQueryForUpdates->func()->count('windows_update_id')])
            ->where(['`WindowsUpdates`.`id` =`WindowsUpdatesHosts`.`windows_update_id`']);

        $subQueryForSecurityUpdates = $WindowsUpdatesHostsTable->find();
        $subQueryForSecurityUpdates->select(
            [$subQueryForSecurityUpdates->func()->count('windows_update_id')])
            ->where(['`WindowsUpdates`.`id` =`WindowsUpdatesHosts`.`windows_update_id`'])
            ->andWhere([
                'is_security_update' => 1,
            ]);

        $query = $this->unhydratedFind()
            ->select([
                'WindowsUpdates.id',
                'WindowsUpdates.name',
                'WindowsUpdates.description',
                'WindowsUpdates.kbarticle_ids',
                'WindowsUpdates.update_id',
                'WindowsUpdates.created',
                'WindowsUpdates.modified',
                'available_updates'          => $subQueryForUpdates,
                'available_security_updates' => $subQueryForSecurityUpdates,
            ])
            ->contain([
                'WindowsUpdatesHosts' => function (SelectQuery $query) use ($MY_RIGHTS) {
                    $query->select([
                        'WindowsUpdatesHosts.windows_update_id',
                        'WindowsUpdatesHosts.reboot_required',
                        'WindowsUpdatesHosts.is_security_update',
                        'WindowsUpdatesHosts.is_optional',
                        'WindowsUpdatesHosts.host_id'

                    ])->innerJoin(
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
                    ])->disableAutoFields();

                    return $query;
                }
            ]);

        $where = $GenericFilter->genericFilters();
        if (isset($where['available_security_updates >=']) && $where['available_security_updates >='] > 0) {
            $query->having(['available_security_updates >' => 0]);
            unset($where['available_security_updates >=']);
        }

        if (isset($where['available_updates >=']) && $where['available_updates >='] > 0) {
            $query->having(['available_updates >' => 0]);
            unset($where['available_updates >=']);
        }

        if (!empty($where)) {
            $query->where($where);
        }


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('WindowsUpdates.name', 'asc'),
                ['WindowsUpdates.id' => 'asc']
            )
        );

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
