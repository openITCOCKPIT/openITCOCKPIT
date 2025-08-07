<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Contactgroup;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ContactgroupsFilter;

/**
 * Contactgroups Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsToMany $Contacts
 *
 * @method \App\Model\Entity\Contactgroup get($primaryKey, $options = [])
 * @method \App\Model\Entity\Contactgroup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Contactgroup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Contactgroup|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contactgroup|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contactgroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Contactgroup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Contactgroup findOrCreate($search, callable $callback = null, $options = [])
 */
class ContactgroupsTable extends Table {

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('contactgroups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ])->setDependent(true);

        $this->belongsToMany('Contacts', [
            'className'        => 'Contacts',
            'foreignKey'       => 'contactgroup_id',
            'targetForeignKey' => 'contact_id',
            'joinTable'        => 'contacts_to_contactgroups',
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
            ->requirePresence('contacts', true, __('You have to choose at least one contact.'))
            ->allowEmptyString('contacts', null, false)
            ->multipleOptions('contacts', [
                'min' => 1
            ], __('You have to choose at least one contact.'));

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
     * @param ContactgroupsFilter $ContactgroupsFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getContactgroupsIndex(ContactgroupsFilter $ContactgroupsFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where($ContactgroupsFilter->indexFilter());

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Containers.parent_id IN' => $MY_RIGHTS]);
            }
            return $q;
        });

        $query->disableHydration();
        $query->orderBy($ContactgroupsFilter->getOrderForPaginator('Containers.name', 'asc'));


        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }

        return $result;
    }

    /**
     * @param int $id
     * @param array $contain
     * @return array
     */
    public function getContactgroupById($id) {
        $query = $this->find()
            ->select([
                'Contactgroups.id',
                'Contactgroups.container_id',
                'Contactgroups.uuid',
                'Contactgroups.description',

                'Containers.name',
                'Containers.parent_id',
            ])
            ->where([
                'Contactgroups.id' => $id
            ])
            ->contain([
                'Containers'
            ])
            ->disableHydration()
            ->first();

        return $this->emptyArrayIfNull($query);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getContactgroupForEdit($id): array {
        $where = [
            'Contactgroups.id' => $id
        ];

        return $this->getContactgroupForEditByWhere($where);
    }

    /**
     * @param string $uuid
     * @return array
     */
    public function getContactgroupForEditByUuid(string $uuid): array {
        $where = [
            'Contactgroups.uuid' => $uuid
        ];

        return $this->getContactgroupForEditByWhere($where);
    }

    /**
     * @param array $where
     * @return array
     */
    private function getContactgroupForEditByWhere(array $where): array {
        $query = $this->find()
            ->where($where)
            ->contain([
                'Containers',
                'Contacts',
            ])
            ->disableHydration()
            ->firstOrFail();


        $contact = $query;
        $contact['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];

        return [
            'Contactgroup' => $contact
        ];
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param string $index
     * @return array
     * @deprecated Use self::getContactgroupsByContainerId()
     */
    public function contactgroupsByContainerId($containerIds = [], $type = 'all', $index = 'id') {
        return $this->getContactgroupsByContainerId($containerIds, $type, $index);
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param string $index
     * @return array
     */
    public function getContactgroupsByContainerId($containerIds = [], $type = 'all', $index = 'id') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        //Lookup for the tenant container of $container_id
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');


        $tenantContainerIds = [];

        foreach ($containerIds as $containerId) {
            if ($containerId != ROOT_CONTAINER) {

                // Get container id of the tenant container
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load contactgroups and contacts
                $path = $ContainersTable->getPathByIdAndCacheResult($containerId, 'ContactGroupContactsByContainerId');

                // Tenant contact groups are available for all users of a tenant (oITC V2 legacy)
                if (isset($path[1])) {
                    $tenantContainerIds[] = $path[1]['id'];
                }
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $tenantContainerIds = array_unique($tenantContainerIds);
        $containerIds = array_unique(array_merge($tenantContainerIds, $containerIds));


        if (empty($containerIds)) {
            return [];
        }

        $query = $this->find()
            ->contain(['Containers'])
            ->where([
                'Containers.parent_id IN'     => $containerIds,
                'Containers.containertype_id' => CT_CONTACTGROUP
            ])
            ->disableHydration()
            ->all();

        $records = $query->toArray();
        if (empty($records) || is_null($records)) {
            return [];
        }

        if ($type === 'all') {
            return $records;
        }

        $list = [];
        foreach ($records as $record) {
            $list[$record[$index]] = $record['container']['name'];
        }
        return $list;
    }

    /**
     * @param array $dataToParse
     * @return array
     */
    public function resolveDataForChangelog($dataToParse = []) {
        $extDataForChangelog = [
            'Contact' => []
        ];

        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');

        foreach ($ContactsTable->getContactsAsList($dataToParse['Contactgroup']['contacts']['_ids']) as $contactId => $contactName) {
            $extDataForChangelog['Contact'][] = [
                'id'   => $contactId,
                'name' => $contactName
            ];
        }

        return $extDataForChangelog;
    }

    /**
     * @return array
     */
    public function getAllContactsAsList() {
        $query = $this->find()
            ->select([
                'Containers.id',
                'Containers.name',
                'Contactgroups.id'
            ])
            ->contain(['Containers'])
            ->where([
                'Containers.containertype_id' => CT_CONTACTGROUP
            ])
            ->disableHydration()
            ->all();

        $records = $query->toArray();
        if (empty($records)) {
            return [];
        }
        $list = [];
        foreach ($records as $record) {
            $list[$record['id']] = $record['container']['name'];
        }
        return $list;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getContactgroupsAsList($ids = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find()
            ->select([
                'Containers.id',
                'Containers.name',
                'Contactgroups.id'
            ])
            ->contain(['Containers'])
            ->where([
                'Contactgroups.id IN'         => $ids,
                'Containers.containertype_id' => CT_CONTACTGROUP
            ])
            ->disableHydration()
            ->all();

        $records = $query->toArray();
        if (empty($records)) {
            return [];
        }
        $list = [];
        foreach ($records as $record) {
            $list[$record['id']] = $record['container']['name'];
        }
        return $list;
    }

    /**
     * @param null $uuid
     * @return array|\Cake\ORM\Query
     */
    public function getContactgroupsForExport($uuid = null) {
        $query = $this->find()
            ->contain([
                'Containers',
                'Contacts'
            ]);
        if (!empty($uuid)) {
            if (!is_array($uuid)) {
                $uuid = [$uuid];
            }
            $query->where([
                'Contactgroups.uuid IN' => $uuid
            ]);
        }
        $query->all();
        return $query;
    }

    /**
     * @param array $containerIds
     * @return array
     */
    public function getContactgroupsByContainerIdsForContainerDelete($containerIds) {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }

        $query = $this->find()
            ->select([
                'Containers.id',
                'Containers.name',
                'Contactgroups.id'
            ])
            ->contain(['Containers'])
            ->where([
                'Contactgroups.container_id IN' => $containerIds,
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getContactgroupsForCopy($ids = [], array $MY_RIGHTS = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->find()
            ->select([
                'Containers.id',
                'Containers.name',
                'Contactgroups.id',
                'Contactgroups.description',
                'Contactgroups.container_id',
            ])
            ->contain(['Containers'])
            ->where([
                'Contactgroups.id IN' => $ids,
            ]);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $q->where(['Containers.parent_id IN' => $MY_RIGHTS]);
                }
                return $q;
            });
        }

        $query
            ->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function allowDelete($id) {
        $tableNames = [
            'ContactgroupsToHosttemplates',
            'ContactgroupsToHosts',
            'ContactgroupsToServicetemplates',
            'ContactgroupsToServices',
            'ContactgroupsToHostescalations',
            'ContactgroupsToServiceescalations',
        ];

        foreach ($tableNames as $tableName) {
            $LinkingTable = TableRegistry::getTableLocator()->get($tableName);
            $count = $LinkingTable->find()
                ->where(['contactgroup_id' => $id])
                ->count();

            if ($count > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id): bool {
        return $this->exists(['Contactgroups.id' => $id]);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public function existsByUuid(string $uuid): bool {
        return $this->exists(['Contactgroups.uuid' => $uuid]);
    }

    /**
     * @param array $contactgroupsId
     * @param array $containerId
     * @return array
     */
    public function removeContactgroupsWhichAreNotInContainer($contactgroupsId, $containerId) {
        if (!is_array($contactgroupsId)) {
            $contactgroupsId = [$contactgroupsId];
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);

        $tenantContainerIds = [];
        foreach ($containerIds as $containerId) {
            if ($containerId != ROOT_CONTAINER) {
                $path = $ContainersTable->getPathByIdAndCacheResult($containerId, 'ContactgroupsRemoveContactgroupsWhichAreNotInContainer');
                // Get container id of the tenant container
                // Tenant contacts are available for all users of a tenant (oITC V2 legacy)
                if (isset($path[1])) {
                    $tenantContainerIds[] = $path[1]['id'];
                }
            } else {
                $tenantContainerIds[] = ROOT_CONTAINER;
            }
        }
        $containerIds = array_unique(array_merge($containerIds, $tenantContainerIds));

        $query = $this->find()
            ->select([
                'Contactgroups.id',
            ])
            ->contain([
                'Containers' => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'Containers.parent_id'
                        ]);
                }
            ])
            ->where([
                'Contactgroups.id IN ' => $contactgroupsId
            ])
            ->disableHydration()
            ->all();

        if ($query === null) {
            return [];
        }

        $contactgroupIds = [];
        foreach ($query->toArray() as $record) {
            if (in_array($record['container']['parent_id'], $containerIds, true)) {
                $contactgroupIds[] = $record['id'];
            }
        }
        return $contactgroupIds;
    }

    /**
     * @param int $contactId
     * @param array $MY_RIGHTS
     * @param bool $enableHydration
     * @return array
     */
    public function getContactgroupsByContactId($contactId, $MY_RIGHTS = [], $enableHydration = true) {

        /** @var ContactsToContactgroupsTable $ContactsToContactgroupsTable */
        $ContactsToContactgroupsTable = TableRegistry::getTableLocator()->get('ContactsToContactgroups');

        $query = $ContactsToContactgroupsTable->find()
            ->select([
                'contactgroup_id'
            ])
            ->where([
                'contact_id' => $contactId
            ])
            ->groupBy([
                'contactgroup_id'
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        if (empty($result)) {
            return [];
        }

        $contactgroupIds = Hash::extract($result, '{n}.contactgroup_id');

        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where([
            'Contactgroups.id IN' => $contactgroupIds
        ]);

        $query->innerJoinWith('Containers', function (Query $q) use ($MY_RIGHTS) {
            if (!empty($MY_RIGHTS)) {
                return $q->where(['Containers.parent_id IN' => $MY_RIGHTS]);
            }
            return $q;
        });

        $query->enableHydration($enableHydration);
        $query->orderBy([
            'Containers.name' => 'asc'
        ]);

        $result = $query->all();

        return $this->emptyArrayIfNull($result->toArray());
    }

    /**
     * @param $containerId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getContactgroupByContainerId($containerId, $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Contactgroups.id'
            ])
            ->contain([
                'Contacts' => function (Query $query) {
                    return $query
                        ->disableAutoFields()
                        ->select(['id']);
                }
            ])
            ->where([
                'Contactgroups.container_id' => $containerId
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
    public function getContactgroupsByContainerIdExact($containerId, $type = 'all', $index = 'container_id', $MY_RIGHTS = []) {
        $query = $this->find()
            ->select([
                'Contactgroups.id',
                'Containers.id',
                'Containers.name'
            ])
            ->contain([
                'Containers'
            ])
            ->where([
                'Containers.parent_id'        => $containerId,
                'Containers.containertype_id' => CT_CONTACTGROUP
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
     * @param int $containerId
     * @return array
     */
    public function getOrphanedContactgroupsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }


    /**
     * @param $ids
     * @return array
     */
    public function getContactgroupsByIdsForExport($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->where([
                'Contactgroups.id IN' => $ids
            ])
            ->contain([
                'Containers',
                'Contacts' => function (Query $q) {
                    return $q->select([
                        'Contacts.id',
                        'Contacts.uuid',
                        'Contacts.name'
                    ]);
                }
            ])
            ->innerJoinWith('Containers', function (Query $q) {
                return $q->where(['Containers.parent_id IN' => ROOT_CONTAINER]);
            })
            ->disableHydration();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * This method provides a unified way to create new contactgroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Contactgroup $entity The entity that will be saved by the Table
     * @param array $contactgroup The contactgroup as array ( [ Contactgroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Contactgroup
     */
    public function createContactgroup(Contactgroup $entity, array $contactgroup, int $userId): Contactgroup {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $extDataForChangelog = $this->resolveDataForChangelog($contactgroup);

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'add',
            'contactgroups',
            $entity->get('id'),
            OBJECT_CONTACTGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($contactgroup, $extDataForChangelog)
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }
        return $entity;
    }

    /**
     * This method provides a unified way to update an existing contactgroup. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     *  ▼ ▼ ▼ READ THIS ▼ ▼ ▼
     * VERY IMPORTANT! Call $ContainersTable->acquireLock(); BEFORE calling this method !
     *  ▲ ▲ ▲ READ THIS ▲ ▲ ▲
     *
     * @param Contactgroup $entity The entity that will be updated by the Table
     * @param array $newContactgroup The new contactgroup as array ( [ Contactgroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param array $oldContactgroup The old contactgroup as array ( [ Contactgroup => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Contactgroup
     */
    public function updateContactgroup(Contactgroup $entity, array $newContactgroup, array $oldContactgroup, int $userId): Contactgroup {
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
            'contactgroups',
            $entity->get('id'),
            OBJECT_CONTACTGROUP,
            $entity->get('container')->get('parent_id'),
            $userId,
            $entity->get('container')->get('name'),
            array_merge($this->resolveDataForChangelog($newContactgroup), $newContactgroup),
            array_merge($this->resolveDataForChangelog($oldContactgroup), $oldContactgroup)
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
    public function getContactgroupByUuidForImportDiff($uuid) {
        $query = $this->find('all')
            ->select([
                'Contactgroups.id',
                'name' => 'Containers.name'
            ])
            ->contain([
                'Containers',
                'Contacts' => function (Query $query) {
                    return $query->select([
                        'name' => 'Contacts.name',
                        'uuid' => 'Contacts.uuid'
                    ]);
                }
            ])
            ->where(['Contactgroups.uuid' => $uuid])
            ->disableHydration()
            ->firstOrFail();

        $contactgroup = $this->emptyArrayIfNull($query);
        if (!empty($contactgroup)) {
            $contactgroup['contacts'] = Hash::remove($contactgroup['contacts'], '{n}._joinData');
        }

        return $contactgroup;
    }
}
