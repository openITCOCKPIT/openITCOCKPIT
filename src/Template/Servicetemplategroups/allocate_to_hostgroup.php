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
        <a ui-sref="ServicetemplategroupsIndex">
            <i class="fa-solid fa-pen-to-square"></i> <?php echo __('Service template group'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Allocate service template group to host group'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Allocate service template group'); ?>
                    <span class="fw-300"><i><?php echo __('to host group'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'servicetemplategroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicetemplategroupsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">


                    <div class="form-group required">
                        <label class="control-label" for="ContainersSelect">
                            <?php echo __('Service template group'); ?>
                        </label>
                        <select
                            id="ContainersSelect"
                            data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="servicetemplategroups"
                            callback="loadServicetemplategroups"
                            ng-options="servicetemplategroup.key as servicetemplategroup.value for servicetemplategroup in servicetemplategroups"
                            ng-model="id">
                        </select>
                        <div ng-show="id < 1" class="warning-glow">
                            <?php echo __('Please select a service template group.'); ?>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="control-label" for="HostgroupSelect">
                            <?php echo __('Host group'); ?>
                        </label>
                        <select
                            id="HostgroupSelect"
                            data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="hostgroups"
                            callback="loadHostgroups"
                            ng-options="hostgroup.key as hostgroup.value for hostgroup in hostgroups"
                            ng-model="hostgroupId">
                        </select>
                        <div ng-show="hostgroupId < 1" class="warning-glow">
                            <?php echo __('Please select a host group.'); ?>
                        </div>
                    </div>

                    <div class="row" ng-show="hostgroupId > 0">
                        <div class="col-12">
                            <div
                                class="alert border-faded bg-transparent text-secondary margin-top-20 margin-bottom-10">
                                <div class="d-flex align-items-center">
                                    <div class="alert-icon">
                                        <span class="icon-stack icon-stack-md">
                                            <i class="base-7 icon-stack-3x color-info-600"></i>
                                            <i class="fas fa-info icon-stack-1x text-white"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <span class="h5 color-info-600">
                                            <?= __('Please notice'); ?>
                                        </span>
                                        <br>
                                        <?= __('Services which use a service template that could not be assigned to the selected host due to container permissions, will be removed automatically.'); ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row form-horizontal" ng-show="hostgroupId > 0">
                        <div class="col-xs-12 col-md-9 col-lg-7 padding-top-15"
                             ng-repeat="hostWithServicesToDeploy in hostsWithServicesToDeploy track by $index">
                            <fieldset>
                                <legend class="margin-0 padding-top-10">
                                    <h4>
                                        <input type="checkbox"
                                               ng-model="hostWithServicesToDeploy.areAllCreateServiceOnTargetHostTrue"
                                               ng-click="handleHostSelect($index, hostWithServicesToDeploy.areAllCreateServiceOnTargetHostTrue,hostWithServicesToDeploy.services)">
                                        <?= __('Service/s to deploy on host:'); ?>
                                        <span class="text-info">
                                            {{hostWithServicesToDeploy.host.hostname}}
                                            ({{hostWithServicesToDeploy.host.address}})
                                        </span>
                                    </h4>

                                </legend>

                                <div ng-repeat="serviceToDeploy in hostWithServicesToDeploy.services"
                                     class="padding-bottom-5 padding-left-10">
                                    <input type="checkbox" ng-model="serviceToDeploy.createServiceOnTargetHost">

                                    {{serviceToDeploy.servicetemplate.name}}
                                    <span class="text-info"
                                          ng-show="serviceToDeploy.servicetemplate.description">
                                        ({{serviceToDeploy.servicetemplate.description}})
                                    </span>

                                    <span class="text-info"
                                          ng-show="serviceToDeploy.doesServicetemplateExistsOnTargetHost"
                                          data-original-title="<?php echo __('Service already exist on selected host. Tick the box to create a duplicate.'); ?>"
                                          data-placement="right"
                                          rel="tooltip"
                                          data-container="body">
                                        <i class="fa fa-info-circle"></i>
                                    </span>

                                    <span
                                        ng-show="serviceToDeploy.doesServicetemplateExistsOnTargetHostAndIsDisabled"
                                        data-original-title="<?php echo __('Service already exist on selected host but is disabled. Tick the box to create a duplicate.'); ?>"
                                        data-placement="right"
                                        rel="tooltip"
                                        data-container="body">
                                        <i class="fa fa-plug"></i>
                                    </span>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row padding-left-10 padding-top-10" ng-show="hostgroupId > 0">
                        <div class="col-xs-6 col-md-2">
                            <span ng-click="selectAll()" class="pointer">
                                <i class="fa fa-check-square"></i>
                                <?php echo __('Select all'); ?>
                            </span>
                        </div>
                        <div class="col-xs-6 col-md-2">
                            <span ng-click="undoSelection()" class="pointer">
                                <i class="fa fa-square"></i>
                                <?php echo __('Undo selection'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="row padding-top-15"><!-- spacer--></div>

                    <div class="well" style="margin-bottom: 20px;" ng-show="isProcessing">
                        <div class="progress progress-striped active" style=" margin-bottom: 0;">
                            <div class="progress-bar bg-primary" style="width: {{percentage}}%;"></div>
                        </div>
                    </div>

                    <div class="card margin-top-10">
                        <div class="card-body">
                            <div class="float-right">
                                <button class="btn btn-primary" ng-click="submit()">
                                    <?php echo __('Allocate to host group'); ?>
                                </button>
                                <?php if ($this->Acl->hasPermission('index', 'servicetemplategroups')): ?>
                                    <a back-button href="javascript:void(0);"
                                       fallback-state='ServicetemplategroupsIndex' class="btn btn-default">
                                        <?php echo __('Cancel'); ?>
                                    </a>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
