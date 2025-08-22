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
 * @var array $all_services
 * @var \itnovum\openITCOCKPIT\Core\Views\UserTime $UserTime
 *
 */

use itnovum\openITCOCKPIT\Core\Views\Logo;


$css = \App\itnovum\openITCOCKPIT\Core\AngularJS\PdfAssets::getCssFiles();
$Logo = new Logo();
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
    foreach ($css as $cssFile): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo WWW_ROOT . $cssFile; ?>"/>
    <?php endforeach; ?>
</head>
<body>
<div class="container-fluid">

    <div class="row">
        <div class="col-6">
            <h6>
                <i class="fa-solid fa-file-invoice"></i>
                <?php echo __('Current state report '); ?>
            </h6>
        </div>
        <div class="col-6 text-end">
            <img src="<?php echo $Logo->getLogoPdfPath(); ?>" width="200"/>
        </div>
    </div>
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-calendar"></i>
            <?php echo h('(' . __('Date: ') . $UserTime->format(time()) . ')'); ?>
        </div>
    </div>


    <?php
    if (sizeof($all_services) > 0):
        foreach ($all_services as $hostId => $currentStateObjectData):
            if (!empty($currentStateObjectData['Services'])):?>
                <div class="pdf-card">
                    <div class="pdf-card-header">
                        <h6>
                            <strong class="<?= $currentStateObjectData['Hoststatus']['humanState']; ?>">
                                <i class="fa-solid fa-desktop <?= $currentStateObjectData['Hoststatus']['humanState']; ?>"></i>
                                <?= h($currentStateObjectData['Host']['hostname']); ?>
                            </strong>
                        </h6>
                    </div>
                    <div class="pdf-card-body pb-1">
                        <div class="row">
                            <div class="col-3 ">
                                <?= __('Description'); ?>
                            </div>
                            <div class="col-9">
                                <?= h(($currentStateObjectData['Host']['description']) ? $currentStateObjectData['Host']['description'] : ' - '); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <?= __('IP address'); ?>
                            </div>
                            <div class="col-9">
                                <?= h($currentStateObjectData['Host']['address']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <?= __('Status'); ?>
                            </div>
                            <div class="col-9">
                                <?= h($currentStateObjectData['Hoststatus']['humanState']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <?= __('Status since'); ?>
                            </div>
                            <div class="col-9">
                                <?= h($currentStateObjectData['Hoststatus']['lastCheck']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <?= __('Host output'); ?>
                            </div>
                            <div class="col-9">
                                <?= h($currentStateObjectData['Hoststatus']['output']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 py-2 bold">
                                <i class="fa-solid fa-gears "></i>
                                <?= __('Checks'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?php
                                foreach ($currentStateObjectData['Services'] as $serviceData): ?>
                                    <div class="row p-0 pt-1" style="border-bottom: 1px solid #e1e1e1;">
                                        <div class="col-3 wrap">
                                            <i class="fa-solid fa-square <?= h($serviceData['Servicestatus']['textClass']); ?>"> </i>
                                            <?= h($serviceData['Service']['servicename']); ?>
                                        </div>
                                        <div class="col-2">
                                            <?= h($serviceData['Servicestatus']['lastCheck']); ?>
                                        </div>
                                        <div class="col-5">
                                            <?= h($serviceData['Servicestatus']['output']); ?>
                                        </div>
                                        <div class="col-2">
                                            <?php foreach ($serviceData['Servicestatus']['perfdataArray'] as $label => $gauge): ?>
                                                <div class="col-12 text-center">
                                                    <?= h($label); ?>
                                                </div>
                                                <div
                                                    class="col-12 text-center bordered <?= h($serviceData['Servicestatus']['cssClass']); ?>">
                                                    <strong class="txt-color-white" style="font-size: 65%!important;">
                                                        <?= h($gauge['current']) . ' ' . h($gauge['unit']) ?>
                                                    </strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php
                                endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endif;
        endforeach;
    endif; ?>

    <?php if (empty($all_services)): ?>
        <div class="w-100 text-center text-danger italic pt-1">
            <?php echo __('No entries match the selection'); ?>
        </div>
    <?php endif; ?>

</div>
</body>
