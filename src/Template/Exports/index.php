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
<ol class="breadcrumb page-breadcrumb" ng-init="gearmanReachable = true">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ExportsIndex">
            <i class="fa fa-retweet"></i> <?php echo __('Export'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Apply configuration'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Export'); ?>
                    <span class="fw-300"><i><?php echo __('Refresh monitoring configuration'); ?></i></span>
                </h2>
                <div class="panel-toolbar">

                </div>
            </div>

            <div class="panel-container show" ng-if="!gearmanReachable">
                <div class="panel-content">
                    <div class="col-lg-12">
                        <div class="alert alert-danger alert-block">
                            <h5 class="alert-heading">
                                <i class="fa fa-warning"></i> <?= __('Critical error!'); ?>
                            </h5>
                            <?= __('Could not connect to Gearman Job Server'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-container show" ng-if="!isGearmanWorkerRunning">
                <div class="panel-content">
                    <div class="col-lg-12">
                        <div class="alert alert-danger alert-block">
                            <h5 class="alert-heading">
                                <i class="fa fa-warning"></i> <?= __('Critical error!'); ?>
                            </h5>
                            <?= __('Gearman Worker background daemon is not running. Please contact your system administrator.'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-container show" ng-if="gearmanReachable && isGearmanWorkerRunning">
                <div class="panel-content">
                    <div class="form-group" ng-class="{'has-error': errors.create_backup}">
                        <div class="custom-control custom-checkbox  margin-bottom-10"
                             ng-class="{'has-error': errors.create_backup}">

                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="createBackupCheckbox"
                                   ng-true-value="1"
                                   ng-false-value="0"
                                   ng-model="post.create_backup">
                            <label class="custom-control-label" for="createBackupCheckbox">
                                <?php echo __('Create backup of current configuration'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Satellite select -->
                    <div class="row">
                        <div class="col-12 margin-bottom-10">
                            <div class="card padding-top-15 padding-bottom-15" ng-show="useSingleInstanceSync">
                                <div class="card-header">
                                    <i class="fas fa-satellite">&nbsp;</i>
                                    <?= __('Select instances which the new configuration should get pushed.'); ?>
                                </div>
                                <div class="card-body no-padding">
                                    <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                        <thead>
                                        <tr>
                                            <th class="no-sort width-15">
                                                <i class="fa fa-check-square"></i>
                                            </th>

                                            <th class="no-sort">
                                                <?= __('Instances name'); ?>
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr ng-repeat="satellite in satellites">
                                            <td>
                                                <label class="checkbox no-required">
                                                    <input type="checkbox" name="checkbox"
                                                           ng-disabled="exportRunning"
                                                           ng-true-value="1"
                                                           ng-false-value="0"
                                                           ng-model="satellite.sync_instance">
                                                    <i class="checkbox-primary"></i>
                                                </label>
                                            </td>
                                            <td>
                                                {{ satellite.name }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <div class="row margin-top-10 margin-bottom-10">
                                        <div class="col-xs-12 col-md-2 text-muted text-center">
                                            <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                                        </div>
                                        <div class="col-xs-12 col-md-2">
                                            <span ng-click="selectAll()" class="pointer">
                                                <i class="fa fa-lg fa-check-square"></i>
                                                <?php echo __('Select all'); ?>
                                            </span>
                                        </div>
                                        <div class="col-xs-12 col-md-2">
                                            <span ng-click="undoSelection()" class="pointer">
                                                <i class="fa fa-lg fa-square"></i>
                                                <?php echo __('Undo selection'); ?>
                                            </span>
                                        </div>
                                        <div class="col-xs-12 col-md-2">
                                            <button class="btn btn-xs btn-outline-success waves-effect waves-themed"
                                                    ng-click="saveInstanceConfigSyncSelection();">
                                                <?= __('Save selection'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Satellite select end -->

                    <div class="card">
                        <div class="card-header" ng-show="exportRunning || showLog">
                            <i class="fas fa-tasks"></i>
                            <?= __('Tasks'); ?>
                        </div>
                        <div class="card-body">


                            <div id="exportError" class="col-xs-12 padding-top-20" ng-show="!exportSuccessfully">
                                <div class="alert alert-danger alert-block">
                                    <h4 class="alert-heading"><i class="fa fa-times"></i> <?= __('Error'); ?></h4>
                                    <?= __('Error while refreshing monitoring configuration.'); ?>
                                </div>
                            </div>

                            <div id="verifyError" class="col-xs-12 padding-top-20"
                                 ng-show="verificationErrors.nagios.hasError">
                                <div class="alert alert-danger alert-block">
                                    <h4 class="alert-heading"><i
                                                class="fa fa-times"></i> <?= __('New configuration is invalid'); ?>
                                    </h4>
                                    &nbsp;
                                    <div class="well txt-color-blueDark" id="verifyOutput">
                                        <pre>{{verificationErrors.nagios.output}}</pre>
                                    </div>
                                </div>
                            </div>

                            <div id="verifyError" class="col-xs-12 padding-top-20"
                                 ng-show="verificationErrors.prometheus.hasError">
                                <div class="alert alert-danger alert-block">
                                    <h4 class="alert-heading"><i
                                                class="fa fa-times"></i> <?= __('New Prometheus configuration is invalid'); ?>
                                    </h4>
                                    &nbsp;
                                    <div class="well txt-color-blueDark" id="verifyOutput">
                                        <pre>{{verificationErrors.prometheus.output}}</pre>
                                    </div>
                                </div>
                            </div>

                            <div id="exportInfo" ng-show="exportRunning || showLog" class="padding-top-15">
                                <div class="col-xs-12">
                                    <div class="well" id="exportLog">
                                        <div ng-repeat="task in tasks">
                                            <i class="fa fa-spin fa-refresh" ng-show="task.finished === 0"></i>
                                            <i class="fa fa-check text-success"
                                               ng-show="task.finished === 1 && task.successfully === 1"></i>
                                            <i class="fa fa-times text-danger"
                                               ng-show="task.finished === 1 && task.successfully === 0"></i>
                                            <span class="code-font txt-color-grayDark">[{{task.modified}}]</span>
                                            <span>{{task.text}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="col-xs-12">
                                <div class="formactions">
                                    <div class="float-right">

                                        <label class="text-info padding-right-10" ng-show="exportRunning">
                                            <i class="fa fa-info-circle"></i>
                                            <?= __('Refresh in progress '); ?>
                                        </label>

                                        <button class="btn btn-success waves-effect waves-themed" type="button"
                                                ng-disabled="exportRunning" ng-click="launchExport();">
                                            <i class="fa fa-rocket" ng-if="!exportRunning"></i>
                                            <i class="fa fa-spin fa-spinner" ng-if="exportRunning"></i>
                                            <?= __('Launch refresh'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" ng-show="(exportRunning || showLog) && satellites.length > 0">
                        <div class="col-12">
                            <div class="alert border-faded bg-transparent text-secondary margin-top-20">
                                <div class="d-flex align-items-center">
                                    <div class="alert-icon">
                                            <span class="icon-stack icon-stack-md">
                                                <i class="base-7 icon-stack-3x color-info-600"></i>
                                                <i class="fas fa-satellite icon-stack-1x text-white"></i>
                                            </span>
                                    </div>
                                    <div class="flex-1">
                                        <span class="h5 color-info-600">
                                            <?= __('Satellite Status has been moved.'); ?>
                                        </span>
                                        <br>
                                        <?= __('Satellite export status has been moved to a separate view.'); ?>
                                    </div>
                                    <?php if ($this->Acl->hasPermission('status', 'satellites', 'DistributeModule')): ?>
                                        <a class="btn btn-outline-success btn-sm btn-w-m waves-effect waves-themed"
                                           ui-sref="SatellitesStatus">
                                            <?= __('Go to "Satellite Status"'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
