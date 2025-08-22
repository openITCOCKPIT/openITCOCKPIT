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
        <a ui-sref="HostgroupsIndex">
            <i class="fas fa-server"></i> <?php echo __('Host groups'); ?>
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
                    <?php echo __('Edit host group:'); ?>
                    <span class="fw-300"><i>{{post.Hostgroup.container.name}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hostgroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostgroupsIndex'
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
                        {objectName : '<?php echo __('Host group'); ?>' , message: '<?php echo __('created successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container.parent_id}">
                            <label class="control-label" for="HostgroupParentContainer">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="HostgroupParentContainer"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Hostgroup.container.parent_id">
                            </select>
                            <div ng-repeat="error in errors.container.parent_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div ng-show="post.Hostgroup.container.parent_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error':errors.container.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Hostgroup.container.name">
                            <div ng-repeat="error in errors.container.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error':errors.description}">
                            <label class="control-label">
                                <?php echo __('Description'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Hostgroup.description">
                            <div ng-repeat="error in errors.description">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error':errors.hostgroup_url}">
                            <label class="control-label">
                                <?php echo __('Host group URL'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Hostgroup.hostgroup_url">
                            <div ng-repeat="error in errors.hostgroup_url">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.hosts}">
                            <label class="control-label" for="HostgroupHostSelect">
                                <?php echo __('Hosts'); ?>
                            </label>
                            <select
                                id="HostgroupHostSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                multiple
                                chosen="hosts"
                                callback="loadHosts"
                                ng-options="host.key as host.value for host in hosts"
                                ng-model="post.Hostgroup.hosts._ids">
                            </select>
                            <div ng-repeat="error in errors.hosts">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.hosttemplates}">
                            <label class="control-label" for="HostgroupHosttemplateSelect">
                                <?php echo __('Host templates'); ?>
                            </label>
                            <select
                                id="HostgroupHosttemplateSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                multiple
                                chosen="hosttemplates"
                                callback="loadHosttemplates"
                                ng-options="hosttemplate.key as hosttemplate.value for hosttemplate in hosttemplates"
                                ng-model="post.Hostgroup.hosttemplates._ids">
                            </select>
                            <div ng-repeat="error in errors.hosttemplates">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Update host group'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='HostgroupsIndex' class="btn btn-default">
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
