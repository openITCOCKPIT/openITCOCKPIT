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

namespace MapModule\Lib;


use App\Lib\PluginAclDependencies;

class AclDependencies extends PluginAclDependencies {

    public function __construct() {
        parent::__construct();

        // Add actions that should always be allowed.
        $this
            ->allow('BackgroundUploads', 'upload')
            ->allow('BackgroundUploads', 'icon')
            ->allow('BackgroundUploads', 'deleteIcon')
            ->allow('BackgroundUploads', 'iconset');

        $this
            ->allow('Mapeditors', 'mapitem')
            ->allow('Mapeditors', 'mapitemMulti')
            ->allow('Mapeditors', 'getDependendMaps')
            ->allow('Mapeditors', 'mapsummaryitem')
            ->allow('Mapeditors', 'graph')
            ->allow('Mapeditors', 'mapsummary')
            ->allow('Mapeditors', 'backgroundImages')
            ->allow('Mapeditors', 'getIconsets')
            ->allow('Mapeditors', 'loadMapsByString')
            ->allow('Mapeditors', 'getPerformanceDataMetrics')
            ->allow('Mapeditors', 'mapWidget')
            ->allow('Mapeditors', 'mapDetails');

        $this
            ->allow('Maps', 'loadUsersForTenant')
            ->allow('Maps', 'loadContainers');

        $this
            ->allow('Rotations', 'loadMaps')
            ->allow('Rotations', 'loadContainers');

        ///////////////////////////////
        //    Add dependencies       //
        //////////////////////////////
        $this
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveItem')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'deleteItem')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveLine')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'deleteLine')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveGadget')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'deleteGadget')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveText')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'deleteText')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveIcon')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'deleteIcon')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveBackground')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'resetBackground')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'getIcons')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveSummaryitem')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'deleteSummaryitem')
            ->dependency('Mapeditors', 'edit', 'Mapeditors', 'saveMapeditorSettings');

        $this->dependency('Mapgenerators', 'add', 'Mapgenerators', 'loadContainers')
            ->dependency('Mapgenerators', 'edit', 'Mapgenerators', 'loadContainers');
    }

}
