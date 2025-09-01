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
                    <?= __('Exchange TLS Certificate') ?>
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
                                ng-disabled="disableNext"
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
                                                <i class="fas fa-certificate"></i>
                                                <?= __('Executing TLS certificate exchange'); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <div class="row">
                                                <div class="col-12" ng-show="runningCheck">
                                                    <div
                                                        class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                        <div class="d-flex align-items-center">
                                                            <div class="alert-icon">
                                                                <span class="icon-stack icon-stack-md">
                                                                    <i class="base-7 icon-stack-3x color-info-600"></i>
                                                                    <i class="fas fa-certificate icon-stack-1x text-white"></i>
                                                                </span>
                                                            </div>
                                                            <div class="flex-1">
                                                                <span class="h5 color-info-600">
                                                                    <?= __('Executing TLS certificate exchange'); ?>
                                                                </span>
                                                                <span
                                                                    ng-show="isSatellite"><?= __('(via remote satellite system)') ?></span>
                                                                <button class="btn btn-xs btn-danger pull-right"
                                                                        ng-show="isSatellite"
                                                                        ng-click="cancelSatRequest();">
                                                                    <?= __('Cancel') ?>
                                                                </button>
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

                                                <div class="col-12" ng-show="connection_test.status === 'success'">
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
                                                                    <?= __('Success'); ?>
                                                                </span>
                                                                <br>
                                                                {{connection_test.error}}
                                                                <div ng-show="connection_test.guzzle_error">
                                                                    {{connection_test.guzzle_error}}
                                                                </div>
                                                            </div>
                                                            <button
                                                                class="btn btn-outline-success btn-sm btn-w-m waves-effect waves-themed"
                                                                ng-click="submit()"
                                                                ng-disabled="disableNext">
                                                                <?= __('Next'); ?>
                                                                <i class="fa fa-arrow-right"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12" ng-show="connection_test.status === 'error'">
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

                                                            <button
                                                                class="btn btn-outline-danger btn-sm btn-w-m waves-effect waves-themed"
                                                                type="button"
                                                                ng-show="connection_test.oitc_errno === <?php echo AgentHttpClientErrors::ERRNO_HTTPS_COMPROMISED ?>"
                                                                ng-click="reExchangeAutoTLS()">
                                                                <i class="fas fa-certificate"></i>
                                                                <?= __('Reset AutoTLS certificate'); ?>
                                                            </button>

                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12" ng-show="connection_test.status === 'warning'">
                                                    <div
                                                        class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                        <div class="d-flex align-items-center">
                                                            <div class="alert-icon">
                                                                <span class="icon-stack icon-stack-md">
                                                                    <i class="base-7 icon-stack-3x color-warning-600"></i>
                                                                    <i class="fas fa-exclamation-triangle icon-stack-1x text-white"></i>
                                                            </div>
                                                            <div class="flex-1">
                                                                <span class="h5 color-warning-600">
                                                                    <?= __('Error!'); ?>
                                                                </span>
                                                                <br>
                                                                {{connection_test.error}}
                                                                <div ng-show="connection_test.guzzle_error">
                                                                    {{connection_test.guzzle_error}}
                                                                </div>
                                                            </div>
                                                            <button
                                                                class="btn btn-outline-warning btn-sm btn-w-m waves-effect waves-themed"
                                                                type="button"
                                                                ng-show="connection_test.oitc_errno === <?php echo AgentHttpClientErrors::ERRNO_AGENT_RESPONSES_TO_HTTP ?>"
                                                                ng-click="reExchangeAutoTLS()">
                                                                <i class="fas fa-user-secret"></i>
                                                                <?= __('Trust this Agent and generate a new certificate.'); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12" ng-show="connection_test.status === 'unknown'">
                                                    <div
                                                        class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                        <div class="d-flex align-items-center">
                                                            <div class="alert-icon">
                                                                <span class="icon-stack icon-stack-md">
                                                                    <i class="base-7 icon-stack-3x unknown"></i>
                                                                    <i class="fas fa-times icon-stack-1x text-white"></i>
                                                            </div>
                                                            <div class="flex-1">
                                                                <span class="h5 unknown">
                                                                    <?= __('Unknown error'); ?>
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


                                                <div class="col-12" ng-show="hasSatelliteError">
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
                                                                {{satelliteErrorMsg}}
                                                            </div>
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
