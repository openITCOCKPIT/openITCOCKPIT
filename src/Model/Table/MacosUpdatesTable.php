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
 * MacosUpdates Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsToMany $Hosts
 *
 * @method \App\Model\Entity\MacosUpdate newEmptyEntity()
 * @method \App\Model\Entity\MacosUpdate newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosUpdate> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MacosUpdate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MacosUpdate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MacosUpdate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosUpdate> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MacosUpdate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MacosUpdate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MacosUpdatesTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('macos_updates');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('MacosUpdatesHosts', [
            'foreignKey' => 'macos_update_id',
            'className'  => MacosUpdatesHostsTable::class,
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
            ->scalar('version')
            ->maxLength('version', 64)
            ->allowEmptyString('version');

        return $validator;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['MacosUpdates.id' => $id]);
    }

    public function getUpdateById($id) {
        $query = $this->unhydratedFind()
            ->where([
                'MacosUpdates.id' => $id
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
        $query->delete('macos_updates')
            ->where(function (QueryExpression $exp, Query\DeleteQuery $query) {
                return $exp->notExists(
                    $this->find()
                        ->select(1)
                        ->from(['macos_updates_hosts'])
                        ->where(['macos_updates_hosts.macos_update_id = macos_updates.id'])
                );
            });

        return $query->execute();
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     */
    public function getMacosUpdatesWithLimit(?int $limit = null, ?int $offset = null): array {
        $query = $this->unhydratedFind()
            ->select([
                'MacosUpdates.id',
                'MacosUpdates.name',
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

    public function getAllMacosUpdatesAsMap() {
        //Multiple queries are faster than one big query
        $updateCount = $this->getUpdatesCount();
        $chunk = 200;
        $queryCount = ceil($updateCount / $chunk);
        $updates = [];
        for ($i = 0; $i < $queryCount; $i++) {
            $_updates = $this->getMacosUpdatesWithLimit($chunk, ($chunk * $i));
            foreach ($_updates as $_update) {
                $updates[$_update['name']] = $_update['id'];
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
        /** @var MacosUpdatesHostsTable $MacosUpdatesHostsTable */
        $MacosUpdatesHostsTable = TableRegistry::getTableLocator()->get('MacosUpdatesHosts');

        // key = macos_update_id, value = MacosUpdatesHost entity
        $existingUpdatesOfHost = $MacosUpdatesHostsTable->getAllUpdatesOfHost($hostId);

        if (empty($availableUpdates)) {
            if (!empty($existingUpdatesOfHost)) {
                $MacosUpdatesHostsTable->deleteAll(conditions: [
                    'host_id' => $hostId
                ]);
            }

            return true;
        }

        // key = name, value = id
        $existingUpdates = $this->getAllMacosUpdatesAsMap();

        // Fake update for testing
        /*$availableUpdates[] = [
            'Name'        => 'macOS Fake 36.2-35C56',
            'Description' => 'macOS Fake 36.2',
            'Version'     => '36.2',
        ];*/
        foreach ($availableUpdates as $update) {
            // [
            //     'Name'        => 'macOS Tahoe 26.2-25C56',
            //     'Description' => 'macOS Tahoe 26.2',
            //     'Version'     => '26.2',
            // ];

            if (empty($update['Name'])) {
                continue;
            }

            if (!isset($existingUpdates[$update['Name']])) {
                // New update - add to macos_updates
                $desc = null;
                if (isset($update['Description'])) {
                    $desc = substr($update['Description'], 0, 512);
                }

                $newUpdates[] = $this->newEntity([
                    'name'        => $update['Name'],
                    'description' => $desc,
                    'version'     => $update['Version'],
                ]);
            }
        }

        if (!empty($newUpdates)) {
            $this->saveMany($newUpdates);

            // Add new update to $existingUpdates map
            foreach ($newUpdates as $newUpdate) {
                $existingUpdates[$newUpdate->name] = $newUpdate->id;
            }
        }

        foreach ($availableUpdates as $update) {
            if (empty($update['Name'])) {
                continue;
            }

            if (!isset($existingUpdates[$update['Name']])) {
                Log::error(sprintf('Update %s not found in existing updates during update processing.', $update['Name']));
                continue;
            }

            $updateId = $existingUpdates[$update['Name']];
            $updatesForDiff[$updateId] = $update['Name'];

            if (!isset($existingUpdatesOfHost[$updateId])) {
                // Create new MacosUpdatesHosts entry
                // For macOS we do not need to update existing updates as for now wo do not save any extra info
                $entity = $MacosUpdatesHostsTable->newEntity([
                    'macos_update_id' => $updateId,
                    'host_id'         => $hostId
                ]);
                if ($MacosUpdatesHostsTable->save($entity)) {
                    $existingUpdatesOfHost[$entity->macos_update_id] = $entity;
                }
            }
        }

        // If the update got installed (is no longer present in $availableUpdates), remove the MacosUpdatesHost entry
        $databaseUpdates = [];
        foreach ($existingUpdatesOfHost as $update) {
            $databaseUpdates[$update->macos_update_id] = $update->id;
        }

        $updatesThatGotInstalledOnHost = (array_diff_key($databaseUpdates, $updatesForDiff));
        if (!empty($updatesThatGotInstalledOnHost)) {
            $MacosUpdatesHostsTable->deleteAll(conditions: [
                'id IN'   => array_values($updatesThatGotInstalledOnHost),
                'host_id' => $hostId
            ]);
        }
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMacosUpdatesForSummary(array $MY_RIGHTS = []): array {
        $all_macos_updates = [
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
                'MacosUpdates.id',
                'MacosUpdates.name',
                'MacosUpdates.description',
                'MacosUpdates.version'
            ])->contain([
                'MacosUpdatesHosts' => function (SelectQuery $q) use ($MY_RIGHTS) {
                    $query = $q->select([
                        'macos_update_id',
                        'host_id'
                    ])->innerJoin(
                        ['Hosts' => 'hosts'],
                        ['Hosts.id = MacosUpdatesHosts.host_id']
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
            return $all_macos_updates;
        }
        foreach ($result as $macos_update) {
            $all_macos_updates['updatesAvailable']++;
            $all_macos_updates['securityUpdates']++;
            foreach ($macos_update['macos_updates_hosts'] as $macos_update_host) {
                $all_macos_updates['hostsWithUpdates'][$macos_update_host['host_id']] = $macos_update_host['host_id'];
                $all_macos_updates['hostsWithSecurityUpdates'][$macos_update_host['host_id']] = $macos_update_host['host_id'];
            }
        }
        $all_macos_updates['hostsWithUpdates'] = array_values($all_macos_updates['hostsWithUpdates']);
        $all_macos_updates['hostsWithSecurityUpdates'] = array_values($all_macos_updates['hostsWithSecurityUpdates']);

        return $all_macos_updates;
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMacosUpdatesIndex(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        /** @var MacosUpdatesHostsTable $MacosUpdatesHostsTable */
        $MacosUpdatesHostsTable = TableRegistry::getTableLocator()->get('MacosUpdatesHosts');
        $subQueryForUpdates = $MacosUpdatesHostsTable->find();
        $subQueryForUpdates->select(
            [$subQueryForUpdates->func()->count('macos_update_id')])
            ->where(['`MacosUpdates`.`id` =`MacosUpdatesHosts`.`macos_update_id`']);

        $subQueryForSecurityUpdates = $MacosUpdatesHostsTable->find();

        $query = $this->unhydratedFind()
            ->select([
                'MacosUpdates.id',
                'MacosUpdates.name',
                'MacosUpdates.description',
                'MacosUpdates.version',
                'MacosUpdates.created',
                'MacosUpdates.modified',
                'available_updates' => $subQueryForUpdates,
            ])
            ->contain([
                'MacosUpdatesHosts' => function (SelectQuery $query) use ($MY_RIGHTS) {
                    $query->select([
                        'MacosUpdatesHosts.host_id',
                        'MacosUpdatesHosts.macos_update_id'

                    ])->innerJoin(
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
                    ])->disableAutoFields();

                    return $query;
                }
            ]);

        $where = $GenericFilter->genericFilters();

        if (isset($where['available_updates >=']) && $where['available_updates >='] > 0) {
            $query->having(['available_updates >' => 0]);
            unset($where['available_updates >=']);
        }

        if (!empty($where)) {
            $query->where($where);
        }


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('MacosUpdates.name', 'asc'),
                ['MacosUpdates.id' => 'asc']
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
