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
//	if you purchased an openitcockpit enterprise edition you can use this file
//	under the terms of the openitcockpit enterprise edition license agreement.
//	license agreement and license key will be shipped with the order
//	confirmation.

/**
 * @var boolean $isldapauth
 */

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="UsersIndex">
            <i class="fa fa-user"></i> <?php echo __('Users'); ?>
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
                    <?php echo __('Users'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'users')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="UsersAdd">
                            <i class="fas fa-plus"></i> <?php echo __('New local user'); ?>
                        </button>
                        <?php if ($isLdapAuth): ?>
                            <a ui-sref="UsersLdap" class="btn btn-xs btn-warning">
                                <i class="fa fa-plus"></i>
                                <?php echo __('Import from LDAP'); ?>
                            </a>
                        <?php endif; ?>
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
                                                   placeholder="<?php echo __('Filter by full name'); ?>"
                                                   ng-model="filter.full_name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by email'); ?>"
                                                   ng-model="filter.Users.email"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by phone'); ?>"
                                                   ng-model="filter.Users.phone"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-building"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by company'); ?>"
                                                   ng-model="filter.Users.company"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-12 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-users"></i></span>
                                            </div>
                                            <select
                                                id="UserRoles"
                                                data-placeholder="<?php echo __('Filter by user role'); ?>"
                                                class="form-control"
                                                chosen="usergroups"
                                                multiple
                                                ng-model="filter.Users.usergroup_id"
                                                ng-options="usergroup.key as usergroup.value for usergroup in usergroups">
                                            </select>
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
                                <th class="no-sort width-15">
                                    <i class="fa fa-check-square"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('full_name')">
                                    <i class="fa" ng-class="getSortClass('full_name')"></i>
                                    <?php echo __('Full name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Users.email')">
                                    <i class="fa" ng-class="getSortClass('Users.email')"></i>
                                    <?php echo __('Email'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Users.phone')">
                                    <i class="fa" ng-class="getSortClass('Users.phone')"></i>
                                    <?php echo __('Phone'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Users.company')">
                                    <i class="fa" ng-class="getSortClass('Users.company')"></i>
                                    <?php echo __('Company'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Users.is_active')">
                                    <i class="fa" ng-class="getSortClass('Users.is_active')"></i>
                                    <?php echo __('Is active'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Type'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Users.usergroup_id')">
                                    <i class="fa" ng-class="getSortClass('Users.usergroup_id')"></i>
                                    <?php echo __('User role'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Users.last_login')">
                                    <i class="fa" ng-class="getSortClass('Users.last_login')"></i>
                                    <?php echo __('Last Login'); ?>
                                </th>
                                <th class="no-sort text-center">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="user in users">
                                <td class="text-center" class="width-15">
                                    <input type="checkbox"
                                           ng-model="massChange[user.id]"
                                           ng-show="user.allow_edit && (user.id != myUserId)">
                                </td>

                                <td>{{user.full_name}}</td>
                                <td>{{user.email}}</td>
                                <td>{{user.phone}}</td>
                                <td>{{user.company}}</td>
                                <td>
                                    <span class="badge border border-danger text-danger"
                                          ng-hide="user.is_active">
                                        <?php echo __('Disabled'); ?>
                                    </span>
                                    <span class="badge border border-success text-success"
                                          ng-show="user.is_active">
                                        <?php echo __('Active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span
                                        ng-repeat="UserType in user.UserTypes"
                                        class="badge border margin-right-10 {{UserType.class}} {{UserType.color}}">
                                        {{UserType.title}}
                                    </span>
                                </td>
                                <td>{{user.usergroup.name}}</td>
                                <td>{{user.last_login}}</td>

                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'users')): ?>
                                            <a ui-sref="UsersEdit({id: user.id})"
                                               ng-if="user.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!user.allow_edit"
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
                                            <?php if ($this->Acl->hasPermission('edit', 'users')): ?>
                                                <a ui-sref="UsersEdit({id: user.id})"
                                                   ng-if="user.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('edit', 'users')): ?>
                                                <a ng-if="!user.samaccountname && user.allow_edit"
                                                   class="dropdown-item"
                                                   href="javascript:void(0);"
                                                   ng-click="resetPasswordModal(user)">
                                                    <i class="fa fa-key"></i>
                                                    <?php echo __('Reset password'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'users')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(user))"
                                                   ng-if="user.allow_edit && (user.id != myUserId)"
                                                   href="javascript:void(0);"
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
                        <div class="margin-top-10" ng-show="users.length == 0">
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
                            <?php if ($this->Acl->hasPermission('delete', 'users')): ?>
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


<!-- reset password modal -->
<div id="angularResetUserPasswordModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Reset user password'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <?php echo __('Do you really want to reset the password for the selected user?'); ?>
                    </div>
                    <div class="col-lg-12">
                        <ul>
                            <li>
                                {{resetPasswordUser.full_name}} ({{resetPasswordUser.email}})
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-12 padding-top-10 text-info">
                        <i class="fa fa-info-circle"></i>
                        <?php echo __('The system will send the user an email with a new random generated password.'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" ng-click="resetPassword()">
                    <?php echo __('Yes - reset password'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
