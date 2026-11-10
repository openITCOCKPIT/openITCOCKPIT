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
use App\Model\Entity\Cve;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * Cves Model
 *
 * Master data of all CVEs that Vuls reported for at least one package state.
 *
 * A CVE counts as open, as long as at least one enabled host has a package installed whose package state
 * is affected by the CVE. Updating the package links the installation to a new package state, so the CVE
 * disappears from the open CVEs without any further action.
 *
 * @property \App\Model\Table\CvePackageStateCvesTable&\Cake\ORM\Association\HasMany $CvePackageStateCves
 * @property \App\Model\Table\CveHostFindingsTable&\Cake\ORM\Association\HasMany $CveHostFindings
 *
 * @method \App\Model\Entity\Cve newEmptyEntity()
 * @method \App\Model\Entity\Cve newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Cve> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Cve get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Cve findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Cve patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Cve> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Cve|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Cve saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CvesTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Number of rows per query when reading or writing many CVEs at once
     */
    private const CHUNK_SIZE = 500;

    /**
     * Fields of the master data that get updated with every check
     */
    private const MASTER_DATA_FIELDS = [
        'summary',
        'published',
        'cvss_score',
        'cvss_vector',
        'cvss_source',
        'kev_cisa',
        'kev_cisa_added',
        'kev_vulncheck',
        'metasploit_count',
        'verified_exploit_count',
        'poc_exploit_count',
        'source_link',
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cves');
        $this->setDisplayField('cve_id');
        $this->setPrimaryKey('id');
        // The inflector turns "Cves" into "Cfe" (like knives => knife)
        $this->setEntityClass(Cve::class);

        $this->addBehavior('Timestamp');

        $this->hasMany('CvePackageStateCves', [
            'foreignKey' => 'cve_id',
        ]);

        $this->hasMany('CveHostFindings', [
            'foreignKey' => 'cve_id',
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
            ->scalar('cve_id')
            ->maxLength('cve_id', 64)
            ->requirePresence('cve_id', 'create')
            ->notEmptyString('cve_id');

        $validator
            ->scalar('summary')
            ->allowEmptyString('summary');

        $validator
            ->dateTime('published')
            ->allowEmptyDateTime('published');

        $validator
            ->decimal('cvss_score')
            ->range('cvss_score', [0, 10])
            ->allowEmptyString('cvss_score');

        $validator
            ->scalar('cvss_vector')
            ->maxLength('cvss_vector', 255)
            ->allowEmptyString('cvss_vector');

        $validator
            ->scalar('cvss_source')
            ->maxLength('cvss_source', 64)
            ->allowEmptyString('cvss_source');

        $validator
            ->boolean('kev_cisa')
            ->notEmptyString('kev_cisa');

        $validator
            ->date('kev_cisa_added')
            ->allowEmptyDate('kev_cisa_added');

        $validator
            ->boolean('kev_vulncheck')
            ->notEmptyString('kev_vulncheck');

        $validator
            ->nonNegativeInteger('metasploit_count')
            ->notEmptyString('metasploit_count');

        $validator
            ->nonNegativeInteger('verified_exploit_count')
            ->notEmptyString('verified_exploit_count');

        $validator
            ->nonNegativeInteger('poc_exploit_count')
            ->notEmptyString('poc_exploit_count');

        $validator
            ->scalar('source_link')
            ->maxLength('source_link', 512)
            ->allowEmptyString('source_link');

        $validator
            ->scalar('feed_digest')
            ->maxLength('feed_digest', 255)
            ->allowEmptyString('feed_digest');

        return $validator;
    }

    /**
     * Inserts new CVEs and updates the master data of existing ones.
     *
     * @param array<string, array> $cves Master data indexed by CVE id, the keys of each entry are the column names
     *                                   of this table, e.g. ['CVE-2026-1234' => ['cvss_score' => 7.5, 'kev_cisa' => false, ...]]
     * @param string $feedDigest Digest of the Vuls database the master data was taken from
     * @return array<string, int> Primary key of each CVE, indexed by CVE id
     */
    public function saveCves(array $cves, string $feedDigest): array {
        $ids = [];

        foreach (array_chunk($cves, self::CHUNK_SIZE, true) as $chunk) {
            /** @var array<string, \App\Model\Entity\Cve> $existing */
            $existing = $this->find()
                ->where(['Cves.cve_id IN' => array_map('strval', array_keys($chunk))])
                ->all()
                ->indexBy('cve_id')
                ->toArray();

            $entities = [];
            foreach ($chunk as $cveId => $masterData) {
                $cveId = (string)$cveId;
                $data = $this->normalizeMasterData($masterData);
                $data['feed_digest'] = $feedDigest;

                if (isset($existing[$cveId])) {
                    // Unchanged CVEs are not dirty, so saveMany() skips them
                    $entities[] = $this->patchEntity($existing[$cveId], $data);
                    continue;
                }

                $entities[] = $this->newEntity(['cve_id' => $cveId] + $data);
            }

            /** @var \App\Model\Entity\Cve $entity */
            foreach ($this->saveManyOrFail($entities) as $entity) {
                $ids[$entity->cve_id] = $entity->id;
            }
        }

        return $ids;
    }

    /**
     * Returns all open CVEs: CVEs that affect a package that is currently installed on at least one enabled host.
     *
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getOpenCves(GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->unhydratedFind();
        $query
            ->select([
                'Cves.id',
                'Cves.cve_id',
                'Cves.summary',
                'Cves.published',
                'Cves.cvss_score',
                'Cves.cvss_source',
                'Cves.kev_cisa',
                'Cves.kev_vulncheck',
                'Cves.metasploit_count',
                'Cves.verified_exploit_count',
                'max_severity'  => $query->func()->max('CvePackageStateCves.severity'),
                'host_count'    => $query->expr('COUNT(DISTINCT PackagesLinuxHosts.host_id)'),
                'package_count' => $query->expr('COUNT(DISTINCT CvePackageStates.name)'),
                'fix_available' => $query->func()->max($query->expr('CvePackageStateCves.fixed_in IS NOT NULL')),
            ]);

        $this->joinInstalledPackages($query, $MY_RIGHTS);

        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query
            ->groupBy(['Cves.id'])
            ->orderBy($GenericFilter->getOrderForPaginator('Cves.cvss_score', 'desc'));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Returns all installed packages of enabled hosts that are affected by the given CVE. One row per installation.
     *
     * @param string $cveId e.g. CVE-2026-1234
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostsByCveId(string $cveId, GenericFilter $GenericFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $query = $this->unhydratedFind()
            ->select([
                'Hosts.id',
                'Hosts.name',
                'Hosts.uuid',
                'Hosts.container_id',
                'PackagesLinuxHosts.id',
                'PackagesLinuxHosts.current_version',
                'PackagesLinuxHosts.available_version',
                'PackagesLinuxHosts.needs_update',
                'CvePackageStates.id',
                'CvePackageStates.name',
                'CvePackageStates.is_kernel',
                'CvePackageStates.kernel_release',
                'CvePackageStateCves.fixed_in',
                'CvePackageStateCves.fix_state',
                'CvePackageStateCves.not_fixed_yet',
                'CvePackageStateCves.severity',
                'CvePackageStateCves.severity_source',
            ])
            ->where([
                'Cves.cve_id' => $cveId
            ]);

        $this->joinInstalledPackages($query, $MY_RIGHTS);

        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        $query
            ->orderBy($GenericFilter->getOrderForPaginator('Hosts.name', 'asc'));

        return $this->getResult($query, $PaginateOMat);
    }

    /**
     * Deletes CVEs that do not affect any package state anymore.
     *
     * @return int Number of deleted CVEs
     */
    public function deleteUnusedCves(): int {
        // Findings of hosts keep their CVE, also after they got resolved, because they are the history
        return $this->deleteAll([
            ['id NOT IN' => $this->CvePackageStateCves->find()->select(['CvePackageStateCves.cve_id'])],
            ['id NOT IN' => $this->CveHostFindings->find()->select(['CveHostFindings.cve_id'])],
        ]);
    }

    /**
     * Joins CVE -> package state -> installed package -> enabled host.
     * The version check ignores links of installations that got updated after the last CVE check.
     *
     * @param SelectQuery $query
     * @param array $MY_RIGHTS
     * @return void
     */
    private function joinInstalledPackages(SelectQuery $query, array $MY_RIGHTS): void {
        $query
            ->innerJoin(
                ['CvePackageStateCves' => 'cve_package_state_cves'],
                ['CvePackageStateCves.cve_id = Cves.id']
            )
            ->innerJoin(
                ['CvePackageStates' => 'cve_package_states'],
                ['CvePackageStates.id = CvePackageStateCves.cve_package_state_id']
            )
            ->innerJoin(
                ['PackagesLinuxHosts' => 'packages_linux_hosts'],
                [
                    'PackagesLinuxHosts.cve_package_state_id = CvePackageStates.id',
                    'PackagesLinuxHosts.current_version = CvePackageStates.version',
                ]
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = PackagesLinuxHosts.host_id']
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
    }

    /**
     * @param SelectQuery $query
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    private function getResult(SelectQuery $query, ?PaginateOMat $PaginateOMat): array {
        if ($PaginateOMat === null) {
            //Just execute query
            return $query->toArray();
        }

        if ($PaginateOMat->useScroll()) {
            return $this->scrollCake4($query, $PaginateOMat->getHandler());
        }
        return $this->paginateCake4($query, $PaginateOMat->getHandler());
    }

    /**
     * Fills missing fields with their defaults and limits the length of strings to the column size.
     *
     * @param array $masterData
     * @return array
     */
    private function normalizeMasterData(array $masterData): array {
        $row = [];
        foreach (self::MASTER_DATA_FIELDS as $field) {
            $row[$field] = $masterData[$field] ?? null;
        }

        $row['kev_cisa'] = (bool)$row['kev_cisa'];
        $row['kev_vulncheck'] = (bool)$row['kev_vulncheck'];
        $row['metasploit_count'] = (int)$row['metasploit_count'];
        $row['verified_exploit_count'] = (int)$row['verified_exploit_count'];
        $row['poc_exploit_count'] = (int)$row['poc_exploit_count'];
        $row['cvss_vector'] = $row['cvss_vector'] === null ? null : mb_substr((string)$row['cvss_vector'], 0, 255);
        $row['cvss_source'] = $row['cvss_source'] === null ? null : mb_substr((string)$row['cvss_source'], 0, 64);
        $row['source_link'] = $row['source_link'] === null ? null : mb_substr((string)$row['source_link'], 0, 512);

        return $row;
    }
}
