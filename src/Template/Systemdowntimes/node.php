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

use Cake\Core\Plugin;

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="SystemdowntimesNode">
            <i class="fa fa-history fa-flip-horizontal"></i> <?php echo __('Recurring downtimes'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-chain"></i> <?php echo __('Containers'); ?>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="alert alert-info">
    <button data-dismiss="alert" class="close">×</button>
    <i class="fa fa-info-circle"></i>
    <strong>
        <?php echo __('Notice'); ?>:
    </strong>
    <?php echo __('Recurring downtimes with deleted objects will be deleted automatically by the cronjob'); ?>
</div>

<!-- ANGAULAR DIRECTIVES -->
<massdelete></massdelete>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Recurring downtimes'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <!-- Tabs start -->
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('host', 'systemdowntimes')): ?>
                            <li ng-class="{'active': $resolve.$$controller === 'SystemdowntimesHostController'}"
                                class="nav-item">
                                <a ui-sref="SystemdowntimesHost" class="nav-link">
                                    <i class="fa fa-desktop">&nbsp;</i>
                                    <span class="hidden-mobile hidden-tablet"> <?php echo __('Host'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('service', 'systemdowntimes')): ?>
                            <li ng-class="{'active': $resolve.$$controller === 'SystemdowntimesServiceController'}"
                                class="nav-item">
                                <a ui-sref="SystemdowntimesService" class="nav-link">
                                    <i class="fa fa-cog">&nbsp;</i>
                                    <span class="hidden-mobile hidden-tablet"> <?php echo __('Service'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('hostgroup', 'systemdowntimes')): ?>
                            <li ng-class="{'active': $resolve.$$controller === 'SystemdowntimesHostgroupController'}"
                                class="nav-item">
                                <a ui-sref="SystemdowntimesHostgroup" class="nav-link">
                                    <i class="fas fa-server">&nbsp;</i>
                                    <span class="hidden-mobile hidden-tablet"> <?php echo __('Host group'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('node', 'systemdowntimes')): ?>
                            <li ng-class="{'active': $resolve.$$controller === 'SystemdowntimesNodeController'}"
                                class="nav-item">
                                <a ui-sref="SystemdowntimesNode" class="nav-link active">
                                    <i class="fa fa-chain">&nbsp;</i>
                                    <span class="hidden-mobile hidden-tablet"> <?php echo __('Container'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('DistributeModule') && $this->Acl->hasPermission('downtime', 'satellites', 'DistributeModule')): ?>
                            <li ng-class="{'active': $resolve.$$controller === 'SystemdowntimesSatelliteController'}"
                                class="nav-item">
                                <a ui-sref="SystemdowntimesSatellite" class="nav-link">
                                    <i class="fas fa-satellite">&nbsp;</i>
                                    <span class="hidden-mobile hidden-tablet"> <?php echo __('Satellite'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <!-- Tabs end -->
                    <!-- header buttons start -->
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <div class="btn-group margin-right-5">
                        <button class="btn btn-success btn-xs dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-plus"></i> <?php echo __('Create downtime'); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php if ($this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
                                <a ui-sref="SystemdowntimesAddHostdowntime" class="dropdown-item">
                                    <i class="fa fa-desktop"></i>
                                    <?php echo __('Create host downtime'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('addServicedowntime', 'systemdowntimes')): ?>
                                <a ui-sref="SystemdowntimesAddServicedowntime" class="dropdown-item">
                                    <i class="fa fa-cog"></i>
                                    <?php echo __('Create service downtime'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
                                <a ui-sref="SystemdowntimesAddHostgroupdowntime" class="dropdown-item">
                                    <i class="fa fa-sitemap"></i>
                                    <?php echo __('Create host group downtime'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
                                <a ui-sref="SystemdowntimesAddContainerdowntime" class="dropdown-item">
                                    <i class="fa fa-link"></i>
                                    <?php echo __('Create container downtime'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (Plugin::isLoaded('DistributeModule') && $this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
                                <a ui-sref="SystemdowntimesAddSatelliteDowntime" class="dropdown-item">
                                    <i class="fas fa-satellite"></i>
                                    <?php echo __('Create satellite downtime'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-xs btn-primary shadow-0 " ng-click="triggerFilter()">
                        <i class="fas fa-filter"></i> <?php echo __('Filter'); ?>
                    </button>
                    <!-- header buttons end -->
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
                                                <span class="input-group-text"><i class="fa fa-chain"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by container name'); ?>"
                                                   ng-model="filter.Containers.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by user'); ?>"
                                                   ng-model="filter.Systemdowntimes.author"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by comment'); ?>"
                                                   ng-model="filter.Systemdowntimes.comment"
                                                   ng-model-options="{debounce: 500}">
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
                    <!-- Filter End -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort text-center"><i class="fa fa-check-square"></i></th>

                                <th class="no-sort" ng-click="orderBy('Containers.name')">
                                    <i class="fa" ng-class="getSortClass('Containers.name')"></i>
                                    <?php echo __('Container name'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Systemdowntimes.author')">
                                    <i class="fa" ng-class="getSortClass('Systemdowntimes.author')"></i>
                                    <?php echo __('User'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Systemdowntimes.comment')">
                                    <i class="fa" ng-class="getSortClass('Systemdowntimes.comment')"></i>
                                    <?php echo __('Comment'); ?>
                                </th>

                                <th class="no-sort"><?php echo __('Weekdays'); ?></th>

                                <th class="no-sort"><?php echo __('Days of month'); ?></th>

                                <th class="no-sort" ng-click="orderBy('Systemdowntimes.from_time')">
                                    <i class="fa" ng-class="getSortClass('Systemdowntimes.from_time')"></i>
                                    <?php echo __('Start time'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Systemdowntimes.duration')">
                                    <i class="fa" ng-class="getSortClass('Systemdowntimes.duration')"></i>
                                    <?php echo __('Duration'); ?>
                                </th>

                                <th class="no-sort"><?php echo __('Delete'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="downtime in systemdowntimes">

                                <td class="width-5">
                                    <input type="checkbox"
                                           ng-model="massChange[downtime.Systemdowntime.id]"
                                           ng-show="downtime.Container.allow_edit">
                                </td>

                                <td>
                                    {{ downtime.Container.name }}
                                </td>

                                <td>{{downtime.Systemdowntime.author}}</td>

                                <td>
                                    <span class="text-muted">
                                        AUTO[{{downtime.Systemdowntime.id}}]:
                                    </span>
                                    {{downtime.Systemdowntime.comment}}
                                </td>

                                <td>{{downtime.Systemdowntime.weekdaysHuman.join(', ')}}</td>

                                <td>
                                    <span
                                        class="text-muted"
                                        ng-show="downtime.Systemdowntime.dayOfMonth.length == 0">
                                        <?php echo __('Every defined weekday'); ?></span>
                                    {{downtime.Systemdowntime.dayOfMonth.join(', ')}}
                                </td>

                                <td>{{downtime.Systemdowntime.startTime}}</td>

                                <td>{{downtime.Systemdowntime.duration}}</td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('delete', 'systemdowntimes')): ?>
                                        <button
                                            class="btn btn-xs btn-danger"
                                            ng-if="downtime.Container.allow_edit"
                                            ng-click="confirmDelete(getObjectForDelete(downtime))">
                                            <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>

                            </tr>

                            </tbody>

                        </table>
                        <div class="margin-top-10" ng-show="systemdowntimes.length == 0">
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
                            <?php if ($this->Acl->hasPermission('delete', 'systemdowntimes')): ?>
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
