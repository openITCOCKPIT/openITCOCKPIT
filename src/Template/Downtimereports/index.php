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
        <a ui-sref="DowntimereportsIndex">
            <i class="fa fa-file-invoice"></i> <?php echo __('Downtime report'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Downtime report'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" ng-class="{'active': tabName=='reportConfig'}"
                               ng-click="tabName='reportConfig'" role="tab">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" ng-class="{'active': tabName=='calendarOverview'}"
                               ng-click="tabName='calendarOverview'"
                               ng-show="reportData.downtimes" role="tab">
                                <i class="fa fa-calendar"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab"
                               ng-class="{'active': tabName=='hostsServicesOverview'}"
                               ng-click="tabName='hostsServicesOverview'"
                               ng-show="reportData.downtimes" role="tab">
                                <i class="fa fa-pie-chart"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <div ng-show="tabName == 'reportConfig'" id="reportConfig">

                            <div class="row" ng-if="errors.no_downtimes">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <div ng-repeat="error in errors.no_downtimes">
                                            <div class="help-block text-info">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form ng-submit="submit();" class="form-horizontal"
                                  ng-init="reportMessage={successMessage : '<?php echo __('Report created successfully'); ?>' , errorMessage: '<?php echo __('Report could not be created'); ?>'}">

                                <div class="form-group required" ng-class="{'has-error': errors.evaluation_type}">
                                    <label class="control-label col-lg-12">
                                        <?php echo __('Evaluation'); ?>
                                    </label>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" id="downtimetype0"
                                               name="downtimeType"
                                               ng-model="post.evaluation_type"
                                               ng-value="0">
                                        <label class="custom-control-label" for="downtimetype0">
                                            <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" id="downtimetype1"
                                               name="downtimeType"
                                               ng-model="post.evaluation_type"
                                               ng-value="1">
                                        <label class="custom-control-label" for="downtimetype1">
                                            <i class="fa fa-cogs"></i> <?php echo __('Hosts and Services'); ?>
                                        </label>
                                    </div>
                                    <div ng-repeat="error in errors.evaluation_type">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.report_format}">
                                    <label class="control-label" for="ReportFormat">
                                        <?php echo __('Report format'); ?>
                                    </label>
                                    <select
                                        id="ReportFormat"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="{}"
                                        ng-model="post.report_format">
                                        <option ng-value="1"><?php echo __('PDF'); ?></option>
                                        <option ng-value="2"><?php echo __('HTML'); ?></option>
                                    </select>
                                    <div ng-repeat="error in errors.report_format">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.timeperiod_id}">
                                    <label class="control-label" for="ReportTimeperiod">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.timeperiod_id})"
                                               ng-if="post.timeperiod_id > 0">
                                                <?php echo __('Timeperiod'); ?>
                                            </a>
                                            <span ng-if="!post.timeperiod_id"><?php echo __('Timeperiod'); ?></span>
                                        <?php else: ?>
                                            <?php echo __('Timeperiod'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <select
                                        id="ReportTimeperiod"
                                        data-placeholder="<?php echo __('Please choose a timeperiod'); ?>"
                                        class="form-control"
                                        chosen="timeperiods"
                                        ng-options="timeperiod.Timeperiod.id as timeperiod.Timeperiod.name for timeperiod in timeperiods"
                                        ng-model="post.timeperiod_id">
                                    </select>
                                    <div ng-repeat="error in errors.timeperiod_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.from_date}">
                                    <label class="control-label">
                                        <?php echo __('From'); ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        type="date"
                                        ng-model="from"
                                        placeholder="<?php echo __('DD.MM.YYYY'); ?>">
                                    <div ng-repeat="error in errors.from_date">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.to_date}">
                                    <label class="control-label">
                                        <?php echo __('To'); ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        type="date"
                                        ng-model="to"
                                        placeholder="<?php echo __('DD.MM.YYYY'); ?>">
                                    <div ng-repeat="error in errors.to_date">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.reflection_state}">
                                    <label class="control-label" for="ReportReflectionState">
                                        <?php echo __('Reflection state'); ?>
                                    </label>
                                    <select
                                        id="ReportReflectionState"
                                        data-placeholder="<?php echo __('Please choose a timeperiod'); ?>"
                                        class="form-control"
                                        chosen="{}"
                                        ng-model="post.reflection_state">
                                        <option ng-value="1"><?php echo __('soft and hard state'); ?></option>
                                        <option ng-value="2"><?php echo __('only hard state'); ?></option>
                                    </select>
                                    <div ng-repeat="error in errors.reflection_state">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>


                                <div class="form-group" ng-show="post.report_format == 2">
                                    <label class="control-label" for="setColorDynamically">
                                        <?php echo __('Dynamic color'); ?>
                                    </label>
                                    <div class="col-xs-12 col-lg-10 smart-form">
                                        <label class="checkbox small-checkbox-label no-required no-padding-top">
                                            <input type="checkbox" name="checkbox"
                                                   id="setColorDynamically"
                                                   ng-model="setColorDynamically">
                                            <i class="checkbox-primary"></i>
                                            <?php echo __('Yes'); ?>
                                            <span class="margin-left-10 label-group-width-auto">
                                        <span class="badge-medium" style="background:#00c851;">100%</span>
                                        <span class="badge-medium" style="background:#33c54b;">90%</span>
                                        <span class="badge-medium" style="background:#66c345;">80%</span>
                                        <span class="badge-medium" style="background:#94c140;">70%</span>
                                        <span class="badge-medium" style="background:#cabe3a;">60%</span>
                                        <span class="badge-medium" style="background:#ffbb33;">50%</span>
                                        <span class="badge-medium" style="background:#f79d2b;">40%</span>
                                        <span class="badge-medium" style="background:#eb721f;">30%</span>
                                        <span class="badge-medium" style="background:#e14c15;">20%</span>
                                        <span class="badge-medium" style="background:#d7260b;">10%</span>
                                        <span class="badge-medium" style="background:#cc0000;">0%</span>
                                    </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="card margin-top-10">
                                    <div class="card-body">
                                        <div class="float-right">
                                            <button class="btn btn-primary" ng-click="createDowntimeReport()"
                                                    type="submit"><?php echo __('Create downtime report'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div ng-if="tabName == 'calendarOverview'" id="calendarOverview">
                            <downtimecalendar downtimes="reportData.downtimes" from-date="post.from_date"
                                              to-date="post.to_date"></downtimecalendar>
                        </div>

                        <div ng-if="tabName == 'hostsServicesOverview'" id="hostsServicesOverview">
                            <div class="row">
                                <div class="card" style="width: 100%;">
                                    <div class="card-header">
                                        <h4>
                                        <span class="fa-stack">
                                            <i class="fa fa-desktop fa-lg fa-stack-1x"></i>
                                            <i class="fa fa-exclamation-triangle fa-stack-1x fa-xs cornered cornered-lr text-danger padding-bottom-2"></i>
                                        </span>
                                            <?php echo __('Involved in outages (Hosts):'); ?>
                                        </h4>
                                    </div>
                                    <div class="card-body padding-bottom-10">
                                        <div
                                            ng-repeat="(chunkIndex, hostsWithOutages) in reportData.hostsWithOutages">
                                            <hosts-bar-chart chart-id="chunkIndex"
                                                             bar-chart-data="hostsWithOutages.hostBarChartData"></hosts-bar-chart>

                                            <div class="row" ng-repeat="host in hostsWithOutages.hosts">
                                                <host-availability-overview data="host"
                                                                            evaluation-type="post.evaluation_type"
                                                                            dynamic-color="setColorDynamically"
                                                                            class="col-lg-12"></host-availability-overview>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="card" style="width: 100%;">
                                    <div class="card-header">
                                        <h4>
                                        <span class="fa-stack">
                                            <i class="fa fa-desktop fa-lg fa-stack-1x"></i>
                                            <i class="fa fa-check-circle fa-stack-1x fa-xs cornered cornered-lr ok padding-bottom-2"></i>
                                        </span>
                                            <?php echo __('Hosts without outages:'); ?>
                                        </h4>
                                    </div>
                                    <div class="card-body padding-bottom-10">
                                        <div class="row" ng-repeat="host in reportData.hostsWithoutOutages.hosts">
                                            <host-availability-overview data="host"
                                                                        evaluation-type="post.evaluation_type"
                                                                        dynamic-color="setColorDynamically"
                                                                        class="col-lg-12"></host-availability-overview>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
