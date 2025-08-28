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
$timezones = \itnovum\openITCOCKPIT\Core\Timezone::listTimezones();
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="UsercontainerrolesIndex">
            <i class="fa fa-users"></i> <?php echo __('User container roles'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit user container role:'); ?>
                    <span class="fw-300"><i> {{post.Usercontainerrole.name}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'usercontainerroles')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='UsercontainerrolesIndex'
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
            {objectName : '<?php echo __('User container role'); ?>' , message: '<?php echo __('saved successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Role name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Usercontainerrole.name">
                            <div ng-repeat="error in errors.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.ldapgroups}" ng-show="isLdapAuth">
                            <label class="control-label" for="ldapGroups">
                                <?php echo __('LDAP groups'); ?>
                            </label>
                            <select
                                id="ldapGroups"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="ldapgroups"
                                callback="loadLdapGroups"
                                multiple
                                ng-options="ldapgroup.key as ldapgroup.value for ldapgroup in ldapgroups"
                                ng-model="post.Usercontainerrole.ldapgroups._ids">
                            </select>
                            <div ng-repeat="error in errors.ldapgroups">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?= __('LDAP users which are member of the selected LDAP groups will be assigned automatically to the user container role.'); ?>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.containers}">
                            <label class="control-label" for="Container">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="Container"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                multiple
                                ng-options="container.key as container.value for container in containers"
                                ng-model="selectedContainers">
                            </select>
                            <div ng-repeat="error in errors.containers">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>
                        <!-- User Container Roles permissions read/write -->
                        <div class="row" ng-repeat="container in selectedContainerWithPermission">
                            <div class="col col-md-2"></div>
                            <div class="col col-md-10">
                                <legend class="no-padding font-sm txt-ack">
                                    {{container.name}}
                                </legend>
                                <input name="ucgroup-{{container.container_id}}"
                                       type="radio"
                                       value="1"
                                       ng-model="container.permission_level"
                                       ng-disabled="container.container_id === 1 || container.readonly"
                                       ng-checked="container.permission_level == 1">
                                <label class="padding-10 font-sm"><?php echo __('read'); ?></label>

                                <input name="ucgroup-{{container.container_id}}"
                                       type="radio"
                                       value="2"
                                       ng-model="container.permission_level"
                                       ng-disabled="container.container_id === 1 || container.readonly"
                                       ng-checked="container.permission_level == 2">
                                <label class="padding-10 font-sm"><?php echo __('read/write'); ?></label>
                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Update user container role'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='UsercontainerrolesIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
