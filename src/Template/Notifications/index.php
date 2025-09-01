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
        <a ui-sref="NotificationsIndex">
            <i class="fa fa-envelope"></i> <?php echo __('Notifications'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>

</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Notifications'); ?>
                    <span class="fw-300"><i><?php echo __('Host'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('index', 'notifications')): ?>
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" ui-sref="NotificationsIndex" role="tab">
                                    <i class="fa fa-desktop">&nbsp;</i> <?php echo __('Host notifications'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('services', 'notifications')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="NotificationsServices" role="tab">
                                    <i class="fa fa-cogs">&nbsp;</i> <?php echo __('Service notifications'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
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
                                                <span class="input-group-text filter-text"><?php echo __('From'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm" style="padding:0.5rem 0.875rem;"
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
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by output'); ?>"
                                                   ng-model="filter.NotificationHosts.output"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text filter-text"><?php echo __('To'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm" style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('To date'); ?>"
                                                   ng-model="to_time"
                                                   ng-model-options="{timeSecondsFormat:'ss', timeStripZeroSeconds: true, debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-terminal"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by notification method'); ?>"
                                                   ng-model="filter.Commands.name"
                                                   ng-model-options="{debounce: 500}">
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
                                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by contact name'); ?>"
                                                   ng-model="filter.Contacts.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('States'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUp"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.NotificationHosts.state.recovery"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-up"
                                                       for="statusFilterUp"><?php echo __('Up'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterDown"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.NotificationHosts.state.down"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-down"
                                                       for="statusFilterDown"><?php echo __('Down'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUnreachable"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.NotificationHosts.state.unreachable"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-unreachable"
                                                       for="statusFilterUnreachable"><?php echo __('Unreachable'); ?></label>
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
                        <table id="notification_list" class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort" ng-click="orderBy('NotificationHosts.state')">
                                    <i class="fa" ng-class="getSortClass('NotificationHosts.state')"></i>
                                    <?php echo __('State'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Hosts.name')">
                                    <i class="fa" ng-class="getSortClass('Hosts.name')"></i>
                                    <?php echo __('Host'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('NotificationHosts.start_time')">
                                    <i class="fa" ng-class="getSortClass('NotificationHosts.start_time')"></i>
                                    <?php echo __('Date'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Contacts.name')">
                                    <i class="fa" ng-class="getSortClass('Contacts.name')"></i>
                                    <?php echo __('Contact'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Commands.name')">
                                    <i class="fa" ng-class="getSortClass('Commands.name')"></i>
                                    <?php echo __('Notification Method'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('NotificationHosts.output')">
                                    <i class="fa" ng-class="getSortClass('NotificationHosts.output')"></i>
                                    <?php echo __('Output'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-repeat="Notification in notifications">

                                <td class="text-center">
                                    <hoststatusicon state="Notification.NotificationHost.state"></hoststatusicon>
                                </td>
                                <td>
                                    <a ui-sref="HostsBrowser({id:Notification.Host.id})">
                                        {{ Notification.Host.hostname }}</a>
                                </td>
                                <td>
                                    {{ Notification.NotificationHost.start_time }}
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'contacts')): ?>
                                    <a ui-sref="ContactsEdit({id: Notification.Contact.id})">
                                        {{ Notification.Contact.name }}</a>
                                    <?php else: ?>
                                        {{ Notification.Contact.name }}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                    <a ui-sref="CommandsEdit({id:Notification.Command.id})">
                                        {{ Notification.Command.name }}</a>
                                    <?php else: ?>
                                        {{ Notification.Command.name }}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    {{ Notification.NotificationHost.output }}
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="notifications.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
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
