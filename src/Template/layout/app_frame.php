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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

/**
 * @var \App\View\AppView $this
 * @var string $systemname ;
 * @var string $userFullName
 * @var string $userImage
 */

use App\Lib\Environments;
use Cake\Core\Configure;
use itnovum\openITCOCKPIT\CakePHP\Folder;
use itnovum\openITCOCKPIT\Core\AngularJS\AngularAssets;

$AngularAssets = new AngularAssets();
$scripts = $AngularAssets->getJsFiles();

$appScripts = [];
if (ENVIRONMENT === Environments::PRODUCTION) {
    $compressedAngularControllers = WWW_ROOT . 'dist' . DS . 'compressed_angular_controllers.js';
    $compressedAngularDirectives = WWW_ROOT . 'dist' . DS . 'compressed_angular_directives.js';
    $compressedAngularServices = WWW_ROOT . 'dist' . DS . 'compressed_angular_services.js';
    $compressedAngularStates = WWW_ROOT . 'dist' . DS . 'compressed_angular_states.js';
    if (file_exists($compressedAngularControllers) && file_exists($compressedAngularDirectives) && file_exists($compressedAngularServices)) {
        $appScripts[] = str_replace(WWW_ROOT, '', $compressedAngularServices);
        $appScripts[] = str_replace(WWW_ROOT, '', $compressedAngularDirectives);
        $appScripts[] = str_replace(WWW_ROOT, '', $compressedAngularControllers);
        if (file_exists($compressedAngularStates)) {
            $appScripts[] = str_replace(WWW_ROOT, '', $compressedAngularStates);
        }
    }
} else {
    $core = new Folder(WWW_ROOT . 'js' . DS . 'scripts');
    $uncompressedAngular = str_replace(WWW_ROOT, '', $core->findRecursive('.*\.js'));
    foreach (\Cake\Core\Plugin::loaded() as $pluginName) {
        $plugin = new Folder(PLUGIN . $pluginName . DS . 'webroot' . DS . 'js' . DS . 'scripts');
        $filenames = str_replace(PLUGIN . $pluginName . DS . 'webroot' . DS, '', $plugin->findRecursive('.*\.js'));
        if (!empty($filenames)) {
            $fullPath = [];
            foreach ($filenames as $filename) {
                $fullPath[] = $pluginName . DS . $filename;
            }
            $uncompressedAngular = array_merge($uncompressedAngular, $fullPath);
        }
    }
    $appScripts = array_merge($appScripts, $uncompressedAngular);
}

?>
<!DOCTYPE html>
<html lang="en" ng-app="openITCOCKPIT">
<head>


    <!--[if IE]>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
    <?php echo $this->Html->charset(); ?>

    <!--
                                    ///
                          ///////////////////////
                      ///////////////////////////////
                    ///////////////////////////////////
                 /////////////////////////////////////////
                ///////////////////////////////////////////
               ////////////////   .////////////////////    ,
              //////////                  //////             .
             ////////         //////        /         ///////.
             ///////       ///////////.   /////// ////////////
            ///////       /////////////      /   /////////////
            ///////       /////////////*  /* /   /////////////
            ///////       /////////////   /*     /////////////
            ////////        //////////    /*       //////////
            //////////                     //
            /////////////               ////////
            /////////////////////////////////////////////
            ///////////////////////////////////////////
            ////////////////////////////////////////
            ////////////////////////////////////

                    Open Source Monitoring Solution

        Website: https://openitcockpit.io/
        GitHub: https://github.com/openITCOCKPIT/openITCOCKPIT
    -->

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script data-pace-options='{ "ajax": false }' src='/js/lib/pace/pace.min.js'></script>
    <title>
        Monitoring powered by openITCOCKPIT
    </title>
    <?php
    $fileVersion = '?v' . time();
    if (ENVIRONMENT === Environments::PRODUCTION):
        Configure::load('version');
        $fileVersion = '?v' . OPENITCOCKPIT_VERSION;
    endif;
    ?>

    <!-- FAVICONS -->
    <link rel="shortcut icon" type="image/x-icon; charset=binary" href="/img/favicons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicons/favicon-16x16.png">
    <link rel="manifest" href="/img/favicons/site.webmanifest">

    <?php


    foreach ($scripts as $script):
        printf('<script src="/%s%s"></script>%s', $script, $fileVersion, PHP_EOL);
    endforeach;

    foreach ($appScripts as $appScript):
        printf('<script src="/%s%s"></script>%s', $appScript, $fileVersion, PHP_EOL);
    endforeach;

    if (ENVIRONMENT === Environments::PRODUCTION && file_exists(WWW_ROOT . 'dist' . DS . 'compressed_app.css')):
        foreach ($AngularAssets->getNodeCssFiles() as $cssFile):
            printf('<link rel="stylesheet" type="text/css" href="%s%s">%s', $cssFile, $fileVersion, PHP_EOL);
        endforeach;
        printf('<link rel="stylesheet" type="text/css" href="/dist/compressed_app.css?%s">%s', $fileVersion, PHP_EOL);

    else:
        //Dev mode - load all css files
        foreach ($AngularAssets->getNodeCssFiles() as $cssFile):
            printf('<link rel="stylesheet" type="text/css" href="%s%s">%s', $cssFile, $fileVersion, PHP_EOL);
        endforeach;

        foreach ($AngularAssets->getCssFiles() as $cssFile):
            printf('<link rel="stylesheet" type="text/css" href="%s%s">%s', $cssFile, $fileVersion, PHP_EOL);
        endforeach;
    endif;

    if (\Cake\Core\Plugin::isLoaded('DesignModule')) {
        //load custom design css file
        $customCss = PLUGIN . 'DesignModule' . DS . 'webroot' . DS . 'css' . DS . 'customStyle.css';
        if (file_exists($customCss)) {
            $customCss = 'design_module/css/customStyle.css';
            printf('<link rel="stylesheet" type="text/css" href="%s%s">%s', $customCss, $fileVersion, PHP_EOL);
        }
    }

    ?>
</head>
<body class="mod-bg-1">
<div class="page-wrapper">
    <div class="page-inner">
        <script>
            var classHolder = document.getElementsByTagName("BODY")[0]; // may delete this if navigation (plugin) changes
            Dropzone.autoDiscover = false;
        </script>

        <div id="global-loading">
            <i class="fas fa-spinner fa-spin"></i>
        </div>

        <sidebar class="page-sidebar" systemname="<?= $systemname; ?>" user-full-name="<?= h($userFullName); ?>"
                 user-image="<?= h($userImage); ?>"></sidebar>

        <div class="page-content-wrapper">
            <!-- HEADER START -->
            <?= $this->element('header') ?>
            <!-- HEADER END -->

            <div id="uglyDropdownMenuHack"></div>
            <!-- BEGIN Page Content -->
            <!-- the #js-page-content id is needed for some plugins to initialize -->
            <main id="js-page-content" role="main" class="page-content" ng-controller="LayoutController">
                <div id="content" style="opacity: 1;">

                    <div class="alert alert-danger" role="alert" style="display:none;"
                         id="globalSudoServerCouldNotConnect">
                        <div class="d-flex align-items-center">
                            <div class="alert-icon width-3">
                                <div class="icon-stack  icon-stack-sm">
                                    <i class="base base-9 icon-stack-3x opacity-100 text-danger"></i>
                                    <i class="fas fa-exclamation-circle icon-stack-1x opacity-100 color-white"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <span class="h5"> <?= __('Attention!') ?></span>
                                <?= __('Could not connect to SudoServer. External commands may not work. Please try to reload this page.'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger" role="alert" style="display:none;"
                         id="globalSudoServerLostConnection">
                        <div class="d-flex align-items-center">
                            <div class="alert-icon width-3">
                                <div class="icon-stack  icon-stack-sm">
                                    <i class="base base-9 icon-stack-3x opacity-100 text-danger"></i>
                                    <i class="fas fa-bolt icon-stack-1x opacity-100 color-white"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <span class="h5"> <?= __('Attention!') ?></span>
                                <?= __('Lost connection to SudoServer. External commands may not work. Please try to reload this page.'); ?>
                            </div>
                        </div>
                    </div>

                    <div ui-view>
                        <?= $this->Flash->render() ?>
                        <?= $this->fetch('content') ?>
                    </div>
                </div>
            </main>
            <!-- this overlay is activated only when mobile menu is triggered -->
            <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
        </div>
    </div>
</div>
<?= $this->element('shortcut_links') ?>
<?= $this->element('color_profiles') ?>
<?= $this->element('quick_menu') ?>
<?php printf('<script src="/%s"></script>', 'smartadmin4/dist/js/app.bundle.js'); ?>
</body>
</html>
