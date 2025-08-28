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
use itnovum\openITCOCKPIT\Agent\AgentHttpClientErrors; ?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="AgentconnectorsWizard">
            <i class="fa fa-user-secret"></i> <?php echo __('openITCOCKPIT Agent'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Wizard'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?= __('openITCOCKPIT Agent Configuration for:'); ?>
                    <span class="fw-300">
                        <i>
                            {{host.name}} ({{host.address}})
                        </i>
                    </span>
                </h2>
            </div>

            <!-- Wizard progressbar -->
            <div class="row margin-0 text-center">
                <div class="col-xs-12 col-md-4 col-lg-2 bg-success text-white">
                    <i class="fas fa-check"></i>
                    <?= __('Select host') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-success text-white">
                    <i class="fas fa-check"></i>
                    <?= __('Configure Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-success text-white">
                    <i class="fas fa-check"></i>
                    <?= __('Install Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 text-white bg-primary">
                    <?= __('Select Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2  bg-light-gray">
                    <?= __('Create services') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 padding-left-0 padding-right-0 bg-light-gray">
                    <div class="btn-group btn-group-xs w-100">
                        <a type="button" class="btn btn-xs btn-primary waves-effect waves-themed"
                           ui-sref="AgentconnectorsInstall({'hostId': hostId})"
                           title="<?= __('Back') ?>"
                           style="border-radius: 0; height: 22px;">
                            <i class="fa fa-arrow-left"></i>
                        </a>

                        <button type="button" class="btn btn-xs btn-success btn-block waves-effect waves-themed"
                                style="border-radius: 0;height: 22px;"
                                ng-disabled="selectedPushAgentId == 0"
                                ng-click="submit()">
                            <?= __('Next') ?>
                            <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- End progressbar -->

            <div class="row">
                <div class="col-12">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card margin-top-20 padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                <i class="fas fa-user-secret"></i>
                                                <?= __('Select Agent to create host assignment'); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <div class="row">
                                                <div class="col-12" ng-show="isLoading">
                                                    <div
                                                        class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                        <div class="d-flex align-items-center">
                                                            <div class="alert-icon">
                                                                <span class="icon-stack icon-stack-md">
                                                                    <i class="base-7 icon-stack-3x color-info-600"></i>
                                                                    <i class="fas fa-hourglass-start icon-stack-1x text-white"></i>
                                                                </span>
                                                            </div>
                                                            <div class="flex-1">
                                                                <span class="h5 color-info-600">
                                                                    <?= __('Waiting for Agent data.'); ?>
                                                                </span>
                                                                <div class="progress mt-1 progress-xs">
                                                                    <div
                                                                        class="progress-bar progress-bar-striped progress-bar-animated bg-info-600"
                                                                        role="progressbar" style="width: 100%"
                                                                        aria-valuenow="100"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row" ng-hide="isLoading">
                                                <div class="col-12">
                                                    <div class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                        <div class="d-flex align-items-center">
                                                            <div class="alert-icon">
                                                                <span class="icon-stack icon-stack-md">
                                                                    <i class="base-7 icon-stack-3x color-info-600"></i>
                                                                    <i class="fas fa-search icon-stack-1x text-white"></i>
                                                                </span>
                                                            </div>
                                                            <div class="flex-1">
                                                                <span class="h5 color-info-600">
                                                                    <?= __('Agent not in list?'); ?>
                                                                </span>
                                                                <br>
                                                                <?= __('Most likely the Agent did not send any data to the openITCOCKPIT Server yet. Please check the log file of the Agent and refresh the list.'); ?>
                                                            </div>

                                                            <button class="btn btn-outline-info btn-sm btn-w-m waves-effect waves-themed"
                                                                    type="button"
                                                                    ng-click="load()">
                                                                <i class="fas fa-refresh"></i>
                                                                <?= __('Refresh list'); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="row" ng-hide="isLoading">
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="enable_push_mode">
                                                        <?php echo __('Available Agents'); ?>
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                                id="enable_push_mode"
                                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                                class="form-control"
                                                                chosen="pushAgents"
                                                                ng-options="agent.id as agent.name for agent in pushAgents"
                                                                ng-model="selectedPushAgentId">
                                                        </select>
                                                        <div class="help-block">
                                                            <?= __('Agents with an exact match of the IP-Address will be selected automatically. If none of the IP-Addresses matches openITCOCKPIT will pre-select the best matching hostname.'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
