<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

class PaginateOMat {

    /**
     * Current Page
     * @var int
     */
    private $page = 1;

    /**
     * @var \Controller
     */
    private $Controller;

    /**
     * @var Cake4Paginator|ScrollIndex
     */
    private $PaginatorOrScrollIndex;

    /**
     * @var bool
     */
    private $isScrollRequest = true;

    /**
     * PaginateOMat constructor.
     * @param \Controller $Controller
     * @param bool $isScrollRequest
     * @param int $page
     */
    public function __construct(Controller $Controller, $isScrollRequest = true, $page = 1) {
        $this->Controller = $Controller;
        $this->isScrollRequest = $isScrollRequest;

        $limit = $this->Controller->Authentication->getIdentity()->getOriginalData()['paginatorlength'];

        if ($this->Controller->getRequest()->getQuery('limit', 0) > 0) {
            $limit = (int)$this->Controller->getRequest()->getQuery('limit');
        }

        if ($this->isScrollRequest) {
            $this->PaginatorOrScrollIndex = new ScrollIndex($Controller, $page, $limit);
        } else {
            $this->PaginatorOrScrollIndex = new Cake4Paginator($Controller, $page, $limit);
        }

        $this->page = $page;
        $this->PaginatorOrScrollIndex->setPage($this->page);
    }

    /**
     * @param int $page
     */
    public function setPage($page) {
        $this->page = $page;
        $this->PaginatorOrScrollIndex->setPage($this->page);
    }

    /**
     * @return bool
     */
    public function useScroll() {
        if ($this->PaginatorOrScrollIndex instanceof ScrollIndex) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function usePagination() {
        return !$this->useScroll();
    }

    /**
     * @return Cake4Paginator|ScrollIndex
     */
    public function getHandler() {
        return $this->PaginatorOrScrollIndex;
    }


}
