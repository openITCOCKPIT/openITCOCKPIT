<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ServiceFilter;

/**
 * DeletedServices Model
 *
 * @method \App\Model\Entity\DeletedService get($primaryKey, $options = [])
 * @method \App\Model\Entity\DeletedService newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\DeletedService[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DeletedService|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DeletedService|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DeletedService patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DeletedService[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\DeletedService findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DeletedServicesTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) :void {
        parent::initialize($config);

        $this->setTable('deleted_services');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) :Validator {
        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', null, false);

        $validator
            ->allowEmptyString('name', null, true);

        $validator
            ->allowEmptyString('description', null, true);

        $validator
            ->integer('deleted_perfdata')
            ->allowEmptyString('deleted_perfdata');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) :RulesChecker {
        return $rules;
    }

    /**
     * @param ServiceFilter $ServiceFilter
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getDeletedServicesIndex(ServiceFilter $ServiceFilter, $PaginateOMat = null) {
        $query = $this->find('all')
            ->where($ServiceFilter->deletedFilter())
            ->disableHydration()
            ->orderBy($ServiceFilter->getOrderForPaginator('DeletedDeleteds.created', 'desc'));


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
     * @return array
     */
    public function getDeletedServicesWherePerfdataWasNotDeletedYet() {
        $query = $this->find('all')
            ->where(['deleted_perfdata' => 0]);

        return $query->toArray();
    }
}
