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
        <a ui-sref="ServicesIndex">
            <i class="fa fa-cog"></i> <?php echo __('Services'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-user-md"></i> <?php echo __('Not monitored'); ?>
    </li>
</ol>
<massdelete></massdelete>
<massdeactivate></massdeactivate>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Services'); ?>
                    <span class="fw-300"><i><?php echo __('Not monitored'); ?></i></span>
                </h2>
                <div class="panel-toolbar pr-3 align-self-end">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" ui-sref="ServicesIndex" role="tab">
                                <i class="fa fa-stethoscope">&nbsp;</i> <?php echo __('Monitored'); ?>
                            </a>
                        </li>
                        <?php if ($this->Acl->hasPermission('notMonitored', 'services')): ?>
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" ui-sref="ServicesNotMonitored" role="tab">
                                    <i class="fa fa-user-md">&nbsp;</i> <?php echo __('Not monitored'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('disabled', 'services')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="ServicesDisabled" role="tab">
                                    <i class="fa fa-plug">&nbsp;</i> <?php echo __('Disabled'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'services')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="ServicesAdd">
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
                                                   ng-model="filter.Hosts.name"
                                                   ng-model-options="{debounce: 500}">
                                            <div class="input-group-append">
                                                <span class="input-group-text pt-0 pb-0">
                                                     <label>
                                                            <?= __('Enable RegEx'); ?>
                                                            <input type="checkbox"
                                                                   ng-model="filter.Hosts.name_regex">
                                                        </label>
                                                    <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                                </span>
                                            </div>
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
                                            <div class="input-group-append">
                                                <span class="input-group-text pt-0 pb-0">
                                                     <label>
                                                            <?= __('Enable RegEx'); ?>
                                                            <input type="checkbox"
                                                                   ng-model="filter.Services.name_regex">
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
                    <table id="service_list" class="table table-striped m-0 table-bordered table-hover table-sm">
                        <thead>
                        <tr>
                            <th colspan="2" class="no-sort">
                                <?php echo __('State'); ?>
                            </th>

                            <th class="no-sort" ng-click="orderBy('servicename')">
                                <i class="fa" ng-class="getSortClass('servicename')"></i>
                                <?php echo __('Service name'); ?>
                            </th>

                            <td class="no-sort">
                                <?php echo __('Service type'); ?>
                            </td>

                            <th class="no-sort text-center editItemWidth width-50">
                                <i class="fa fa-gear"></i>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr ng-repeat-start="service in services"
                            ng-if="services[$index-1].Host.uuid !== service.Host.uuid">
                            <td colspan="13" class="service_table_host_header">

                                <hoststatusicon host="service"></hoststatusicon>

                                <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                    <a class="padding-left-5 txt-color-blueDark"
                                       ui-sref="HostsBrowser({id:service.Host.id})">
                                        {{service.Host.hostname}} ({{service.Host.address}})
                                    </a>
                                <?php else: ?>
                                    {{service.Host.hostname}} ({{service.Host.address}})
                                <?php endif; ?>

                                <?php if ($this->Acl->hasPermission('serviceList', 'services')): ?>
                                    <a class="pull-right txt-color-blueDark"
                                       ui-sref="ServicesServiceList({id: service.Host.id})">
                                        <i class="fa fa-list"
                                           title=" <?php echo __('Go to Service list'); ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr ng-repeat-end="">
                            <td class="width-5">
                                <input type="checkbox"
                                       ng-model="massChange[service.Service.id]"
                                       ng-show="service.Service.allow_edit">
                            </td>

                            <td class="text-center width-55">
                                <servicestatusicon service="fakeServicestatus"></servicestatusicon>
                            </td>


                            <td>
                                <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                    <a ui-sref="ServicesBrowser({id:service.Service.id})">
                                        {{ service.Service.servicename }}
                                    </a>
                                <?php else: ?>
                                    {{ service.Service.servicename }}
                                <?php endif; ?>
                            </td>

                            <td>
                                    <span
                                        class="badge border margin-right-10 {{service.ServiceType.class}} {{service.ServiceType.color}}">
                                            <i class="{{service.ServiceType.icon}}"></i>
                                            {{service.ServiceType.title}}
                                    </span>
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
                                            <a ng-if="service.Service.allow_edit"
                                               class="dropdown-item"
                                               href="javascript:void(0);"
                                               ng-click="confirmDeactivate(getObjectForDelete(service))">
                                                <i class="fa fa-plug"></i>
                                                <?php echo __('Disable'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                            <a ui-sref="ChangelogsEntity({objectTypeId: 'service', objectId: service.Service.id})"
                                               class="dropdown-item">
                                                <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                <?php echo __('Changelog'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php
                                        $AdditionalLinks = new \App\Lib\AdditionalLinks($this);
                                        echo $AdditionalLinks->getLinksAsHtmlList('services', 'notMonitored', 'list');
                                        ?>
                                        <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                                            <div class="dropdown-divider"></div>
                                            <a ui-sref="ServicesCopy({ids: service.Service.id})"
                                               ng-if="service.Service.allow_edit"
                                               class="dropdown-item">
                                                <i class="fas fa-files-o"></i>
                                                <?php echo __('Copy'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                            <div class="dropdown-divider"></div>
                                            <a href="javascript:void(0);"
                                               ng-click="confirmDelete(getObjectForDelete(service))"
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
                    <div class="margin-top-10" ng-show="services.length == 0">
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
                        <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                            <div class="col-xs-12 col-md-2">
                                <a ui-sref="ServicesCopy({ids: linkForCopy()})" class="a-clean">
                                    <i class="fas fa-lg fa-files-o"></i>
                                    <?php echo __('Copy'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
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
