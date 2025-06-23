<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Lib\Traits\CustomValidationTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * OrganizationalCharts Model
 *
 * @property \App\Model\Table\OrganizationalChartStructuresTable&\Cake\ORM\Association\HasMany $OrganizationalChartStructures
 *
 * @method \App\Model\Entity\OrganizationalChart newEmptyEntity()
 * @method \App\Model\Entity\OrganizationalChart newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart get($primaryKey, $options = [])
 * @method \App\Model\Entity\OrganizationalChart findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\OrganizationalChart patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationalChart|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChart saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\OrganizationalChart[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrganizationalChartsTable extends Table {
    use PaginationAndScrollIndexTrait;
    use CustomValidationTrait;


    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('organizational_charts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('OrganizationalChartStructures', [
            'foreignKey' => 'organizational_chart_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        return $validator;
    }

    public function getOrganizationalChartsIndex(GenericFilter $GenericFilter, PaginateOMat $PaginateOMat, array $MY_RIGHTS) {

        $query = $this->find('all')
            //->contain(['Containers'])
            ->disableHydration();
        if (!empty($GenericFilter->genericFilters())) {
            $query->where($GenericFilter->genericFilters());
        }

        if (!empty($MY_RIGHTS)) {
           /*
            $query->andWhere([
                'Containers.parent_id IN' => $MY_RIGHTS
            ]);
           */
        }

        $query->disableHydration();
        $query->order($GenericFilter->getOrderForPaginator('OrganizationalCharts.name', 'asc'));

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
}
