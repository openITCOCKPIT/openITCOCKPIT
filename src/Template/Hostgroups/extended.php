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
        <a ui-sref="HostgroupsIndex">
            <i class="fas fa-server"></i> <?php echo __('Host group'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-eye"></i> <?php echo __('Extended overview'); ?>
    </li>
</ol>

<query-handler-directive></query-handler-directive>
<massdelete></massdelete>
<massdeactivate></massdeactivate>
<massactivate></massactivate>
<reschedule-host callback="showFlashMsg"></reschedule-host>
<disable-host-notifications callback="showFlashMsg"></disable-host-notifications>
<enable-host-notifications callback="showFlashMsg"></enable-host-notifications>
<acknowledge-host author="<?php echo h($username); ?>" callback="showFlashMsg"></acknowledge-host>
<host-downtime author="<?php echo h($username); ?>" callback="showFlashMsg"></host-downtime>


<div class="alert alert-success alert-block" ng-show="showFlashSuccess">
    <a href="javascript:void(0);" data-dismiss="alert" class="close">×</a>
    <h4 class="alert-heading"><i class="far fa-check-circle"></i> <?php echo __('Command sent successfully'); ?></h4>
    <?php echo __('Data refresh in'); ?> {{ autoRefreshCounter }} <?php echo __('seconds...'); ?>
</div>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    {{hostgroup.Hostgroup.container.name}}
                    <span class="fw-300"><i><?php echo __('UUID: '); ?>{{hostgroup.Hostgroup.uuid}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean flex-column flex-sm-row"
                        role="tablist">
                        <li class="nav-item pointer">
                            <a class="nav-link"
                               ng-class="{'active': selectedTab == 'tab1'}"
                               data-toggle="tab" ng-click="selectedTab = 'tab1'"
                               role="tab">
                                <i class="fa fa-desktop">&nbsp;</i> <?php echo __('Hosts'); ?>
                            </a>
                        </li>
                        <?php if (Plugin::isLoaded('SLAModule') && $this->Acl->hasPermission('slaHostgroupHostsStatusOverview', 'Slas', 'SLAModule')): ?>
                            <li class="nav-item pointer" ng-show="hostgroup.hasSLAHosts">
                                <a class="nav-link" data-toggle="tab"
                                   ng-class="{'active': selectedTab == 'tab2'}"
                                   ng-click="selectedTab = 'tab2'"
                                   role="tab">
                                    <i class="fa-solid fa-award"></i>&nbsp;<?php echo __('SLA Hosts Status'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <?php if (Plugin::isLoaded('ImportModule') && $this->Acl->hasPermission('dependencyTree', 'ImportedHostgroups', 'ImportModule')): ?>
                        <a class="btn btn-outline-primary btn-sm btn-w-m waves-effect waves-themed mr-1"
                           ui-sref="ImportedHostgroupsDependencyTree({id: post.Hostgroup.id})"
                           ng-if="AdditionalInformationExists">
                            <i class="fas fa-sitemap fa-rotate-270"></i>
                            <?= __('Dependency tree'); ?>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="loadHostsWithStatus()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'hostgroups')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="HostgroupsAdd">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('index', 'hostgroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostgroupsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row">
                        <div class="form-group required col-lg-12">
                            <label class="control-label" for="HostgroupSelect">
                                <?php echo __('Host group'); ?>
                            </label>
                        </div>
                        <div class="form-group required col-lg-9">
                            <select
                                id="HostgroupSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                chosen="hostgroups"
                                ng-options="hostgroup.key as hostgroup.value for hostgroup in hostgroups"
                                callback="loadHostgroupsCallback"
                                ng-model="post.Hostgroup.id">
                            </select>
                        </div>

                        <div class="col-lg-1">
                            <div class="btn-group btn-group-sm"
                                 ng-show="hostgroup.Hostgroup.allowEdit">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                    <?php echo __('Action'); ?>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <?php if ($this->Acl->hasPermission('edit', 'hostgroups')): ?>
                                        <button ui-sref="HostgroupsEdit({id:post.Hostgroup.id})"
                                                ng-show="hostgroup.Hostgroup.allowEdit"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-cog"></i> <?php echo __('Edit'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                        <a ui-sref="ChangelogsEntity({objectTypeId: 'hostgroup', objectId: post.Hostgroup.id})"
                                           class="dropdown-item">
                                            <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                            <?php echo __('Changelog'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('copy', 'hostgroups')): ?>
                                        <div class="dropdown-divider"></div>
                                        <a ui-sref="HostgroupsCopy({ids: post.Hostgroup.id})"
                                           class="dropdown-item">
                                            <i class="fas fa-files-o"></i>
                                            <?php echo __('Copy'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('externalcommands', 'hosts')): ?>
                                        <div class="dropdown-divider"></div>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_reschedule"
                                                ng-click="rescheduleHost(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-refresh"></i> <?php echo __('Reset check time'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_schedule_downtime"
                                                ng-click="hostDowntime(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-clock-o"></i> <?php echo __('Set planned maintenance times'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_ack_state"
                                                ng-click="acknowledgeHost(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-user"></i> <?php echo __('Acknowledge host status'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_disable_notifications"
                                                ng-click="disableHostNotifications(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="far fa-envelope"></i> <?php echo __('Disable notification'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_enable_notifications"
                                                ng-click="enableHostNotifications(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-envelope"></i> <?php echo __('Enable notifications'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div ng-show="selectedTab == 'tab1'" class="tab-pane">
                        <div class="frame-wrap">
                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                <thead>
                                <tr>
                                    <td colspan="8" class="no-padding">
                                        <div class="form-group">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm"
                                                       placeholder="<?php echo __('Filter by host name'); ?>"
                                                       ng-model="filter.Host.name"
                                                       ng-model-options="{debounce: 500}">
                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="6" class="no-padding">
                                        <div class="row no-margin" style="height:32px; ">
                                            <div class="col-lg-4 bg-{{state}}" style="padding-top: 7px;"
                                                 ng-repeat="(state,stateCount) in hostgroup.StatusSummary">
                                                <div class="custom-control custom-checkbox txt-color-white float-right">
                                                    <input type="checkbox"
                                                           id="statusFilter{{state}}"
                                                           class="custom-control-input"
                                                           name="checkbox"
                                                           checked="checked"
                                                           ng-model-options="{debounce: 500}"
                                                           ng-value="{{state}}"
                                                           ng-model="filter.Hoststatus.current_state[state]">
                                                    <label
                                                        class="extended-list custom-control-label custom-control-label-{{state}} no-margin"
                                                        for="statusFilter{{state}}">{{stateCount}}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th class="width-20 text-center">
                                        <?php echo __('Status'); ?>
                                    </th>
                                    <th class="width-20 text-center"></th>
                                    <th class="width-20 text-center">
                                        <i class="fa fa-user" title="is acknowledged"></i>
                                    </th>
                                    <th class="width-20 text-center">
                                        <i class="fa fa-power-off" title="is in downtime"></i>
                                    </th>
                                    <th class="width-20 text-center">
                                        <strong title="<?php echo __('Passively transferred service'); ?>">
                                            <?php echo __('P'); ?>
                                        </strong>
                                    </th>
                                    <th>
                                        <?php echo __('Host name'); ?>
                                    </th>
                                    <th>
                                        <?php echo __('State since'); ?>
                                    </th>
                                    <th>
                                        <?php echo __('Last check'); ?>
                                    </th>
                                    <th>
                                        <?php echo __('Next check'); ?>
                                    </th>
                                    <th>
                                        <?php echo __('Service Summary '); ?>
                                    </th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tr ng-show="hostgroup.Hosts.length == 0">
                                    <td class="no-padding text-center" colspan="14">
                                        <div class="col-xs-12 text-center txt-color-red italic padding-10">
                                            <?php echo __('No entries match the selection'); ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr ng-show="hostgroupsStateFilter[host.Hoststatus.currentState]"
                                    ng-repeat-start="host in hostgroup.Hosts">
                                    <td class="width-20 text-center pointer fa-lg">
                                        <i ng-class="(!showServices[host.Host.id]) ? 'fas fa-plus-square' : 'far fa-minus-square'"
                                           ng-click="showServicesCallback(host.Host.id)"
                                        ></i>
                                    </td>
                                    <td class="text-center">
                                        <hoststatusicon host="host"></hoststatusicon>
                                    </td>
                                    <td class="text-center">
                                        <servicecumulatedstatusicon state="host.ServicestatusSummary.cumulatedState">

                                        </servicecumulatedstatusicon>
                                    </td>
                                    <td class="text-center">
                                        <i class="far fa-user"
                                           ng-show="host.Hoststatus.problemHasBeenAcknowledged"
                                           ng-if="host.Hoststatus.acknowledgement_type == 1"></i>

                                        <i class="fas fa-user"
                                           ng-show="host.Hoststatus.problemHasBeenAcknowledged"
                                           ng-if="host.Hoststatus.acknowledgement_type == 2"
                                           title="<?php echo __('Sticky Acknowledgedment'); ?>"></i>
                                    </td>
                                    <td class="text-center">
                                        <i class="fa fa-power-off"
                                           ng-show="host.Hoststatus.scheduledDowntimeDepth > 0"></i>
                                    </td>
                                    <td class="text-center">
                                        <strong title="<?php echo __('Passively transferred service'); ?>"
                                                ng-show="host.Host.active_checks_enabled === false || host.Host.is_satellite_host === true">
                                            P
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                            <a ui-sref="HostsBrowser({id:host.Host.id})">
                                                {{ host.Host.hostname }}
                                            </a>
                                        <?php else: ?>
                                            {{ host.Host.hostname }}
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        {{ host.Hoststatus.last_state_change }}
                                    </td>
                                    <td>
                                        {{ host.Hoststatus.lastCheck }}
                                    </td>
                                    <td>
                                        <span
                                            ng-if="host.Hoststatus.activeChecksEnabled && host.Host.is_satellite_host === false">
                                            {{ host.Hoststatus.nextCheck }}
                                        </span>
                                        <span
                                            ng-if="host.Hoststatus.activeChecksEnabled === false || host.Host.is_satellite_host === true">
                                            <?php echo __('n/a'); ?>
                                        </span>
                                    </td>
                                    <td class="width-160">
                                        <div class="btn-group btn-group-justified btn-group-xs" role="group">
                                            <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                                                <a class="btn btn-success state-button"
                                                   ui-sref="ServicesIndex({servicestate: [0], host_id: host.Host.id, sort: 'Servicestatus.last_state_change', direction: 'desc'})">
                                                    {{host.ServicestatusSummary.state['ok']}}
                                                </a>
                                            <?php else: ?>
                                                <a class="btn btn-success state-button">
                                                    {{host.ServicestatusSummary.state['ok']}}
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                                                <a class="btn btn-warning state-button"
                                                   ui-sref="ServicesIndex({servicestate: [1], host_id: host.Host.id, sort: 'Servicestatus.last_state_change', direction: 'desc'})">
                                                    {{host.ServicestatusSummary.state['warning']}}
                                                </a>
                                            <?php else: ?>
                                                <a class="btn btn-warning state-button">
                                                    {{host.ServicestatusSummary.state['warning']}}
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                                                <a class="btn btn-danger state-button"
                                                   ui-sref="ServicesIndex({servicestate: [2], host_id: host.Host.id, sort: 'Servicestatus.last_state_change', direction: 'desc'})">
                                                    {{host.ServicestatusSummary.state['critical']}}
                                                </a>
                                            <?php else: ?>
                                                <a class="btn btn-danger state-button">
                                                    {{host.ServicestatusSummary.state['critical']}}
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                                                <a class="btn btn-default state-button"
                                                   ui-sref="ServicesIndex({servicestate: [3], host_id: host.Host.id, sort: 'Servicestatus.last_state_change', direction: 'desc'})">
                                                    {{host.ServicestatusSummary.state['unknown']}}
                                                </a>
                                            <?php else: ?>
                                                <a class="btn btn-default state-button">
                                                    {{host.ServicestatusSummary.state['unknown']}}
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="width-50">
                                        <div class="btn-group btn-group-xs" role="group">
                                            <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                                <a ui-sref="HostsEdit({id:host.Host.id})"
                                                   ng-if="host.Host.allow_edit"
                                                   class="btn btn-default btn-lower-padding">
                                                    <i class="fa fa-cog"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="javascript:void(0);"
                                                   class="btn btn-default btn-lower-padding">
                                                    <i class="fa fa-cog"></i></a>
                                            <?php endif; ?>
                                            <button type="button"
                                                    class="btn btn-default dropdown-toggle btn-lower-padding"
                                                    data-toggle="dropdown">
                                                <i class="caret"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                                    <a ui-sref="HostsEdit({id:host.Host.id})"
                                                       ng-if="host.Host.allow_edit"
                                                       class="dropdown-item">
                                                        <i class="fa fa-cog"></i>
                                                        <?php echo __('Edit'); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($this->Acl->hasPermission('deactivate', 'hosts')): ?>
                                                    <a ng-click="confirmDeactivate(getObjectForDelete(host))"
                                                       ng-if="host.Host.allow_edit && !host.Host.disabled"
                                                       href="javascript:void(0);"
                                                       class="dropdown-item">
                                                        <i class="fa fa-plug"></i>
                                                        <?php echo __('Disable'); ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($this->Acl->hasPermission('enable', 'hosts')): ?>
                                                    <a ng-click="confirmActivate(getObjectForDelete(host))"
                                                       ng-if="host.Host.allow_edit && host.Host.disabled"
                                                       href="javascript:void(0);"
                                                       class="dropdown-item">
                                                        <i class="fa fa-plug"></i>
                                                        <?php echo __('Enable'); ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                    <a ui-sref="ChangelogsEntity({objectTypeId: 'host', objectId: host.Host.id})"
                                                       class="dropdown-item">
                                                        <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                        <?php echo __('Changelog'); ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($this->Acl->hasPermission('copy', 'hosts')): ?>
                                                    <div class="dropdown-divider" ng-if="host.Host.allow_edit"></div>
                                                    <a ui-sref="HostsCopy({ids: host.Host.id})"
                                                       ng-if="host.Host.allow_edit"
                                                       class="dropdown-item">
                                                        <i class="fas fa-files-o"></i>
                                                        <?php echo __('Copy'); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($this->Acl->hasPermission('delete', 'hosts')): ?>
                                                    <div class="dropdown-divider"></div>
                                                    <a ng-click="confirmDelete(getObjectForDelete(host))"
                                                       ng-if="host.Host.allow_edit"
                                                       class="dropdown-item txt-color-red">
                                                        <i class="fa fa-trash"></i>
                                                        <?php echo __('Delete'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr ng-show="showServices[host.Host.id]" ng-repeat-end="">
                                    <td colspan="12">
                                        <host-service-list
                                            host-id="host.Host.id"
                                            show-services="showServices"
                                            timezone="timezone"
                                            host="host"
                                            ng-if="timezone">
                                        </host-service-list>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                    <!-- SLA Module start -->
                    <div ng-show="selectedTab == 'tab2'" class="tab-pane">
                        <div class="frame-wrap">
                            <?php if (Plugin::isLoaded('SLAModule') && $this->Acl->hasPermission('slaHostgroupHostsStatusOverview', 'Slas', 'SLAModule')): ?>
                                <sla-hostgroup-hosts-status-overview ng-if="selectedTab == 'tab2'"
                                                                     hostgroup-id="{{post.Hostgroup.id}}"></sla-hostgroup-hosts-status-overview>
                            <?php else: ?>
                                <label class="text-danger">
                                    <?php echo __('No permissions'); ?>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- SLA Module end -->
                </div>
            </div>
        </div>
    </div>
</div>
