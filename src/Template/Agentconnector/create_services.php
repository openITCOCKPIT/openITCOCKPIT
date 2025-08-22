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
                <div class="col-xs-12 col-md-4 col-lg-2 bg-success text-white">
                    <i class="fas fa-check"></i>
                    <span ng-hide="config.bool.enable_push_mode">
                        <?= __('Exchange TLS Certificate') ?>
                    </span>
                    <span ng-show="config.bool.enable_push_mode">
                        <?= __('Select Agent') ?>
                    </span>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 text-white"
                     ng-class="{'bg-success':successful, 'bg-primary':!successful}">
                    <i class="fas fa-check" ng-show="successful"></i>
                    <?= __('Create services') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 padding-left-0 padding-right-0 bg-light-gray">
                    <div class="btn-group btn-group-xs w-100">
                        <a type="button" class="btn btn-xs btn-primary waves-effect waves-themed"
                           ui-sref="AgentconnectorsAutotls({'hostId': hostId})"
                           ng-if="config.bool.enable_push_mode === false"
                           title="<?= __('Back') ?>"
                           style="border-radius: 0; height: 22px;">
                            <i class="fa fa-arrow-left"></i>
                        </a>
                        <a type="button" class="btn btn-xs btn-primary waves-effect waves-themed"
                           ui-sref="AgentconnectorsSelectAgent({'hostId': hostId})"
                           ng-if="config.bool.enable_push_mode"
                           title="<?= __('Back') ?>"
                           style="border-radius: 0; height: 22px;">
                            <i class="fa fa-arrow-left"></i>
                        </a>

                        <button type="button" class="btn btn-xs btn-success btn-block waves-effect waves-themed"
                                style="border-radius: 0;height: 22px;"
                                ng-disabled="successful"
                                ng-click="submit()">
                            <i class="fas fa-check" ng-show="successful"></i>
                            <?= __('Finish') ?>
                            <i class="fa fa-arrow-right" ng-hide="successful"></i>
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
                                                <i class="fa fa-cogs"></i>
                                                <?= __('Select Services to monitor'); ?>
                                            </h4>
                                        </legend>

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

                                        <div class="row"
                                             ng-show="connection_test && connection_test.status !== 'success'">
                                            <div class="col-12">
                                                <div
                                                    class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                    <div class="d-flex align-items-center">
                                                        <div class="alert-icon">
                                                        <span class="icon-stack icon-stack-md">
                                                            <i class="base-7 icon-stack-3x color-danger-600"></i>
                                                            <i class="fas fa-exclamation-triangle icon-stack-1x text-white"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                        <span class="h5 color-danger-600">
                                                            <?= __('Error'); ?>
                                                        </span>
                                                            <br>
                                                            {{connection_test.error}}
                                                            <div ng-show="connection_test.guzzle_error">
                                                                {{connection_test.guzzle_error}}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" ng-show="services.length === 0">
                                            <div class="col-12">
                                                <div
                                                    class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                    <div class="d-flex align-items-center">
                                                        <div class="alert-icon">
                                                            <span class="icon-stack icon-stack-md">
                                                                <i class="base-7 icon-stack-3x color-warning-600"></i>
                                                                <i class="fas fa-exclamation icon-stack-1x text-white"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <span class="h5 color-warning-600">
                                                                <?= __('There are no new services available'); ?>
                                                            </span>
                                                            <br>
                                                            <?= __('Please verify your Agent Configuration.'); ?>
                                                        </div>
                                                        <a class="btn btn-outline-warning btn-sm btn-w-m waves-effect waves-themed"
                                                           ui-sref="AgentconnectorsConfig({'hostId': hostId})">
                                                            <i class="fas fa-cogs"></i>
                                                            <?= __('Go to "Configure Agent"'); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12" ng-show="saving">
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
                                                                <?= __('Creating new services...'); ?>
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

                                        <div class="row" ng-show="successful">
                                            <div class="col-12">
                                                <div
                                                    class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                    <div class="d-flex align-items-center">
                                                        <div class="alert-icon">
                                                            <span class="icon-stack icon-stack-md">
                                                                <i class="base-7 icon-stack-3x color-success-600"></i>
                                                                <i class="fas fa-check icon-stack-1x text-white"></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <span class="h5 color-success-600">
                                                                <?= __('Services created successfully!'); ?>
                                                            </span>
                                                            <br>
                                                            <?= __('To apply the changes, please refresh your monitoring configuration.'); ?>
                                                        </div>
                                                        <?php if ($this->Acl->hasPermission('index', 'exports')): ?>
                                                            <a class="btn btn-outline-success btn-sm btn-w-m waves-effect waves-themed"
                                                               ui-sref="ExportsIndex">
                                                                <i class="fa fa-retweet"></i>
                                                                <?= __('Go to "Refresh monitoring configuration"'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div ng-hide="hideConfig">
                                            <div ng-show="(servicesToCreateCheckboxValues | json) != '{}'">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-gear"></i>
                                                        <?= __('System'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12"
                                                     ng-repeat="(key, value) in servicesToCreateCheckboxValues">
                                                    <div class="custom-control custom-checkbox margin-bottom-10">
                                                        <input type="checkbox"
                                                               class="custom-control-input"
                                                               id="{{key}}"
                                                               ng-model="servicesToCreateCheckboxValues[key]">
                                                        <label class="custom-control-label"
                                                               for="{{key}}">
                                                            {{services[key].name}}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div ng-show="services.disks || services.disks_free || services.disk_io">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-hard-drive"></i>
                                                        <?= __('Disk information'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="diskusage">
                                                        <?php echo __('Disk usage'); ?>
                                                        ({{lengthOf(services.disks)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="diskusage"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.disks"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.disks"
                                                            ng-model="servicesToCreateArrayIndices.disks">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="diskfree">
                                                        <?php echo __('Disk space free'); ?>
                                                        ({{lengthOf(services.disks_free)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="diskfree"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.disks_free"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.disks_free"
                                                            ng-model="servicesToCreateArrayIndices.disks_free">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="diskio">
                                                        <?php echo __('Disk IO'); ?>
                                                        ({{lengthOf(services.disk_io)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="diskio"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.disk_io"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.disk_io"
                                                            ng-model="servicesToCreateArrayIndices.disk_io">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div ng-show="services.net_stats || services.net_io">

                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-network-wired"></i>
                                                        <?= __('Networking'); ?>
                                                    </span>
                                                </div>

                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="net_stats">
                                                        <?= __('Link status'); ?>
                                                        ({{lengthOf(services.net_stats)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="net_stats"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.net_stats"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.net_stats"
                                                            ng-model="servicesToCreateArrayIndices.net_stats">
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="net_io">
                                                        <?php echo __('Network IO'); ?>
                                                        ({{lengthOf(services.net_io)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="net_io"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.net_io"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.net_io"
                                                            ng-model="servicesToCreateArrayIndices.net_io">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                ng-show="services.docker_running || services.docker_cpu || services.docker_memory">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-brands fa-docker"></i>
                                                        <?= __('Docker'); ?>
                                                    </span>
                                                </div>

                                                <div class="form-group col-12 padding-left-0"
                                                     ng-show="services.docker_running">
                                                    <label class="col-12 control-label"
                                                           for="docker_running">
                                                        <?php echo __('Docker status'); ?>
                                                        ({{lengthOf(services.docker_running)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="docker_running"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.docker_running"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.docker_running"
                                                            ng-model="servicesToCreateArrayIndices.docker_running">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group col-12 padding-left-0"
                                                     ng-show="services.docker_cpu">
                                                    <label class="col-12 control-label"
                                                           for="docker_cpu">
                                                        <?php echo __('Container CPU percentage'); ?>
                                                        ({{lengthOf(services.docker_cpu)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="docker_cpu"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.docker_cpu"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.docker_cpu"
                                                            ng-model="servicesToCreateArrayIndices.docker_cpu">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group col-12 padding-left-0"
                                                     ng-show="services.docker_memory">
                                                    <label class="col-12 control-label"
                                                           for="docker_memory">
                                                        <?php echo __('Container Memory usage'); ?>
                                                        ({{lengthOf(services.docker_memory)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="docker_memory"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.docker_memory"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.docker_memory"
                                                            ng-model="servicesToCreateArrayIndices.docker_memory">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div ng-show="services.processes">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-gears"></i>
                                                        <?= __('Running processes'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="processes">
                                                        <?php echo __('Processes'); ?>
                                                        ({{lengthOf(services.processes)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="processes"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.processes"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.processes"
                                                            ng-model="servicesToCreateArrayIndices.processes">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div ng-show="services.windows_services">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-brands fa-windows"></i>
                                                        <?= __('Windows'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="windows_services">
                                                        <?php echo __('Windows services'); ?>
                                                        ({{lengthOf(services.windows_services)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="windows_services"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.windows_services"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.windows_services"
                                                            ng-model="servicesToCreateArrayIndices.windows_services">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="windows_services">
                                                        <?php echo __('Windows event logs'); ?>
                                                        ({{lengthOf(services.windows_eventlog)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="windows_eventlog"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.windows_eventlog"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.windows_eventlog"
                                                            ng-model="servicesToCreateArrayIndices.windows_eventlog">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div ng-show="services.launchd_services">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-brands fa-apple"></i>
                                                        <?= __('macOS'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="launchd_services">
                                                        <?php echo __('Launchd services'); ?>
                                                        ({{lengthOf(services.launchd_services)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="launchd_services"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.launchd_services"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.launchd_services"
                                                            ng-model="servicesToCreateArrayIndices.launchd_services">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div ng-show="services.systemd_services">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-brands fa-linux"></i>
                                                        <?= __('Linux'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="systemd_services">
                                                        <?php echo __('Systemd services'); ?>
                                                        ({{lengthOf(services.systemd_services)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="systemd_services"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.systemd_services"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.systemd_services"
                                                            ng-model="servicesToCreateArrayIndices.systemd_services">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div ng-show="services.sensors">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-temperature-half"></i>
                                                        <?= __('Sensors'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="systemd_services">
                                                        <?php echo __('Sensors'); ?>
                                                        ({{lengthOf(services.sensors)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="sensors"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.sensors"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.sensors"
                                                            ng-model="servicesToCreateArrayIndices.sensors">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div ng-show="services.libvirt">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-cloud"></i>
                                                        <?= __('Libvirt'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="libvirt">
                                                        <?php echo __('Libvirt (KVM)'); ?>
                                                        ({{lengthOf(services.libvirt)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="libvirt"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.libvirt"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.libvirt"
                                                            ng-model="servicesToCreateArrayIndices.libvirt">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div ng-show="services.customchecks">
                                                <div class="hr-separator padding-top-10">
                                                    <span class="h5">
                                                        <i class="fa-solid fa-terminal"></i>
                                                        <?= __('Custom checks'); ?>
                                                    </span>
                                                </div>
                                                <div class="form-group col-12 padding-left-0 ">
                                                    <label class="col-12 control-label"
                                                           for="customchecks">
                                                        <?php echo __('Custom checks'); ?>
                                                        ({{lengthOf(services.customchecks)}})
                                                    </label>
                                                    <div class="col-12">
                                                        <select
                                                            id="customchecks"
                                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                                            class="form-control"
                                                            multiple="multiple"
                                                            chosen="services.customchecks"
                                                            ng-options="arrayIndex as service.name for (arrayIndex, service) in services.customchecks"
                                                            ng-model="servicesToCreateArrayIndices.customchecks">
                                                        </select>
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
