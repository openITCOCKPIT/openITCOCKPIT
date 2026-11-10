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
use App\Model\Entity\MacosAppsHost;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * MacosApps Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsToMany $Hosts
 *
 * @method \App\Model\Entity\MacosApp newEmptyEntity()
 * @method \App\Model\Entity\MacosApp newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosApp> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MacosApp get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MacosApp findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MacosApp patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosApp> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MacosApp|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MacosApp saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MacosApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosApp>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosApp> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosApp>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosApp>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosApp> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MacosAppsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('macos_apps');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('MacosAppsHosts', [
            'foreignKey' => 'macos_app_id',
            'className'  => MacosAppsHostsTable::class,
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
            ->maxLength('description', 1000)
            ->allowEmptyString('description');

        return $validator;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['MacosApps.id' => $id]);
    }


    public function getAppById($id) {
        $query = $this->unhydratedFind()
            ->where([
                'MacosApps.id' => $id
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
    public function getMacosAppsWithLimit(?int $limit = null, ?int $offset = null): array {
        $query = $this->unhydratedFind()
            ->select([
                'MacosApps.id',
                'MacosApps.name',
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

    public function getAllMacosAppsAsMap(): array {
        //Multiple queries are faster than one big query
        $appCount = $this->getAppsCount();
        $chunk = 200;
        $queryCount = ceil($appCount / $chunk);
        $apps = [];
        for ($i = 0; $i < $queryCount; $i++) {
            $_apps = $this->getMacosAppsWithLimit($chunk, ($chunk * $i));
            foreach ($_apps as $_app) {
                $apps[$_app['name']] = $_app['id'];
            }
            unset($_apps);
        }

        return $apps;
    }

    /**
     * Returns all installations of a host. One app can be installed several times in different versions,
     * e.g. Python, so the result is a list and not indexed by app.
     *
     * @param int $hostId
     * @return MacosAppsHost[]
     */
    public function getInstallationsOfHost(int $hostId): array {
        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');

        return $MacosAppsHostsTable->find()
            ->where([
                'host_id' => $hostId,
            ])
            ->orderBy(['id' => 'asc'])
            ->toArray();
    }

    /**
     * Stores the installed apps a macOS agent reports.
     *
     * An installation is one app (by name) in one version. The same app can be installed several times,
     * e.g. Python of the Command Line Tools and of python.org, each of them is its own row in macos_apps_hosts.
     * The stored installations are compared with the reported ones:
     * - reported but not stored: insert
     * - stored but not reported anymore: delete (uninstalled or updated to another version)
     * - stored more than once: keep one row and delete the rest
     * - stored with another bundle identifier: update it (agents before 3.8.0 report none)
     *
     * @param int $hostId
     * @param array $installedApps Apps of the agent with the keys Name, Version, Description and BundleID
     * @return bool
     */
    public function saveAppsForHost(int $hostId, array $installedApps): bool {
        if (empty($installedApps)) {
            return true;
        }

        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');

        // 1. Add apps that are new for the whole system to the catalog (macos_apps)
        // key = app name, value = app id
        $existingApps = $this->getAllMacosAppsAsMap();

        $newApps = [];
        foreach ($installedApps as $app) {
            if (empty($app['Name']) || empty($app['Version'])) {
                continue;
            }

            if (!isset($existingApps[$app['Name']]) && !isset($newApps[$app['Name']])) {
                $description = null;
                if (isset($app['Description'])) {
                    $description = substr($app['Description'], 0, 1000);
                }

                $newApps[$app['Name']] = $this->newEntity([
                    'name'        => $app['Name'],
                    'description' => $description
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
                'host_id'      => $hostId,
                'macos_app_id' => $appId,
                'version'      => $version,
                'bundle_id'    => empty($app['BundleID']) ? null : $app['BundleID'],
            ];
        }

        // 3. Compare with the stored installations
        $storedKeys = [];
        $idsToDelete = [];
        $changedInstallations = [];
        foreach ($this->getInstallationsOfHost($hostId) as $installation) {
            $key = $this->getInstallationKey($installation->macos_app_id, (string)$installation->version);

            $isStillInstalled = isset($reportedInstallations[$key]);
            $isDuplicate = isset($storedKeys[$key]);
            if (!$isStillInstalled || $isDuplicate) {
                $idsToDelete[] = $installation->id;
                continue;
            }

            $storedKeys[$key] = true;

            // ITC-3796 Agents 3.8.0 and later report the bundle identifier, older rows have none
            $bundleId = $reportedInstallations[$key]['bundle_id'];
            if ($installation->bundle_id !== $bundleId) {
                $installation->bundle_id = $bundleId;
                $changedInstallations[] = $installation;
            }
        }

        $newInstallations = [];
        foreach ($reportedInstallations as $key => $installation) {
            if (!isset($storedKeys[$key])) {
                $newInstallations[] = $MacosAppsHostsTable->newEntity($installation);
            }
        }

        // 4. Write all changes at once, so the list of the host is never half updated
        $MacosAppsHostsTable->getConnection()->transactional(function () use ($MacosAppsHostsTable, $hostId, $idsToDelete, $changedInstallations, $newInstallations) {
            if (!empty($idsToDelete)) {
                $MacosAppsHostsTable->deleteAll([
                    'id IN'   => $idsToDelete,
                    'host_id' => $hostId,
                ]);
            }

            if (!empty($changedInstallations)) {
                $MacosAppsHostsTable->saveManyOrFail($changedInstallations);
            }

            if (!empty($newInstallations)) {
                $MacosAppsHostsTable->saveManyOrFail($newInstallations);
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
    public function getMacosAppsForSummary(array $MY_RIGHTS = []): array {
        $all_macos_apps = [
            'totalPackages'      => 0,
            'totalInstallations' => 0,
            'allHosts'           => []
        ];


        $query = $this->unhydratedFind('all')
            ->disableAutoFields()
            ->contain([
                'MacosAppsHosts' => function (SelectQuery $query) {
                    $query
                        ->innerJoin(
                            ['Hosts' => 'hosts'],
                            ['Hosts.id = MacosAppsHosts.host_id']
                        )
                        ->select([
                            'MacosAppsHosts.macos_app_id',
                            'MacosAppsHosts.version',
                            'MacosAppsHosts.host_id'
                        ]);
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
        $result = $query->toArray();
        if (empty($result)) {
            return $all_macos_apps;
        }

        foreach ($result as $macos_app) {
            $all_macos_apps['totalPackages']++;
            foreach ($macos_app['macos_apps_hosts'] as $hostApp) {
                $all_macos_apps['totalInstallations']++;
                $all_macos_apps['allHosts'][$hostApp['host_id']] = $hostApp['host_id'];
            }
        }
        return $all_macos_apps;
    }

    public function getMacosAppsIndex(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');
        $query = $this->unhydratedFind()
            ->select([
                'MacosApps.id',
                'MacosApps.name',
                'MacosApps.description',
                'MacosApps.created',
                'MacosApps.modified',
            ])
            ->contain([
                'MacosAppsHosts' => function (SelectQuery $query) use ($MY_RIGHTS) {
                    $query->select([
                        'MacosAppsHosts.macos_app_id',
                        'MacosAppsHosts.host_id'

                    ])->innerJoin(
                        ['Hosts' => 'hosts'],
                        ['Hosts.id = MacosAppsHosts.host_id']
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
                $GenericFilter->getOrderForPaginator('MacosApps.name', 'asc'),
                ['MacosApps.id' => 'asc']
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
