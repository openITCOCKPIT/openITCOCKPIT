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
?>
<div class="row">
    <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
        <h1 class="page-title txt-color-blueDark">
            <i class="fa <?= $options['icon'] ?> fa-fw "></i>
            <?php echo __('Error'); ?>
            <span>>
                <?php echo __('403'); ?>
            </span>
        </h1>
    </div>
</div>

<div class="jarviswidget jarviswidget-color-redLight" id="wid-id-0">
    <header>
        <span class="widget-icon"> <i class="fa <?= $options['icon'] ?>"></i> </span>
        <h2><?php echo $options['headline']; ?></h2>
    </header>
    <div>
        <div class="widget-body text-center padding-top-20">
            <i class="fa fa-ban text-danger"></i> <?php echo $options['error']; ?>
            <br/>
            <br/>
            <a href="/"
               class="btn btn-default btn-lg padding-left-20 padding-right-20"><?=  __('Back') ?></a>
        </div>
    </div>
</div>
