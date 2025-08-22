<?php

namespace App\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CalendarHolidays Model
 *
 * @property \App\Model\Table\CalendarsTable|\Cake\ORM\Association\BelongsTo $Calendars
 *
 * @method \App\Model\Entity\CalendarHoliday get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CalendarHoliday newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\CalendarHoliday[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CalendarHoliday|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CalendarHoliday saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CalendarHoliday patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CalendarHoliday[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\CalendarHoliday findOrCreate($search, ?callable $callback = null, array $options = [])
 */
class CalendarHolidaysTable extends Table {

    public function getSchema(): TableSchemaInterface {
        $schema = parent::getSchema();
        $schema->addColumn('date', 'string');
        return $schema;
    }

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('calendar_holidays');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Calendars', [
            'foreignKey' => 'calendar_id',
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
            ->integer('default_holiday')
            ->notEmptyString('default_holiday')
            ->boolean('default_holiday');

        $validator
            ->date('date')
            ->requirePresence('date', 'create')
            ->notEmptyDate('date');

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
        $rules->add($rules->existsIn(['calendar_id'], 'Calendars'));

        return $rules;
    }
}
