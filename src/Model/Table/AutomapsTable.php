<?php

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\AutomapsFilter;

/**
 * Automaps Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\Automap get($primaryKey, $options = [])
 * @method \App\Model\Entity\Automap newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Automap[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Automap|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Automap saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Automap patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Automap[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Automap findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AutomapsTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('automaps');
        $this->setDisplayField('name');
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        $validator
            ->requirePresence('container_id', 'create')
            ->greaterThan('container_id', 0, __('You need to select at least one container'))
            ->numeric('container_id')
            ->allowEmptyString('container_id', __('You need to select at least one container'), false);


        $validator
            ->scalar('host_regex')
            ->maxLength('host_regex', 255)
            ->notEmptyString('host_regex');

        $validator
            ->scalar('hostgroup_regex')
            ->maxLength('hostgroup_regex', 255)
            ->allowEmptyString('hostgroup_regex');

        $validator
            ->scalar('service_regex')
            ->maxLength('service_regex', 255)
            ->notEmptyString('service_regex');

        $validator
            ->boolean('show_ok')
            ->requirePresence('show_ok', 'create', __('You have to choose at least one option.'))
            ->notEmptyString('show_ok')
            ->add('show_ok', 'custom', [
                'rule'    => [$this, 'checkStatusOptions'],
                'message' => 'You have to choose at least one option.',
            ]);

        $validator
            ->boolean('show_warning')
            ->notEmptyString('show_warning');

        $validator
            ->boolean('show_critical')
            ->notEmptyString('show_critical');

        $validator
            ->boolean('show_unknown')
            ->notEmptyString('show_unknown');

        $validator
            ->boolean('show_acknowledged')
            ->notEmptyString('show_acknowledged');

        $validator
            ->boolean('show_downtime')
            ->notEmptyString('show_downtime');

        $validator
            ->boolean('show_label')
            ->notEmptyString('show_label');

        $validator
            ->boolean('group_by_host')
            ->notEmptyString('group_by_host');

        $validator
            ->boolean('use_paginator')
            ->notEmptyString('use_paginator');

        $validator
            ->scalar('font_size')
            ->maxLength('font_size', 255)
            ->inList('font_size', ['1', '2', '3', '4', '5', '6', '7'], __('Font size out of range (1-7)'))
            ->allowEmptyString('font_size');

        $validator
            ->boolean('recursive')
            ->notEmptyString('recursive');

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
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     */
    public function checkStatusOptions($value, $context) {
        $statusOptions = [
            'show_ok',
            'show_warning',
            'show_critical',
            'show_unknown'
        ];

        foreach ($statusOptions as $statusOption) {
            if (isset($context['data'][$statusOption]) && $context['data'][$statusOption] == 1) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Automaps.id' => $id]);
    }

    /**
     * @param AutomapsFilter $AutomapsFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return mixed
     */
    public function getAutomapsIndex(AutomapsFilter $AutomapsFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all');
        $query->contain(['Containers']);
        $query->where($AutomapsFilter->indexFilter());

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        $query->orderBy($AutomapsFilter->getOrderForPaginator('Automaps.name', 'asc'));

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
     * @param $selected
     * @param AutomapsFilter $AutomapsFilter
     * @param $MY_RIGHTS
     * @return array
     */
    public function getAutomapsForAngular($selected, AutomapsFilter $AutomapsFilter, $MY_RIGHTS = []) {
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $query = $this->find('list')
            ->limit(ITN_AJAX_LIMIT)
            ->select([
                'Automaps.id',
                'Automaps.name'
            ])->where(
                $AutomapsFilter->indexFilter()
            );

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Automaps.container_id IN' => $MY_RIGHTS
            ]);
        }

        $selected = array_filter($selected);
        if (!empty($selected)) {
            $query->where([
                'Automaps.id NOT IN' => $selected
            ]);
            if (!empty($MY_RIGHTS)) {
                $query->andWhere([
                    'Automaps.container_id IN' => $MY_RIGHTS
                ]);
            }
        }

        $query->orderBy(['Automaps.name' => 'ASC']);
        $automapsWithLimit = $query->toArray();
        $selectedAutomaps = [];
        if (!empty($selected)) {
            $query = $this->find('list')
                ->select([
                    'Automaps.id',
                    'Automaps.name'
                ])
                ->where([
                    'Automaps.id IN' => $selected
                ]);

            $query->orderBy(['Automaps.name' => 'ASC']);

            $selectedAutomaps = $query->toArray();
        }

        $automaps = $automapsWithLimit + $selectedAutomaps;
        asort($automaps, SORT_FLAG_CASE | SORT_NATURAL);
        return $automaps;
    }

    /**
     * @param array $ids
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getAutomapsForCopy($ids = [], array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['Automaps.id IN' => $ids])
            ->orderBy(['Automaps.id' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Automaps.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration()
            ->all();

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param $sourceAutomapId
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getSourceAutomapForCopy($sourceAutomapId, array $MY_RIGHTS = []) {
        $query = $this->find()
            ->where(['Automaps.id' => $sourceAutomapId]);
        if (!empty($MY_RIGHTS)) {
            $query->andWhere([
                'Automaps.container_id IN' => $MY_RIGHTS
            ]);
        }

        $result = $query->firstOrFail();
        return $this->emptyArrayIfNull($result->toArray());
    }
}
