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

use itnovum\openITCOCKPIT\ConfigGenerator\GraphingDocker;

/** @var GraphingDocker $GraphingDocker */

$timezones = \itnovum\openITCOCKPIT\Core\Timezone::listTimezones();

?>

<form ng-submit="submit();" class="form-horizontal">

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.carbon_path}">
        <label class="control-label">
            <?php echo __('Carbon storage path'); ?>
        </label>
        <input
            class="form-control"
            type="text"
            ng-model="post.string.carbon_path">
        <div ng-repeat="error in errors.Configfile.carbon_path">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('carbon_path')); ?>
        </div>
    </div>

    <div class="form-group" ng-class="{'has-error': errors.Configfile.WHISPER_FALLOCATE_CREATE}">
        <div class="custom-control custom-checkbox  margin-bottom-10"
             ng-class="{'has-error': errors.Configfile.WHISPER_FALLOCATE_CREATE}">

            <input type="checkbox"
                   class="custom-control-input"
                   ng-true-value="1"
                   ng-false-value="0"
                   id="WHISPER_FALLOCATE_CREATE"
                   ng-model="post.bool.WHISPER_FALLOCATE_CREATE">
            <label class="custom-control-label" for="WHISPER_FALLOCATE_CREATE">
                WHISPER_FALLOCATE_CREATE
            </label>
        </div>

        <div class="col col-xs-12 col-md-offset-2 help-block">
            <?php echo h($GraphingDocker->getHelpText('WHISPER_FALLOCATE_CREATE')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.default_average_x_files_factor}">
        <label class="control-label">
            <?php echo __('Default average xFilesFactor'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="0"
            max="1"
            step="any"
            ng-model="post.float.default_average_x_files_factor">
        <div ng-repeat="error in errors.Configfile.default_average_x_files_factor">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('default_average_x_files_factor')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.victoria_metrics_storage_path}">
        <label class="control-label">
            <?php echo __('VictoriaMetrics storage path'); ?>
        </label>
        <input
            class="form-control"
            type="text"
            ng-model="post.string.victoria_metrics_storage_path">
        <div ng-repeat="error in errors.Configfile.victoria_metrics_storage_path">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('victoria_metrics_storage_path')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.number_of_carbon_cache_instances}">
        <label class="control-label">
            <?php echo __('Carbon Cache instances'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="1"
            ng-model="post.int.number_of_carbon_cache_instances">
        <div ng-repeat="error in errors.Configfile.number_of_carbon_cache_instances">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('number_of_carbon_cache_instances')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.number_of_carbon_c_relay_workers}">
        <label class="control-label">
            <?php echo __('Carbon C Relay workers'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="1"
            ng-model="post.int.number_of_carbon_c_relay_workers">
        <div ng-repeat="error in errors.Configfile.number_of_carbon_c_relay_workers">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('number_of_carbon_c_relay_workers')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.carbon_storage_schema}">
        <label class="control-label">
            <?php echo __('Carbon storage schema'); ?>
        </label>
        <input
            class="form-control"
            type="text"
            ng-model="post.string.carbon_storage_schema">
        <div ng-repeat="error in errors.Configfile.carbon_storage_schema">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('carbon_storage_schema')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.victoria_metrics_retention_period}">
        <label class="control-label">
            <?php echo __('VictoriaMetrics retention period'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="1"
            max="120"
            ng-model="post.int.victoria_metrics_retention_period">
        <div ng-repeat="error in errors.Configfile.victoria_metrics_retention_period">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('victoria_metrics_retention_period')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.local_graphite_http_port}">
        <label class="control-label">
            <?php echo __('Local Graphite-Web port'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="1"
            ng-model="post.int.local_graphite_http_port">
        <div ng-repeat="error in errors.Configfile.local_graphite_http_port">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('local_graphite_http_port')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.timezone}">
        <label class="control-label" for="timezone">
            <?php echo __('Graphite-Web timezone'); ?>
        </label>
        <select
            id="timezone"
            data-placeholder="<?php echo __('Please choose'); ?>"
            class="form-control"
            chosen="{}"
            ng-init="post.string.timezone = post.string.timezone || 'Europe/Berlin'"
            ng-model="post.string.timezone">
            <?php foreach ($timezones as $continent => $continentTimezons): ?>
                <optgroup label="<?php echo h($continent); ?>">
                    <?php foreach ($continentTimezons as $timezoneKey => $timezoneName): ?>
                        <option value="<?php echo h($timezoneKey); ?>"><?php echo h($timezoneName); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach;; ?>
        </select>
        <div ng-repeat="error in errors.Configfile.timezone">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('timezone')); ?>
            <br/>
            <?php echo __('Server timezone is:'); ?>
            <strong>
                <?php echo h(date_default_timezone_get()); ?>
            </strong>
            <?php echo __('Current server time:'); ?>
            <strong>
                <?php echo date('d.m.Y H:i:s'); ?>
            </strong>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.local_graphite_plaintext_port}">
        <label class="control-label">
            <?php echo __('Local Carbon C Relay port'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="1"
            ng-model="post.int.local_graphite_plaintext_port">
        <div ng-repeat="error in errors.Configfile.local_graphite_plaintext_port">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('local_graphite_plaintext_port')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.local_victoria_metrics_http_port}">
        <label class="control-label">
            <?php echo __('VictoriaMetrics retention period'); ?>
        </label>
        <input
            class="form-control"
            type="number"
            min="1"
            ng-model="post.int.local_victoria_metrics_http_port">
        <div ng-repeat="error in errors.Configfile.local_victoria_metrics_http_port">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('local_victoria_metrics_http_port')); ?>
        </div>
    </div>

    <div class="form-group" ng-class="{'has-error': errors.Configfile.enable_docker_userland_proxy}">
        <div class="custom-control custom-checkbox  margin-bottom-10"
             ng-class="{'has-error': errors.Configfile.enable_docker_userland_proxy}">

            <input type="checkbox"
                   class="custom-control-input"
                   ng-true-value="1"
                   ng-false-value="0"
                   id="enable_docker_userland_proxy"
                   ng-model="post.bool.enable_docker_userland_proxy">
            <label class="custom-control-label" for="enable_docker_userland_proxy">
                <?php echo __('Enable Docker userland proxy'); ?>
            </label>
        </div>

        <div class="col col-xs-12 col-md-offset-2 help-block">
            <?php echo h($GraphingDocker->getHelpText('enable_docker_userland_proxy')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.USE_AUTO_NETWORKING}">
        <label class="control-label" for="USE_AUTO_NETWORKING">
            <?php echo __('Network configuration'); ?>
        </label>
        <select
            id="USE_AUTO_NETWORKING"
            data-placeholder="<?php echo __('Please choose'); ?>"
            class="form-control"
            chosen="{}"
            ng-model="post.string.USE_AUTO_NETWORKING">
            <option value="1"><?php echo __('Automatically'); ?></option>
            <option value="0"><?php echo __('Manually'); ?></option>
        </select>
        <div ng-repeat="error in errors.Configfile.USE_AUTO_NETWORKING">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($GraphingDocker->getHelpText('USE_AUTO_NETWORKING')); ?>
        </div>
    </div>

    <div ng-show="post.string.USE_AUTO_NETWORKING == '0'">
        <?php
        /*
         * Maybe we implement this some day? /etc/docker/daemon.json
            <div class="form-group required" ng-class="{'has-error': errors.Configfile.bip}">
                <label class="col col-md-2 control-label">
                    <?php echo __('BIP'); ?>
                </label>
                <div class="col col-xs-10">
                    <input
                            class="form-control"
                            type="text"
                            ng-model="post.string.bip">
                    <div ng-repeat="error in errors.Configfile.bip">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>
                <div class="helpText text-muted col-md-offset-2 col-md-6">
                    <?php echo h($GraphingDocker->getHelpText('bip')); ?>
                </div>
            </div>

            <div class="form-group required" ng-class="{'has-error': errors.Configfile.fixed_cidr}">
                <label class="col col-md-2 control-label">
                    <?php echo __('Fixed CIDR'); ?>
                </label>
                <div class="col col-xs-10">
                    <input
                            class="form-control"
                            type="text"
                            ng-model="post.string.fixed_cidr">
                    <div ng-repeat="error in errors.Configfile.fixed_cidr">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>
                <div class="helpText text-muted col-md-offset-2 col-md-6">
                    <?php echo h($GraphingDocker->getHelpText('fixed_cidr')); ?>
                </div>
            </div>
            */ ?>
        <div class="form-group required" ng-class="{'has-error': errors.Configfile.docker_compose_subnet}">
            <label class="control-label">
                <?php echo __('Docker Compose subnet'); ?>
            </label>
            <input
                class="form-control"
                type="text"
                ng-model="post.string.docker_compose_subnet">
            <div ng-repeat="error in errors.Configfile.docker_compose_subnet">
                <div class="help-block text-danger">{{ error }}</div>
            </div>
            <div class="help-block">
                <?php echo h($GraphingDocker->getHelpText('docker_compose_subnet')); ?>
            </div>
        </div>
    </div>


    <div class="card margin-top-10">
        <div class="card-body">
            <div class="float-right">
                <button class="btn btn-primary"
                        type="submit"><?php echo __('Save'); ?></button>
                <a back-button href="javascript:void(0);" fallback-state='ConfigurationFilesIndex'
                   class="btn btn-default"><?php echo __('Cancel'); ?></a>
            </div>
        </div>
    </div>

</form>
