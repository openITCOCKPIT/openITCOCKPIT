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

namespace App\Lib\Traits;

use App\Lib\PluginManager;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Class PluginManagerTableTrait
 * @package App\Lib\Traits
 */
trait PluginManagerTableTrait {

    use LocatorAwareTrait;

    /**
     * @return $this
     */
    public function initializePluginTables() {
        $associations = PluginManager::$associations;
        
        if (isset($associations[$this->getRegistryAlias()])) {
            //$TableLocator = TableRegistry::getTableLocator();
            foreach ($associations[$this->getRegistryAlias()] as $pluginTable) {
                //$TableLocator->get($pluginTable);
                $pluginTable = $this->getTableLocator()->get($pluginTable);
                if (is_object($pluginTable) && method_exists($pluginTable, 'bindCoreAssociations')) {
                    $pluginTable->bindCoreAssociations($this);
                }
            }
        }

        return $this;
    }
}
