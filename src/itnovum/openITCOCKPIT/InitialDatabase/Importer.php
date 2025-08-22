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

namespace itnovum\openITCOCKPIT\InitialDatabase;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class Importer implements ImporterInterface {

    /**
     * @var Table
     */
    protected $Table;

    public function __construct(Table $Table) {
        $this->Table = $Table;
    }

    /**
     * @return bool
     */
    public function isTableEmpty() {
        // SELECT COUNT(*) count FROM <TABLE>
        $result = $this->Table->find()->count();
        $result = (int)$result;

        return $result === 0;
    }

    /**
     * @return array
     */
    public function getData() {
        return [];
    }

    /**
     * @param Entity $entity
     * @param array $record
     * @return \Cake\Datasource\EntityInterface
     */
    public function patchEntityAndKeepAllIds(EntityInterface $entity, array $record){
        $associatedAccessibleFields = [];
        foreach($this->Table->associations() as $association){
            $associationKey = $association->getName(); //cakephp >= 4.1
            //$associationKey = strtolower(Inflector::underscore($association->getName())); // cakephp <= 4.1
            $associatedAccessibleFields[$associationKey] = ['accessibleFields' => ['*' => true]];
        }

        $entity = $this->Table->patchEntity($entity, $record, [
            'validate'         => false,
            'accessibleFields' => ['*' => true],
            'associated'       => $associatedAccessibleFields
        ]);
        return $entity;
    }

}
