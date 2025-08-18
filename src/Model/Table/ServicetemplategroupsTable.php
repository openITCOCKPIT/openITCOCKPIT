<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Lib\Traits\PluginManagerTableTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Servicetemplategroup;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServicetemplategroupsConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServicetemplategroupsFilter;

/**
 * Servicetemplategroups Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ServicetemplatesTable|\Cake\ORM\Association\HasMany $servicetemplates
 *
 * @method \App\Model\Entity\Servicetemplategroup get($primaryKey, $options = [])
 * @method \App\Model\Entity\Servicetemplategroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Servicetemplategroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Servicetemplategroup|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicetemplategroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Servicetemplategroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Servicetemplategroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Servicetemplategroup findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServicetemplategroupsTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;
    use PluginManagerTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('servicetemplategroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Servicetemplates', [
            'className'        => 'Servicetemplates',
            'foreignKey'       => 'servicetemplategroup_id',
            'targetForeignKey' => 'servicetemplate_id',
            'joinTable'        => 'servicetemplates_to_servicetemplategroups',
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
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description', null, true);

        $validator
            ->add('servicetemplates', 'custom', [
                'rule'    => [$this, 'atLeastOne'],
                'message' => __('You must select at least one service template.')
            ]);

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
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for contacts and or contact groups
     */
    public function atLeastOne($value, $context) {
        return !empty($context['data']['servicetemplates']['_ids']);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Servicetemplategroups.id' => $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsByUuid($uuid) {
        return $this->exists(['Servicetemplategroups.uuid' => $uuid]);
    }


    /**
     * @param ServicetemplategroupsFilter $ServicetemplategroupsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicetemplategroupsIndex(ServicetemplategroupsFilter $ServicetemplategroupsFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Containers'
            ])
            ->disableHydration();
        $where = $ServicetemplategroupsFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['Containers.parent_id IN'] = $MY_RIGHTS;
        }

        $query->where($where);
        $query->orderBy($ServicetemplategroupsFilter->getOrderForPaginator('Containers.name', 'asc'));


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
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getServicetemplategroupForView($id) {
        $query = $this->find()
            ->contain([
                'Containers',
                'Servicetemplates' => function (Query $query) {
                    return $query->enableAutoFields(false)
                        ->select([
                            'Servicetemplates.id',
                        ]);
                }
            ])
            ->where([
                'Servicetemplategroups.id' => $id
            ])
            ->firstOrFail();

        return $query;
    }

    /**
     * @param array $dataToParse
     * @return array
     */
    public function resolveDataForChangelog($dataToParse = []) {
        $extDataForChangelog = [
            'Contact' => []
        ];

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');

        foreach ($ServicetemplatesTable->getServicetemplatesAsList($dataToParse['Servicetemplategroup']['servicetemplates']['_ids']) as $servicetemplateId => $servicetemplateName) {
            $extDataForChangelog['Servicetemplate'][] = [
                'id'            => $servicetemplateId,
                'template_name' => $servicetemplateName
            ];
        }

        return $extDataForChangelog;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getServicetemplategroupForEdit($id): array {
        $where = [
            'Servicetemplategroups.id' => $id
        ];

        return $this->getServicetemplategroupForEditByWhere($where);
    }

    /**
     * @param string $uuid
     * @return array
     */
    public function getServicetemplategroupForEditByUuid(string $uuid): array {
        $where = [
            'Servicetemplategroups.uuid' => $uuid
        ];

        return $this->getServicetemplategroupForEditByWhere($where);
    }

    /**
     * @param array $where
     * @return array
     */
    private function getServicetemplategroupForEditByWhere(array $where): array {
        $query = $this->find()
            ->where($where)
            ->contain([
                'Containers',
                'Servicetemplates' => function (Query $query) {
                    $query->enableAutoFields(false)
                        ->select([
                            'Servicetemplates.id'
                        ]);
                    return $query;
                }
            ])
            ->disableHydration()
            ->firstOrFail();


        $servicetemplategroup = $query;
        $servicetemplategroup['servicetemplates'] = [
            '_ids' => Hash::extract($query, 'servicetemplates.{n}.id')
        ];

        return [
            'Servicetemplategroup' => $servicetemplategroup
        ];
    }

    /**
     * @param ServicetemplategroupsConditions $ServicetemplategroupsConditions
     * @param array|int $selected
     * @return array|null
     */
    public function getServicetemplategroupsForAngular(ServicetemplategroupsConditions $ServicetemplategroupsConditions, $selected = []) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $selected = array_filter($selected);


        $where = $ServicetemplategroupsConditions->getConditionsForFind();

        if (!empty($selected)) {
            $where['NOT'] = [
                'Servicetemplategroups.id IN' => $selected
            ];
        }

        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->select([
                'Containers.name',
                'Servicetemplategroups.id'
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

        $groupsWithLimit = [];
        $result = $this->emptyArrayIfNull($query->toArray());
        foreach ($result as $row) {
            $groupsWithLimit[$row['id']] = $row['container']['name'];
        }

        $selectedGroups = [];
        if (!empty($selected)) {
            $query = $this->find()
                ->contain([
                    'Containers'
                ])
                ->select([
                    'Containers.name',
                    'Servicetemplategroups.id'
                ])
                ->where([
                    'Servicetemplategroups.id IN' => $selected
                ])
                ->orderBy([
                    'Containers.name' => 'asc'
                ])
                ->limit(ITN_AJAX_LIMIT)
                ->disableHydration()
                ->all();

            $selectedGroups = [];
            $result = $this->emptyArrayIfNull($query->toArray());
            foreach ($result as $row) {
                $selectedGroups[$row['id']] = $row['container']['name'];
            }
        }

        $groups = $groupsWithLimit + $selectedGroups;
        asort($groups, SORT_FLAG_CASE | SORT_NATURAL);

        return $groups;
    }


    /**
     * @param $servicetemplategroupId
     * @param array $containerIds
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getServicetemplatesforAllocation($servicetemplategroupId, $containerIds = []) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $query = $this->find()
            ->where([
                'Servicetemplategroups.id' => $servicetemplategroupId
            ])
            ->contain([
                'Servicetemplates' => function (Query $query) use ($containerIds) {
                    $query->enableAutoFields(false)
                        ->select([
                            'Servicetemplates.id',
                            'Servicetemplates.name',
                            'Servicetemplates.description'
                        ])
                        ->orderBy([
                            'Servicetemplates.name' => 'ASC'
                        ]);
                    if (!empty($containerIds)) {
                        $query->where([
                            'Servicetemplates.container_id IN' => $containerIds
                        ]);
                    }
                    return $query;
                }
            ])
            ->disableHydration()
            ->first();

        return $query;
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getServicetemplategroupNameById($id) {
        $result = $this->find()
            ->select([
                'Servicetemplategroups.id',
                'Containers.name'
            ])
            ->contain([
                'Containers'
            ])
            ->where([
                'Servicetemplategroups.id' => $id
            ])
            ->firstOrFail();

        $result = $result->toArray();
        return $result['container']['name'];
    }

    /**
     * @param $containerId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicetemplategroupByContainerId($containerId, $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Servicetemplategroups.id'
            ])
            ->contain([
                'Servicetemplates' => function (Query $query) {
                    return $query
                        ->disableAutoFields()
                        ->select(['id']);
                }
            ])
            ->where([
                'Servicetemplategroups.container_id' => $containerId
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
     * @param int $id
     * @return array
     */
    public function getServicetemplatesByServicetemplategroupId($id) {
        $servicetemplates = $this->find()
            ->contain([
                // Get all services that are in this service group through the service template AND
                // which does NOT have any own service groups
                'Servicetemplates' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'id'
                        ]);
                    return $query;
                }
            ])
            ->where([
                'Servicetemplategroups.id' => $id
            ])
            ->disableHydration()
            ->first();

        if (empty(!$servicetemplates['servicetemplates'])) {
            return [];
        }
        return Hash::extract($servicetemplates['servicetemplates'], '{n}.id');
    }

    /**
     * @param int $containerId
     * @param string $type
     * @param string $index
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicetemplategroupsByContainerIdExact($containerId, $type = 'all', $index = 'container_id', $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Servicetemplategroups.id',
                'Containers.id',
                'Containers.name'
            ])
            ->contain([
                'Containers'
            ])
            ->where([
                'Containers.parent_id'        => $containerId,
                'Containers.containertype_id' => CT_SERVICETEMPLATEGROUP
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

    public function getServicetemplategroupsByNames($names) {
        if (!is_array($names)) {
            $names = [$names];
        }

        $query = $this->find()
            ->contain([
                'Containers',
                'Servicetemplates'
            ])
            ->where([
                'Containers.name IN'          => $names,
                'Containers.containertype_id' => CT_SERVICETEMPLATEGROUP
            ])
            ->disableHydration();

        return $query->toArray();

    }

    /**
     * @param array $hostgroupIds
     * @param int $hostId
     * @param int $userId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function assignMatchingServicetemplategroupsByHostgroupsToHost($hostgroupIds, $hostId, $userId = 0, $MY_RIGHTS = []) {
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        if (empty($hostgroupIds)) {
            //Host has no hostgroups
            return [
                'newServiceIds'                       => [],
                'errors'                              => [],
                'servicetemplategroups_removed_count' => 0
            ];
        }

        $host = $HostsTable->getServicesByHostIdForAllocation($hostId);

        $hostgroupNames = $HostgroupsTable->getHostgroupNamesByIds($hostgroupIds);


        $servicetemplategroups_tmp = $this->getServicetemplategroupsByNames($hostgroupNames);
        $servicetemplategroups_removed = [];
        $servicetemplategroups = [];

        if (empty($MY_RIGHTS)) {
            //Permission check is disabled
            $servicetemplategroups = $servicetemplategroups_tmp;
        }

        //Check container permissions - do not this in SQL so we know what servicetemplategroups got removed so we can print error messages
        if (!empty($MY_RIGHTS)) {
            foreach ($servicetemplategroups_tmp as $servicetemplategroup) {
                if (in_array($servicetemplategroup['container']['parent_id'], $MY_RIGHTS, true)) {
                    $servicetemplategroups[] = $servicetemplategroup;
                } else {
                    //User has no permissions to this servicetemplategroup
                    $servicetemplategroups_removed[] = $servicetemplategroup;
                }
            }
        }

        if (!empty($servicetemplategroups_removed) && empty($servicetemplategroups)) {
            //Removed all service template groups due to insufficient permissions
            return [
                'newServiceIds'                       => [],
                'errors'                              => [],
                'servicetemplategroups_removed_count' => sizeof($servicetemplategroups_removed)
            ];
        }

        if (empty($servicetemplategroups)) {
            //No matching service template groups found
            return [
                'newServiceIds'                       => [],
                'errors'                              => [],
                'servicetemplategroups_removed_count' => 0
            ];
        }

        $existingServicetemplateIds = Hash::combine($host['services'], '{n}.servicetemplate_id', '{n}.servicetemplate_id');
        $servicetemplatesToCreate = [];

        foreach ($servicetemplategroups as $servicetemplategroup) {
            foreach ($servicetemplategroup['servicetemplates'] as $servicetemplate) {
                if (!isset($existingServicetemplateIds[$servicetemplate['id']])) {
                    $servicetemplatesToCreate[$servicetemplate['id']] = $servicetemplate['id'];
                }
            }
        }

        $result = $ServicesTable->createServiceByServicetemplateIds($servicetemplatesToCreate, $hostId, $userId);
        $result['servicetemplategroups_removed_count'] = sizeof($servicetemplategroups_removed);
        return $result;
    }


    /**
     * @param $hostId
     * @param int $userId
     * @param array|null $oldHostgroupsIds
     * @param array|null $currentHostgroupsIds
     * @return array
     */
    public function disableServicesIfMatchingHostgroupsHasBeenRemoved($hostId, $userId, $oldHostgroupsIds, $currentHostgroupsIds) {
        $result = [
            'disabledServiceIds'      => [],
            'errors'                  => [],
            'services_disabled_count' => 0
        ];

        if (empty($oldHostgroupsIds) && empty($currentHostgroupsIds)) {
            //Host has no old host groups and no new host groups
            return $result;
        }
        if (empty($oldHostgroupsIds)) {
            //Host has no old host groups - no check needed
            return $result;
        }

        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');

        $hostgroupNamesToRemove = [];
        $hostgroupNamesInUse = [];

        if (!empty($oldHostgroupsIds) && empty($currentHostgroupsIds)) {
            // all services from matching old matching by service template groups must be disabled
            $hostgroupNamesToRemove = $HostgroupsTable->getHostgroupNamesByIds($oldHostgroupsIds);
        }

        if (!empty($oldHostgroupsIds) && !empty($currentHostgroupsIds)) {
            // disable services from host if matching host group has been removed
            $hostGroupIdsHasBeenRemoved = array_diff($oldHostgroupsIds, $currentHostgroupsIds);
            if (!empty($hostGroupIdsHasBeenRemoved)) {
                $hostgroupNamesToRemove = $HostgroupsTable->getHostgroupNamesByIds($hostGroupIdsHasBeenRemoved);
            }
            if (!empty($currentHostgroupsIds)) {
                $hostGroupNamesInUse = $HostgroupsTable->getHostgroupNamesByIds($currentHostgroupsIds);
            }
        }

        if (!empty($hostgroupNamesToRemove)) {
            $servicetemplateGroups = $this->getServicetemplategroupsByNames($hostgroupNamesToRemove);
            $serviceTemplateIdsToDisable = array_unique(Hash::extract($servicetemplateGroups, '{n}.servicetemplates.{n}.id'));
            if (empty($serviceTemplateIdsToDisable)) {
                return $result;
            }

            if (!empty($hostGroupNamesInUse)) {
                $servicetemplateGroupsInUse = $this->getServicetemplategroupsByNames($hostGroupNamesInUse);
                $serviceTemplateIdsInUse = array_unique(Hash::extract($servicetemplateGroupsInUse, '{n}.servicetemplates.{n}.id'));
                $serviceTemplateIdsToDisable_tmp = [];
                foreach ($serviceTemplateIdsToDisable as $serviceTemplateIdToDisable) {
                    if (!in_array($serviceTemplateIdToDisable, $serviceTemplateIdsInUse, true)) {
                        $serviceTemplateIdsToDisable_tmp[] = $serviceTemplateIdToDisable;
                    }
                }
                if (empty($serviceTemplateIdsToDisable_tmp)) {
                    return $result;
                }
                $serviceTemplateIdsToDisable = $serviceTemplateIdsToDisable_tmp;
            }
            /** @var $ServicesTable ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');
            $result = $ServicesTable->disableServiceByServicetemplateIds($serviceTemplateIdsToDisable, $hostId, $userId);
            $result['services_disabled_count'] = sizeof($result['disabledServiceIds']);
        }

        return $result;
    }

    /**
     * @param array $containerIds
     * @return array
     */
    public function getServicetemplategroupsByContainerId(array $containerIds) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $query = $this->find()
            ->contain([
                'Containers'
            ])
            ->select([
                'Containers.name',
                'Servicetemplategroups.id'
            ])
            ->where([
                'Containers.parent_id IN ' => $containerIds
            ])
            ->orderBy([
                'Containers.name' => 'asc'
            ])
            ->disableHydration()
            ->all();

        $servicetemplategroups = [];
        $result = $this->emptyArrayIfNull($query->toArray());
        foreach ($result as $row) {
            $servicetemplategroups[$row['id']] = $row['container']['name'];
        }

        return $servicetemplategroups;
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getServicetemplategroupsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['Servicetemplategroups.id IN' => $ids])
            ->contain([
                'Containers'
            ])
            ->orderBy(['Servicetemplategroups.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    public function getSourceServicetemplategroupForCopy($id, array $MY_RIGHTS) {
        $query = $this->find()
            ->where(['Servicetemplategroups.id' => $id])
            ->contain([
                'Servicetemplates',
                'Containers'
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $result = $query->firstOrFail();
        $servicetemplategroup = $result;
        $servicetemplategroup['servicetemplates'] = [
            '_ids' => Hash::extract($result, 'servicetemplates.{n}.id')
        ];

        return $servicetemplategroup;
    }

    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedServicetemplategroupsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }

    /**
     * @param $ids
     * @return array
     */
    public function getServicetemplategroupsByIdsForExport($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->select([
                'Servicetemplategroups.id',
                'Servicetemplategroups.uuid',
                'Servicetemplategroups.container_id',
                'Servicetemplategroups.description',
            ])
            ->where([
                'Servicetemplategroups.id IN' => $ids
            ])
            ->contain([
                'Containers'       => function (Query $q) {
                    $q->select([
                        'Containers.id',
                        'Containers.parent_id',
                        'Containers.name'
                    ]);
                    return $q;
                },
                'Servicetemplates' => function (Query $q) {
                    $q->select([
                        'Servicetemplates.id',
                        'Servicetemplates.uuid',
                        'Servicetemplates.template_name'
                    ]);
                    return $q;
                }
            ])
            ->innerJoinWith('Containers', function (Query $q) {
                return $q->where(['Containers.parent_id IN' => ROOT_CONTAINER]);
            })
            ->disableHydration();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * This method provides a unified way to create new servicetemplategroups. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Servicetemplategroup $entity The entity that will be saved by the Table
     * @param array $servicetemplategroup The servicetemplategroup as array ( [ Servicetemplategroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Servicetemplategroup
     */
    public function createServicetemplategroup(Servicetemplategroup $entity, array $servicetemplategroup, int $userId): Servicetemplategroup {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $extDataForChangelog = $this->resolveDataForChangelog($servicetemplategroup);

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'add',
            'servicetemplategroups',
            $entity->get('id'),
            OBJECT_SERVICETEMPLATEGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($servicetemplategroup, $extDataForChangelog)
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }
        return $entity;
    }

    /**
     * This method provides a unified way to update an existing servicetemplategroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Servicetemplategroup $entity The entity that will be updated by the Table
     * @param array $newServicetemplategroup The new servicetemplategroup as array ( [ Servicetemplategroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param array $oldServicetemplategroup The old servicetemplategroup as array ( [ Servicetemplategroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Servicetemplategroup
     */
    public function updateServicetemplategroup(Servicetemplategroup $entity, array $newServicetemplategroup, array $oldServicetemplategroup, int $userId): Servicetemplategroup {
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
            'servicetemplategroups',
            $entity->get('id'),
            OBJECT_SERVICETEMPLATEGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($this->resolveDataForChangelog($newServicetemplategroup), $newServicetemplategroup),
            array_merge($this->resolveDataForChangelog($oldServicetemplategroup), $oldServicetemplategroup)
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        return $entity;
    }


    /**
     * @param $uuid
     * @return array
     */
    public function getServicetemplategroupByUuidForImportDiff($uuid) {
        $query = $this->find('all')
            ->select([
                'Servicetemplategroups.id',
                'name' => 'Containers.name'
            ])
            ->contain([
                'Containers',
                'Servicetemplates' => function (Query $query) {
                    return $query->select([
                        'name' => 'Servicetemplates.name',
                        'uuid' => 'Servicetemplates.uuid'
                    ]);
                }
            ])
            ->where(['Servicetemplategroups.uuid' => $uuid])
            ->disableHydration()
            ->firstOrFail();

        $servicetemplategroup = $this->emptyArrayIfNull($query);
        if (!empty($servicetemplategroup)) {
            $servicetemplategroup['servicetemplates'] = Hash::remove($servicetemplategroup['servicetemplates'], '{n}._joinData');
        }

        return $servicetemplategroup;
    }

}
