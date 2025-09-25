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
        <a ui-sref="ServicegroupsIndex">
            <i class="fa fa-cogs"></i> <?php echo __('Service group'); ?>
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
<reschedule-service callback="showFlashMsg"></reschedule-service>
<disable-notifications callback="showFlashMsg"></disable-notifications>
<enable-notifications callback="showFlashMsg"></enable-notifications>
<acknowledge-service author="<?php echo h($username); ?>" callback="showFlashMsg"></acknowledge-service>
<service-downtime author="<?php echo h($username); ?>" callback="showFlashMsg"></service-downtime>


<div class="alert alert-success alert-block" ng-show="showFlashSuccess">
    <a href="javascript:void(0);" data-dismiss="alert" class="close">×</a>
    <h4 class="alert-heading"><i class="fa fa-check-circle"></i> <?php echo __('Command sent successfully'); ?></h4>
    <?php echo __('Data refresh in'); ?> {{ autoRefreshCounter }} <?php echo __('seconds...'); ?>
</div>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    {{servicegroup.Servicegroup.container.name}}
                    <span class="fw-300"><i><?php echo __('UUID: '); ?>{{servicegroup.Servicegroup.uuid}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'servicegroups')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="ServicegroupsAdd">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('index', 'servicegroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicegroupsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row">
                        <div class="form-group required col-lg-10">
                            <label class="control-label" for="ServicegroupSelect">
                                <?php echo __('Service group'); ?>
                            </label>
                            <select
                                id="ServicegroupSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                chosen="servicegroups"
                                callback="loadServicegroupsCallback"
                                ng-options="servicegroup.key as servicegroup.value for servicegroup in servicegroups"
                                ng-model="post.Servicegroup.id">
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <div class="btn-group btn-group-sm" style="padding-top:23px;">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                    <?php echo __('Action'); ?>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <?php if ($this->Acl->hasPermission('edit', 'servicegroups')): ?>
                                        <button ui-sref="ServicegroupsEdit({id:post.Servicegroup.id})"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-cog"></i> <?php echo __('Edit'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                        <a ui-sref="ChangelogsEntity({objectTypeId: 'servicegroup', objectId: post.Servicegroup.id})"
                                           class="dropdown-item">
                                            <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                            <?php echo __('Changelog'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('copy', 'servicegroups')): ?>
                                        <div class="dropdown-divider"></div>
                                        <a ui-sref="ServicegroupsCopy({ids: post.Servicegroup.id})"
                                           class="dropdown-item">
                                            <i class="fas fa-files-o"></i>
                                            <?php echo __('Copy'); ?>
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('externalcommands', 'services')): ?>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_reschedule"
                                                ng-click="reschedule(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-refresh"></i> <?php echo __('Reset check time'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_schedule_downtime"
                                                ng-click="serviceDowntime(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-clock-o"></i> <?php echo __('Set planned maintenance times'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_ack_state"
                                                ng-click="acknowledgeService(getNotOkObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-user"></i> <?php echo __('Acknowledge service status'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_disable_notifications"
                                                ng-click="disableNotifications(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="far fa-envelope"></i> <?php echo __('Disable notification'); ?>
                                        </button>
                                        <button data-toggle="modal"
                                                data-target="#nag_command_enable_notifications"
                                                ng-click="enableNotifications(getObjectsForExternalCommand())"
                                                class="dropdown-item"
                                                type="button">
                                            <i class="fa fa-envelope"></i> <?php echo __('Enable notifications'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <td colspan="8" class="no-padding">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-cog"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by service name'); ?>"
                                                   ng-model="filter.servicename"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </td>
                                <td colspan="6" class="no-padding">
                                    <div class="row no-margin" style="height:32px; ">
                                        <div class="col-lg-3 bg-{{state}}" style="padding-top: 7px;"
                                             ng-repeat="(state,stateCount) in servicegroup.StatusSummary">
                                            <div class="custom-control custom-checkbox txt-color-white float-right">
                                                <input type="checkbox"
                                                       id="statusFilter{{state}}"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model-options="{debounce: 500}"
                                                       ng-value="{{state}}"
                                                       ng-model="filter.Servicestatus.current_state[state]">
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
                                <th>
                                    <?php echo __('Status'); ?>
                                </th>
                                <th class="width-20 text-center">
                                    <i class="fa fa-user" title="is acknowledged"></i>
                                </th>
                                <th class="width-20 text-center">
                                    <i class="fa fa-power-off" title="is in downtime"></i>
                                </th>
                                <th class="width-20 text-center">
                                    <i class="fa fa-lg fa-area-chart" title="Grapher"></i>
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
                                    <?php echo __('Service name'); ?>
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
                                <th class="width-240">
                                    <?php echo __('Output'); ?>
                                </th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-show="servicegroup.Services.length == 0">
                                <td class="no-padding text-center" colspan="13">
                                    <div class="col-xs-12 text-center txt-color-red italic padding-10">
                                        <?php echo __('No entries match the selection'); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr ng-repeat-end
                                ng-show="servicegroupsStateFilter[service.Servicestatus.currentState] || service.Servicestatus.currentState == null"
                                ng-repeat="service in servicegroup.Services">
                                <td class="text-center">
                                    <servicestatusicon service="service"></servicestatusicon>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-user"
                                       ng-show="service.Servicestatus.problemHasBeenAcknowledged"
                                       ng-if="service.Servicestatus.acknowledgement_type == 1"></i>

                                    <i class="fa fa-user"
                                       ng-show="service.Servicestatus.problemHasBeenAcknowledged"
                                       ng-if="service.Servicestatus.acknowledgement_type == 2"
                                       title="<?php echo __('Sticky Acknowledgedment'); ?>"></i>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-power-off"
                                       ng-show="service.Servicestatus.scheduledDowntimeDepth > 0"></i>
                                </td>
                                <td class="text-center">
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id: service.Service.id})"
                                           class="txt-color-blueDark"
                                           ng-mouseenter="mouseenter($event, service.Host.uuid, service.Service.uuid)"
                                           ng-mouseleave="mouseleave()"
                                           ng-if="service.Service.has_graph">
                                            <i class="fa fa-lg fa-area-chart">
                                            </i>
                                        </a>
                                    <?php else: ?>
                                        <div ng-mouseenter="mouseenter($event, service.Host.uuid, service.Service.uuid)"
                                             ng-mouseleave="mouseleave()"
                                             ng-if="service.Service.has_graph">
                                            <i class="fa fa-lg fa-area-chart">
                                            </i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <strong title="<?php echo __('Passively transferred service'); ?>"
                                            ng-show="service.Service.active_checks_enabled === false || service.Host.is_satellite_host === true">
                                        P
                                    </strong>
                                </td>
                                <td class="table-color-{{(service.Hoststatus.currentState !== null)?service.Hoststatus.currentState:'disabled'}}">
                                    <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                        <a ui-sref="HostsBrowser({id: service.Host.id})">
                                            {{ service.Host.hostname }}
                                        </a>
                                    <?php else: ?>
                                        {{ service.Host.hostname }}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id: service.Service.id})">
                                            {{ service.Service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ service.Service.servicename }}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    {{ service.Servicestatus.last_state_change }}
                                </td>
                                <td>
                                    {{service.Servicestatus.lastCheck }}
                                </td>
                                <td>
                                    <span
                                        ng-if="service.Service.active_checks_enabled && service.Host.is_satellite_host === false">{{
                                        service.Servicestatus.nextCheck }}</span>
                                    <span
                                        ng-if="service.Service.active_checks_enabled === false || service.Host.is_satellite_host === true">
                                        <?php echo __('n/a'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="word-break"
                                         ng-bind-html="service.Servicestatus.outputHtml | trustAsHtml"></div>
                                </td>
                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                            <a ui-sref="ServicesEdit({id: service.Service.id})"
                                               ng-if="service.Service.allow_edit"
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
                                            <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                                <a ui-sref="ServicesEdit({id: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('deactivate', 'services')): ?>
                                                <a ng-click="confirmDeactivate(getObjectForDelete(service.Host, service))"
                                                   ng-if="service.Service.allow_edit && !service.Service.disabled"
                                                   href="javascript:void(0);"
                                                   class="dropdown-item">
                                                    <i class="fa fa-plug"></i>
                                                    <?php echo __('Disable'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('enable', 'services')): ?>
                                                <a ng-click="confirmActivate(getObjectForDelete(service.Host, service))"
                                                   ng-if="service.Service.allow_edit && service.Service.disabled"
                                                   href="javascript:void(0);"
                                                   class="dropdown-item">
                                                    <i class="fa fa-plug"></i>
                                                    <?php echo __('Enable'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                <a ui-sref="ChangelogsEntity({objectTypeId: 'service', objectId: service.Service.id})"
                                                   class="dropdown-item">
                                                    <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                    <?php echo __('Changelog'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                                                <div class="dropdown-divider" ng-if="service.Service.allow_edit"></div>
                                                <a ui-sref="ServicesCopy({ids: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                                <div class="dropdown-divider" ng-if="service.Service.allow_edit"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(service.Host, service))"
                                                   ng-if="service.Service.allow_edit"
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
                        <popover-graph-directive></popover-graph-directive>
                    </div>
                    <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                    <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                    <?php echo $this->element('paginator_or_scroll'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
