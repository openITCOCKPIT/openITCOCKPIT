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

namespace App\itnovum\openITCOCKPIT\Core\Angular;

use App\Lib\PluginManager;

/**
 * By default, openITCOCKPIT will block all non API requests to the backend and will serve a default error message.
 * "You have reached the openITCOCKPIT backend API."
 * In some cases, you might want to whitelist certain paths to be accessible without triggering the default error message.
 * This class can be used to define such whitelisted actions.
 */
class BackendWhitelist implements BackendWhitelistInterface {

    /**
     * @param string $controller
     * @param string $action
     * @param string $plugin (CakePHP passes the plugin name in lowercase with no underscores, e.g. 'designmodule' for 'DesignModule' or 'eventcorrelationmodule' or 'EventcorrelationModule')
     * @return bool
     */
    public function isWhitelisted(string $controller, string $action, string $plugin = ''): bool {
        if (empty($plugin)) {
            // Check core whitelist
            $whitelist = $this->getWhitelistedActions();

            $key = $controller . '.' . $action;
            if (isset($whitelist[$key])) {
                // We hit a whitelist controller.action
                return true;
            }
        } else {
            // Check plugin/module whitelist
            $whitelistPerPlugin = $this->getModuleWhitelistedActions();

            $key = $controller . '.' . $action;
            if (isset($whitelistPerPlugin[$plugin][$key])) {
                // We hit a whitelist controller.action
                return true;
            }
        }

        return false;
    }

    /**
     * Controller actions of the openITCOCKPIT core that are whitelisted by default.
     * This list can be extended by modules/plugins.
     *
     * The list gets checked by isset() so only the key is relevant.
     *
     * @return string[]
     */
    public function getWhitelistedActions(): array {
        $whitelist = [
            'administrators.php_info'    => 'administrators.php_info',
            'eventlogs.listtocsv'        => 'eventlogs.listtocsv',
            'hosts.listtocsv'            => 'hosts.listtocsv',
            'services.listtocsv'         => 'services.listtocsv',
            'hostgroups.listtocsv'       => 'hostgroups.listtocsv',
            'servicegroups.listtocsv'    => 'servicegroups.listtocsv',
            'users.listtoxlsx'           => 'users.listtoxlsx',
            'users.listtocsv'            => 'users.listtocsv',
            'users.login'                => 'users.login',
            'users.logout'               => 'users.logout',
            'statuspages.publicview'     => 'statuspages.publicview',
            'backups.downloadbackupfile' => 'backups.downloadbackupfile'
        ];

        return $whitelist;
    }


    public function getModuleWhitelistedActions(): array {
        $whitelists = [];

        //Load Plugin configuration files
        foreach (PluginManager::getAvailablePlugins() as $pluginName) {
            // Convert 'PluginName' to 'pluginname' as CakePHP does this if you access $this->request->getParam('plugin') ['eventcorrelation_module' in url gets to 'eventcorrelationmodule']
            $moduleUrlName = strtolower($pluginName);

            $className = sprintf('\%s\Lib\BackendWhitelist', $pluginName);
            if (class_exists($className)) {
                /** @var BackendWhitelistInterface $ModuleBackendWhitelist */
                $ModuleBackendWhitelist = new $className();

                $whitelists[$moduleUrlName] = $ModuleBackendWhitelist->getWhitelistedActions();
            }
        }

        return $whitelists;
    }

}
