<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * MessagesOtd Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\MessagesOtd newEmptyEntity()
 * @method \App\Model\Entity\MessagesOtd newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MessagesOtd[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MessagesOtd get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MessagesOtd findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\MessagesOtd patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\MessagesOtd[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MessagesOtd|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MessagesOtd saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MessagesOtd[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MessagesOtd[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\MessagesOtd[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MessagesOtd[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MessagesOtdTable extends Table {
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('messages_otd');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'   => 'INNER',
        ]);


        $this->belongsToMany('Usergroups', [
            'className'        => 'Usergroups',
            'foreignKey'       => 'message_otd_id',
            'targetForeignKey' => 'usergroup_id',
            'joinTable'        => 'messages_otd_to_usergroups',
            'saveStrategy'     => 'replace',
            'dependent'        => true
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('content')
            ->notEmptyString('content', __('Please set content for the message of the day'));

        $validator
            ->scalar('style')
            ->maxLength('style', 255)
            ->requirePresence('style', 'create')
            ->notEmptyString('style');

        $validator
            ->date('date', ['ymd'])
            ->requirePresence('date')
            ->notEmptyString('date');

        $validator
            ->scalar('expiration_duration')
            ->requirePresence('expiration_duration', 'create')
            ->notEmptyString('expiration_duration',
                __('Please enter the expiry time in days'), function ($context) {
                    return ($context['data']['expire'] === true);
                });

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
        $rules->add($rules->isUnique(['date']), ['errorField' => 'date']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }


    /**
     * @param GenericFilter $GenericFilter
     * @param null $PaginateOMat
     * @return mixed
     */
    public function getMessagesOTDIndex(GenericFilter $GenericFilter, $PaginateOMat = null) {
        $query = $this->find('all')
            ->disableHydration();
        $where = $GenericFilter->genericFilters();
        if (!empty($where['MessagesOtd.date LIKE'])) {
            if (strpos($where['MessagesOtd.date LIKE'], '-') !== false) {
                $where['DATE_FORMAT(MessagesOtd.date, "%Y-%m-%d") LIKE'] = $where['MessagesOtd.date LIKE'];
            } else {
                $where['DATE_FORMAT(MessagesOtd.date, "%d.%m.%Y") LIKE'] = $where['MessagesOtd.date LIKE'];
            }
            unset($where['MessagesOtd.date LIKE']);
        }

        $query->where($where);


        $query->orderBy($GenericFilter->getOrderForPaginator('MessagesOtd.date', 'asc'));

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

    /**
     * @param $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['MessagesOtd.id' => $id]);
    }

    /**
     * @param $id
     * @return array|\Cake\Datasource\EntityInterface
     */
    public function getMessageOtdByIdForEdit($id) {
        $result = $this->find()
            ->contain([
                'Usergroups'
            ])
            ->where([
                'MessagesOtd.id' => $id
            ])
            ->disableHydration()
            ->firstOrFail();

        $result['usergroups'] = [
            '_ids' => Hash::extract($result, 'usergroups.{n}.id')
        ];
        return $result;
    }

    /**
     * @param $userTimezone
     * @param $usergroupId
     * @return array|\Cake\Datasource\EntityInterface|null
     * @throws \Exception
     */
    public function getMessageOtdForToday($userTimezone, $usergroupId) {
        $today = new \DateTime('now', new \DateTimeZone($userTimezone));
        $query = $this->find();
        return $query->select([
            'MessagesOtd.id',
            'MessagesOtd.title',
            'MessagesOtd.description',
            'MessagesOtd.content',
            'MessagesOtd.date',
            'MessagesOtd.expiration_duration',
            'MessagesOtd.style',
            'usergroup_ids' => $query->newExpr('GROUP_CONCAT(DISTINCT MessagesOtdToUsergroups.usergroup_id)'),
        ])->leftJoin(
            ['MessagesOtdToUsergroups' => 'messages_otd_to_usergroups'],
            ['MessagesOtdToUsergroups.message_otd_id = MessagesOtd.id']
        )->where([
            ':today BETWEEN MessagesOtd.date AND IF(MessagesOtd.expiration_duration IS NULL, :today, DATE_ADD(MessagesOtd.date, INTERVAL MessagesOtd.expiration_duration DAY))'
        ])->bind(':today', $today, 'date')
            ->disableHydration()
            ->groupBy('MessagesOtd.date')
            ->having([
                'FIND_IN_SET(:usergroup_id, IF(usergroup_ids IS NULL, :usergroup_id, usergroup_ids))'
            ])->bind(':usergroup_id', $usergroupId, 'integer')
            ->orderBy(['MessagesOtd.date' => 'DESC'])
            ->first();
    }
}
