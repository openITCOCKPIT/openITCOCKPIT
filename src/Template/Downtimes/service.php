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
        <a ui-sref="DowntimesService">
            <i class="fa fa-power-off"></i> <?php echo __('Downtimes'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-cog"></i> <?php echo __('Services'); ?>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-success alert-block" ng-show="showFlashSuccess">
            <a href="javascript:void(0);" data-dismiss="alert" class="close">×</a>
            <h4 class="alert-heading"><i
                    class="far fa-check-circle"></i> <?php echo __('Command sent successfully'); ?>
            </h4>
            <?php echo __('Data refresh in'); ?> {{ autoRefreshCounter }} <?php echo __('seconds...'); ?>
        </div>
    </div>
</div>

<mass-delete-service-downtimes callback="showServiceDowntimeFlashMsg"></mass-delete-service-downtimes>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service downtimes'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <!-- Tabs start -->
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('host', 'downtimes')): ?>
                            <li class="nav-item">
                                <a ui-sref="DowntimesHost" class="nav-link">
                                    <i class="fa fa-desktop">&nbsp;</i>
                                    <span
                                        class="hidden-mobile hidden-tablet"> <?php echo __('Host downtimes'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('service', 'downtimes')): ?>
                            <li class="nav-item">
                                <a ui-sref="DowntimesService" class="nav-link active">
                                    <i class="fa fa-cog">&nbsp;</i>
                                    <span
                                        class="hidden-mobile hidden-tablet"> <?php echo __('Service downtimes'); ?></span>
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
                                                <span
                                                    class="input-group-text filter-text"><?php echo __('From'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm"
                                                   style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('From date'); ?>"
                                                   ng-model="from_time"
                                                   ng-model-options="{debounce: 500, timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                                   ng-model="filter.Hosts.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span
                                                    class="input-group-text filter-text"><?php echo __('To'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm"
                                                   style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('To date'); ?>"
                                                   ng-model="to_time"
                                                   ng-model-options="{debounce: 500, timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-cog"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by service name'); ?>"
                                                   ng-model="filter.Services.name"
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
                                                   ng-model="filter.DowntimeServices.author_name"
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
                                                   ng-model="filter.DowntimeServices.comment_data"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Options'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterIsRunning"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.isRunning"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statusFilterIsRunning"><?php echo __('Is running'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterWasNotCancelled"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.DowntimeServices.was_not_cancelled"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statusFilterWasNotCancelled"><?php echo __('Was not cancelled'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterWasCancelled"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.DowntimeServices.was_cancelled"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statusFilterWasCancelled"><?php echo __('Was cancelled'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterHideExpired"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.hideExpired"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statusFilterHideExpired"><?php echo __('Hide expired'); ?></label>
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
                    <!-- Filter End -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort text-center"><i class="fa fa-check-square"></i></th>
                                <th class="no-sort"><?php echo __('Running'); ?></th>
                                <th class="no-sort" ng-click="orderBy('Hosts.name')">
                                    <i class="fa" ng-class="getSortClass('Hosts.name')"></i>
                                    <?php echo __('Host'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('servicename')">
                                    <i class="fa" ng-class="getSortClass('servicename')"></i>
                                    <?php echo __('Service'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.author_name')">
                                    <i class="fa" ng-class="getSortClass('DowntimeServices.author_name')"></i>
                                    <?php echo __('User'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.comment_data')">
                                    <i class="fa" ng-class="getSortClass('DowntimeServices.comment_data')"></i>
                                    <?php echo __('Comment'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.entry_time')">
                                    <i class="fa" ng-class="getSortClass('DowntimeServices.entry_time')"></i>
                                    <?php echo __('Created'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.scheduled_start_time')">
                                    <i class="fa"
                                       ng-class="getSortClass('DowntimeServices.scheduled_start_time')"></i>
                                    <?php echo __('Start'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.scheduled_end_time')">
                                    <i class="fa" ng-class="getSortClass('DowntimeServices.scheduled_end_time')"></i>
                                    <?php echo __('End'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.duration')">
                                    <i class="fa" ng-class="getSortClass('DowntimeServices.duration')"></i>
                                    <?php echo __('Duration'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('DowntimeServices.was_cancelled')">
                                    <i class="fa" ng-class="getSortClass('DowntimeServices.was_cancelled')"></i>
                                    <?php echo __('Was cancelled'); ?>
                                </th>
                                <th class="no-sort"><?php echo __('Cancel'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="downtime in downtimes">
                                <td class="width-5">
                                    <input type="checkbox"
                                           ng-model="massChange[downtime.DowntimeService.internalDowntimeId]"
                                           ng-show="downtime.DowntimeService.allowEdit && downtime.DowntimeService.isCancellable">
                                </td>

                                <td class="text-center">
                                    <downtimeicon downtime="downtime.DowntimeService"></downtimeicon>
                                </td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                        <a ui-sref="HostsBrowser({id: downtime.Host.id})">
                                            {{ downtime.Host.hostname }}
                                        </a>
                                    <?php else: ?>
                                        {{ downtime.Host.hostname }}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id: downtime.Service.id})">
                                            {{ downtime.Service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ downtime.Service.servicename }}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    {{downtime.DowntimeService.authorName}}
                                </td>

                                <td>
                                    {{downtime.DowntimeService.commentData}}
                                </td>

                                <td>
                                    {{downtime.DowntimeService.entryTime}}
                                </td>

                                <td>
                                    {{downtime.DowntimeService.scheduledStartTime}}
                                </td>

                                <td>
                                    {{downtime.DowntimeService.scheduledEndTime}}
                                </td>

                                <td>
                                    {{downtime.DowntimeService.durationHuman}}
                                </td>

                                <td>
                                    <span ng-if="downtime.DowntimeService.wasCancelled"><?php echo __('Yes'); ?></span>
                                    <span ng-if="!downtime.DowntimeService.wasCancelled"><?php echo __('No'); ?></span>
                                </td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('delete', 'downtimes')): ?>
                                        <button
                                            class="btn btn-xs btn-danger"
                                            ng-if="downtime.DowntimeService.allowEdit && downtime.DowntimeService.isCancellable"
                                            ng-click="confirmServiceDowntimeDelete(getObjectForDelete(downtime))">
                                            <i class="fa fa-trash"></i> <?php echo __('Cancel'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>

                            </tr>

                            <tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="downtimes.length == 0">
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
                            <?php if ($this->Acl->hasPermission('delete', 'downtimes')): ?>
                                <div class="col-xs-12 col-md-2 txt-color-red">
                                    <span ng-click="confirmServiceDowntimeDelete(getObjectsForDelete())"
                                          class="pointer">
                                        <i class="fas fa-trash"></i>
                                        <?php echo __('Cancel selected'); ?>
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
