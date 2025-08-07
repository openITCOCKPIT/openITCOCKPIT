<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\SystemfailuresFilter;

/**
 * Systemfailures Model
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Systemfailure get($primaryKey, $options = [])
 * @method \App\Model\Entity\Systemfailure newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Systemfailure[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Systemfailure|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Systemfailure saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Systemfailure patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Systemfailure[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Systemfailure findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SystemfailuresTable extends Table {

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) :void {
        parent::initialize($config);

        $this->setTable('systemfailures');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id'
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

        $validator
            ->dateTime('start_time')
            ->requirePresence('start_time', 'create')
            ->allowEmptyString('start_time', null, true)
            ->allowEmptyDateTime('start_time', __('Please enter a valid date'), false)
            ->add('start_time', 'custom', [
                'rule'    => [$this, 'startBeforeEnd'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('Start time must be before end time.')
            ]);


        $validator
            ->dateTime('end_time')
            ->requirePresence('end_time', 'create')
            ->allowEmptyDateTime('end_time', __('Please enter a valid date'), false)
            ->add('start_time', 'custom', [
                'rule'    => [$this, 'startBeforeEnd'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('Start time must be before end time.')
            ]);

        $validator
            ->scalar('comment')
            ->maxLength('comment', 255)
            ->requirePresence('comment', 'create')
            ->notEmptyString('comment');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * @param SystemfailuresFilter $SystemfailuresFilter
     * @param null|PaginateOMat $PaginateOMat
     * @return array
     */
    public function getSystemfailuresIndex(SystemfailuresFilter $SystemfailuresFilter, $PaginateOMat = null) {
        $query = $this->find('all')
            ->contain([
                'Users' => function (Query $q) {
                    $q->disableAutoFields();
                    $q->select([
                        'Users.id',
                        'Users.firstname',
                        'Users.lastname',
                        'full_name' => $q->func()->concat([
                            'Users.firstname' => 'literal',
                            ' ',
                            'Users.lastname'  => 'literal'
                        ])
                    ]);
                    return $q;
                }
            ])
            ->disableHydration();
        $where = $SystemfailuresFilter->indexFilter();

        $having = [];
        if (isset($where['full_name LIKE'])) {
            $having['full_name LIKE'] = $where['full_name LIKE'];
            unset($where['full_name LIKE']);
        }

        $query->where($where);
        if (!empty($having)) {
            $query->having($having);
        }
        $query->orderBy($SystemfailuresFilter->getOrderForPaginator('Systemfailures.start_time', 'asc'));

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
     * Custom Validation
     *
     * @param $value
     * @param $context
     * @return bool
     */
    public function startBeforeEnd($value, $context) {
        if (isset($context['data']['start_time']) && $context['data']['end_time']) {
            $startTimestamp = strtotime($context['data']['start_time']);
            $endTimestamp = strtotime($context['data']['end_time']);

            return $endTimestamp > $startTimestamp;
        }

        return false;
    }

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Systemfailures.id' => $id]);
    }

    /**
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    public function getSystemfailuresForReporting($startTimestamp, $endTimestamp) {
        $startDateSqlFormat = date('Y-m-d H:i:s', $startTimestamp);
        $endDateSqlFormat = date('Y-m-d H:i:s', $endTimestamp);


        $query = $this
            ->find()
            ->where([
                'OR' => [
                    ['(:start1 BETWEEN Systemfailures.start_time AND Systemfailures.end_time)'],
                    ['(:end1    BETWEEN Systemfailures.start_time AND Systemfailures.end_time)'],
                    ['(Systemfailures.start_time BETWEEN :start2 AND :end2)'],

                ]
            ])
            ->bind(':start1', $startDateSqlFormat, 'date')
            ->bind(':end1', $endDateSqlFormat, 'date')
            ->bind(':start2', $startDateSqlFormat, 'date')
            ->bind(':end2', $endDateSqlFormat, 'date')
            ->disableHydration()
            ->all();

        return $this->formatResultAsCake2($query->toArray(), false);
    }

    /**
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return array
     */
    public function getSystemfailuresForReportingFormatedAsDowntime($startTimestamp, $endTimestamp) {
        $startDateSqlFormat = date('Y-m-d H:i:s', $startTimestamp);
        $endDateSqlFormat = date('Y-m-d H:i:s', $endTimestamp);

        $query = $this
            ->find()
            ->where([
                'OR' => [
                    ['(:start1 BETWEEN Systemfailures.start_time AND Systemfailures.end_time)'],
                    ['(:end1    BETWEEN Systemfailures.start_time AND Systemfailures.end_time)'],
                    ['(Systemfailures.start_time BETWEEN :start2 AND :end2)'],

                ]
            ])
            ->bind(':start1', $startDateSqlFormat, 'date')
            ->bind(':end1', $endDateSqlFormat, 'date')
            ->bind(':start2', $startDateSqlFormat, 'date')
            ->bind(':end2', $endDateSqlFormat, 'date')
            ->contain([
                'Users' => function (Query $query) {
                    $query->disableAutoFields()
                        ->select([
                            'Users.firstname',
                            'Users.lastname'
                        ]);
                    return $query;
                }
            ])
            ->disableHydration()
            ->all();

        $result = $this->emptyArrayIfNull($query->toArray());

        $fakeDowntimes = [];

        foreach ($result as $record) {
            /** @var \Cake\I18n\DateTime $startTime */
            $startTime = $record['start_time'];
            /** @var \Cake\I18n\DateTime $endTime */
            $endTime = $record['end_time'];

            $fakeDowntimes[] = [
                'author_name'          => $record['user']['firstname'] . ' ' . $record['user']['lastname'],
                'scheduled_start_time' => $startTime->format('Y-m-d H:i:s'),
                'scheduled_end_time'   => $endTime->format('Y-m-d H:i:s'),
                'comment_data'         => $record['comment']
            ];
        }

        return [
            'Systemfailure' => $fakeDowntimes
        ];
    }

}
