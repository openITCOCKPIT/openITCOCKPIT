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
        <a ui-sref="ServicedependenciesIndex">
            <i class="fa fa-sitemap"></i> <?php echo __('Service dependencies'); ?>
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
                    <?php echo __('Create new service dependency'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'servicedependencies')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicedependenciesIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form class="form-horizontal" ng-init="successMessage=
            {objectName : '<?php echo __('Service dependency'); ?>' , message: '<?php echo __('created successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="ServicedependenciesContainer">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="ServicedependenciesContainer"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Servicedependency.container_id">
                            </select>
                            <div ng-show="post.Servicedependency.container_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div class="help-block">
                                <?php echo __('Service dependencies are an advanced feature that allow you to
                                    suppress notifications for services based on the status of one or more other services.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.services}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-270" aria-hidden="true"></i>
                                <?php echo __('Services'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServicedependenciesServices"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    multiple
                                    chosen="services"
                                    callback="loadServices"
                                    ng-options="service.key as service.value.servicename group by service.value._matchingData.Hosts.name disable when service.disabled for service in services"
                                    ng-model="post.Servicedependency.services._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.services">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.services_dependent}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-90 text-primary" aria-hidden="true"></i>
                                <?php echo __('Dependent services'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServicedependenciesDependentServices"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    multiple
                                    chosen="services_dependent"
                                    callback="loadDependentServices"
                                    ng-options="service.key as service.value.servicename group by service.value._matchingData.Hosts.name disable when service.disabled for service in services_dependent"
                                    ng-model="post.Servicedependency.services_dependent._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.services_dependent">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.servicegroups}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-270" aria-hidden="true"></i>
                                <?php echo __('Service groups'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServicedependencyServicegroups"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    multiple
                                    chosen="servicegroups"
                                    ng-options="servicegroup.key as servicegroup.value disable when servicegroup.disabled for servicegroup in servicegroups"
                                    ng-model="post.Servicedependency.servicegroups._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.servicegroups">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.servicegroups_dependent}">
                            <label class="control-label">
                                <i class="fa fa-sitemap fa-rotate-90 text-primary" aria-hidden="true"></i>
                                <?php echo __('Dependent service groups'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServicedependenciesDependentServicegroups"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    multiple
                                    chosen="servicegroups_dependent"
                                    ng-options="servicegroup.key as servicegroup.value disable when servicegroup.disabled for servicegroup in servicegroups_dependent"
                                    ng-model="post.Servicedependency.servicegroups_dependent._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.servicegroups_dependent">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.timeperiod_id}">
                            <label class="control-label">
                                <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                    <a ui-sref="TimeperiodsEdit({id:post.Servicedependency.timeperiod_id})"
                                       ng-if="post.Servicedependency.timeperiod_id > 0">
                                        <?php echo __('Timeperiod'); ?>
                                    </a>
                                    <span ng-if="!post.Servicedependency.timeperiod_id"><?php echo __('Timeperiod'); ?></span>
                                <?php else: ?>
                                    <?php echo __('Timeperiod'); ?>
                                <?php endif; ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServicedependenciesTimeperiod"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    chosen="timeperiods"
                                    ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                    ng-model="post.Servicedependency.timeperiod_id">
                                    <option></option>
                                </select>
                            </div>
                            <div ng-repeat="error in errors.timeperiod_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.inherits_parent}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.inherits_parent}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="inheritsParent"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       ng-model="post.Servicedependency.inherits_parent">
                                <label class="custom-control-label" for="inheritsParent">
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
                                            <?= __('Disable active service checking of dependend service/s, if master service state is one of the selected states.'); ?>
                                        </div>
                                    </legend>
                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_ok}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_ok"
                                               ng-model="post.Servicedependency.execution_fail_on_ok">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_ok">
                                            <span class="badge badge-success notify-label"><?php echo __('Ok'); ?></span>
                                            <i class="checkbox-success"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_warning}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_warning"
                                               ng-model="post.Servicedependency.execution_fail_on_warning">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_warning">
                                            <span class="badge badge-warning notify-label"><?php echo __('Warning'); ?></span>
                                            <i class="checkbox-warning"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_critical}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_critical"
                                               ng-model="post.Servicedependency.execution_fail_on_critical">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_critical">
                                            <span class="badge badge-danger notify-label"><?php echo __('Critical'); ?></span>
                                            <i class="checkbox-danger"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_unknown}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_unknown"
                                               ng-model="post.Servicedependency.execution_fail_on_unknown">
                                        <label class="custom-control-label"
                                               for="execution_fail_on_unknown">
                                            <span class="badge badge-secondary notify-label"><?php echo __('Unknown'); ?></span>
                                            <i class="checkbox-secondary"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.execution_fail_on_pending}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="execution_fail_on_pending"
                                               ng-model="post.Servicedependency.execution_fail_on_pending">
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
                                               id="execution_none"
                                               ng-model="post.Servicedependency.execution_none">
                                        <label class="custom-control-label"
                                               for="execution_none">
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
                                            <?= __('Disable service notifications of dependend service/s, if master service state is one of the selected states.'); ?>
                                        </div>
                                    </legend>
                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_ok}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_ok"
                                               ng-model="post.Servicedependency.notification_fail_on_ok">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_ok">
                                            <span class="badge badge-success notify-label"><?php echo __('Ok'); ?></span>
                                            <i class="checkbox-success"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_warning}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_warning"
                                               ng-model="post.Servicedependency.notification_fail_on_warning">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_warning">
                                            <span class="badge badge-warning notify-label"><?php echo __('Warning'); ?></span>
                                            <i class="checkbox-warning"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_critical}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_critical"
                                               ng-model="post.Servicedependency.notification_fail_on_critical">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_critical">
                                            <span class="badge badge-danger notify-label"><?php echo __('Critical'); ?></span>
                                            <i class="checkbox-danger"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_unknown}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_unknown"
                                               ng-model="post.Servicedependency.notification_fail_on_unknown">
                                        <label class="custom-control-label"
                                               for="notification_fail_on_unknown">
                                            <span class="badge badge-secondary notify-label"><?php echo __('Unknown'); ?></span>
                                            <i class="checkbox-secondary"></i>
                                        </label>
                                    </div>

                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.notification_fail_on_pending}">
                                        <input type="checkbox" class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notification_fail_on_pending"
                                               ng-model="post.Servicedependency.notification_fail_on_pending">
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
                                               ng-model="post.Servicedependency.notification_none">
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
                                            type="submit" ng-click="submit()"><?php echo __('Create service dependency'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='ServicedependenciesIndex'
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
