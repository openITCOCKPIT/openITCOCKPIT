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

namespace itnovum\openITCOCKPIT\Database;

use Cake\Controller\Controller;

class ScrollIndex {

    /**
     * Current (CakePHP 4) Controller
     * @var Controller
     */
    private $Controller;

    /**
     * @var int
     */
    private $limit = 25;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var bool
     */
    private $hasNextPage = true;


    /**
     * ScrollIndex constructor.
     * @param Controller $Controller
     * @param int $page
     * @param int $limit
     */
    public function __construct(Controller $Controller, $page = 1, $limit = 25) {
        $this->Controller = $Controller;

        $this->page = (int)$page;
        $this->limit = (int)$limit;

        if ($this->page === 0 || $this->page < 0) {
            $this->page = 1;
        }

        if ($this->limit === 0 || $this->limit < 0) {
            $this->limit = 25;
        }
    }

    public function determineHasNextPage($dbResults) {
        if (is_array($dbResults)) {
            $this->hasNextPage = sizeof($dbResults) === $this->limit;
            return;
        }
        $this->hasNextPage = true;
    }

    /**
     * @param int $page
     */
    public function setPage($page) {
        $this->page = $page;
    }

    /**
     * @return int
     */
    public function getOffset() {
        //return (int)$this->limit * $this->page;
        if ($this->page === 1) {
            return 0;
        }
        return (int)$this->limit * ($this->page - 1);
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    public function getScroll() {
        return [
            'page'        => $this->page,
            'limit'       => $this->limit,
            'offset'      => $this->getOffset(),
            'hasPrevPage' => $this->page !== 1,
            'prevPage'    => ($this->page !== 1) ? $this->page - 1 : 1,
            'nextPage'    => $this->page + 1,
            'current'     => $this->page,
            'hasNextPage' => $this->hasNextPage
        ];
    }

    public function scroll() {
        $this->Controller->set('scroll', $this->getScroll());
    }

}
