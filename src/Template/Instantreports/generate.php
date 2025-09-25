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
        <a ui-sref="InstantreportsIndex">
            <i class="fa fa-file-invoice"></i> <?php echo __('Instant reports'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Generate'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Generate instant report'); ?>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab"
                               ng-class="{'active': tabName=='reportConfig'}"
                               ng-click="tabName='reportConfig'" role="tab">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab"
                               ng-class="{'active': tabName=='instantReport'}"
                               ng-click="tabName='instantReport'"
                               ng-show="reportData" role="tab">
                                <i class="fa fa-pie-chart"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <!-- Config tab -->
                        <div ng-show="tabName == 'reportConfig'" id="reportConfig">
                            <form class="form-horizontal clear" ng-init="reportMessage=
                    {successMessage : '<?php echo __('Report created successfully'); ?>' , errorMessage: '<?php echo __('Report could not be created'); ?>'}">
                                <div class="form-group required" ng-class="{'has-error': errors.instantreport_id}">
                                    <label class="control-label" for="Instantreport">
                                        <?php echo __('Instant report'); ?>
                                    </label>
                                    <select
                                        id="Instantreport"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="instantreports"
                                        ng-options="instantreport.Instantreport.id as instantreport.Instantreport.name for instantreport in instantreports"
                                        ng-model="post.instantreport_id">
                                    </select>
                                    <div ng-repeat="error in errors.instantreport_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.report_format}">
                                    <label class="control-label" for="InstantreportFormat">
                                        <?php echo __('Report format'); ?>
                                    </label>
                                    <select
                                        id="InstantreportFormat"
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

                                <div class="form-group" ng-class="{'has-error': errors.from_date}">
                                    <label class="control-label">
                                        <?php echo __('From'); ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        type="text"
                                        ng-model="post.from_date"
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
                                        type="text"
                                        ng-model="post.to_date"
                                        placeholder="<?php echo __('DD.MM.YYYY'); ?>">
                                    <div ng-repeat="error in errors.to_date">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>
                                <div class="card margin-top-10">
                                    <div class="card-body">
                                        <div class="float-right">
                                            <button class="btn btn-primary" ng-click="createInstantReport()"
                                                    type="submit"><?php echo __('Generate report'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div ng-if="tabName == 'instantReport'" id="instantReport">
                            <div class="row margin-top-10 font-md padding-bottom-10">
                                <div class="col-md-9 text-left">
                                    <i class="fa fa-calendar txt-color-blueDark"></i>
                                    <?php echo __('Analysis period: '); ?>
                                    {{reportDetails.from}}
                                    <i class="fas fa-long-arrow-alt-right"></i>
                                    {{reportDetails.to}}
                                </div>
                                <div class="col-md-3 text-left">

                                </div>
                            </div>
                            <div class="row" ng-if="!reportDetails.summary" ng-repeat="report in reportData">

                                <div class="col-lg-12 padding-5">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>
                                                <i class="fa fa-desktop"></i>
                                                <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                                    <a ui-sref="HostsBrowser({id:report.Host.id})">
                                                        {{report.Host.name}}
                                                    </a>
                                                <?php else: ?>
                                                    {{report.Host.name}}
                                                <?php endif; ?>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row" ng-if="report.Host.reportData">
                                                <div class="col col-md-12 padding-2">
                                                    <host-availability-pie-chart chart-id="report.Host.id"
                                                                                 data="report.Host"></host-availability-pie-chart>
                                                </div>
                                            </div>
                                            <div ng-repeat="service in report.Host.Services">
                                                <div class="row">
                                                    <div class="col-lg-12 padding-2">
                                                        <i class="fa fa-cog"></i>
                                                        <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                                            <a ui-sref="ServicesBrowser({id:service.Service.id})">
                                                                {{service.Service.name}}
                                                            </a>
                                                        <?php else: ?>
                                                            {{service.Service.name}}
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="row" ng-if="service.Service.reportData">
                                                    <div class="col-lg-6 col-md-12">
                                                        <service-availability-bar-chart
                                                            chart-id="service.Service.id"
                                                            data="service.Service"></service-availability-bar-chart>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" ng-if="reportDetails.summary">
                                <div class="col-lg-12 padding-5"
                                     ng-if="reportDetails.summary_hosts">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>
                                                <i class="fa fa-desktop"></i>
                                                <?php echo __('Hosts summary'); ?>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row" ng-if="reportDetails.summary_hosts.reportData">
                                                <div class="col col-md-12 padding-2">
                                                    <host-availability-pie-chart chart-id="'hostSummary'"
                                                                                 data="reportDetails.summary_hosts"></host-availability-pie-chart>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 padding-5"
                                     ng-if="reportDetails.summary_services">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>
                                                <i class="fa fa-cog"></i>
                                                <?php echo __('Services summary'); ?>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row" ng-if="reportDetails.summary_services.reportData">
                                                <div class="col col-md-12 no-padding"
                                                     ng-if="reportDetails.summary_services.reportData">
                                                    <div
                                                        class="col col-lg-3 col-md-12 col-sm-12 col-xs-12 no-padding">
                                                        <service-availability-pie-chart chart-id="'serviceSummary'"
                                                                                        data="reportDetails.summary_services"></service-availability-pie-chart>
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
        </div>
    </div>
</div>
