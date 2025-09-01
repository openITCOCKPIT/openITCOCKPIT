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

use itnovum\openITCOCKPIT\Core\Views\HoststatusIcon;
use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 * @var array $all_hosts
 * @var \itnovum\openITCOCKPIT\Core\ValueObjects\User $User
 */

$Logo = new Logo();
$css = \App\itnovum\openITCOCKPIT\Core\AngularJS\PdfAssets::getCssFiles();


$UserTime = $User->getUserTime();

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
                <i class="fas fa-desktop"></i>
                <?php echo __('Hosts Overview'); ?>
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
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Hosts: ' . sizeof($all_hosts)); ?>
        </div>
    </div>
    <div class="pt-3">
        <table class="table table-striped table-bordered table-sm m-0">
            <thead>
            <tr>
                <th class="width-50"><?php echo __('Status'); ?></th>
                <th class="no-sort text-center width-20"><i class="fa-solid fa-user"></i></th>
                <th class="no-sort text-center width-20"><i class="fa-solid fa-power-off"></i></th>
                <th><?php echo __('Host'); ?></th>
                <th class="width-160"><?php echo __('Last state change'); ?></th>
                <th class="width-160"><?php echo __('Last check'); ?></th>
                <th><?php echo __('Output'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($all_hosts as $host): ?>
                <?php
                /** @var \itnovum\openITCOCKPIT\Core\Hoststatus $Hoststatus */
                $Hoststatus = $host['Hoststatus'];
                $HoststatusIcon = new HoststatusIcon($Hoststatus->currentState());
                ?>

                <tr>
                    <td class="text-center">
                        <?php
                        if ($Hoststatus->isFlapping()):
                            echo $Hoststatus->getHostFlappingIconColored();
                        else:
                            echo $HoststatusIcon->getPdfIcon();
                        endif;
                        ?>
                    </td>
                    <td class="text-center">
                        <?php if ($Hoststatus->isAcknowledged()): ?>
                            <i class="fa-solid fa-user"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($Hoststatus->isInDowntime()): ?>
                            <i class="fa-solid fa-power-off"></i>
                        <?php endif; ?>
                    </td>
                    <td class="wrap">
                        <?= h($host['Host']['name']); ?>
                    </td>
                    <?php if ($Hoststatus->isInMonitoring()): ?>
                        <td>
                            <?= h($UserTime->format($Hoststatus->getLastStateChange())) ?>
                        </td>
                        <td>
                            <?= h($UserTime->format($Hoststatus->getLastCheck())) ?>
                        </td>
                        <td>
                            <?= h($Hoststatus->getOutput()) ?>
                        </td>
                    <?php else: ?>
                        <td><?php echo __('n/a'); ?></td>
                        <td><?php echo __('n/a'); ?></td>
                        <td><?php echo __('n/a'); ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($all_hosts)): ?>
            <div class="w-100 text-center text-danger italic pt-1">
                <?php echo __('No entries match the selection'); ?>
            </div>
        <?php endif; ?>

    </div>
</body>
