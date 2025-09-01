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
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="SystemfailuresIndex">
            <i class="fa fa-exclamation-circle"></i> <?php echo __('System failure'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Add'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Create new system failure'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'systemfailures')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='SystemfailuresIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('System failure'); ?>' , message: '<?php echo __(' created successfully'); ?>'}">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="alert alert-info alert-block">
                                    <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
                                    <h4 class="alert-heading"><?php echo __('What are "System Failures" for?'); ?></h4>
                                    <?php echo __('<i>System failures</i> are outages of the openITCOCKPIT server itself. They need to be created manually.'); ?>
                                    <br/>
                                    <?php echo __('Timeframes defined by System failures will be ignored while report generation.'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group required">
                            <label class="control-label" for="AuthorFakeSelect">
                                <?php echo __('Author'); ?>
                            </label>
                            <select
                                id="AuthorFakeSelect"
                                class="form-control"
                                disabled="disabled"
                                chosen="containers">
                                <option>
                                    <?php echo h($User->getFullName()); ?>
                                </option>
                            </select>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.comment}">
                            <label class="control-label">
                                <?php echo __('Comment'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Systemfailure.comment">
                            <div ng-repeat="error in errors.comment">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.start_time}">
                            <label class="control-label">
                                <?php echo __('From'); ?>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="icon-prepend far fa-calendar-alt"></i>
                                    </span>
                                </div>
                                <input
                                    id="SystemfailureFromDate"
                                    class="form-control col-lg-2"
                                    type="date"
                                    placeholder="DD.MM.YYYY"
                                    ng-model="post.Systemfailure.from_date">
                                <div class="input-group-append input-group-prepend">
                                    <span class="input-group-text"><i class="icon-prepend far fa-clock"></i></span>
                                </div>
                                <input
                                    class="form-control col"
                                    type="time"
                                    placeholder="hh:mm"
                                    ng-model="post.Systemfailure.from_time"
                                    ng-model-options="{timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                <div ng-repeat="error in errors.start_time">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.end_time}">
                            <label class="control-label">
                                <?php echo __('To'); ?>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="icon-prepend far fa-calendar-alt"></i>
                                    </span>
                                </div>
                                <input
                                    id="SystemfailureToDate"
                                    class="form-control col-lg-2"
                                    type="date"
                                    placeholder="DD.MM.YYYY"
                                    ng-model="post.Systemfailure.to_date">
                                <div class="input-group-append input-group-prepend">
                                    <span class="input-group-text"><i class="icon-prepend far fa-clock"></i></span>
                                </div>
                                <input
                                    class="form-control col"
                                    type="time"
                                    placeholder="hh:mm"
                                    ng-model="post.Systemfailure.to_time"
                                    ng-model-options="{timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                <div ng-repeat="error in errors.end_time">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <label>
                                        <input type="checkbox" ng-model="data.createAnother">
                                        <?php echo __('Create another'); ?>
                                    </label>
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Create system failure'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='SystemfailuresIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
