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
            <i class="fas fa-server"></i> <?php echo __('Host groups'); ?>
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
                    <?php echo __('Host groups'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'hostgroups')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="HostgroupsAdd">
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
                                                <span class="input-group-text"><i class="fas fa-server"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by host group name'); ?>"
                                                   ng-model="filter.containers.name"
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
                                                   placeholder="<?php echo __('Filter by description'); ?>"
                                                   ng-model="filter.hostgroups.description"
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
                                <th class="no-sort sorting_disabled width-15">
                                    <i class="fa fa-check-square"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Containers.name')">
                                    <i class="fa" ng-class="getSortClass('Containers.name')"></i>
                                    <?php echo __('Host group name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Hostgroups.description')">
                                    <i class="fa" ng-class="getSortClass('Hostgroups.description')"></i>
                                    <?php echo __('Description'); ?>
                                </th>
                                <th class="no-sort text-center">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="hostgroup in hostgroups">
                                <td class="text-center" class="width-15">
                                    <input type="checkbox"
                                           ng-model="massChange[hostgroup.id]"
                                           ng-show="hostgroup.allowEdit">
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('extended', 'hostgroups')): ?>
                                        <a ui-sref="HostgroupsExtended({id: hostgroup.id})">
                                            {{ hostgroup.container.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ hostgroup.container.name }}
                                    <?php endif; ?>

                                    <?php if (Plugin::isLoaded('SLAModule') && $this->Acl->hasPermission('slaHostgroupHostsStatusOverview', 'Slas', 'SLAModule')): ?>
                                        <span class="badge border border-warning"
                                              ng-show="hostgroup.hasSLAHosts">
                                            <a ui-sref="HostgroupsExtended({id: hostgroup.id, selectedTab: 'tab2'})"
                                               class="text-warning">
                                                <i class="fa-solid fa-award"></i> <?php echo __('SLA'); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (Plugin::isLoaded('ImportModule') && $this->Acl->hasPermission('dependencyTree', 'ImportedHostgroups', 'ImportModule')): ?>
                                        <span class="badge border border-info"
                                              ng-show="hostgroup.additionalInformationExists">
                                            <a ui-sref="ImportedHostgroupsDependencyTree({id: hostgroup.id})"
                                               class="text-info">
                                                <i class="fas fa-database"></i> <?php echo __('CMDB'); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>

                                </td>
                                <td>
                                    {{ hostgroup.description }}
                                </td>
                                <td class="width-50">

                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'hostgroups')): ?>
                                            <a ui-sref="HostgroupsEdit({id: hostgroup.id})"
                                               ng-if="hostgroup.allowEdit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!hostgroup.allowEdit"
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
                                            <?php if ($this->Acl->hasPermission('edit', 'hostgroups')): ?>
                                                <a ui-sref="HostgroupsEdit({id: hostgroup.id})"
                                                   ng-if="hostgroup.allowEdit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('extended', 'hostgroups')): ?>
                                                <a ui-sref="HostgroupsExtended({id: hostgroup.id})"
                                                   class="dropdown-item">
                                                    <i class="fa fa-plus-square"></i>
                                                    <?php echo __('Extended view'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (Plugin::isLoaded('SLAModule') && $this->Acl->hasPermission('slaHostgroupHostsStatusOverview', 'Slas', 'SLAModule')): ?>
                                                <a ui-sref="HostgroupsExtended({id: hostgroup.id, selectedTab: 'tab2'})"
                                                   class="dropdown-item" ng-show="hostgroup.hasSLAHosts">
                                                    <i class="fa-solid fa-award"></i>
                                                    <?php echo __('SLA Status Overview'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (Plugin::isLoaded('ImportModule') && $this->Acl->hasPermission('dependencyTree', 'ImportedHostgroups', 'ImportModule')): ?>
                                                <a ui-sref="ImportedHostgroupsDependencyTree({id: hostgroup.id})"
                                                   class="dropdown-item"
                                                   ng-show="hostgroup.additionalInformationExists">
                                                    <i class="fas fa-database"></i>
                                                    <?php echo __('CMDB'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                <a ui-sref="ChangelogsEntity({objectTypeId: 'hostgroup', objectId: hostgroup.id})"
                                                   class="dropdown-item">
                                                    <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                    <?php echo __('Changelog'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('copy', 'hostgroups')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ui-sref="HostgroupsCopy({ids: hostgroup.id})"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'hostgroups')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(hostgroup))"
                                                   ng-if="hostgroup.allowEdit"
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
                        <div class="margin-top-10" ng-show="hostgroups.length == 0">
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
                            <?php if ($this->Acl->hasPermission('copy', 'hostgroups')): ?>
                                <div class="col-xs-12 col-md-2">
                                    <a ui-sref="HostgroupsCopy({ids: linkForCopy()})" class="a-clean">
                                        <i class="fas fa-lg fa-files-o"></i>
                                        <?php echo __('Copy'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('delete', 'hostgroups')): ?>
                                <div class="col-xs-12 col-md-2 txt-color-red">
                                    <span ng-click="confirmDelete(getObjectsForDelete())" class="pointer">
                                        <i class="fas fa-trash"></i>
                                        <?php echo __('Delete selected'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?php echo __('More actions'); ?>
                                </button>
                                <div class="dropdown-menu" x-placement="bottom-start"
                                     style="position: absolute; will-change: top, left; top: 37px; left: 0px;">
                                    <a ng-href="{{ linkFor('pdf') }}" class="dropdown-item">
                                        <i class="fa fa-file-pdf-o"></i> <?php echo __('List as PDF'); ?>
                                    </a>
                                    <a ng-href="{{ linkFor('csv') }}" class="dropdown-item">
                                        <i class="fa-solid fa-file-csv"></i> <?php echo __('List as CSV'); ?>
                                    </a>
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
</div>
