<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

use Cake\I18n\DateTime;
use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 * @var array $statuspage
 * @var string $systemname
 * @var int $id
 * @var string $timezone
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
                <span class="text-header"><?= h($statuspage['statuspage']['public_title']); ?></span>
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
                        <div class="small mt-1">
                            <?= __('Last refresh') ?>
                            : <?= h((new DateTime())->format('Y-m-d H:i:s')) ?> <?= __('(Servertime, Timezone: ') ?> <?= h($timezone); ?>
                            )
                        </div>
                        <div class="small mt-1">
                            <?= __('Refresh interval') ?>
                            : <?= h($statuspage['statuspage']['public_refresh'] ?? 60) ?> <?= __(' seconds') ?>
                        </div>
                    </div>

                    <div
                        class="p-3 bg-<?= h($statuspage['statuspage']['cumulatedColor']); ?> rounded overflow-hidden position-relative text-white">
                        <div class="d-flex align-items-baseline justify-content-between position-relative z-1 pe-5">
                            <h5 class="l-h-n m-0 fw-500 d-inline">
                                <?= h($statuspage['statuspage']['cumulatedHumanStatus']); ?>
                            </h5>
                            <?php if ($statuspage['statuspage']['cumulatedColorId'] > 0 && !empty($statuspage['statuspage']['lastStateChange'])): ?>
                                <span><?= __('State since'); ?>: <?= h($statuspage['statuspage']['lastStateChange']); ?>
                                    (<?= h($timezone); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <i class="<?= h($statuspage['statuspage']['cumulatedIcon']); ?> statuspage-icon position-absolute pos-right pos-bottom opacity-15 pe-1"></i>
                    </div>
                </div>
            </div>
            <!-- end overall status -->


            <div class="my-3">

                <?php foreach ($statuspage['groupedItems'] as $group): ?>
                    <div class="card mt-2 border-<?= h($group['cumulatedColor']); ?>">
                        <?php if (empty($group['isUngrouped'])): ?>
                            <div class="card-header border-<?= h($group['cumulatedColor']); ?>">
                                <h5><?= h($group['group']); ?></h5>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <?php foreach ($group['items'] as $item): ?>
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
                                                    <div class="d-flex justify-content-between align-items-baseline">

                                                        <span
                                                            class="h6 me-2 <?= h($item['cumulatedColor']); ?>"><?= h($item['cumulatedStateName']); ?></span>
                                                        <?php if ($item['cumulatedColorId'] > 0 && !empty($item['lastStateChange'])): ?>
                                                            <span>  <?= __('State since'); ?>
                                                                : <?= h($item['lastStateChange']); ?>
                                                                (<?= h($timezone); ?>)</span>
                                                        <?php endif; ?>

                                                    </div>
                                                </div>
                                                <!-- end of status name -->
                                                <!-- Handle acknowledgement comments -->
                                                <?php if (!empty($item['acknowledgedProblemsText']) && $statuspage['statuspage']['showAcknowledgements'] && $item['cumulatedColorId'] > 0): ?>
                                                    <?php
                                                    // create unique ID for each object
                                                    $uniqueId = 'ack-comments-' . h($item['type'] ?? 'item') . '-' . h($item['id']);
                                                    $hasComments = !empty($item['acknowledgeComment']);
                                                    ?>
                                                    <div class="col-12 mt-2">
                                                        <div class="d-flex align-items-center">
                                                            <i class="far fa-user me-2"></i>

                                                            <?php if ($hasComments): ?>
                                                                <span
                                                                    class="p-0 me-2 text-decoration-none"
                                                                    onclick="toggleSection('<?= $uniqueId; ?>', this)"
                                                                    title="<?= __('expand comments'); ?>">
                                                                    <i class="far fa-plus-square fa-lg icon-toggle"></i>
                                                                </span>
                                                            <?php endif; ?>

                                                            <span><?= h($item['acknowledgedProblemsText']); ?></span>
                                                        </div>

                                                        <?php if ($hasComments): ?>
                                                            <div id="<?= $uniqueId; ?>" class="ps-4 mt-1"
                                                                 style="display: none;">
                                                                <?php foreach ($item['acknowledgeComment'] as $comment): ?>
                                                                    <div class="text-truncate small">
                                                                        <strong><?= __('Comment'); ?>
                                                                            :</strong> <?= h($comment); ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <!-- handle downtimes -->
                                                <?php if ($statuspage['statuspage']['showDowntimes']): ?>
                                                    <!-- Current Downtimes -->
                                                    <?php if (!empty($item['downtimeData']) && count($item['downtimeData']) > 0): ?>
                                                        <?php $currentId = 'current-downtime-' . h($item['type'] ?? 'item') . '-' . h($item['id']); ?>
                                                        <div class="col-12">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="pt-1 d-flex align-items-center">
                                                                        <i class="fa fa-power-off me-1"></i>
                                                                        <span
                                                                            class="p-0 me-2 text-decoration-none"
                                                                            onclick="toggleSection('<?= $currentId; ?>', this)"
                                                                            title="<?= __('expand downtime data'); ?>">
                                                                            <i class="far fa-plus-square fa-lg icon-toggle"></i>
                                                                        </span>
                                                                        <span><?= count($item['downtimeData']); ?> <?= __('current maintenances'); ?></span>
                                                                    </div>

                                                                    <!-- Collapsible Container (Hidden by default) -->
                                                                    <div id="<?= $currentId; ?>" class="mt-2"
                                                                         style="display: none;">
                                                                        <?php foreach ($item['downtimeData'] as $downtime): ?>
                                                                            <div class="row mb-1">
                                                                                <div class="col-xs-12 col-md-3">
                                                                                    <strong><?= __('Start'); ?>
                                                                                        :</strong> <?= h($downtime['scheduledStartTime']); ?>
                                                                                </div>
                                                                                <div class="col-xs-12 col-md-3">
                                                                                    <strong><?= __('End'); ?>
                                                                                        :</strong> <?= h($downtime['scheduledEndTime']); ?>
                                                                                </div>
                                                                                <div class="col-xs-12 col-md-3">
                                                                                    <strong><?= __('Comment'); ?>
                                                                                        :</strong> <?= h($downtime['comment']); ?>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <!-- end of current downtimes -->
                                                    <!-- Planned Downtimes -->
                                                    <?php if (!empty($item['plannedDowntimeData']) && count($item['plannedDowntimeData']) > 0): ?>
                                                        <?php $plannedId = 'planned-downtime-' . h($item['type'] ?? 'item') . '-' . h($item['id']); ?>
                                                        <div class="col-12 mt-2">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="pt-1 d-flex align-items-center">
                                                                        <i class="fa fa-power-off me-1"></i>
                                                                        <span
                                                                            class="p-0 me-2 text-decoration-none"
                                                                            onclick="toggleSection('<?= $plannedId; ?>', this)"
                                                                            title="<?= __('expand downtime data'); ?>">
                                                                            <i class="far fa-plus-square fa-lg icon-toggle"></i>
                                                                        </span>
                                                                        <span><?= count($item['plannedDowntimeData']); ?> <?= __('scheduled maintenances for the next 10 days'); ?></span>
                                                                    </div>

                                                                    <!-- Collapsible Container (Hidden by default) -->
                                                                    <div id="<?= $plannedId; ?>" class="mt-2"
                                                                         style="display: none;">
                                                                        <?php foreach ($item['plannedDowntimeData'] as $downtime): ?>
                                                                            <div class="row mb-1">
                                                                                <div class="col-xs-12 col-md-3">
                                                                                    <strong><?= __('Start'); ?>
                                                                                        :</strong> <?= h($downtime['scheduledStartTime']); ?>
                                                                                </div>
                                                                                <div class="col-xs-12 col-md-3">
                                                                                    <strong><?= __('End'); ?>
                                                                                        :</strong> <?= h($downtime['scheduledEndTime']); ?>
                                                                                </div>
                                                                                <div class="col-xs-12 col-md-3">
                                                                                    <strong><?= __('Comment'); ?>
                                                                                        :</strong> <?= h($downtime['comment']); ?>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <!-- end of planned downtimes -->
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

                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>

<script>
    function toggleSection(elementId, btn) {
        const container = document.getElementById(elementId);
        if(!container) return;

        const icon = btn.querySelector('.icon-toggle');

        if(container.style.display === 'none') {
            container.style.display = 'block';
            if(icon) {
                icon.classList.remove('fa-plus-square');
                icon.classList.add('fa-minus-square');
            }
        } else {
            container.style.display = 'none';
            if(icon) {
                icon.classList.remove('fa-minus-square');
                icon.classList.add('fa-plus-square');
            }
        }
    }
</script>
