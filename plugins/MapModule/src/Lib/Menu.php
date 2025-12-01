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

namespace MapModule\Lib;


use itnovum\openITCOCKPIT\Core\Menu\MenuCategory;
use itnovum\openITCOCKPIT\Core\Menu\MenuHeadline;
use itnovum\openITCOCKPIT\Core\Menu\MenuInterface;
use itnovum\openITCOCKPIT\Core\Menu\MenuLink;

class Menu implements MenuInterface {

    /**
     * @return array
     */
    public function getHeadlines() {
        $Overview = new MenuHeadline(\itnovum\openITCOCKPIT\Core\Menu\Menu::MENU_OVERVIEW);
        $Overview
            ->addCategory((new MenuCategory(
                'maps_category',
                __('Maps')
            ))
                ->addLink(new MenuLink(
                    __('Maps'),
                    'MapsIndex',
                    'maps',
                    'index',
                    'MapModule',
                    ['fas', 'location-pin'],
                    [],
                    3,
                    true,
                    'map_module/maps/index'
                ))
                ->addLink(new MenuLink(
                    __('Rotations'),
                    'RotationsIndex',
                    'rotations',
                    'index',
                    'MapModule',
                    ['fas', 'retweet'],
                    [],
                    4,
                    true,
                    'map_module/rotations/index'
                ))
                ->addLink(new MenuLink(
                    __('Map Generators'),
                    'MapgeneratorsIndex',
                    'mapgenerators',
                    'index',
                    'MapModule',
                    ['fas', 'cog'],
                    [],
                    5,
                    true,
                    'map_module/mapgenerators/index'
                ))
            );

        return [$Overview];
    }

}
