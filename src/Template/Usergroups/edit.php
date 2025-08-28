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
        <a ui-sref="UsergroupsIndex">
            <i class="fa fa-users"></i> <?php echo __('User roles'); ?>
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
                    <?php echo __('Edit user role:'); ?>
                    <span class="fw-300"><i>
                            {{post.Usergroup.name}}
                        </i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'usergroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='UsergroupsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('User role'); ?>' , message: '<?php echo __('saved successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-disabled="post.Usergroup.name === 'Administrator'"
                                ng-model="post.Usergroup.name">
                            <div ng-repeat="error in errors.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.description}">
                            <label class="control-label">
                                <?php echo __('Description'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Usergroup.description">
                            <div ng-repeat="error in errors.description">
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
                                ng-model="post.Usergroup.ldapgroups._ids">
                            </select>
                            <div ng-repeat="error in errors.ldapgroups">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?= __('LDAP users which are member of the selected LDAP groups will be assigned automatically to the user container role.'); ?>
                            </div>
                        </div>

                        <hr>

                        <div class="row" ng-show="post.Usergroup.name === 'Administrator'">
                            <div class="col-lg-12">
                                <div class="alert alert-info alert-block">
                                    <h4 class="alert-heading"><?php echo __('Notice!'); ?></h4>
                                    <?= __('Permissions of the user role <strong>Administrator</strong> will be set back to default on every update of {0}!', $systemname); ?>
                                </div>
                            </div>
                        </div>

                        <div class="card margin-top-10 margin-bottom-25">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm"
                                                       placeholder="<?= __('Filter by controller') ?>"
                                                       ng-model="ctrlFilter"
                                                       ng-model-options="{debounce: 150}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="btn-group float-right">
                                            <button type="button" class="btn btn-default dropdown-toggle"
                                                    data-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                <?php echo __('Bulk actions'); ?>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="javascript:void(0);"
                                                   ng-click="tickAll('all')"
                                                   class="dropdown-item">
                                                    <i class="fa fa-check-square text-primary"></i> <?php echo __('Tick all'); ?>
                                                </a>
                                                <a href="javascript:void(0);"
                                                   ng-click="untickAll('all')"
                                                   class="dropdown-item">
                                                    <i class="far fa-square"></i> <?php echo __('Untick all'); ?>
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-click="tickAll('index')"
                                                   class="dropdown-item">
                                                    <i class="fa fa-check-square txt-ack"></i> <?php echo __('Tick all: index'); ?>
                                                </a>
                                                <a href="javascript:void(0);"
                                                   ng-click="untickAll('index')"
                                                   class="dropdown-item">
                                                    <i class="far fa-square"></i> <?php echo __('Untick all: index'); ?>
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-click="tickAll('add')"
                                                   class="dropdown-item">
                                                    <i class="fa fa-check-square ok"></i> <?php echo __('Tick all: add'); ?>
                                                </a>
                                                <a href="javascript:void(0);"
                                                   ng-click="untickAll('add')"
                                                   class="dropdown-item">
                                                    <i class="far fa-square"></i> <?php echo __('Untick all: add'); ?>
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-click="tickAll('edit')"
                                                   class="dropdown-item">
                                                    <i class="fa fa-check-square warning"></i> <?php echo __('Tick all: edit'); ?>
                                                </a>
                                                <a href="javascript:void(0);"
                                                   ng-click="untickAll('edit')"
                                                   class="dropdown-item">
                                                    <i class="far fa-square"></i> <?php echo __('Untick all: edit'); ?>
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-click="tickAll('delete')"
                                                   class="dropdown-item">
                                                    <i class="fa fa-check-square down"></i> <?php echo __('Tick all: delete'); ?>
                                                </a>
                                                <a href="javascript:void(0);"
                                                   ng-click="untickAll('delete')"
                                                   class="dropdown-item">
                                                    <i class="far fa-square"></i> <?php echo __('Untick all: delete'); ?>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="float-right">
                                            <button class="btn btn-primary"
                                                    type="submit"><?php echo __('Update user role'); ?></button>
                                            <a back-button href="javascript:void(0);" fallback-state='UsergroupsIndex'
                                               class="btn btn-default margin-right-5"><?php echo __('Cancel'); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row margin-left-10" ng-repeat="aco in acos">
                            <div class="col-12">
                                <div class="row padding-bottom-15" ng-repeat="controller in aco.children"
                                     ng-if="controller.children.length > 0 && controller.alias.substr(-6) !== 'Module'"
                                     ng-show="ctrlFilter === '' || controller.alias.toLowerCase().includes(ctrlFilter)">
                                    <div class="col-xs-12">
                                        <h5 ng-class="{'ok': controller.alias.substr(-6) == 'Module'}">
                                            {{controller.alias}}
                                        </h5>
                                    </div>

                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-xs-12 col-md-2 col-lg-4"
                                                 ng-repeat="action in controller.children">
                                                <div
                                                    class="custom-control custom-checkbox custom-control-left margin-right-10"
                                                    ng-class="{'txt-ack': action.alias == 'index', 'ok': action.alias == 'add', 'warning': action.alias == 'edit', 'down': action.alias == 'delete'}">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           ng-true-value="1"
                                                           ng-false-value="0"
                                                           id="{{controller.alias + action.alias + post.Acos[action.id]}}"
                                                           ng-model="post.Acos[action.id]">
                                                    <label class="custom-control-label"
                                                           for="{{controller.alias + action.alias + post.Acos[action.id]}}">
                                                        {{action.alias}}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row padding-bottom-15 border-top-1" ng-repeat="plugin in aco.children"
                                     ng-if="plugin.children.length > 0 && plugin.alias.substr(-6) === 'Module'">
                                    <div class="col-lg-12">
                                        <h5 class="ok">
                                            <i class="fa fa-puzzle-piece"></i>
                                            {{plugin.alias}}
                                        </h5>
                                    </div>

                                    <div class="col-xs-12 col-md-4 col-lg-2" ng-repeat="controller in plugin.children">
                                        <h5>
                                            {{controller.alias}}
                                        </h5>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="col-xs-12 col-md-2 col-lg-4"
                                                     ng-repeat="action in controller.children">
                                                    <div
                                                        class="custom-control custom-checkbox custom-control-left margin-right-10"
                                                        ng-class="{'txt-ack': action.alias == 'index', 'ok': action.alias == 'add', 'warning': action.alias == 'edit', 'down': action.alias == 'delete'}">
                                                        <input type="checkbox"
                                                               class="custom-control-input"
                                                               ng-true-value="1"
                                                               ng-false-value="0"
                                                               id="{{controller.alias + action.alias + post.Acos[action.id]}}"
                                                               ng-model="post.Acos[action.id]">
                                                        <label class="custom-control-label"
                                                               for="{{controller.alias + action.alias + post.Acos[action.id]}}">
                                                            {{action.alias}}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Update user role'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='UsergroupsIndex'
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
