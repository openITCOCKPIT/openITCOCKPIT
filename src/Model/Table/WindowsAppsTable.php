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
use App\Model\Entity\WindowsAppsHost;
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
 * WindowsApps Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsToMany $Hosts
 *
 * @method \App\Model\Entity\WindowsApp newEmptyEntity()
 * @method \App\Model\Entity\WindowsApp newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsApp> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WindowsApp get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\WindowsApp findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\WindowsApp patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsApp> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WindowsApp|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WindowsApp saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsApp>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsApp> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsApp>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsApp> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WindowsAppsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('windows_apps');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('WindowsAppsHosts', [
            'foreignKey' => 'windows_app_id',
            'className'  => WindowsAppsHostsTable::class,
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
            ->scalar('publisher')
            ->maxLength('publisher', 255)
            ->allowEmptyString('publisher');

        return $validator;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['WindowsApps.id' => $id]);
    }

    public function getAppById($id) {
        $query = $this->unhydratedFind()
            ->where([
                'WindowsApps.id' => $id
            ])
            ->firstOrFail();

        return $query;
    }

    /**
     * @return int
     */
    public function getAppsCount(): int {
        $query = $this->find()
            ->count();

        return $query;
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     */
    public function getWindowsAppsWithLimit(?int $limit = null, ?int $offset = null): array {
        $query = $this->unhydratedFind()
            ->select([
                'WindowsApps.id',
                'WindowsApps.name',
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

    public function getAllWindowsAppsAsMap(): array {
        //Multiple queries are faster than one big query
        $appCount = $this->getAppsCount();
        $chunk = 200;
        $queryCount = ceil($appCount / $chunk);
        $apps = [];
        for ($i = 0; $i < $queryCount; $i++) {
            $_apps = $this->getWindowsAppsWithLimit($chunk, ($chunk * $i));
            foreach ($_apps as $_app) {
                $apps[$_app['name']] = $_app['id'];
            }
            unset($_apps);
        }

        return $apps;
    }

    /**
     * Returns all installations of a host. One app can be installed several times in different versions,
     * so the result is a list and not indexed by app.
     *
     * @param int $hostId
     * @return WindowsAppsHost[]
     */
    public function getInstallationsOfHost(int $hostId): array {
        /** @var WindowsAppsHostsTable $WindowsAppsHostsTable */
        $WindowsAppsHostsTable = TableRegistry::getTableLocator()->get('WindowsAppsHosts');

        return $WindowsAppsHostsTable->find()
            ->where([
                'host_id' => $hostId,
            ])
            ->orderBy(['id' => 'asc'])
            ->toArray();
    }

    /**
     * Stores the installed apps a Windows agent reports.
     *
     * An installation is one app (by name) in one version. The same app can be installed several times,
     * e.g. per user and per machine in different versions, each of them is its own row in windows_apps_hosts.
     * The stored installations are compared with the reported ones:
     * - reported but not stored: insert
     * - stored but not reported anymore: delete (uninstalled or updated to another version)
     * - stored more than once: keep one row and delete the rest
     *
     * @param int $hostId
     * @param array $installedApps Apps of the agent with the keys Name, Version and Publisher
     * @return bool
     */
    public function saveAppsForHost(int $hostId, array $installedApps): bool {
        if (empty($installedApps)) {
            return true;
        }

        /** @var WindowsAppsHostsTable $WindowsAppsHostsTable */
        $WindowsAppsHostsTable = TableRegistry::getTableLocator()->get('WindowsAppsHosts');

        // 1. Add apps that are new for the whole system to the catalog (windows_apps)
        // key = app name, value = app id
        $existingApps = $this->getAllWindowsAppsAsMap();

        $newApps = [];
        foreach ($installedApps as $app) {
            if (empty($app['Name']) || empty($app['Version'])) {
                continue;
            }

            if (!isset($existingApps[$app['Name']]) && !isset($newApps[$app['Name']])) {
                $newApps[$app['Name']] = $this->newEntity([
                    'name'      => $app['Name'],
                    'publisher' => $app['Publisher'] ?? null,
                ]);
            }
        }

        if (!empty($newApps)) {
            $this->saveMany($newApps);
            foreach ($newApps as $newApp) {
                $existingApps[$newApp->name] = $newApp->id;
            }
        }

        // 2. Installations the agent reports, one per app and version
        $reportedInstallations = [];
        foreach ($installedApps as $app) {
            if (empty($app['Name']) || empty($app['Version'])) {
                continue;
            }

            if (!isset($existingApps[$app['Name']])) {
                Log::error(sprintf('App %s not found in existing app after insertion.', $app['Name']));
                continue;
            }

            $appId = (int)$existingApps[$app['Name']];
            // Same length as the column, so the comparison with stored versions works
            $version = substr((string)$app['Version'], 0, 64);
            $reportedInstallations[$this->getInstallationKey($appId, $version)] = [
                'host_id'        => $hostId,
                'windows_app_id' => $appId,
                'version'        => $version,
            ];
        }

        // 3. Compare with the stored installations
        $storedKeys = [];
        $idsToDelete = [];
        foreach ($this->getInstallationsOfHost($hostId) as $installation) {
            $key = $this->getInstallationKey($installation->windows_app_id, (string)$installation->version);

            $isStillInstalled = isset($reportedInstallations[$key]);
            $isDuplicate = isset($storedKeys[$key]);
            if (!$isStillInstalled || $isDuplicate) {
                $idsToDelete[] = $installation->id;
                continue;
            }

            $storedKeys[$key] = true;
        }

        $newInstallations = [];
        foreach ($reportedInstallations as $key => $installation) {
            if (!isset($storedKeys[$key])) {
                $newInstallations[] = $WindowsAppsHostsTable->newEntity($installation);
            }
        }

        // 4. Write all changes at once, so the list of the host is never half updated
        $WindowsAppsHostsTable->getConnection()->transactional(function () use ($WindowsAppsHostsTable, $hostId, $idsToDelete, $newInstallations) {
            if (!empty($idsToDelete)) {
                $WindowsAppsHostsTable->deleteAll([
                    'id IN'   => $idsToDelete,
                    'host_id' => $hostId,
                ]);
            }

            if (!empty($newInstallations)) {
                $WindowsAppsHostsTable->saveManyOrFail($newInstallations);
            }
        });

        return true;
    }

    /**
     * One app in one version
     *
     * @param int $appId
     * @param string $version
     * @return string
     */
    private function getInstallationKey(int $appId, string $version): string {
        return $appId . '|' . $version;
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getWindowsAppsForSummary(array $MY_RIGHTS = []): array {
        $all_windows_apps = [
            'totalPackages'      => 0,
            'totalInstallations' => 0,
            'allHosts'           => []
        ];


        $query = $this->unhydratedFind('all')
            ->disableAutoFields()
            ->contain([
                'WindowsAppsHosts' => function (SelectQuery $query) use ($MY_RIGHTS) {
                    $query
                        ->innerJoin(
                            ['Hosts' => 'hosts'],
                            ['Hosts.id = WindowsAppsHosts.host_id']
                        );
                    if (!empty($MY_RIGHTS)) {
                        $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                            'HostsToContainersSharing.host_id = Hosts.id'
                        ]);
                        $query->where([
                            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                        ]);
                    }
                    $query->select([
                        'WindowsAppsHosts.windows_app_id',
                        'WindowsAppsHosts.version',
                        'WindowsAppsHosts.host_id'
                    ])
                        ->where([
                            'Hosts.disabled' => 0
                        ])->disableAutoFields();
                    return $query;
                }
            ]);
        $result = $query->toArray();
        if (empty($result)) {
            return $all_windows_apps;
        }

        foreach ($result as $windows_app) {
            $all_windows_apps['totalPackages']++;
            foreach ($windows_app['windows_apps_hosts'] as $hostApp) {
                $all_windows_apps['totalInstallations']++;
                $all_windows_apps['allHosts'][$hostApp['host_id']] = $hostApp['host_id'];
            }
        }
        return $all_windows_apps;
    }

    public function getWindowsAppsIndex(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        /** @var WindowsAppsHostsTable $WindowsAppsHostsTable */
        $WindowsAppsHostsTable = TableRegistry::getTableLocator()->get('WindowsAppsHosts');
        $query = $this->unhydratedFind()
            ->select([
                'WindowsApps.id',
                'WindowsApps.name',
                'WindowsApps.publisher',
                'WindowsApps.created',
                'WindowsApps.modified',
            ])
            ->contain([
                'WindowsAppsHosts' => function (SelectQuery $query) use ($MY_RIGHTS) {
                    $query->select([
                        'WindowsAppsHosts.windows_app_id',
                        'WindowsAppsHosts.host_id'

                    ])->innerJoin(
                        ['Hosts' => 'hosts'],
                        ['Hosts.id = WindowsAppsHosts.host_id']
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

        if (!empty($where)) {
            $query->where($where);
        }


        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('WindowsApps.name', 'asc'),
                ['WindowsApps.id' => 'asc']
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
