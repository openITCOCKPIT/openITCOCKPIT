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
        <a ui-sref="HostsIndex">
            <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-sitemap fa-rotate-270"></i> <?php echo __('Shared containers'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Sharing for'); ?>
                    <span class="fw-300"><i>{{host.name}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="text-muted cursor-default d-none d-sm-none d-md-none d-lg-block margin-right-10">
                        UUID: {{host.uuid}}
                    </div>
                    <?php if ($this->Acl->hasPermission('index', 'hosts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal" ng-init="successMessage=
                        {objectName : '<?php echo __('Host sharing'); ?>' , message: '<?php echo __('edit successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="HostContainer">
                                <?php echo __('Primary container'); ?>
                            </label>
                            <select
                                id="HostContainer"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                disabled="disabled"
                                chosen="containers"
                                ng-options="container.key as container.value for container in primaryContainerPathSelect"
                                ng-model="host.container_id">
                            </select>
                            <div class="text-info">
                                <i class="fa fa-info-circle"></i>
                                <?php echo __('Due to dependencies it is not possible to change the primary container in this view.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.hosts_to_containers_sharing}">
                            <label class="control-label" for="HostSharedContainer">
                                <?php echo __('Shared containers'); ?>
                            </label>
                            <select
                                id="HostSharedContainer"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="sharingContainers"
                                multiple
                                ng-options="container.key as container.value for container in sharingContainers"
                                ng-model="post.Host.hosts_to_containers_sharing._ids">
                            </select>
                            <div class="text-info">
                                <div ng-repeat="error in errors.hosts_to_containers_sharing">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                            <div class="card margin-top-10">
                                <div class="card-body">
                                    <div class="float-right">
                                        <button class="btn btn-primary" type="submit">
                                            <?php echo __('Update sharing'); ?>
                                        </button>
                                        <?php if ($this->Acl->hasPermission('index', 'Hosts')): ?>
                                            <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                                               class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
