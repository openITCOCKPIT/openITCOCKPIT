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

namespace itnovum\openITCOCKPIT\Core\AngularJS;


use itnovum\openITCOCKPIT\CakePHP\Folder;

/**
 * @deprecated This is obsolete because the AngularJS frontend got replaced by an Angular implementation
 */
class AngularAssets implements AngularAssetsInterface {

    /**
     * @var array
     */
    protected $jsFiles = [
    ];

    /**
     * @var array
     * Gets added before self::cssFiles in the HTML document
     */
    protected $nodeCssFiles = [
    ];

    /**
     * @var array
     */
    protected $cssFiles = [
    ];

    /**
     * @return array
     * @deprecated
     */
    public function getJsFiles() {
        $jsFiles = $this->jsFiles;
        //Load Plugin configuration files

        $Folder = new Folder(PLUGIN);
        $folders = $Folder->subdirectories();

        $loadedModules = array_filter($folders, function ($value) {
            return strpos($value, 'Module') !== false;
        });

        foreach ($loadedModules as $loadedModule) {
            $file = $loadedModule . DS . 'src' . DS . 'Lib' . DS . 'AngularAssets.php';
            if (file_exists($file)) {
                require_once $file;
                $moduleNameArray = explode('/', $loadedModule);
                $moduleName = array_pop($moduleNameArray);
                $dynamicAngularAssets = sprintf('itnovum\openITCOCKPIT\%s\AngularAssets\AngularAssets', $moduleName);
                $ModuleAngularAssets = new $dynamicAngularAssets();
                /** @var AngularAssetsInterface $ModuleAngularAssets */
                foreach ($ModuleAngularAssets->getJsFiles() as $jsFile) {
                    $jsFiles[] = $jsFile;
                }
            }
        }
        return $jsFiles;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getCssFiles() {
        $cssFiles = $this->cssFiles;
        //Load Plugin configuration files

        $Folder = new Folder(PLUGIN);
        $folders = $Folder->subdirectories();

        $loadedModules = array_filter($folders, function ($value) {
            return strpos($value, 'Module') !== false;
        });

        foreach ($loadedModules as $loadedModule) {
            $file = $loadedModule . DS . 'src' . DS . 'Lib' . DS . 'AngularAssets.php';
            if (file_exists($file)) {
                require_once $file;
                $moduleNameArray = explode('/', $loadedModule);
                $moduleName = array_pop($moduleNameArray);
                $dynamicAngularAssets = sprintf('itnovum\openITCOCKPIT\%s\AngularAssets\AngularAssets', $moduleName);
                $ModuleAngularAssets = new $dynamicAngularAssets();
                /** @var AngularAssetsInterface $ModuleAngularAssets */
                foreach ($ModuleAngularAssets->getCssFiles() as $cssFile) {
                    $cssFiles[] = $cssFile;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getNodeCssFiles() {
        return $this->nodeCssFiles;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getCssFilesOnDisk() {
        $cssFiles = [];
        foreach ($this->cssFiles as $cssFile) {
            if (substr($cssFile, 0, 1) === '/') {
                //Remove leading / from path
                $cssFile = substr($cssFile, 1);
            }

            $cssFiles[] = WWW_ROOT . $cssFile;
        }

        //Load Plugin configuration files
        $Folder = new Folder(PLUGIN);
        $folders = $Folder->subdirectories();

        $loadedModules = array_filter($folders, function ($value) {
            return strpos($value, 'Module') !== false;
        });

        foreach ($loadedModules as $loadedModule) {
            $file = $loadedModule . DS . 'src' . DS . 'Lib' . DS . 'AngularAssets.php';
            if (file_exists($file)) {
                require_once $file;
                $moduleNameArray = explode('/', $loadedModule);
                $moduleName = array_pop($moduleNameArray);
                $dynamicAngularAssets = sprintf('itnovum\openITCOCKPIT\%s\AngularAssets\AngularAssets', $moduleName);
                $ModuleAngularAssets = new $dynamicAngularAssets();
                /** @var AngularAssetsInterface $ModuleAngularAssets */
                foreach ($ModuleAngularAssets->getCssFilesOnDisk() as $cssFile) {
                    $cssFiles[] = $cssFile;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getNodeCssFilesOnDisk() {
        $cssFiles = [];
        foreach ($this->nodeCssFiles as $cssFile) {
            if (substr($cssFile, 0, 1) === '/') {
                //Remove leading / from path
                $cssFile = substr($cssFile, 1);
            }

            $cssFiles[] = WWW_ROOT . $cssFile;
        }
        return $cssFiles;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getNodeJsFiles() {
        $jsFiles = [];
        foreach ($this->jsFiles as $jsFile) {
            if (substr($jsFile, 0, 1) === '/') {
                //Remove leading / from path
                $jsFile = substr($jsFile, 1);
            }

            if (substr($jsFile, 0, 12) === 'node_modules') {
                $jsFiles[] = $jsFile;
            }
        }
        return $jsFiles;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getJsFilesOnDisk() {
        // TODO: Implement getJsFilesOnDisk() method.
    }

    public function getPluginNgStateJsFiles() {
        $ngStateJsFiles = [];

        $Folder = new Folder(PLUGIN);
        $folders = $Folder->subdirectories();
        $loadedModules = array_filter($folders, function ($value) {
            return strpos($value, 'Module') !== false;
        });

        foreach ($loadedModules as $loadedModule) {
            //Also include ng.stats.js of the Plugin
            $ngStateJs = $loadedModule . DS . 'webroot' . DS . 'js' . DS . 'scripts' . DS . 'ng.states.js';
            if (file_exists($ngStateJs)) {
                $jsFiles[] = $ngStateJs;
            }
        }

        return $jsFiles;
    }
}
