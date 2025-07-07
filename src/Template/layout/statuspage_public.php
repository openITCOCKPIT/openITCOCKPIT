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

/**
 * @var \App\View\AppView $this
 */

use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 * @var int $id
 * @var string $systemname
 * @var array $statuspage
 */

$logo = new Logo();
?>

<!DOCTYPE html>
<html>
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

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta name="theme-color" content="#4085c6">

    <link rel="stylesheet" type="text/css" href="/node_modules/font-awesome/css/font-awesome.min.css">

    <link rel="stylesheet" type="text/css" href="/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/dist/compressed_app.css">
    <link rel="stylesheet" type="text/css" href="/smartadmin4/dist/css/themes/cust-theme-10.css">
    <link rel="stylesheet" type="text/css" href="/css/openitcockpit-colors.css">
    <link rel="stylesheet" type="text/css" href="/css/openitcockpit-utils.css">
    <link rel="stylesheet" type="text/css" href="/css/openitcockpit.css">

    <link rel="stylesheet" type="text/css" href="/css/statuspage.css">

    <title>
        <?= __('Statuspage') ?>
        -
        <?= h($statuspage['statuspage']['name']); ?>
    </title>

    <!-- FAVICONS -->
    <link rel="shortcut icon" type="image/x-icon; charset=binary" href="/img/favicons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicons/favicon-16x16.png">
    <link rel="manifest" href="/img/favicons/site.webmanifest">

</head>
<body class="mod-bg-1 mod-nav-link desktop pace-done nav-function-top blur dark-mode-body">

<header class="page-header" role="banner">
    <!-- we need this logo when user switches to nav-function-top -->
    <div class="page-logo">
        <a href="<?= $this->Html->Url->build(['controller' => 'Statuspages', 'action' => 'publicView', $id]); ?>"
           class="page-logo-link d-flex align-items-center position-relative">
            <img src="<?= $logo->getHeaderLogoForHtml(); ?>" alt="<?= h($systemname); ?> WebApp"
                 aria-roledescription="logo">
            <span class="page-logo-text mr-1"><?= h($systemname); ?></span>
        </a>
    </div>

    <!-- DOC: mobile button appears during mobile width -->
    <div class="hidden-lg-up">
        <a href="<?= $this->Html->Url->build(['controller' => 'Statuspages', 'action' => 'publicView', $id]); ?>"
           class="page-logo-link d-flex align-items-center position-relative">
            <img src="<?= $logo->getHeaderLogoForHtml(); ?>" alt="<?= h($systemname); ?> WebApp"
                 aria-roledescription="logo">
            <span class="page-logo-text mr-1 text-dark"><?= h($systemname); ?></span>
        </a>
    </div>

    <div class="ml-auto d-flex">

        <?php /*
        // This is here as reference if we want to add some icons to the main menu bar in the future
        <!-- app settings -->
        <div class="hidden-md-down">
            <a href="#" class="header-icon">
                <i class="fa fa-cogs"></i>
            </a>
        </div>
        */ ?>

    </div>
</header>
<div class="page-content-margin">
    <main role="main">
        <?= $this->fetch('content') ?>
    </main>
</div>

<?php /*
 // At the moment we do not need any JavaScript on this page
<script src="/node_modules/jquery/dist/jquery.min.js"></script>
*/ ?>

</body>
</html>
