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

use App\Model\Entity\CvePackageStateCve;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CvePackageStateCves Model
 *
 * Which CVE affects which package state. The fix and the severity depend on the distribution,
 * so they are stored here and not in the master data of the CVE.
 *
 * @property \App\Model\Table\CvePackageStatesTable&\Cake\ORM\Association\BelongsTo $CvePackageStates
 * @property \App\Model\Table\CvesTable&\Cake\ORM\Association\BelongsTo $Cves
 *
 * @method \App\Model\Entity\CvePackageStateCve newEmptyEntity()
 * @method \App\Model\Entity\CvePackageStateCve newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CvePackageStateCve> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CvePackageStateCve get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CvePackageStateCve patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CvePackageStateCve|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class CvePackageStateCvesTable extends Table {

    /**
     * Number of rows per INSERT in insertMatches()
     */
    private const CHUNK_SIZE = 500;

    /**
     * Columns written by insertMatches()
     */
    private const INSERT_FIELDS = [
        'cve_package_state_id',
        'cve_id',
        'fixed_in',
        'fix_state',
        'not_fixed_yet',
        'severity',
        'severity_source',
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_package_state_cves');
        $this->setDisplayField('cve_id');
        $this->setPrimaryKey('id');
        // The inflector turns "Cves" into "Cfe" (like knives => knife)
        $this->setEntityClass(CvePackageStateCve::class);

        $this->belongsTo('CvePackageStates', [
            'foreignKey' => 'cve_package_state_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsTo('Cves', [
            'foreignKey'   => 'cve_id',
            'joinType'     => 'INNER',
            'propertyName' => 'cve',
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
            ->integer('cve_package_state_id')
            ->requirePresence('cve_package_state_id', 'create')
            ->notEmptyString('cve_package_state_id');

        $validator
            ->integer('cve_id')
            ->requirePresence('cve_id', 'create')
            ->notEmptyString('cve_id');

        $validator
            ->scalar('fixed_in')
            ->maxLength('fixed_in', 255)
            ->allowEmptyString('fixed_in');

        $validator
            ->scalar('fix_state')
            ->maxLength('fix_state', 255)
            ->allowEmptyString('fix_state');

        $validator
            ->boolean('not_fixed_yet')
            ->notEmptyString('not_fixed_yet');

        $validator
            ->integer('severity')
            ->range('severity', [0, 4])
            ->allowEmptyString('severity');

        $validator
            ->scalar('severity_source')
            ->maxLength('severity_source', 64)
            ->allowEmptyString('severity_source');

        return $validator;
    }

    /**
     * Validates and inserts many rows at once.
     *
     * Deliberately not saveMany(): it sends one INSERT per row. Measured with 37,102 rows (Cake 5.4.4, MySQL 8.0):
     * saveMany() 6.4 s, newEntities() plus one INSERT per 500 rows 1.2 s. Every row still runs through the
     * validation of this table. This table has no timestamps, no buildRules() and no save events,
     * so nothing else gets skipped.
     *
     * @param array<int, array> $rows Rows with the columns of this table, without id
     * @return void
     * @throws PersistenceFailedException If one row fails the validation, nothing of the chunk gets saved
     */
    public function insertMatches(array $rows): void {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $entities = $this->newEntities($chunk);

            $query = $this->insertQuery()->insert(self::INSERT_FIELDS);
            foreach ($entities as $entity) {
                if ($entity->hasErrors()) {
                    throw new PersistenceFailedException($entity, ['insertMatches']);
                }
                $query->values($entity->extract(self::INSERT_FIELDS));
            }
            $query->execute();
        }
    }

    /**
     * Returns all CVEs of one package state with their master data, most severe first.
     * Used for the details of one package in the package list of a host.
     *
     * @param int $cvePackageStateId
     * @return array
     */
    public function getCvesOfPackageState(int $cvePackageStateId): array {
        return $this->unhydratedFind()
            ->contain([
                'Cves'
            ])
            ->where([
                'CvePackageStateCves.cve_package_state_id' => $cvePackageStateId
            ])
            ->orderBy([
                'Cves.kev_cisa'                => 'desc',
                'CvePackageStateCves.severity' => 'desc',
                'Cves.cvss_score'              => 'desc',
                'Cves.cve_id'                  => 'asc',
            ])
            ->toArray();
    }
}
