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
        <a ui-sref="ServicesIndex">
            <i class="fa fa-cog"></i> <?php echo __('Services'); ?>
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
                    <?php echo __('Copy service/s'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicesIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="form-group required">
                        <label class="control-label" for="Host">
                            <?php echo __('Host'); ?>
                        </label>
                        <select
                            id="Host"
                            data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="hosts"
                            callback="loadHosts"
                            ng-options="host.key as host.value for host in hosts"
                            ng-model="hostId">
                        </select>
                        <div ng-show="hostId < 1" class="warning-glow">
                            <?php echo __('Please select a container.'); ?>
                        </div>
                    </div>

                    <div class="row form-horizontal" ng-show="hostId > 0">
                        <div class="col-xs-12 col-md-9 col-lg-7">
                            <div class="col col-md-2 control-label">
                                <!-- Fancy layout -->
                            </div>
                            <div class="col col-xs-10">
                                <div class="text-info">
                                    <i class="fa fa-info-circle"></i>
                                    <?php echo __('Please notice:'); ?>
                                    <?php echo __('Services which use a service template that could not be assigned to the selected host due to container permissions, will be removed automatically.'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card margin-bottom-10" ng-repeat="sourceService in sourceServices" ng-show="hostId > 0">
                        <div class="card-header">
                            <i class="fa fa-cog"></i>
                            <?php echo __('Source service:'); ?>
                            {{sourceService.Source.hostname}} / {{sourceService.Source._name}}
                        </div>
                        <div class="card-body">
                            <div class="form-group required" ng-class="{'has-error': sourceService.Service.name}">
                                <label for="Service{{$index}}Name" class="control-label required">
                                    <?php echo __('Service name'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="sourceService.Service.name"
                                    id="Service{{$index}}Name">
                                <span class="help-block">
                                <?php echo __('Name of the new host'); ?>
                                </span>
                                <div ng-repeat="error in sourceService.Error.name">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': sourceService.Service.description}">
                                <label for="Service{{$index}}Description" class="control-label required">
                                    <?php echo __('Description'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="sourceService.Service.description"
                                    id="Service{{$index}}Description">
                                <div ng-repeat="error in sourceService.Error.description">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': sourceService.Error.command_id}">
                                <label class="control-label" for="CheckCommands">
                                    <?php echo __('Check command'); ?>
                                </label>
                                <select
                                    id="CheckCommands"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="commands"
                                    ng-options="command.key as command.value for command in commands"
                                    ng-change="loadCommandArguments(sourceService.Source.id, sourceService.Service.command_id, $index)"
                                    ng-model="sourceService.Service.command_id">
                                </select>
                                <div class="help-block"
                                     ng-hide="sourceService.Service.active_checks_enabled">
                                    <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                </div>
                                <div ng-repeat="error in sourceService.Error.command_id">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group"
                                 ng-class="{'has-error': sourceService.Error.servicecommandargumentvalues}"
                                 ng-repeat="servicecommandargumentvalue in sourceService.Service.servicecommandargumentvalues">
                                <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                    {{servicecommandargumentvalue.commandargument.human_name}}
                                </label>
                                <div class="col-xs-12 col-lg-8">
                                    <input
                                        class="form-control"
                                        type="text"
                                        ng-model="servicecommandargumentvalue.value">
                                    <div ng-repeat="error in sourceService.Error.servicecommandargumentvalues">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                    <div class="help-block">
                                        {{servicecommandargumentvalue.commandargument.name}}
                                    </div>
                                </div>
                            </div>
                            <div class="form-group"
                                 ng-show="sourceService.Service.servicecommandargumentvalues.length == 0">
                                <div class="col-xs-12 col-lg-offset-2 text-info">
                                    <i class="fa fa-info-circle"></i>
                                    <?php echo __('This command does not have any parameters.'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card margin-top-10">
                        <div class="card-body">
                            <div class="float-right">
                                <button class="btn btn-primary" ng-click="copy()">
                                    <?php echo __('Copy services'); ?>
                                </button>
                                <?php if ($this->Acl->hasPermission('index', 'Services')): ?>
                                    <a back-button href="javascript:void(0);" fallback-state='ServicesIndex'
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
