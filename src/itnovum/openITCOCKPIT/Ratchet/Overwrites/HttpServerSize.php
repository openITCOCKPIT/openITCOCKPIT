<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace itnovum\openITCOCKPIT\Ratchet\Overwrites;


use Ratchet\Http\HttpServer;
use Ratchet\Http\HttpServerInterface;

/**
 * @deprecated With openITCOCKPIT 5.7.0 and ITC-3487 all WebSocket functionality got removed and is not handled by a seperate WebSocket server written in Go.
 */
class HttpServerSize extends HttpServer {

    public function __construct(HttpServerInterface $component) {
        parent::__construct($component);
        $this->_reqParser->maxSize = '8192';
    }

}
