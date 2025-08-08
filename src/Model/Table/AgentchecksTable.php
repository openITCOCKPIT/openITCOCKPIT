<?php

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Lib\Traits\PluginManagerTableTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\AgentchecksFilter;

/**
 * Agentchecks Model
 *
 * @property \App\Model\Table\ServicetemplatesTable|\Cake\ORM\Association\BelongsTo $Servicetemplates
 *
 * @method \App\Model\Entity\Agentcheck get($primaryKey, $options = [])
 * @method \App\Model\Entity\Agentcheck newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Agentcheck[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Agentcheck|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Agentcheck saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Agentcheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Agentcheck[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Agentcheck findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AgentchecksTable extends Table {

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

        $this->setTable('agentchecks');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Servicetemplates', [
            'foreignKey' => 'servicetemplate_id'
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('plugin_name')
            ->maxLength('plugin_name', 255)
            ->requirePresence('plugin_name', 'create')
            ->notEmptyString('plugin_name');

        $validator
            ->scalar('servicetemplate_id')
            ->requirePresence('servicetemplate_id', 'create')
            ->notEmptyString('servicetemplate_id')
            ->integer('servicetemplate_id');

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
        $rules->add($rules->existsIn(['servicetemplate_id'], 'Servicetemplates'));
        $rules->add($rules->isUnique(['name']));

        return $rules;
    }

    /**
     * @param AgentchecksFilter $AgentchecksFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getAgentchecksIndex(AgentchecksFilter $AgentchecksFilter, PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all');
        $where = $AgentchecksFilter->indexFilter();
        if (!empty($MY_RIGHTS)) {
            $where['Servicetemplates.container_id IN'] = $MY_RIGHTS;
        }

        $query->contain([
            'Servicetemplates'
        ]);
        $query->where($where);
        $query->orderBy($AgentchecksFilter->getOrderForPaginator('Agentchecks.name', 'asc'));

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
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getAgentcheckById($id) {
        return $this->selectQuery()
            ->contain([
                'Servicetemplates'
            ])
            ->where([
                'Agentchecks.id' => $id
            ])
            ->first();
    }

    /**
     * @param $name
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getAgentcheckByName($name) {
        return $this->selectQuery()
            ->contain([
                'Servicetemplates' => function (Query $q) {
                    $q->contain([
                        'Servicetemplatecommandargumentvalues' => [
                            'Commandarguments'
                        ]
                    ]);
                    return $q;
                }
            ])
            ->where([
                'Agentchecks.name' => $name
            ])
            ->disableHydration()
            ->first();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Agentchecks.id' => $id]);
    }

    /**
     * @param $name
     * @param $servicetemplateId
     * @return bool
     */
    public function existsByNameAndServicetemplateId($name, $servicetemplateId) {
        return $this->exists(['Agentchecks.name' => $name, 'Agentchecks.servicetemplate_id' => $servicetemplateId]);
    }

    /**
     * @param array $containerIds
     * @param string $type
     * @param null $activeChecksEnabled | false => passive or true => active
     * @return array
     */
    public function getAgentchecksByContainerId($containerIds = [], $type = 'all', $activeChecksEnabled = null) {
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
                // $container_id is may be a location, devicegroup or whatever, so we need to container id of the tenant container to load service templates
                $path = $ContainersTable->getPathByIdAndCacheResult($containerId, 'ServicetemplatesByContainerId');

                // Tenant service templates are available for all users of a tenant (oITC V2 legacy)
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

        $where = [
            'Servicetemplates.container_id IN' => $containerIds,
        ];


        $query = $this->find('all');
        if (!empty($containerIds)) {
            $where['Servicetemplates.container_id IN'] = $containerIds;
        }

        if (!is_null($activeChecksEnabled)) {
            $where['Servicetemplates.active_checks_enabled'] = (int)$activeChecksEnabled;
        }

        $query = $this->find()
            ->contain(['Servicetemplates'])
            ->where($where)
            ->orderBy('Agentchecks.name')
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
            $list[$record['id']] = $record['name'];
        }
        return $list;
    }

    /**
     * @return array
     */
    public function getAgentchecksAsList() {
        return $this->find('list')
            ->toArray();
    }

}
