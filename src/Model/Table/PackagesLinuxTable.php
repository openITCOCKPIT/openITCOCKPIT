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
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
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
        $query = $this->find()
            ->where([
                'PackagesLinux.id' => $id
            ])
            ->disableHydration()
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
        $query = $this->find()
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

        $query->disableHydration();

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
     * Get all linux packages of a specific host
     *
     * @param int $hostId
     * @return PackagesLinuxHost[]
     */
    public function getAllPackagesLinuxOfHost(int $hostId): array {
        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');
        $query = $PackagesLinuxHostsTable->find()
            ->where([
                'host_id' => $hostId,
            ])
            ->contain([
                'PackagesLinux' => function (Query $query) {
                    return $query->select([
                        'id',
                        'name',
                    ]);
                }
            ]);

        $result = [];
        foreach ($query->toArray() as $packageLinuxHost) {
            $result[$packageLinuxHost->package_linux_id] = $packageLinuxHost;
        }

        return $result;
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
    public function savePackagesForHost(int $hostId, array $installedPackages, array $availableUpdates): bool {
        if (empty($installedPackages)) {
            return true;
        }

        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = TableRegistry::getTableLocator()->get('PackagesLinuxHosts');

        // key = package name, value = package id
        $existingPackages = $this->getAllPackagesLinuxAsMap();
        // key = package id, value = PackagesLinuxHost entity
        $existingPackagesOfHost = $this->getAllPackagesLinuxOfHost($hostId);

        $newPackages = [];
        $newLinuxPackagesHosts = [];


        // Fake package for testing
        /*
        $installedPackages[] = [
            'Name'        => 'delete-test',
            'Version'     => '1.0.0',
            'Description' => 'This is a test package to be deleted',
        ];*/
        foreach ($installedPackages as $package) {
            //[
            //    'Name'        => 'adduser',
            //    'Version'     => '3.137ubuntu1',
            //    'Description' => 'add and remove users and groups\n This package includes the 'adduser' and 'deluse'
            //]
            if (empty($package['Name']) || empty($package['Version'])) {
                continue;
            }

            if (!isset($existingPackages[$package['Name']])) {
                // New package - add to packages_linux
                $desc = null;
                if (isset($package['Description'])) {
                    $desc = substr($package['Description'], 0, 1000);
                }

                $newPackages[] = $this->newEntity([
                    'name'        => $package['Name'],
                    'description' => $desc,
                    'is_patch'    => false,
                ]);
            }
        }

        // SUSE distributions report security updates as patches.
        // So we handle patches as packages so we can group them together later.
        /*$availableUpdates[] = [
            'Name'             => 'delete-test',
            'CurrentVersion'   => '1.0.0',
            'AvailableVersion' => '2.0.0',
            'IsSecurityUpdate' => false,
            'IsPatch'          => false,
        ];*/
        foreach ($availableUpdates as $update) {
            //[
            //    'Name'             => 'kernel-default',
            //    'CurrentVersion'   => '6.12.0-160000.7.1',
            //    'AvailableVersion' => '6.12.0-160000.8.1',
            //    'IsSecurityUpdate' => false,
            //    'IsPatch'          => false
            //];

            // A variable is considered empty if it does not exist or if its value equals false.
            // https://www.php.net/manual/en/function.empty.php
            if (empty($update['Name']) || empty($update['IsPatch'])) {
                continue;
            }

            if (!isset($existingPackages[$update['Name']]) && $update['IsPatch'] === true) {
                // Save patch as new package - add to packages_linux
                $patch = $this->newEntity([
                    'name'        => $update['Name'],
                    'description' => null, // Patches do not have descriptions
                    'is_patch'    => $update['IsPatch'],
                ]);
                if ($this->save($patch)) {
                    $existingPackages[$patch->name] = $patch->id;

                }
            }
        }


        if (!empty($newPackages)) {
            $this->saveMany($newPackages);

            // Add new packages to existingPackages map
            foreach ($newPackages as $newPackage) {
                $existingPackages[$newPackage->name] = $newPackage->id;
            }
        }


        // Save new installed packages for host
        foreach ($installedPackages as $package) {
            if (empty($package['Name']) || empty($package['Version'])) {
                continue;
            }

            if (!isset($existingPackages[$package['Name']])) {
                Log::error(sprintf('Package %s not found in existing packages after insertion.', $package['Name']));
                continue;
            }

            $packageId = $existingPackages[$package['Name']];
            if (isset($existingPackagesOfHost[$packageId])) {
                // Package already exists for host - update it
                // This stores new versions of already existing packages (after apt-get dist-upgrade for example)
                $linuxPackageHost = $existingPackagesOfHost[$packageId];
                if ($linuxPackageHost->current_version != $package['Version']) {
                    $linuxPackageHost->current_version = $package['Version'];
                    $linuxPackageHost->available_version = $package['Version'];
                    $linuxPackageHost->needs_update = false;
                    $linuxPackageHost->is_security_update = false;
                    $linuxPackageHost->is_patch = false;
                    if ($PackagesLinuxHostsTable->save($linuxPackageHost)) {
                        $existingPackagesOfHost[$linuxPackageHost->package_linux_id] = $linuxPackageHost;
                    }
                }

            } else {
                // Create new entry in packages_linux_hosts
                $newLinuxPackagesHosts[] = $PackagesLinuxHostsTable->newEntity([
                    'package_linux_id'   => $packageId,
                    'host_id'            => $hostId,
                    'current_version'    => $package['Version'],
                    'available_version'  => $package['Version'],
                    'needs_update'       => false,
                    'is_security_update' => false,
                    'is_patch'           => false,
                ]);
            }
        }

        if (!empty($newLinuxPackagesHosts)) {
            $PackagesLinuxHostsTable->saveMany($newLinuxPackagesHosts);

            // Add new packages to existingPackages map
            foreach ($newLinuxPackagesHosts as $newLinuxPackagesHost) {
                $existingPackagesOfHost[$newLinuxPackagesHost->package_linux_id] = $newLinuxPackagesHost;
            }
        }

        // Update installed packages if new versions is available (but not installed yet)
        // This store only available_version (before the apt-get dist-upgrade got executed)
        $currentlyInstalledPackagesNameAndId = [];
        foreach ($availableUpdates as $update) {
            if (empty($update['Name']) || empty($update['AvailableVersion'])) {
                continue;
            }


            if (!isset($existingPackages[$update['Name']])) {
                Log::error(sprintf('Package %s not found in existing packages during update processing.', $update['Name']));
                continue;
            }

            $packageId = $existingPackages[$update['Name']];
            if (isset($existingPackagesOfHost[$packageId])) {


                // Package already exists for host - update it
                $linuxPackageHost = $existingPackagesOfHost[$packageId];
                if (
                    $linuxPackageHost->current_version != $update['CurrentVersion'] ||
                    $linuxPackageHost->available_version != $update['AvailableVersion'] ||
                    $linuxPackageHost->is_security_update != $update['IsSecurityUpdate']
                ) {
                    $linuxPackageHost->current_version = $update['CurrentVersion'];
                    $linuxPackageHost->available_version = $update['AvailableVersion'];
                    $linuxPackageHost->is_security_update = $update['IsSecurityUpdate'];
                    $linuxPackageHost->is_patch = $update['IsPatch'];
                    $linuxPackageHost->needs_update = true;
                    if ($PackagesLinuxHostsTable->save($linuxPackageHost)) {
                        $existingPackagesOfHost[$linuxPackageHost->package_linux_id] = $linuxPackageHost;
                    }
                }

            } else {
                // Create new patches - as a patch will never be reported as installed package
                if (!empty($update['IsPatch'])) {
                    // Create new patch entry in packages_linux_hosts
                    $entity = $PackagesLinuxHostsTable->newEntity([
                        'package_linux_id'   => $packageId,
                        'host_id'            => $hostId,
                        'current_version'    => "0",
                        'available_version'  => $update['AvailableVersion'],
                        'needs_update'       => true,
                        'is_security_update' => $update['IsSecurityUpdate'],
                        'is_patch'           => $update['IsPatch'],
                    ]);
                    if ($PackagesLinuxHostsTable->save($entity)) {
                        $existingPackagesOfHost[$entity->package_linux_id] = $entity;
                    }
                } else {
                    // This should not happen - log error
                    Log::error(sprintf('PackageLinuxHost entry for package %s and host ID %d not found during update processing.', $update['Name'], $hostId));
                }
            }
        }

        // Remove uninstalled packages
        $existingPackagesOfHostNameAndId = [];
        foreach ($existingPackagesOfHost as $packageLinuxHost) {
            // New installed packages may have no JOIN data (package name)
            // but this is not needed as we want to remote uninstalled packages from the packages_linux_hosts table
            if (!empty($packageLinuxHost->packages_linux->name)) {
                $existingPackagesOfHostNameAndId[$packageLinuxHost->packages_linux->name] = $packageLinuxHost->package_linux_id;
            }
        }

        foreach ($installedPackages as $package) {
            if (!isset($existingPackages[$package['Name']])) {
                continue;
            }
            $packageId = $existingPackages[$package['Name']];
            $currentlyInstalledPackagesNameAndId[$package['Name']] = $packageId;
        }

        $packagesThatGotRemovedFromSystem = (array_diff_key($existingPackagesOfHostNameAndId, $currentlyInstalledPackagesNameAndId));
        // Remove these packages from packages_linux_hosts
        if (!empty($packagesThatGotRemovedFromSystem)) {
            $PackagesLinuxHostsTable->deleteAll(conditions: [
                'package_linux_id IN' => array_values($packagesThatGotRemovedFromSystem),
                'host_id'             => $hostId,
                'is_patch'            => false,
            ]);
        }

        if (!empty(Hash::extract($existingPackagesOfHost, '{n}[is_patch=1]'))) {
            $availableUpdatesNames = Hash::combine($availableUpdates, '{n}.Name', '{n}.Name');
            $existingPackagesOfHostNames = [];
            foreach ($existingPackagesOfHost as $packageEnity) {
                if ($packageEnity->is_patch) {
                    if (!empty($packageEnity->packages_linux->name)) {
                        // New created patches do not have JOIN data (package name)
                        // Due to we have inserted the patch a few lines above, we do not have to check if it needs to be removed from this host
                        // The name is only relevant for the next execution to identify patches that need to be removed from the database
                        $existingPackagesOfHostNames[$packageEnity->packages_linux->name] = $packageEnity->id;
                    }
                }
            }

            $patchesToDelete = array_diff_key($existingPackagesOfHostNames, $availableUpdatesNames);

            if (!empty($patchesToDelete)) {
                $PackagesLinuxHostsTable->deleteAll(conditions: [
                    'id IN'    => array_values($patchesToDelete),
                    'host_id'  => $hostId,
                    'is_patch' => true,
                ]);
            }
        }


        return true;
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

        $query = $this->find();
        $query->select([
            'PackagesLinux.id',
            'PackagesLinux.name',
            'PackagesLinux.description',
            'PackagesLinux.is_patch',
            'PackagesLinux.created',
            'PackagesLinux.modified',
            'available_updates'          => $subQueryForUpdates,
            'available_security_updates' => $subQueryForSecurityUpdates,
            'packageStatus'              => $query->newExpr()->add([
                'CASE
            WHEN (' . $subQueryForSecurityUpdates . ') > 0 THEN 2
            WHEN (' . $subQueryForUpdates . ') > 0 THEN 1
            ELSE 0
        END'
            ])
        ])
            ->contain([
                'PackageLinuxHosts' => function (Query $query) use ($MY_RIGHTS) {
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


        $query = $this->find('all')
            ->disableAutoFields()
            ->contain([
                'PackageLinuxHosts' => function (Query $query) {
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


        $query->disableHydration();
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
