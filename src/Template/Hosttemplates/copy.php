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
        <a ui-sref="HosttemplatesIndex">
            <i class="fa-solid fa-pen-to-square"></i> <?php echo __('Host templates'); ?>
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
                    <?php echo __('Host templates'); ?>
                    <span class="fw-300"><i><?php echo __('Copy host template/s'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hosttemplates')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HosttemplatesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="card margin-bottom-10" ng-repeat="sourceHosttemplate in sourceHosttemplates">
                        <div class="card-header">
                            <i class="fa fa-cog"></i>
                            <?php echo __('Source host template:'); ?>
                            {{sourceHosttemplate.Source.name}}

                        </div>
                        <div class="card-body">
                            <div class="form-group required" ng-class="{'has-error': sourceHosttemplate.Error.name}">
                                <label for="Hosttemplate{{$index}}Name" class="control-label required">
                                    <?php echo __('Host template name'); ?>
                                </label>
                                <input
                                        class="form-control"
                                        type="text"
                                        ng-model="sourceHosttemplate.Hosttemplate.name"
                                        id="Hosttemplate{{$index}}Name">
                                <span class="help-block">
                                <?php echo __('Name of the new host template'); ?>
                                </span>
                                <div ng-repeat="error in sourceHosttemplate.Error.name">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required"
                                 ng-class="{'has-error': sourceHosttemplate.Error.description}">
                                <label for="Hosttemplate{{$index}}Description" class="control-label required">
                                    <?php echo __('Description'); ?>
                                </label>
                                <input
                                        class="form-control"
                                        type="text"
                                        ng-model="sourceHosttemplate.Hosttemplate.description"
                                        id="Hosttemplate{{$index}}Description">
                                <div ng-repeat="error in sourceHosttemplate.Error.description">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required"
                                 ng-class="{'has-error': sourceHosttemplate.Error.command_id}">
                                <label class="control-label" for="Hosttemplate{{$index}}CommandId">
                                    <?php echo __('Check command'); ?>
                                </label>
                                <select
                                        id="Hosttemplate{{$index}}CommandId"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="commands"
                                        ng-options="command.key as command.value for command in commands"
                                        ng-change="loadCommandArguments(sourceHosttemplate.Source.id, sourceHosttemplate.Hosttemplate.command_id, $index)"
                                        ng-model="sourceHosttemplate.Hosttemplate.command_id">
                                </select>
                                <div ng-repeat="error in sourceHosttemplate.Error.command_id">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>


                            <div class="form-group required"
                                 ng-class="{'has-error': sourceHosttemplate.Error.hosttemplatecommandargumentvalues}"
                                 ng-repeat="hosttemplatecommandargumentvalue in sourceHosttemplate.Hosttemplate.hosttemplatecommandargumentvalues">
                                <label
                                        for="Hosttemplate{{hosttemplatecommandargumentvalue.commandargument.human_name}}Arg"
                                        class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                    {{hosttemplatecommandargumentvalue.commandargument.human_name}}
                                </label>
                                <div class="col-xs-12 col-lg-8">
                                    <input
                                            class="form-control"
                                            type="text"
                                            ng-model="hosttemplatecommandargumentvalue.value"
                                            id="Hosttemplate{{hosttemplatecommandargumentvalue.commandargument.human_name}}Arg">
                                    <span class="help-block">
                                    {{hosttemplatecommandargumentvalue.commandargument.name}}
                                </span>
                                    <div ng-repeat="error in sourceHosttemplate.Error.hosttemplatecommandargumentvalues">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group"
                                 ng-show="sourceHosttemplate.Hosttemplate.hosttemplatecommandargumentvalues.length == 0">
                                <div class="col-lg-12 col-lg-offset-2 text-info">
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
                                    <?php echo __('Copy host templates'); ?>
                                </button>
                                <?php if ($this->Acl->hasPermission('index', 'hosttemplates')): ?>
                                    <a back-button href="javascript:void(0);" fallback-state='HosttemplatesIndex'
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
