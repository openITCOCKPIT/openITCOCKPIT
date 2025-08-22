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
        <a ui-sref="HostsIndex">
            <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-trash"></i> <?php echo __('Deleted'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Deleted hosts'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('index', 'hosts')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="HostsIndex" role="tab">
                                    <i class="fa fa-stethoscope">&nbsp;</i> <?php echo __('Monitored'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('notMonitored', 'hosts')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="HostsNotMonitored" role="tab">
                                    <i class="fa fa-user-md">&nbsp;</i> <?php echo __('Not monitored'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('disabled', 'hosts')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="HostsDisabled" role="tab">
                                    <i class="fa fa-power-off">&nbsp;</i> <?php echo __('Disabled'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" ui-sref="DeletedHostsIndex" role="tab">
                                <i class="fa fa-trash">&nbsp;</i> <?php echo __('Deleted'); ?>
                            </a>
                        </li>
                    </ul>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'hosts')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="HostsAdd">
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
                                                <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                                   ng-model="filter.DeletedHost.name"
                                                   ng-model-options="{debounce: 500}">
                                            <div class="input-group-append">
                                                <span class="input-group-text pt-0 pb-0">
                                                        <label>
                                                            <?= __('Enable RegEx'); ?>
                                                            <input type="checkbox"
                                                                   ng-model="filter.DeletedHost.name_regex">
                                                        </label>
                                                    <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
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
                    <!-- End Filter -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort" ng-click="orderBy('DeletedHosts.name')">
                                    <i class="fa" ng-class="getSortClass('DeletedHosts.name')"></i>
                                    <?php echo __('Host name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DeletedHosts.uuid')">
                                    <i class="fa" ng-class="getSortClass('DeletedHosts.uuid')"></i>
                                    <?php echo __('UUID'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DeletedHosts.created')">
                                    <i class="fa" ng-class="getSortClass('DeletedHosts.created')"></i>
                                    <?php echo __('Date'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DeletedHosts.deleted_perfdata')">
                                    <i class="fa" ng-class="getSortClass('DeletedHosts.deleted_perfdata')"></i>
                                    <?php echo __('Performance data deleted'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="host in hosts">
                                <td>
                                    {{ host.DeletedHost.name }}
                                </td>

                                <td>
                                    {{ host.DeletedHost.uuid }}
                                </td>

                                <td>
                                    {{ host.DeletedHost.created }}
                                </td>

                                <td class="text-center">
                                    <span class="badge badge-info" ng-show="!host.DeletedHost.perfdataDeleted">
                                        <?= __('Pending'); ?>
                                    </span>

                                    <span class="badge badge-success" ng-show="host.DeletedHost.perfdataDeleted">
                                        <?= __('Yes'); ?>
                                    </span>
                                </td>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="hosts.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
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
