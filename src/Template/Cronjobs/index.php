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
        <a ui-sref="CronjobsIndex">
            <i class="fa fa-clock-o"></i> <?php echo __('Cron jobs'); ?>
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
                    <?php echo __('Cronjobs'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'cronjobs')): ?>
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
                                <th class="no-sort"><?php echo __('Task') ?></th>
                                <th class="no-sort"><?php echo __('Plugin') ?></th>
                                <th class="no-sort"><?php echo __('Interval'); ?></th>
                                <th class="no-sort"><?php echo __('Last scheduled'); ?></th>
                                <th class="no-sort"><?php echo __('Is currently running'); ?></th>
                                <th class="no-sort"><?php echo __('Enabled'); ?></th>
                                <th class="no-sort text-center"><i class="fa fa-gear"></i></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="cronjob in cronjobs">
                                <td>{{cronjob.Cronjob.task}}</td>
                                <td>{{cronjob.Cronjob.plugin}}</td>
                                <td>{{cronjob.Cronjob.interval}}</td>
                                <td>{{cronjob.Cronschedule.last_scheduled_usertime}}</td>
                                <td class="text-center">
                                    <span ng-show="cronjob.Cronschedule.is_running == 1"><?php echo __('Yes'); ?></span>
                                    <span ng-show="cronjob.Cronschedule.is_running == 0"><?php echo __('No'); ?></span>
                                </td>
                                <td class="text-align-center" ng-if="cronjob.Cronjob.enabled">
                                    <i class="fa fa-check text-success"></i>
                                </td>
                                <td class="text-align-center" ng-if="!cronjob.Cronjob.enabled">
                                    <i class="fa fa-times text-danger"></i>
                                </td>
                                <td class="width-50">
                                        <?php if ($this->Acl->hasPermission('edit', 'cronjobs')): ?>
                                            <button ng-click="triggerEditModal(cronjob.Cronjob);"
                                               href="javascript:void(0);"
                                               class="btn btn-default btn-xs btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="javascript:void(0);" class="btn btn-default btn-xs disabled btn-lower-padding">
                                                &nbsp;<i class="fa fa-cog"></i>&nbsp;</a>
                                        <?php endif; ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="cronjobs.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Add cronjob modal -->
<div id="addCronjobModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-plus"></i>
                    <?php echo __('Add Cronjob'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group required" ng-class="{'has-error': errors.name}">
                    <label class="control-label" for="AddCronjobPluginSelect">
                        <?php echo __('Plugin'); ?>
                    </label>
                    <select
                        id="AddCronjobPluginSelect"
                        data-placeholder="<?php echo __('Please choose'); ?>"
                        class="form-control"
                        class="form-control"
                        chosen="availablePlugins"
                        ng-options="value as value for (key , value) in availablePlugins"
                        ng-model="post.Cronjob.plugin">
                    </select>
                    <div ng-repeat="error in errors.plugin">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group required" ng-class="{'has-error': errors.task}">
                    <label class="control-label" for="AddCronjobTaskSelect">
                        <?php echo __('Task'); ?>
                    </label>
                    <select
                        id="AddCronjobTaskSelect"
                        data-placeholder="<?php echo __('Please choose'); ?>"
                        class="form-control"
                        class="form-control"
                        chosen="availableTasks"
                        ng-options="value as value for (key , value) in availableTasks"
                        ng-model="post.Cronjob.task">
                    </select>
                    <div ng-repeat="error in errors.task">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.interval}">
                    <label class="control-label">
                        <?php echo __('Interval'); ?>
                    </label>
                    <input
                        class="form-control"
                        type="number"
                        min="0"
                        ng-model="post.Cronjob.interval">
                    <div ng-repeat="error in errors.interval">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                    <div class="help-block">
                        <?php echo __('Cronjob schedule interval in minutes'); ?>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.enabled}">
                    <div class="custom-control custom-checkbox  margin-bottom-10"
                         ng-class="{'has-error': errors.enabled}">

                        <input type="checkbox"
                               id="cronjobEnabled"
                               class="custom-control-input"
                               ng-true-value="1"
                               ng-false-value="0"
                               ng-model="post.Cronjob.enabled">
                        <label class="custom-control-label" for="cronjobEnabled">
                            <?php echo __('Enabled'); ?>
                        </label>
                    </div>

                    <div class="col col-xs-12 col-md-offset-2 help-block">
                        <?php echo __('Determine if this cronjob should be executed.'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" ng-click="saveCronjob()">
                    <?php echo __('Save'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit cronjob modal -->
<div id="editCronjobModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Edit Cronjob'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group required" ng-class="{'has-error': errors.name}">
                    <label class="control-label" for="AddCronjobPluginSelect">
                        <?php echo __('Plugin'); ?>
                    </label>
                    <select
                        id="AddCronjobPluginSelect"
                        data-placeholder="<?php echo __('Please choose'); ?>"
                        class="form-control"
                        class="form-control"
                        chosen="availablePlugins"
                        ng-options="value as value for (key , value) in availablePlugins"
                        ng-model="editPost.Cronjob.plugin">
                    </select>
                    <div ng-repeat="error in errors.plugin">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group required" ng-class="{'has-error': errors.task}">
                    <label class="control-label" for="AddCronjobTaskSelect">
                        <?php echo __('Task'); ?>
                    </label>
                    <select
                        id="AddCronjobTaskSelect"
                        data-placeholder="<?php echo __('Please choose'); ?>"
                        class="form-control"
                        class="form-control"
                        chosen="availableTasks"
                        ng-options="value as value for (key , value) in availableTasks"
                        ng-model="editPost.Cronjob.task">
                    </select>
                    <div ng-repeat="error in errors.task">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.interval}">
                    <label class="control-label">
                        <?php echo __('Interval'); ?>
                    </label>
                    <input
                        class="form-control"
                        type="number"
                        min="0"
                        ng-model="editPost.Cronjob.interval">
                    <div ng-repeat="error in errors.interval">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                    <div class="help-block">
                        <?php echo __('Cronjob schedule interval in minutes'); ?>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.enabled}">
                    <div class="custom-control custom-checkbox  margin-bottom-10"
                         ng-class="{'has-error': errors.enabled}">

                        <input type="checkbox"
                               id="cronjobEditEnabled"
                               class="custom-control-input"
                               ng-model="editPost.Cronjob.enabled">
                        <label class="custom-control-label" for="cronjobEditEnabled">
                            <?php echo __('Enabled'); ?>
                        </label>
                    </div>

                    <div class="col col-xs-12 col-md-offset-2 help-block">
                        <?php echo __('Determine if this cronjob should be executed.'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" ng-click="deleteCronjob()">
                    <?php echo __('Delete'); ?>
                </button>

                <button type="button" class="btn btn-success" ng-click="editCronjob()">
                    <?php echo __('Save'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
