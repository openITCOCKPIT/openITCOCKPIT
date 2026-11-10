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

use Cake\Database\Expression\CaseStatementExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * CvePackageStates Model
 *
 * Stores every distinct package state of the Linux inventory only once. The result of Vuls only depends on the
 * operating system and the package itself, so hosts with the same package state share one check.
 * Everything that is specific to a host (available update, running kernel, exceptions) gets evaluated afterwards.
 *
 * packages_linux_hosts --(cve_package_state_id)--> cve_package_states <-- cve_package_state_cves --> cves
 *
 * Workflow:
 * 1. syncFromInventory() creates missing package states, refreshes last_seen and links every installation
 *    in packages_linux_hosts to its package state
 * 2. getPendingGroups() / getStatesOfGroup() return the package states that have to be sent to Vuls,
 *    one group per operating system (and running kernel for kernel packages)
 * 3. saveCheckResults() / saveCheckError() store the answer of Vuls
 * 4. deleteUnusedStates() removes package states that no host has installed anymore
 *
 * @property \App\Model\Table\CvePackageStateCvesTable&\Cake\ORM\Association\HasMany $CvePackageStateCves
 *
 * @method \App\Model\Entity\CvePackageState newEmptyEntity()
 * @method \App\Model\Entity\CvePackageState newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CvePackageState> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CvePackageState get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CvePackageState findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\CvePackageState patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\CvePackageState> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CvePackageState|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CvePackageState saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CvePackageStatesTable extends Table {

    use LocatorAwareTrait;

    /**
     * CVE status of an installed package, see getCveStatus()
     */
    public const CVE_STATUS_NOT_EVALUABLE = 'not_evaluable';
    public const CVE_STATUS_NOT_CHECKED = 'not_checked';
    public const CVE_STATUS_NO_KNOWN_CVES = 'no_known_cves';
    public const CVE_STATUS_AFFECTED = 'affected';

    /**
     * Number of rows per query when reading or writing many package states at once
     */
    private const CHUNK_SIZE = 500;

    /**
     * Source packages of RPM based distributions that build the kernel
     */
    private const RPM_KERNEL_SOURCE_PACKAGES = [
        'kernel',
        'kernel-uek',
        'kernel-rt',
        'kernel-default',
        'kernel-preempt',
        'kernel-azure',
        'kernel-64kb',
        'kernel-kvmsmall',
    ];

    /**
     * Debian and Ubuntu source packages which start with "linux" but do not contain the kernel
     */
    private const DEB_NOT_KERNEL_SOURCE_PACKAGES = [
        'linux-base',
        'linux-firmware',
        'linux-atm',
        'linux-ftpd',
        'linux-pam',
        'linux-sysctl-defaults',
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_package_states');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('CvePackageStateCves', [
            'foreignKey' => 'cve_package_state_id',
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
            ->scalar('state_hash')
            ->lengthBetween('state_hash', [64, 64])
            ->requirePresence('state_hash', 'create')
            ->notEmptyString('state_hash');

        $validator
            ->scalar('os_name')
            ->maxLength('os_name', 255)
            ->requirePresence('os_name', 'create')
            ->notEmptyString('os_name');

        $validator
            ->scalar('os_version')
            ->maxLength('os_version', 255)
            ->requirePresence('os_version', 'create')
            ->notEmptyString('os_version');

        $validator
            ->boolean('is_kernel')
            ->notEmptyString('is_kernel');

        $validator
            ->scalar('kernel_release')
            ->maxLength('kernel_release', 255)
            ->allowEmptyString('kernel_release');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('version')
            ->maxLength('version', 255)
            ->requirePresence('version', 'create')
            ->notEmptyString('version');

        $validator
            ->scalar('architecture')
            ->maxLength('architecture', 255)
            ->allowEmptyString('architecture');

        $validator
            ->scalar('source_package')
            ->maxLength('source_package', 255)
            ->allowEmptyString('source_package');

        $validator
            ->scalar('source_version')
            ->maxLength('source_version', 255)
            ->allowEmptyString('source_version');

        $validator
            ->scalar('modularity_label')
            ->maxLength('modularity_label', 255)
            ->allowEmptyString('modularity_label');

        $validator
            ->scalar('feed_digest')
            ->maxLength('feed_digest', 255)
            ->allowEmptyString('feed_digest');

        $validator
            ->dateTime('checked')
            ->allowEmptyDateTime('checked');

        $validator
            ->nonNegativeInteger('cve_count')
            ->notEmptyString('cve_count');

        $validator
            ->integer('max_severity')
            ->range('max_severity', [0, 4])
            ->allowEmptyString('max_severity');

        $validator
            ->boolean('has_kev')
            ->notEmptyString('has_kev');

        $validator
            ->scalar('error_message')
            ->allowEmptyString('error_message');

        $validator
            ->dateTime('last_seen')
            ->requirePresence('last_seen', 'create')
            ->notEmptyDateTime('last_seen');

        return $validator;
    }

    /**
     * Returns true if Vuls only evaluates this package for the running kernel.
     * In case of doubt a package counts as kernel package. This only splits the package state per kernel
     * and costs an additional check, but never returns a wrong result.
     *
     * @param string $name Name of the binary package
     * @param string|null $sourcePackage Name of the source package
     * @return bool
     */
    public function isKernelPackage(string $name, ?string $sourcePackage): bool {
        $source = $sourcePackage ?: $name;

        if (in_array($source, self::RPM_KERNEL_SOURCE_PACKAGES, true)) {
            return true;
        }

        // Ubuntu splits the firmware into many source packages like linux-firmware-intel-graphics
        if (in_array($source, self::DEB_NOT_KERNEL_SOURCE_PACKAGES, true) || str_starts_with($source, 'linux-firmware-')) {
            return false;
        }

        return preg_match('/^linux(-|$)/', $source) === 1;
    }

    /**
     * Builds the package state of one installed package.
     *
     * Returns null if the package can not be checked. Debian based systems require the source package,
     * without it Vuls reports 0 CVEs without any error.
     *
     * @param array $host One row of PackagesHostDetailsTable::getLinuxHostsForCveCheck()
     * @param array $package One row of PackagesLinuxHostsTable::getPackagesForCveCheck()
     * @return array|null
     */
    public function buildPackageState(array $host, array $package): ?array {
        $name = trim((string)($package['name'] ?? ''));
        $version = trim((string)($package['version'] ?? ''));
        $osName = trim((string)($host['os_name'] ?? ''));
        $osVersion = trim((string)($host['os_version'] ?? ''));

        if ($name === '' || $version === '' || $osName === '' || $osVersion === '') {
            return null;
        }

        $architecture = $this->emptyToNull($package['architecture'] ?? null);
        $sourcePackage = $this->emptyToNull($package['source_package'] ?? null);
        $sourceVersion = $this->emptyToNull($package['source_version'] ?? null);

        $isDebianBased = ($host['os_family'] ?? '') === 'debian';
        if ($isDebianBased && ($architecture === null || $sourcePackage === null || $sourceVersion === null)) {
            return null;
        }

        $isKernel = $this->isKernelPackage($name, $sourcePackage);

        $state = [
            'os_name'          => $osName,
            'os_version'       => $osVersion,
            'is_kernel'        => $isKernel,
            'kernel_release'   => $isKernel ? $this->emptyToNull($host['kernel_release'] ?? null) : null,
            'name'             => $name,
            'version'          => $version,
            'architecture'     => $architecture,
            'source_package'   => $sourcePackage,
            'source_version'   => $sourceVersion,
            'modularity_label' => $this->emptyToNull($package['modularity_label'] ?? null),
        ];
        $state['state_hash'] = $this->getStateHash($state);

        return $state;
    }

    /**
     * Calculates the unique hash of a package state. Only fields that change the result of Vuls are part of the hash.
     *
     * @param array $state
     * @return string
     */
    public function getStateHash(array $state): string {
        return hash('sha256', json_encode([
            $state['os_name'],
            $state['os_version'],
            (bool)$state['is_kernel'],
            $state['is_kernel'] ? $state['kernel_release'] : null,
            $state['name'],
            $state['version'],
            $state['architecture'],
            $state['source_package'],
            $state['source_version'],
            $state['modularity_label'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Creates missing package states for all Linux hosts, refreshes last_seen of all existing ones and links
     * every installation in packages_linux_hosts to its package state.
     * Installations that can not be checked get unlinked, so they never look like a package without CVEs.
     *
     * @param int[] $hostIds Only sync these hosts, all hosts if empty
     * @return array{hosts: int, hosts_without_states: int[], installations: int, skipped_installations: int, states: int, new_states: int, changed_links: int}
     */
    public function syncFromInventory(array $hostIds = []): array {
        /** @var PackagesHostDetailsTable $PackagesHostDetailsTable */
        $PackagesHostDetailsTable = $this->fetchTable('PackagesHostDetails');
        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = $this->fetchTable('PackagesLinuxHosts');

        $summary = [
            'hosts'                 => 0,
            'hosts_without_states'  => [],
            'installations'         => 0,
            'skipped_installations' => 0,
            'states'                => 0,
            'new_states'            => 0,
            'changed_links'         => 0,
        ];

        $states = [];
        // Installation id => [current link, hash of the package state or null]
        $installations = [];

        foreach ($PackagesHostDetailsTable->getLinuxHostsForCveCheck($hostIds) as $host) {
            $summary['hosts']++;
            $statesOfHost = 0;

            foreach ($PackagesLinuxHostsTable->getPackagesForCveCheck((int)$host['host_id']) as $package) {
                $summary['installations']++;
                $currentLink = $package['cve_package_state_id'] === null ? null : (int)$package['cve_package_state_id'];

                $state = $this->buildPackageState($host, $package);
                if ($state === null) {
                    $summary['skipped_installations']++;
                    $installations[(int)$package['id']] = [$currentLink, null];
                    continue;
                }

                $states[$state['state_hash']] = $state;
                $installations[(int)$package['id']] = [$currentLink, $state['state_hash']];
                $statesOfHost++;
            }

            if ($statesOfHost === 0) {
                $summary['hosts_without_states'][] = (int)$host['host_id'];
            }
        }

        $summary['states'] = count($states);
        $stateIds = $this->saveStates($states, DateTime::now(), $summary['new_states']);
        $summary['changed_links'] = $this->linkInstallations($installations, $stateIds);

        return $summary;
    }

    /**
     * Inserts missing package states and sets last_seen of all given package states.
     *
     * @param array<string, array> $states Package states of buildPackageState(), indexed by state_hash
     * @param DateTime $seen
     * @param int|null $newStates Gets set to the number of inserted package states
     * @return array<string, int> Primary key of each package state, indexed by state_hash
     */
    public function saveStates(array $states, DateTime $seen, ?int &$newStates = null): array {
        $newStates = 0;
        $ids = [];

        foreach (array_chunk($states, self::CHUNK_SIZE, true) as $chunk) {
            $existing = $this->getIdsByHashes(array_map('strval', array_keys($chunk)));
            if (!empty($existing)) {
                $this->updateAll(['last_seen' => $seen], ['id IN' => array_values($existing)]);
                $ids += $existing;
            }

            $entities = [];
            foreach (array_diff_key($chunk, $existing) as $state) {
                $entities[] = $this->newEntity($state + [
                    'cve_count' => 0,
                    'has_kev'   => false,
                    'last_seen' => $seen,
                ]);
            }

            if (!empty($entities)) {
                /** @var \App\Model\Entity\CvePackageState $entity */
                foreach ($this->saveManyOrFail($entities) as $entity) {
                    $ids[$entity->state_hash] = $entity->id;
                }
                $newStates += count($entities);
            }
        }

        return $ids;
    }

    /**
     * Returns all groups that contain package states without a result for the given Vuls database.
     * One group becomes one or more requests to Vuls: all package states of a group share operating system
     * and running kernel. Kernel packages are grouped per running kernel, all other packages per operating system.
     *
     * @param string $feedDigest Digest of the current Vuls database
     * @return array<int, array{os_name: string, os_version: string, is_kernel: bool, kernel_release: string|null, pending: int}>
     */
    public function getPendingGroups(string $feedDigest): array {
        $query = $this->unhydratedFind();
        $groups = $query
            ->select([
                'os_name',
                'os_version',
                'is_kernel',
                'kernel_release',
                'pending' => $query->func()->count('*'),
            ])
            ->where($this->getPendingConditions($feedDigest))
            ->groupBy(['os_name', 'os_version', 'is_kernel', 'kernel_release'])
            ->orderBy(['os_name' => 'asc', 'os_version' => 'asc', 'is_kernel' => 'asc', 'kernel_release' => 'asc'])
            ->toArray();

        foreach ($groups as $index => $group) {
            $groups[$index]['is_kernel'] = (bool)$group['is_kernel'];
            $groups[$index]['pending'] = (int)$group['pending'];
        }

        return $groups;
    }

    /**
     * Returns the package states of one group of getPendingGroups().
     *
     * @param string $osName
     * @param string $osVersion
     * @param bool $isKernel
     * @param string|null $kernelRelease Only used for kernel packages
     * @param string|null $pendingForFeedDigest Only return package states without a result for this Vuls database, all if null
     * @return \App\Model\Entity\CvePackageState[]
     */
    public function getStatesOfGroup(string $osName, string $osVersion, bool $isKernel, ?string $kernelRelease, ?string $pendingForFeedDigest = null): array {
        $query = $this->find()
            ->where([
                'os_name'    => $osName,
                'os_version' => $osVersion,
                'is_kernel'  => $isKernel,
            ])
            ->orderBy(['name' => 'asc', 'version' => 'asc', 'architecture' => 'asc']);

        if ($isKernel) {
            $query->where(['kernel_release IS' => $kernelRelease]);
        }

        if ($pendingForFeedDigest !== null) {
            $query->where($this->getPendingConditions($pendingForFeedDigest));
        }

        return $query->toArray();
    }

    /**
     * Returns the package states for the given hashes, indexed by state_hash.
     *
     * @param string[] $stateHashes
     * @return array<string, \App\Model\Entity\CvePackageState>
     */
    public function getStatesByHashes(array $stateHashes): array {
        $states = [];

        foreach (array_chunk(array_values(array_unique($stateHashes)), self::CHUNK_SIZE) as $chunk) {
            foreach ($this->find()->where(['state_hash IN' => $chunk])->all() as $state) {
                $states[$state->state_hash] = $state;
            }
        }

        return $states;
    }

    /**
     * Stores the answer of Vuls. Replaces all CVEs of the given package states and updates their summary
     * (cve_count, max_severity, has_kev). A package state without CVEs needs an empty list, so it counts as checked.
     *
     * @param string $feedDigest Digest of the Vuls database that was used for the check
     * @param array<string, array> $cves Master data of all CVEs that are referenced in $matchesByStateId,
     *                                   see CvesTable::saveCves()
     * @param array<int, array> $matchesByStateId CVEs per package state id, e.g.
     *                                            [42 => [['cve_id' => 'CVE-2026-1234', 'fixed_in' => '1.2-3', 'fix_state' => null,
     *                                            'not_fixed_yet' => false, 'severity' => 3, 'severity_source' => 'ubuntu']]]
     * @return void
     */
    public function saveCheckResults(string $feedDigest, array $cves, array $matchesByStateId): void {
        foreach ($matchesByStateId as $stateId => $matches) {
            foreach ($matches as $match) {
                if (!isset($cves[$match['cve_id'] ?? ''])) {
                    throw new InvalidArgumentException(sprintf('Master data of %s is missing (package state %d)', $match['cve_id'] ?? '?', $stateId));
                }
            }
        }

        /** @var CvesTable $CvesTable */
        $CvesTable = $this->fetchTable('Cves');
        $now = DateTime::now();

        // One transaction for the whole result: a row that fails the validation must not leave
        // master data or half of the package states behind
        $this->getConnection()->transactional(function () use ($CvesTable, $cves, $matchesByStateId, $feedDigest, $now) {
            $cveIds = $CvesTable->saveCves($cves, $feedDigest);

            foreach (array_chunk($matchesByStateId, self::CHUNK_SIZE, true) as $chunk) {
                $stateIds = array_map('intval', array_keys($chunk));
                $this->CvePackageStateCves->deleteAll(['cve_package_state_id IN' => $stateIds]);

                $matchRows = [];
                $summaries = [];
                foreach ($chunk as $stateId => $matches) {
                    $maxSeverity = null;
                    $hasKev = false;
                    $seen = [];

                    foreach ($matches as $match) {
                        $cveId = (string)$match['cve_id'];
                        if (isset($seen[$cveId])) {
                            // One row per CVE and package state, even if Vuls reports the CVE twice
                            continue;
                        }
                        $seen[$cveId] = true;

                        $severity = isset($match['severity']) ? (int)$match['severity'] : null;
                        if ($severity !== null && ($maxSeverity === null || $severity > $maxSeverity)) {
                            $maxSeverity = $severity;
                        }
                        $hasKev = $hasKev || !empty($cves[$cveId]['kev_cisa']);

                        $matchRows[] = [
                            'cve_package_state_id' => (int)$stateId,
                            'cve_id'               => $cveIds[$cveId],
                            'fixed_in'             => $this->limitLength($match['fixed_in'] ?? null, 255),
                            'fix_state'            => $this->limitLength($match['fix_state'] ?? null, 255),
                            'not_fixed_yet'        => !empty($match['not_fixed_yet']),
                            'severity'             => $severity,
                            'severity_source'      => $this->limitLength($match['severity_source'] ?? null, 64),
                        ];
                    }

                    $summaries[(int)$stateId] = [
                        'feed_digest'   => $feedDigest,
                        'checked'       => $now,
                        'cve_count'     => count($seen),
                        'max_severity'  => $maxSeverity,
                        'has_kev'       => $hasKev,
                        'error_message' => null,
                    ];
                }

                $this->CvePackageStateCves->insertMatches($matchRows);

                $states = [];
                foreach ($this->find()->where(['CvePackageStates.id IN' => $stateIds])->all() as $state) {
                    $states[] = $this->patchEntity($state, $summaries[$state->id]);
                }
                $this->saveManyOrFail($states);
            }
        });
    }

    /**
     * Stores the error of a failed check. The last successful result stays untouched and the package states
     * remain pending, so they get checked again with the next run.
     *
     * @param int[] $stateIds
     * @param string $errorMessage
     * @return void
     */
    public function saveCheckError(array $stateIds, string $errorMessage): void {
        foreach (array_chunk($stateIds, self::CHUNK_SIZE) as $chunk) {
            $this->updateAll([
                'error_message' => $errorMessage,
                'modified'      => DateTime::now(),
            ], [
                'id IN' => $chunk
            ]);
        }
    }

    /**
     * Deletes package states that no host had installed since the given date, including their CVEs
     * and the links in packages_linux_hosts. CVEs without any package state get removed as well.
     *
     * @param DateTime $notSeenSince
     * @return int Number of deleted package states
     */
    public function deleteUnusedStates(DateTime $notSeenSince): int {
        $stateIds = $this->unhydratedFind()
            ->select(['id'])
            ->where(['last_seen <' => $notSeenSince])
            ->all()
            ->extract('id')
            ->toList();

        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = $this->fetchTable('PackagesLinuxHosts');

        foreach (array_chunk($stateIds, self::CHUNK_SIZE) as $chunk) {
            $this->getConnection()->transactional(function () use ($chunk, $PackagesLinuxHostsTable) {
                $PackagesLinuxHostsTable->updateAll(['cve_package_state_id' => null], ['cve_package_state_id IN' => $chunk]);
                $this->CvePackageStateCves->deleteAll(['cve_package_state_id IN' => $chunk]);
                $this->deleteAll(['id IN' => $chunk]);
            });
        }

        /** @var CvesTable $CvesTable */
        $CvesTable = $this->fetchTable('Cves');
        $CvesTable->deleteUnusedCves();

        return count($stateIds);
    }

    /**
     * Returns the CVE status of an installed package.
     *
     * - not_evaluable: The package can not be checked, e.g. the agent does not send the source package
     * - not_checked:   The package state exists, but Vuls has not checked it yet
     * - no_known_cves: Vuls knows no CVE for this package. This is no proof that the package is secure
     * - affected:      At least one CVE affects the package
     *
     * @param array|null $cvePackageState Fields id, checked and cve_count of the linked package state, null if not linked
     * @return string One of the CVE_STATUS_* constants
     */
    public function getCveStatus(?array $cvePackageState): string {
        if ($cvePackageState === null || empty($cvePackageState['id'])) {
            return self::CVE_STATUS_NOT_EVALUABLE;
        }

        if (empty($cvePackageState['checked'])) {
            return self::CVE_STATUS_NOT_CHECKED;
        }

        if ((int)$cvePackageState['cve_count'] === 0) {
            return self::CVE_STATUS_NO_KNOWN_CVES;
        }

        return self::CVE_STATUS_AFFECTED;
    }

    /**
     * @param string[] $stateHashes
     * @return array<string, int> Primary key of each package state, indexed by state_hash
     */
    private function getIdsByHashes(array $stateHashes): array {
        $ids = [];

        $rows = $this->unhydratedFind()
            ->select(['id', 'state_hash'])
            ->where(['state_hash IN' => $stateHashes])
            ->toArray();
        foreach ($rows as $row) {
            $ids[(string)$row['state_hash']] = (int)$row['id'];
        }

        return $ids;
    }

    /**
     * Writes the changed links of installations to their package state.
     *
     * @param array<int, array{0: int|null, 1: string|null}> $installations Installation id => [current link, state hash or null]
     * @param array<string, int> $stateIds Package state id, indexed by state_hash
     * @return int Number of changed installations
     */
    private function linkInstallations(array $installations, array $stateIds): int {
        // Installation id => new package state id, null to unlink
        $changes = [];
        foreach ($installations as $installationId => [$currentLink, $stateHash]) {
            $newLink = $stateHash === null ? null : ($stateIds[$stateHash] ?? null);
            if ($newLink !== $currentLink) {
                $changes[$installationId] = $newLink;
            }
        }

        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = $this->fetchTable('PackagesLinuxHosts');

        // Nearly every installation has its own package state, so one query per package state would mean
        // one query per installation. A CASE expression updates a whole chunk with a single query.
        foreach (array_chunk($changes, self::CHUNK_SIZE, true) as $chunk) {
            $newLinks = new CaseStatementExpression(new IdentifierExpression('id'));
            foreach ($chunk as $installationId => $stateId) {
                $newLinks->when($installationId, 'integer')->then($stateId, 'integer');
            }

            $PackagesLinuxHostsTable->updateAll(
                ['cve_package_state_id' => $newLinks],
                ['id IN' => array_keys($chunk)]
            );
        }

        return count($changes);
    }

    /**
     * @param string $feedDigest
     * @return array
     */
    private function getPendingConditions(string $feedDigest): array {
        return [
            'OR' => [
                'feed_digest IS' => null,
                'feed_digest !=' => $feedDigest,
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function emptyToNull(mixed $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    /**
     * @param mixed $value
     * @param int $length
     * @return string|null
     */
    private function limitLength(mixed $value, int $length): ?string {
        $value = $this->emptyToNull($value);
        return $value === null ? null : mb_substr($value, 0, $length);
    }
}
