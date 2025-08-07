<?php
// Copyright (C) <2018>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace App\Lib\Traits;


use Cake\I18n\FrozenTime;
use Cake\ORM\Association;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\Utility\Inflector;

/**
 * Trait Cake2ResultTrait
 * @package App\Lib\Traits
 * @deprecated Remove this ASAP !!!
 */
trait Cake2ResultTableTrait {


    /**
     * @param array $records
     * @param bool $contain
     * @return array
     */
    public function formatResultAsCake2($records = [], $contain = true) {
        if (empty($records) || is_null($records)) {
            return [];
        }


        $modelName = ucfirst(Inflector::singularize($this->getAlias()));
        /** @var \Cake\ORM\AssociationCollection $AssociationCollection */
        $AssociationCollection = $this->associations();
        $associations = array_flip($AssociationCollection->keys());
        $cake2Result = [];

        //Map Cake4 property name to CakePHP2 Model Name
        $associationMapping = $this->getCakePHP4ToCakePHP2Mapping($AssociationCollection);

        foreach ($records as $row) {
            $record = [];
            foreach ($row as $key => $value) {
                if (isset($associationMapping[$key]) && is_array($value)) {
                    //Field is from an associated model
                    $assocInformation = $associationMapping[$key];

                    $assocRecords = [];
                    if ($assocInformation['isHasMany']) {
                        foreach ($value as $assocRow) {
                            $assocRecord = [];
                            foreach ($assocRow as $assocKey => $assocValue) {
                                $assocRecord[$assocKey] = $this->asString($assocValue);
                            }
                            $assocRecords[] = $assocRecord;
                        }
                    } else {
                        //hasOne
                        foreach ($value as $assocKey => $assocValue) {
                            $assocRecords[$assocKey] = $this->asString($assocValue);
                        }
                    }
                    $assocModelName = $assocInformation['modelName']; //CakePHP 2 like model name
                    $record[$assocModelName] = $assocRecords;
                } else {
                    //Field is from the model itself
                    $record[$modelName][$key] = $this->asString($value);
                }

                //Add missing associations
                if ($contain === true) {
                    foreach ($associationMapping as $propertyName => $assocInformation) {
                        if (!isset($record[$assocInformation['modelName']])) {
                            $record[$assocInformation['modelName']] = [];
                        }
                    }
                }
            }
            $cake2Result[] = $record;
        }

        return $cake2Result;
    }

    /**
     * @param array $records
     * @param bool $contain
     * @return array
     */
    public function formatFirstResultAsCake2($row = [], $contain = true) {
        if (empty($row) || is_null($row)) {
            return [];
        }


        $modelName = ucfirst(Inflector::singularize($this->getAlias()));
        /** @var \Cake\ORM\AssociationCollection $AssociationCollection */
        $AssociationCollection = $this->associations();
        $associations = array_flip($AssociationCollection->keys());

        //Map Cake4 property name to CakePHP2 Model Name
        $associationMapping = $this->getCakePHP4ToCakePHP2Mapping($AssociationCollection);

        $record = [];
        foreach ($row as $key => $value) {
            if (isset($associationMapping[$key]) && is_array($value)) {
                //Field is from an associated model
                $assocInformation = $associationMapping[$key];

                $assocRecords = [];
                if ($assocInformation['isHasMany']) {
                    foreach ($value as $assocRow) {
                        $assocRecord = [];
                        foreach ($assocRow as $assocKey => $assocValue) {
                            $assocRecord[$assocKey] = $this->asString($assocValue);
                        }
                        $assocRecords[] = $assocRecord;
                    }
                } else {
                    //hasOne
                    foreach ($value as $assocKey => $assocValue) {
                        $assocRecords[$assocKey] = $this->asString($assocValue);
                    }
                }
                $assocModelName = $assocInformation['modelName']; //CakePHP 2 like model name
                $record[$assocModelName] = $assocRecords;
            } else {
                //Field is from the model itself
                $record[$modelName][$key] = $this->asString($value);
            }

            //Add missing associations
            if ($contain === true) {
                foreach ($associationMapping as $propertyName => $assocInformation) {
                    if (!isset($record[$assocInformation['modelName']])) {
                        $record[$assocInformation['modelName']] = [];
                    }
                }
            }
        }

        return $record;
    }

    /**
     * @param array $records
     * @param string $key
     * @param string $value
     * @return array
     */
    public function formatListAsCake2($records = [], $key = 'id', $value = 'name') {
        if (empty($records) || is_null($records)) {
            return [];
        }

        $result = [];
        foreach ($records as $row) {
            $result[$row[$key]] = $row[$value];
        }

        return $result;

    }

    private function asString($value) {
        if ($value instanceof \Cake\I18n\DateTime) {
            /** @var FrozenTime $value */
            return date('Y-m-d H:i:s', $value->getTimestamp());
        }

        if (is_array($value)) {
            /** @var array $value */
            return $value;
        }

        /** @var string|mixed $value */
        return $value;
    }

    private function getPropertyName(Association $assoc) {
        [, $name] = pluginSplit($assoc->getName());
        if ($assoc instanceof HasMany || $assoc instanceof BelongsToMany) {
            return Inflector::underscore($name);
        } else {
            // hasOne and belongsTo is singular
            return Inflector::underscore(Inflector::singularize($name));
        }
    }

    private function getCakephp2ModelName(string $propertyName) {
        return ucfirst(Inflector::camelize(Inflector::singularize($propertyName)));
    }

    private function getCakePHP4ToCakePHP2Mapping(\Cake\ORM\AssociationCollection $AssociationCollection) {
        //Map Cake4 property name to CakePHP2 Model Name
        $associations = array_flip($AssociationCollection->keys());

        $associationMapping = [];

        foreach ($associations as $associationAlias => $int) {
            $assoc = $AssociationCollection->get($associationAlias);
            $propertyName = $this->getPropertyName($assoc);

            if (strpos($propertyName, '_') > 0) {
                //PropertyNames with an underscore where buggy in the original Cake2 Result Trait before it was refactored for CakePHP 4.1
                //Ignore these like timeperiod_timeranges or hosts_to_containers_sharing
                continue;
            }

            $associationMapping[$propertyName] = [
                'modelName' => $this->getCakephp2ModelName($propertyName),
                'isHasMany' => $assoc instanceof HasMany || $assoc instanceof BelongsToMany
            ];
        }

        return $associationMapping;
    }
}