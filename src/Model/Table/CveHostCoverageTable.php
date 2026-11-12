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

use App\Model\Entity\CveHostCoverage;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CveHostCoverage Model
 *
 * What could not be evaluated on a host, and why. One row per item instead of a JSON field, so it is possible
 * to ask across all hosts, e.g. which applications are installed most often without a CPE rule.
 *
 * Types, see CveHostCoverage::TYPE_*:
 * - reason:             Reason code of the evaluation, e.g. missing_source_metadata or running_kernel_unknown (CoverageReason class)
 * - missing_metadata:   Package without the information Vuls requires
 * - inactive_kernel:    Installed kernel that is not running (as was not evaluated by Vuls)
 * - unmapped_app:       Application without a CPE rule
 * - other_platform_cve: CVE that only affects another operating system according to its description
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\CveHostCoverage newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CveHostCoverage> newEntities(array $data, array $options = [])
 */
class CveHostCoverageTable extends Table {

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_host_coverage');
        $this->setDisplayField('value');
        $this->setPrimaryKey('id');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
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
            ->requirePresence('host_id', 'create')
            ->notEmptyString('host_id');

        $validator
            ->inList('type', [
                CveHostCoverage::TYPE_REASON,
                CveHostCoverage::TYPE_MISSING_METADATA,
                CveHostCoverage::TYPE_INACTIVE_KERNEL,
                CveHostCoverage::TYPE_UNMAPPED_APP,
                CveHostCoverage::TYPE_OTHER_PLATFORM_CVE,
            ])
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('value')
            ->maxLength('value', 255)
            ->requirePresence('value', 'create')
            ->notEmptyString('value');

        return $validator;
    }

    /**
     * Replaces the coverage of a host with the result of a new evaluation.
     *
     * @param int $hostId
     * @param array<string, string[]> $itemsByType e.g. ['reason' => ['running_kernel_unknown'], 'unmapped_app' => ['Discord', 'OBS']]
     * @return int Number of stored items
     */
    public function replaceCoverageOfHost(int $hostId, array $itemsByType): int {
        $rows = [];
        foreach ($itemsByType as $type => $values) {
            foreach (array_unique(array_map(fn($value) => mb_substr(trim((string)$value), 0, 255), $values)) as $value) {
                if ($value !== '') {
                    $rows[] = ['host_id' => $hostId, 'type' => (string)$type, 'value' => $value];
                }
            }
        }

        return $this->getConnection()->transactional(function () use ($hostId, $rows) {
            $this->deleteAll(['host_id' => $hostId]);
            if (!empty($rows)) {
                $this->saveManyOrFail($this->newEntities($rows));
            }
            return count($rows);
        });
    }

    /**
     * @param int $hostId
     * @return array<string, string[]> Values indexed by type
     */
    public function getCoverageOfHost(int $hostId): array {
        $coverage = [];

        $rows = $this->unhydratedFind()
            ->select(['type', 'value'])
            ->where(['CveHostCoverage.host_id' => $hostId])
            ->orderBy(['CveHostCoverage.type' => 'asc', 'CveHostCoverage.value' => 'asc'])
            ->toArray();
        foreach ($rows as $row) {
            $coverage[$row['type']][] = $row['value'];
        }

        return $coverage;
    }

    /**
     * Returns the most common values of one type across all enabled hosts,
     * e.g. the applications that need a CPE rule most.
     *
     * @param string $type One of CveHostCoverage::TYPE_*
     * @param int $limit
     * @param array $MY_RIGHTS
     * @return array<int, array{value: string, hosts: int}>
     */
    public function getMostCommonValues(string $type, int $limit = 50, array $MY_RIGHTS = []): array {
        $query = $this->unhydratedFind();
        $query
            ->select([
                'value' => 'CveHostCoverage.value',
                'hosts' => $query->expr('COUNT(DISTINCT CveHostCoverage.host_id)'),
            ])
            ->innerJoinWith('Hosts')
            ->where([
                'CveHostCoverage.type' => $type,
                'Hosts.disabled'       => 0,
            ])
            ->groupBy(['CveHostCoverage.value'])
            ->orderBy(['hosts' => 'desc', 'value' => 'asc'])
            ->limit($limit);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = CveHostCoverage.host_id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        return array_map(fn($row) => ['value' => (string)$row['value'], 'hosts' => (int)$row['hosts']], $query->toArray());
    }
}
