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
use App\Model\Entity\PackagesLinuxHost;
use Cake\Database\Expression\QueryExpression;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * PackagesLinux Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsToMany $Hosts
 *
 * @method \App\Model\Entity\PackagesLinux newEmptyEntity()
 * @method \App\Model\Entity\PackagesLinux newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\PackagesLinux> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PackagesLinux get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PackagesLinux findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\PackagesLinux patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\PackagesLinux> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PackagesLinux|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\PackagesLinux saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesLinux>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesLinux>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesLinux>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesLinux> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesLinux>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesLinux>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesLinux>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesLinux> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PackagesLinuxTable extends Table {
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('packages_linux');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');


        $this->hasMany('PackageLinuxHosts', [
            'foreignKey' => 'package_linux_id',
            'className'  => PackagesLinuxHostsTable::class,
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
        return $this->exists(['PackagesLinux.id' => $id]);
    }

    public function getPackageBy($id) {
        $query = $this->unhydratedFind()
            ->where([
                'PackagesLinux.id' => $id
            ])
            ->firstOrFail();

        return $query;
    }

    /**
     * @return int
     */
    public function getPackagesCount(): int {
        $query = $this->find()
            ->count();

        return $query;
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     */
    public function getPackagesLinuxWithLimit(?int $limit = null, ?int $offset = null): array {
        $query = $this->unhydratedFind()
            ->select([
                'PackagesLinux.id',
                'PackagesLinux.name',
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

    public function getAllPackagesLinuxAsMap(): array {
        //Multiple queries are faster than one big query
        $packagesCount = $this->getPackagesCount();
        $chunk = 200;
        $queryCount = ceil($packagesCount / $chunk);
        $packages = [];
        for ($i = 0; $i < $queryCount; $i++) {
            $_packages = $this->getPackagesLinuxWithLimit($chunk, ($chunk * $i));
            foreach ($_packages as $_package) {
                $packages[$_package['name']] = $_package['id'];
            }
            unset($_packages);
        }

        return $packages;
    }

    /**
     * Returns all installations of a host, including SUSE patches. One package can be installed several times,
     * e.g. three kernel versions or glibc for i686 and x86_64, so the result is a list and not indexed by package.
     *
     * @param int $hostId
     * @return PackagesLinuxHost[]
     */
    public function getInstallationsOfHost(int $hostId): array {
        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');

        return $PackagesLinuxHostsTable->find()
            ->where([
                'host_id' => $hostId,
            ])
            ->orderBy(['id' => 'asc'])
            ->toArray();
    }

    public function deleteUnusedPackages() {
        $query = $this->deleteQuery();
        $query->delete('packages_linux')
            ->where(function (QueryExpression $exp, Query\DeleteQuery $query) {
                return $exp->notExists(
                    $this->find()
                        ->select(1)
                        ->from(['packages_linux_hosts'])
                        ->where(['packages_linux_hosts.package_linux_id = packages_linux.id'])
                );
            });

        return $query->execute();
    }

    /**
     * Save installed packages for a specific host
     *
     * @param int $hostId
     * @param array $installedPackages
     * @param array $availableUpdates
     * @return bool
     * @throws \Exception
     */
    /**
     * Stores the installed packages and the available updates a Linux agent reports.
     *
     * An installation is one package in one architecture and one version. The same package can be installed
     * several times, e.g. three versions of "kernel" on RHEL or "glibc" for i686 and x86_64, each of them is its
     * own row in packages_linux_hosts. SUSE patches are rows of their own with current_version "0".
     *
     * The installed version always comes from the list of installed packages, never from an update.
     * An update only sets available_version, needs_update and is_security_update, see getUpdateTargets().
     *
     * The stored rows are compared with the reported ones:
     * - reported but not stored: insert
     * - stored but not reported anymore: delete (uninstalled, updated to another version, patch installed)
     * - stored more than once: keep one row and delete the rest
     * - stored with other details (update, source package): update the row
     *
     * @param int $hostId
     * @param array $installedPackages Packages of the agent: Name, Version, Description, Architecture,
     *                                 SourcePackage, SourceVersion, ModularityLabel, SourceRPM
     * @param array $availableUpdates Updates of the agent: Name, CurrentVersion, AvailableVersion,
     *                                IsSecurityUpdate, IsPatch
     * @return bool
     */
    public function savePackagesForHost(int $hostId, array $installedPackages, array $availableUpdates): bool {
        if (empty($installedPackages)) {
            return true;
        }

        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');

        $packageIds = $this->addMissingPackagesToCatalog($installedPackages, $availableUpdates);

        // 1. Installations the agent reports, one per package, architecture and version
        $reportedRows = [];
        // key = package name, value = keys of $reportedRows, used to assign updates
        $installationsByName = [];
        foreach ($installedPackages as $package) {
            if (empty($package['Name']) || empty($package['Version'])) {
                continue;
            }

            if (!isset($packageIds[$package['Name']])) {
                Log::error(sprintf('Package %s not found in existing packages after insertion.', $package['Name']));
                continue;
            }

            $packageId = (int)$packageIds[$package['Name']];
            // Same length as the columns, so the comparison with stored rows works
            $version = $this->limitLength($package['Version'], 64);
            $architecture = $this->limitLength($package['Architecture'] ?? null, 255);

            $key = $this->getInstallationKey($packageId, $architecture, $version);
            $reportedRows[$key] = [
                'package_linux_id'   => $packageId,
                'host_id'            => $hostId,
                'current_version'    => $version,
                'available_version'  => $version,
                'needs_update'       => false,
                'is_security_update' => false,
                'is_patch'           => false,
                'source_package'     => $this->limitLength($package['SourcePackage'] ?? null, 255),
                'source_version'     => $this->limitLength($package['SourceVersion'] ?? null, 255),
                'architecture'       => $architecture,
                'modularity_label'   => $this->limitLength($package['ModularityLabel'] ?? null, 255),
                'source_rpm'         => $this->limitLength($package['SourceRPM'] ?? null, 255),
            ];
            $installationsByName[$package['Name']][$key] = $version;
        }

        // 2. Assign the updates to the installations and add SUSE patches
        foreach ($availableUpdates as $update) {
            if (empty($update['Name']) || empty($update['AvailableVersion'])) {
                continue;
            }

            $availableVersion = $this->limitLength($update['AvailableVersion'], 64);
            $isSecurityUpdate = !empty($update['IsSecurityUpdate']);

            if (!empty($update['IsPatch'])) {
                // A patch is never reported as installed package, so it is a row of its own
                if (!isset($packageIds[$update['Name']])) {
                    continue;
                }
                $packageId = (int)$packageIds[$update['Name']];
                $reportedRows[$this->getPatchKey($packageId)] = [
                    'package_linux_id'   => $packageId,
                    'host_id'            => $hostId,
                    'current_version'    => '0',
                    'available_version'  => $availableVersion,
                    'needs_update'       => true,
                    'is_security_update' => $isSecurityUpdate,
                    'is_patch'           => true,
                    'source_package'     => null,
                    'source_version'     => null,
                    'architecture'       => null,
                    'modularity_label'   => null,
                    'source_rpm'         => null,
                ];
                continue;
            }

            if (empty($installationsByName[$update['Name']])) {
                Log::error(sprintf('Update for package %s of host ID %d, but the package is not installed.', $update['Name'], $hostId));
                continue;
            }

            foreach ($this->getUpdateTargets($installationsByName[$update['Name']]) as $key) {
                $reportedRows[$key]['available_version'] = $availableVersion;
                $reportedRows[$key]['needs_update'] = true;
                $reportedRows[$key]['is_security_update'] = $isSecurityUpdate;
            }
        }

        // 3. Compare with the stored rows
        $storedKeys = [];
        $idsToDelete = [];
        $changedRows = [];
        foreach ($this->getInstallationsOfHost($hostId) as $row) {
            if ($row->is_patch) {
                $key = $this->getPatchKey($row->package_linux_id);
            } else {
                $key = $this->getInstallationKey($row->package_linux_id, $row->architecture, (string)$row->current_version);
            }

            $isStillReported = isset($reportedRows[$key]);
            $isDuplicate = isset($storedKeys[$key]);
            if (!$isStillReported || $isDuplicate) {
                $idsToDelete[] = $row->id;
                continue;
            }

            $storedKeys[$key] = true;

            // Update, security flag or source information changed
            $row = $PackagesLinuxHostsTable->patchEntity($row, $reportedRows[$key]);
            if ($row->isDirty()) {
                $changedRows[] = $row;
            }
        }

        $newRows = [];
        foreach ($reportedRows as $key => $data) {
            if (!isset($storedKeys[$key])) {
                $newRows[] = $PackagesLinuxHostsTable->newEntity($data);
            }
        }

        // 4. Write all changes at once, so the list of the host is never half updated
        $PackagesLinuxHostsTable->getConnection()->transactional(function () use ($PackagesLinuxHostsTable, $hostId, $idsToDelete, $changedRows, $newRows) {
            if (!empty($idsToDelete)) {
                $PackagesLinuxHostsTable->deleteAll([
                    'id IN'   => $idsToDelete,
                    'host_id' => $hostId,
                ]);
            }

            if (!empty($changedRows)) {
                $PackagesLinuxHostsTable->saveManyOrFail($changedRows);
            }

            if (!empty($newRows)) {
                $PackagesLinuxHostsTable->saveManyOrFail($newRows);
            }
        });

        return true;
    }

    /**
     * Adds packages and SUSE patches that are new for the whole system to the catalog (packages_linux).
     *
     * @param array $installedPackages
     * @param array $availableUpdates
     * @return array<string, int> Id of every package, indexed by name
     */
    private function addMissingPackagesToCatalog(array $installedPackages, array $availableUpdates): array {
        // key = package name, value = package id
        $packageIds = $this->getAllPackagesLinuxAsMap();

        $newPackages = [];
        foreach ($installedPackages as $package) {
            if (empty($package['Name']) || empty($package['Version'])) {
                continue;
            }

            if (!isset($packageIds[$package['Name']]) && !isset($newPackages[$package['Name']])) {
                $description = null;
                if (isset($package['Description'])) {
                    $description = substr($package['Description'], 0, 1000);
                }

                $newPackages[$package['Name']] = $this->newEntity([
                    'name'        => $package['Name'],
                    'description' => $description,
                    'is_patch'    => false,
                ]);
            }
        }

        // SUSE distributions report security updates as patches. A patch gets stored like a package,
        // so it can be grouped with the same patch of other hosts.
        foreach ($availableUpdates as $update) {
            if (empty($update['Name']) || empty($update['IsPatch'])) {
                continue;
            }

            if (!isset($packageIds[$update['Name']]) && !isset($newPackages[$update['Name']])) {
                $newPackages[$update['Name']] = $this->newEntity([
                    'name'        => $update['Name'],
                    'description' => null, // Patches do not have descriptions
                    'is_patch'    => true,
                ]);
            }
        }

        if (!empty($newPackages)) {
            $this->saveMany($newPackages);
            foreach ($newPackages as $newPackage) {
                $packageIds[$newPackage->name] = $newPackage->id;
            }
        }

        return $packageIds;
    }

    /**
     * Returns the installations an update belongs to: those with the newest installed version of the package.
     *
     * - One installation: the update belongs to it
     * - Several architectures in the same version (e.g. glibc for i686 and x86_64): to all of them
     * - Several versions (e.g. three kernels): to the newest. A kernel update installs a new kernel next to the
     *   old ones, the old ones stay as they are
     *
     * CurrentVersion of the update is not used on purpose: dnf takes it from the list of installed packages by
     * name, so with three kernels it is one of them by chance.
     *
     * @param array<string, string> $installations Version of each installation, indexed by installation key
     * @return string[] Installation keys
     */
    private function getUpdateTargets(array $installations): array {
        $newestVersion = null;
        foreach ($installations as $version) {
            if ($newestVersion === null || version_compare($version, $newestVersion, '>')) {
                $newestVersion = $version;
            }
        }

        $targets = [];
        foreach ($installations as $key => $version) {
            if ($version === $newestVersion) {
                $targets[] = $key;
            }
        }

        return $targets;
    }

    /**
     * One package in one architecture and one version
     *
     * @param int $packageId
     * @param string|null $architecture null for agents that do not report it
     * @param string $version
     * @return string
     */
    private function getInstallationKey(int $packageId, ?string $architecture, string $version): string {
        return $packageId . '|' . ($architecture ?? '') . '|' . $version;
    }

    /**
     * A SUSE patch exists at most once per host
     *
     * @param int $packageId
     * @return string
     */
    private function getPatchKey(int $packageId): string {
        return 'patch|' . $packageId;
    }

    /**
     * @param mixed $value
     * @param int $length Length of the column
     * @return string|null null for empty values
     */
    private function limitLength(mixed $value, int $length): ?string {
        if ($value === null || $value === '') {
            return null;
        }
        return substr((string)$value, 0, $length);
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getPackagesLinuxIndex(GenericFilter $GenericFilter, $PaginateOMat = null, array $MY_RIGHTS = []): array {
        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');
        $subQueryForUpdates = $PackagesLinuxHostsTable->find();
        $subQueryForUpdates->select(
            [$subQueryForUpdates->func()->count('package_linux_id')])
            ->where(['`PackagesLinux`.`id` =`PackagesLinuxHosts`.`package_linux_id`'])
            ->andWhere([
                'needs_update' => 1,
            ]);

        $subQueryForSecurityUpdates = $PackagesLinuxHostsTable->find();
        $subQueryForSecurityUpdates->select(
            [$subQueryForSecurityUpdates->func()->count('package_linux_id')])
            ->where(['`PackagesLinux`.`id` =`PackagesLinuxHosts`.`package_linux_id`'])
            ->andWhere([
                'is_security_update' => 1,
            ]);

        $query = $this->unhydratedFind();
        $query->select([
            'PackagesLinux.id',
            'PackagesLinux.name',
            'PackagesLinux.description',
            'PackagesLinux.is_patch',
            'PackagesLinux.created',
            'PackagesLinux.modified',
            'available_updates'          => $subQueryForUpdates,
            'available_security_updates' => $subQueryForSecurityUpdates,
            'packageStatus'              => $query->expr()->add([
                'CASE
            WHEN (' . $subQueryForSecurityUpdates . ') > 0 THEN 2
            WHEN (' . $subQueryForUpdates . ') > 0 THEN 1
            ELSE 0
        END'
            ])
        ])
            ->contain([
                'PackageLinuxHosts' => function (SelectQuery $query) use ($MY_RIGHTS) {
                    $query->select([
                        'PackageLinuxHosts.package_linux_id',
                        'PackageLinuxHosts.needs_update',
                        'PackageLinuxHosts.is_security_update',
                        'PackageLinuxHosts.is_patch',
                        'PackageLinuxHosts.host_id'

                    ])->innerJoin(
                        ['Hosts' => 'hosts'],
                        ['Hosts.id = PackageLinuxHosts.host_id']
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
                $GenericFilter->getOrderForPaginator('PackagesLinux.name', 'asc'),
                ['PackagesLinux.id' => 'asc']
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

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getPackagesLinuxForSummary(array $MY_RIGHTS = []): array {
        $all_packages_linux_summary = [
            'totalPackages'            => 0,
            'upToDate'                 => 0,
            'updatesAvailable'         => 0,
            'securityUpdates'          => 0,
            'totalInstallations'       => 0,
            'allHosts'                 => [],
            'hostsUpToDate'            => [],
            'hostsWithUpdates'         => [],
            'hostsWithSecurityUpdates' => [],
        ];


        $query = $this->unhydratedFind('all')
            ->disableAutoFields()
            ->contain([
                'PackageLinuxHosts' => function (SelectQuery $query) {
                    $query->select([
                        'PackageLinuxHosts.package_linux_id',
                        'PackageLinuxHosts.needs_update',
                        'PackageLinuxHosts.is_security_update',
                        'PackageLinuxHosts.is_patch',
                        'PackageLinuxHosts.host_id'
                    ])
                        ->innerJoin(
                            ['Hosts' => 'hosts'],
                            ['Hosts.id = PackageLinuxHosts.host_id']
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
        $result = $query->toArray();
        if (empty($result)) {
            return $all_packages_linux_summary;
        }

        foreach ($result as $packages_linux) {
            $all_packages_linux_summary['totalPackages']++;
            foreach ($packages_linux['package_linux_hosts'] as $hostPackage) {
                $all_packages_linux_summary['totalInstallations']++;
                $all_packages_linux_summary['allHosts'][$hostPackage['host_id']] = $hostPackage['host_id'];
                if ($hostPackage['needs_update'] === false) {
                    $all_packages_linux_summary['upToDate']++;
                    $all_packages_linux_summary['hostsUpToDate'][$hostPackage['host_id']] = $hostPackage['host_id'];

                } else {
                    $all_packages_linux_summary['updatesAvailable']++;
                    $all_packages_linux_summary['hostsWithUpdates'][$hostPackage['host_id']] = $hostPackage['host_id'];
                    if ($hostPackage['is_security_update'] === true) {
                        $all_packages_linux_summary['securityUpdates']++;
                        $all_packages_linux_summary['hostsWithSecurityUpdates'][$hostPackage['host_id']] = $hostPackage['host_id'];
                    }
                }
            }
        }
        $all_packages_linux_summary['hostsUpToDate'] = array_values($all_packages_linux_summary['hostsUpToDate']);
        $all_packages_linux_summary['hostsWithUpdates'] = array_values($all_packages_linux_summary['hostsWithUpdates']);
        $all_packages_linux_summary['hostsWithSecurityUpdates'] = array_values($all_packages_linux_summary['hostsWithSecurityUpdates']);
        return $all_packages_linux_summary;
    }
}
