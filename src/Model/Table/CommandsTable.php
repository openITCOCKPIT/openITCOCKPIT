<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\Command;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\CommandsFilter;

/**
 * Commands Model
 *
 * @property \App\Model\Table\CommandargumentsTable|\Cake\ORM\Association\HasMany $Commandarguments
 *
 * @method \App\Model\Entity\Command get($primaryKey, $options = [])
 * @method \App\Model\Entity\Command newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Command[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Command|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Command|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Command patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Command[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Command findOrCreate($search, callable $callback = null, $options = [])
 */
class CommandsTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * @var array
     */
    private $commandTypes = [];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('commands');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Commandarguments', [
            'foreignKey'   => 'command_id',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->commandTypes = [
            CHECK_COMMAND        => __('Service check command'),
            HOSTCHECK_COMMAND    => __('Host check command'),
            NOTIFICATION_COMMAND => __('Notification command'),
            EVENTHANDLER_COMMAND => __('Eventhandler command'),
        ];
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->allowEmptyString('name', null, false)
            ->add('name', 'unique', [
                'rule'     => 'validateUnique',
                'provider' => 'table',
                'message'  => __('This command name has already been taken.')
            ]);

        $validator
            ->scalar('command_line')
            ->allowEmptyString('command_line', __('This field cannot be left blank.'), false);

        $validator
            ->integer('command_type')
            ->requirePresence('command_type', 'create')
            ->allowEmptyString('command_type', null, false);

        $validator
            ->scalar('human_args')
            ->allowEmptyString('human_args');

        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
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
        $rules->add($rules->isUnique(['uuid']));

        return $rules;
    }

    /**
     * @param CommandsFilter $CommandsFilter
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getCommandsIndex(CommandsFilter $CommandsFilter, $PaginateOMat = null) {
        $query = $this->find('all')->disableHydration();
        $query->where($CommandsFilter->indexFilter());
        $query->orderBy($CommandsFilter->getOrderForPaginator('Commands.name', 'asc'));

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

        foreach ($result as $index => $row) {
            $result[$index]['Command']['type'] = $this->commandTypes[$row['Command']['command_type']];
        }
        return $result;
    }


    /**
     * @param $id
     * @return array
     */
    public function getCommandById($id) {
        if ($id === null) {
            return [];
        }
        $command = $this->find('all')
            ->contain('Commandarguments')
            ->where(['Commands.id' => $id])
            ->first();

        return $this->formatFirstResultAsCake2($command->toArray());
    }

    /**
     * @param int|array $ids
     * @return array
     */
    public function getCommandByIds($ids = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $command = $this->find()
            ->contain('Commandarguments')
            ->where(['Commands.id IN' => $ids])
            ->disableHydration()
            ->all();
        return $this->formatResultAsCake2($command->toArray());
    }

    /**
     * @param int|array $ids
     * @return array
     */
    public function getCommandByIdAsList($ids = []) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $command = $this->find()
            ->select([
                'Commands.id',
                'Commands.name'
            ])
            ->where(['Commands.id IN' => $ids])
            ->all();

        return $this->formatListAsCake2($command->toArray());
    }

    /**
     * @param int|array
     * @return array
     */
    public function getCommandByTypeAsList($commandTypeId) {
        if (!is_array($commandTypeId)) {
            $commandTypeId = [$commandTypeId];
        }

        $command = $this->find()
            ->select([
                'Commands.id',
                'Commands.name'
            ])
            ->where(['Commands.command_type IN' => $commandTypeId])
            ->all();

        return $this->formatListAsCake2($command->toArray());
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getCommandsForCopy($ids = []) {
        $query = $this->find()
            ->select([
                'Commands.id',
                'Commands.name',
                'Commands.command_line',
                'Commands.description'
            ])
            ->where(['Commands.id IN' => $ids])
            ->orderBy(['Commands.id' => 'asc'])
            ->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $typeId
     * @return array
     */
    public function getCommandsByTypeId($typeId) {
        $query = $this->find()
            ->select([
                'Commands.id',
                'Commands.name',
                'Commands.command_line',
                'Commands.uuid',
                'Commands.command_type',
                'Commands.description'
            ])
            ->where(['Commands.command_type' => $typeId])
            ->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }


    /**
     * @param string $uuid
     * @return int|null
     */
    public function getCommandIdByCommandUuid($uuid) {
        $command = $this->find('all')
            ->select(['Commands.id'])
            ->where(['Commands.uuid' => $uuid])
            ->first();

        if (is_null($command)) {
            return null;
        }

        return $command->id;
    }

    /**
     * @param int $id
     * @return string|null
     */
    public function getCommandUuidByCommandId($id) {
        $command = $this->find('all')
            ->select(['Commands.uuid'])
            ->where(['Commands.id' => $id])
            ->first();

        if (is_null($command)) {
            return null;
        }

        return $command->uuid;
    }

    /**
     * @param string $uuid
     * @param bool $contain
     * @return array
     */
    public function getCommandByUuid($uuid, $contain = false, $formatResultAsCake2 = true) {
        $command = $this->find('all')
            ->where(['Commands.uuid' => $uuid])
            ->disableHydration();

        if ($contain) {
            $command->contain('Commandarguments');
        }
        $command->first();

        if ($formatResultAsCake2) {
            return $this->formatFirstResultAsCake2($command->toArray());
        }
        return $command->toArray();
    }

    /**
     * @param $name
     * @param bool $contain
     * @param bool $formatResultAsCake2
     * @return array
     */
    public function getCommandByName($name, $contain = false, $formatResultAsCake2 = true) {
        $command = $this->find('all')
            ->where(['Commands.name' => $name])
            ->disableHydration();

        if ($contain) {
            $command->contain('Commandarguments');
        }
        $command->first();

        if ($formatResultAsCake2) {
            return $this->formatFirstResultAsCake2($command->toArray());
        }
        return $command->toArray();
    }

    /**
     * @param bool $contain
     * @return array
     */
    public function getAllCommands($contain = false) {
        $command = $this->find('all')
            ->disableHydration()
            ->all();

        if ($contain) {
            $command->contain('Commandarguments');
        }

        return $this->formatResultAsCake2($command->toArray(), $contain);
    }

    public function getAllCommandsAsList() {
        $result = $this->find('list',
        keyField: 'id',
        valueField: 'name')
            ->disableHydration()
            ->orderBy(['Commands.name' => 'asc'])
            ->all();


        return $this->emptyArrayIfNull($result);
    }

    /**
     * @return array
     */
    public function getAllCommandsUuidsAsList() {
        $query = $this->find('list',
        keyField: 'id',
        valueField: 'uuid')
            ->disableHydration();
        return $query->toArray();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id): bool {
        return $this->exists(['Commands.id' => $id]);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public function existsByUuid(string $uuid): bool {
        return $this->exists(['Commands.uuid' => $uuid]);
    }

    public function existsByName(string $name): bool {
        return $this->exists(['Commands.name' => $name]);
    }

    /**
     * @param $id
     * @return Command|EntityInterface
     */
    public function getCommandForEdit($id) {
        $where = [
            'Commands.id' => $id
        ];

        return $this->getCommandForEditByWhere($where);
    }

    /**
     * @param string $uuid
     * @return array|EntityInterface
     */
    public function getCommandForEditByUuid(string $uuid) {
        $where = [
            'Commands.uuid' => $uuid
        ];

        return $this->getCommandForEditByWhere($where);
    }

    /**
     * @param array $where
     * @return array|EntityInterface
     */
    private function getCommandForEditByWhere(array $where) {
        $query = $this->find()
            ->where($where)
            ->contain([
                'Commandarguments'
            ]);

        return $query->firstOrFail();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function allowDelete($id) {

        //Check if the command is used by contacts
        $tableNames = [
            'ContactsToHostcommands',
            'ContactsToServicecommands',
        ];

        foreach ($tableNames as $tableName) {
            $LinkingTable = TableRegistry::getTableLocator()->get($tableName);
            $count = $LinkingTable->find()
                ->where(['command_id' => $id])
                ->count();

            if ($count > 0) {
                return false;
            }
        }

        //Check if the command is used by host or service templates
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        if ($HosttemplatesTable->isCommandUsedByHosttemplate($id)) {
            return false;
        }

        /** @var $ServicetemplatesTable ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        if ($ServicetemplatesTable->isCommandUsedByServicetemplate($id)) {
            return false;
        }

        //Checking host and services
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        if ($HostsTable->isCommandUsedByHost($id)) {
            return false;
        }

        /** @var $ServicesTable ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        if ($ServicesTable->isCommandUsedByService($id)) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getCommandTypes() {
        return $this->commandTypes;
    }

    public function getSourceCommandForCopy($sourceCommandId) {
        $sourceCommand = $this->get($sourceCommandId, contain: [
            'Commandarguments'
        ])->toArray();

        //Remove all source ids so the new copied command will not use the original command arguments...
        $commandarguments = [];
        foreach ($sourceCommand['commandarguments'] as $commandargument) {
            unset($commandargument['id'], $commandargument['command_id']);
            $commandarguments[] = $commandargument;
        }
        $sourceCommand['commandarguments'] = $commandarguments;
        return $sourceCommand;
    }

    /**
     * @param $ids
     * @return array
     */
    public function getCommandsByIdsForExport($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $query = $this->find()
            ->contain([
                'Commandarguments'
            ])
            ->where([
                'Commands.id IN' => $ids
            ])
            ->disableHydration();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * This method provides a unified way to create new commands. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     * @param Command $entity The entity that will be saved by the Table
     * @param array $command The command as array ( [ Command => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Command
     */
    public function createCommand(Command $entity, array $command, int $userId): Command {
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
            'add',
            'Commands',
            $entity->get('id'),
            OBJECT_COMMAND,
            [ROOT_CONTAINER],
            $userId,
            $entity->get('name'),
            $command
        );
        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }


        return $entity;
    }

    /**
     * This method provides a unified way to update an existing commands. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     * @param Command $entity The entity that will be updated by the Table
     * @param array $newCommand The new command as array ( [ Command => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param array $oldCommand The old command as array ( [ Command => [ name => Foo, type => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return Command
     */
    public function updateCommand(Command $entity, array $newCommand, array $oldCommand, int $userId): Command {
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
            'Commands',
            $entity->get('id'),
            OBJECT_COMMAND,
            [ROOT_CONTAINER],
            $userId,
            $entity->get('name'),
            $newCommand,
            $oldCommand
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
    public function getCommandByUuidForImportDiff($uuid) {
        $query = $this->find('all')
            ->contain('Commandarguments')
            ->where(['Commands.uuid' => $uuid])
            ->disableHydration()
            ->firstOrFail();

        return $this->emptyArrayIfNull($query);
    }
}
