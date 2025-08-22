<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\Core\Plugin;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use EventcorrelationModule\Model\Table\EventcorrelationsTable;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\TenantFilter;

/**
 * Tenants Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\Tenant get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Tenant newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Tenant[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Tenant|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Tenant|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Tenant patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Tenant[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Tenant findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TenantsTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('tenants');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
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
            ->integer('zipcode')
            ->allowEmptyString('zipcode');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        return $validator;
    }

    /**
     * @param TenantFilter $TenantFilter
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getTenantsIndex(TenantFilter $TenantFilter, $PaginateOMat = null, array $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain(['Containers'])
            ->disableHydration();
        $query->where($TenantFilter->indexFilter());

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Tenants.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->orderBy($TenantFilter->getOrderForPaginator('Containers.name', 'asc'));

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
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getTenantById($id) {
        $query = $this->find()
            ->where([
                'Tenants.id' => $id
            ])
            ->contain([
                'Containers'
            ])
            ->disableHydration()
            ->firstOrFail();

        return $query;
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getTenantByContainerId($id) {
        $query = $this->find()
            ->where([
                'Tenants.container_id' => $id
            ])
            ->contain([
                'Containers'
            ])
            ->disableHydration()
            ->firstOrFail();

        return $query;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Tenants.id' => $id]);
    }

    /**
     * @param $containerId
     * @return bool
     */
    public function allowDelete($containerId) {
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServiceTable */
        $ServiceTable = TableRegistry::getTableLocator()->get('Services');

        $children = $ContainersTable->getChildren($containerId);

        $newContainerIds = Hash::extract($children, '{n}.id');
        //append the containerID itself
        $newContainerIds[] = $containerId;
        //get the hosts of these containers
        $hostIds = Hash::extract($HostsTable->getHostsByContainerIdForDelete($newContainerIds), '{n}.id');
        $serviceIds = Hash::extract($ServiceTable->getServicesByHostIdForDelete($hostIds), '{n}.id');

        //check if the host is used somwhere
        if (Plugin::isLoaded('EventcorrelationModule') && !empty($hostIds)) {
            /** @var EventcorrelationsTable $EventcorrelationTable */
            $EventcorrelationTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.Eventcorrelations');

            if (!empty($hostIds) && !empty($serviceIds)) {
                $query = $EventcorrelationTable->find()
                    ->where([
                        'OR' => [
                            'Eventcorrelations.host_id IN'    => $hostIds,
                            'Eventcorrelations.service_id IN' => $serviceIds,
                        ]
                    ])
                    ->count();

                if (!empty($query) && $query > 0) {
                    return false;
                }
            }

            if (!empty($hostIds)) {
                $query = $EventcorrelationTable->find()
                    ->where([
                        'Eventcorrelations.host_id IN' => $hostIds,
                    ])
                    ->count();

                if (!empty($query) && $query > 0) {
                    return false;
                }
            }

            if (!empty($serviceIds)) {
                $query = $EventcorrelationTable->find()
                    ->where([
                        'Eventcorrelations.service_id IN' => $serviceIds,
                    ])
                    ->count();

                if (!empty($query) && $query > 0) {
                    return false;
                }
            }
        }

        if (!empty($hostIds) || !empty($serviceIds)) {
            return false;
        }

        return true;
    }

    /**
     * @param array|int $containerIds
     * @param string $type
     * @param string $index
     * @return array|null
     */
    public function tenantsByContainerId($containerIds, $type = 'all', $index = 'id') {
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        $containerIds = array_unique($containerIds);

        $query = $this->find()
            ->where([
                'container_id IN' => $containerIds
            ])
            ->contain([
                'Containers'
            ])
            ->disableHydration()
            ->all();

        switch ($type) {
            case 'list':
                $list = [];
                foreach ($this->emptyArrayIfNull($query->toArray()) as $tenant) {
                    $list[$tenant[$index]] = $tenant['container']['name'];
                }
                return $list;
                break;

            default:
                return $this->emptyArrayIfNull($query->toArray());
                break;
        }
    }

    /**
     * @param array|int $containerIds
     * @param string $type
     * @param string $index
     * @return array|null
     */
    public function getTenants($type = 'all', $index = 'id', $MY_RIGHTS = []) {
        $query = $this->find();
        if (!empty($MY_RIGHTS)) {
            $query->where([
                'container_id IN' => $MY_RIGHTS
            ]);
        }

        $query
            ->contain([
                'Containers'
            ])
            ->disableHydration()
            ->all();

        switch ($type) {
            case 'list':
                $list = [];
                foreach ($this->emptyArrayIfNull($query->toArray()) as $tenant) {
                    $list[$tenant[$index]] = $tenant['container']['name'];
                }
                return $list;
                break;

            default:
                return $this->emptyArrayIfNull($query->toArray());
                break;
        }
    }

    /**
     * @param array $MY_RIGHTS
     * @param int $userId
     * @return array|null
     */
    public function getTenantsForBrowsersIndex($MY_RIGHTS, $userId) {
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->getUserById($userId);

        $tenants = [];
        foreach ($user['containers'] as $_container) {
            $_container = $_container['_joinData'];
            $path = $ContainersTable->getPathByIdAndCacheResult($_container['container_id'], 'UserGetTenantIds');
            foreach ($path as $subContainer) {
                if ($subContainer['containertype_id'] == CT_TENANT) {
                    $tenants[$subContainer['id']] = $subContainer['name'];
                }
            }
        }

        /** @var TenantsTable $TenantsTable */
        $TenantsTable = TableRegistry::getTableLocator()->get('Tenants');

        return $TenantsTable->tenantsByContainerId(
            array_merge(
                $MY_RIGHTS, array_keys(
                    $tenants
                )
            )
            , 'all', 'container_id'
        );
    }

    /**
     * @param $containerId
     * @param $MY_RIGHTS
     * @return bool|mixed
     */
    public function getTenantIdByContainerId($containerId, $MY_RIGHTS = []) {
        $query = $this->find()
            ->where([
                'Tenants.container_id' => $containerId
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->where([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }
        $result = $query->first();
        if (empty($result)) {
            return false;
        }
        return $result->get('id');
    }
}
