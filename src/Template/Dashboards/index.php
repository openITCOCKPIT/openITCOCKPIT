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
        <i class="fa fa-tachometer-alt"></i> <?php echo __('Dashboard'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="widget-container" class="panel">
            <div class="panel-hdr">
                <div class="row w-100">
                    <div class="col-xl-8 col-lg-8 col-md-6 col-sm-12 col-xs-12">
                        <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean h-100" role="tablist">
                            <li class="padding-right-20">
                                <h2>
                                    <?php echo __('Dashboard'); ?>
                                </h2>
                            </li>
                            <li class="nav-item" data-tab-id="{{tab.id}}" ng-repeat="tab in tabs"
                                ng-class="{'active':activeTab === tab.id, 'ui-sortable-handle': !tab.pinned}">
                                <a class="nav-link"
                                   href="javascript:void(0);"
                                   ng-if="activeTab !== tab.id"
                                   ng-class="{ 'tab-allocated': !tab.isOwner}"
                                   role="tab">
                                    <span class="text" ng-click="loadTabContent(tab.id)"
                                          ng-class="{ 'text-primary': tab.shared === true,
                                      'tab-allocated': tab.dashboard_tab_allocation,
                                       'tab-shared-and-allocated-gradient': tab.shared === true && tab.dashboard_tab_allocation}">
                                        <i class="fa-solid fa-thumbtack" ng-show="tab.pinned"></i>
                                        {{tab.name}}
                                    </span>
                                </a>

                                <!-- For owners of the tab (include drop down menu with options) -->
                                <a href="javascript:void(0);"
                                   class="nav-link active dropdown-toggle"
                                   data-toggle="dropdown"
                                   aria-expanded="false"
                                   ng-if="activeTab === tab.id && tab.isOwner"
                                   ng-class="{ 'text-primary': tab.shared }">
                                    <span class="text"
                                          ng-class="{ 'text-primary': tab.shared === true,
                                          'tab-allocated': tab.dashboard_tab_allocation,
                                          'tab-shared-and-allocated-gradient': tab.shared === true && tab.dashboard_tab_allocation}">
                                        <i class="fa-solid fa-thumbtack" ng-show="tab.pinned"></i>
                                        {{tab.name}}
                                    </span>
                                    <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu" id="menuHack-tab-{{tab.id}}" ng-if="tab.isOwner">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item"
                                           ng-click="triggerRenameTabModal(tab.name)"
                                           ng-class="{'text-muted':dashboardIsLocked}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <?php echo __('Rename'); ?>
                                        </a>
                                    </li>
                                    <?php if ($this->Acl->hasPermission('add', 'DashboardAllocations') &&
                                        $this->Acl->hasPermission('edit', 'DashboardAllocations')): ?>
                                        <li ng-hide="!tab.isOwner">
                                            <a href="javascript:void(0);" class="dropdown-item"
                                               ng-click="allocateDashboard(tab.id)">
                                                <i class="fa fa-user"></i>
                                                <?php echo __('Allocate Dashboard'); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li ng-hide="tab.shared">
                                        <a href="javascript:void(0);" class="dropdown-item"
                                           ng-click="startSharing(tab.id)">
                                            <i class="fa fa-code-fork"></i>
                                            <?php echo __('Start sharing'); ?>
                                        </a>
                                    </li>
                                    <li ng-show="tab.shared">
                                        <a href="javascript:void(0);" class="dropdown-item"
                                           ng-click="stopSharing(tab.id)">
                                            <i class="fa fa-code-fork"></i>
                                            <?php echo __('Stop sharing'); ?>
                                        </a>
                                    </li>
                                    <div class="dropdown-divider"></div>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item txt-color-red"
                                           ng-click="deleteTab(tab.id)">
                                            <i class="fa fa-trash"></i>
                                            <?php echo __('Delete'); ?>
                                        </a>
                                    </li>
                                </ul>

                                <!-- For users who got this tab by allocation (no drop down menu with options) -->
                                <a href="javascript:void(0);"
                                   class="nav-link active tab-allocated"
                                   aria-expanded="false"
                                   ng-if="activeTab === tab.id && !tab.isOwner"
                                   ng-class="{ 'text-primary': tab.shared}">
                                    <span class="text"
                                          ng-class="{ 'text-primary': tab.shared === true}">
                                        <i class="fa-solid fa-thumbtack" ng-show="tab.pinned"></i>
                                        {{tab.name}}
                                    </span>
                                </a>

                            </li>
                        </ul>
                    </div>
                    <div
                        class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-xs-12 panel-toolbar pl-0 pr-0 pt-2 d-inline text-right">
                        <button class="btn btn-xs mr-1 shadow-0 btn-primary"
                                title="<?php echo __('Setup tab rotation'); ?>"
                                data-toggle="modal" data-target="#tabRotationModal">
                            <i class="fa fa-spinner"></i>
                        </button>
                        <button class="btn btn-xs mr-1 shadow-0"
                                ng-class="{ 'btn-danger': dashboardIsLocked, 'btn-primary': !dashboardIsLocked }"
                                ng-hide="isReadonly"
                                title="<?php echo __('Lock for edit'); ?>"
                                ng-click="lockOrUnlockDashboard()">
                            <i class="fa fa-lock"
                               ng-class="{ 'fa-lock': dashboardIsLocked, 'fa-unlock': !dashboardIsLocked }"></i>
                        </button>

                        <div class="btn-group btn-group-xs margin-right-5"
                             ng-hide="isReadonly">
                            <button class="btn btn-success waves-effect waves-themed" type="button"
                                    data-toggle="modal" data-target="#addWidgetModal" ng-click="resetWidgetSearch()"
                                    ng-disabled="dashboardIsLocked">
                                <?php echo __('Add Widget'); ?>
                            </button>
                        </div>

                        <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="refresh()"
                                title="<?php echo __('Refresh'); ?>">
                            <i class="fa fa-refresh"></i>
                            <?php echo __('Refresh'); ?>
                        </button>

                        <button class="btn btn-xs btn-success mr-1 shadow-0"
                                title="<?php echo __('Add tab'); ?>"
                                data-toggle="modal" data-target="#addNewTabModal"
                                ng-click="loadSharedTabs()">
                            <i class="fa fa-plus"></i>
                        </button>

                        <button class="btn btn-xs btn-success shadow-0" ng-click="toggleFullscreenMode()"
                                title="<?php echo __('Fullscreen mode'); ?>">
                            <i class="fa fa-expand-arrows-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div gridster="gridsterOpts">
                        <ul>
                            <li gridster-item="widget" ng-repeat="widget in activeWidgets"
                                style="display:flex; display: -webkit-flex; flex-direction: row; -webkit-flex-direction: row; -webkit-align-content: stretch; align-content: stretch;">
                                <div class="card {{widget.color}}"
                                     data-widget-colorbutton="true"
                                     style="width:100%;" id="widget-{{widget.id}}">
                                    <div role="heading"
                                         class="ui-sortable-handle card-header d-flex"
                                         ng-style="{'cursor': widget.isReadonly || dashboardIsLocked ? '' : 'move'}">
                                        <div class="card-title mr-auto padding-top-0_2-rem">
                                            <i class="{{widget.icon}}"></i>
                                            {{widget.title}}
                                        </div>
                                        <a class="btn btn-sm btn-icon waves-effect waves-themed"
                                           title="<?php echo __('Edit title'); ?>"
                                           ng-click="triggerRenameWidgetModal(widget.id)"
                                           ng-hide="dashboardIsLocked">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        <div>
                                            <button type="button" ng-hide="dashboardIsLocked"
                                                    class="btn btn-sm btn-icon waves-effect waves-themed"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-palette"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right flex-wrap"
                                                 style="width: 10.2rem; padding: 0.5rem">
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-statusGreen-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-statusGreen-gradient"
                                                        ng-click="widget.color='widget-statusGreen'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-statusYellow-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-statusYellow-gradient"
                                                        ng-click="widget.color='widget-statusYellow'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-statusRed-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-statusRed-gradient"
                                                        ng-click="widget.color='widget-statusRed'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-statusGrey-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-statusGrey-gradient"
                                                        ng-click="widget.color='widget-statusGrey'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-default width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-default"
                                                        ng-click="widget.color='widget-default'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-white width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-white"
                                                        ng-click="widget.color='widget-white'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-black-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-black-gradient"
                                                        ng-click="widget.color='widget-black'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-anthracite-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-anthracite-gradient"
                                                        ng-click="widget.color='widget-anthracite'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-colorbomb-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-colorbomb-gradient"
                                                        ng-click="widget.color='widget-colorbomb'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-colorbomb2-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-colorbomb2-gradient"
                                                        ng-click="widget.color='widget-colorbomb2'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-aqua-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-aqua-gradient"
                                                        ng-click="widget.color='widget-aqua'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-ocean-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-aqua-gradient"
                                                        ng-click="widget.color='widget-ocean'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-purple-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-aqua-gradient"
                                                        ng-click="widget.color='widget-purple'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-hacktober-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-aqua-gradient"
                                                        ng-click="widget.color='widget-hacktober'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-orange-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-aqua-gradient"
                                                        ng-click="widget.color='widget-orange'"
                                                        style="margin:1px;"></button>
                                                <button type="button"
                                                        class="btn d-inline-block bg-widget-blue-gradient width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                                        data-panel-setstyle="bg-widget-aqua-gradient"
                                                        ng-click="widget.color='widget-blue'"
                                                        style="margin:1px;"></button>
                                            </div>
                                        </div>
                                        <a class="btn btn-sm btn-icon  waves-effect waves-themed"
                                           title="<?php echo __('Remove widget'); ?>"
                                           ng-click="removeWidgetFromTab(widget.id)"
                                           ng-hide="dashboardIsLocked">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                    <!-- Loading used AngularJs directives dynamically -->
                                    <div role="content" id="widget-content-{{widget.id}}"
                                         style="height:100%; overflow: auto;" class="card-body">
                                        <ng-include
                                            src="'/dashboards/dynamicDirective?directive='+ widget.directive + '&readonly=' + widget.isReadonly"></ng-include>
                                    </div>
                                </div>
                                <div ng-if="$last" ng-init="$last?enableWatch():null"></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Rename widget modal -->
<div id="renameWidgetModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Edit widget title'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-group" ng-class="{'has-error': ack.error}">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="icon-prepend fa fa-pencil-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="<?php echo __('Edit widget title'); ?>"
                                   ng-model="data.renameWidgetTitle">
                        </div>
                        <div ng-repeat="error in errors.name">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" ng-click="renameWidget()">
                    <?php echo __('Save widget Title'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add new Tab modal -->
<div id="addNewTabModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-plus"></i>
                    <?php echo __('Create new tab'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group padding-bottom-10">
                            <label class="control-label">
                                <?php echo __('Tab name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="data.newTabName">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <button type="button" class="btn btn-primary float-right" ng-click="addNewTab()">
                            <?php echo __('Create new tab'); ?>
                        </button>
                    </div>
                </div>
                <hr/>
                <div class="row">
                    <h5 class="modal-title margin-left-12">
                        <i class="fa fa-plus"></i>
                        <?php echo __('Create from shared tab'); ?>
                    </h5>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group margin-top-20 padding-bottom-10">
                            <label class="control-label">
                                <?php echo __('Select shared tab'); ?>
                            </label>
                            <select
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="sharedTabs"
                                ng-options="sharedTab.id as sharedTab.name for sharedTab in sharedTabs"
                                ng-model="data.createTabFromSharedTabId">
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <button type="button" class="btn btn-primary float-right" ng-click="addFromSharedTab()">
                            <?php echo __('Create from shared tab'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Tab rotation modal -->
<div id="tabRotationModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Tab rotation interval'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">

                    <div class="col-lg-12">
                        <label>
                            <?php echo __('Set tab rotation interval'); ?>
                        </label>
                        <div class="slidecontainer">
                            <input type="range" step="10" min="0" max="900" class="slider" style="width: 100%"
                                   ng-model="data.viewTabRotateInterval" ng-mouseup="saveTabRotateInterval()">
                            <div>
                                <div class="help-block text-muted">{{ intervalText }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <br/>
                <div class="row">
                    <div class="col-lg-12 text-info">
                        <i class="fa fa-info-circle"></i>
                        <?php echo __('If you only have one tab, the rotation will just refresh the data of your current tab.'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Rename tab modal -->
<div id="renameTabModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Rename tab'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-group" ng-class="{'has-error': ack.error}">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="icon-prepend fa fa-pencil-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="<?php echo __('New tab name'); ?>"
                                   ng-model="data.renameTabName">
                        </div>
                        <div ng-repeat="error in errors.name">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" ng-click="renameTab()">
                    <?php echo __('Rename tab'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Update available modal -->
<div id="updateAvailableModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-code-fork"></i>
                    <?php echo __('For your dashboard is an update available'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-lg-12 text-info">
                        <i class="fa fa-info-circle"></i>
                        <?php echo __('You created this dashboard out of an "shared" dashboard. The original dashboard was updated.'); ?>
                        <br/>
                        <?php echo __('This means the original dashboard was reorder, new objects where added or existing objects gets deleted.'); ?>
                        <?php echo __('You can now choose if you want to update your dashboard or keep your current dashboard.'); ?>
                        <br/><br/>
                        <?php echo __('Warning: By updating your dashboard, local modifications will get lost.'); ?>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" ng-click="neverPerformUpdates()">
                    <?php echo __('Never perform an update for this dashboard'); ?>
                </button>

                <button type="button" class="btn btn-primary " ng-click="performUpdate()">
                    <?php echo __('Yes update'); ?>
                </button>

                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('No thanks'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rename widget modal -->
<div id="renameWidgetModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i>
                    <?php echo __('Edit widget title'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">

                    <div class="col-xs-12">
                        <div class="form-group">
                            <label class="input"> <i class="icon-prepend fa fa-pencil"></i>
                                <input type="text" class="input-sm"
                                       placeholder="<?php echo __('New title of widget'); ?>"
                                       ng-model="data.renameWidgetTitle">
                            </label>
                        </div>
                        <div ng-repeat="error in errors.name">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                </div>
                <br/>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success" ng-click="renameWidget()">
                    <?php echo __('Save widget Title'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<!--- Allocate Dashboard Modal --->
<div id="allocateDashboardModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-user"></i>
                    <?php echo __('Allocate Dashboard'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal"
                      ng-init="successMessage=
                        {objectName : '<?php echo __('Dashboard tab allocation'); ?>' , message:
                        '<?= __('saved successfully'); ?>'}">

                    <!-- Select Container -->
                    <div class="form-group" ng-class="{'has-error': errors.containers}">
                        <label class="control-label hintmark" for="UserContainers">
                            <?php echo __('Container'); ?>
                        </label>
                        <select
                            id="UserContainers"
                            data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="containers"
                            ng-options="container.key as container.value for container in containers"
                            ng-model="post.DashboardAllocation.container_id">
                        </select>
                        <div ng-repeat="error in errors.container_id">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                    <div class="form-group required" ng-class="{'has-error': errors.name}">
                        <label class="control-label">
                            <?php echo __('Name of the allocation'); ?>
                        </label>
                        <input
                            class="form-control"
                            type="text"
                            ng-model="post.DashboardAllocation.name">
                        <div ng-repeat="error in errors.name">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                    <!-- Select Dashboard Tab to Share -->
                    <div class="form-group required" ng-class="{'has-error': errors.dashboard_tab_id}">
                        <label class="control-label" for="DashboardTab">
                            <?php echo __('Dashboard to allocate'); ?>
                        </label>
                        <select
                            id="DashboardTab"
                            data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="dashboard_tabs"
                            ng-readonly="true"
                            ng-disabled="true"
                            ng-options="dashboard_tab.key as dashboard_tab.value for dashboard_tab in dashboard_tabs"
                            ng-model="post.DashboardAllocation.dashboard_tab_id">
                        </select>
                        <div ng-repeat="error in errors.dashboard_tab_id">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>

                    <!-- Select Users -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group margin-top-20 padding-bottom-10">
                                <label class="control-label">
                                    <?php echo __('Allocated Users'); ?>
                                </label>
                                <select
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="users"
                                    ng-options="user.key as user.value for user in users"
                                    ng-model="post.DashboardAllocation.users._ids"
                                    multiple="multiple">
                                </select>
                                <div ng-repeat="error in errors.users">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Select Roles -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group margin-top-20 padding-bottom-10">
                                <label class="control-label">
                                    <?php echo __('Allocated User roles'); ?>
                                </label>
                                <select
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="usergroups"
                                    ng-options="usergroup.key as usergroup.value for usergroup in usergroups"
                                    ng-model="post.DashboardAllocation.usergroups._ids"
                                    multiple="multiple">
                                </select>
                                <div ng-repeat="error in errors.usergroups">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- pinDashboard -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group margin-top-20 padding-bottom-10">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="pinDashboard"
                                           ng-model="post.DashboardAllocation.pinned">
                                    <label class="custom-control-label" for="pinDashboard">
                                        <?php echo __('Pin Dashboard'); ?>
                                    </label>
                                </div>
                                <div
                                    class="help-block"><?php echo __('If enabled, this dashboard will be pinned at the leftmost tab.'); ?></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <?php if ($this->Acl->hasPermission('delete', 'DashboardAllocations')): ?>
                    <button type="button" ng-show="post.DashboardAllocation.id"
                            class="btn btn-danger mr-auto" ng-click="deleteAllocation(post.DashboardAllocation.id)">
                        <?php echo __('Delete'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" ng-hide="post.DashboardAllocation.id" class="btn btn-primary"
                        ng-click="addDashboardAllocation()">
                    <?php echo __('Create Allocation'); ?>
                </button>
                <button type="button" ng-show="post.DashboardAllocation.id" class="btn btn-primary"
                        ng-click="editDashboardAllocation()">
                    <?php echo __('Update Allocation'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add widget modal -->
<div id="addWidgetModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-plus"></i>
                    <?php echo __('Add Widget'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        ng-click="resetWidgetSearch()">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="control-label">
                                <?php echo __('Search'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                size="255"
                                placeholder="<?php echo __('Search for widgets'); ?>"
                                ng-model="widgetSearchStr">
                            <div ng-if="noWidgetsFound">
                                <div
                                    class="help-block text-danger"><?php echo __('No matching widgets found.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 margin-top-20 mb-1">
                        <span><?php echo __('List of Widgets'); ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="list-group">
                            <a href="javascript:void(0);" ng-repeat="availableWidget in availableWidgets"
                               ng-show="widgetSearchStr.length === 0 || availableWidget.title.toLowerCase().includes(widgetSearchStr.toLowerCase())"
                               ng-click="addWidgetToTab(availableWidget.type_id)"
                               class="list-group-item list-group-item-action py-2">
                                <i class="{{availableWidget.icon}}"></i>&nbsp;
                                {{availableWidget.title}}
                            </a>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" ng-click="restoreDefault()">
                    <i class="fa fa-recycle"></i>
                    <?php echo __('Restore default'); ?>
                </button>

                <button type="button" class="btn btn-default" data-dismiss="modal" ng-click="resetWidgetSearch()">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
