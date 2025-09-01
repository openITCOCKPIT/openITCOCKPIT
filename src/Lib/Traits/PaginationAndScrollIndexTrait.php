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

namespace App\Lib\Traits;


use Cake\ORM\Query;
use itnovum\openITCOCKPIT\Database\Cake4Paginator;
use itnovum\openITCOCKPIT\Database\ScrollIndex;

/**
 * Trait PaginationAndScrollIndexTrait
 * @package App\Lib\Traits
 */
trait PaginationAndScrollIndexTrait {

    /**
     * @param array|null $result
     * @return array
     */
    public function emptyArrayIfNull($result) {
        if ($result === null) {
            return [];
        }

        return $result;
    }

    /**
     * @param Query $query
     * @param Cake4Paginator $Cake4Paginator
     * @param bool $contain
     * @return array
     */
    public function paginate(Query $query, Cake4Paginator $Cake4Paginator, $contain = true) {
        $Cake4Paginator->setCountResult($query->count());

        $query->offset($Cake4Paginator->getOffset());
        $query->limit($Cake4Paginator->getLimit());
        $result = $this->formatResultAsCake2($query->toArray(), $contain);
        $Cake4Paginator->setCurrent(sizeof($result));
        $Cake4Paginator->paginate();
        return $result;
    }

    /**
     * @param Query $query
     * @param Cake4Paginator $Cake4Paginator
     * @return array
     */
    public function paginateCake4(Query $query, Cake4Paginator $Cake4Paginator) {
        $Cake4Paginator->setCountResult($query->count());

        $query->offset($Cake4Paginator->getOffset());
        $query->limit($Cake4Paginator->getLimit());
        $result = $this->emptyArrayIfNull($query->toArray());
        $Cake4Paginator->setCurrent(sizeof($result));
        $Cake4Paginator->paginate();
        return $result;
    }

    /**
     * @param Query $query
     * @param ScrollIndex $ScrollIndex
     * @param bool $contain
     * @return array
     */
    public function scroll(Query $query, ScrollIndex $ScrollIndex, $contain = true) {
        $query->offset($ScrollIndex->getOffset());
        $query->limit($ScrollIndex->getLimit());
        $result = $this->formatResultAsCake2($query->toArray(), $contain);
        $ScrollIndex->determineHasNextPage($result);
        $ScrollIndex->scroll();
        return $result;
    }

    /**
     * @param Query $query
     * @param ScrollIndex $ScrollIndex
     * @return array
     */
    public function scrollCake4(Query $query, ScrollIndex $ScrollIndex) {
        $query->offset($ScrollIndex->getOffset());
        $query->limit($ScrollIndex->getLimit());

        $query->all();
        $result = $query->toArray();
        if ($result === null) {
            $result = [];
        }
        $ScrollIndex->determineHasNextPage($result);
        $ScrollIndex->scroll();
        return $result;
    }

}
