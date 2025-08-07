<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Hostgroup;
use Cake\Database\Expression\Comparison;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\HostgroupConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\HostgroupFilter;

/**
 * Hostgroups Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\HostsTable|\Cake\ORM\Association\BelongsToMany $Hosts
 * @property \App\Model\Table\HosttemplatesTable|\Cake\ORM\Association\BelongsToMany $Hosttemplates
 *
 * @method \App\Model\Entity\Hostgroup get($primaryKey, $options = [])
 * @method \App\Model\Entity\Hostgroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Hostgroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Hostgroup|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hostgroup|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hostgroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Hostgroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Hostgroup findOrCreate($search, callable $callback = null, $options = [])
 */
class HostgroupsTable extends Table {

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;
    use PluginManagerTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('hostgroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ])->setDependent(true);

        $this->belongsToMany('Hosts', [
            'className'        => 'Hosts',
            'foreignKey'       => 'hostgroup_id',
            'targetForeignKey' => 'host_id',
            'joinTable'        => 'hosts_to_hostgroups',
            'saveStrategy'     => 'replace'
        ]);
        $this->belongsToMany('Hosttemplates', [
            'className'        => 'Hosttemplates',
            'foreignKey'       => 'hostgroup_id',
            'targetForeignKey' => 'hosttemplate_id',
            'joinTable'        => 'hosttemplates_to_hostgroups',
            'saveStrategy'     => 'replace'
        ]);
        $this->belongsToMany('Statuspages', [
            'className'        => 'Statuspages',
            'foreignKey'       => 'hostgroup_id',
            'targetForeignKey' => 'statuspage_id',
            'joinTable'        => 'statuspages_to_hostgroups',
            'saveStrategy'     => 'replace'
        ])->setDependent(true);
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
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', false)
            ->allowEmptyString('description', null, true);

        $validator
            ->scalar('hostgroup_url')
            ->maxLength('hostgroup_url', 255)
            ->allowEmptyString('hostgroup_url', null, true)
            ->url('hostgroup_url', __('Not a valid URL.'));

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
        $rules->add($rules->isUnique(['uuid']));
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param string $index
     * @return array|\Cake\ORM\Query
     * @deprecated Use self::getHostgroupsByContainerId()
     */
    public function hostgroupsByContainerId($containerIds = [], $type = 'all', $index = 'container_id') {
        return $this->getHostgroupsByContainerId($containerIds, $type, $index);
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param string $index
     * @return array
     */
    public function getHostgroupsByContainerId($containerIds = [], $type = 'all', $index = 'container_id') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $tenantContainerIds = [];

        foreach ($containerIds as $container_id) {
            if ($container_id != ROOT_CONTAINER) {
                // Get contaier id of the tenant container
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load contactgroups and contacts
                $path = $ContainersTable->getPathByIdAndCacheResult($container_id, 'HostgroupHostgroupsByContainerId');

                // Tenant host groups are available for all users of a tenant (oITC V2 legacy)
                $tenantContainerIds[] = $path[1]['id'];
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);
        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));


        switch ($type) {
            case 'all':
                $query = $this->find()
                    ->contain([
                        'Containers'
                    ])
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_HOSTGROUP
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();

                return $this->emptyArrayIfNull($query->toArray());


            default:
                $query = $this->find()
                    ->contain([
                        'Containers'
                    ])
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_HOSTGROUP
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();

                $query = $query->toArray();
                if (empty($query)) {
                    $query = [];
                }


                $return = [];
                foreach ($query as $hostgroup) {
                    if ($index === 'id') {
                        $return[$hostgroup['id']] = $hostgroup['container']['name'];
                    } else {
                        $return[$hostgroup['container_id']] = $hostgroup['container']['name'];
                    }
                }

                return $return;
        }
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getHostgroupsAsList($ids = [], $MY_RIGHTS = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->select([
                'Hostgroups.id',
                'Containers.name'
            ])
            ->contain(['Containers'])
            ->disableHydration();

        $where = [];
        if (!empty($ids)) {
            $where = [
                'Hostgroups.id IN'            => $ids,
                'Containers.containertype_id' => CT_HOSTGROUP
            ];
        }

        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        if (!empty($where)) {
            $query->where($where);
        }

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $list = [];
        foreach ($result as $row) {
            $list[$row['id']] = $row['container']['name'];
        }

        return $list;
    }

    /**
     * @param HostgroupFilter $HostgroupFilter
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     * @package array $MY_RIGHTS
     */
    public function getHostgroupsIndex(HostgroupFilter $HostgroupFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find()
            ->contain([
                'Containers'
            ]);

        $where = $HostgroupFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }
        $query->where($where);

        $query->orderBy($HostgroupFilter->getOrderForPaginator('Containers.name', 'asc'));

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
     * @param array $dataToParse
     * @param ObjectsCache|null $Cache
     * @return array|array[]
     */
    public function resolveDataForChangelog($dataToParse = [], ?ObjectsCache $Cache = null) {
        $extDataForChangelog = [
            'Host'         => [],
            'Hosttemplate' => [],
        ];

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');

        if (!empty($dataToParse['Hostgroup']['hosts']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($HostsTable->getHostsAsList($dataToParse['Hostgroup']['hosts']['_ids']) as $hostId => $hostName) {
                    $extDataForChangelog['Host'][] = [
                        'id'   => $hostId,
                        'name' => $hostName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Hostgroup']['hosts']['_ids'] as $hostId) {
                    if (!$Cache->has(OBJECT_HOST, $hostId)) {
                        foreach ($HostsTable->getHostsAsList($hostId) as $hostName) {
                            $Cache->set(OBJECT_HOST, $hostId, [
                                'id'   => $hostId,
                                'name' => $hostName
                            ]);
                        }
                    }
                    $extDataForChangelog['Host'][] = $Cache->get(OBJECT_HOST, $hostId);
                }
            }
        }

        if (!empty($dataToParse['Hostgroup']['hosttemplates']['_ids'])) {
            if ($Cache === null) {
                // Legacy - no caching
                foreach ($HosttemplatesTable->getHosttemplatesAsList($dataToParse['Hostgroup']['hosttemplates']['_ids']) as $hosttemplateId => $hosttemplateName) {
                    $extDataForChangelog['Hosttemplate'][] = [
                        'id'   => $hosttemplateId,
                        'name' => $hosttemplateName
                    ];
                }
            } else {
                // Used the passed Cache instance
                foreach ($dataToParse['Hostgroup']['hosttemplates']['_ids'] as $hosttemplateId) {
                    if (!$Cache->has(OBJECT_HOSTTEMPLATE, $hosttemplateId)) {
                        foreach ($HosttemplatesTable->getHosttemplatesAsList($hosttemplateId) as $hosttemplateName) {
                            $Cache->set(OBJECT_HOSTTEMPLATE, $hosttemplateId, [
                                'id'   => $hosttemplateId,
                                'name' => $hosttemplateName
                            ]);
                        }
                    }
                    $extDataForChangelog['Hosttemplate'][] = $Cache->get(OBJECT_HOSTTEMPLATE, $hosttemplateId);
                }
            }
        }

        return $extDataForChangelog;
    }

    public function getHostgroupForEdit($id) {
        $query = $this->find()
            ->where([
                'Hostgroups.id' => $id
            ])
            ->contain([
                'Hosts',
                'Hosttemplates',
                'Containers'
            ])
            ->disableHydration()
            ->first();

        $hostgroup = $query;
        $hostgroup['hosts'] = [
            '_ids' => Hash::extract($query, 'hosts.{n}.id')
        ];
        $hostgroup['hosttemplates'] = [
            '_ids' => Hash::extract($query, 'hosttemplates.{n}.id')
        ];

        return [
            'Hostgroup' => $hostgroup
        ];
    }

    /**
     * @param int $id
     * @return \App\Model\Entity\Hostgroup
     */
    public function getHostgroupById($id) {
        return $this->get($id, contain: [
            'Containers'
        ]);
    }

    /**
     * @param int $id
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostgroupByIdForMapeditor($id, $MY_RIGHTS = []) {
        $query = $this->find()
            ->contain([
                'Containers'    => function (Query $q) {
                    return $q->select([
                        'Containers.id',
                        'Containers.name'
                    ]);
                },
                'Hosts'         => function (Query $q) use ($MY_RIGHTS) {
                    if (!empty($MY_RIGHTS)) {
                        $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                            'HostsToContainersSharing.host_id = Hosts.id'
                        ]);
                        $q->where([
                            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                        ]);
                    }
                    $q->contain([
                        'HostsToContainersSharing',
                        'Services' => function (Query $q) {
                            return $q->where([
                                'Services.disabled' => 0
                            ])
                                ->select([
                                    'Services.id',
                                    'Services.uuid',
                                    'Services.host_id'
                                ]);
                        }
                    ])->where([
                        'Hosts.disabled' => 0
                    ]);
                    return $q;
                },
                'Hosttemplates' => function (Query $q) use ($MY_RIGHTS) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'id'
                        ])
                        ->contain([
                            'Hosts' => function (Query $query) use ($MY_RIGHTS) {
                                if (!empty($MY_RIGHTS)) {
                                    $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                        'HostsToContainersSharing.host_id = Hosts.id'
                                    ]);
                                    $query->where([
                                        'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                    ]);
                                }

                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid',
                                        'Hosts.name',
                                        'Hosts.hosttemplate_id'
                                    ])
                                    ->contain([
                                        'HostsToContainersSharing',
                                        'Services'
                                    ]);
                                $query
                                    ->leftJoinWith('Hostgroups')
                                    ->whereNull('Hostgroups.id');
                                return $query;
                            }
                        ]);
                }
            ])
            ->where([
                'Hostgroups.id' => $id
            ])
            ->select([
                'Hostgroups.id',
                'Hostgroups.description'
            ]);

        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param HostgroupConditions $HostgroupConditions
     * @param array|int $selected
     * @param bool $returnEmptyArrayIfMyRightsIsEmpty
     * @return array|null
     */
    public function getHostgroupsForAngular(HostgroupConditions $HostgroupConditions, $selected = [], $returnEmptyArrayIfMyRightsIsEmpty = false) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $selected = array_filter($selected);

        if ($returnEmptyArrayIfMyRightsIsEmpty) {
            if (empty($HostgroupConditions->getContainerIds())) {
                return [];
            }
        }


        $where = $HostgroupConditions->getConditionsForFind();

        if (!empty($selected)) {
            $where['NOT'] = [
                'Hostgroups.id IN' => $selected
            ];
        }

        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->select([
                'Containers.name',
                'Hostgroups.id'
            ])
            ->where(
                $where
            )
            ->orderBy([
                'Containers.name' => 'asc'
            ])
            ->limit(ITN_AJAX_LIMIT)
            ->disableHydration()
            ->all();

        $hostgroupsWithLimit = [];
        $result = $this->emptyArrayIfNull($query->toArray());
        foreach ($result as $row) {
            $hostgroupsWithLimit[$row['id']] = $row['container']['name'];
        }

        $selectedHostgroups = [];
        if (!empty($selected)) {
            $query = $this->find()
                ->contain([
                    'Containers'
                ])
                ->select([
                    'Containers.name',
                    'Hostgroups.id'
                ])
                ->where([
                    'Hostgroups.id IN' => $selected
                ]);

            if (!empty($HostgroupConditions->getContainerIds())) {
                $query->where([
                    'Containers.parent_id IN' => $HostgroupConditions->getContainerIds()
                ]);
            }
            $query->orderBy([
                'Containers.name' => 'asc'
            ])
                ->limit(ITN_AJAX_LIMIT)
                ->disableHydration()
                ->all();

            $selectedHostgroups = [];
            $result = $this->emptyArrayIfNull($query->toArray());
            foreach ($result as $row) {
                $selectedHostgroups[$row['id']] = $row['container']['name'];
            }
        }

        $hostgroups = $hostgroupsWithLimit + $selectedHostgroups;
        asort($hostgroups, SORT_FLAG_CASE | SORT_NATURAL);

        return $hostgroups;
    }

    /**
     * @param HostgroupConditions $HostgroupConditions
     * @param array|int $selected
     * @return array|null
     */
    public function getHostgroupsByContainerIdNew(HostgroupConditions $HostgroupConditions) {
        $where = $HostgroupConditions->getConditionsForFind();

        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->select([
                'Containers.name',
                'Hostgroups.id'
            ])
            ->where(
                $where
            )
            ->orderBy([
                'Containers.name' => 'asc'
            ])
            ->disableHydration()
            ->all();

        $hostgroups = [];
        $result = $this->emptyArrayIfNull($query->toArray());
        foreach ($result as $row) {
            $hostgroups[$row['id']] = $row['container']['name'];
        }

        return $hostgroups;
    }

    /**
     * @param HostgroupFilter $HostgroupFilter
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostgroupsForPdf(HostgroupFilter $HostgroupFilter, $MY_RIGHTS = []) {

        $query = $this->find()
            ->contain([
                'Hosts',
                'Containers'
            ]);

        $where = $HostgroupFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }
        $query->where($where)
            ->orderBy(
                $HostgroupFilter->getOrderForPaginator('Containers.name', 'asc')
            )->disableHydration();

        $data = $query->all();
        return $this->emptyArrayIfNull($data->toArray());
    }

    /**
     * @return array
     */
    public function getHostgroupsForExport() {
        $query = $this->find()
            ->select([
                'Hostgroups.id',
                'Hostgroups.uuid',
                'Hostgroups.description'
            ])
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param int $id
     * @return string
     * @throws RecordNotFoundException
     */
    public function getHostgroupUuidById($id) {
        $query = $this->find()
            ->select([
                'Hostgroups.uuid',
            ])
            ->where([
                'Hostgroups.id' => $id
            ]);

        $hostgroup = $query->firstOrFail();

        return $hostgroup->get('uuid');
    }

    /**
     * @param string $uuid
     * @return array
     */
    public function getHostsByHostgroupUuidForExternalcommands($uuid) {
        $query = $this->find()
            ->select([
                'Hostgroups.id'
            ])
            ->where([
                'Hostgroups.uuid' => $uuid,
            ])
            ->contain([
                'Hosts' =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'Hosts.id',
                                'Hosts.uuid',
                                'Hosts.satellite_id',
                                'Hosts.active_checks_enabled'
                            ])
                            ->where([
                                'Hosts.disabled' => 0
                            ])
                            ->contain([
                                'Hosttemplates' =>
                                    function (Query $q) {
                                        return $q->enableAutoFields(false)
                                            ->select([
                                                'Hosttemplates.active_checks_enabled'
                                            ]);
                                    }
                            ]);
                    },
            ])->disableHydration();
        try {
            $result = $query->firstOrFail();
            return $result;
        } catch (RecordNotFoundException $e) {
            return [];
        }
    }

    /**
     * @param string $uuid
     * @return array
     */
    public function getHostsByHostgroupUuidForExternalcommandsIncludeingHosttemplateHosts($uuid) {
        $query = $this->find()
            ->select([
                'Hostgroups.id'
            ])
            ->where([
                'Hostgroups.uuid' => $uuid,
            ])
            ->contain([
                'Hosts'         =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'Hosts.id',
                                'Hosts.uuid',
                                'Hosts.satellite_id',
                                'Hosts.active_checks_enabled'
                            ])
                            ->where([
                                'Hosts.disabled' => 0
                            ])
                            ->contain([
                                'Hosttemplates' =>
                                    function (Query $q) {
                                        return $q->enableAutoFields(false)
                                            ->select([
                                                'Hosttemplates.id',
                                                'Hosttemplates.active_checks_enabled'
                                            ]);
                                    }
                            ]);
                    },
                'Hosttemplates' =>
                    function (Query $q) {
                        return $q->enableAutoFields(false)
                            ->select([
                                'Hosttemplates.id',
                            ]);
                    },
            ])->disableHydration();
        try {
            $tmpResult = $query->firstOrFail();
            /** @var $HostsTable HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

            $hosts = [];
            foreach ($tmpResult['hosts'] as $host) {
                $hosts[$host['id']] = $host;
            }

            foreach ($tmpResult['hosttemplates'] as $hosttemplate) {
                $hostsFromHosttemplate = $HostsTable->find()
                    ->select([
                        'Hosts.id',
                        'Hosts.uuid',
                        'Hosts.satellite_id',
                        'Hosts.active_checks_enabled'
                    ])
                    ->where([
                        'Hosts.hosttemplate_id' => $hosttemplate['id'],
                        'Hosts.disabled'        => 0
                    ])
                    ->contain([
                        'Hosttemplates' =>
                            function (Query $q) {
                                return $q->enableAutoFields(false)
                                    ->select([
                                        'Hosttemplates.id',
                                        'Hosttemplates.active_checks_enabled'
                                    ]);
                            }
                    ])
                    ->disableHydration()
                    ->all();

                //Merge Hosts from host templates
                $hostsFromHosttemplate = $this->emptyArrayIfNull($hostsFromHosttemplate->toArray());
                foreach ($hostsFromHosttemplate as $hostFromHosttemplate) {
                    $hosts[$hostFromHosttemplate['id']] = $hostFromHosttemplate;
                }
            }

            $tmpResult['hosts'] = $hosts;

            return $tmpResult;
        } catch (RecordNotFoundException $e) {
            return [];
        }
    }

    /**
     * @param $id
     * @param array $MY_RIGHTS
     * @return array|Hostgroup
     */
    public function getHostsByHostgroupForMaps($id, $MY_RIGHTS = []) {
        $where = [
            'Hostgroups.id' => $id
        ];
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        $hostgroup = $this->find()
            ->select([
                'Hostgroups.id',
                'Hostgroups.description',
                'Containers.name'
            ])
            ->contain([
                'Containers',
                'Hosts'         => function (Query $q) use ($MY_RIGHTS) {
                    $q->enableAutoFields(false)
                        ->select([
                            'Hosts.id',
                            'Hosts.uuid',
                            'Hosts.name',
                            'Hosts.description'
                        ])
                        ->contain(['HostsToContainersSharing']);

                    if (!empty($MY_RIGHTS)) {
                        $q->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                            'HostsToContainersSharing.host_id = Hosts.id'
                        ]);
                        $q->where([
                            'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                        ]);
                    }
                    return $q;
                },
                'Hosttemplates' => function (Query $q) use ($MY_RIGHTS) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'id'
                        ])
                        ->contain([
                            'Hosts' => function (Query $query) use ($MY_RIGHTS) {
                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid',
                                        'Hosts.name',
                                        'Hosts.hosttemplate_id'
                                    ])
                                    ->contain(['HostsToContainersSharing']);

                                if (!empty($MY_RIGHTS)) {
                                    $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                                        'HostsToContainersSharing.host_id = Hosts.id'
                                    ]);
                                    $query->where([
                                        'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
                                    ]);
                                }

                                $query
                                    ->leftJoinWith('Hostgroups')
                                    ->whereNull('Hostgroups.id');
                                return $query;
                            }
                        ]);
                }
            ])
            ->where($where)
            ->disableHydration()
            ->firstOrFail();

        return $hostgroup;
    }

    /**
     * @param $id
     * @param $MY_RIGHTS
     * @return array
     */
    public function getHostsIdsByHostgroupForMaps($id, $MY_RIGHTS = []) {
        $where = [
            'Hostgroups.id' => $id
        ];
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        $hostgroup = $this->find()
            ->select([
                'Hostgroups.id'
            ])
            ->contain([
                'Containers',
                'Hosts'         => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Hosts.id'

                        ])
                        ->contain(['HostsToContainersSharing']);
                },
                'Hosttemplates' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'id'
                        ])
                        ->contain([
                            'Hosts' => function (Query $query) {
                                $query
                                    ->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.hosttemplate_id'
                                    ])
                                    ->contain(['HostsToContainersSharing']);
                                $query
                                    ->leftJoinWith('Hostgroups')
                                    ->whereNull('Hostgroups.id');
                                return $query;
                            }
                        ]);
                }
            ])
            ->where($where)
            ->disableHydration()
            ->first();

        if ($hostgroup === null) {
            return [];
        }

        return array_unique(array_merge(
            Hash::extract($hostgroup, 'hosts.{n}.id'),
            Hash::extract($hostgroup, 'hosttemplates.{n}.hosts.{n}.id')
        ));
    }


    /**
     * @param int $id
     * @return array
     */
    public function getHostIdsByHostgroupId($id) {
        $hostgroup = $this->find()
            ->contain([
                // Get all hosts that are in this host group through the host template AND
                // which does NOT have any own host groups
                'Hosttemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'id',
                        ])
                        ->contain([
                            'Hosts' => function (Query $query) {
                                $query->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid',
                                        'Hosts.hosttemplate_id',
                                        'Hostgroups.id'
                                    ])
                                    ->leftJoinWith('Hostgroups')
                                    ->whereNull('Hostgroups.id');
                                return $query;
                            }
                        ]);
                    return $query;
                },

                // Get all hosts from this host group
                'Hosts'         => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.uuid',
                        ]);
                    return $query;
                }
            ])
            ->where([
                'Hostgroups.id' => $id
            ])
            ->disableHydration()
            ->first();


        $hostIds = array_unique(array_merge(
            Hash::extract($hostgroup, 'hosts.{n}.id'),
            Hash::extract($hostgroup, 'hosttemplates.{n}.hosts.{n}.id')
        ));

        return $hostIds;
    }

    /**
     * @param string $hostgroupRegex
     * @return array
     */
    public function getHostIdsByHostgroupNameRegex($hostgroupRegex, $containerIds) {
        $hostGroupIds = $this->getHostgroupIdsByNameRegex($hostgroupRegex, $containerIds);
        $allHostIdsArray = [];
        foreach ($hostGroupIds as $hostGroupId) {
            $hostIds = $this->getHostIdsByHostgroupId($hostGroupId);
            foreach ($hostIds as $hostId) {
                $allHostIdsArray[$hostId] = $hostId;
            }
        }
        return $allHostIdsArray;

    }

    /**
     * @param string $hostgroupRegex
     * @param array|mixed $containerIds
     * @param @param string $type (all or count, list is NOT supported!)
     * @return array|int
     */
    public function getHostgroupIdsByNameRegex(string $hostgroupRegex, $containerIds, $type = 'all') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $query = $this->find()
            ->select([
                'Hostgroups.id',
            ])
            ->contain(['Containers'])
            ->where(['Containers.parent_id IN' => $containerIds])
            ->disableHydration();
        $where = [];

        if ($this->isValidRegularExpression($hostgroupRegex)) {
            $where[] = new Comparison(
                'Containers.name',
                $hostgroupRegex,
                'string',
                'RLIKE'
            );
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }
        if ($type === 'count') {
            return $query->count();
        }

        $result = $query->all();
        return $this->emptyArrayIfNull(Hash::extract($result->toArray(), '{n}.id'));
    }

    /**
     * @param $id
     * @param $satelliteId
     * @return array
     */
    public function getHostsByHostgroupIdAndSatelliteId($id, $satelliteId) {
        $hostgroup = $this->find()
            ->contain([
                // Get all hosts that are in this host group through the host template AND
                // which does NOT have any own host groups
                'Hosttemplates' => function (Query $query) use ($satelliteId) {
                    $query->disableAutoFields()
                        ->select([
                            'id',
                        ])
                        ->contain([
                            'Hosts' => function (Query $query) use ($satelliteId) {
                                $query->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid',
                                        'Hosts.hosttemplate_id',
                                        'Hosts.satellite_id',
                                        'Hostgroups.id'
                                    ])
                                    ->leftJoinWith('Hostgroups')
                                    ->whereNull('Hostgroups.id')
                                    ->where(['Hosts.satellite_id' => $satelliteId]);
                                return $query;
                            }
                        ]);
                    return $query;
                },

                // Get all hosts from this host group
                'Hosts'         => function (Query $query) use ($satelliteId) {
                    $query->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.uuid',
                            'Hosts.satellite_id'
                        ])
                        ->where(['Hosts.satellite_id' => $satelliteId]);
                    return $query;
                }
            ])
            ->where([
                'Hostgroups.id' => $id
            ])
            ->disableHydration()
            ->first();

        $hosts = [];

        if (!empty($hostgroup['hosts'])) {
            foreach ($hostgroup['hosts'] as $host) {
                $hosts[$host['id']] = $host;
            }
        }

        if (!empty($hostgroup['hosttemplates'])) {
            foreach ($hostgroup['hosttemplates'] as $hosttemplate) {
                foreach ($hosttemplate['hosts'] as $host) {
                    $hosts[$host['id']] = $host;
                }
            }
        }

        return $hosts;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Hostgroups.id' => $id]);
    }

    /**
     * @param $name
     * @param array $MY_RIGHTS
     * @return array|\Cake\Datasource\EntityInterface
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getHostgroupByName($name, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->where([
                'Containers.name' => $name
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $result = $query->firstOrFail();

        return $result;
    }

    /**
     * @param int $hostId
     * @param int|array $hostgroupIds
     * @return bool
     */
    public function isHostInHostgroup($hostId, $hostgroupIds) {
        if (!is_array($hostgroupIds)) {
            $hostgroupIds = [$hostgroupIds];
        }
        $hostgroupIds = array_unique($hostgroupIds);

        /** @var HostsToHostgroupsTable $HostsToHostgroupsTable */
        $HostsToHostgroupsTable = TableRegistry::getTableLocator()->get('HostsToHostgroups');

        $count = $HostsToHostgroupsTable->find()
            ->where([
                'host_id'         => $hostId,
                'hostgroup_id IN' => $hostgroupIds
            ])
            ->count();

        if ($count > 0) {
            return true;
        }

        // Through hosttemplate maybe?
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $host = $HostsTable->get($hostId, contain: [
            'Hostgroups'
        ]);

        if (empty($host->get('hostgroups'))) {
            /** @var HosttemplatesToHostgroupsTable $HosttemplatesToHostgroupsTable */
            $HosttemplatesToHostgroupsTable = TableRegistry::getTableLocator()->get('HosttemplatesToHostgroups');
            $count = $HosttemplatesToHostgroupsTable->find()
                ->where([
                    'hosttemplate_id' => $host->get('hosttemplate_id'),
                    'hostgroup_id IN' => $hostgroupIds
                ])
                ->count();

            return $count > 0;
        }

        return false;
    }

    /**
     * @param $containerId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostgroupByContainerId($containerId, $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Hostgroups.id'
            ])
            ->contain([
                'Hosts'         => function (Query $query) {
                    return $query
                        ->disableAutoFields()
                        ->select(['id']);
                },
                'Hosttemplates' => function (Query $query) {
                    return $query
                        ->disableAutoFields()
                        ->select(['id']);
                }
            ])
            ->where([
                'Hostgroups.container_id' => $containerId
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $q->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $q;
            });
        }
        $result = $query->first();
        if (empty($result)) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param string $index
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostgroupsByContainerIdExact($containerId, $type = 'all', $index = 'container_id', $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Hostgroups.id',
                'Containers.id',
                'Containers.name'
            ])
            ->contain([
                'Containers'
            ])
            ->where([
                'Containers.parent_id'        => $containerId,
                'Containers.containertype_id' => CT_HOSTGROUP
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $query->orderBy([
            'Containers.name' => 'asc'
        ]);

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        if ($type === 'all') {
            return $result;
        }

        $list = [];
        foreach ($result as $row) {
            if ($index === 'id') {
                $list[$row['id']] = $row['container']['name'];
            } else {
                $list[$row['container']['id']] = $row['container']['name'];
            }
        }

        return $list;
    }

    /**
     * @param $ids
     * @return array
     */
    public function getHostgroupNamesByIds($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find()
            ->select([
                'Containers.name'
            ])
            ->contain(['Containers'])
            ->where([
                'Hostgroups.id IN'            => $ids,
                'Containers.containertype_id' => CT_HOSTGROUP
            ])
            ->disableHydration();

        $results = $query->toArray() ?? [];
        return Hash::extract($results, '{n}.container.name');
    }

    /**
     * @param $containerIds
     * @param $hostIds
     * @param $type
     * @param $index
     * @return array
     */
    public function getHostgroupsByContainerIdAndHostIds($containerIds, $hostIds, $type = 'all', $index = 'container_id') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        if (!is_array($hostIds)) {
            $hostIds = [$hostIds];
        }
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $tenantContainerIds = [];

        foreach ($containerIds as $container_id) {
            if ($container_id != ROOT_CONTAINER) {
                // Get contaier id of the tenant container
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load contactgroups and contacts
                $path = $ContainersTable->getPathByIdAndCacheResult($container_id, 'HostgroupHostgroupsByContainerId');

                // Tenant host groups are available for all users of a tenant (oITC V2 legacy)
                $tenantContainerIds[] = $path[1]['id'];
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);
        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));


        switch ($type) {
            case 'all':
                $query = $this->find()
                    ->contain([
                        'Containers'
                    ])
                    ->innerJoinWith('Hosts')
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_HOSTGROUP,
                        'Hosts.id IN '                => $hostIds
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();
                return $this->emptyArrayIfNull($query->toArray());
            default:
                $query = $this->find()
                    ->contain([
                        'Containers'
                    ])
                    ->innerJoinWith('Hosts')
                    ->where([
                        'Containers.parent_id IN'     => $containerIds,
                        'Containers.containertype_id' => CT_HOSTGROUP,
                        'Hosts.id IN '                => $hostIds
                    ])
                    ->orderBy([
                        'Containers.name' => 'ASC'
                    ])
                    ->disableHydration()
                    ->all();
                $query = $query->toArray();
                if (empty($query)) {
                    $query = [];
                }
                $return = [];
                foreach ($query as $hostgroup) {
                    if ($index === 'id') {
                        $return[$hostgroup['id']] = $hostgroup['container']['name'];
                    } else {
                        $return[$hostgroup['container_id']] = $hostgroup['container']['name'];
                    }
                }

                return $return;
        }
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostgroupsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['Hostgroups.id IN' => $ids])
            ->contain([
                'Containers'
            ])
            ->orderBy(['Hostgroups.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    public function getSourceHostgroupForCopy($id, array $MY_RIGHTS) {
        $query = $this->find()
            ->where(['Hostgroups.id' => $id])
            ->contain([
                'Hosts',
                'Hosttemplates',
                'Containers'
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $result = $query->firstOrFail();
        $hostgroup = $result;
        $hostgroup['hosts'] = [
            '_ids' => Hash::extract($result, 'hosts.{n}.id')
        ];
        $hostgroup['hosttemplates'] = [
            '_ids' => Hash::extract($result, 'hosttemplates.{n}.id')
        ];
        return $hostgroup;
    }

    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedHostgroupsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }

    /**
     * @param int $hostId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getHostGroupsByHostId(int $hostId, array $MY_RIGHTS): array {
        $query = $this->find()
            ->select([
                'Containers.name',
                'Hostgroups.id'
            ])
            ->innerJoin(
                ['HostsToHostgroupsTable' => 'hosts_to_hostgroups'],
                [
                    'HostsToHostgroupsTable.hostgroup_id = Hostgroups.id',
                    'HostsToHostgroupsTable.host_id' => $hostId
                ]
            )
            ->innerJoin(
                ['Containers' => 'containers'],
                [
                    'Hostgroups.container_id = Containers.id'
                ]
            )
            ->where([
                'host_id' => $hostId
            ])
            ->disableHydration();
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }
        $return = [];
        foreach ($query->toArray() as $result) {
            $return[] = [
                'name' => $result['Containers']['name'],
                'id'   => $result['id']
            ];
        }
        return $return;
    }

    /**
     * This method provides a unified way to create new hostgroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Hostgroup $entity The entity that will be saved by the Table
     * @param array $hostgroup The hostgroup as array ( [ Hostgroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @param ObjectsCache|null $Cache
     * @return Hostgroup
     */
    public function createHostgroup(Hostgroup $entity, array $hostgroup, int $userId, ?ObjectsCache $Cache = null): Hostgroup {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $extDataForChangelog = $this->resolveDataForChangelog($hostgroup, $Cache);

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'add',
            'hostgroups',
            $entity->get('id'),
            OBJECT_HOSTGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($hostgroup, $extDataForChangelog)
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }
        return $entity;
    }

    /**
     * This method provides a unified way to update an existing hostgroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Hostgroup $entity The entity that will be updated by the Table
     * @param array $newHostgroup The new hostgroup as array ( [ Hostgroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param array $oldHostgroup The old hostgroup as array ( [ Hostgroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @param ObjectsCache|null $Cache
     * @return Hostgroup
     */
    public function updateHostgroup(Hostgroup $entity, array $newHostgroup, array $oldHostgroup, int $userId, ?ObjectsCache $Cache = null): Hostgroup {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'edit',
            'hostgroups',
            $entity->get('id'),
            OBJECT_HOSTGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($this->resolveDataForChangelog($newHostgroup, $Cache), $newHostgroup),
            array_merge($this->resolveDataForChangelog($oldHostgroup, $Cache), $oldHostgroup)
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        return $entity;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHostUudsAndServiceUuidsByHostgroupId($id) {
        $hostAndServiceUuids = [];
        $hostgroup = $this->find()
            ->contain([
                // Get all hosts that are in this host group through the host template AND
                // which does NOT have any own host groups
                'Hosttemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'id',
                        ])
                        ->contain([
                            'Hosts' => function (Query $query) {
                                $query->disableAutoFields()
                                    ->select([
                                        'Hosts.id',
                                        'Hosts.uuid',
                                        'Hosts.hosttemplate_id',
                                        'Hostgroups.id'
                                    ])
                                    ->contain([
                                        'Services' => function (Query $query) {
                                            return $query->select([
                                                'Services.id',
                                                'Services.host_id',
                                                'Services.uuid'
                                            ])->where([
                                                'Services.disabled' => 0
                                            ]);
                                        }
                                    ])
                                    ->leftJoinWith('Hostgroups')
                                    ->where(['Hosts.disabled' => 0])
                                    ->whereNull('Hostgroups.id');
                                return $query;
                            }
                        ]);
                    return $query;
                },

                // Get all hosts from this host group
                'Hosts'         => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Hosts.id',
                            'Hosts.uuid',
                        ])->contain([
                            'Services' => function (Query $query) {
                                return $query->select([
                                    'Services.id',
                                    'Services.host_id',
                                    'Services.uuid'
                                ])->where([
                                    'Services.disabled' => 0
                                ]);
                            }
                        ])->where(['Hosts.disabled' => 0]);
                    return $query;
                }
            ])
            ->where([
                'Hostgroups.id' => $id
            ])
            ->disableHydration()
            ->first();
        foreach ($hostgroup['hosts'] as $host) {
            $hostAndServiceUuids['host_uuids'][$host['uuid']] = $host['id'];
            foreach ($host['services'] as $service) {
                $hostAndServiceUuids['service_uuids'][$service['uuid']] = $service['id'];
            }
        }

        foreach ($hostgroup['hosttemplates'] as $hosttemplate) {
            foreach ($hosttemplate['hosts'] as $host) {
                $hostAndServiceUuids['host_uuids'][$host['uuid']] = $host['id'];
                foreach ($host['services'] as $service) {
                    $hostAndServiceUuids['service_uuids'][$service['uuid']] = $service['id'];
                }
            }
        }

        return $hostAndServiceUuids;
    }


    /**
     * @param $regEx
     * @return bool
     */
    private function isValidRegularExpression($regEx) {
        return @preg_match('`' . $regEx . '`', '') !== false;
    }
}
