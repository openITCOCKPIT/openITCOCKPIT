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
?>

<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="AgentconnectorsWizard">
            <i class="fa fa-user-secret"></i> <?php echo __('openITCOCKPIT Agent'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="AgentchecksIndex">
            <?php echo __('Checks'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <?php echo __('Overview'); ?>
    </li>
</ol>

<massdelete></massdelete>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Agent checks overview'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'servicetemplates', '')): ?>
                        <a class="btn btn-xs btn-default mr-1 shadow-0"
                           ui-sref="ServicetemplatesIndex({servicetemplateTypes:['<?= OITC_AGENT_SERVICE ?>']})">
                            <i class="fas fa-pencil-square-o"></i> <?php echo __('Go to Servicetemplates'); ?>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'agentchecks')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="AgentchecksAdd">
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

                    <div class="list-filter card margin-bottom-10" ng-show="showFilter">
                        <div class="card-header">
                            <i class="fa fa-filter"></i> <?php echo __('Filter'); ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by agent check name'); ?>"
                                                   ng-model="filter.Agentchecks.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by service template name'); ?>"
                                                   ng-model="filter.Servicetemplates.template_name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="float-right margin-top-20">
                                <button type="button" ng-click="resetFilter()"
                                        class="btn btn-xs btn-danger">
                                    <?php echo __('Reset Filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-hover table-bordered table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort sorting_disabled width-15">
                                    <i class="fa fa-check-square fa-lg"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Agentchecks.name')">
                                    <i class="fa" ng-class="getSortClass('Agentchecks.name')"></i>
                                    <?php echo __('Agent check name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Agentchecks.plugin_name')">
                                    <i class="fa" ng-class="getSortClass('Agentchecks.plugin_name')"></i>
                                    <?php echo __('Plugin name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Servicetemplates.template_name')">
                                    <i class="fa" ng-class="getSortClass('Servicetemplates.template_name')"></i>
                                    <?php echo __('Service template name'); ?>
                                </th>
                                <th class="no-sort text-center">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr ng-repeat="agentcheck in agentchecks">
                                <td class="text-center" class="width-15">
                                    <?php if ($this->Acl->hasPermission('delete', 'agentchecks')): ?>
                                        <input type="checkbox"
                                               ng-model="massChange[agentcheck.id]"
                                               ng-show="agentcheck.allow_edit">
                                    <?php endif; ?>
                                </td>
                                <td>{{agentcheck.name}}</td>
                                <td>{{agentcheck.plugin_name}}</td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                        <a ui-sref="ServicetemplatesEdit({id: agentcheck.servicetemplate.id})">
                                            {{agentcheck.servicetemplate.template_name}}
                                        </a>
                                    <?php else: ?>
                                        {{agentcheck.servicetemplate.template_name}}
                                    <?php endif; ?>
                                </td>
                                <td class="width-50">
                                    <div class="btn-group btn-group-xs">
                                        <?php if ($this->Acl->hasPermission('edit', 'agentchecks')): ?>
                                            <a ui-sref="AgentchecksEdit({id: agentcheck.id})"
                                               ng-if="agentcheck.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!agentcheck.allow_edit"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button data-toggle="dropdown" type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"><i
                                                    class="caret"></i></button>
                                        <div class="dropdown-menu dropdown-menu-right"
                                             id="menuHack-{{agentcheck.id}}">
                                            <?php if ($this->Acl->hasPermission('edit', 'agentchecks')): ?>
                                                <a ui-sref="AgentchecksEdit({id:agentcheck.id})"
                                                   ng-if="agentcheck.allow_edit" class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('delete', 'agentchecks')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);" ng-if="agentcheck.allow_edit"
                                                   class="dropdown-item txt-color-red"
                                                   ng-click="confirmDelete(getObjectForDelete(agentcheck))">
                                                    <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="agentchecks.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                    </div>


                    <div class="row margin-top-10 margin-bottom-10">
                        <div class="col-xs-12 col-md-2 text-muted text-center">
                            <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <span ng-click="selectAll()" class="pointer">
                                <i class="fa fa-lg fa-check-square"></i>
                                <?php echo __('Select all'); ?>
                            </span>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <span ng-click="undoSelection()" class="pointer">
                                <i class="fa fa-lg fa-square"></i>
                                <?php echo __('Undo selection'); ?>
                            </span>
                        </div>
                        <?php if ($this->Acl->hasPermission('delete', 'agentchecks')): ?>
                            <div class="col-xs-12 col-md-2 txt-color-red">
                                <span ng-click="confirmDelete(getObjectsForDelete())" class="pointer">
                                    <i class="fa fa-lg fa-trash"></i>
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
