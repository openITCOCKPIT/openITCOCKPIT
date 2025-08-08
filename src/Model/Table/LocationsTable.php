<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\LocationFilter;

/**
 * Locations Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\Location get($primaryKey, $options = [])
 * @method \App\Model\Entity\Location newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Location[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Location|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Location|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Location patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Location[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Location findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LocationsTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;
    use CustomValidationTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) :void {
        parent::initialize($config);

        $this->setTable('locations');
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
    public function validationDefault(Validator $validator) :Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
        /*
                $validator
                    ->integer('parent_id')
                    ->greaterThan('parent_id', 0)
                    ->requirePresence('parent_id')
                    ->allowEmptyString('parent_id', null, false);
        */
        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        $validator
            ->scalar('latitude')
            ->longitude('latitude',
                'The provided value is invalid.')
            ->allowEmptyString('latitude')
            ->add('longitude', 'custom', [
                'rule'    => [$this, 'checkGeoCoordinate'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('It is required to specify valid values for LONGITUDE and LATITUDE')
            ]);

        $validator
            ->scalar('longitude')
            ->longitude('longitude',
                'The provided value is invalid. ')
            ->allowEmptyString('longitude')
            ->add('latitude', 'custom', [
                'rule'    => [$this, 'checkGeoCoordinate'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('It is required to specify valid values for LONGITUDE and LATITUDE')
            ]);


        return $validator;
    }

    /**
     * @param LocationFilter $LocationFilter
     * @param null|PaginateOMat $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getLocationsIndex(LocationFilter $LocationFilter, $PaginateOMat = null, array $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain(['Containers'])
            ->disableHydration();
        $query->where($LocationFilter->indexFilter());

        if(!empty($MY_RIGHTS)){
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
        }

        $query->orderBy($LocationFilter->getOrderForPaginator('Containers.name', 'asc'));

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
    public function buildRules(RulesChecker $rules) :RulesChecker {
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getLocationById($id) {
        $query = $this->find()
            ->where([
                'Locations.id' => $id
            ])
            ->contain([
                'Containers' => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'Containers.id',
                            'Containers.parent_id',
                            'Containers.name'
                        ]);
                }
            ])
            ->firstOrFail();
        return $query;
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getLocationByContainerId($containerId) {
        $query = $this->find()
            ->where([
                'Locations.container_id' => $containerId
            ])
            ->contain([
                'Containers' => function (Query $q) {
                    return $q->disableAutoFields()
                        ->select([
                            'Containers.id',
                            'Containers.parent_id',
                            'Containers.name'
                        ]);
                }
            ])
            ->firstOrFail();
        return $query;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Locations.id' => $id]);
    }

    /**
     * @param $containerId
     * @param array $MY_RIGHTS
     * @return bool|mixed
     */
    public function getLocationIdByContainerId($containerId, $MY_RIGHTS = []) {
        $query = $this->find()
            ->where([
                'Locations.container_id' => $containerId
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->where([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }
        $result = $query->first();
        if (empty($query)) {
            return false;
        }
        return $result->get('id');
    }

    /**
     * @param int $containerId
     * @return array
     */
    public function getOrphanedLocationsByContainerId(int $containerId) {
        $query = $this->find()
            ->where(['container_id' => $containerId]);
        $result = $query->all();

        return $result->toArray();
    }

}
