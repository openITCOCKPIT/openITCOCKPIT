<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace Statusengine2Module\Model\Table;

use App\Lib\Exceptions\InvalidArgumentException;
use App\Lib\Interfaces\ServicestatusTableInterface;
use App\Lib\Traits\Cake2ResultTableTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServicestatusConditions;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;

/**
 * Servicestatus Model
 *
 * Bake command: bin/cake bake model -p Statusengine2Module Servicestatus
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Object
 *
 * @method \Statusengine2Module\Model\Entity\Servicestatus get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\Servicestatus findOrCreate($search, callable $callback = null, $options = [])
 */
class ServicestatusTable extends Table implements ServicestatusTableInterface {

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('nagios_servicestatus');
        $this->setDisplayField('servicestatus_id');
        $this->setPrimaryKey('servicestatus_id');

        $this->belongsTo('Objects', [
            'foreignKey' => 'service_object_id',
            'joinType'   => 'INNER',
            'className'  => 'Statusengine2Module.Objects'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        //Readonly table
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
        //Readonly table
        return $rules;
    }

    /**
     * @param null $uuid
     * @param ServicestatusFields $ServicestatusFields
     * @param null|ServicestatusConditions $ServicestatusConditions
     * @return array|bool
     */
    public function byUuidMagic($uuid, ServicestatusFields $ServicestatusFields, ?ServicestatusConditions $ServicestatusConditions = null) {
        if ($uuid === null || empty($uuid)) {
            return [];
        }

        $select = $ServicestatusFields->getFields();

        $query = $this->find()
            ->select($select)
            ->contain([
                'Objects' => [
                    'fields' => [
                        'Objects.name2'
                    ]
                ]
            ]);

        $where = [];
        if ($ServicestatusConditions !== null) {
            if ($ServicestatusConditions->hasConditions()) {
                $where = $ServicestatusConditions->getConditions();
            }
        }

        $where['Objects.objecttype_id'] = 2;
        $findType = 'all';
        if (is_array($uuid)) {
            $where['Objects.name2 IN'] = $uuid;
        } else {
            $where['Objects.name2'] = $uuid;
            $findType = 'first';
        }

        $query->where($where);
        $query->disableHydration();

        if ($findType === 'all') {
            $result = $query->all();
            $result = $this->formatResultAsCake2($result->toArray());

            $return = [];
            foreach ($result as $record) {
                $return[$record['Servicestatus']['object']['name2']] = $record;
            }

            return $return;
        }

        if ($findType === 'first') {
            $result = $query->first();
            return $this->formatFirstResultAsCake2($result);
        }

        return [];
    }

    /**
     * @param string $uuid
     * @param ServicestatusFields $ServicestatusFields
     * @param null|ServicestatusConditions $ServicestatusConditions
     * @return array|bool
     */
    public function byUuid($uuid, ServicestatusFields $ServicestatusFields, ?ServicestatusConditions $ServicestatusConditions = null) {
        return $this->byUuidMagic($uuid, $ServicestatusFields, $ServicestatusConditions);
    }

    /**
     * @param array $uuids
     * @param ServicestatusFields $ServicestatusFields
     * @param null $ServicestatusConditions
     * @return array|bool
     * @throws InvalidArgumentException
     */
    public function byUuids($uuids, ServicestatusFields $ServicestatusFields, $ServicestatusConditions = null) {
        if (!is_array($uuids)) {
            throw new InvalidArgumentException('$uuids need to be an array!');
        }
        return $this->byUuidMagic($uuids, $ServicestatusFields, $ServicestatusConditions);
    }
}
