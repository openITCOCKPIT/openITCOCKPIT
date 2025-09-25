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
 * @var array $all_hostgroups
 * @var int $numberOfHostgroups
 * @var int $numberOfHosts
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
                <i class="fa-solid fa-sitemap"></i>
                <?php echo __('Hostgroups Overview'); ?>
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
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Hostgroups: ' . $numberOfHostgroups); ?>
        </div>
    </div>
    <div class="col-12">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Hosts: ' . $numberOfHosts); ?>
        </div>
    </div>
    <div class="padding-top-10">
        <table class="table table-striped table-bordered table-sm m-0">
            <thead>
            <tr>
                <th class="width-20"><?php echo __('Status'); ?></th>
                <th class="no-sort text-center width-20"><i class="fa-solid fa-user"></i></th>
                <th class="no-sort text-center width-20"><i class="fa-solid fa-power-off"></i></th>
                <th><?php echo __('Host'); ?></th>
                <th class="width-90"><?php echo __('Status since'); ?></th>
                <th class="width-90"><?php echo __('Last check'); ?></th>
                <th class="width-90"><?php echo __('Next check'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($hostgroups as $hostgroup): ?>
                <!-- Hostgroup -->
                <tr>
                    <td class="table-secondary wrap" colspan="8">
                        <span><?php echo h($hostgroup['Hostgroup']['container']['name']); ?></span>
                    </td>
                </tr>
                <?php if (!empty($hostgroup['Hosts'])): ?>
                    <?php foreach ($hostgroup['Hosts'] as $host): ?>
                        <?php
                        if (isset($hostgroup['Hoststatus'][$host['Host']['uuid']]['Hoststatus'])):
                            $Hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus(
                                $hostgroup['Hoststatus'][$host['Host']['uuid']]['Hoststatus']
                            );
                        else:
                            $Hoststatus = new \itnovum\openITCOCKPIT\Core\Hoststatus([]);
                        endif;

                        $HoststatusIcon = new HoststatusIcon($Hoststatus->currentState());
                        $HoststatusIcon->getTextColor()
                        ?>

                        <tr>
                            <!-- status -->
                            <td class="text-center">
                                <?php
                                if ($Hoststatus->isFlapping()):
                                    $Hoststatus->getFlappingIconColored();
                                else:
                                    echo '<i class="fa-solid fa-square ' . $HoststatusIcon->getTextColor() . '"></i>';
                                endif;
                                ?>
                            </td>
                            <!-- ACK -->
                            <td class="text-center">
                                <?php if ($Hoststatus->isAcknowledged()): ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </td>
                            <!-- Downtime -->
                            <td class="text-center">
                                <?php if ($Hoststatus->isInDowntime()): ?>
                                    <i class="fa-solid fa-power-off"></i>
                                <?php endif; ?>
                            </td>
                            <!-- Host -->
                            <td class="wrap"><?php echo h($host['Host']['name']); ?></td>
                            <!-- status since -->
                            <td>
                                <?php echo $UserTime->format($Hoststatus->getLastStateChange()); ?>
                            </td>
                            <!-- last check -->
                            <td>
                                <?php echo $UserTime->format($Hoststatus->getLastCheck()); ?>
                            </td>
                            <!-- next check -->
                            <td><?php echo $UserTime->format($Hoststatus->getNextCheck()); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td class="text-center"
                            colspan="8"><?php echo __('This host group has no hosts'); ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($hostgroups)): ?>
            <div class="w-100 text-center text-danger italic pt-1">
                <?php echo __('No entries match the selection'); ?>
            </div>
        <?php endif; ?>

    </div>
</div>
</body>
