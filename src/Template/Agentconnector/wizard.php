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
?>
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
                    <?= __('openITCOCKPIT Agent'); ?>
                    <span class="fw-300">
                        <i>
                            <?= __('Configuration'); ?>
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('agents', 'agentconnector')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='AgentconnectorsAgent'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Wizard progressbar -->
            <div class="row margin-0 text-center">
                <div class="col-xs-12 col-md-4 col-lg-2 bg-primary text-white">
                    <?= __('Select host') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-light-gray">
                    <?= __('Configure Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-light-gray">
                    <?= __('Install Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-light-gray">
                    <?= __('Exchange TLS Certificate') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2  bg-light-gray">
                    <?= __('Create services') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 padding-left-0 padding-right-0 bg-light-gray">
                    <div class="btn-group btn-group-xs w-100">
                        <button type="button" class="btn btn-xs btn-primary waves-effect waves-themed"
                                disabled="disabled"
                                title="<?= __('Back') ?>"
                                style="border-radius: 0; height: 22px;">
                            <i class="fa fa-arrow-left"></i>
                        </button>

                        <button type="button" class="btn btn-xs btn-success btn-block waves-effect waves-themed"
                                style="border-radius: 0;height: 22px;" disabled="disabled">
                            <?= __('Next') ?>
                            <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- End progressbar -->

            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row">
                        <div class="form-group required col-lg-12">
                            <label class="control-label" for="HostsSelect">
                                <?php echo __('Host'); ?>
                            </label>
                            <select
                                id="HostsSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                chosen="hosts"
                                ng-options="host.key as host.value for host in hosts"
                                callback="load"
                                ng-model="hostId">
                            </select>
                            <div ng-show="hostId < 1" class="warning-glow">
                                <?php echo __('Please select a host.'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row padding-top-20" ng-hide="isConfigured">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 offset-lg-2 offset-xl-2">
                            <div class="panel panel-default agent-mode-box-pull">
                                <div class="panel-hrd padding-left-20 padding-right-20 padding-top-20">
                                    <h4>
                                        <?= __('Register Agent in pull mode'); ?>
                                    </h4>
                                    <hr/>
                                </div>

                                <div class="panel-container padding-left-20 padding-right-20"
                                     style="min-height: 180px;">
                                    <h5><?= __('When to use pull mode?'); ?></h5>
                                    <div class="text">
                                        <ul>
                                            <li><?= __('In pull mode the openITCOCKPIT server will frequently connect to the agent via an HTTP/S connection to get the latest check results.'); ?></li>

                                            <li><?= __('Use the pull mode when your openITCOCKPIT server can establish a direct connection to the target system.'); ?></li>

                                            <li><?= __('If your openITCOCKPIT server is located in the same datacenter.'); ?></li>

                                        </ul>
                                    </div>
                                </div>

                                <div class="panel-footer padding-20">
                                    <div class="col-xs-12 padding-right-0">
                                        <a class="btn btn-outline-primary pull-right"
                                           ng-show="hostId && !pushAgentId"
                                           ui-sref="AgentconnectorsConfig({hostId: hostId, mode: 'pull' })">
                                            <?= __('Continue with pull mode'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4">
                            <div class="panel panel-default agent-mode-box-push">
                                <div class="panel-hrd padding-left-20 padding-right-20 padding-top-20">
                                    <h4>
                                        <?= __('Register Agent in push mode'); ?>
                                    </h4>
                                    <hr/>
                                </div>

                                <div class="panel-container padding-left-20 padding-right-20"
                                     style="min-height: 180px;">
                                    <h5><?= __('When to use push mode?'); ?></h5>
                                    <div class="text">
                                        <ul>
                                            <li><?= __('In push mode the Agent will frequently push the latest check results to the openITCOCKPIT server via an HTTPS connection on port 443.'); ?></li>

                                            <li><?= __('Use the push mode whenever your openITCOCKPIT server cannot establish a direct connection the the target system.'); ?></li>

                                            <li><?= __('In a fast changing environment or when your openITCOCKPIT server is running in the cloud.'); ?></li>

                                        </ul>
                                    </div>
                                </div>

                                <div class="panel-footer padding-20">
                                    <div class="col-xs-12 padding-right-0">
                                        <a class="btn btn-outline-primary pull-right"
                                           ng-show="hostId"
                                           ui-sref="AgentconnectorsConfig({hostId: hostId, mode: 'push', pushAgentId: pushAgentId})">
                                            <?= __('Continue with push mode'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONFIG ALREADY EXISTS  -->

                    <div class="row padding-top-20" ng-show="hostId && isConfigured">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4  offset-lg-2 offset-xl-2">
                            <div class="panel panel-default agent-mode-box-create-services">
                                <div class="panel-hrd padding-left-20 padding-right-20 padding-top-20">
                                    <h4>
                                        <?= __('Create new services'); ?>
                                    </h4>
                                    <hr/>
                                </div>

                                <div class="panel-container padding-left-20 padding-right-20"
                                     style="min-height: 180px;">
                                    <h5><?= __('Agent is already configured'); ?></h5>
                                    <div class="text">
                                        <ul>
                                            <li><?= __('Add new services to your monitoring'); ?></li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="panel-footer padding-20">
                                    <div class="col-xs-12 padding-right-0">
                                        <a
                                            type="button"
                                            class="btn btn-outline-success pull-right waves-effect waves-themed"
                                            ui-sref="AgentconnectorsCreateServices({hostId: hostId, testConnection: 'true'})">
                                            <?= __('Continue with service creation'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4">
                            <div class="panel panel-default agent-mode-box-config-edit">
                                <div class="panel-hrd padding-left-20 padding-right-20 padding-top-20">
                                    <h4>
                                        <?= __('Edit agent configuration'); ?>
                                    </h4>
                                    <hr/>
                                </div>

                                <div class="panel-container padding-left-20 padding-right-20"
                                     style="min-height: 180px;">
                                    <h5><?= __('When is a configuration change required?'); ?></h5>
                                    <div class="text">
                                        <ul>
                                            <li><?= __('To enable new agent checks/features'); ?></li>

                                            <li><?= __('To switch from "Push" to "Pull" mode and vice versa'); ?></li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="panel-footer padding-20">
                                    <div class="col-xs-12 padding-right-0">
                                        <a
                                            type="button"
                                            class="btn btn-outline-primary pull-right waves-effect waves-themed"
                                            ui-sref="AgentconnectorsConfig({hostId: hostId})">
                                            <?= __('Edit agent configuration'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
