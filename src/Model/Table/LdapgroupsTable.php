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

use App\Model\Entity\Ldapgroup;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Ldapgroups Model
 *
 * @property \App\Model\Table\UsercontainerrolesTable&\Cake\ORM\Association\HasMany $LdapgroupsToUsercontainerroles
 *
 * @method \App\Model\Entity\Ldapgroup newEmptyEntity()
 * @method \App\Model\Entity\Ldapgroup newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Ldapgroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Ldapgroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Ldapgroup findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Ldapgroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Ldapgroup[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Ldapgroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Ldapgroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Ldapgroup[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Ldapgroup[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Ldapgroup[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Ldapgroup[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class LdapgroupsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('ldapgroups');
        $this->setDisplayField('cn');
        $this->setPrimaryKey('id');

        $this->belongsToMany('Usercontainerroles', [
            'className'        => 'Usercontainerroles',
            'joinTable'        => 'ldapgroups_to_usercontainerroles',
            'foreignKey'       => 'ldapgroup_id',
            'targetForeignKey' => 'usercontainerrole_id',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Usergroups', [
            'className'        => 'Usergroups',
            'joinTable'        => 'ldapgroups_to_usergroups',
            'foreignKey'       => 'ldapgroup_id',
            'targetForeignKey' => 'usergroup_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('cn')
            ->maxLength('cn', 255)
            ->requirePresence('cn', 'create')
            ->notEmptyString('cn');

        $validator
            ->scalar('dn')
            ->maxLength('dn', 512)
            ->requirePresence('dn', 'create')
            ->notEmptyString('dn');

        $validator
            ->scalar('description')
            ->maxLength('description', 512)
            ->allowEmptyString('description');

        return $validator;
    }


    /**
     * @param bool $enableHydration
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getGroups(bool $enableHydration = true) {
        $result = $this->find()
            ->enableHydration($enableHydration)
            ->all();

        return $result;
    }

    /**
     * @return Ldapgroup[]
     */
    public function getGroupsForSync() {
        $result = $this->find()
            ->select([
                'id',
                'dn'
            ])
            ->all();

        $resultHash = [];
        foreach ($result as $record) {
            /** @var Ldapgroup $record */
            $resultHash[$record->dn] = $record;
        }

        return $resultHash;
    }

    /**
     * @param array $where
     * @param $selected
     * @return array
     */
    public function getLdapgroupsForAngular(array $where, $selected = []) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }

        $query = $this->find('list');

        if (is_array($selected)) {
            $selected = array_filter($selected);
        }
        if (!empty($selected)) {
            $where['NOT'] = [
                'Ldapgroups.id IN' => $selected
            ];
        }

        if (!empty($where['NOT'])) {
            // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
            $where['NOT'] = [
                'OR' => $where['NOT']
            ];
        }
        if (!empty($where)) {
            $query->where($where);
        }
        $query->orderBy([
            'Ldapgroups.cn' => 'asc'
        ]);
        $query->limit(ITN_AJAX_LIMIT);

        $ldapgroupsWithLimit = $query->toArray();

        $selectedLdapgroups = [];
        if (!empty($selected)) {
            $query = $this->find('list');
            $where = [
                'Ldapgroups.id IN' => $selected
            ];

            if (!empty($where['NOT'])) {
                // https://github.com/cakephp/cakephp/issues/14981#issuecomment-694770129
                $where['NOT'] = [
                    'OR' => $where['NOT']
                ];
            }

            if (!empty($where)) {
                $query->where($where);
            }
            $query->orderBy([
                'Ldapgroups.cn' => 'asc'
            ]);

            $selectedLdapgroups = $query->toArray();

        }

        $ldapgroups = $ldapgroupsWithLimit + $selectedLdapgroups;
        asort($ldapgroups, SORT_FLAG_CASE | SORT_NATURAL);
        return $ldapgroups;
    }

    /**
     * @param $dn
     * @param bool $enableHydration
     * @return array
     */
    public function getGroupsByDn($dn, bool $enableHydration = false) {
        if (!is_array($dn)) {
            $dn = [$dn];
        }

        $query = $this->find()
            ->where([
                'dn IN' => $dn
            ])
            ->enableHydration($enableHydration)
            ->all();

        if (empty($query)) {
            return [];
        }

        return $query->toArray();
    }

}
