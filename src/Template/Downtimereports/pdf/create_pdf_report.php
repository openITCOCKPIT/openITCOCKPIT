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
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\MultipleBarChart;

/**
 * @var \App\View\AppView $this
 * @var array $downtimeReport
 * @var int $fromDate
 * @var int $toDate
 * @var \itnovum\openITCOCKPIT\Core\Views\UserTime $UserTime
 *
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
                <i class="fa-solid fa-power-off"></i>
                <?php echo __('Downtime Report'); ?>
            </h6>
        </div>
        <div class="col-6 text-end">
            <img src="<?php echo $Logo->getLogoPdfPath(); ?>" width="200"/>
        </div>
    </div>
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-calendar"></i>
            <?php echo __('Analysis period: '); ?>
            <?php echo h($UserTime->format($fromDate)); ?>
            <i class="fa-solid fa-long-arrow-alt-right"></i>
            <?php echo h($UserTime->format($toDate)); ?>
        </div>
    </div>

    <?php if (!empty($error['no_downtimes']['empty'])): ?>
        <div class="col-12 text-info italic mb-1">
            <?php echo $error['no_downtimes']['empty']; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($downtimeReport['hostsWithOutages'])): ?>
        <div class="pdf-card">
            <div class="pdf-card-header">
                <h6>
                    <span class="fa-stack">
                        <i class="fa-solid fa-check fa-stack-1x ok"></i>
                        <i class="fa-solid fa-ban fa-stack-2x critical opacity-50"></i>
                    </span>
                    <?= __('Involved in outages (Hosts):'); ?>
                </h6>
            </div>
            <div class="pdf-card-body pb-1">
                <?php
                foreach ($downtimeReport['hostsWithOutages'] as $chunkKey => $hostsWithOutages):
                    $MultipleBarChart = new MultipleBarChart();
                    $overview_chart = $MultipleBarChart->createBarChart(
                        Hash::combine($hostsWithOutages['hosts'], '{n}.Host.name', '{n}.Host.reportData')
                    ); ?>
                    <img src="<?= WWW_ROOT; ?>img/charts/<?= $overview_chart; ?>"/>
                    <?php
                    foreach ($hostsWithOutages['hosts'] as $hostWithOutages): ?>
                        <div class="pdf-card">
                            <div class="pdf-card-header">
                                <h6>
                                    <i class="fa fa-desktop"></i>
                                    <?= h($hostWithOutages['Host']['name']); ?>
                                </h6>
                            </div>
                            <div class="pdf-card-body">
                                <div class="row">
                                    <div class="col-3">
                                        <?= __('Description'); ?>
                                    </div>
                                    <div class="col-9">
                                        <?= h(($hostWithOutages['Host']['description']) ? $hostWithOutages['Host']['description'] : ' - '); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-3">
                                        <?= __('IP address'); ?>
                                    </div>
                                    <div class="col-9">
                                        <?= h($hostWithOutages['Host']['address']); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-3">
                                        <?= __('Status'); ?>
                                    </div>
                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                        <?php $HoststatusIcon = new \itnovum\openITCOCKPIT\Core\Views\HoststatusIcon($i); ?>
                                        <div
                                            class="col-3 <?= $HoststatusIcon->getBgColor() ?> downtime-report-state-overview p-1">
                                            <strong class="txt-color-white">
                                                <?= $hostWithOutages['pieChartData']['widgetOverview'][$i]['percent']; ?>
                                            </strong>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <div class="row">
                                    <div class="col-3">
                                        &nbsp;
                                    </div>
                                    <?php
                                    for ($i = 0; $i < 3; $i++):?>
                                        <?php $HoststatusIcon = new \itnovum\openITCOCKPIT\Core\Views\HoststatusIcon($i); ?>
                                        <div
                                            class="col-3 <?= $HoststatusIcon->getBgColor() ?> downtime-report-state-overview p-1">
                                            <strong class="txt-color-white">
                                                <?= $hostWithOutages['pieChartData']['widgetOverview'][$i]['human']; ?>
                                            </strong>
                                        </div>
                                    <?php
                                    endfor; ?>
                                </div>
                                <?php
                                if (isset($hostWithOutages['Services'])):
                                    $servicesWithOutages =
                                        Hash::extract(
                                            $hostWithOutages['Services'],
                                            '{s}.Service.reportData[2>0]'
                                        );
                                    if (!empty($servicesWithOutages)):?>
                                        <div class="row">
                                            <div class="col-9 py-1">
                                                <strong class="">
                                                    <?= __('Involved in outages (Services):'); ?>
                                                </strong>
                                            </div>
                                        </div>
                                    <?php
                                    endif;
                                    foreach ($hostWithOutages['Services'] as $uuid => $service):
                                        if ($service['Service']['reportData'][2] > 0):
                                            $serviceName = empty($service['Service']['name']) ?
                                                $service['Servicetemplate']['name'] :
                                                $service['Service']['name'];
                                            ?>
                                            <div class="row">
                                                <div class="col-6">
                                                    <i class="fa fa-cog"></i>
                                                    <?= h($serviceName); ?>
                                                </div>
                                                <div class="col-3 text-end ps-2 text-info">
                                                    <?= __('Servicetemplate'); ?>
                                                </div>
                                                <div class="col-3 text-info wrap">
                                                    <i class="fa-solid fa-edit"></i>
                                                    <?= h($service['Servicetemplate']['template_name']); ?>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-3 text-end pb-1">
                                                    <?php
                                                    $overview_chart = \itnovum\openITCOCKPIT\Core\Views\PieChart::createPieChartOnDisk([
                                                        $service['Service']['reportData'][0],
                                                        $service['Service']['reportData'][1],
                                                        $service['Service']['reportData'][2],
                                                        $service['Service']['reportData'][3]
                                                    ]);
                                                    ?>
                                                    <img src="<?= WWW_ROOT; ?>img/charts/<?= $overview_chart; ?>"
                                                         width="100"/>
                                                </div>
                                                <div class="col-9">
                                                    <?php for ($i = 0; $i <= 3; $i++):
                                                        $ServicestatusIcon = new \itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon($i); ?>
                                                        <div class="row">
                                                            <div class="col-4 text-end">
                                                                <em>
                                                                    <?= $ServicestatusIcon->getHumanState(); ?>
                                                                </em>
                                                            </div>
                                                            <div
                                                                class="col-4 <?= $ServicestatusIcon->getBgColor(); ?> downtime-report-state-overview">
                                                                <strong class="txt-color-white">
                                                                    <?= $service['pieChartData']['widgetOverview'][$i]['percent']; ?>
                                                                </strong>
                                                            </div>
                                                            <div
                                                                class="col-4 <?= $ServicestatusIcon->getBgColor(); ?> downtime-report-state-overview">
                                                                <strong class="txt-color-white">
                                                                    <?= $service['pieChartData']['widgetOverview'][$i]['human']; ?>
                                                                </strong>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    endfor; ?>
                                                </div>
                                            </div>
                                        <?php
                                        endif;
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php
                    endforeach;
                endforeach; ?>
            </div>
        </div>
    <?php
    endif;
    if (!empty($downtimeReport['hostsWithoutOutages']['hosts'])): ?>
        <div class="pdf-card">
            <div class="pdf-card-header">
                <h6>
                    <i class="fa-regular fa-check-square"></i>
                    <?= __(' Hosts without outages:'); ?>
                </h6>
            </div>
            <div class="pdf-card-body">
                <?php
                foreach ($downtimeReport['hostsWithoutOutages']['hosts'] as $hostWithoutOutages): ?>
                    <div class="pdf-card">
                        <div class="pdf-card-header">
                            <h6>
                                <i class="fa-solid fa-desktop"></i>
                                <?= h($hostWithoutOutages['Host']['name']); ?>
                            </h6>
                        </div>
                        <div class="pdf-card-body">
                            <div class="row">
                                <div class="col-3">
                                    <?= __('Description'); ?>
                                </div>
                                <div class="col-9">
                                    <?= h(($hostWithoutOutages['Host']['description']) ? $hostWithoutOutages['Host']['description'] : ' - '); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3">
                                    <?= __('IP address'); ?>
                                </div>
                                <div class="col-9">
                                    <?= h($hostWithoutOutages['Host']['address']); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-3">
                                    <?= __('Status'); ?>
                                </div>
                                <?php
                                for ($i = 0; $i < 3; $i++):?>
                                    <?php $HoststatusIcon = new \itnovum\openITCOCKPIT\Core\Views\HoststatusIcon($i); ?>
                                    <div
                                        class="col-3 <?= $HoststatusIcon->getBgColor() ?> downtime-report-state-overview p-1">
                                        <strong class="txt-color-white">
                                            <strong class="txt-color-white">
                                                <?= $hostWithoutOutages['pieChartData']['widgetOverview'][$i]['percent']; ?>
                                            </strong>
                                        </strong>
                                    </div>
                                <?php
                                endfor;
                                ?>
                            </div>
                            <div class="row">
                                <div class="col-3">
                                    &nbsp;
                                </div>
                                <?php
                                for ($i = 0; $i < 3; $i++):?>
                                    <?php $HoststatusIcon = new \itnovum\openITCOCKPIT\Core\Views\HoststatusIcon($i); ?>
                                    <div
                                        class="col-3 <?= $HoststatusIcon->getBgColor() ?> downtime-report-state-overview p-1">
                                        <strong class="txt-color-white">
                                            <?= $hostWithoutOutages['pieChartData']['widgetOverview'][$i]['human']; ?>
                                        </strong>
                                    </div>
                                <?php
                                endfor;
                                ?>
                            </div>

                            <?php
                            $servicesWithOutages = [];
                            if (isset($hostWithoutOutages['Services'])):
                                $servicesWithOutages =
                                    Hash::extract(
                                        $hostWithoutOutages['Services'],
                                        '{s}.Service.reportData[2>0]'
                                    );
                                if (!empty($servicesWithOutages)): ?>
                                    <div class="row">
                                        <div class="col-9 py-2">
                                            <strong>
                                                <?= __('Involved in outages (Services):'); ?>
                                            </strong>
                                        </div>
                                    </div>

                                <?php endif;
                                foreach ($hostWithoutOutages['Services'] as $uuid => $service):
                                    if ($service['Service']['reportData'][2] > 0):
                                        $serviceName = empty($service['Service']['name']) ?
                                            $service['Servicetemplate']['name'] :
                                            $service['Service']['name'];
                                        ?>
                                        <div class="row margin-top-20">
                                            <div class="col-6">
                                                <i class="fa fa-cog"></i>
                                                <?= h($serviceName); ?>
                                            </div>
                                            <div class="col-3 text-end ps-2 text-info">
                                                <?= __('Servicetemplate'); ?>
                                            </div>
                                            <div class="col-3 text-info wrap">
                                                <i class="fa-solid fa-edit"></i>
                                                <?= h($service['Servicetemplate']['template_name']); ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-3 text-end pb-1">
                                                <?php
                                                $overview_chart = \itnovum\openITCOCKPIT\Core\Views\PieChart::createPieChartOnDisk([
                                                    $service['Service']['reportData'][0],
                                                    $service['Service']['reportData'][1],
                                                    $service['Service']['reportData'][2],
                                                    $service['Service']['reportData'][3]
                                                ]);
                                                ?>
                                                <img src="<?= WWW_ROOT; ?>img/charts/<?= $overview_chart; ?>"
                                                     width="100"/>
                                            </div>
                                            <div class="col-9">
                                                <?php
                                                for ($i = 0; $i <= 3; $i++):?>
                                                    <?php $ServicestatusIcon = new \itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon($i); ?>
                                                    <div class="row">
                                                        <div class="col-4 text-end">
                                                            <em>
                                                                <?= $ServicestatusIcon->getHumanState() ?>
                                                            </em>
                                                        </div>
                                                        <div
                                                            class="col-4 <?= $ServicestatusIcon->getBgColor() ?> downtime-report-state-overview ">
                                                            <strong class="txt-color-white">
                                                                <?= $service['pieChartData']['widgetOverview'][$i]['percent']; ?>
                                                            </strong>
                                                        </div>
                                                        <div
                                                            class="col-4 <?= $ServicestatusIcon->getBgColor() ?> downtime-report-state-overview ">
                                                            <strong class="txt-color-white">
                                                                <?= $service['pieChartData']['widgetOverview'][$i]['human']; ?>
                                                            </strong>
                                                        </div>
                                                    </div>
                                                <?php endfor;
                                                ?>
                                            </div>
                                        </div>
                                    <?php
                                    endif;
                                endforeach;
                            endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>

