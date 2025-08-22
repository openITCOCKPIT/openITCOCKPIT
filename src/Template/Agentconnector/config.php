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
            <i class="fa fa-user-secret"></i> <?php echo __('openITCOCKPIT Agent '); ?>
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
                <div class="col-xs-12 col-md-4 col-lg-2 bg-primary text-white">
                    <?= __('Configure Agent') ?>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-2 bg-light-gray">
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
                           ui-sref="AgentconnectorsWizard({'hostId': hostId})"
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
                                                <i class="fa fa-cogs"></i>
                                                <?= __('Which operation system do you want to monitor?'); ?>
                                            </h4>
                                        </legend>
                                        <div>
                                            <div class="btn-group btn-group-lg">
                                                <button type="button"
                                                        class="btn btn-outline-primary waves-effect waves-themed"
                                                        ng-class="{'btn-primary text-white': config.string.operating_system === 'windows'}"
                                                        ng-click="changeOs('windows')">
                                                    <i class="fab fa-windows font-size-90"></i>
                                                    <br>
                                                    <?= __('Windows'); ?>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-primary waves-effect waves-themed"
                                                        ng-class="{'btn-primary text-white': config.string.operating_system === 'linux'}"
                                                        ng-click="changeOs('linux')">
                                                    <i class="fab fa-linux font-size-90"></i>
                                                    <br>
                                                    <?= __('Linux'); ?>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-primary waves-effect waves-themed"
                                                        ng-class="{'btn-primary text-white': config.string.operating_system === 'macos'}"
                                                        ng-click="changeOs('macos')">
                                                    <i class="fab fa-apple font-size-90"></i>
                                                    <br>
                                                    <?= __('macOS'); ?>
                                                </button>
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
                                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                                <?= __('Basic configuration'); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <div class="form-group col-12 padding-left-0 ">
                                                <label class="col-12 control-label"
                                                       for="enable_push_mode">
                                                    <?php echo __('Operation mode'); ?>
                                                </label>
                                                <div class="col-12">
                                                    <select
                                                        id="enable_push_mode"
                                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                                        class="form-control"
                                                        chosen="{}"
                                                        ng-model="config.bool.enable_push_mode">
                                                        <option ng-value="false"><?= __('Pull mode'); ?></option>
                                                        <option ng-value="true"><?= __('Push mode'); ?></option>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-class="{'has-error': errors.bind_address}">
                                                <label class="col-12 control-label"
                                                       for="bind_address">
                                                    <?php echo __('Agent bind address'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="bind_address"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="<?php echo __('0.0.0.0'); ?>"
                                                        ng-model="config.string.bind_address">

                                                    <div class="help-block">
                                                        <?= __('IP address that openITCOCKPIT Agent should bind to.'); ?>
                                                        <?= __('Set 0.0.0.0 to bind to all interfaces.'); ?>
                                                    </div>
                                                    <div ng-repeat="error in errors.bind_address">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-class="{'has-error': errors.bind_port}">
                                                <label class="col-12 control-label"
                                                       for="bind_port">
                                                    <?php echo __('Agent bind port'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="bind_port"
                                                        class="form-control"
                                                        type="number"
                                                        min="1"
                                                        max="65536"
                                                        placeholder="3333"
                                                        ng-model="config.int.bind_port">

                                                    <div class="help-block">
                                                        <?= __('Port number that openITCOCKPIT Agent should bind to.'); ?>
                                                        <?= __('Default: 3333'); ?>
                                                    </div>
                                                    <div ng-repeat="error in errors.bind_port">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-class="{'has-error': errors.check_interval}">
                                                <label class="col-12 control-label"
                                                       for="check_interval">
                                                    <?php echo __('Check interval'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="check_interval"
                                                        class="form-control"
                                                        type="number"
                                                        min="1"
                                                        max="7200"
                                                        placeholder="30"
                                                        ng-model="config.int.check_interval">

                                                    <div class="help-block">
                                                        <?= __('Determines in seconds how often the openITCOCKPIT Agent will execute all checks.'); ?>
                                                        <?= __('Default: 30'); ?>
                                                    </div>
                                                    <div ng-repeat="error in errors.check_interval">
                                                        <div class="help-block text-danger">{{ error }}</div>
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

                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" ng-show="config.bool.enable_push_mode">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <?= __('Push mode configuration'); ?>
                                            </h4>
                                        </legend>
                                        <div>
                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-if="host.satellite_id == 0"
                                                 ng-class="{'has-error': errors.push_oitc_server_url}">
                                                <!-- Host is on the master -->
                                                <label class="col-12 control-label"
                                                       for="push_oitc_server_url">
                                                    <?php echo __('openITCOCKPIT Server Address'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="push_oitc_server_url"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="https://<?= $_SERVER['SERVER_ADDR']; ?>"
                                                        ng-model="config.string.push_oitc_server_url">
                                                    <div class="help-block">
                                                        <?= __('External address of your openITCOCKPIT Server.'); ?>
                                                    </div>
                                                    <div ng-repeat="error in errors.push_oitc_server_url">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-if="host.satellite_id > 0"
                                                 ng-class="{'has-error': errors.push_oitc_server_url}">
                                                <!-- Host is on a Satellite -->
                                                <label class="col-12 control-label"
                                                       for="push_oitc_server_url">
                                                    <?php echo __('openITCOCKPIT Satellite Server Address'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="push_oitc_server_url"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="https://{{satellite.address}}"
                                                        ng-model="config.string.push_oitc_server_url">
                                                    <div class="help-block">
                                                        <?= __('External address of your openITCOCKPIT Satellite Server.'); ?>
                                                    </div>
                                                    <div ng-repeat="error in errors.push_oitc_server_url">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-class="{'has-error': errors.push_oitc_api_key}">
                                                <label class="col-12 control-label"
                                                       for="push_oitc_api_key">
                                                    <?php echo __('openITCOCKPIT API Key'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="push_oitc_api_key"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="b803b7fb76524e1514bed81cf3a936845cc160511a1c0d51672c..."
                                                        ng-model="config.string.push_oitc_api_key">
                                                    <div class="help-block">
                                                        <?php echo __('You need to create an openITCOCKPIT user defined API key first.'); ?>
                                                        <a href="javascript:void(0);"
                                                           data-toggle="modal"
                                                           data-target="#ApiKeyOverviewModal">
                                                            <?= __('Click here for help') ?>
                                                        </a>
                                                    </div>
                                                    <div ng-repeat="error in errors.push_oitc_api_key">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-12 padding-left-0">
                                                <label class="col-12 control-label"
                                                       for="push_proxy_address">
                                                    <?php echo __('HTTP Proxy'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="push_proxy_address"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="http://proxy.example.org:3128"
                                                        ng-model="config.string.push_proxy_address">
                                                    <div class="help-block">
                                                        <?= __('HTTP Proxy that should be used by the agent. Leave blank for no proxy.'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="push_verify_server_certificate"
                                                           ng-model="config.bool.push_verify_server_certificate">
                                                    <label class="custom-control-label"
                                                           for="push_verify_server_certificate">
                                                        <?php echo __('Verify server certificate'); ?>
                                                    </label>
                                                    <div class="help-block">
                                                        <?php echo __('Require valid TLS certificates (e.g. Let\'s Encrypt)'); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-class="{'has-error': errors.push_timeout}">
                                                <label class="col-12 control-label"
                                                       for="push_timeout">
                                                    <?php echo __('Push timeout'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="push_timeout"
                                                        class="form-control"
                                                        type="number"
                                                        min="1"
                                                        max="40"
                                                        placeholder="1"
                                                        ng-model="config.int.push_timeout">
                                                    <div class="help-block">
                                                        <?php echo __('HTTP timeout in seconds'); ?>
                                                    </div>
                                                    <div ng-repeat="error in errors.push_timeout">
                                                        <div class="help-block text-danger">{{ error }}</div>
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
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" ng-hide="config.bool.enable_push_mode">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                <i class="fas fa-cloud-download-alt"></i>
                                                <?= __('Pull mode configuration'); ?>
                                            </h4>
                                        </legend>
                                        <div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="use_proxy"
                                                           ng-model="config.bool.use_proxy">
                                                    <label class="custom-control-label"
                                                           for="use_proxy">
                                                        <?php echo __('Use Proxy'); ?>
                                                    </label>
                                                    <div class="help-block margin-0">
                                                        <?php
                                                        if ($this->Acl->hasPermission('index', 'proxy', '')):
                                                            echo __('Determine if the <a href="/#!/proxy/index">configured proxy</a> should be used.');
                                                        else:
                                                            echo __('Determine if the configured proxy should be used.');
                                                        endif;
                                                        ?>
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
                                                <i class="fas fa-shield-alt"></i>
                                                <?= __('Security configuration'); ?>
                                            </h4>
                                        </legend>
                                        <div>

                                            <!-- Connection type in PULL mode -->
                                            <div class="form-group col-12 padding-left-0 "
                                                 ng-show="config.bool.enable_push_mode === false">
                                                <label class="col-12 control-label"
                                                       for="connection_type">
                                                    <?php echo __('Connection type'); ?>
                                                </label>
                                                <div class="col-12">
                                                    <select
                                                        id="connection_type"
                                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                                        class="form-control"
                                                        chosen="{}"
                                                        ng-model="connection_type">
                                                        <option value="autotls"><?= __('Auto-TLS'); ?></option>
                                                        <option value="https"><?= __('HTTPS'); ?></option>
                                                        <option value="http"><?= __('HTTP (plaintext)'); ?></option>
                                                    </select>
                                                    <div class="help-block" ng-show="connection_type === 'autotls'">
                                                        <?= __('openITCOCKPIT automatically generate a TLS certificate for authentication and encryption purpose. Auto-TLS is only available in Pull mode.'); ?>
                                                    </div>
                                                    <div class="help-block" ng-show="connection_type === 'https'">
                                                        <?= __('Start the Agents web server with own TLS certificates from Let\'s Encrypt for example.'); ?>
                                                    </div>
                                                    <div class="help-block text-danger"
                                                         ng-show="connection_type === 'https'">
                                                        <?= __('It\'s recommended to also enable HTTP Basic Authentication to restrict the access to the agent.'); ?>
                                                    </div>
                                                    <div class="help-block text-danger"
                                                         ng-show="connection_type === 'http'">
                                                        <?= __('Communication will be plaintext. No encryption and authentication. Not recommended!'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End connection type -->

                                            <!-- Webserver settings in PUSH mode -->

                                            <div class="form-group col-12" ng-show="config.bool.enable_push_mode">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="push_enable_webserver"
                                                           ng-model="config.bool.push_enable_webserver">
                                                    <label class="custom-control-label"
                                                           for="push_enable_webserver">
                                                        <?php echo __('Enable Webserver'); ?>
                                                    </label>
                                                    <div class="help-block">
                                                        <?php echo __('By default the web server of the Agent is disabled when running in Push mode.'); ?>
                                                    </div>
                                                    <div class="help-block text-danger">
                                                        <?= __('It is highly recommended to enable at least HTTP Basic Authentication!'); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 "
                                                 ng-show="config.bool.enable_push_mode === true">
                                                <label class="col-12 control-label"
                                                       for="webserver_type">
                                                    <?php echo __('Web server type'); ?>
                                                </label>
                                                <div class="col-12">
                                                    <select
                                                        id="webserver_type"
                                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                                        class="form-control"
                                                        ng-disabled="!config.bool.push_enable_webserver"
                                                        chosen="{}"
                                                        ng-model="webserver_type">
                                                        <option value="https"><?= __('HTTPS'); ?></option>
                                                        <option value="http"><?= __('HTTP (plaintext)'); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- End Webserver settings -->
                                            <div class="form-group col-12" ng-show="connection_type === 'https'">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="use_https_verify"
                                                           ng-model="config.bool.use_https_verify">
                                                    <label class="custom-control-label"
                                                           for="use_https_verify">
                                                        <?php echo __('Verify certificate'); ?>
                                                    </label>
                                                    <div class="help-block">
                                                        <?php echo __('Enabled TLS certificate validation. This requires valid certificates like from Let\'s Encrypt'); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-show="(connection_type === 'https' && !config.bool.enable_push_mode) || (webserver_type === 'https' && config.bool.enable_push_mode)"
                                                 ng-class="{'has-error': errors.ssl_certfile}">
                                                <label class="col-12 control-label"
                                                       for="ssl_certfile">
                                                    <?php echo __('TLS certificate file'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        ng-disabled="(config.bool.enable_push_mode === true && config.bool.push_enable_webserver === false )"
                                                        id="ssl_certfile"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="<?= __('/etc/ssl/acme.sh/example.org/cert.pem'); ?>"
                                                        ng-model="config.string.ssl_certfile">
                                                    <div ng-repeat="error in errors.ssl_certfile">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                    <div class="help-block">
                                                        <?php echo __('Full path to certificate file the Agent should use'); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0 required"
                                                 ng-show="(connection_type === 'https' && !config.bool.enable_push_mode) || (webserver_type === 'https' && config.bool.enable_push_mode)"
                                                 ng-class="{'has-error': errors.ssl_keyfile}">
                                                <label class="col-12 control-label"
                                                       for="ssl_keyfile">
                                                    <?php echo __('TLS key file'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        ng-disabled="(config.bool.enable_push_mode === true && config.bool.push_enable_webserver === false )"
                                                        id="ssl_keyfile"
                                                        class="form-control"
                                                        type="text"
                                                        placeholder="<?= __('/etc/ssl/acme.sh/example.org/privkey.pem'); ?>"
                                                        ng-model="config.string.ssl_keyfile">
                                                    <div ng-repeat="error in errors.ssl_keyfile">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                    <div class="help-block">
                                                        <?php echo __('Full path to key file the Agent should use'); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="use_http_basic_auth"
                                                           ng-model="config.bool.use_http_basic_auth">
                                                    <label class="custom-control-label"
                                                           for="use_http_basic_auth">
                                                        <?php echo __('Enable HTTP Basic Authentication'); ?>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0"
                                                 ng-class="{'required': config.bool.use_http_basic_auth, 'has-error': errors.username}">
                                                <label class="col-12 control-label"
                                                       for="username">
                                                    <?php echo __('Username'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="username"
                                                        class="form-control"
                                                        ng-disabled="!config.bool.use_http_basic_auth"
                                                        type="text"
                                                        placeholder="<?= __('Username'); ?>"
                                                        ng-model="config.string.username">
                                                    <div ng-repeat="error in errors.username">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-12 padding-left-0"
                                                 ng-class="{'required': config.bool.use_http_basic_auth, 'has-error': errors.password}">
                                                <label class="col-12 control-label"
                                                       for="password">
                                                    <?php echo __('Password'); ?>
                                                </label>

                                                <div class="col-12">
                                                    <input
                                                        id="password"
                                                        class="form-control"
                                                        ng-disabled="!config.bool.use_http_basic_auth"
                                                        type="password"
                                                        placeholder="<?= __('Password'); ?>"
                                                        ng-model="config.string.password">
                                                    <div ng-repeat="error in errors.password">
                                                        <div class="help-block text-danger">{{ error }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="enable_remote_config_update"
                                                           ng-model="config.bool.enable_remote_config_update">
                                                    <label class="custom-control-label"
                                                           for="enable_remote_config_update">
                                                        <?php echo __('Enable remote configuration update mode'); ?>
                                                    </label>
                                                    <div class="help-block">
                                                        <?php echo __('Enables the remote agent configuration update mode.'); ?>
                                                        <br>
                                                        <?php echo __('Should only be configured after an successful TLS configuration.'); ?>
                                                        <br>
                                                        <p class="text-danger">
                                                            <?php echo __('Warning: This could lead to remote code execution!'); ?>
                                                        </p>
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
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                <i class="far fa-check-square"></i>
                                                <?= __('Enable/disable checks'); ?>
                                            </h4>
                                        </legend>
                                        <div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="cpustats"
                                                           ng-model="config.bool.cpustats">
                                                    <label class="custom-control-label"
                                                           for="cpustats">
                                                        <?php echo __('Enable CPU stats'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="memory"
                                                           ng-model="config.bool.memory">
                                                    <label class="custom-control-label"
                                                           for="memory">
                                                        <?php echo __('Enable Memory stats'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="swap"
                                                           ng-model="config.bool.swap">
                                                    <label class="custom-control-label"
                                                           for="swap">
                                                        <?php echo __('Enable Swap stats'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="processstats"
                                                           ng-model="config.bool.processstats">
                                                    <label class="custom-control-label"
                                                           for="processstats">
                                                        <?php echo __('Enable Process checks'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="netstats"
                                                           ng-model="config.bool.netstats">
                                                    <label class="custom-control-label"
                                                           for="netstats">
                                                        <?php echo __('Enable Network stats'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="netio"
                                                           ng-model="config.bool.netio">
                                                    <label class="custom-control-label"
                                                           for="netio">
                                                        <?php echo __('Enable Network I/O stats'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="diskstats"
                                                           ng-model="config.bool.diskstats">
                                                    <label class="custom-control-label"
                                                           for="diskstats">
                                                        <?php echo __('Enable Disk usage checks'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="diskio"
                                                           ng-model="config.bool.diskio">
                                                    <label class="custom-control-label"
                                                           for="diskio">
                                                        <?php echo __('Enable Disk I/O stats'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="systemdservices"
                                                           ng-model="config.bool.systemdservices">
                                                    <label class="custom-control-label"
                                                           for="systemdservices">
                                                        <?php echo __('Enable Systemd checks (only affects Linux)'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="launchdservices"
                                                           ng-model="config.bool.launchdservices">
                                                    <label class="custom-control-label"
                                                           for="launchdservices">
                                                        <?php echo __('Enable Launchd checks (only affects macOS)'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="winservices"
                                                           ng-model="config.bool.winservices">
                                                    <label class="custom-control-label"
                                                           for="winservices">
                                                        <?php echo __('Enable Windows Services checks (only affects Windows)'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="wineventlog"
                                                           ng-model="config.bool.wineventlog">
                                                    <label class="custom-control-label"
                                                           for="wineventlog">
                                                        <?php echo __('Enable Windows Event Log checks (only affects Windows)'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="sensorstats"
                                                           ng-model="config.bool.sensorstats">
                                                    <label class="custom-control-label"
                                                           for="sensorstats">
                                                        <?php echo __('Enable Sensor stats (only affects Linux and macOS)'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="dockerstats"
                                                           ng-model="config.bool.dockerstats">
                                                    <label class="custom-control-label"
                                                           for="dockerstats">
                                                        <?php echo __('Enable Docker checks'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="libvirt"
                                                           ng-model="config.bool.libvirt">
                                                    <label class="custom-control-label"
                                                           for="libvirt">
                                                        <?php echo __('Enable KVM checks through libvirt'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="userstats"
                                                           ng-model="config.bool.userstats">
                                                    <label class="custom-control-label"
                                                           for="userstats">
                                                        <?php echo __('Collect information about logged in users'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="custom-control custom-checkbox margin-bottom-10">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="ntp"
                                                           ng-model="config.bool.ntp">
                                                    <label class="custom-control-label"
                                                           for="ntp">
                                                        <?php echo __('Monitor of system clock is synchronized (NTP)'); ?>
                                                    </label>
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
<?php echo $this->element('apikey_help'); ?>
