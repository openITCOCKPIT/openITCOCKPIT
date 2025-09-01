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

declare(strict_types=1);

namespace Statusengine3Module\Model\Table;

use App\Lib\Exceptions\InvalidArgumentException;
use App\Lib\Interfaces\HoststatusTableInterface;
use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\HoststatusConditions;
use itnovum\openITCOCKPIT\Core\HoststatusFields;

/**
 * Hoststatus Model
 *
 * @method \Statusengine3Module\Model\Entity\Hoststatus newEmptyEntity()
 * @method \Statusengine3Module\Model\Entity\Hoststatus newEntity(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus[] newEntities(array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus get($primaryKey, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Statusengine3Module\Model\Entity\Hoststatus[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class HoststatusTable extends Table implements HoststatusTableInterface {

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    use PaginationAndScrollIndexTrait;
    use Cake2ResultTableTrait;


    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('statusengine_hoststatus');
        $this->setDisplayField('hostname');
        $this->setPrimaryKey('hostname');
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
     * @param null|string|array $uuid
     * @param HoststatusFields $HoststatusFields
     * @param HoststatusConditions|null $HoststatusConditions
     * @return array
     */
    public function byUuidMagic($uuid, HoststatusFields $HoststatusFields, ?HoststatusConditions $HoststatusConditions = null) {
        if ($uuid === null || empty($uuid)) {
            return [];
        }

        $select = $HoststatusFields->getFields();
        if (!empty($select)) {
            $select[] = 'Hoststatus.hostname';
        }

        $query = $this->find()
            ->select($select)
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hoststatus.hostname = Hosts.uuid']
            );

        $where = [];
        if ($HoststatusConditions !== null) {
            if ($HoststatusConditions->hasConditions()) {
                $where = $HoststatusConditions->getConditions();
            }
        }

        $findType = 'all';
        if (is_array($uuid)) {
            $where['Hosts.uuid IN'] = $uuid;
        } else {
            $where['Hosts.uuid'] = $uuid;
            $findType = 'first';
        }

        $query->where($where);
        $query->disableHydration();

        if ($findType === 'all') {
            $result = $query->all();
            $result = $this->formatResultAsCake2($result->toArray());

            $return = [];
            foreach ($result as $record) {
                $return[$record['Hoststatus']['hostname']] = $record;
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
     * @param $uuid
     * @param HoststatusFields $HoststatusFields
     * @param HoststatusConditions|null $HoststatusConditions
     * @return array|string
     */
    public function byUuid($uuid, HoststatusFields $HoststatusFields, ?HoststatusConditions $HoststatusConditions = null) {
        return $this->byUuidMagic($uuid, $HoststatusFields, $HoststatusConditions);
    }


    /**
     * @param $uuids
     * @param HoststatusFields $HoststatusFields
     * @param HoststatusConditions|null $HoststatusConditions
     * @return array
     * @throws \App\Lib\Exceptions\InvalidArgumentException
     */
    public function byUuids($uuids, HoststatusFields $HoststatusFields, ?HoststatusConditions $HoststatusConditions = null) {
        if (!is_array($uuids)) {
            throw new InvalidArgumentException('$uuids need to be an array!');
        }
        return $this->byUuidMagic($uuids, $HoststatusFields, $HoststatusConditions);
    }
}
