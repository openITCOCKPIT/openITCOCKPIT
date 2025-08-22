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
        <a ui-sref="StatuspagesIndex">
            <i class="fas fa-info-circle"></i> <?php echo __('Status pages'); ?>
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
                    <?php echo __('Status pages'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'statuspages')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="StatuspagesAdd">
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
                                                   placeholder="<?php echo __('Filter by Status page name'); ?>"
                                                   ng-model="filter.Statuspages.name"
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
                                                   placeholder="<?php echo __('Filter by Status page description'); ?>"
                                                   ng-model="filter.Statuspages.description"
                                                   ng-model-options="{debounce: 500}">

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-lg-2">
                                    <fieldset>
                                        <h5><?php echo __('Public'); ?></h5>
                                        <div class="form-group smart-form">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statuspageIsPublic"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Statuspages.is_public"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statuspageIsPublic"><?php echo __('Is public'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statuspageIsNotPublic"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Statuspages.is_not_public"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statuspageIsNotPublic"><?php echo __('Not public'); ?></label>
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
                    <!-- Filter end -->
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort sorting_disabled width-15">
                                    <i class="fa fa-check-square"></i>
                                </th>
                                <th class="no-sort col-4" ng-click="orderBy('Statuspages.name')">
                                    <i class="fa" ng-class="getSortClass('Statuspages.name')"></i>
                                    <?php echo __('Status page name'); ?>
                                </th>
                                <th class="no-sort col-4" ng-click="orderBy('Statuspages.description')">
                                    <i class="fa" ng-class="getSortClass('Statuspages.description')"></i>
                                    <?php echo __('Description'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Statuspages.show_downtimes')">
                                    <i class="fa" ng-class="getSortClass('Statuspages.show_downtimes')"></i>
                                    <?php echo __('Show downtimes'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Statuspages.show_acknowledgements')">
                                    <i class="fa" ng-class="getSortClass('Statuspages.show_acknowledgements')"></i>
                                    <?php echo __('Show acknowledgements'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Statuspages.public')">
                                    <i class="fa" ng-class="getSortClass('Statuspages.public')"></i>
                                    <?php echo __('Public'); ?>
                                </th>
                                <th class="no-sort text-center width-50">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr ng-repeat="statuspage in statuspages">
                                <td class="text-center" class="width-15">
                                    <?php if ($this->Acl->hasPermission('delete', 'statuspages')): ?>
                                        <input type="checkbox"
                                               ng-model="massChange[statuspage.id]"
                                               ng-show="statuspage.allow_edit">
                                    <?php endif; ?>
                                </td>

                                <td class="word-break">
                                    <?php if ($this->Acl->hasPermission('view', 'statuspages')): ?>
                                        <a ui-sref="StatuspagesView({id: statuspage.id})">
                                            {{statuspage.name}}
                                        </a>
                                    <?php else: ?>
                                        {{statuspage.name}}
                                    <?php endif; ?>
                                </td>
                                <td class="text-truncate text-truncate-sm">{{statuspage.description}}</td>
                                <td class="text-center">
                                    <span class="badge border border-danger text-danger"
                                          ng-hide="statuspage.show_downtimes">
                                        <?= __('No'); ?>
                                    </span>
                                    <span class="badge border border-success text-success"
                                          ng-show="statuspage.show_downtimes && !statuspage.show_downtime_comments">
                                        <?= __('Yes'); ?>
                                    </span>
                                    <span class="badge border border-success text-success"
                                          ng-show="statuspage.show_downtimes && statuspage.show_downtime_comments">
                                        <?= __('Yes with comments'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge border border-danger text-danger"
                                          ng-hide="statuspage.show_acknowledgements">
                                        <?= __('No'); ?>
                                    </span>
                                    <span class="badge border border-success text-success"
                                          ng-show="statuspage.show_acknowledgements && !statuspage.show_downtime_comments">
                                        <?= __('Yes'); ?>
                                    </span>
                                    <span class="badge border border-success text-success"
                                          ng-show="statuspage.show_acknowledgements && statuspage.show_downtime_comments">
                                        <?= __('Yes with comments'); ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge border border-primary text-primary" ng-show="statuspage.public">
                                        <?= __('Yes'); ?>
                                    </span>
                                    <span class="badge border border-secondary text-secondary"
                                          ng-show="!statuspage.public">
                                        <?= __('No'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'statuspages')): ?>
                                            <a ui-sref="StatuspagesEdit({id: statuspage.id})"
                                               ng-if="statuspage.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'statuspages')): ?>
                                                <a ui-sref="StatuspagesEdit({id: statuspage.id})"
                                                   ng-if="statuspage.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('view', 'statuspages')): ?>
                                                <a ui-sref="StatuspagesView({id: statuspage.id})"
                                                   class="dropdown-item">
                                                    <i class="fas fa-eye"></i>
                                                    <?php echo __('View'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <a href="/statuspages/publicView/{{statuspage.id}}" target="_blank"
                                               class="dropdown-item" ng-show="statuspage.public">
                                                <i class="fa-solid fa-users-rectangle"></i>
                                                <?php echo __('Public View'); ?>
                                            </a>
                                            <?php if ($this->Acl->hasPermission('delete', 'statuspages')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-if="statuspage.allow_edit"
                                                   class="txt-color-red dropdown-item"
                                                   ng-click="confirmDelete(getObjectForDelete(statuspage))">
                                                    <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="statuspages.length == 0">
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
                            <?php if ($this->Acl->hasPermission('copy', 'statuspages')): ?>
                                <div class="col-xs-12 col-md-2">
                                    <a ui-sref="StatuspagesCopy({ids: linkForCopy()})" class="a-clean">
                                        <i class="fas fa-lg fa-files-o"></i>
                                        <?php echo __('Copy'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('delete', 'statuspages')): ?>
                                <div class="col-xs-12 col-md-4 txt-color-red">
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
