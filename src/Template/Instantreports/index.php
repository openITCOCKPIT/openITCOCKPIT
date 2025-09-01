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
                    <?php echo __('Instant reports'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'instantreports')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="InstantreportsAdd">
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
                            <div class="row">
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by instant report name'); ?>"
                                                   ng-model="filter.instantreport.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Type'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterTypeHostgroups"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.type.hostgroups"
                                                       ng-true-value="1"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterAdd"><?php echo __('Host groups'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterTypeHosts"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.type.hosts"
                                                       ng-true-value="2"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterTypeHosts"><?php echo __('Hosts'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterTypeServicegroups"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.type.servicegroups"
                                                       ng-true-value="3"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterTypeServicegroups"><?php echo __('Service groups'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterTypeServices"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.type.services"
                                                       ng-true-value="4"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterTypeServices"><?php echo __('Services'); ?></label>
                                            </div>


                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Evaluation'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterEvaluationHosts"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.evaluation.hosts"
                                                       ng-true-value="1"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterEvaluationHosts"><?php echo __('Hosts'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterEvaluationHostsAndServices"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.evaluation.hostsandservices"
                                                       ng-true-value="2"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterEvaluationHostsAndServices"><?php echo __('Hosts and Services'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterEvaluationServices"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.instantreport.evaluation.services"
                                                       ng-true-value="3"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterEvaluationServices"><?php echo __('Services'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                            <div class="float-right">
                                <button type="button" ng-click="resetFilter()"
                                        class="btn btn-xs btn-danger">
                                    <?php echo __('Reset Filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- END FILTER -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort sorting_disabled width-15">
                                    <i class="fa fa-check-square"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Instantreports.name')">
                                    <i class="fa" ng-class="getSortClass('Instantreports.name')"></i>
                                    <?php echo __('Name'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Type'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Evaluation'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Timeperiods.name')">
                                    <i class="fa" ng-class="getSortClass('Timeperiods.name')"></i>
                                    <?php echo __('Time period'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Instantreports.summary')">
                                    <i class="fa" ng-class="getSortClass('Instantreports.summary')"></i>
                                    <?php echo __('Summary display'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Instantreports.downtimes')">
                                    <i class="fa" ng-class="getSortClass('Instantreports.downtimes')"></i>
                                    <?php echo __('Consider downtimes'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Send interval'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Send to'); ?>
                                </th>
                                <th class="no-sort text-center width-70">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="instantreport in instantreports">
                                <td class="text-center" class="width-15">
                                    <?php if ($this->Acl->hasPermission('delete', 'instantreports')): ?>
                                        <input type="checkbox"
                                               ng-model="massChange[instantreport.Instantreport.id]">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    {{ instantreport.Instantreport.name }}
                                </td>
                                <td>
                                    <span ng-show="instantreport.Instantreport.type === 1">
                                        <i class="fa fa-server"></i>
                                        <?php echo __('Host groups'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.type === 2">
                                        <i class="fa fa-desktop"></i>
                                        <?php echo __('Hosts'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.type === 3">
                                        <i class="fa fa-cogs"></i>
                                        <?php echo __('Service groups'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.type === 4">
                                        <i class="fa fa-cog"></i>
                                        <?php echo __('Services'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span ng-show="instantreport.Instantreport.evaluation === 1">
                                        <i class="fa fa-desktop"></i>
                                        <?php echo __('Hosts'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.evaluation === 2">
                                        <i class="fa fa-cogs"></i>
                                        <?php echo __('Hosts and services'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.evaluation === 3">
                                        <i class="fa fa-cog"></i>
                                        <?php echo __('Services'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    {{ instantreport.Timeperiod.name }}
                                </td>
                                <td class="text-center">
                                    <label class="badge badge-success"
                                           ng-show="instantreport.Instantreport.summary === 1">
                                        <?php echo __('Yes'); ?>
                                    </label>
                                    <label class="badge badge-danger"
                                           ng-show="instantreport.Instantreport.summary === 0">
                                        <?php echo __('No'); ?>
                                    </label>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success"
                                          ng-show="instantreport.Instantreport.downtimes === 1">
                                        <?php echo __('Yes'); ?>
                                    </span>
                                    <span class="badge badge-danger"
                                          ng-show="instantreport.Instantreport.downtimes === 0">
                                        <?php echo __('No'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span ng-show="instantreport.Instantreport.send_interval === 0">
                                        <?php echo __('NEVER'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.send_interval === 1">
                                        <?php echo __('DAY'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.send_interval === 2">
                                        <?php echo __('WEEK'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.send_interval === 3">
                                        <?php echo __('MONTH'); ?>
                                    </span>
                                    <span ng-show="instantreport.Instantreport.send_interval === 4">
                                        <?php echo __('YEAR'); ?>
                                    </span>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <ul class="list-unstyled">
                                            <li ng-repeat="user in instantreport.User">
                                                <span>
                                                    {{ user.firstname }} {{ user.lastname }}
                                                </span>
                                            </li>
                                        </ul>
                                    </ul>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'instantreports')): ?>
                                            <a ui-sref="InstantreportsEdit({id:instantreport.Instantreport.id})"
                                               ng-if="instantreport.allowEdit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!instantreport.allowEdit"
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
                                            <?php if ($this->Acl->hasPermission('edit', 'instantreports')): ?>
                                                <a ui-sref="InstantreportsEdit({id:instantreport.Instantreport.id})"
                                                   ng-if="instantreport.allowEdit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('generate', 'instantreports')): ?>
                                                <a class="dropdown-item"
                                                   ui-sref="InstantreportsGenerate({id:instantreport.Instantreport.id})">
                                                    <i class="fa fa-file-invoice"></i>
                                                    <?php echo __('Generate'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'instantreports')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(instantreport))"
                                                   ng-if="instantreport.allowEdit"
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
                        <div class="margin-top-10" ng-show="instantreports.length == 0">
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
                                    <i class="fas fa-check-square"></i>
                                    <?php echo __('Select all'); ?>
                                </span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="undoSelection()" class="pointer">
                                    <i class="fas fa-square"></i>
                                    <?php echo __('Undo selection'); ?>
                                </span>
                            </div>
                            <?php if ($this->Acl->hasPermission('delete', 'instantreports')): ?>
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
