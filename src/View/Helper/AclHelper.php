<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * Class AclHelper
 */
class AclHelper extends Helper {

    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $_defaultConfig = [];

    /**
     * @param string $action
     * @param string $controller
     * @param string $plugin
     * @return bool
     */
    public function hasPermission(string $action, string $controller, string $plugin = '') {
        $controller = strtolower($controller);
        $action = strtolower($action);
        $plugin = strtolower($plugin);

        $ACLPERMISSIONS = $this->_View->get('ACLPERMISSIONS');
        if ($plugin === null || $plugin === '') {
            return isset($ACLPERMISSIONS[$controller][$action]);
        }

        return isset($ACLPERMISSIONS[$plugin][$controller][$action]);
    }

    public function isWritableContainer($containerIds) {
        $hasRootPrivileges = $this->_View->get('hasRootPrivileges');
        $MY_RIGHTS_LEVEL = $this->_View->get('MY_RIGHTS_LEVEL');

        if ($hasRootPrivileges === true) {
            return true;
        }
        if (!is_array($containerIds)) {
            $containerIds = [$containerIds];
        }
        foreach ($containerIds as $containerId) {
            if (isset($MY_RIGHTS_LEVEL[$containerId])) {
                if ($MY_RIGHTS_LEVEL[$containerId] == WRITE_RIGHT) {
                    return true;
                }
            }
        }

        return false;
    }

}
