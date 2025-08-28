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
<div class="btn-group mr-2" role="group" aria-label="<?= __('Display of system health notifications'); ?>">
    <a href="javascript:void(0);" class="btn btn-default" data-toggle="dropdown"
       data-original-title="<?= __('System health'); ?>"
       data-placement="bottom" rel="tooltip" aria-expanded="false">
        <i class="fas fa-heartbeat {{class}}"></i>
    </a>

    <div class="dropdown-menu dropdown-menu-animated dropdown-xl scrollable-menu dropdown-remove-paddings-and-margins">
        <div
            class="dropdown-header {{bgClass}} d-flex justify-content-center align-items-center rounded-top padding-10">
            <h4 class="m-0 text-center color-white">
                <?= __('System Health'); ?>
            </h4>
        </div>

        <div class="dropdown-item ng-scope" ng-if="systemHealth.state == 'ok'">
            <a ui-sref="AdministratorsDebug" class="d-flex align-items-center" href="#!/Administrators/debug">
                <span class="mr-2">
                    <i class="d-inline-block fas fa-check fa-2x ok"></i>
                </span>
                <span class="d-flex flex-column flex-1 ml-1">
                    <span class="notification-title">
                        <?= __('Alright!'); ?>
                    </span>
                    <span class="notification-message fs-sm">
                        <?= __('No issues detected. System operates normally.'); ?>
                    </span>
                </span>
            </a>
        </div><!-- end ngIf: systemHealth.state == 'ok' -->
        <!-- ngIf: systemHealth.state == 'warning' || systemHealth.state == 'critical' -->
        <div class="dropdown-item ng-scope no-padding"
             ng-if="systemHealth.state == 'warning' || systemHealth.state == 'critical'">
            <a ui-sref="AdministratorsDebug" class="d-flex align-items-center" href="#!/Administrators/debug">
                <ul class="padding-5 list-unstyled system-health-item notification-message fs-sm" style="width: 100%;">
                    <li ng-if="!systemHealth.isNagiosRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning down"></i>
                                    <?php echo __('Critical'); ?>
                                </h6>
                                <i><?php echo __('Monitoring engine is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.gearman_reachable">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning down"></i>
                                    <?php echo __('Critical'); ?>
                                </h6>
                                <i><?php echo __('Gearman job server not reachable!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.gearman_worker_running">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning down"></i>
                                    <?php echo __('Critical'); ?>
                                </h6>
                                <i><?php echo __('Service gearman_worker is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.isNdoInstalled && !systemHealth.isNdoRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning down"></i>
                                    <?php echo __('Critical'); ?>
                                </h6>
                                <i><?php echo __('Database connector NDOUtils is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.isStatusengineInstalled && !systemHealth.isStatusengineRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning down"></i>
                                    <?php echo __('Critical'); ?>
                                </h6>
                                <i><?php echo __('Database connector Statusengine is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.isStatusenginePerfdataProcessor && !systemHealth.isStatusengineRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>
                                <i><?php echo __('Performance data processer Statusengine is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.isStatusenginePerfdataProcessor && !systemHealth.isNpcdRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>
                                <i><?php echo __('Performance data processer NPCD is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.isSudoServerRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>
                                <i><?php echo __('Service sudo_server is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.isOitcCmdRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>
                                <i><?php echo __('Service oitc_cmd is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.isPushNotificationRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>

                                <i><?php echo __('Service push_notification is not running!'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="!systemHealth.isNodeJsServerRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>

                                <i><?php echo __('Nodejs backend is not running'); ?></i>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.load.state !== 'ok'">
                        <span>
                            <div class="padding-5">
                                <p class="margin-bottom-5">
                                    <i><?php echo __('Current CPU load is too high!'); ?></i>
                                    <br/>
                                    <i>{{ systemHealth.load.load1 }}, {{ systemHealth.load.load5 }}, {{
                                        systemHealth.load.load15 }}</i>
                                </p>
                            </div>
                        </span>
                    </li>

                    <li ng-repeat="satellite in systemHealth.satellites"
                        ng-if="satellite.satellite_status.status !== 1">
                        <span>
                            <div class="padding-5">
                                <p class="margin-bottom-5">
                                    <i><?php echo __('Sync status'); ?><?php echo __('failed') ?></i>
                                    <br/>
                                    <i>{{ satellite.name }}, <?php echo __('last seen') ?> {{ satellite.satellite_status.last_seen }}</i>
                                </p>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.memory_usage.memory.state !== 'ok'">
                        <span>
                            <div class="padding-5">
                                <p class="margin-bottom-5">
                                    <i><?php echo __('High memory usage.'); ?></i>
                                    <span class="pull-right semi-bold text-muted">
                                        {{ systemHealth.memory_usage.memory.percentage }}%
                                    </span>
                                </p>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-color-darken"
                                         style="width: {{ systemHealth.memory_usage.memory.percentage }}%;"></div>
                                </div>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.memory_usage.swap.state !== 'ok'">
                        <span>
                            <div class="padding-5">
                                <p class="margin-bottom-5">
                                    <i><?php echo __('High Swap usage'); ?></i>
                                    <span class="pull-right semi-bold text-muted">
                                        {{ systemHealth.memory_usage.swap.percentage }}%
                                    </span>
                                </p>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-color-darken"
                                         style="width: {{ systemHealth.memory_usage.swap.percentage }}%;"></div>
                                </div>
                            </div>
                        </span>
                    </li>

                    <li ng-repeat="disk in systemHealth.disk_usage" ng-if="disk.state !== 'ok'">
                        <span>
                            <div class="padding-5">
                                <p class="margin-bottom-5">
                                    <i><?php echo __('Low disk space left for mountpoint:'); ?></i>
                                    <br/>
                                    <i>"{{ disk.mountpoint }}"</i>
                                    <span class="pull-right semi-bold text-muted">
                                        {{ disk.use_percentage }}%
                                    </span>
                                </p>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-color-darken"
                                         style="width: {{ disk.use_percentage }}%;"></div>
                                </div>
                            </div>
                        </span>
                    </li>

                    <li ng-if="systemHealth.isDistributeModuleInstalled && !systemHealth.isNstaRunning">
                        <span>
                            <div class="padding-5">
                                <h6>
                                    <i class="fa fa-warning warning"></i>
                                    <?php echo __('Warning'); ?>
                                </h6>
                                <i><?php echo __('Service NSTA is not running!'); ?></i>
                            </div>
                        </span>
                    </li>
                </ul>
            </a>
        </div>
        <!-- end ngIf: systemHealth.state == 'warning' || systemHealth.state == 'critical' -->
        <!-- ngIf: systemHealth.state === 'unknown' -->
        <div class="dropdown-item ng-scope no-padding" ng-if="systemHealth.state === 'unknown'">
            <ul class="padding-5 list-unstyled system-health-item notification-message fs-sm" style="width: 100%;">
                <li ng-if="!systemHealth.isNagiosRunning">
                    <span>
                        <div class="padding-5">
                            <p class="margin-bottom-5">
                                <i class="fa fa-question-circle text-primary"></i>
                                <strong><?php echo __('Unknown'); ?></strong>
                                <br/>
                                <i><?php echo __('Could not detect system health status.'); ?></i>
                            </p>
                        </div>
                    </span>
                </li>
            </ul>
        </div>
        <?php if ($this->Acl->hasPermission('index', 'systemHealthUsers')): ?>
            <div class="padding-5 d-flex justify-content-between">
                <a ui-sref="SystemHealthUsersIndex" class="btn btn-primary btn-xs mr-1 shadow-0"
                   title="<?php echo __('System health notification list'); ?>">
                    <i class="fa fa-user"></i>
                </a>
                <!-- ngIf: systemHealth.state === 'unknown' -->
                <div class="italic text-info text-right">
                    <?= __('Last update'); ?>: {{ systemHealth.update }}
                </div>
            </div>
        <?php else: ?>
            <!-- ngIf: systemHealth.state === 'unknown' -->
            <div class="padding-5 italic text-info text-right">
                <?= __('Last update'); ?>: {{ systemHealth.update }}
            </div>
        <?php endif; ?>
    </div>

    <button class="btn {{btnClass}}"
            data-placement="bottom" rel="tooltip"
            data-container="body">{{systemHealth.errorCount}}
    </button>
</div>
