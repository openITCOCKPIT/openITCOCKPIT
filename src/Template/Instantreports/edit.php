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
        <i class="fa fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit instant report'); ?>
                    <span class="fw-300"><i>{{post.Instantreport.name}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'instantreports')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='InstantreportsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal" ng-init="successMessage=
                        {objectName : '<?php echo __('Instant report'); ?>' , message: '<?php echo __('updated successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="InstantreportContainer">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                    id="InstantreportContainer"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="containers"
                                    ng-options="container.key as container.value for container in containers"
                                    ng-model="post.Instantreport.container_id">
                            </select>
                            <div ng-show="post.Instantreport.container_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                    class="form-control"
                                    type="text"
                                    ng-model="post.Instantreport.name">
                            <div ng-repeat="error in errors.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.type}">
                            <label class="control-label" for="InstantreportType">
                                <?php echo __('Type'); ?>
                            </label>
                            <select
                                    id="InstantreportType"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="{}"
                                    ng-model="post.Instantreport.type">
                                <option ng-value="1"><?php echo __('Host groups'); ?></option>
                                <option ng-value="2"><?php echo __('Hosts'); ?></option>
                                <option ng-value="3"><?php echo __('Service groups'); ?></option>
                                <option ng-value="4"><?php echo __('Services'); ?></option>
                            </select>
                            <div ng-repeat="error in errors.type">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?php echo __('Select the object type, which should be evaluated by the report.'); ?>
                            </div>
                        </div>
                        <div ng-switch="post.Instantreport.type" class="margin-bottom-10">
                            <div class="form-group required" ng-class="{'has-error': errors.hostgroups}"
                                 ng-switch-when="1">
                                <label class="control-label" for="HostgroupId">
                                    <?php echo __('Host groups'); ?>
                                </label>
                                <select
                                        id="HostgroupId"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="hostgroups"
                                        multiple
                                        ng-options="hostgroup.key as hostgroup.value for hostgroup in hostgroups"
                                        ng-model="post.Instantreport.hostgroups._ids">
                                </select>
                                <div ng-repeat="error in errors.hostgroups">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': errors.hosts}" ng-switch-when="2">
                                <label class="control-label" for="HostId">
                                    <?php echo __('Hosts'); ?>
                                </label>
                                <select
                                        multiple
                                        id="HostId"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="hosts"
                                        callback="loadHosts"
                                        ng-options="host.key as host.value for host in hosts"
                                        ng-model="post.Instantreport.hosts._ids">
                                </select>
                                <div ng-repeat="error in errors.hosts">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': errors.servicegroups}"
                                 ng-switch-when="3">
                                <label class="control-label" for="ServicegroupId">
                                    <?php echo __('Service groups'); ?>
                                </label>
                                <select
                                        multiple
                                        id="ServicegroupId"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="servicegroups"
                                        ng-options="servicegroup.key as servicegroup.value for servicegroup in servicegroups"
                                        ng-model="post.Instantreport.servicegroups._ids">
                                </select>
                                <div ng-repeat="error in errors.servicegroups">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': errors.services}"
                                 ng-switch-when="4">
                                <label class="control-label" for="ServiceId">
                                    <?php echo __('Services'); ?>
                                </label>
                                <select
                                        multiple
                                        id="ServiceId"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="services"
                                        callback="loadServices"
                                        ng-options="service.key as service.value.servicename group by service.value._matchingData.Hosts.name disable when service.disabled for service in services"
                                        ng-model="post.Instantreport.services._ids">
                                </select>
                                <div ng-repeat="error in errors.services">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.evaluation}">
                            <label class="control-label">
                                <?php echo __('Evaluation'); ?>
                            </label>
                            <div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input"
                                           name="downtimeType"
                                           id="InstantreportEvaluation1"
                                           ng-model="post.Instantreport.evaluation"
                                           ng-value="1">
                                    <label class="custom-control-label" for="InstantreportEvaluation1">
                                        <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
                                    </label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input"
                                           name="downtimeType"
                                           id="InstantreportEvaluation2"
                                           ng-model="post.Instantreport.evaluation"
                                           ng-value="2">
                                    <label class="custom-control-label" for="InstantreportEvaluation2">
                                        <i class="fa fa-cogs"></i> <?php echo __('Hosts and Services'); ?>
                                    </label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input"
                                           name="downtimeType"
                                           id="InstantreportEvaluation3"
                                           ng-model="post.Instantreport.evaluation"
                                           ng-value="3">
                                    <label class="custom-control-label" for="InstantreportEvaluation3">
                                        <i class="fa fa-cog"></i> <?php echo __('Service'); ?>
                                    </label>
                                </div>
                            </div>

                            <div ng-repeat="error in errors.evaluation">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?php echo __('Choose if <b><u>only</u></b> host, host and services or <b><u>only</u></b> services should be evaluated.'); ?>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.timeperiod_id}">
                            <label class="control-label" for="InstantReportTimeperiod">
                                <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                    <a ui-sref="TimeperiodsEdit({id:post.Instantreport.timeperiod_id})"
                                       ng-if="post.Instantreport.timeperiod_id > 0">
                                        <?php echo __('Timeperiod'); ?>
                                    </a>
                                    <span ng-if="!post.Instantreport.timeperiod_id"><?php echo __('Timeperiod'); ?></span>
                                <?php else: ?>
                                    <?php echo __('Timeperiod'); ?>
                                <?php endif; ?>
                            </label>
                            <select
                                    id="InstantReportTimeperiod"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="timeperiods"
                                    ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                    ng-model="post.Instantreport.timeperiod_id">
                            </select>
                            <div ng-repeat="error in errors.timeperiod_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.reflection}">
                            <label class="control-label" for="InstantreportReflection">
                                <?php echo __('Reflection state'); ?>
                            </label>
                            <select
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    id="InstantreportReflection"
                                    chosen="states"
                                    ng-model="post.Instantreport.reflection">
                                <option ng-value="1">
                                    <?php echo __('soft and hard state'); ?>
                                </option>
                                <option ng-value="2">
                                    <?php echo __('only hard state'); ?>
                                </option>
                            </select>
                            <div ng-repeat="error in errors.reflection">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.downtimes}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.downtimes}">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="InstantreportDowntimes"
                                       ng-false-value="0"
                                       ng-true-value="1"
                                       ng-model="post.Instantreport.downtimes">
                                <label class="custom-control-label" for="InstantreportDowntimes">
                                    <?php echo __('Consider downtimes'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.summary}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.summary}">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="InstantreportSummary"
                                       ng-false-value="0"
                                       ng-true-value="1"
                                       ng-model="post.Instantreport.summary">
                                <label class="custom-control-label" for="InstantreportSummary">
                                    <?php echo __('Summary display'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.send_email}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.send_email}">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="InstantreportSendEmail"
                                       ng-false-value="0"
                                       ng-true-value="1"
                                       ng-model="post.Instantreport.send_email">
                                <label class="custom-control-label" for="InstantreportSendEmail">
                                    <?php echo __('Send email'); ?>
                                </label>
                            </div>
                        </div>
                        <div class="send-interval-holder" ng-if="post.Instantreport.send_email">
                            <div class="form-group {{(post.Instantreport.send_email)?'required':''}}"
                                 ng-class="{'has-error': errors.send_interval}">
                                <label class="control-label" for="InstantreportSendInterval">
                                    <?php echo __('Send interval'); ?>
                                </label>
                                <select
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="{}"
                                        id="InstantreportSendInterval"
                                        ng-model="post.Instantreport.send_interval">
                                    <option ng-value="0" ng-if="!post.Instantreport.send_email">
                                        <?php echo __('NEVER'); ?>
                                    </option>
                                    <option ng-value="1">
                                        <?php echo __('DAY'); ?>
                                    </option>
                                    <option ng-value="2">
                                        <?php echo __('WEEK'); ?>
                                    </option>
                                    <option ng-value="3">
                                        <?php echo __('MONTH'); ?>
                                    </option>
                                    <option ng-value="4">
                                        <?php echo __('YEAR'); ?>
                                    </option>
                                </select>
                                <div ng-repeat="error in errors.send_interval">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': errors.users}">
                                <label class="control-label" for="UserId">
                                    <?php echo __('Users to send'); ?>
                                </label>
                                <select
                                        multiple
                                        id="UserId"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="users"
                                        ng-options="user.key as user.value for user in users"
                                        ng-model="post.Instantreport.users._ids">
                                </select>
                                <div ng-repeat="error in errors.users">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Update instant report'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='InstantreportsIndex'
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
