<?php
// Copyright (C) <2018>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace App\Lib;


use App\Application;
use Cake\Core\Plugin;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PluginManager {

    /**
     * @var array
     */
    private $modules = [];

    /**
     * @var Application
     */
    private $application;

    /**
     * @var bool
     */
    private $bootstrapPlugins;

    public static $associations = [];

    /**
     * PluginManager constructor.
     * @param Application $application
     * @param bool $bootstrapPlugins
     */
    public function __construct(Application $application, $bootstrapPlugins = true) {
        $this->application = $application;
        $this->bootstrapPlugins = $bootstrapPlugins;

        FactoryLocator::add('Table', new PluginManagerTableLocator()); // CakePHP >= 4.1
        //TableRegistry::setTableLocator(new PluginManagerTableLocator()); // CakePHP <= 4.1

        $this->_getAvailablePlugins();
        $this->addAllPlugins();
    }

    /**
     * @return array
     */
    private function _getAvailablePlugins() {
        $this->modules = self::getAvailablePlugins();
        return $this->modules;
    }

    private function addAllPlugins() {
        /** @var ClassLoader $loader */
        $loader = require ROOT . '/vendor/autoload.php';

        //Autoload Plugin Classes
        foreach ($this->modules as $moduleName) {
            $loader->setPsr4(
                sprintf('%s\\', $moduleName),
                PLUGIN . $moduleName . DS . 'src'
            );
            $this->application->addPlugin($moduleName);

            // https://github.com/openITCOCKPIT/openITCOCKPIT/pull/1578
            // This leads to issues when Plugins/Modules have older version of CakePHP (to run Tests for example)
            // Issues like 'Unknown method "selectQuery" called on App\\Model\\Table\\AgentchecksTable'
            //if (is_file(PLUGIN . $moduleName . DS . 'vendor/autoload.php')) {
            //    require PLUGIN . $moduleName . DS . 'vendor/autoload.php';
            //}

            $pluginAssociationsFile = PLUGIN . $moduleName . DS . 'config' . DS . 'associations.php';
            if (file_exists($pluginAssociationsFile)) {
                $mapping = require_once $pluginAssociationsFile;
                self::$associations = Hash::merge(self::$associations, $mapping);
            }

        }

        if ($this->bootstrapPlugins === true) {
            $this->application->pluginBootstrap();
        }
    }

    public static function getAvailablePlugins() {
        $Finder = new Finder();
        $Finder->in(PLUGIN)
            ->directories()
            ->ignoreDotFiles(true)
            ->depth(0);

        $modules = [];

        /** @var SplFileInfo $folder */
        foreach ($Finder as $folder) {
            $modules[] = $folder->getFilename();
        }

        return $modules;
    }

}
