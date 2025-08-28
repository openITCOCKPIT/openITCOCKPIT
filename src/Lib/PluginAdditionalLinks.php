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

namespace App\Lib;

/**
 * Class PluginAdditionalLinks
 * @package App\Lib
 */
abstract class PluginAdditionalLinks {

    /**
     * @var array
     */
    private $links = [];

    /**
     * PluginAdditionalLinks constructor.
     */
    public function __construct() {
        // Add a link to hosts index drop down
        //$this
        //    ->link(
        //        'hosts',
        //        'index',
        //        'list',
        //        'AutoreportsHostUsedBy({id: host.Host.id})',
        //        'fa fa-reply-all',
        //        __('Host used by Autoreport'),
        //        'autoreports', //controller for permission check
        //        'hostUsedBy' //action for permission check
        //    );
    }

    public function link(string $controller, string $action, string $position, string $ngState, string $icon, string $text, string $aclController, string $aclAction, ?string $ngIf = null) {
        $this->links[] = [
            'controller' => $controller,
            'action'     => $action,
            'position'   => $position,
            'ngState'    => $ngState,
            'icon'       => $icon,
            'text'       => $text,
            'ngIf'       => $ngIf,
            'acl'        => [
                'controller' => $aclController,
                'action'     => $aclAction
            ]
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getLinks() {
        return $this->links;
    }

}
