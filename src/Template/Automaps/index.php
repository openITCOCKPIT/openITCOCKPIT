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
        <a ui-sref="AutomapsIndex">
            <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Auto Maps'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>
<massdelete></massdelete>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Auto Maps'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'automaps')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="AutomapsAdd">
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
                                                   placeholder="<?php echo __('Filter by auto map name'); ?>"
                                                   ng-model="filter.Automaps.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by description'); ?>"
                                                   ng-model="filter.Automaps.description"
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
                    <!-- END FILTER -->

                    <div class="frame-wrap">
                        <table id="automaps_list" class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort width-5">
                                    <i class="fa fa-check-square"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Automaps.name')">
                                    <i class="fa" ng-class="getSortClass('Automaps.name')"></i>
                                    <?php echo __('Name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Automaps.description')">
                                    <i class="fa" ng-class="getSortClass('Automaps.description')"></i>
                                    <?php echo __('Description'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Automaps.host_regex')">
                                    <i class="fa" ng-class="getSortClass('Automaps.host_regex')"></i>
                                    <?php echo __('Host RegEx'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Automaps.hostgroup_regex')">
                                    <i class="fa" ng-class="getSortClass('Automaps.hostgroup_regex')"></i>
                                    <?php echo __('Host group RegEx'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Automaps.service_regex')">
                                    <i class="fa" ng-class="getSortClass('Automaps.service_regex')"></i>
                                    <?php echo __('Service RegEx'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Status filters'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Recursive container'); ?>
                                </th>
                                <th class="no-sort text-center">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="automap in automaps">
                                <td class="text-center" class="width-15">
                                    <input type="checkbox"
                                           ng-model="massChange[automap.id]"
                                           ng-show="automap.allow_edit">
                                </td>

                                <td class="word-break">
                                    <?php if ($this->Acl->hasPermission('view', 'automaps')): ?>
                                        <a ui-sref="AutomapsView({id:automap.id})">
                                            {{automap.name}}
                                        </a>
                                    <?php else: ?>
                                        {{automap.name}}
                                    <?php endif; ?>
                                </td>
                                <td class="word-break">{{automap.description}}</td>
                                <td class="word-break">{{automap.host_regex}}</td>
                                <td class="word-break">{{automap.hostgroup_regex}}</td>
                                <td class="word-break">{{automap.service_regex}}</td>

                                <td>
                                    <span class="badge badge-success margin-right-5"
                                          title="<?php echo __('Ok'); ?>"
                                          ng-show="automap.show_ok">
                                        <?php echo __('O'); ?>
                                    </span>
                                    <span class="badge badge-warning margin-right-5"
                                          title="<?php echo __('Warning'); ?>"
                                          ng-show="automap.show_warning">
                                        <?php echo __('W'); ?>
                                    </span>
                                    <span class="badge badge-danger margin-right-5"
                                          title="<?php echo __('Critical'); ?>"
                                          ng-show="automap.show_critical">
                                        <?php echo __('C'); ?>
                                    </span>
                                    <span class="badge badge-secondary margin-right-5"
                                          title="<?php echo __('Unknown'); ?>"
                                          ng-show="automap.show_unknown">
                                        <?php echo __('U'); ?>
                                    </span>
                                    <span class="badge badge-primary margin-right-5"
                                          title="<?php echo __('Acknowledged'); ?>"
                                          ng-show="automap.show_acknowledged">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    <span class="badge badge-primary"
                                          title="<?php echo __('In downtime'); ?>"
                                          ng-show="automap.show_downtime">
                                        <i class="fa fa-power-off"></i>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-danger"
                                          ng-hide="automap.recursive">
                                        <?php echo __('Disabled'); ?>
                                    </span>
                                    <span class="badge badge-success"
                                          ng-show="automap.recursive">
                                        <?php echo __('Enabled'); ?>
                                    </span>
                                </td>


                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'automaps')): ?>
                                            <a ui-sref="AutomapsEdit({id: automap.id})"
                                               ng-if="automap.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!automap.allow_edit"
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
                                        <!-- <ul class="dropdown-menu" id="menuHack-{{service.Service.uuid}}" > -->
                                        <div class="dropdown-menu">
                                            <?php if ($this->Acl->hasPermission('edit', 'automaps')): ?>
                                                <a ui-sref="AutomapsEdit({id:automap.id})"
                                                   class="dropdown-item"
                                                   ng-if="automap.allow_edit">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('view', 'automaps')): ?>
                                                <a ui-sref="AutomapsView({id:automap.id})"
                                                   class="dropdown-item">
                                                    <i class="fa fa-eye"></i>
                                                    <?php echo __('View'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('copy', 'automaps')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ui-sref="AutomapsCopy({ids: automap.id})"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('delete', 'automaps')): ?>
                                                <div class="dropdown-divider" ng-if="automap.allow_edit"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(automap))"
                                                   ng-if="automap.allow_edit"
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
                        <div class="margin-top-10" ng-show="automaps.length == 0">
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
                            <?php if ($this->Acl->hasPermission('copy', 'automaps')): ?>
                                <div class="col-xs-12 col-md-2">
                                    <a ui-sref="AutomapsCopy({ids: linkForCopy()})" class="a-clean">
                                        <i class="fas fa-lg fa-files-o"></i>
                                        <?php echo __('Copy'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('delete', 'automaps')): ?>
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
