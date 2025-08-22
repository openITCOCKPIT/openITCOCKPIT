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
        <i class="fa fa-copy"></i> <?php echo __('Copy'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Hosts'); ?>
                    <span class="fw-300"><i><?php echo __('Copy host/s'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hosts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="card margin-bottom-10" ng-repeat="sourceHost in sourceHosts">
                        <div class="card-header">
                            <i class="fa fa-desktop"></i>
                            <?php echo __('Source host:'); ?>
                            {{sourceHost.Source.name}}
                            <span class="italic">({{sourceHost.Source.address}})</span>

                        </div>
                        <div class="card-body">
                            <div class="form-group required" ng-class="{'has-error': sourceHost.Host.name}">
                                <label for="Host{{$index}}Name" class="control-label required">
                                    <?php echo __('Host name'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="sourceHost.Host.name"
                                    ng-blur="checkForDuplicateHostname(sourceHost.Host.name, $index);"
                                id="Host{{$index}}Name">
                                <span class="help-block">
                                <?php echo __('Name of the new host'); ?>
                                </span>
                                <div ng-repeat="error in sourceHost.Error.name">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="alert alert-warning" role="alert" ng-show="isHostnameInUse[$index]">
                                <div class="d-flex align-items-center">
                                    <div class="alert-icon width-3">
                                        <div class='icon-stack  icon-stack-sm'>
                                            <i class="base base-9 icon-stack-3x opacity-100 color-warning-600"></i>
                                            <i class="fas fa-exclamation-circle icon-stack-1x opacity-100 color-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <?= __('A host with the name <strong>{0}</strong> already exists. Duplicate host names could lead to confusion.', '{{ checkedName[$index] }}'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': sourceHost.Host.description}">
                                <label for="Host{{$index}}Description" class="control-label required">
                                    <?php echo __('Description'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="sourceHost.Host.description"
                                    id="Host{{$index}}Description">
                                <div ng-repeat="error in sourceHost.Error.description">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': sourceHost.Host.address}">
                                <label for="Host{{$index}}Address" class="control-label required">
                                    <?php echo __('Address'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="sourceHost.Host.address"
                                    id="Host{{$index}}Address">
                                <span class="help-block">
                                <?php echo __('Address of the new host'); ?>
                                </span>
                                <div ng-repeat="error in sourceHost.Error.address">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': sourceHost.Host.host_url}">
                                <label for="Host{{$index}}Url" class="control-label required">
                                    <?php echo __('Host URL'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="sourceHost.Host.host_url"
                                    id="Host{{$index}}Url">
                                <div ng-repeat="error in sourceHost.Error.host_url">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card margin-top-10">
                        <div class="card-body">
                            <div class="float-right">
                                <button class="btn btn-primary" ng-click="copy()">
                                    <?php echo __('Copy'); ?>
                                </button>
                                <?php if ($this->Acl->hasPermission('index', 'Hosts')): ?>
                                    <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
