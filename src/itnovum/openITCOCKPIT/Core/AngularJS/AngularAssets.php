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

class AngularAssets implements AngularAssetsInterface {

    /**
     * @var array
     */
    protected $jsFiles = [
        'node_modules/jquery/dist/jquery.min.js',
        'node_modules/jquery-ui-dist/jquery-ui.min.js',
        'node_modules/popper.js/dist/umd/popper.min.js',
        'node_modules/popper.js/dist/umd/popper-utils.min.js',
        'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
        'js/lib/throttle-debounce/jquery.ba-throttle-debounce.min.js',
        'js/jquery-snippets.js',
        'node_modules/jquery-slimscroll/jquery.slimscroll.js',
        'node_modules/node-waves/dist/waves.js',
        'node_modules/angular/angular.min.js',
        'node_modules/angular-ui-router/release/angular-ui-router.min.js',
        'node_modules/angular-ui-router/release/stateEvents.min.js',
        'node_modules/jquery.cookie/jquery.cookie.js',
        'js/lib/chosen.jquery.min.js',
        'node_modules/bootstrap4-tagsinput/tagsinput.js',
        'js/lib/layoutfix.js',
        'js/lib/ColorGenerator.js',
        'js/lib/colr.js',
        'js/lib/php.js',
        'js/lib/jquery.flot.cust.js', //legacy but no new alternative
        'node_modules/flot-axislabels/jquery.flot.axislabels.js',
        'node_modules/flot/jquery.flot.time.js',
        'node_modules/flot/jquery.flot.fillbetween.js',
        'node_modules/flot/jquery.flot.resize.js',
        'node_modules/flot/jquery.flot.threshold.js',
        'node_modules/flot/jquery.flot.selection.js',
        'js/lib/jquery.flot.curvedLines.js',
        //'node_modules/jquery-nestable/jquery.nestable.js',
        //webroot/legacy/smartadmin/js/plugin/jquery-nestable/jquery.nestable.js
        'js/lib/jquery-nestable/jquery.nestable.js',
        'js/lib/angular-nestable.js',
        'js/lib/parseuri.js',
        'node_modules/moment/min/moment.min.js',
        'node_modules/vis-util/peer/umd/vis-util.min.js',
        'node_modules/vis-data/peer/umd/vis-data.min.js',
        'node_modules/vis-network/peer/umd/vis-network.min.js',
        //'node_modules/vis-timeline/peer/umd/vis-timeline-graph2d.min.js',
        'node_modules/vis-timeline/standalone/umd/vis-timeline-graph2d.min.js',
        'js/scripts/ng.app.js',
        'js/lib/UUID.js-4.0.3/dist/uuid.core.js',
        'node_modules/underscore/underscore-min.js',
        'node_modules/noty/lib/noty.min.js',
        'js/lib/gauge/gauge.min.js',
        'js/lib/jquery.svg.min.js',
        'js/lib/jquery.svgfilter.min.js',
        'node_modules/dropzone/dist/min/dropzone.min.js',
        'node_modules/rangyinputs/rangyinputs-jquery.js',
        'node_modules/angular-gridster/dist/angular-gridster.min.js',
        'js/lib/GraphDefaults.js',
        'js/lib/uPlotGraphDefaults.js',
        'js/lib/jqconsole.min.js',
        'node_modules/jquery-blockui/jquery.blockUI.js',
        'js/lib/jquery-jvectormap-1.2.2.min.js',
        'js/lib/maps/jquery-jvectormap-world-mill-en.js',
        'node_modules/@fullcalendar/core/main.js',
        'node_modules/@fullcalendar/interaction/main.js',
        'node_modules/@fullcalendar/daygrid/main.js',
        'node_modules/@fullcalendar/timegrid/main.js',
        'node_modules/@fullcalendar/list/main.js',
        'node_modules/@fullcalendar/bootstrap/main.js',
        'node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.js',
        'node_modules/spectrum-colorpicker2/dist/spectrum.min.js',
        'node_modules/chart.js/dist/Chart.min.js',
        'js/lib/highlight-within-textarea/jquery.highlight-within-textarea.js',
        'node_modules/jquery-sparkline/jquery.sparkline.min.js',
        'js/lib/raphael-charts-cust/raphael.js',
        'js/lib/raphael-charts-cust/raphael-charts-0.2.1.js',
        'js/lib/raphael-charts-cust/g.raphael.js',
        'js/lib/raphael-charts-cust/g.bar.js',
        'js/lib/raphael-charts-cust/pielicious.js',
        'node_modules/file-saver/dist/FileSaver.min.js',
        'node_modules/canvas-gauges/gauge.min.js',
        'node_modules/tsparticles/tsparticles.min.js',
        'node_modules/bootstrap4-toggle/js/bootstrap4-toggle.min.js',
        'node_modules/lodash/lodash.min.js',
        'node_modules/luxon/build/global/luxon.min.js',
        'node_modules/uplot/dist/uPlot.iife.min.js',
        'node_modules/chartjs-plugin-annotation/chartjs-plugin-annotation.min.js',
        'node_modules/jsoneditor/dist/jsoneditor.min.js'
    ];

    /**
     * @var array
     * Gets added before self::cssFiles in the HTML document
     */
    protected $nodeCssFiles = [
        '/node_modules/jquery-ui-dist/jquery-ui.css',
        '/node_modules/node-waves/dist/waves.css',
        '/node_modules/bootstrap4-tagsinput/tagsinput.css',
        '/node_modules/bootstrap4c-chosen/dist/css/component-chosen.css',
        '/node_modules/dropzone/dist/min/dropzone.min.css',
        '/node_modules/font-awesome/css/font-awesome.css',
        '/node_modules/@fortawesome/fontawesome-free/css/all.min.css',
        '/node_modules/@fullcalendar/core/main.css',
        '/node_modules/@fullcalendar/daygrid/main.css',
        '/node_modules/@fullcalendar/timegrid/main.css',
        '/node_modules/@fullcalendar/list/main.css',
        '/node_modules/@fullcalendar/bootstrap/main.css',
        '/node_modules/flag-icon-css/css/flag-icon.css',
        '/node_modules/animate.css/animate.min.css',
        '/node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.css',
        '/node_modules/spectrum-colorpicker2/dist/spectrum.min.css',
        '/node_modules/vis-network/dist/dist/vis-network.min.css',
        '/node_modules/vis-timeline/dist/vis-timeline-graph2d.css',
        '/node_modules/noty/lib/noty.css',
        '/node_modules/angular-flippy/dist/css/angular-flippy.min.css',
        '/node_modules/angular-gridster/dist/angular-gridster.min.css',
        '/node_modules/font-logos/assets/font-logos.css',
        '/node_modules/bootstrap4-toggle/css/bootstrap4-toggle.min.css',
        '/node_modules/uplot/dist/uPlot.min.css',
        '/node_modules/jsoneditor/dist/jsoneditor.min.css'
    ];

    /**
     * @var array
     */
    protected $cssFiles = [
        '/css/roboto.css',
        '/css/timeline.css',
        '/css/changelog.css',
        '/smartadmin4/dist/css/vendors.bundle.css',
        '/smartadmin4/dist/css/app.bundle.css',
        '/smartadmin4/dist/css/themes/cust-theme-10.css',
        '/css/lib/jquery-jvectormap-1.2.2.css',
        '/css/console.css',
        '/css/lib/ansi.css',
        '/css/lib/image-picker.css',
        '/js/lib/highlight-within-textarea/jquery.highlight-within-textarea.css',
        '/css/openitcockpit-colors.css',
        '/css/openitcockpit-utils.css',
        '/css/openitcockpit.css',
        '/css/openitcockpit-menu.css',
        '/css/nestable.css',
        '/css/openitcockpit-vis.css',
        '/css/thresholds.css'
    ];

    /**
     * @return array
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

    public function getNodeCssFiles() {
        return $this->nodeCssFiles;
    }

    /**
     * @return array
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
