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


use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 */


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

    <link rel="stylesheet" type="text/css" href="/css/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="/css/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/css/coreui/coreui.css">
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

<body class="dark-mode-body">
<main role="main">
    <?= $this->fetch('content') ?>
</main>
</body>

</html>
