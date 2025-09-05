<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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
use App\Model\Entity\Statuspagegroup;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * Statuspagegroups Model
 *
 * @property \App\Model\Table\ContainersTable&\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\StatuspagegroupCategoriesTable&\Cake\ORM\Association\HasMany $StatuspagegroupCategories
 * @property \App\Model\Table\StatuspagegroupCollectionsTable&\Cake\ORM\Association\HasMany $StatuspagegroupCollections
 * @property \App\Model\Table\StatuspagesMembershipTable&\Cake\ORM\Association\HasMany $StatuspagesToStatuspagegroups
 *
 * @method \App\Model\Entity\Statuspagegroup newEmptyEntity()
 * @method \App\Model\Entity\Statuspagegroup newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Statuspagegroup> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Statuspagegroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Statuspagegroup findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Statuspagegroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Statuspagegroup> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Statuspagegroup|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Statuspagegroup saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Statuspagegroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Statuspagegroup>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Statuspagegroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Statuspagegroup> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Statuspagegroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Statuspagegroup>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Statuspagegroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Statuspagegroup> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StatuspagegroupsTable extends Table {
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statuspagegroups');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER',
        ]);

        $this->hasMany('StatuspagegroupCategories', [
            'foreignKey'       => 'statuspagegroup_id',
            'saveStrategy'     => 'replace',
            'dependent'        => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('StatuspagegroupCollections', [
            'foreignKey'       => 'statuspagegroup_id',
            'saveStrategy'     => 'replace',
            'dependent'        => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('Statuspages', [
            'className'        => 'Statuspages',
            'through'          => 'StatuspagesMembership',
            'targetForeignKey' => 'statuspage_id',
            'saveStrategy'     => 'replace'
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
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['container_id'], 'Containers'), ['errorField' => 'container_id']);

        return $rules;
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param $PaginateOMat
     * @param $MY_RIGHTS
     * @return array
     */
    public function getStatuspagegroupsIndex(GenericFilter $GenericFilter, $PaginateOMat = null, $MY_RIGHTS = []): array {
        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->where($GenericFilter->genericFilters());

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Statuspagegroups.container_id IN' => $MY_RIGHTS]);
            }
            return $q;
        });
        $query->disableHydration();
        $query->orderBy($GenericFilter->getOrderForPaginator('Statuspagegroups.name', 'asc'));

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
     * @param int $id
     * @return bool
     */
    public function existsById(int $id): bool {
        return $this->exists(['Statuspagegroups.id' => $id]);
    }

    /**
     * @param int $id
     * @return Statuspagegroup
     */
    public function getStatuspagegroupForEdit(int $id): Statuspagegroup {
        $query = $this->find();
        $query->contain([
            'StatuspagegroupCategories'  => function (Query $query) {
                // Keep the order of categories stable
                return $query->orderBy(['StatuspagegroupCategories.id' => 'ASC']);
            },
            'StatuspagegroupCollections' => function (Query $query) {
                // Keep the order of collections stable
                return $query->orderBy(['StatuspagegroupCollections.id' => 'ASC']);
            },
            'Statuspages'                => function (Query $query) {
                return $query->select([
                    'id',
                    'name'
                ]);
            }
        ])
            ->where([
                'Statuspagegroups.id' => $id
            ]);

        return $query->firstOrFail();
    }

    /**
     * @param int $id
     * @return array
     */
    public function getStatuspagegroupForById(int $id): array {
        $query = $this->find();
        $query->contain([
            'StatuspagegroupCategories'  => function (Query $query) {
                // Keep the order of categories stable
                return $query->orderBy(['StatuspagegroupCategories.id' => 'ASC']);
            },
            'StatuspagegroupCollections' => function (Query $query) {
                // Keep the order of collections stable
                return $query->orderBy(['StatuspagegroupCollections.id' => 'ASC']);
            },
            'StatuspagesMemberships'     => function (Query $query) {
                return $query->select([
                    'id',
                    'name'
                ])->disableHydration();
            }
        ])
            ->where([
                'Statuspagegroups.id' => $id
            ])->disableHydration();

        return $query->firstOrFail();
    }
}
