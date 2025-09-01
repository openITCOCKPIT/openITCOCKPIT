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


/** @var \itnovum\openITCOCKPIT\ConfigGenerator\NSTAMaster $NSTAMaster */
?>


<form ng-submit="submit();" class="form-horizontal">

    <div class="form-group" ng-class="{'has-error': errors.Configfile.use_nginx_proxy}">
        <div class="custom-control custom-checkbox  margin-bottom-10"
             ng-class="{'has-error': errors.Configfile.use_nginx_proxy}">

            <input type="checkbox"
                   class="custom-control-input"
                   ng-true-value="1"
                   ng-false-value="0"
                   id="use_nginx_proxy"
                   ng-model="post.bool.use_nginx_proxy">
            <label class="custom-control-label" for="use_nginx_proxy">
                <?= __('Use nginx as reverse proxy'); ?>
            </label>
        </div>

        <div class="col col-xs-12 col-md-offset-2 help-block">
            <?php echo h($NSTAMaster->getHelpText('use_nginx_proxy')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.listen_http}">
        <label class="control-label">
            <?php echo __('HTTP bind address'); ?>
        </label>
        <input
                class="form-control"
                type="text"
                ng-disabled="post.bool.use_nginx_proxy===1"
                ng-model="post.string.listen_http">
        <div ng-repeat="error in errors.Configfile.listen_http">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($NSTAMaster->getHelpText('listen_http')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.listen_https}">
        <label class="control-label">
            <?php echo __('HTTPS bind address'); ?>
        </label>
        <input
                class="form-control"
                type="text"
                ng-disabled="post.bool.use_nginx_proxy===1"
                ng-model="post.string.listen_https">
        <div ng-repeat="error in errors.Configfile.listen_https">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($NSTAMaster->getHelpText('listen_https')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.tls_key}">
        <label class="control-label">
            <?php echo __('TLS key file'); ?>
        </label>
        <input
                class="form-control"
                type="text"
                ng-disabled="post.bool.use_nginx_proxy===1"
                ng-model="post.string.tls_key">
        <div ng-repeat="error in errors.Configfile.tls_key">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($NSTAMaster->getHelpText('tls_key')); ?>
        </div>
    </div>

    <div class="form-group required" ng-class="{'has-error': errors.Configfile.tls_cert}">
        <label class="control-label">
            <?php echo __('TLS certificate file'); ?>
        </label>
        <input
                class="form-control"
                type="text"
                ng-disabled="post.bool.use_nginx_proxy===1"
                ng-model="post.string.tls_cert">
        <div ng-repeat="error in errors.Configfile.tls_cert">
            <div class="help-block text-danger">{{ error }}</div>
        </div>
        <div class="help-block">
            <?php echo h($NSTAMaster->getHelpText('tls_cert')); ?>
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
