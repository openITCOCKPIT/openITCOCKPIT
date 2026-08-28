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

use App\itnovum\openITCOCKPIT\Filter\UserDefaultTemplatesFilter;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * UserDefaultTemplates Model
 *
 * @property \App\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 *
 * @method \App\Model\Entity\UserDefaultTemplate newEmptyEntity()
 * @method \App\Model\Entity\UserDefaultTemplate newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserDefaultTemplate> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserDefaultTemplate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserDefaultTemplate> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UserDefaultTemplatesTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('user_default_templates');
        $this->setDisplayField('i18n');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Usergroups', [
            'foreignKey' => 'usergroup_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsToMany('UserContainers', [
            'through'          => 'UserDefaultTemplatesToUserContainers',
            'className'        => 'Containers',
            'foreignKey'       => 'user_default_template_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'user_default_templates_to_user_containers',
            'propertyName'     => 'user_containers',
        ]);

        $this->belongsToMany('Containers', [
            'through'          => 'UserDefaultTemplatesToContainers',
            'className'        => 'Containers',
            'foreignKey'       => 'user_default_template_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'user_default_templates_to_containers',
            'propertyName'     => 'containers',
        ]);

        $this->belongsToMany('Ldapgroups', [
            'className'        => 'Ldapgroups',
            'joinTable'        => 'ldapgroups_to_user_default_templates',
            'foreignKey'       => 'user_default_template_id',
            'targetForeignKey' => 'ldapgroup_id',
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
            ->requirePresence('containers', true, __('You have to choose at least one option container.'))
            ->allowEmptyString('containers', null, false)
            ->multipleOptions('containers', [
                'min' => 1
            ], __('You have to choose at least one option container.'));


        $validator
            ->requirePresence('ldapgroups', true, __('You have to choose at least one LDAP group.'))
            ->allowEmptyString('ldapgroups', null, false)
            ->multipleOptions('ldapgroups', [
                'min' => 1
            ], __('You have to choose at least one LDAP group.'));

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', false)
            ->allowEmptyString('description', null, true);

        $validator
            ->integer('usergroup_id')
            ->requirePresence('usergroup_id', 'create')
            ->greaterThan('usergroup_id', 0, __('You have to select a user role.'))
            ->allowEmptyString('usergroup_id', null, false);

        $validator
            ->scalar('timezone')
            ->maxLength('timezone', 100)
            ->allowEmptyString('timezone');

        $validator
            ->scalar('dateformat')
            ->maxLength('dateformat', 100)
            ->allowEmptyString('dateformat');

        $validator
            ->boolean('showstatsinmenu')
            ->requirePresence('showstatsinmenu', 'create')
            ->allowEmptyString('showstatsinmenu', null, false);

        $validator
            ->integer('dashboard_tab_rotation')
            ->requirePresence('dashboard_tab_rotation', 'create')
            ->allowEmptyString('dashboard_tab_rotation', null, false);

        $validator
            ->integer('paginatorlength')
            ->requirePresence('paginatorlength', 'create')
            ->allowEmptyString('paginatorlength', null, false)
            ->greaterThan('paginatorlength', 0, __('Minimum amount is 1'))
            ->lessThanOrEqual('paginatorlength', 1000, __('Maximum amount is 1000'));

        $validator
            ->boolean('recursive_browser')
            ->requirePresence('recursive_browser', 'create')
            ->allowEmptyString('recursive_browser', null, false);

        $validator
            ->scalar('i18n')
            ->maxLength('i18n', 100)
            ->notEmptyString('i18n');

        $validator
            ->boolean('is_oauth')
            ->notEmptyString('is_oauth');

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
        $rules->add($rules->existsIn(['usergroup_id'], 'Usergroups'), ['errorField' => 'usergroup_id']);

        return $rules;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for containers and or user container roles
     */
    public function validateHasContainerOrContainerUserRolePermissions($value, $context) {
        // ITC-3073
        if (!empty($context['data']['usercontainers']['_ids']) || !empty($context['data']['usercontainerroles']['_ids'])) {
            return true;
        }

        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['UserDefaultTemplates.id' => $id]);
    }

    /**
     * @param array $rights
     * @param UserDefaultTemplatesFilter $UserDefaultTemplatesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getUserDefaultTemplatesIndex(UserDefaultTemplatesFilter $UserDefaultTemplatesFilter, PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {

        $where = $UserDefaultTemplatesFilter->indexFilter();

        $query = $this->find();
        $query->select([
            'UserDefaultTemplates.id',
            'UserDefaultTemplates.is_oauth',
            'UserDefaultTemplates.name',
            'UserDefaultTemplates.description',
            'Usergroups.id',
            'Usergroups.name',
        ])
            ->matching('Containers')
            ->contain([
                'Usergroups',
                'Containers',
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->where([
                'UserDefaultTemplatesToContainers.container_id IN' => $MY_RIGHTS
            ]);
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }

        $query->orderBy(
            $UserDefaultTemplatesFilter->getOrderForPaginator('UserDefaultTemplates.id', 'asc')
        );
        $query->groupBy([
            'UserDefaultTemplates.id'
        ]);

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
     * @return array
     */
    public function getUserDefaultTemplateForEdit($id) {

        $query = $this->find()
            ->select([
                'UserDefaultTemplates.id',
                'UserDefaultTemplates.usergroup_id',
                'UserDefaultTemplates.name',
                'UserDefaultTemplates.description',
                'UserDefaultTemplates.timezone',
                'UserDefaultTemplates.i18n',
                'UserDefaultTemplates.dateformat',
                'UserDefaultTemplates.showstatsinmenu',
                'UserDefaultTemplates.dashboard_tab_rotation',
                'UserDefaultTemplates.paginatorlength',
                'UserDefaultTemplates.recursive_browser',
                'UserDefaultTemplates.is_oauth'
            ])
            ->where([
                'UserDefaultTemplates.id' => $id
            ])
            ->contain([
                'Usergroups',
                'UserContainers',
                'Containers',
                'Ldapgroups' => 'Usercontainerroles'
            ])
            ->disableHydration()
            ->first();

        $userDefaultTemplate = $query;

        $intCasts = [
            'showstatsinmenu',
            'dashboard_tab_rotation',
            'paginatorlength',
            'recursive_browser'
        ];
        foreach ($intCasts as $intCast) {
            $userDefaultTemplate[$intCast] = (int)$userDefaultTemplate[$intCast];
        }

        $userDefaultTemplate['usercontainers'] = [
            '_ids' => Hash::extract($query, 'user_containers.{n}.id')
        ];
        $userDefaultTemplate['containers'] = [
            '_ids' => Hash::extract($query, 'containers.{n}.id')
        ];
        $userDefaultTemplate['ldapgroups'] = [
            '_ids' => Hash::extract($query, 'ldapgroups.{n}.id')
        ];
        $userDefaultTemplate['usercontainerroles'] = [
            '_ids' => Hash::extract($query, 'ldapgroups.{n}.usercontainerroles.{n}.id')
        ];

        //Build up data struct for radio inputs (only of user containers - NOT for container roles)
        $userDefaultTemplate['UserDefaultTemplatesToUserContainers'] = [];
        foreach ($query['user_containers'] as $container) {
            //Cast permission_level to int for Angular... (AngularJS requires a string)
            $userDefaultTemplate['UserDefaultTemplatesToUserContainers'][$container['id']] = (int)$container['_joinData']['permission_level'];
        }

        if (empty($userDefaultTemplate['UserDefaultTemplatesToUserContainers'])) {
            //Make this an empty object {} in the JSON, not an empty array []
            $userDefaultTemplate['UserDefaultTemplatesToUserContainers'] = new \stdClass();
        }
        return [
            'UserDefaultTemplate' => $userDefaultTemplate
        ];
    }

    /**
     * May deprecated functions after fully moving to cakephp 4
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getUserDefaultTemplateById($id) {
        $query = $this->find('all')
            ->disableHydration()
            ->contain([
                'UserContainers',
                'Containers',
            ])
            ->where([
                'UserDefaultTemplates.id' => $id
            ]);
        if (is_null($query)) {
            return [];
        }
        return $query->first();
    }

    /**
     * @param array $ldapgroupIds
     * @param array $MY_RIGHTS
     * @return
     */
    public function getUserDefaultTemplatesForUserEdit(array $ldapgroupIds = [], array $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->select([
                'UserDefaultTemplates.id',
                'UserDefaultTemplates.usergroup_id',
                'UserDefaultTemplates.container_id',
                'UserDefaultTemplates.name',
                'UserDefaultTemplates.description',
                'UserDefaultTemplates.timezone',
                'UserDefaultTemplates.i18n',
                'UserDefaultTemplates.dateformat',
                'UserDefaultTemplates.showstatsinmenu',
                'UserDefaultTemplates.dashboard_tab_rotation',
                'UserDefaultTemplates.paginatorlength',
                'UserDefaultTemplates.recursive_browser',
                'UserDefaultTemplates.is_oauth'
            ])
            ->contain([
                'Usergroups',
                'UserContainers',
                'Containers',
                'Ldapgroups',
                'FallbackContainers'
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Containers', function (Query $query) use ($MY_RIGHTS) {
                return $query->where(['Containers.id IN' => $MY_RIGHTS]);
            });
        }
        if (!empty($ldapgroupIds)) {
            $query->innerJoinWith('Ldapgroups', function (Query $query) use ($ldapgroupIds) {
                return $query->where(['Ldapgroups.id IN' => $ldapgroupIds]);
            });
        }

        $query->disableHydration()
            ->distinct(['UserDefaultTemplates.id'])
            ->all();

        $result = $query->toArray();
        $userDefaultTemplates = $result;

        $intCasts = [
            'showstatsinmenu',
            'dashboard_tab_rotation',
            'paginatorlength',
            'recursive_browser'
        ];

        foreach ($userDefaultTemplates as $userDefaultTemplateId => $userDefaultTemplate) {
            foreach ($intCasts as $intCast) {
                $userDefaultTemplates[$userDefaultTemplateId][$intCast] = (int)$userDefaultTemplates[$userDefaultTemplateId][$intCast];
            }

            $userDefaultTemplates[$userDefaultTemplateId]['user_containers'] = [
                '_ids' => Hash::extract($result[$userDefaultTemplateId], 'user_containers.{n}.id')
            ];
            $userDefaultTemplates[$userDefaultTemplateId]['containers'] = [
                '_ids' => Hash::extract($result[$userDefaultTemplateId], 'containers.{n}.id')
            ];
            $userDefaultTemplates[$userDefaultTemplateId]['ldapgroups'] = [
                '_ids' => Hash::extract($result[$userDefaultTemplateId], 'ldapgroups.{n}.id')
            ];

            //Build up data struct for radio inputs (only of user containers - NOT for container roles)
            $userDefaultTemplates[$userDefaultTemplateId]['UserDefaultTemplatesToUserContainers'] = [];
            foreach ($result[$userDefaultTemplateId]['user_containers'] as $container) {
                //Cast permission_level to int for Angular... (AngularJS requires a string)
                $userDefaultTemplates[$userDefaultTemplateId]['UserDefaultTemplatesToUserContainers'][$container['id']] = (int)$container['_joinData']['permission_level'];
            }

            if (empty($userDefaultTemplates[$userDefaultTemplateId]['UserDefaultTemplatesToUserContainers'])) {
                //Make this an empty object {} in the JSON, not an empty array []
                $userDefaultTemplates[$userDefaultTemplateId]['UserDefaultTemplatesToUserContainers'] = new \stdClass();
            }
        }

        return [
            'UserDefaultTemplates'       => Api::makeItJavaScriptAble(Hash::combine($userDefaultTemplates, '{n}.id', '{n}.name')),
            'UserDefaultTemplateDetails' => Hash::combine($userDefaultTemplates, '{n}.id', '{n}')
        ];
    }

    public function getUserDefaultTemplatesForLdapUserImport(): array {
        $query = $this->find()
            ->select([
                'UserDefaultTemplates.id',
                'UserDefaultTemplates.usergroup_id',
                'UserDefaultTemplates.name',
                'UserDefaultTemplates.description',
                'UserDefaultTemplates.timezone',
                'UserDefaultTemplates.i18n',
                'UserDefaultTemplates.dateformat',
                'UserDefaultTemplates.showstatsinmenu',
                'UserDefaultTemplates.paginatorlength',
                'UserDefaultTemplates.recursive_browser',
                'UserDefaultTemplates.is_oauth',
                'UserDefaultTemplates.dashboard_tab_rotation',
                'Ldapgroups.id',
                'Ldapgroups.cn',
                'Ldapgroups.dn'
            ])
            ->innerJoinWith('Ldapgroups', function (Query $query) {
                return $query->contain(['Usercontainerroles']);
            })
            ->contain(['UserContainers'])
            ->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }
}
