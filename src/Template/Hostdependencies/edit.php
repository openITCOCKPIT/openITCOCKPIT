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
$timezones = \itnovum\openITCOCKPIT\Core\Timezone::listTimezones();
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="HostdependenciesIndex">
            <i class="fa fa-sitemap"></i> <?php echo __('Host dependencies'); ?>
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
                    <?php echo __('Edit host dependency'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hostdependencies')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostdependenciesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form class="form-horizontal" ng-init="successMessage=
            {objectName : '<?php echo __('Host dependency'); ?>' , message: '<?php echo __('saved successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="HostdependenciesContainer">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                    id="HostdependenciesContainer"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="containers"
                                    ng-options="container.key as container.value for container in containers"
                                    ng-model="post.Hostdependency.container_id">
                            </select>
                            <div ng-show="post.Hostdependency.container_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div class="help-block">
                                <?php echo __('Host dependencies are an advanced feature that allow you to
                                    suppress notifications for hosts based on the status of one or more other hosts.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.hosts}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-270" aria-hidden="true"></i>
                                <?php echo __('Hosts'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                        id="HostdependenciesHosts"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="hosts"
                                        callback="loadHosts"
                                        ng-options="host.key as host.value disable when host.disabled for host in hosts"
                                        ng-model="post.Hostdependency.hosts._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.hosts">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.hosts_dependent}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-90 text-primary" aria-hidden="true"></i>
                                <?php echo __('Dependent hosts'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                        id="HostdependenciesDependentHosts"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="hosts_dependent"
                                        callback="loadDependentHosts"
                                        ng-options="host.key as host.value disable when host.disabled for host in hosts_dependent"
                                        ng-model="post.Hostdependency.hosts_dependent._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.hosts_dependent">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.hostgroups}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-270" aria-hidden="true"></i>
                                <?php echo __('Host groups'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                        id="HostdependencyHostgroups"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="hostgroups"
                                        ng-options="hostgroup.key as hostgroup.value disable when hostgroup.disabled for hostgroup in hostgroups"
                                        ng-model="post.Hostdependency.hostgroups._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.hostgroups">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.hostgroups_dependent}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-90 text-primary" aria-hidden="true"></i>
                                <?php echo __('Dependent host groups'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                        id="HostedepemdenciesDependentHostgroups"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="hostgroups_dependent"
                                        ng-options="hostgroup.key as hostgroup.value disable when hostgroup.disabled for hostgroup in hostgroups_dependent"
                                        ng-model="post.Hostdependency.hostgroups_dependent._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.hostgroups_dependent">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">
                                <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                    <a ui-sref="TimeperiodsEdit({id:post.Hostdependency.timeperiod_id})"
                                       ng-if="post.Hostdependency.timeperiod_id > 0">
                                        <?php echo __('Timeperiod'); ?>
                                    </a>
                                    <span ng-if="!post.Hostdependency.timeperiod_id"><?php echo __('Timeperiod'); ?></span>
                                <?php else: ?>
                                    <?php echo __('Timeperiod'); ?>
                                <?php endif; ?>
                            </label>
                            <div class="input-group">
                                <select
                                        id="HostedepemdenciesTimeperiod"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        chosen="timeperiods"
                                        ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                        ng-model="post.Hostdependency.timeperiod_id">
                                    <option></option>
                                </select>
                            </div>

                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.inherits_parent}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.inherits_parent}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="HostdependenciesInheritParent"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       ng-model="post.Hostdependency.inherits_parent">
                                <label class="custom-control-label" for="HostdependenciesInheritParent">
                                    <?php echo __('Inherits parent'); ?>
                                </label>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-lg-6">
                                <fieldset>
                                    <legend class="fs-md">
                                        <div class="required">
                                            <label>
                                                <?php echo __('Execution failure criteria'); ?>
                                            </label>
                                        </div>
                                        <div class="help-block">
                                            <?= __('Disable active host checking of dependend host/s, if master host state is one of the selected states.'); ?>
                                        </div>
                                    </legend>
                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_up}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_up"
                                               ng-model="post.Hostdependency.execution_fail_on_up">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_up">
                                            <span class="badge badge-success notify-label"><?php echo __('Up'); ?></span>
                                            <i class="checkbox-success"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_down}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_down"
                                               ng-model="post.Hostdependency.execution_fail_on_down">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_down">
                                            <span class="badge badge-danger notify-label"><?php echo __('Down'); ?></span>
                                            <i class="checkbox-danger"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_unreachable}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_unreachable"
                                               ng-model="post.Hostdependency.execution_fail_on_unreachable">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_unreachable">
                                            <span class="badge badge-secondary notify-label"><?php echo __('Unreachable'); ?></span>
                                            <i class="checkbox-secondary"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_pending}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_pending"
                                               ng-model="post.Hostdependency.execution_fail_on_pending">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_pending">
                                            <span class="badge badge-primary notify-label"><?php echo __('Pending'); ?></span>
                                            <i class="checkbox-primary"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_none}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_none"
                                               ng-model="post.Hostdependency.execution_none">
                                        <label class="custom-control-label"
                                               for="execution_fail_none">
                                            <span class="badge badge-primary notify-label"><?php echo __('Execution none'); ?></span>
                                            <i class="checkbox-primary"></i>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-lg-6">
                                <fieldset>
                                    <legend class="fs-md">
                                        <div class="required">
                                            <label>
                                                <?php echo __('Notification failure criteria'); ?>
                                            </label>
                                        </div>
                                        <div class="help-block">
                                            <?= __('Disable host notifications of dependend host/s, if master host state is one of the selected states.'); ?>
                                        </div>
                                    </legend>
                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_up}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_up"
                                               ng-model="post.Hostdependency.notification_fail_on_up">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_up">
                                            <span class="badge badge-success notify-label"><?php echo __('Up'); ?></span>
                                            <i class="checkbox-success"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_down}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_down"
                                               ng-model="post.Hostdependency.notification_fail_on_down">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_down">
                                            <span class="badge badge-danger notify-label"><?php echo __('Down'); ?></span>
                                            <i class="checkbox-danger"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_unreachable}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_unreachable"
                                               ng-model="post.Hostdependency.notification_fail_on_unreachable">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_unreachable">
                                            <span class="badge badge-secondary notify-label"><?php echo __('Unreachable'); ?></span>
                                            <i class="checkbox-secondary"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_pending}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_pending"
                                               ng-model="post.Hostdependency.notification_fail_on_pending">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_pending">
                                            <span class="badge badge-primary notify-label"><?php echo __('Pending'); ?></span>
                                            <i class="checkbox-primary"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_none}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_none"
                                               ng-model="post.Hostdependency.notification_none">
                                        <label class="custom-control-label"
                                               for="notification_none">
                                            <span class="badge badge-primary notify-label"><?php echo __('Notification none'); ?></span>
                                            <i class="checkbox-primary"></i>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"
                                            ng-click="submit()"><?php echo __('Update host dependency'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='HostdependenciesIndex'
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
