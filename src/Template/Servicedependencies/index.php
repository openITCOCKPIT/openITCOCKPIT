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
        <a ui-sref="ServicedependenciesIndex">
            <i class="fa fa-sitemap"></i> <?php echo __('Service dependencies'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<!-- ANGAULAR DIRECTIVES -->
<massdelete></massdelete>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service dependencies'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'servicedependencies')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="ServicedependenciesAdd">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
                    <?php endif; ?>

                    <button class="btn btn-xs btn-primary shadow-0" ng-click="triggerFilter()">
                        <i class="fas fa-filter"></i> <?php echo __('Filter'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <!-- Start Filter -->
                    <div class="list-filter card margin-bottom-10" ng-show="showFilter">
                        <div class="card-header">
                            <i class="fa fa-filter"></i> <?php echo __('Filter'); ?>
                        </div>
                        <div class="card-body">
                            <!-- servicename/servicegroup filter start -->
                            <div class="row">
                                <div class="col-lg-6 bordered-vertical-on-left">
                                    <div class="col-xs-12 col-lg-12 margin-bottom-10">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-cog"></i></span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm"
                                                       placeholder="<?php echo __('Filter by service name'); ?>"
                                                       ng-model="filter.Services.servicename"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-focus="serviceFocus=true;filter.ServicesDependent.servicename='';serviceDependentFocus=false;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row padding-top-5 padding-bottom-5 padding-left-25">
                                        <div class="col-xs-12 help-block helptext text-info">
                                            <i class="fa fa-info-circle text-info"></i>
                                            <?php echo __('You can either search for  <b>"service"</b> OR <b>"dependent service"</b>. Opposing field will be reset automatically'); ?>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-lg-12 margin-bottom-10">
                                        <div class="form-group">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <div class='icon-stack'>
                                                            <i class="fas fa-cog icon-stack-3x opacity-100 "></i>
                                                            <i class="fas fa-sitemap opacity-100 fa-xs text-primary cornered cornered-lr"></i>
                                                        </div>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control "
                                                       placeholder="<?php echo __('Filter by dependent service name'); ?>"
                                                       ng-model="filter.ServicesDependent.servicename"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-focus="serviceDependentFocus=true;filter.Services.servicename='';serviceFocus=false;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 bordered-vertical-on-left">
                                    <div class="col-xs-12 col-lg-12 margin-bottom-10">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-cogs"></i></span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm"
                                                       placeholder="<?php echo __('Filter by service group'); ?>"
                                                       ng-model="filter.Servicegroups.name"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-focus="servicegroupFocus=true;filter.ServicegroupsDependent.name='';servicegroupDependentFocus=false;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row padding-top-5 padding-bottom-5 padding-left-25">
                                        <div class="col-xs-12 no-padding help-block helptext text-info">
                                            <i class="fa fa-info-circle text-info"></i>
                                            <?php echo __('You can either search for  <b>"service group"</b> OR <b>"dependent service group"</b>.  Opposing field will be reset automatically'); ?>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-lg-12 margin-bottom-10">
                                        <div class="form-group">
                                            <div class="input-group  input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <div class='icon-stack'>
                                                            <i class="fas fa-cogs icon-stack-3x opacity-100 "></i>
                                                            <i class="fas fa-sitemap opacity-100 fa-xs text-primary cornered cornered-lr"></i>
                                                        </div>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control"
                                                       placeholder="<?php echo __('Filter by dependent service group'); ?>"
                                                       ng-model="filter.ServicegroupsDependent.name"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-focus="servicegroupDependentFocus=true;filter.Servicegroups.name='';servicegroupFocus=false;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- servicename/servicegroup filter end -->

                            <!-- execution fail on filter start -->
                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Execution fail on ...'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="execution_fail_on_ok"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.execution_fail_on_ok"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-up"
                                                       for="execution_fail_on_ok"><?php echo __('Ok'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="execution_fail_on_warning"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.execution_fail_on_warning"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-warning"
                                                       for="execution_fail_on_warning"><?php echo __('Warning'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="execution_fail_on_critical"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.execution_fail_on_critical"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-critical"
                                                       for="execution_fail_on_critical"><?php echo __('Critical'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="execution_fail_on_unknown"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.execution_fail_on_unknown"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-unknown"
                                                       for="execution_fail_on_unknown"><?php echo __('Unknown'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="execution_fail_on_pending"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.execution_fail_on_pending"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label"
                                                       for="execution_fail_on_pending"><?php echo __('Pending'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="execution_none"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.execution_none"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label"
                                                       for="execution_none"><?php echo __('Execution none'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Notification fail on ...'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="notification_fail_on_ok"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.notification_fail_on_ok"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-ok"
                                                       for="notification_fail_on_ok"><?php echo __('Ok'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="notification_fail_on_warning"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.notification_fail_on_warning"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-warning"
                                                       for="notification_fail_on_warning"><?php echo __('Warning'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="notification_fail_on_critical"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.notification_fail_on_critical"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-critical"
                                                       for="notification_fail_on_critical"><?php echo __('Critical'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="notification_fail_on_unknown"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.notification_fail_on_unknown"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label custom-control-label-unknown"
                                                       for="notification_fail_on_unknown"><?php echo __('Unknown'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="notification_fail_on_pending"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.notification_fail_on_pending"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label"
                                                       for="notification_fail_on_pending"><?php echo __('Pending'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="notification_none"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.notification_none"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="1"
                                                       ng-false-value="">
                                                <label class="custom-control-label"
                                                       for="notification_none"><?php echo __('Notification none'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Options'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="inherits_parent"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.inherits_parent[1]"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="true"
                                                       ng-false-value="false">
                                                <label class="custom-control-label"
                                                       for="inherits_parent"><?php echo __('Inherits parent'); ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="not_inherits_parent"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicedependencies.inherits_parent[0]"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-true-value="true"
                                                       ng-false-value="false">
                                                <label class="custom-control-label"
                                                       for="not_inherits_parent"><?php echo __('Not inherits parent'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                            <!-- execution fail on filter end -->
                            <div class="float-right">
                                <button type="button" ng-click="resetFilter()"
                                        class="btn btn-xs btn-danger">
                                    <?php echo __('Reset Filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End Filter -->
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="text-align-center"><i class="fa fa-check-square"
                                                                 aria-hidden="true"></i></th>
                                <th><?php echo __('Services'); ?></th>
                                <th><?php echo __('Dependent services'); ?></th>
                                <th><?php echo __('Service groups'); ?></th>
                                <th><?php echo __('Dependent service groups'); ?></th>
                                <th><?php echo __('Timeperiod'); ?></th>
                                <th><?php echo __('Inherits parent'); ?></th>
                                <th class="no-sort"><?php echo __('Execution failure criteria'); ?></th>
                                <th class="no-sort"><?php echo __('Notification failure criteria'); ?></th>
                                <th class="no-sort text-center"><i class="fa fa-gear"></i></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="servicedependency in servicedependencies">
                                <td class="text-center" class="width-15">
                                    <?php if ($this->Acl->hasPermission('delete', 'servicedependencies')): ?>
                                        <input type="checkbox"
                                               ng-model="massChange[servicedependency.id]"
                                               ng-show="servicedependency.allowEdit">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <li ng-repeat="service in servicedependency.services">
                                            <div class="label-group label-breadcrumb label-breadcrumb-default padding-2"
                                                 title="{{service.servicename}}">
                                                <label class="badge badge-secondary label-xs">
                                                    <i class="fa fa-sitemap fa-rotate-270" aria-hidden="true"></i>
                                                </label>
                                                <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                                    <a ui-sref="ServicesEdit({id:service.id})"
                                                       class="badge badge-light label-xs">
                                                        {{service.servicename}}
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light label-xs">
                                                        {{service.servicename}}
                                                    </span>
                                                <?php endif; ?>
                                                <i ng-if="service.disabled == 1"
                                                   class="fa fa-power-off text-danger"
                                                   title="disabled" aria-hidden="true"></i>
                                            </div>
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <li ng-repeat="service in servicedependency.services_dependent">
                                            <div class="label-group label-breadcrumb label-breadcrumb-primary padding-2"
                                                 title="{{service.servicename}}">
                                                <label class="badge badge-primary label-xs">
                                                    <i class="fa fa-sitemap fa-rotate-90" aria-hidden="true"></i>
                                                </label>
                                                <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                                    <a ui-sref="ServicesEdit({id:service.id})"
                                                       class="badge badge-light label-xs">
                                                        {{service.servicename}}
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light label-xs">
                                                        {{service.servicename}}
                                                    </span>
                                                <?php endif; ?>
                                                <i ng-if="service.disabled == 1"
                                                   class="fa fa-power-off text-danger"
                                                   title="disabled" aria-hidden="true"></i>
                                            </div>
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <li ng-repeat="servicegroup in servicedependency.servicegroups">
                                            <div class="label-group label-breadcrumb label-breadcrumb-default padding-2"
                                                 title="{{servicegroup.container.name}}">
                                                <label class="badge badge-secondary label-xs">
                                                    <i class="fa fa-sitemap fa-rotate-270" aria-hidden="true"></i>
                                                </label>
                                                <?php if ($this->Acl->hasPermission('edit', 'servicegroups')): ?>
                                                    <a ui-sref="ServicegroupsEdit({id: servicegroup.id})"
                                                       class="badge badge-light label-xs">
                                                        {{servicegroup.container.name}}
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light label-xs">
                                                        {{servicegroup.container.name}}
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <li ng-repeat="servicegroup in servicedependency.servicegroups_dependent">
                                            <div class="label-group label-breadcrumb label-breadcrumb-primary padding-2"
                                                 title="{{servicegroup.container.name}}">
                                                <label class="badge badge-primary label-xs">
                                                    <i class="fa fa-sitemap fa-rotate-90" aria-hidden="true"></i>
                                                </label>
                                                <?php if ($this->Acl->hasPermission('edit', 'servicegroups')): ?>
                                                    <a ui-sref="ServicegroupsEdit({id: servicegroup.id})"
                                                       class="badge badge-light label-xs">
                                                        {{servicegroup.container.name}}
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light label-xs">
                                                        {{servicegroup.container.name}}
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                        <a ui-sref="TimeperiodsEdit({id: servicedependency.timeperiod.id})">{{
                                            servicedependency.timeperiod.name }}</a>
                                    <?php else: ?>
                                        {{ servicedependency.timeperiod.name }}
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="label-forced badge-success margin-right-5"
                                          title="<?php echo __('Yes'); ?>"
                                          ng-show="servicedependency.inherits_parent === 1">
                                        <?php echo __('Yes'); ?>
                                    </span>
                                    <span class="label-forced badge-danger margin-right-5"
                                          title="<?php echo __('No'); ?>"
                                          ng-show="servicedependency.inherits_parent === 0">
                                        <?php echo __('No'); ?>
                                    </span>
                                </td>
                                <td class="text-align-center">
                                    <div>
                                        <span class="label-forced badge-success margin-right-5"
                                              title="<?php echo __('Ok'); ?>"
                                              ng-show="servicedependency.execution_fail_on_ok">
                                            <?php echo __('O'); ?>
                                        </span>
                                        <span class="label-forced badge-warning margin-right-5"
                                              title="<?php echo __('Warning'); ?>"
                                              ng-show="servicedependency.execution_fail_on_warning">
                                            <?php echo __('W'); ?>
                                        </span>
                                        <span class="label-forced badge-danger margin-right-5"
                                              title="<?php echo __('Critical'); ?>"
                                              ng-show="servicedependency.execution_fail_on_critical">
                                            <?php echo __('C'); ?>
                                        </span>
                                        <span class="label-forced badge-secondary margin-right-5"
                                              title="<?php echo __('Unknown'); ?>"
                                              ng-show="servicedependency.execution_fail_on_unknown">
                                            <?php echo __('U'); ?>
                                        </span>
                                        <span class="label-forced badge-primary margin-right-5"
                                              title="<?php echo __('Pending'); ?>"
                                              ng-show="servicedependency.execution_fail_on_pending">
                                            <?php echo __('P'); ?>
                                        </span>
                                        <span class="label-forced badge-primary margin-right-5"
                                              title="<?php echo __('Execution none'); ?>"
                                              ng-show="servicedependency.execution_none">
                                            <?php echo __('N'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-align-center">
                                    <div>
                                        <span class="label-forced badge-success margin-right-5"
                                              title="<?php echo __('Ok'); ?>"
                                              ng-show="servicedependency.notification_fail_on_ok">
                                            <?php echo __('O'); ?>
                                        </span>
                                        <span class="label-forced badge-warning margin-right-5"
                                              title="<?php echo __('Warning'); ?>"
                                              ng-show="servicedependency.notification_fail_on_warning">
                                            <?php echo __('W'); ?>
                                        </span>
                                        <span class="label-forced badge-danger margin-right-5"
                                              title="<?php echo __('Critical'); ?>"
                                              ng-show="servicedependency.notification_fail_on_critical">
                                            <?php echo __('C'); ?>
                                        </span>
                                        <span class="label-forced badge-secondary margin-right-5"
                                              title="<?php echo __('Unknown'); ?>"
                                              ng-show="servicedependency.notification_fail_on_unknown">
                                            <?php echo __('U'); ?>
                                        </span>
                                        <span class="label-forced badge-primary margin-right-5"
                                              title="<?php echo __('Pending'); ?>"
                                              ng-show="servicedependency.notification_fail_on_pending">
                                            <?php echo __('P'); ?>
                                        </span>
                                        <span class="label-forced badge-primary margin-right-5"
                                              title="<?php echo __('Notification none'); ?>"
                                              ng-show="servicedependency.notification_none">
                                            <?php echo __('N'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'servicedependencies')): ?>
                                            <a ui-sref="ServicedependenciesEdit({id: servicedependency.id})"
                                               ng-if="servicedependency.allowEdit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!servicedependency.allowEdit"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'servicedependencies')): ?>
                                                <a ui-sref="ServicedependenciesEdit({id: servicedependency.id})"
                                                   ng-if="servicedependency.allowEdit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'servicedependencies')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(servicedependency))"
                                                   ng-if="servicedependency.allowEdit"
                                                   href="javascript:void(0);"
                                                   class="dropdown-item txt-color-red">
                                                    <i class="fa fa-trash"></i>
                                                    <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="servicedependencies.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                        <div class="row margin-top-10 margin-bottom-10">
                            <div class="col-xs-12 col-md-2 text-muted text-center">
                                <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="selectAll()" class="pointer">
                                    <i class="fas fa-lg fa-check-square"></i>
                                    <?php echo __('Select all'); ?>
                                </span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="undoSelection()" class="pointer">
                                    <i class="fas fa-lg fa-square"></i>
                                    <?php echo __('Undo selection'); ?>
                                </span>
                            </div>
                            <?php if ($this->Acl->hasPermission('delete', 'servicedependencies')): ?>
                                <div class="col-xs-12 col-md-2 txt-color-red">
                                    <span ng-click="confirmDelete(getObjectsForDelete())" class="pointer">
                                        <i class="fas fa-trash"></i>
                                        <?php echo __('Delete selected'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
