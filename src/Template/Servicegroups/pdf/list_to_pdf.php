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
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;

/**
 * @var \App\View\AppView $this
 * @var array $servicegroups
 * @var int $numberOfServicegroups
 * @var int $numberOfServices
 * @var int $numberOfHosts
 * @var \itnovum\openITCOCKPIT\Core\ValueObjects\User $User
 */

$Logo = new Logo();
$css = \App\itnovum\openITCOCKPIT\Core\AngularJS\PdfAssets::getCssFiles();

/** @var \itnovum\openITCOCKPIT\Core\ValueObjects\User $User */
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
                <i class="fa-solid fa-cogs"></i>
                <?php echo __('Service groups Overview'); ?>
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
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Servicegroups: ' . $numberOfServicegroups); ?>
        </div>
    </div>
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Hosts: ' . $numberOfHosts); ?>
        </div>
    </div>
    <div class="col-12">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Services: ' . $numberOfServices); ?>
        </div>
    </div>

    <div class="padding-top-10">
        <table class="table table-striped table-bordered table-sm m-0">
            <thead>
            <tr>
                <th><?php echo __('Status'); ?></th>
                <th class="no-sort text-center width-20"><i class="fa-solid fa-user"></i></th>
                <th class="no-sort text-center width-20"><i class="fa-solid fa-power-off"></i></th>
                <th><?php echo __('Service name'); ?></th>
                <th class="width-90"><?php echo __('Status since'); ?></th>
                <th class="width-90"><?php echo __('Last check'); ?></th>
                <th class="width-90"><?php echo __('Next check'); ?></th>
                <th><?php echo __('Service output'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($servicegroups as $servicegroup): ?>
                <!-- Servicegroup -->
                <tr>
                    <td class="table-secondary wrap" colspan="8">
                        <i class="fa-solid fa-cogs"></i>
                        <?php echo __('Service group: '); ?>
                        <?php echo h($servicegroup['Servicegroup']['container']['name']); ?>
                    </td>
                </tr>
                <?php
                $tmpHostName = null;
                if (!empty($servicegroup['Services'])):
                    foreach ($servicegroup['Services'] as $service):
                        $Service = new \itnovum\openITCOCKPIT\Core\Views\Service($service);
                        $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus($service['Servicestatus'], $UserTime);

                        if ($tmpHostName !== $Service->getHostname()):
                            $tmpHostName = $Service->getHostname(); ?>
                            <!-- Host -->
                            <tr>
                                <td class="table-secondary wrap" colspan="8">
                                    <?php
                                    $Hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus($service['Hoststatus']);
                                    if ($Hoststatus->isFlapping()):
                                        echo $Hoststatus->getFlappingIconColored();
                                    else:
                                        $HoststatusIcon = new HoststatusIcon($Hoststatus->currentState());
                                        echo '<i class="fa-solid fa-square ' . $HoststatusIcon->getTextColor() . '"></i>';
                                    endif;
                                    ?>
                                    <span><?php echo h($Service->getHostname()); ?></span>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <!-- Status -->
                        <tr>
                            <!-- status -->
                            <td class="text-center">
                                <?php
                                if ($Servicestatus->isFlapping()):
                                    echo $Servicestatus->getFlappingIconColored();
                                else:
                                    $ServicestatusIcon = new ServicestatusIcon($Servicestatus->currentState());
                                    echo '<i class="fa-solid fa-square ' . $ServicestatusIcon->getTextColor() . '"></i>';
                                endif;
                                ?>
                            </td>
                            <!-- ACK -->
                            <td class="text-center">
                                <?php if ($Servicestatus->isAcknowledged()): ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </td>
                            <!-- Downtime -->
                            <td class="text-center">
                                <?php if ($Servicestatus->isInDowntime()): ?>
                                    <i class="fa-solid fa-power-off"></i>
                                <?php endif; ?>
                            </td>
                            <!-- name -->
                            <td class="wrap">
                                <?php echo h($Service->getServicename()); ?>
                            </td>
                            <!-- Status Since -->
                            <td>
                                <?php echo h($UserTime->format($Servicestatus->getLastStateChange())); ?>
                            </td>
                            <!-- last check -->
                            <td>
                                <?php echo h($UserTime->format($Servicestatus->getLastCheck())); ?>
                            </td>
                            <!-- next check -->
                            <td>
                                <?php echo h($UserTime->format($Servicestatus->getNextCheck())); ?>
                            </td>
                            <!-- Service output -->
                            <td><?php echo h($Servicestatus->getOutput()); ?></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td class="text-center"
                            colspan="8"><?php echo __('There are no services defined'); ?></td>
                    </tr>
                <?php
                endif;
            endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($servicegroups)): ?>
            <div class="w-100 text-center text-danger italic pt-1">
                <?php echo __('No entries match the selection'); ?>
            </div>
        <?php endif; ?>

    </div>
</div>
</body>
