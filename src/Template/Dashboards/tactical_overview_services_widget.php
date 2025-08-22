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
$hasServiceIndexPermissions = $this->Acl->hasPermission('index', 'services', '');
?>
<div>
    <flippy vertical
            class="col-lg-12"
            flip="['custom:FLIP_EVENT_OUT']"
            flip-back="['custom:FLIP_EVENT_IN']"
            duration="800"
            timing-function="ease-in-out">

        <flippy-front class="fixFlippy">
            <div class="row">
                <div class="col-lg-1">
                    <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark"
                       ng-click="showConfig()" ng-hide="readOnly">
                        <i class="fa fa-cog fa-sm"></i>
                    </a>
                </div>
            </div>
            <div class="container-fluid padding-top-10 text-center" ng-if="servicestatusSummary">
                <div class="d-flex flex-row justify-content-end">
                    <span class="padding-5 font-md">
                        <?= __('Total services'); ?>: {{servicestatusSummary.total}}
                    </span>
                </div>
                <div class="d-flex flex-row">
                    <div class="p-1 bg-color-grayDark text-white tactical-overview-first-flex-item">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="bg-up text-white tactical-overview-flex-item padding-top-50 padding-bottom-50">
                        {{servicestatusSummary.state[0]}}
                    </div>
                    <div class="bg-warning text-white tactical-overview-flex-item">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="text-white" ng-if="servicestatusSummary.state[1] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.state.serviceIds[1]})">
                                {{servicestatusSummary.state[1]}}
                            </a>
                            <span ng-if="servicestatusSummary.state[1] === 0">
                            {{servicestatusSummary.state[1]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.state[1]}}
                        <?php endif; ?>
                    </div>
                    <div class="bg-down text-white tactical-overview-flex-item">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="text-white" ng-if="servicestatusSummary.state[2] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.state.serviceIds[2]})">
                                {{servicestatusSummary.state[2]}}
                            </a>
                            <span ng-if="servicestatusSummary.state[2] === 0">
                            {{servicestatusSummary.state[2]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.state[2]}}
                        <?php endif; ?>
                    </div>
                    <div class="bg-unreachable text-white tactical-overview-flex-item">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="text-white" ng-if="servicestatusSummary.state[3] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.state.serviceIds[3]})">
                                {{servicestatusSummary.state[3]}}
                            </a>
                            <span ng-if="servicestatusSummary.state[3] === 0">
                            {{servicestatusSummary.state[3]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.state[3]}}
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-row">
                    <div class="p-1 bg-color-grayDark tactical-overview-first-flex-item text-white">
                        <i class="fas fa-exclamation-triangle warning"></i>
                    </div>
                    <div class="bg-color-grayDark tactical-overview-flex-item font-md text-white text-left">
                        <?= __('Unhandled Services'); ?>
                    </div>
                    <div class="bg-warning-soft tactical-overview-flex-item font-xl text-white">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="text-white" ng-if="servicestatusSummary.not_handled[1] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.not_handled.serviceIds[1]})">
                                {{servicestatusSummary.not_handled[1]}}
                            </a>
                            <span ng-if="servicestatusSummary.not_handled[1] === 0">
                            {{servicestatusSummary.not_handled[1]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.not_handled[1]}}
                        <?php endif; ?>
                    </div>
                    <div class="bg-down-soft tactical-overview-flex-item font-xl text-white">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="text-white" ng-if="servicestatusSummary.not_handled[2] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.not_handled.serviceIds[2]})">
                                {{servicestatusSummary.not_handled[2]}}
                            </a>
                            <span ng-if="servicestatusSummary.not_handled[2] === 0">
                            {{servicestatusSummary.not_handled[2]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.not_handled[2]}}
                        <?php endif; ?>
                    </div>
                    <div class="bg-unreachable-soft tactical-overview-flex-item font-xl text-white">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="text-white" ng-if="servicestatusSummary.not_handled[3] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.not_handled.serviceIds[3]})">
                                {{servicestatusSummary.not_handled[3]}}
                            </a>
                            <span ng-if="servicestatusSummary.not_handled[3] === 0">
                            {{servicestatusSummary.not_handled[3]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.not_handled[3]}}
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-row">
                    <div class="p-1 tactical-overview-first-flex-item">
                        <i class="fa fa-user text-primary" title="<?= __('is acknowledged'); ?>"></i>
                    </div>
                    <div class="tactical-overview-flex-item font-md text-left">
                        <?= __('Acknowledgments'); ?>
                    </div>
                    <div class="warning tactical-overview-flex-item font-xl">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="warning" ng-if="servicestatusSummary.acknowledged[1] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.acknowledged.serviceIds[1]})">
                                {{servicestatusSummary.acknowledged[1]}}
                            </a>
                            <span ng-if="servicestatusSummary.acknowledged[1] === 0">
                            {{servicestatusSummary.acknowledged[1]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.acknowledged[1]}}
                        <?php endif; ?>
                    </div>
                    <div class="down tactical-overview-flex-item font-xl">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="down" ng-if="servicestatusSummary.acknowledged[2] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.acknowledged.serviceIds[2]})">
                                {{servicestatusSummary.acknowledged[2]}}
                            </a>
                            <span ng-if="servicestatusSummary.acknowledged[2] === 0">
                            {{servicestatusSummary.acknowledged[2]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.acknowledged[2]}}
                        <?php endif; ?>
                    </div>
                    <div class="unreachable tactical-overview-flex-item font-xl">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="unreachable" ng-if="servicestatusSummary.acknowledged[3] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.acknowledged.serviceIds[3]})">
                                {{servicestatusSummary.acknowledged[3]}}
                            </a>
                            <span ng-if="servicestatusSummary.acknowledged[3] === 0">
                            {{servicestatusSummary.acknowledged[3]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.acknowledged[3]}}
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex flex-row">
                    <div class="p-1 tactical-overview-first-flex-item">
                        <i class="fa fa-power-off text-primary" title="<?= __('is in downtime'); ?>"></i>
                    </div>
                    <div class="tactical-overview-flex-item font-md text-left">
                        <?= __('Downtimes'); ?>
                    </div>
                    <div class="warning tactical-overview-flex-item font-xl">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="warning" ng-if="servicestatusSummary.in_downtime[1] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.in_downtime.serviceIds[1]})">
                                {{servicestatusSummary.in_downtime[1]}}
                            </a>
                            <span ng-if="servicestatusSummary.in_downtime[1] === 0">
                            {{servicestatusSummary.in_downtime[1]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.in_downtime[1]}}
                        <?php endif; ?>
                    </div>
                    <div class="down tactical-overview-flex-item font-xl">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="down" ng-if="servicestatusSummary.in_downtime[2] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.in_downtime.serviceIds[2]})">
                                {{servicestatusSummary.in_downtime[2]}}
                            </a>
                            <span ng-if="servicestatusSummary.in_downtime[2] === 0">
                            {{servicestatusSummary.in_downtime[2]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.in_downtime[2]}}
                        <?php endif; ?>
                    </div>
                    <div class="unreachable tactical-overview-flex-item font-xl">
                        <?php if ($hasServiceIndexPermissions): ?>
                            <a class="unreachable" ng-if="servicestatusSummary.in_downtime[3] > 0"
                               ui-sref="ServicesIndex({id: servicestatusSummary.in_downtime.serviceIds[3]})">
                                {{servicestatusSummary.in_downtime[3]}}
                            </a>
                            <span ng-if="servicestatusSummary.in_downtime[3] === 0">
                            {{servicestatusSummary.in_downtime[3]}}
                        </span>
                        <?php else: ?>
                            {{servicestatusSummary.in_downtime[3]}}
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </flippy-front>
        <flippy-back class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark margin-bottom-10"
               ng-click="hideConfig()">
                <i class="fa fa-eye fa-sm"></i>
            </a>
            <div class="padding-10" style="border: 1px solid #c3c3c3;">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-xs-12 col-lg-6 margin-bottom-5">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="icon-prepend fa fa-desktop"></i></span>
                                </div>
                                <input type="text" class="form-control"
                                       placeholder="<?php echo __('Filter by host name'); ?>"
                                       ng-model="filter.Host.name"
                                       ng-model-options="{debounce: 500}">
                                <div class="input-group-append">
                                    <span class="input-group-text pt-0 pb-0">
                                       <label>
                                        <?= __('Enable RegEx'); ?>
                                        <input type="checkbox"
                                               ng-model="filter.Host.name_regex">
                                        </label>
                                        <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-lg-6 margin-bottom-5">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="icon-prepend fa fa-cog"></i></span>
                                </div>
                                <input type="text" class="form-control"
                                       placeholder="<?php echo __('Filter by service name'); ?>"
                                       ng-model="filter.Service.servicename"
                                       ng-model-options="{debounce: 500}">
                                <div class="input-group-append">
                                    <span class="input-group-text pt-0 pb-0">
                                        <label>
                                            <?= __('Enable RegEx'); ?>
                                            <input type="checkbox"
                                                   ng-model="filter.Service.servicename_regex">
                                        </label>
                                        <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-lg-6 margin-bottom-5">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                </div>
                                <div class="col tagsinputFilter">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           data-role="tagsinput"
                                           id="ServicesKeywordsInput{{widget.id}}"
                                           placeholder="<?php echo __('Filter by tags'); ?>"
                                           ng-model="filter.Service.keywords"
                                           ng-model-options="{debounce: 500}"
                                           style="display: none;">
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-lg-6 margin-bottom-5">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                </div>
                                <div class="col tagsinputFilter">
                                    <input type="text" class="input-sm"
                                           data-role="tagsinput"
                                           id="ServicesNotKeywordsInput{{widget.id}}"
                                           placeholder="<?php echo __('Filter by excluded tags'); ?>"
                                           ng-model="filter.Service.not_keywords"
                                           ng-model-options="{debounce: 500}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row padding-bottom-5">
                        <div class="col-xs-12 col-lg-12">
                            <fieldset>
                                <h5><?php echo __('Service groups'); ?></h5>
                                <div class="form-group smart-form">
                                    <select
                                        id="Servicegroup"
                                        data-placeholder="<?php echo __('Filter by service groups'); ?>"
                                        class="form-control"
                                        chosen="servicegroups"
                                        callback="loadServicegroups"
                                        multiple
                                        ng-options="servicegroup.key as servicegroup.value for servicegroup in servicegroups"
                                        ng-model="filter.Servicegroup._ids"
                                        ng-model-options="{debounce: 500}">
                                    </select>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <button class="btn btn-primary float-right"
                                ng-click="saveSettings()">
                            <?php echo __('Save'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </flippy-back>
    </flippy>
</div>
