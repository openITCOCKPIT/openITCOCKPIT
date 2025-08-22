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
        <a ui-sref="MacrosIndex">
            <i class="fa fa-usd"></i> <?php echo __('User defined macros'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-block alert-warning">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-exclamation-triangle"></i>
                <?php echo __('Security notice'); ?>
            </h4>
            <?php echo __('User defined macros can also be used inside of check commands. This could lead to unwanted code execution.'); ?>
            <br />
            <?php echo __('It is recommended to only provide access for a certain group of users to edit commands and user defined macros.'); ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Macros'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'macros')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ng-click="triggerAddModal()">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort">
                                    <?php echo __('Name'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Value'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Description'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Actions'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="macro in macros">
                                <td class="text-primary bold">
                                    {{macro.name}}
                                </td>
                                <td>
                                    <code ng-class="{'macroPassword': macro.password}">
                                        {{macro.value}}
                                    </code>
                                </td>
                                <td>
                                    {{macro.description}}
                                </td>
                                <td>
                                    <button class="btn btn-default txt-color-red btn-icon btn-sm"
                                            title="<?php echo __('Hide value'); ?>"
                                            ng-click="macro.password = 1"
                                            ng-hide="macro.password">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>

                                    <button class="btn btn-default txt-color-blue btn-icon btn-sm"
                                            title="<?php echo __('Show value'); ?>"
                                            ng-click="macro.password = 0"
                                            ng-show="macro.password">
                                        <i class="fa fa-eye"></i>
                                    </button>

                                    <button class="btn btn-default btn-icon btn-sm" ng-click="triggerEditModal(macro);"
                                            title="<?php echo __('Edit macro'); ?>">
                                        <i class="fa fa-cog"></i>
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="users.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 padding-bottom-10 text-info">
                        <i class="fa fa-info-circle"></i>
                        <?php echo __('Nagios supports up to 256 $USERx$ macros ($USER1$ through $USER256$)'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add macro modal -->
<div id="addMacroModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-default">
                <h5 class="modal-title">
                    <i class="fa fa-usd"></i>
                    <?php echo __('Add user defined macro'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group required" ng-class="{'has-error': errors.name}">
                    <label class="control-label" for="AddMacroNameSelect">
                        <?php echo __('Macro name'); ?>
                    </label>
                    <select
                        id="AddMacroNameSelect"
                        data-placeholder="<?php echo __('Please choose'); ?>"
                        class="form-control"
                        class="form-control"
                        chosen="availableMacros"
                        ng-options="value as value for (key , value) in availableMacros"
                        ng-model="post.Macro.name">
                    </select>
                    <div ng-repeat="error in errors.name">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group required" ng-class="{'has-error': errors.value}">
                    <label class="control-label">
                        <?php echo __('Value'); ?>
                    </label>
                    <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-usd"></i></span>
                    </div>
                    <input
                        class="form-control"
                        type="text"
                        ng-model="post.Macro.value">
                    </div>
                    <div ng-repeat="error in errors.value">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.description}">
                    <label class="control-label">
                        <?php echo __('Description'); ?>
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                        </div>
                    <input
                        class="form-control"
                        type="text"
                        ng-model="post.Macro.description">
                    <div ng-repeat="error in errors.description">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.password}">
                    <div class="custom-control custom-checkbox  margin-bottom-10"
                         ng-class="{'has-error': errors.password}">

                        <input type="checkbox"
                               id="hideMacro"
                               class="custom-control-input"
                               ng-true-value="1"
                               ng-false-value="0"
                               ng-model="post.Macro.password">
                        <label class="custom-control-label" for="hideMacro">
                            <?php echo __('Hide value'); ?>
                        </label>
                    </div>

                    <div class="col col-xs-12 col-md-offset-2 help-block">
                        <?php echo __('Blur macro value to prevent accidentally leak values if your PC is connected to a projector or television.'); ?>
                        <?php echo __('Security notice: The value will be still written to the HTML document in plaintext!'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" ng-click="saveMacro()">
                    <?php echo __('Save'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Edit macro modal -->
<div id="editMacroModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-default">
                <h5 class="modal-title">
                    <i class="fa fa-usd"></i>
                    <?php echo __('Edit user defined macro'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group required" ng-class="{'has-error': errors.name}">
                    <label class="control-label" for="EditMacroNameSelect">
                        <?php echo __('Select macro name'); ?>
                    </label>
                    <select
                        id="EditMacroNameSelect"
                        data-placeholder="<?php echo __('Please choose'); ?>"
                        class="form-control"
                        class="form-control"
                        chosen="availableMacros"
                        ng-options="value as value for (key , value) in availableMacros"
                        ng-model="editPost.Macro.name">
                    </select>
                    <div class="help-block txt-color-yellow">
                        <i class="fa fa-exclamation-triangle"></i>
                        <?php echo __('If you change the macro name, you manually need to edit all occurrences of the macro!'); ?>
                    </div>
                    <div ng-repeat="error in errors.name">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group required" ng-class="{'has-error': errors.value}">
                    <label class="control-label">
                        <?php echo __('Value'); ?>
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-usd"></i></span>
                        </div>
                        <input
                            class="form-control"
                            type="text"
                            ng-model="editPost.Macro.value">
                    </div>
                    <div ng-repeat="error in errors.value">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.description}">
                    <label class="control-label">
                        <?php echo __('Description'); ?>
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                        </div>
                        <input
                            class="form-control"
                            type="text"
                            ng-model="editPost.Macro.description">
                        <div ng-repeat="error in errors.description">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.password}">
                    <div class="custom-control custom-checkbox  margin-bottom-10"
                         ng-class="{'has-error': errors.password}">

                        <input type="checkbox"
                               id="editHideMacro"
                               class="custom-control-input"
                               ng-true-value="1"
                               ng-false-value="0"
                               ng-model="editPost.Macro.password">
                        <label class="custom-control-label" for="editHideMacro">
                            <?php echo __('Hide value'); ?>
                        </label>
                    </div>

                    <div class="col col-xs-12 col-md-offset-2 help-block">
                        <?php echo __('Blur macro value to prevent accidentally leak values if your PC is connected to a projector or television.'); ?>
                        <?php echo __('Security notice: The value will be still written to the HTML document in plaintext!'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" ng-click="deleteMacro()">
                    <?php echo __('Delete'); ?>
                </button>
                <button type="button" class="btn btn-primary" ng-click="editMacro()">
                    <?php echo __('Save'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
