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
                <div class="col-xs-12 col-md-4 col-lg-2 bg-primary text-white">
                    <?= __('Install Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-light-gray">
                    <span ng-hide="config.bool.enable_push_mode">
                        <?= __('Exchange TLS Certificate') ?>
                    </span>
                    <span ng-show="config.bool.enable_push_mode">
                        <?= __('Select Agent') ?>
                    </span>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2  bg-light-gray">
                    <?= __('Create services') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 padding-left-0 padding-right-0 bg-light-gray">
                    <div class="btn-group btn-group-xs w-100">
                        <a type="button" class="btn btn-xs btn-primary waves-effect waves-themed"
                           ui-sref="AgentconnectorsConfig({'hostId': hostId})"
                           title="<?= __('Back') ?>"
                           style="border-radius: 0; height: 22px;">
                            <i class="fa fa-arrow-left"></i>
                        </a>

                        <button type="button" class="btn btn-xs btn-success btn-block waves-effect waves-themed"
                                style="border-radius: 0;height: 22px;"
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
                                                1.
                                                <?= __('Download and install openITCOCKPIT Monitoring Agent '); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="alert border-faded bg-transparent text-secondary margin-top-20">
                                                        <div class="d-flex align-items-center">
                                                            <div class="alert-icon">
                                                                        <span class="icon-stack icon-stack-md">
                                                                            <i class="base-7 icon-stack-3x color-info-600"></i>
                                                                            <i class="fas fa-download icon-stack-1x text-white"></i>
                                                                        </span>
                                                            </div>
                                                            <div class="flex-1">
                                                                        <span class="h5 color-info-600">
                                                                            <?= __('Download and install the openITCOCKPIT Monitoring Agent.'); ?>
                                                                        </span>
                                                                <br>
                                                                <?= __('If not already done, please {0} the openITCOCKPIT Agent now.', '<a href="https://openitcockpit.io/download_agent" target="_blank">' . __('download and install') . '</a>'); ?>
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
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                2.
                                                <?= __('Configuration file/s for the openITCOCKPIT Monitoring Agent'); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <?= __('After the installation process is completed you should replace the default openITCOCKPIT Agent configuration with the recently generated configuration.'); ?>
                                            <br>
                                            <?= __('Copy and paste the shown configuration file to'); ?>
                                            <code ng-show="config.string.operating_system === 'windows'"><?= __('C:\Program Files\it-novum\openitcockpit-agent\config.ini'); ?></code>
                                            <code ng-show="config.string.operating_system === 'linux'"><?= __('/etc/openitcockpit-agent/config.ini'); ?></code>
                                            <code ng-show="config.string.operating_system === 'macos'"><?= __('/Applications/openitcockpit-agent/config.ini'); ?></code>

                                            <textarea class="form-control code-font margin-top-10" readonly="" ng-model="config_as_ini" style="min-height: 580px; width: 100%;"></textarea>

                                        </div>

                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                3.
                                                <?= __(' Restart openITCOCKPIT Agent '); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <div class="col-12 padding-bottom-10">
                                                <?= __('To enable the new configuration a restart of the openITCOCKPIT Agent is required.'); ?>
                                            </div>

                                            <div class="col-12" ng-show="config.string.operating_system === 'windows'">
                                                <?= __('Run as administrator (via cmd.exe)'); ?>
                                                <code><?= __('sc stop openITCOCKPITAgent && sc start openITCOCKPITAgent'); ?></code>
                                            </div>

                                            <div class="col-12" ng-show="config.string.operating_system === 'linux'">
                                                <code><?= __('sudo systemctl restart openitcockpit-agent.service'); ?></code>
                                            </div>

                                            <div class="col-12" ng-show="config.string.operating_system === 'macos'">
                                                <code><?= __('sudo /bin/launchctl stop com.it-novum.openitcockpit.agent'); ?></code>
                                                <br>
                                                <code><?= __('sudo /bin/launchctl start com.it-novum.openitcockpit.agent'); ?></code>
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
