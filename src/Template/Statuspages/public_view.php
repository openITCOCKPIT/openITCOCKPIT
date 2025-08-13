<?php
// Copyright (C) <2015>  <it-novum GmbH>
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
//	confirmation.;
use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 * @var array $statuspage
 * @var int $id
 */

$logo = new Logo();
?>
<div class="container">
    <header class="header ps-0">
        <a class="header-brand"
           href="<?= $this->Html->Url->build(['controller' => 'Statuspages', 'action' => 'publicView', $id]); ?>">
            <img src="<?= $logo->getHeaderLogoForHtml(); ?>" alt="<?= h($systemname); ?> Public statuspage"
                 class="logo-public">
            <?php if (!empty($statuspage['statuspage']['public_title'])): ?>
                <span class="text-header "><?= h($statuspage['statuspage']['public_title']); ?></span>
            <?php else: ?>
                <span class="text-header"><?= h($systemname); ?></span>
            <?php endif; ?>
        </a>
    </header>

    <div class="row">
        <div class="m-0 w-100">
            <!-- Statuspage over all status -->
            <div>
                <div class="p-0">
                    <div class="col-12 pt-2 pb-4">

                        <div class="row pb-3">
                            <?php if ($logo->isCustomStatusPageHeader()): ?>
                                <img src="<?= $logo->getCustomStatusPageHeaderHtml(); ?>"
                                     alt="<?= h($systemname); ?> WebApp"
                                     class="img-responsive"
                                     aria-roledescription="image">
                            <?php endif; ?>
                        </div>

                        <h4 class="d-block l-h-n m-0">
                            <?= h($statuspage['statuspage']['name']); ?>
                        </h4>
                        <div class="m-0 l-h-n">
                            <?= h($statuspage['statuspage']['description']); ?>
                        </div>
                    </div>

                    <div
                        class="p-3 bg-<?= h($statuspage['statuspage']['cumulatedColor']); ?> rounded overflow-hidden position-relative text-white">
                        <div>
                            <h5 class="d-block l-h-n m-0 fw-500">
                                <?= h($statuspage['statuspage']['cumulatedHumanStatus']); ?>
                            </h5>
                        </div>
                        <i class="<?= h($statuspage['statuspage']['cumulatedIcon']); ?> statuspage-icon position-absolute pos-right pos-bottom opacity-15 pe-1"></i>
                    </div>
                </div>
            </div>
            <!-- end overall status -->


            <div class="my-3">
                <?php foreach ($statuspage['items'] as $item): ?>
                    <div class="p-0">
                        <!-- Status page object card -->
                        <div class="card d-flex flex-row min-h-110 mb-2">
                            <div class="p-2">
                                <div
                                    class="h-100 status-line bg-<?= h($item['cumulatedColor']); ?> shadow-<?= h($item['cumulatedColor']); ?>"></div>
                            </div>
                            <div class="flex-1">
                                <div class="row p-2">
                                    <div class="col-12 text-primary h5">
                                        <?= h($item['name']); ?>
                                    </div>

                                    <!-- Handle status name -->
                                    <div class="col-12">
                                        <h6 class="<?= h($item['cumulatedColor']); ?>"><?= h($item['cumulatedStateName']); ?></h6>
                                    </div>
                                    <!-- end of status name -->
                                    <!-- Handle acknowledgement comments -->
                                    <?php if (!empty($item['acknowledgedProblemsText']) && $statuspage['statuspage']['showAcknowledgements'] && $item['cumulatedColorId'] > 0): ?>
                                        <div class="col-12">
                                            <div class="row">
                                                <div class="col-12">
                                                    <?php if (!empty($item['acknowledgedProblemsText'])): ?>
                                                        <div>
                                                            <i class="far fa-user"></i>
                                                            <?= h($item['acknowledgedProblemsText']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['acknowledgeComment'])): ?>
                                                        <?php foreach ($item['acknowledgeComment'] as $comment): ?>
                                                            <div class="text-truncate">
                                                                <?php echo __('Comment'); ?>
                                                                : <?= h($comment); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    <!-- Handle acknowledgement comments -->
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <!-- handle current downtime comments -->
                                    <?php if ($statuspage['statuspage']['showDowntimes']): ?>
                                        <?php if (!empty($item['downtimeData']) && count($item['downtimeData']) > 0): ?>
                                            <div class="col-12 ">
                                                <div class="row">
                                                    <div class="col-12 ">
                                                        <div class="pt-1">
                                                            <i class="fa fa-power-off"></i>
                                                            <?= __('Currently under maintenance.'); ?>
                                                        </div>
                                                        <?php foreach ($item['downtimeData'] as $downtime): ?>
                                                            <div class="row">
                                                                <div class="col-xs-12 col-md-3">
                                                                    <?= __('Start'); ?>:
                                                                    <?= h($downtime['scheduledStartTime']); ?>
                                                                </div>
                                                                <div class="col-xs-12 col-md-3">
                                                                    <?= __('End'); ?>:
                                                                    <?= h($downtime['scheduledEndTime']); ?>
                                                                </div>
                                                                <div class="col-xs-12 col-md-3">
                                                                    <?= __('Comment'); ?>:
                                                                    <?= h($downtime['comment']); ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <!-- end of current downtimes -->
                                        <!-- handle plant downtime comments -->
                                        <?php if (!empty($item['plannedDowntimeData']) && count($item['plannedDowntimeData']) > 0): ?>
                                            <div class="col-12 ">
                                                <div class="row">
                                                    <div class="col-12 ">
                                                        <div class="pt-1">
                                                            <i class="fa fa-power-off"></i>
                                                            <?= __('Scheduled maintenance for the next 10 days:'); ?>
                                                        </div>
                                                        <?php foreach ($item['plannedDowntimeData'] as $downtime): ?>
                                                            <div class="row">
                                                                <div class="col-xs-12 col-md-3">
                                                                    <?= __('Start'); ?>:
                                                                    <?= h($downtime['scheduledStartTime']); ?>
                                                                </div>
                                                                <div class="col-xs-12 col-md-3">
                                                                    <?= __('End'); ?>:
                                                                    <?= h($downtime['scheduledEndTime']); ?>
                                                                </div>
                                                                <div class="col-xs-12 col-md-3">
                                                                    <?= __('Comment'); ?>:
                                                                    <?= h($downtime['comment']); ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <!-- end of planed downtimes -->
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="p-2 hidden-md-down">
                                <div
                                    class="h-100 status-line bg-<?= h($item['cumulatedColor']); ?> shadow-<?= h($item['cumulatedColor']); ?>"></div>
                            </div>
                        </div>
                        <!-- end object card -->
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>


