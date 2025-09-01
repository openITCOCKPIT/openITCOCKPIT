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
 * @var array $all_events
 * @var array $logTypes
 * @var array $typeTranslations
 * @var array $typeIconClasses
 */

$Logo = new Logo();
$css = \App\itnovum\openITCOCKPIT\Core\AngularJS\PdfAssets::getCssFiles();

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
                <i class="fa-solid fa-file-text"></i>
                <?php echo __('Event Logs Overview'); ?>
            </h6>
        </div>
        <div class="col-6 text-end">
            <img src="<?php echo $Logo->getLogoPdfPath(); ?>" width="200"/>
        </div>
    </div>
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-calendar"></i> <?php echo date('F d, Y H:i:s'); ?>
        </div>
    </div>
    <div class="col-12">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Events: ' . sizeof($all_events)); ?>
        </div>
    </div>
    <div class="padding-top-10">
        <table class="table table-striped table-bordered table-sm m-0">
            <thead>
            <tr>
                <th colspan="2">
                    <?php echo __('Event Type'); ?>
                </th>
                <th>
                    <?php echo __('Name'); ?>
                </th>
                <?php if (in_array('login', $logTypes) || in_array('user_delete', $logTypes) || in_array('user_password_change', $logTypes)): ?>
                    <th>
                        <?php echo __('Email'); ?>
                    </th>
                <?php endif; ?>
                <th>
                    <?php echo __('Date'); ?>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($all_events as $event): ?>
                <tr>
                    <td>
                        <i class="<?= h($typeIconClasses[$event['type']]['iconPdf']); ?> <?= h($typeIconClasses[$event['type']]['className']); ?>"
                           title="<?= h($typeTranslations[$event['type']]); ?>">
                    </td>
                    <td>
                        <?= h($typeTranslations[$event['type']]); ?>
                    </td>
                    <td class="wrap">
                        <?php if ($event['recordExists']): ?>
                            <?= h($event['name']); ?>
                        <?php else: ?>
                            <s><?= h($event['name']); ?></s>
                        <?php endif; ?>
                    </td>
                    <?php if (in_array('login', $logTypes) || in_array('user_delete', $logTypes) || in_array('user_password_change', $logTypes)): ?>
                        <td>
                            <?php if ($event['recordExists']): ?>
                                <?= h($event['user_email']); ?>
                            <?php else: ?>
                                <s><?= h($event['data']['user_email']); ?></s>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td>
                        <?= h($event['time']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($all_events)): ?>
            <div class="w-100 text-center text-danger italic pt-1">
                <?php echo __('No entries match the selection'); ?>
            </div>
        <?php endif; ?>

    </div>
</div>
</body>
