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
        <a ui-sref="SystemHealthUsersIndex">
            <i class="fa fa-user"></i> <?php echo __('System health users'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Add'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Create new system health user:'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'systemHealthUsers')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='SystemHealthUsersIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('System health users'); ?>' , message: '<?php echo __('saved successfully'); ?>'}">

                        <div class="form-group required"
                             ng-class="{'has-error': errors.users}">
                            <label class="col-xs-12 col-lg-2 control-label">
                                <?php echo __('Users'); ?>
                            </label>
                            <div class="input-group" style="width: 100%">
                                <select
                                    id="UsersSelect"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="users"
                                    multiple
                                    ng-options="user.key as user.value for user in users"
                                    ng-model="post.SystemHealthUser.user_ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.users">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <hr>

                        <?php
                        $notificationOptions = [
                            [
                                'field' => 'notify_on_recovery',
                                'class' => 'success',
                                'text'  => __('Recovery')
                            ],
                            [
                                'field' => 'notify_on_warning',
                                'class' => 'warning',
                                'text'  => __('Warning')
                            ],
                            [
                                'field' => 'notify_on_critical',
                                'class' => 'danger',
                                'text'  => __('Critical')
                            ],
                        ];
                        ?>
                        <fieldset>
                            <legend class="fs-md"
                                    ng-class="{'has-error-no-form': errors.notify_on_recovery}">
                                <div class="required">
                                    <label>
                                        <?php echo __('System health notification options'); ?>
                                    </label>

                                    <div ng-repeat="error in errors.notify_on_recovery">
                                        <div class="text-danger">{{ error }}</div>
                                    </div>

                                </div>
                            </legend>
                            <?php foreach ($notificationOptions as $notificationOption): ?>
                                <div class="custom-control custom-checkbox margin-bottom-10"
                                     ng-class="{'has-error': errors.<?php echo $notificationOption['field']; ?>}">
                                    <input type="checkbox" class="custom-control-input"
                                           ng-true-value="1"
                                           ng-false-value="0"
                                           id="<?php echo $notificationOption['field']; ?>"
                                           ng-model="post.SystemHealthUser.<?php echo $notificationOption['field']; ?>">
                                    <label class="custom-control-label"
                                           for="<?php echo $notificationOption['field']; ?>">
                                        <span
                                            class="badge badge-<?php echo $notificationOption['class']; ?> notify-label"><?php echo $notificationOption['text']; ?></span>
                                        <i class="checkbox-<?php echo $notificationOption['class']; ?>"></i>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </fieldset>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <label>
                                        <input type="checkbox" ng-model="data.createAnother">
                                        <?php echo __('Create another'); ?>
                                    </label>
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Create user'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='SystemHealthUsersIndex'
                                       class="btn btn-default">
                                        <?php echo __('Cancel'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
