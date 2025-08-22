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

/**
 * @var \App\View\AppView $this
 */

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="StatuspagesIndex">
            <i class="fas fa-info-circle"></i> <?php echo __('Status pages'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('View'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Status page:'); ?>
                    <span class="fw-300"><i>{{ Statuspage.statuspage.name }}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-default btn-xs mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>

                    <a ng-href="/statuspages/publicView/{{id}}" target="_blank"
                       class="btn btn-xs btn-primary mr-1 shadow-0" ng-show="Statuspage.statuspage.public">
                        <i class="fas fa-eye"></i>
                        <?php echo __('Public View'); ?>
                    </a>

                    <?php if ($this->Acl->hasPermission('index', 'statuspages')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='StatuspagesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statuspage over all status -->
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="no-padding">

                        <div class="col-12 pt-2 pb-4">
                            <h4 class="d-block l-h-n m-0 fw-500">
                                {{Statuspage.statuspage.name}}
                                <small class="m-0 l-h-n">
                                    {{Statuspage.statuspage.description}}
                                </small>
                            </h4>
                        </div>


                        <div
                            class="p-3 bg-{{Statuspage.statuspage.cumulatedColor}} rounded overflow-hidden position-relative text-white">
                            <div>
                                <h2 class="d-block l-h-n m-0 fw-500">
                                    {{Statuspage.statuspage.cumulatedHumanStatus}}
                                </h2>
                            </div>
                            <i class="{{Statuspage.statuspage.cumulatedIcon}} statuspage-icon position-absolute pos-right pos-bottom opacity-15 pr-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end overall status -->

            <div class="panel-container show">
                <div class="panel-content">
                    <div class="no-padding margin-bottom-10" ng-repeat="item in Statuspage.items">
                        <!-- Status page object card -->
                        <div class="card d-flex flex-row min-h-110 margin-bottom-10">
                            <div class="p-2">
                                <div
                                    class="h-100 status-line bg-{{item.cumulatedColor}} shadow-{{item.cumulatedColor}}"></div>
                            </div>

                            <div class="flex-1">
                                <div class="row p-2">

                                    <div class="col-12 text-truncate h4" ng-if="item.type === 'host'">
                                        <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                            <a ui-sref="HostsBrowser({id:item.id})">
                                                {{ item.name }}
                                            </a>
                                        <?php else: ?>
                                            {{ item.name }}
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 text-truncate h4" ng-if="item.type === 'service'">
                                        <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                            <a ui-sref="ServicesBrowser({id:item.id})">
                                                {{ item.name }}
                                            </a>
                                        <?php else: ?>
                                            {{ item.name }}
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 text-truncate h4" ng-if="item.type === 'hostgroup'">
                                        <?php if ($this->Acl->hasPermission('extended', 'hostgroups')): ?>
                                            <a ui-sref="HostgroupsExtended({id: item.id})">
                                                {{ item.name }}
                                            </a>
                                        <?php else: ?>
                                            {{ item.name }}
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 text-truncate h4" ng-if="item.type === 'servicegroup'">
                                        <?php if ($this->Acl->hasPermission('extended', 'servicegroups')): ?>
                                            <a ui-sref="ServicegroupsExtended({id: item.id})">
                                                {{ item.name }}
                                            </a>
                                        <?php else: ?>
                                            {{ item.name }}
                                        <?php endif; ?>
                                    </div>

                                    <!-- Handle status name -->
                                    <div class="col-12">
                                        <h4 class="{{item.cumulatedColor}}">{{item.cumulatedStateName}}</h4>
                                    </div>
                                    <!-- end of status name -->

                                    <!-- Handle acknowledgement comments -->
                                    <div class="col-12 text-truncate">
                                        <div class="row">
                                            <div class="col-12 text-truncate">
                                                <div ng-if="item.acknowledgedProblemsText && item.cumulatedColorId > 0 && Statuspage.statuspage.showAcknowledgements">
                                                    <i class="far fa-user"></i>
                                                    {{item.acknowledgedProblemsText}}
                                                </div>
                                                <div ng-if="item.acknowledgeComment">
                                                   <!-- <div ng-if="Statuspage.statuspage.showAcknowledgementComments">-->
                                                        <div ng-repeat="comment in item.acknowledgeComment track by $index">
                                                            <div class="text-truncate">
                                                                <?php echo __('Comment'); ?>:  {{comment}}
                                                            </div>
                                                        </div>
                                                   <!-- </div>-->
                                                </div>
                                                <!-- end of acknowledgements -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- handle current downtimes -->
                                    <div class="col-12 text-truncate" ng-if="item.downtimeData.length > 0 && Statuspage.statuspage.showDowntimes">
                                        <div class="row">
                                            <div class="col-12 text-truncate">
                                                <div class="pt-1">
                                                    <i class="fa fa-power-off"></i>
                                                    <?= __('Currently under maintenance.'); ?>
                                                </div>

                                                <div class="row" ng-repeat="downtime in item.downtimeData">
                                                    <div class="col-xs-12 col-md-3">
                                                        <?= __('Start'); ?>: {{downtime.scheduledStartTime}}
                                                    </div>
                                                    <div class="col-xs-12 col-md-3">
                                                        <?= __('End'); ?>: {{downtime.scheduledEndTime}}
                                                    </div>
                                                    <div class="col-xs-12 col-md-3">
                                                        <?= __('Comment'); ?>: {{downtime.comment}}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end of current downtimes -->

                                    <!-- handle planed downtimes -->
                                    <div class="col-12 text-truncate" ng-if="item.plannedDowntimeData.length > 0 && Statuspage.statuspage.showDowntimes">
                                        <div class="row">
                                            <div class="col-12 text-truncate">
                                                <div class="pt-1">
                                                    <i class="fa fa-power-off"></i>
                                                    <?= __('Scheduled maintenance for the next 10 days:'); ?>
                                                </div>

                                                <div class="row" ng-repeat="downtime in item.plannedDowntimeData">
                                                    <div class="col-xs-12 col-md-3">
                                                        <?= __('Start'); ?>: {{downtime.scheduledStartTime}}
                                                    </div>
                                                    <div class="col-xs-12 col-md-3">
                                                        <?= __('End'); ?>: {{downtime.scheduledEndTime}}
                                                    </div>
                                                    <div class="col-xs-12 col-md-3">
                                                        <?= __('Comment'); ?>: {{downtime.comment}}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end of planed downtimes -->

                                </div>
                            </div>

                            <div class="p-2 hidden-md-down">
                                <div
                                    class="h-100 status-line bg-{{item.cumulatedColor}} shadow-{{item.cumulatedColor}}"></div>
                            </div>
                        </div>
                        <!-- end object card -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
