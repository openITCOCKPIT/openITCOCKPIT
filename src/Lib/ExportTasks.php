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
 * Class ExportTasks
 * @package App\Lib
 */
class ExportTasks {

    /**
     * @var PluginExportTasks[]
     */
    private $tasks = [];

    /**
     * ExportTasks constructor.
     */
    public function __construct() {
        //Load Plugin Export Tasks
        foreach (PluginManager::getAvailablePlugins() as $pluginName) {
            $className = sprintf('\%s\Lib\ExportTasks', $pluginName);
            if (class_exists($className)) {
                /** @var PluginExportTasks $PluginExportTasks */
                $PluginExportTasks = new $className();
                $this->tasks[] = $PluginExportTasks;
            }
        }
    }

    /**
     * @return PluginExportTasks[]
     */
    public function getTasks() {
        return $this->tasks;
    }


}

