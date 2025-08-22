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

namespace itnovum\openITCOCKPIT\Core;


use Cake\Http\ServerRequest;
use itnovum\openITCOCKPIT\Filter\HostFilter;

class HostControllerRequest {

    /**
     * @var ServerRequest
     */
    private $request;

    /**
     * @var HostFilter
     */
    private $HostFilter;

    public function __construct(ServerRequest $request, HostFilter $HostFilter) {
        $this->request = $request;
        $this->HostFilter = $HostFilter;
    }

    /**
     * @return bool
     */
    public function isRequestFromBrowser() {
        if ($this->request->getQuery('BrowserContainerId', null) !== null) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getBrowserContainerIdsByRequest() {
        $containerIds = $this->request->getQuery('BrowserContainerId', []);

        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        return $containerIds;
    }


    /**
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getOrder($sort = '', $direction = '') {
        return $this->HostFilter->getOrderForPaginator($sort, $direction);
    }

    public function includeDowntimeInformation() {
        $includes = $this->request->getQuery('includes', []);
        return in_array('downtimes', $includes, true);
    }

    public function includeAcknowledgementInformation() {
        $includes = $this->request->getQuery('includes', []);
        return in_array('acknowledgements', $includes, true);
    }

}
