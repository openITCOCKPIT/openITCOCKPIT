<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\PushAgent;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Core\Plugin;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use ImportModule\Model\Table\ImportedHostsTable;
use itnovum\openITCOCKPIT\Agent\AgentConfiguration;
use itnovum\openITCOCKPIT\Core\FileDebugger;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * PushAgents Model
 *
 * @property \App\Model\Table\AgentconfigsTable&\Cake\ORM\Association\BelongsTo $Agentconfigs
 *
 * @method \App\Model\Entity\PushAgent newEmptyEntity()
 * @method \App\Model\Entity\PushAgent newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\PushAgent[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PushAgent get($primaryKey, $options = [])
 * @method \App\Model\Entity\PushAgent findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\PushAgent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PushAgent[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PushAgent|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PushAgent saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PushAgent[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\PushAgent[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\PushAgent[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\PushAgent[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PushAgentsTable extends Table {
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('push_agents');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Agentconfigs', [
            'foreignKey' => 'agentconfig_id',
            'joinType'   => 'LEFT'

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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->notEmptyString('uuid')
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->scalar('hostname')
            ->maxLength('hostname', 255)
            ->allowEmptyString('hostname');

        $validator
            ->scalar('ipaddress')
            ->maxLength('ipaddress', 255)
            ->allowEmptyString('ipaddress');

        $validator
            ->scalar('remote_address')
            ->maxLength('remote_address', 255)
            ->allowEmptyString('remote_address');

        $validator
            ->scalar('http_x_forwarded_for')
            ->maxLength('http_x_forwarded_for', 255)
            ->allowEmptyString('http_x_forwarded_for');

        $validator
            ->scalar('checkresults')
            ->maxLength('checkresults', 16777215)
            ->allowEmptyString('checkresults');

        $validator
            ->dateTime('last_update')
            ->notEmptyDateTime('last_update');

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
        $rules->add($rules->isUnique(['uuid']), ['errorField' => 'uuid']);
        $rules->add($rules->existsIn(['agentconfig_id'], 'Agentconfigs'), ['errorField' => 'agentconfig_id']);

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['PushAgents.id' => $id]);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public function existsByUuid($uuid) {
        return $this->exists(['PushAgents.uuid' => $uuid]);
    }

    /**
     * @param string $uuid
     * @param string $password
     * @return bool
     */
    public function existsByUuidAndPassword($uuid, $password) {
        return $this->exists([
            'PushAgents.uuid'     => $uuid,
            'PushAgents.password' => $password
        ]);
    }

    /**
     * @param $uuid
     * @param $password
     * @return array|EntityInterface
     */
    public function getPushAgentByUuidAndPassword($uuid, $password) {
        return $this->find()
            ->where([
                'PushAgents.uuid'     => $uuid,
                'PushAgents.password' => $password
            ])
            ->firstOrFail();
    }

    /**
     * @param int $agentConfigId
     * @return bool
     */
    public function existsByAgentconfigId($agentConfigId) {
        return $this->exists([
            'PushAgents.agentconfig_id' => $agentConfigId
        ]);
    }

    /**
     * @param int $agentConfigId
     * @return array|bool|EntityInterface|null
     */
    public function getByAgentconfigId($agentConfigId) {
        return $this->find()
            ->where([
                'PushAgents.agentconfig_id' => $agentConfigId
            ])
            ->first();
    }

    /**
     * @param string $uuid
     * @param string $password
     * @return PushAgent|EntityInterface
     * @throws RecordNotFoundException
     */
    public function getConfigWithHostForSubmitCheckdata($uuid, $password) {
        $query = $this->find()
            ->where([
                'PushAgents.uuid'     => $uuid,
                'PushAgents.password' => $password,
                'PushAgents.agentconfig_id IS NOT NULL'
            ])
            ->contain([
                'Agentconfigs' => function (Query $q) {
                    $q
                        ->disableAutoFields()
                        ->select([
                            'Agentconfigs.id',
                            'Agentconfigs.host_id'
                        ])
                        ->contain([
                            'Hosts' => function (Query $q) {
                                $q
                                    ->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid'
                                    ]);
                                return $q;
                            }
                        ]);
                    return $q;
                }
            ]);
        //FileDebugger::dieQuery($query);
        return $query->firstOrFail();
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getPushAgents(GenericFilter $GenericFilter, PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {
        // Yes - this query is from hell!

        $query = $this->find();
        $query->select([
            'PushAgents.id',
            'PushAgents.uuid',
            'PushAgents.hostname',
            'Hosts.name',
            'Hosts.id',
            'Agentconfigs.host_id',
            'PushAgents.ipaddress',
            'PushAgents.remote_address',
            'PushAgents.http_x_forwarded_for',
            //'PushAgents.checkresults', // The Agent check result is not necessary, slows down the query and uses A LOT of memory with large paginator limits
            'PushAgents.last_update'
        ])
            ->leftJoin(
                ['Agentconfigs' => 'agentconfigs'],
                ['PushAgents.agentconfig_id = Agentconfigs.id']
            )
            ->leftJoin(
                ['Hosts' => 'hosts'],
                ['Agentconfigs.host_id = Hosts.id']
            );
        if (!empty($MY_RIGHTS)) {
            $placehoders = [];
            foreach ($MY_RIGHTS as $index => $MY_RIGHT) {
                $placehoders[] = sprintf(':myright%s', $index);
            }

            // SQL: IF(Hosts.id IS NOT NULL, `HostsToContainersSharing`.`container_id` in (1, 15, 99999), 1=1)
            $exp = $query->newExpr();
            $exp->add("IF(Hosts.id IS NOT NULL, `HostsToContainersSharing`.`container_id` IN (" . implode(', ', $placehoders) . "), 1=1) ");

            foreach ($MY_RIGHTS as $index => $MY_RIGHT) {
                $query->bind(
                    sprintf(':myright%s', $index),
                    $MY_RIGHT,
                    'integer'
                );
            }
            $query->select([
                'container_ids' => $query->newExpr('
            IF(Hosts.id IS NOT NULL,
                GROUP_CONCAT(
                    DISTINCT `HostsToContainersSharing`.`container_id`
                ),
                NULL)
                ')
            ])
                ->leftJoin(
                    ['HostsToContainersSharing' => 'hosts_to_containers'],
                    ['HostsToContainersSharing.host_id = Hosts.id']

                )->where($exp);
        }

        $where = $GenericFilter->genericFilters();
        if (isset($where['hasHostAssignment'])) {
            if ($where['hasHostAssignment'] == 1) {
                $query->whereNotNull([
                    'Hosts.id'
                ]);
            } else {
                $query->whereNull([
                    'Hosts.id'
                ]);
            }
            unset($where['hasHostAssignment']);
        }

        $query->where($where);
        $query->disableHydration();
        $query->orderBy(
            array_merge(
                $GenericFilter->getOrderForPaginator('Hosts.name', 'asc'),
                ['PushAgents.id' => 'asc']
            )

        );
        $query->groupBy('PushAgents.id');
        //FileDebugger::dieQuery($query);

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->emptyArrayIfNull($query->toArray());
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
     * @param int|null $agentConfigId
     * @return array
     */
    public function getPushAgentsForAssignments($agentConfigId = null) {
        $query = $this->find();
        if (is_numeric($agentConfigId)) {
            $query->where([
                'OR' => [
                    'PushAgents.agentconfig_id' => $agentConfigId,
                    'PushAgents.agentconfig_id IS NULL'
                ]
            ]);
        } else {
            $query->where('PushAgents.agentconfig_id IS NULL');
        }
        $query->disableHydration();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int $agentConfigId
     * @return array
     */
    public function getAgentOutputByAgentconfigId($agentConfigId) {
        try {
            $result = $this->find()
                ->where([
                    'PushAgents.agentconfig_id' => $agentConfigId
                ])
                ->firstOrFail();

            if (!empty($result->get('checkresults'))) {
                return json_decode($result->get('checkresults'), true);
            }
        } catch (RecordNotFoundException $e) {
            //Not found
        }
        return [];
    }

    /**
     * This method will return all Agents running in Push Mode which are
     * a) Not assigned to a host yet or
     * b) Where assigned automatically via the Import Module
     *
     * This method will NOT return Agents, which where assigned manually by a user!
     *
     * @param int $importerId
     * @return array
     */
    public function getAllPushAgentsForImport(int $importerId = 3) {
        if (!Plugin::isLoaded('ImportModule')) {
            throw new MissingComponentException(
                'ImportModule is required for this action'
            );
        }

        /** @var ImportedHostsTable $ImportedHostsTable */
        $ImportedHostsTable = TableRegistry::getTableLocator()->get('ImportModule.ImportedHosts');

        $query = $this->find();
        $importedHostsSubSelect = $ImportedHostsTable->subquery()
            ->select('ImportedHosts.id')
            ->where([
                'ImportedHosts.identifier = PushAgents.uuid',
                'ImportedHosts.importer_id' => $importerId
            ]);

        $query->select([
            'PushAgents.id',
            'PushAgents.uuid',
            'PushAgents.hostname',
            'PushAgents.ipaddress',
            'PushAgents.remote_address',
            'PushAgents.http_x_forwarded_for',
            'PushAgents.checkresults',
            'PushAgents.last_update'
        ])
            ->where([
                'OR' => [
                    'PushAgents.agentconfig_id IS NULL',
                    $importedHostsSubSelect
                ]
            ]);
        $query->disableHydration();
        $query->orderBy('PushAgents.ipaddress', 'asc');

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param $pushAgentUuid
     * @return array|EntityInterface|null
     */
    public function getByPushAgentByUuid($pushAgentUuid) {
        return $this->find()
            ->where([
                'PushAgents.uuid' => $pushAgentUuid
            ])
            ->first();
    }

    /**
     * @param string $agentUuid
     * @param int $hostId
     * Method used by ImportModule
     */
    public function assignHostToAgent(string $agentUuid, int $hostId) {
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');
        if ($AgentconfigsTable->existsByHostId($hostId)) {
            $configEntity = $AgentconfigsTable->getConfigByHostId($hostId);
        } else {
            // Create new dummy/default config
            $AgentConfiguration = new AgentConfiguration();
            $config = $AgentConfiguration->unmarshal('');
            $config['bool']['enable_push_mode'] = true;

            // Legacy configuration for Agent version < 3.x
            $AgentConfiguration->setConfigForJson($config);
            $data = [
                'host_id'       => $hostId,
                'port'          => $config['int']['bind_port'],
                'basic_auth'    => $config['bool']['use_http_basic_auth'],
                'username'      => $config['bool']['use_http_basic_auth'] ? $config['string']['username'] : '',
                'password'      => $config['bool']['use_http_basic_auth'] ? $config['string']['password'] : '',
                'proxy'         => $config['bool']['use_proxy'],
                'insecure'      => !$config['bool']['use_https_verify'], // Validate TLS certificate in PULL mode
                'use_https'     => $config['bool']['use_https'], // Use own TLS certificate for the agent like Let's Encrypt
                'use_autossl'   => $config['bool']['use_autossl'], // New field with agent 3.x
                'use_push_mode' => $config['bool']['enable_push_mode'], // New field with agent 3.x
                'config'        => $AgentConfiguration->marshal(), // New field with agent 3.x
            ];

            $configEntity = $AgentconfigsTable->newEmptyEntity();
            $configEntity = $AgentconfigsTable->patchEntity($configEntity, $data);
            $AgentconfigsTable->save($configEntity);
        }
        try {
            $entity = $this->find()
                ->where([
                    'uuid' => $agentUuid
                ])
                ->firstOrFail();

            $entity->set('agentconfig_id', $configEntity->get('id'));
            return $this->save($entity);
        } catch (RecordNotFoundException $e) {
        }
        return false;
    }
}
