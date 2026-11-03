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
     * Get all Macos apps of a specific host
     *
     * @param int $hostId
     * @return MacosAppsHost[]
     */
    public function getAllMacosAppsOfHost(int $hostId): array {
        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');
        $query = $MacosAppsHostsTable->find()
            ->where([
                'host_id' => $hostId,
            ])
            ->contain([
                'MacosApps' => function (SelectQuery $query) {
                    return $query->select([
                        'id',
                        'name',
                    ]);
                }
            ]);

        $result = [];
        foreach ($query->toArray() as $macosAppHost) {
            $result[$macosAppHost->macos_app_id] = $macosAppHost;
        }

        return $result;
    }

    public function deleteUnusedApps() {
        $query = $this->deleteQuery();
        $query->delete('macos_apps')
            ->where(function (QueryExpression $exp, Query\DeleteQuery $query) {
                return $exp->notExists(
                    $this->find()
                        ->select(1)
                        ->from(['macos_apps_hosts'])
                        ->where(['macos_apps_hosts.macos_app_id = macos_apps.id'])
                );
            });

        return $query->execute();
    }

    /**
     * Save installed apps for a specific host
     *
     * @param int $hostId
     * @param array $installedApps
     * @return bool
     * @throws \Exception
     */
    public function saveAppsForHost(int $hostId, array $installedApps): bool {
        if (empty($installedApps)) {
            return true;
        }

        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = TableRegistry::getTableLocator()->get('MacosAppsHosts');

        // key = app name, value = app id
        $existingApps = $this->getAllMacosAppsAsMap();
        // key = app id, value = MacosAppsHost entity
        $existingAppsOfHost = $this->getAllMacosAppsOfHost($hostId);

        $newApps = [];
        $newAppsHosts = [];

        // Fake app for testing
        /*
        $installedApps[] = [
            'Name'      => 'delete-test',
            'Version'   => '2.0.0',
            'Description' => 'Fake app for deletion test',
        ];*/
        foreach ($installedApps as $app) {
            if (empty($app['Name']) || empty($app['Version'])) {
                continue;
            }

            if (!isset($existingApps[$app['Name']])) {
                // New App - add to macos_apps
                $desc = null;
                if (isset($app['Description'])) {
                    $desc = substr($app['Description'], 0, 1000);
                }

                $newApps[] = $this->newEntity([
                    'name'        => $app['Name'],
                    'description' => $desc
                ]);
            }
        }

        if (!empty($newApps)) {
            $this->saveMany($newApps);

            // Add new app to existingApps map
            foreach ($newApps as $newApp) {
                $existingApps[$newApp->name] = $newApp->id;
            }
        }

        // Save / update new installed apps for host
        foreach ($installedApps as $app) {
            if (empty($app['Name']) || empty($app['Version'])) {
                continue;
            }

            if (!isset($existingApps[$app['Name']])) {
                Log::error(sprintf('App %s not found in existing app after insertion.', $app['Name']));
                continue;
            }

            $appId = $existingApps[$app['Name']];
            if (isset($existingAppsOfHost[$appId])) {
                // App already exists for host - update it
                $macosAppHostEntity = $existingAppsOfHost[$appId];
                if ($macosAppHostEntity->version != $app['Version']) {
                    $macosAppHostEntity->version = $app['Version'];
                    if ($MacosAppsHostsTable->save($macosAppHostEntity)) {
                        $existingAppsOfHost[$macosAppHostEntity->macos_app_id] = $macosAppHostEntity;
                    }
                }
            } else {
                // Create a new entry
                $newAppsHosts[] = $MacosAppsHostsTable->newEntity([
                    'host_id'      => $hostId,
                    'macos_app_id' => $appId,
                    'version'      => $app['Version'],
                ]);
            }
        }

        if (!empty($newAppsHosts)) {
            $MacosAppsHostsTable->saveMany($newAppsHosts);

            // Add new apps to existingApps map
            foreach ($newAppsHosts as $newAppHost) {
                $existingAppsOfHost[$newAppHost->macos_app_id] = $newAppHost;
            }
        }

        // Remove uninstalled apps
        $existingAppsOfHostNameAndId = [];
        foreach ($existingAppsOfHost as $appMacosHost) {
            // New installed apps may have no JOIN data (app name)
            // but this is not needed as we want to remote uninstalled apps from the macos_apps_hosts table
            if (!empty($appMacosHost->macos_app->name)) {
                $existingAppsOfHostNameAndId[$appMacosHost->macos_app->name] = $appMacosHost->macos_app_id;
            }
        }


        $currentlyInstalledAppsNameAndId = [];
        foreach ($installedApps as $app) {
            if (!isset($existingApps[$app['Name']])) {
                continue;
            }
            $appId = $existingApps[$app['Name']];
            $currentlyInstalledAppsNameAndId[$app['Name']] = $appId;
        }

        $appsThatGotRemovedFromSystem = (array_diff_key($existingAppsOfHostNameAndId, $currentlyInstalledAppsNameAndId));
        // Remove these apps from macos_apps_hosts
        if (!empty($appsThatGotRemovedFromSystem)) {
            $MacosAppsHostsTable->deleteAll(conditions: [
                'macos_app_id IN' => array_values($appsThatGotRemovedFromSystem),
                'host_id'         => $hostId,
            ]);
        }

        return true;
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
